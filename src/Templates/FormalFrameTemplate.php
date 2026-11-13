<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/** "Formal Frame": the submission layout inside a bold single frame, centered header. */
final class FormalFrameTemplate extends SubmissionFormatTemplate
{
    private const INSET = 18.0;
    private const THICK = 9.0;

    public function key(): string
    {
        return 'formal';
    }

    public function label(): string
    {
        return 'Formal Frame';
    }

    public function description(): string
    {
        return 'Submitted to / Submitted by layout inside a bold frame.';
    }

    protected function sidePt(CoverData $d): float
    {
        return $d->showBorder ? 68.0 : 60.0;
    }

    protected function topPt(CoverData $d): float
    {
        return $d->showBorder ? 62.0 : 54.0;
    }

    protected function bottomPt(CoverData $d): float
    {
        return $d->showBorder ? self::INSET + self::THICK + 28.0 : 40.0;
    }

    protected function decor(CoverData $d): string
    {
        return self::frameBars(self::INSET, self::THICK, $d->accentColor);
    }

    protected function thumbDecor(): string
    {
        return ThumbKit::frame(4, 3.4, 'a');
    }
}
