<?php

declare(strict_types=1);

namespace App\Templates;

use App\CoverData;

/**
 * The "Formal" family: the classic university submission layout.
 *
 *     University
 *     Department
 *     Program: LLB (Hons)
 *
 *     Submitted to ---          (teacher name, "(Designation)" underneath)
 *     Submitted by ---          (Name / Id / Batch ...)
 *     [Group Details ---]
 *     Course Details ---        (Course Title / Course Code)
 *
 *     Submission date: ...
 *     Assignment Topic: ...
 *
 * Unlike the other designs the submission date is NOT pinned to the page
 * bottom: it flows between the course details and the topic, exactly as on
 * the paper format this family copies. The Program value moves up into the
 * header (so it is left out of "Submitted by"), and the label wording
 * (Id, Course Title, Course Code ...) follows that format too.
 *
 * Variants only decide the page decoration and the header treatment; they
 * override decor() / thumbDecor() and, where needed, the small hooks below.
 * All mPDF rules from BaseTemplate apply: one table per block, fixed boxes
 * for decoration, spacing as padding on plain <div>s.
 */
abstract class SubmissionFormatTemplate extends BaseTemplate
{
    protected const SECTION_SIZE = 24.0;

    /** Page decoration (fixed boxes), drawn only when "Show decorative border" is on. */
    abstract protected function decor(CoverData $d): string;

    /** Thumbnail shapes for the decoration. */
    abstract protected function thumbDecor(): string;

    // ---- hooks ------------------------------------------------------

    protected function sidePt(CoverData $d): float
    {
        return 66.0;
    }

    protected function topPt(CoverData $d): float
    {
        return 60.0;
    }

    protected function bottomPt(CoverData $d): float
    {
        return 60.0;
    }

    protected function headerAlign(): string
    {
        return 'center';
    }

    /** Draw a rule under each section heading? */
    protected function ruledHeadings(): bool
    {
        return false;
    }

    /** True when header() is placed inside the padded content box (false for a full-width band). */
    protected function headerInsidePad(): bool
    {
        return true;
    }

    // ---- Template interface -----------------------------------------

    public function category(): string
    {
        return 'Formal';
    }

    public function bottomMarginPt(CoverData $d, float $spacingScale, float $fontScale): float
    {
        return $this->bottomPt($d);
    }

    public function thumbnailSvg(): string
    {
        $svg = $this->thumbDecor()
            . ThumbKit::r(46, 14, 28, 2.6, 's')
            . ThumbKit::r(20, 20, 80, 7, 'a')
            . ThumbKit::r(36, 31, 48, 3.2, 's')
            . ThumbKit::r(42, 37, 36, 2.4, 's');

        // Submitted to
        $svg .= ThumbKit::r(16, 48, 44, 4, 'p')
            . ThumbKit::r(22, 55, 46, 3, 's')
            . ThumbKit::r(22, 60, 32, 2.2, 's');
        // Submitted by
        $svg .= ThumbKit::r(16, 70, 40, 4, 'p') . ThumbKit::rows(20, 18, 44, 34, 77, 3, 6.2);
        // Course details
        $svg .= ThumbKit::r(16, 99, 44, 4, 'p') . ThumbKit::rows(20, 24, 50, 34, 106, 2, 6.2);
        // Submission date + topic
        $svg .= ThumbKit::r(16, 124, 24, 2.6, 'p') . ThumbKit::r(44, 124, 36, 2.6, 'a')
            . ThumbKit::r(16, 134, 34, 2.6, 'p')
            . ThumbKit::r(16, 140, 82, 3, 's')
            . ThumbKit::r(16, 146, 58, 3, 's');

        return ThumbKit::svg($svg);
    }

    // ---- content ----------------------------------------------------

    /** @return list<array{0:string,1:string}> */
    private function studentRows(CoverData $d): array
    {
        $r = [];
        if ($d->showStudentName)    { $r[] = ['Name', $d->studentName]; }
        if ($d->showStudentId)      { $r[] = ['Id', $d->studentId]; }
        if ($d->showStudentBatch)   { $r[] = ['Batch', $d->studentBatch]; }
        if ($d->showStudentSection) { $r[] = ['Section', $d->studentSection]; }
        if ($d->showSemester)       { $r[] = [$d->semesterType, $d->semester]; }
        if ($d->showStudentSession) { $r[] = ['Session', $d->studentSession]; }
        if ($d->showStudentEmail)   { $r[] = ['Email', $d->studentEmail]; }
        return $r;
    }

    /** @return list<array{0:string,1:string}> */
    private function courseRows(CoverData $d): array
    {
        $r = [];
        if ($d->showCourseTitle) { $r[] = ['Course Title', $d->courseTitleHtml]; }
        if ($d->showCourseCode)  { $r[] = ['Course Code', $d->courseCode]; }
        return $r;
    }

