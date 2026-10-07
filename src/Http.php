<?php

declare(strict_types=1);

namespace App;

final class Http
{
    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        return strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    /** Public base URL without a trailing slash (the admin "Site URL" wins). */
    public static function baseUrl(): string
    {
        $configured = rtrim(trim(Settings::get('site_url')), '/');
        if ($configured !== '' && preg_match('#^https?://[^\s/]+$#i', $configured)) {
            return $configured;
        }
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        if (!preg_match('/^[A-Za-z0-9.\-]+(:\d{1,5})?$/', $host)) {
            $host = 'localhost';
        }
        return (self::isHttps() ? 'https' : 'http') . '://' . $host;
    }

    /** Only same-site relative paths are allowed as a post-login destination. */
    public static function safeReturn(?string $path): string
    {
        $path = (string) $path;
        if ($path === '' || $path[0] !== '/' || str_starts_with($path, '//') || preg_match('/[\\\\\r\n]/', $path)) {
            return '/';
        }
        return $path;
    }

    public static function clientIp(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public static function redirect(string $location): never
    {
        header('Location: ' . $location, true, 302);
        exit;
    }

    /** @param array<string,mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
