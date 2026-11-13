<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\CoverData;
use App\FontManager;
use App\Http;
use App\PdfService;
use App\PreviewService;

/**
 * Live preview: takes the same POST as generate.php and answers with a PNG of
 * page 1 of the PDF it would produce (nothing is stored, nothing is counted).
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Http::json(['ok' => false, 'error' => 'Method not allowed.'], 405);
}
if (!PreviewService::available()) {
    Http::json(['ok' => false, 'error' => 'unavailable'], 501);
}
if (PreviewService::throttled()) {
    header('Retry-After: 10');
    Http::json(['ok' => false, 'error' => 'Too many preview requests — please slow down for a moment.'], 429);
}

try {
    $fonts = new FontManager();
    $data  = CoverData::fromRequest($_POST, $fonts);
    $pdf   = PdfService::render($data, $fonts)->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    $png   = PreviewService::pngFromPdf((string) $pdf);
    if ($png === null) {
        Http::json(['ok' => false, 'error' => 'Could not draw the preview.'], 500);
    }
    header('Content-Type: image/png');
    header('Cache-Control: no-store');
    header('Content-Length: ' . strlen($png));
    echo $png;
} catch (\Throwable $e) {
    error_log('[assignment-cover-generator] preview: ' . $e->getMessage());
    Http::json(['ok' => false, 'error' => 'Could not draw the preview.'], 500);
}
