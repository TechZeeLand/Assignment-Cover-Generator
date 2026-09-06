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
 */
final class CoverBuilder
{
    public static function buildHtml(CoverData $d): string
    {
        $font = fn (string $key) => htmlspecialchars($key, ENT_QUOTES);
        $col  = fn (string $hex) => htmlspecialchars($hex, ENT_QUOTES);

        $borderPadding = $d->showBorder ? 20 : 0;
        $borderRule    = $d->showBorder ? "border: 15pt solid {$col($d->accentColor)};" : '';

        // mPDF's HTML/CSS engine shrink-wraps block heights to content and
        // doesn't reliably support calc()/box-sizing, so the full-page
        // border height has to be computed by hand here instead (the live
        // preview gets this "for free" from calc(100% - ...) in style.css,
        // which is why the two used to look different).
        //   A4 = 595.28 x 841.89pt at mPDF's default 0 margins.
        //   .page has a 15pt padding + 1pt border on every side.
        //   .border-box then has its own border+padding ($borderPadding,
        //   used for both) on every side.
        $pageHeightPt      = 841.89;
        $pageInnerHeightPt = $pageHeightPt - 2 * (15 + 1); // .page's content-box height
        $borderBoxHeightPt = $pageInnerHeightPt - 4 * $borderPadding; // minus border-box's own border+padding, top+bottom

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
            $rows['course'][] = self::row('Title', $d->courseTitleHtml, true);
        }
        if ($d->showCourseTeacherName) {
            $rows['course'][] = self::row('Teacher', $d->courseTeacherName);
        }

        $studentRowsHtml = implode('', $rows['student'] ?? []);
        $courseRowsHtml  = implode('', $rows['course'] ?? []);

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
    .page {
        padding: 15pt;
        height: {$pageInnerHeightPt}pt;
    }
    .border-box {
        {$borderRule}
        padding: {$borderPadding}pt;
        height: {$borderBoxHeightPt}pt;
    }
    header { text-align: center; }
    header p, header h1 { margin: 0; }
    .bismillah {
        font-family: amiri;
        font-size: 16pt;
        color: {$col($d->secondaryColor)};
        margin-bottom: 4pt !important;
    }
    .versity-name {
        font-size: 30pt;
        font-weight: bold;
        font-family: {$font($d->versityFont)};
        color: {$col($d->accentColor)};
    }
    .dept-name {
        font-size: 22pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        margin-bottom: 10pt !important;
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
        font-size: 24pt;
        font-family: {$font($d->secondaryFont)};
        padding: 0;
        vertical-align: top;
    }
    table.grid td.label { width: 80pt; color: {$col($d->primaryColor)}; }
    table.grid td.colon { width: 15pt; text-align: center; margin-left: 15px; }
    table.grid td.value { color: {$col($d->secondaryColor)}; }
    .designation {
        margin: 2pt 0 0 0;
        text-align: center;
        font-size: 16pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .topic-grid { margin-left: 25pt; margin-top: 20pt; }
    .topic-label {
        width: 80pt;
        font-weight: bold;
        font-size: 26pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->primaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .topic-colon { width: 15pt; vertical-align: top; padding: 0; }
    .topic-value {
        font-size: 24pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .submission {
        font-size: 18pt;
        margin-top: 15pt;
        margin-left: 0;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
</style>
</head>
<body>
    <div class="page">
        <div class="border-box">
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

    private static function row(string $label, string $valueHtml, bool $isRichText = false): string
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
