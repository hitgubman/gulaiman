<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Helpers;
use Dompdf\Image\Cache;


class Image extends AbstractFrameDecorator
{

    
    protected $_image_url;

    
    protected $_image_msg;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);

        $node = $frame->get_node();
        $url = $node->getAttribute("src");

        $debug_png = $dompdf->getOptions()->getDebugPng();
        if ($debug_png) {
            print '[__construct ' . $url . ']';
        }

        list($this->_image_url, , $this->_image_msg) = Cache::resolve_url(
            $url,
            $dompdf->getProtocol(),
            $dompdf->getBaseHost(),
            $dompdf->getBasePath(),
            $dompdf->getOptions()
        );

        if (Cache::is_broken($this->_image_url) && ($alt = $node->getAttribute("alt")) !== "") {
            $fontMetrics = $dompdf->getFontMetrics();
            $style = $frame->get_style();
            $font = $style->font_family;
            $size = $style->font_size;
            $word_spacing = $style->word_spacing;
            $letter_spacing = $style->letter_spacing;

            $style->width = $fontMetrics->getTextWidth($alt, $font, $size, $word_spacing, $letter_spacing);
            $style->height = $fontMetrics->getFontHeight($font, $size);
        }
    }

    
    public function get_intrinsic_dimensions(): array
    {
        [$width, $height] = Helpers::dompdf_getimagesize($this->_image_url, $this->_dompdf->getHttpContext());

        return [$width, $height];
    }

    
    public function resample($length): float
    {
        $dpi = $this->_dompdf->getOptions()->getDpi();
        return ($length * 72) / $dpi;
    }

    
    function get_image_url()
    {
        return $this->_image_url;
    }

    
    function get_image_msg()
    {
        return $this->_image_msg;
    }

}
