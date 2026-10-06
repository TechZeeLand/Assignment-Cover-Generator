<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * Shared layout for the two fully-centered designs (Double Frame and Corner
 * Brackets). They differ only in the page decoration - see decor().
 *
 * Layout: centered header, a short divider rule, centered section headings
 * with a hairline underneath, a label | colon | value table whose colons sit
 * on a common centre axis, the topic between two hairlines, and the
 * submission date pinned (centered) near the bottom.
 */
abstract class CenteredTemplate extends BaseTemplate
{
    protected const SIDE = 56.0;
    protected const TOP = 54.0;
    protected const DATE_BOTTOM = 54.0;   // distance from the date's last line to the page bottom
    private const SECTION_SIZE = 26.0;

    /** Page decoration (fixed boxes), drawn only when "Show decorative border" is on. */
    abstract protected function decor(CoverData $d): string;

    /** Thumbnail shapes for the decoration. */
    abstract protected function thumbDecor(): string;

    public function thumbnailSvg(): string
    {
        $svg = $this->thumbDecor()
            . ThumbKit::r(46, 18, 28, 2.8, 's')
            . ThumbKit::r(20, 24, 80, 7, 'a')
            . ThumbKit::r(36, 35, 48, 3.2, 's')
            . ThumbKit::r(46, 43, 28, 1, 'a');

        // centered section headers, colons on a common axis
        $svg .= ThumbKit::r(38, 51, 44, 4.5, 'p') . ThumbKit::r(24, 58, 72, 0.7, 'pl');
        for ($i = 0; $i < 5; $i++) {
            $y = 62 + $i * 6.3;
            $svg .= ThumbKit::r(60 - 20, $y, 20, 2.4, 'p') . ThumbKit::r(64, $y, $i % 2 ? 24 : 30, 2.4, 's');
        }
        $svg .= ThumbKit::r(38, 98, 44, 4.5, 'p') . ThumbKit::r(24, 105, 72, 0.7, 'pl');
        for ($i = 0; $i < 3; $i++) {
            $y = 109 + $i * 6.3;
            $svg .= ThumbKit::r(60 - 20, $y, 20, 2.4, 'p') . ThumbKit::r(64, $y, $i % 2 ? 24 : 30, 2.4, 's');
        }

        // topic between hairlines + date
        $svg .= ThumbKit::r(24, 128, 72, 0.8, 'pl')
            . ThumbKit::r(52, 132, 16, 2.4, 'a')
            . ThumbKit::r(32, 138, 56, 3.4, 's')
            . ThumbKit::r(24, 146, 72, 0.8, 'pl')
            . ThumbKit::r(30, 154, 60, 3, 'a');

        return ThumbKit::svg($svg);
    }

    private function dateBlockHeight(CoverData $d): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        $lines = self::submissionLines($d, self::PAGE_W - 2 * self::SIDE);
        return $lines * $d->submissionFontSize * 1.32 + 4.0;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        $h = $this->dateBlockHeight($d);
        return $h > 0 ? self::DATE_BOTTOM + $h + 14.0 : 56.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z    = self::sizes($d, $fontScale);
        $acc  = self::e($d->accentColor);
        $rule = self::e(Color::tint($d->primaryColor, 0.5));
        $fSec = self::e($d->secondaryFont);
        $side = self::SIDE;
        $top  = self::sp(self::TOP, $spacingScale);

        $dividerGap = self::sp(12.0, $spacingScale);
        $headGap    = self::sp(22.0, $spacingScale);
        $topicGap   = self::sp(24.0, $spacingScale);
        $rowPad     = self::sp(1.5, $spacingScale);
        $topicPad   = self::sp(9.0, $spacingScale);

        $css = self::commonCss($d, $fontScale, $spacingScale, self::SECTION_SIZE);
        $css .= <<<CSS
.dept-name { margin-bottom: 0 !important; }
div.pad { padding: {$top}pt {$side}pt 0 {$side}pt; }
table.grid td.section-h {
    text-align: center;
    padding: 0 0 5pt 0;
    border-bottom: 1pt solid {$rule};
}
table.grid td.label { width: 38%; text-align: right; padding: {$rowPad}pt 0 {$rowPad}pt 0; white-space: normal; }
table.grid td.colon { width: 3%; text-align: center; padding: {$rowPad}pt 0 {$rowPad}pt 0; }
table.grid td.value { width: 59%; padding: {$rowPad}pt 0 {$rowPad}pt 6pt; }
table.grid td.designation { padding: 0 0 2pt 6pt; }
td.topic-cell {
    text-align: center;
    padding: {$topicPad}pt 10pt {$topicPad}pt 10pt;
    border-top: 1pt solid {$rule};
    border-bottom: 1pt solid {$rule};
}
.topic-label {
    font-size: 13pt;
    letter-spacing: 3pt;
    margin-bottom: 3pt !important;
    color: {$acc};
}

CSS;

        // ---- Decoration + pinned date ----
        $decor = $d->showBorder ? $this->decor($d) : '';

        $date = '';
        if (self::hasSubmission($d)) {
            $h   = $this->dateBlockHeight($d);
            $dateTop = self::PAGE_H - self::DATE_BOTTOM - $h;
            $date = '<div style="position:fixed; left:' . $side . 'pt; top:' . round($dateTop, 2) . 'pt; width:'
                . round(self::PAGE_W - 2 * $side, 2) . 'pt; text-align:center;">'
                . '<p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:' . $fSec . '; color:' . $acc . ';">'
                . '<b>Submission Date:</b> ' . self::e($d->submissionDateDisplay) . '</p></div>';
        }

        // ---- Header + divider ----
        $divider = '<div style="margin-top:' . $dividerGap . 'pt; margin-bottom:' . $dividerGap . 'pt;">'
            . '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td width="35%" style="font-size:1pt;">&nbsp;</td>'
            . '<td width="30%" style="border-top:2pt solid ' . $acc . '; font-size:1pt; line-height:1pt;">&nbsp;</td>'
            . '<td width="35%" style="font-size:1pt;">&nbsp;</td>'
            . '</tr></table></div>';

        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'      => true,
            'gapPt'      => $headGap,
            'firstGapPt' => 0.0,
        ]);

        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $topicGap . 'pt;">'
                . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="topic-cell">'
                . '<p class="topic-label">TOPIC</p>'
                . '<p class="topic-text">' . $d->topicHtml . '</p>'
                . '</td></tr></table></div>';
        }

        $body = $decor . "\n" . $date . "\n"
            . '<div class="pad">' . self::headerBlock($d) . $divider . $details . $topic . '</div>';

        return self::document($css, $body);
    }
}
