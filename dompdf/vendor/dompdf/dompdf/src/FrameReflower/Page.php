<?php

namespace Dompdf\FrameReflower;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Page as PageFrameDecorator;


class Page extends AbstractFrameReflower
{

    
    private $_callbacks;

    
    private $_canvas;

    
    function __construct(PageFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    function apply_page_style(Frame $frame, $page_number)
    {
        $style = $frame->get_style();
        $page_styles = $style->get_stylesheet()->get_page_styles();

        
        if (count($page_styles) > 1) {
            $odd = $page_number % 2 == 1;
            $first = $page_number == 1;

            $style = clone $page_styles["base"];

            
            if ($odd && isset($page_styles[":right"])) {
                $style->merge($page_styles[":right"]);
            }

            if ($odd && isset($page_styles[":odd"])) {
                $style->merge($page_styles[":odd"]);
            }

            
            if (!$odd && isset($page_styles[":left"])) {
                $style->merge($page_styles[":left"]);
            }

            if (!$odd && isset($page_styles[":even"])) {
                $style->merge($page_styles[":even"]);
            }

            if ($first && isset($page_styles[":first"])) {
                $style->merge($page_styles[":first"]);
            }

            $frame->set_style($style);
        }

        $frame->calculate_bottom_page_edge();
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        
        $frame = $this->_frame;
        $child = $frame->get_first_child();
        $fixed_children = [];
        $prev_child = null;
        $current_page = 0;

        while ($child) {
            $this->apply_page_style($frame, $current_page + 1);

            $style = $frame->get_style();

            
            $cb = $frame->get_containing_block();
            $left = (float)$style->length_in_pt($style->margin_left, $cb["w"]);
            $right = (float)$style->length_in_pt($style->margin_right, $cb["w"]);
            $top = (float)$style->length_in_pt($style->margin_top, $cb["h"]);
            $bottom = (float)$style->length_in_pt($style->margin_bottom, $cb["h"]);

            $content_x = $cb["x"] + $left;
            $content_y = $cb["y"] + $top;
            $content_width = $cb["w"] - $left - $right;
            $content_height = $cb["h"] - $top - $bottom;

            
            if ($current_page == 0) {
                foreach ($child->get_children() as $onechild) {
                    if ($onechild->get_style()->position === "fixed") {
                        $fixed_children[] = $onechild->deep_copy();
                    }
                }
                $fixed_children = array_reverse($fixed_children);
            }

            $child->set_containing_block($content_x, $content_y, $content_width, $content_height);

            
            $this->_check_callbacks("begin_page_reflow", $child);

            
            if ($current_page >= 1) {
                foreach ($fixed_children as $fixed_child) {
                    $child->insert_child_before($fixed_child->deep_copy(), $child->get_first_child());
                }
            }

            $child->reflow();
            $next_child = $child->get_next_sibling();

            
            $this->_check_callbacks("begin_page_render", $child);

            
            $frame->get_renderer()->render($child);

            
            $this->_check_callbacks("end_page_render", $child);

            if ($next_child) {
                $frame->next_page();
            }

            
            
            if ($prev_child) {
                $prev_child->dispose(true);
            }
            $prev_child = $child;
            $child = $next_child;
            $current_page++;
        }

        
        if ($prev_child) {
            $prev_child->dispose(true);
        }
    }

    
    protected function _check_callbacks(string $event, Frame $frame): void
    {
        if (!isset($this->_callbacks)) {
            $dompdf = $this->get_dompdf();
            $this->_callbacks = $dompdf->getCallbacks();
            $this->_canvas = $dompdf->getCanvas();
        }

        if (isset($this->_callbacks[$event])) {
            $fs = $this->_callbacks[$event];
            $canvas = $this->_canvas;
            $fontMetrics = $this->get_dompdf()->getFontMetrics();

            foreach ($fs as $f) {
                $f($frame, $canvas, $fontMetrics);
            }
        }
    }
}
