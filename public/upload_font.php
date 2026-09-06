<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\FontManager;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed.']);
    exit;
}

try {
    $fonts = new FontManager();

    $familyName = (string) ($_POST['font-name'] ?? '');
    $files = [
        'regular'    => $_FILES['font-regular'] ?? null,
        'bold'       => $_FILES['font-bold'] ?? null,
        'italic'     => $_FILES['font-italic'] ?? null,
        'bolditalic' => $_FILES['font-bolditalic'] ?? null,
    ];
    // Drop any file inputs that weren't actually used.
    foreach ($files as $k => $f) {
        if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            unset($files[$k]);
        }
    }

    $entry = $fonts->registerUpload($familyName, $files);

    echo json_encode([
        'ok'    => true,
        'font'  => $entry,
        'fonts' => $fonts->listFonts(),
    ]);
} catch (\RuntimeException $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (\Throwable $e) {
    error_log('[assignment-cover-generator] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not upload the font.']);
}
