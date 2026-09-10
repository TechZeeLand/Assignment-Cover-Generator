<?php

declare(strict_types=1);

namespace App;

/**
 * Renders a CoverData object into the HTML that gets fed to mPDF.
 *
 * Several mPDF-specific constraints drive the structure below:
 *
 * 1. Font sizes must be declared via a CSS class in the <style> block, not
 *    via an inline style="" attribute on a <td>. mPDF's table layout engine
 *    computes cell metrics from the stylesheet in an earlier pass and does
 *    not reliably pick up per-cell inline font-size overrides.
 *
 * 2. Colon alignment across "Student Details" and "Course Details" requires
 *    every label/colon/value row - from BOTH sections - to live in a single
 *    <table>. mPDF (like a browser) sizes auto-layout table columns once per
 *    <table>; two separate tables would each size their own label column
 *    independently and the colons would drift apart. The section headers
 *    are therefore rows inside that same table (colspan="3"), not separate
 *    elements outside it.
 *
 * 3. The page border must always span the full page, independent of how
 *    much content is present, and must never itself consume vertical flow
 *    space (an empty div with an explicit height still reserves that height
 *    in mPDF's normal flow, which pushes real content onto a spurious
 *    second page). CSS `position: fixed` is mPDF's supported mechanism for
 *    content that is pinned to a fixed spot on every page without
 *    occupying flow space (the same mechanism mPDF documents for running
 *    headers/footers/watermarks), so the border - and the submission date,
 *    which must always sit at the bottom of the page above the border even
 *    when the rest of the content ends much higher up - are both built as
 *    position:fixed blocks anchored to page coordinates.
 *
 * 4. Margins must never be relied upon for spacing on <table> elements or
 *    inside the <header> tag: mPDF's handling of both is unreliable (a
 *    margin on a <table> can silently be overridden by a broader selector
 *    such as `table.grid { margin: 0 }` sized for a different purpose, and
 *    margins on children of <header> are not dependably applied). Spacing
 *    between blocks is therefore applied via margin on plain <div>/<p>
 *    wrappers, and indentation is applied via padding on table cells
 *    instead of margin on the table.
 *
 * $fontScale and $spacingScale let PdfService shrink the page in small
 * steps (fonts and whitespace independently) so a page that would otherwise
 * spill onto a second page can be brought back down to exactly one, without
 * ever needing to touch the content itself.
 */
final class CoverBuilder
{
    // ---- Page geometry (points). A4 at 72pt/inch: 210mm x 297mm. ----
    private const PAGE_WIDTH_PT  = 595.2756;
    private const PAGE_HEIGHT_PT = 841.8898;

    // Gap between the physical page edge and the border stroke.
    private const OUTER_INSET_PT = 15.0;

    // Border stroke thickness.
    private const BORDER_THICKNESS_PT = 18.0;

    // Gap between the border's inner edge and normal content (top/left/right).
    private const CONTENT_PADDING_PT = 20.0;

    // Small breathing gap between the border's inner edge and the
    // submission-date line pinned above it.
    private const SUBMISSION_BORDER_GAP_PT = 6.0;

    // Fixed font size for both section headers ("Student Details" /
    // "Course Details") and, per request, the "Topic" label - so the two
    // always match regardless of the user-editable Topic font size.
    private const SECTION_HEADER_FONT_SIZE = 34.0;

    private const MIN_FONT_SCALE_PT = 6.0;

    public static function pageWidthPt(): float
    {
        return self::PAGE_WIDTH_PT;
    }

    public static function pageHeightPt(): float
    {
        return self::PAGE_HEIGHT_PT;
    }

    public static function ptToMm(float $pt): float
    {
        return $pt * 25.4 / 72;
    }

    /**
     * How much vertical space (points) must be reserved at the bottom of
     * the normal content flow so the pinned submission-date line - and the
     * border beneath it - never overlap real content. Sized generously
     * enough for the line to wrap onto two lines without touching the
     * border.
     */
    public static function bottomReservePt(CoverData $d, float $spacingScale = 1.0): float
    {
        if (!$d->showSubmissionDate || $d->submissionDateDisplay === '') {
            return self::CONTENT_PADDING_PT;
        }
        $gap = max(3.0, self::SUBMISSION_BORDER_GAP_PT * $spacingScale);
        $lineHeight = $d->submissionFontSize * 1.32;
        // Reserve room for up to two lines in case the date line wraps.
        return $gap + ($lineHeight * 2) + 4.0;
    }

