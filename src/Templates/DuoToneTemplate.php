<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Duo Tone": the page is split in two columns. A tinted column on the left
 * carries the student (and group) details and the submission date; the right
 * column carries the header, cover title, course/instructor and the topic.
 * A solid bar closes the page at the bottom.
 *
 * The tinted column is a table cell given an explicit height, so its colour
 * is part of the same table as its text (no overlapping boxes). All text on
 * the tint is checked against it with Color::ensureContrast().
 */
final class DuoToneTemplate extends BaseTemplate
{
    private const LEFT_W = 205.0;
    private const BAR_H = 27.0;
    private const BOTTOM_MARGIN = 20.0;

    public function key(): string
    {
        return 'duotone';
    }

    public function category(): string
    {
        return 'Modern';
    }

    public function label(): string
    {
        return 'Duo Tone';
    }

    public function description(): string
    {
        return 'Two-column page: tinted student column, course and topic on the right.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::r(0, 0, 42, 159, 'pl') . ThumbKit::r(0, 159, 120, 11, 'a');
        for ($i = 0; $i < 5; $i++) {
            $y = 24 + $i * 17;
            $svg .= ThumbKit::r(7, $y, 14, 1.8, 'p') . ThumbKit::r(7, $y + 4, $i % 2 ? 24 : 28, 3, 's');
        }
        $svg .= ThumbKit::r(7, 128, 20, 1.8, 'p') . ThumbKit::r(7, 132, 26, 3, 'a')
            . ThumbKit::r(52, 16, 18, 2.4, 's') . ThumbKit::r(52, 22, 54, 7, 'a') . ThumbKit::r(52, 32, 34, 3, 's')
            . ThumbKit::r(52, 44, 40, 3.4, 'p');
        for ($i = 0; $i < 3; $i++) {
            $y = 54 + $i * 14;
            $svg .= ThumbKit::r(52, $y, 14, 1.8, 'p') . ThumbKit::r(52, $y + 4, $i % 2 ? 40 : 50, 3, 's');
        }
        $svg .= ThumbKit::r(52, 108, 14, 2.2, 'a') . ThumbKit::r(52, 113, 54, 3.6, 's') . ThumbKit::r(52, 120, 40, 3.6, 's');
        return ThumbKit::svg($svg);
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        return self::BOTTOM_MARGIN;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z = self::sizes($d, $fontScale);
        $tint = $d->showBorder;
        $leftBg = $tint ? Color::tint($d->primaryColor, 0.9) : '#ffffff';
        $leftLabel = Color::ensureContrast($d->primaryColor, $leftBg, 4.5);
        $leftValue = Color::ensureContrast($d->secondaryColor, $leftBg, 4.5);
        $leftAccent = Color::ensureContrast($d->accentColor, $leftBg, 3.0);
        $rule = self::e(Color::tint($d->primaryColor, 0.6));
        $acc = self::e($d->accentColor);
        $headColor = Color::ensureContrast($d->accentColor, '#ffffff', 3.0);

        $barTop = self::PAGE_H - self::BAR_H;
        $cellH = round($barTop, 2);
        $padTop = self::sp(46.0, $spacingScale);
        $gap = self::sp(16.0, $spacingScale);
        $item = self::sp(8.0, $spacingScale);
        $sStud = max(10.0, round($z['student'] * 0.6, 2));
        $sCour = max(10.0, round($z['course'] * 0.62, 2));
        $leftW = self::LEFT_W;

        $css = self::commonCss($d, $fontScale, $spacingScale, 20.0);
        $css .= <<<CSS
.dept-name { margin-bottom: 0 !important; }
.card-title { margin: 0 0 8pt 0; font-size: 12pt; letter-spacing: 2pt; text-transform: uppercase; font-weight: bold; }
.topic-label { font-size: 12pt; letter-spacing: 3pt; margin-bottom: 3pt !important; }

CSS;
        $suffix = $d->headerSuffix !== '' ? ' ' . self::e($d->headerSuffix) : '';
        $rows = self::collectRows($d);

        // ---- left column ----
        $left = '';
        if (!empty($rows['student'])) {
            $left .= '<p class="card-title" style="color:' . self::e($leftAccent) . ';">Student Details' . $suffix . '</p>'
                . self::stackedRows($d, $rows['student'], [
                    'labelColor' => $leftLabel, 'valueColor' => $leftValue, 'labelSize' => 9.5, 'valueSize' => $sStud, 'gap' => $item,
                ]);
        }
        if (!empty($rows['group'])) {
            $left .= '<div style="margin-top:' . $gap . 'pt;"><p class="card-title" style="color:' . self::e($leftAccent) . ';">Group Details' . $suffix . '</p>'
                . self::stackedRows($d, $rows['group'], [
                    'labelColor' => $leftLabel, 'valueColor' => $leftValue, 'labelSize' => 9.5, 'valueSize' => $sStud, 'gap' => $item,
                ]) . '</div>';
        }
        if (self::hasSubmission($d)) {
            $left .= '<div style="margin-top:' . $gap . 'pt;">' . self::stackedRows($d, [['Submission Date', self::e($d->submissionDateDisplay)]], [
                'labelColor' => $leftLabel, 'valueColor' => $leftAccent, 'labelSize' => 9.5,
                'valueSize' => min($z['submission'], 18.0), 'gap' => 0.0,
            ]) . '</div>';
        }

        // ---- right column ----
        $right = self::headerBlock($d, ['scale' => $fontScale, 'align' => 'left'])
            . self::titleBlock($d, [
                'scale' => $fontScale, 'color' => $headColor, 'align' => 'left', 'before' => 8.0, 'after' => 0.0, 'size' => 17.0, 'spacing' => 4.0,
            ]);
        $right .= '<div style="height:' . $gap . 'pt; font-size:1pt; line-height:1pt;">&nbsp;</div>';
        if (!empty($rows['course']) || $rows['designation'] !== '') {
            $right .= '<p class="card-title" style="color:' . self::e($headColor) . ';">Course Details' . $suffix . '</p>'
                . self::stackedRows($d, $rows['course'], ['labelSize' => 9.5, 'valueSize' => $sCour, 'gap' => $item]);
            if ($rows['designation'] !== '') {
                $right .= '<p style="margin:0 0 ' . $item . 'pt 0; font-size:' . $z['designation'] . 'pt; font-family:'
                    . self::e($d->secondaryFont) . '; color:' . self::e($d->secondaryColor) . ';">' . $rows['designation'] . '</p>';
            }
        }
        if ($d->showTopic) {
            $right .= '<div style="margin-top:' . $gap . 'pt; border-top:1pt solid ' . $rule . ';"><p class="topic-label" style="color:'
                . self::e($headColor) . '; margin-top:10pt;">TOPIC</p><p class="topic-text">' . $d->topicHtml . '</p></div>';
        }

        $leftCell = '<td style="width:' . $leftW . 'pt; height:' . $cellH . 'pt; vertical-align:top; background-color:' . self::e($leftBg)
            . '; padding:' . $padTop . 'pt 20pt 20pt 28pt;' . ($tint ? '' : ' border-right:1pt solid ' . $rule . ';') . '">' . $left . '</td>';
        $rightCell = '<td style="vertical-align:top; padding:' . $padTop . 'pt 44pt 20pt 30pt;">' . $right . '</td>';

        $bar = $d->showBorder ? self::box($barTop, 0.0, self::PAGE_W, self::BAR_H, $d->accentColor) : '';
        $body = $bar . "\n" . '<table width="100%" cellpadding="0" cellspacing="0"><tr>' . $leftCell . $rightCell . '</tr></table>';

        return self::document($css, $body);
    }
}
