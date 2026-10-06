<?php

declare(strict_types=1);

namespace App\Templates;

/**
 * The list of available cover designs. To add a new design: create a class
 * implementing Template (usually by extending BaseTemplate) and add one
 * instance to all() below - the form picker, validation and PDF rendering
 * all pick it up automatically.
 */
final class TemplateRegistry
{
    public const DEFAULT_KEY = 'classic';

    /** @return array<string, Template> keyed by Template::key(), in display order */
    public static function all(): array
    {
        static $all = null;
        if ($all === null) {
            $all = [];
            foreach ([
                new ClassicTemplate(),
                new ModernBandsTemplate(),
                new DoubleFrameTemplate(),
                new CornersTemplate(),
                new SidebarTemplate(),
                new MinimalTemplate(),
            ] as $template) {
                $all[$template->key()] = $template;
            }
        }
        return $all;
    }

    /** Returns a known design key, falling back to the original design. */
    public static function resolveKey(string $requested): string
    {
        $requested = strtolower(trim($requested));
        return array_key_exists($requested, self::all()) ? $requested : self::DEFAULT_KEY;
    }

    public static function get(string $key): Template
    {
        $all = self::all();
        return $all[$key] ?? $all[self::DEFAULT_KEY];
    }
}
