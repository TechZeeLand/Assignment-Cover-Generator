<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\Ads;
use App\Auth;
use App\Settings;

$pageTitle = 'Cookie Policy — Assignment Cover Generator';
$pageDescription = 'Which cookies and browser storage Assignment Cover Generator uses, why, and how to control them.';
require __DIR__ . '/partials/head.php';

$adsOn = Ads::enabled();
$signIn = Auth::signInAvailable();
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="content-wrap">
    <h1>Cookie Policy</h1>
    <p class="content-lede">Last updated: <?php echo date('F Y'); ?></p>

    <div class="content-card">
        <h2>Short version</h2>
        <p>You can use the generator without any cookies at all. A cookie is only set if you choose to sign in with Google<?php echo $adsOn ? ', and advertising cookies are only used if you accept them' : ''; ?>. We use no analytics or tracking scripts of our own.</p>

        <h2>What we use</h2>
        <table class="policy-table">
            <thead><tr><th>Name</th><th>Type</th><th>Purpose</th><th>Lifetime</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>acg_session</code></td>
                    <td>Essential cookie</td>
                    <td>Keeps you signed in after "Sign in with Google" and protects forms against cross-site requests. It is <strong>not</strong> created unless you sign in<?php echo $signIn ? '' : ' (sign-in is currently turned off on this site)'; ?>.</td>
                    <td>Up to 30 days, or until you sign out</td>
                </tr>
                <tr>
                    <td><code>acg-theme</code></td>
                    <td>Browser storage (localStorage)</td>
                    <td>Remembers whether you chose light or dark mode. Never sent to the server.</td>
                    <td>Until you clear your browser data</td>
                </tr>
                <tr>
                    <td><code>acg-consent</code></td>
                    <td>Browser storage (localStorage)</td>
                    <td>Remembers your answer to the cookie banner. Never sent to the server.</td>
                    <td>Until you clear your browser data</td>
                </tr>
            </tbody>
        </table>

        <?php if ($adsOn): ?>
        <h2>Advertising cookies (Google AdSense)</h2>
        <p>This site shows ads served by Google AdSense. <?php echo Ads::requiresConsent() && Settings::bool('cookie_banner_enabled') ? 'The AdSense script is loaded <strong>only after you click "Accept all"</strong>. If you choose "Essential only", no ad code is loaded and no advertising cookies are set.' : 'The AdSense script is loaded when a page with an ad is opened.'; ?> Once loaded, Google and its partners may set cookies and use identifiers to serve and measure ads, and to limit how often you see the same ad. We do not control those cookies. Learn how Google uses data from sites that use its services at <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">policies.google.com/technologies/partner-sites</a>, and manage ad personalisation at <a href="https://adssettings.google.com" target="_blank" rel="noopener">adssettings.google.com</a>.</p>
        <?php endif; ?>

        <?php if ($signIn): ?>
        <h2>Google sign-in</h2>
        <p>When you click "Sign in", you are sent to Google, which may set its own cookies on its own domain under its own policy. We only receive your name and email address back &mdash; see the <a href="/privacy-policy.php">Privacy Policy</a>.</p>
        <?php endif; ?>

        <h2>Third-party resources</h2>
        <p>Pages load the Font Awesome icon kit from its CDN. Requesting it reveals your IP address and browser details to that provider, and it may use its own cookies or caching under its own policy.</p>

        <h2>Your choices</h2>
        <ul>
            <li>Change your cookie choice at any time with <button type="button" class="link-button" data-cookie-settings>Cookie settings</button>.</li>
            <li>Sign out from the account menu to remove the session cookie, or delete cookies in your browser settings.</li>
            <li>Clearing your browser's site data removes the theme and consent entries.</li>
        </ul>

        <h2>Questions</h2>
        <p>Reach out via the <a href="/contact.php">contact page</a>.</p>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
