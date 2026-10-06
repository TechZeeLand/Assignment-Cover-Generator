<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Minimal Lines": left-aligned and quiet. A bold bar across the top and a
 * slim one at the bottom, hairline rules between rows, small upper-case
 * labels (no colons) over large values, and the date pinned bottom-left
 * above a hairline.
 *
 * With the border switched off the two bars are simply omitted.
 */
final class MinimalTemplate extends BaseTemplate
{
    private const SIDE = 56.0;
    private const TOP_BAR = 12.0;
    private const BOTTOM_BAR = 5.0;
    private const DATE_BOTTOM = 40.0;     // distance from the date's last line to the page bottom
    private const SECTION_SIZE = 15.0;

    public function key(): string
    {
        return 'minimal';
    }

    public function label(): string
    {
        return 'Minimal Lines';
    }

    public function description(): string
    {
        return 'Clean left-aligned layout with hairline rules and small-caps labels.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::r(0, 0, 120, 6, 'a')
            . ThumbKit::r(0, 166, 120, 4, 'a')
            . ThumbKit::r(82, 15, 24, 2.4, 's')
            . ThumbKit::r(14, 22, 64, 7, 'a')
            . ThumbKit::r(14, 33, 40, 3, 's');

        $svg .= ThumbKit::r(14, 46, 30, 3, 'p') . ThumbKit::r(14, 50.5, 92, 1.2, 'a');
        for ($i = 0; $i < 5; $i++) {
            $y = 56 + $i * 8;
            $svg .= ThumbKit::r(14, $y, 14, 2, 'p')
                . ThumbKit::r(46, $y - 0.5, $i % 2 ? 30 : 38, 3, 's')
                . ThumbKit::r(14, $y + 5, 92, 0.5, 'pl');
        }
        $svg .= ThumbKit::r(14, 100, 30, 3, 'p') . ThumbKit::r(14, 104.5, 92, 1.2, 'a');
        for ($i = 0; $i < 3; $i++) {
            $y = 110 + $i * 8;
            $svg .= ThumbKit::r(14, $y, 14, 2, 'p')
                . ThumbKit::r(46, $y - 0.5, $i % 2 ? 30 : 38, 3, 's')
                . ThumbKit::r(14, $y + 5, 92, 0.5, 'pl');
        }
        $svg .= ThumbKit::r(14, 137, 12, 2, 'p') . ThumbKit::r(14, 142, 70, 3.4, 's')
            . ThumbKit::r(14, 150, 92, 0.6, 'pl')
            . ThumbKit::r(14, 154, 20, 2, 'p') . ThumbKit::r(14, 158, 36, 3, 'a');

        return ThumbKit::svg($svg);
    }

    /** Height of the pinned date block: small label + date line(s). */
    private function dateBlockHeight(CoverData $d): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        $lines = self::estimateLines(
            self::unescape($d->submissionDateDisplay),
            self::PAGE_W - 2 * self::SIDE,
            $d->submissionFontSize
        );
        return 9.0 * 1.4 + 3.0 + $lines * $d->submissionFontSize * 1.32;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        $h = $this->dateBlockHeight($d);
        // block + the hairline above it (12pt) + breathing room
        return $h > 0 ? self::DATE_BOTTOM + $h + 12.0 + 12.0 : 44.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z    = self::sizes($d, $fontScale);
        $acc  = self::e($d->accentColor);
        $pri  = self::e($d->primaryColor);
        $fSec = self::e($d->secondaryFont);
        $fPri = self::e($d->primaryFont);
        $hair = self::e(Color::tint($d->primaryColor, 0.72));
        $side = self::SIDE;

        $padTop   = self::sp($d->showBorder ? 62.0 : 52.0, $spacingScale);
        $gap      = self::sp(26.0, $spacingScale);
        $topicGap = self::sp(22.0, $spacingScale);
        $rowPad   = self::sp(6.0, $spacingScale);
        $deptGap  = self::sp(18.0, $spacingScale);

        $labStudent = max(8.0, round($z['student'] * 0.5, 2));
        $labCourse  = max(8.0, round($z['course'] * 0.5, 2));
        $labTopic   = max(8.0, round($z['topic'] * 0.5, 2));

        $css = self::commonCss($d, $fontScale, $spacingScale, self::SECTION_SIZE);
        $css .= <<<CSS
div.pad { padding: {$padTop}pt {$side}pt 0 {$side}pt; }
.header-block { text-align: left; }
.bismillah { text-align: right; margin-bottom: 8pt !important; }
.dept-name { margin-bottom: {$deptGap}pt !important; }
table.grid td.section-h {
    letter-spacing: 2pt;
    text-transform: uppercase;
    padding: 0 0 5pt 0;
    border-bottom: 2pt solid {$acc};
}
table.grid td.label {
    width: 30%;
    font-family: {$fPri};
    letter-spacing: 1pt;
    text-transform: uppercase;
    white-space: normal;
    vertical-align: middle;
    padding: {$rowPad}pt 8pt {$rowPad}pt 0;
}
table.grid td.lab-student { font-size: {$labStudent}pt; }
table.grid td.lab-course { font-size: {$labCourse}pt; }
table.grid td.value { vertical-align: middle; padding: {$rowPad}pt 0 {$rowPad}pt 0; }
table.grid td.label, table.grid td.value { border-bottom: 0.75pt solid {$hair}; }
table.grid td.designation { padding: 3pt 0 0 0; }
td.topic-cell { padding: {$rowPad}pt 0 {$rowPad}pt 0; border-bottom: 0.75pt solid {$hair}; }
.topic-label {
    font-size: {$labTopic}pt;
    letter-spacing: 1pt;
    text-transform: uppercase;
    margin-bottom: 3pt !important;
}
.foot-label {
    margin: 0 0 3pt 0;
    font-size: 9pt;
    letter-spacing: 1.5pt;
    text-transform: uppercase;
    font-family: {$fPri};
    color: {$pri};
}

CSS;

        // ---- Bars + pinned date ----
        $deco = '';
        if ($d->showBorder) {
            $deco .= self::box(0.0, 0.0, self::PAGE_W, self::TOP_BAR, $d->accentColor);
            $deco .= self::box(self::PAGE_H - self::BOTTOM_BAR, 0.0, self::PAGE_W, self::BOTTOM_BAR, $d->accentColor);
        }
        if (self::hasSubmission($d)) {
            $h   = $this->dateBlockHeight($d);
            $top = self::PAGE_H - self::DATE_BOTTOM - $h;
            $deco .= self::box($top - 12.0, $side, self::PAGE_W - 2 * $side, 0.8, Color::tint($d->primaryColor, 0.6));
            $deco .= '<div style="position:fixed; left:' . $side . 'pt; top:' . round($top, 2) . 'pt; width:'
                . round(self::PAGE_W - 2 * $side, 2) . 'pt;">'
                . '<p class="foot-label">Submission Date</p>'
                . '<p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:' . $fSec . '; color:' . $acc . ';">'
                . self::e($d->submissionDateDisplay) . '</p></div>';
        }

        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'      => false,
            'gapPt'      => $gap,
            'firstGapPt' => 0.0,
        ]);

        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $topicGap . 'pt;">'
                . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="topic-cell">'
                . '<p class="topic-label">Topic</p>'
                . '<p class="topic-text">' . $d->topicHtml . '</p>'
                . '</td></tr></table></div>';
        }

        $body = $deco . "\n"
            . '<div class="pad">' . self::headerBlock($d) . $details . $topic . '</div>';

        return self::document($css, $body);
    }
}
