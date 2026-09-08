<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$pageTitle = 'About — Assignment Cover Generator';
$pageDescription = 'Learn about Assignment Cover Generator, a free, self-hosted, open-source tool for building print-ready assignment cover pages.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="content-wrap">
    <h1>About Assignment Cover Generator</h1>
    <p class="content-lede">A small, focused tool that does one job well: turning a form into a clean, print-ready assignment cover page.</p>

    <div class="content-card">
        <h2>What it is</h2>
        <p>Assignment Cover Generator is a free web app for students and teachers who need a properly formatted cover page for an assignment, lab report, or project submission. Fill in your university, department, student, and course details, and export a print-ready PDF in one click.</p>

        <h2>Why it exists</h2>
        <p>Most cover pages get built from scratch in a word processor every single time, with alignment and formatting redone by hand. This tool keeps the layout consistent and correct, so all that's left to do is fill in the details.</p>

        <h2>Open source &amp; self-hosted</h2>
        <p>The project is fully open source under the AGPL-3.0 license, and is built to be self-hosted: run your own copy on your own server with Docker, with nothing tracked or stored beyond the fonts people choose to share with everyone else using that instance. You can find the source code, report issues, or contribute on <a href="https://github.com/TechZeeLand/Assignment-Cover-Generator" target="_blank" rel="noopener">GitHub</a>.</p>

        <h2>Made in Bangladesh</h2>
        <p>Assignment Cover Generator is built and maintained by <a href="https://github.com/TechZeeLand" target="_blank" rel="noopener">TechZeeLand</a>, based in Bangladesh.</p>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
