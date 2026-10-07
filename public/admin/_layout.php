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
        'dashboard' => ['index.php', 'Dashboard', 'fa-gauge-high'],
        'designs'   => ['designs.php', 'Designs', 'fa-palette'],
        'signin'    => ['signin.php', 'Sign-in (Google)', 'fa-user-lock'],
        'ads'       => ['ads.php', 'Ads &amp; ads.txt', 'fa-rectangle-ad'],
        'settings'  => ['settings.php', 'Site settings', 'fa-sliders'],
        'users'     => ['users.php', 'Users', 'fa-users'],
        'fonts'     => ['fonts.php', 'Custom fonts', 'fa-font'],
        'account'   => ['account.php', 'Admin password', 'fa-key'],
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
<link rel="stylesheet" href="/assets/css/admin.css">
<script src="https://kit.fontawesome.com/43a3c20016.js" crossorigin="anonymous"></script>
</head>
<body>
<div class="shell">
    <aside class="side">
        <a class="brand" href="/admin/"><i class="fa-solid fa-shield-halved"></i> Admin</a>
        <nav>
            <?php foreach ($nav as $key => [$href, $label, $icon]): ?>
            <a href="/admin/<?php echo $href; ?>" class="<?php echo $key === $active ? 'on' : ''; ?>"><i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $label; ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="side-foot">
            <a href="/" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> View site</a>
            <form method="post" action="/admin/logout.php"><?php echo Csrf::field(); ?><button type="submit"><i class="fa-solid fa-right-from-bracket"></i> Log out</button></form>
        </div>
    </aside>
    <main class="main">
        <h1><?php echo Html::e($title); ?></h1>
        <?php
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        foreach ($flashes as $f): ?>
        <div class="flash <?php echo Html::e($f['type']); ?>"><?php echo Html::e($f['msg']); ?></div>
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
