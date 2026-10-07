<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\Csrf;
use App\GoogleOAuth;
use App\Html;
use App\Http;
use App\Settings;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $clientId = trim((string) ($_POST['google_client_id'] ?? ''));
    $secret   = trim((string) ($_POST['google_client_secret'] ?? ''));

    if ($clientId !== '' && !preg_match('/^[0-9A-Za-z._\-]+\.apps\.googleusercontent\.com$/', $clientId)) {
        admin_flash('error', 'That does not look like a Google OAuth client ID (it should end in .apps.googleusercontent.com).');
        admin_redirect('signin.php');
    }
    $values = [
        'signin_enabled'   => isset($_POST['signin_enabled']) ? '1' : '0',
        'profiles_enabled' => isset($_POST['profiles_enabled']) ? '1' : '0',
        'google_client_id' => $clientId,
    ];
    if (isset($_POST['remove_secret'])) {
        $values['google_client_secret'] = '';
    } elseif ($secret !== '') {
        $values['google_client_secret'] = $secret;   // blank = keep the stored one
    }
    Settings::setMany($values);
    admin_flash('ok', 'Sign-in settings saved.');
    admin_redirect('signin.php');
}

$hasSecret = Settings::get('google_client_secret') !== '';
$redirect = GoogleOAuth::redirectUri();
$origin = Http::baseUrl();

admin_head('Sign-in (Google)', 'signin');
?>
<form method="post" class="panel">
    <?php echo Csrf::field(); ?>
    <h2>Settings</h2>
    <?php echo admin_check('signin_enabled', 'Offer &ldquo;Sign in with Google&rdquo; to visitors', Settings::bool('signin_enabled'), 'Signing in is always optional. The button only appears once both keys below are filled in.'); ?>
    <?php echo admin_check('profiles_enabled', 'Let signed-in users save their details', Settings::bool('profiles_enabled'), 'Stores their form details (not the topic or date) on this server.'); ?>

    <label for="cid">Google OAuth client ID</label>
    <input type="text" id="cid" name="google_client_id" value="<?php echo Html::e(Settings::get('google_client_id')); ?>" placeholder="1234567890-abc123.apps.googleusercontent.com" autocomplete="off" spellcheck="false">

    <label for="csec">Google OAuth client secret</label>
    <input type="password" id="csec" name="google_client_secret" value="" placeholder="<?php echo $hasSecret ? '•••••••• saved — leave blank to keep' : 'GOCSPX-…'; ?>" autocomplete="new-password">
    <?php if ($hasSecret): ?><?php echo admin_check('remove_secret', 'Remove the saved secret', false); ?><?php endif; ?>

    <p><button type="submit" class="btn primary">Save</button></p>
</form>

<section class="panel">
    <h2>How to get the keys</h2>
    <ol class="steps">
        <li>Open <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console &rarr; APIs &amp; Services &rarr; Credentials</a> (create a project if you don't have one).</li>
        <li>Configure the <strong>OAuth consent screen</strong> (External). Only the default scopes (<code>openid</code>, <code>email</code>, <code>profile</code>) are used. Publish the app to <em>In production</em> so everyone can sign in.</li>
        <li><strong>Create credentials &rarr; OAuth client ID &rarr; Web application.</strong></li>
        <li>Add this under <em>Authorized redirect URIs</em> <strong>exactly</strong>:
            <div class="copy"><code><?php echo Html::e($redirect); ?></code></div></li>
        <li>Add this under <em>Authorized JavaScript origins</em> (optional but recommended):
            <div class="copy"><code><?php echo Html::e($origin); ?></code></div></li>
        <li>Paste the client ID and secret above and save.</li>
    </ol>
    <p class="hint">The address above comes from <a href="/admin/settings.php">Site settings &rarr; Site URL</a> (or from this request if that is empty). Behind HTTPS or a proxy, set the Site URL so it starts with <code>https://</code>.</p>
    <p class="hint">Only the visitor's name and email are read from Google. The Google account ID and profile picture are not stored.</p>
</section>
<?php admin_foot();
