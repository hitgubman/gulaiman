<?php


namespace Svg\Surface;

use Svg\Style;


interface SurfaceInterface
{
    public function save();

    public function restore();

    
    public function scale($x, $y);

    public function rotate($angle);

    public function translate($x, $y);

    public function transform($a, $b, $c, $d, $e, $f);

    
    public function beginPath();

    public function closePath();

    public function fill();

    public function stroke(bool $close = false);

    public function endPath();

    public function fillStroke(bool $close = false);

    public function clip();

    
    public function fillText($text, $x, $y, $maxWidth = null);

    public function strokeText($text, $x, $y, $maxWidth = null);

    public function measureText($text);

    
    public function drawImage($image, $sx, $sy, $sw = null, $sh = null, $dx = null, $dy = null, $dw = null, $dh = null);

    
    public function lineTo($x, $y);

    public function moveTo($x, $y);

    public function quadraticCurveTo($cpx, $cpy, $x, $y);

    public function bezierCurveTo($cp1x, $cp1y, $cp2x, $cp2y, $x, $y);

    public function arcTo($x1, $y1, $x2, $y2, $radius);

    public function circle($x, $y, $radius);

    public function arc($x, $y, $radius, $startAngle, $endAngle, $anticlockwise = false);

    public function ellipse($x, $y, $radiusX, $radiusY, $rotation, $startAngle, $endAngle, $anticlockwise);

    
    public function rect($x, $y, $w, $h, $rx = 0, $ry = 0);

    public function fillRect($x, $y, $w, $h);

    public function strokeRect($x, $y, $w, $h);

    public function setStyle(Style $style);

    
    public function getStyle();

    public function setFont($family, $style, $weight);
}
