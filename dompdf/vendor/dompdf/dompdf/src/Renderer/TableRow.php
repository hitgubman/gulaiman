<?php

namespace Dompdf\Renderer;

use Dompdf\Frame;


class TableRow extends Block
{
    
    function render(Frame $frame)
    {
        $style = $frame->get_style();
        $node = $frame->get_node();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        $border_box = $frame->get_border_box();

        
        
        
        
        
        

        $this->_render_outline($frame, $border_box);

        $this->addNamedDest($node);
        $this->addHyperlink($node, $border_box);
    }
}
