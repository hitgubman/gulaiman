<?php

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;


abstract class AbstractPositioner
{

    
    abstract function position(AbstractFrameDecorator $frame): void;

    
    function move(
        AbstractFrameDecorator $frame,
        float $offset_x,
        float $offset_y,
        bool $ignore_self = false
    ): void {
        [$x, $y] = $frame->get_position();

        if (!$ignore_self) {
            $frame->set_position($x + $offset_x, $y + $offset_y);
        }

        foreach ($frame->get_children() as $child) {
            $child->move($offset_x, $offset_y);
        }
    }
}
