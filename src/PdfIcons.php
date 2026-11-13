<?php

declare(strict_types=1);

namespace App;

/**
 * The small line icons that can be printed on the cover ("Icons" switch):
 * student, teacher, clock, book, group and topic.
 *
 * They are plain SVG files in public/assets/icons/pdf/ that use only the
 * primitives mPDF's SVG renderer is reliable with (circles, rects, lines,
 * polylines and simple paths - no arcs). Each icon is recoloured by swapping
 * `currentColor` for a hex colour, written once per colour to the scratch
 * folder and referenced from an <img>. If that folder is not writable the
 * icon falls back to an inline base64 data URI.
 */
final class PdfIcons
{
    public const NAMES = ['student', 'teacher', 'clock', 'book', 'group', 'topic'];

    /** @var array<string,string> rendered <img> tags by cache key */
    private static array $cache = [];

    /**
     * An inline <img> for icon $name, $sizePt points square, drawn in $hex,
     * followed by a non-breaking space so the label that comes next never
     * touches it. Returns '' for an unknown icon (never throws).
     */
    public static function img(string $name, string $hex, float $sizePt): string
    {
        if (!in_array($name, self::NAMES, true)) {
            return '';
        }
        $hex = Sanitize::color($hex, '#000000');
        $size = max(6.0, round($sizePt, 1));
        $key = $name . '|' . $hex . '|' . $size;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $svg = self::coloured($name, $hex);
        if ($svg === '') {
            return self::$cache[$key] = '';
        }
        $src = self::fileFor($name, $hex, $svg);
        if ($src === '') {
            $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        return self::$cache[$key] = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" style="width:' . $size
            . 'pt; height:' . $size . 'pt; vertical-align:middle;" />&nbsp;';
    }

    private static function coloured(string $name, string $hex): string
    {
        $path = dirname(__DIR__) . '/public/assets/icons/pdf/' . $name . '.svg';
        $raw = is_file($path) ? trim((string) file_get_contents($path)) : '';
        if ($raw === '' || !str_starts_with($raw, '<svg')) {
            error_log('[assignment-cover-generator] missing pdf icon: ' . $name);
            return '';
        }
        return str_replace('currentColor', $hex, $raw);
    }

    /** Writes (once) the recoloured icon to the scratch folder; '' on any failure. */
    private static function fileFor(string $name, string $hex, string $svg): string
    {
        $dir = rtrim(Config::tempPath(), '/') . '/icons';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return '';
        }
        $file = $dir . '/' . $name . '-' . ltrim($hex, '#') . '.svg';
        if (!is_file($file) || filesize($file) === 0) {
            $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
            if (@file_put_contents($tmp, $svg) === false || !@rename($tmp, $file)) {
                @unlink($tmp);
                return '';
            }
        }
        return $file;
    }
}
