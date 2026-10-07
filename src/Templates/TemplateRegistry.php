<?php

declare(strict_types=1);

namespace App\Templates;

use App\Settings;

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
                new OfficialFormTemplate(),
                new HeritageTemplate(),
                new ExecutiveTemplate(),
                new HeroBlockTemplate(),
                new MosaicTemplate(),
                new DuoToneTemplate(),
            ] as $template) {
                $all[$template->key()] = $template;
            }
        }
        return $all;
    }

    /**
     * Designs the admin has left switched on (all of them until the admin
     * saves a selection). Never empty, and always in display order.
     *
     * @return array<string, Template>
     */
    public static function active(): array
    {
        $all = self::all();
        $json = Settings::get('active_designs');
        $keys = $json !== '' ? json_decode($json, true) : null;
        if (!is_array($keys)) {
            return $all;
        }
        $active = array_filter($all, static fn (Template $t): bool => in_array($t->key(), $keys, true));
        return $active !== [] ? $active : $all;
    }

    /** The design pre-selected for new visitors. */
    public static function defaultKey(): string
    {
        $active = self::active();
        $wanted = Settings::get('default_design');
        return isset($active[$wanted]) ? $wanted : (string) array_key_first($active);
    }

    /** Returns an ACTIVE design key, falling back to the default design. */
    public static function resolveKey(string $requested): string
    {
        $requested = strtolower(trim($requested));
        return array_key_exists($requested, self::active()) ? $requested : self::defaultKey();
    }

    /** Looks a design up by key; inactive designs still render if asked for directly (e.g. by tests). */
    public static function get(string $key): Template
    {
        $all = self::all();
        return $all[$key] ?? $all[self::defaultKey()];
    }
}
