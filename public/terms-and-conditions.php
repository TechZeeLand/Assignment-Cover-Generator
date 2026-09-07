<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'Terms & Conditions — Assignment Cover Generator';
$pageDescription = 'Terms of use for Assignment Cover Generator, a free and open-source assignment cover page tool.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="content-wrap">
    <h1>Terms &amp; Conditions</h1>
    <p class="content-lede">Last updated: <?php echo date('F Y'); ?></p>

    <div class="content-card">
        <h2>Using this app</h2>
        <p>Assignment Cover Generator is provided free of charge for generating assignment cover pages. You're welcome to use it for personal, academic, or institutional purposes.</p>

        <h2>Open-source license</h2>
        <p>The source code is released under the <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener">GNU Affero General Public License v3.0 (AGPL-3.0)</a>. You may run, study, modify, and redistribute it under the terms of that license, including for self-hosted deployments.</p>

        <h2>No warranty</h2>
        <p>This software is provided "as is", without warranty of any kind, express or implied. The generated output is a formatting convenience — you're responsible for checking that your cover page meets your institution's specific requirements before submitting it.</p>

        <h2>Font uploads</h2>
        <p>Font uploads are shared with every visitor of the instance they're uploaded to. By uploading a font, you confirm that you have the right to share it (e.g. it's your own work, or licensed for redistribution, such as under an open font license). Don't upload fonts you don't have permission to share, or any file that isn't actually a font. Uploaded fonts may be removed by the instance operator at any time.</p>

        <h2>Acceptable use</h2>
        <p>Don't use this app to generate misleading documents, impersonate an institution or person you're not authorized to represent, or upload files that aren't legitimate font files. Operators of self-hosted instances may apply their own additional rules.</p>

        <h2>Changes</h2>
        <p>These terms may be updated from time to time as the project evolves. Continued use of the app after changes constitutes acceptance of the updated terms.</p>

        <h2>Questions</h2>
        <p>Reach out via the <a href="contact.php">contact page</a> with any questions.</p>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
