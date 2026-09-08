<?php

declare(strict_types=1);

namespace App;

final class PdfService
{
    public static function render(CoverData $data, FontManager $fonts): \Mpdf\Mpdf
    {
        $fontConfig = $fonts->buildMpdfFontConfig();

        $mpdf = new \Mpdf\Mpdf([
            'format'        => 'A4',
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'fontDir'       => $fontConfig['fontDir'],
            'fontdata'      => $fontConfig['fontdata'],
            'default_font'  => 'sans',
            'tempDir'       => Config::tempPath(),
        ]);

        $mpdf->SetTitle('Assignment Cover' . ($data->versityName !== '' ? ' - ' . self::unescape($data->versityName) : ''));
        $mpdf->SetAuthor($data->studentName !== '' ? self::unescape($data->studentName) : 'Assignment Cover Generator');
        $mpdf->WriteHTML(CoverBuilder::buildHtml($data));

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
