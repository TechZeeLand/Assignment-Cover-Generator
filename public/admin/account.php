<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

use App\AdminAuth;
use App\Csrf;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $new = (string) ($_POST['new'] ?? '');
    if (!hash_equals($new, (string) ($_POST['confirm'] ?? ''))) {
        admin_flash('error', 'The new passwords do not match.');
    } else {
        $error = AdminAuth::changePassword((string) ($_POST['current'] ?? ''), $new);
        $error === null ? admin_flash('ok', 'Password changed.') : admin_flash('error', $error);
    }
    admin_redirect('account.php');
}

admin_head('Admin password', 'account');
?>
<form method="post" class="panel narrow" autocomplete="off">
    <?php echo Csrf::field(); ?>
    <label for="c">Current password</label>
    <input type="password" id="c" name="current" required autocomplete="current-password">
    <label for="n">New password <small class="muted">(at least 10 characters)</small></label>
    <input type="password" id="n" name="new" required minlength="10" autocomplete="new-password">
    <label for="n2">Repeat new password</label>
    <input type="password" id="n2" name="confirm" required minlength="10" autocomplete="new-password">
    <p><button type="submit" class="btn primary">Change password</button></p>
    <p class="hint">The password is stored hashed. If you ever lose it, set <code>ADMIN_PASSWORD</code> and <code>ADMIN_PASSWORD_RESET=1</code> in the container's environment and restart to set a new one.</p>
</form>
<?php admin_foot();
