<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Formal Header Band": the submission layout under a full-width coloured
 * header band. Text on the band is derived from the band colour (and set
 * inline), so it is always readable; logos sit on a white plate. With the
 * decorative border switched off the band becomes plain text.
 */
final class FormalBandTemplate extends SubmissionFormatTemplate
{
    public function key(): string
    {
        return 'formal-band';
    }

    public function label(): string
    {
        return 'Formal Header Band';
    }

    public function description(): string
    {
        return 'Submitted to / Submitted by layout under a coloured header band.';
    }

    protected function sidePt(CoverData $d): float
    {
        return 64.0;
    }

    protected function topPt(CoverData $d): float
    {
        return 30.0;
    }

    protected function bottomPt(CoverData $d): float
    {
        return $d->showBorder ? 36.0 : 40.0;
    }

    protected function headerInsidePad(): bool
    {
        return false;
    }

    protected function decor(CoverData $d): string
    {
        return self::box(self::PAGE_H - 9.0, 0.0, self::PAGE_W, 9.0, $d->accentColor)
            . self::box(self::PAGE_H - 13.0, 0.0, self::PAGE_W, 2.0, $d->primaryColor);
    }

    protected function thumbDecor(): string
    {
        return ThumbKit::r(0, 0, 120, 44, 'a') . ThumbKit::r(0, 44, 120, 2, 'p') . ThumbKit::r(0, 164, 120, 6, 'a');
    }

    public function thumbnailSvg(): string
    {
        // Same wireframe, but the header text sits on the band (white shapes).
        $svg = $this->thumbDecor()
            . ThumbKit::r(46, 9, 28, 2.6, 'w')
            . ThumbKit::r(20, 15, 80, 7, 'w')
            . ThumbKit::r(36, 26, 48, 3.2, 'w')
            . ThumbKit::r(42, 32, 36, 2.4, 'w');
        $svg .= ThumbKit::r(16, 54, 44, 4, 'p') . ThumbKit::r(22, 61, 46, 3, 's') . ThumbKit::r(22, 66, 32, 2.2, 's');
        $svg .= ThumbKit::r(16, 76, 40, 4, 'p') . ThumbKit::rows(20, 18, 44, 34, 83, 3, 6.2);
        $svg .= ThumbKit::r(16, 105, 44, 4, 'p') . ThumbKit::rows(20, 24, 50, 34, 112, 2, 6.2);
        $svg .= ThumbKit::r(16, 130, 24, 2.6, 'p') . ThumbKit::r(44, 130, 36, 2.6, 'a')
            . ThumbKit::r(16, 140, 34, 2.6, 'p') . ThumbKit::r(16, 146, 82, 3, 's') . ThumbKit::r(16, 152, 58, 3, 's');
        return ThumbKit::svg($svg);
    }

    protected function header(CoverData $d, float $fontScale): string
    {
        $side = $this->sidePt($d);
        if (!$d->showBorder) {
            return '<div style="padding:50pt ' . $side . 'pt 0 ' . $side . 'pt;">' . parent::header($d, $fontScale) . '</div>';
        }

        $onAcc    = Color::contrastText($d->accentColor);
        $onAccDim = Color::mix($onAcc, $d->accentColor, 0.18);
        $inner = self::headerBlock($d, [
            'scale'  => $fontScale,
            'colors' => ['bismillah' => $onAcc, 'versity' => $onAcc, 'faculty' => $onAccDim, 'dept' => $onAccDim],
            'plate'  => '#ffffff',
        ]) . $this->programLine($d, 'center', $onAccDim);

        return '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="background-color:' . self::e($d->accentColor) . '; padding:40pt ' . $side . 'pt 24pt ' . $side . 'pt; text-align:center;">'
            . $inner . '</td></tr></table>'
            . '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="background-color:' . self::e($d->primaryColor) . '; height:4pt; font-size:1pt; line-height:1pt;">&nbsp;</td></tr></table>';
    }
}
