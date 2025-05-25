<?php

namespace Dompdf\Adapter;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\Exception;
use Dompdf\FontMetrics;
use Dompdf\Helpers;
use Dompdf\Image\Cache;


class PDFLib implements Canvas
{

    
    public static $PAPER_SIZES = []; 

    
    static $IN_MEMORY = true;

    
    protected static $MAJOR_VERSION = null;


    
    public static $nativeFontsToPDFLib = [
        "courier"               => "Courier",
        "courier-bold"          => "Courier-Bold",
        "courier-oblique"       => "Courier-Oblique",
        "courier-boldoblique"   => "Courier-BoldOblique",
        "helvetica"             => "Helvetica",
        "helvetica-bold"        => "Helvetica-Bold",
        "helvetica-oblique"     => "Helvetica-Oblique",
        "helvetica-boldoblique" => "Helvetica-BoldOblique",
        "times"                 => "Times-Roman",
        "times-roman"           => "Times-Roman",
        "times-bold"            => "Times-Bold",
        "times-italic"          => "Times-Italic",
        "times-bolditalic"      => "Times-BoldItalic",
        "symbol"                => "Symbol",
        "zapfdinbats"           => "ZapfDingbats",
        "zapfdingbats"          => "ZapfDingbats",
    ];

    
    protected $_dompdf;

    
    protected $_pdf;

    
    protected $_file;

    
    protected $_width;

    
    protected $_height;

    
    protected $_last_fill_color;

    
    protected $_last_stroke_color;

    
    protected $_current_opacity;

    
    protected $_imgs;

    
    protected $_fonts;

    
    protected $_fontsFiles;

    
    protected $_objs;

    
    protected $_gstates = [];

    
    protected $_page_number;

    
    protected $_page_count;

    
    protected $_pages;

    public function __construct($paper = "letter", string $orientation = "portrait", ?Dompdf $dompdf = null)
    {
        if (is_array($paper)) {
            $size = array_map("floatval", $paper);
        } else {
            $paper = strtolower($paper);
            $size = self::$PAPER_SIZES[$paper] ?? self::$PAPER_SIZES["letter"];
        }

        if (strtolower($orientation) === "landscape") {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }

        $this->_width = $size[2] - $size[0];
        $this->_height = $size[3] - $size[1];

        if ($dompdf === null) {
            $this->_dompdf = new Dompdf();
        } else {
            $this->_dompdf = $dompdf;
        }
        $options = $dompdf->getOptions();

        $this->_pdf = new \PDFLib();

        $license = $options->getPdflibLicense();
        if (strlen($license) > 0) {
            $this->setPDFLibParameter("license", $license);
        }

        if ($this->getPDFLibMajorVersion() < 10) {
            $this->setPDFLibParameter("textformat", "utf8");
        }
        if ($this->getPDFLibMajorVersion() >= 7) {
            $this->setPDFLibParameter("errorpolicy", "return");
            
            
        } else {
            $this->setPDFLibParameter("fontwarning", "false");
        }

        $searchPath = [$options->getFontDir(), $options->getRootDir() . "/lib/fonts"];
        if (empty($searchPath) === false) {
            $this->_pdf->set_option('searchpath={{' . implode("} {", $searchPath) . '}}');
        }

        
        $this->_pdf->set_info("Producer Addendum", sprintf("%s + PDFLib %s", $dompdf->version, $this->getPDFLibMajorVersion()));

        
        $tz = @date_default_timezone_get();
        date_default_timezone_set("UTC");
        $this->_pdf->set_info("Date", date("Y-m-d"));
        date_default_timezone_set($tz);

        $doc_options = "";

        if ($options->isPdfAEnabled()) {
            $doc_options = "pdfa=PDF/A-3b autoxmp";
        }

        if (self::$IN_MEMORY) {
            $this->_pdf->begin_document("", $doc_options);
        } else {
            $tmp_dir = $options->getTempDir();
            $tmp_name = @tempnam($tmp_dir, "libdompdf_pdf_");
            @unlink($tmp_name);
            $this->_file = "$tmp_name.pdf";
            $this->_pdf->begin_document($this->_file, $doc_options);
        }

        if ($options->isPdfAEnabled()) {
            $iccProfilePath = $options->getRootDir() . '/lib/res/sRGB2014.icc';
            $this->_pdf->load_iccprofile($iccProfilePath, "usage=outputintent");
        }

        $this->_pdf->begin_page_ext($this->_width, $this->_height, "");

        $this->_page_number = $this->_page_count = 1;

        $this->_imgs = [];
        $this->_fonts = [];
        $this->_objs = [];
    }

