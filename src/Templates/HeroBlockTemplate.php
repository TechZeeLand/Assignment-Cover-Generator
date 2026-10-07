<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Hero Block": a big solid colour hero holding the header (and the cover
 * title in large type), a three-colour strip under it, then clean ruled
 * rows with a coloured bar beside each section heading, and a "chip" for
 * the submission date.
 *
 * Text on the hero / chip always gets a colour derived from that fill and
 * set inline. With the border switched off the hero fill is dropped and the
 * header is plain coloured text.
 */
final class HeroBlockTemplate extends BaseTemplate
{
    private const SIDE = 50.0;
    private const CHIP_MARGIN = 40.0;

    public function key(): string
    {
        return 'hero';
    }

    public function category(): string
    {
        return 'Modern';
    }

    public function label(): string
    {
        return 'Hero Block';
    }

    public function description(): string
    {
        return 'Big colour hero with large title, tri-colour strip and a date chip.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::r(0, 0, 120, 52, 'a')
            . ThumbKit::r(14, 10, 22, 2.4, 'w') . ThumbKit::r(14, 16, 74, 7, 'w') . ThumbKit::r(14, 26, 44, 3, 'w')
            . ThumbKit::r(14, 36, 62, 8, 'w')
            . ThumbKit::r(0, 52, 60, 4, 'p') . ThumbKit::r(60, 52, 36, 4, 's') . ThumbKit::r(96, 52, 24, 4, 'al');
        $y = 64.0;
        foreach ([5, 3] as $n) {
            $svg .= ThumbKit::r(14, $y, 3, 6, 'p') . ThumbKit::r(20, $y + 1, 38, 4, 'p');
            $y += 10;
            for ($i = 0; $i < $n; $i++) {
                $svg .= ThumbKit::r(14, $y, 16, 2.4, 'p') . ThumbKit::r(44, $y, $i % 2 ? 30 : 38, 2.4, 's') . ThumbKit::r(14, $y + 4.2, 92, 0.5, 'pl');
                $y += 7;
            }
            $y += 4;
        }
        $svg .= ThumbKit::r(14, 134, 2, 14, 's') . ThumbKit::r(20, 135, 12, 2.2, 'p') . ThumbKit::r(20, 141, 66, 3.6, 's')
            . ThumbKit::r(14, 154, 58, 9, 'p') . ThumbKit::r(19, 157.5, 40, 2.4, 'w');
        return ThumbKit::svg($svg);
    }

