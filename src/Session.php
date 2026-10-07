<?php

declare(strict_types=1);

namespace App;

/**
 * Sessions are started lazily: an anonymous visitor never receives a cookie.
 * One is only created when someone signs in (Google or admin).
 */
final class Session
{
    public const NAME = 'acg_session';
    private const LIFETIME = 2592000; // 30 days

    public static function hasCookie(): bool
    {
        return isset($_COOKIE[self::NAME]);
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $dir = Config::dataPath() . '/sessions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0770, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
        }
        session_name(self::NAME);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', (string) self::LIFETIME);
        session_set_cookie_params([
            'lifetime' => self::LIFETIME,
            'path'     => '/',
            'secure'   => Http::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}
