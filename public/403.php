<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

http_response_code(403);

$pageTitle = 'Access denied — Assignment Cover Generator';
$pageDescription = 'You don’t have permission to access this page.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="error-page">
    <div class="error-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></div>
    <p class="error-code">Error 403</p>
    <h1 class="error-title">Access denied</h1>
    <p class="error-desc">You don't have permission to view this page. If you think this is a mistake, let us know.</p>

    <div class="error-actions">
        <a href="/index.php" class="btn btn-primary"><i class="fa-solid fa-house"></i> Go to homepage</a>
        <a href="/contact.php" class="btn btn-secondary"><i class="fa-solid fa-envelope"></i> Contact us</a>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
