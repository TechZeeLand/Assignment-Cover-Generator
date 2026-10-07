<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Side Panel": a solid colour panel down the left edge (with a thin primary
 * stripe next to it) that carries the submission date at its foot; all other
 * content sits to the right.
 *
 * The panel is built from two stacked, same-coloured fixed boxes - a tall one
 * with no text and a short one at the bottom that holds the date - so no text
 * ever depends on how mPDF orders overlapping fixed elements (the two boxes
 * overlap by 1pt, in an area that never contains text).
 *
 * With the border switched off there is no panel: content is simply
 * left-aligned with the date pinned bottom-left in the accent colour.
 */
final class SidebarTemplate extends BaseTemplate
{
    private const PANEL_W = 124.0;
    private const STRIPE_W = 5.0;
    private const PANEL_PAD = 14.0;       // horizontal padding of the date text inside the panel
    private const CONTENT_GAP = 24.0;     // panel stripe -> content
    private const RIGHT = 36.0;
    private const SECTION_SIZE = 26.0;
    private const LABEL_SIZE = 10.0;

    public function key(): string
    {
        return 'sidebar';
    }

    public function category(): string
    {
        return 'Modern';
    }

    public function label(): string
    {
        return 'Side Panel';
    }

    public function description(): string
    {
        return 'Solid colour panel down the left with the date; content on the right.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::r(0, 0, 30, 170, 'a')
            . ThumbKit::r(30, 0, 2, 170, 'p')
            . ThumbKit::r(5, 138, 18, 2, 'w')
            . ThumbKit::r(5, 143, 20, 3, 'w')
            . ThumbKit::r(5, 149, 13, 3, 'w');

        $svg .= ThumbKit::r(66, 16, 22, 2.6, 's')
            . ThumbKit::r(40, 22, 68, 6.5, 'a')
            . ThumbKit::r(56, 32, 40, 3, 's')
            . ThumbKit::r(40, 46, 38, 4.5, 'p')
            . ThumbKit::r(40, 53, 68, 0.8, 'a')
            . ThumbKit::rows(42, 17, 64, 38, 58, 5)
            . ThumbKit::r(40, 94, 38, 4.5, 'p')
            . ThumbKit::r(40, 101, 68, 0.8, 'a')
            . ThumbKit::rows(42, 17, 64, 38, 106, 3)
            . ThumbKit::r(40, 130, 68, 1.2, 'a')
            . ThumbKit::r(42, 134, 14, 2.4, 'a')
            . ThumbKit::r(42, 140, 52, 3.4, 's')
            . ThumbKit::r(40, 150, 68, 1.2, 'a');

        return ThumbKit::svg($svg);
    }

