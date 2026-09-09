<?php

declare(strict_types=1);

namespace App;

/**
 * Renders a CoverData object into the HTML that gets fed to mPDF.
 *
 * The original reference design uses CSS Grid for the label/colon/value
 * rows, which mPDF's HTML/CSS engine does not support. Every grid row is
 * reproduced here as a small fixed-layout <table> instead (80pt label
 * column, 15pt colon column, flexible value column) so the printed result
 * matches the reference pixel-for-pixel while remaining renderable by mPDF.
 *
 * Two important mPDF-specific constraints drive the structure below:
 *
 * 1. Font sizes must be declared via a CSS class in the <style> block, not
 *    via an inline style="" attribute on a <td>. mPDF's table layout engine
 *    computes cell metrics from the stylesheet in an earlier pass and does
 *    not reliably pick up per-cell inline font-size overrides. Each
 *    editable font-size group therefore gets its own CSS class (e.g.
 *    .grid-student, .grid-course) with the size baked into the class rule.
 *
 * 2. The page border must NOT be a separate, absolutely-positioned div.
 *    mPDF's position:absolute does not reliably remove an element from the
 *    page's normal flow the way a browser does — an empty div given an
 *    explicit height to visually span the page still reserves that height
 *    in the flow, which pushed all real content onto a spurious page 2
 *    (with the border alone on a blank page 1). The border is instead a
 *    plain CSS `border` + `padding` on the same block that holds the
 *    content, with no explicit `height` anywhere, so it hugs the content
 *    naturally and a second page only appears when content genuinely does
 *    not fit on one page.
 */
final class CoverBuilder
{
    // Outer inset between the page edge and the border/content area.
    private const CONTENT_INSET_PT = 15.0;

