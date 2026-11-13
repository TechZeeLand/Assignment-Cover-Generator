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

if (!Auth::emailAvailable()) {
    Http::redirect(Auth::signInAvailable() ? '/auth/signin.php' : '/');
}
$return = Http::safeReturn((string) ($_POST['return'] ?? $_GET['return'] ?? '/'));
if (Auth::user() !== null) {
    Http::redirect($return);
}

$error = '';
$name = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requirePost();
    $name = (string) ($_POST['name'] ?? '');
    $email = (string) ($_POST['email'] ?? '');
    try {
        $id = EmailAuth::register($name, $email, (string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        Auth::login($id);
        Stats::hit('signin');
        Http::redirect($return);
    } catch (\RuntimeException $e) {
        $error = $e->getMessage();
    } catch (\Throwable $e) {
        error_log('[assignment-cover-generator] register: ' . $e->getMessage());
        $error = 'Something went wrong while creating your account. Please try again.';
    }
}

// Must run before any output: it starts the session (and sets its cookie).
$csrfField = Csrf::field();

auth_head('Create account');
?>
    <h1>Create an account</h1>
    <p class="content-lede">An email and a password &mdash; nothing else. Accounts are optional and only used to save your details.</p>
    <div class="content-card auth-card">
        <?php if ($error !== ''): ?><p class="auth-error" role="alert"><?php echo Html::e($error); ?></p><?php endif; ?>
        <form method="post" class="auth-form" action="/auth/register.php" novalidate>
            <?php echo $csrfField; ?>
            <input type="hidden" name="return" value="<?php echo Html::e($return); ?>">
            <label for="name">Name</label>
            <input class="text-input" type="text" id="name" name="name" value="<?php echo Html::e($name); ?>" autocomplete="name" maxlength="80" required autofocus>
            <label for="email">Email</label>
            <input class="text-input" type="email" id="email" name="email" value="<?php echo Html::e($email); ?>" autocomplete="email" maxlength="120" required>
            <label for="password">Password <small>(at least <?php echo EmailAuth::MIN_PASSWORD; ?> characters)</small></label>
            <input class="text-input" type="password" id="password" name="password" autocomplete="new-password" minlength="<?php echo EmailAuth::MIN_PASSWORD; ?>" maxlength="200" required>
            <label for="confirm">Confirm password</label>
            <input class="text-input" type="password" id="confirm" name="confirm" autocomplete="new-password" maxlength="200" required>
            <button type="submit" class="btn btn-primary"><?php echo Icon::svg('user'); ?> Create account</button>
        </form>
        <p class="hint">We can't verify your email address and can't send password resets, so choose a password you will remember.
            Already have an account? <a href="/auth/signin.php?return=<?php echo rawurlencode($return); ?>">Sign in</a>.</p>
    </div>
<?php auth_foot();
