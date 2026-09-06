<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Config;

$key = (string) ($_GET['key'] ?? '');
$variant = (string) ($_GET['variant'] ?? 'regular');

if (!preg_match('/^[a-z0-9\-]{1,32}$/', $key) || !in_array($variant, ['regular', 'bold', 'italic', 'bolditalic'], true)) {
    http_response_code(400);
    exit('Invalid request.');
}

$indexFile = Config::fontsStoragePath() . '/index.json';
$index = is_file($indexFile) ? json_decode((string) file_get_contents($indexFile), true) : [];
$index = is_array($index) ? $index : [];

$filename = null;
foreach ($index as $entry) {
    if (($entry['key'] ?? null) === $key) {
        $filename = $entry['files'][$variant] ?? null;
        break;
    }
}

if ($filename === null) {
    http_response_code(404);
    exit('Font not found.');
}

$path = Config::fontsStoragePath() . '/' . basename($filename);
if (!is_file($path)) {
    http_response_code(404);
    exit('Font file missing.');
}

$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
header('Content-Type: ' . ($ext === 'otf' ? 'font/otf' : 'font/ttf'));
header('Cache-Control: public, max-age=86400');
header('Content-Length: ' . (string) filesize($path));
readfile($path);
