<?php

declare(strict_types=1);

namespace App;

/**
 * The small line icons that can be printed on the cover ("Icons" switch):
 * student (a user), clock, book, group and topic.
 *
 * They are plain SVG files in public/assets/icons/pdf/ that use only the
 * primitives mPDF's SVG renderer is reliable with (circles, rects, lines,
 * polylines and simple paths - no arcs).
 *
 * mPDF does not reliably inherit stroke/fill from the root <svg> element, so
 * the files keep their attributes on the root (handy for browsers and editors)
 * and this class copies them onto EVERY shape and resolves `currentColor` to a
 * hex colour. The result is written once per (icon, colour, content hash) to
 * the scratch folder and referenced from an <img>; the hash is part of the file
 * name, so editing an icon file can never serve a stale cached copy. If the
 * folder is not writable the icon falls back to an inline base64 data URI.
 */
final class PdfIcons
{
    public const NAMES = ['student', 'clock', 'book', 'group', 'topic'];

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
        $base = $key;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $svg = self::coloured($name, $hex);
        if ($svg === '') {
            return self::$cache[$key] = '';
        }
        // The hash covers the final markup, so a changed icon file or a changed
        // stroke rule below gets a new file name and a new in-memory key.
        $hash = substr(hash('sha256', $svg), 0, 10);
        $key .= '|' . $hash;
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        $src = self::fileFor($name, $hex, $hash, $svg);
        if ($src === '') {
            $src = 'data:image/svg+xml;base64,' . base64_encode($svg);
        }

        return self::$cache[$base] = self::$cache[$key] = '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" style="width:' . $size
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
        $raw = str_replace('currentColor', $hex, $raw);

        // Presentation attributes found on the root element, copied onto each shape.
        $attrs = ['fill' => 'none', 'stroke' => $hex, 'stroke-width' => '2',
                  'stroke-linecap' => 'round', 'stroke-linejoin' => 'round'];
        if (preg_match('/^<svg\b[^>]*>/i', $raw, $root)) {
            foreach (array_keys($attrs) as $attr) {
                if (preg_match('/\s' . preg_quote($attr, '/') . '="([^"]*)"/i', $root[0], $m)) {
                    $attrs[$attr] = $m[1];
                }
            }
        }
        $attrs['stroke'] = $attrs['stroke'] === 'currentColor' ? $hex : $attrs['stroke'];
        // Add the attributes to every shape that does not already set its own.
        $raw = (string) preg_replace_callback(
            '/<(path|circle|rect|line|polyline|polygon|ellipse)\b([^>]*?)(\/?)>/i',
            static function (array $m) use ($attrs): string {
                $extra = '';
                foreach ($attrs as $k => $v) {
                    if (!preg_match('/\s' . preg_quote($k, '/') . '=/i', $m[2])) {
                        $extra .= ' ' . $k . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
                    }
                }
                return '<' . $m[1] . $m[2] . $extra . $m[3] . '>';
            },
            $raw
        );
        // Keep the root minimal: size and viewBox only (no inheritable paint to rely on).
        $raw = (string) preg_replace_callback('/^<svg\b[^>]*>/i', static function (array $m): string {
            $vb = preg_match('/\sviewBox="([^"]*)"/i', $m[0], $v) ? $v[1] : '0 0 24 24';
            return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $vb . '" width="24" height="24">';
        }, $raw, 1);

        return $raw;
    }

    /** Writes (once) the recoloured icon to the scratch folder; '' on any failure. */
    private static function fileFor(string $name, string $hex, string $hash, string $svg): string
    {
        $dir = rtrim(Config::tempPath(), '/') . '/icons';
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            return '';
        }
        $file = $dir . '/' . $name . '-' . ltrim($hex, '#') . '-' . $hash . '.svg';
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
