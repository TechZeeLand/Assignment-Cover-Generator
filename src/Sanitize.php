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
        $value = is_string($value) ? trim($value) : '';

        // Normalise <div>/<p> line breaks some contenteditable browsers insert.
        $value = preg_replace('#</(div|p)>#i', '<br>', $value) ?? $value;
        $value = preg_replace('#<(div|p)[^>]*>#i', '', $value) ?? $value;

        $allowed = Config::allowedRichTextTags();
        $stripped = strip_tags($value, $allowed);

        // Remove any attributes that survived on the allowed tags (e.g. onclick=).
        $clean = preg_replace('/<(b|strong|i|em|br)\b[^>]*>/i', '<$1>', $stripped) ?? $stripped;

        // Truncate by *visible* character count, not raw markup length, and
        // never cut in the middle of a tag - doing the length check before
        // stripping tags (as this used to) could sever a tag like "<b>" mid
        // way through, leaving invalid HTML that renderers can choke on or
        // silently drop.
        return self::truncateHtmlSafe($clean, $maxLength);
    }

    private static function truncateHtmlSafe(string $html, int $maxLength): string
    {
        $visibleCount = 0;
        $result = '';
        $openTags = [];
        $len = function_exists('mb_strlen') ? mb_strlen($html) : strlen($html);
        $charAt = function (int $i) use ($html) {
            return function_exists('mb_substr') ? mb_substr($html, $i, 1) : substr($html, $i, 1);
        };

        for ($i = 0; $i < $len; $i++) {
            $ch = $charAt($i);

            if ($ch === '<') {
                $tagEnd = function_exists('mb_strpos') ? mb_strpos($html, '>', $i) : strpos($html, '>', $i);
                if ($tagEnd === false) {
                    break; // malformed trailing fragment - stop here
                }
                $tag = function_exists('mb_substr') ? mb_substr($html, $i, $tagEnd - $i + 1) : substr($html, $i, $tagEnd - $i + 1);
                $result .= $tag;
                if (preg_match('#^</(\w+)>$#', $tag, $m)) {
                    $idx = array_search(strtolower($m[1]), array_reverse($openTags, true), true);
                    if ($idx !== false) {
                        unset($openTags[$idx]);
                    }
                } elseif (preg_match('#^<(\w+)>$#', $tag, $m) && strtolower($m[1]) !== 'br') {
                    $openTags[] = strtolower($m[1]);
                }
                $i = $tagEnd;
                continue;
            }

            if ($visibleCount >= $maxLength) {
                break;
            }
            $result .= $ch;
            $visibleCount++;
        }

        foreach (array_reverse($openTags) as $tag) {
            $result .= "</{$tag}>";
        }

        return $result;
    }

    /** True/false from an HTML checkbox's presence in POST data. */
    public static function bool(mixed $value): bool
    {
        return $value === '1' || $value === 'true' || $value === 'on' || $value === true;
    }

    /**
     * Font size in points, from a user-editable number field. Falls back to
     * $default when the value is missing, non-numeric, or outside the
     * allowed [$min, $max] range, so a stray/malicious value can never
     * shrink text to nothing or blow up the layout.
     */
    public static function fontSize(mixed $value, float $default, float $min = 6.0, float $max = 72.0): float
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return $default;
        }
        $num = (float) $value;
        if (!is_finite($num) || $num < $min || $num > $max) {
            return $default;
        }
        return round($num, 1);
    }
}
