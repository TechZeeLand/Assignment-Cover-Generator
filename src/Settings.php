<?php

declare(strict_types=1);

namespace App;

/**
 * Key/value site settings edited from the admin portal. Reads never throw:
 * if the database is unavailable the built-in defaults are used, so the
 * public site keeps working.
 */
final class Settings
{
    public const DEFAULTS = [
        'site_name'              => 'Assignment Cover Generator',
        'site_url'               => '',
        'contact_email'          => '',
        'announcement_enabled'   => '0',
        'announcement_text'      => '',
        'signin_enabled'         => '1',
        'profiles_enabled'       => '1',
        'google_client_id'       => '',
        'google_client_secret'   => '',
        'active_designs'         => '',   // JSON list; empty = every design
        'default_design'         => 'classic',
        'font_uploads_enabled'   => '1',
        'cookie_banner_enabled'  => '1',
        'ads_enabled'            => '0',
        'ads_require_consent'    => '1',
        'adsense_publisher_id'   => '',
        'ad_slot_top'            => '',
        'ad_slot_middle'         => '',
        'ad_slot_bottom'         => '',
        'ads_txt'                => '',
        'admin_password_hash'    => '',
    ];

    /** @var array<string,string>|null */
    private static ?array $cache = null;

    /** @return array<string,string> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $values = self::DEFAULTS;
        try {
            foreach (Db::pdo()->query('SELECT key, value FROM settings') as $row) {
                if (array_key_exists($row['key'], $values)) {
                    $values[$row['key']] = (string) $row['value'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] settings unavailable: ' . $e->getMessage());
        }
        return self::$cache = $values;
    }

    public static function get(string $key): string
    {
        return self::all()[$key] ?? '';
    }

    public static function bool(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function set(string $key, string $value): void
    {
        if (!array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException('Unknown setting: ' . $key);
        }
        $stmt = Db::pdo()->prepare(
            'INSERT INTO settings (key, value) VALUES (:k, :v)
             ON CONFLICT(key) DO UPDATE SET value = excluded.value'
        );
        $stmt->execute([':k' => $key, ':v' => $value]);
        self::$cache = null;
    }

    /** @param array<string,string> $values */
    public static function setMany(array $values): void
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            foreach ($values as $k => $v) {
                self::set($k, $v);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
