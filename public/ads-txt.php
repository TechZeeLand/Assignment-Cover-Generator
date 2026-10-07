<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$body = App\Ads::adsTxt();
if ($body === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Not found\n";
    exit;
}
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo $body;
