<?php

declare(strict_types=1);

namespace App;

final class Http
{
    public static function isHttps(): bool
    {
        return \Tzl\Kit::isHttps();
    }

    /** Public base URL without a trailing slash (Admin > Site & domains, then TZL_BASE_URL, then the request). */
    public static function baseUrl(): string
    {
        return \Tzl\Kit::baseUrl();
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
        return \Tzl\Kit::clientIp();
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
