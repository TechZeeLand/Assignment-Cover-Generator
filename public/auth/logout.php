<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Csrf;
use App\Http;

Csrf::requirePost();
Auth::logout();
Http::redirect(Http::safeReturn((string) ($_POST['return'] ?? '/')));
