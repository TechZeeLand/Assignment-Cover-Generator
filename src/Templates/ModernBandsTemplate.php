<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Modern Bands": a solid header band (flowing table cell, so it grows with
 * the university name) + a thin primary-colour stripe, shaded alternating
 * rows, a call-out box for the topic and a solid footer band pinned to the
 * bottom edge that carries the submission date.
 *
 * With "Show decorative border" switched off the bands become plain text:
 * no fills, just a thin rule under the header and above the date.
 */
final class ModernBandsTemplate extends BaseTemplate
{
    private const SIDE = 48.0;        // left/right padding of all content
    private const FOOT_PAD = 14.0;    // padding above/below the date text in the footer band
    private const SECTION_SIZE = 26.0;

    public function key(): string
    {
        return 'modern';
    }

    public function category(): string
    {
        return 'Modern';
    }

    public function label(): string
    {
        return 'Modern Bands';
    }

    public function description(): string
    {
        return 'Solid header and footer bands, shaded rows and a highlighted topic box.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::r(0, 0, 120, 36, 'a')
            . ThumbKit::r(0, 36, 120, 3, 'p')
            . ThumbKit::r(48, 7, 24, 2.5, 'w')
            . ThumbKit::r(22, 13, 76, 6, 'w')
            . ThumbKit::r(38, 23, 44, 3, 'w');

        // header + zebra rows
        $svg .= ThumbKit::r(14, 47, 2.5, 6, 'a') . ThumbKit::r(19, 48, 38, 4, 'p');
        for ($i = 0; $i < 5; $i++) {
            $y = 58 + $i * 7;
            if ($i % 2 === 1) {
                $svg .= ThumbKit::r(14, $y - 1.8, 92, 6.4, 'pl');
            }
            $svg .= ThumbKit::r(18, $y, 20, 2.6, 'p') . ThumbKit::r(48, $y, $i % 2 ? 28 : 36, 2.6, 's');
        }
        $svg .= ThumbKit::r(14, 96, 2.5, 6, 'a') . ThumbKit::r(19, 97, 38, 4, 'p');
        $svg .= ThumbKit::rows(18, 20, 48, 34, 107, 2, 7);

        // topic call-out
        $svg .= ThumbKit::r(14, 126, 92, 22, 'al')
            . ThumbKit::r(14, 126, 3, 22, 'a')
            . ThumbKit::r(22, 130, 14, 3, 'a')
            . ThumbKit::r(22, 136, 60, 3.5, 's');

        // footer band
        $svg .= ThumbKit::r(0, 154, 120, 16, 'a') . ThumbKit::r(22, 160, 70, 3, 'w');

        return ThumbKit::svg($svg);
    }

