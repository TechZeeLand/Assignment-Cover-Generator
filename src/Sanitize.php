<?php

declare(strict_types=1);

namespace App;

final class Sanitize
{
    /** Plain text: trim + escape for safe HTML output. */
    public static function text(mixed $value, int $maxLength = 300): string
    {
        $value = is_string($value) ? $value : '';
        $value = trim($value);
        if (function_exists('mb_substr')) {
            $value = mb_substr($value, 0, $maxLength);
        } else {
            $value = substr($value, 0, $maxLength);
        }
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /** Hex color, e.g. #1a2b3c. Falls back to a default if invalid/empty. */
    public static function color(mixed $value, string $default = '#000000'): string
    {
        $value = is_string($value) ? trim($value) : '';
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $default;
    }

    /**
     * Rich text (Course Title / Topic): allow only <b>/<strong>/<i>/<em>/<br>,
     * strip every attribute from those tags, and escape everything else.
     */
    public static function richText(mixed $value, int $maxLength = 600): string
    {
        $value = is_string($value) ? $value : '';
        if (function_exists('mb_substr')) {
            $value = mb_substr(trim($value), 0, $maxLength);
        } else {
            $value = substr(trim($value), 0, $maxLength);
        }

        // Normalise <div>/<p> line breaks some contenteditable browsers insert.
        $value = preg_replace('#</(div|p)>#i', '<br>', $value) ?? $value;
        $value = preg_replace('#<(div|p)[^>]*>#i', '', $value) ?? $value;

        $allowed = Config::allowedRichTextTags();
        $stripped = strip_tags($value, $allowed);

        // Remove any attributes that survived on the allowed tags (e.g. onclick=).
        $clean = preg_replace('/<(b|strong|i|em|br)\b[^>]*>/i', '<$1>', $stripped) ?? $stripped;

        return $clean;
    }

    /** True/false from an HTML checkbox's presence in POST data. */
    public static function bool(mixed $value): bool
    {
        return $value === '1' || $value === 'true' || $value === 'on' || $value === true;
    }
}
