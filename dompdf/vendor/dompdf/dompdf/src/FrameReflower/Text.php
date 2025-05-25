<?php

namespace Dompdf\FrameReflower;

use Dompdf\Exception;
use Dompdf\FontMetrics;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Inline as InlineFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;
use Dompdf\Helpers;


class Text extends AbstractFrameReflower
{
    
    const SOFT_HYPHEN = "\xC2\xAD";

    
    public static $_whitespace_pattern = '/([^\S\xA0\x{202F}\x{2007}]+)/u';

    
    public static $_wordbreak_pattern = '/([^\S\xA0\x{202F}\x{2007}\n]+|\R|\-+|\xAD+)/u';

    
    protected $_frame;

    
    protected $trailingWs = null;

    
    private $fontMetrics;

    
    public function __construct(TextFrameDecorator $frame, FontMetrics $fontMetrics)
    {
        parent::__construct($frame);
        $this->setFontMetrics($fontMetrics);
    }

    
    protected function pre_process_text(string $text): string
    {
        $style = $this->_frame->get_style();

        
        switch ($style->text_transform) {
            case "capitalize":
                $text = Helpers::mb_ucwords($text);
                break;
            case "uppercase":
                $text = mb_convert_case($text, MB_CASE_UPPER);
                break;
            case "lowercase":
                $text = mb_convert_case($text, MB_CASE_LOWER);
                break;
            default:
                break;
        }

        
        switch ($style->white_space) {
            default:
            case "normal":
            case "nowrap":
                $text = preg_replace(self::$_whitespace_pattern, " ", $text) ?? "";
                break;

            case "pre-line":
                
                $text = preg_replace('/([^\S\xA0\x{202F}\x{2007}\n]+)/u', " ", $text) ?? "";
                break;

            case "pre":
            case "pre-wrap":
                break;

        }

        return $text;
    }

    
    protected function line_break(string $text, BlockFrameDecorator $block, bool $nowrap = false)
    {
        $fontMetrics = $this->getFontMetrics();
        $frame = $this->_frame;
        $style = $frame->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $style->word_spacing;
        $letter_spacing = $style->letter_spacing;

        
        $current_line = $block->get_current_line_box();
        $line_width = $frame->get_containing_block("w");
        $current_line_width = $current_line->left + $current_line->w + $current_line->right;
        $available_width = $line_width - $current_line_width;

        
        $visible_text = preg_replace('/\xAD/u', "", $text);
        $text_width = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $letter_spacing);
        $mbp_width = (float) $style->length_in_pt([
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left,
            $style->padding_right,
            $style->border_right_width,
            $style->margin_right
        ], $line_width);
        $frame_width = $text_width + $mbp_width;

        if (Helpers::lengthLessOrEqual($frame_width, $available_width)) {
            return false;
        }

        $force_first = $current_line->left == 0
            && $current_line->right == 0
            && $current_line->is_empty();

