<?php

namespace Sabberworm\CSS\RuleSet;

use Sabberworm\CSS\Comment\Comment;
use Sabberworm\CSS\Comment\Commentable;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parsing\ParserState;
use Sabberworm\CSS\Parsing\UnexpectedEOFException;
use Sabberworm\CSS\Parsing\UnexpectedTokenException;
use Sabberworm\CSS\Renderable;
use Sabberworm\CSS\Rule\Rule;


abstract class RuleSet implements Renderable, Commentable
{
    
    private $aRules;

    
    protected $iLineNo;

    
    protected $aComments;

    
    public function __construct($iLineNo = 0)
    {
        $this->aRules = [];
        $this->iLineNo = $iLineNo;
        $this->aComments = [];
    }

    
    public static function parseRuleSet(ParserState $oParserState, RuleSet $oRuleSet)
    {
        while ($oParserState->comes(';')) {
            $oParserState->consume(';');
        }
        while (!$oParserState->comes('}')) {
            $oRule = null;
            if ($oParserState->getSettings()->bLenientParsing) {
                try {
                    $oRule = Rule::parse($oParserState);
                } catch (UnexpectedTokenException $e) {
                    try {
                        $sConsume = $oParserState->consumeUntil(["\n", ";", '}'], true);
                        
                        if ($oParserState->streql(substr($sConsume, -1), '}')) {
                            $oParserState->backtrack(1);
                        } else {
                            while ($oParserState->comes(';')) {
                                $oParserState->consume(';');
                            }
                        }
                    } catch (UnexpectedTokenException $e) {
                        
                        return;
                    }
                }
            } else {
                $oRule = Rule::parse($oParserState);
            }
            if ($oRule) {
                $oRuleSet->addRule($oRule);
            }
        }
        $oParserState->consume('}');
    }

    
    public function getLineNo()
    {
        return $this->iLineNo;
    }

    
    public function addRule(Rule $oRule, $oSibling = null)
    {
        $sRule = $oRule->getRule();
        if (!isset($this->aRules[$sRule])) {
            $this->aRules[$sRule] = [];
        }

        $iPosition = count($this->aRules[$sRule]);

        if ($oSibling !== null) {
            $iSiblingPos = array_search($oSibling, $this->aRules[$sRule], true);
            if ($iSiblingPos !== false) {
                $iPosition = $iSiblingPos;
                $oRule->setPosition($oSibling->getLineNo(), $oSibling->getColNo() - 1);
            }
        }
        if ($oRule->getLineNo() === 0 && $oRule->getColNo() === 0) {
            
            $rules = $this->getRules();
            $pos = count($rules);
            if ($pos > 0) {
                $last = $rules[$pos - 1];
                $oRule->setPosition($last->getLineNo() + 1, 0);
            }
        }

        array_splice($this->aRules[$sRule], $iPosition, 0, [$oRule]);
    }

    
    public function getRules($mRule = null)
    {
        if ($mRule instanceof Rule) {
            $mRule = $mRule->getRule();
        }
        
        $aResult = [];
        foreach ($this->aRules as $sName => $aRules) {
            
            
            if (
                !$mRule || $sName === $mRule
                || (
                    strrpos($mRule, '-') === strlen($mRule) - strlen('-')
                    && (strpos($sName, $mRule) === 0 || $sName === substr($mRule, 0, -1))
                )
            ) {
                $aResult = array_merge($aResult, $aRules);
            }
        }
        usort($aResult, function (Rule $first, Rule $second) {
            if ($first->getLineNo() === $second->getLineNo()) {
                return $first->getColNo() - $second->getColNo();
            }
            return $first->getLineNo() - $second->getLineNo();
        });
        return $aResult;
    }

    
    public function setRules(array $aRules)
    {
        $this->aRules = [];
        foreach ($aRules as $rule) {
            $this->addRule($rule);
        }
    }

    
    public function getRulesAssoc($mRule = null)
    {
        
        $aResult = [];
        foreach ($this->getRules($mRule) as $oRule) {
            $aResult[$oRule->getRule()] = $oRule;
        }
        return $aResult;
    }

    
    public function removeRule($mRule)
    {
        if ($mRule instanceof Rule) {
            $sRule = $mRule->getRule();
            if (!isset($this->aRules[$sRule])) {
                return;
            }
            foreach ($this->aRules[$sRule] as $iKey => $oRule) {
                if ($oRule === $mRule) {
                    unset($this->aRules[$sRule][$iKey]);
                }
            }
        } else {
            foreach ($this->aRules as $sName => $aRules) {
                
                
                
                if (
                    !$mRule || $sName === $mRule
                    || (strrpos($mRule, '-') === strlen($mRule) - strlen('-')
                        && (strpos($sName, $mRule) === 0 || $sName === substr($mRule, 0, -1)))
                ) {
                    unset($this->aRules[$sName]);
                }
            }
        }
    }

    
    public function __toString()
    {
        return $this->render(new OutputFormat());
    }

    
    protected function renderRules(OutputFormat $oOutputFormat)
    {
        $sResult = '';
        $bIsFirst = true;
        $oNextLevel = $oOutputFormat->nextLevel();
        foreach ($this->aRules as $aRules) {
            foreach ($aRules as $oRule) {
                $sRendered = $oNextLevel->safely(function () use ($oRule, $oNextLevel) {
                    return $oRule->render($oNextLevel);
                });
                if ($sRendered === null) {
                    continue;
                }
                if ($bIsFirst) {
                    $bIsFirst = false;
                    $sResult .= $oNextLevel->spaceBeforeRules();
                } else {
                    $sResult .= $oNextLevel->spaceBetweenRules();
                }
                $sResult .= $sRendered;
            }
        }

        if (!$bIsFirst) {
            
            $sResult .= $oOutputFormat->spaceAfterRules();
        }

        return $oOutputFormat->removeLastSemicolon($sResult);
    }

    
    public function addComments(array $aComments)
    {
        $this->aComments = array_merge($this->aComments, $aComments);
    }

    
    public function getComments()
    {
        return $this->aComments;
    }

    
    public function setComments(array $aComments)
    {
        $this->aComments = $aComments;
    }
}
