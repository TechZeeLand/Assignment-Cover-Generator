<?php

declare(strict_types=1);

namespace App;

use App\Templates\TemplateRegistry;
use Tzl\Admin;
use Tzl\Auth as AdminAuth;
use Tzl\Json;
use Tzl\Kit;
use Tzl\Router;
use Tzl\Ui;

/** Generator-specific admin screens. Layout, menu, login and CSRF come from the TZL Kit. */
final class AcgAdmin
{
    public static function routes(): void
    {
        $g = static fn(string $re, string $m) => Router::get($re, [self::class, $m]);
        $p = static fn(string $re, string $m) => Router::post($re, [self::class, $m]);
        $g('#^/admin/designs$#', 'designs');     $p('#^/admin/designs$#', 'designsSave');
        $g('#^/admin/signin$#', 'signin');       $p('#^/admin/signin$#', 'signinSave');
        $g('#^/admin/users$#', 'users');         $p('#^/admin/users$#', 'usersDelete');
        $g('#^/admin/users\.csv$#', 'usersCsv');
        $g('#^/admin/fonts$#', 'fonts');         $p('#^/admin/fonts$#', 'fontsSave');
        $g('#^/admin/ads$#', 'ads');             $p('#^/admin/ads$#', 'adsSave');
    }

    private static function back(string $to): void { Kit::redirect($to); }

    /* ---------- Designs ---------- */

    public static function designs(): void
    {
        $all = TemplateRegistry::all();
        $active = TemplateRegistry::active();
        $default = TemplateRegistry::defaultKey();
        $cards = '';
        foreach ($all as $key => $design) {
            $cards .= '<div class="dcard"><div class="design-thumb">' . $design->thumbnailSvg() . '</div>'
                . '<strong>' . Kit::e($design->label()) . '</strong> <small class="muted">' . Kit::e($design->category()) . '</small>'
                . '<p class="hint">' . Kit::e($design->description()) . '</p>'
                . '<label class="row-check"><input type="checkbox" name="active[]" value="' . Kit::e($key) . '"' . (isset($active[$key]) ? ' checked' : '') . '><span class="switch"></span><span class="row-check-label">Active</span></label>'
                . '<label class="mini-check"><input type="radio" name="default" value="' . Kit::e($key) . '"' . ($key === $default ? ' checked' : '') . '> <span>Default</span></label></div>';
        }
        $h = '<form method="post" action="/admin/designs" class="admin-form">' . AdminAuth::field();
        $h .= Ui::card('Cover designs', 'palette', '<div class="design-admin" style="--t-accent:#111;--t-primary:#0384fc;--t-secondary:#fc9803;">' . $cards . '</div>',
            'Choose which cover designs visitors can pick, and which one is selected by default. Hidden designs stay in the code and can be switched back on at any time.');
        $h .= '<div class="form-actions"><button class="btn btn-primary" type="submit">' . Kit::icon('save') . ' Save designs</button></div></form>';
        Admin::page('Designs', 'designs', $h);
    }

    public static function designsSave(): void
    {
        AdminAuth::verifyCsrf();
        $all = TemplateRegistry::all();
        $selected = array_values(array_intersect(array_keys($all), (array) ($_POST['active'] ?? [])));
        if ($selected === []) {
            Admin::flash('err', 'At least one design must stay active.');
            self::back('/admin/designs');
        }
        $default = (string) ($_POST['default'] ?? '');
        if (!in_array($default, $selected, true)) {
            $default = $selected[0];
        }
        Settings::setMany([
            // "Everything selected" is stored as empty so designs added in future updates appear automatically.
            'active_designs' => count($selected) === count($all) ? '' : (string) json_encode($selected),
            'default_design' => $default,
        ]);
        Json::log('Updated cover designs (' . count($selected) . ' active, default ' . $default . ')');
        Admin::flash('ok', 'Designs saved.');
        self::back('/admin/designs');
    }

