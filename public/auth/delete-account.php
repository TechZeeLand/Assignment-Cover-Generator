<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Auth;
use App\Csrf;
use App\Http;
use App\Users;

Csrf::requirePost();
$user = Auth::user();
if ($user !== null) {
    Users::delete($user['id']);   // saved details go with it (ON DELETE CASCADE)
    Auth::logout();
}
Http::redirect('/');
