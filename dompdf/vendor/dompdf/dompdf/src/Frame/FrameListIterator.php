<?php

namespace Dompdf\Frame;

use Iterator;
use Dompdf\Frame;


class FrameListIterator implements Iterator
{
    
    protected $parent;

    
    protected $cur;

    
    protected $prev;

    
    protected $num;

    
    public function __construct(Frame $frame)
    {
        $this->parent = $frame;
        $this->rewind();
    }

    public function rewind(): void
    {
        $this->cur = $this->parent->get_first_child();
        $this->prev = null;
        $this->num = 0;
    }

    
    public function valid(): bool
    {
        return $this->cur !== null;
    }

    
    public function key(): int
    {
        return $this->num;
    }

    
    public function current(): ?Frame
    {
        return $this->cur;
    }

    public function next(): void
    {
        if ($this->cur === null) {
            return;
        }

        if ($this->cur->get_parent() === $this->parent) {
            $this->prev = $this->cur;
            $this->cur = $this->cur->get_next_sibling();
            $this->num++;
        } else {
            
            
            $this->cur = $this->prev !== null
                ? $this->prev->get_next_sibling()
                : $this->parent->get_first_child();
        }
    }
}
