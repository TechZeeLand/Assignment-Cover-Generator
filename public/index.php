<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Tzl/Kit.php';

use App\Settings;
use App\Web;
use Tzl\Kit;

Kit::boot([
    'root'        => dirname(__DIR__),
    'service'     => 'cover',
    'version'     => '2.0.0',
    'name'        => 'Assignment Cover Generator',
    'short'       => 'Cover Generator',
    'tagline'     => 'Print-ready assignment cover pages as PDF.',
    'description' => 'Free, open-source assignment cover page generator. Design your cover, then export a print-ready PDF.',
    'github'      => 'https://github.com/TechZeeLand/Assignment-Cover-Generator',
    'css'         => ['assets/css/acg-global.css'],
    'js'          => ['assets/js/acg-data.js', 'assets/js/consent.js'],
    'nav'         => [['Create cover', '/', 'file-pdf'], ['About', '/about', 'info']],
    'sitemap'     => ['/'],
    'robots_disallow' => ['/generate', '/auth/', '/api/'],
    'defaults'    => Settings::DEFAULTS,
    'secret_keys' => ['google_client_secret'],
    'admin_menu'  => [
        'designs' => ['/admin/designs', 'Designs', 'palette'],
        'signin'  => ['/admin/signin', 'Sign-in (Google)', 'key'],
        'users'   => ['/admin/users', 'Users', 'users'],
        'fonts'   => ['/admin/fonts', 'Custom fonts', 'font'],
        'ads'     => ['/admin/ads', 'Ads & ads.txt', 'ad'],
    ],
    'dashboard'   => [Web::class, 'dashboard'],
    'checks'      => [[Web::class, 'checks']],
    'pages'       => App\Content::pages(),
    'page_extra'  => App\Content::extra(),
    'consent_banner' => 'We use a few essential cookies to keep you signed in and, only if you agree, Google AdSense cookies to show ads that help keep this free.',
    'csp_extra'     => [Web::class, 'cspExtra'],
    'header_extra'  => [Web::class, 'headerExtra'],
    'before_footer' => [Web::class, 'beforeFooter'],
    'foot_extra'    => [Web::class, 'footExtra'],
]);

if (Tzl\Core::run([Web::class, 'routes']) === false) {
    return false;   // php -S: serve the static file
}
