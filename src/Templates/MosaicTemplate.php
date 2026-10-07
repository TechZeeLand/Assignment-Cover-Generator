<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Mosaic": an asymmetric, geometric page. Clusters of flat squares in the
 * user's colours (and tints of them) grow out of the top-right and
 * bottom-left corners, the header is left-aligned with room kept clear of
 * the top cluster, sections are numbered 01 / 02 / 03, and the date sits
 * bottom-right.
 *
 * The squares are decoration only (no text is ever drawn on them), so no
 * colour pairing can go wrong.
 */
final class MosaicTemplate extends BaseTemplate
{
    private const SIDE = 50.0;
    private const SQ = 30.0;
    private const STEP = 34.0;
    private const CLUSTER_CLEAR = 140.0;   // header keeps this much clear on the right
    private const DATE_BOTTOM = 40.0;
    private const BOTTOM_CLEAR = 70.0;     // bottom-left cluster height + breathing room

    public function key(): string
    {
        return 'mosaic';
    }

    public function category(): string
    {
        return 'Modern';
    }

    public function label(): string
    {
        return 'Mosaic';
    }

    public function description(): string
    {
        return 'Asymmetric geometric squares in your colours, numbered sections.';
    }

    public function thumbnailSvg(): string
    {
        $svg = '';
        $tr = [[0, 0, 'a', 1], [1, 0, 'p', .45], [2, 0, 's', .3], [0, 1, 'p', 1], [1, 1, 'a', .3], [0, 2, 's', .45]];
        foreach ($tr as [$c, $r, $cls, $op]) {
            $svg .= '<rect x="' . (120 - 8 - $c * 8.6) . '" y="' . ($r * 8.6) . '" width="7.6" height="7.6" class="t-' . $cls
                . '" style="opacity:' . $op . '"/>';
        }
        $bl = [[0, 0, 's', 1], [1, 0, 'p', .45], [2, 0, 'a', 1], [0, 1, 'a', .3], [3, 0, 's', .3]];
        foreach ($bl as [$c, $r, $cls, $op]) {
            $svg .= '<rect x="' . ($c * 8.6) . '" y="' . (170 - 7.6 - $r * 8.6) . '" width="7.6" height="7.6" class="t-' . $cls
                . '" style="opacity:' . $op . '"/>';
        }
        $svg .= ThumbKit::r(14, 20, 20, 2.4, 's') . ThumbKit::r(14, 26, 62, 7, 'a') . ThumbKit::r(14, 36, 40, 3, 's')
            . ThumbKit::r(14, 46, 30, 3.4, 'p');
        $y = 56.0;
        foreach ([5, 3] as $k => $n) {
            $svg .= ThumbKit::r(14, $y, 8, 5, 's') . ThumbKit::r(25, $y + 0.5, 34, 4, 'p');
            $y += 9;
            for ($i = 0; $i < $n; $i++) {
                $svg .= ThumbKit::r(14, $y - 0.5, 1.2, 5, 'a') . ThumbKit::r(18, $y, 18, 2.4, 'p') . ThumbKit::r(46, $y, $i % 2 ? 28 : 36, 2.4, 's');
                $y += 6.4;
            }
            $y += 5;
        }
        $svg .= ThumbKit::r(14, 132, 14, 2.2, 'a') . ThumbKit::r(14, 137, 70, 3.6, 's')
            . ThumbKit::r(62, 156, 44, 3, 'a');
        return ThumbKit::svg($svg);
    }

    private function dateWidth(): float
    {
        return self::PAGE_W - self::SIDE - 170.0;   // starts right of the bottom-left cluster
    }

    private function dateHeight(CoverData $d): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        $lines = self::estimateLines('Submission Date: ' . self::unescape($d->submissionDateDisplay), $this->dateWidth(), $d->submissionFontSize);
        return $lines * $d->submissionFontSize * 1.32 + 2.0;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        // The bottom-left cluster is ~64pt tall; the date (if any) sits beside it.
        return max(self::BOTTOM_CLEAR, $this->dateHeight($d) + self::DATE_BOTTOM + 14.0);
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z = self::sizes($d, $fontScale);
        $acc = self::e($d->accentColor);
        $side = self::SIDE;
        $clear = self::CLUSTER_CLEAR;

        $padTop = self::sp(54.0, $spacingScale);
        $gap    = self::sp(24.0, $spacingScale);
        $rowPad = self::sp(2.0, $spacingScale);
        $sHead  = self::fs(24.0, $fontScale);

