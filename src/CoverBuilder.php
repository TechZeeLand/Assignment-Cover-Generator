<?php

declare(strict_types=1);

namespace App;

/**
 * Renders a CoverData object into the HTML that gets fed to mPDF.
 *
 * mPDF's HTML/CSS engine doesn't support CSS Grid, so every label/colon/
 * value row is built as a small HTML <table> instead. Student Details and
 * Course Details are rendered as ONE shared <table> (with colspan="3"
 * section-header rows) rather than one table per row: mPDF sizes a table's
 * columns once, based on the widest cell in that table, so putting every
 * row in the same table is what forces every colon in both sections to
 * line up under the widest label - separate per-row tables can't do this,
 * since each one would size its own label column independently.
 *
 * The decorative border is a separate, absolutely-positioned empty <div>
 * pinned to fixed pt coordinates on the page, completely decoupled from
 * the content flow. The actual reading margin (space between the border
 * and the text) is reserved as real mPDF page margins (set in
 * PdfService::render() via contentInsetPt()), not CSS padding - mPDF
 * shrink-wraps block heights to content and doesn't reliably support
 * calc()/box-sizing, so a content-flow-based box previously either clipped
 * the border to the content height or left it sized wrong. Page margins
 * and the border position are derived from the same constants below so
 * the two can never drift out of sync.
 */
final class CoverBuilder
{
    // A4 in points at 72pt/inch.
    private const PAGE_WIDTH_PT  = 595.28;
    private const PAGE_HEIGHT_PT = 841.89;

    // Space from the physical page edge to the decorative border line.
    private const OUTER_GAP_PT = 15.0;

    // Thickness of the decorative border stroke itself.
    private const BORDER_THICKNESS_PT = 15.0;

    // Space between the border line and where text content starts.
    private const CONTENT_GAP_PT = 20.0;

    /**
     * Total inset from the physical page edge to where content starts.
     * Used both to size/position the decorative border and as the real
     * mPDF page margin (see PdfService::render()), so they can't go out
     * of sync. This inset is constant whether or not the border is shown,
     * so toggling the border never reflows the content.
     */
    public static function contentInsetPt(): float
    {
        return self::OUTER_GAP_PT + self::BORDER_THICKNESS_PT + self::CONTENT_GAP_PT;
    }