    function get_dompdf()
    {
        return $this->_dompdf;
    }

    
    protected function _close()
    {
        $this->_place_objects();

        
        $this->_pdf->suspend_page("");
        for ($p = 1; $p <= $this->_page_count; $p++) {
            $this->_pdf->resume_page("pagenumber=$p");
            $this->_pdf->end_page_ext("");
        }

        $this->_pdf->end_document("");
    }


    
    public function get_pdflib()
    {
        return $this->_pdf;
    }

    public function add_info(string $label, string $value): void
    {
        $this->_pdf->set_info($label, $value);
    }

    
    public function open_object()
    {
        $this->_pdf->suspend_page("");
        if ($this->getPDFLibMajorVersion() >= 7) {
            $ret = $this->_pdf->begin_template_ext($this->_width, $this->_height, "");
        } else {
            $ret = $this->_pdf->begin_template($this->_width, $this->_height);
        }
        $this->_pdf->save();
        $this->_objs[$ret] = ["start_page" => $this->_page_number];

        return $ret;
    }

    
    public function reopen_object($object)
    {
        throw new Exception("PDFLib does not support reopening objects.");
    }

    
    public function close_object()
    {
        $this->_pdf->restore();
        if ($this->getPDFLibMajorVersion() >= 7) {
            $this->_pdf->end_template_ext($this->_width, $this->_height);
        } else {
            $this->_pdf->end_template();
        }
        $this->_pdf->resume_page("pagenumber=" . $this->_page_number);
    }

    
    public function add_object($object, $where = 'all')
    {

        if (mb_strpos($where, "next") !== false) {
            $this->_objs[$object]["start_page"]++;
            $where = str_replace("next", "", $where);
            if ($where == "") {
                $where = "add";
            }
        }

        $this->_objs[$object]["where"] = $where;
    }

    
    public function stop_object($object)
    {

        if (!isset($this->_objs[$object])) {
            return;
        }

        $start = $this->_objs[$object]["start_page"];
        $where = $this->_objs[$object]["where"];

        
        if ($this->_page_number >= $start &&
            (($this->_page_number % 2 == 0 && $where === "even") ||
                ($this->_page_number % 2 == 1 && $where === "odd") ||
                ($where === "all"))
        ) {
            $this->_pdf->fit_image($object, 0, 0, "");
        }

        $this->_objs[$object] = null;
        unset($this->_objs[$object]);
    }

    
    protected function _place_objects()
    {

        foreach ($this->_objs as $obj => $props) {
            $start = $props["start_page"];
            $where = $props["where"];

            
            if ($this->_page_number >= $start &&
                (($this->_page_number % 2 == 0 && $where === "even") ||
                    ($this->_page_number % 2 == 1 && $where === "odd") ||
                    ($where === "all"))
            ) {
                $this->_pdf->fit_image($obj, 0, 0, "");
            }
        }
    }

    public function get_width()
    {
        return $this->_width;
    }

    public function get_height()
    {
        return $this->_height;
    }

    public function get_page_number()
    {
        return $this->_page_number;
    }

    public function get_page_count()
    {
        return $this->_page_count;
    }

    
    public function set_page_number($num)
    {
        $this->_page_number = (int)$num;
    }

