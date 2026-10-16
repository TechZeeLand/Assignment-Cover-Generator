<?php

declare(strict_types=1);

namespace App;

/**
 * Only the Google brand mark is inlined from this project (public/assets/icons/social/google.svg),
 * because Google's sign-in guidelines require the exact multi-colour logo. Every other icon
 * comes from the TechZeeLand Icons service via Tzl\Kit::icon().
 */
final class Icon
{
    public static function svg(string $name): string
    {
        if ($name !== 'google') {
            return \Tzl\Kit::icon($name);
        }
        $path = dirname(__DIR__) . '/public/assets/icons/social/google.svg';
        $svg = is_file($path) ? trim((string) file_get_contents($path)) : '';
        if ($svg === '' || !str_starts_with($svg, '<svg')) {
            return '';
        }
        return (string) preg_replace_callback(
            '/^<svg\b([^>]*)>/',
            static function (array $m): string {
                $attrs = preg_replace('/\s(?:width|height|class)="[^"]*"/', '', $m[1]) ?? $m[1];
                return '<svg class="icon icon-google" aria-hidden="true" focusable="false" width="18" height="18"' . $attrs . '>';
            },
            $svg,
            1
        );
    }
}
