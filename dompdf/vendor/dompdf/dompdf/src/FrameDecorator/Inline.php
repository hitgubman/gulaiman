<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Exception;


class Inline extends AbstractFrameDecorator
{

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    
    public function get_margin_height(): float
    {
        $style = $this->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $this->_dompdf->getFontMetrics()->getFontHeight($font, $size);

        return ($style->line_height / ($size > 0 ? $size : 1)) * $fontHeight;
    }

    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $this->get_parent()->split($this, $page_break, $forced);
            return;
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        $this->revert_counter_increment();
        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        
        $style->margin_right = 0.0;
        $style->padding_right = 0.0;
        $style->border_right_width = 0.0;
        $style->border_top_right_radius = 0.0;
        $style->border_bottom_right_radius = 0.0;

        
        
        $split_style->margin_left = 0.0;
        $split_style->padding_left = 0.0;
        $split_style->border_left_width = 0.0;
        $split_style->border_top_left_radius = 0.0;
        $split_style->border_bottom_left_radius = 0.0;

        
        if ($split->get_node()->nodeName == "dompdf_generated") {
            $split_style->content = "normal";
        }

        
        
        
        if (($url = $style->background_image) && $url !== "none"
            && ($repeat = $style->background_repeat) && $repeat !== "repeat" && $repeat !== "repeat-x"
        ) {
            $split_style->background_image = "none";
        }

        $this->get_parent()->insert_child_after($split, $this);

        
        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $split->append_child($frame);
        }

        $parent = $this->get_parent();

        if ($page_break) {
            $parent->split($split, $page_break, $forced);
        } elseif ($parent instanceof Inline) {
            $parent->split($split);
        }
    }

}
