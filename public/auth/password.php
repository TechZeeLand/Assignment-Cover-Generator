<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

use App\Auth;
use App\Csrf;
use App\EmailAuth;
use App\Html;
use App\Http;
use App\Icon;
use App\Users;

$user = Auth::user();
if ($user === null) {
    Http::redirect('/auth/signin.php?return=' . rawurlencode('/auth/password.php'));
}

$error = '';
$done = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm'] ?? '');
    try {
        if ($user['has_password'] && !Users::checkPassword($user['id'], $current)) {
            // Count wrong "current password" guesses like failed sign-ins.
            try {
                EmailAuth::signIn($user['email'], $current);
            } catch (\RuntimeException $e) {
                throw new \RuntimeException(str_contains($e->getMessage(), 'Too many') ? $e->getMessage() : 'Your current password is incorrect.');
            }
        }
        if (($problem = EmailAuth::passwordProblem($new)) !== null) {
            $error = $problem;
        } elseif (!hash_equals($new, $confirm)) {
            $error = 'The two passwords do not match.';
        } else {
            Users::setPassword($user['id'], $new);
            $done = true;
            $user['has_password'] = true;
        }
    } catch (\RuntimeException $e) {
        $error = $e->getMessage();
    } catch (\Throwable $e) {
        error_log('[assignment-cover-generator] set password: ' . $e->getMessage());
        $error = 'Could not change the password right now.';
    }
}

// Must run before any output: it starts the session (and sets its cookie).
$csrfField = Csrf::field();

auth_head($user['has_password'] ? 'Change password' : 'Set a password');
?>
    <h1><?php echo $user['has_password'] ? 'Change password' : 'Set a password'; ?></h1>
    <p class="content-lede"><?php echo $user['has_password'] ? 'Choose a new password for ' : 'Add a password so you can also sign in without Google as '; ?><strong><?php echo Html::e($user['email']); ?></strong>.</p>
    <div class="content-card auth-card">
        <?php if ($done): ?><p class="auth-ok" role="status">Your password was saved.</p><?php endif; ?>
        <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?php echo Html::e($error); ?></p><?php endif; ?>
        <form method="post" class="auth-form" action="/auth/password.php" novalidate>
            <?php echo $csrfField; ?>
            <?php if ($user['has_password']): ?>
            <label for="current">Current password</label>
            <input class="text-input" type="password" id="current" name="current" autocomplete="current-password" maxlength="200" required>
            <?php endif; ?>
            <label for="password">New password <small>(at least <?php echo EmailAuth::MIN_PASSWORD; ?> characters)</small></label>
            <input class="text-input" type="password" id="password" name="password" autocomplete="new-password" maxlength="200" required>
            <label for="confirm">Confirm new password</label>
            <input class="text-input" type="password" id="confirm" name="confirm" autocomplete="new-password" maxlength="200" required>
            <button type="submit" class="btn btn-primary"><?php echo Icon::svg('key'); ?> Save password</button>
        </form>
        <p class="hint"><a href="/">Back to the generator</a></p>
    </div>
<?php auth_foot();
