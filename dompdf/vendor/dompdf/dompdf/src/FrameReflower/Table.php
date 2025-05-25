<?php

namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\Helpers;


class Table extends AbstractFrameReflower
{
    
    protected $_frame;

    
    protected $_state;

    
    function __construct(TableFrameDecorator $frame)
    {
        $this->_state = null;
        parent::__construct($frame);
    }

    
    public function reset(): void
    {
        parent::reset();
        $this->_state = null;
    }

    protected function _assign_widths()
    {
        $style = $this->_frame->get_style();

        
        
        $delta = $this->_state["width_delta"];
        $min_width = $this->_state["min_width"];
        $max_width = $this->_state["max_width"];
        $percent_used = $this->_state["percent_used"];
        $absolute_used = $this->_state["absolute_used"];
        $auto_min = $this->_state["auto_min"];

        $absolute =& $this->_state["absolute"];
        $percent =& $this->_state["percent"];
        $auto =& $this->_state["auto"];

        
        
        $cb = $this->_frame->get_containing_block();
        $columns =& $this->_frame->get_cellmap()->get_columns();

        $width = $style->width;
        $min_table_width = $this->resolve_min_width($cb["w"]) - $delta;

        if ($width !== "auto") {
            $preferred_width = (float) $style->length_in_pt($width, $cb["w"]) - $delta;

            if ($preferred_width < $min_table_width) {
                $preferred_width = $min_table_width;
            }

            if ($preferred_width > $min_width) {
                $width = $preferred_width;
            } else {
                $width = $min_width;
            }

        } else {
            if ($max_width + $delta < $cb["w"]) {
                $width = $max_width;
            } elseif ($cb["w"] - $delta > $min_width) {
                $width = $cb["w"] - $delta;
            } else {
                $width = $min_width;
            }

            if ($width < $min_table_width) {
                $width = $min_table_width;
            }

        }

        
        $style->set_used("width", $width);

        $cellmap = $this->_frame->get_cellmap();

        if ($cellmap->is_columns_locked()) {
            return;
        }

        
        if ($width == $max_width) {
            foreach ($columns as $i => $col) {
                $cellmap->set_column_width($i, $col["max-width"]);
            }

            return;
        }

        
        if ($width > $min_width) {
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            
            

            
            if ($percent_used == 0 && count($auto)) {
                foreach ($absolute as $i) {
                    $w = $columns[$i]["min-width"];
                    $cellmap->set_column_width($i, $w);
                }

                if ($width < $max_width) {
                    $increment = $width - $min_width;
                    $table_delta = $max_width - $min_width;

                    foreach ($auto as $i) {
                        $min = $columns[$i]["min-width"];
                        $max = $columns[$i]["max-width"];
                        $col_delta = $max - $min;
                        $w = $min + $increment * ($col_delta / $table_delta);
                        $cellmap->set_column_width($i, $w);
                    }
                } else {
                    $increment = $width - $max_width;
                    $auto_max = $max_width - $absolute_used;

                    foreach ($auto as $i) {
                        $max = $columns[$i]["max-width"];
                        $f = $auto_max > 0 ? $max / $auto_max : 1 / count($auto);
                        $w = $max + $increment * $f;
                        $cellmap->set_column_width($i, $w);
                    }
                }
                return;
            }

            
            if ($percent_used == 0 && !count($auto)) {
                $increment = $width - $absolute_used;

                foreach ($absolute as $i) {
                    $abs = $columns[$i]["min-width"];
                    $f = $absolute_used > 0 ? $abs / $absolute_used : 1 / count($absolute);
                    $w = $abs + $increment * $f;
                    $cellmap->set_column_width($i, $w);
                }
                return;
            }

            
            if ($percent_used > 0) {
                
                
                if ($percent_used > 100 || count($auto) == 0) {
                    $scale = 100 / $percent_used;
                } else {
                    $scale = 1;
                }

                
                
                
                $used_width = $auto_min + $absolute_used;

                foreach ($absolute as $i) {
                    $w = $columns[$i]["min-width"];
                    $cellmap->set_column_width($i, $w);
                }

                $percent_min = 0;

                foreach ($percent as $i) {
                    $percent_min += $columns[$i]["min-width"];
                }

                
                foreach ($percent as $i) {
                    $min = $columns[$i]["min-width"];
                    $percent_min -= $min;
                    $slack = $width - $used_width - $percent_min;

                    $columns[$i]["percent"] *= $scale;
                    $w = min($columns[$i]["percent"] * $width / 100, $slack);

                    if ($w < $min) {
                        $w = $min;
                    }

                    $cellmap->set_column_width($i, $w);
                    $used_width += $w;
                }

                
                
                if (count($auto) > 0) {
                    $increment = ($width - $used_width) / count($auto);

                    foreach ($auto as $i) {
                        $w = $columns[$i]["min-width"] + $increment;
                        $cellmap->set_column_width($i, $w);
                    }
                }
                return;
            }
        } else {
            
            
            foreach ($columns as $i => $col) {
                $cellmap->set_column_width($i, $col["min-width"]);
            }
        }
    }

    
    protected function _calculate_height()
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        $height = $style->length_in_pt($style->height, $cb["h"]);

        $cellmap = $frame->get_cellmap();
        $cellmap->assign_frame_heights();
        $rows = $cellmap->get_rows();

        
        $content_height = 0.0;
        foreach ($rows as $r) {
            $content_height += $r["height"];
        }

        if ($height === "auto") {
            $height = $content_height;
        }

        
        
