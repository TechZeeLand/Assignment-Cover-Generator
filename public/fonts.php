<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\FontManager;

header('Content-Type: application/json; charset=utf-8');

try {
    $fonts = new FontManager();
    echo json_encode(['ok' => true, 'fonts' => $fonts->listFonts()]);
} catch (\Throwable $e) {
    error_log('[assignment-cover-generator] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not load fonts.']);
}
