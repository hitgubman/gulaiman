<?php


namespace Svg\Tag;

use Svg\Style;

class Symbol extends AbstractTag
{
    protected function before($attributes)
    {
        $surface = $this->document->getSurface();

        $surface->save();

        $style = $this->makeStyle($attributes);

        $this->setStyle($style);
        $surface->setStyle($style);

        $this->applyViewbox($attributes);
        $this->applyTransform($attributes);
    }

    protected function after()
    {
        $this->document->getSurface()->restore();
    }
}