<?php

namespace Dompdf\FrameReflower;

use Dompdf\Exception;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableRowGroup as TableRowGroupFrameDecorator;


class TableRowGroup extends AbstractFrameReflower
{

    
    function __construct(TableRowGroupFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        
        $frame = $this->_frame;
        $page = $frame->get_root();
        $parent = $frame->get_parent();
        $dompdf_generated = $parent->get_frame()->get_node()->nodeName === "dompdf_generated";

        
        $this->_set_content();

        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb["x"], $cb["y"], $cb["w"], $cb["h"]);
            $child->reflow();

            
            $page->check_page_break($child);

            if ($page->is_full()) {
                break;
            }
        }

        if ($page->is_full() && $dompdf_generated && $frame->get_parent() === null) {
            return;
        }

        $table = TableFrameDecorator::find_parent_table($frame);
        if ($table === null) {
            throw new Exception("Parent table not found for table row group");
        }
        $cellmap = $table->get_cellmap();

        
        
        if ($page->is_full() && !$cellmap->frame_exists_in_cellmap($frame)) {
            return;
        }

        $style->set_used("width", $cellmap->get_frame_width($frame));
        $style->set_used("height", $cellmap->get_frame_height($frame));

        $frame->set_position($cellmap->get_frame_position($frame));
    }
}
