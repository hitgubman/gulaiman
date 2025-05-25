<?php

namespace Sabberworm\CSS\Parsing;


class Anchor
{
    
    private $iPosition;

    
    private $oParserState;

    
    public function __construct($iPosition, ParserState $oParserState)
    {
        $this->iPosition = $iPosition;
        $this->oParserState = $oParserState;
    }

    
    public function backtrack()
    {
        $this->oParserState->setPosition($this->iPosition);
    }
}
