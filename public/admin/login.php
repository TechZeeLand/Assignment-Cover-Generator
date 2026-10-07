<?php

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\AdminAuth;
use App\Csrf;
use App\Html;
use App\Http;

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');

$error = null;
try {
    if (AdminAuth::isLoggedIn()) {
        Http::redirect('/admin/');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        Csrf::requirePost();
        if (!AdminAuth::configured()) {
            $error = 'No admin password is set. Set the ADMIN_PASSWORD environment variable and restart the container.';
        } elseif (AdminAuth::throttled()) {
            $error = 'Too many failed attempts. Please wait 15 minutes and try again.';
        } elseif (AdminAuth::attempt((string) ($_POST['password'] ?? ''))) {
            Http::redirect('/admin/');
        } else {
            $error = AdminAuth::throttled()
                ? 'Too many failed attempts. Please wait 15 minutes and try again.'
                : 'Incorrect password.';
        }
    }
    $configured = AdminAuth::configured();
} catch (\Throwable $e) {
    error_log('[assignment-cover-generator] admin login: ' . $e->getMessage());
    $error = 'The admin area could not start. Check that the data folder is writable.';
    $configured = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin login</title>
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-body">
<form class="login-card" method="post" action="/admin/login.php" autocomplete="off">
    <h1>Admin login</h1>
    <?php if ($error): ?><div class="flash error"><?php echo Html::e($error); ?></div><?php endif; ?>
    <?php if (!$configured): ?>
        <div class="flash warn">Set the <code>ADMIN_PASSWORD</code> environment variable, then restart the container.</div>
    <?php endif; ?>
    <?php echo Csrf::field(); ?>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autofocus autocomplete="current-password">
    <button type="submit" class="btn primary">Log in</button>
    <p class="hint"><a href="/">&larr; Back to the site</a></p>
</form>
</body>
</html>
