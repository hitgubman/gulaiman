<?php

namespace Dompdf;

use DOMDocument;
use DOMNode;
use Dompdf\Adapter\CPDF;
use DOMXPath;
use Dompdf\Frame\Factory;
use Dompdf\Frame\FrameTree;
use Dompdf\Image\Cache;
use Dompdf\Css\Stylesheet;
use Dompdf\Helpers;
use Masterminds\HTML5;


class Dompdf
{
    
    private $version = 'dompdf';

    
    private $dom;

    
    private $tree;

    
    private $css;

    
    private $canvas;

    
    private $paperSize;

    
    private $paperOrientation = "portrait";

    
    private $callbacks = [];

    
    private $cacheId;

    
    private $baseHost = "";

    
    private $basePath = "";

    
    private $protocol = "";

    
    private $systemLocale = null;

    
    private $mbstringEncoding = null;

    
    private $pcreJit = null;

    
    private $defaultView = "Fit";

    
    private $defaultViewOptions = [];

    
    private $quirksmode = false;

    
    private $allowedLocalFileExtensions = ["htm", "html"];

    
    private $messages = [];

    
    private $options;

    
    private $fontMetrics;

    
    public static $native_fonts = [
        "courier", "courier-bold", "courier-oblique", "courier-boldoblique",
        "helvetica", "helvetica-bold", "helvetica-oblique", "helvetica-boldoblique",
        "times-roman", "times-bold", "times-italic", "times-bolditalic",
        "symbol", "zapfdinbats"
    ];

    
    public static $nativeFonts = [
        "courier", "courier-bold", "courier-oblique", "courier-boldoblique",
        "helvetica", "helvetica-bold", "helvetica-oblique", "helvetica-boldoblique",
        "times-roman", "times-bold", "times-italic", "times-bolditalic",
        "symbol", "zapfdinbats"
    ];

    
    public function __construct($options = null)
    {
        if (isset($options) && $options instanceof Options) {
            $this->setOptions($options);
        } elseif (is_array($options)) {
            $this->setOptions(new Options($options));
        } else {
            $this->setOptions(new Options());
        }

        $versionFile = realpath(__DIR__ . '/../VERSION');
        if (($version = file_get_contents($versionFile)) !== false) {
            $version = trim($version);
            if ($version !== '$Format:<%h>$') {
                $this->version = sprintf('dompdf %s', $version);
            }
        }

        $this->setPhpConfig();

        $this->paperSize = $this->options->getDefaultPaperSize();
        $this->paperOrientation = $this->options->getDefaultPaperOrientation();

        $this->canvas = CanvasFactory::get_instance($this, $this->paperSize, $this->paperOrientation);
        $this->fontMetrics = new FontMetrics($this->canvas, $this->options);
        $this->css = new Stylesheet($this);

        $this->restorePhpConfig();
    }

    
    private function setPhpConfig()
    {
        if (sprintf('%.1f', 1.0) !== '1.0') {
            $this->systemLocale = setlocale(LC_NUMERIC, "0");
            setlocale(LC_NUMERIC, "C");
        }

        $this->pcreJit = @ini_get('pcre.jit');
        @ini_set('pcre.jit', '0');

        $this->mbstringEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
    }

    
    private function restorePhpConfig()
    {
        if ($this->systemLocale !== null) {
            setlocale(LC_NUMERIC, $this->systemLocale);
            $this->systemLocale = null;
        }

        if ($this->pcreJit !== null) {
            @ini_set('pcre.jit', $this->pcreJit);
            $this->pcreJit = null;
        }

        if ($this->mbstringEncoding !== null) {
            mb_internal_encoding($this->mbstringEncoding);
            $this->mbstringEncoding = null;
        }
    }

    
    public function load_html_file($file)
    {
        $this->loadHtmlFile($file);
    }

    
    public function loadHtmlFile($file, $encoding = null)
    {
        $this->setPhpConfig();

        if (!$this->protocol && !$this->baseHost && !$this->basePath) {
            [$this->protocol, $this->baseHost, $this->basePath] = Helpers::explode_url($file);
        }
        $protocol = strtolower($this->protocol);
        $uri = Helpers::build_url($this->protocol, $this->baseHost, $this->basePath, $file, $this->options->getChroot());

        $allowed_protocols = $this->options->getAllowedProtocols();
        if (!array_key_exists($protocol, $allowed_protocols)) {
            throw new Exception("Permission denied on $file. The communication protocol is not supported.");
        }

        if ($protocol === "file://") {
            $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedLocalFileExtensions)) {
                throw new Exception("Permission denied on $file: The file extension is forbidden.");
            }
        }

        foreach ($allowed_protocols[$protocol]["rules"] as $rule) {
            [$result, $message] = $rule($uri);
            if (!$result) {
                throw new Exception("Error loading $file: $message");
            }
        }

        [$contents, $http_response_header] = Helpers::getFileContent($uri, $this->options->getHttpContext());
        if ($contents === null) {
            throw new Exception("File '$file' not found.");
        }

        
        if (isset($http_response_header)) {
            foreach ($http_response_header as $_header) {
                if (preg_match("@Content-Type:\s*[\w/]+;\s*?charset=([^\s]+)@i", $_header, $matches)) {
                    $encoding = strtoupper($matches[1]);
                    break;
                }
            }
        }

        $this->restorePhpConfig();

        $this->loadHtml($contents, $encoding);
    }

    
    public function load_html($str, $encoding = null)
    {
        $this->loadHtml($str, $encoding);
    }

    
    public function loadDOM($doc, $quirksmode = false)
    {
        
        $tag_names = ["html", "head", "table", "tbody", "thead", "tfoot", "tr"];
        foreach ($tag_names as $tag_name) {
            $nodes = $doc->getElementsByTagName($tag_name);

            foreach ($nodes as $node) {
                self::removeTextNodes($node);
            }
        }

        $this->dom = $doc;
        $this->quirksmode = $quirksmode;
        $this->tree = new FrameTree($this->dom);
    }

    
    public function loadHtml($str, $encoding = null)
    {
        $this->setPhpConfig();

        
        
        
        if (strncmp($str, "\xFE\xFF", 2) === 0) {
            $str = substr($str, 2);
            $encoding = "UTF-16BE";
        } elseif (strncmp($str, "\xFF\xFE", 2) === 0) {
            $str = substr($str, 2);
            $encoding = "UTF-16LE";
        } elseif (strncmp($str, "\xEF\xBB\xBF", 3) === 0) {
            $str = substr($str, 3);
            $encoding = "UTF-8";
        }

        
        $encodingGiven = $encoding !== null && $encoding !== "";

        if ($encodingGiven && !in_array(strtoupper($encoding), ["UTF-8", "UTF8"], true)) {
            $converted = mb_convert_encoding($str, "UTF-8", $encoding);

            if ($converted !== false) {
                $str = $converted;
            }
        }

        
        $charset = "(?<charset>[a-z0-9\-]+)";
        $contentType = "http-equiv\s*=\s* ([\"']?)\s* Content-Type";
        $contentStart = "content\s*=\s* ([\"']?)\s* [\w\/]+ \s*;\s* charset\s*=\s*";
        $metaTags = [
            "/<meta \s[^>]* $contentType \s*\g1\s* $contentStart $charset \s*\g2 [^>]*>/isx", 
            "/<meta \s[^>]* $contentStart $charset \s*\g1\s* $contentType \s*\g3 [^>]*>/isx", 
            "/<meta \s[^>]* charset\s*=\s* ([\"']?)\s* $charset \s*\g1 [^>]*>/isx",           
        ];

        foreach ($metaTags as $pattern) {
            if (preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE)) {
                [$documentEncoding, $offset] = $matches["charset"];
                break;
            }
        }

        
        
        
        
        if (isset($documentEncoding) && isset($offset)) {
            if (!in_array(strtoupper($documentEncoding), ["UTF-8", "UTF8"], true)) {
                $str = substr($str, 0, $offset) . "UTF-8" . substr($str, $offset + strlen($documentEncoding));
            }
        } elseif (($headPos = stripos($str, "<head>")) !== false) {
            $str = substr($str, 0, $headPos + 6) . '<meta charset="UTF-8">' . substr($str, $headPos + 6);
        } else {
            $str = '<meta charset="UTF-8">' . $str;
        }

        
        
        $fallbackEncoding = $documentEncoding ?? "auto";

        if (!$encodingGiven && !in_array(strtoupper($fallbackEncoding), ["UTF-8", "UTF8"], true)) {
            $converted = mb_convert_encoding($str, "UTF-8", $fallbackEncoding);

            if ($converted !== false) {
                $str = $converted;
            }
        }

        
        set_error_handler([Helpers::class, "record_warnings"]);

        try {
            
            
            
            $quirksmode = false;

            $html5 = new HTML5(["encoding" => "UTF-8", "disable_html_ns" => true]);
            $dom = $html5->loadHTML($str);

            
            
            $doc = new DOMDocument("1.0", "UTF-8");
            $doc->preserveWhiteSpace = true;
            $doc->loadHTML($html5->saveHTML($dom), LIBXML_NOWARNING | LIBXML_NOERROR);

            $this->loadDOM($doc, $quirksmode);
        } finally {
            restore_error_handler();
            $this->restorePhpConfig();
        }
    }

    
    public static function remove_text_nodes(DOMNode $node)
    {
        self::removeTextNodes($node);
    }

    
    public static function removeTextNodes(DOMNode $node)
    {
        $children = [];
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);
            if ($child->nodeName === "#text") {
                $children[] = $child;
            }
        }

        foreach ($children as $child) {
            $node->removeChild($child);
        }
    }

    
    private function processHtml()
    {
        $this->tree->build_tree();

        $this->css->load_css_file($this->css->getDefaultStylesheet(), Stylesheet::ORIG_UA);

        $acceptedmedia = Stylesheet::$ACCEPTED_GENERIC_MEDIA_TYPES;
        $acceptedmedia[] = $this->options->getDefaultMediaType();

        
        
        $baseNode = $this->dom->getElementsByTagName("base")->item(0);
        $baseHref = $baseNode ? $baseNode->getAttribute("href") : "";
        if ($baseHref !== "") {
            [$this->protocol, $this->baseHost, $this->basePath] = Helpers::explode_url($baseHref);
        }

        
        $this->css->set_protocol($this->protocol);
        $this->css->set_host($this->baseHost);
        $this->css->set_base_path($this->basePath);

        
        $xpath = new DOMXPath($this->dom);
        $stylesheets = $xpath->query("//*[name() = 'link' or name() = 'style']");

        
        foreach ($stylesheets as $tag) {
            switch (strtolower($tag->nodeName)) {
                
                case "link":
                    if (
                        (stripos($tag->getAttribute("rel"), "stylesheet") !== false 
                        || mb_strtolower($tag->getAttribute("type")) === "text/css")
                        && stripos($tag->getAttribute("rel"), "alternate") === false 
                    ) {
                        
                        
                        $formedialist = preg_split("/[\s\n,]/", $tag->getAttribute("media"), -1, PREG_SPLIT_NO_EMPTY);
                        if (count($formedialist) > 0) {
                            $accept = false;
                            foreach ($formedialist as $type) {
                                if (in_array(mb_strtolower(trim($type)), $acceptedmedia)) {
                                    $accept = true;
                                    break;
                                }
                            }

                            if (!$accept) {
                                
                                
                                break;
                            }
                        }

                        $url = $tag->getAttribute("href");
                        $url = Helpers::build_url($this->protocol, $this->baseHost, $this->basePath, $url, $this->options->getChroot());

                        if ($url !== null) {
                            $this->css->load_css_file($url, Stylesheet::ORIG_AUTHOR);
                        }
                    }
                    break;

                
                case "style":
                    
                    
                    
                    
                    if ($tag->hasAttributes() &&
                        ($media = $tag->getAttribute("media")) &&
                        !in_array($media, $acceptedmedia)
                    ) {
                        break;
                    }

                    $css = "";
                    if ($tag->hasChildNodes()) {
                        $child = $tag->firstChild;
                        while ($child) {
                            $css .= $child->nodeValue; 
                            $child = $child->nextSibling;
                        }
                    } else {
                        $css = $tag->nodeValue;
                    }

                    
                    $this->css->set_protocol($this->protocol);
                    $this->css->set_host($this->baseHost);
                    $this->css->set_base_path($this->basePath);

                    $this->css->load_css($css, Stylesheet::ORIG_AUTHOR);
                    break;
            }

            
            $this->css->set_protocol($this->protocol);
            $this->css->set_host($this->baseHost);
            $this->css->set_base_path($this->basePath);
        }
    }

    
    public function enable_caching($cacheId)
    {
        $this->enableCaching($cacheId);
    }

    
    public function enableCaching($cacheId)
    {
        $this->cacheId = $cacheId;
    }

    
    public function parse_default_view($value)
    {
        return $this->parseDefaultView($value);
    }

    
    public function parseDefaultView($value)
    {
        $valid = ["XYZ", "Fit", "FitH", "FitV", "FitR", "FitB", "FitBH", "FitBV"];

        $options = preg_split("/\s*,\s*/", trim($value));
        $defaultView = array_shift($options);

        if (!in_array($defaultView, $valid)) {
            return false;
        }

        $this->setDefaultView($defaultView, $options);
        return true;
    }

    
    public function render()
    {
        $this->setPhpConfig();

        $logOutputFile = $this->options->getLogOutputFile();
        if ($logOutputFile) {
            if (!file_exists($logOutputFile) && is_writable(dirname($logOutputFile))) {
                touch($logOutputFile);
            }

            $startTime = microtime(true);
            if (is_writable($logOutputFile)) {
                ob_start();
            }
        }

        $this->processHtml();

        $this->css->apply_styles($this->tree);

        
        $pageStyles = $this->css->get_page_styles();
        $basePageStyle = $pageStyles["base"];
        unset($pageStyles["base"]);

        foreach ($pageStyles as $pageStyle) {
            $pageStyle->inherit($basePageStyle);
        }

        
        if (is_array($basePageStyle->size)) {
            
            
            
            
            
            [$width, $height] = $basePageStyle->size;
            $this->setPaper([0, 0, $width, $height]);
        }

        
        
        $canvasWidth = $this->canvas->get_width();
        $canvasHeight = $this->canvas->get_height();
        $size = $this->getPaperSize();

        if ($canvasWidth !== $size[2] || $canvasHeight !== $size[3]) {
            $this->canvas = CanvasFactory::get_instance($this, $this->paperSize, $this->paperOrientation);
            $this->fontMetrics->setCanvas($this->canvas);
        }

        $canvas = $this->canvas;

        $root_frame = $this->tree->get_root();
        $root = Factory::decorate_root($root_frame, $this);
        foreach ($this->tree as $frame) {
            if ($frame === $root_frame) {
                continue;
            }
            Factory::decorate_frame($frame, $this, $root);
        }

        
        $title = $this->dom->getElementsByTagName("title");
        if ($title->length) {
            $canvas->add_info("Title", trim($title->item(0)->nodeValue));
        }

        $metas = $this->dom->getElementsByTagName("meta");
        $labels = [
            "author" => "Author",
            "keywords" => "Keywords",
            "description" => "Subject",
        ];
        
        foreach ($metas as $meta) {
            $name = mb_strtolower($meta->getAttribute("name"));
            $value = trim($meta->getAttribute("content"));

            if (isset($labels[$name])) {
                $canvas->add_info($labels[$name], $value);
                continue;
            }

            if ($name === "dompdf.view" && $this->parseDefaultView($value)) {
                $canvas->set_default_view($this->defaultView, $this->defaultViewOptions);
            }
        }

        $root->set_containing_block(0, 0, $canvas->get_width(), $canvas->get_height());
        $root->set_renderer(new Renderer($this));

        
        $root->reflow();

        if (isset($this->callbacks["end_document"])) {
            $fs = $this->callbacks["end_document"];

            foreach ($fs as $f) {
                $canvas->page_script($f);
            }
        }

        
        if (!$this->options->getDebugKeepTemp()) {
            Cache::clear($this->options->getDebugPng());
        }

        global $_dompdf_warnings, $_dompdf_show_warnings;
        if ($_dompdf_show_warnings && isset($_dompdf_warnings)) {
            echo '<b>Dompdf Warnings</b><br><pre>';
            foreach ($_dompdf_warnings as $msg) {
                echo $msg . "\n";
            }

            if ($canvas instanceof CPDF) {
                echo $canvas->get_cpdf()->messages;
            }
            echo '</pre>';
            flush();
        }

        if ($logOutputFile && is_writable($logOutputFile)) {
            $this->writeLog($logOutputFile, $startTime);
            ob_end_clean();
        }

        $this->restorePhpConfig();
    }

    
    private function writeLog(string $logOutputFile, float $startTime): void
    {
        $frames = Frame::$ID_COUNTER;
        $memory = memory_get_peak_usage(true) / 1024;
        $time = (microtime(true) - $startTime) * 1000;

        $out = sprintf(
            "<span style='color: #000' title='Frames'>%6d</span>" .
            "<span style='color: #009' title='Memory'>%10.2f KB</span>" .
            "<span style='color: #900' title='Time'>%10.2f ms</span>" .
            "<span  title='Quirksmode'>  " .
            ($this->quirksmode ? "<span style='color: #d00'> ON</span>" : "<span style='color: #0d0'>OFF</span>") .
            "</span><br />", $frames, $memory, $time);

        $out .= ob_get_contents();
        ob_clean();

        file_put_contents($logOutputFile, $out);
    }

    
    public function add_info($label, $value)
    {
        $this->addInfo($label, $value);
    }

    
    public function addInfo(string $label, string $value): void
    {
        $this->canvas->add_info($label, $value);
    }

    
    public function stream($filename = "document.pdf", $options = [])
    {
        $this->setPhpConfig();

        $this->canvas->stream($filename, $options);

        $this->restorePhpConfig();
    }

    
    public function output($options = [])
    {
        $this->setPhpConfig();

        $output = $this->canvas->output($options);

        $this->restorePhpConfig();

        return $output;
    }

    
    public function output_html()
    {
        return $this->outputHtml();
    }

    
    public function outputHtml()
    {
        return $this->dom->saveHTML();
    }

    
    public function get_option($key)
    {
        return $this->options->get($key);
    }

    
    public function set_option($key, $value)
    {
        $this->options->set($key, $value);
        return $this;
    }

    
    public function set_options(array $options)
    {
        $this->options->set($options);
        return $this;
    }

    
    public function set_paper($size, $orientation = "portrait")
    {
        $this->setPaper($size, $orientation);
    }

    
    public function setPaper($size, string $orientation = "portrait"): self
    {
        $this->paperSize = $size;
        $this->paperOrientation = $orientation;
        return $this;
    }

    
    public function getPaperSize(): array
    {
        $paper = $this->paperSize;
        $orientation = $this->paperOrientation;

        if (is_array($paper)) {
            $size = array_map("floatval", $paper);
        } else {
            $paper = strtolower($paper);
            $size = CPDF::$PAPER_SIZES[$paper] ?? CPDF::$PAPER_SIZES["letter"];
        }

        if (strtolower($orientation) === "landscape") {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }

        return $size;
    }

    
    public function getPaperOrientation(): string
    {
        return $this->paperOrientation;
    }

    
    public function setTree(FrameTree $tree)
    {
        $this->tree = $tree;
        return $this;
    }

    
    public function get_tree()
    {
        return $this->getTree();
    }

    
    public function getTree()
    {
        return $this->tree;
    }

    
    public function set_protocol($protocol)
    {
        return $this->setProtocol($protocol);
    }

    
    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    
    public function get_protocol()
    {
        return $this->getProtocol();
    }

    
    public function getProtocol()
    {
        return $this->protocol;
    }

    
    public function set_host($host)
    {
        $this->setBaseHost($host);
    }

    
    public function setBaseHost(string $baseHost)
    {
        $this->baseHost = $baseHost;
        return $this;
    }

    
    public function get_host()
    {
        return $this->getBaseHost();
    }

    
    public function getBaseHost()
    {
        return $this->baseHost;
    }

    
    public function set_base_path($path)
    {
        $this->setBasePath($path);
    }

    
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    
    public function get_base_path()
    {
        return $this->getBasePath();
    }

    
    public function getBasePath()
    {
        return $this->basePath;
    }

    
    public function set_default_view($default_view, $options)
    {
        return $this->setDefaultView($default_view, $options);
    }

    
    public function setDefaultView($defaultView, $options)
    {
        $this->defaultView = $defaultView;
        $this->defaultViewOptions = $options;
        return $this;
    }

    
    public function set_http_context($http_context)
    {
        return $this->setHttpContext($http_context);
    }

    
    public function setHttpContext($httpContext)
    {
        $this->options->setHttpContext($httpContext);
        return $this;
    }

    
    public function get_http_context()
    {
        return $this->getHttpContext();
    }

    
    public function getHttpContext()
    {
        return $this->options->getHttpContext();
    }

    
    public function setCanvas(Canvas $canvas)
    {
        $this->canvas = $canvas;
        return $this;
    }

    
    public function get_canvas()
    {
        return $this->getCanvas();
    }

    
    public function getCanvas()
    {
        return $this->canvas;
    }

    
    public function setCss(Stylesheet $css)
    {
        $this->css = $css;
        return $this;
    }

    
    public function get_css()
    {
        return $this->getCss();
    }

    
    public function getCss()
    {
        return $this->css;
    }

    
    public function setDom(DOMDocument $dom)
    {
        $this->dom = $dom;
        return $this;
    }

    
    public function get_dom()
    {
        return $this->getDom();
    }

    
    public function getDom()
    {
        return $this->dom;
    }

    
    public function setOptions(Options $options)
    {
        
        if ($this->options && $this->options->getHttpContext() && !$options->getHttpContext()) {
            $options->setHttpContext($this->options->getHttpContext());
        }

        $this->options = $options;
        $fontMetrics = $this->fontMetrics;
        if (isset($fontMetrics)) {
            $fontMetrics->setOptions($options);
        }
        return $this;
    }

    
    public function getOptions()
    {
        return $this->options;
    }

    
    public function get_callbacks()
    {
        return $this->getCallbacks();
    }

    
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    
    public function set_callbacks($callbacks)
    {
        return $this->setCallbacks($callbacks);
    }

    
    public function setCallbacks(array $callbacks): self
    {
        $this->callbacks = [];

        foreach ($callbacks as $c) {
            if (is_array($c) && isset($c["event"]) && isset($c["f"])) {
                $event = $c["event"];
                $f = $c["f"];
                if (is_string($event) && is_callable($f)) {
                    $this->callbacks[$event][] = $f;
                }
            }
        }

        return $this;
    }

    
    public function get_quirksmode()
    {
        return $this->getQuirksmode();
    }

    
    public function getQuirksmode()
    {
        return $this->quirksmode;
    }

    
    public function setFontMetrics(FontMetrics $fontMetrics)
    {
        $this->fontMetrics = $fontMetrics;
        return $this;
    }

    
    public function getFontMetrics()
    {
        return $this->fontMetrics;
    }

    
    function __get($prop)
    {
        switch ($prop) {
            case 'version':
                return $this->version;
            default:
                throw new Exception('Invalid property: ' . $prop);
        }
    }
}
