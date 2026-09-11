<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

http_response_code(500);

$pageTitle = 'Something went wrong — Assignment Cover Generator';
$pageDescription = 'The server hit a problem loading this page. Please try again in a moment.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="error-page">
    <div class="error-icon" aria-hidden="true"><i class="fa-solid fa-server"></i></div>
    <p class="error-code">Error 500</p>
    <h1 class="error-title">Something went wrong on our end</h1>
    <p class="error-desc">The server hit a snag loading this page. This is usually temporary — please try again in a moment.</p>

    <div class="error-actions">
        <button type="button" class="btn btn-primary" onclick="location.reload()"><i class="fa-solid fa-rotate-right"></i> Try again</button>
        <a href="/index.php" class="btn btn-secondary"><i class="fa-solid fa-house"></i> Go to homepage</a>
    </div>

    <p class="error-links">
        Still stuck? <a href="https://github.com/TechZeeLand/Assignment-Cover-Generator/issues" target="_blank" rel="noopener">Report it on GitHub</a> or <a href="/contact.php">get in touch</a>.
    </p>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
