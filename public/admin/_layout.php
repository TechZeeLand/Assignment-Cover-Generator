<?php

declare(strict_types=1);

use App\Csrf;
use App\Html;
use App\Session;

/** Queue a one-time message shown on the next admin page view. */
function admin_flash(string $type, string $message): void
{
    Session::start();
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function admin_redirect(string $page): never
{
    header('Location: /admin/' . $page);
    exit;
}

/** @param string $active nav key of the current page */
function admin_head(string $title, string $active): void
{
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    $nav = [
        'dashboard' => ['index.php', 'Dashboard', 'dashboard'],
        'designs'   => ['designs.php', 'Designs', 'palette'],
        'signin'    => ['signin.php', 'Sign-in (Google)', 'user-lock'],
        'ads'       => ['ads.php', 'Ads &amp; ads.txt', 'ad'],
        'settings'  => ['settings.php', 'Site settings', 'sliders'],
        'users'     => ['users.php', 'Users', 'users'],
        'fonts'     => ['fonts.php', 'Custom fonts', 'font'],
        'account'   => ['account.php', 'Admin password', 'key'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo Html::e($title); ?> — Admin</title>
<link rel="icon" href="/assets/favicon/favicon-32x32.png">
<link rel="stylesheet" href="<?php echo \App\Html::asset('/assets/css/admin.css'); ?>">
</head>
<body>
<div class="shell">
    <aside class="side">
        <a class="brand" href="/admin/"><?php echo \App\Icon::svg('shield'); ?> Admin</a>
        <nav>
            <?php foreach ($nav as $key => [$href, $label, $icon]): ?>
            <a href="/admin/<?php echo $href; ?>" class="<?php echo $key === $active ? 'on' : ''; ?>"><?php echo \App\Icon::svg($icon); ?> <?php echo $label; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="side-foot">
            <a href="/" target="_blank" rel="noopener"><?php echo \App\Icon::svg('external-link'); ?> View site</a>
            <form method="post" action="/admin/logout.php"><?php echo Csrf::field(); ?><button type="submit"><?php echo \App\Icon::svg('logout'); ?> Log out</button></form>
        </div>
    </aside>
    <main class="main">
        <h1><?php echo Html::e($title); ?></h1>
        <?php
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        foreach ($flashes as $f): ?>
        <div class="flash <?php echo Html::e($f['type']); ?>"><?php echo \App\Icon::svg($f['type'] === 'ok' ? 'check-circle' : ($f['type'] === 'warn' ? 'alert-triangle' : 'alert-circle')); ?> <span><?php echo Html::e($f['msg']); ?></span></div>
        <?php endforeach;
}

function admin_foot(): void
{
    ?>
    </main>
</div>
</body>
</html>
<?php
}

/** Checkbox switch markup. */
function admin_check(string $name, string $label, bool $checked, string $hint = ''): string
{
    return '<label class="check"><input type="checkbox" name="' . Html::e($name) . '" value="1"' . ($checked ? ' checked' : '') . '> <span>'
        . $label . '</span></label>' . ($hint !== '' ? '<p class="hint">' . $hint . '</p>' : '');
}
