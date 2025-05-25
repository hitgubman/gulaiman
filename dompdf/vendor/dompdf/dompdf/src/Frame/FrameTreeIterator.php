<?php

namespace Dompdf\Frame;

use Iterator;
use Dompdf\Frame;


class FrameTreeIterator implements Iterator
{
    
    protected $_root;

    
    protected $_stack = [];

    
    protected $_num;

    
    public function __construct(Frame $root)
    {
        $this->_stack[] = $this->_root = $root;
        $this->_num = 0;
    }

    public function rewind(): void
    {
        $this->_stack = [$this->_root];
        $this->_num = 0;
    }

    
    public function valid(): bool
    {
        return count($this->_stack) > 0;
    }

    
    public function key(): int
    {
        return $this->_num;
    }

    
    public function current(): Frame
    {
        return end($this->_stack);
    }

    public function next(): void
    {
        $b = array_pop($this->_stack);
        $this->_num++;

        
        if ($c = $b->get_last_child()) {
            $this->_stack[] = $c;
            while ($c = $c->get_prev_sibling()) {
                $this->_stack[] = $c;
            }
        }
    }
}
