<?php


namespace Svg\Tag;

class Polyline extends Shape
{
    public function start($attributes)
    {
        $tmp = array();
        preg_match_all('/([\-]*[0-9\.]+)/', $attributes['points'], $tmp, PREG_PATTERN_ORDER);

        $points = $tmp[0];
        $count = count($points);

        if ($count < 4) {
            
            return;
        }

        $surface = $this->document->getSurface();
        list($x, $y) = $points;
        $surface->moveTo($x, $y);

        for ($i = 2; $i < $count; $i += 2) {
            if ($i + 1 === $count) {
                
                continue;
            }
            $x = $points[$i];
            $y = $points[$i + 1];
            $surface->lineTo($x, $y);
        }
    }
} 
