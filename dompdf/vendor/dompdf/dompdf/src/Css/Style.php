<?php

namespace Dompdf\Css;

use Dompdf\Adapter\CPDF;
use Dompdf\Css\Content\Attr;
use Dompdf\Css\Content\CloseQuote;
use Dompdf\Css\Content\ContentPart;
use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\Counters;
use Dompdf\Css\Content\NoCloseQuote;
use Dompdf\Css\Content\NoOpenQuote;
use Dompdf\Css\Content\OpenQuote;
use Dompdf\Css\Content\StringPart;
use Dompdf\Css\Content\Url;
use Dompdf\Exception;
use Dompdf\FontMetrics;
use Dompdf\Frame;
use Dompdf\Helpers;


class Style
{
    protected const CSS_IDENTIFIER = "-?[_a-zA-Z]+[_a-zA-Z0-9-]*";
    protected const CSS_INTEGER = "[+-]?\d+";
    protected const CSS_NUMBER = "[+-]?\d*\.?\d+(?:[eE][+-]?\d+)?";
    protected const CSS_STRING = "" .
        '"(?>(?:\\\\["]|[^"])*)(?<!\\\\)"|' . 
        "'(?>(?:\\\\[']|[^'])*)(?<!\\\\)'";   
    protected const CSS_VAR = "var\((([^()]|(?R))*)\)";

    
    protected const CSS_MATH_FUNCTIONS = [
        
        "calc" => true,
        
        "min" => true,
        "max" => true,
        "clamp" => true,
        
        "round" => true,                          
        "mod" => true,
        "rem" => true,
        
        "sin" => true,
        "cos" => true,
        "tan" => true,
        "asin" => true,
        "acos" => true,
        "atan" => true,
        "atan2" => true,
        
        "pow" => true,
        "sqrt" => true,
        "hypot" => true,
        "log" => true,
        "exp" => true,
        
        "abs" => true,
        "sign" => true
    ];

    
    protected const CUSTOM_IDENT_FORBIDDEN = ["inherit", "initial", "unset", "default"];

    
    public static $default_font_size = 12;

    
    public static $default_line_height = 1.2;

    
    public static $font_size_keywords = [
        "xx-small" => 0.6, 
        "x-small" => 0.75, 
        "small" => 0.889, 
        "medium" => 1, 
        "large" => 1.2, 
        "x-large" => 1.5, 
        "xx-large" => 2.0, 
    ];

    
    public const TEXT_ALIGN_KEYWORDS = ["left", "right", "center", "justify"];

    
    public const VERTICAL_ALIGN_KEYWORDS = ["baseline", "bottom", "middle",
        "sub", "super", "text-bottom", "text-top", "top"];

    
    public const BLOCK_LEVEL_TYPES = [
        "block",
        
        "list-item",
        
        
        "table"
    ];

    
    public const INLINE_LEVEL_TYPES = [
        "inline",
        "inline-block",
        
        
        "inline-table"
    ];

    
    public const TABLE_INTERNAL_TYPES = [
        "table-row-group",
        "table-header-group",
        "table-footer-group",
        "table-row",
        "table-cell",
        "table-column-group",
        "table-column",
        "table-caption"
    ];

    
    public const INLINE_TYPES = ["inline"];

    
    public const BLOCK_TYPES = ["block", "inline-block", "table-cell", "list-item"];

    
    public const TABLE_TYPES = ["table", "inline-table"];

    
    protected static $valid_display_types = [];

    
    public const POSITIONED_TYPES = ["relative", "absolute", "fixed"];

    
    public const BORDER_STYLES = [
        "none", "hidden",
        "dotted", "dashed", "solid",
        "double", "groove", "ridge", "inset", "outset"
    ];

    
    protected const OUTLINE_STYLES = [
        "auto", "none",
        "dotted", "dashed", "solid",
        "double", "groove", "ridge", "inset", "outset"
    ];

    
    protected static $_props_shorthand = [
        "background" => [
            "background_image",
            "background_position",
            "background_size",
            "background_repeat",
            
            
            "background_attachment",
            "background_color"
        ],
        "border" => [
            "border_top_width",
            "border_right_width",
            "border_bottom_width",
            "border_left_width",
            "border_top_style",
            "border_right_style",
            "border_bottom_style",
            "border_left_style",
            "border_top_color",
            "border_right_color",
            "border_bottom_color",
            "border_left_color"
        ],
        "border_top" => [
            "border_top_width",
            "border_top_style",
            "border_top_color"
        ],
        "border_right" => [
            "border_right_width",
            "border_right_style",
            "border_right_color"
        ],
        "border_bottom" => [
            "border_bottom_width",
            "border_bottom_style",
            "border_bottom_color"
        ],
        "border_left" => [
            "border_left_width",
            "border_left_style",
            "border_left_color"
        ],
        "border_width" => [
            "border_top_width",
            "border_right_width",
            "border_bottom_width",
            "border_left_width"
        ],
        "border_style" => [
            "border_top_style",
            "border_right_style",
            "border_bottom_style",
            "border_left_style"
        ],
        "border_color" => [
            "border_top_color",
            "border_right_color",
            "border_bottom_color",
            "border_left_color"
        ],
        "border_radius" => [
            "border_top_left_radius",
            "border_top_right_radius",
            "border_bottom_right_radius",
            "border_bottom_left_radius"
        ],
        "font" => [
            "font_family",
            "font_size",
            
            "font_style",
            "font_variant",
            "font_weight",
            "line_height"
        ],
        "inset" => [
            "top",
            "right",
            "bottom",
            "left"
        ],
        "list_style" => [
            "list_style_image",
            "list_style_position",
            "list_style_type"
        ],
        "margin" => [
            "margin_top",
            "margin_right",
            "margin_bottom",
            "margin_left"
        ],
        "padding" => [
            "padding_top",
            "padding_right",
            "padding_bottom",
            "padding_left"
        ],
        "outline" => [
            "outline_width",
            "outline_style",
            "outline_color"
        ]
    ];

    
    protected static $_props_alias = [
        "word_wrap"                           => "overflow_wrap",
        "_dompdf_background_image_resolution" => "background_image_resolution",
        "_dompdf_image_resolution"            => "image_resolution",
        "_webkit_transform"                   => "transform",
        "_webkit_transform_origin"            => "transform_origin"
    ];

    
    protected static $_defaults = null;

    
    protected static $_inherited = [
        "azimuth" => true,
        "background_image_resolution" => true,
        "border_collapse" => true,
        "border_spacing" => true,
        "caption_side" => true,
        "color" => true,
        "cursor" => true,
        "direction" => true,
        "elevation" => true,
        "empty_cells" => true,
        "font_family" => true,
        "font_size" => true,
        "font_style" => true,
        "font_variant" => true,
        "font_weight" => true,
        "font" => true,
        "image_resolution" => true,
        "letter_spacing" => true,
        "line_height" => true,
        "list_style_image" => true,
        "list_style_position" => true,
        "list_style_type" => true,
        "list_style" => true,
        "orphans" => true,
        "overflow_wrap" => true,
        "pitch_range" => true,
        "pitch" => true,
        "quotes" => true,
        "richness" => true,
        "speak_header" => true,
        "speak_numeral" => true,
        "speak_punctuation" => true,
        "speak" => true,
        "speech_rate" => true,
        "stress" => true,
        "text_align" => true,
        "text_indent" => true,
        "text_transform" => true,
        "visibility" => true,
        "voice_family" => true,
        "volume" => true,
        "white_space" => true,
        "widows" => true,
        "word_break" => true,
        "word_spacing" => true
    ];

    
    protected static $_dependency_map = [
        "border_top_style" => [
            "border_top_width"
        ],
        "border_bottom_style" => [
            "border_bottom_width"
        ],
        "border_left_style" => [
            "border_left_width"
        ],
        "border_right_style" => [
            "border_right_width"
        ],
        "direction" => [
            "text_align"
        ],
        "font_size" => [
            "background_position",
            "background_size",
            "border_top_width",
            "border_right_width",
            "border_bottom_width",
            "border_left_width",
            "border_top_left_radius",
            "border_top_right_radius",
            "border_bottom_right_radius",
            "border_bottom_left_radius",
            "letter_spacing",
            "line_height",
            "margin_top",
            "margin_right",
            "margin_bottom",
            "margin_left",
            "outline_width",
            "outline_offset",
            "padding_top",
            "padding_right",
            "padding_bottom",
            "padding_left",
            "word_spacing",
            "width",
            "height",
            "min-width",
            "min-height",
            "max-width",
            "max-height"
        ],
        "float" => [
            "display"
        ],
        "position" => [
            "display"
        ],
        "outline_style" => [
            "outline_width"
        ]
    ];

    
    protected static $_dependent_props = [];

    
    protected static $_methods_cache = [];

    
    protected $_stylesheet;

    
    protected $_media_queries;

    
    protected $_important_props = [];

    
    protected $_props = [];

    
    protected $_props_specified = [];

    
    protected $_props_computed = [];

    
    protected $_props_used = [];

    
    protected $non_final_used = [];

    
    protected $_prop_stack = [];

    
    protected $_var_stack = [];

    
    protected $parent_style;

    
    protected $_frame;

    
    protected $_origin = Stylesheet::ORIG_AUTHOR;

    
    private $_computed_bottom_spacing = null;

    
    private $has_border_radius_cache = null;

    
    private $resolved_border_radius = null;

    
    private $fontMetrics;

    
    public function __construct(Stylesheet $stylesheet, int $origin = Stylesheet::ORIG_AUTHOR)
    {
        $this->fontMetrics = $stylesheet->getFontMetrics();

        $this->_stylesheet = $stylesheet;
        $this->_media_queries = [];
        $this->_origin = $origin;
        $this->parent_style = null;

        if (!isset(self::$_defaults)) {

            
            $d =& self::$_defaults;

            
            
            
            
            $d["azimuth"] = "center";
            $d["background_attachment"] = "scroll";
            $d["background_color"] = "transparent";
            $d["background_image"] = "none";
            $d["background_image_resolution"] = "normal";
            $d["background_position"] = [0.0, 0.0];
            $d["background_repeat"] = "repeat";
            $d["background"] = "";
            $d["border_collapse"] = "separate";
            $d["border_color"] = "";
            $d["border_spacing"] = [0.0, 0.0];
            $d["border_style"] = "";
            $d["border_top"] = "";
            $d["border_right"] = "";
            $d["border_bottom"] = "";
            $d["border_left"] = "";
            $d["border_top_color"] = "currentcolor";
            $d["border_right_color"] = "currentcolor";
            $d["border_bottom_color"] = "currentcolor";
            $d["border_left_color"] = "currentcolor";
            $d["border_top_style"] = "none";
            $d["border_right_style"] = "none";
            $d["border_bottom_style"] = "none";
            $d["border_left_style"] = "none";
            $d["border_top_width"] = "medium";
            $d["border_right_width"] = "medium";
            $d["border_bottom_width"] = "medium";
            $d["border_left_width"] = "medium";
            $d["border_width"] = "";
            $d["border_bottom_left_radius"] = 0.0;
            $d["border_bottom_right_radius"] = 0.0;
            $d["border_top_left_radius"] = 0.0;
            $d["border_top_right_radius"] = 0.0;
            $d["border_radius"] = "";
            $d["border"] = "";
            $d["bottom"] = "auto";
            $d["caption_side"] = "top";
            $d["clear"] = "none";
            $d["clip"] = "auto";
            $d["color"] = "#000000";
            $d["content"] = "normal";
            $d["counter_increment"] = "none";
            $d["counter_reset"] = "none";
            $d["cue_after"] = "none";
            $d["cue_before"] = "none";
            $d["cue"] = "";
            $d["cursor"] = "auto";
            $d["direction"] = "ltr";
            $d["display"] = "inline";
            $d["elevation"] = "level";
            $d["empty_cells"] = "show";
            $d["float"] = "none";
            $d["font_family"] = $stylesheet->get_dompdf()->getOptions()->getDefaultFont();
            $d["font_size"] = "medium";
            $d["font_style"] = "normal";
            $d["font_variant"] = "normal";
            $d["font_weight"] = 400;
            $d["font"] = "";
            $d["height"] = "auto";
            $d["image_resolution"] = "normal";
            $d["inset"] = "";
            $d["left"] = "auto";
            $d["letter_spacing"] = "normal";
            $d["line_height"] = "normal";
            $d["list_style_image"] = "none";
            $d["list_style_position"] = "outside";
            $d["list_style_type"] = "disc";
            $d["list_style"] = "";
            $d["margin_right"] = 0.0;
            $d["margin_left"] = 0.0;
            $d["margin_top"] = 0.0;
            $d["margin_bottom"] = 0.0;
            $d["margin"] = "";
            $d["max_height"] = "none";
            $d["max_width"] = "none";
            $d["min_height"] = "auto";
            $d["min_width"] = "auto";
            $d["orphans"] = 2;
            $d["outline_color"] = "currentcolor"; 
            $d["outline_style"] = "none";
            $d["outline_width"] = "medium";
            $d["outline_offset"] = 0.0;
            $d["outline"] = "";
            $d["overflow"] = "visible";
            $d["overflow_wrap"] = "normal";
            $d["padding_top"] = 0.0;
            $d["padding_right"] = 0.0;
            $d["padding_bottom"] = 0.0;
            $d["padding_left"] = 0.0;
            $d["padding"] = "";
            $d["page_break_after"] = "auto";
            $d["page_break_before"] = "auto";
            $d["page_break_inside"] = "auto";
            $d["pause_after"] = "0";
            $d["pause_before"] = "0";
            $d["pause"] = "";
            $d["pitch_range"] = "50";
            $d["pitch"] = "medium";
            $d["play_during"] = "auto";
            $d["position"] = "static";
            $d["quotes"] = "auto";
            $d["richness"] = "50";
            $d["right"] = "auto";
            $d["size"] = "auto"; 
            $d["speak_header"] = "once";
            $d["speak_numeral"] = "continuous";
            $d["speak_punctuation"] = "none";
            $d["speak"] = "normal";
            $d["speech_rate"] = "medium";
            $d["stress"] = "50";
            $d["table_layout"] = "auto";
            $d["text_align"] = "";
            $d["text_decoration"] = "none";
            $d["text_indent"] = 0.0;
            $d["text_transform"] = "none";
            $d["top"] = "auto";
            $d["unicode_bidi"] = "normal";
            $d["vertical_align"] = "baseline";
            $d["visibility"] = "visible";
            $d["voice_family"] = "";
            $d["volume"] = "medium";
            $d["white_space"] = "normal";
            $d["widows"] = 2;
            $d["width"] = "auto";
            $d["word_break"] = "normal";
            $d["word_spacing"] = "normal";
            $d["z_index"] = "auto";

            
            $d["opacity"] = 1.0;
            $d["background_size"] = ["auto", "auto"];
            $d["transform"] = [];
            $d["transform_origin"] = ["50%", "50%", 0.0];

            
            $d["src"] = "";
            $d["unicode_range"] = "";

            
            $d["_dompdf_keep"] = "";

            
            foreach (self::$_dependency_map as $props) {
                foreach ($props as $prop) {
                    self::$_dependent_props[$prop] = true;
                }
            }

            
            self::$valid_display_types = [
                "none"                => true,
                "-dompdf-br"          => true,
                "-dompdf-image"       => true,
                "-dompdf-list-bullet" => true,
                "-dompdf-page"        => true
            ];
            foreach (self::BLOCK_LEVEL_TYPES as $val) {
                self::$valid_display_types[$val] = true;
            }
            foreach (self::INLINE_LEVEL_TYPES as $val) {
                self::$valid_display_types[$val] = true;
            }
            foreach (self::TABLE_INTERNAL_TYPES as $val) {
                self::$valid_display_types[$val] = true;
            }
        }
    }

    
    public function reset(): void
    {
        foreach (array_keys($this->non_final_used) as $prop) {
            unset($this->_props_used[$prop]);
        }

        $this->non_final_used = [];
    }

    
    public function set_media_queries(array $media_queries): void
    {
        $this->_media_queries = $media_queries;
    }

    
    public function get_media_queries(): array
    {
        return $this->_media_queries;
    }

    
    public function set_frame(Frame $frame): void
    {
        $this->_frame = $frame;
    }

    
    public function get_frame(): ?Frame
    {
        return $this->_frame;
    }

    
    public function set_origin(int $origin): void
    {
        $this->_origin = $origin;
    }

    
    public function get_origin(): int
    {
        return $this->_origin;
    }

    
    public function get_stylesheet(): Stylesheet
    {
        return $this->_stylesheet;
    }

