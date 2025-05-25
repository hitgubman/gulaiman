<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;


class TableCell extends BlockFrameDecorator
{
    
    protected $content_height;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $this->content_height = 0.0;
    }

    function reset()
    {
        parent::reset();
        $this->content_height = 0.0;
    }

    
    public function get_content_height(): float
    {
        return $this->content_height;
    }

    
    public function set_content_height(float $height): void
    {
        $this->content_height = $height;
    }

    
    public function set_cell_height(float $height): void
    {
        $style = $this->get_style();
        $v_space = (float)$style->length_in_pt(
            [
                $style->margin_top,
                $style->padding_top,
                $style->border_top_width,
                $style->border_bottom_width,
                $style->padding_bottom,
                $style->margin_bottom
            ],
            (float)$style->length_in_pt($style->height)
        );

        $new_height = $height - $v_space;
        $style->set_used("height", $new_height);

        if ($new_height > $this->content_height) {
            $y_offset = 0;

            
            switch ($style->vertical_align) {
                default:
                case "baseline":
                    

                case "top":
                    
                    return;

                case "middle":
                    $y_offset = ($new_height - $this->content_height) / 2;
                    break;

                case "bottom":
                    $y_offset = $new_height - $this->content_height;
                    break;
            }

            if ($y_offset) {
                
                foreach ($this->get_line_boxes() as $line) {
                    foreach ($line->get_frames() as $frame) {
                        $frame->move(0, $y_offset);
                    }
                }
            }
        }
    }
}
