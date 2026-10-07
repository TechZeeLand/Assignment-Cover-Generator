<?php

declare(strict_types=1);

namespace App\Templates;

use App\Color;
use App\CoverData;

/**
 * "Official Form": a letterhead (logo, university, double rule) above a
 * fully ruled form. Section headings are solid bars; every row is a shaded
 * label cell next to a value cell; the topic and the submission date are
 * rows of the same form.
 *
 * Every colour that sits on one of the design's own fills is derived from
 * that fill (Color::contrastText / ensureContrast) and written inline.
 */
final class OfficialFormTemplate extends BaseTemplate
{
    private const SIDE = 46.0;
    private const DATE_BOTTOM = 46.0;

    public function key(): string
    {
        return 'official';
    }

    public function category(): string
    {
        return 'Professional';
    }

    public function label(): string
    {
        return 'Official Form';
    }

    public function description(): string
    {
        return 'Letterhead with a fully ruled form: shaded labels and solid section bars.';
    }

    public function thumbnailSvg(): string
    {
        $svg = ThumbKit::frame(4, 1.6, 'a')
            . ThumbKit::r(46, 14, 28, 2.6, 's')
            . ThumbKit::r(20, 20, 80, 6, 'a')
            . ThumbKit::r(36, 30, 48, 3, 's')
            . ThumbKit::r(14, 38, 92, 1.6, 'a')
            . ThumbKit::r(14, 41, 92, 0.6, 'a')
            . ThumbKit::r(30, 46, 60, 6, 'al');
        $y = 57.0;
        foreach ([5, 3] as $n) {
            $svg .= ThumbKit::r(14, $y, 92, 6, 'a');
            $y += 6;
            for ($i = 0; $i < $n; $i++) {
                $svg .= ThumbKit::r(14, $y, 34, 6.2, 'pl') . ThumbKit::r(14, $y, 92, 0.5, 'p') . ThumbKit::r(48, $y, 0.5, 6.2, 'p')
                    . ThumbKit::r(18, $y + 2, 20, 2.2, 'p') . ThumbKit::r(53, $y + 2, $i % 2 ? 28 : 36, 2.2, 's');
                $y += 6.2;
            }
            $svg .= ThumbKit::r(14, $y, 92, 0.5, 'p');
            $y += 7;
        }
        $svg .= ThumbKit::r(14, 140, 34, 8, 'pl') . ThumbKit::r(14, 140, 92, 0.5, 'p') . ThumbKit::r(14, 148, 92, 0.5, 'p')
            . ThumbKit::r(18, 143, 14, 2.2, 'p') . ThumbKit::r(53, 143, 44, 2.6, 's')
            . ThumbKit::r(14, 154, 34, 6, 'pl') . ThumbKit::r(14, 154, 92, 0.5, 'p') . ThumbKit::r(14, 160, 92, 0.5, 'p')
            . ThumbKit::r(18, 156, 22, 2.2, 'p') . ThumbKit::r(53, 156, 28, 2.2, 'a');
        return ThumbKit::svg($svg);
    }

