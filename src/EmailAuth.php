<?php

declare(strict_types=1);

namespace App;

/**
 * Email + password accounts: validation, throttling and the register / sign-in
 * flows. Throttling reuses the login_attempts table with prefixed keys
 * ("u:<ip>", "e:<hash of email>", "r:<ip>") so it never mixes with the admin's
 * own counters. No password reset by email exists (there is no SMTP).
 */
final class EmailAuth
{
    private const WINDOW = 900;        // 15 minutes
    private const MAX_PER_IP = 10;
    private const MAX_PER_EMAIL = 8;
    private const MAX_REGISTER_PER_IP = 6;
    public const MIN_PASSWORD = 8;
    public const MAX_PASSWORD = 200;

    /** Lower-cased, valid address or '' . */
    public static function normalizeEmail(string $email): string
    {
        $email = strtolower(trim($email));
        return (strlen($email) <= 120 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false) ? $email : '';
    }

    public static function cleanName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $name) ?? '';
        return trim(mb_substr(trim($name), 0, 80));
    }

    /** Returns an error message, or null when the password is acceptable. */
    public static function passwordProblem(string $password): ?string
    {
        if (strlen($password) < self::MIN_PASSWORD) {
            return 'Choose a password with at least ' . self::MIN_PASSWORD . ' characters.';
        }
        if (strlen($password) > self::MAX_PASSWORD) {
            return 'That password is too long (max ' . self::MAX_PASSWORD . ' characters).';
        }
        return null;
    }

    /**
     * @throws \RuntimeException with a message that is safe to show
     */
    public static function register(string $name, string $emailInput, string $password, string $confirm): int
    {
        $ipKey = 'r:' . Http::clientIp();
        if (self::count($ipKey) >= self::MAX_REGISTER_PER_IP) {
            throw new \RuntimeException('Too many attempts. Please wait a few minutes and try again.');
        }
        self::record($ipKey);

        $name = self::cleanName($name);
        $email = self::normalizeEmail($emailInput);
        if ($name === '') {
            throw new \RuntimeException('Please enter your name.');
        }
        if ($email === '') {
            throw new \RuntimeException('Please enter a valid email address.');
        }
        if (($problem = self::passwordProblem($password)) !== null) {
            throw new \RuntimeException($problem);
        }
        if (!hash_equals($password, $confirm)) {
            throw new \RuntimeException('The two passwords do not match.');
        }
        return Users::registerWithPassword($email, $name, $password);
    }

    /**
     * @throws \RuntimeException with a message that is safe to show
     */
    public static function signIn(string $emailInput, string $password): int
    {
        $email = self::normalizeEmail($emailInput);
        $keys = ['u:' . Http::clientIp(), 'e:' . sha1($email !== '' ? $email : strtolower(trim($emailInput)))];
        if (self::count($keys[0]) >= self::MAX_PER_IP || self::count($keys[1]) >= self::MAX_PER_EMAIL) {
            throw new \RuntimeException('Too many failed attempts. Please wait 15 minutes and try again.');
        }

        $id = $email !== '' && $password !== '' ? Users::verifyPassword($email, $password) : null;
        if ($id === null) {
            foreach ($keys as $key) {
                self::record($key);
            }
            usleep(300000); // slow down guessing
            throw new \RuntimeException('That email and password don\'t match an account.');
        }
        Db::pdo()->prepare('DELETE FROM login_attempts WHERE ip = :a OR ip = :b')->execute([':a' => $keys[0], ':b' => $keys[1]]);
        return $id;
    }

    private static function count(string $key): int
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM login_attempts WHERE ts < :t')->execute([':t' => time() - self::WINDOW]);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = :k AND ts > :t');
        $stmt->execute([':k' => $key, ':t' => time() - self::WINDOW]);
        return (int) $stmt->fetchColumn();
    }

    private static function record(string $key): void
    {
        Db::pdo()->prepare('INSERT INTO login_attempts (ip, ts) VALUES (:k, :t)')->execute([':k' => $key, ':t' => time()]);
    }
}