    public function set_page_count($count)
    {
        $this->_page_count = (int)$count;
    }

    
    protected function _set_line_style($width, $cap, $join, $dash)
    {
        if (!is_array($dash)) {
            $dash = [];
        }

        
        
        foreach ($dash as &$d) {
            if ($d == 0) {
                $d = 1.5e-5;
            }
        }

        if (count($dash) === 1) {
            $dash[] = $dash[0];
        }

        if ($this->getPDFLibMajorVersion() >= 9) {
            if (count($dash) > 1) {
                $this->_pdf->set_graphics_option("dasharray={" . implode(" ", $dash) . "}");
            } else {
                $this->_pdf->set_graphics_option("dasharray=none");
            }
        } else {
            if (count($dash) > 1) {
                $this->_pdf->setdashpattern("dasharray={" . implode(" ", $dash) . "}");
            } else {
                $this->_pdf->setdash(0, 0);
            }
        }

        switch ($join) {
            case "miter":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linejoin=0');
                } else {
                    $this->_pdf->setlinejoin(0);
                }
                break;

            case "round":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linejoin=1');
                } else {
                    $this->_pdf->setlinejoin(1);
                }
                break;

            case "bevel":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linejoin=2');
                } else {
                    $this->_pdf->setlinejoin(2);
                }
                break;

            default:
                break;
        }

        switch ($cap) {
            case "butt":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linecap=0');
                } else {
                    $this->_pdf->setlinecap(0);
                }
                break;

            case "round":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linecap=1');
                } else {
                    $this->_pdf->setlinecap(1);
                }
                break;

            case "square":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linecap=2');
                } else {
                    $this->_pdf->setlinecap(2);
                }
                break;

            default:
                break;
        }

        $this->_pdf->setlinewidth($width);
    }

    
    protected function _set_stroke_color($color)
    {
        
        
        if ($this->_last_stroke_color == $color) {
            
            
            
        }

        $alpha = isset($color["alpha"]) ? $color["alpha"] : 1;
        if (isset($this->_current_opacity)) {
            $alpha *= $this->_current_opacity;
        }

        $this->_last_stroke_color = $color;

        if (isset($color[3])) {
            $type = "cmyk";
            list($c1, $c2, $c3, $c4) = [$color[0], $color[1], $color[2], $color[3]];
        } elseif (isset($color[2])) {
            $type = "rgb";
            list($c1, $c2, $c3, $c4) = [$color[0], $color[1], $color[2], 0];
        } else {
            $type = "gray";
            list($c1, $c2, $c3, $c4) = [$color[0], $color[1], 0, 0];
        }

        $this->_set_stroke_opacity($alpha, "Normal");
        $this->_pdf->setcolor("stroke", $type, $c1, $c2, $c3, $c4);
    }

    
    protected function _set_fill_color($color)
    {
        
        
        if ($this->_last_fill_color == $color) {
            
            
            
        }

        $alpha = isset($color["alpha"]) ? $color["alpha"] : 1;
        if (isset($this->_current_opacity)) {
            $alpha *= $this->_current_opacity;
        }

        $this->_last_fill_color = $color;

        if (isset($color[3])) {
            $type = "cmyk";
            list($c1, $c2, $c3, $c4) = [$color[0], $color[1], $color[2], $color[3]];
        } elseif (isset($color[2])) {
            $type = "rgb";
            list($c1, $c2, $c3, $c4) = [$color[0], $color[1], $color[2], 0];
        } else {
            $type = "gray";
            list($c1, $c2, $c3, $c4) = [$color[0], $color[1], 0, 0];
        }

        $this->_set_fill_opacity($alpha, "Normal");
        $this->_pdf->setcolor("fill", $type, $c1, $c2, $c3, $c4);
    }

    
    public function _set_fill_opacity($opacity, $mode = "Normal")
    {
        if ($mode === "Normal" && isset($opacity)) {
            $this->_set_gstate("opacityfill=$opacity");
        }
    }

    
    public function _set_stroke_opacity($opacity, $mode = "Normal")
    {
        if ($mode === "Normal" && isset($opacity)) {
            $this->_set_gstate("opacitystroke=$opacity");
        }
    }

    public function set_opacity(float $opacity, string $mode = "Normal"): void
    {
        if ($mode === "Normal") {
            $this->_set_gstate("opacityfill=$opacity opacitystroke=$opacity");
            $this->_current_opacity = $opacity;
        }
    }

    
    public function _set_gstate($gstate_options)
    {
        if (($gstate = array_search($gstate_options, $this->_gstates)) === false) {
            $gstate = $this->_pdf->create_gstate($gstate_options);
            $this->_gstates[$gstate] = $gstate_options;
        }

        return $this->_pdf->set_gstate($gstate);
    }

    public function set_default_view($view, $options = [])
    {
        
        
        
        
    }

    
    protected function _load_font($font, $encoding = null, $options = "")
    {
        
        $baseFont = basename($font);
        $isNativeFont = false;
        $lcBaseFont = strtolower($baseFont);
        if (isset(self::$nativeFontsToPDFLib[$lcBaseFont])) {
            $baseFont = self::$nativeFontsToPDFLib[$lcBaseFont];
            $isNativeFont = true;
        }

        
        if (!$isNativeFont) {
            $options .= " embedding=true";
        }

        $options .= " autosubsetting=" . ($this->_dompdf->getOptions()->getIsFontSubsettingEnabled() === false ? "false" : "true");

        if (is_null($encoding)) {
            
            
            if (strlen($this->_dompdf->getOptions()->getPdflibLicense()) > 0) {
                $encoding = "unicode";
            } else {
                $encoding = "auto";
            }
        }

        $key = "$font:$encoding:$options";
        if (isset($this->_fonts[$key])) {
            return $this->_fonts[$key];
        }

        
        if ($isNativeFont) {
            $this->_fonts[$key] = $this->_pdf->load_font($baseFont, $encoding, $options);
            return $this->_fonts[$key];
        }

        $fontOutline = $this->getPDFLibParameter("FontOutline", 1);
        if ($fontOutline === "" || $fontOutline < 0) {
            $families = $this->_dompdf->getFontMetrics()->getFontFamilies();
            foreach ($families as $files) {
                foreach ($files as $file) {
                    $face = basename($file);
                    $afm = null;

                    if (isset($this->_fontsFiles[$face])) {
                        continue;
                    }

                    
                    if (file_exists("$file.ttf")) {
                        $outline = "$file.ttf";
                    } elseif (file_exists("$file.TTF")) {
                        $outline = "$file.TTF";
                    } elseif (file_exists("$file.pfb")) {
                        $outline = "$file.pfb";
                        if (file_exists("$file.afm")) {
                            $afm = "$file.afm";
                        }
                    } elseif (file_exists("$file.PFB")) {
                        $outline = "$file.PFB";
                        if (file_exists("$file.AFM")) {
                            $afm = "$file.AFM";
                        }
                    } else {
                        continue;
                    }

                    $this->_fontsFiles[$face] = true;

                    if ($this->getPDFLibMajorVersion() >= 9) {
                        $this->setPDFLibParameter("FontOutline", '{' . "$face=$outline" . '}');
                    } else {
                        $this->setPDFLibParameter("FontOutline", "\{$face\}=\{$outline\}");
                    }

                    if (is_null($afm)) {
                        continue;
                    }
                    if ($this->getPDFLibMajorVersion() >= 9) {
                        $this->setPDFLibParameter("FontAFM", '{' . "$face=$afm" . '}');
                    } else {
                        $this->setPDFLibParameter("FontAFM", "\{$face\}=\{$afm\}");
                    }
                }
            }
        }

        $this->_fonts[$key] = $this->_pdf->load_font($baseFont, $encoding, $options);

        return $this->_fonts[$key];
    }

    
    protected function y($y)
    {
        return $this->_height - $y;
    }

    public function line($x1, $y1, $x2, $y2, $color, $width, $style = [], $cap = "butt")
    {
        $this->_set_line_style($width, $cap, "", $style);
        $this->_set_stroke_color($color);

        $y1 = $this->y($y1);
        $y2 = $this->y($y2);

        $this->_pdf->moveto($x1, $y1);
        $this->_pdf->lineto($x2, $y2);
        $this->_pdf->stroke();

        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    public function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = [], $cap = "butt")
    {
        $this->_set_line_style($width, $cap, "", $style);
        $this->_set_stroke_color($color);

        $y = $this->y($y);

        $this->_pdf->arc($x, $y, $r1, $astart, $aend);
        $this->_pdf->stroke();

        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    public function rectangle($x1, $y1, $w, $h, $color, $width, $style = [], $cap = "butt")
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, $cap, "", $style);

        $y1 = $this->y($y1) - $h;

        $this->_pdf->rect($x1, $y1, $w, $h);
        $this->_pdf->stroke();

        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    public function filled_rectangle($x1, $y1, $w, $h, $color)
    {
        $this->_set_fill_color($color);

        $y1 = $this->y($y1) - $h;

        $this->_pdf->rect(floatval($x1), floatval($y1), floatval($w), floatval($h));
        $this->_pdf->fill();

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
    }

    public function clipping_rectangle($x1, $y1, $w, $h)
    {
        $this->_pdf->save();

        $y1 = $this->y($y1) - $h;

        $this->_pdf->rect(floatval($x1), floatval($y1), floatval($w), floatval($h));
        $this->_pdf->clip();
    }

    public function clipping_roundrectangle($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL)
    {
        if ($this->getPDFLibMajorVersion() < 9) {
            $this->clipping_rectangle($x1, $y1, $w, $h);
            return;
        }

        $this->_pdf->save();

        
        

        $path = 0;
        
        $path = $this->_pdf->add_path_point($path, 0, 0 - $rTL + $h, "move", "");
        
        $path = $this->_pdf->add_path_point($path, 0, 0 + $rBL, "line", "");
        
        if ($rBL > 0) {
            $path = $this->_pdf->add_path_point($path, 0 + $rBL, 0, "elliptical", "radius=$rBL clockwise=false");
        }
        
        $path = $this->_pdf->add_path_point($path, 0 - $rBR + $w, 0, "line", "");
        
        if ($rBR > 0) {
            $path = $this->_pdf->add_path_point($path, 0 + $w, 0 + $rBR, "elliptical", "radius=$rBR clockwise=false");
        }
        
        $path = $this->_pdf->add_path_point($path, 0 + $w, 0 - $rTR + $h, "line", "");
        
        if ($rTR > 0) {
            $path = $this->_pdf->add_path_point($path, 0 - $rTR + $w, 0 + $h, "elliptical", "radius=$rTR clockwise=false");
        }
        
        $path = $this->_pdf->add_path_point($path, 0 + $rTL, 0 + $h, "line", "");
        
        if ($rTL > 0) {
            $path = $this->_pdf->add_path_point($path, 0, 0 - $rTL + $h, "elliptical", "radius=$rTL clockwise=false");
        }
        $this->_pdf->draw_path($path, $x1, $this->_height-$y1-$h, "clip=true");
    }

    public function clipping_polygon(array $points): void
    {
        $this->_pdf->save();

        $y = $this->y(array_pop($points));
        $x = array_pop($points);
        $this->_pdf->moveto($x, $y);

        while (count($points) > 1) {
            $y = $this->y(array_pop($points));
            $x = array_pop($points);
            $this->_pdf->lineto($x, $y);
        }

        $this->_pdf->closepath();
        $this->_pdf->clip();
    }

    public function clipping_end()
    {
        $this->_pdf->restore();
    }

    public function save()
    {
        $this->_pdf->save();
    }

    function restore()
    {
        $this->_pdf->restore();
    }

    public function rotate($angle, $x, $y)
    {
        $pdf = $this->_pdf;
        $pdf->translate($x, $this->_height - $y);
        $pdf->rotate(-$angle);
        $pdf->translate(-$x, -$this->_height + $y);
    }

    public function skew($angle_x, $angle_y, $x, $y)
    {
        $pdf = $this->_pdf;
        $pdf->translate($x, $this->_height - $y);
        $pdf->skew($angle_y, $angle_x); 
        $pdf->translate(-$x, -$this->_height + $y);
    }

    public function scale($s_x, $s_y, $x, $y)
    {
        $pdf = $this->_pdf;
        $pdf->translate($x, $this->_height - $y);
        $pdf->scale($s_x, $s_y);
        $pdf->translate(-$x, -$this->_height + $y);
    }

    public function translate($t_x, $t_y)
    {
        $this->_pdf->translate($t_x, -$t_y);
    }

    public function transform($a, $b, $c, $d, $e, $f)
    {
        $this->_pdf->concat($a, $b, $c, $d, $e, $f);
    }

    public function polygon($points, $color, $width = null, $style = [], $fill = false)
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);

        if (!$fill && isset($width)) {
            $this->_set_line_style($width, "square", "miter", $style);
        }

        $y = $this->y(array_pop($points));
        $x = array_pop($points);
        $this->_pdf->moveto($x, $y);

        while (count($points) > 1) {
            $y = $this->y(array_pop($points));
            $x = array_pop($points);
            $this->_pdf->lineto($x, $y);
        }

        if ($fill) {
            $this->_pdf->fill();
        } else {
            $this->_pdf->closepath_stroke();
        }

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    public function circle($x, $y, $r, $color, $width = null, $style = [], $fill = false)
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);

        if (!$fill && isset($width)) {
            $this->_set_line_style($width, "round", "round", $style);
        }

        $y = $this->y($y);

        $this->_pdf->circle($x, $y, $r);

        if ($fill) {
            $this->_pdf->fill();
        } else {
            $this->_pdf->stroke();
        }

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    
    protected function _convert_to_png($image_url, $type)
    {
        $filename = Cache::getTempImage($image_url);

        if ($filename !== null && file_exists($filename)) {
            return $filename;
        }
 
        $func_name = "imagecreatefrom$type";

        set_error_handler([Helpers::class, "record_warnings"]);

        if (method_exists(Helpers::class, $func_name)) {
            $func_name = [Helpers::class, $func_name];
        } elseif (!function_exists($func_name)) {
            throw new Exception("Function $func_name() not found.  Cannot convert $type image: $image_url.  Please install the image PHP extension.");
        }

        try {
            $im = call_user_func($func_name, $image_url);

            if ($im) {
                imageinterlace($im, false);

                $tmp_dir = $this->_dompdf->getOptions()->getTempDir();
                $tmp_name = @tempnam($tmp_dir, "{$type}_dompdf_img_");
                @unlink($tmp_name);
                $filename = "$tmp_name.png";

                imagepng($im, $filename);
                imagedestroy($im);
            } else {
                $filename = null;
            }
        } finally {
            restore_error_handler();
        }

        if ($filename !== null) {
            Cache::addTempImage($image_url, $filename);
        }

        return $filename;
    }

    public function image($img, $x, $y, $w, $h, $resolution = "normal")
    {
        $w = (int)$w;
        $h = (int)$h;

        $img_type = Cache::detect_type($img, $this->get_dompdf()->getHttpContext());

        
        if (substr($img, 0, 7) === "file://") {
            $img = substr($img, 7);
        }

        if (!isset($this->_imgs[$img])) {
            switch (strtolower($img_type)) {
                case "webp":
                    $img = $this->_convert_to_png($img, $img_type);
                    if ($img === null) {
                        $img = Cache::$broken_image;
                    }
                    $this->image($img, $x, $y, $w, $h, $resolution);
                    return;
                case "gif":
                    if ($this->getPDFLibMajorVersion() >= 10) {
                        $img = $this->_convert_to_png($img, $img_type);
                        if ($img === null) {
                            $img = Cache::$broken_image;
                        }
                        $this->image($img, $x, $y, $w, $h, $resolution);
                        return;
                    }
                case "bmp":
                
                case "jpeg":
                
                case "png":
                    $image_load_response = $this->_pdf->load_image($img_type, $img, "");
                    break;
                case "svg":
                    $image_load_response = $this->_pdf->load_graphics($img_type, $img, "");
                    break;
                default:
                    
                    $this->image(Cache::$broken_image, $x, $y, $w, $h, $resolution);
                    return;
            }
            if ($image_load_response === 0) {
                
                $error = $this->_pdf->get_errmsg();
                return;
            }
            $this->_imgs[$img] = $image_load_response;
        }

        $img = $this->_imgs[$img];

        $y = $this->y($y) - $h;
        if (strtolower($img_type) === "svg") {
            $this->_pdf->fit_graphics($img, $x, $y, 'boxsize={' . "$w $h" . '} fitmethod=entire');
        } else {
            $this->_pdf->fit_image($img, $x, $y, 'boxsize={' . "$w $h" . '} fitmethod=entire');
        }
    }

    public function text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_spacing = 0, $char_spacing = 0, $angle = 0)
    {
        if ($size == 0) {
            return;
        }

        $fh = $this->_load_font($font);

        $this->_pdf->setfont($fh, $size);
        $this->_set_fill_color($color);

        $y = $this->y($y) - $this->get_font_height($font, $size);

        $word_spacing = (float)$word_spacing;
        $char_spacing = (float)$char_spacing;
        $angle = -(float)$angle;

        $this->_pdf->fit_textline($text, $x, $y, "rotate=$angle wordspacing=$word_spacing charspacing=$char_spacing ");

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
    }

    public function javascript($code)
    {
        if (strlen($this->_dompdf->getOptions()->getPdflibLicense()) > 0) {
            $this->_pdf->create_action("JavaScript", $code);
        }
    }

    public function add_named_dest($anchorname)
    {
        $this->_pdf->add_nameddest($anchorname, "");
    }

    public function add_link($url, $x, $y, $width, $height)
    {
        $y = $this->y($y) - $height;
        if (strpos($url, '#') === 0) {
            
            $name = substr($url, 1);
            if ($name) {
                $this->_pdf->create_annotation($x, $y, $x + $width, $y + $height, 'Link',
                    "contents={$url} destname=" . substr($url, 1) . " linewidth=0");
            }
        } else {
            
            $action = $this->_pdf->create_action("URI", "url={{$url}}");
            
            if ($action !== 0) {
                $this->_pdf->create_annotation($x, $y, $x + $width, $y + $height, 'Link', "contents={{$url}} action={activate=$action} linewidth=0");
            }
        }
    }

    public function font_supports_char(string $font, string $char): bool
    {
        if ($char === "") {
            return true;
        }

        $fh = $this->_load_font($font);
        if ($fh === 0) {
            return false;
        }
        $this->_pdf->setfont($fh, 10);

        
        
        
        $char_code = Helpers::uniord($char, "UTF-8");
        $options = "unicode=$char_code";
        $glyphid = (int) $this->_pdf->info_font($fh, "glyphid", $options);

        return $glyphid !== -1;
    }

    public function get_text_width($text, $font, $size, $word_spacing = 0.0, $letter_spacing = 0.0)
    {
        if ($size == 0) {
            return 0.0;
        }

        $fh = $this->_load_font($font);

        
        $num_spaces = mb_substr_count($text, " ");
        $delta = $word_spacing * $num_spaces;

        if ($letter_spacing) {
            $num_chars = mb_strlen($text);
            $delta += $num_chars * $letter_spacing;
        }

        return $this->_pdf->stringwidth($text, $fh, $size) + $delta;
    }

    public function get_font_height($font, $size)
    {
        if ($size == 0) {
            return 0.0;
        }

        $fh = $this->_load_font($font);

        $this->_pdf->setfont($fh, $size);

        $asc = $this->_pdf->info_font($fh, "ascender", "fontsize=$size");
        $desc = $this->_pdf->info_font($fh, "descender", "fontsize=$size");

        
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();

        return (abs($asc) + abs($desc)) * $ratio;
    }

    public function get_font_baseline($font, $size)
    {
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();

        return $this->get_font_height($font, $size) / $ratio * 1.1;
    }

    
    public function page_script($callback): void
    {
        if (is_string($callback)) {
            $this->processPageScript(function (
                int $PAGE_NUM,
                int $PAGE_COUNT,
                self $pdf,
                FontMetrics $fontMetrics
            ) use ($callback) {
                eval($callback);
            });
            return;
        }

        $this->processPageScript($callback);
    }

    public function page_text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0)
    {
        $this->processPageScript(function (int $pageNumber, int $pageCount) use ($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle) {
            $text = str_replace(
                ["{PAGE_NUM}", "{PAGE_COUNT}"],
                [$pageNumber, $pageCount],
                $text
            );
            $this->text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
        });
    }

    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = [])
    {
        $this->processPageScript(function () use ($x1, $y1, $x2, $y2, $color, $width, $style) {
            $this->line($x1, $y1, $x2, $y2, $color, $width, $style);
        });
    }

    public function new_page()
    {
        
        $this->_place_objects();

        $this->_pdf->suspend_page("");
        $this->_pdf->begin_page_ext($this->_width, $this->_height, "");
        $this->_page_number = ++$this->_page_count;
    }

    protected function processPageScript(callable $callback): void
    {
        $this->_pdf->suspend_page("");

        for ($p = 1; $p <= $this->_page_count; $p++) {
            $this->_pdf->resume_page("pagenumber=$p");

            $fontMetrics = $this->_dompdf->getFontMetrics();
            $callback($p, $this->_page_count, $this, $fontMetrics);

            $this->_pdf->suspend_page("");
        }

        $this->_pdf->resume_page("pagenumber=" . $this->_page_number);
    }

    
    public function stream($filename = "document.pdf", $options = [])
    {
        if (headers_sent()) {
            die("Unable to stream pdf: headers already sent");
        }

        if (!isset($options["compress"])) {
            $options["compress"] = true;
        }
        if (!isset($options["Attachment"])) {
            $options["Attachment"] = true;
        }

        if ($options["compress"]) {
            $this->setPDFLibValue("compress", 6);
        } else {
            $this->setPDFLibValue("compress", 0);
        }

        $this->_close();

        $data = "";

        if (self::$IN_MEMORY) {
            $data = $this->_pdf->get_buffer();
            $size = mb_strlen($data, "8bit");
        } else {
            $size = filesize($this->_file);
        }

        header("Cache-Control: private");
        header("Content-Type: application/pdf");
        header("Content-Length: " . $size);

        $filename = str_replace(["\n", "'"], "", basename($filename, ".pdf")) . ".pdf";
        $attachment = $options["Attachment"] ? "attachment" : "inline";
        header(Helpers::buildContentDispositionHeader($attachment, $filename));

        if (self::$IN_MEMORY) {
            echo $data;
        } else {
            
            $chunk = (1 << 21); 
            $fh = fopen($this->_file, "rb");
            if (!$fh) {
                throw new Exception("Unable to load temporary PDF file: " . $this->_file);
            }

            while (!feof($fh)) {
                echo fread($fh, $chunk);
            }
            fclose($fh);

            
            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[pdflib stream unlink ' . $this->_file . ']';
            }
            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($this->_file);
            }
            $this->_file = null;
            unset($this->_file);
        }

        flush();
    }

    public function output($options = [])
    {
        if (!isset($options["compress"])) {
            $options["compress"] = true;
        }

        if ($options["compress"]) {
            $this->setPDFLibValue("compress", 6);
        } else {
            $this->setPDFLibValue("compress", 0);
        }

        $this->_close();

        if (self::$IN_MEMORY) {
            $data = $this->_pdf->get_buffer();
        } else {
            $data = file_get_contents($this->_file);

            
            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[pdflib output unlink ' . $this->_file . ']';
            }
            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($this->_file);
            }
            $this->_file = null;
            unset($this->_file);
        }

        return $data;
    }

    
    protected function getPDFLibParameter($keyword, $optlist = "")
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->_pdf->get_option($keyword, "");
        }

        return $this->_pdf->get_parameter($keyword, $optlist);
    }

    
    protected function setPDFLibParameter($keyword, $value)
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->_pdf->set_option($keyword . "=" . $value);
        }

        return $this->_pdf->set_parameter($keyword, $value);
    }

    
    protected function getPDFLibValue($keyword, $optlist = "")
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->getPDFLibParameter($keyword, $optlist);
        }

        return $this->_pdf->get_value($keyword);
    }

    
    protected function setPDFLibValue($keyword, $value)
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->setPDFLibParameter($keyword, $value);
        }

        return $this->_pdf->set_value($keyword, $value);
    }

    
    protected function getPDFLibMajorVersion()
    {
        if (is_null(self::$MAJOR_VERSION)) {
            if (method_exists($this->_pdf, "get_option")) {
                self::$MAJOR_VERSION = abs(intval($this->_pdf->get_option("major", "")));
            } else {
                self::$MAJOR_VERSION = abs(intval($this->_pdf->get_value("major", "")));
            }
        }

        return self::$MAJOR_VERSION;
    }
}


PDFLib::$PAPER_SIZES = CPDF::$PAPER_SIZES;
