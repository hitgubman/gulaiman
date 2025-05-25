<?php

namespace Dompdf\FrameReflower;

use Dompdf\Css\Content\Attr;
use Dompdf\Css\Content\CloseQuote;
use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\Counters;
use Dompdf\Css\Content\NoCloseQuote;
use Dompdf\Css\Content\NoOpenQuote;
use Dompdf\Css\Content\OpenQuote;
use Dompdf\Css\Content\StringPart;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Frame\Factory;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Block;


abstract class AbstractFrameReflower
{

    
    protected $_frame;

    
    protected $_min_max_child_cache;

    
    protected $_min_max_cache;

    
    function __construct(AbstractFrameDecorator $frame)
    {
        $this->_frame = $frame;
        $this->_min_max_child_cache = null;
        $this->_min_max_cache = null;
    }

    
    function get_dompdf()
    {
        return $this->_frame->get_dompdf();
    }

    public function reset(): void
    {
        $this->_min_max_child_cache = null;
        $this->_min_max_cache = null;
    }

    
    protected function determine_absolute_containing_block(): void
    {
        $frame = $this->_frame;
        $style = $frame->get_style();

        switch ($style->position) {
            case "absolute":
                $parent = $frame->find_positioned_parent();
                if ($parent !== $frame->get_root()) {
                    $parent_style = $parent->get_style();
                    $parent_padding_box = $parent->get_padding_box();
                    
                    
                    
                    
                    if ($parent_style->height === "auto") {
                        $parent_containing_block = $parent->get_containing_block();
                        $containing_block_height = $parent_containing_block["h"] -
                            (float)$parent_style->length_in_pt([
                                $parent_style->margin_top,
                                $parent_style->margin_bottom,
                                $parent_style->border_top_width,
                                $parent_style->border_bottom_width
                            ], $parent_containing_block["w"]);
                    } else {
                        $containing_block_height = $parent_padding_box["h"];
                    }
                    $frame->set_containing_block($parent_padding_box["x"], $parent_padding_box["y"], $parent_padding_box["w"], $containing_block_height);
                    break;
                }
            case "fixed":
                $root = $frame->get_root();
                $parent = $frame->get_parent();
                do {
                    $parents_parent = $parent->get_parent();
                    if ($parents_parent == $root) {
                        break;
                    }
                    $parent = $parents_parent;
                } while ($parent);
                $initial_cb = $parent->get_containing_block();
                $frame->set_containing_block($initial_cb["x"], $initial_cb["y"], $initial_cb["w"], $initial_cb["h"]);
                break;
            default:
                
                break;
        }
    }

    
    protected function _collapse_margins(): void
    {
        $frame = $this->_frame;

        
        if (!$frame->is_in_flow() || $frame->is_inline_level()
            || $frame->get_root() === $frame || $frame->get_parent() === $frame->get_root()
        ) {
            return;
        }

        $cb = $frame->get_containing_block();
        $style = $frame->get_style();

        $t = $style->length_in_pt($style->margin_top, $cb["w"]);
        $b = $style->length_in_pt($style->margin_bottom, $cb["w"]);

        
        if ($t === "auto") {
            $style->set_used("margin_top", 0.0);
            $t = 0.0;
        }

        if ($b === "auto") {
            $style->set_used("margin_bottom", 0.0);
            $b = 0.0;
        }

        
        $n = $frame->get_next_sibling();
        if ( $n && !($n->is_block_level() && $n->is_in_flow()) ) {
            while ($n = $n->get_next_sibling()) {
                if ($n->is_block_level() && $n->is_in_flow()) {
                    break;
                }

                if (!$n->get_first_child()) {
                    $n = null;
                    break;
                }
            }
        }

        if ($n) {
            $n_style = $n->get_style();
            $n_t = (float)$n_style->length_in_pt($n_style->margin_top, $cb["w"]);

            $b = $this->get_collapsed_margin_length($b, $n_t);
            $style->set_used("margin_bottom", $b);
            $n_style->set_used("margin_top", 0.0);
        }

        
        if ($style->border_top_width == 0 && $style->length_in_pt($style->padding_top) == 0) {
            $f = $this->_frame->get_first_child();
            if ( $f && !($f->is_block_level() && $f->is_in_flow()) ) {
                while ($f = $f->get_next_sibling()) {
                    if ($f->is_block_level() && $f->is_in_flow()) {
                        break;
                    }

                    if (!$f->get_first_child()) {
                        $f = null;
                        break;
                    }
                }
            }

            
            if ($f) {
                $f_style = $f->get_style();
                $f_t = (float)$f_style->length_in_pt($f_style->margin_top, $cb["w"]);

                $t = $this->get_collapsed_margin_length($t, $f_t);
                $style->set_used("margin_top", $t);
                $f_style->set_used("margin_top", 0.0);
            }
        }

        
        if ($style->border_bottom_width == 0 && $style->length_in_pt($style->padding_bottom) == 0) {
            $l = $this->_frame->get_last_child();
            if ( $l && !($l->is_block_level() && $l->is_in_flow()) ) {
                while ($l = $l->get_prev_sibling()) {
                    if ($l->is_block_level() && $l->is_in_flow()) {
                        break;
                    }

                    if (!$l->get_last_child()) {
                        $l = null;
                        break;
                    }
                }
            }

            
            if ($l) {
                $l_style = $l->get_style();
                $l_b = (float)$l_style->length_in_pt($l_style->margin_bottom, $cb["w"]);

                $b = $this->get_collapsed_margin_length($b, $l_b);
                $style->set_used("margin_bottom", $b);
                $l_style->set_used("margin_bottom", 0.0);
            }
        }
    }

    
    private function get_collapsed_margin_length(float $l1, float $l2): float
    {
        if ($l1 < 0 && $l2 < 0) {
            return min($l1, $l2); 
        }
        
        if ($l1 < 0 || $l2 < 0) {
            return $l1 + $l2; 
        }
        
        return max($l1, $l2);
    }

    
    protected function position_relative(AbstractFrameDecorator $frame): void
    {
        $style = $frame->get_style();

        if ($style->position === "relative") {
            $cb = $frame->get_containing_block();
            $top = $style->length_in_pt($style->top, $cb["h"]);
            $right = $style->length_in_pt($style->right, $cb["w"]);
            $bottom = $style->length_in_pt($style->bottom, $cb["h"]);
            $left = $style->length_in_pt($style->left, $cb["w"]);

            
            
            if ($left === "auto" && $right === "auto") {
                $left = 0;
            } elseif ($left === "auto") {
                $left = -$right;
            }

            if ($top === "auto" && $bottom === "auto") {
                $top = 0;
            } elseif ($top === "auto") {
                $top = -$bottom;
            }

            $frame->move($left, $top);
        }
    }

    
    abstract function reflow(?Block $block = null);

    
    protected function resolve_min_width(?float $cbw): float
    {
        $style = $this->_frame->get_style();
        $min_width = $style->min_width;

        return $min_width !== "auto"
            ? $style->length_in_pt($min_width, $cbw ?? 0)
            : 0.0;
    }

    
    protected function resolve_max_width(?float $cbw): float
    {
        $style = $this->_frame->get_style();
        $max_width = $style->max_width;

        return $max_width !== "none"
            ? $style->length_in_pt($max_width, $cbw ?? INF)
            : INF;
    }

    
    protected function resolve_min_height(?float $cbh): float
    {
        $style = $this->_frame->get_style();
        $min_height = $style->min_height;

        return $min_height !== "auto"
            ? $style->length_in_pt($min_height, $cbh ?? 0)
            : 0.0;
    }

    
    protected function resolve_max_height(?float $cbh): float
    {
        $style = $this->_frame->get_style();
        $max_height = $style->max_height;

        return $max_height !== "none"
            ? $style->length_in_pt($style->max_height, $cbh ?? INF)
            : INF;
    }

    
    public function get_min_max_child_width(): array
    {
        if (!is_null($this->_min_max_child_cache)) {
            return $this->_min_max_child_cache;
        }

        $low = [];
        $high = [];

        for ($iter = $this->_frame->get_children(); $iter->valid(); $iter->next()) {
            $inline_min = 0;
            $inline_max = 0;

            
            while ($iter->valid() && ($iter->current()->is_inline_level() || $iter->current()->get_style()->display === "-dompdf-image")) {
                
                $child = $iter->current();
                $child->get_reflower()->_set_content();
                $minmax = $child->get_min_max_width();

                if (in_array($child->get_style()->white_space, ["pre", "nowrap"], true)) {
                    $inline_min += $minmax["min"];
                } else {
                    $low[] = $minmax["min"];
                }

                $inline_max += $minmax["max"];
                $iter->next();
            }

            if ($inline_min > 0) {
                $low[] = $inline_min;
            }
            if ($inline_max > 0) {
                $high[] = $inline_max;
            }

            
            if ($iter->valid() && !$iter->current()->is_absolute()) {
                
                $child = $iter->current();
                $child->get_reflower()->_set_content();
                list($low[], $high[]) = $child->get_min_max_width();
            }
        }

        $min = count($low) ? max($low) : 0;
        $max = count($high) ? max($high) : 0;

        return $this->_min_max_child_cache = [$min, $max];
    }

    
    public function get_min_max_content_width(): array
    {
        return $this->get_min_max_child_width();
    }

    
    public function get_min_max_width(): array
    {
        if (!is_null($this->_min_max_cache)) {
            return $this->_min_max_cache;
        }

        $style = $this->_frame->get_style();
        [$min, $max] = $this->get_min_max_content_width();

        
        $dims = [
            $style->padding_left,
            $style->padding_right,
            $style->border_left_width,
            $style->border_right_width,
            $style->margin_left,
            $style->margin_right
        ];

        
        $delta = (float) $style->length_in_pt($dims, 0);
        $min += $delta;
        $max += $delta;

        return $this->_min_max_cache = [$min, $max, "min" => $min, "max" => $max];
    }

    
    protected function resolve_content(): ?string
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $content = $style->content;