    /** Height of the bottom panel box that holds the date. */
    private function panelDateHeight(CoverData $d): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        $width = self::PANEL_W - 2 * self::PANEL_PAD;
        $vLines = self::estimateLines(self::unescape($d->submissionDateDisplay), $width, $d->submissionFontSize);
        $labelH = 2 * self::LABEL_SIZE * 1.4;
        return 22.0 + $labelH + 4.0 + $vLines * $d->submissionFontSize * 1.32 + 26.0;
    }

    /** Height of the free-standing date line used when the panel is off. */
    private function plainDateHeight(CoverData $d, float $contentWidth): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        return self::submissionLines($d, $contentWidth) * $d->submissionFontSize * 1.32 + 4.0;
    }

    private function contentLeft(CoverData $d): float
    {
        return $d->showBorder ? self::PANEL_W + self::STRIPE_W + self::CONTENT_GAP : 52.0;
    }

    private function contentRight(CoverData $d): float
    {
        return $d->showBorder ? self::RIGHT : 52.0;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        if ($d->showBorder) {
            return 40.0;
        }
        $width = self::PAGE_W - $this->contentLeft($d) - $this->contentRight($d);
        $h = $this->plainDateHeight($d, $width);
        return $h > 0 ? 44.0 + $h + 14.0 : 44.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z    = self::sizes($d, $fontScale);
        $acc  = self::e($d->accentColor);
        $pri  = self::e($d->primaryColor);
        $fSec = self::e($d->secondaryFont);
        $fPri = self::e($d->primaryFont);
        $on   = self::e(Color::contrastText($d->accentColor));

        $left  = $this->contentLeft($d);
        $right = $this->contentRight($d);
        $contentW = self::PAGE_W - $left - $right;

        $padTop   = self::sp(50.0, $spacingScale);
        $gap      = self::sp(26.0, $spacingScale);
        $topicGap = self::sp(24.0, $spacingScale);
        $rowPad   = self::sp(1.5, $spacingScale);
        $topicPad = self::sp(9.0, $spacingScale);
        $dividerGap = self::sp(6.0, $spacingScale);

        $css = self::commonCss($d, $fontScale, $spacingScale, self::SECTION_SIZE);
        $css .= <<<CSS
div.pad { padding: {$padTop}pt {$right}pt 0 {$left}pt; }
table.grid td.section-h {
    padding: 0 0 4pt 0;
    border-bottom: 1.5pt solid {$acc};
}
table.grid td.label { padding: {$rowPad}pt 0 {$rowPad}pt 6pt; }
table.grid td.colon { padding: {$rowPad}pt 4pt {$rowPad}pt 4pt; }
table.grid td.value { padding: {$rowPad}pt 0 {$rowPad}pt 0; }
table.grid td.designation { padding: 0 0 2pt 0; }
td.topic-cell {
    padding: {$topicPad}pt 0 {$topicPad}pt 0;
    border-top: 1.5pt solid {$acc};
    border-bottom: 1.5pt solid {$acc};
}
.topic-label {
    font-size: 12pt;
    letter-spacing: 3pt;
    margin-bottom: 3pt !important;
    color: {$acc};
}

CSS;

        // ---- Panel / date ----
        $deco = '';
        if ($d->showBorder) {
            $dh = $this->panelDateHeight($d);
            $upperH = self::PAGE_H - $dh + 1.0;   // reaches 1pt into the date box
            $deco .= self::box(0.0, 0.0, self::PANEL_W, $upperH, $d->accentColor);
            if ($dh > 0) {
                $deco .= '<div style="position:fixed; top:' . round(self::PAGE_H - $dh, 2) . 'pt; left:0pt; width:'
                    . self::PANEL_W . 'pt; height:' . round($dh, 2) . 'pt; background-color:' . $acc . ';">'
                    . '<div style="padding:22pt ' . self::PANEL_PAD . 'pt 0 ' . self::PANEL_PAD . 'pt;">'
                    . '<p style="margin:0 0 4pt 0; font-size:' . self::LABEL_SIZE . 'pt; font-weight:bold; letter-spacing:1.5pt;'
                    . ' text-transform:uppercase; font-family:' . $fPri . '; color:' . $on . ';">Submission Date</p>'
                    . '<p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:' . $fSec . '; color:' . $on . ';">'
                    . self::e($d->submissionDateDisplay) . '</p>'
                    . '</div></div>';
            }
            $deco .= self::box(0.0, self::PANEL_W, self::STRIPE_W, self::PAGE_H, $d->primaryColor);
        } elseif (self::hasSubmission($d)) {
            $h = $this->plainDateHeight($d, $contentW);
            $deco .= '<div style="position:fixed; left:' . $left . 'pt; top:' . round(self::PAGE_H - 44.0 - $h, 2)
                . 'pt; width:' . round($contentW, 2) . 'pt;">'
                . '<p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:' . $fSec . '; color:' . $acc . ';">'
                . '<b>Submission Date:</b> ' . self::e($d->submissionDateDisplay) . '</p></div>';
        }

        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'      => true,
            'gapPt'      => $gap,
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

        $header = self::headerBlock($d, ['scale' => $fontScale])
            . self::titleBlock($d, ['scale' => $fontScale, 'color' => $d->accentColor, 'before' => 10.0, 'after' => 0.0, 'size' => 19.0]);
        $spacer = '<div style="height:' . $dividerGap . 'pt; font-size:1pt; line-height:1pt;">&nbsp;</div>';

        $body = $deco . "\n"
            . '<div class="pad">' . $header . $spacer . $details . $topic . '</div>';

        return self::document($css, $body);
    }
}
