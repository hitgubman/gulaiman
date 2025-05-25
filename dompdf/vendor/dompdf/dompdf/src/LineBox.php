<?php

namespace Dompdf;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Block;
use Dompdf\FrameDecorator\ListBullet;
use Dompdf\FrameDecorator\Page;
use Dompdf\FrameReflower\Text as TextFrameReflower;
use Dompdf\Positioner\Inline as InlinePositioner;
use Iterator;


class LineBox
{
    
    protected $_block_frame;

    
    protected $_frames = [];

    
    protected $list_markers = [];

    
    public $wc = 0;

    
    public $y = 0.0;

    
    public $w = 0.0;

    
    public $h = 0.0;

    
    public $left = 0.0;

    
    public $right = 0.0;

    
    public $tallest_frame = null;

    
    public $floating_blocks = [];

    
    public $br = false;

    
    public $inline = false;

    
    public function __construct(Block $frame, float $y = 0.0)
    {
        $this->_block_frame = $frame;
        $this->_frames = [];
        $this->y = $y;

        $this->get_float_offsets();
    }

    
    public function get_floats_inside(Page $root): array
    {
        $floating_frames = $root->get_floating_frames();

        if (count($floating_frames) == 0) {
            return $floating_frames;
        }

        
        $p = $this->_block_frame;
        while ($p->get_style()->float === "none") {
            $parent = $p->get_parent();

            if (!$parent) {
                break;
            }

            $p = $parent;
        }

        if ($p == $root) {
            return $floating_frames;
        }

        $parent = $p;

        $childs = [];

        foreach ($floating_frames as $_floating) {
            $p = $_floating->get_parent();

            while (($p = $p->get_parent()) && $p !== $parent);

            if ($p) {
                $childs[] = $p;
            }
        }

        return $childs;
    }

    public function get_float_offsets(): void
    {
        static $anti_infinite_loop = 10000; 

        $reflower = $this->_block_frame->get_reflower();

        if (!$reflower) {
            return;
        }

        $cb_w = null;

        $block = $this->_block_frame;
        $root = $block->get_root();

        if (!$root) {
            return;
        }

        $style = $this->_block_frame->get_style();
        $floating_frames = $this->get_floats_inside($root);
        $inside_left_floating_width = 0;
        $inside_right_floating_width = 0;
        $outside_left_floating_width = 0;
        $outside_right_floating_width = 0;

        foreach ($floating_frames as $child_key => $floating_frame) {
            $floating_frame_parent = $floating_frame->get_parent();
            $id = $floating_frame->get_id();

            if (isset($this->floating_blocks[$id])) {
                continue;
            }

            $float = $floating_frame->get_style()->float;
            $floating_width = $floating_frame->get_margin_width();

            if (!$cb_w) {
                $cb_w = $floating_frame->get_containing_block("w");
            }

            $line_w = $this->get_width();

            if (!$floating_frame->_float_next_line && ($cb_w <= $line_w + $floating_width) && ($cb_w > $line_w)) {
                $floating_frame->_float_next_line = true;
                continue;
            }

            
            if ($anti_infinite_loop-- > 0 &&
                $floating_frame->get_position("y") + $floating_frame->get_margin_height() >= $this->y &&
                $block->get_position("x") + $block->get_margin_width() >= $floating_frame->get_position("x")
            ) {
                if ($float === "left") {
                    if ($floating_frame_parent === $this->_block_frame) {
                        $inside_left_floating_width += $floating_width;
                    } else {
                        $outside_left_floating_width += $floating_width;
                    }
                } elseif ($float === "right") {
                    if ($floating_frame_parent === $this->_block_frame) {
                        $inside_right_floating_width += $floating_width;
                    } else {
                        $outside_right_floating_width += $floating_width;
                    }
                }

                $this->floating_blocks[$id] = true;
            } 
            else {
                $root->remove_floating_frame($child_key);
            }
        }

        $this->left += $inside_left_floating_width;
        if ($outside_left_floating_width > 0 && $outside_left_floating_width > ((float)$style->length_in_pt($style->margin_left) + (float)$style->length_in_pt($style->padding_left))) {
            $this->left += $outside_left_floating_width - (float)$style->length_in_pt($style->margin_left) - (float)$style->length_in_pt($style->padding_left);
        }
        $this->right += $inside_right_floating_width;
        if ($outside_right_floating_width > 0 && $outside_right_floating_width > ((float)$style->length_in_pt($style->margin_left) + (float)$style->length_in_pt($style->padding_right))) {
            $this->right += $outside_right_floating_width - (float)$style->length_in_pt($style->margin_right) - (float)$style->length_in_pt($style->padding_right);
        }
    }

    
    public function get_width(): float
    {
        return $this->left + $this->w + $this->right;
    }

    
    public function get_block_frame(): Block
    {
        return $this->_block_frame;
    }

    
    public function &get_frames(): array
    {
        return $this->_frames;
    }

    
    public function is_empty(): bool
    {
        return $this->_frames === [];
    }

    
    public function add_frame(Frame $frame): void
    {
        $this->_frames[] = $frame;

        if ($frame->get_positioner() instanceof InlinePositioner) {
            $this->inline = true;
        }
    }

    
    public function remove_frames(int $index): void
    {
        $lastIndex = count($this->_frames) - 1;

        if ($index < 0 || $index > $lastIndex) {
            return;
        }

        for ($i = $lastIndex; $i >= $index; $i--) {
            $f = $this->_frames[$i];
            unset($this->_frames[$i]);
            $this->w -= $f->get_margin_width();
        }

        
        $this->_frames = array_values($this->_frames);

        
        $h = 0.0;
        $this->inline = false;

        foreach ($this->_frames as $f) {
            $h = max($h, $f->get_margin_height());

            if ($f->get_positioner() instanceof InlinePositioner) {
                $this->inline = true;
            }
        }

        $this->h = $h;
    }

    
    public function get_list_markers(): array
    {
        return $this->list_markers;
    }

    
    public function add_list_marker(ListBullet $marker): void
    {
        $this->list_markers[] = $marker;
    }

    
    public function frames_to_align(): Iterator
    {
        yield from $this->list_markers;

        foreach ($this->_frames as $frame) {
            if ($frame->get_positioner() instanceof InlinePositioner) {
                yield $frame;
            }
        }
    }

    
    public function trim_trailing_ws(): void
    {
        $lastIndex = count($this->_frames) - 1;

        if ($lastIndex < 0) {
            return;
        }

        $lastFrame = $this->_frames[$lastIndex];
        $reflower = $lastFrame->get_reflower();

        if ($reflower instanceof TextFrameReflower && !$lastFrame->is_pre()) {
            $reflower->trim_trailing_ws();
            $this->recalculate_width();
        }
    }

    
    public function recalculate_width(): float
    {
        $width = 0.0;

        foreach ($this->_frames as $frame) {
            $width += $frame->get_margin_width();
        }

        return $this->w = $width;
    }

    public function __toString(): string
    {
        $props = ["wc", "y", "w", "h", "left", "right", "br"];
        $s = "";
        foreach ($props as $prop) {
            $s .= "$prop: " . $this->$prop . "\n";
        }
        $s .= count($this->_frames) . " frames\n";

        return $s;
    }
}