    /**
     * The page insets (points) that keep normal body content inside the
     * border and, at the bottom, above the pinned submission-date line.
     *
     * Only 'bottom' is ever passed to mPDF as a real page margin. mPDF
     * measures CSS `position: fixed` offsets from the margin box's
     * top-left corner (not the physical page edge) for margin_top /
     * margin_left, but a fixed element positioned near the bottom or
     * right of the page is simply clipped away once margin_bottom /
     * margin_right shrink the margin box - there is no equivalent
     * "reachable" area past those. So margin_top/left/right are always 0
     * (leaving fixed elements free to reach every edge, and 'top'/'left'
     * on them equal to true page-relative points), and the top/left/right
     * inset for ordinary flowing content is applied as CSS padding on a
     * wrapping <div> instead (see buildHtml()). margin_bottom is left as
     * a real mPDF margin because it is what makes mPDF trigger a second
     * page once flowing content would otherwise run into the reserved
     * bottom strip - there is no flow-side equivalent to padding for that.
     *
     * @return array{top: float, right: float, bottom: float, left: float}
     */
    public static function marginsPt(CoverData $d, float $spacingScale = 1.0): array
    {
        $sideInset = self::OUTER_INSET_PT + self::BORDER_THICKNESS_PT + self::CONTENT_PADDING_PT;

        return [
            'top'    => $sideInset,
            'right'  => $sideInset,
            'bottom' => self::OUTER_INSET_PT + self::BORDER_THICKNESS_PT + self::bottomReservePt($d, $spacingScale),
            'left'   => $sideInset,
        ];
    }