    /* ---------- Google sign-in ---------- */

    public static function signin(): void
    {
        $hasSecret = Settings::get('google_client_secret') !== '';
        $redirect = GoogleOAuth::redirectUri();
        $origin = Http::baseUrl();
        $urlSet = trim((string) \Tzl\Site::get('base_url')) !== '' || Kit::env('TZL_BASE_URL') !== '';

        $h = '<form method="post" action="/admin/signin" class="admin-form" autocomplete="off">' . AdminAuth::field();
        $h .= Ui::card('Settings', 'key',
            '<div class="switch-list">'
            . Ui::check('signin_enabled', 'Offer "Sign in with Google" to visitors', Settings::bool('signin_enabled'), 'optional for visitors; the button appears once both keys are filled in')
            . Ui::check('profiles_enabled', 'Let signed-in users save their details', Settings::bool('profiles_enabled'), 'stores their form details (not the topic or date) on this server')
            . '</div>'
            . Ui::text('Google OAuth client ID', 'google_client_id', Settings::get('google_client_id'), ['placeholder' => '1234567890-abc123.apps.googleusercontent.com', 'autocomplete' => 'off'])
            . Ui::text('Google OAuth client secret', 'google_client_secret', '', ['type' => 'password', 'autocomplete' => 'new-password',
                'placeholder' => $hasSecret ? 'saved. Leave blank to keep it' : 'GOCSPX-...'])
            . ($hasSecret ? '<div class="switch-list">' . Ui::check('remove_secret', 'Remove the saved secret', false) . '</div>' : '')
            . '<p class="hint">The secret is stored on the server only. It is left out of settings exports; it is part of the backup ZIP, so keep backups private.</p>');
        $h .= '<div class="form-actions"><button class="btn btn-primary" type="submit">' . Kit::icon('save') . ' Save</button></div></form>';

        $steps = '<ol class="steps">'
            . '<li>Open <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener">Google Cloud Console &rarr; APIs &amp; Services &rarr; Credentials</a> (create a project if you do not have one).</li>'
            . '<li>Configure the <strong>OAuth consent screen</strong> (External). Only the default scopes (<code>openid</code>, <code>email</code>, <code>profile</code>) are used. Publish the app to <em>In production</em> so everyone can sign in.</li>'
            . '<li><strong>Create credentials &rarr; OAuth client ID &rarr; Web application.</strong></li>'
            . '<li>Add this under <em>Authorized redirect URIs</em> <strong>exactly</strong>:<div class="code-block"><pre><code>' . Kit::e($redirect) . '</code></pre></div></li>'
            . '<li>Add this under <em>Authorized JavaScript origins</em> (optional but recommended):<div class="code-block"><pre><code>' . Kit::e($origin) . '</code></pre></div></li>'
            . '<li>Paste the client ID and secret above and save.</li></ol>'
            . '<p class="hint">The address comes from <a href="/admin/site">Site &amp; domains &rarr; Public URL</a> (or <code>TZL_BASE_URL</code>, or this request). Behind HTTPS or a proxy it must start with <code>https://</code>'
            . ($urlSet ? '' : '. <strong>It is not set yet, so it is being detected from this request.</strong>') . '</p>'
            . '<p class="hint"><strong>After a domain move</strong> (for example to <code>techzeeland.com</code>), add the new redirect URI in Google Cloud Console too. Keep the old one until the move is finished.</p>'
            . '<p class="hint">Only the visitor\'s name and email are read from Google. The Google account ID and profile picture are not stored.</p>';
        $h .= Ui::card('How to get the keys', 'info', $steps);
        Admin::page('Sign-in (Google)', 'signin', $h);
    }