        if ($content === "normal" || $content === "none") {
            return null;
        }

        $quotes = $style->quotes;
        $text = "";

        foreach ($content as $val) {
            if ($val instanceof StringPart) {
                $text .= $val->string;
            }

            elseif ($val instanceof OpenQuote) {
                
                if ($quotes !== "none" && isset($quotes[0][0])) {
                    $text .= $quotes[0][0];
                }
            }

            elseif ($val instanceof CloseQuote) {
                
                if ($quotes !== "none" && isset($quotes[0][1])) {
                    $text .= $quotes[0][1];
                }
            }
            
            elseif ($val instanceof NoOpenQuote) {
                
            }

            elseif ($val instanceof NoCloseQuote) {
                
            }

            elseif ($val instanceof Attr) {
                $text .= $frame->get_parent()->get_node()->getAttribute($val->attribute);
            }

            elseif ($val instanceof Counter) {
                $p = $frame->lookup_counter_frame($val->name, true);
                $text .= $p->counter_value($val->name, $val->style);
            }

            elseif ($val instanceof Counters) {
                $p = $frame->lookup_counter_frame($val->name, true);
                $tmp = [];
                while ($p) {
                    array_unshift($tmp, $p->counter_value($val->name, $val->style));
                    $p = $p->lookup_counter_frame($val->name);
                }
                $text .= implode($val->string, $tmp);
            }
        }

        return $text;
    }

    
    protected function _set_content(): void
    {
        $frame = $this->_frame;

        if ($frame->content_set) {
            return;
        }

        $style = $frame->get_style();

        if (($reset = $style->counter_reset) !== "none") {
            $frame->reset_counters($reset);
        }

        if (($increment = $style->counter_increment) !== "none") {
            $frame->increment_counters($increment);
        }

        if ($frame->get_node()->nodeName === "dompdf_generated") {
            $content = $this->resolve_content();

            if ($content !== null) {
                $node = $frame->get_node()->ownerDocument->createTextNode($content);

                $new_style = $style->get_stylesheet()->create_style();
                $new_style->inherit($style);

                $new_frame = new Frame($node);
                $new_frame->set_style($new_style);

                Factory::decorate_frame($new_frame, $frame->get_dompdf(), $frame->get_root());
                $frame->append_child($new_frame);
            }
        }

        $frame->content_set = true;
    }
}
