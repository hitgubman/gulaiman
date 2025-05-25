<?php

namespace Dompdf;

use Dompdf\Css\Style;
use Dompdf\Frame\FrameListIterator;


class Frame
{
    const WS_TEXT = 1;
    const WS_SPACE = 2;

    
    protected $_node;

    
    protected $_id;

    
    public static $ID_COUNTER = 0; 

    
    protected $_style;

    
    protected $_parent;

    
    protected $_first_child;

    
    protected $_last_child;

    
    protected $_prev_sibling;

    
    protected $_next_sibling;

    
    protected $_containing_block;

    
    protected $_position;

    
    protected $_opacity;

    
    protected $_decorator;

    
    protected $_containing_line;

    
    protected $_is_cache = [];

    
    public $_already_pushed = false;

    
    public $_float_next_line = false;

    
    public static $_ws_state = self::WS_SPACE;

    
    public function __construct(\DOMNode $node)
    {
        $this->_node = $node;

        $this->_parent = null;
        $this->_first_child = null;
        $this->_last_child = null;
        $this->_prev_sibling = $this->_next_sibling = null;

        $this->_style = null;

        $this->_containing_block = [
            "x" => null,
            "y" => null,
            "w" => null,
            "h" => null,
        ];

        $this->_containing_block[0] =& $this->_containing_block["x"];
        $this->_containing_block[1] =& $this->_containing_block["y"];
        $this->_containing_block[2] =& $this->_containing_block["w"];
        $this->_containing_block[3] =& $this->_containing_block["h"];

        $this->_position = [
            "x" => null,
            "y" => null,
        ];

        $this->_position[0] =& $this->_position["x"];
        $this->_position[1] =& $this->_position["y"];

        $this->_opacity = 1.0;
        $this->_decorator = null;

        $this->set_id(self::$ID_COUNTER++);
    }

    
    protected function ws_trim()
    {
        if ($this->ws_keep()) {
            return;
        }

        if (self::$_ws_state === self::WS_SPACE) {
            $node = $this->_node;

            if ($node->nodeName === "#text" && !empty($node->nodeValue)) {
                $node->nodeValue = preg_replace("/[ \t\r\n\f]+/u", " ", trim($node->nodeValue));
                self::$_ws_state = self::WS_TEXT;
            }
        }
    }

    
    protected function ws_keep()
    {
        $whitespace = $this->get_style()->white_space;

        return in_array($whitespace, ["pre", "pre-wrap", "pre-line"]);
    }

    
    protected function ws_is_text()
    {
        $node = $this->get_node();

        if ($node->nodeName === "img") {
            return true;
        }

        if (!$this->is_in_flow()) {
            return false;
        }

        if ($this->is_text_node()) {
            return trim($node->nodeValue) !== "";
        }

        return true;
    }

    
    public function dispose($recursive = false)
    {
        if ($recursive) {
            while ($child = $this->_first_child) {
                $child->dispose(true);
            }
        }

        
        if ($this->_prev_sibling) {
            $this->_prev_sibling->_next_sibling = $this->_next_sibling;
        }

        if ($this->_next_sibling) {
            $this->_next_sibling->_prev_sibling = $this->_prev_sibling;
        }

        if ($this->_parent && $this->_parent->_first_child === $this) {
            $this->_parent->_first_child = $this->_next_sibling;
        }

        if ($this->_parent && $this->_parent->_last_child === $this) {
            $this->_parent->_last_child = $this->_prev_sibling;
        }

        if ($this->_parent) {
            $this->_parent->get_node()->removeChild($this->_node);
        }

        $this->_style = null;
        unset($this->_style);
    }

    
    public function reset()
    {
        $this->_position["x"] = null;
        $this->_position["y"] = null;

        $this->_containing_block["x"] = null;
        $this->_containing_block["y"] = null;
        $this->_containing_block["w"] = null;
        $this->_containing_block["h"] = null;

        $this->_style->reset();
    }

    
    public function get_node()
    {
        return $this->_node;
    }

    
    public function get_id()
    {
        return $this->_id;
    }

    
    public function get_style()
    {
        return $this->_style;
    }

    
    public function get_original_style()
    {
        return $this->_style;
    }

    
    public function get_parent()
    {
        return $this->_parent;
    }

    
    public function get_decorator()
    {
        return $this->_decorator;
    }

    
    public function get_first_child()
    {
        return $this->_first_child;
    }

    
    public function get_last_child()
    {
        return $this->_last_child;
    }

    
    public function get_prev_sibling()
    {
        return $this->_prev_sibling;
    }

    
    public function get_next_sibling()
    {
        return $this->_next_sibling;
    }

    
    public function get_children(): FrameListIterator
    {
        return new FrameListIterator($this);
    }

    

    
    public function get_containing_block($i = null)
    {
        if (isset($i)) {
            return $this->_containing_block[$i];
        }

        return $this->_containing_block;
    }

    
    public function get_position($i = null)
    {
        if (isset($i)) {
            return $this->_position[$i];
        }

        return $this->_position;
    }

    

    
    public function get_margin_width(): float
    {
        $style = $this->_style;

        return (float)$style->length_in_pt([
            $style->width,
            $style->margin_left,
            $style->margin_right,
            $style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right
        ], $this->_containing_block["w"]);
    }

    
    public function get_margin_height(): float
    {
        $style = $this->_style;

        return (float)$style->length_in_pt(
            [
                $style->height,
                (float)$style->length_in_pt(
                    [
                        $style->border_top_width,
                        $style->border_bottom_width,
                        $style->margin_top,
                        $style->margin_bottom,
                        $style->padding_top,
                        $style->padding_bottom
                    ], $this->_containing_block["w"]
                )
            ],
            $this->_containing_block["h"]
        );
    }

    
    public function get_content_box(): array
    {
        $style = $this->_style;
        $cb = $this->_containing_block;

        $x = $this->_position["x"] +
            (float)$style->length_in_pt(
                [
                    $style->margin_left,
                    $style->border_left_width,
                    $style->padding_left
                ],
                $cb["w"]
            );

        $y = $this->_position["y"] +
            (float)$style->length_in_pt(
                [
                    $style->margin_top,
                    $style->border_top_width,
                    $style->padding_top
                ], $cb["w"]
            );

        $w = (float)$style->length_in_pt($style->width, $cb["w"]);

        $h = (float)$style->length_in_pt($style->height, $cb["h"]);

        return [0 => $x, "x" => $x,
            1 => $y, "y" => $y,
            2 => $w, "w" => $w,
            3 => $h, "h" => $h];
    }

    
    public function get_padding_box(): array
    {
        $style = $this->_style;
        $cb = $this->_containing_block;

        $x = $this->_position["x"] +
            (float)$style->length_in_pt(
                [
                    $style->margin_left,
                    $style->border_left_width
                ],
                $cb["w"]
            );

        $y = $this->_position["y"] +
            (float)$style->length_in_pt(
                [
                    $style->margin_top,
                    $style->border_top_width
                ],
                $cb["h"]
            );

        $w = (float)$style->length_in_pt(
                [
                    $style->padding_left,
                    $style->width,
                    $style->padding_right
                ],
                $cb["w"]
            );

        $h = (float)$style->length_in_pt(
                [
                    $style->padding_top,
                    $style->padding_bottom,
                    $style->length_in_pt($style->height, $cb["h"])
                ],
                $cb["w"]
            );

        return [0 => $x, "x" => $x,
            1 => $y, "y" => $y,
            2 => $w, "w" => $w,
            3 => $h, "h" => $h];
    }

    
    public function get_border_box(): array
    {
        $style = $this->_style;
        $cb = $this->_containing_block;

        $x = $this->_position["x"] + (float)$style->length_in_pt($style->margin_left, $cb["w"]);

        $y = $this->_position["y"] + (float)$style->length_in_pt($style->margin_top, $cb["w"]);

        $w = (float)$style->length_in_pt(
            [
                $style->border_left_width,
                $style->padding_left,
                $style->width,
                $style->padding_right,
                $style->border_right_width
            ],
            $cb["w"]
        );

        $h = (float)$style->length_in_pt(
            [
                $style->border_top_width,
                $style->padding_top,
                $style->padding_bottom,
                $style->border_bottom_width,
                $style->length_in_pt($style->height, $cb["h"])
            ],
            $cb["w"]
        );

        return [0 => $x, "x" => $x,
            1 => $y, "y" => $y,
            2 => $w, "w" => $w,
            3 => $h, "h" => $h];
    }

    
    public function get_opacity(?float $opacity = null): float
    {
        if ($opacity !== null) {
            $this->set_opacity($opacity);
        }

        return $this->_opacity;
    }

    
    public function &get_containing_line()
    {
        return $this->_containing_line;
    }

    
    

    
    public function set_id($id)
    {
        $this->_id = $id;

        
        
        
        if ($this->_node->nodeType == XML_ELEMENT_NODE) {
            $this->_node->setAttribute("frame_id", $id);
        }
    }

    
    public function set_style(Style $style): void
    {
        
        $this->_style = $style;
    }

    
    public function set_decorator(FrameDecorator\AbstractFrameDecorator $decorator)
    {
        $this->_decorator = $decorator;
    }

    
    public function set_containing_block($x = null, $y = null, $w = null, $h = null)
    {
        if (is_array($x)) {
            foreach ($x as $key => $val) {
                $$key = $val;
            }
        }

        if (is_numeric($x)) {
            $this->_containing_block["x"] = $x;
        }

        if (is_numeric($y)) {
            $this->_containing_block["y"] = $y;
        }

        if (is_numeric($w)) {
            $this->_containing_block["w"] = $w;
        }

        if (is_numeric($h)) {
            $this->_containing_block["h"] = $h;
        }
    }

    
    public function set_position($x = null, $y = null)
    {
        if (is_array($x)) {
            list($x, $y) = [$x["x"], $x["y"]];
        }

        if (is_numeric($x)) {
            $this->_position["x"] = $x;
        }

        if (is_numeric($y)) {
            $this->_position["y"] = $y;
        }
    }

    
    public function set_opacity(float $opacity): void
    {
        $parent = $this->get_parent();
        $base_opacity = $parent && $parent->_opacity !== null ? $parent->_opacity : 1.0;
        $this->_opacity = $base_opacity * $opacity;
    }

    
    public function set_containing_line(LineBox $line)
    {
        $this->_containing_line = $line;
    }

    
    public function is_auto_height()
    {
        $style = $this->_style;

        return in_array(
            "auto",
            [
                $style->height,
                $style->margin_top,
                $style->margin_bottom,
                $style->border_top_width,
                $style->border_bottom_width,
                $style->padding_top,
                $style->padding_bottom,
                $this->_containing_block["h"]
            ],
            true
        );
    }

    
    public function is_auto_width()
    {
        $style = $this->_style;

        return in_array(
            "auto",
            [
                $style->width,
                $style->margin_left,
                $style->margin_right,
                $style->border_left_width,
                $style->border_right_width,
                $style->padding_left,
                $style->padding_right,
                $this->_containing_block["w"]
            ],
            true
        );
    }

    
    public function is_text_node(): bool
    {
        if (isset($this->_is_cache["text_node"])) {
            return $this->_is_cache["text_node"];
        }

        return $this->_is_cache["text_node"] = ($this->get_node()->nodeName === "#text");
    }

    
    public function is_positioned(): bool
    {
        if (isset($this->_is_cache["positioned"])) {
            return $this->_is_cache["positioned"];
        }

        $position = $this->get_style()->position;

        return $this->_is_cache["positioned"] = in_array($position, Style::POSITIONED_TYPES, true);
    }

    
    public function is_absolute(): bool
    {
        if (isset($this->_is_cache["absolute"])) {
            return $this->_is_cache["absolute"];
        }

        return $this->_is_cache["absolute"] = $this->get_style()->is_absolute();
    }

    
    public function is_block(): bool
    {
        if (isset($this->_is_cache["block"])) {
            return $this->_is_cache["block"];
        }

        return $this->_is_cache["block"] = in_array($this->get_style()->display, Style::BLOCK_TYPES, true);
    }

    
    public function is_block_level(): bool
    {
        if (isset($this->_is_cache["block_level"])) {
            return $this->_is_cache["block_level"];
        }

        $display = $this->get_style()->display;

        return $this->_is_cache["block_level"] = in_array($display, Style::BLOCK_LEVEL_TYPES, true);
    }

    
    public function is_inline_level(): bool
    {
        if (isset($this->_is_cache["inline_level"])) {
            return $this->_is_cache["inline_level"];
        }

        $display = $this->get_style()->display;

        return $this->_is_cache["inline_level"] = in_array($display, Style::INLINE_LEVEL_TYPES, true);
    }

    
    public function is_in_flow(): bool
    {
        if (isset($this->_is_cache["in_flow"])) {
            return $this->_is_cache["in_flow"];
        }

        return $this->_is_cache["in_flow"] = $this->get_style()->is_in_flow();
    }

    
    public function is_pre(): bool
    {
        if (isset($this->_is_cache["pre"])) {
            return $this->_is_cache["pre"];
        }

        $white_space = $this->get_style()->white_space;

        return $this->_is_cache["pre"] = in_array($white_space, ["pre", "pre-wrap"], true);
    }

    
    public function is_table(): bool
    {
        if (isset($this->_is_cache["table"])) {
            return $this->_is_cache["table"];
        }

        $display = $this->get_style()->display;

        return $this->_is_cache["table"] = in_array($display, Style::TABLE_TYPES, true);
    }


    
    public function prepend_child(Frame $child, $update_node = true)
    {
        if ($update_node) {
            $this->_node->insertBefore($child->_node, $this->_first_child ? $this->_first_child->_node : null);
        }

        
        if ($child->_parent) {
            $child->_parent->remove_child($child, false);
        }

        $child->_parent = $this;
        $decorator = $child->get_decorator();
        
        if ($decorator !== null) {
            $decorator->get_parent(false);
        }
        $child->_prev_sibling = null;

        
        if (!$this->_first_child) {
            $this->_first_child = $child;
            $this->_last_child = $child;
            $child->_next_sibling = null;
        } else {
            $this->_first_child->_prev_sibling = $child;
            $child->_next_sibling = $this->_first_child;
            $this->_first_child = $child;
        }
    }

    
    public function append_child(Frame $child, $update_node = true)
    {
        if ($update_node) {
            $this->_node->appendChild($child->_node);
        }

        
        if ($child->_parent) {
            $child->_parent->remove_child($child, false);
        }

        $child->_parent = $this;
        $decorator = $child->get_decorator();
        
        if ($decorator !== null) {
            $decorator->get_parent(false);
        }
        $child->_next_sibling = null;

        
        if (!$this->_last_child) {
            $this->_first_child = $child;
            $this->_last_child = $child;
            $child->_prev_sibling = null;
        } else {
            $this->_last_child->_next_sibling = $child;
            $child->_prev_sibling = $this->_last_child;
            $this->_last_child = $child;
        }
    }

    
    public function insert_child_before(Frame $new_child, Frame $ref, $update_node = true)
    {
        if ($ref === $this->_first_child) {
            $this->prepend_child($new_child, $update_node);

            return;
        }

        if (is_null($ref)) {
            $this->append_child($new_child, $update_node);

            return;
        }

        if ($ref->_parent !== $this) {
            throw new Exception("Reference child is not a child of this node.");
        }

        
        if ($update_node) {
            $this->_node->insertBefore($new_child->_node, $ref->_node);
        }

        
        if ($new_child->_parent) {
            $new_child->_parent->remove_child($new_child, false);
        }

        $new_child->_parent = $this;
        $decorator = $new_child->get_decorator();
        
        if ($decorator !== null) {
            $decorator->get_parent(false);
        }
        $new_child->_next_sibling = $ref;
        $new_child->_prev_sibling = $ref->_prev_sibling;

        if ($ref->_prev_sibling) {
            $ref->_prev_sibling->_next_sibling = $new_child;
        }

        $ref->_prev_sibling = $new_child;
    }

    
    public function insert_child_after(Frame $new_child, Frame $ref, $update_node = true)
    {
        if ($ref === $this->_last_child) {
            $this->append_child($new_child, $update_node);

            return;
        }

        if (is_null($ref)) {
            $this->prepend_child($new_child, $update_node);

            return;
        }

        if ($ref->_parent !== $this) {
            throw new Exception("Reference child is not a child of this node.");
        }

        
        if ($update_node) {
            if ($ref->_next_sibling) {
                $next_node = $ref->_next_sibling->_node;
                $this->_node->insertBefore($new_child->_node, $next_node);
            } else {
                $new_child->_node = $this->_node->appendChild($new_child->_node);
            }
        }

        
        if ($new_child->_parent) {
            $new_child->_parent->remove_child($new_child, false);
        }

        $new_child->_parent = $this;
        $decorator = $new_child->get_decorator();
        
        if ($decorator !== null) {
            $decorator->get_parent(false);
        }
        $new_child->_prev_sibling = $ref;
        $new_child->_next_sibling = $ref->_next_sibling;

        if ($ref->_next_sibling) {
            $ref->_next_sibling->_prev_sibling = $new_child;
        }

        $ref->_next_sibling = $new_child;
    }

    
    public function remove_child(Frame $child, $update_node = true)
    {
        if ($child->_parent !== $this) {
            throw new Exception("Child not found in this frame");
        }

        if ($update_node) {
            $this->_node->removeChild($child->_node);
        }

        if ($child === $this->_first_child) {
            $this->_first_child = $child->_next_sibling;
        }

        if ($child === $this->_last_child) {
            $this->_last_child = $child->_prev_sibling;
        }

        if ($child->_prev_sibling) {
            $child->_prev_sibling->_next_sibling = $child->_next_sibling;
        }

        if ($child->_next_sibling) {
            $child->_next_sibling->_prev_sibling = $child->_prev_sibling;
        }

        $child->_next_sibling = null;
        $child->_prev_sibling = null;
        $child->_parent = null;

        
        $decorator = $child->get_decorator();
        if ($decorator !== null) {
            $decorator->get_parent(false);
        }

        return $child;
    }

    

    
    
