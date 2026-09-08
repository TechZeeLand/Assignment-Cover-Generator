<?php

declare(strict_types=1);

namespace App;

/**
 * Renders a CoverData object into the HTML that gets fed to mPDF.
 *
 * The original reference design uses CSS Grid for the label/colon/value
 * rows, which mPDF's HTML/CSS engine does not support. Every grid row is
 * reproduced here as a small fixed-layout <table> instead so the printed
 * result matches the reference while remaining renderable by mPDF.
 *
 * The label column width is NOT a hardcoded guess: it's measured with
 * mPDF's own font metrics (Mpdf::GetStringWidth) against whichever labels
 * are actually visible, so the colon always lines up immediately after the
 * widest visible label - in both the Student Details and Course Details
 * sections at once, since both use the same computed width.
 */
final class CoverBuilder
{
    /** pt-per-mm, matches \Mpdf\Mpdf::SCALE (72 / 25.4). GetStringWidth()
     *  returns millimetres internally regardless of the document's units,
     *  so every measurement below is converted back to points with this. */
    private const MM_TO_PT = 72 / 25.4;

    private const ROW_FONT_SIZE_PT   = 24.0;
    private const TOPIC_FONT_SIZE_PT = 26.0;

    /** Breathing room added after the measured label text, before the colon. */
    private const LABEL_BUFFER_PT = 8.0;
    private const LABEL_MIN_WIDTH_PT = 40.0;

    private const PAGE_PADDING_PT   = 15.0; // .page padding, all sides
    private const BORDER_WIDTH_PT   = 15.0; // .border-box border width when shown
    private const BORDER_PADDING_PT = 20.0; // .border-box padding when shown

    /** Gap between the header block (Bismillah/Versity/Dept) and "Student Details". */
    private const STUDENT_HEADER_GAP_PT = 20.0;
    /** Gap between the end of Student Details and "Course Details". */
    private const COURSE_HEADER_GAP_PT = 20.0;
    /** Gap between the end of Course Details (incl. designation) and "Topic". */
    private const TOPIC_HEADER_GAP_PT = 26.0;

