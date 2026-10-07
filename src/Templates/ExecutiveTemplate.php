<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Executive": a letterhead with the logo at its left, a two-column body
 * (student on the left, course and instructor on the right, label above
 * value) divided by a hairline, a highlighted topic bar, and the submission
 * date pinned bottom-left above a rule.
 */
final class ExecutiveTemplate extends BaseTemplate
{
    private const SIDE = 50.0;
    private const DATE_BOTTOM = 42.0;

    public function key(): string
    {
        return 'executive';
    }

    public function category(): string
    {
        return 'Professional';
    }

    public function label(): string
    {
        return 'Executive';
    }

    public function description(): string
    {
        return 'Letterhead, two columns (student | course) and a highlighted topic bar.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::r(0, 0, 120, 6, 'a') . ThumbKit::r(0, 6, 120, 1.4, 'p')
            . ThumbKit::r(14, 16, 14, 14, 'pl')
            . ThumbKit::r(33, 16, 58, 6, 'a') . ThumbKit::r(33, 25, 36, 3, 's')
            . ThumbKit::r(14, 36, 92, 1.2, 'a')
            . ThumbKit::r(14, 43, 40, 3.4, 'p');
        for ($i = 0; $i < 4; $i++) {
            $y = 53 + $i * 13;
            $svg .= ThumbKit::r(14, $y, 14, 1.8, 'p') . ThumbKit::r(14, $y + 4, 34, 3, 's')
                . ThumbKit::r(66, $y, 14, 1.8, 'p') . ThumbKit::r(66, $y + 4, $i % 2 ? 30 : 38, 3, 's');
        }
        $svg .= ThumbKit::r(59, 42, 0.6, 62, 'pl')
            . ThumbKit::r(14, 112, 92, 22, 'al') . ThumbKit::r(14, 112, 3, 22, 'a')
            . ThumbKit::r(21, 116, 14, 2.2, 'a') . ThumbKit::r(21, 121, 62, 3.6, 's')
            . ThumbKit::r(14, 148, 92, 0.6, 'pl') . ThumbKit::r(14, 152, 20, 2, 'p') . ThumbKit::r(14, 156, 36, 3, 'a')
            . ThumbKit::r(0, 166, 120, 4, 'a');
        return ThumbKit::svg($svg);
    }

    private function dateHeight(CoverData $d): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        $lines = self::estimateLines(self::unescape($d->submissionDateDisplay), self::PAGE_W - 2 * self::SIDE, $d->submissionFontSize);
        return 9.0 * 1.4 + 3.0 + $lines * $d->submissionFontSize * 1.32;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        $h = $this->dateHeight($d);
        return $h > 0 ? self::DATE_BOTTOM + $h + 12.0 + 12.0 : 40.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z = self::sizes($d, $fontScale);
        $acc = self::e($d->accentColor);
        $pri = self::e($d->primaryColor);
        $side = self::SIDE;
        $rule = Color::tint($d->primaryColor, 0.65);
        $calloutBg = Color::tint($d->accentColor, 0.9);
        $calloutLab = Color::ensureContrast($d->accentColor, $calloutBg, 4.5);
        $calloutTxt = Color::ensureContrast($d->secondaryColor, $calloutBg, 4.5);
        $headColor = Color::ensureContrast($d->accentColor, '#ffffff', 3.0);

        $padTop = self::sp(46.0, $spacingScale);
        $gap    = self::sp(22.0, $spacingScale);
        $itemGap = self::sp(8.0, $spacingScale);
        $sStud = max(10.0, round($z['student'] * 0.6, 2));
        $sCour = max(10.0, round($z['course'] * 0.6, 2));
        $sTopic = $z['topic'];

        $css = self::commonCss($d, $fontScale, $spacingScale, 20.0);
        $css .= <<<CSS
div.pad { padding: {$padTop}pt {$side}pt 0 {$side}pt; }
.dept-name { margin-bottom: 0 !important; }
.card-title { margin: 0 0 8pt 0; font-size: 12pt; letter-spacing: 2pt; text-transform: uppercase; font-weight: bold; }
.topic-label { font-size: 12pt; letter-spacing: 2pt; margin-bottom: 3pt !important; }
.foot-label { margin: 0 0 3pt 0; font-size: 9pt; letter-spacing: 1.5pt; text-transform: uppercase; }

CSS;

