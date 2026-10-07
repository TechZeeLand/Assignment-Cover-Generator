<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Csrf;
use App\Html;
use App\Settings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();

    $name = trim((string) ($_POST['site_name'] ?? ''));
    $url = rtrim(trim((string) ($_POST['site_url'] ?? '')), '/');
    $email = trim((string) ($_POST['contact_email'] ?? ''));
    $ann = trim((string) ($_POST['announcement_text'] ?? ''));

    if ($name === '' || mb_strlen($name) > 60) {
        admin_flash('error', 'Site name is required (max 60 characters).');
        admin_redirect('settings.php');
    }
    if ($url !== '' && !preg_match('#^https?://[A-Za-z0-9.\-]+(:\d{1,5})?$#', $url)) {
        admin_flash('error', 'Site URL must look like https://example.com (no path, no trailing slash).');
        admin_redirect('settings.php');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        admin_flash('error', 'That contact email is not valid.');
        admin_redirect('settings.php');
    }
    Settings::setMany([
        'site_name'             => $name,
        'site_url'              => $url,
        'contact_email'         => $email,
        'announcement_enabled'  => isset($_POST['announcement_enabled']) ? '1' : '0',
        'announcement_text'     => mb_substr($ann, 0, 200),
        'cookie_banner_enabled' => isset($_POST['cookie_banner_enabled']) ? '1' : '0',
        'font_uploads_enabled'  => isset($_POST['font_uploads_enabled']) ? '1' : '0',
    ]);
    admin_flash('ok', 'Settings saved.');
    admin_redirect('settings.php');
}

admin_head('Site settings', 'settings');
?>
<form method="post" class="panel">
    <?php echo Csrf::field(); ?>
    <h2>General</h2>
    <label for="n">Site name</label>
    <input type="text" id="n" name="site_name" value="<?php echo Html::e(Settings::get('site_name')); ?>" maxlength="60" required>

    <label for="u">Site URL</label>
    <input type="text" id="u" name="site_url" value="<?php echo Html::e(Settings::get('site_url')); ?>" placeholder="https://cover.example.com">
    <p class="hint">The public address of this site. Used for the Google redirect URI. Leave empty to detect it automatically.</p>

    <label for="e">Contact email</label>
    <input type="text" id="e" name="contact_email" value="<?php echo Html::e(Settings::get('contact_email')); ?>" placeholder="you@example.com">
    <p class="hint">Shown on the Contact page.</p>

    <h2>Announcement banner</h2>
    <?php echo admin_check('announcement_enabled', 'Show a banner at the top of every page', Settings::bool('announcement_enabled')); ?>
    <input type="text" name="announcement_text" value="<?php echo Html::e(Settings::get('announcement_text')); ?>" maxlength="200" placeholder="e.g. New designs added!">

    <h2>Privacy &amp; features</h2>
    <?php echo admin_check('cookie_banner_enabled', 'Show the cookie banner', Settings::bool('cookie_banner_enabled'), 'Needed to ask for consent before ads load.'); ?>
    <?php echo admin_check('font_uploads_enabled', 'Allow visitors to upload custom fonts', Settings::bool('font_uploads_enabled'), 'Uploaded fonts are shared with everyone. Turn this off if you see abuse; manage existing fonts under Custom fonts.'); ?>

    <p><button type="submit" class="btn primary">Save</button></p>
</form>
<?php admin_foot();
