<?php

namespace Dompdf\FrameDecorator;

use DOMElement;
use DOMNode;
use Dompdf\Helpers;
use Dompdf\Dompdf;
use Dompdf\Exception;
use Dompdf\Frame;
use Dompdf\Frame\Factory;
use Dompdf\Frame\FrameListIterator;
use Dompdf\Frame\FrameTreeIterator;
use Dompdf\FrameReflower\AbstractFrameReflower;
use Dompdf\Css\Style;
use Dompdf\Positioner\AbstractPositioner;


abstract class AbstractFrameDecorator extends Frame
{
    const DEFAULT_COUNTER = "-dompdf-default-counter";

    
    public $_counters = [];

    
    protected $_root;

    
    protected $_frame;

    
    protected $_positioner;

    
    protected $_reflower;

    
    protected $_dompdf;

    
    private $_block_parent;

    
    private $_positioned_parent;

    
    private $_cached_parent;

    
    public $content_set = false;

    
    public $is_split = false;

    
    public $is_split_off = false;

    
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        $this->_frame = $frame;
        $this->_root = null;
        $this->_dompdf = $dompdf;
        $frame->set_decorator($this);
    }

    
    function dispose($recursive = false)
    {
        if ($recursive) {
            while ($child = $this->get_first_child()) {
                $child->dispose(true);
            }
        }

        $this->_root = null;
        unset($this->_root);

        $this->_frame->dispose(true);
        $this->_frame = null;
        unset($this->_frame);

        $this->_positioner = null;
        unset($this->_positioner);

        $this->_reflower = null;
        unset($this->_reflower);
    }

    
    function copy(DOMNode $node)
    {
        $frame = new Frame($node);
        $style = clone $this->_frame->get_style();

        $style->reset();
        $frame->set_style($style);

        if ($node instanceof DOMElement && $node->hasAttribute("id")) {
            $node->setAttribute("data-dompdf-original-id", $node->getAttribute("id"));
            $node->removeAttribute("id");
        }

        return Factory::decorate_frame($frame, $this->_dompdf, $this->_root);
    }

    
    function deep_copy()
    {
        $node = $this->_frame->get_node()->cloneNode();
        $frame = new Frame($node);
        $style = clone $this->_frame->get_style();

        $style->reset();
        $frame->set_style($style);

        if ($node instanceof DOMElement && $node->hasAttribute("id")) {
            $node->setAttribute("data-dompdf-original-id", $node->getAttribute("id"));
            $node->removeAttribute("id");
        }

        $deco = Factory::decorate_frame($frame, $this->_dompdf, $this->_root);

        foreach ($this->get_children() as $child) {
            $deco->append_child($child->deep_copy());
        }

        return $deco;
    }

    
    public function create_anonymous_child(string $node_name, string $display): AbstractFrameDecorator
    {
        $style = $this->get_style();
        $child_style = $style->get_stylesheet()->create_style();
        $child_style->set_prop("display", $display);
        $child_style->inherit($style);

        $node = $this->get_node()->ownerDocument->createElement($node_name);
        $frame = new Frame($node);
        $frame->set_style($child_style);

        return Factory::decorate_frame($frame, $this->_dompdf, $this->_root);
    }

    function reset()
    {
        $this->_frame->reset();
        $this->_reflower->reset();
        $this->reset_generated_content();
        $this->revert_counter_increment();

        $this->content_set = false;
        $this->_counters = [];

        
        $this->_cached_parent = null;
        $this->_block_parent = null;
        $this->_positioned_parent = null;

        
        foreach ($this->get_children() as $child) {
            $child->reset();
        }
    }

    
    protected function reset_generated_content(): void
    {
        if ($this->content_set
            && $this->get_node()->nodeName === "dompdf_generated"
        ) {
            $content = $this->get_style()->content;

            if ($content !== "normal" && $content !== "none") {
                foreach ($this->get_children() as $child) {
                    $this->remove_child($child);
                }
            }
        }
    }

    
    protected function revert_counter_increment(): void
    {
        if ($this->content_set
            && $this->get_node()->nodeName !== "body"
            && ($decrement = $this->get_style()->counter_increment) !== "none"
        ) {
            $this->decrement_counters($decrement);
        }
    }

    

    function get_id()
    {
        return $this->_frame->get_id();
    }

    
    function get_frame()
    {
        return $this->_frame;
    }

    function get_node()
    {
        return $this->_frame->get_node();
    }

    function get_style()
    {
        return $this->_frame->get_style();
    }

    
    function get_original_style()
    {
        return $this->_frame->get_style();
    }

    function get_containing_block($i = null)
    {
        return $this->_frame->get_containing_block($i);
    }

    function get_position($i = null)
    {
        return $this->_frame->get_position($i);
    }

    
    function get_dompdf()
    {
        return $this->_dompdf;
    }

    public function get_margin_width(): float
    {
        return $this->_frame->get_margin_width();
    }

    public function get_margin_height(): float
    {
        return $this->_frame->get_margin_height();
    }

    public function get_content_box(): array
    {
        return $this->_frame->get_content_box();
    }

    public function get_padding_box(): array
    {
        return $this->_frame->get_padding_box();
    }

    public function get_border_box(): array
    {
        return $this->_frame->get_border_box();
    }

    function set_id($id)
    {
        $this->_frame->set_id($id);
    }

    public function set_style(Style $style): void
    {
        $this->_frame->set_style($style);
    }

    function set_containing_block($x = null, $y = null, $w = null, $h = null)
    {
        $this->_frame->set_containing_block($x, $y, $w, $h);
    }

    function set_position($x = null, $y = null)
    {
        $this->_frame->set_position($x, $y);
    }

    function is_auto_height()
    {
        return $this->_frame->is_auto_height();
    }

    function is_auto_width()
    {
        return $this->_frame->is_auto_width();
    }

    function __toString()
    {
        return $this->_frame->__toString();
    }

    function prepend_child(Frame $child, $update_node = true)
    {
        while ($child instanceof AbstractFrameDecorator) {
            $child = $child->_frame;
        }

        $this->_frame->prepend_child($child, $update_node);
    }

    function append_child(Frame $child, $update_node = true)
    {
        while ($child instanceof AbstractFrameDecorator) {
            $child = $child->_frame;
        }

        $this->_frame->append_child($child, $update_node);
    }

    function insert_child_before(Frame $new_child, Frame $ref, $update_node = true)
    {
        while ($new_child instanceof AbstractFrameDecorator) {
            $new_child = $new_child->_frame;
        }

        if ($ref instanceof AbstractFrameDecorator) {
            $ref = $ref->_frame;
        }

        $this->_frame->insert_child_before($new_child, $ref, $update_node);
    }

    function insert_child_after(Frame $new_child, Frame $ref, $update_node = true)
    {
        $insert_frame = $new_child;
        while ($insert_frame instanceof AbstractFrameDecorator) {
            $insert_frame = $insert_frame->_frame;
        }

        $reference_frame = $ref;
        while ($reference_frame instanceof AbstractFrameDecorator) {
            $reference_frame = $reference_frame->_frame;
        }

        $this->_frame->insert_child_after($insert_frame, $reference_frame, $update_node);
    }

    function remove_child(Frame $child, $update_node = true)
    {
        while ($child instanceof AbstractFrameDecorator) {
            $child = $child->_frame;
        }

        return $this->_frame->remove_child($child, $update_node);
    }

    
    function get_parent($use_cache = true)
    {
        if ($use_cache && $this->_cached_parent) {
            return $this->_cached_parent;
        }
        $p = $this->_frame->get_parent();
        if ($p && $deco = $p->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $this->_cached_parent = $deco;
        } else {
            return $this->_cached_parent = $p;
        }
    }

    
    function get_first_child()
    {
        $c = $this->_frame->get_first_child();
        if ($c && $deco = $c->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($c) {
                return $c;
            }
        }

        return null;
    }

    
    function get_last_child()
    {
        $c = $this->_frame->get_last_child();
        if ($c && $deco = $c->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($c) {
                return $c;
            }
        }

        return null;
    }

    
    function get_prev_sibling()
    {
        $s = $this->_frame->get_prev_sibling();
        if ($s && $deco = $s->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($s) {
                return $s;
            }
        }

        return null;
    }

    
    function get_next_sibling()
    {
        $s = $this->_frame->get_next_sibling();
        if ($s && $deco = $s->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($s) {
                return $s;
            }
        }

        return null;
    }

    
    public function get_children(): FrameListIterator
    {
        return new FrameListIterator($this);
    }

    
    function get_subtree(): FrameTreeIterator
    {
        return new FrameTreeIterator($this);
    }

    function set_positioner(AbstractPositioner $posn)
    {
        $this->_positioner = $posn;
        if ($this->_frame instanceof AbstractFrameDecorator) {
            $this->_frame->set_positioner($posn);
        }
    }

    function set_reflower(AbstractFrameReflower $reflower)
    {
        $this->_reflower = $reflower;
        if ($this->_frame instanceof AbstractFrameDecorator) {
            $this->_frame->set_reflower($reflower);
        }
    }

    
    function get_positioner()
    {
        return $this->_positioner;
    }

    
    function get_reflower()
    {
        return $this->_reflower;
    }

    
    function set_root(Frame $root)
    {
        $this->_root = $root;

        if ($this->_frame instanceof AbstractFrameDecorator) {
            $this->_frame->set_root($root);
        }
    }

    
    function get_root()
    {
        return $this->_root;
    }

    
    function find_block_parent()
    {
        
        if (isset($this->_block_parent)) {
            return $this->_block_parent;
        }

        $p = $this->get_parent();

        while ($p) {
            if ($p->is_block()) {
                break;
            }

            $p = $p->get_parent();
        }

        return $this->_block_parent = $p;
    }

    
    function find_positioned_parent()
    {
        
        if (isset($this->_positioned_parent)) {
            return $this->_positioned_parent;
        }

        $p = $this->get_parent();
        while ($p) {
            if ($p->is_positioned()) {
                break;
            }

            $p = $p->get_parent();
        }

        if (!$p) {
            $p = $this->_root;
        }

        return $this->_positioned_parent = $p;
    }

    
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $this->get_parent()->split($this, $page_break, $forced);
            return;
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        $this->revert_counter_increment();

        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        
        if ($node->nodeName !== "body") {
            
            $style->margin_bottom = 0.0;
            $style->padding_bottom = 0.0;
            $style->border_bottom_width = 0.0;
            $style->border_bottom_left_radius = 0.0;
            $style->border_bottom_right_radius = 0.0;

            
            $split_style->margin_top = 0.0;
            $split_style->padding_top = 0.0;
            $split_style->border_top_width = 0.0;
            $split_style->border_top_left_radius = 0.0;
            $split_style->border_top_right_radius = 0.0;
            $split_style->page_break_before = "auto";
        }

        $split_style->text_indent = 0.0;
        $split_style->counter_reset = "none";

        $this->is_split = true;
        $split->is_split_off = true;
        $split->_already_pushed = true;

        $this->get_parent()->insert_child_after($split, $this);

        if ($this instanceof Block) {
            
            
            $this->remove_frames_from_line($child);

            
            foreach ($this->get_line_boxes() as $line_box) {
                $line_box->get_float_offsets();
            }
        }

        if (!$forced) {
            
            
            $child->get_style()->margin_top = 0.0;
        }

        
        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $split->append_child($frame);
        }

        $this->get_parent()->split($split, $page_break, $forced);

        
        
        $split->_counters = $this->_counters;
    }

    
    public function reset_counters(array $counters): void
    {
        foreach ($counters as $id => $value) {
            $this->reset_counter($id, $value);
        }
    }

    
    public function reset_counter(string $id = self::DEFAULT_COUNTER, int $value = 0): void
    {
        $this->get_parent()->_counters[$id] = $value;
    }

    
    public function decrement_counters(array $counters): void
    {
        foreach ($counters as $id => $increment) {
            $this->increment_counter($id, $increment * -1);
        }
    }

    
    public function increment_counters(array $counters): void
    {
        foreach ($counters as $id => $increment) {
            $this->increment_counter($id, $increment);
        }
    }

    
    public function increment_counter(string $id = self::DEFAULT_COUNTER, int $increment = 1): void
    {
        $counter_frame = $this->lookup_counter_frame($id, true);
        $counter_frame->_counters[$id] += $increment;
    }

    
    public function lookup_counter_frame(
        string $id = self::DEFAULT_COUNTER,
        bool $auto_reset = false
    ): ?AbstractFrameDecorator {
        $f = $this->get_parent();

        while ($f) {
            if (isset($f->_counters[$id])) {
                return $f;
            }
            $f = $f->get_parent();
        }

        if ($auto_reset) {
            $f = $this->get_parent();
            $f->_counters[$id] = 0;
            return $f;
        }

        return null;
    }

    
    public function counter_value(string $id = self::DEFAULT_COUNTER, string $type = "decimal"): string
    {
        $value = $this->_counters[$id] ?? 0;

        switch ($type) {
            default:
            case "decimal":
                return $value;

            case "decimal-leading-zero":
                return str_pad($value, 2, "0", STR_PAD_LEFT);

            case "lower-roman":
                return Helpers::dec2roman($value);

            case "upper-roman":
                return strtoupper(Helpers::dec2roman($value));

            case "lower-latin":
            case "lower-alpha":
                return chr((($value - 1) % 26) + ord('a'));

            case "upper-latin":
            case "upper-alpha":
                return chr((($value - 1) % 26) + ord('A'));

            case "lower-greek":
                return Helpers::unichr($value + 944);

            case "upper-greek":
                return Helpers::unichr($value + 912);
        }
    }

    final function position()
    {
        $this->_positioner->position($this);
    }

    
    final function move(float $offset_x, float $offset_y, bool $ignore_self = false): void
    {
        $this->_positioner->move($this, $offset_x, $offset_y, $ignore_self);
    }

    
    final function reflow(?Block $block = null)
    {
        
        
        
        $this->_reflower->reflow($block);
    }

    
    final public function get_min_max_width(): array
    {
        return $this->_reflower->get_min_max_width();
    }
}
