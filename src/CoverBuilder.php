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
 * Page/border layout: mPDF silently ignores an explicit CSS `height` on any
 * block that has a <table> among its descendants, and does not reliably
 * support calc()/box-sizing. A previous version of this class fought that by
 * hand-computing a `height` for the div wrapping the content tables — but
 * since that div *does* contain tables, mPDF ignored the height anyway, and
 * the hand-computed numbers drifted from reality, so a normal amount of
 * content could overflow onto a spurious second page.
 *
 * The fix: the decorative border is drawn by a separate, empty
 * `.border-frame` div (no table descendants, so its explicit height is
 * honoured reliably), absolutely positioned so it never participates in —
 * or constrains — the content's normal flow. The actual content
 * (`.content`) has no explicit height at all; it simply flows, and mPDF
 * only starts a second page if the content genuinely does not fit, which is
 * correct/expected behaviour rather than an artifact of a miscalculated box.
 */
final class CoverBuilder
{
    // A4 page size in points at mPDF's default 0 page margins.
    private const PAGE_WIDTH_PT  = 595.28;
    private const PAGE_HEIGHT_PT = 841.89;

    // Outer inset between the page edge and the border/content area.
    private const CONTENT_INSET_PT = 15.0;

    public static function buildHtml(CoverData $d): string
    {
        $font = fn (string $key) => htmlspecialchars($key, ENT_QUOTES);
        $col  = fn (string $hex) => htmlspecialchars($hex, ENT_QUOTES);

        $borderPadding = $d->showBorder ? 20.0 : 0.0;
        $insetPt       = self::CONTENT_INSET_PT;

        // Size of the area available for the border frame, inset from the
        // page edge by CONTENT_INSET_PT on every side.
        $frameWidthPt  = self::PAGE_WIDTH_PT - 2 * self::CONTENT_INSET_PT;
        $frameHeightPt = self::PAGE_HEIGHT_PT - 2 * self::CONTENT_INSET_PT;

        // The border div uses the (default) content-box model: its declared
        // width/height plus its own border thickness must equal the frame
        // size above, so the border's outer edge lands exactly at the frame
        // boundary regardless of border thickness.
        $borderFrameHtml = '';
        if ($d->showBorder) {
            $borderContentW = $frameWidthPt - 2 * $borderPadding;
            $borderContentH = $frameHeightPt - 2 * $borderPadding;
            $borderFrameHtml = '<div class="border-frame" style="'
                . "top:{$insetPt}pt;left:{$insetPt}pt;"
                . "width:{$borderContentW}pt;height:{$borderContentH}pt;"
                . "border:{$borderPadding}pt solid {$col($d->accentColor)};"
                . '"></div>';
        }

        $rows = [];

        if ($d->showStudentName) {
            $rows['student'][] = self::row('Name', $d->studentName, $d->studentFontSize);
        }
        if ($d->showStudentId) {
            $rows['student'][] = self::row('ID', $d->studentId, $d->studentFontSize);
        }
        if ($d->showStudentSection) {
            $rows['student'][] = self::row('Section', $d->studentSection, $d->studentFontSize);
        }
        if ($d->showStudentBatch) {
            $rows['student'][] = self::row('Batch', $d->studentBatch, $d->studentFontSize);
        }
        if ($d->showStudentProgram) {
            $rows['student'][] = self::row('Program', $d->studentProgram, $d->studentFontSize);
        }
        if ($d->showSemester) {
            $rows['student'][] = self::row($d->semesterType, $d->semester, $d->studentFontSize);
        }

        if ($d->showCourseCode) {
            $rows['course'][] = self::row('Code', $d->courseCode, $d->courseFontSize);
        }
        if ($d->showCourseTitle) {
            $rows['course'][] = self::row('Title', $d->courseTitleHtml, $d->courseFontSize);
        }
        if ($d->showCourseTeacherName) {
            $rows['course'][] = self::row('Teacher', $d->courseTeacherName, $d->courseFontSize);
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
            ? '<p class="bismillah" style="font-size:' . $d->bismillahFontSize . 'pt;">&#xFDFD;</p>'
            : '';

        $versityBlock = $d->showVersityName
            ? '<h1 class="versity-name" style="font-size:' . $d->versityFontSize . 'pt;">'
                . htmlspecialchars($d->versityName, ENT_QUOTES) . '</h1>'
            : '';

        $deptBlock = $d->showDeptName
            ? '<p class="dept-name" style="font-size:' . $d->deptFontSize . 'pt;">'
                . htmlspecialchars($d->deptName, ENT_QUOTES) . '</p>'
            : '';

        $topicBlock = '';
        if ($d->showTopic) {
            $topicLabelSize = $d->topicFontSize + 2;
            $topicBlock = '
                <table class="grid topic-grid" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="topic-label" style="font-size:' . $topicLabelSize . 'pt;">Topic</td>
                        <td class="colon topic-colon" style="font-size:' . $d->topicFontSize . 'pt;">:</td>
                        <td class="topic-value" style="font-size:' . $d->topicFontSize . 'pt;">' . $d->topicHtml . '</td>
                    </tr>
                </table>';
        }

        $submissionBlock = '';
        if ($d->showSubmissionDate && $d->submissionDateDisplay !== '') {
            $submissionBlock = '<p class="submission" style="font-size:' . $d->submissionFontSize . 'pt;"><b>Submission Date:</b> '
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
    .page-frame {
        position: relative;
        padding: {$insetPt}pt;
    }
    .border-frame {
        position: absolute;
    }
    .content {
        padding: {$borderPadding}pt;
    }
    header { text-align: center; }
    header p, header h1 { margin: 0; }
    .bismillah {
        font-family: amiri;
        color: {$col($d->secondaryColor)};
        margin-bottom: 4pt !important;
    }
    .versity-name {
        font-weight: bold;
        font-family: {$font($d->versityFont)};
        color: {$col($d->accentColor)};
    }
    .dept-name {
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        margin-bottom: 50pt !important;
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
    .designation {
        margin: 2pt 0 0 0;
        text-align: center;
        font-size: 16pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .topic-grid { margin-left: 25pt; margin-top: 50pt; }
    .topic-label {
        width: 80pt;
        font-weight: bold;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->primaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .topic-colon { width: 15pt; vertical-align: top; padding: 0; }
    .topic-value {
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        vertical-align: top;
        padding: 0;
    }
    .submission {
        margin-top: 15pt;
        margin-left: 0;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
</style>
</head>
<body>
    <div class="page-frame">
        {$borderFrameHtml}
        <div class="content">
            <header>
                {$bismillah}
                {$versityBlock}
                {$deptBlock}
            </header>
            <br>
            {$studentSection}
            {$courseSection}
            <br>
            {$topicBlock}
            {$submissionBlock}
        </div>
    </div>
</body>
</html>
HTML;
    }

    private static function row(string $label, string $valueHtml, float $fontSizePt): string
    {
        $labelSafe = htmlspecialchars($label, ENT_QUOTES);
        $sizeStyle = 'font-size:' . $fontSizePt . 'pt;';
        return '
                    <table class="grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="label" style="' . $sizeStyle . '">' . $labelSafe . '</td>
                            <td class="colon" style="' . $sizeStyle . '">:</td>
                            <td class="value" style="' . $sizeStyle . '">' . $valueHtml . '</td>
                        </tr>
                    </table>';
    }
}
