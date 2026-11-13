<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

use App\Auth;
use App\Csrf;
use App\EmailAuth;
use App\Html;
use App\Http;
use App\Icon;
use App\Stats;

if (!Auth::signInAvailable()) {
    Http::redirect('/');
}
$return = Http::safeReturn((string) ($_POST['return'] ?? $_GET['return'] ?? '/'));
if (Auth::user() !== null) {
    Http::redirect($return);
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::emailAvailable()) {
    Csrf::requirePost();
    $email = trim((string) ($_POST['email'] ?? ''));
    try {
        $id = EmailAuth::signIn($email, (string) ($_POST['password'] ?? ''));
        Auth::login($id);
        Stats::hit('signin');
        Http::redirect($return);
    } catch (\RuntimeException $e) {
        $error = $e->getMessage();
    } catch (\Throwable $e) {
        error_log('[assignment-cover-generator] email sign-in: ' . $e->getMessage());
        $error = 'Something went wrong while signing you in. Please try again.';
    }
}

// Must run before any output: it starts the session (and sets its cookie).
$csrfField = Csrf::field();

auth_head('Sign in');
?>
    <h1>Sign in</h1>
    <p class="content-lede">Signing in is optional &mdash; it only lets you save your details, favorite designs and preferences.</p>
    <div class="content-card auth-card">
        <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?php echo Html::e($error); ?></p><?php endif; ?>

        <?php if (Auth::emailAvailable()): ?>
        <form method="post" class="auth-form" action="/auth/signin.php" novalidate>
            <?php echo $csrfField; ?>
            <input type="hidden" name="return" value="<?php echo Html::e($return); ?>">
            <label for="email">Email</label>
            <input class="text-input" type="email" id="email" name="email" value="<?php echo Html::e($email); ?>" autocomplete="email" maxlength="120" required autofocus>
            <label for="password">Password</label>
            <input class="text-input" type="password" id="password" name="password" autocomplete="current-password" maxlength="200" required>
            <button type="submit" class="btn btn-primary"><?php echo Icon::svg('lock'); ?> Sign in</button>
        </form>
        <p class="hint">New here? <a href="/auth/register.php?return=<?php echo rawurlencode($return); ?>">Create an account</a>.
            Forgot your password? There is no email reset
            <?php echo Auth::googleAvailable() ? '&mdash; signing in with Google using the same address takes over the account and removes the old password.' : '&mdash; please contact the site operator.'; ?></p>
        <?php endif; ?>

        <?php if (Auth::googleAvailable()): ?>
        <?php if (Auth::emailAvailable()): ?><p class="auth-or"><span>or</span></p><?php endif; ?>
        <a class="google-btn google-btn-lg" href="/auth/google.php?return=<?php echo rawurlencode($return); ?>">
            <?php echo Icon::svg('google'); ?>
            <span>Sign in with Google</span>
        </a>
        <?php endif; ?>
    </div>
<?php auth_foot();