        // ---- letterhead ----
        $logo = self::logoHtml($d, 'left', null, $fontScale, 180.0);
        $headerHtml = self::headerBlock($d, ['scale' => $fontScale, 'align' => 'left', 'logo' => false]);
        if ($logo !== '' && $d->logo !== null) {
            $h = max(20.0, round($d->logoHeight * $fontScale, 2));
            $w = min(180.0, round($h * $d->logo['w'] / max(1, $d->logo['h']), 2));
            $header = '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="width:' . ($w + 18) . 'pt; vertical-align:middle;">' . $logo . '</td>'
                . '<td style="vertical-align:middle;">' . $headerHtml . '</td></tr></table>';
        } else {
            $header = $headerHtml;
        }
        $rules = '<div style="margin-top:6pt; margin-bottom:' . $gap . 'pt;"><table width="100%" cellpadding="0" cellspacing="0"><tr>'
            . '<td style="border-top:1.5pt solid ' . $acc . '; font-size:1pt; line-height:1pt;">&nbsp;</td></tr></table></div>';
        $title = self::titleBlock($d, [
            'scale' => $fontScale, 'color' => $headColor, 'align' => 'left', 'before' => 0.0, 'after' => $gap, 'size' => 17.0, 'spacing' => 4.0,
        ]);

        // ---- two columns ----
        $rows = self::collectRows($d);
        $suffix = $d->headerSuffix !== '' ? ' ' . self::e($d->headerSuffix) : '';
        $card = static function (string $heading, string $inner, string $color): string {
            return '<p class="card-title" style="color:' . htmlspecialchars($color, ENT_QUOTES) . ';">' . $heading . '</p>' . $inner;
        };

        $left = '';
        if (!empty($rows['student'])) {
            $left .= $card('Student Details' . $suffix, self::stackedRows($d, $rows['student'], [
                'labelSize' => 9.5, 'valueSize' => $sStud, 'gap' => $itemGap,
            ]), $headColor);
        }
        if (!empty($rows['group'])) {
            $left .= '<div style="margin-top:' . $itemGap . 'pt;">' . $card('Group Details' . $suffix, self::stackedRows($d, $rows['group'], [
                'labelSize' => 9.5, 'valueSize' => $sStud, 'gap' => $itemGap,
            ]), $headColor) . '</div>';
        }
        $right = '';
        if (!empty($rows['course']) || $rows['designation'] !== '') {
            $inner = self::stackedRows($d, $rows['course'], ['labelSize' => 9.5, 'valueSize' => $sCour, 'gap' => $itemGap]);
            if ($rows['designation'] !== '') {
                $inner .= '<p style="margin:0 0 ' . $itemGap . 'pt 0; font-size:' . $z['designation']
                    . 'pt; font-family:' . self::e($d->secondaryFont) . '; color:' . self::e($d->secondaryColor) . ';">' . $rows['designation'] . '</p>';
            }
            $right = $card('Course Details' . $suffix, $inner, $headColor);
        }

        $columns = '';
        if ($left !== '' && $right !== '') {
            $columns = '<table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="width:47%; vertical-align:top; padding-right:18pt; border-right:1pt solid ' . self::e($rule) . ';">' . $left . '</td>'
                . '<td style="width:53%; vertical-align:top; padding-left:22pt;">' . $right . '</td></tr></table>';
        } elseif ($left !== '' || $right !== '') {
            $columns = $left . $right;
        }

        // ---- topic bar ----
        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $gap . 'pt;"><table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="background-color:' . self::e($calloutBg) . '; border-left:5pt solid ' . $acc . '; padding:10pt 14pt 12pt 14pt;">'
                . '<p class="topic-label" style="color:' . self::e($calloutLab) . ';">TOPIC</p>'
                . '<p class="topic-text" style="color:' . self::e($calloutTxt) . '; font-size:' . $sTopic . 'pt;">' . $d->topicHtml . '</p>'
                . '</td></tr></table></div>';
        }

        // ---- fixed parts ----
        $deco = '';
        if ($d->showBorder) {
            $deco .= self::box(0.0, 0.0, self::PAGE_W, 10.0, $d->accentColor)
                . self::box(10.0, 0.0, self::PAGE_W, 2.0, $d->primaryColor)
                . self::box(self::PAGE_H - 5.0, 0.0, self::PAGE_W, 5.0, $d->accentColor);
        }
        if (self::hasSubmission($d)) {
            $h = $this->dateHeight($d);
            $top = self::PAGE_H - self::DATE_BOTTOM - $h;
            $deco .= self::box($top - 12.0, $side, self::PAGE_W - 2 * $side, 0.8, $rule);
            $deco .= '<div style="position:fixed; left:' . $side . 'pt; top:' . round($top, 2) . 'pt; width:'
                . round(self::PAGE_W - 2 * $side, 2) . 'pt;">'
                . '<p class="foot-label" style="font-family:' . self::e($d->primaryFont) . '; color:' . $pri . ';">Submission Date</p>'
                . '<p style="margin:0; font-size:' . $z['submission'] . 'pt; font-family:' . self::e($d->secondaryFont) . '; color:' . $acc . ';">'
                . self::e($d->submissionDateDisplay) . '</p></div>';
        }

        $body = $deco . "\n" . '<div class="pad">' . $header . $rules . $title . $columns . $topic . '</div>';
        return self::document($css, $body);
    }
}