    public static function buildHtml(CoverData $d, \Mpdf\Mpdf $mpdf): string
    {
        $font = fn (string $key) => htmlspecialchars($key, ENT_QUOTES);
        $col  = fn (string $hex) => htmlspecialchars($hex, ENT_QUOTES);

        $showBorder     = $d->showBorder;
        $borderWidthPt  = $showBorder ? self::BORDER_WIDTH_PT : 0.0;
        $borderPadPt    = $showBorder ? self::BORDER_PADDING_PT : 0.0;
        $borderRule     = $showBorder ? "border: {$borderWidthPt}pt solid {$col($d->accentColor)};" : '';
        $pagePaddingPt  = self::PAGE_PADDING_PT;
        $topicGapPt     = self::TOPIC_HEADER_GAP_PT;

        // A4 = 595.28 x 841.89pt. .page reserves PAGE_PADDING_PT on every
        // side (content-box, no border on .page itself). .border-box then
        // adds its own border + padding on every side (also content-box),
        // so its CSS "height" must be the *remaining* space after those are
        // subtracted - not the full remaining page height, or the border
        // falls short of the page edge (previously this used a mismatched
        // 20pt stand-in for the border's actual 15pt width, so the box was
        // ~10pt short of the page every time it was drawn).
        $pageHeightPt      = 841.89;
        $pageInnerHeightPt = $pageHeightPt - 2 * $pagePaddingPt;
        $borderBoxHeightPt = $pageInnerHeightPt - 2 * $borderWidthPt - 2 * $borderPadPt;

        // ---- Collect the rows that will actually be shown, so the width
        // measurement below only considers labels that are on the page. ----
        $studentRows = [];
        if ($d->showStudentName)    $studentRows[] = ['Name', htmlspecialchars($d->studentName, ENT_QUOTES)];
        if ($d->showStudentId)      $studentRows[] = ['ID', htmlspecialchars($d->studentId, ENT_QUOTES)];
        if ($d->showStudentSection) $studentRows[] = ['Section', htmlspecialchars($d->studentSection, ENT_QUOTES)];
        if ($d->showStudentBatch)   $studentRows[] = ['Batch', htmlspecialchars($d->studentBatch, ENT_QUOTES)];
        if ($d->showStudentProgram) $studentRows[] = ['Program', htmlspecialchars($d->studentProgram, ENT_QUOTES)];
        if ($d->showSemester)       $studentRows[] = [$d->semesterType, htmlspecialchars($d->semester, ENT_QUOTES)];

        $courseRows = [];
        if ($d->showCourseCode)        $courseRows[] = ['Code', htmlspecialchars($d->courseCode, ENT_QUOTES)];
        if ($d->showCourseTitle)       $courseRows[] = ['Title', $d->courseTitleHtml];
        if ($d->showCourseTeacherName) $courseRows[] = ['Teacher', htmlspecialchars($d->courseTeacherName, ENT_QUOTES)];

        $allLabels = array_merge(
            array_map(fn ($r) => $r[0], $studentRows),
            array_map(fn ($r) => $r[0], $courseRows)
        );
        $labelColWidthPt = self::measureLabelColumnWidth($mpdf, $allLabels, $d->secondaryFont, self::ROW_FONT_SIZE_PT);
        $topicColWidthPt = self::measureLabelColumnWidth($mpdf, ['Topic'], $d->secondaryFont, self::TOPIC_FONT_SIZE_PT);

        $studentRowsHtml = implode('', array_map(fn ($r) => self::row($r[0], $r[1]), $studentRows));
        $courseRowsHtml  = implode('', array_map(fn ($r) => self::row($r[0], $r[1]), $courseRows));

        $headerHasContent = $d->showBismillah || $d->showVersityName || $d->showDeptName;
        $studentTopGap    = $headerHasContent ? self::STUDENT_HEADER_GAP_PT : 0.0;

        $studentSection = '';
        if (!empty($studentRows)) {
            $studentSection = '
                <h2 class="section-h" style="margin-top:' . $studentTopGap . 'pt;">Student Details ' . htmlspecialchars($d->headerSuffix, ENT_QUOTES) . '</h2>
                <div class="grid-wrap">' . $studentRowsHtml . '</div>';
        }

        $courseSection = '';
        if (!empty($courseRows) || $d->showCourseTeacherDesignation) {
            $designationHtml = $d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== ''
                ? '<p class="designation">' . htmlspecialchars($d->courseTeacherDesignation, ENT_QUOTES) . '</p>'
                : '';
            $courseSection = '
                <h2 class="section-h" style="margin-top:' . self::COURSE_HEADER_GAP_PT . 'pt;">Course Details ' . htmlspecialchars($d->headerSuffix, ENT_QUOTES) . '</h2>
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
        padding: {$pagePaddingPt}pt;
        height: {$pageInnerHeightPt}pt;
    }
    .border-box {
        {$borderRule}
        padding: {$borderPadPt}pt;
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
        margin: 0;
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
    table.grid td.label { width: {$labelColWidthPt}pt; color: {$col($d->primaryColor)}; }
    table.grid td.colon { width: 16pt; text-align: center; }
    table.grid td.value { color: {$col($d->secondaryColor)}; }
    .designation {
        margin: 2pt 0 0 0;
        text-align: center;
        font-size: 16pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .topic-grid { margin-left: 25pt; margin-top: {$topicGapPt}pt; }
    .topic-label {
        width: {$topicColWidthPt}pt;
        font-weight: bold;
        font-size: 26pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->primaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .topic-colon { width: 16pt; vertical-align: top; padding: 0; text-align: center; }
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

    /**
     * Measures the widest of $labels in mPDF's own metrics for $fontKey at
     * $sizePt, and returns a column width (in pt) generous enough to fit
     * it plus a small buffer. Falls back to a safe default if the font
     * can't be loaded for measurement (e.g. mid-upload race), so a label
     * measurement failure never breaks PDF generation.
     */
    private static function measureLabelColumnWidth(\Mpdf\Mpdf $mpdf, array $labels, string $fontKey, float $sizePt): float
    {
        $labels = array_filter($labels, fn ($l) => $l !== '');
        if (empty($labels)) {
            return self::LABEL_MIN_WIDTH_PT;
        }

        try {
            $mpdf->SetFont($fontKey, '', $sizePt, false);
            $maxMm = 0.0;
            foreach ($labels as $label) {
                $w = $mpdf->GetStringWidth($label);
                if ($w > $maxMm) {
                    $maxMm = $w;
                }
            }
            $widthPt = $maxMm * self::MM_TO_PT + self::LABEL_BUFFER_PT;
            return max($widthPt, self::LABEL_MIN_WIDTH_PT);
        } catch (\Throwable $e) {
            return self::LABEL_MIN_WIDTH_PT + 20.0;
        }
    }
}