        if ($nowrap) {
            return $force_first ? false : 0;
        }

        
        $words = preg_split(self::$_wordbreak_pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wc = count($words);

        
        $width = 0.0;
        $str = "";

        $space_width = $fontMetrics->getTextWidth(" ", $font, $size, $word_spacing, $letter_spacing);
        $shy_width = $fontMetrics->getTextWidth(self::SOFT_HYPHEN, $font, $size);

        
        for ($i = 0; $i < $wc; $i += 2) {
            
            
            
            $sep = $words[$i + 1] ?? "";
            $word = $sep === " " ? $words[$i] : $words[$i] . $sep;
            $word_width = $fontMetrics->getTextWidth($word, $font, $size, $word_spacing, $letter_spacing);
            $used_width = $width + $word_width + $mbp_width;

            if ($used_width > 0 && Helpers::lengthGreater($used_width, $available_width)) {
                
                
                
                if (isset($words[$i - 1]) && self::SOFT_HYPHEN === $words[$i - 1]) {
                    $width += $shy_width;
                }
                break;
            }

            
            
            
            if ($sep === self::SOFT_HYPHEN) {
                $width += $word_width - $shy_width;
                $str .= $word;
            } elseif ($sep === " ") {
                $width += $word_width + $space_width;
                $str .= $word . $sep;
            } else {
                $width += $word_width;
                $str .= $word;
            }
        }

        
        
        if ($force_first && $width === 0.0) {
            if ($sep === " ") {
                $word .= $sep;
            }

            
            $wrap = $style->overflow_wrap;
            $break_word = $wrap === "anywhere" || $wrap === "break-word";

            if ($break_word) {
                $s = "";
                $len = mb_strlen($word);

                for ($j = 0; $j < $len; $j++) {
                    $c = mb_substr($word, $j, 1);
                    $w = $fontMetrics->getTextWidth($s . $c, $font, $size, $word_spacing, $letter_spacing);

                    if (Helpers::lengthGreater($w, $available_width)) {
                        break;
                    }

                    $s .= $c;
                }

                
                $str = $j === 0 ? $s . $c : $s;
            } else {
                $str = $word;
            }
        }

        $offset = mb_strlen($str);
        return $offset;
    }

    
    protected function newline_break(string $text)
    {
        if (($i = mb_strpos($text, "\n")) === false) {
            return false;
        }

        return $i + 1;
    }

    
    protected function layout_line(BlockFrameDecorator $block): ?bool
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $current_line = $block->get_current_line_box();
        $text = $frame->get_text();

        
        if ($current_line->is_empty() && !$frame->is_pre()) {
            $text = ltrim($text, " ");
        }

        if ($text === "") {
            $frame->set_text("");
            $style->set_used("width", 0.0);
            return false;
        }

        
        
        $white_space = $style->white_space;
        $nowrap = $white_space === "nowrap" || $white_space === "pre";

        switch ($white_space) {
            default:
            case "normal":
            case "nowrap":
                $split = $this->line_break($text, $block, $nowrap);
                $add_line = false;
                break;

            case "pre":
            case "pre-line":
            case "pre-wrap":
                $hard_split = $this->newline_break($text);
                $first_line = $hard_split !== false
                    ? mb_substr($text, 0, $hard_split)
                    : $text;
                $soft_split = $this->line_break($first_line, $block, $nowrap);

                $split = $soft_split !== false ? $soft_split : $hard_split;
                $add_line = $hard_split !== false;
                break;
        }

        if ($split === 0) {
            
            
            
            
            
            if ($current_line->h === 0.0) {
                
                $h = max($frame->get_margin_height(), 1.0);
                $block->maximize_line_height($h, $frame);
            }

            
            $block->add_line();

            
            $child = $frame;
            $p = $child->get_parent();
            while ($p instanceof InlineFrameDecorator && !$child->get_prev_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if ($p instanceof InlineFrameDecorator) {
                
                
                $p->split($child);
                return null;
            }

            return $this->layout_line($block);
        }

        
        if ($split !== false && $split < mb_strlen($text)) {
            
            $frame->set_text($text);
            $frame->split_text($split, true);
            $add_line = true;

            
            $t = $frame->get_text();
            $shyPosition = mb_strpos($t, self::SOFT_HYPHEN);
            if (false !== $shyPosition && $shyPosition < mb_strlen($t) - 1) {
                $t = str_replace(self::SOFT_HYPHEN, "", mb_substr($t, 0, -1)) . mb_substr($t, -1);
                $frame->set_text($t);
            }
        } else {
            
            
            $text = str_replace(self::SOFT_HYPHEN, "", $text);
            $frame->set_text($text);
        }

        
        $frame->recalculate_width();

        return $add_line;
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        $frame = $this->_frame;
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        if ($page->is_full()) {
            return;
        }

        $style = $frame->get_style();

        
        $frame->set_text($this->pre_process_text($frame->get_text()));

        
        $frame->apply_font_mapping();
        $text = $frame->get_text();

        
        $size = $style->font_size;
        $font = $style->font_family;
        $font_height = $this->getFontMetrics()->getFontHeight($font, $size);
        $style->set_used("height", $font_height);

        if ($block === null) {
            return;
        }

        $add_line = $this->layout_line($block);

        if ($add_line === null) {
            return;
        }

        $frame->position();

        
        
        $text = $frame->get_text();
        if ($text === "" && $frame->get_margin_width() === 0.0) {
            return;
        }

        $line = $block->add_frame_to_line($frame);
        $trimmed = trim($text);

        
        
        if ($trimmed !== "") {
            $words = preg_split(self::$_whitespace_pattern, $trimmed);
            $line->wc += count($words);
        }

        if ($add_line) {
            $block->add_line();
        }
    }

    
    public function trim_trailing_ws(): void
    {
        $frame = $this->_frame;
        $text = $frame->get_text();
        $trailing = mb_substr($text, -1);

        
        
        if ($trailing === " ") {
            $this->trailingWs = $trailing;
            $frame->set_text(mb_substr($text, 0, -1));
            $frame->recalculate_width();
        }
    }

