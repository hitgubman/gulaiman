<?php

namespace Dompdf;


interface Canvas
{
    
    public function __construct($paper = "letter", string $orientation = "portrait", ?Dompdf $dompdf = null);

    
    function get_dompdf();

    
    function get_page_number();

    
    function get_page_count();

    
    function set_page_count($count);

    
    function line($x1, $y1, $x2, $y2, $color, $width, $style = [], $cap = "butt");

    
    function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = [], $cap = "butt");

    
    function rectangle($x1, $y1, $w, $h, $color, $width, $style = [], $cap = "butt");

    
    function filled_rectangle($x1, $y1, $w, $h, $color);

    
    function clipping_rectangle($x1, $y1, $w, $h);

    
    function clipping_roundrectangle($x1, $y1, $w, $h, $tl, $tr, $br, $bl);

    
    public function clipping_polygon(array $points): void;

    
    function clipping_end();

    
    public function page_script($callback): void;

    
    public function page_text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0);

    
    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = []);

    
    function save();

    
    function restore();

    
    function rotate($angle, $x, $y);

    
    function skew($angle_x, $angle_y, $x, $y);

    
    function scale($s_x, $s_y, $x, $y);

    
    function translate($t_x, $t_y);

    
    function transform($a, $b, $c, $d, $e, $f);

    
    function polygon($points, $color, $width = null, $style = [], $fill = false);

    
    function circle($x, $y, $r, $color, $width = null, $style = [], $fill = false);

    
    function image($img, $x, $y, $w, $h, $resolution = "normal");

    
    function text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0);

    
    function add_named_dest($anchorname);

    
    function add_link($url, $x, $y, $width, $height);

    
    public function add_info(string $label, string $value): void;

    
    function font_supports_char(string $font, string $char): bool;

    
    function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0);

    
    function get_font_height($font, $size);

    
    

    
    function get_font_baseline($font, $size);

    
    function get_width();

    
    function get_height();

    
    public function set_opacity(float $opacity, string $mode = "Normal"): void;

    
    function set_default_view($view, $options = []);

    
    function javascript($code);

    
    function new_page();

    
    function stream($filename, $options = []);

    
    function output($options = []);
}
