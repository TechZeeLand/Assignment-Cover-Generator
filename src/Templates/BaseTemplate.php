<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/**
 * Shared building blocks for the non-classic designs.
 *
 * Every design follows the same mPDF-safe rules the original CoverBuilder
 * documents (see the long comment at the top of CoverBuilder.php):
 *
 *  - font sizes come from CSS classes, never inline styles on <td>;
 *  - ALL label/colon/value rows (Student + Course) live in ONE <table> so the
 *    colons line up across both sections;
 *  - decorations (frames, bars, panels) are position:fixed, background-filled
 *    <div>s - never CSS borders on fixed boxes, and never overlapping text
 *    that lives in a different box (so z-order can't hide anything);
 *  - page margins are 0 except margin_bottom (the only one PdfService passes
 *    to mPDF, via bottomMarginPt()); left/right/top spacing is padding on a
 *    wrapper <div>;
 *  - spacing uses padding / margin on plain <div>/<p>, not margins on tables.
 */
abstract class BaseTemplate implements Template
{
    protected const PAGE_W = 595.2756;
    protected const PAGE_H = 841.8898;
    protected const MIN_FONT_PT = 6.0;

    // ------------------------------------------------------------------
    // Small utilities
    // ------------------------------------------------------------------

    /**
     * Escape for HTML without double-encoding: most CoverData strings were
     * already escaped by Sanitize::text(), and escaping them twice would
     * print things like "&#039;" literally.
     */
    protected static function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }

    protected static function unescape(string $value): string
    {
        return htmlspecialchars_decode($value, ENT_QUOTES);
    }

    protected static function fs(float $pt, float $scale): float
    {
        return max(self::MIN_FONT_PT, round($pt * $scale, 2));
    }

    protected static function sp(float $pt, float $scale): float
    {
        return max(0.0, round($pt * $scale, 2));
    }

    /** @return array<string, float> scaled font sizes in points */
    protected static function sizes(CoverData $d, float $fontScale): array
    {
        return [
            'bismillah'   => self::fs($d->bismillahFontSize, $fontScale),
            'versity'     => self::fs($d->versityFontSize, $fontScale),
            'dept'        => self::fs($d->deptFontSize, $fontScale),
            'faculty'     => self::fs(max(10.0, $d->deptFontSize * 0.78), $fontScale),
            'student'     => self::fs($d->studentFontSize, $fontScale),
            'course'      => self::fs($d->courseFontSize, $fontScale),
            'topic'       => self::fs($d->topicFontSize, $fontScale),
            'designation' => self::fs(16.0, $fontScale),
            // Like the classic design, the submission line is never shrunk.
            'submission'  => $d->submissionFontSize,
        ];
    }

    // ------------------------------------------------------------------
    // Fixed (page-anchored) boxes
    // ------------------------------------------------------------------

    /** A solid, position:fixed rectangle in page coordinates (points). */
    protected static function box(float $top, float $left, float $w, float $h, string $color): string
    {
        return '<div style="position:fixed; top:' . round($top, 2) . 'pt; left:' . round($left, 2)
            . 'pt; width:' . round($w, 2) . 'pt; height:' . round($h, 2)
            . 'pt; background-color:' . self::e($color) . ';"></div>';
    }

    /** A rectangular frame drawn as four solid bars. */
    protected static function frameBars(float $inset, float $thick, string $color): string
    {
        $w = self::PAGE_W - 2 * $inset;
        $h = self::PAGE_H - 2 * $inset;
        return "\n    " . self::box($inset, $inset, $w, $thick, $color)
            . "\n    " . self::box($inset + $h - $thick, $inset, $w, $thick, $color)
            . "\n    " . self::box($inset, $inset, $thick, $h, $color)
            . "\n    " . self::box($inset, $inset + $w - $thick, $thick, $h, $color);
    }

    // ------------------------------------------------------------------
    // Submission date helpers
    // ------------------------------------------------------------------

    protected static function hasSubmission(CoverData $d): bool
    {
        return $d->showSubmissionDate && $d->submissionDateDisplay !== '';
    }

    /**
     * Rough number of lines $text needs when wrapped into $widthPt at
     * $fontSize (greedy word wrap, ~0.56em average glyph width). Used to
     * reserve enough room for the pinned submission date; it only needs to
     * be a sensible upper-ish estimate, the exact fit is handled by
     * PdfService's shrink loop.
     */
    protected static function estimateLines(string $text, float $widthPt, float $fontSize): int
    {
        $maxChars = max(1, (int) floor($widthPt / max(1.0, $fontSize * 0.56)));
        $lines = 1;
        $cur = 0;
        $words = preg_split('/\s+/', trim($text)) ?: [];
        foreach ($words as $word) {
            $len = mb_strlen($word);
            if ($len === 0) {
                continue;
            }
            if ($cur === 0) {
                $cur = $len;
            } elseif ($cur + 1 + $len <= $maxChars) {
                $cur += 1 + $len;
            } else {
                $lines++;
                $cur = $len;
            }
            if ($cur > $maxChars) { // a single over-long word breaks across lines
                $lines += intdiv($cur - 1, $maxChars);
                $cur = $cur % $maxChars === 0 ? $maxChars : $cur % $maxChars;
            }
        }
        return $lines;
    }

    protected static function submissionLines(CoverData $d, float $widthPt): int
    {
        return self::estimateLines(
            'Submission Date: ' . self::unescape($d->submissionDateDisplay),
            $widthPt,
            $d->submissionFontSize
        );
    }

    // ------------------------------------------------------------------
    // Content
    // ------------------------------------------------------------------

    /**
     * The visible detail rows, grouped. Values are already HTML-safe.
     *
     * @return array{student: list<array{0:string,1:string}>, course: list<array{0:string,1:string}>, group: list<array{0:string,1:string}>, designation: string}
     */
    protected static function collectRows(CoverData $d): array
    {
        $student = [];
        if ($d->showStudentName)    { $student[] = ['Name', $d->studentName]; }
        if ($d->showStudentId)      { $student[] = ['ID', $d->studentId]; }
        if ($d->showStudentSection) { $student[] = ['Section', $d->studentSection]; }
        if ($d->showStudentBatch)   { $student[] = ['Batch', $d->studentBatch]; }
        if ($d->showStudentProgram) { $student[] = ['Program', $d->studentProgram]; }
        if ($d->showSemester)       { $student[] = [$d->semesterType, $d->semester]; }
        if ($d->showStudentSession) { $student[] = ['Session', $d->studentSession]; }
        if ($d->showStudentEmail)   { $student[] = ['Email', $d->studentEmail]; }

        $course = [];
        if ($d->showCourseCode)        { $course[] = ['Code', $d->courseCode]; }
        if ($d->showCourseTitle)       { $course[] = ['Title', $d->courseTitleHtml]; }
        if ($d->showCourseTeacherName) { $course[] = ['Teacher', $d->courseTeacherName]; }

        $designation = ($d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== '')
            ? $d->courseTeacherDesignation
            : '';

        $group = [];
        if ($d->showGroup) {
            if ($d->groupName !== '') {
                $group[] = ['Group', $d->groupName];
            }
            foreach ($d->groupMembers as $i => $member) {
                $value = $member['name'] . ($member['id'] !== '' ? ' (' . $member['id'] . ')' : '');
                $group[] = [(string) ($i + 1), $value];
            }
        }

        return ['student' => $student, 'course' => $course, 'group' => $group, 'designation' => $designation];
    }

    /** Public-facing text of the optional cover title ("Lab Report No. 3"), or ''. */
    protected static function coverTitleText(CoverData $d): string
    {
        return $d->showCoverTitle ? $d->coverTitle : '';
    }

    /**
     * The university logo as an <img> block. $plate (a hex colour) puts it
     * on a solid plate - used when the logo sits on a coloured band, where
     * a dark or transparent logo could otherwise disappear.
     */
    protected static function logoHtml(CoverData $d, string $align = 'center', ?string $plate = null, float $scale = 1.0, float $maxWidth = 260.0): string
    {
        if ($d->logo === null) {
            return '';
        }
        $h = max(20.0, round($d->logoHeight * $scale, 2));
        $w = round($h * $d->logo['w'] / max(1, $d->logo['h']), 2);
        if ($w > $maxWidth) {
            $w = $maxWidth;
            $h = round($w * $d->logo['h'] / max(1, $d->logo['w']), 2);
        }
        $img = '<img src="' . $d->logo['uri'] . '" style="width:' . $w . 'pt; height:' . $h . 'pt;" />';

        if ($plate !== null) {
            $img = '<table align="' . $align . '" cellpadding="0" cellspacing="0"><tr>'
                . '<td style="background-color:' . self::e($plate) . '; padding:6pt;">' . $img . '</td></tr></table>';
            return '<div style="margin-bottom:8pt;">' . $img . '</div>';
        }
        return '<div style="text-align:' . $align . '; margin-bottom:8pt;">' . $img . '</div>';
    }

    /**
     * Bismillah, logo, university, faculty and department, as the page
     * header. Options ($o):
     *   align      'center' (default) | 'left'
     *   scale      font scale, so the logo shrinks together with the text
     *   colors     inline text colours per element (bismillah, versity,
     *              faculty, dept) - set whenever the header sits on a
     *              coloured fill, because inline colours always win
     *   plate      hex colour for a plate behind the logo
     *   logo       false to leave the logo out (the design places it itself)
     *
     * @param array<string, mixed> $o
     */
    protected static function headerBlock(CoverData $d, array $o = []): string
    {
        $align  = ($o['align'] ?? 'center') === 'left' ? 'left' : 'center';
        $colors = is_array($o['colors'] ?? null) ? $o['colors'] : [];
        $color  = static fn (string $k): string => isset($colors[$k]) ? ' style="color:' . self::e((string) $colors[$k]) . ';"' : '';

        $html = '';
        if ($d->showBismillah) {
            $html .= '<p class="bismillah"' . $color('bismillah') . '>&#xFDFD;</p>';
        }
        if (($o['logo'] ?? true) !== false) {
            $html .= self::logoHtml($d, $align, isset($o['plate']) ? (string) $o['plate'] : null, (float) ($o['scale'] ?? 1.0));
        }
        if ($d->showVersityName) {
            $html .= '<h1 class="versity-name"' . $color('versity') . '>' . $d->versityName . '</h1>';
        }
        if ($d->showFaculty && $d->faculty !== '') {
            $html .= '<p class="faculty-name"' . $color('faculty') . '>' . $d->faculty . '</p>';
        }
        if ($d->showDeptName) {
            $html .= '<p class="dept-name"' . $color('dept') . '>' . $d->deptName . '</p>';
        }
        return '<div class="header-block" style="text-align:' . $align . ';">' . $html . '</div>';
    }

    /**
     * The optional cover title ("ASSIGNMENT", "LAB REPORT NO. 3").
     * Options: align, color, size (pt, default 20), scale, before / after
     * (spacing in pt), spacing (letter-spacing in pt).
     *
     * @param array<string, mixed> $o
     */
    protected static function titleBlock(CoverData $d, array $o = []): string
    {
        $text = self::coverTitleText($d);
        if ($text === '') {
            return '';
        }
        $size   = self::fs((float) ($o['size'] ?? 20.0), (float) ($o['scale'] ?? 1.0));
        $color  = self::e((string) ($o['color'] ?? $d->primaryColor));
        $align  = (string) ($o['align'] ?? 'center');
        $align  = in_array($align, ['left', 'right', 'center'], true) ? $align : 'center';
        $before = (float) ($o['before'] ?? 10.0);
        $after  = (float) ($o['after'] ?? 10.0);
        $ls     = (float) ($o['spacing'] ?? 3.0);

        return '<div style="margin-top:' . $before . 'pt; margin-bottom:' . $after . 'pt;">'
            . '<p class="cover-title" style="text-align:' . $align . '; font-size:' . $size . 'pt; color:' . $color
            . '; letter-spacing:' . $ls . 'pt;">' . $text . '</p></div>';
    }

    /**
     * Label-above-value blocks (used by the two-column designs).
     * Options: labelColor, valueColor, labelSize, valueSize, gap (pt after
     * each pair), align, upper (bool: upper-case labels).
     *
     * @param list<array{0:string,1:string}> $rows
     * @param array<string, mixed> $o
     */
    protected static function stackedRows(CoverData $d, array $rows, array $o): string
    {
        $lc = self::e((string) ($o['labelColor'] ?? $d->primaryColor));
        $vc = self::e((string) ($o['valueColor'] ?? $d->secondaryColor));
        $ls = (float) ($o['labelSize'] ?? 10.0);
        $vs = (float) ($o['valueSize'] ?? 16.0);
        $gap = (float) ($o['gap'] ?? 8.0);
        $align = (string) ($o['align'] ?? 'left');
        $align = in_array($align, ['left', 'right', 'center'], true) ? $align : 'left';
        $upper = !array_key_exists('upper', $o) || $o['upper'];
        $fPri = self::e($d->primaryFont);
        $fSec = self::e($d->secondaryFont);

        $out = '';
        foreach ($rows as [$label, $valueHtml]) {
            $out .= '<p style="margin:0; text-align:' . $align . '; font-family:' . $fPri . '; font-size:' . $ls . 'pt; color:' . $lc
                . '; letter-spacing:1pt;' . ($upper ? ' text-transform:uppercase;' : '') . '">' . self::e($label) . '</p>'
                . '<p style="margin:0 0 ' . $gap . 'pt 0; text-align:' . $align . '; font-family:' . $fSec . '; font-size:' . $vs . 'pt; color:' . $vc . ';">'
                . $valueHtml . '</p>';
        }
        return $out;
    }

    /**
     * The single Student / Course / Group details table.
     *
     * Options ($o):
     *   colon        bool   include the ":" column (default true)
     *   zebra        bool   alternate row shading (see 'alt')
     *   spacer       bool   gap between sections as a spacer row instead of
     *                       padding-top on the section header cell
     *   gapPt        float  space between sections
     *   firstGapPt   float  space above the first section header
     *   hStyle / lStyle / cStyle / vStyle
     *                string inline CSS for header / label / colon / value
     *                cells. Anything colour-critical (text on a fill) goes
     *                here, not in the stylesheet: inline styles always win.
     *   titles       array  custom (HTML) section headings by key:
     *                       student / course / group
     *   alt          array  ['l' => css, 'c' => css, 'v' => css] added to the
     *                       cells of every second row when 'zebra' is on
     *
     * Cell classes: section-h, label, colon, value, grp-student/grp-course
     * (font size per group), lab-student/lab-course (label-only hooks),
     * designation.
     *
     * @param array<string, mixed> $o
     */
    protected static function detailsTable(CoverData $d, array $rows, array $o = []): string
    {
        $present = [
            'student' => !empty($rows['student']),
            'course'  => !empty($rows['course']) || $rows['designation'] !== '',
            'group'   => !empty($rows['group']),
        ];
        if (!in_array(true, $present, true)) {
            return '';
        }

        $colon    = $o['colon'] ?? true;
        $cols     = $colon ? 3 : 2;
        $zebra    = !empty($o['zebra']);
        $spacer   = !empty($o['spacer']);
        $gap      = (float) ($o['gapPt'] ?? 0.0);
        $firstGap = (float) ($o['firstGapPt'] ?? 0.0);
        $suffix   = $d->headerSuffix !== '' ? ' ' . self::e($d->headerSuffix) : '';
        $alt      = is_array($o['alt'] ?? null) ? $o['alt'] : [];
        $titles   = is_array($o['titles'] ?? null) ? $o['titles'] : [];
        $style    = static function (string $base, string $extra = ''): string {
            $css = trim($base . ' ' . $extra);
            return $css === '' ? '' : ' style="' . $css . '"';
        };

        $sections = [
            ['student', 'Student Details', 'student'],
            ['course',  'Course Details',  'course'],
            ['group',   'Group Details',   'student'],
        ];

        $body = '';
        $emitted = false;
        foreach ($sections as [$key, $title, $grp]) {
            if (!$present[$key]) {
                continue;
            }
            $pad = $emitted ? $gap : $firstGap;
            $padStyle = '';
            if ($pad > 0) {
                if ($spacer) {
                    $body .= '<tr><td colspan="' . $cols . '" style="height:' . $pad
                        . 'pt; font-size:1pt; line-height:1pt;">&nbsp;</td></tr>';
                } else {
                    $padStyle = 'padding-top:' . $pad . 'pt;';
                }
            }
            $body .= '<tr><td colspan="' . $cols . '" class="section-h"'
                . $style($padStyle, (string) ($o['hStyle'] ?? '')) . '>' . ($titles[$key] ?? $title . $suffix) . '</td></tr>';

            $i = 0;
            foreach ($rows[$key] as [$label, $valueHtml]) {
                $isAlt = $zebra && $i % 2 === 1;
                $cls = 'grp-' . $grp . ' lab-' . $grp;
                $body .= '<tr><td class="label ' . $cls . '"'
                    . $style((string) ($o['lStyle'] ?? ''), $isAlt ? (string) ($alt['l'] ?? '') : '') . '>' . self::e($label) . '</td>';
                if ($colon) {
                    $body .= '<td class="colon grp-' . $grp . '"'
                        . $style((string) ($o['cStyle'] ?? ''), $isAlt ? (string) ($alt['c'] ?? '') : '') . '>:</td>';
                }
                $body .= '<td class="value grp-' . $grp . '"'
                    . $style((string) ($o['vStyle'] ?? ''), $isAlt ? (string) ($alt['v'] ?? '') : '') . '>' . $valueHtml . '</td></tr>';
                $i++;
            }

            if ($key === 'course' && $rows['designation'] !== '') {
                $body .= '<tr><td colspan="' . ($cols - 1) . '" class="blank"></td>'
                    . '<td class="designation">' . $rows['designation'] . '</td></tr>';
            }
            $emitted = true;
        }

        return '<table class="grid" cellpadding="0" cellspacing="0">' . $body . '</table>';
    }

    /**
     * CSS shared by every design: fonts, colours and per-group font sizes.
     * Designs append their own layout rules afterwards (later rules win).
     */
    protected static function commonCss(CoverData $d, float $fontScale, float $spacingScale, float $sectionPt): string
    {
        $z    = self::sizes($d, $fontScale);
        $acc  = self::e($d->accentColor);
        $pri  = self::e($d->primaryColor);
        $sec  = self::e($d->secondaryColor);
        $fVer = self::e($d->versityFont);
        $fPri = self::e($d->primaryFont);
        $fSec = self::e($d->secondaryFont);

        $sectionSize = self::fs($sectionPt, $fontScale);
        $bisGap      = self::sp(4.0, $spacingScale);
        $deptGap     = self::sp(14.0, $spacingScale);
        $desGap      = self::sp(2.0, $spacingScale);

        return <<<CSS
body { margin: 0; padding: 0; color: {$sec}; }
.header-block { text-align: center; }
.header-block p, .header-block h1 { margin: 0; }
.bismillah {
    font-family: amiri;
    font-size: {$z['bismillah']}pt;
    color: {$sec};
    margin-bottom: {$bisGap}pt !important;
}
.versity-name {
    font-size: {$z['versity']}pt;
    font-weight: lighter !important;
    font-family: {$fVer};
    color: {$acc};
}
.dept-name {
    font-size: {$z['dept']}pt;
    font-weight: lighter !important;
    font-family: {$fSec};
    color: {$sec};
    margin-bottom: {$deptGap}pt !important;
}
table.grid { width: 100%; border-collapse: collapse; margin: 0; }
table.grid td { padding: 0; vertical-align: top; }
table.grid td.section-h {
    font-size: {$sectionSize}pt;
    font-weight: bold;
    font-family: {$fPri} !important;
    color: {$pri};
    text-align: left;
}
table.grid td.label { color: {$pri}; font-family: {$fSec}; white-space: nowrap; }
table.grid td.colon { color: {$pri}; text-align: left; padding: 0 4pt; }
table.grid td.value { color: {$sec}; font-family: {$fSec}; width: 100%; }
table.grid td.grp-student { font-size: {$z['student']}pt; }
table.grid td.grp-course { font-size: {$z['course']}pt; }
table.grid td.designation {
    font-size: {$z['designation']}pt;
    font-family: {$fSec};
    color: {$sec};
    padding-top: {$desGap}pt;
}
.faculty-name {
    font-size: {$z['faculty']}pt;
    font-family: {$fSec};
    color: {$sec};
    margin-bottom: {$desGap}pt !important;
}
.cover-title {
    margin: 0;
    font-family: {$fPri} !important;
    font-weight: bold;
    text-transform: uppercase;
    color: {$pri};
}
.topic-label {
    margin: 0;
    font-family: {$fPri} !important;
    font-weight: bold;
    color: {$pri};
}
.topic-text {
    margin: 0;
    font-size: {$z['topic']}pt;
    font-family: {$fSec};
    color: {$sec};
}

CSS;
    }

    /** Wraps CSS + body markup into a complete HTML document for mPDF. */
    protected static function document(string $css, string $body): string
    {
        return "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"UTF-8\" />\n<style>\n"
            . $css
            . "\n</style>\n</head>\n<body>\n"
            . $body
            . "\n</body>\n</html>";
    }
}
