<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Exception;


class Text extends AbstractFrameDecorator
{
    
    protected $text_spacing;

    
    protected $mapped_font;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        if (!$frame->is_text_node()) {
            throw new Exception("Text_Decorator can only be applied to #text nodes.");
        }

        parent::__construct($frame, $dompdf);
        $this->text_spacing = 0.0;
    }

    function reset()
    {
        parent::reset();
        $this->text_spacing = 0.0;
        $this->mapped_font = null;
    }

    

    
    public function get_text_spacing(): float
    {
        return $this->text_spacing;
    }

    
    function get_text()
    {
        













        return $this->_frame->get_node()->data;
    }

    

    
    public function get_margin_height(): float
    {
        
        
        $style = $this->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $this->_dompdf->getFontMetrics()->getFontHeight($font, $size);

        return ($style->line_height / ($size > 0 ? $size : 1)) * $fontHeight;
    }

    public function get_padding_box(): array
    {
        $style = $this->_frame->get_style();
        $pb = $this->_frame->get_padding_box();
        $pb[3] = $pb["h"] = (float) $style->length_in_pt($style->height);
        return $pb;
    }

    
    public function set_text_spacing(float $spacing): void
    {
        $this->text_spacing = $spacing;
        $this->recalculate_width();
    }

    
    public function recalculate_width(): float
    {
        $fontMetrics = $this->_dompdf->getFontMetrics();
        $style = $this->get_style();
        $text = $this->get_text();
        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $this->text_spacing + $style->word_spacing;
        $letter_spacing = $style->letter_spacing;
        $text_width = $fontMetrics->getTextWidth($text, $font, $size, $word_spacing, $letter_spacing);

        $style->set_used("width", $text_width);
        return $text_width;
    }

    

    
    function split_text(int $offset, bool $split_parent = true): ?self
    {
        if ($offset === 0) {
            return null;
        }

        $split = $this->_frame->get_node()->splitText($offset);
        if ($split === false) {
            return null;
        }

        
        $deco = $this->copy($split);
        $style = $this->_frame->get_style();
        $split_style = $deco->get_style();

        if ($this->mapped_font !== null) {
            $split_style->set_used("font_family", $this->mapped_font);
            $deco->mapped_font = $this->mapped_font;
        }

        
        
        $style->margin_right = 0.0;
        $style->padding_right = 0.0;
        $style->border_right_width = 0.0;

        $split_style->margin_left = 0.0;
        $split_style->padding_left = 0.0;
        $split_style->border_left_width = 0.0;

        $p = $this->get_parent();
        $p->insert_child_after($deco, $this, false);

        if ($split_parent && $p instanceof Inline) {
            $p->split($deco);
        }

        return $deco;
    }

    
    function delete_text($offset, $count)
    {
        $this->_frame->get_node()->deleteData($offset, $count);
    }

    
    function set_text($text)
    {
        $this->_frame->get_node()->data = $text;
    }

    
    function apply_font_mapping(): void
    {
        if ($this->mapped_font !== null) {
            return;
        }

        $fontMetrics = $this->_dompdf->getFontMetrics();
        $style = $this->get_style();
        $families = $style->get_font_family_computed();
        $subtype = $fontMetrics->getType($style->font_weight . ' ' . $style->font_style);
        $charMapping = $fontMetrics->mapTextToFonts($this->get_text(), $families, $subtype, 1);

        if (isset($charMapping[0])) {
            if ($charMapping[0]["length"] !== 0) {
                $this->split_text($charMapping[0]["length"], false);
            }
            $mapped_font = $charMapping[0]["font"];
            if ($mapped_font !== null) {
                $style->set_used("font_family", $mapped_font);
                $this->mapped_font = $mapped_font;
            }
        }
    }
}
