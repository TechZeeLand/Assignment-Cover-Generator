<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Formal Side Rule": left-aligned header, a vertical accent rule down the
 * left edge and a thin rule under every section heading.
 */
final class FormalRuleTemplate extends SubmissionFormatTemplate
{
    private const BAR = 20.0;

    public function key(): string
    {
        return 'formal-rule';
    }

    public function label(): string
    {
        return 'Formal Side Rule';
    }

    public function description(): string
    {
        return 'Submitted to / Submitted by layout, left-aligned with a side rule.';
    }

    protected function sidePt(CoverData $d): float
    {
        return $d->showBorder ? 64.0 : 56.0;
    }

    protected function topPt(CoverData $d): float
    {
        return 56.0;
    }

    protected function bottomPt(CoverData $d): float
    {
        return 44.0;
    }

    protected function headerAlign(): string
    {
        return 'left';
    }

    protected function ruledHeadings(): bool
    {
        return true;
    }

    protected function decor(CoverData $d): string
    {
        return self::box(0.0, 0.0, self::BAR, self::PAGE_H, $d->accentColor)
            . self::box(0.0, self::BAR + 5.0, 2.0, self::PAGE_H, Color::tint($d->primaryColor, 0.45));
    }

    protected function thumbDecor(): string
    {
        return ThumbKit::r(0, 0, 8, 170, 'a') . ThumbKit::r(11, 0, 1, 170, 'pl');
    }

    public function thumbnailSvg(): string
    {
        $svg = $this->thumbDecor()
            . ThumbKit::r(22, 14, 28, 2.6, 's')
            . ThumbKit::r(22, 20, 76, 7, 'a')
            . ThumbKit::r(22, 31, 48, 3.2, 's')
            . ThumbKit::r(22, 37, 36, 2.4, 's');
        $svg .= ThumbKit::r(22, 48, 40, 4, 'p') . ThumbKit::r(22, 54, 80, 0.9, 'a')
            . ThumbKit::r(28, 58, 46, 3, 's') . ThumbKit::r(28, 63, 32, 2.2, 's');
        $svg .= ThumbKit::r(22, 72, 36, 4, 'p') . ThumbKit::r(22, 78, 80, 0.9, 'a') . ThumbKit::rows(26, 18, 50, 34, 82, 3, 6.2);
        $svg .= ThumbKit::r(22, 104, 40, 4, 'p') . ThumbKit::r(22, 110, 80, 0.9, 'a') . ThumbKit::rows(26, 24, 54, 34, 114, 2, 6.2);
        $svg .= ThumbKit::r(22, 130, 24, 2.6, 'p') . ThumbKit::r(50, 130, 36, 2.6, 'a')
            . ThumbKit::r(22, 140, 34, 2.6, 'p') . ThumbKit::r(22, 146, 80, 3, 's') . ThumbKit::r(22, 152, 56, 3, 's');
        return ThumbKit::svg($svg);
    }
}
