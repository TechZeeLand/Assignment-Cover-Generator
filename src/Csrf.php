<?php

declare(strict_types=1);

namespace App;

final class Csrf
{
    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . Html::e(self::token()) . '">';
    }

    public static function valid(?string $submitted): bool
    {
        Session::start();
        $expected = $_SESSION['csrf'] ?? '';
        return is_string($expected) && $expected !== '' && is_string($submitted) && hash_equals($expected, $submitted);
    }

    /** Ends the request with 403 unless the POSTed (or X-CSRF-Token header) token matches. */
    public static function requirePost(): void
    {
        $token = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !self::valid(is_string($token) ? $token : null)) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid or expired form token. Please go back, reload the page and try again.';
            exit;
        }
    }
}
