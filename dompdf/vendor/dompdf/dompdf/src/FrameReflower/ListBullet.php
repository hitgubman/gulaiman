<?php

namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\ListBullet as ListBulletFrameDecorator;


class ListBullet extends AbstractFrameReflower
{

    
    function __construct(ListBulletFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        if ($block === null) {
            return;
        }

        
        $frame = $this->_frame;
        $style = $frame->get_style();

        $style->set_used("width", $frame->get_width());
        $frame->position();

        if ($style->list_style_position === "inside") {
            $block->add_frame_to_line($frame);
        } else {
            $block->add_dangling_marker($frame);
        }
    }
}
