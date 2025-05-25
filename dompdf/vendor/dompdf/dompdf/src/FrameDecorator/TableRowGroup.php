<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;


class TableRowGroup extends AbstractFrameDecorator
{

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            parent::split($child, $page_break, $forced);
            return;
        }

        
        
        $parent = $this->get_parent();
        $cellmap = $parent->get_cellmap();
        $iter = $child;

        while ($iter) {
            $cellmap->remove_row($iter);
            $iter = $iter->get_next_sibling();
        }

        
        $iter = $this->get_next_sibling();

        while ($iter) {
            $cellmap->remove_row_group($iter);
            $iter = $iter->get_next_sibling();
        }

        
        
        if ($child === $this->get_first_child()) {
            $cellmap->remove_row_group($this);
            parent::split(null, $page_break, $forced);
            return;
        }

        $cellmap->update_row_group($this, $child->get_prev_sibling());
        parent::split($child, $page_break, $forced);
    }
}
