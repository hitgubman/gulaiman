<?php

namespace Svg;

class CssLength
{
    
    protected static $units = [
        'vmax',
        'vmin',
        'rem',
        'px',
        'pt',
        'cm',
        'mm',
        'in',
        'pc',
        'em',
        'ex',
        'ch',
        'vw',
        'vh',
        '%',
        'q',
    ];

    
    protected static $inchDivisions = [
        'in' => 1,
        'cm' => 2.54,
        'mm' => 25.4,
        'q' => 101.6,
        'pc' => 6,
        'pt' => 72,
    ];

    
    protected $unit = '';

    
    protected $value = 0;

    
    protected $unparsed;

    public function __construct(string $length)
    {
        $this->unparsed = $length;
        $this->parseLengthComponents($length);
    }

    
    protected function parseLengthComponents(string $length): void
    {
        $length = strtolower($length);

        foreach (self::$units as $unit) {
            $pos = strpos($length, $unit);
            if ($pos) {
                $this->value = floatval(substr($length, 0, $pos));
                $this->unit = $unit;
                return;
            }
        }

        $this->unit = '';
        $this->value = floatval($length);
    }

    
    public function getUnit(): string
    {
        return $this->unit;
    }

    
    public function toPixels(float $referenceSize = 11.0, float $dpi = 96.0): float
    {
        
        if (in_array($this->unit, ['em', 'rem', 'ex', 'ch'])) {
            return $this->value * $referenceSize;
        }

        
        if (in_array($this->unit, ['%', 'vw', 'vh', 'vmin', 'vmax'])) {
            return $this->value * ($referenceSize / 100);
        }

        
        if (in_array($this->unit, array_keys(static::$inchDivisions))) {
            $inchValue = $this->value * $dpi;
            $division = static::$inchDivisions[$this->unit];
            return $inchValue / $division;
        }

        return $this->value;
    }
}