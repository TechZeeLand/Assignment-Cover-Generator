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
     * @return array{student: list<array{0:string,1:string}>, course: list<array{0:string,1:string}>, designation: string}
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

        $course = [];
        if ($d->showCourseCode)        { $course[] = ['Code', $d->courseCode]; }
        if ($d->showCourseTitle)       { $course[] = ['Title', $d->courseTitleHtml]; }
        if ($d->showCourseTeacherName) { $course[] = ['Teacher', $d->courseTeacherName]; }

        $designation = ($d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== '')
            ? $d->courseTeacherDesignation
            : '';

        return ['student' => $student, 'course' => $course, 'designation' => $designation];
    }

    /** Bismillah + university + department, as the page header. */
    protected static function headerBlock(CoverData $d): string
    {
        $html = '';
        if ($d->showBismillah) {
            $html .= '<p class="bismillah">&#xFDFD;</p>';
        }
        if ($d->showVersityName) {
            $html .= '<h1 class="versity-name">' . $d->versityName . '</h1>';
        }
        if ($d->showDeptName) {
            $html .= '<p class="dept-name">' . $d->deptName . '</p>';
        }
        return '<div class="header-block">' . $html . '</div>';
    }

    /**
     * The single Student/Course details table.
     *
     * Options ($o):
     *   colon        bool   include the ":" column (default true)
     *   zebra        bool   alternate row shading via class "alt"
     *   spacer       bool   gap between sections as a spacer row instead of
     *                       padding-top on the section header cell
     *   gapPt        float  space between the two sections
     *   firstGapPt   float  space above the first section header
     *
     * Cell classes: section-h, label, colon, value, grp-student/grp-course
     * (font size per group), lab-student/lab-course (label-only hooks),
     * alt (zebra), designation.
     *
     * @param array<string, mixed> $o
     */
    protected static function detailsTable(CoverData $d, array $rows, array $o = []): string
    {
        $hasStudent = !empty($rows['student']);
        $hasCourse  = !empty($rows['course']) || $rows['designation'] !== '';
        if (!$hasStudent && !$hasCourse) {
            return '';
        }

        $colon    = $o['colon'] ?? true;
        $cols     = $colon ? 3 : 2;
        $zebra    = !empty($o['zebra']);
        $spacer   = !empty($o['spacer']);
        $gap      = (float) ($o['gapPt'] ?? 0.0);
        $firstGap = (float) ($o['firstGapPt'] ?? 0.0);
        $suffix   = $d->headerSuffix !== '' ? ' ' . self::e($d->headerSuffix) : '';

        $body = '';
        $emitted = false;

        $sections = [
            ['student', 'Student Details', 'student', $hasStudent],
            ['course',  'Course Details',  'course',  $hasCourse],
        ];

        foreach ($sections as [$key, $title, $grp, $present]) {
            if (!$present) {
                continue;
            }
            $pad = $emitted ? $gap : $firstGap;
            $style = '';
            if ($pad > 0) {
                if ($spacer) {
                    $body .= '<tr><td colspan="' . $cols . '" style="height:' . $pad
                        . 'pt; font-size:1pt; line-height:1pt;">&nbsp;</td></tr>';
                } else {
                    $style = ' style="padding-top:' . $pad . 'pt;"';
                }
            }
            $body .= '<tr><td colspan="' . $cols . '" class="section-h"' . $style . '>' . $title . $suffix . '</td></tr>';

            $i = 0;
            foreach ($rows[$key] as [$label, $valueHtml]) {
                $alt = ($zebra && $i % 2 === 1) ? ' alt' : '';
                $cls = 'grp-' . $grp . ' lab-' . $grp . $alt;
                $body .= '<tr><td class="label ' . $cls . '">' . self::e($label) . '</td>';
                if ($colon) {
                    $body .= '<td class="colon grp-' . $grp . $alt . '">:</td>';
                }
                $body .= '<td class="value grp-' . $grp . $alt . '">' . $valueHtml . '</td></tr>';
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
