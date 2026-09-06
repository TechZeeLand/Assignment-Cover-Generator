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
        ]);

        $mpdf->SetTitle('Assignment Cover' . ($data->versityName !== '' ? ' - ' . $data->versityName : ''));
        $mpdf->SetAuthor($data->studentName !== '' ? $data->studentName : 'Assignment Cover Generator');
        $mpdf->WriteHTML(CoverBuilder::buildHtml($data));

        return $mpdf;
    }

    /** Suggests a safe download filename based on the student's details. */
    public static function suggestFilename(CoverData $data): string
    {
        $parts = array_filter([$data->studentName, $data->studentId, 'Assignment Cover']);
        $base = implode(' - ', $parts);
        $base = preg_replace('/[^A-Za-z0-9 _\-]/', '', $base) ?? 'Assignment Cover';
        $base = trim($base) !== '' ? trim($base) : 'Assignment Cover';
        return $base . '.pdf';
    }
}
