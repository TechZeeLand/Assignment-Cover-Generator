<?php

declare(strict_types=1);

namespace App;

use App\Templates\TemplateRegistry;
use Tzl\Kit;
use Tzl\Layout;
use Tzl\Router;
use Tzl\Site;
use Tzl\Stats as KitStats;
use Tzl\Ui;

/** Public routes and the kit hooks of the Assignment Cover Generator. */
final class Web
{
    /** Data for the JSON block read by assets/js/acg-data.js (set by the page that needs it). */
    private static array $data = [];

    public static function routes(): void
    {
        // Resolve the Google session BEFORE any output, because the shared header shows the account menu.
        $path = Router::path();
        if ($path !== '/admin' && strncmp($path, '/admin/', 7) !== 0) {
            Auth::user();
        }

        // Old .php addresses keep working (bookmarks, search engines).
        Router::get('#^/(index|about|contact|privacy-policy|terms-and-conditions|cookie-policy)\.php$#', static function (string $p): void {
            Kit::redirect($p === 'index' ? '/' : '/' . $p, 301);
        });

        Router::get('#^/$#', [self::class, 'home']);
        Router::get('#^/generate(?:\.php)?$#', static function (): void { Kit::redirect('/'); });
        Router::post('#^/generate(?:\.php)?$#', [self::class, 'generate']);

        Router::get('#^/fonts\.php$#', [self::class, 'fontsList']);
        Router::get('#^/font_file\.php$#', [self::class, 'fontFile']);
        Router::post('#^/upload_font\.php$#', [self::class, 'uploadFont']);

        Router::get('#^/api/profile(?:\.php)?$#', [self::class, 'profile']);
        Router::post('#^/api/profile(?:\.php)?$#', [self::class, 'profile']);

        // The Google redirect URI stays /auth/google-callback.php, so the Google Cloud Console needs no change.
        Router::get('#^/auth/google(?:\.php)?$#', [self::class, 'googleStart']);
        Router::get('#^/auth/google-callback(?:\.php)?$#', [self::class, 'googleCallback']);
        Router::post('#^/auth/logout(?:\.php)?$#', [self::class, 'logout']);
        Router::post('#^/auth/delete-account(?:\.php)?$#', [self::class, 'deleteAccount']);

        Router::get('#^/ads\.txt$#', [self::class, 'adsTxt']);

        AcgAdmin::routes();
    }

    /* ---------- kit hooks ---------- */

    /** Extra Content-Security-Policy hosts, only while ads are switched on. */
    public static function cspExtra(): array
    {
        if (!Ads::enabled()) {
            return [];
        }
        return [
            'script-src'  => 'https://pagead2.googlesyndication.com https://*.googlesyndication.com https://*.googleadservices.com https://*.google.com https://*.gstatic.com https://*.doubleclick.net https://adservice.google.com',
            'frame-src'   => 'https://googleads.g.doubleclick.net https://tpc.googlesyndication.com https://*.googlesyndication.com https://*.google.com https://*.doubleclick.net',
            'connect-src' => 'https://pagead2.googlesyndication.com https://*.googlesyndication.com https://*.google.com https://*.doubleclick.net https://*.googleadservices.com',
            'img-src'     => 'https:',
        ];
    }

    /** Account menu or "Sign in" button in the shared header. */
    public static function headerExtra(): string
    {
        $user = Auth::user();
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $ret = Http::safeReturn($uri);
        if ($user !== null) {
            return '<details class="user-menu"><summary aria-label="Account menu"><span class="user-avatar" aria-hidden="true">'
                . Html::e(mb_strtoupper(mb_substr($user['name'], 0, 1))) . '</span><span class="user-name">'
                . Html::e(explode(' ', $user['name'])[0]) . '</span></summary><div class="user-pop"><p class="user-pop-name">'
                . Html::e($user['name']) . '</p><p class="user-pop-email">' . Html::e($user['email']) . '</p>'
                . '<form method="post" action="/auth/logout">' . Csrf::field() . '<input type="hidden" name="return" value="' . Html::e($ret) . '">'
                . '<button type="submit" class="user-signout">' . Kit::icon('logout') . ' Sign out</button></form>'
                . '<form method="post" action="/auth/delete-account">' . Csrf::field()
                . '<button type="submit" class="user-delete" data-confirm="Delete your account and all saved details? This cannot be undone.">Delete my account &amp; data</button></form>'
                . '</div></details>';
        }
        if (Auth::signInAvailable()) {
            return '<a class="google-btn" href="/auth/google?return=' . rawurlencode($ret) . '">' . Icon::svg('google') . '<span>Sign in</span></a>';
        }
        return '';
    }

