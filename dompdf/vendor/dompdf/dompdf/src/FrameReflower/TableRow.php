<?php

namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableRow as TableRowFrameDecorator;
use Dompdf\Exception;


class TableRow extends AbstractFrameReflower
{
    
    function __construct(TableRowFrameDecorator $frame)
    {
        parent::__construct($frame);
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

        $frame->position();
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow();

            if ($page->is_full()) {
                break;
            }
        }

        if ($page->is_full()) {
            return;
        }

        $table = TableFrameDecorator::find_parent_table($frame);
        if ($table === null) {
            throw new Exception("Parent table not found for table row");
        }
        $cellmap = $table->get_cellmap();

        $style->set_used("width", $cellmap->get_frame_width($frame));
        $style->set_used("height", $cellmap->get_frame_height($frame));

        $frame->set_position($cellmap->get_frame_position($frame));
    }

    
    public function get_min_max_width(): array
    {
        throw new Exception("Min/max width is undefined for table rows");
    }
}
