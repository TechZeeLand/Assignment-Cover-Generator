<?php

declare(strict_types=1);

namespace App;

use Tzl\Layout;

/**
 * Email + password pages on the TZL Kit layout: /auth/signin, /auth/register,
 * /auth/password. Routed from Web::routes(). Google sign-in lives in Web.
 */
final class AuthPages
{
    public static function signin(): void
    {
        if (!Auth::signInAvailable()) {
            Http::redirect('/');
        }
        $return = Http::safeReturn((string) ($_POST['return'] ?? $_GET['return'] ?? '/'));
        if (Auth::user() !== null) {
            Http::redirect($return);
        }

        $error = '';
        $email = '';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && Auth::emailAvailable()) {
            Csrf::requirePost();
            $email = trim((string) ($_POST['email'] ?? ''));
            try {
                $id = EmailAuth::signIn($email, (string) ($_POST['password'] ?? ''));
                Auth::login($id);
                Stats::hit('signin');
                \Tzl\Stats::event('signin');
                Http::redirect($return);
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                error_log('[assignment-cover-generator] email sign-in: ' . $e->getMessage());
                $error = 'Something went wrong while signing you in. Please try again.';
            }
        }

        $rq = rawurlencode($return);
        $html = '<div class="auth-card">' . self::alert($error);
        if (Auth::emailAvailable()) {
            $html .= '<form method="post" class="auth-form" action="/auth/signin" novalidate>' . Csrf::field()
                . '<input type="hidden" name="return" value="' . Html::e($return) . '">'
                . '<label for="email">Email</label>'
                . '<input class="text-input" type="email" id="email" name="email" value="' . Html::e($email) . '" autocomplete="email" maxlength="120" required autofocus>'
                . '<label for="password">Password</label>'
                . '<input class="text-input" type="password" id="password" name="password" autocomplete="current-password" maxlength="200" required>'
                . '<button type="submit" class="btn btn-primary">Sign in</button></form>'
                . '<p class="hint">New here? <a href="/auth/register?return=' . $rq . '">Create an account</a>. '
                . 'There is no email reset: '
                . (Auth::googleAvailable()
                    ? 'if you forget your password, signing in with Google using the same address takes the account over and removes the old password.'
                    : 'if you forget your password, please contact the site operator.')
                . '</p>';
        }
        if (Auth::googleAvailable()) {
            $html .= (Auth::emailAvailable() ? '<p class="auth-or"><span>or</span></p>' : '')
                . '<a class="google-btn google-btn-lg" href="/auth/google?return=' . $rq . '">' . Icon::svg('google') . '<span>Sign in with Google</span></a>';
        }
        $html .= '</div>';

        Layout::page('Sign in', 'Sign in to the Assignment Cover Generator.', 'Sign in',
            'Signing in is optional. It only lets you save your details, favorite designs and preferences.', $html, ['noindex' => true]);
    }

    public static function register(): void
    {
        if (!Auth::emailAvailable()) {
            Http::redirect(Auth::signInAvailable() ? '/auth/signin' : '/');
        }
        $return = Http::safeReturn((string) ($_POST['return'] ?? $_GET['return'] ?? '/'));
        if (Auth::user() !== null) {
            Http::redirect($return);
        }

        $error = '';
        $name = '';
        $email = '';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::requirePost();
            $name = (string) ($_POST['name'] ?? '');
            $email = (string) ($_POST['email'] ?? '');
            try {
                $id = EmailAuth::register($name, $email, (string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? ''));
                Auth::login($id);
                Stats::hit('signin');
                \Tzl\Stats::event('signin');
                Http::redirect($return);
            } catch (\RuntimeException $e) {
                $error = $e->getMessage();
            } catch (\Throwable $e) {
                error_log('[assignment-cover-generator] register: ' . $e->getMessage());
                $error = 'Could not create the account right now. Please try again.';
            }
        }

        $html = '<div class="auth-card">' . self::alert($error)
            . '<form method="post" class="auth-form" action="/auth/register" novalidate>' . Csrf::field()
            . '<input type="hidden" name="return" value="' . Html::e($return) . '">'
            . '<label for="name">Your name</label>'
            . '<input class="text-input" type="text" id="name" name="name" value="' . Html::e($name) . '" autocomplete="name" maxlength="80" required autofocus>'
            . '<label for="email">Email</label>'
            . '<input class="text-input" type="email" id="email" name="email" value="' . Html::e($email) . '" autocomplete="email" maxlength="120" required>'
            . '<label for="password">Password <small>(at least ' . EmailAuth::MIN_PASSWORD . ' characters)</small></label>'
            . '<input class="text-input" type="password" id="password" name="password" autocomplete="new-password" maxlength="200" required>'
            . '<label for="confirm">Confirm password</label>'
            . '<input class="text-input" type="password" id="confirm" name="confirm" autocomplete="new-password" maxlength="200" required>'
            . '<button type="submit" class="btn btn-primary">Create account</button></form>'
            . '<p class="hint">Already have an account? <a href="/auth/signin?return=' . rawurlencode($return) . '">Sign in</a>. '
            . 'Email addresses are not verified (there is no email sending), so an account only holds your own saved preferences.</p></div>';

        Layout::page('Create an account', 'Create an account for the Assignment Cover Generator.', 'Create an account',
            'Only a name, an email address and a password. No verification email is sent.', $html, ['noindex' => true]);
    }

    public static function password(): void
    {
        $user = Auth::user();
        if ($user === null) {
            Http::redirect('/auth/signin?return=' . rawurlencode('/auth/password'));
        }

        $error = '';
        $done = false;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::requirePost();
            $current = (string) ($_POST['current'] ?? '');
            $new = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['confirm'] ?? '');
            try {
                if ($user['has_password'] && !Users::checkPassword($user['id'], $current)) {
                    // Wrong "current password" guesses are throttled like failed sign-ins.
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

        $has = $user['has_password'];
        $html = '<div class="auth-card">'
            . ($done ? '<p class="auth-ok" role="status">Your password was saved.</p>' : '') . self::alert($error)
            . '<form method="post" class="auth-form" action="/auth/password" novalidate>' . Csrf::field()
            . ($has ? '<label for="current">Current password</label><input class="text-input" type="password" id="current" name="current" autocomplete="current-password" maxlength="200" required>' : '')
            . '<label for="password">New password <small>(at least ' . EmailAuth::MIN_PASSWORD . ' characters)</small></label>'
            . '<input class="text-input" type="password" id="password" name="password" autocomplete="new-password" maxlength="200" required>'
            . '<label for="confirm">Confirm new password</label>'
            . '<input class="text-input" type="password" id="confirm" name="confirm" autocomplete="new-password" maxlength="200" required>'
            . '<button type="submit" class="btn btn-primary">Save password</button></form>'
            . '<p class="hint"><a href="/">Back to the generator</a></p></div>';

        Layout::page($has ? 'Change password' : 'Set a password', 'Manage your password.', $has ? 'Change password' : 'Set a password',
            ($has ? 'Choose a new password for ' : 'Add a password so you can also sign in without Google as ') . $user['email'] . '.', $html, ['noindex' => true]);
    }

    private static function alert(string $error): string
    {
        return $error === '' ? '' : '<p class="auth-error" role="alert">' . Html::e($error) . '</p>';
    }
}
