<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Tzl/Kit.php';

use App\Settings;
use App\Web;
use Tzl\Kit;

Kit::boot([
    'root'        => dirname(__DIR__),
    'service'     => 'cover',
    'version'     => '2.0.0',
    'name'        => 'Assignment Cover Generator',
    'short'       => 'Cover Generator',
    'tagline'     => 'Print-ready assignment cover pages as PDF.',
    'description' => 'Free, open-source assignment cover page generator. Design your cover, then export a print-ready PDF.',
    'github'      => 'https://github.com/TechZeeLand/Assignment-Cover-Generator',
    'css'         => ['assets/css/acg-global.css'],
    'js'          => ['assets/js/acg-data.js', 'assets/js/consent.js'],
    'nav'         => [['Create cover', '/', 'file-pdf'], ['About', '/about', 'info']],
    'sitemap'     => ['/'],
    'robots_disallow' => ['/generate', '/auth/', '/api/'],
    'defaults'    => Settings::DEFAULTS,
    'secret_keys' => ['google_client_secret'],
    'admin_menu'  => [
        'designs' => ['/admin/designs', 'Designs', 'palette'],
        'signin'  => ['/admin/signin', 'Sign-in (Google)', 'key'],
        'users'   => ['/admin/users', 'Users', 'users'],
        'fonts'   => ['/admin/fonts', 'Custom fonts', 'font'],
        'ads'     => ['/admin/ads', 'Ads & ads.txt', 'ad'],
    ],
    'dashboard'   => [Web::class, 'dashboard'],
    'checks'      => [[Web::class, 'checks']],
    'pages'       => App\Content::pages(),
    'page_extra'  => App\Content::extra(),
    'consent_banner' => 'We use a few essential cookies to keep you signed in and, only if you agree, Google AdSense cookies to show ads that help keep this free.',
    'csp_extra'     => [Web::class, 'cspExtra'],
    'header_extra'  => [Web::class, 'headerExtra'],
    'before_footer' => [Web::class, 'beforeFooter'],
    'foot_extra'    => [Web::class, 'footExtra'],
]);

if (Tzl\Core::run([Web::class, 'routes']) === false) {
    return false;   // php -S: serve the static file
}
<<<<<<< ours
$canSignIn = Auth::signInAvailable() && $profilesOn;

// Favourite designs (signed-in users): shown first, with a filled heart.
$favoritesOn = $acgUser !== null && $profilesOn;
$designOrder = array_flip(array_keys($designs));   // original order, so an un-favorited card can go back
$favorites = [];
if ($favoritesOn && is_array($savedProfile) && !empty($savedProfile['favorite-designs'])) {
    foreach (explode(',', (string) $savedProfile['favorite-designs']) as $favKey) {
        if (isset($designs[$favKey])) {
            $favorites[] = $favKey;
        }
    }
    $pinned = [];
    foreach ($favorites as $favKey) {
        $pinned[$favKey] = $designs[$favKey];
    }
    $designs = $pinned + array_diff_key($designs, $pinned);
}

$previewOn = \App\PreviewService::available();

