<?php

namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Inline as InlineFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;


class Inline extends AbstractFrameReflower
{
    
    function __construct(InlineFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    protected function reflow_empty(BlockFrameDecorator $block): void
    {
        
        $frame = $this->_frame;
        $style = $frame->get_style();

        
        $style->set_used("width", 0.0);

        $cb = $frame->get_containing_block();
        $line = $block->get_current_line_box();
        $width = $frame->get_margin_width();

        if ($width > ($cb["w"] - $line->left - $line->w - $line->right)) {
            $block->add_line();

            
            $child = $frame;
            $p = $child->get_parent();
            while ($p instanceof InlineFrameDecorator && !$child->get_prev_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if ($p instanceof InlineFrameDecorator) {
                
                
                $p->split($child);
                return;
            }
        }

        $frame->position();
        $block->add_frame_to_line($frame);
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        
        $frame = $this->_frame;

        
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        if ($page->is_full()) {
            return;
        }

        
        $this->_set_content();

        $style = $frame->get_style();

        
        
        
        if ($style->margin_left === "auto") {
            $style->set_used("margin_left", 0.0);
        }
        if ($style->margin_right === "auto") {
            $style->set_used("margin_right", 0.0);
        }
        if ($style->margin_top === "auto") {
            $style->set_used("margin_top", 0.0);
        }
        if ($style->margin_bottom === "auto") {
            $style->set_used("margin_bottom", 0.0);
        }

        
        if ($frame->get_node()->nodeName === "br") {
            if ($block) {
                $line = $block->get_current_line_box();
                $frame->set_containing_line($line);
                $block->maximize_line_height($frame->get_margin_height(), $frame);
                $block->add_line(true);

                $next = $frame->get_next_sibling();
                $p = $frame->get_parent();

                if ($next && $p instanceof InlineFrameDecorator) {
                    $p->split($next);
                }
            }
            return;
        }

        
        if (!$frame->get_first_child()) {
            if ($block) {
                $this->reflow_empty($block);
            }
            return;
        }

        
        
        if (($f = $frame->get_first_child()) && $f instanceof TextFrameDecorator) {
            $f_style = $f->get_style();
            $f_style->margin_left = $style->margin_left;
            $f_style->padding_left = $style->padding_left;
            $f_style->border_left_width = $style->border_left_width;
        }

        if (($l = $frame->get_last_child()) && $l instanceof TextFrameDecorator) {
            $l_style = $l->get_style();
            $l_style->margin_right = $style->margin_right;
            $l_style->padding_right = $style->padding_right;
            $l_style->border_right_width = $style->border_right_width;
        }

        $frame->position();

        $cb = $frame->get_containing_block();

        
        
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow($block);

            
            
            if (!$frame->content_set) {
                return;
            }
        }

        
        
        $child = $frame->get_first_child();
        while ($child && !$child->is_in_flow()) {
            $child = $child->get_next_sibling();
        }

        if ($child) {
            [$x, $y] = $child->get_position();
            $frame->set_position($x, $y);
        }

        
        foreach ($frame->get_children() as $child) {
            $this->position_relative($child);
        }

        if ($block) {
            $block->add_frame_to_line($frame);
        }
    }
}
