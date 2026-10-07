<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/**
 * "Heritage": a formal, certificate-style page. A triple-line frame with
 * solid corner blocks, everything centered, and a row of three small
 * squares as the ornament under the header.
 */
final class HeritageTemplate extends CenteredTemplate
{
    private const OUTER_INSET = 14.0;
    private const OUTER_THICK = 5.0;
    private const CORNER = 15.0;

    public function key(): string
    {
        return 'heritage';
    }

    public function category(): string
    {
        return 'Professional';
    }

    public function label(): string
    {
        return 'Heritage';
    }

    public function description(): string
    {
        return 'Formal certificate-style triple frame with corner blocks and an ornament.';
    }

    protected function decor(CoverData $d): string
    {
        $c = $d->accentColor;
        $i = self::OUTER_INSET;
        $mid = $i + self::OUTER_THICK + 3.0;          // second line
        $inner = $mid + 1.2 + 3.0;                    // third (hairline)
        $k = self::CORNER;
        $right = self::PAGE_W - $i - $k;
        $bottom = self::PAGE_H - $i - $k;

        return self::frameBars($i, self::OUTER_THICK, $c)
            . self::frameBars($mid, 1.2, $c)
            . self::frameBars($inner, 0.6, $c)
            . "\n    " . self::box($i, $i, $k, $k, $c)
            . "\n    " . self::box($i, $right, $k, $k, $c)
            . "\n    " . self::box($bottom, $i, $k, $k, $c)
            . "\n    " . self::box($bottom, $right, $k, $k, $c);
    }

    protected function thumbDecor(): string
    {
        return ThumbKit::frame(4, 2, 'a') . ThumbKit::frame(7.6, 0.8, 'a') . ThumbKit::frame(9.8, 0.4, 'a')
            . ThumbKit::r(4, 4, 6, 6, 'a') . ThumbKit::r(110, 4, 6, 6, 'a')
            . ThumbKit::r(4, 160, 6, 6, 'a') . ThumbKit::r(110, 160, 6, 6, 'a');
    }

    /** Three small squares (accent / primary / accent) instead of the plain rule. */
    protected function divider(CoverData $d, float $gap): string
    {
        $sq = static fn (string $color): string => '<td width="7" style="background-color:' . htmlspecialchars($color, ENT_QUOTES)
            . '; font-size:1pt; line-height:1pt; height:7pt;">&nbsp;</td>';
        $sp = '<td width="9" style="font-size:1pt;">&nbsp;</td>';
        return '<div style="margin-top:' . $gap . 'pt; margin-bottom:' . $gap . 'pt;">'
            . '<table align="center" cellpadding="0" cellspacing="0"><tr>'
            . $sq($d->accentColor) . $sp . $sq($d->primaryColor) . $sp . $sq($d->accentColor)
            . '</tr></table></div>';
    }
}
