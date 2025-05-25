<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\LineBox;


class Block extends AbstractFrameDecorator
{
    
    protected $_cl;

    
    protected $_line_boxes;

    
    protected $dangling_markers;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);

        $this->_line_boxes = [new LineBox($this)];
        $this->_cl = 0;
        $this->dangling_markers = [];
    }

    function reset()
    {
        parent::reset();

        $this->_line_boxes = [new LineBox($this)];
        $this->_cl = 0;
        $this->dangling_markers = [];
    }

    
    function get_current_line_box()
    {
        return $this->_line_boxes[$this->_cl];
    }

    
    function get_current_line_number()
    {
        return $this->_cl;
    }

    
    function get_line_boxes()
    {
        return $this->_line_boxes;
    }

    
    function set_current_line_number($line_number)
    {
        $line_boxes_count = count($this->_line_boxes);
        $cl = max(min($line_number, $line_boxes_count), 0);
        return ($this->_cl = $cl);
    }

    
    function clear_line($i)
    {
        if (isset($this->_line_boxes[$i])) {
            unset($this->_line_boxes[$i]);
        }
    }

    
    public function add_frame_to_line(Frame $frame): ?LineBox
    {
        $current_line = $this->_line_boxes[$this->_cl];
        $frame->set_containing_line($current_line);

        
        
        if ($frame instanceof Inline) {
            return null;
        }

        $current_line->add_frame($frame);

        $this->increase_line_width($frame->get_margin_width());
        $this->maximize_line_height($frame->get_margin_height(), $frame);

        
        if ($this->_cl === 0 && $current_line->inline
            && $this->dangling_markers !== []
        ) {
            foreach ($this->dangling_markers as $marker) {
                $current_line->add_list_marker($marker);
                $this->maximize_line_height($marker->get_margin_height(), $marker);
            }

            $this->dangling_markers = [];
        }

        return $current_line;
    }

    
    public function remove_frames_from_line(Frame $frame): void
    {
        
        
        $actualFrame = $frame;
        while ($actualFrame !== null && $actualFrame instanceof Inline) {
            $actualFrame = $actualFrame->get_first_child();
        }

        if ($actualFrame === null) {
            return;
        }

        
        $frame = $actualFrame;
        $i = $this->_cl;
        $j = null;

        while ($i >= 0) {
            $line = $this->_line_boxes[$i];
            foreach ($line->get_frames() as $index => $f) {
                if ($frame === $f) {
                    $j = $index;
                    break 2;
                }
            }
            $i--;
        }

        if ($j === null) {
            return;
        }

        
        for ($k = $this->_cl; $k > $i; $k--) {
            unset($this->_line_boxes[$k]);
        }

        
        if ($j > 0) {
            $line->remove_frames($j);
        } else {
            unset($this->_line_boxes[$i]);
        }

        
        $this->_line_boxes = array_values($this->_line_boxes);
        $this->_cl = count($this->_line_boxes) - 1;
    }

    
    public function increase_line_width(float $w): void
    {
        $this->_line_boxes[$this->_cl]->w += $w;
    }

    
    public function maximize_line_height(float $val, Frame $frame): void
    {
        if ($val > $this->_line_boxes[$this->_cl]->h) {
            $this->_line_boxes[$this->_cl]->tallest_frame = $frame;
            $this->_line_boxes[$this->_cl]->h = $val;
        }
    }

    
    public function add_line(bool $br = false): void
    {
        $line = $this->_line_boxes[$this->_cl];

        $line->br = $br;
        $y = $line->y + $line->h;

        $new_line = new LineBox($this, $y);

        $this->_line_boxes[++$this->_cl] = $new_line;
    }

    
    public function add_dangling_marker(ListBullet $marker): void
    {
        $this->dangling_markers[] = $marker;
    }

    
    public function inherit_dangling_markers(self $block): void
    {
        if ($block->dangling_markers !== []) {
            $this->dangling_markers = $block->dangling_markers;
            $block->dangling_markers = [];
        }
    }
}