    public function __toString()
    {
        





        $str = "<b>" . $this->_node->nodeName . ":</b><br/>";
        
        $str .= "Id: " . $this->get_id() . "<br/>";
        $str .= "Class: " . get_class($this) . "<br/>";

        if ($this->is_text_node()) {
            $tmp = htmlspecialchars($this->_node->nodeValue);
            $str .= "<pre>'" . mb_substr($tmp, 0, 70) .
                (mb_strlen($tmp) > 70 ? "..." : "") . "'</pre>";
        } elseif ($css_class = $this->_node->getAttribute("class")) {
            $str .= "CSS class: '$css_class'<br/>";
        }

        if ($this->_parent) {
            $str .= "\nParent:" . $this->_parent->_node->nodeName .
                " (" . spl_object_hash($this->_parent->_node) . ") " .
                "<br/>";
        }

        if ($this->_prev_sibling) {
            $str .= "Prev: " . $this->_prev_sibling->_node->nodeName .
                " (" . spl_object_hash($this->_prev_sibling->_node) . ") " .
                "<br/>";
        }

        if ($this->_next_sibling) {
            $str .= "Next: " . $this->_next_sibling->_node->nodeName .
                " (" . spl_object_hash($this->_next_sibling->_node) . ") " .
                "<br/>";
        }

        $d = $this->get_decorator();
        while ($d && $d != $d->get_decorator()) {
            $str .= "Decorator: " . get_class($d) . "<br/>";
            $d = $d->get_decorator();
        }

        $str .= "Position: " . Helpers::pre_r($this->_position, true);
        $str .= "\nContaining block: " . Helpers::pre_r($this->_containing_block, true);
        $str .= "\nMargin width: " . Helpers::pre_r($this->get_margin_width(), true);
        $str .= "\nMargin height: " . Helpers::pre_r($this->get_margin_height(), true);

        $str .= "\nStyle: <pre>" . $this->_style->__toString() . "</pre>";

        if ($this->_decorator instanceof FrameDecorator\Block) {
            $str .= "Lines:<pre>";
            foreach ($this->_decorator->get_line_boxes() as $line) {
                foreach ($line->get_frames() as $frame) {
                    if ($frame instanceof FrameDecorator\Text) {
                        $str .= "\ntext: ";
                        $str .= "'" . htmlspecialchars($frame->get_text()) . "'";
                    } else {
                        $str .= "\nBlock: " . $frame->get_node()->nodeName . " (" . spl_object_hash($frame->get_node()) . ")";
                    }
                }

                $str .=
                    "\ny => " . $line->y . "\n" .
                    "w => " . $line->w . "\n" .
                    "h => " . $line->h . "\n" .
                    "left => " . $line->left . "\n" .
                    "right => " . $line->right . "\n";
            }
            $str .= "</pre>";
        }

        $str .= "\n";
        if (php_sapi_name() === "cli") {
            $str = strip_tags(str_replace(["<br/>", "<b>", "</b>"],
                ["\n", "", ""],
                $str));
        }

        return $str;
    }
}
