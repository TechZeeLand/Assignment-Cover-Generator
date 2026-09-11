<?php
declare(strict_types=1);
/**
 * Shared <head>. Expects $pageTitle and $pageDescription to be set by the
 * including page before this file is required.
 */
$pageTitle = $pageTitle ?? 'Assignment Cover Generator';
$pageDescription = $pageDescription ?? 'Free, open-source assignment cover page generator. Design your cover, then export a print-ready PDF.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?></title>
<meta name="description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>" />

<!-- Open Graph -->
<meta property="og:type" content="website" />
<meta property="og:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>" />
<meta property="og:image" content="/assets/img/cover.png" />

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle, ENT_QUOTES); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription, ENT_QUOTES); ?>">
<meta name="twitter:image" content="/assets/img/cover.png">

<!-- Favicons -->
<link rel="icon" type="image/svg+xml" href="/assets/img/logo.svg" />
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png" />
<link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png" />
<link rel="shortcut icon" href="/assets/favicon/favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/apple-touch-icon.png" />
<link rel="manifest" href="/assets/favicon/site.webmanifest" />
<meta name="theme-color" content="#0b3d91" media="(prefers-color-scheme: light)" />
<meta name="theme-color" content="#0f1420" media="(prefers-color-scheme: dark)" />

<link rel="stylesheet" href="/assets/css/style.css" />
<script src="https://kit.fontawesome.com/43a3c20016.js" crossorigin="anonymous"></script>

<!-- Blocking (render-before-paint) theme script: applies the saved/system
     theme to <html> before first paint so there is no flash of the wrong
     theme. Deliberately tiny and dependency-free. -->
<script>
(function () {
    try {
        var saved = localStorage.getItem('acg-theme');
        var theme = saved === 'light' || saved === 'dark'
            ? saved
            : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        document.documentElement.setAttribute('data-theme', theme);
    } catch (e) {
        document.documentElement.setAttribute('data-theme', 'light');
    }
})();
</script>
</head>
