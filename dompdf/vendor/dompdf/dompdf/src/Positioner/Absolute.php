<?php

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameReflower\Block;


class Absolute extends AbstractPositioner
{

    
    function position(AbstractFrameDecorator $frame): void
    {
        if ($frame->get_reflower() instanceof Block) {
            $style = $frame->get_style();
            [$cbx, $cby, $cbw, $cbh] = $frame->get_containing_block();

            
            
            $left = (float) $style->length_in_pt($style->left, $cbw);
            $top = (float) $style->length_in_pt($style->top, $cbh);

            $frame->set_position($cbx + $left, $cby + $top);
        } else {
            
            
            
            $style = $frame->get_style();
            $block_parent = $frame->find_block_parent();
            $current_line = $block_parent->get_current_line_box();
    
            list($x, $y, $w, $h) = $frame->get_containing_block();
            $inflow_x = $block_parent->get_content_box()["x"] + $current_line->left + $current_line->w;
            $inflow_y = $current_line->y;

            $top = $style->length_in_pt($style->top, $h);
            $right = $style->length_in_pt($style->right, $w);
            $bottom = $style->length_in_pt($style->bottom, $h);
            $left = $style->length_in_pt($style->left, $w);

            list($width, $height) = [$frame->get_margin_width(), $frame->get_margin_height()];

            $orig_width = $style->get_specified("width");
            $orig_height = $style->get_specified("height");

            

            if ($left === "auto") {
                if ($right === "auto") {
                    
                    $x = $inflow_x;
                } else {
                    if ($orig_width === "auto") {
                        
                        $x += $w - $width - $right;
                    } else {
                        
                        $x += $w - $width - $right;
                    }
                }
            } else {
                if ($right === "auto") {
                    
                    $x += (float)$left;
                } else {
                    if ($orig_width === "auto") {
                        
                        $x += (float)$left;
                    } else {
                        
                        $x += (float)$left;
                    }
                }
            }

            
            if ($top === "auto") {
                if ($bottom === "auto") {
                    
                    $y = $inflow_y;
                } else {
                    if ($orig_height === "auto") {
                        
                        $y += (float)$h - $height - (float)$bottom;
                    } else {
                        
                        $y += (float)$h - $height - (float)$bottom;
                    }
                }
            } else {
                if ($bottom === "auto") {
                    
                    $y += (float)$top;
                } else {
                    if ($orig_height === "auto") {
                        
                        $y += (float)$top;
                    } else {
                        
                        $y += (float)$top;
                    }
                }
            }

            $frame->set_position($x, $y);
        }
    }
}