    public function reset(): void
    {
        parent::reset();

        
        
        if ($this->trailingWs !== null) {
            $text = $this->_frame->get_text();
            $this->_frame->set_text($text . $this->trailingWs);
            $this->trailingWs = null;
        }
    }

    

    public function get_min_max_width(): array
    {
        $fontMetrics = $this->getFontMetrics();
        $frame = $this->_frame;
        $style = $frame->get_style();

        
        $frame->set_text($this->pre_process_text($frame->get_text()));

        
        $frame->apply_font_mapping();
        $text = $frame->get_text();

        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $style->word_spacing;
        $letter_spacing = $style->letter_spacing;

        if (!$frame->is_pre()) {
            
            
            $child = $frame;
            $p = $frame->get_parent();
            while (!$p->is_block() && !$child->get_prev_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if (!$child->get_prev_sibling()) {
                $text = ltrim($text, " ");
            }

            
            
            $child = $frame;
            $p = $frame->get_parent();
            while (!$p->is_block() && !$child->get_next_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if (!$child->get_next_sibling()) {
                $text = rtrim($text, " ");
            }
        }

        
        $visible_text = preg_replace('/\xAD/u', "", $text);

        
        switch ($style->white_space) {
            default:
            case "normal":
            case "pre-line":
            case "pre-wrap":
                
                
                
                
                
                if ($style->overflow_wrap === "anywhere") {
                    $char = mb_substr($visible_text, 0, 1);
                    $min = $fontMetrics->getTextWidth($char, $font, $size, $word_spacing, $letter_spacing);
                } else {
                    
                    $words = preg_split(self::$_wordbreak_pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                    $lengths = array_map(function ($chunk) use ($fontMetrics, $font, $size, $word_spacing, $letter_spacing) {
                        
                        
                        $sep = $chunk[1] ?? "";
                        $word = $sep === " " ? $chunk[0] : $chunk[0] . $sep;
                        return $fontMetrics->getTextWidth($word, $font, $size, $word_spacing, $letter_spacing);
                    }, array_chunk($words, 2));
                    $min = max($lengths);
                }
                break;

            case "pre":
                
                $lines = array_flip(preg_split("/\R/u", $visible_text));
                array_walk($lines, function (&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $letter_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $letter_spacing);
                });
                arsort($lines);
                $min = reset($lines);
                break;

            case "nowrap":
                $min = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $letter_spacing);
                break;
        }

        
        switch ($style->white_space) {
            default:
            case "normal":
                $max = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $letter_spacing);
                break;

            case "pre-line":
            case "pre-wrap":
                
                $lines = array_flip(preg_split("/\R/u", $visible_text));
                array_walk($lines, function (&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $letter_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $letter_spacing);
                });
                arsort($lines);
                $max = reset($lines);
                break;

            case "pre":
            case "nowrap":
                $max = $min;
                break;
        }

        
        $dims = [
            $style->padding_left,
            $style->padding_right,
            $style->border_left_width,
            $style->border_right_width,
            $style->margin_left,
            $style->margin_right
        ];

        
        $delta = (float) $style->length_in_pt($dims, 0);
        $min += $delta;
        $max += $delta;

        return [$min, $max, "min" => $min, "max" => $max];
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
}
