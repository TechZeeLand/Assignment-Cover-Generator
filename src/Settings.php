<?php

declare(strict_types=1);

namespace App;

use Tzl\Site;

/**
 * Compatibility layer: the generator's own code still calls Settings::get()/bool()/set(),
 * but every value now lives in the TZL Kit site settings (data/site.json), so the shared
 * admin (Site & domains, Appearance, Security ...) and the generator screens edit one store.
 */
final class Settings
{
    /** Old generator key => kit key (all other keys keep their name). */
    private const MAP = [
        'site_url'              => 'base_url',
        'announcement_enabled'  => 'announcement_on',
        'cookie_banner_enabled' => 'cookie_banner_on',
    ];

    /** Kit keys that hold true/false. */
    private const BOOLS = [
        'signin_enabled', 'profiles_enabled', 'font_uploads_enabled', 'ads_enabled',
        'ads_require_consent', 'announcement_on', 'cookie_banner_on',
    ];

    /** Defaults passed to Kit::boot(['defaults' => ...]). */
    public const DEFAULTS = [
        'signin_enabled'       => true,
        'profiles_enabled'     => true,
        'google_client_id'     => '',
        'google_client_secret' => '',
        'active_designs'       => '',   // JSON list; empty = every design
        'default_design'       => 'classic',
        'font_uploads_enabled' => true,
        'cookie_banner_on'     => true,
        'ads_enabled'          => false,
        'ads_require_consent'  => true,
        'adsense_publisher_id' => '',
        'ad_slot_top'          => '',
        'ad_slot_middle'       => '',
        'ad_slot_bottom'       => '',
    ];

    public static function key(string $legacy): string
    {
        return self::MAP[$legacy] ?? $legacy;
    }

    public static function get(string $key): string
    {
        $v = Site::get(self::key($key));
        if (is_bool($v)) {
            return $v ? '1' : '0';
        }
        return is_scalar($v) ? (string) $v : '';
    }

    public static function bool(string $key): bool
    {
        return Site::on(self::key($key));
    }

    public static function set(string $key, string $value): void
    {
        self::setMany([$key => $value]);
    }

    /** @param array<string,string|bool> $values legacy or kit keys */
    public static function setMany(array $values): void
    {
        $new = [];
        foreach ($values as $k => $v) {
            $k = self::key((string) $k);
            $new[$k] = in_array($k, self::BOOLS, true) ? ($v === true || $v === '1' || $v === 1) : (string) $v;
        }
        Site::save($new);
    }
}
