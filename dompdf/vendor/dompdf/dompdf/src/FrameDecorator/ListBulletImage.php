<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Image\Cache;


class ListBulletImage extends ListBullet
{

    
    protected $_img;

    
    protected $_width;

    
    protected $_height;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        $style = $frame->get_style();
        $url = $style->list_style_image;
        $frame->get_node()->setAttribute("src", $url);
        $this->_img = new Image($frame, $dompdf);
        parent::__construct($this->_img, $dompdf);

        $url = $this->_img->get_image_url();

        if (Cache::is_broken($url)) {
            $this->_width = parent::get_width();
            $this->_height = parent::get_height();
        } else {
            
            [$width, $height] = $this->_img->get_intrinsic_dimensions();
            $this->_width = $this->_img->resample($width);
            $this->_height = $this->_img->resample($height);
        }
    }

    public function get_width(): float
    {
        return $this->_width;
    }

    public function get_height(): float
    {
        return $this->_height;
    }

    public function get_margin_width(): float
    {
        $style = $this->get_style();
        return $this->_width + $style->font_size * self::MARKER_INDENT;
    }

    public function get_margin_height(): float
    {
        $fontMetrics = $this->_dompdf->getFontMetrics();
        $style = $this->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $fontMetrics->getFontHeight($font, $size);
        $baseline = $fontMetrics->getFontBaseline($font, $size);

        
        
        $f = $style->line_height / ($size > 0 ? $size : 1);

        
        
        return $f * ($fontHeight - $baseline) + $this->_height;
    }

    
    function get_image_url()
    {
        return $this->_img->get_image_url();
    }
}
