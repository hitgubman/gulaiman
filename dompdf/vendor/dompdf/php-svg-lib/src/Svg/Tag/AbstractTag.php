<?php


namespace Svg\Tag;

use Svg\CssLength;
use Svg\Document;
use Svg\Style;

abstract class AbstractTag
{
    
    protected $document;

    public $tagName;

    
    protected $style;

    protected $attributes = array();

    protected $hasShape = true;

    
    protected $children = array();

    public function __construct(Document $document, $tagName)
    {
        $this->document = $document;
        $this->tagName = $tagName;
    }

    public function getDocument(){
        return $this->document;
    }

    
    public function getParentGroup() {
        $stack = $this->getDocument()->getStack();
        for ($i = count($stack)-2; $i >= 0; $i--) {
            $tag = $stack[$i];

            if ($tag instanceof Group || $tag instanceof Document) {
                return $tag;
            }
        }

        return null;
    }

    public function handle($attributes)
    {
        $this->attributes = $attributes;

        if (!$this->getDocument()->inDefs || $this instanceof StyleTag) {
            $this->before($attributes);
            $this->start($attributes);
        }
    }

    public function handleEnd()
    {
        if (!$this->getDocument()->inDefs || $this instanceof StyleTag) {
            $this->end();
            $this->after();
        }
    }

    protected function before($attributes)
    {
    }

    protected function start($attributes)
    {
    }

    protected function end()
    {
    }

    protected function after()
    {
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    protected function setStyle(Style $style)
    {
        $this->style = $style;

        if ($style->display === "none") {
            $this->hasShape = false;
        }
    }

    
    public function getStyle()
    {
        return $this->style;
    }

    
    protected function makeStyle($attributes) {
        $style = new Style($this->document);
        $style->inherit($this);
        $style->fromStyleSheets($this, $attributes);
        $style->fromAttributes($attributes);

        return $style;
    }

    protected function applyTransform($attributes)
    {

        if (isset($attributes["transform"])) {
            $surface = $this->document->getSurface();

            $transform = $attributes["transform"];

            $matches = array();
            preg_match_all(
                '/(matrix|translate|scale|rotate|skew|skewX|skewY)\((.*?)\)/is',
                $transform,
                $matches,
                PREG_SET_ORDER
            );

            $transformations = array();
            foreach ($matches as $match) {
                $arguments = preg_split('/[ ,]+/', $match[2]);
                array_unshift($arguments, $match[1]);
                $transformations[] = $arguments;
            }

            foreach ($transformations as $t) {
                switch ($t[0]) {
                    case "matrix":
                        $surface->transform($t[1], $t[2], $t[3], $t[4], $t[5], $t[6]);
                        break;

                    case "translate":
                        $surface->translate($t[1], isset($t[2]) ? $t[2] : 0);
                        break;

                    case "scale":
                        $surface->scale($t[1], isset($t[2]) ? $t[2] : $t[1]);
                        break;

                    case "rotate":
                        if (isset($t[2])) {
                            $t[3] = isset($t[3]) ? $t[3] : 0;
                            $surface->translate($t[2], $t[3]);
                            $surface->rotate($t[1]);
                            $surface->translate(-$t[2], -$t[3]);
                        } else {
                            $surface->rotate($t[1]);
                        }
                        break;

                    case "skewX":
                        $tan_x = tan(deg2rad($t[1]));
                        $surface->transform(1, 0, $tan_x, 1, 0, 0);
                        break;

                    case "skewY":
                        $tan_y = tan(deg2rad($t[1]));
                        $surface->transform(1, $tan_y, 0, 1, 0, 0);
                        break;
                }
            }
        }
    }

    
    protected function applyViewbox($attributes) {
        if (!isset($attributes["viewbox"])) {
            return;
        }

        $surface = $this->document->getSurface();
        $viewBox = preg_split('/[\s,]+/is', trim($attributes['viewbox']));
        if (count($viewBox) != 4) {
            return;
        }

        
        

        
        [$vbX, $vbY, $vbWidth, $vbHeight] = $viewBox;

        if ($vbWidth < 0 || $vbHeight < 0) {
            return;
        }

        
        
        

        
        $eX = $attributes["x"] ?? 0;
        $eY = $attributes["y"] ?? 0;
        $eWidth = $attributes["width"] ?? $this->document->getWidth();
        $eHeight = $attributes["height"] ?? $this->document->getHeight();

        
        $preserveAspectRatio = explode(" ", $attributes["preserveAspectRatio"] ?? "xMidYMid meet");
        $align = $preserveAspectRatio[0];

        
        $meetOrSlice = $meetOrSlice ?? "meet";

        
        $scaleX = $vbWidth == 0 ? 0 : ($eWidth / $vbWidth);

        
        $scaleY = $vbHeight == 0 ? 0 : ($eHeight / $vbHeight);

        
        if ($align !== "none" && $meetOrSlice === "meet") {
            $scaleX = min($scaleX, $scaleY);
            $scaleY = min($scaleX, $scaleY);
        }

        
        elseif ($align !== "none" && $meetOrSlice === "slice") {
            $scaleX = max($scaleX, $scaleY);
            $scaleY = max($scaleX, $scaleY);
        }

        
        $translateX = $eX - ($vbX * $scaleX);

        
        $translateY = $eY - ($vbY * $scaleY);

        
        if (strpos($align, "xMid") !== false) {
            $translateX += ($eWidth - $vbWidth * $scaleX) / 2;
        }

        
        if (strpos($align, "xMax") !== false) {
            $translateX += ($eWidth - $vbWidth * $scaleX);
        }

        
        if (strpos($align, "yMid") !== false) {
            $translateX += ($eHeight - $vbHeight * $scaleY) / 2;
        }

        
        if (strpos($align, "yMid") !== false) {
            $translateX += ($eHeight - $vbHeight * $scaleY);
        }

        $surface->translate($translateX, $translateY);
        $surface->scale($scaleX, $scaleY);
    }

    
    protected function convertSize(string $size, float $pxReference): float
    {
        $length = new CssLength($size);
        $reference = $pxReference;
        $defaultFontSize = 12;

        switch ($length->getUnit()) {
            case "em":
                $reference = $this->style->fontSize ?? $defaultFontSize;
                break;
            case "rem":
                $reference = $this->document->style->fontSize ?? $defaultFontSize;
                break;
            case "ex":
            case "ch":
                $emRef = $this->style->fontSize ?? $defaultFontSize;
                $reference = $emRef * 0.5;
                break;
            case "vw":
                $reference = $this->getDocument()->getWidth();
                break;
            case "vh":
                $reference = $this->getDocument()->getHeight();
                break;
            case "vmin":
                $reference = min($this->getDocument()->getHeight(), $this->getDocument()->getWidth());
                break;
            case "vmax":
                $reference = max($this->getDocument()->getHeight(), $this->getDocument()->getWidth());
                break;
        }

        return (new CssLength($size))->toPixels($reference);
    }
} 
