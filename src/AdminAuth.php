<?php

declare(strict_types=1);

namespace App;

/**
 * Password-only admin login. The password comes from the ADMIN_PASSWORD
 * environment variable the first time (it is then stored hashed), and can be
 * changed from the panel afterwards. Set ADMIN_PASSWORD_RESET=1 together with
 * ADMIN_PASSWORD to force the environment value to win again.
 */
final class AdminAuth
{
    private const IDLE_TIMEOUT = 7200;     // 2 hours
    private const MAX_FAILURES = 5;
    private const WINDOW = 900;            // 15 minutes

    public static function configured(): bool
    {
        return Settings::get('admin_password_hash') !== '' || self::envPassword() !== '';
    }

    public static function isLoggedIn(): bool
    {
        if (!Session::hasCookie() && session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        Session::start();
        $last = $_SESSION['admin_last'] ?? 0;
        if (empty($_SESSION['admin']) || !is_int($last) || time() - $last > self::IDLE_TIMEOUT) {
            unset($_SESSION['admin'], $_SESSION['admin_last']);
            return false;
        }
        $_SESSION['admin_last'] = time();
        return true;
    }

    /** Redirects to the login page unless an admin session is active. */
    public static function requireLogin(): void
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        header('X-Frame-Options: DENY');
        if (!self::isLoggedIn()) {
            Http::redirect('/admin/login.php');
        }
    }

    public static function throttled(): bool
    {
        $stmt = Db::pdo()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :ip AND ts > :t');
        $stmt->execute([':ip' => Http::clientIp(), ':t' => time() - self::WINDOW]);
        return (int) $stmt->fetchColumn() >= self::MAX_FAILURES;
    }

    public static function attempt(string $password): bool
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM login_attempts WHERE ts < :t')->execute([':t' => time() - self::WINDOW]);
        if (self::throttled()) {
            return false;
        }

        $ok = self::verify($password);
        if ($ok) {
            $pdo->prepare('DELETE FROM login_attempts WHERE ip = :ip')->execute([':ip' => Http::clientIp()]);
            Session::start();
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            $_SESSION['admin_last'] = time();
            return true;
        }
        $pdo->prepare('INSERT INTO login_attempts (ip, ts) VALUES (:ip, :t)')
            ->execute([':ip' => Http::clientIp(), ':t' => time()]);
        usleep(400000); // slow down guessing
        return false;
    }

    public static function logout(): void
    {
        Session::start();
        unset($_SESSION['admin'], $_SESSION['admin_last'], $_SESSION['csrf']);
    }

    public static function changePassword(string $current, string $new): ?string
    {
        if (!self::verify($current)) {
            return 'Your current password is incorrect.';
        }
        if (strlen($new) < 10) {
            return 'Choose a password with at least 10 characters.';
        }
        Settings::set('admin_password_hash', password_hash($new, PASSWORD_DEFAULT));
        return null;
    }

    private static function verify(string $password): bool
    {
        $hash = Settings::get('admin_password_hash');
        $env  = self::envPassword();

        if ($env !== '' && ($hash === '' || self::envReset())) {
            if (hash_equals($env, $password)) {
                Settings::set('admin_password_hash', password_hash($password, PASSWORD_DEFAULT));
                return true;
            }
            if ($hash === '') {
                return false;
            }
        }
        return $hash !== '' && password_verify($password, $hash);
    }

    private static function envPassword(): string
    {
        return (string) (getenv('ADMIN_PASSWORD') ?: '');
    }

    private static function envReset(): bool
    {
        return in_array(strtolower((string) getenv('ADMIN_PASSWORD_RESET')), ['1', 'true', 'yes'], true);
    }
}
