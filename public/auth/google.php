<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\GoogleOAuth;
use App\Http;

if (!Auth::signInAvailable()) {
    Http::redirect('/');
}
Http::redirect(GoogleOAuth::startUrl((string) ($_GET['return'] ?? '/')));
