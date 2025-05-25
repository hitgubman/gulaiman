<?php

namespace Dompdf;

use Dompdf\Renderer\AbstractRenderer;
use Dompdf\Renderer\Block;
use Dompdf\Renderer\Image;
use Dompdf\Renderer\Inline;
use Dompdf\Renderer\ListBullet;
use Dompdf\Renderer\TableCell;
use Dompdf\Renderer\TableRow;
use Dompdf\Renderer\TableRowGroup;
use Dompdf\Renderer\Text;


class Renderer extends AbstractRenderer
{

    
    protected $_renderers;

    
    private $_callbacks;

    
    function new_page()
    {
        $this->_canvas->new_page();
    }

    
    public function render(Frame $frame)
    {
        global $_dompdf_debug;

        $this->_check_callbacks("begin_frame", $frame);

        if ($_dompdf_debug) {
            echo $frame;
            flush();
        }

        $style = $frame->get_style();

        if (in_array($style->visibility, ["hidden", "collapse"], true)) {
            return;
        }

        $display = $style->display;
        $transformList = $style->transform;
        $hasTransform = $transformList !== [];

        
        if ($hasTransform) {
            $this->_canvas->save();

            [$x, $y] = $frame->get_padding_box();
            [$originX, $originY] = $style->transform_origin;
            $w = (float) $style->length_in_pt($style->width);
            $h = (float) $style->length_in_pt($style->height);

            foreach ($transformList as $transform) {
                [$function, $values] = $transform;

                if ($function === "matrix") {
                    $function = "transform";
                } elseif ($function === "translate") {
                    $values[0] = $style->length_in_pt($values[0], $w);
                    $values[1] = $style->length_in_pt($values[1], $h);
                }

                $values[] = $x + $style->length_in_pt($originX, $w);
                $values[] = $y + $style->length_in_pt($originY, $h);

                call_user_func_array([$this->_canvas, $function], $values);
            }
        }

        switch ($display) {

            case "block":
            case "list-item":
            case "inline-block":
            case "table":
            case "inline-table":
                $this->_render_frame("block", $frame);
                break;

            case "inline":
                if ($frame->is_text_node()) {
                    $this->_render_frame("text", $frame);
                } else {
                    $this->_render_frame("inline", $frame);
                }
                break;

            case "table-cell":
                $this->_render_frame("table-cell", $frame);
                break;

            case "table-row":
                $this->_render_frame("table-row", $frame);
                break;

            case "table-row-group":
            case "table-header-group":
            case "table-footer-group":
                $this->_render_frame("table-row-group", $frame);
                break;

            case "-dompdf-list-bullet":
                $this->_render_frame("list-bullet", $frame);
                break;

            case "-dompdf-image":
                $this->_render_frame("image", $frame);
                break;

            case "none":
                $node = $frame->get_node();

                if ($node->nodeName === "script") {
                    if ($node->getAttribute("type") === "text/php" ||
                        $node->getAttribute("language") === "php"
                    ) {
                        
                        $this->_render_frame("php", $frame);
                    } elseif ($node->getAttribute("type") === "text/javascript" ||
                        $node->getAttribute("language") === "javascript"
                    ) {
                        
                        $this->_render_frame("javascript", $frame);
                    }
                }

                
                return;

            default:
                break;

        }

        
        if ($style->overflow === "hidden") {
            $padding_box = $frame->get_padding_box();
            [$x, $y, $w, $h] = $padding_box;
            $style = $frame->get_style();

            if ($style->has_border_radius()) {
                $border_box = $frame->get_border_box();
                [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box, $padding_box);
                $this->_canvas->clipping_roundrectangle($x, $y, $w, $h, $tl, $tr, $br, $bl);
            } else {
                $this->_canvas->clipping_rectangle($x, $y, $w, $h);
            }
        }

        $stack = [];

        foreach ($frame->get_children() as $child) {
            
            
            
            
            $child_style = $child->get_style();
            $child_z_index = $child_style->z_index;
            $z_index = 0;

            if ($child_z_index !== "auto") {
                $z_index = $child_z_index + 1;
            } elseif ($child_style->float !== "none" || $child->is_positioned()) {
                $z_index = 1;
            }

            $stack[$z_index][] = $child;
        }

        ksort($stack);

        foreach ($stack as $by_index) {
            foreach ($by_index as $child) {
                $this->render($child);
            }
        }

        
        if ($style->overflow === "hidden") {
            $this->_canvas->clipping_end();
        }

        if ($hasTransform) {
            $this->_canvas->restore();
        }

        
        $this->_check_callbacks("end_frame", $frame);
    }

    
    protected function _check_callbacks(string $event, Frame $frame): void
    {
        if (!isset($this->_callbacks)) {
            $this->_callbacks = $this->_dompdf->getCallbacks();
        }

        if (isset($this->_callbacks[$event])) {
            $fs = $this->_callbacks[$event];
            $canvas = $this->_canvas;
            $fontMetrics = $this->_dompdf->getFontMetrics();

            foreach ($fs as $f) {
                $f($frame, $canvas, $fontMetrics);
            }
        }
    }

    
    protected function _render_frame($type, $frame)
    {

        if (!isset($this->_renderers[$type])) {

            switch ($type) {
                case "block":
                    $this->_renderers[$type] = new Block($this->_dompdf);
                    break;

                case "inline":
                    $this->_renderers[$type] = new Inline($this->_dompdf);
                    break;

                case "text":
                    $this->_renderers[$type] = new Text($this->_dompdf);
                    break;

                case "image":
                    $this->_renderers[$type] = new Image($this->_dompdf);
                    break;

                case "table-cell":
                    $this->_renderers[$type] = new TableCell($this->_dompdf);
                    break;

                case "table-row":
                    $this->_renderers[$type] = new TableRow($this->_dompdf);
                    break;

                case "table-row-group":
                    $this->_renderers[$type] = new TableRowGroup($this->_dompdf);
                    break;

                case "list-bullet":
                    $this->_renderers[$type] = new ListBullet($this->_dompdf);
                    break;

                case "php":
                    $this->_renderers[$type] = new PhpEvaluator($this->_canvas);
                    break;

                case "javascript":
                    $this->_renderers[$type] = new JavascriptEmbedder($this->_dompdf);
                    break;

            }
        }

        $this->_renderers[$type]->render($frame);
    }
}
