<?php

namespace Dompdf\FrameDecorator;

use Dompdf\Cellmap;
use DOMNode;
use Dompdf\Css\Style;
use Dompdf\Dompdf;
use Dompdf\Frame;


class Table extends AbstractFrameDecorator
{
    public const VALID_CHILDREN = Style::TABLE_INTERNAL_TYPES;

    
    public const ROW_GROUPS = [
        "table-row-group",
        "table-header-group",
        "table-footer-group"
    ];

    
    protected $_cellmap;

    
    protected $_headers;

    
    protected $_footers;

    
    public function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $this->_cellmap = new Cellmap($this);

        if ($frame->get_style()->table_layout === "fixed") {
            $this->_cellmap->set_layout_fixed(true);
        }

        $this->_headers = [];
        $this->_footers = [];
    }

    public function reset()
    {
        parent::reset();
        $this->_cellmap->reset();
        $this->_headers = [];
        $this->_footers = [];
        $this->_reflower->reset();
    }

    

    
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            parent::split($child, $page_break, $forced);
            return;
        }

        
        
        if (count($this->_headers)
            && !in_array($child, $this->_headers, true)
            && !in_array($child->get_prev_sibling(), $this->_headers, true)
        ) {
            $first_header = null;

            
            foreach ($this->_headers as $header) {

                $new_header = $header->deep_copy();

                if (is_null($first_header)) {
                    $first_header = $new_header;
                }

                $this->insert_child_before($new_header, $child);
            }

            parent::split($first_header, $page_break, $forced);

        } elseif (in_array($child->get_style()->display, self::ROW_GROUPS, true)) {

            
            parent::split($child, $page_break, $forced);

        } else {

            $iter = $child;

            while ($iter) {
                $this->_cellmap->remove_row($iter);
                $iter = $iter->get_next_sibling();
            }

            parent::split($child, $page_break, $forced);
        }
    }

    public function copy(DOMNode $node)
    {
        $deco = parent::copy($node);

        
        $deco->_cellmap->set_columns($this->_cellmap->get_columns());
        $deco->_cellmap->lock_columns();

        return $deco;
    }

    
    public static function find_parent_table(Frame $frame)
    {
        while ($frame = $frame->get_parent()) {
            if ($frame->is_table()) {
                break;
            }
        }

        return $frame;
    }

    
    public function get_cellmap()
    {
        return $this->_cellmap;
    }

    

    
    private function isEmptyTextNode(AbstractFrameDecorator $frame): bool
    {
        
        
        $wsPattern = '/^[^\S\xA0\x{202F}\x{2007}]*$/u';
        $validChildOrNull = function ($frame) {
            return $frame === null
                || in_array($frame->get_style()->display, self::VALID_CHILDREN, true);
        };

        return $frame instanceof Text
            && !$frame->is_pre()
            && preg_match($wsPattern, $frame->get_text())
            && $validChildOrNull($frame->get_prev_sibling())
            && $validChildOrNull($frame->get_next_sibling());
    }

    
    public function normalize(): void
    {
        $column_caption = ["table-column-group", "table-column", "table-caption"];
        $children = iterator_to_array($this->get_children());
        $tbody = null;

        foreach ($children as $child) {
            $display = $child->get_style()->display;

            if (in_array($display, self::ROW_GROUPS, true)) {
                
                $tbody = null;

                
                if ($display === "table-header-group") {
                    $this->_headers[] = $child;
                } elseif ($display === "table-footer-group") {
                    $this->_footers[] = $child;
                }
                continue;
            }

            if (in_array($display, $column_caption, true)) {
                continue;
            }

            
            if ($this->isEmptyTextNode($child)) {
                $this->remove_child($child);
                continue;
            }

            
            if ($tbody === null) {
                $tbody = $this->create_anonymous_child("tbody", "table-row-group");
                $this->insert_child_before($tbody, $child);
            }

            $tbody->append_child($child);
        }

        
        if (!$this->get_first_child()) {
            $tbody = $this->create_anonymous_child("tbody", "table-row-group");
            $this->append_child($tbody);
        }

        foreach ($this->get_children() as $child) {
            $display = $child->get_style()->display;

            if (in_array($display, self::ROW_GROUPS, true)) {
                $this->normalizeRowGroup($child);
            }
        }
    }

    private function normalizeRowGroup(AbstractFrameDecorator $frame): void
    {
        $children = iterator_to_array($frame->get_children());
        $tr = null;

        foreach ($children as $child) {
            $display = $child->get_style()->display;

            if ($display === "table-row") {
                
                $tr = null;
                continue;
            }

            
            if ($this->isEmptyTextNode($child)) {
                $frame->remove_child($child);
                continue;
            }

            
            if ($tr === null) {
                $tr = $frame->create_anonymous_child("tr", "table-row");
                $frame->insert_child_before($tr, $child);
            }

            $tr->append_child($child);
        }

        
        if (!$frame->get_first_child()) {
            $tr = $frame->create_anonymous_child("tr", "table-row");
            $frame->append_child($tr);
        }

        foreach ($frame->get_children() as $child) {
            $this->normalizeRow($child);
        }
    }

    private function normalizeRow(AbstractFrameDecorator $frame): void
    {
        $children = iterator_to_array($frame->get_children());
        $td = null;

        foreach ($children as $child) {
            $display = $child->get_style()->display;

            if ($display === "table-cell") {
                
                $td = null;
                continue;
            }

            
            if ($this->isEmptyTextNode($child)) {
                $frame->remove_child($child);
                continue;
            }

            
            if ($td === null) {
                $td = $frame->create_anonymous_child("td", "table-cell");
                $frame->insert_child_before($td, $child);
            }

            $td->append_child($child);
        }

        
        if (!$frame->get_first_child()) {
            $td = $frame->create_anonymous_child("td", "table-cell");
            $frame->append_child($td);
        }
    }
}