$pageTitle = 'Assignment Cover Generator';
$pageDescription = 'Free, open-source assignment cover page generator. Design your cover, then export a print-ready PDF.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-form">Skip to form</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main class="layout<?php echo $previewOn ? ' layout-wide' : ''; ?>">
<div class="workspace<?php echo $previewOn ? ' has-preview' : ''; ?>">
<div class="workspace-main">

    <div class="page-intro">
        <h1 class="page-title">Create Your Assignment Cover</h1>
        <p class="page-sub">Fill in the sections below, tick the fields you want to show, then generate a print-ready PDF. Everything has a sensible default, so you only need to change what matters to you.</p>
    </div>

    <nav class="section-nav" aria-label="Jump to section">
        <a href="#sec-design" class="section-nav-link"><?php echo \App\Icon::svg('palette'); ?><span>Design</span></a>
        <a href="#sec-header" class="section-nav-link"><?php echo \App\Icon::svg('heading'); ?><span>Header</span></a>
        <a href="#sec-student" class="section-nav-link"><?php echo \App\Icon::svg('graduation-cap'); ?><span>Student</span></a>
        <a href="#sec-course" class="section-nav-link"><?php echo \App\Icon::svg('book-open'); ?><span>Course</span></a>
        <a href="#sec-group" class="section-nav-link"><?php echo \App\Icon::svg('users-group'); ?><span>Group</span></a>
        <a href="#sec-topic" class="section-nav-link"><?php echo \App\Icon::svg('square-pen'); ?><span>Topic</span></a>
    </nav>

    <?php echo Ads::slot('top'); ?>

    <?php if ($acgUser !== null && $profilesOn): ?>
    <section class="panel profile-bar" id="profile-bar" aria-label="Saved details">
        <div class="profile-bar-text">
            <strong><?php echo \App\Icon::svg('save'); ?> Your saved details</strong>
            <span id="profile-note"><?php echo $savedProfile ? 'Loaded automatically below.' : 'Nothing saved yet &mdash; fill in the form, then save it for next time.'; ?></span>
        </div>
        <div class="profile-bar-actions">
            <button type="button" class="btn btn-secondary" id="profile-save"><?php echo \App\Icon::svg('save'); ?> Save my details</button>
            <label class="row-check profile-auto" title="Saves your form automatically a moment after every change">
                <input type="checkbox" name="auto-save" id="auto-save" form="main-form">
                <span class="switch" aria-hidden="true"></span>
                <span class="row-check-label">Auto-save</span>
            </label>
            <button type="button" class="btn btn-secondary" id="profile-clear"<?php echo $savedProfile ? '' : ' disabled'; ?>><?php echo \App\Icon::svg('trash'); ?> Delete saved</button>
        </div>
        <p class="profile-status" id="profile-status" role="status"></p>
    </section>
    <?php elseif ($canSignIn): ?>
    <section class="panel profile-bar profile-bar-promo" aria-label="Sign in to save your details">
        <div class="profile-bar-text">
            <strong><?php echo \App\Icon::svg('save'); ?> Tired of retyping the same details?</strong>
            <span>Optionally sign in (email &amp; password<?php echo Auth::googleAvailable() ? ' or Google' : ''; ?>) and save everything &mdash; details, favorite designs and preferences &mdash; for next time. Signing in is never required.</span>
        </div>
        <div class="profile-bar-actions">
            <?php if (Auth::emailAvailable()): ?>
            <a class="btn btn-primary" href="/auth/signin.php?return=<?php echo rawurlencode('/'); ?>"><?php echo \App\Icon::svg('user'); ?> Sign in</a>
            <a class="btn btn-secondary" href="/auth/register.php?return=<?php echo rawurlencode('/'); ?>">Create account</a>
            <?php endif; ?>
            <?php if (Auth::googleAvailable()): ?>
            <a class="google-btn google-btn-lg" href="/auth/google.php?return=<?php echo rawurlencode('/'); ?>">
                <?php echo \App\Icon::svg('google'); ?>
                <span>Sign in with Google</span>
            </a>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <form id="main-form" class="panel form-panel" action="generate.php" method="post" target="_blank" novalidate>

        <!-- ============ DESIGN ============ -->
        <section class="card" id="sec-design">
            <div class="card-head">
                <h2><span class="step-badge">1</span><?php echo \App\Icon::svg('palette'); ?> Design</h2>
                <p class="card-sub">Pick a layout, then set the border, colors and fonts used across your cover page.</p>
            </div>

            <div class="field-group">
                <div class="field">
                    <span class="field-label" id="design-label">Cover design</span>
                    <div class="design-picker" id="design-picker">
                        <div class="design-toolbar">
                            <span class="design-count"><?php echo count($designs); ?> designs &middot; swipe or scroll sideways<?php echo $favoritesOn ? ' &middot; tap the heart to pin favorites first' : ''; ?></span>
                            <button type="button" class="chip-btn" id="design-expand" aria-expanded="false" aria-controls="design-grid">
                                <span class="ico-expand"><?php echo \App\Icon::svg('grid'); ?></span><span class="ico-collapse"><?php echo \App\Icon::svg('minimize'); ?></span> <span class="expand-label">Expand all</span>
                            </button>
                        </div>
                        <div class="design-scroll-wrap">
                            <button type="button" class="design-arrow design-arrow-prev" id="design-prev" aria-label="Show previous designs" hidden><?php echo \App\Icon::svg('chevron-left'); ?></button>
                            <div class="design-grid" id="design-grid" role="radiogroup" aria-labelledby="design-label">
                                <?php foreach ($designs as $design): ?>
                                <?php $isFav = in_array($design->key(), $favorites, true); ?>
                                <label class="design-card<?php echo $isFav ? ' is-fav' : ''; ?>" data-design="<?php echo Html::e($design->key()); ?>" data-order="<?php echo (int) ($designOrder[$design->key()] ?? 0); ?>">
                                    <input type="radio" name="design" value="<?php echo Html::e($design->key()); ?>"<?php echo $design->key() === $defaultDesign ? ' checked' : ''; ?>>
                                    <?php if ($favoritesOn): ?>
                                    <button type="button" class="fav-btn" data-design="<?php echo Html::e($design->key()); ?>" data-label="<?php echo Html::e($design->label()); ?>" aria-pressed="<?php echo $isFav ? 'true' : 'false'; ?>" aria-label="<?php echo $isFav ? 'Remove' : 'Add'; ?> <?php echo Html::e($design->label()); ?> <?php echo $isFav ? 'from' : 'to'; ?> favorites" title="<?php echo $isFav ? 'Remove from favorites' : 'Add to favorites'; ?>"><span class="fav-off"><?php echo \App\Icon::svg('heart'); ?></span><span class="fav-on"><?php echo \App\Icon::svg('heart-filled'); ?></span></button>
                                    <?php endif; ?>
                                    <span class="design-thumb"><?php echo $design->thumbnailSvg(); ?></span>
                                    <span class="design-name"><?php echo Html::e($design->label()); ?></span>
                                    <span class="design-badge"><?php echo Html::e($design->category()); ?></span>
                                    <span class="design-desc"><?php echo Html::e($design->description()); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="design-arrow design-arrow-next" id="design-next" aria-label="Show more designs" hidden><?php echo \App\Icon::svg('chevron-right'); ?></button>
                        </div>
                    </div>
                    <p class="hint">The previews follow your colors below. Every design uses the same details you fill in.</p>
                </div>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="border" id="border" checked>
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Show decorative border</span>
                </label>
                <p class="hint">Frame, bands, panel or rules &mdash; whichever the chosen design uses.</p>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="icons" id="icons">
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Icons</span>
                </label>
                <p class="hint">Adds small icons before the section headings &mdash; a student, teacher, book, group, topic and clock &mdash; in your primary color.</p>
            </div>

            <div class="field-group">
                <div class="field-row">
                    <div class="field">
                        <label for="primary-color">Primary color</label>
                        <div class="color-field">
                            <input type="color" name="primary-color" id="primary-color" value="#0384fc">
                            <span class="color-hex" data-for="primary-color">#0384FC</span>
                        </div>
                    </div>
                    <div class="field">
                        <label for="secondary-color">Secondary color</label>
                        <div class="color-field">
                            <input type="color" name="secondary-color" id="secondary-color" value="#fc9803">
                            <span class="color-hex" data-for="secondary-color">#FC9803</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="use-title-border-color" id="use-title-border-color" checked>
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Use a different color for the title &amp; thick border</span>
                </label>
                <div class="nested-field">
                    <div class="field">
                        <label for="title-border-color">Title / border color</label>
                        <div class="color-field">
                            <input type="color" name="title-border-color" id="title-border-color" value="#000000" disabled>
                            <span class="color-hex" data-for="title-border-color">#000000</span>
                        </div>
                        <p class="hint">Turn this off to reuse the primary color above instead.</p>
                    </div>
                </div>
            </div>

            <details class="font-block">
                <summary><?php echo \App\Icon::svg('font'); ?> Choose fonts <span class="font-hint">optional &mdash; good defaults are pre-selected</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body">
                    <div class="field">
                        <label for="versity-name-font">University name font</label>
                        <select name="versity-name-font" id="versity-name-font" class="font-select"></select>
                    </div>
                    <div class="field">
                        <label for="primary-font">Primary font <small>(section headers)</small></label>
                        <select name="primary-font" id="primary-font" class="font-select"></select>
                    </div>
                    <div class="field">
                        <label for="secondary-font">Secondary font <small>(names, values, body text)</small></label>
                        <select name="secondary-font" id="secondary-font" class="font-select"></select>
                    </div>
                </div>
            </details>

            <?php if (Settings::bool('font_uploads_enabled')): ?>
            <details class="font-block">
                <summary><?php echo \App\Icon::svg('plus-circle'); ?> Add a custom font <span class="font-hint">shared with everyone</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body">
                    <p class="hint">Upload TTF/OTF font files (max 2 MB each). Only "Regular" is required; add Bold/Italic for better results with bold/italic text.</p>
                    <div class="field">
                        <label for="font-name">Font name</label>
                        <input type="text" id="font-name" placeholder="e.g. Open Sans" maxlength="60">
                    </div>
                    <div class="field-row">
                        <div class="field"><label for="font-regular">Regular *</label><input type="file" id="font-regular" accept=".ttf,.otf"></div>
                        <div class="field"><label for="font-bold">Bold</label><input type="file" id="font-bold" accept=".ttf,.otf"></div>
                    </div>
                    <div class="field-row">
                        <div class="field"><label for="font-italic">Italic</label><input type="file" id="font-italic" accept=".ttf,.otf"></div>
                        <div class="field"><label for="font-bolditalic">Bold Italic</label><input type="file" id="font-bolditalic" accept=".ttf,.otf"></div>
                    </div>
                    <button type="button" id="upload-font-btn" class="btn btn-secondary"><?php echo \App\Icon::svg('upload'); ?><?php echo \App\Icon::svg('loader'); ?> <span>Upload font</span></button>
                    <p id="upload-font-msg" class="upload-msg" role="status"></p>
                </div>
            </details>
            <?php endif; ?>
        </section>

        <!-- ============ HEADER ============ -->
        <section class="card" id="sec-header">
            <div class="card-head">
                <h2><span class="step-badge">2</span><?php echo \App\Icon::svg('heading'); ?> Header</h2>
                <p class="card-sub">The Bismillah, university and department name shown at the top of the page.</p>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="bismillah" id="bismillah" checked>
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Show Bismillah (﷽)</span>
                </label>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="show-versity-name" id="show-versity-name" checked>
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">University name</span>
                </label>
                <input type="text" class="text-input" name="versity" id="versity" placeholder="Enter university name">
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="show-dept-name" id="show-dept-name" checked>
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Department name</span>
                </label>
                <input type="text" class="text-input" name="dept-name" id="dept-name" placeholder="Enter department">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-faculty" id="show-faculty"><span class="switch" aria-hidden="true"></span><span class="row-check-label">Faculty / School <small>(optional)</small></span></label>
                <input type="text" class="text-input" name="faculty" id="faculty" placeholder="e.g. Faculty of Arts and Social Sciences">
            </div>

            <div class="field-group logo-field">
                <span class="field-label">University logo <small>(optional)</small></span>
                <div class="logo-row">
                    <div class="logo-preview" id="logo-preview" aria-live="polite"><span class="logo-empty">No logo</span></div>
                    <div class="logo-controls">
                        <input type="file" id="logo-file" accept="image/png,image/jpeg,image/webp">
                        <button type="button" class="btn btn-secondary btn-sm" id="logo-remove" hidden>Remove logo</button>
                        <div class="field field-narrow">
                            <label for="logo-size">Logo height <small>(pt)</small></label>
                            <input type="number" class="text-input input-narrow" name="logo-size" id="logo-size" value="70" min="30" max="140" step="5">
                        </div>
                    </div>
                </div>
                <input type="hidden" name="logo-data" id="logo-data">
                <p class="hint">PNG, JPG or WebP. It is resized in your browser and only sent when you generate the PDF.</p>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="show-cover-title" id="show-cover-title">
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Cover title <small>(e.g. "Lab Report No. 3")</small></span>
                </label>
                <div class="field-row">
                    <div class="field">
                        <label for="assignment-type">Type</label>
                        <input type="text" class="text-input" name="assignment-type" id="assignment-type" list="assignment-types" placeholder="Assignment" maxlength="40">
                        <datalist id="assignment-types">
                            <option value="Assignment"><option value="Lab Report"><option value="Term Paper"><option value="Project Report">
                            <option value="Case Study"><option value="Presentation"><option value="Homework"><option value="Quiz">
                        </datalist>
                    </div>
                    <div class="field">
                        <label for="assignment-no">Number <small>(optional)</small></label>
                        <input type="text" class="text-input" name="assignment-no" id="assignment-no" placeholder="e.g. 2" maxlength="12">
                    </div>
                </div>
            </div>

            <div class="field-group">
                <div class="field">
                    <label for="header-suffix">Header suffix <small>(after "Student/Course Details" &mdash; clear it for none)</small></label>
                    <input type="text" class="text-input" name="header-suffix" id="header-suffix" placeholder="none" value="---" maxlength="10">
                </div>
            </div>

            <details class="font-block">
                <summary><?php echo \App\Icon::svg('text-size'); ?> Font sizes <span class="font-hint">optional</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body font-grid">
                    <div class="field">
                        <label for="bismillah-font-size">Bismillah <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="bismillah-font-size" id="bismillah-font-size" value="16" min="8" max="60" step="1">
                    </div>
                    <div class="field">
                        <label for="versity-font-size">University name <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="versity-font-size" id="versity-font-size" value="36" min="8" max="60" step="1">
                    </div>
                    <div class="field">
                        <label for="dept-font-size">Department name <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="dept-font-size" id="dept-font-size" value="22" min="8" max="60" step="1">
                    </div>
                    <div class="field">
                        <label for="faculty-font-size">Faculty / School <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="faculty-font-size" id="faculty-font-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                    <div class="field">
                        <label for="cover-title-font-size">Cover title <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="cover-title-font-size" id="cover-title-font-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                    <div class="field">
                        <label for="program-font-size">Program line <small>(pt &middot; Formal designs)</small></label>
                        <input type="number" class="text-input input-narrow" name="program-font-size" id="program-font-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                    <p class="hint font-grid-note">Leave a box empty to use the chosen design's own size.</p>
                </div>
            </details>
        </section>

        <!-- ============ STUDENT ============ -->
        <section class="card" id="sec-student">
            <div class="card-head">
                <h2><span class="step-badge">3</span><?php echo \App\Icon::svg('graduation-cap'); ?> Student Details</h2>
                <p class="card-sub">Your name, ID, and other academic details.</p>
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-name" id="show-student-name" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Name</span></label>
                <input type="text" class="text-input" name="student-name" id="student-name" placeholder="Enter student name">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-id" id="show-student-id" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">ID</span></label>
                <input type="text" class="text-input" name="student-id" id="student-id" placeholder="Enter student ID">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-section" id="show-student-section" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Section</span></label>
                <input type="text" class="text-input" name="student-section" id="student-section" placeholder="Enter section">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-batch" id="show-student-batch" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Batch</span></label>
                <input type="text" class="text-input" name="student-batch" id="student-batch" placeholder="Enter batch">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-program" id="show-student-program" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Program</span></label>
                <input type="text" class="text-input" name="student-program" id="student-program" placeholder="Enter program">
            </div>

            <div class="field-group">
                <div class="field">
                    <label for="semester-type">Semester label</label>
                    <select name="semester-type" id="semester-type" class="text-input">
                        <option value="Semester">Semester</option>
                        <option value="Trimester" selected>Trimester</option>
                    </select>
                </div>
                <label class="row-check"><input type="checkbox" name="show-semester" id="show-semester" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Semester value</span></label>
                <input type="text" class="text-input" name="semester" id="semester" placeholder="e.g. Summer">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-session" id="show-student-session"><span class="switch" aria-hidden="true"></span><span class="row-check-label">Session / academic year <small>(optional)</small></span></label>
                <input type="text" class="text-input" name="student-session" id="student-session" placeholder="e.g. 2025-2026">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-student-email" id="show-student-email"><span class="switch" aria-hidden="true"></span><span class="row-check-label">Email <small>(optional)</small></span></label>
                <input type="text" class="text-input" name="student-email" id="student-email" placeholder="e.g. you@example.com">
            </div>

            <details class="font-block">
                <summary><?php echo \App\Icon::svg('text-size'); ?> Font size <span class="font-hint">optional</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body">
                    <div class="field field-narrow">
                        <label for="student-font-size">Font size for this section <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="student-font-size" id="student-font-size" value="28" min="8" max="60" step="1">
                    </div>
                    <div class="field field-narrow">
                        <label for="student-heading-size">Heading size <small>(pt &mdash; &ldquo;Student Details&rdquo; / &ldquo;Submitted by&rdquo;; empty = design default)</small></label>
                        <input type="number" class="text-input input-narrow" name="student-heading-size" id="student-heading-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                </div>
            </details>
        </section>

        <!-- ============ COURSE ============ -->
        <section class="card" id="sec-course">
            <div class="card-head">
                <h2><span class="step-badge">4</span><?php echo \App\Icon::svg('book-open'); ?> Course Details</h2>
                <p class="card-sub">Course code, title, and instructor information.</p>
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-course-code" id="show-course-code" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Course code</span></label>
                <input type="text" class="text-input" name="course-code" id="course-code" placeholder="e.g. ENG 0232-2417">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-course-title" id="show-course-title" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Course title</span></label>
                <div class="richtext" data-target="course-title-html">
                    <div class="richtext-toolbar">
                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                    </div>
                    <div class="richtext-input" id="course-title" contenteditable="true" data-placeholder="Enter course title"></div>
                </div>
                <input type="hidden" name="course-title-html" id="course-title-html">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-course-teacher-name" id="show-course-teacher-name" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Teacher name</span></label>
                <input type="text" class="text-input" name="course-teacher-name" id="course-teacher-name" placeholder="Enter teacher name">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-course-teacher-designation" id="show-course-teacher-designation" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Teacher designation</span></label>
                <input type="text" class="text-input" name="course-teacher-designation" id="course-teacher-designation" placeholder="e.g. Assistant Professor">
            </div>

            <details class="font-block">
                <summary><?php echo \App\Icon::svg('text-size'); ?> Font size <span class="font-hint">optional</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body">
                    <div class="field field-narrow">
                        <label for="course-font-size">Font size for this section <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="course-font-size" id="course-font-size" value="28" min="8" max="60" step="1">
                    </div>
                    <div class="field field-narrow">
                        <label for="course-heading-size">Heading size <small>(pt &mdash; &ldquo;Course Details&rdquo;; empty = design default)</small></label>
                        <input type="number" class="text-input input-narrow" name="course-heading-size" id="course-heading-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                    <div class="field field-narrow">
                        <label for="teacher-heading-size">&ldquo;Submitted to&rdquo; heading size <small>(pt &mdash; Formal designs)</small></label>
                        <input type="number" class="text-input input-narrow" name="teacher-heading-size" id="teacher-heading-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                    <div class="field field-narrow">
                        <label for="designation-font-size">Teacher designation <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="designation-font-size" id="designation-font-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                </div>
            </details>
        </section>

        <!-- ============ GROUP ============ -->
        <section class="card" id="sec-group">
            <div class="card-head">
                <h2><span class="step-badge">5</span><?php echo \App\Icon::svg('users-group'); ?> Group <span class="optional-tag">optional</span></h2>
                <p class="card-sub">For group assignments: add a group name and list the members.</p>
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-group" id="show-group"><span class="switch" aria-hidden="true"></span><span class="row-check-label">Show group details</span></label>
                <input type="text" class="text-input" name="group-name" id="group-name" placeholder="Group name or number, e.g. Group 4" maxlength="60">
            </div>
            <div class="field-group">
                <div class="field">
                    <label for="group-members">Members <small>(one per line &mdash; Name, ID &mdash; up to 12)</small></label>
                    <textarea class="text-input" name="group-members" id="group-members" rows="5" maxlength="900" placeholder="Azlan Aziz, ME601001&#10;Another Member, ME601002"></textarea>
                </div>
            </div>
            <details class="font-block">
                <summary><?php echo \App\Icon::svg('text-size'); ?> Font size <span class="font-hint">optional</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body">
                    <div class="field field-narrow">
                        <label for="group-heading-size">&ldquo;Group Details&rdquo; heading size <small>(pt &mdash; empty = design default)</small></label>
                        <input type="number" class="text-input input-narrow" name="group-heading-size" id="group-heading-size" min="8" max="60" step="1" placeholder="auto">
                    </div>
                    <p class="hint">The members themselves use the Student Details font size.</p>
                </div>
            </details>
        </section>

        <?php echo Ads::slot('middle'); ?>

        <!-- ============ TOPIC ============ -->
        <section class="card" id="sec-topic">
            <div class="card-head">
                <h2><span class="step-badge">6</span><?php echo \App\Icon::svg('square-pen'); ?> Topic &amp; Submission</h2>
                <p class="card-sub">What you're submitting, and when.</p>
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-topic" id="show-topic" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Topic</span></label>
                <div class="richtext" data-target="topic-html">
                    <div class="richtext-toolbar">
                        <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                        <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                    </div>
                    <div class="richtext-input" id="topic" contenteditable="true" data-placeholder="Enter topic"></div>
                </div>
                <input type="hidden" name="topic-html" id="topic-html">
            </div>

            <div class="field-group">
                <label class="row-check"><input type="checkbox" name="show-submission-date" id="show-submission-date" checked><span class="switch" aria-hidden="true"></span><span class="row-check-label">Submission date</span></label>
                <input type="date" class="text-input" name="submission-date" id="submission-date">
            </div>

            <details class="font-block">
                <summary><?php echo \App\Icon::svg('text-size'); ?> Font sizes <span class="font-hint">optional</span><?php echo \App\Icon::svg('chevron-down', ['class' => 'caret']); ?></summary>
                <div class="font-body font-grid font-grid-2">
                    <div class="field">
                        <label for="topic-font-size">Topic <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="topic-font-size" id="topic-font-size" value="24" min="8" max="60" step="1">
                    </div>
                    <div class="field">
                        <label for="submission-font-size">Submission date <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="submission-font-size" id="submission-font-size" value="16" min="8" max="60" step="1">
                    </div>
                </div>
            </details>
        </section>

        <div class="form-actions">
            <button type="reset" class="btn btn-secondary btn-reset"><?php echo \App\Icon::svg('rotate-ccw'); ?> <span>Reset</span></button>
            <button type="submit" class="btn btn-primary btn-generate"><?php echo \App\Icon::svg('hammer'); ?> Generate PDF</button>
        </div>
    </form>
