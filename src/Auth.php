<?php

declare(strict_types=1);

namespace App;

/** Who (if anyone) is signed in on this request (Google or email + password). */
final class Auth
{
    /** @var array{id:int,name:string,email:string,has_password:bool}|null|false false = not looked up yet */
    private static $user = false;

    /** Google sign-in is offered only when enabled in the admin panel AND the keys are present. */
    public static function googleAvailable(): bool
    {
        return Settings::bool('signin_enabled')
            && Settings::get('google_client_id') !== ''
            && Settings::get('google_client_secret') !== '';
    }

    /** Email + password accounts, switched on/off in the admin panel. */
    public static function emailAvailable(): bool
    {
        return Settings::bool('email_signin_enabled');
    }

    /** Is ANY way of signing in offered? */
    public static function signInAvailable(): bool
    {
        return self::googleAvailable() || self::emailAvailable();
    }

    /** @return array{id:int,name:string,email:string,has_password:bool}|null */
    public static function user(): ?array
    {
        if (self::$user !== false) {
            return self::$user;
        }
        self::$user = null;
        // Never create a session (cookie) for visitors who aren't signed in.
        if (!Session::hasCookie() && session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        try {
            Session::start();
            $id = $_SESSION['uid'] ?? null;
            if (is_int($id)) {
                self::$user = Users::find($id);
                if (self::$user === null) {
                    unset($_SESSION['uid']); // user was deleted by the admin
                }
            }
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] auth: ' . $e->getMessage());
        }
        return self::$user;
    }

    public static function login(int $userId): void
    {
        Session::start();
        session_regenerate_id(true);
        $_SESSION['uid'] = $userId;
        unset($_SESSION['csrf']);
        self::$user = false;
    }

    public static function logout(): void
    {
        Session::start();
        unset($_SESSION['uid'], $_SESSION['csrf']);
        self::$user = null;
    }
}
