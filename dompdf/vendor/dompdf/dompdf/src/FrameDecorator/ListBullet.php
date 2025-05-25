<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;


class ListBullet extends AbstractFrameDecorator
{
    
    public const BULLET_SIZE = 0.35;

    
    public const BULLET_OFFSET = 0.1;

    
    public const BULLET_THICKNESS = 0.04;

    
    public const MARKER_INDENT = 0.52;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    
    public function get_width(): float
    {
        $style = $this->_frame->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        return $style->font_size * self::BULLET_SIZE;
    }

    
    public function get_height(): float
    {
        $style = $this->_frame->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        return $style->font_size * self::BULLET_SIZE;
    }

    
    public function get_margin_width(): float
    {
        $style = $this->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        return $style->font_size * (self::BULLET_SIZE + self::MARKER_INDENT);
    }

    
    public function get_margin_height(): float
    {
        $style = $this->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        
        
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $this->_dompdf->getFontMetrics()->getFontHeight($font, $size);

        return ($style->line_height / ($size > 0 ? $size : 1)) * $fontHeight;
    }
}