</div>

<?php if ($previewOn): ?>
<aside class="preview-pane" id="preview-pane" aria-label="Live preview">
    <div class="preview-head"><strong><?php echo \App\Icon::svg('eye'); ?> Live preview</strong><span class="preview-status" role="status" aria-live="polite"></span></div>
    <div class="preview-frame"><img class="preview-img" alt="Live preview of your cover page" hidden><div class="preview-msg">Preparing preview&hellip;</div></div>
    <p class="hint">Page 1 of your PDF, exactly as it will print. It refreshes a moment after you stop typing.</p>
</aside>
<?php endif; ?>
</div>
</main>

<?php if ($previewOn): ?>
<button type="button" class="preview-fab" id="preview-fab" aria-haspopup="dialog"><?php echo \App\Icon::svg('eye'); ?><span>Preview</span></button>
<dialog class="preview-modal" id="preview-modal" aria-label="Live preview">
    <div class="preview-modal-head">
        <strong>Live preview</strong><span class="preview-status" role="status" aria-live="polite"></span>
        <button type="button" class="chip-btn" id="preview-close"><?php echo \App\Icon::svg('close'); ?> Close</button>
    </div>
    <div class="preview-frame"><img class="preview-img" alt="Live preview of your cover page" hidden><div class="preview-msg">Preparing preview&hellip;</div></div>
    <div class="preview-modal-actions">
        <button type="submit" form="main-form" class="btn btn-primary"><?php echo \App\Icon::svg('hammer'); ?> Generate PDF</button>
    </div>
</dialog>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>window.__INITIAL_FONTS__ = <?php echo Html::json($initialFonts); ?>;
window.__SAVED_PROFILE__ = <?php echo Html::json($savedProfile); ?>;
window.__DESIGN_FAVORITES__ = <?php echo Html::json($favorites); ?>;
window.__FONT_UPLOADS__ = <?php echo Settings::bool('font_uploads_enabled') ? 'true' : 'false'; ?>;</script>
<script src="<?php echo \App\Html::asset('/assets/js/app.js'); ?>"></script>
<?php if ($previewOn): ?><script src="<?php echo \App\Html::asset('/assets/js/preview.js'); ?>"></script><?php endif; ?>
</body>
</html>
=======
>>>>>>> theirs