    public static function buildHtml(CoverData $d): string
    {
        $font = fn (string $key) => htmlspecialchars($key, ENT_QUOTES);
        $col  = fn (string $hex) => htmlspecialchars($hex, ENT_QUOTES);

        $borderPadding = $d->showBorder ? 20.0 : 0.0;
        $insetPt       = self::CONTENT_INSET_PT;
        $borderRule    = $d->showBorder ? "border: {$borderPadding}pt solid {$col($d->accentColor)};" : '';

        $rows = [];

        if ($d->showStudentName) {
            $rows['student'][] = self::row('Name', $d->studentName);
        }
        if ($d->showStudentId) {
            $rows['student'][] = self::row('ID', $d->studentId);
        }
        if ($d->showStudentSection) {
            $rows['student'][] = self::row('Section', $d->studentSection);
        }
        if ($d->showStudentBatch) {
            $rows['student'][] = self::row('Batch', $d->studentBatch);
        }
        if ($d->showStudentProgram) {
            $rows['student'][] = self::row('Program', $d->studentProgram);
        }
        if ($d->showSemester) {
            $rows['student'][] = self::row($d->semesterType, $d->semester);
        }

        if ($d->showCourseCode) {
            $rows['course'][] = self::row('Code', $d->courseCode);
        }
        if ($d->showCourseTitle) {
            $rows['course'][] = self::row('Title', $d->courseTitleHtml);
        }
        if ($d->showCourseTeacherName) {
            $rows['course'][] = self::row('Teacher', $d->courseTeacherName);
        }

        // "grid-student"/"grid-course" carry the group's editable font
        // size (declared in <style> below); "grid" carries the shared
        // structural rules (column widths, alignment, etc).
        $studentRowsHtml = implode('', array_map(
            static fn (string $row) => str_replace('class="grid"', 'class="grid grid-student"', $row),
            $rows['student'] ?? []
        ));
        $courseRowsHtml = implode('', array_map(
            static fn (string $row) => str_replace('class="grid"', 'class="grid grid-course"', $row),
            $rows['course'] ?? []
        ));

        $studentSection = '';
        if (!empty($rows['student'])) {
            $studentSection = '
                <h2 class="section-h">Student Details ' . htmlspecialchars($d->headerSuffix, ENT_QUOTES) . '</h2>
                <div class="grid-wrap">' . $studentRowsHtml . '</div>';
        }

        $courseSection = '';
        if (!empty($rows['course']) || $d->showCourseTeacherDesignation) {
            $designationHtml = $d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== ''
                ? '<p class="designation">' . htmlspecialchars($d->courseTeacherDesignation, ENT_QUOTES) . '</p>'
                : '';
            $courseSection = '
                <h2 class="section-h" style="margin-top:20pt;">Course Details ' . htmlspecialchars($d->headerSuffix, ENT_QUOTES) . '</h2>
                <div class="grid-wrap">' . $courseRowsHtml . '</div>'
                . $designationHtml;
        }

        $bismillah = $d->showBismillah
            ? '<p class="bismillah">&#xFDFD;</p>'
            : '';

        $versityBlock = $d->showVersityName
            ? '<h1 class="versity-name">' . htmlspecialchars($d->versityName, ENT_QUOTES) . '</h1>'
            : '';

        $deptBlock = $d->showDeptName
            ? '<p class="dept-name">' . htmlspecialchars($d->deptName, ENT_QUOTES) . '</p>'
            : '';

        $topicBlock = '';
        if ($d->showTopic) {
            $topicBlock = '
                <table class="grid topic-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="topic-label">Topic</td>
                        <td class="colon topic-colon">:</td>
                        <td class="topic-value">' . $d->topicHtml . '</td>
                    </tr>
                </table>';
        }

        $submissionBlock = '';
        if ($d->showSubmissionDate && $d->submissionDateDisplay !== '') {
            $submissionBlock = '<p class="submission"><b>Submission Date:</b> '
                . htmlspecialchars($d->submissionDateDisplay, ENT_QUOTES) . '</p>';
        }

        $topicLabelSize = $d->topicFontSize + 2;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<style>
    body {
        margin: 0;
        padding: 0;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .page-frame {
        padding: {$insetPt}pt;
    }
    .content {
        {$borderRule}
        padding: {$borderPadding}pt;
    }
    header { text-align: center; }
    header p, header h1 { margin: 0; }
    .bismillah {
        font-family: amiri;
        font-size: {$d->bismillahFontSize}pt;
        color: {$col($d->secondaryColor)};
        margin-bottom: 4pt !important;
    }
    .versity-name {
        font-size: {$d->versityFontSize}pt;
        font-weight: bold;
        font-family: {$font($d->versityFont)};
        color: {$col($d->accentColor)};
    }
    .dept-name {
        font-size: {$d->deptFontSize}pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        margin-bottom: 500px;
    }
    .section-h {
        font-size: 26pt;
        font-weight: bold;
        font-family: {$font($d->primaryFont)};
        color: {$col($d->primaryColor)};
        margin: 0;
    }
    .grid-wrap { margin-left: 25pt; }
    table.grid { width: 100%; border-collapse: collapse; margin: 0; }
    table.grid td {
        font-family: {$font($d->secondaryFont)};
        padding: 0;
        vertical-align: top;
    }
    table.grid td.label { width: 80pt; color: {$col($d->primaryColor)}; }
    table.grid td.colon { width: 15pt; text-align: center; margin-left: 15px; }
    table.grid td.value { color: {$col($d->secondaryColor)}; }
    table.grid-student td { font-size: {$d->studentFontSize}pt; }
    table.grid-course td { font-size: {$d->courseFontSize}pt; }
    .designation {
        margin: 2pt 0 0 0;
        text-align: center;
        font-size: 16pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .topic-grid { margin-left: 50pt; margin-top: 500px; }
    .topic-label {
        width: 80pt;
        font-weight: bold;
        font-size: {$topicLabelSize}pt;
        font-family: {$font($d->primaryFont)};
        color: {$col($d->primaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .topic-colon { width: 15pt; vertical-align: top; padding: 0; font-size: {$d->topicFontSize}pt; }
    .topic-value {
        font-size: {$d->topicFontSize}pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .submission {
        font-size: {$d->submissionFontSize}pt;
        margin-top: 15pt;
        margin-left: 0;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
</style>
</head>
<body>
    <div class="page-frame">
        <div class="content">
            <header>
                {$bismillah}
                {$versityBlock}
                {$deptBlock}
            </header>
            {$studentSection}
            {$courseSection}
            {$topicBlock}
            {$submissionBlock}
        </div>
    </div>
</body>
</html>
HTML;
    }

    private static function row(string $label, string $valueHtml): string
    {
        $labelSafe = htmlspecialchars($label, ENT_QUOTES);
        return '
                    <table class="grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label">' . $labelSafe . '</td>
                            <td class="colon">:</td>
                            <td class="value">' . $valueHtml . '</td>
                        </tr>
                    </table>';
    }
}