    /** Bottom ad slot above the footer (never on error pages: AdSense policy). */
    public static function beforeFooter(): string
    {
        return http_response_code() >= 400 ? '' : Ads::slot('bottom');
    }

    /** JSON data block for the scripts (a data block is not executable, so the strict CSP allows it). */
    public static function footExtra(): string
    {
        $data = self::$data + ['acg' => self::clientConfig()];
        return '<script type="application/json" id="acg-data">' . Html::json($data) . '</script>';
    }

    private static function clientConfig(): array
    {
        $user = Auth::user();
        return [
            'signedIn'     => $user !== null,
            'csrf'         => $user !== null ? Csrf::token() : '',
            'cookieBanner' => Site::on('cookie_banner_on'),
            'adsEnabled'   => Ads::enabled(),
            'adsConsent'   => Ads::requiresConsent(),
            'adsClient'    => Ads::publisherId(),
        ];
    }

    /* ---------- generator page ---------- */

    public static function home(): void
    {
        $fontManager = new FontManager();
        $initialFonts = $fontManager->listFonts();
        $designs = TemplateRegistry::active();
        $defaultDesign = TemplateRegistry::defaultKey();

        $acgUser = Auth::user();
        $profilesOn = Settings::bool('profiles_enabled');
        $savedProfile = null;
        if ($acgUser !== null && $profilesOn) {
            try {
                $savedProfile = ProfileStore::load($acgUser['id']);
            } catch (\Throwable $e) {
                error_log('[assignment-cover-generator] load profile: ' . $e->getMessage());
            }
        }
        $canSignIn = Auth::signInAvailable() && $profilesOn;

        self::$data = [
            'fonts'       => $initialFonts,
            'profile'     => $savedProfile,
            'fontUploads' => Settings::bool('font_uploads_enabled'),
        ];

        Layout::head('', '', ['css' => ['assets/css/acg.css']]);
        require dirname(__DIR__) . '/views/generator.php';
        Layout::foot(['js' => ['assets/js/app.js']]);
    }