    /** @return array{0:float,1:float} chip width and height in points */
    private function chipSize(CoverData $d): array
    {
        if (!self::hasSubmission($d)) {
            return [0.0, 0.0];
        }
        $text = 'Submission Date: ' . self::unescape($d->submissionDateDisplay);
        $maxW = self::PAGE_W - 2 * self::SIDE;
        $w = min($maxW, mb_strlen($text) * $d->submissionFontSize * 0.6 + 34.0);
        $lines = self::estimateLines($text, $w - 28.0, $d->submissionFontSize);
        return [$w, 16.0 + $lines * $d->submissionFontSize * 1.32];
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        [, $h] = $this->chipSize($d);
        return $h > 0 ? self::CHIP_MARGIN + $h + 14.0 : 40.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z = self::sizes($d, $fontScale);
        $acc = self::e($d->accentColor);
        $pri = self::e($d->primaryColor);
        $side = self::SIDE;
        $hero = $d->showBorder;

        $onAcc    = Color::contrastText($d->accentColor);
        $onAccDim = Color::mix($onAcc, $d->accentColor, 0.15);
        $onPri    = Color::contrastText($d->primaryColor);
        $hair     = self::e(Color::tint($d->primaryColor, 0.72));

        $heroTop = self::sp(44.0, $spacingScale);
        $heroBot = self::sp(30.0, $spacingScale);
        $padTop  = self::sp(26.0, $spacingScale);
        $gap     = self::sp(20.0, $spacingScale);
        $rowPad  = self::sp(4.0, $spacingScale);
        $sHead   = self::fs(19.0, $fontScale);

        $css = self::commonCss($d, $fontScale, $spacingScale, 19.0);
        $css .= <<<CSS
.dept-name { margin-bottom: 0 !important; }
div.pad { padding: {$padTop}pt {$side}pt 0 {$side}pt; }
td.hero-cell { padding: {$heroTop}pt {$side}pt {$heroBot}pt {$side}pt; }
table.grid td.section-h { font-size: {$sHead}pt; padding-bottom: 4pt; }
table.grid td.label { padding: {$rowPad}pt 0 {$rowPad}pt 10pt; }
table.grid td.colon { padding: {$rowPad}pt 6pt; }
table.grid td.value { padding: {$rowPad}pt 0 {$rowPad}pt 0; }
.topic-label { font-size: 12pt; letter-spacing: 3pt; margin-bottom: 3pt !important; }

CSS;

        // ---- hero ----
        $opts = ['scale' => $fontScale, 'align' => 'left'];
        $titleColor = $d->primaryColor;
        if ($hero) {
            $opts['colors'] = ['bismillah' => $onAcc, 'versity' => $onAcc, 'faculty' => $onAccDim, 'dept' => $onAccDim];
            $opts['plate'] = '#ffffff';
            $titleColor = $onAcc;
        } else {
            $titleColor = Color::ensureContrast($d->accentColor, '#ffffff', 3.0);
        }
        $title = self::titleBlock($d, [
            'scale' => $fontScale, 'color' => $titleColor, 'align' => 'left', 'before' => self::sp(18.0, $spacingScale),
            'after' => 0.0, 'size' => 30.0, 'spacing' => 5.0,
        ]);
        $heroHtml = '<table width="100%" cellpadding="0" cellspacing="0"><tr><td class="hero-cell"'
            . ($hero ? ' style="background-color:' . $acc . ';"' : '') . '>'
            . self::headerBlock($d, $opts) . $title . '</td></tr></table>';

        $strip = '';
        if ($hero) {
            $strip = '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td width="50%" style="background-color:' . $pri . '; height:8pt; font-size:1pt; line-height:1pt;">&nbsp;</td>'
                . '<td width="30%" style="background-color:' . self::e($d->secondaryColor) . '; height:8pt; font-size:1pt; line-height:1pt;">&nbsp;</td>'
                . '<td width="20%" style="background-color:' . self::e(Color::tint($d->accentColor, 0.7)) . '; height:8pt; font-size:1pt; line-height:1pt;">&nbsp;</td>'
                . '</tr></table>';
        } else {
            $strip = '<div style="padding:0 ' . $side . 'pt;"><table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="border-top:2pt solid ' . $acc . '; font-size:1pt; line-height:1pt;">&nbsp;</td></tr></table></div>';
        }

        // ---- details ----
        $cellRule = 'border-bottom:0.75pt solid ' . $hair . ';';
        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'  => true,
            'spacer' => true,
            'gapPt'  => $gap,
            'hStyle' => 'border-left:8pt solid ' . $pri . '; padding-left:10pt;',
            'lStyle' => $cellRule,
            'cStyle' => $cellRule,
            'vStyle' => $cellRule,
        ]);

        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $gap . 'pt;"><table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="border-left:4pt solid ' . self::e($d->secondaryColor) . '; padding:2pt 0 2pt 14pt;">'
                . '<p class="topic-label">TOPIC</p><p class="topic-text">' . $d->topicHtml . '</p></td></tr></table></div>';
        }

        // ---- date chip ----
        $chip = '';
        [$cw, $ch] = $this->chipSize($d);
        if ($ch > 0) {
            $top = self::PAGE_H - self::CHIP_MARGIN - $ch;
            $text = '<b>Submission Date:</b> ' . self::e($d->submissionDateDisplay);
            if ($hero) {
                $chip = '<div style="position:fixed; left:' . $side . 'pt; top:' . round($top, 2) . 'pt; width:' . round($cw, 2)
                    . 'pt; height:' . round($ch, 2) . 'pt; background-color:' . $pri . ';">'
                    . '<div style="padding:8pt 14pt 0 14pt;"><p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:'
                    . self::e($d->secondaryFont) . '; color:' . self::e($onPri) . ';">' . $text . '</p></div></div>';
            } else {
                $chip = '<div style="position:fixed; left:' . $side . 'pt; top:' . round($top, 2) . 'pt; width:' . round($cw, 2) . 'pt;">'
                    . '<p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:' . self::e($d->secondaryFont)
                    . '; color:' . $acc . ';">' . $text . '</p></div>';
            }
        }

        $body = $chip . "\n" . $heroHtml . $strip . "\n" . '<div class="pad">' . $details . $topic . '</div>';
        return self::document($css, $body);
    }
}