    /** "Program: LLB (Hons)" under the department. Skipped when empty. */
    protected function programLine(CoverData $d, string $align, ?string $color = null): string
    {
        if (!$d->showStudentProgram || $d->studentProgram === '') {
            return '';
        }
        $style = 'text-align:' . $align . ';' . ($color !== null ? ' color:' . self::e($color) . ';' : '');
        return '<div class="header-block" style="text-align:' . $align . ';"><p class="program" style="' . $style . '">'
            . 'Program: ' . $d->studentProgram . '</p></div>';
    }

    /** Header (Bismillah, logo, university, faculty, department, program). Variants may wrap it. */
    protected function header(CoverData $d, float $fontScale): string
    {
        $align = $this->headerAlign();
        return self::headerBlock($d, ['scale' => $fontScale, 'align' => $align])
            . $this->programLine($d, $align);
    }

    private function heading(CoverData $d, string $icon, string $text, string $suffix, float $gapTop): string
    {
        // The icon name also identifies the heading: teacher = Submitted to, student = Submitted by, book = Course Details.
        $key  = ['teacher' => 'teacher', 'student' => 'student', 'group' => 'group', 'book' => 'course'][$icon] ?? 'student';
        $size = self::headCss($d, $key);
        $pt   = $d->headingSize($key);
        // "Submitted to" has no icon; the others match the heading colour.
        $html = ($icon === 'teacher' ? '' : ($pt !== null ? self::iconPt($d, $icon, $d->primaryColor, $pt * 0.8) : self::icon($d, $icon))) . $text . $suffix;
        $inline = $size !== '' ? ' style="' . trim($size) . '"' : '';
        if ($this->ruledHeadings()) {
            return '<div style="margin-top:' . $gapTop . 'pt;"><table width="100%" cellpadding="0" cellspacing="0"><tr>'
                . '<td class="sub-h-cell"' . $inline . '>' . $html . '</td></tr></table></div>';
        }
        return '<div style="margin-top:' . $gapTop . 'pt;"><p class="sub-h"' . $inline . '>' . $html . '</p></div>';
    }

    /**
     * @param list<array{0:string,1:string}> $rows
     */
    private function rowsTable(array $rows, string $grp): string
    {
        $body = '';
        foreach ($rows as [$label, $valueHtml]) {
            $body .= '<tr><td class="label grp-' . $grp . '">' . self::e($label) . '</td>'
                . '<td class="colon grp-' . $grp . '">:</td>'
                . '<td class="value grp-' . $grp . '">' . $valueHtml . '</td></tr>';
        }
        return '<div class="blk"><table class="grid" cellpadding="0" cellspacing="0">' . $body . '</table></div>';
    }

    /** "(Lecturer, CGED)" - the brackets are added unless the user already typed them. */
    private function bracketed(string $designationEscaped): string
    {
        $plain = trim(self::unescape($designationEscaped));
        if ($plain !== '' && $plain[0] === '(' && substr($plain, -1) === ')') {
            return $designationEscaped;
        }
        return '(' . $designationEscaped . ')';
    }

