<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

$contactEmail = App\Settings::get('contact_email') ?: 'azlanaziz@rayaz.org';
$pageTitle = 'Contact — Assignment Cover Generator';
$pageDescription = 'Get in touch with TechZeeLand, the team behind Assignment Cover Generator.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-content">Skip to content</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main id="main-content" class="content-wrap">
    <h1>Contact</h1>
    <p class="content-lede">Questions, bug reports, or feature ideas — here's where to find us.</p>

    <div class="content-card">
        <p>For bugs or feature requests, opening an issue on GitHub is the fastest way to reach us. For anything else, reach out by email or on social media below.</p>

        <ul class="contact-list">
            <li><a href="mailto:<?php echo App\Html::e($contactEmail); ?>"><?php echo \App\Icon::svg('mail'); ?> <?php echo App\Html::e($contactEmail); ?></a></li>
            <li><a href="https://github.com/TechZeeLand/Assignment-Cover-Generator" target="_blank" rel="noopener"><?php echo \App\Icon::svg('github'); ?> Open an issue on GitHub</a></li>
            <li><a href="https://www.youtube.com/@TechZeeLand" target="_blank" rel="noopener"><?php echo \App\Icon::svg('youtube'); ?> YouTube — @TechZeeLand</a></li>
            <li><a href="https://www.facebook.com/TechZeeLand" target="_blank" rel="noopener"><?php echo \App\Icon::svg('facebook'); ?> Facebook — TechZeeLand</a></li>
            <li><a href="https://www.instagram.com/TechZeeLand" target="_blank" rel="noopener"><?php echo \App\Icon::svg('instagram'); ?> Instagram — @TechZeeLand</a></li>
            <li><a href="https://www.tiktok.com/@TechZeeLand" target="_blank" rel="noopener"><?php echo \App\Icon::svg('tiktok'); ?> TikTok — @TechZeeLand</a></li>
        </ul>
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
</body>
</html>
