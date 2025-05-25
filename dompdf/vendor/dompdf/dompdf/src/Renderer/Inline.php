<?php

namespace Dompdf\Renderer;

use Dompdf\Frame;


class Inline extends AbstractRenderer
{
    function render(Frame $frame)
    {
        
        $child = $frame->get_first_child();
        while ($child && !$child->is_in_flow()) {
            $child = $child->get_next_sibling();
        }

        if (!$child) {
            return; 
        }

        $style = $frame->get_style();
        $node = $frame->get_node();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        
        
        
        
        [$x, $y] = $child->get_position();
        [$w, $h] = $this->get_child_size($frame);

        [, , $cbw] = $frame->get_containing_block();
        $margin_left = $style->length_in_pt($style->margin_left, $cbw);
        $pt = $style->length_in_pt($style->padding_top, $cbw);
        $pb = $style->length_in_pt($style->padding_bottom, $cbw);

        
        
        
        
        
        $x += $margin_left;
        $y -= $style->border_top_width + $pt - ($h * 0.1);
        $h += $style->border_top_width + $pt + $style->border_bottom_width + $pb;

        $border_box = [$x, $y, $w, $h];
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        $this->addNamedDest($node);
        $this->addHyperlink($node, $border_box);

        $options = $this->_dompdf->getOptions();

        if ($options->getDebugLayout() && $options->getDebugLayoutInline()) {
            $this->debugLayout($border_box, "blue");

            if ($options->getDebugLayoutPaddingBox()) {
                $padding_box = [
                    $x + $style->border_left_width,
                    $y + $style->border_top_width,
                    $w - $style->border_left_width - $style->border_right_width,
                    $h - $style->border_top_width - $style->border_bottom_width
                ];
                $this->debugLayout($padding_box, "blue", [0.5, 0.5]);
            }
        }
    }

    protected function get_child_size(Frame $frame): array
    {
        $w = 0.0;
        $h = 0.0;

        foreach ($frame->get_children() as $child) {
            if (!$child->is_in_flow()) {
                continue;
            }

            
            if ($child->get_node()->nodeValue === " "
                && $child->get_prev_sibling() && !$child->get_next_sibling()
            ) {
                break;
            }

            $style = $child->get_style();
            $auto_width = $style->width === "auto";
            $auto_height = $style->height === "auto";
            [, , $child_w, $child_h] = $child->get_border_box();

            if ($auto_width || $auto_height) {
                [$child_w2, $child_h2] = $this->get_child_size($child);

                if ($auto_width) {
                    $child_w = $child_w2;
                }
    
                if ($auto_height) {
                    $child_h = $child_h2;
                }
            }

            $w += $child_w;
            $h = max($h, $child_h);
        }

        return [$w, $h];
    }
}
