<?php

namespace Dompdf;

class Options
{
    
    private $rootDir;

    
    private $tempDir;

    
    private $fontDir;

    
    private $fontCache;

    
    private $chroot;

    
    private $allowedProtocols = [
        "data://" => ["rules" => []],
        "file://" => ["rules" => []],
        "http://" => ["rules" => []],
        "https://" => ["rules" => []]
    ];

    
    private $artifactPathValidation = null;

    
    private $logOutputFile = '';

    
    private $defaultMediaType = "screen";

    
    private $defaultPaperSize = "letter";

    
    private $defaultPaperOrientation = "portrait";

    
    private $defaultFont = "serif";

    
    private $dpi = 96;

    
    private $fontHeightRatio = 1.1;

    
    private $isPhpEnabled = false;

    
    private $isRemoteEnabled = false;

    
    private $allowedRemoteHosts = null;

    
    private $isPdfAEnabled = false;

    
    private $isJavascriptEnabled = true;

    
    private $isHtml5ParserEnabled = true;

    
    private $isFontSubsettingEnabled = true;

    
    private $debugPng = false;

    
    private $debugKeepTemp = false;

    
    private $debugCss = false;

    
    private $debugLayout = false;

    
    private $debugLayoutLines = true;

    
    private $debugLayoutBlocks = true;

    
    private $debugLayoutInline = true;

    
    private $debugLayoutPaddingBox = true;

    
    private $pdfBackend = "CPDF";

    
    private $pdflibLicense = "";

    
    private $httpContext;

    
    public function __construct(?array $attributes = null)
    {
        $rootDir = realpath(__DIR__ . "/../");
        $this->setChroot(array($rootDir));
        $this->setRootDir($rootDir);
        $this->setTempDir(sys_get_temp_dir());
        $this->setFontDir($rootDir . "/lib/fonts");
        $this->setFontCache($this->getFontDir());

        $ver = "";
        $versionFile = realpath(__DIR__ . '/../VERSION');
        if (($version = file_get_contents($versionFile)) !== false) {
            $version = trim($version);
            if ($version !== '$Format:<%h>$') {
                $ver = "/$version";
            }
        }
        $this->setHttpContext([
            "http" => [
                "follow_location" => false,
                "user_agent" => "Dompdf$ver https://github.com/dompdf/dompdf"
            ]
        ]);

        $this->setAllowedProtocols(["data://", "file://", "http://", "https://"]);

        $this->setArtifactPathValidation([$this, "validateArtifactPath"]);

        if (null !== $attributes) {
            $this->set($attributes);
        }
    }

    
    public function set($attributes, $value = null)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes => $value];
        }
        foreach ($attributes as $key => $value) {
            if ($key === 'tempDir' || $key === 'temp_dir') {
                $this->setTempDir($value);
            } elseif ($key === 'fontDir' || $key === 'font_dir') {
                $this->setFontDir($value);
            } elseif ($key === 'fontCache' || $key === 'font_cache') {
                $this->setFontCache($value);
            } elseif ($key === 'chroot') {
                $this->setChroot($value);
            } elseif ($key === 'allowedProtocols' || $key === 'allowed_protocols') {
                $this->setAllowedProtocols($value);
            } elseif ($key === 'artifactPathValidation') {
                $this->setArtifactPathValidation($value);
            } elseif ($key === 'logOutputFile' || $key === 'log_output_file') {
                $this->setLogOutputFile($value);
            } elseif ($key === 'defaultMediaType' || $key === 'default_media_type') {
                $this->setDefaultMediaType($value);
            } elseif ($key === 'defaultPaperSize' || $key === 'default_paper_size') {
                $this->setDefaultPaperSize($value);
            } elseif ($key === 'defaultPaperOrientation' || $key === 'default_paper_orientation') {
                $this->setDefaultPaperOrientation($value);
            } elseif ($key === 'defaultFont' || $key === 'default_font') {
                $this->setDefaultFont($value);
            } elseif ($key === 'dpi') {
                $this->setDpi($value);
            } elseif ($key === 'fontHeightRatio' || $key === 'font_height_ratio') {
                $this->setFontHeightRatio($value);
            } elseif ($key === 'isPhpEnabled' || $key === 'is_php_enabled' || $key === 'enable_php') {
                $this->setIsPhpEnabled($value);
            } elseif ($key === 'isRemoteEnabled' || $key === 'is_remote_enabled' || $key === 'enable_remote') {
                $this->setIsRemoteEnabled($value);
            } elseif ($key === 'allowedRemoteHosts' || $key === 'allowed_remote_hosts') {
                $this->setAllowedRemoteHosts($value);
            } elseif ($key === 'isPdfAEnabled' || $key === 'is_pdf_a_enabled' || $key === 'enable_pdf_a') {
                $this->setIsPdfAEnabled($value);
            } elseif ($key === 'isJavascriptEnabled' || $key === 'is_javascript_enabled' || $key === 'enable_javascript') {
                $this->setIsJavascriptEnabled($value);
            } elseif ($key === 'isHtml5ParserEnabled' || $key === 'is_html5_parser_enabled' || $key === 'enable_html5_parser') {
                $this->setIsHtml5ParserEnabled($value);
            } elseif ($key === 'isFontSubsettingEnabled' || $key === 'is_font_subsetting_enabled' || $key === 'enable_font_subsetting') {
                $this->setIsFontSubsettingEnabled($value);
            } elseif ($key === 'debugPng' || $key === 'debug_png') {
                $this->setDebugPng($value);
            } elseif ($key === 'debugKeepTemp' || $key === 'debug_keep_temp') {
                $this->setDebugKeepTemp($value);
            } elseif ($key === 'debugCss' || $key === 'debug_css') {
                $this->setDebugCss($value);
            } elseif ($key === 'debugLayout' || $key === 'debug_layout') {
                $this->setDebugLayout($value);
            } elseif ($key === 'debugLayoutLines' || $key === 'debug_layout_lines') {
                $this->setDebugLayoutLines($value);
            } elseif ($key === 'debugLayoutBlocks' || $key === 'debug_layout_blocks') {
                $this->setDebugLayoutBlocks($value);
            } elseif ($key === 'debugLayoutInline' || $key === 'debug_layout_inline') {
                $this->setDebugLayoutInline($value);
            } elseif ($key === 'debugLayoutPaddingBox' || $key === 'debug_layout_padding_box') {
                $this->setDebugLayoutPaddingBox($value);
            } elseif ($key === 'pdfBackend' || $key === 'pdf_backend') {
                $this->setPdfBackend($value);
            } elseif ($key === 'pdflibLicense' || $key === 'pdflib_license') {
                $this->setPdflibLicense($value);
            } elseif ($key === 'httpContext' || $key === 'http_context') {
                $this->setHttpContext($value);
            }
        }
        return $this;
    }

    
    public function get($key)
    {
        if ($key === 'tempDir' || $key === 'temp_dir') {
            return $this->getTempDir();
        } elseif ($key === 'fontDir' || $key === 'font_dir') {
            return $this->getFontDir();
        } elseif ($key === 'fontCache' || $key === 'font_cache') {
            return $this->getFontCache();
        } elseif ($key === 'chroot') {
            return $this->getChroot();
        } elseif ($key === 'allowedProtocols' || $key === 'allowed_protocols') {
            return $this->getAllowedProtocols();
        } elseif ($key === 'artifactPathValidation') {
            return $this->getArtifactPathValidation();
        } elseif ($key === 'logOutputFile' || $key === 'log_output_file') {
            return $this->getLogOutputFile();
        } elseif ($key === 'defaultMediaType' || $key === 'default_media_type') {
            return $this->getDefaultMediaType();
        } elseif ($key === 'defaultPaperSize' || $key === 'default_paper_size') {
            return $this->getDefaultPaperSize();
        } elseif ($key === 'defaultPaperOrientation' || $key === 'default_paper_orientation') {
            return $this->getDefaultPaperOrientation();
        } elseif ($key === 'defaultFont' || $key === 'default_font') {
            return $this->getDefaultFont();
        } elseif ($key === 'dpi') {
            return $this->getDpi();
        } elseif ($key === 'fontHeightRatio' || $key === 'font_height_ratio') {
            return $this->getFontHeightRatio();
        } elseif ($key === 'isPhpEnabled' || $key === 'is_php_enabled' || $key === 'enable_php') {
            return $this->getIsPhpEnabled();
        } elseif ($key === 'isRemoteEnabled' || $key === 'is_remote_enabled' || $key === 'enable_remote') {
            return $this->getIsRemoteEnabled();
        } elseif ($key === 'allowedRemoteHosts' || $key === 'allowed_remote_hosts') {
            return $this->getAllowedProtocols();
        } elseif ($key === 'isPdfAEnabled' || $key === 'is_pdf_a_enabled' || $key === 'enable_pdf_a') {
            $this->getIsPdfAEnabled();
        } elseif ($key === 'isJavascriptEnabled' || $key === 'is_javascript_enabled' || $key === 'enable_javascript') {
            return $this->getIsJavascriptEnabled();
        } elseif ($key === 'isHtml5ParserEnabled' || $key === 'is_html5_parser_enabled' || $key === 'enable_html5_parser') {
            return $this->getIsHtml5ParserEnabled();
        } elseif ($key === 'isFontSubsettingEnabled' || $key === 'is_font_subsetting_enabled' || $key === 'enable_font_subsetting') {
            return $this->getIsFontSubsettingEnabled();
        } elseif ($key === 'debugPng' || $key === 'debug_png') {
            return $this->getDebugPng();
        } elseif ($key === 'debugKeepTemp' || $key === 'debug_keep_temp') {
            return $this->getDebugKeepTemp();
        } elseif ($key === 'debugCss' || $key === 'debug_css') {
            return $this->getDebugCss();
        } elseif ($key === 'debugLayout' || $key === 'debug_layout') {
            return $this->getDebugLayout();
        } elseif ($key === 'debugLayoutLines' || $key === 'debug_layout_lines') {
            return $this->getDebugLayoutLines();
        } elseif ($key === 'debugLayoutBlocks' || $key === 'debug_layout_blocks') {
            return $this->getDebugLayoutBlocks();
        } elseif ($key === 'debugLayoutInline' || $key === 'debug_layout_inline') {
            return $this->getDebugLayoutInline();
        } elseif ($key === 'debugLayoutPaddingBox' || $key === 'debug_layout_padding_box') {
            return $this->getDebugLayoutPaddingBox();
        } elseif ($key === 'pdfBackend' || $key === 'pdf_backend') {
            return $this->getPdfBackend();
        } elseif ($key === 'pdflibLicense' || $key === 'pdflib_license') {
            return $this->getPdflibLicense();
        } elseif ($key === 'httpContext' || $key === 'http_context') {
            return $this->getHttpContext();
        }
        return null;
    }

    
    public function setPdfBackend($pdfBackend)
    {
        $this->pdfBackend = $pdfBackend;
        return $this;
    }

    
    public function getPdfBackend()
    {
        return $this->pdfBackend;
    }

    
    public function setPdflibLicense($pdflibLicense)
    {
        $this->pdflibLicense = $pdflibLicense;
        return $this;
    }

    
    public function getPdflibLicense()
    {
        return $this->pdflibLicense;
    }

    
    public function setChroot($chroot, $delimiter = ',')
    {
        if (is_string($chroot)) {
            $this->chroot = explode($delimiter, $chroot);
        } elseif (is_array($chroot)) {
            $this->chroot = $chroot;
        }
        return $this;
    }

    
    public function getAllowedProtocols()
    {
        return $this->allowedProtocols;
    }

    
    public function setAllowedProtocols(array $allowedProtocols)
    {
        $protocols = [];
        foreach ($allowedProtocols as $protocol => $config) {
            if (is_string($protocol)) {
                $protocols[$protocol] = [];
                if (is_array($config)) {
                    $protocols[$protocol] = $config;
                }
            } elseif (is_string($config)) {
                $protocols[$config] = [];
            }
        }
        $this->allowedProtocols = [];
        foreach ($protocols as $protocol => $config) {
            $this->addAllowedProtocol($protocol, ...($config["rules"] ?? []));
        }
        return $this;
    }

    
    public function addAllowedProtocol(string $protocol, callable ...$rules)
    {
        $protocol = strtolower($protocol);
        if (empty($rules)) {
            $rules = [];
            switch ($protocol) {
                case "data://":
                    break;
                case "file://":
                    $rules[] = [$this, "validateLocalUri"];
                    break;
                case "http://":
                case "https://":
                    $rules[] = [$this, "validateRemoteUri"];
                    break;
                case "phar://":
                    $rules[] = [$this, "validatePharUri"];
                    break;
            }
        }
        $this->allowedProtocols[$protocol] = ["rules" => $rules];
        return $this;
    }

    
    public function getArtifactPathValidation()
    {
        return $this->artifactPathValidation;
    }

    
    public function setArtifactPathValidation($validator)
    {
        $this->artifactPathValidation = $validator;
        return $this;
    }

    
    public function getChroot()
    {
        $chroot = [];
        if (is_array($this->chroot)) {
            $chroot = $this->chroot;
        }
        return $chroot;
    }

    
    public function setDebugCss($debugCss)
    {
        $this->debugCss = $debugCss;
        return $this;
    }

    
    public function getDebugCss()
    {
        return $this->debugCss;
    }

    
    public function setDebugKeepTemp($debugKeepTemp)
    {
        $this->debugKeepTemp = $debugKeepTemp;
        return $this;
    }

    
    public function getDebugKeepTemp()
    {
        return $this->debugKeepTemp;
    }

    
    public function setDebugLayout($debugLayout)
    {
        $this->debugLayout = $debugLayout;
        return $this;
    }

    
    public function getDebugLayout()
    {
        return $this->debugLayout;
    }

    
    public function setDebugLayoutBlocks($debugLayoutBlocks)
    {
        $this->debugLayoutBlocks = $debugLayoutBlocks;
        return $this;
    }

    
    public function getDebugLayoutBlocks()
    {
        return $this->debugLayoutBlocks;
    }

    
    public function setDebugLayoutInline($debugLayoutInline)
    {
        $this->debugLayoutInline = $debugLayoutInline;
        return $this;
    }

    
    public function getDebugLayoutInline()
    {
        return $this->debugLayoutInline;
    }

    
    public function setDebugLayoutLines($debugLayoutLines)
    {
        $this->debugLayoutLines = $debugLayoutLines;
        return $this;
    }

    
    public function getDebugLayoutLines()
    {
        return $this->debugLayoutLines;
    }

    
    public function setDebugLayoutPaddingBox($debugLayoutPaddingBox)
    {
        $this->debugLayoutPaddingBox = $debugLayoutPaddingBox;
        return $this;
    }

    
    public function getDebugLayoutPaddingBox()
    {
        return $this->debugLayoutPaddingBox;
    }

    
    public function setDebugPng($debugPng)
    {
        $this->debugPng = $debugPng;
        return $this;
    }

    
    public function getDebugPng()
    {
        return $this->debugPng;
    }

    
    public function setDefaultFont($defaultFont)
    {
        if (!($defaultFont === null || trim($defaultFont) === "")) {
            $this->defaultFont = $defaultFont;
        } else {
            $this->defaultFont = "serif";
        }
        return $this;
    }

    
    public function getDefaultFont()
    {
        return $this->defaultFont;
    }

    
    public function setDefaultMediaType($defaultMediaType)
    {
        $this->defaultMediaType = $defaultMediaType;
        return $this;
    }

    
    public function getDefaultMediaType()
    {
        return $this->defaultMediaType;
    }

    
    public function setDefaultPaperSize($defaultPaperSize): self
    {
        $this->defaultPaperSize = $defaultPaperSize;
        return $this;
    }

    
    public function setDefaultPaperOrientation(string $defaultPaperOrientation): self
    {
        $this->defaultPaperOrientation = $defaultPaperOrientation;
        return $this;
    }

    
    public function getDefaultPaperSize()
    {
        return $this->defaultPaperSize;
    }

    
    public function getDefaultPaperOrientation(): string
    {
        return $this->defaultPaperOrientation;
    }

    
    public function setDpi($dpi)
    {
        $this->dpi = $dpi;
        return $this;
    }

    
    public function getDpi()
    {
        return $this->dpi;
    }

    
    public function setFontCache($fontCache)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($fontCache, "fontCache") === true) {
            $this->fontCache = $fontCache;
        }
        return $this;
    }

    
    public function getFontCache()
    {
        return $this->fontCache;
    }

    
    public function setFontDir($fontDir)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($fontDir, "fontDir") === true) {
            $this->fontDir = $fontDir;
        }
        return $this;
    }

    
    public function getFontDir()
    {
        return $this->fontDir;
    }

    
    public function setFontHeightRatio($fontHeightRatio)
    {
        $this->fontHeightRatio = $fontHeightRatio;
        return $this;
    }

    
    public function getFontHeightRatio()
    {
        return $this->fontHeightRatio;
    }

    
    public function setIsFontSubsettingEnabled($isFontSubsettingEnabled)
    {
        $this->isFontSubsettingEnabled = $isFontSubsettingEnabled;
        return $this;
    }

    
    public function getIsFontSubsettingEnabled()
    {
        return $this->isFontSubsettingEnabled;
    }

    
    public function isFontSubsettingEnabled()
    {
        return $this->getIsFontSubsettingEnabled();
    }

    
    public function setIsHtml5ParserEnabled($isHtml5ParserEnabled)
    {
        $this->isHtml5ParserEnabled = $isHtml5ParserEnabled;
        return $this;
    }

    
    public function getIsHtml5ParserEnabled()
    {
        return $this->isHtml5ParserEnabled;
    }

    
    public function isHtml5ParserEnabled()
    {
        return $this->getIsHtml5ParserEnabled();
    }

    
    public function setIsJavascriptEnabled($isJavascriptEnabled)
    {
        $this->isJavascriptEnabled = $isJavascriptEnabled;
        return $this;
    }

    
    public function getIsJavascriptEnabled()
    {
        return $this->isJavascriptEnabled;
    }

    
    public function isJavascriptEnabled()
    {
        return $this->getIsJavascriptEnabled();
    }

    
    public function setIsPhpEnabled($isPhpEnabled)
    {
        $this->isPhpEnabled = $isPhpEnabled;
        return $this;
    }

    
    public function getIsPhpEnabled()
    {
        return $this->isPhpEnabled;
    }

    
    public function isPhpEnabled()
    {
        return $this->getIsPhpEnabled();
    }

    
    public function setIsRemoteEnabled($isRemoteEnabled)
    {
        $this->isRemoteEnabled = $isRemoteEnabled;
        return $this;
    }

    
    public function getIsRemoteEnabled()
    {
        return $this->isRemoteEnabled;
    }

    
    public function isRemoteEnabled()
    {
        return $this->getIsRemoteEnabled();
    }

    
    public function setAllowedRemoteHosts($allowedRemoteHosts)
    {
        if (is_array($allowedRemoteHosts)) {
            
            foreach ($allowedRemoteHosts as &$host) {
                $host = mb_strtolower($host);
            }

            unset($host);
        }

        $this->allowedRemoteHosts = $allowedRemoteHosts;
        return $this;
    }

    
    public function getAllowedRemoteHosts()
    {
        return $this->allowedRemoteHosts;
    }

    
    public function setIsPdfAEnabled($isPdfAEnabled)
    {
        $this->isPdfAEnabled = $isPdfAEnabled;
        return $this;
    }

    
    public function getIsPdfAEnabled()
    {
        return $this->isPdfAEnabled;
    }

    
    public function isPdfAEnabled()
    {
        return $this->getIsPdfAEnabled();
    }

    
    public function setLogOutputFile($logOutputFile)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($logOutputFile, "logOutputFile") === true) {
            $this->logOutputFile = $logOutputFile;
        }
        return $this;
    }

    
    public function getLogOutputFile()
    {
        return $this->logOutputFile;
    }

    
    public function setTempDir($tempDir)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($tempDir, "tempDir") === true) {
            $this->tempDir = $tempDir;
        }
        return $this;
    }

    
    public function getTempDir()
    {
        return $this->tempDir;
    }

    
    public function setRootDir($rootDir)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($rootDir, "rootDir") === true) {
            $this->rootDir = $rootDir;
        }
        return $this;
    }

    
    public function getRootDir()
    {
        return $this->rootDir;
    }

    
    public function setHttpContext($httpContext)
    {
        $this->httpContext = is_array($httpContext) ? stream_context_create($httpContext) : $httpContext;
        return $this;
    }

    
    public function getHttpContext()
    {
        return $this->httpContext;
    }


    public function validateArtifactPath(?string $path, string $option)
    {
        if ($path === null) {
            return true;
        }
        $parsed_uri = parse_url($path);
        if ($parsed_uri === false || (array_key_exists("scheme", $parsed_uri) && strtolower($parsed_uri["scheme"]) === "phar")) {
            return false;
        }
        return true;
    }

    public function validateLocalUri(string $uri)
    {
        if ($uri === null || strlen($uri) === 0) {
            return [false, "The URI must not be empty."];
        }

        $realfile = realpath(str_replace("file://", "", $uri));

        $dirs = $this->chroot;
        $dirs[] = $this->rootDir;
        $chrootValid = false;
        foreach ($dirs as $chrootPath) {
            $chrootPath = realpath($chrootPath);
            if ($chrootPath !== false && strpos($realfile, $chrootPath) === 0) {
                $chrootValid = true;
                break;
            }
        }
        if ($chrootValid !== true) {
            return [false, "Permission denied. The file could not be found under the paths specified by Options::chroot."];
        }

        if (!$realfile) {
            return [false, "File not found."];
        }

        return [true, null];
    }

    public function validatePharUri(string $uri)
    {
        if ($uri === null || strlen($uri) === 0) {
            return [false, "The URI must not be empty."];
        }

        $file = substr(substr($uri, 0, strpos($uri, ".phar") + 5), 7);
        return $this->validateLocalUri($file);
    }

    public function validateRemoteUri(string $uri)
    {
        if ($uri === null || strlen($uri) === 0) {
            return [false, "The URI must not be empty."];
        }

        if (!$this->isRemoteEnabled) {
            return [false, "Remote file requested, but remote file download is disabled."];
        }

        if (is_array($this->allowedRemoteHosts) && count($this->allowedRemoteHosts) > 0) {
            $host = parse_url($uri, PHP_URL_HOST);
            $host = mb_strtolower($host);

            if (!in_array($host, $this->allowedRemoteHosts, true)) {
                return [false, "Remote host is not in allowed list: " . $host];
            }
        }

        return [true, null];
    }
}
