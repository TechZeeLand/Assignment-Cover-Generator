<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/** Bold L-shaped corner brackets around a hairline frame; light and airy. */
final class CornersTemplate extends CenteredTemplate
{
    private const BRACKET_INSET = 16.0;
    private const BRACKET_THICK = 7.0;
    private const BRACKET_LEN = 68.0;
    private const HAIRLINE_INSET = 24.0;
    private const HAIRLINE_THICK = 0.9;

    public function key(): string
    {
        return 'corners';
    }

    public function label(): string
    {
        return 'Corner Brackets';
    }

    public function description(): string
    {
        return 'Light, airy page with bold corner brackets and a centered layout.';
    }

    protected function decor(CoverData $d): string
    {
        $c = $d->accentColor;
        $i = self::BRACKET_INSET;
        $t = self::BRACKET_THICK;
        $l = self::BRACKET_LEN;
        $right  = self::PAGE_W - $i - $l;       // x where the right-hand horizontal arms start
        $rightV = self::PAGE_W - $i - $t;       // x of the right-hand vertical arms
        $bottomH = self::PAGE_H - $i - $t;      // y of the bottom horizontal arms
        $bottomV = self::PAGE_H - $i - $l;      // y where the bottom vertical arms start

        return self::frameBars(self::HAIRLINE_INSET, self::HAIRLINE_THICK, $c)
            // top-left
            . "\n    " . self::box($i, $i, $l, $t, $c)
            . "\n    " . self::box($i, $i, $t, $l, $c)
            // top-right
            . "\n    " . self::box($i, $right, $l, $t, $c)
            . "\n    " . self::box($i, $rightV, $t, $l, $c)
            // bottom-left
            . "\n    " . self::box($bottomH, $i, $l, $t, $c)
            . "\n    " . self::box($bottomV, $i, $t, $l, $c)
            // bottom-right
            . "\n    " . self::box($bottomH, $right, $l, $t, $c)
            . "\n    " . self::box($bottomV, $rightV, $t, $l, $c);
    }

    protected function thumbDecor(): string
    {
        $o = ThumbKit::frame(9, 0.6, 'al');
        $arm = 17.0;
        $t = 3.2;
        $i = 5.0;
        return $o
            . ThumbKit::r($i, $i, $arm, $t, 'a') . ThumbKit::r($i, $i, $t, $arm, 'a')
            . ThumbKit::r(120 - $i - $arm, $i, $arm, $t, 'a') . ThumbKit::r(120 - $i - $t, $i, $t, $arm, 'a')
            . ThumbKit::r($i, 170 - $i - $t, $arm, $t, 'a') . ThumbKit::r($i, 170 - $i - $arm, $t, $arm, 'a')
            . ThumbKit::r(120 - $i - $arm, 170 - $i - $t, $arm, $t, 'a') . ThumbKit::r(120 - $i - $t, 170 - $i - $arm, $t, $arm, 'a');
    }
}
