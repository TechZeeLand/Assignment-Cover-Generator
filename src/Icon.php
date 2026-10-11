<?php

declare(strict_types=1);

namespace App;

/**
 * Inline SVG icons from public/assets/icons/{ui,social,other}/NAME.svg.
 *
 * Icons are inlined (no extra requests) so they inherit the surrounding text
 * colour through `currentColor` and are sized from CSS (1em by default, see
 * the .icon rule in style.css / admin.css). Usage:
 *
 *     <?php echo \App\Icon::svg('palette'); ?>
 *     <?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?>
 */
final class Icon
{
    private const GROUPS = ['ui', 'social', 'other'];

    /** @var array<string,string|null> raw file contents by icon name */
    private static array $raw = [];

    /**
     * @param array{class?:string,title?:string} $options
     */
    public static function svg(string $name, array $options = []): string
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name)) {
            return '';
        }
        $svg = self::load($name);
        if ($svg === null) {
            error_log('[assignment-cover-generator] missing icon: ' . $name);
            return '';
        }

        $classes = 'icon icon-' . $name;
        if (!empty($options['class']) && preg_match('/^[A-Za-z0-9_\- ]+$/', $options['class'])) {
            $classes .= ' ' . $options['class'];
        }
        $title = isset($options['title']) ? trim($options['title']) : '';
        $a11y = $title !== '' ? 'role="img" aria-label="' . Html::e($title) . '"' : 'aria-hidden="true"';

        // Rewrite only the root <svg ...> tag: drop the fixed width/height
        // so CSS controls the size, and add class + accessibility attributes.
        return (string) preg_replace_callback(
            '/^<svg\b([^>]*)>/',
            static function (array $m) use ($classes, $a11y): string {
                $attrs = preg_replace('/\s(?:width|height|class)="[^"]*"/', '', $m[1]) ?? $m[1];
                return '<svg class="' . $classes . '" ' . $a11y . ' focusable="false"' . $attrs . '>';
            },
            $svg,
            1
        );
    }

    private static function load(string $name): ?string
    {
        if (array_key_exists($name, self::$raw)) {
            return self::$raw[$name];
        }
        $base = dirname(__DIR__) . '/public/assets/icons/';
        foreach (self::GROUPS as $group) {
            $path = $base . $group . '/' . $name . '.svg';
            if (is_file($path)) {
                $content = trim((string) file_get_contents($path));
                return self::$raw[$name] = (str_starts_with($content, '<svg') ? $content : null);
            }
        }
        return self::$raw[$name] = null;
    }
}
