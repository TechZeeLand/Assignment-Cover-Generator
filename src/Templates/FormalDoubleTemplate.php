<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/** "Formal Double Line": the submission layout inside a refined thin double frame. */
final class FormalDoubleTemplate extends SubmissionFormatTemplate
{
    private const OUTER_INSET = 16.0;
    private const OUTER_THICK = 3.5;
    private const GAP = 5.0;
    private const INNER_THICK = 1.0;

    public function key(): string
    {
        return 'formal-double';
    }

    public function label(): string
    {
        return 'Formal Double Line';
    }

    public function description(): string
    {
        return 'Submitted to / Submitted by layout in a thin double-line frame.';
    }

    protected function sidePt(CoverData $d): float
    {
        return $d->showBorder ? 70.0 : 60.0;
    }

    protected function topPt(CoverData $d): float
    {
        return $d->showBorder ? 64.0 : 54.0;
    }

    protected function bottomPt(CoverData $d): float
    {
        return $d->showBorder ? self::OUTER_INSET + self::OUTER_THICK + self::GAP + self::INNER_THICK + 34.0 : 40.0;
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
