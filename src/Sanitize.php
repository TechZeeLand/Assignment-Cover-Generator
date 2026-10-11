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

        // Normalise the <div>/<p> line wrappers contenteditable browsers
        // insert (Chrome/Edge/Safari wrap every new line in a <div>, e.g.
        // "one<div>two</div>"). A wrapper starts a new line, so each opening
        // tag becomes a <br> and closing tags are dropped; an empty wrapper
        // ("<div><br></div>") is a blank line. Previously the closing tag
        // became the <br>, which glued "one" and "two" together.
        $value = preg_replace('#<(div|p)[^>]*>\s*<br\s*/?>\s*</\1>#i', '<br>', $value) ?? $value;
        $value = preg_replace('#<(div|p)[^>]*>#i', '<br>', $value) ?? $value;
        $value = preg_replace('#</(div|p)>#i', '', $value) ?? $value;
        // No leading / trailing blank lines.
        $value = preg_replace('#^(?:\s*<br\s*/?>)+#i', '', $value) ?? $value;
        $value = preg_replace('#(?:<br\s*/?>\s*)+$#i', '', $value) ?? $value;

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

    /**
     * Cuts already-stripped rich text (only <b>/<strong>/<i>/<em>/<br> tags
     * remain) to $maxLength *visible* characters. Text is decoded, cut and
     * re-escaped, so an entity such as "&amp;" is never severed half way
     * and a bare "&" or "<" can never reach the PDF renderer as markup.
     * Unmatched closing tags are dropped and unclosed ones are closed.
     */
    private static function truncateHtmlSafe(string $html, int $maxLength): string
    {
        $parts = preg_split('#(</?(?:b|strong|i|em)>|<br>)#i', $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $remaining = $maxLength;
        $result = '';
        $open = [];

        foreach ($parts as $part) {
            if (preg_match('#^</(b|strong|i|em)>$#i', $part, $m)) {
                $tag = strtolower($m[1]);
                for ($i = count($open) - 1; $i >= 0; $i--) {
                    if ($open[$i] === $tag) {
                        array_splice($open, $i, 1);
                        $result .= '</' . $tag . '>';
                        break;
                    }
                }
            } elseif (preg_match('#^<(b|strong|i|em)>$#i', $part, $m)) {
                if ($remaining > 0) {
                    $tag = strtolower($m[1]);
                    $open[] = $tag;
                    $result .= '<' . $tag . '>';
                }
            } elseif (strtolower($part) === '<br>') {
                if ($remaining > 0) {
                    $result .= '<br>';
                }
            } elseif ($remaining > 0) {
                $text = html_entity_decode($part, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = function_exists('mb_substr') ? mb_substr($text, 0, $remaining) : substr($text, 0, $remaining);
                $remaining -= function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
                $result .= htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        foreach (array_reverse($open) as $tag) {
            $result .= '</' . $tag . '>';
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
