<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\AdminAuth;

AdminAuth::requireLogin();
require __DIR__ . '/_layout.php';
