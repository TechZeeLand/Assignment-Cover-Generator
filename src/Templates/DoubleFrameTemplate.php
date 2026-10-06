<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/** Elegant thin double-line frame (outer line thicker than the inner one). */
final class DoubleFrameTemplate extends CenteredTemplate
{
    private const OUTER_INSET = 16.0;
    private const OUTER_THICK = 3.5;
    private const GAP = 5.0;
    private const INNER_THICK = 1.0;

    public function key(): string
    {
        return 'double';
    }

    public function label(): string
    {
        return 'Double Frame';
    }

    public function description(): string
    {
        return 'Elegant double-line frame with a fully centered layout.';
    }

    protected function decor(CoverData $d): string
    {
        return self::frameBars(self::OUTER_INSET, self::OUTER_THICK, $d->accentColor)
            . self::frameBars(self::OUTER_INSET + self::OUTER_THICK + self::GAP, self::INNER_THICK, $d->accentColor);
    }

    protected function thumbDecor(): string
    {
        return ThumbKit::frame(4, 2.2, 'a') . ThumbKit::frame(8.5, 0.8, 'a');
    }
}