    public static function signinSave(): void
    {
        AdminAuth::verifyCsrf();
        $clientId = Admin::str('google_client_id', 200);
        $secret = Admin::str('google_client_secret', 200);
        if ($clientId !== '' && !preg_match('/^[0-9A-Za-z._\-]+\.apps\.googleusercontent\.com$/', $clientId)) {
            Admin::flash('err', 'That does not look like a Google OAuth client ID (it should end in .apps.googleusercontent.com).');
            self::back('/admin/signin');
        }
        $values = [
            'signin_enabled'   => Admin::bool('signin_enabled'),
            'profiles_enabled' => Admin::bool('profiles_enabled'),
            'google_client_id' => $clientId,
        ];
        if (Admin::bool('remove_secret')) {
            $values['google_client_secret'] = '';
        } elseif ($secret !== '') {
            $values['google_client_secret'] = $secret;   // blank = keep the stored one
        }
        Settings::setMany($values);
        Json::log('Updated Google sign-in settings');
        Admin::flash('ok', 'Sign-in settings saved.');
        self::back('/admin/signin');
    }

    /* ---------- Users ---------- */

    public static function users(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $perPage = 25;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $res = Users::search($q, $perPage, ($page - 1) * $perPage);
        $pages = max(1, (int) ceil($res['total'] / $perPage));

        $rows = '';
        foreach ($res['rows'] as $r) {
            $rows .= '<tr><td>' . Kit::e($r['name']) . '</td><td>' . Kit::e($r['email']) . '</td><td class="nowrap">' . gmdate('Y-m-d', (int) $r['created_at'])
                . '</td><td class="nowrap">' . gmdate('Y-m-d H:i', (int) $r['last_login_at']) . ' UTC</td><td class="right">' . (int) $r['login_count'] . '</td><td>'
                . ($r['has_profile'] ? '<span class="badge badge-ok">Yes</span>' : '<span class="badge badge-soft">No</span>') . '</td><td class="right">'
                . '<form method="post" action="/admin/users">' . AdminAuth::field() . '<input type="hidden" name="delete" value="' . (int) $r['id'] . '">'
                . '<button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this user and their saved details?">' . Kit::icon('trash') . ' Delete</button></form></td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="7" class="muted">No users yet.</td></tr>';
        }
        $qs = $q !== '' ? '&q=' . rawurlencode($q) : '';
        $pager = '';
        if ($pages > 1) {
            $pager = '<p class="center">' . ($page > 1 ? '<a class="btn btn-secondary btn-sm" href="?page=' . ($page - 1) . Kit::e($qs) . '">Previous</a> ' : '')
                . 'Page ' . $page . ' of ' . $pages
                . ($page < $pages ? ' <a class="btn btn-secondary btn-sm" href="?page=' . ($page + 1) . Kit::e($qs) . '">Next</a>' : '') . '</p>';
        }
        $h = Ui::card('Users who signed in with Google', 'users',
            '<form method="get" action="/admin/users" class="toolbar"><div class="search-box">' . Kit::icon('search') . '<input type="search" name="q" value="' . Kit::e($q) . '" placeholder="Search name or email" aria-label="Search users"></div>'
            . '<button class="btn btn-secondary" type="submit">Search</button> <a class="btn btn-secondary" href="/admin/users.csv?q=' . rawurlencode($q) . '">' . Kit::icon('download') . ' Export CSV</a></form>'
            . '<p class="hint">' . (int) $res['total'] . ' user' . ($res['total'] === 1 ? '' : 's') . '. Only a name and email are stored for each person.</p>'
            . '<div class="table-wrap"><table class="data-table"><thead><tr><th>Name</th><th>Email</th><th>First sign-in</th><th>Last sign-in</th><th class="right">Sign-ins</th><th>Saved</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></div>' . $pager);
        Admin::page('Users', 'users', $h);
    }

    public static function usersDelete(): void
    {
        AdminAuth::verifyCsrf();
        $id = (int) ($_POST['delete'] ?? 0);
        if ($id > 0) {
            Users::delete($id);
            Json::log('Deleted a user and their saved details');
            Admin::flash('ok', 'User and their saved details were deleted.');
        }
        self::back('/admin/users');
    }

