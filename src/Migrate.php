<?php

declare(strict_types=1);

namespace App;

use Tzl\Auth as AdminAuth;
use Tzl\Json;
use Tzl\Kit;
use Tzl\Site;

/**
 * One-time import of the settings and the admin password hash from the old SQLite `settings` table
 * (Assignment Cover Generator 1.x) into the TZL Kit stores (site.json, admin.json).
 * Users, saved details and counters stay in SQLite untouched. Safe to run on every start.
 */
final class Migrate
{
    private const IMPORT = [
        'site_name', 'site_url', 'contact_email', 'announcement_enabled', 'announcement_text',
        'cookie_banner_enabled', 'signin_enabled', 'profiles_enabled', 'google_client_id', 'google_client_secret',
        'active_designs', 'default_design', 'font_uploads_enabled', 'ads_enabled', 'ads_require_consent',
        'adsense_publisher_id', 'ad_slot_top', 'ad_slot_middle', 'ad_slot_bottom', 'ads_txt',
    ];

    public static function run(): void
    {
        $marker = Json::path('migrated-to-tzl.json');
        if (is_file($marker)) {
            return;
        }
        $db = Config::dataPath() . '/app.sqlite';
        if (!is_file($db)) {
            Json::write('migrated-to-tzl.json', ['at' => Kit::now(), 'note' => 'fresh install']);
            return;
        }
        try {
            $rows = Db::pdo()->query('SELECT key, value FROM settings')->fetchAll(\PDO::FETCH_KEY_PAIR);
            $current = Json::read('site.json', []);
            $current = is_array($current) ? $current : [];
            $imported = [];
            $new = [];
            foreach (self::IMPORT as $legacy) {
                if (!isset($rows[$legacy]) || (string) $rows[$legacy] === '') {
                    continue;
                }
                $key = Settings::key($legacy);
                if (array_key_exists($key, $current)) {
                    continue; // the new store already has a value
                }
                $val = (string) $rows[$legacy];
                $isBool = array_key_exists($key, Settings::DEFAULTS) && is_bool(Settings::DEFAULTS[$key])
                    || in_array($key, ['announcement_on', 'cookie_banner_on'], true);
                $new[$key] = $isBool ? $val === '1' : $val;
                $imported[] = $legacy;
            }
            if ($new) {
                Site::save($new);
            }
            $hash = (string) ($rows['admin_password_hash'] ?? '');
            $adminDone = false;
            if ($hash !== '' && !AdminAuth::admin()) {
                Json::write('admin.json', [
                    'username'    => 'admin',
                    'hash'        => $hash,
                    'must_change' => false,
                    'created'     => Kit::now(),
                    'migrated'    => true,
                ]);
                @chmod(Json::path('admin.json'), 0660);
                $adminDone = true;
                Json::log('Admin password carried over from Assignment Cover Generator 1.x (user name: admin)');
            }
            Json::write('migrated-to-tzl.json', ['at' => Kit::now(), 'settings' => $imported, 'admin' => $adminDone]);
            Json::log('Imported ' . count($imported) . ' settings from the previous version');
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] migration failed: ' . $e->getMessage());
        }
    }
}