    public static function buildHtml(CoverData $d, float $fontScale = 1.0, float $spacingScale = 1.0): string
    {
        $font = fn (string $key) => htmlspecialchars($key, ENT_QUOTES);
        $col  = fn (string $hex) => htmlspecialchars($hex, ENT_QUOTES);

        $fscale = fn (float $pt) => max(self::MIN_FONT_SCALE_PT, round($pt * $fontScale, 2));
        $sscale = fn (float $pt) => max(0.0, round($pt * $spacingScale, 2));

        $margins = self::marginsPt($d, $spacingScale);

        $bismillahFontSize  = $fscale($d->bismillahFontSize);
        $versityFontSize    = $fscale($d->versityFontSize);
        $deptFontSize       = $fscale($d->deptFontSize);
        $studentFontSize    = $fscale($d->studentFontSize);
        $courseFontSize     = $fscale($d->courseFontSize);
        $topicFontSize      = $fscale($d->topicFontSize);
        $submissionFontSize = $fscale($d->submissionFontSize);
        $sectionHeaderSize  = $fscale(self::SECTION_HEADER_FONT_SIZE);

        $deptGapPt        = $sscale(20.0);
        $sectionGapPt     = $sscale(40.0);
        $topicGapPt       = $sscale(20.0);
        $labelIndentPt    = max(4.0, $sscale(25.0));
        $topicIndentPt    = max(4.0, $sscale(25.0));
        $topicLabelColPt  = 10.0;
        $bismillahGapPt   = $sscale(4.0);
        $designationGapPt = $sscale(2.0);

        $rows = [];

        if ($d->showStudentName) {
            $rows['student'][] = ['Name', $d->studentName];
        }
        if ($d->showStudentId) {
            $rows['student'][] = ['ID', $d->studentId];
        }
        if ($d->showStudentSection) {
            $rows['student'][] = ['Section', $d->studentSection];
        }
        if ($d->showStudentBatch) {
            $rows['student'][] = ['Batch', $d->studentBatch];
        }
        if ($d->showStudentProgram) {
            $rows['student'][] = ['Program', $d->studentProgram];
        }
        if ($d->showSemester) {
            $rows['student'][] = [$d->semesterType, $d->semester];
        }

        if ($d->showCourseCode) {
            $rows['course'][] = ['Code', $d->courseCode];
        }
        if ($d->showCourseTitle) {
            $rows['course'][] = ['Title', $d->courseTitleHtml];
        }
        if ($d->showCourseTeacherName) {
            $rows['course'][] = ['Teacher', $d->courseTeacherName];
        }

        $hasStudent = !empty($rows['student']);
        $hasCourse  = !empty($rows['course']) || $d->showCourseTeacherDesignation;

        $detailsTable = '';
        if ($hasStudent || $hasCourse) {
            $body = '';

            if ($hasStudent) {
                $body .= self::headerRow(
                    'Student Details ' . htmlspecialchars($d->headerSuffix, ENT_QUOTES),
                    $hasStudent ? $sectionGapPt : 0.0
                );
                foreach ($rows['student'] as [$label, $valueHtml]) {
                    $body .= self::row($label, $valueHtml, 'grp-student');
                }
            }

            if ($hasCourse) {
                $body .= self::headerRow(
                    'Course Details ' . htmlspecialchars($d->headerSuffix, ENT_QUOTES),
                    $hasStudent ? $sectionGapPt : 0.0
                );
                foreach ($rows['course'] as [$label, $valueHtml]) {
                    $body .= self::row($label, $valueHtml, 'grp-course');
                }
                if ($d->showCourseTeacherDesignation && $d->courseTeacherDesignation !== '') {
                    $body .= '<tr><td colspan="3" class="designation-cell">'
                        . '<p class="designation">' . htmlspecialchars($d->courseTeacherDesignation, ENT_QUOTES) . '</p>'
                        . '</td></tr>';
                }
            }

            $detailsTable = '<table class="grid" cellpadding="0" cellspacing="0">' . $body . '</table>';
        }

        $topicBlock = '';
        if ($d->showTopic) {
            $topicBlock = '
                <div class="topic-wrap" style="margin-top:' . $topicGapPt . 'pt;">
                    <table class="topic-grid" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="topic-label">Topic:</td>
                            <td class="topic-value">' . $d->topicHtml . '</td>
                        </tr>
                    </table>
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

        $submissionFixed = '';
        if ($d->showSubmissionDate && $d->submissionDateDisplay !== '') {
            $bottomReserve  = self::bottomReservePt($d, $spacingScale);
            $gap            = max(3.0, self::SUBMISSION_BORDER_GAP_PT * $spacingScale);
            $submissionLeft = $margins['left'];
            $submissionWidth = self::PAGE_WIDTH_PT - $margins['left'] - $margins['right'];
            // Top of the reserved bottom strip (just inside the border),
            // so the line renders at the top of that strip and grows
            // downward - never touching the border - even if it wraps.
            $submissionTop = self::PAGE_HEIGHT_PT - self::OUTER_INSET_PT - self::BORDER_THICKNESS_PT - $bottomReserve + $gap;

            $submissionFixed = '
    <div class="submission" style="position:fixed; left:' . $submissionLeft . 'pt; top:' . round($submissionTop, 2) . 'pt; width:' . $submissionWidth . 'pt;">
        <p><b>Submission Date:</b> ' . htmlspecialchars($d->submissionDateDisplay, ENT_QUOTES) . '</p>
    </div>';
        }

        $borderFixed = $d->showBorder ? self::borderFrameHtml($col($d->accentColor)) : '';

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<style>
    body {
        margin: 0;
        padding: 0;
        color: {$col($d->secondaryColor)};
    }
    .header-block { text-align: center; }
    .header-block p, .header-block h1 { margin: 0; }
    .bismillah {
        font-family: amiri;
        font-size: {$bismillahFontSize}pt;
        color: {$col($d->secondaryColor)};
        margin-bottom: {$bismillahGapPt}pt !important;
    }
    .versity-name {
        font-size: {$versityFontSize}pt;
        font-family: {$font($d->versityFont)};
        color: {$col($d->accentColor)};
    }
    .dept-name {
        font-size: {$deptFontSize}pt;
        font-weight: lighter !important;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        margin-bottom: {$deptGapPt}pt !important;
    }
    .section-h {
        font-size: {$sectionHeaderSize}pt;
        font-weight: bold;
        font-family: {$font($d->primaryFont)} !important;
        color: {$col($d->primaryColor)};
        text-align: left;
    }
    table.grid { width: 100%; border-collapse: collapse; margin: 0; }
    table.grid td {
        font-family: {$font($d->secondaryFont)};
        padding: 0;
        vertical-align: top;
    }
    table.grid td.label { color: {$col($d->primaryColor)}; padding-left: {$labelIndentPt}pt; white-space: nowrap; }
    table.grid td.colon { text-align: left; padding: 0 4pt; }
    table.grid td.value { color: {$col($d->secondaryColor)}; width: 100%; }
    table.grid td.grp-student { font-size: {$studentFontSize}pt; }
    table.grid td.grp-course { font-size: {$courseFontSize}pt; }
    .designation-cell { padding: 0; text-align: center; }
    .designation {
        margin: {$designationGapPt}pt 0 0 0;
        text-align: center;
        font-size: {$fscale(16.0)}pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .topic-grid { width: 100%; border-collapse: collapse; margin: 0; }
    .topic-label {
        font-weight: bold;
        font-size: {$topicFontSize}pt;
        font-family: {$font($d->primaryFont)} !important;
        color: {$col($d->primaryColor)};
        vertical-align: top;
        padding: 0;
        padding-left: {$topicIndentPt}pt;
        white-space: nowrap;
    }
    .topic-value {
        font-size: {$topicFontSize}pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
        vertical-align: top;
        padding: 0;
        width: auto;
        margin-left: -50pt;
    }
    .submission p {
        margin: 0;
        font-size: {$submissionFontSize}pt;
        font-family: {$font($d->secondaryFont)};
        color: {$col($d->secondaryColor)};
    }
    .content-pad {
        padding-top: {$margins['top']}pt;
        padding-left: {$margins['left']}pt;
        padding-right: {$margins['right']}pt;
    }
</style>
</head>
<body>
    {$borderFixed}
    {$submissionFixed}
    <div class="content-pad">
        <div class="header-block">
            {$bismillah}
            {$versityBlock}
            {$deptBlock}
        </div>
        {$detailsTable}
        {$topicBlock}
    </div>
</body>
</html>
HTML;
    }

    /**
     * Builds the full-page border as four independent filled bars
     * (position:fixed, background-color) rather than a single div with a
     * CSS `border`. A background-filled box's rendered size is exactly its
     * declared width/height with no box-model ambiguity, whereas a `border`
     * shorthand adds the stroke outside (or inside, depending on
     * box-sizing support) the declared box - a source of drift mPDF does
     * not reliably resolve for position:fixed elements. Four bars sidestep
     * that entirely and are trivially exact on every side.
     */
    private static function borderFrameHtml(string $accentColorSafe): string
    {
        $outerW = self::PAGE_WIDTH_PT - (2 * self::OUTER_INSET_PT);
        $outerH = self::PAGE_HEIGHT_PT - (2 * self::OUTER_INSET_PT);
        $t = self::BORDER_THICKNESS_PT;
        $inset = self::OUTER_INSET_PT;

        $bar = function (float $top, float $left, float $width, float $height) use ($accentColorSafe): string {
            return '<div style="position:fixed; top:' . round($top, 2) . 'pt; left:' . round($left, 2) . 'pt; '
                . 'width:' . round($width, 2) . 'pt; height:' . round($height, 2) . 'pt; '
                . 'background-color:' . $accentColorSafe . ';"></div>';
        };

        return "\n    " . $bar($inset, $inset, $outerW, $t)                       // top
            . "\n    " . $bar($inset + $outerH - $t, $inset, $outerW, $t)         // bottom
            . "\n    " . $bar($inset, $inset, $t, $outerH)                        // left
            . "\n    " . $bar($inset, $inset + $outerW - $t, $t, $outerH);        // right
    }

    private static function headerRow(string $labelHtml, float $paddingTopPt): string
    {
        $style = $paddingTopPt > 0 ? ' style="padding-top:' . $paddingTopPt . 'pt;"' : '';
        return '<tr><td colspan="3" class="section-h"' . $style . '>' . $labelHtml . '</td></tr>';
    }

    private static function row(string $label, string $valueHtml, string $groupClass): string
    {
        $labelSafe = htmlspecialchars($label, ENT_QUOTES);
        return '
            <tr>
                <td class="label ' . $groupClass . '">' . $labelSafe . '</td>
                <td class="colon ' . $groupClass . '">:</td>
                <td class="value ' . $groupClass . '">' . $valueHtml . '</td>
            </tr>';
    }
}
