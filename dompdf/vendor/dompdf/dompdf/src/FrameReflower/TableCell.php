<?php

namespace Dompdf\FrameReflower;

use Dompdf\Exception;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableCell as TableCellFrameDecorator;
use Dompdf\Helpers;


class TableCell extends Block
{
    
    function __construct(BlockFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        
        $frame = $this->_frame;
        $table = TableFrameDecorator::find_parent_table($frame);
        if ($table === null) {
            throw new Exception("Parent table not found for table cell");
        }

        
        $this->_set_content();

        $style = $frame->get_style();
        $cellmap = $table->get_cellmap();

        [$x, $y] = $cellmap->get_frame_position($frame);
        $frame->set_position($x, $y);

        $cells = $cellmap->get_spanned_cells($frame);

        $w = 0;
        foreach ($cells["columns"] as $i) {
            $col = $cellmap->get_column($i);
            $w += $col["used-width"];
        }

        
        $h = $frame->get_containing_block("h");

        $left_space = (float)$style->length_in_pt([$style->margin_left,
                $style->padding_left,
                $style->border_left_width],
            $w);

        $right_space = (float)$style->length_in_pt([$style->padding_right,
                $style->margin_right,
                $style->border_right_width],
            $w);

        $top_space = (float)$style->length_in_pt([$style->margin_top,
                $style->padding_top,
                $style->border_top_width],
            $h);
        $bottom_space = (float)$style->length_in_pt([$style->margin_bottom,
                $style->padding_bottom,
                $style->border_bottom_width],
            $h);

        $cb_w = $w - $left_space - $right_space;
        $style->set_used("width", $cb_w);

        $content_x = $x + $left_space;
        $content_y = $line_y = $y + $top_space;

        
        $indent = (float)$style->length_in_pt($style->text_indent, $w);
        $frame->increase_line_width($indent);

        $page = $frame->get_root();

        
        $line_box = $frame->get_current_line_box();
        $line_box->y = $line_y;

        
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($content_x, $content_y, $cb_w, $h);
            $this->process_clear($child);
            $child->reflow($frame);
            $this->process_float($child, $content_x, $cb_w);

            if ($page->is_full()) {
                break;
            }
        }

        
        $style_height = (float) $style->length_in_pt($style->height, $h);
        $content_height = $this->_calculate_content_height();
        $height = max($style_height, $content_height);

        $frame->set_content_height($content_height);

        
        $cell_height = $height / count($cells["rows"]);

        if ($style_height <= $height) {
            $cell_height += $top_space + $bottom_space;
        }

        foreach ($cells["rows"] as $i) {
            $cellmap->set_row_height($i, $cell_height);
        }

        $style->set_used("height", $height);

        $this->_text_align();
        $this->vertical_align();

        
        foreach ($frame->get_children() as $child) {
            $this->position_relative($child);
        }
    }

    public function get_min_max_content_width(): array
    {
        
        
        $style = $this->_frame->get_style();
        $width = $style->width;
        $fixed_width = $width !== "auto" && !Helpers::is_percent($width);

        [$min, $max] = $this->get_min_max_child_width();

        
        
        if ($fixed_width) {
            $width = (float) $style->length_in_pt($width, 0);
            $min = max($width, $min);
            $max = $min;
        }

        
        $min_width = $this->resolve_min_width(null);
        $max_width = $this->resolve_max_width(null);
        $min = Helpers::clamp($min, $min_width, $max_width);
        $max = Helpers::clamp($max, $min_width, $max_width);

        return [$min, $max];
    }
}
