<?php

namespace Sabberworm\CSS\Value;

use Sabberworm\CSS\OutputFormat;

class CalcRuleValueList extends RuleValueList
{
    
    public function __construct($iLineNo = 0)
    {
        parent::__construct(',', $iLineNo);
    }

    
    public function render($oOutputFormat)
    {
        return $oOutputFormat->implode(' ', $this->aComponents);
    }
}