    public function buildHtml(CoverData $d, float $fontScale, float $spacingScale): string
    {
        $z    = self::sizes($d, $fontScale);
        $acc  = self::e($d->accentColor);
        $pri  = self::e($d->primaryColor);
        $sec  = self::e($d->secondaryColor);
        $fPri = self::e($d->primaryFont);
        $fSec = self::e($d->secondaryFont);
        $side = $this->sidePt($d);
        $top  = self::sp($this->topPt($d), $spacingScale);

        $blockGap = self::sp(24.0, $spacingScale);
        $headGap  = self::sp(4.0, $spacingScale);
        $rowPad   = self::sp(2.0, $spacingScale);
        $indent   = self::sp(18.0, $spacingScale);
        $program  = $z['program'];
        $dateSize = $z['submission'];
        $topicSize = $z['topic'];
        $section  = self::fs(self::SECTION_SIZE, $fontScale);

        // commonCss() first: it records the font sizes the icon helpers use.
        $css = self::commonCss($d, $fontScale, $spacingScale, self::SECTION_SIZE);
        $ruleBorder = $this->ruledHeadings() ? "border-bottom: 1.5pt solid {$acc}; padding-bottom: 4pt;" : '';
        $css .= <<<CSS
div.pad { padding: {$top}pt {$side}pt 0 {$side}pt; }
.dept-name, .faculty-last { margin-bottom: 2pt !important; }
.program {
    margin: 0 0 4pt 0;
    font-size: {$program}pt;
    font-family: {$fSec};
    color: {$sec};
}
.sub-h {
    margin: 0;
    font-size: {$section}pt;
    font-weight: bold;
    font-family: {$fPri} !important;
    color: {$pri};
    text-align: left;
}
td.sub-h-cell {
    font-size: {$section}pt;
    font-weight: bold;
    font-family: {$fPri} !important;
    color: {$pri};
    text-align: left;
    padding: 0 0 4pt 0;
    {$ruleBorder}
}
div.blk { padding: {$headGap}pt 0 0 {$indent}pt; }
table.grid td.label, table.grid td.colon, table.grid td.value { padding-top: {$rowPad}pt; padding-bottom: {$rowPad}pt; }
table.grid td.label { white-space: nowrap; padding-right: 2pt; }
.t-name { margin: 0; font-size: {$z['course']}pt; font-family: {$fSec}; color: {$sec}; }
.t-des { margin: 2pt 0 0 0; font-size: {$z['designation']}pt; font-family: {$fSec}; color: {$sec}; }
.sub-line { margin: 0; font-size: {$dateSize}pt; font-family: {$fSec}; color: {$sec}; }
.topic-line { margin: 0; font-size: {$topicSize}pt; font-family: {$fSec}; color: {$sec}; }
.lab { font-family: {$fPri} !important; font-weight: bold; color: {$pri}; }

CSS;

        $suffix = $d->headerSuffix !== '' ? ' ' . self::e($d->headerSuffix) : '';
        $parts = [];
        $first = true;
        $gap = static function () use (&$first, $blockGap): float {
            $g = $first ? 0.0 : $blockGap;
            $first = false;
            return $g;
        };

        // ---- Submitted to ----
        $toLines = '';
        if ($d->showCourseTeacherName && $d->courseTeacherName !== '') {
            $toLines .= '<p class="t-name">' . $d->courseTeacherName . '</p>';
        }
        if ($d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== '') {
            $toLines .= '<p class="t-des">' . $this->bracketed($d->courseTeacherDesignation) . '</p>';
        }
        if ($toLines !== '') {
            $parts[] = $this->heading($d, 'teacher', 'Submitted to', $suffix, $gap()) . '<div class="blk">' . $toLines . '</div>';
        }

        // ---- Submitted by (+ group) ----
        $byRows = $this->studentRows($d);
        if ($byRows !== []) {
            $parts[] = $this->heading($d, 'student', 'Submitted by', $suffix, $gap()) . $this->rowsTable($byRows, 'student');
        }
        $groupRows = self::collectRows($d)['group'];
        if ($groupRows !== []) {
            $parts[] = $this->heading($d, 'group', 'Group Details', $suffix, $gap()) . $this->rowsTable($groupRows, 'student');
        }

        // ---- Course details ----
        $courseRows = $this->courseRows($d);
        if ($courseRows !== []) {
            $parts[] = $this->heading($d, 'book', 'Course Details', $suffix, $gap()) . $this->rowsTable($courseRows, 'course');
        }

        // ---- Submission date (in the flow; may be left blank to write by hand) ----
        if ($d->showSubmissionDate) {
            $parts[] = '<div style="margin-top:' . $gap() . 'pt;"><p class="sub-line">'
                . self::iconPt($d, 'clock', $d->primaryColor, $dateSize * 0.9)
                . '<span class="lab">Submission date:</span> ' . self::e($d->submissionDateDisplay) . '</p></div>';
        }

        // ---- Assignment topic ----
        if ($d->showTopic) {
            $parts[] = '<div style="margin-top:' . $gap() . 'pt;"><p class="topic-line">'
                . self::iconPt($d, 'topic', $d->primaryColor, $topicSize * 0.85)
                . '<span class="lab">Assignment Topic:</span> ' . $d->topicHtml . '</p></div>';
        }

        $title = self::titleBlock($d, [
            'scale' => $fontScale, 'color' => $d->accentColor, 'align' => $this->headerAlign(),
            'before' => 10.0, 'after' => self::sp(6.0, $spacingScale), 'size' => 19.0,
        ]);
        $lead = $title !== '' ? $title : '<div style="height:' . self::sp(14.0, $spacingScale) . 'pt; font-size:1pt; line-height:1pt;">&nbsp;</div>';

        $decor  = $d->showBorder ? $this->decor($d) : '';
        $header = $this->header($d, $fontScale);
        $rest   = $lead . implode('', $parts);

        $body = $decor . "\n";
        $body .= $this->headerInsidePad()
            ? '<div class="pad">' . $header . $rest . '</div>'
            : $header . '<div class="pad">' . $rest . '</div>';

        return self::document($css, $body);
    }
}
