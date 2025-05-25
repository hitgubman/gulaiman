<?php

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Inline as InlineFrameDecorator;
use Dompdf\Exception;
use Dompdf\Helpers;


class Inline extends AbstractPositioner
{

    
    function position(AbstractFrameDecorator $frame): void
    {
        
        $block = $frame->find_block_parent();
        $cb = $frame->get_containing_block();

        if (!$block) {
            
            
            
            $frame->set_position($cb["x"], $cb["y"]);
            return;
        }

        $line = $block->get_current_line_box();

        if (!$frame->is_text_node() && !($frame instanceof InlineFrameDecorator)) {
            
            
            $width = $frame->get_margin_width();
            $available_width = $cb["w"] - $line->left - $line->w - $line->right;

            if (Helpers::lengthGreater($width, $available_width)) {
                $block->add_line();
                $line = $block->get_current_line_box();
            }
        }

        $frame->set_position($cb["x"] + $line->w, $line->y);
    }
}