    private function dateHeight(CoverData $d): float
    {
        if (!self::hasSubmission($d)) {
            return 0.0;
        }
        $valueW = (self::PAGE_W - 2 * self::SIDE) * 0.64 - 20.0;
        $lines = self::estimateLines(self::unescape($d->submissionDateDisplay), $valueW, $d->submissionFontSize);
        return 12.0 + $lines * $d->submissionFontSize * 1.32 + 2.0;
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        $h = $this->dateHeight($d);
        return $h > 0 ? self::DATE_BOTTOM + $h + 12.0 : 48.0;
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z = self::sizes($d, $fontScale);
        $acc = self::e($d->accentColor);
        $side = self::SIDE;

        $onAcc    = self::e(Color::contrastText($d->accentColor));
        $labelBg  = Color::tint($d->primaryColor, 0.9);
        $labelTxt = self::e(Color::ensureContrast($d->primaryColor, $labelBg, 4.5));
        $rule     = self::e(Color::mix($d->primaryColor, '#ffffff', 0.45));
        $titleBg  = Color::tint($d->accentColor, 0.88);
        $titleTxt = Color::ensureContrast($d->accentColor, $titleBg, 4.5);
        $labelBgE = self::e($labelBg);

        $padTop = self::sp(40.0, $spacingScale);
        $gap    = self::sp(14.0, $spacingScale);
        $cellV  = self::sp(5.0, $spacingScale);

        // Relative sizing: the form looks best when its rows are smaller than
        // the classic cover's, but still follow the user's per-group sizes.
        $sStud = max(9.0, round($z['student'] * 0.62, 2));
        $sCour = max(9.0, round($z['course'] * 0.62, 2));
        $sTopic = max(9.0, round($z['topic'] * 0.7, 2));
        $sHead = max(9.0, round(13.0 * $fontScale, 2));
        $sDate = $z['submission'];

        $css = self::commonCss($d, $fontScale, $spacingScale, 13.0);
        $css .= <<<CSS
.dept-name { margin-bottom: 0 !important; }
div.pad { padding: {$padTop}pt {$side}pt 0 {$side}pt; }
table.grid td.section-h { font-size: {$sHead}pt; letter-spacing: 1pt; text-transform: uppercase; }
table.grid td.grp-student { font-size: {$sStud}pt; }
table.grid td.grp-course { font-size: {$sCour}pt; }
table.grid td.designation { padding: 4pt 10pt 0 10pt; }
td.topic-l, td.topic-v, td.date-l, td.date-v { font-size: {$sTopic}pt; }
td.date-l, td.date-v { font-size: {$sDate}pt; }

CSS;
        $cellBase = 'border:0.75pt solid ' . $rule . '; padding:' . $cellV . 'pt 10pt;';
        $labelCss = $cellBase . ' background-color:' . $labelBgE . '; color:' . $labelTxt . '; width:34%;';
        $valueCss = $cellBase;

        $details = self::detailsTable($d, self::collectRows($d), [
            'colon'      => false,
            'gapPt'      => $gap,
            'firstGapPt' => 0.0,
            'spacer'     => true,
            'hStyle'     => 'background-color:' . $acc . '; color:' . $onAcc . '; border:1pt solid ' . $acc . '; padding:' . $cellV . 'pt 10pt;',
            'lStyle'     => $labelCss,
            'vStyle'     => $valueCss,
        ]);

        $topic = '';
        if ($d->showTopic) {
            $topic = '<div style="margin-top:' . $gap . 'pt;"><table class="grid" cellpadding="0" cellspacing="0"><tr>'
                . '<td class="topic-l" style="' . $labelCss . ' font-family:' . self::e($d->primaryFont) . ';">Topic</td>'
                . '<td class="topic-v" style="' . $valueCss . ' color:' . self::e($d->secondaryColor) . '; font-family:' . self::e($d->secondaryFont) . ';">'
                . $d->topicHtml . '</td></tr></table></div>';
        }

        // Letterhead: header, then a thick + a thin rule.
        $rules = '<div style="margin-top:8pt;"><table width="100%" cellpadding="0" cellspacing="0">'
            . '<tr><td style="border-top:3pt solid ' . $acc . '; font-size:1pt; line-height:1pt;">&nbsp;</td></tr></table>'
            . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="font-size:1pt; line-height:2pt;">&nbsp;</td></tr>'
            . '<tr><td style="border-top:1pt solid ' . $acc . '; font-size:1pt; line-height:1pt;">&nbsp;</td></tr></table></div>';

        $titleBar = '';
        if (self::coverTitleText($d) !== '') {
            $titleBar = '<div style="margin-top:' . $gap . 'pt; margin-bottom:' . $gap . 'pt;">'
                . '<table width="100%" cellpadding="0" cellspacing="0"><tr><td style="background-color:' . self::e($titleBg)
                . '; border-top:1pt solid ' . $acc . '; border-bottom:1pt solid ' . $acc . '; padding:6pt 10pt; text-align:center;">'
                . '<p class="cover-title" style="font-size:' . self::fs(17.0, $fontScale) . 'pt; letter-spacing:3pt; color:' . self::e($titleTxt) . ';">'
                . $d->coverTitle . '</p></td></tr></table></div>';
        } else {
            $titleBar = '<div style="height:' . $gap . 'pt; font-size:1pt; line-height:1pt;">&nbsp;</div>';
        }

        // Frame + pinned date row.
        $frame = $d->showBorder
            ? self::frameBars(20.0, 2.5, $d->accentColor) . self::frameBars(26.0, 0.8, $d->accentColor)
            : '';
        $date = '';
        if (self::hasSubmission($d)) {
            $top = self::PAGE_H - self::DATE_BOTTOM - $this->dateHeight($d);
            $date = '<div style="position:fixed; left:' . $side . 'pt; top:' . round($top, 2) . 'pt; width:'
                . round(self::PAGE_W - 2 * $side, 2) . 'pt;"><table class="grid" cellpadding="0" cellspacing="0"><tr>'
                . '<td class="date-l" style="' . $labelCss . ' font-family:' . self::e($d->primaryFont) . ';">Submission Date</td>'
                . '<td class="date-v" style="' . $valueCss . ' color:' . $acc . '; font-family:' . self::e($d->secondaryFont) . ';">'
                . self::e($d->submissionDateDisplay) . '</td></tr></table></div>';
        }

        $body = $frame . "\n" . $date . "\n"
            . '<div class="pad">' . self::headerBlock($d, ['scale' => $fontScale]) . $rules . $titleBar . $details . $topic . '</div>';

        return self::document($css, $body);
    }
}
