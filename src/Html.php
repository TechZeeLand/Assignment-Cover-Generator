<?php

declare(strict_types=1);

namespace App;

final class Html
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Asset URL with a ?v=<mtime> suffix so browsers never keep a stale CSS/JS file after an update. */
    public static function asset(string $path): string
    {
        $file = dirname(__DIR__) . '/public' . $path;
        return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
    }

    /** JSON that is safe to embed inside an inline <script>. */
    public static function json(mixed $value): string
    {
        return (string) json_encode(
            $value,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}
