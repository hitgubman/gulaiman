<?php

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\ListBullet as ListBulletFrameDecorator;


class ListBullet extends AbstractPositioner
{
    
    function position(AbstractFrameDecorator $frame): void
    {
        
        
        $parent = $frame->get_parent();
        $style = $parent->get_style();
        $cbw = $parent->get_containing_block("w");
        $margin_left = (float) $style->length_in_pt($style->margin_left, $cbw);
        $border_edge = $parent->get_position("x") + $margin_left;

        
        $x = $border_edge - $frame->get_margin_width();

        
        
        $p = $frame->find_block_parent();
        $y = $p->get_current_line_box()->y;

        $frame->set_position($x, $y);
    }
}
