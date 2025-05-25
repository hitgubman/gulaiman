<?php

namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\TableCell as TableCellFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;
use Dompdf\Exception;
use Dompdf\Css\Style;
use Dompdf\Helpers;


class Block extends AbstractFrameReflower
{
    
    const MIN_JUSTIFY_WIDTH = 0.80;

    
    protected $_frame;

    function __construct(BlockFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    protected function _calculate_width($width)
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $absolute = $frame->is_absolute();

        $cb = $frame->get_containing_block();
        $w = $cb["w"];

        $rm = $style->length_in_pt($style->margin_right, $w);
        $lm = $style->length_in_pt($style->margin_left, $w);

        $left = $style->length_in_pt($style->left, $w);
        $right = $style->length_in_pt($style->right, $w);

        
        $dims = [$style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right,
            $width !== "auto" ? $width : 0,
            $rm !== "auto" ? $rm : 0,
            $lm !== "auto" ? $lm : 0];

        
        if ($absolute) {
            $dims[] = $left !== "auto" ? $left : 0;
            $dims[] = $right !== "auto" ? $right : 0;
        }

        $sum = (float)$style->length_in_pt($dims, $w);

        
        $diff = $w - $sum;

        if ($absolute) {
            
            

            if ($width === "auto" || $left === "auto" || $right === "auto") {
                
                if ($lm === "auto") {
                    $lm = 0;
                }
                if ($rm === "auto") {
                    $rm = 0;
                }

                $block_parent = $frame->find_block_parent();
                $parent_content = $block_parent->get_content_box();
                $line = $block_parent->get_current_line_box();

                
                
                $inflow_x = $parent_content["x"] - $cb["x"] + $line->left + $line->w;

                if ($width === "auto" && $left === "auto" && $right === "auto") {
                    
                    
                    $left = $inflow_x;
                    [$min, $max] = $this->get_min_max_child_width();
                    $width = min(max($min, $diff - $left), $max);
                    $right = $diff - $left - $width;
                } elseif ($width === "auto" && $left === "auto") {
                    
                    
                    [$min, $max] = $this->get_min_max_child_width();
                    $width = min(max($min, $diff), $max);
                    $left = $diff - $width;
                } elseif ($width === "auto" && $right === "auto") {
                    
                    
                    [$min, $max] = $this->get_min_max_child_width();
                    $width = min(max($min, $diff), $max);
                    $right = $diff - $width;
                } elseif ($left === "auto" && $right === "auto") {
                    
                    $left = $inflow_x;
                    $right = $diff - $left;
                } elseif ($left === "auto") {
                    
                    $left = $diff;
                } elseif ($width === "auto") {
                    
                    $width = max($diff, 0);
                } else {
                    
                    
                    $right = $diff;
                }
            } else {
                
                if ($diff >= 0) {
                    if ($lm === "auto" && $rm === "auto") {
                        $lm = $rm = $diff / 2;
                    } elseif ($lm === "auto") {
                        $lm = $diff;
                    } elseif ($rm === "auto") {
                        $rm = $diff;
                    }
                } else {
                    
                    $right = $right + $diff;

                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }
                }
            }
        } elseif ($style->float !== "none" || $style->display === "inline-block") {
            
            
            

            if ($width === "auto") {
                [$min, $max] = $this->get_min_max_child_width();
                $width = min(max($min, $diff), $max);
            }
            if ($lm === "auto") {
                $lm = 0;
            }
            if ($rm === "auto") {
                $rm = 0;
            }
        } else {
            
            

            if ($diff >= 0) {
                
                if ($width === "auto") {
                    $width = $diff;

                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }
                } elseif ($lm === "auto" && $rm === "auto") {
                    $lm = $rm = $diff / 2;
                } elseif ($lm === "auto") {
                    $lm = $diff;
                } elseif ($rm === "auto") {
                    $rm = $diff;
                }
            } else {
                
                $rm = (float) $rm + $diff;

                if ($width === "auto") {
                    $width = 0;
                }
                if ($lm === "auto") {
                    $lm = 0;
                }
            }
        }

        return [
            "width" => $width,
            "margin_left" => $lm,
            "margin_right" => $rm,
            "left" => $left,
            "right" => $right,
        ];
    }

    
    protected function _calculate_restricted_width()
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        if (!isset($cb["w"])) {
            throw new Exception("Box property calculation requires containing block width");
        }

        $width = $style->length_in_pt($style->width, $cb["w"]);

        $values = $this->_calculate_width($width);
        $margin_left = $values["margin_left"];
        $margin_right = $values["margin_right"];
        $width = $values["width"];
        $left = $values["left"];
        $right = $values["right"];

        
        
        $min_width = $this->resolve_min_width($cb["w"]);
        $max_width = $this->resolve_max_width($cb["w"]);

        if ($width > $max_width) {
            $values = $this->_calculate_width($max_width);
            $margin_left = $values["margin_left"];
            $margin_right = $values["margin_right"];
            $width = $values["width"];
            $left = $values["left"];
            $right = $values["right"];
        }

        if ($width < $min_width) {
            $values = $this->_calculate_width($min_width);
            $margin_left = $values["margin_left"];
            $margin_right = $values["margin_right"];
            $width = $values["width"];
            $left = $values["left"];
            $right = $values["right"];
        }

        return [$width, $margin_left, $margin_right, $left, $right];
    }

    
    protected function _calculate_content_height(): float
    {
        $height = 0.0;
        $lines = $this->_frame->get_line_boxes();
        if (count($lines) > 0) {
            $last_line = end($lines);
            $content_box = $this->_frame->get_content_box();
            $height = $last_line->y + $last_line->h - $content_box["y"];
        }
        return $height;
    }

    
    protected function _calculate_restricted_height()
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $content_height = $this->_calculate_content_height();
        $cb = $frame->get_containing_block();

        $height = $style->length_in_pt($style->height, $cb["h"]);
        $margin_top = $style->length_in_pt($style->margin_top, $cb["w"]);
        $margin_bottom = $style->length_in_pt($style->margin_bottom, $cb["w"]);

        $top = $style->length_in_pt($style->top, $cb["h"]);
        $bottom = $style->length_in_pt($style->bottom, $cb["h"]);

        if ($frame->is_absolute()) {
            
            

            $h_dims = [
                $top !== "auto" ? $top : 0,
                $height !== "auto" ? $height : 0,
                $bottom !== "auto" ? $bottom : 0
            ];
            $w_dims = [
                $style->margin_top !== "auto" ? $style->margin_top : 0,
                $style->padding_top,
                $style->border_top_width,
                $style->border_bottom_width,
                $style->padding_bottom,
                $style->margin_bottom !== "auto" ? $style->margin_bottom : 0
            ];

            $sum = (float)$style->length_in_pt($h_dims, $cb["h"])
                + (float)$style->length_in_pt($w_dims, $cb["w"]);

            $diff = $cb["h"] - $sum;

            if ($height === "auto" || $top === "auto" || $bottom === "auto") {
                
                if ($margin_top === "auto") {
                    $margin_top = 0;
                }
                if ($margin_bottom === "auto") {
                    $margin_bottom = 0;
                }

                $block_parent = $frame->find_block_parent();
                $current_line = $block_parent->get_current_line_box();

                
                
                $inflow_y = $current_line->y - $cb["y"];

                if ($height === "auto" && $top === "auto" && $bottom === "auto") {
                    
                    $top = $inflow_y;
                    $height = $content_height;
                    $bottom = $diff - $top - $height;
                } elseif ($height === "auto" && $top === "auto") {
                    
                    $height = $content_height;
                    $top = $diff - $height;
                } elseif ($height === "auto" && $bottom === "auto") {
                    
                    $height = $content_height;
                    $bottom = $diff - $height;
                } elseif ($top === "auto" && $bottom === "auto") {
                    
                    $top = $inflow_y;
                    $bottom = $diff - $top;
                } elseif ($top === "auto") {
                    
                    $top = $diff;
                } elseif ($height === "auto") {
                    
                    $height = max($diff, 0);
                } else {
                    
                    
                    $bottom = $diff;
                }
            } else {
                
                if ($diff >= 0) {
                    if ($margin_top === "auto" && $margin_bottom === "auto") {
                        $margin_top = $margin_bottom = $diff / 2;
                    } elseif ($margin_top === "auto") {
                        $margin_top = $diff;
                    } elseif ($margin_bottom === "auto") {
                        $margin_bottom = $diff;
                    }
                } else {
                    
                    $bottom = $bottom + $diff;

                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }
                }
            }
        } else {
            
            

            if ($height === "auto") {
                $height = $content_height;
            }
            if ($margin_top === "auto") {
                $margin_top = 0;
            }
            if ($margin_bottom === "auto") {
                $margin_bottom = 0;
            }

            
            
            $min_height = $this->resolve_min_height($cb["h"]);
            $max_height = $this->resolve_max_height($cb["h"]);
            $height = Helpers::clamp($height, $min_height, $max_height);
        }

        
        
        
        
        

        return [$height, $margin_top, $margin_bottom, $top, $bottom];
    }

    
    protected function _text_align()
    {
        $style = $this->_frame->get_style();
        $w = $this->_frame->get_containing_block("w");
        $width = (float)$style->length_in_pt($style->width, $w);
        $text_indent = (float)$style->length_in_pt($style->text_indent, $w);

        switch ($style->text_align) {
            default:
            case "left":
                foreach ($this->_frame->get_line_boxes() as $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    if ($line->left) {
                        foreach ($line->frames_to_align() as $frame) {
                            $frame->move($line->left, 0);
                        }
                    }
                }
                break;

            case "right":
                foreach ($this->_frame->get_line_boxes() as $i => $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    $indent = $i === 0 ? $text_indent : 0;
                    $dx = $width - $line->w - $line->right - $indent;

                    foreach ($line->frames_to_align() as $frame) {
                        $frame->move($dx, 0);
                    }
                }
                break;

            case "justify":
                
                
                
                $lines = $this->_frame->get_line_boxes();
                $last_line_index = $this->_frame->is_split ? null : count($lines) - 1;

                foreach ($lines as $i => $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    if ($line->left) {
                        foreach ($line->frames_to_align() as $frame) {
                            $frame->move($line->left, 0);
                        }
                    }

                    if ($line->br || $i === $last_line_index) {
                        continue;
                    }

                    $frames = $line->get_frames();
                    $other_frame_count = 0;

                    foreach ($frames as $frame) {
                        if (!($frame instanceof TextFrameDecorator)) {
                            $other_frame_count++;
                        }
                    }

                    $word_count = $line->wc + $other_frame_count;

                    
                    if ($word_count > 1) {
                        $indent = $i === 0 ? $text_indent : 0;
                        $spacing = ($width - $line->get_width() - $indent) / ($word_count - 1);
                    } else {
                        $spacing = 0;
                    }

                    $dx = 0;
                    foreach ($frames as $frame) {
                        if ($frame instanceof TextFrameDecorator) {
                            $text = $frame->get_text();
                            $spaces = mb_substr_count($text, " ");

                            $frame->move($dx, 0);
                            $frame->set_text_spacing($spacing);

                            $dx += $spaces * $spacing;
                        } else {
                            $frame->move($dx, 0);
                        }
                    }

                    
                    $line->w = $width;
                }
                break;

            case "center":
            case "centre":
                foreach ($this->_frame->get_line_boxes() as $i => $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    $indent = $i === 0 ? $text_indent : 0;
                    $dx = ($width + $line->left - $line->w - $line->right - $indent) / 2;

                    foreach ($line->frames_to_align() as $frame) {
                        $frame->move($dx, 0);
                    }
                }
                break;
        }
    }

    
    function vertical_align()
    {
        $fontMetrics = $this->get_dompdf()->getFontMetrics();

        foreach ($this->_frame->get_line_boxes() as $line) {
            $height = $line->h;

            
            foreach ($line->get_list_markers() as $marker) {
                $x = $marker->get_position("x");
                $marker->set_position($x, $line->y);
            }

            foreach ($line->frames_to_align() as $frame) {
                $style = $frame->get_style();
                $isInlineBlock = $style->display !== "inline"
                    && $style->display !== "-dompdf-list-bullet";

                $baseline = $fontMetrics->getFontBaseline($style->font_family, $style->font_size);
                $y_offset = 0;

                
                if ($isInlineBlock) {
                    
                    
                    
                    
                    
                    $skip = true;

                    foreach ($line->get_frames() as $other) {
                        if ($other !== $frame
                            && !($other->is_text_node() && $other->get_node()->nodeValue === "")
                         ) {
                            $skip = false;
                            break;
                        }
                    }

                    if ($skip) {
                        continue;
                    }

                    $marginHeight = $frame->get_margin_height();
                    $imageHeightDiff = $height * 0.8 - $marginHeight;

                    $align = $frame->get_style()->vertical_align;
                    if (in_array($align, Style::VERTICAL_ALIGN_KEYWORDS, true)) {
                        switch ($align) {
                            case "middle":
                                $y_offset = $imageHeightDiff / 2;
                                break;

                            case "sub":
                                $y_offset = 0.3 * $height + $imageHeightDiff;
                                break;

                            case "super":
                                $y_offset = -0.2 * $height + $imageHeightDiff;
                                break;

                            case "text-top": 
                                $y_offset = $height - $style->line_height;
                                break;

                            case "top":
                                break;

                            case "text-bottom": 
                            case "bottom":
                                $y_offset = 0.3 * $height + $imageHeightDiff;
                                break;

                            case "baseline":
                            default:
                                $y_offset = $imageHeightDiff;
                                break;
                        }
                    } else {
                        $y_offset = $baseline - (float)$style->length_in_pt($align, $style->font_size) - $marginHeight;
                    }
                } else {
                    $parent = $frame->get_parent();
                    if ($parent instanceof TableCellFrameDecorator) {
                        $align = "baseline";
                    } else {
                        $align = $parent->get_style()->vertical_align;
                    }
                    if (in_array($align, Style::VERTICAL_ALIGN_KEYWORDS, true)) {
                        switch ($align) {
                            case "middle":
                                $y_offset = ($height * 0.8 - $baseline) / 2;
                                break;

                            case "sub":
                                $y_offset = $height * 0.8 - $baseline * 0.5;
                                break;

                            case "super":
                                $y_offset = $height * 0.8 - $baseline * 1.4;
                                break;

                            case "text-top":
                            case "top": 
                                break;

                            case "text-bottom":
                            case "bottom":
                                $y_offset = $height * 0.8 - $baseline;
                                break;

                            case "baseline":
                            default:
                                $y_offset = $height * 0.8 - $baseline;
                                break;
                        }
                    } else {
                        $y_offset = $height * 0.8 - $baseline - (float)$style->length_in_pt($align, $style->font_size);
                    }
                }

                if ($y_offset !== 0) {
                    $frame->move(0, $y_offset);
                }
            }
        }
    }

    
    function process_clear(AbstractFrameDecorator $child)
    {
        $child_style = $child->get_style();
        $root = $this->_frame->get_root();

        
        if ($child_style->clear !== "none") {
            
            if ($child->get_prev_sibling() !== null) {
                $this->_frame->add_line();
            }
            if ($child_style->float !== "none" && $child->get_next_sibling()) {
                $this->_frame->set_current_line_number($this->_frame->get_current_line_number() - 1);
            }

            $lowest_y = $root->get_lowest_float_offset($child);

            
            if ($lowest_y) {
                if ($child->is_in_flow()) {
                    $line_box = $this->_frame->get_current_line_box();
                    $line_box->y = $lowest_y + $child->get_margin_height();
                    $line_box->left = 0;
                    $line_box->right = 0;
                }

                $child->move(0, $lowest_y - $child->get_position("y"));
            }
        }
    }

    
    function process_float(AbstractFrameDecorator $child, $cb_x, $cb_w)
    {
        $child_style = $child->get_style();
        $root = $this->_frame->get_root();

        
        if ($child_style->float !== "none") {
            $root->add_floating_frame($child);

            
            $next = $child->get_next_sibling();
            if ($next && $next instanceof TextFrameDecorator) {
                $next->set_text(ltrim($next->get_text()));
            }

            $line_box = $this->_frame->get_current_line_box();
            list($old_x, $old_y) = $child->get_position();

            $float_x = $cb_x;
            $float_y = $old_y;
            $float_w = $child->get_margin_width();

            if ($child_style->clear === "none") {
                switch ($child_style->float) {
                    case "left":
                        $float_x += $line_box->left;
                        break;
                    case "right":
                        $float_x += ($cb_w - $line_box->right - $float_w);
                        break;
                }
            } else {
                if ($child_style->float === "right") {
                    $float_x += ($cb_w - $float_w);
                }
            }

            if ($cb_w < $float_x + $float_w - $old_x) {
                
            }

            $line_box->get_float_offsets();

            if ($child->_float_next_line) {
                $float_y += $line_box->h;
            }

            $child->set_position($float_x, $float_y);
            $child->move($float_x - $old_x, $float_y - $old_y, true);
        }
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {

        
        $page = $this->_frame->get_root();
        $page->check_forced_page_break($this->_frame);

        
        if ($page->is_full()) {
            return;
        }

        $this->determine_absolute_containing_block();

        
        $this->_set_content();

        
        if ($block && $this->_frame->is_in_flow()) {
            $this->_frame->inherit_dangling_markers($block);
        }

        
        $this->_collapse_margins();

        $style = $this->_frame->get_style();
        $cb = $this->_frame->get_containing_block();

        
        
        [$width, $margin_left, $margin_right, $left, $right] = $this->_calculate_restricted_width();

        
        $style->set_used("width", $width);
        $style->set_used("margin_left", $margin_left);
        $style->set_used("margin_right", $margin_right);
        $style->set_used("left", $left);
        $style->set_used("right", $right);

        $margin_top = $style->length_in_pt($style->margin_top, $cb["w"]);
        $margin_bottom = $style->length_in_pt($style->margin_bottom, $cb["w"]);

        $auto_top = $style->top === "auto";
        $auto_margin_top = $margin_top === "auto";

        
        $this->_frame->position();
        [$x, $y] = $this->_frame->get_position();

        
        $indent = (float)$style->length_in_pt($style->text_indent, $cb["w"]);
        $this->_frame->increase_line_width($indent);

        
        $top = (float)$style->length_in_pt([
            $margin_top !== "auto" ? $margin_top : 0,
            $style->border_top_width,
            $style->padding_top
        ], $cb["w"]);
        $bottom = (float)$style->length_in_pt([
            $margin_bottom !== "auto" ? $margin_bottom : 0,
            $style->border_bottom_width,
            $style->padding_bottom
        ], $cb["w"]);

        $cb_x = $x + (float)$margin_left + (float)$style->length_in_pt([$style->border_left_width,
                $style->padding_left], $cb["w"]);

        $cb_y = $y + $top;

        $height = $style->length_in_pt($style->height, $cb["h"]);
        if ($height === "auto") {
            $height = ($cb["h"] + $cb["y"]) - $bottom - $cb_y;
        }

        
        $line_box = $this->_frame->get_current_line_box();
        $line_box->y = $cb_y;
        $line_box->get_float_offsets();

        
        foreach ($this->_frame->get_children() as $child) {
            $child->set_containing_block($cb_x, $cb_y, $width, $height);
            $this->process_clear($child);
            $child->reflow($this->_frame);

            
            $page->check_page_break($child);

            
            
            
            if ($page->is_full() && $child->get_position("x") === null) {
                break;
            }

            $this->process_float($child, $cb_x, $width);
        }

        
        
        if ($page->is_full() && $this->_frame->get_position("x") === null) {
            return;
        }

        
        [$height, $margin_top, $margin_bottom, $top, $bottom] = $this->_calculate_restricted_height();

        $style->set_used("height", $height);
        $style->set_used("margin_top", $margin_top);
        $style->set_used("margin_bottom", $margin_bottom);
        $style->set_used("top", $top);
        $style->set_used("bottom", $bottom);

        if ($this->_frame->is_absolute()) {
            if ($auto_top) {
                $this->_frame->move(0, $top);
            }
            if ($auto_margin_top) {
                $this->_frame->move(0, $margin_top, true);
            }
        }

        $this->_text_align();
        $this->vertical_align();

        
        foreach ($this->_frame->get_children() as $child) {
            $this->position_relative($child);
        }

        if ($block && $this->_frame->is_in_flow()) {
            $block->add_frame_to_line($this->_frame);

            if ($this->_frame->is_block_level()) {
                $block->add_line();
            }
        }
    }

    public function get_min_max_content_width(): array
    {
        
        
        
        
        $style = $this->_frame->get_style();
        $width = $style->width;
        $fixed_width = $width !== "auto" && !Helpers::is_percent($width);

        
        
        if ($fixed_width) {
            $min = (float) $style->length_in_pt($width, 0);
            $max = $min;
        } else {
            [$min, $max] = $this->get_min_max_child_width();
        }

        
        $min_width = $this->resolve_min_width(null);
        $max_width = $this->resolve_max_width(null);
        $min = Helpers::clamp($min, $min_width, $max_width);
        $max = Helpers::clamp($max, $min_width, $max_width);

        return [$min, $max];
    }
}
