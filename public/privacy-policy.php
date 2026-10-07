<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'Privacy Policy — Assignment Cover Generator';
$pageDescription = 'How Assignment Cover Generator handles data: what is stored, what is not, and how cookies and local storage are used.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="content-wrap">
    <h1>Privacy Policy</h1>
    <p class="content-lede">Last updated: <?php echo date('F Y'); ?></p>

    <?php
    $adsOn = \App\Ads::enabled();
    $signIn = \App\Auth::signInAvailable();
    ?>
    <div class="content-card">
        <h2>Short version</h2>
        <p>You can use the generator without an account, and nothing you type is stored unless you choose to <strong>sign in with Google and save your details</strong>. If you do, we keep your name, your email address and the details you saved &mdash; and nothing else from your Google account. You can delete all of it yourself at any time.</p>

        <h2>If you don't sign in</h2>
        <ul>
            <li>Your university, department, student, course and logo details are used only to generate your PDF. They are not written to disk, saved, or logged, and the PDF is streamed straight to your browser.</li>
            <li>We keep anonymous daily totals (for example, "42 PDFs were generated today, 5 with a given design"). They contain no IP addresses, names or other identifiers.</li>
            <li>No cookies are set by this site, and no analytics or tracking scripts of our own are used.</li>
        </ul>

        <?php if ($signIn): ?>
        <h2>If you sign in with Google (optional)</h2>
        <p>Signing in uses Google's OAuth. We ask Google only for your <strong>name</strong> and <strong>email address</strong> (the standard <code>openid</code>, <code>email</code> and <code>profile</code> scopes). We do not store your Google account ID, profile picture, contacts or any other Google data, and we never see your Google password.</p>
        <ul>
            <li><strong>What we store:</strong> your name, your email address, when you first and last signed in, how many times you signed in, and a session cookie that keeps you signed in (see the <a href="/cookie-policy.php">Cookie Policy</a>).</li>
            <li><strong>Saved details (only if you press "Save my details"):</strong> the university, department, faculty, student, course, group and appearance settings from the form, and your logo if you added one. The topic and submission date are never saved.</li>
            <li><strong>Why:</strong> to recognise you when you return and pre-fill the form. We do not sell your data, send marketing emails, or use it for advertising.</li>
            <li><strong>Your controls:</strong> use <em>Delete saved</em> on the form to remove your saved details, or <em>Delete my account &amp; data</em> in the account menu to remove everything we hold about you immediately. You can also ask us to do it via the <a href="/contact.php">contact page</a>.</li>
        </ul>
        <?php endif; ?>

        <h2>Fonts uploaded by visitors</h2>
        <p>If a visitor uploads a custom font, the font files and family name are saved on the server and shared with everyone using this instance, by design. Don't upload fonts you don't have the right to share. The site operator can remove any font.</p>

        <?php if ($adsOn): ?>
        <h2>Advertising</h2>
        <p>This site shows ads from Google AdSense. Google and its partners may use cookies and similar technologies to serve and measure ads. <?php echo \App\Ads::requiresConsent() && \App\Settings::bool('cookie_banner_enabled') ? 'The AdSense code is loaded only after you accept cookies in the cookie banner.' : ''; ?> Learn more in the <a href="/cookie-policy.php">Cookie Policy</a> and in <a href="https://policies.google.com/technologies/partner-sites" target="_blank" rel="noopener">Google's explanation of how it uses data</a>. We never share your name, email or saved details with advertisers.</p>
        <?php endif; ?>

        <h2>Where data lives &amp; how long</h2>
        <p>Data is stored on the server that runs this site, in a small database that is not publicly accessible. Account data is kept until you delete it (or ask us to). Session cookies expire after 30 days or when you sign out. Server access logs kept by the hosting provider are outside this app's control.</p>

        <h2>Self-hosted instances</h2>
        <p>Assignment Cover Generator is open-source software that anyone can run on their own server. This policy describes the behaviour of the application itself; if you're using an instance run by someone else, that operator's hosting, logs and settings are their responsibility.</p>

        <h2>Third-party resources</h2>
        <p>Pages load icon fonts from Font Awesome's CDN<?php echo $signIn ? ", and signing in sends you to Google" : ''; ?><?php echo $adsOn ? ', and (with your consent) ads from Google' : ''; ?>. Loading any external resource means your browser makes a request to that provider, subject to their own privacy practices.</p>

        <h2>Your rights</h2>
        <p>Depending on where you live (for example under the GDPR) you may have the right to access, correct, export or delete your personal data and to withdraw consent. Most of this is available directly in the app as described above; for anything else, contact us.</p>

        <h2>Changes &amp; questions</h2>
        <p>We'll update this page when the app's data handling changes. If you have any questions, reach out via the <a href="/contact.php">contact page</a>.</p>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