    public static function usersCsv(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $all = Users::search($q, 100000, 0);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['name', 'email', 'first_sign_in', 'last_sign_in', 'sign_ins', 'has_saved_details']);
        // Prefix cells that spreadsheets would execute as formulas.
        $safe = static fn(string $v): string => preg_match('/^[=+\-@]/', $v) ? "'" . $v : $v;
        foreach ($all['rows'] as $r) {
            fputcsv($out, [$safe((string) $r['name']), $safe((string) $r['email']), gmdate('c', (int) $r['created_at']),
                gmdate('c', (int) $r['last_login_at']), $r['login_count'], $r['has_profile'] ? 'yes' : 'no']);
        }
        Json::log('Exported the users list (CSV)');
        exit;
    }

    /* ---------- Custom fonts ---------- */

    public static function fonts(): void
    {
        $fonts = new FontManager();
        $rows = '';
        foreach ($fonts->listCustom() as $f) {
            $rows .= '<tr><td>' . Kit::e($f['name']) . '</td><td><code>' . Kit::e($f['key']) . '</code></td><td>' . Kit::e(implode(', ', array_keys($f['files']))) . '</td><td class="right">'
                . '<form method="post" action="/admin/fonts">' . AdminAuth::field() . '<input type="hidden" name="delete" value="' . Kit::e($f['key']) . '">'
                . '<button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this font?">' . Kit::icon('trash') . ' Delete</button></form></td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="muted">No custom fonts uploaded.</td></tr>';
        }
        $h = '<form method="post" action="/admin/fonts" class="admin-form">' . AdminAuth::field()
            . Ui::card('Visitor uploads', 'upload', '<div class="switch-list">' . Ui::check('font_uploads_enabled', 'Allow visitors to upload custom fonts', Settings::bool('font_uploads_enabled'), 'uploaded fonts are shared with everyone; turn this off if you see abuse') . '</div>')
            . '<div class="form-actions"><button class="btn btn-primary" type="submit" name="do" value="save">' . Kit::icon('save') . ' Save</button></div></form>';
        $h .= Ui::card('Uploaded fonts', 'font', '<p class="hint">Fonts uploaded by visitors are shared with everyone. Remove anything you do not want to host.</p><div class="table-wrap"><table class="data-table"><thead><tr><th>Name</th><th>Key</th><th>Files</th><th></th></tr></thead><tbody>' . $rows . '</tbody></table></div>');
        Admin::page('Custom fonts', 'fonts', $h);
    }

    public static function fontsSave(): void
    {
        AdminAuth::verifyCsrf();
        $key = (string) ($_POST['delete'] ?? '');
        if ($key !== '') {
            $fonts = new FontManager();
            if (preg_match('/^[a-z0-9\-]{1,40}$/', $key) && $fonts->deleteFont($key)) {
                Json::log('Deleted custom font ' . $key);
                Admin::flash('ok', 'Font deleted. Saved covers that used it will fall back to a default font.');
            } else {
                Admin::flash('err', 'Could not delete that font.');
            }
        } else {
            Settings::setMany(['font_uploads_enabled' => Admin::bool('font_uploads_enabled')]);
            Json::log('Font uploads ' . (Admin::bool('font_uploads_enabled') ? 'enabled' : 'disabled'));
            Admin::flash('ok', 'Saved.');
        }
        self::back('/admin/fonts');
    }

    /* ---------- Ads ---------- */

    public static function ads(): void
    {
        $effective = Ads::adsTxt();
        $h = '<form method="post" action="/admin/ads" class="admin-form">' . AdminAuth::field();
        $h .= Ui::card('Google AdSense', 'ad',
            '<div class="switch-list">'
            . Ui::check('ads_enabled', 'Show ads on the site', Settings::bool('ads_enabled'), 'nothing is shown until a valid publisher ID is saved; ads are never placed inside the PDF')
            . Ui::check('ads_require_consent', 'Load ads only after the visitor accepts cookies', Settings::bool('ads_require_consent'), 'recommended; needs the notice banner (Appearance)')
            . '</div>'
            . '<p class="hint">If you serve visitors in the EEA, UK or Switzerland, Google also requires a certified consent management platform for personalised ads. Consider enabling Google\'s own consent message in your AdSense account.</p>'
            . Ui::text('Publisher ID', 'adsense_publisher_id', Settings::get('adsense_publisher_id'), ['placeholder' => 'ca-pub-1234567890123456', 'autocomplete' => 'off']));
        $h .= Ui::card('Ad units', 'layout',
            '<p class="hint">Create <em>Display ads</em> (responsive) in AdSense and paste each unit\'s numeric ID. Leave a field empty to skip that placement.</p>'
            . '<div class="field-row">' . Ui::text('Top: below the page intro', 'ad_slot_top', Settings::get('ad_slot_top'), ['placeholder' => '1234567890'])
            . Ui::text('Middle: between form sections', 'ad_slot_middle', Settings::get('ad_slot_middle'), ['placeholder' => '1234567890']) . '</div>'
            . Ui::text('Bottom: above the footer (all pages)', 'ad_slot_bottom', Settings::get('ad_slot_bottom'), ['placeholder' => '1234567890']));
        $h .= Ui::card('ads.txt', 'file-text',
            '<p class="hint">Served at <a href="/ads.txt" target="_blank" rel="noopener">' . Kit::e(Http::baseUrl()) . '/ads.txt</a>. Leave empty to generate the standard AdSense line from your publisher ID; paste your own lines to override it (this is the same field as in SEO, ads &amp; code).</p>'
            . Ui::area('ads.txt lines', 'ads_txt', Settings::get('ads_txt'), ['rows' => 5, 'mono' => true, 'maxlength' => 5000, 'placeholder' => 'google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0',
                'hint' => 'Currently serving: ' . ($effective === '' ? '<em>nothing (404)</em>' : '<code>' . nl2br(Kit::e(trim($effective))) . '</code>')]));
        $h .= '<div class="form-actions"><button class="btn btn-primary" type="submit">' . Kit::icon('save') . ' Save</button></div></form>';
        Admin::page('Ads & ads.txt', 'ads', $h);
    }

    public static function adsSave(): void
    {
        AdminAuth::verifyCsrf();
        $pub = Admin::str('adsense_publisher_id', 40);
        if ($pub !== '' && preg_match('/^pub-\d{8,20}$/', $pub)) {
            $pub = 'ca-' . $pub;
        }
        if ($pub !== '' && !preg_match('/^ca-pub-\d{8,20}$/', $pub)) {
            Admin::flash('err', 'The publisher ID should look like ca-pub-1234567890123456.');
            self::back('/admin/ads');
        }
        $values = [
            'ads_enabled'          => Admin::bool('ads_enabled'),
            'ads_require_consent'  => Admin::bool('ads_require_consent'),
            'adsense_publisher_id' => $pub,
        ];
        foreach (Ads::SLOTS as $slotKey) {
            $v = Admin::str($slotKey, 30);
            if ($v !== '' && !preg_match('/^\d{6,20}$/', $v)) {
                Admin::flash('err', 'Ad unit IDs are numbers only (for example 1234567890).');
                self::back('/admin/ads');
            }
            $values[$slotKey] = $v;
        }
        $txt = trim(Admin::raw('ads_txt', 5000));
        if (preg_match('/[^\x20-\x7E\n\t]/', $txt)) {
            Admin::flash('err', 'ads.txt must be plain ASCII text (max 5000 characters).');
            self::back('/admin/ads');
        }
        $values['ads_txt'] = $txt;
        Settings::setMany($values);
        Json::log('Updated ad settings');
        Admin::flash('ok', 'Ad settings saved.');
        self::back('/admin/ads');
    }
}