    public static function generate(): void
    {
        try {
            $fonts = new FontManager();
            $data = CoverData::fromRequest($_POST, $fonts);

            $mpdf = PdfService::render($data, $fonts);
            $filename = PdfService::suggestFilename($data);

            // Anonymous counters (no personal data): the generator's own history and the kit's traffic page.
            Stats::hit('pdf');
            Stats::hit('pdf:' . $data->design);
            KitStats::event('pdf', $data->design);

            $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        } catch (\Throwable $e) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            error_log('[assignment-cover-generator] ' . $e->getMessage());
            echo "Sorry, something went wrong while generating the PDF.\n";
            echo 'Please double-check your inputs and try again.';
        }
        exit;
    }

    /* ---------- fonts ---------- */

    public static function fontsList(): void
    {
        try {
            $fonts = new FontManager();
            Http::json(['ok' => true, 'fonts' => $fonts->listFonts()]);
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] ' . $e->getMessage());
            Http::json(['ok' => false, 'error' => 'Could not load fonts.'], 500);
        }
    }

    public static function fontFile(): void
    {
        $key = (string) ($_GET['key'] ?? '');
        $variant = (string) ($_GET['variant'] ?? 'regular');
        if (!preg_match('/^[a-z0-9\-]{1,32}$/', $key) || !in_array($variant, ['regular', 'bold', 'italic', 'bolditalic'], true)) {
            http_response_code(400);
            exit('Invalid request.');
        }
        $indexFile = Config::fontsStoragePath() . '/index.json';
        $index = is_file($indexFile) ? json_decode((string) file_get_contents($indexFile), true) : [];
        $index = is_array($index) ? $index : [];

        $filename = null;
        foreach ($index as $entry) {
            if (($entry['key'] ?? null) === $key) {
                $filename = $entry['files'][$variant] ?? null;
                break;
            }
        }
        if ($filename === null) {
            http_response_code(404);
            exit('Font not found.');
        }
        $path = Config::fontsStoragePath() . '/' . basename((string) $filename);
        if (!is_file($path)) {
            http_response_code(404);
            exit('Font file missing.');
        }
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($ext === 'otf' ? 'font/otf' : 'font/ttf'));
        header('Cache-Control: public, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }

    public static function uploadFont(): void
    {
        try {
            if (!Settings::bool('font_uploads_enabled')) {
                Http::json(['ok' => false, 'error' => 'Font uploads are turned off on this site.'], 403);
            }
            $fonts = new FontManager();
            $familyName = (string) ($_POST['font-name'] ?? '');
            $files = [
                'regular'    => $_FILES['font-regular'] ?? null,
                'bold'       => $_FILES['font-bold'] ?? null,
                'italic'     => $_FILES['font-italic'] ?? null,
                'bolditalic' => $_FILES['font-bolditalic'] ?? null,
            ];
            foreach ($files as $k => $f) {
                if (!$f || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    unset($files[$k]);
                }
            }
            $entry = $fonts->registerUpload($familyName, $files);
            KitStats::event('font-upload');
            Http::json(['ok' => true, 'font' => $entry, 'fonts' => $fonts->listFonts()]);
        } catch (\RuntimeException $e) {
            Http::json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] ' . $e->getMessage());
            Http::json(['ok' => false, 'error' => 'Could not upload the font.'], 500);
        }
    }

    /* ---------- saved details API ---------- */

    public static function profile(): void
    {
        try {
            $user = Auth::user();
            if ($user === null || !Settings::bool('profiles_enabled')) {
                Http::json(['ok' => false, 'error' => 'Please sign in to save your details.'], 401);
            }
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
                Http::json(['ok' => true, 'profile' => ProfileStore::load($user['id'])]);
            }
            if (!Csrf::valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
                Http::json(['ok' => false, 'error' => 'Your session expired. Please reload the page.'], 403);
            }
            $body = json_decode((string) file_get_contents('php://input'), true);
            if (!is_array($body)) {
                Http::json(['ok' => false, 'error' => 'Invalid request.'], 400);
            }
            if (($body['action'] ?? '') === 'delete') {
                ProfileStore::delete($user['id']);
                Http::json(['ok' => true]);
            }
            if (($body['action'] ?? '') === 'save' && is_array($body['data'] ?? null)) {
                ProfileStore::save($user['id'], $body['data']);
                Http::json(['ok' => true]);
            }
            Http::json(['ok' => false, 'error' => 'Unknown action.'], 400);
        } catch (\RuntimeException $e) {
            Http::json(['ok' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] profile api: ' . $e->getMessage());
            Http::json(['ok' => false, 'error' => 'Could not save right now.'], 500);
        }
    }

    /* ---------- Google sign-in ---------- */

    public static function googleStart(): void
    {
        if (!Auth::signInAvailable()) {
            Http::redirect('/');
        }
        Http::redirect(GoogleOAuth::startUrl((string) ($_GET['return'] ?? '/')));
    }

    public static function googleCallback(): void
    {
        $error = null;
        try {
            if (!Auth::signInAvailable()) {
                throw new \RuntimeException('Sign-in is currently turned off.');
            }
            $result = GoogleOAuth::finish($_GET);
            $userId = Users::upsert($result['email'], $result['name']);
            Auth::login($userId);
            Stats::hit('signin');
            KitStats::event('signin');
            Http::redirect($result['return']);
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] sign-in failed: ' . $e->getMessage());
            $error = 'Something went wrong while signing you in. Please try again.';
        }
        Layout::page(
            'Sign-in problem',
            'Sign-in problem',
            "We couldn't sign you in",
            '',
            '<p>' . Kit::e($error) . '</p><p>Signing in is optional. You can keep using the generator without it.</p>'
            . '<p><a class="btn btn-primary" href="/">Back to the generator</a></p>',
            ['noindex' => true]
        );
    }

    public static function logout(): void
    {
        Csrf::requirePost();
        Auth::logout();
        Http::redirect(Http::safeReturn((string) ($_POST['return'] ?? '/')));
    }

    public static function deleteAccount(): void
    {
        Csrf::requirePost();
        $user = Auth::user();
        if ($user !== null) {
            Users::delete($user['id']);   // saved details go with it (ON DELETE CASCADE)
            Auth::logout();
        }
        Http::redirect('/');
    }

    public static function adsTxt(): void
    {
        $body = Ads::adsTxt();
        header('Content-Type: text/plain; charset=utf-8');
        if ($body === '') {
            http_response_code(404);
            echo "Not found\n";
            exit;
        }
        header('Cache-Control: public, max-age=3600');
        echo $body;
        exit;
    }

    /* ---------- admin dashboard + health checks ---------- */

    public static function dashboard(array $days): array
    {
        try {
            $now = time();
            $users = Users::total();
            $pdfAll = Stats::total('pdf');
            $pdf30 = Stats::total('pdf', 30);
            $pdfSeries = Stats::series('pdf', 30);
            $signSeries = Stats::series('signin', 30);
            $usage = Stats::designUsage();
            arsort($usage);
            $designs = TemplateRegistry::all();
            $active = TemplateRegistry::active();

            $rows = '';
            foreach ($designs as $key => $design) {
                $rows .= '<tr><td>' . Kit::e($design->label()) . ' <small class="muted">' . Kit::e($design->category()) . '</small></td><td>'
                    . (isset($active[$key]) ? '<span class="badge badge-ok">Active</span>' : '<span class="badge badge-soft">Hidden</span>')
                    . '</td><td class="right">' . number_format((int) ($usage[$key] ?? 0)) . '</td></tr>';
            }
            $html = '<div class="admin-cols">'
                . Ui::card('PDFs and sign-ins, last 30 days', 'file-pdf', Ui::chart(array_keys($pdfSeries), [['PDFs generated', $pdfSeries, 'bar-file'], ['Sign-ins', $signSeries, 'bar-css']])
                    . '<p class="hint">Counters are anonymous: no IP addresses or user IDs are stored.</p>')
                . Ui::card('Design usage (all time)', 'palette', '<div class="table-wrap"><table class="data-table"><thead><tr><th>Design</th><th>Status</th><th class="right">PDFs</th></tr></thead><tbody>' . $rows . '</tbody></table></div>')
                . '</div>';
            $people = '<ul class="activity-mini">'
                . '<li><strong>' . Users::signInsTotal() . '</strong> total sign-ins</li>'
                . '<li><strong>' . Users::activeSince($now - 86400) . ' / ' . Users::activeSince($now - 7 * 86400) . ' / ' . Users::activeSince($now - 30 * 86400) . '</strong> active users, 24 h / 7 d / 30 d</li>'
                . '<li><strong>' . Users::createdSince($now - 7 * 86400) . ' / ' . Users::createdSince($now - 30 * 86400) . '</strong> new users, 7 d / 30 d</li>'
                . '<li><strong>' . Users::withProfile() . '</strong> users with saved details</li></ul>';
            $html .= Ui::card('People', 'users', $people);

            return [
                'cards' => [
                    ['users', number_format($users), 'users signed in with Google'],
                    ['file-pdf', number_format($pdfAll) . ' (' . number_format($pdf30) . ' in 30 d)', 'PDFs generated'],
                ],
                'html'  => $html,
            ];
        } catch (\Throwable $e) {
            error_log('[assignment-cover-generator] dashboard: ' . $e->getMessage());
            return ['cards' => [], 'html' => ''];
        }
    }

    /** Extra health checks shown on the dashboard. Each row: [label, ok, help]. */
    public static function checks(): array
    {
        $rows = [];
        $rows[] = ['PDF library installed (mPDF)', class_exists(\Mpdf\Mpdf::class), 'Rebuild the image: composer dependencies are missing.'];
        $rows[] = ['Font storage is writable', is_writable(Config::fontsStoragePath()), 'Make storage/fonts writable by the web server user.'];
        $rows[] = ['Google sign-in is configured', Auth::signInAvailable(), 'Optional: add the client ID and secret under Admin > Sign-in (Google).'];
        $rows[] = ['Ads need the consent banner', !Ads::enabled() || !Ads::requiresConsent() || Settings::bool('cookie_banner_enabled'), 'Turn the banner on under Appearance when ads require consent.'];
        return $rows;
    }
}
