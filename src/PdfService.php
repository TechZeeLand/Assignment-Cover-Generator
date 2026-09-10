<?php

declare(strict_types=1);

namespace App;

final class PdfService
{
    // Safety cap on shrink attempts. Spacing is shrunk first (cheapest,
    // least noticeable), then, only if that alone isn't enough, fonts are
    // shrunk too - both floor out well before this limit is reached for any
    // input the form actually allows.
    private const MAX_FIT_ATTEMPTS = 24;
    private const SPACING_SCALE_FLOOR = 0.35;
    private const SPACING_SCALE_STEP  = 0.13;
    private const FONT_SCALE_FLOOR    = 0.6;
    private const FONT_SCALE_STEP     = 0.04;

    /**
     * Renders the cover to a single page. If the content is long enough
     * that it would naturally spill onto a second page, this re-renders
     * with progressively tighter spacing and, if needed, slightly smaller
     * fonts until it fits - the cover must never be more than one page.
     */
    public static function render(CoverData $data, FontManager $fonts): \Mpdf\Mpdf
    {
        $fontScale    = 1.0;
        $spacingScale = 1.0;
        $mpdf         = null;

        for ($attempt = 0; $attempt < self::MAX_FIT_ATTEMPTS; $attempt++) {
            $mpdf = self::renderAttempt($data, $fonts, $fontScale, $spacingScale);

            if ($mpdf->page <= 1) {
                break;
            }

            if ($spacingScale > self::SPACING_SCALE_FLOOR) {
                $spacingScale = max(self::SPACING_SCALE_FLOOR, $spacingScale - self::SPACING_SCALE_STEP);
            } else {
                $fontScale = max(self::FONT_SCALE_FLOOR, $fontScale - self::FONT_SCALE_STEP);
            }
        }

        return $mpdf;
    }

    private static function renderAttempt(CoverData $data, FontManager $fonts, float $fontScale, float $spacingScale): \Mpdf\Mpdf
    {
        $fontConfig = $fonts->buildMpdfFontConfig();
        $margins    = CoverBuilder::marginsPt($data, $spacingScale, $fontScale);

        $mpdf = new \Mpdf\Mpdf([
            'format'        => 'A4',
            // Left/top/right stay at 0: mPDF measures position:fixed
            // offsets from the margin box's top-left corner, and clips
            // anything beyond the margin box's right/bottom edge, so a
            // non-zero margin here would both shift and clip the fixed
            // border/submission-date blocks in CoverBuilder. The visual
            // top/left/right inset for normal content is applied instead
            // as CSS padding in CoverBuilder::buildHtml(). margin_bottom
            // is kept real because it is what makes mPDF trigger a second
            // page once flowing content would run into the bottom strip
            // reserved for the pinned submission date - see
            // CoverBuilder::marginsPt() for the full explanation.
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => CoverBuilder::ptToMm($margins['bottom']),
            'margin_header' => 0,
            'margin_footer' => 0,
            'fontDir'       => $fontConfig['fontDir'],
            'fontdata'      => $fontConfig['fontdata'],
            'default_font'  => 'sans',
            'tempDir'       => Config::tempPath(),
        ]);

        // mPDF's own table-shrinking (separate from the fit-to-one-page
        // loop above, which already handles overflow deliberately) only
        // kicks in for a table with page-break-inside:avoid that won't
        // fit on the page - our tables don't set that - but setting this
        // to 1 ("no shrink") makes sure it can never silently override
        // the font sizes CoverBuilder has already calculated.
        $mpdf->shrink_tables_to_fit = 1;

        $mpdf->SetTitle('Assignment Cover' . ($data->versityName !== '' ? ' - ' . self::unescape($data->versityName) : ''));
        $mpdf->SetAuthor($data->studentName !== '' ? self::unescape($data->studentName) : 'Assignment Cover Generator');
        $mpdf->WriteHTML(CoverBuilder::buildHtml($data, $fontScale, $spacingScale));

        return $mpdf;
    }

    /** Suggests a safe download filename based on the student's details. */
    public static function suggestFilename(CoverData $data): string
    {
        $parts = array_filter([self::unescape($data->studentName), self::unescape($data->studentId), 'Assignment Cover']);
        $base = implode(' - ', $parts);
        $base = preg_replace('/[^A-Za-z0-9 _\-]/', '', $base) ?? 'Assignment Cover';
        $base = trim($base) !== '' ? trim($base) : 'Assignment Cover';
        return $base . '.pdf';
    }

    /**
     * CoverData's string fields are pre-escaped for safe HTML embedding
     * (via Sanitize::text), which is wrong for plain-text uses like PDF
     * metadata or a download filename — e.g. an apostrophe becomes
     * "&#039;", and a stripped-down filename regex would otherwise keep
     * the stray digits "039" from that entity. Reverse the escaping here
     * for those plain-text contexts.
     */
    private static function unescape(string $value): string
    {
        return htmlspecialchars_decode($value, ENT_QUOTES);
    }
}
