<?php
/**
 * First-run setup (runs at every container start, does nothing when already done):
 *  1. imports settings and the admin password from the previous version (SQLite -> TZL Kit)
 *  2. creates the admin account if none exists and prints the generated password once
 *  3. ADMIN_PASSWORD_RESET=1 + ADMIN_PASSWORD: forces that password again (lost-password recovery)
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Tzl/Kit.php';

Tzl\Kit::boot(['root' => dirname(__DIR__), 'service' => 'cover', 'name' => 'Assignment Cover Generator', 'defaults' => App\Settings::DEFAULTS]);

$legacy = (string) (getenv('ADMIN_PASSWORD') ?: '');
if ($legacy !== '' && (string) getenv('TZL_ADMIN_PASSWORD') === '') {
    putenv('TZL_ADMIN_PASSWORD=' . $legacy);   // the old variable still works for the very first admin
}

App\Migrate::run();

$reset = in_array(strtolower((string) getenv('ADMIN_PASSWORD_RESET')), ['1', 'true', 'yes'], true);
if ($reset && $legacy !== '' && Tzl\Auth::admin()) {
    $a = Tzl\Auth::admin();
    $a['hash'] = password_hash($legacy, PASSWORD_DEFAULT);
    $a['must_change'] = false;
    Tzl\Json::write('admin.json', $a);
    Tzl\Json::log('Admin password reset from ADMIN_PASSWORD_RESET');
    fwrite(STDOUT, "\n== Admin password reset to the value of ADMIN_PASSWORD (user: " . ($a['username'] ?? 'admin') . "). Remove ADMIN_PASSWORD_RESET now. ==\n\n");
}

$pw = Tzl\Auth::ensureAdmin();
if ($pw !== null) {
    $a = Tzl\Auth::admin();
    fwrite(STDOUT, "\n== First run ==\n   Admin user    : " . ($a['username'] ?? 'admin') . "\n   Admin password: " . $pw . "\n   Change it after signing in at /admin\n\n");
}