    private function footerHeight(CoverData $d): float
    {
        if (self::hasSubmission($d)) {
            $lines = self::submissionLines($d, self::PAGE_W - 2 * self::SIDE);
            return 2 * self::FOOT_PAD + $lines * $d->submissionFontSize * 1.32;
        }
        return $d->showBorder ? 14.0 : 0.0;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        $h = $this->footerHeight($d);
        return $h > 0 ? $h + 10.0 : 28.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z   = self::sizes($d, $fontScale);
        $acc = self::e($d->accentColor);
        $pri = self::e($d->primaryColor);
        $sec = self::e($d->secondaryColor);
        $fSec = self::e($d->secondaryFont);

        $bands   = $d->showBorder;
        // Every colour that sits on a fill of this design's own is derived
        // from that fill, and applied INLINE (inline styles always win in
        // mPDF), so text can never end up the same colour as its background.
        $onAcc    = Color::contrastText($d->accentColor);          // text on the accent band
        $onAccDim = Color::mix($onAcc, $d->accentColor, 0.18);     // slightly softer, for the department
        $zebraBg  = Color::tint($d->primaryColor, 0.88);
        $zebraLab = Color::ensureContrast($d->primaryColor, $zebraBg, 4.5);
        $zebraVal = Color::ensureContrast($d->secondaryColor, $zebraBg, 4.5);
        $calloutBg = Color::tint($d->accentColor, 0.9);
        $calloutLabel = Color::ensureContrast($d->accentColor, $calloutBg, 4.5);
        $calloutText  = Color::ensureContrast($d->secondaryColor, $calloutBg, 4.5);
        $zebra   = self::e($zebraBg);
        $callout = self::e($calloutBg);

        $bandTop = self::sp(34.0, $spacingScale);
        $bandBot = self::sp(22.0, $spacingScale);
        $padTop  = self::sp(24.0, $spacingScale);
        $gap     = self::sp(22.0, $spacingScale);
        $topicGap = self::sp(22.0, $spacingScale);
        $rowPad  = self::sp(3.0, $spacingScale);
        $side    = self::SIDE;

        $css = self::commonCss($d, $fontScale, $spacingScale, self::SECTION_SIZE);
        $css .= <<<CSS
.dept-name { margin-bottom: 0 !important; }
td.band-cell { padding: {$bandTop}pt {$side}pt {$bandBot}pt {$side}pt; text-align: center; }
td.stripe { padding: 0; height: 7pt; font-size: 1pt; line-height: 1pt; background-color: {$pri}; }
td.stripe-thin { padding: 0; height: 2pt; font-size: 1pt; line-height: 1pt; background-color: {$acc}; }
div.pad { padding: {$padTop}pt {$side}pt 0 {$side}pt; }
table.grid td.section-h {
    border-left: 6pt solid {$acc};
    padding: 2pt 0 3pt 10pt;
}
table.grid td.label { padding: {$rowPad}pt 0 {$rowPad}pt 10pt; }
table.grid td.colon { padding: {$rowPad}pt 6pt; }
table.grid td.value { padding: {$rowPad}pt 8pt {$rowPad}pt 0; }
table.grid td.designation { padding: 0 8pt 3pt 0; }
td.topic-cell { padding: 10pt 14pt 12pt 14pt; }
.topic-label {
    font-size: 13pt;
    letter-spacing: 2pt;
    margin-bottom: 3pt !important;
}
.foot-text { margin: 0; font-family: {$fSec}; }

CSS;


        // ---- Footer (pinned to the bottom edge) ----
        $footer = '';
        $fh = $this->footerHeight($d);
        if ($fh > 0) {
            $top = self::PAGE_H - $fh;
            $text = '';
            if (self::hasSubmission($d)) {
                $textColor = $bands ? $onAcc : $d->accentColor;
                $text = '<p class="foot-text" style="font-size:' . $z['submission'] . 'pt; color:' . self::e($textColor) . ';">'
                    . '<b>Submission Date:</b> ' . self::e($d->submissionDateDisplay) . '</p>';
            }
            $padTopFoot = self::FOOT_PAD;
            if ($bands) {
                $footer = '<div style="position:fixed; top:' . round($top, 2) . 'pt; left:0pt; width:'
                    . round(self::PAGE_W, 2) . 'pt; height:' . round($fh, 2) . 'pt; background-color:' . $acc . ';">'
                    . '<div style="padding:' . $padTopFoot . 'pt ' . $side . 'pt 0 ' . $side . 'pt;">' . $text . '</div></div>';
            } else {
                $footer = self::box($top, $side, self::PAGE_W - 2 * $side, 1.2, $d->accentColor)
                    . '<div style="position:fixed; top:' . round($top, 2) . 'pt; left:0pt; width:'
                    . round(self::PAGE_W, 2) . 'pt;">'
                    . '<div style="padding:' . $padTopFoot . 'pt ' . $side . 'pt 0 ' . $side . 'pt;">' . $text . '</div></div>';
            }
        }

        // ---- Header: band + stripe ----
        $headerOpts = ['scale' => $fontScale];
        if ($bands) {
            $headerOpts['colors'] = ['bismillah' => $onAcc, 'versity' => $onAcc, 'faculty' => $onAccDim, 'dept' => $onAccDim];
            $headerOpts['plate'] = '#ffffff';   // logos sit on a white plate, never straight on the band
        }
        $header = '<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="band-cell"'
            . ($bands ? ' style="background-color:' . $acc . ';"' : '') . '>'
            . self::headerBlock($d, $headerOpts) . '</td></tr></table>';
        if ($bands) {
            $header .= '<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="stripe" style="background-color:' . $pri . ';">&nbsp;</td></tr></table>';
        } else {
            $header .= '<div style="padding:0 ' . $side . 'pt;"><table width="100%" cellpadding="0" cellspacing="0">'
                . '<tr><td class="stripe-thin" style="background-color:' . $acc . ';">&nbsp;</td></tr></table></div>';
        }

        // ---- Details ----
        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'  => true,
            'zebra'  => true,
            'spacer' => true,
            'gapPt'  => $gap,
            'hStyle' => 'border-left:6pt solid ' . $acc . ';',
            'alt'    => [
                'l' => 'background-color:' . $zebra . '; color:' . self::e($zebraLab) . ';',
                'c' => 'background-color:' . $zebra . '; color:' . self::e($zebraLab) . ';',
                'v' => 'background-color:' . $zebra . '; color:' . self::e($zebraVal) . ';',
            ],
        ]);

        // ---- Topic ----
        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $topicGap . 'pt;">'
                . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="topic-cell"'
                . ' style="background-color:' . $callout . '; border-left:7pt solid ' . $acc . ';">'
                . '<p class="topic-label" style="color:' . self::e($calloutLabel) . ';">TOPIC</p>'
                . '<p class="topic-text" style="color:' . self::e($calloutText) . ';">' . $d->topicHtml . '</p>'
                . '</td></tr></table></div>';
        }

        $title = self::titleBlock($d, [
            'scale' => $fontScale, 'color' => Color::ensureContrast($d->primaryColor, '#ffffff', 3.0),
            'before' => 0.0, 'after' => 18.0, 'align' => 'left',
        ]);

        $body = $footer . "\n" . $header . "\n"
            . '<div class="pad">' . $title . $details . $topic . '</div>';

        return self::document($css, $body);
    }
}
