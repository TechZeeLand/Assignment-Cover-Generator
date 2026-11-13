<?php

declare(strict_types=1);

namespace App;

/** Page texts specific to the Assignment Cover Generator. Markdown lives in content/*.md (editable in Admin > Pages & policies). */
final class Content
{
    private static function md(string $name): string
    {
        $f = dirname(__DIR__) . '/content/' . $name . '.md';
        return is_file($f) ? (string) file_get_contents($f) : '';
    }

    /** Full replacements for default pages. */
    public static function pages(): array
    {
        return [
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'desc'  => 'How {site} handles data: what is stored, what is not, and how cookies and local storage are used.',
                'md'    => self::md('privacy-policy'),
            ],
            'cookie-policy' => [
                'title' => 'Cookie Policy',
                'desc'  => 'Cookies and local storage used by {site}.',
                'md'    => self::md('cookie-policy'),
            ],
        ];
    }

    /** Markdown appended to the kit's default pages. */
    public static function extra(): array
    {
        return [
            'about'                => "\n\n" . self::md('about-extra'),
            'terms-and-conditions' => "\n\n" . self::md('terms-extra'),
        ];
    }
}
