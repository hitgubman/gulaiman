<?php


namespace Svg\Tag;

use Svg\Style;

class Shape extends AbstractTag
{
    protected function before($attributes)
    {
        $surface = $this->document->getSurface();

        $surface->save();

        $style = $this->makeStyle($attributes);

        $this->setStyle($style);
        $surface->setStyle($style);

        $this->applyTransform($attributes);
    }

    protected function after()
    {
        $surface = $this->document->getSurface();

        if ($this->hasShape) {
            $style = $surface->getStyle();

            $fill   = $style->fill   && is_array($style->fill);
            $stroke = $style->stroke && is_array($style->stroke);

            if ($fill) {
                if ($stroke) {
                    $surface->fillStroke(false);
                } else {







                    $surface->fill();
                }
            }
            elseif ($stroke) {
                $surface->stroke(false);
            }
            else {
                $surface->endPath();
            }
        }

        $surface->restore();
    }
} 