    public static function buildHtml(CoverData $d): string
    {
        $font = fn (string $key) => htmlspecialchars($key, ENT_QUOTES);
        $col  = fn (string $hex) => htmlspecialchars($hex, ENT_QUOTES);

        $borderDiv = '';
        if ($d->showBorder) {
            $boxWidth  = self::PAGE_WIDTH_PT - 2 * self::OUTER_GAP_PT - 2 * self::BORDER_THICKNESS_PT;
            $boxHeight = self::PAGE_HEIGHT_PT - 2 * self::OUTER_GAP_PT - 2 * self::BORDER_THICKNESS_PT;
            $borderDiv = sprintf(
                '<div style="position:absolute; top:%1$spt; left:%1$spt; width:%2$spt; height:%3$spt; border:%4$spt solid %5$s;"></div>',
                self::numFmt(self::OUTER_GAP_PT),
                self::numFmt($boxWidth),
                self::numFmt($boxHeight),
                self::numFmt(self::BORDER_THICKNESS_PT),
                $col($d->accentColor)
            );
        }

        $studentRows = [];
        if ($d->showStudentName) {
            $studentRows[] = self::row('Name', $d->studentName);
        }
        if ($d->showStudentId) {
            $studentRows[] = self::row('ID', $d->studentId);
        }
        if ($d->showStudentSection) {
            $studentRows[] = self::row('Section', $d->studentSection);
        }
        if ($d->showStudentBatch) {
            $studentRows[] = self::row('Batch', $d->studentBatch);
        }
        if ($d->showStudentProgram) {
            $studentRows[] = self::row('Program', $d->studentProgram);
        }
        if ($d->showSemester) {
            $studentRows[] = self::row($d->semesterType, $d->semester);
        }

        $courseRows = [];
        if ($d->showCourseCode) {
            $courseRows[] = self::row('Code', $d->courseCode);
        }
        if ($d->showCourseTitle) {
            $courseRows[] = self::row('Title', $d->courseTitleHtml, true);
        }
        if ($d->showCourseTeacherName) {
            $courseRows[] = self::row('Teacher', $d->courseTeacherName);
        }

        $hasStudentSection = !empty($studentRows);
        $hasCourseSection  = !empty($courseRows) || $d->showCourseTeacherDesignation;

        $tableRows = '';
        if ($hasStudentSection) {
            $tableRows .= self::sectionHeaderRow('Student Details ' . $d->headerSuffix, false);
            $tableRows .= implode('', $studentRows);
        }
        if ($hasCourseSection) {
            $tableRows .= self::sectionHeaderRow('Course Details ' . $d->headerSuffix, $hasStudentSection);
            $tableRows .= implode('', $courseRows);
        }

        $detailsBlock = '';
        if ($tableRows !== '') {
            $designationHtml = $d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== ''
                ? '<p class="designation">' . htmlspecialchars($d->courseTeacherDesignation, ENT_QUOTES) . '</p>'
                : '';
            $detailsBlock = '
                <div class="details-wrap">
                    <table class="details-table" cellpadding="0" cellspacing="0">' . $tableRows . '</table>'
                    . $designationHtml . '
                </div>';
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
                <div class="topic-wrap">
                    <table class="topic-grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="topic-label">Topic</td>
                            <td class="topic-colon">:</td>
                            <td class="topic-value">' . $d->topicHtml . '</td>
                        </tr>
                    </table>
                </div>';
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
    .cover-header { text-align: center; }
    .cover-header p, .cover-header h1 { margin: 0; }
    .bismillah {
        font-family: amiri;
        font-size: 16pt;
        color: {$col($d->secondaryColor)};
        margin-bottom: 4pt;
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
        margin: 0 0 4pt;
    }
    .details-wrap { margin-top: 24pt; }
    table.details-table { width: 100%; border-collapse: collapse; margin: 0; }
    table.details-table td {
        font-size: 24pt;
        font-family: {$font($d->secondaryFont)};
        padding: 0;
        vertical-align: top;
    }
    table.details-table td.section-header {
        font-size: 26pt;
        font-weight: bold;
        font-family: {$font($d->primaryFont)};
        color: {$col($d->primaryColor)};
        padding: 0;
    }
    table.details-table td.section-header.course-header { padding-top: 20pt; }
    table.details-table td.label {
        padding-left: 25pt;
        color: {$col($d->primaryColor)};
        white-space: nowrap;
    }
    table.details-table td.colon { text-align: center; padding: 0 4pt; }
    table.details-table td.value { color: {$col($d->secondaryColor)}; }
    .designation {
        margin: 2pt 0 0 0;
        text-align: center;
        font-size: 16pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .topic-wrap { margin-left: 25pt; margin-top: 24pt; }
    table.topic-grid { width: 100%; border-collapse: collapse; margin: 0; }
    .topic-label {
        width: 80pt;
        font-weight: bold;
        font-size: 26pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->primaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .topic-colon {
        width: 18pt;
        font-size: 26pt;
        text-align: center;
        vertical-align: top;
        padding: 0;
    }
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
    {$borderDiv}
    <div class="cover-header">
        {$bismillah}
        {$versityBlock}
        {$deptBlock}
    </div>
    {$detailsBlock}
    {$topicBlock}
    {$submissionBlock}
</body>
</html>
HTML;
    }

    private static function row(string $label, string $valueHtml, bool $isRichText = false): string
    {
        $labelSafe = htmlspecialchars($label, ENT_QUOTES);
        return '
                        <tr>
                            <td class="label">' . $labelSafe . '</td>
                            <td class="colon">:</td>
                            <td class="value">' . $valueHtml . '</td>
                        </tr>';
    }

    private static function sectionHeaderRow(string $text, bool $isCourseHeader): string
    {
        $safe  = htmlspecialchars($text, ENT_QUOTES);
        $class = $isCourseHeader ? 'section-header course-header' : 'section-header';
        return '
                        <tr>
                            <td colspan="3" class="' . $class . '">' . $safe . '</td>
                        </tr>';
    }

    /** Formats a pt value for embedding in inline CSS without trailing zeros/locale surprises. */
    private static function numFmt(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
