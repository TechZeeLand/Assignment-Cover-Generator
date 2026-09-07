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

    <div class="content-card">
        <h2>Short version</h2>
        <p>This app is intentionally stateless. Nothing you type into the cover form is saved anywhere — not on the server, not in a database. The only things this app stores are fonts that people choose to upload for everyone to use, and a theme preference saved in your own browser.</p>

        <h2>What is not stored</h2>
        <ul>
            <li>Your university, department, student, or course details are used only to render the live preview in your browser and to generate your PDF. They are never written to disk or logged.</li>
            <li>Generated PDFs are streamed directly to your browser and are not retained on the server afterward.</li>
            <li>No accounts, cookies for tracking, or analytics scripts are used by this app itself.</li>
        </ul>

        <h2>What is stored</h2>
        <ul>
            <li><strong>Uploaded fonts.</strong> If you or another visitor uploads a custom font, the font files and the family name you provide are saved on the server and made available to everyone using this instance, since fonts are shared app-wide by design. Don't upload fonts you don't have the right to share.</li>
            <li><strong>Theme preference.</strong> Whether you're using light or dark mode is saved in your browser's local storage so it's remembered on your next visit. This never leaves your device.</li>
        </ul>

        <h2>Self-hosted instances</h2>
        <p>Assignment Cover Generator is open-source software that anyone can run on their own server. This policy describes the behavior of the application itself; if you're using an instance run by someone else, that operator's server logs, hosting provider, and network setup are outside this app's control.</p>

        <h2>Third-party resources</h2>
        <p>This page loads icon fonts from Font Awesome's CDN. Loading any external resource means your browser makes a request to that third party's servers, subject to their own privacy practices.</p>

        <h2>Questions</h2>
        <p>If you have any questions about this policy, reach out via the <a href="contact.php">contact page</a>.</p>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
