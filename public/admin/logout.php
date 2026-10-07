<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\AdminAuth;
use App\Csrf;
use App\Http;

Csrf::requirePost();
AdminAuth::logout();
Http::redirect('/admin/login.php');