        $min_height = $this->resolve_min_height($cb["h"]);
        $max_height = $this->resolve_max_height($cb["h"]);
        $height = Helpers::clamp($height, $min_height, $max_height);

        
        if ($height <= $content_height) {
            $height = $content_height;
        } else {
            
            
        }

        return $height;
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        
        $frame = $this->_frame;

        
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        
        if ($page->is_full()) {
            return;
        }

        
        
        
        
        $page->table_reflow_start();

        $this->determine_absolute_containing_block();

        
        $this->_set_content();

        
        $this->_collapse_margins();

        
        

        if (is_null($this->_state)) {
            $this->get_min_max_width();
        }

        $cb = $frame->get_containing_block();
        $style = $frame->get_style();

        
        
        
        if ($style->border_collapse === "separate") {
            [$h, $v] = $style->border_spacing;
            $v = $v / 2;
            $h = $h / 2;

            $style->set_used("padding_left", (float)$style->length_in_pt($style->padding_left, $cb["w"]) + $h);
            $style->set_used("padding_right", (float)$style->length_in_pt($style->padding_right, $cb["w"]) + $h);
            $style->set_used("padding_top", (float)$style->length_in_pt($style->padding_top, $cb["w"]) + $v);
            $style->set_used("padding_bottom", (float)$style->length_in_pt($style->padding_bottom, $cb["w"]) + $v);
        }

        $this->_assign_widths();

        
        $delta = $this->_state["width_delta"];
        $width = $style->width;
        $left = $style->length_in_pt($style->margin_left, $cb["w"]);
        $right = $style->length_in_pt($style->margin_right, $cb["w"]);

        $diff = (float) $cb["w"] - (float) $width - $delta;

        if ($left === "auto" && $right === "auto") {
            if ($diff < 0) {
                $left = 0;
                $right = $diff;
            } else {
                $left = $right = $diff / 2;
            }
        } else {
            if ($left === "auto") {
                $left = max($diff - $right, 0);
            }
            if ($right === "auto") {
                $right = max($diff - $left, 0);
            }
        }

        $style->set_used("margin_left", $left);
        $style->set_used("margin_right", $right);

        $frame->position();
        [$x, $y] = $frame->get_position();

        
        $offset_x = (float)$left + (float)$style->length_in_pt([
            $style->padding_left,
            $style->border_left_width
        ], $cb["w"]);
        $offset_y = (float)$style->length_in_pt([
            $style->margin_top,
            $style->border_top_width,
            $style->padding_top
        ], $cb["w"]);
        $content_x = $x + $offset_x;
        $content_y = $y + $offset_y;

        if (isset($cb["h"])) {
            $h = $cb["h"];
        } else {
            $h = null;
        }

        $cellmap = $frame->get_cellmap();
        $col =& $cellmap->get_column(0);
        $col["x"] = $offset_x;

        $row =& $cellmap->get_row(0);
        $row["y"] = $offset_y;

        $cellmap->assign_x_positions();

        
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($content_x, $content_y, $width, $h);
            $child->reflow();

            if (!$page->in_nested_table()) {
                
                $page->check_page_break($child);
    
                if ($page->is_full()) {
                    break;
                }
            }
        }

        
        
        if ($page->is_full() && $frame->get_position("x") === null) {
            $page->table_reflow_end();
            return;
        }

        
        $style->set_used("height", $this->_calculate_height());

        $page->table_reflow_end();

        if ($block && $frame->is_in_flow()) {
            $block->add_frame_to_line($frame);

            if ($frame->is_block_level()) {
                $block->add_line();
            }
        }
    }

    public function get_min_max_width(): array
    {
        if (!is_null($this->_min_max_cache)) {
            return $this->_min_max_cache;
        }

        $style = $this->_frame->get_style();
        $cellmap = $this->_frame->get_cellmap();

        $this->_frame->normalize();

        
        
        $cellmap->add_frame($this->_frame);

        
        
        $this->_state = [];
        $this->_state["min_width"] = 0;
        $this->_state["max_width"] = 0;

        $this->_state["percent_used"] = 0;
        $this->_state["absolute_used"] = 0;
        $this->_state["auto_min"] = 0;

        $this->_state["absolute"] = [];
        $this->_state["percent"] = [];
        $this->_state["auto"] = [];

        $columns =& $cellmap->get_columns();
        foreach ($columns as $i => $col) {
            $this->_state["min_width"] += $col["min-width"];
            $this->_state["max_width"] += $col["max-width"];

            if ($col["absolute"] > 0) {
                $this->_state["absolute"][] = $i;
                $this->_state["absolute_used"] += $col["min-width"];
            } elseif ($col["percent"] > 0) {
                $this->_state["percent"][] = $i;
                $this->_state["percent_used"] += $col["percent"];
            } else {
                $this->_state["auto"][] = $i;
                $this->_state["auto_min"] += $col["min-width"];
            }
        }

        
        $cb_w = $this->_frame->get_containing_block("w");
        $lm = (float) $style->length_in_pt($style->margin_left, $cb_w);
        $rm = (float) $style->length_in_pt($style->margin_right, $cb_w);

        $dims = [
            $style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right
        ];

        if ($style->border_collapse !== "collapse") {
            list($dims[]) = $style->border_spacing;
        }

        $delta = (float) $style->length_in_pt($dims, $cb_w);

        $this->_state["width_delta"] = $delta;

        $min_width = $this->_state["min_width"] + $delta + $lm + $rm;
        $max_width = $this->_state["max_width"] + $delta + $lm + $rm;

        return $this->_min_max_cache = [
            $min_width,
            $max_width,
            "min" => $min_width,
            "max" => $max_width
        ];
    }
}
