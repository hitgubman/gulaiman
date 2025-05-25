<?php

namespace Dompdf\FrameReflower;

use Dompdf\Helpers;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Image as ImageFrameDecorator;


class Image extends AbstractFrameReflower
{

    
    function __construct(ImageFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    
    function reflow(?BlockFrameDecorator $block = null)
    {
        $this->determine_absolute_containing_block();

        
        $this->_set_content();

        
        
        

        
        
        

        $this->resolve_dimensions();
        $this->resolve_margins();

        $frame = $this->_frame;
        $frame->position();

        if ($block && $frame->is_in_flow()) {
            $block->add_frame_to_line($frame);
        }
    }

    public function get_min_max_content_width(): array
    {
        
        
        
        
        $style = $this->_frame->get_style();

        [$width] = $this->calculate_size(null, null);
        $min_width = $this->resolve_min_width(null);
        $percent_width = Helpers::is_percent($style->width)
            || Helpers::is_percent($style->max_width)
            || ($style->width === "auto"
                && (Helpers::is_percent($style->height) || Helpers::is_percent($style->max_height)));

        
        
        
        $min = $percent_width ? $min_width : $width;
        $max = $width;

        return [$min, $max];
    }

    
    protected function calculate_size(?float $cbw, ?float $cbh): array
    {
        
        $frame = $this->_frame;
        $style = $frame->get_style();

        $computed_width = $style->width;
        $computed_height = $style->height;

        $width = $cbw === null && Helpers::is_percent($computed_width)
            ? "auto"
            : $style->length_in_pt($computed_width, $cbw ?? 0);
        $height = $cbh === null && Helpers::is_percent($computed_height)
            ? "auto"
            : $style->length_in_pt($computed_height, $cbh ?? 0);
        $min_width = $this->resolve_min_width($cbw);
        $max_width = $this->resolve_max_width($cbw);
        $min_height = $this->resolve_min_height($cbh);
        $max_height = $this->resolve_max_height($cbh);

        if ($width === "auto" && $height === "auto") {
            
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            $w = $frame->resample($img_width);
            $h = $frame->resample($img_height);

            
            
            $max_width = max($min_width, $max_width);
            $max_height = max($min_height, $max_height);

            if (($w > $max_width && $h <= $max_height)
                || ($w > $max_width && $h > $max_height && $max_width / $w <= $max_height / $h)
                || ($w < $min_width && $h > $min_height)
                || ($w < $min_width && $h < $min_height && $min_width / $w > $min_height / $h)
            ) {
                $width = Helpers::clamp($w, $min_width, $max_width);
                $height = $width * ($img_height / $img_width);
                $height = Helpers::clamp($height, $min_height, $max_height);
            } else {
                $height = Helpers::clamp($h, $min_height, $max_height);
                $width = $height * ($img_width / $img_height);
                $width = Helpers::clamp($width, $min_width, $max_width);
            }
        } elseif ($height === "auto") {
            
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            $width = Helpers::clamp((float) $width, $min_width, $max_width);
            $height = $width * ($img_height / $img_width);
            $height = Helpers::clamp($height, $min_height, $max_height);
        } elseif ($width === "auto") {
            
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            $height = Helpers::clamp((float) $height, $min_height, $max_height);
            $width = $height * ($img_width / $img_height);
            $width = Helpers::clamp($width, $min_width, $max_width);
        } else {
            
            $width = Helpers::clamp((float) $width, $min_width, $max_width);
            $height = Helpers::clamp((float) $height, $min_height, $max_height);
        }

        return [$width, $height];
    }

    protected function resolve_dimensions(): void
    {
        
        $frame = $this->_frame;
        $style = $frame->get_style();

        $debug_png = $this->get_dompdf()->getOptions()->getDebugPng();

        if ($debug_png) {
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            print "resolve_dimensions() " .
                $frame->get_style()->width . " " .
                $frame->get_style()->height . ";" .
                $frame->get_parent()->get_style()->width . " " .
                $frame->get_parent()->get_style()->height . ";" .
                $frame->get_parent()->get_parent()->get_style()->width . " " .
                $frame->get_parent()->get_parent()->get_style()->height . ";" .
                $img_width . " " .
                $img_height . "|";
        }

        [, , $cbw, $cbh] = $frame->get_containing_block();
        [$width, $height] = $this->calculate_size($cbw, $cbh);

        if ($debug_png) {
            print $width . " " . $height . ";";
        }

        $style->set_used("width", $width);
        $style->set_used("height", $height);
    }

    protected function resolve_margins(): void
    {
        
        
        
        $style = $this->_frame->get_style();

        if ($style->margin_left === "auto") {
            $style->set_used("margin_left", 0.0);
        }
        if ($style->margin_right === "auto") {
            $style->set_used("margin_right", 0.0);
        }
        if ($style->margin_top === "auto") {
            $style->set_used("margin_top", 0.0);
        }
        if ($style->margin_bottom === "auto") {
            $style->set_used("margin_bottom", 0.0);
        }
    }
}
