<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

http_response_code(404);

$pageTitle = 'Page not found — Assignment Cover Generator';
$pageDescription = 'The page you were looking for doesn’t exist. Head back to the Assignment Cover Generator homepage.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="error-page">
    <div class="error-icon" aria-hidden="true"><i class="fa-solid fa-map-signs"></i></div>
    <p class="error-code">Error 404</p>
    <h1 class="error-title">This page doesn't exist</h1>
    <p class="error-desc">The link might be broken, or the page may have been moved or removed. Let's get you back on track.</p>

    <div class="error-actions">
        <a href="/index.php" class="btn btn-primary"><i class="fa-solid fa-house"></i> Go to homepage</a>
        <a href="/contact.php" class="btn btn-secondary"><i class="fa-solid fa-envelope"></i> Contact us</a>
    </div>

    <p class="error-links">
        Or try: <a href="/about.php">About</a> &middot; <a href="/privacy-policy.php">Privacy Policy</a> &middot; <a href="/terms-and-conditions.php">Terms &amp; Conditions</a>
    </p>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
