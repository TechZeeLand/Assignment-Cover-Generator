<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\CoverData;
use App\FontManager;
use App\PdfService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain');
    echo 'Method not allowed. Please submit the form from the app.';
    exit;
}

try {
    $fonts = new FontManager();
    $data  = CoverData::fromRequest($_POST, $fonts);

    $mpdf = PdfService::render($data, $fonts);
    $filename = PdfService::suggestFilename($data);

    // 'I' streams inline (browser opens/downloads the PDF directly).
    $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    // Do not leak stack traces to the client; log server-side instead.
    error_log('[assignment-cover-generator] ' . $e->getMessage());
    echo "Sorry, something went wrong while generating the PDF.\n";
    echo "Please double-check your inputs and try again.";
}
