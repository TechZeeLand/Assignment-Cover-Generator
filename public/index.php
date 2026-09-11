<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\FontManager;

$fontManager = new FontManager();
$initialFonts = $fontManager->listFonts();

$pageTitle = 'Assignment Cover Generator';
$pageDescription = 'Free, open-source assignment cover page generator. Design your cover, then export a print-ready PDF.';
require __DIR__ . '/partials/head.php';
?>
<body>
<a class="skip-link" href="#main-form">Skip to form</a>

<?php require __DIR__ . '/partials/topbar.php'; ?>

<main class="layout">

    <div class="page-intro">
        <h1 class="page-title">Create Your Assignment Cover</h1>
        <p class="page-sub">Fill in the sections below, tick the fields you want to show, then generate a print-ready PDF. Everything has a sensible default, so you only need to change what matters to you.</p>
    </div>

    <nav class="section-nav" aria-label="Jump to section">
        <a href="#sec-design" class="section-nav-link"><i class="fa-solid fa-palette"></i><span>Design</span></a>
        <a href="#sec-header" class="section-nav-link"><i class="fa-solid fa-heading"></i><span>Header</span></a>
        <a href="#sec-student" class="section-nav-link"><i class="fa-solid fa-user-graduate"></i><span>Student</span></a>
        <a href="#sec-course" class="section-nav-link"><i class="fa-brands fa-readme"></i><span>Course</span></a>
        <a href="#sec-topic" class="section-nav-link"><i class="fa-solid fa-square-pen"></i><span>Topic</span></a>
    </nav>

    <form id="main-form" class="panel form-panel" action="generate.php" method="post" target="_blank" novalidate>

        <!-- ============ DESIGN ============ -->
        <section class="card" id="sec-design">
            <div class="card-head">
                <h2><span class="step-badge">1</span><i class="fa-solid fa-palette"></i> Design</h2>
                <p class="card-sub">The border, colors and fonts used across your cover page.</p>
            </div>

            <div class="field-group">
                <label class="row-check">
                    <input type="checkbox" name="border" id="border" checked>
                    <span class="switch" aria-hidden="true"></span>
                    <span class="row-check-label">Show decorative border</span>
                </label>
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
                <summary><i class="fa-solid fa-font"></i> Choose fonts <span class="font-hint">optional &mdash; good defaults are pre-selected</span><i class="fa-solid fa-caret-down"></i></summary>
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

            <details class="font-block">
                <summary><i class="fa-solid fa-circle-plus"></i> Add a custom font <span class="font-hint">shared with everyone</span><i class="fa-solid fa-caret-down"></i></summary>
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
                    <button type="button" id="upload-font-btn" class="btn btn-secondary">Upload font</button>
                    <p id="upload-font-msg" class="upload-msg" role="status"></p>
                </div>
            </details>
        </section>

        <!-- ============ HEADER ============ -->
        <section class="card" id="sec-header">
            <div class="card-head">
                <h2><span class="step-badge">2</span><i class="fa-solid fa-heading"></i> Header</h2>
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
                <div class="field">
                    <label for="header-suffix">Header suffix <small>(after "Student/Course Details")</small></label>
                    <input type="text" class="text-input" name="header-suffix" id="header-suffix" placeholder="---" value="---" maxlength="10">
                </div>
            </div>

            <details class="font-block">
                <summary><i class="fa-solid fa-text-height"></i> Font sizes <span class="font-hint">optional</span><i class="fa-solid fa-caret-down"></i></summary>
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
                </div>
            </details>
        </section>

        <!-- ============ STUDENT ============ -->
        <section class="card" id="sec-student">
            <div class="card-head">
                <h2><span class="step-badge">3</span><i class="fa-solid fa-user-graduate"></i> Student Details</h2>
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

            <details class="font-block">
                <summary><i class="fa-solid fa-text-height"></i> Font size <span class="font-hint">optional</span><i class="fa-solid fa-caret-down"></i></summary>
                <div class="font-body">
                    <div class="field field-narrow">
                        <label for="student-font-size">Font size for this section <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="student-font-size" id="student-font-size" value="28" min="8" max="60" step="1">
                    </div>
                </div>
            </details>
        </section>

        <!-- ============ COURSE ============ -->
        <section class="card" id="sec-course">
            <div class="card-head">
                <h2><span class="step-badge">4</span><i class="fa-brands fa-readme"></i> Course Details</h2>
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
                <summary><i class="fa-solid fa-text-height"></i> Font size <span class="font-hint">optional</span><i class="fa-solid fa-caret-down"></i></summary>
                <div class="font-body">
                    <div class="field field-narrow">
                        <label for="course-font-size">Font size for this section <small>(pt)</small></label>
                        <input type="number" class="text-input input-narrow" name="course-font-size" id="course-font-size" value="28" min="8" max="60" step="1">
                    </div>
                </div>
            </details>
        </section>

        <!-- ============ TOPIC ============ -->
        <section class="card" id="sec-topic">
            <div class="card-head">
                <h2><span class="step-badge">5</span><i class="fa-solid fa-square-pen"></i> Topic &amp; Submission</h2>
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
                <summary><i class="fa-solid fa-text-height"></i> Font sizes <span class="font-hint">optional</span><i class="fa-solid fa-caret-down"></i></summary>
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
            <button type="reset" class="btn btn-secondary btn-reset"><i class="fa-solid fa-arrow-rotate-left"></i> <span>Reset</span></button>
            <button type="submit" class="btn btn-primary btn-generate"><i class="fa-solid fa-hammer"></i> Generate PDF</button>
        </div>
    </form>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>window.__INITIAL_FONTS__ = <?php echo json_encode($initialFonts, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/js/app.js"></script>
</body>
</html>