        $css = self::commonCss($d, $fontScale, $spacingScale, 24.0);
        $css .= <<<CSS
div.pad { padding: {$padTop}pt {$side}pt 0 {$side}pt; }
div.head-wrap { padding-right: {$clear}pt; }
.dept-name { margin-bottom: 0 !important; }
table.grid td.section-h { font-size: {$sHead}pt; padding-bottom: 6pt; }
table.grid td.label { padding: {$rowPad}pt 0 {$rowPad}pt 12pt; }
table.grid td.colon { padding: {$rowPad}pt 6pt; }
table.grid td.value { padding: {$rowPad}pt 0 {$rowPad}pt 0; }
.topic-label { font-size: 12pt; letter-spacing: 3pt; margin-bottom: 3pt !important; }

CSS;

        // ---- decoration ----
        $deco = '';
        if ($d->showBorder) {
            $a = $d->accentColor;
            $p = $d->primaryColor;
            $s = $d->secondaryColor;
            $sq = self::SQ;
            $st = self::STEP;
            $W = self::PAGE_W;
            $H = self::PAGE_H;
            $tr = [
                [0, 0, $a], [1, 0, Color::tint($p, 0.55)], [2, 0, Color::tint($s, 0.7)],
                [0, 1, $p], [1, 1, Color::tint($a, 0.75)], [0, 2, Color::tint($s, 0.4)],
            ];
            foreach ($tr as [$c, $r, $color]) {
                $deco .= "\n    " . self::box($r * $st, $W - $sq - $c * $st, $sq, $sq, $color);
            }
            $bl = [
                [0, 0, $s], [1, 0, Color::tint($p, 0.55)], [2, 0, $a], [3, 0, Color::tint($s, 0.7)], [0, 1, Color::tint($a, 0.7)],
            ];
            foreach ($bl as [$c, $r, $color]) {
                $deco .= "\n    " . self::box($H - $sq - $r * $st, $c * $st, $sq, $sq, $color);
            }
        }

        $date = '';
        if (self::hasSubmission($d)) {
            $h = $this->dateHeight($d);
            $w = $this->dateWidth();
            $left = self::PAGE_W - $side - $w;
            $date = '<div style="position:fixed; left:' . round($left, 2) . 'pt; top:' . round(self::PAGE_H - self::DATE_BOTTOM - $h, 2)
                . 'pt; width:' . round($w, 2) . 'pt; text-align:right;"><p style="margin:0; font-size:' . $z['submission']
                . 'pt; font-family:' . self::e($d->secondaryFont) . '; color:' . $acc . ';"><b>Submission Date:</b> '
                . self::e($d->submissionDateDisplay) . '</p></div>';
        }

        // ---- content ----
        $head = '<div class="head-wrap">' . self::headerBlock($d, ['scale' => $fontScale, 'align' => 'left'])
            . self::titleBlock($d, [
                'scale' => $fontScale, 'color' => Color::ensureContrast($d->accentColor, '#ffffff', 3.0), 'align' => 'left',
                'before' => 12.0, 'after' => 0.0, 'size' => 17.0, 'spacing' => 4.0,
            ]) . '</div>';

        $num = static fn (string $n, string $color, string $text): string =>
            '<span style="color:' . htmlspecialchars($color, ENT_QUOTES) . ';">' . $n . '</span>&nbsp;&nbsp;' . $text;
        $suffix = $d->headerSuffix !== '' ? ' ' . self::e($d->headerSuffix) : '';
        $sec = $d->secondaryColor;
        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'      => true,
            'gapPt'      => $gap,
            'firstGapPt' => $gap,
            'lStyle'     => 'border-left:3pt solid ' . $acc . ';',
            'titles'     => [
                'student' => $num('01', $sec, 'Student Details' . $suffix),
                'course'  => $num('02', $sec, 'Course Details' . $suffix),
                'group'   => $num('03', $sec, 'Group Details' . $suffix),
            ],
        ]);

        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $gap . 'pt;"><p class="topic-label" style="color:' . self::e(Color::ensureContrast($d->accentColor, '#ffffff', 3.0))
                . ';">TOPIC</p><p class="topic-text">' . $d->topicHtml . '</p></div>';
        }

        $body = $deco . "\n" . $date . "\n" . '<div class="pad">' . $head . $details . $topic . '</div>';
        return self::document($css, $body);
    }
}