    public function is_custom_property(string $prop): bool
    {
        return \substr($prop, 0, 2) === "--";
    }

    public function is_absolute(): bool
    {
        $position = $this->__get("position");
        return $position === "absolute" || $position === "fixed";
    }

    public function is_in_flow(): bool
    {
        $float = $this->__get("float");
        return $float === "none" && !$this->is_absolute();
    }

    
    public function length_in_pt($length, ?float $ref_size = null)
    {
        $font_size = $this->__get("font_size");
        $ref_size = $ref_size ?? $font_size;

        if (!\is_array($length)) {
            $length = [$length];
        }

        $ret = 0.0;

        foreach ($length as $l) {
            if ($l === "auto" || $l === "none") {
                return $l;
            }

            
            if (is_numeric($l)) {
                $ret += (float) $l;
                continue;
            }

            $val = $this->single_length_in_pt((string) $l, $ref_size, $font_size);
            $ret += $val ?? 0;
        }

        return $ret;
    }

    
    protected function single_length_in_pt(string $l, float $ref_size = 0, ?float $font_size = null): ?float
    {
        static $cache = [];

        $font_size = $font_size ?? $this->__get("font_size");

        $key = "$l/$ref_size/$font_size";

        if (\array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $number = self::CSS_NUMBER;
        $pattern = "/^($number)([a-zA-Z%]*)?$/";

        if (!preg_match($pattern, $l, $matches)) {
            $ident = self::CSS_IDENTIFIER;
            $pattern = "/^($ident)\(.*\)$/i";
            if (preg_match($pattern, $l)) {
                $value = $this->evaluate_func($this->parse_func($l), $ref_size, $font_size);
                return $cache[$key] = $value;
            }
            return null;
        }

        $v = (float) $matches[1];
        $unit = strtolower($matches[2]);

        if ($unit === "") {
            
            
            $value = $v;
        }

        elseif ($unit === "%") {
            $value = $v / 100 * $ref_size;
        }

        elseif ($unit === "px") {
            $dpi = $this->_stylesheet->get_dompdf()->getOptions()->getDpi();
            $value = ($v * 72) / $dpi;
        }

        elseif ($unit === "pt") {
            $value = $v;
        }

        elseif ($unit === "rem") {
            $tree = $this->_stylesheet->get_dompdf()->getTree();
            $root_style = $tree !== null ? $tree->get_root()->get_style() : null;
            $root_font_size = $root_style === null || $root_style === $this
                ? $font_size
                : $root_style->__get("font_size");
            $value = $v * $root_font_size;

            
            
            
            if ($root_style === null) {
                return $value;
            }
        }

        elseif ($unit === "em") {
            $value = $v * $font_size;
        }

        elseif ($unit === "cm") {
            $value = $v * 72 / 2.54;
        }

        elseif ($unit === "mm") {
            $value = $v * 72 / 25.4;
        }

        elseif ($unit === "ex") {
            
            $value = $v * $font_size / 2;
        }

        elseif ($unit === "in") {
            $value = $v * 72;
        }

        elseif ($unit === "pc") {
            $value = $v * 12;
        }

        else {
            
            $value = null;
        }

        return $cache[$key] = $value;
    }

    
    private function parse_func(string $expr): array
    {
        if (substr_count($expr, '(') !== substr_count($expr, ')')) {
            return [];
        }

        $expr = str_replace(['(', ')', '*', '/', ','], [' ( ', ' ) ', ' * ', ' / ', ' , '], $expr);
        $expr = trim(preg_replace('/\s+/', ' ', $expr));

        if ($expr === '') {
            return [];
        }

        $precedence = ['*' => 3, '/' => 3, '+' => 2, '-' => 2, ',' => 1];

        $opStack = [];
        $queue = [];

        $parts = explode(' ', $expr);

        foreach ($parts as $part) {
            if ($part === '(') {
                $opStack[] = $part;
            } elseif (\array_key_exists(strtolower($part), self::CSS_MATH_FUNCTIONS)) {
                $opStack[] = strtolower($part);
            } elseif ($part === ')') {
                while (\count($opStack) > 0 && end($opStack) !== '(' && !\array_key_exists(end($opStack), self::CSS_MATH_FUNCTIONS)) {
                    $queue[] = array_pop($opStack);
                }
                if (end($opStack) === '(') {
                    array_pop($opStack);
                }
                if (\count($opStack) > 0 && \array_key_exists(end($opStack), self::CSS_MATH_FUNCTIONS)) {
                    $queue[] = array_pop($opStack);
                }
            } elseif (\array_key_exists($part, $precedence)) {
                while (\count($opStack) > 0 && end($opStack) !== '(' && $precedence[end($opStack)] >= $precedence[$part]) {
                    $queue[] = array_pop($opStack);
                }
                $opStack[] = $part;
            } else {
                $queue[] = $part;
            }
        }

        while (\count($opStack) > 0) {
            $queue[] = array_pop($opStack);
        }

        return $queue;
    }

    
    private function evaluate_func(array $rpn, float $ref_size = 0, ?float $font_size = null): ?float
    {
        if (\count($rpn) === 0) {
            return null;
        }

        $ops = ['*', '/', '+', '-', ','];

        $stack = [];

        foreach ($rpn as $part) {
            if (\array_key_exists($part, self::CSS_MATH_FUNCTIONS)) {
                $argv = array_pop($stack);
                if (!is_array($argv)) {
                    $argv = [$argv];
                }
                $argc = \count($argv);
                switch ($part) {
                    case 'abs':
                    case 'acos':
                    case 'asin':
                    case 'atan':
                    case 'cos':
                    case 'exp':
                    case 'sin':
                    case 'sqrt':
                    case 'tan':
                        if ($argc !== 1) {
                            return null;
                        }
                        $stack[] = call_user_func_array($part, $argv);
                        break;
                    case 'atan2':
                    case 'hypot':
                    case 'pow':
                        if ($argc !== 2) {
                            return null;
                        }
                        $stack[] = call_user_func_array($part, $argv);
                        break;
                    case 'log':
                        if ($argc === 1) {
                            $stack[] = log($argv[0]);
                        } elseif ($argc === 2) {
                            $stack[] = log($argv[0], $argv[1]);
                        } else {
                            return null;
                        }
                        break;
                    case 'max':
                        $stack[] = max($argv);
                        break;
                    case 'min':
                        $stack[] = min($argv);
                        break;
                    case 'mod':
                        if ($argc !== 2 || $argv[1] === 0.0) {
                            return null;
                        }
                        if ($argv[1] > 0) {
                            $stack[] = $argv[0] - floor($argv[0] / $argv[1]) * $argv[1];
                        } else {
                            $stack[] = $argv[0] - ceil($argv[0] * -1 / $argv[1]) * $argv[1] * -1 ;
                        }
                        break;
                    case 'rem':
                        if ($argc !== 2 || $argv[1] === 0.0) {
                            return null;
                        }
                        $stack[] = $argv[0] - (intval($argv[0] / $argv[1]) * $argv[1]);
                        break;
                    case 'round':
                        if ($argc !== 2 || $argv[1] === 0.0) {
                            return null;
                        }
                        if ($argv[0] >= 0) {
                            $stack[] = round($argv[0] / $argv[1], 0, PHP_ROUND_HALF_UP) * $argv[1];
                        } else {
                            $stack[] = round($argv[0] / $argv[1], 0, PHP_ROUND_HALF_DOWN) * $argv[1];
                        }
                        break;
                    case 'calc':
                        if ($argc !== 1) {
                            return null;
                        }
                        $stack[] = $argv[0];
                        break;
                    case 'clamp':
                        if ($argc !== 3) {
                            return null;
                        }
                        $stack[] = max($argv[0], min($argv[1], $argv[2]));
                        break;
                    case 'sign':
                        if ($argc !== 1) {
                            return null;
                        }
                        $stack[] = $argv[0] == 0 ? 0.0 : ($argv[0] / abs($argv[0]));
                        break;
                    default:
                        return null;
                }
            } elseif (\in_array($part, $ops, true)) {
                $rightValue = array_pop($stack);
                $leftValue = array_pop($stack);
                if ($rightValue === null || $leftValue === null) {
                    return null;
                }
                switch ($part) {
                    case '*':
                        $stack[] = $leftValue * $rightValue;
                        break;
                    case '/':
                        if ($rightValue === 0.0) {
                            return null;
                        }
                        $stack[] = $leftValue / $rightValue;
                        break;
                    case '+':
                        $stack[] = $leftValue + $rightValue;
                        break;
                    case '-':
                        $stack[] = $leftValue - $rightValue;
                        break;
                    case ',':
                        if (is_array($leftValue)) {
                            $leftValue[] = $rightValue;
                            $stack[] = $leftValue;
                        } else {
                            $stack[] = [$leftValue, $rightValue];
                        }
                        break;
                }
            } else {
                $val = $this->single_length_in_pt($part, $ref_size, $font_size);
                if ($val === null) {
                    return null;
                }
                $stack[] = $val;
            }
        }

        if (\count($stack) > 1) {
            return null;
        }

        return floatval(end($stack));
    }

    
    private function parse_var($matches) {
        $variable = is_array($matches) ? $matches[1] : $matches;

        if (\in_array($variable, $this->_var_stack, true)) {
            return null;
        }
        array_push($this->_var_stack, $variable);

        
        [$custom_prop, $fallback] = explode(',', $variable, 2) + ['', ''];
        $fallback = trim($fallback);

        
        
        $value = $this->computed($custom_prop) ?? $fallback;

        
        $pattern = self::CSS_VAR;
        $value = preg_replace_callback(
            "/$pattern/",
            [$this, "parse_var"],
            $value);

        array_pop($this->_var_stack);
        return $value ?: null;
    }

    
    public function inherit(?Style $parent = null): void
    {
        $this->parent_style = $parent;

        
        
        unset($this->_props_computed["font_size"]);
        unset($this->_props_used["font_size"]);

        if ($parent) {
            
            
            
            
            foreach ($parent->_props as $prop => $val) {
                if (
                    !isset($this->_props[$prop])
                    && (
                        isset(self::$_inherited[$prop])
                        || $this->is_custom_property($prop)
                    )
                ) {
                    $parent_val = $parent->computed($prop);

                    if ($this->is_custom_property($prop)) {
                        $this->set_prop($prop, $parent_val);
                    } else {
                        $this->_props[$prop] = $parent_val;
                        $this->_props_computed[$prop] = $parent_val;
                        $this->_props_used[$prop] = null;
                    }
                }
            }
        }

        foreach ($this->_props as $prop => $val) {
            if ($val === "inherit") {
                if ($parent && isset($parent->_props[$prop])) {
                    $parent_val = $parent->computed($prop);

                    if ($this->is_custom_property($prop)) {
                        $this->set_prop($prop, $parent_val);
                    } else {
                        $this->_props[$prop] = $parent_val;
                        $this->_props_computed[$prop] = $parent_val;
                        $this->_props_used[$prop] = null;
                    }
                } else {
                    if ($this->is_custom_property($prop)) {
                        $this->set_prop($prop, "unset");
                    } else {
                        
                        $this->_props[$prop] = self::$_defaults[$prop];
                        unset($this->_props_computed[$prop]);
                        unset($this->_props_used[$prop]);
                    }
                }
            }
        }
    }

    
    public function merge(Style $style): void
    {
        foreach ($style->_props as $prop => $val) {
            $important = isset($style->_important_props[$prop]);

            
            if (!$important && isset($this->_important_props[$prop])) {
                continue;
            }

            if ($important) {
                $this->_important_props[$prop] = true;
            }

            if ($this->is_custom_property($prop)) {
                $this->set_prop($prop, $val, $important);
            } else {
                $this->_props[$prop] = $val;
            }

            
            
            if (!isset(self::$_dependent_props[$prop])
                && \array_key_exists($prop, $style->_props_computed)
            ) {
                $this->_props_computed[$prop] = $style->_props_computed[$prop];
                $this->_props_used[$prop] = null;
            } else {
                unset($this->_props_computed[$prop]);
                unset($this->_props_used[$prop]);
            }

            if (\array_key_exists($prop, $style->_props_specified)) {
                $this->_props_specified[$prop] = true;
            }
        }

        
        foreach (array_keys($this->_props) as $prop) {
            if (!$this->is_custom_property($prop)) {
                continue;
            }
            $this->set_prop($prop, $this->_props[$prop], isset($this->_important_props[$prop]));
        }
    }

    
    public function clear_important(): void
    {
        $this->_important_props = [];
    }

    
    protected function clear_cache(string $prop): void
    {
        
        
        if ($prop === "border_top_left_radius"
            || $prop === "border_top_right_radius"
            || $prop === "border_bottom_left_radius"
            || $prop === "border_bottom_right_radius"
        ) {
            $this->has_border_radius_cache = null;
            $this->resolved_border_radius = null;
        }

        
        
        if ($prop === "margin_bottom"
            || $prop === "padding_bottom"
            || $prop === "border_bottom_width"
            || $prop === "border_bottom_style"
        ) {
            $this->_computed_bottom_spacing = null;
        }
    }

    
    public function set_prop(string $prop, $val, bool $important = false, bool $clear_dependencies = true): void
    {
        
        if (!$this->is_custom_property($prop)) {

            $prop = str_replace("-", "_", $prop);

            
            if (isset(self::$_props_alias[$prop])) {
                $prop = self::$_props_alias[$prop];
            }

            if (!isset(self::$_defaults[$prop])) {
                global $_dompdf_warnings;
                $_dompdf_warnings[] = "'$prop' is not a recognized CSS property.";
                return;
            }
        }
        $this->_props_specified[$prop] = true;

        
        
        
        if (\is_string($val)) {
            $val = trim($val);
            $lower = strtolower($val);

            if ($lower === "initial" || $lower === "inherit" || $lower === "unset") {
                $val = $lower;
            }
        }

        if (isset(self::$_props_shorthand[$prop])) {
            
            
            if ($val === "initial" || $val === "inherit" || $val === "unset") {
                foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                    $this->set_prop($sub_prop, $val, $important, $clear_dependencies);
                }
            } else {
                $method = "_set_$prop";

                
                $pattern = self::CSS_VAR;

                
                
                $this->_props[$prop] = $val;

                
                preg_match_all("/$pattern/", $val, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    if ($this->parse_var($match) === null) {
                        
                        foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                            unset($this->_props_specified[$sub_prop]);
                        }
                        return;
                    }
                }
                $val = preg_replace_callback(
                    "/$pattern/",
                    [$this, "parse_var"],
                    $val);

                if (!isset(self::$_methods_cache[$method])) {
                    self::$_methods_cache[$method] = method_exists($this, $method);
                }

                if (self::$_methods_cache[$method]) {
                    $values = $this->$method($val);

                    if ($values === []) {
                        return;
                    }

                    
                    
                    foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                        $sub_val = $values[$sub_prop] ?? self::$_defaults[$sub_prop];
                        $this->set_prop($sub_prop, $sub_val, $important, $clear_dependencies);
                        unset($this->_props_specified[$sub_prop]);
                    }
                }
            }
        } else {
            
            
            if ($prop === "word_break"
                && \is_string($val) && strcasecmp($val, "break-word") === 0
            ) {
                $val = "normal";
                $this->set_prop("overflow_wrap", "anywhere", $important, $clear_dependencies);
            }

            
            if (!$important && isset($this->_important_props[$prop])) {
                return;
            }

            if ($important) {
                $this->_important_props[$prop] = true;
            }

            
            if ($val === "unset") {
                $val = isset(self::$_inherited[$prop]) || $this->is_custom_property($prop) ? "inherit" : "initial";
            }

            
            if ($val === "initial" && !$this->is_custom_property($prop)) {
                $val = self::$_defaults[$prop];
            }

            
            
            if (\is_string($val) && \preg_match("/" . self::CSS_VAR . "/", $val)) {
                $this->_props[$prop] = $val;
            }

            $computed = $this->compute_prop($prop, $val);

            
            if ($computed === null) {
                return;
            }

            $this->_props[$prop] = $val;
            $this->_props_computed[$prop] = $computed;
            $this->_props_used[$prop] = null;

            
            if ($this->is_custom_property($prop) && !\in_array($prop, $this->_prop_stack, true)) {
                array_push($this->_prop_stack, $prop);
                $specified_props = array_filter($this->_props, function($key) {
                    return \array_key_exists($key, $this->_props_specified);
                }, ARRAY_FILTER_USE_KEY); 
                foreach ($specified_props as $specified_prop => $specified_value) {
                    if (!$this->is_custom_property($specified_prop) || strpos($specified_value, "var($prop") !== false) {
                        $this->set_prop($specified_prop, $specified_value, isset($this->_important_props[$specified_prop]), true);
                        if (isset(self::$_props_shorthand[$specified_prop])) {
                            foreach (self::$_props_shorthand[$specified_prop] as $sub_prop) {
                                if (\array_key_exists($sub_prop, $specified_props)) {
                                    $this->set_prop($sub_prop, $specified_props[$sub_prop], isset($this->_important_props[$sub_prop]), true);
                                }
                            }
                        }
                    }
                }
                array_pop($this->_prop_stack);
            }

            if ($clear_dependencies) {
                
                
                if (isset(self::$_dependency_map[$prop])) {
                    foreach (self::$_dependency_map[$prop] as $dependent) {
                        unset($this->_props_computed[$dependent]);
                        unset($this->_props_used[$dependent]);
                    }
                }

                $this->clear_cache($prop);
            }
        }
    }

    
    public function get_specified(string $prop)
    {
        
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop]) && !$this->is_custom_property($prop)) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        return $this->_props[$prop] ?? self::$_defaults[$prop];
    }

    
    public function __set(string $prop, $val)
    {
        
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop]) && !$this->is_custom_property($prop)) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset(self::$_props_shorthand[$prop])) {
            foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                $this->__set($sub_prop, $val);
            }
        } else {
            $this->_props[$prop] = $val;
            $this->_props_computed[$prop] = $val;
            $this->_props_used[$prop] = $val;

            $this->clear_cache($prop);
        }
    }

    
    public function set_used(string $prop, $val): void
    {
        
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop])) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset(self::$_props_shorthand[$prop])) {
            foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                $this->set_used($sub_prop, $val);
            }
        } else {
            $this->_props_used[$prop] = $val;
            $this->non_final_used[$prop] = true;
        }
    }

    
    public function __get(string $prop)
    {
        
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop]) && !$this->is_custom_property($prop)) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset($this->_props_used[$prop])) {
            return $this->_props_used[$prop];
        }

        $method = "_get_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        if (isset(self::$_props_shorthand[$prop])) {
            
            
            
            if (self::$_methods_cache[$method]) {
                return $this->$method();
            } else {
                return implode(" ", array_map(function ($sub_prop) {
                    $val = $this->__get($sub_prop);
                    return \is_array($val) ? implode(" ", $val) : $val;
                }, self::$_props_shorthand[$prop]));
            }
        } else {
            $computed = $this->computed($prop);
            $used = self::$_methods_cache[$method]
                ? $this->$method($computed)
                : $computed;

            $this->_props_used[$prop] = $used;
            return $used;
        }
    }

    
    protected function compute_prop(string $prop, $val)
    {
        
        
        
        if ($val === "inherit" && !$this->is_custom_property($prop)) {
            $val = self::$_defaults[$prop];
        }

        
        if (!\is_string($val)) {
            return $val;
        }

        
        $pattern = self::CSS_VAR;
        $val = preg_replace_callback(
            "/$pattern/",
            [$this, "parse_var"],
            $val);

        $method = "_compute_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        if (self::$_methods_cache[$method]) {
            return $this->$method($val);
        } elseif ($val !== "") {
            return strtolower($val);
        } else {
            return null;
        }
    }

    
    protected function computed(string $prop)
    {
        if (!\array_key_exists($prop, $this->_props_computed)) {
            if (!\array_key_exists($prop, $this->_props) && $this->is_custom_property($prop)) {
                return null;
            }
            $val = $this->_props[$prop] ?? self::$_defaults[$prop];
            $computed = $this->compute_prop($prop, $val);

            if ($computed === null) {
                if ($this->is_custom_property($prop)) {
                    return null;
                }
                $computed = $this->compute_prop($prop, self::$_defaults[$prop]);
            }

            $this->_props_computed[$prop] = $computed;
        }

        return $this->_props_computed[$prop];
    }

    
    public function computed_bottom_spacing(float $cbw)
    {
        
        
        
        
        if ($this->_computed_bottom_spacing !== null) {
            return $this->_computed_bottom_spacing;
        }
        return $this->_computed_bottom_spacing = $this->length_in_pt(
            [
                $this->margin_bottom,
                $this->padding_bottom,
                $this->border_bottom_width
            ],
            $cbw
        );
    }

    
    public function munge_color($color)
    {
        return Color::parse($color);
    }

    
    public function get_font_family_raw(): string
    {
        return trim($this->_props["font_family"], " \t\n\r\x0B\"'");
    }

    
    public function get_font_family_computed(): array
    {
        return $this->computed("font_family");
    }

    
    protected function _get_font_family($computed): string
    {
        
        
        

        
        

        $fontMetrics = $this->getFontMetrics();
        $weight = $this->__get("font_weight");
        $fontStyle = $this->__get("font_style");
        $subtype = $fontMetrics->getType($weight . ' ' . $fontStyle);

        foreach ($computed as $family) {
            $font = $fontMetrics->getFont($family, $subtype);

            if ($font !== null) {
                return $font;
            }
        }

        $font = $fontMetrics->getFont(null, $subtype);

        if ($font !== null) {
            return $font;
        }

        $specified = implode(", ", $computed);
        throw new Exception("Unable to find a suitable font replacement for: '$specified'");
    }

    
    protected function _get_font_size($computed)
    {
        
        return max($computed, 0.0);
    }

    
    protected function _get_word_spacing($computed)
    {
        if (\is_float($computed)) {
            return $computed;
        }

        
        $font_size = $this->__get("font_size");
        return $this->single_length_in_pt($computed, $font_size);
    }

    
    protected function _get_letter_spacing($computed)
    {
        if (\is_float($computed)) {
            return $computed;
        }

        
        $font_size = $this->__get("font_size");
        return $this->single_length_in_pt($computed, $font_size);
    }

    
    protected function _get_line_height($computed)
    {
        
        if (\is_float($computed)) {
            
            return max($computed, 0.0);
        }

        $font_size = $this->__get("font_size");
        $factor = $computed === "normal"
            ? self::$default_line_height
            : (float) $computed;

        return $factor * $font_size;
    }

    
    protected function get_color_value($computed, bool $current_is_parent = false)
    {
        if ($computed === "currentcolor") {
            
            if ($current_is_parent) {
                
                
                return isset($this->parent_style)
                    ? $this->parent_style->__get("color")
                    : $this->munge_color(self::$_defaults["color"]);
            }

            return $this->__get("color");
        }

        return $this->munge_color($computed) ?? "transparent";
    }

    
    protected function _get_color($computed)
    {
        return $this->get_color_value($computed, true);
    }

    
    protected function _get_background_color($computed)
    {
        return $this->get_color_value($computed);
    }

    
    protected function _get_background_image($computed): string
    {
        return $this->_stylesheet->resolve_url($computed, true);
    }

    
    protected function _get_border_top_color($computed)
    {
        return $this->get_color_value($computed);
    }

    
    protected function _get_border_right_color($computed)
    {
        return $this->get_color_value($computed);
    }

    
    protected function _get_border_bottom_color($computed)
    {
        return $this->get_color_value($computed);
    }

    
    protected function _get_border_left_color($computed)
    {
        return $this->get_color_value($computed);
    }

    
    public function get_border_properties(): array
    {
        return [
            "top" => [
                "width" => $this->__get("border_top_width"),
                "style" => $this->__get("border_top_style"),
                "color" => $this->__get("border_top_color"),
            ],
            "bottom" => [
                "width" => $this->__get("border_bottom_width"),
                "style" => $this->__get("border_bottom_style"),
                "color" => $this->__get("border_bottom_color"),
            ],
            "right" => [
                "width" => $this->__get("border_right_width"),
                "style" => $this->__get("border_right_style"),
                "color" => $this->__get("border_right_color"),
            ],
            "left" => [
                "width" => $this->__get("border_left_width"),
                "style" => $this->__get("border_left_style"),
                "color" => $this->__get("border_left_color"),
            ],
        ];
    }

    
    protected function get_border_side(string $side): string
    {
        $color = $this->__get("border_{$side}_color");

        return $this->__get("border_{$side}_width") . " " .
            $this->__get("border_{$side}_style") . " " .
            (\is_array($color) ? $color["hex"] : $color);
    }

    
    protected function _get_border_top(): string
    {
        return $this->get_border_side("top");
    }

    
    protected function _get_border_right(): string
    {
        return $this->get_border_side("right");
    }

    
    protected function _get_border_bottom(): string
    {
        return $this->get_border_side("bottom");
    }

    
    protected function _get_border_left(): string
    {
        return $this->get_border_side("left");
    }

    public function has_border_radius(): bool
    {
        if (isset($this->has_border_radius_cache)) {
            return $this->has_border_radius_cache;
        }

        
        
        
        $tl = (float) $this->length_in_pt($this->border_top_left_radius, 12);
        $tr = (float) $this->length_in_pt($this->border_top_right_radius, 12);
        $br = (float) $this->length_in_pt($this->border_bottom_right_radius, 12);
        $bl = (float) $this->length_in_pt($this->border_bottom_left_radius, 12);

        $this->has_border_radius_cache = $tl + $tr + $br + $bl > 0;
        return $this->has_border_radius_cache;
    }

    
    public function resolve_border_radius(
        array $border_box,
        ?array $render_box = null
    ): array {
        $render_box = $render_box ?? $border_box;
        $use_cache = $render_box === $border_box;

        if ($use_cache && isset($this->resolved_border_radius)) {
            return $this->resolved_border_radius;
        }

        [$x, $y, $w, $h] = $border_box;

        
        
        $tl = (float) $this->length_in_pt($this->border_top_left_radius, $w);
        $tr = (float) $this->length_in_pt($this->border_top_right_radius, $w);
        $br = (float) $this->length_in_pt($this->border_bottom_right_radius, $w);
        $bl = (float) $this->length_in_pt($this->border_bottom_left_radius, $w);

        if ($tl + $tr + $br + $bl > 0) {
            [$rx, $ry, $rw, $rh] = $render_box;

            $t_offset = $y - $ry;
            $r_offset = $rx + $rw - $x - $w;
            $b_offset = $ry + $rh - $y - $h;
            $l_offset = $x - $rx;

            if ($tl > 0) {
                $tl = max($tl + ($t_offset + $l_offset) / 2, 0);
            }
            if ($tr > 0) {
                $tr = max($tr + ($t_offset + $r_offset) / 2, 0);
            }
            if ($br > 0) {
                $br = max($br + ($b_offset + $r_offset) / 2, 0);
            }
            if ($bl > 0) {
                $bl = max($bl + ($b_offset + $l_offset) / 2, 0);
            }

            if ($tl + $bl > $rh) {
                $f = $rh / ($tl + $bl);
                $tl = $f * $tl;
                $bl = $f * $bl;
            }
            if ($tr + $br > $rh) {
                $f = $rh / ($tr + $br);
                $tr = $f * $tr;
                $br = $f * $br;
            }
            if ($tl + $tr > $rw) {
                $f = $rw / ($tl + $tr);
                $tl = $f * $tl;
                $tr = $f * $tr;
            }
            if ($bl + $br > $rw) {
                $f = $rw / ($bl + $br);
                $bl = $f * $bl;
                $br = $f * $br;
            }
        }

        $values = [$tl, $tr, $br, $bl];

        if ($use_cache) {
            $this->resolved_border_radius = $values;
        }

        return $values;
    }

    
    protected function _get_outline_color($computed)
    {
        return $this->get_color_value($computed);
    }

    
    protected function _get_outline_style($computed): string
    {
        return $computed === "auto" ? "solid" : $computed;
    }

    
    protected function _get_outline(): string
    {
        $color = $this->__get("outline_color");

        return $this->__get("outline_width") . " " .
            $this->__get("outline_style") . " " .
            (\is_array($color) ? $color["hex"] : $color);
    }

    
    protected function _get_list_style_image($computed): string
    {
        return $this->_stylesheet->resolve_url($computed, true);
    }

    
    protected function _get_quotes($computed)
    {
        if ($computed === "auto") {
            
            
            return [['"', '"'], ["'", "'"]];
        }

        return $computed;
    }

    

    
    protected function parse_property_value(string $value): array
    {
        $string = self::CSS_STRING;
        $ident = self::CSS_IDENTIFIER;
        $number = self::CSS_NUMBER;

        $pattern = "/\n" .
            "\s* (?<string>$string)                                        |\n" . 
            "\s* (url \( (?> (\\\\[\"'()] | [^\"'()])* ) (?<!\\\\)\) )     |\n" . 
            "\s* ($ident (\( ((?> \g<string> | [^\"'()]+ ) | (?-2))* \)) ) |\n" . 
            "\s* ($ident)                                                  |\n" . 
            "\s* (\#[0-9a-fA-F]*)                                          |\n" . 
            "\s* ($number [a-zA-Z%]*)                                      |\n" . 
            "\s* ([\/,;])                                                   \n" . 
            "/iSx";

        if (!preg_match_all($pattern, $value, $matches)) {
            return [];
        }

        return array_map("trim", $matches[0]);
    }

    protected function is_color_value(string $val): bool
    {
        return $val === "currentcolor"
            || $val === "transparent"
            || isset(Color::$cssColorNames[$val])
            || preg_match("/^#|rgb\(|rgba\(|cmyk\(/", $val);
    }

    
    protected function compute_color_value(string $val): ?string
    {
        
        $val = strtolower($val);
        $munged_color = $val !== "currentcolor"
            ? $this->munge_color($val)
            : $val;

        if ($munged_color === null) {
            return null;
        }

        return \is_array($munged_color) ? $munged_color["hex"] : $munged_color;
    }

    
    protected function compute_integer(string $val): ?int
    {
        $integer = self::CSS_INTEGER;
        return preg_match("/^$integer$/", $val)
            ? (int) $val
            : null;
    }

    
    protected function compute_number(string $val): ?float
    {
        $number = self::CSS_NUMBER;
        return preg_match("/^$number$/", $val)
            ? (float) $val
            : null;
    }

    
    protected function compute_length(string $val): ?float
    {
        return strpos($val, "%") === false
            ? $this->single_length_in_pt($val)
            : null;
    }

    
    protected function compute_length_positive(string $val): ?float
    {
        $computed = $this->compute_length($val);

        
        if ($computed === null
            || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
        ) {
            return null;
        }

        return $computed;
    }

    
    protected function compute_length_percentage(string $val)
    {
        
        
        $computed = $this->single_length_in_pt($val, 12);

        if ($computed === null) {
            return null;
        }

        
        return strpos($val, "%") === false ? $computed : $val;
    }

    
    protected function compute_length_percentage_positive(string $val)
    {
        
        
        $computed = $this->single_length_in_pt($val, 12);

        
        if ($computed === null
            || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
        ) {
            return null;
        }

        
        return strpos($val, "%") === false ? $computed : $val;
    }

    
    protected function compute_line_width(string $val, string $style_prop): ?float
    {
        $val = strtolower($val);

        
        if ($val === "thin") {
            $computed = 0.5;
        } elseif ($val === "medium") {
            $computed = 1.5;
        } elseif ($val === "thick") {
            $computed = 2.5;
        } else {
            $computed = $this->compute_length_positive($val);
        }

        if ($computed === null) {
            return null;
        }

        
        
        
        $lineStyle = $this->__get($style_prop);
        $hasLineStyle = $lineStyle !== "none" && $lineStyle !== "hidden";

        return $hasLineStyle ? $computed : 0.0;
    }

    
    protected function compute_border_style(string $val): ?string
    {
        $val = strtolower($val);
        return \in_array($val, self::BORDER_STYLES, true) ? $val : null;
    }

    
    protected function compute_angle_or_zero(string $val): ?float
    {
        $number = self::CSS_NUMBER;
        $pattern = "/^($number)(deg|grad|rad|turn)?$/i";

        if (!preg_match($pattern, $val, $matches)) {
            return null;
        }

        $v = (float) $matches[1];
        $unit = strtolower($matches[2] ?? "");

        switch ($unit) {
            case "deg":
                return $v;
            case "grad":
                return $v * 0.9;
            case "rad":
                return rad2deg($v);
            case "turn":
                return $v * 360;
            default:
                return $v === 0.0 ? $v : null;
        }
    }

    
    protected function computeBackgroundPositionTransformOrigin(string $v1, string $v2): array
    {
        $x = null;
        $y = null;

        switch ($v1) {
            case "left":
                $x = 0.0;
                break;
            case "right":
                $x = "100%";
                break;
            case "top":
                $y = 0.0;
                break;
            case "bottom":
                $y = "100%";
                break;
            case "center":
                if ($v2 === "left" || $v2 === "right") {
                    $y = "50%";
                } else {
                    $x = "50%";
                }
                break;
            default:
                $x = $this->compute_length_percentage($v1);
                break;
        }

        switch ($v2) {
            case "left":
                $x = 0.0;
                break;
            case "right":
                $x = "100%";
                break;
            case "top":
                $y = 0.0;
                break;
            case "bottom":
                $y = "100%";
                break;
            case "center":
                if ($v1 === "top" || $v1 === "bottom") {
                    $x = "50%";
                } else {
                    $y = "50%";
                }
                break;
            default:
                $y = $this->compute_length_percentage($v2);
                break;
        }

        return [$x, $y];
    }

    
    protected function isValidCounterName(string $name): bool
    {
        return $name !== "none"
            && !in_array($name, self::CUSTOM_IDENT_FORBIDDEN, true);
    }

    
    protected function isValidCounterStyleName(string $name): bool
    {
        return $name !== "none"
            && !in_array($name, self::CUSTOM_IDENT_FORBIDDEN, true);
    }

    
    protected function set_quad_shorthand(string $prop, string $value): array
    {
        $v = $this->parse_property_value($value);

        switch (\count($v)) {
            case 1:
                $values = [$v[0], $v[0], $v[0], $v[0]];
                break;
            case 2:
                $values = [$v[0], $v[1], $v[0], $v[1]];
                break;
            case 3:
                $values = [$v[0], $v[1], $v[2], $v[1]];
                break;
            case 4:
                $values = [$v[0], $v[1], $v[2], $v[3]];
                break;
            default:
                return [];
        }

        return array_combine(self::$_props_shorthand[$prop], $values);
    }

    

    
    protected function _compute_display(string $val)
    {
        $val = strtolower($val);

        
        
        switch ($val) {
            case "flow-root":
            case "flex":
            case "grid":
            case "table-caption":
                $val = "block";
                break;
            case "inline-flex":
            case "inline-grid":
                $val = "inline-block";
                break;
        }

        if (!isset(self::$valid_display_types[$val])) {
            return null;
        }

        
        if ($this->is_in_flow()) {
            return $val;
        } else {
            switch ($val) {
                case "inline":
                case "inline-block":
                
                
                
                
                
                
                
                
                    return "block";
                case "inline-table":
                    return "table";
                default:
                    return $val;
            }
        }
    }

    
    protected function _compute_color(string $color)
    {
        return $this->compute_color_value($color);
    }

    
    protected function _compute_background_color(string $color)
    {
        return $this->compute_color_value($color);
    }

    
    protected function _compute_background_image(string $val)
    {
        $parsed_val = $this->_stylesheet->resolve_url($val);

        if ($parsed_val === "none") {
            return "none";
        } else {
            return "url(\"" . str_replace("\"", "\\\"", $parsed_val) . "\")";
        }
    }

    
    protected function _compute_background_repeat(string $val)
    {
        $keywords = ["repeat", "repeat-x", "repeat-y", "no-repeat"];
        $val = strtolower($val);
        return \in_array($val, $keywords, true) ? $val : null;
    }

    
    protected function _compute_background_attachment(string $val)
    {
        $keywords = ["scroll", "fixed"];
        $val = strtolower($val);
        return \in_array($val, $keywords, true) ? $val : null;
    }

    
    protected function _compute_background_position(string $val)
    {
        $val = strtolower($val);
        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 2) {
            return null;
        }

        $v1 = $parts[0];
        $v2 = $parts[1] ?? "center";
        [$x, $y] = $this->computeBackgroundPositionTransformOrigin($v1, $v2);

        if ($x === null || $y === null) {
            return null;
        }

        return [$x, $y];
    }

    
    protected function _compute_background_size(string $val)
    {
        $val = strtolower($val);

        if ($val === "cover" || $val === "contain") {
            return $val;
        }

        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 2) {
            return null;
        }

        $width = $parts[0];
        if ($width !== "auto") {
            $width = $this->compute_length_percentage_positive($width);
        }

        $height = $parts[1] ?? "auto";
        if ($height !== "auto") {
            $height = $this->compute_length_percentage_positive($height);
        }

        if ($width === null || $height === null) {
            return null;
        }

        return [$width, $height];
    }

    
    protected function _set_background(string $value): array
    {
        $components = $this->parse_property_value($value);
        $props = [];
        $pos_size = [];

        foreach ($components as $val) {
            $lower = strtolower($val);

            if ($lower === "none") {
                $props["background_image"] = $lower;
            } elseif (strncmp($lower, "url(", 4) === 0) {
                $props["background_image"] = $val;
            } elseif ($lower === "scroll" || $lower === "fixed") {
                $props["background_attachment"] = $lower;
            } elseif ($lower === "repeat" || $lower === "repeat-x" || $lower === "repeat-y" || $lower === "no-repeat") {
                $props["background_repeat"] = $lower;
            } elseif ($this->is_color_value($lower)) {
                $props["background_color"] = $lower;
            } else {
                $pos_size[] = $lower;
            }
        }

        if (\count($pos_size)) {
            
            $index = array_search("/", $pos_size, true);

            if ($index !== false) {
                $pos = \array_slice($pos_size, 0, $index);
                $size = \array_slice($pos_size, $index + 1);
            } else {
                $pos = $pos_size;
                $size = [];
            }

            $props["background_position"] = implode(" ", $pos);

            if (\count($size)) {
                $props["background_size"] = implode(" ", $size);
            }
        }

        return $props;
    }

    
    protected function _compute_font_family(string $val)
    {
        return array_map(
            function ($name) {
                return trim($name, " '\"");
            },
            preg_split("/\s*,\s*/", $val)
        );
    }

    
    protected function _compute_font_size(string $val)
    {
        $val = strtolower($val);
        $parentFontSize = isset($this->parent_style)
            ? $this->parent_style->__get("font_size")
            : self::$default_font_size;

        switch ($val) {
            case "xx-small":
            case "x-small":
            case "small":
            case "medium":
            case "large":
            case "x-large":
            case "xx-large":
                $computed = self::$default_font_size * self::$font_size_keywords[$val];
                break;

            case "smaller":
                $computed = 8 / 9 * $parentFontSize;
                break;

            case "larger":
                $computed = 6 / 5 * $parentFontSize;
                break;

            default:
                $computed = $this->single_length_in_pt($val, $parentFontSize, $parentFontSize);

                
                if ($computed === null
                    || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
                ) {
                    return null;
                }
                break;
        }

        return $computed;
    }

    
    protected function _compute_font_style(string $val)
    {
        $val = strtolower($val);
        return $val === "normal" || $val === "italic" || $val === "oblique"
            ? $val
            : null;
    }

    
    protected function _compute_font_weight(string $val)
    {
        $val = strtolower($val);

        switch ($val) {
            case "normal":
                return 400;

            case "bold":
                return 700;

            case "bolder":
                
                $w = isset($this->parent_style)
                    ? $this->parent_style->__get("font_weight")
                    : 400;

                if ($w < 350) {
                    return 400;
                } elseif ($w < 550) {
                    return 700;
                } elseif ($w < 900) {
                    return 900;
                } else {
                    return $w;
                }

            case "lighter":
                
                $w = isset($this->parent_style)
                    ? $this->parent_style->__get("font_weight")
                    : 400;

                if ($w < 100) {
                    return $w;
                } elseif ($w < 550) {
                    return 100;
                } elseif ($w < 750) {
                    return 400;
                } else {
                    return 700;
                }

            default:
                $number = self::CSS_NUMBER;
                $weight = preg_match("/^$number$/", $val)
                    ? (int) $val
                    : null;
                return $weight !== null && $weight >= 1 && $weight <= 1000
                    ? $weight
                    : null;
        }
    }

    
    protected function _compute_src(string $val)
    {
        return $val;
    }

    
    protected function _set_font(string $value): array
    {
        $value = strtolower($value);
        $components = $this->parse_property_value($value);
        $props = [];

        $number = self::CSS_NUMBER;
        $unit = "pt|px|pc|rem|em|ex|in|cm|mm|%";
        $sizePattern = "/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|$number(?:$unit)|0)$/";
        $sizeIndex = null;

        
        foreach ($components as $i => $val) {
            if (preg_match($sizePattern, $val)) {
                $sizeIndex = $i;
                $props["font_size"] = $val;
                break;
            }
        }

        
        if ($sizeIndex === null) {
            return [];
        }

        
        $styleVariantWeight = \array_slice($components, 0, $sizeIndex);
        $stylePattern = "/^(italic|oblique)$/";
        $variantPattern = "/^(small-caps)$/";
        $weightPattern = "/^(bold|bolder|lighter|$number)$/";

        if (\count($styleVariantWeight) > 3) {
            return [];
        }

        foreach ($styleVariantWeight as $val) {
            if ($val === "normal") {
                
                
            } elseif (!isset($props["font_style"]) && preg_match($stylePattern, $val)) {
                $props["font_style"] = $val;
            } elseif (!isset($props["font_variant"]) && preg_match($variantPattern, $val)) {
                $props["font_variant"] = $val;
            } elseif (!isset($props["font_weight"]) && preg_match($weightPattern, $val)) {
                $props["font_weight"] = $val;
            } else {
                
                return [];
            }
        }

        
        $lineFamily = \array_slice($components, $sizeIndex + 1);
        $hasLineHeight = $lineFamily !== [] && $lineFamily[0] === "/";
        $lineHeight = $hasLineHeight ? \array_slice($lineFamily, 1, 1) : [];
        $fontFamily = $hasLineHeight ? \array_slice($lineFamily, 2) : $lineFamily;
        $lineHeightPattern = "/^(normal|$number(?:$unit)?)$/";

        
        if ($fontFamily === []
            || ($hasLineHeight && !preg_match($lineHeightPattern, $lineHeight[0]))
        ) {
            return [];
        }

        if ($hasLineHeight) {
            $props["line_height"] = $lineHeight[0];
        }

        $props["font_family"] = implode("", $fontFamily);

        return $props;
    }

    
    protected function _compute_text_align(string $val)
    {
        $alignment = strtolower($val);

        if ($alignment === "") {
            $alignment = $this->__get("direction") === "rtl"
                ? "right"
                : "left";
        }

        if (!\in_array($alignment, self::TEXT_ALIGN_KEYWORDS, true)) {
            return null;
        }

        return $alignment;
    }

    
    protected function _compute_word_spacing(string $val)
    {
        $val = strtolower($val);

        if ($val === "normal") {
            return 0.0;
        }

        return $this->compute_length_percentage($val);
    }

    
    protected function _compute_letter_spacing(string $val)
    {
        $val = strtolower($val);

        if ($val === "normal") {
            return 0.0;
        }

        return $this->compute_length_percentage($val);
    }

    
    protected function _compute_line_height(string $val)
    {
        $val = strtolower($val);

        if ($val === "normal") {
            return $val;
        }

        
        if (is_numeric($val)) {
            return (string) $val;
        }

        $font_size = $this->__get("font_size");
        $computed = $this->single_length_in_pt($val, $font_size);

        
        if ($computed === null
            || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
        ) {
            return null;
        }

        return $computed;
    }

    
    protected function _compute_text_indent(string $val)
    {
        return $this->compute_length_percentage($val);
    }

    
    protected function _compute_page_break_before(string $val)
    {
        $break = strtolower($val);

        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        return $break;
    }

    
    protected function _compute_page_break_after(string $val)
    {
        $break = strtolower($val);

        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        return $break;
    }

    
    protected function _compute_width(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _compute_height(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _compute_min_width(string $val)
    {
        $val = strtolower($val);

        
        if ($val === "auto" || $val === "none") {
            return "auto";
        }

        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _compute_min_height(string $val)
    {
        $val = strtolower($val);

        
        if ($val === "auto" || $val === "none") {
            return "auto";
        }

        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _compute_max_width(string $val)
    {
        $val = strtolower($val);

        
        if ($val === "none" || $val === "auto") {
            return "none";
        }

        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _compute_max_height(string $val)
    {
        $val = strtolower($val);

        
        if ($val === "none" || $val === "auto") {
            return "none";
        }

        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _set_inset(string $val): array
    {
        return $this->set_quad_shorthand("inset", $val);
    }

    
    protected function compute_box_inset(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage($val);
    }

    protected function _compute_top(string $val)
    {
        return $this->compute_box_inset($val);
    }

    protected function _compute_right(string $val)
    {
        return $this->compute_box_inset($val);
    }

    protected function _compute_bottom(string $val)
    {
        return $this->compute_box_inset($val);
    }

    protected function _compute_left(string $val)
    {
        return $this->compute_box_inset($val);
    }

    
    protected function _set_margin(string $val): array
    {
        return $this->set_quad_shorthand("margin", $val);
    }

    
    protected function compute_margin(string $val)
    {
        $val = strtolower($val);

        
        if ($val === "none") {
            return 0.0;
        }

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage($val);
    }

    protected function _compute_margin_top(string $val)
    {
        return $this->compute_margin($val);
    }

    protected function _compute_margin_right(string $val)
    {
        return $this->compute_margin($val);
    }

    protected function _compute_margin_bottom(string $val)
    {
        return $this->compute_margin($val);
    }

    protected function _compute_margin_left(string $val)
    {
        return $this->compute_margin($val);
    }

    
    protected function _set_padding(string $val): array
    {
        return $this->set_quad_shorthand("padding", $val);
    }

    
    protected function compute_padding(string $val)
    {
        $val = strtolower($val);

        
        if ($val === "none") {
            return 0.0;
        }

        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_padding_top(string $val)
    {
        return $this->compute_padding($val);
    }

    protected function _compute_padding_right(string $val)
    {
        return $this->compute_padding($val);
    }

    protected function _compute_padding_bottom(string $val)
    {
        return $this->compute_padding($val);
    }

    protected function _compute_padding_left(string $val)
    {
        return $this->compute_padding($val);
    }

    
    protected function parse_border_side(string $value, array $styles = self::BORDER_STYLES): ?array
    {
        $value = strtolower($value);
        $components = $this->parse_property_value($value);
        $width = null;
        $style = null;
        $color = null;

        foreach ($components as $val) {
            if ($style === null && \in_array($val, $styles, true)) {
                $style = $val;
            } elseif ($color === null && $this->is_color_value($val)) {
                $color = $val;
            } elseif ($width === null) {
                
                $width = $val;
            } else {
                
                return null;
            }
        }

        return [$width, $style, $color];
    }

    
    protected function _set_border(string $value): array
    {
        $values = $this->parse_border_side($value);

        if ($values === null) {
            return [];
        }

        return array_merge(
            array_combine(self::$_props_shorthand["border_top"], $values),
            array_combine(self::$_props_shorthand["border_right"], $values),
            array_combine(self::$_props_shorthand["border_bottom"], $values),
            array_combine(self::$_props_shorthand["border_left"], $values)
        );
    }

    
    protected function set_border_side(string $prop, string $value): array
    {
        $values = $this->parse_border_side($value);

        if ($values === null) {
            return [];
        }

        return array_combine(self::$_props_shorthand[$prop], $values);
    }

    protected function _set_border_top(string $val): array
    {
        return $this->set_border_side("border_top", $val);
    }

    protected function _set_border_right(string $val): array
    {
        return $this->set_border_side("border_right", $val);
    }

    protected function _set_border_bottom(string $val): array
    {
        return $this->set_border_side("border_bottom", $val);
    }

    protected function _set_border_left(string $val): array
    {
        return $this->set_border_side("border_left", $val);
    }

    
    protected function _set_border_color(string $val): array
    {
        return $this->set_quad_shorthand("border_color", $val);
    }

    protected function _compute_border_top_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_border_right_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_border_bottom_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_border_left_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    
    protected function _set_border_style(string $val): array
    {
        return $this->set_quad_shorthand("border_style", $val);
    }

    protected function _compute_border_top_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_border_right_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_border_bottom_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_border_left_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    
    protected function _set_border_width(string $val): array
    {
        return $this->set_quad_shorthand("border_width", $val);
    }

    protected function _compute_border_top_width(string $val)
    {
        return $this->compute_line_width($val, "border_top_style");
    }

    protected function _compute_border_right_width(string $val)
    {
        return $this->compute_line_width($val, "border_right_style");
    }

    protected function _compute_border_bottom_width(string $val)
    {
        return $this->compute_line_width($val, "border_bottom_style");
    }

    protected function _compute_border_left_width(string $val)
    {
        return $this->compute_line_width($val, "border_left_style");
    }

    
    protected function _set_border_radius(string $val): array
    {
        return $this->set_quad_shorthand("border_radius", $val);
    }

    protected function _compute_border_top_left_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_border_top_right_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_border_bottom_right_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_border_bottom_left_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    
    protected function _set_outline(string $value): array
    {
        $values = $this->parse_border_side($value, self::OUTLINE_STYLES);

        if ($values === null) {
            return [];
        }

        return array_combine(self::$_props_shorthand["outline"], $values);
    }

    protected function _compute_outline_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_outline_style(string $val)
    {
        $val = strtolower($val);
        return \in_array($val, self::OUTLINE_STYLES, true) ? $val : null;
    }

    protected function _compute_outline_width(string $val)
    {
        return $this->compute_line_width($val, "outline_style");
    }

    
    protected function _compute_outline_offset(string $val)
    {
        return $this->compute_length($val);
    }

    
    protected function _compute_border_spacing(string $val)
    {
        $val = strtolower($val);
        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 2) {
            return null;
        }

        $h = $this->compute_length_positive($parts[0]);
        $v = isset($parts[1])
            ? $this->compute_length_positive($parts[1])
            : $h;

        if ($h === null || $v === null) {
            return null;
        }

        return [$h, $v];
    }

    
    protected function _compute_list_style_image(string $val)
    {
        $parsed_val = $this->_stylesheet->resolve_url($val);

        if ($parsed_val === "none") {
            return "none";
        } else {
            return "url(\"" . str_replace("\"", "\\\"", $parsed_val) . "\")";
        }
    }

    
    protected function _compute_list_style_position(string $val)
    {
        $val = strtolower($val);
        return $val === "inside" || $val === "outside" ? $val : null;
    }

    
    protected function _compute_list_style_type(string $val)
    {
        $val = strtolower($val);

        if ($val === "none") {
            return $val;
        }

        $ident = self::CSS_IDENTIFIER;
        return $val !== "default" && preg_match("/^$ident$/", $val)
            ? $val
            : null;
    }

    
    protected function _set_list_style(string $value): array
    {
        $components = $this->parse_property_value($value);
        $none = 0;
        $position = null;
        $image = null;
        $type = null;

        foreach ($components as $val) {
            $lower = strtolower($val);

            
            if ($none < 2 && $lower === "none") {
                $none++;
            } elseif ($position === null && ($lower === "inside" || $lower === "outside")) {
                $position = $lower;
            } elseif ($image === null && strncmp($lower, "url(", 4) === 0) {
                $image = $val;
            } elseif ($type === null) {
                $type = $val;
            } else {
                
                return [];
            }
        }

        
        
        
        
        
        
        if ($none === 2) {
            if ($image !== null || $type !== null) {
                return [];
            }

            $image = "none";
            $type = "none";
        } elseif ($none === 1) {
            if ($image !== null && $type !== null) {
                return [];
            }

            $image = $image ?? "none";
            $type = $type ?? "none";
        }

        return [
            "list_style_position" => $position,
            "list_style_image" => $image,
            "list_style_type" => $type
        ];
    }

    
    protected function compute_counter_prop(string $value, int $default, bool $sumDuplicates = false)
    {
        $lower = strtolower($value);

        if ($lower === "none") {
            return $lower;
        }

        $ident = self::CSS_IDENTIFIER;
        $integer = self::CSS_INTEGER;
        $counterDef = "($ident)(?:\s+($integer))?";
        $validationPattern = "/^$counterDef(\s+$counterDef)*$/";

        if (!preg_match($validationPattern, $value)) {
            return null;
        }

        preg_match_all("/$counterDef/", $value, $matches, PREG_SET_ORDER);
        $counters = [];

        foreach ($matches as $match) {
            $name = $match[1];

            if (!$this->isValidCounterName($name)) {
                return null;
            }

            $value = isset($match[2]) ? (int) $match[2] : $default;
            $counters[$name] = $sumDuplicates
                ? ($counters[$name] ?? 0) + $value
                : $value;
        }

        return $counters;
    }

    
    protected function _compute_counter_increment(string $val)
    {
        return $this->compute_counter_prop($val, 1, true);
    }

    
    protected function _compute_counter_reset(string $val)
    {
        return $this->compute_counter_prop($val, 0);
    }

    
    protected function _compute_quotes(string $val)
    {
        $lower = strtolower($val);

        
        if ($lower === "none" || $lower === "auto") {
            return $lower;
        }

        $components = $this->parse_property_value($val);
        $quotes = [];

        foreach ($components as $value) {
            if (strncmp($value, '"', 1) !== 0
                && strncmp($value, "'", 1) !== 0
            ) {
                return null;
            }

            $quotes[] = $this->_stylesheet->parse_string($value);
        }

        if ($quotes === [] || \count($quotes) % 2 !== 0) {
            return null;
        }

        return array_chunk($quotes, 2);
    }

    
    protected function _compute_content(string $val)
    {
        $lower = strtolower($val);

        if ($lower === "normal" || $lower === "none") {
            return $lower;
        }

        $components = $this->parse_property_value($val);
        $parts = [];

        if ($components === []) {
            return null;
        }

        foreach ($components as $value) {
            
            if (strncmp($value, '"', 1) === 0 || strncmp($value, "'", 1) === 0) {
                $parts[] = new StringPart($this->_stylesheet->parse_string($value));
                continue;
            }

            $lower = strtolower($value);

            
            if ($lower === "open-quote") {
                $parts[] = new OpenQuote;
                continue;
            } elseif ($lower === "close-quote") {
                $parts[] = new CloseQuote;
                continue;
            } elseif ($lower === "no-open-quote") {
                $parts[] = new NoOpenQuote;
                continue;
            } elseif ($lower === "no-close-quote") {
                $parts[] = new NoCloseQuote;
                continue;
            }

            
            $pos = strpos($lower, "(");

            if ($pos === false) {
                return null;
            }

            
            
            $function = substr($lower, 0, $pos);
            $arguments = trim(substr($value, $pos + 1, -1));

            
            if ($function === "attr") {
                $attr = strtolower($arguments);

                if ($attr === "") {
                    return null;
                }

                $parts[] = new Attr($attr);
            }

            
            elseif ($function === "counter") {
                $ident = self::CSS_IDENTIFIER;

                if (!preg_match("/^($ident)(?:\s*,\s*($ident))?$/", $arguments, $matches)) {
                    return null;
                }

                $name = $matches[1];
                $type = isset($matches[2]) ? strtolower($matches[2]) : "decimal";

                if (!$this->isValidCounterName($name)
                    || !$this->isValidCounterStyleName($type)
                ) {
                    return null;
                }

                $parts[] = new Counter($name, $type);
            }

            
            elseif ($function === "counters") {
                $ident = self::CSS_IDENTIFIER;
                $string = self::CSS_STRING;

                if (!preg_match("/^($ident)\s*,\s*($string)(?:\s*,\s*($ident))?$/", $arguments, $matches)) {
                    return null;
                }

                $name = $matches[1];
                $string = $this->_stylesheet->parse_string($matches[2]);
                $type = isset($matches[3]) ? strtolower($matches[3]) : "decimal";

                if (!$this->isValidCounterName($name)
                    || !$this->isValidCounterStyleName($type)
                ) {
                    return null;
                }

                $parts[] = new Counters($name, $string, $type);
            }

            
            elseif ($function === "url") {
                $url = $this->_stylesheet->parse_string($arguments);
                $parts[] = new Url($url);
            }

            else {
                return null;
            }
        }

        return $parts;
    }

    
    protected function _compute_size(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 3) {
            return null;
        }

        $size = null;
        $orientation = null;
        $lengths = [];

        foreach ($parts as $part) {
            if ($size === null && isset(CPDF::$PAPER_SIZES[$part])) {
                $size = $part;
            } elseif ($orientation === null && ($part === "portrait" || $part === "landscape")) {
                $orientation = $part;
            } else {
                $lengths[] = $part;
            }
        }

        if ($size !== null && $lengths !== []) {
            return null;
        }

        if ($size !== null) {
            
            [$l1, $l2] = \array_slice(CPDF::$PAPER_SIZES[$size], 2, 2);
        } elseif ($lengths === []) {
            
            $dims = $this->_stylesheet->get_dompdf()->getPaperSize();
            [$l1, $l2] = \array_slice($dims, 2, 2);
        } else {
            
            $l1 = $this->compute_length_positive($lengths[0]);
            $l2 = isset($lengths[1]) ? $this->compute_length_positive($lengths[1]) : $l1;

            if ($l1 === null || $l2 === null) {
                return null;
            }
        }

        if (($orientation === "portrait" && $l1 > $l2)
            || ($orientation === "landscape" && $l2 > $l1)
        ) {
            return [$l2, $l1];
        }

        return [$l1, $l2];
    }

    
    protected function _compute_transform(string $val)
    {
        $val = strtolower($val);

        if ($val === "none") {
            return [];
        }

        $parts = $this->parse_property_value($val);
        $transforms = [];

        if ($parts === []) {
            return null;
        }

        foreach ($parts as $part) {
            if (!preg_match("/^([a-z]+)\((.+)\)$/s", $part, $matches)) {
                return null;
            }

            $name = $matches[1];
            $arguments = trim($matches[2]);
            $values = $this->parse_property_value($arguments);
            $values = array_values(array_filter($values, function ($v) {
                return $v !== ",";
            }));
            $count = \count($values);

            if ($count === 0) {
                return null;
            }

            switch ($name) {
                
                
                
                

                
                

                
                case "translate":
                    if ($count > 2) {
                        return null;
                    }

                    $values = [
                        $this->compute_length_percentage($values[0]),
                        isset($values[1]) ? $this->compute_length_percentage($values[1]) : 0.0
                    ];
                    break;

                case "translatex":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "translate";
                    $values = [$this->compute_length_percentage($values[0]), 0.0];
                    break;

                case "translatey":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "translate";
                    $values = [0.0, $this->compute_length_percentage($values[0])];
                    break;

                
                case "scale":
                    if ($count > 2) {
                        return null;
                    }

                    $v0 = $this->compute_number($values[0]);
                    $v1 = isset($values[1]) ? $this->compute_number($values[1]) : $v0;
                    $values = [$v0, $v1];
                    break;

                case "scalex":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "scale";
                    $values = [$this->compute_number($values[0]), 1.0];
                    break;

                case "scaley":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "scale";
                    $values = [1.0, $this->compute_number($values[0])];
                    break;

                
                case "rotate":
                    if ($count > 1) {
                        return null;
                    }

                    $values = [$this->compute_angle_or_zero($values[0])];
                    break;

                case "skew":
                    if ($count > 2) {
                        return null;
                    }

                    $values = [
                        $this->compute_angle_or_zero($values[0]),
                        isset($values[1]) ? $this->compute_angle_or_zero($values[1]) : 0.0
                    ];
                    break;

                case "skewx":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "skew";
                    $values = [$this->compute_angle_or_zero($values[0]), 0.0];
                    break;

                case "skewy":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "skew";
                    $values = [0.0, $this->compute_angle_or_zero($values[0])];
                    break;

                default:
                    return null;
            }

            foreach ($values as $v) {
                if ($v === null) {
                    return null;
                }
            }

            $transforms[] = [$name, $values];
        }

        return $transforms;
    }

    
    protected function _compute_transform_origin(string $val)
    {
        $val = strtolower($val);
        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 3) {
            return null;
        }

        $v1 = $parts[0];
        $v2 = $parts[1] ?? "center";
        [$x, $y] = $this->computeBackgroundPositionTransformOrigin($v1, $v2);
        $z = $count === 3 ? $this->compute_length($parts[2]) : 0.0;

        if ($x === null || $y === null || $z === null) {
            return null;
        }

        return [$x, $y, $z];
    }

    
    protected function parse_image_resolution(string $val): ?string
    {
        
        

        $val = strtolower($val);
        $re = '/^\s*(\d+|normal|auto)\s*$/';

        if (!preg_match($re, $val, $matches)) {
            return null;
        }

        return $matches[1];
    }

    
    protected function _compute_background_image_resolution(string $val)
    {
        return $this->parse_image_resolution($val);
    }

    
    protected function _compute_image_resolution(string $val)
    {
        return $this->parse_image_resolution($val);
    }

    
    protected function _compute_orphans(string $val)
    {
        return $this->compute_integer($val);
    }

    
    protected function _compute_widows(string $val)
    {
        return $this->compute_integer($val);
    }

    
    protected function _compute_opacity(string $val)
    {
        $number = self::CSS_NUMBER;
        $pattern = "/^($number)(%?)$/";

        if (!preg_match($pattern, $val, $matches)) {
            return null;
        }

        $v = (float) $matches[1];
        $percent = $matches[2] === "%";
        $opacity = $percent ? ($v / 100) : $v;

        return max(0.0, min($opacity, 1.0));
    }

    
    protected function _compute_z_index(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_integer($val);
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

    
    
    public function __toString(): string
    {
        $parent_font_size = $this->parent_style
            ? $this->parent_style->font_size
            : self::$default_font_size;

        return print_r(array_merge(["parent_font_size" => $parent_font_size],
            $this->_props), true);
    }

    
    public function debug_print(): void
    {
        $parent_font_size = $this->parent_style
            ? $this->parent_style->font_size
            : self::$default_font_size;

        print "    parent_font_size:" . $parent_font_size . ";\n";
        print "    Props [\n";
        print "      specified [\n";
        foreach ($this->_props as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            if (isset($this->_important_props[$prop])) {
                print ' !important';
            }
            print ";\n";
        }
        print "      ]\n";
        print "      computed [\n";
        foreach ($this->_props_computed as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            print ";\n";
        }
        print "      ]\n";
        print "      cached [\n";
        foreach ($this->_props_used as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            print ";\n";
        }
        print "      ]\n";
        print "    ]\n";
    }
}
