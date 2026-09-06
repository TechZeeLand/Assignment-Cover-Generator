<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use App\FontManager;

$fontManager = new FontManager();
$initialFonts = $fontManager->listFonts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Assignment Cover Generator</title>
<meta name="description" content="Free, open-source assignment cover page generator. Design your cover, then export a print-ready PDF." />
<link rel="stylesheet" href="assets/css/style.css" />
<script src="https://kit.fontawesome.com/43a3c20016.js" crossorigin="anonymous"></script>
</head>
<body>
<a class="skip-link" href="#main-form">Skip to form</a>

<header class="topbar">
    <div class="topbar-inner">
        <h1><i class="fa-regular fa-file-lines"></i> Assignment Cover Generator</h1>
        <a class="gh-link" href="https://github.com/TechZeeLand/Assignment-Cover-Generator" target="_blank" rel="noopener" id="gh-link">
            <svg viewBox="0 0 16 16" width="20" height="20" aria-hidden="true"><path fill="currentColor" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z"/></svg>
            <span>Source on GitHub</span>
        </a>
    </div>
</header>

<main class="layout">
    <form id="main-form" class="panel form-panel" action="generate.php" method="post" target="_blank" novalidate>

        <!-- ============ DESIGN ============ -->
        <section class="card">
            <h2><i class="fa-solid fa-palette"></i> Design</h2>

            <label class="row-check">
                <input type="checkbox" name="border" id="border" checked>
                <span>Show decorative border</span>
            </label>

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

            <div class="field-row">
                <div class="field">
                    <label for="primary-color">Primary color</label>
                    <input type="color" name="primary-color" id="primary-color" value="#3ce9be">
                </div>
                <div class="field">
                    <label for="secondary-color">Secondary color</label>
                    <input type="color" name="secondary-color" id="secondary-color" value="#000000">
                </div>
            </div>

            <label class="row-check">
                <input type="checkbox" name="use-title-border-color" id="use-title-border-color">
                <span>Use a custom color for the title &amp; thick border</span>
            </label>
            <div class="field">
                <label for="title-border-color">Title / border color</label>
                <input type="color" name="title-border-color" id="title-border-color" value="#3ce9be" disabled>
                <small class="hint">Leave off to reuse the primary color above.</small>
            </div>

            <details class="font-upload">
                <summary><i class="fa-solid fa-circle-plus"></i> Add a custom font (shared with everyone)</summary>
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
            </details>
        </section>

        <!-- ============ UNIVERSITY ============ -->
        <section class="card">
            <h2><i class="fa-solid fa-heading"></i> Header</h2>

            <label class="row-check">
                <input type="checkbox" name="bismillah" id="bismillah" checked>
                <span>Show Bismillah (﷽)</span>
            </label>

            <label class="row-check">
                <input type="checkbox" name="show-versity-name" id="show-versity-name" checked>
                <span>University name</span>
            </label>
            <input type="text" class="text-input" name="versity" id="versity" placeholder="Enter university name">

            <label class="row-check">
                <input type="checkbox" name="show-dept-name" id="show-dept-name" checked>
                <span>Department name</span>
            </label>
            <input type="text" class="text-input" name="dept-name" id="dept-name" placeholder="Enter department">

            <div class="field">
                <label for="header-suffix">Header suffix <small>(after "Student/Course Details")</small></label>
                <input type="text" class="text-input" name="header-suffix" id="header-suffix" placeholder="---" value="---" maxlength="10">
            </div>
        </section>

        <!-- ============ STUDENT ============ -->
        <section class="card">
            <h2><i class="fa-solid fa-user-graduate"></i> Student Details</h2>

            <label class="row-check"><input type="checkbox" name="show-student-name" id="show-student-name" checked><span>Name</span></label>
            <input type="text" class="text-input" name="student-name" id="student-name" placeholder="Enter student name">

            <label class="row-check"><input type="checkbox" name="show-student-id" id="show-student-id" checked><span>ID</span></label>
            <input type="text" class="text-input" name="student-id" id="student-id" placeholder="Enter student ID">

            <label class="row-check"><input type="checkbox" name="show-student-section" id="show-student-section" checked><span>Section</span></label>
            <input type="text" class="text-input" name="student-section" id="student-section" placeholder="Enter section">

            <label class="row-check"><input type="checkbox" name="show-student-batch" id="show-student-batch" checked><span>Batch</span></label>
            <input type="text" class="text-input" name="student-batch" id="student-batch" placeholder="Enter batch">

            <label class="row-check"><input type="checkbox" name="show-student-program" id="show-student-program" checked><span>Program</span></label>
            <input type="text" class="text-input" name="student-program" id="student-program" placeholder="Enter program">

            <div class="field">
                <label for="semester-type">Semester label</label>
                <select name="semester-type" id="semester-type" class="text-input">
                    <option value="Semester">Semester</option>
                    <option value="Trimester">Trimester</option>
                </select>
            </div>
            <label class="row-check"><input type="checkbox" name="show-semester" id="show-semester" checked><span>Semester value</span></label>
            <input type="text" class="text-input" name="semester" id="semester" placeholder="e.g. Summer">
        </section>

        <!-- ============ COURSE ============ -->
        <section class="card">
            <h2><i class="fa-brands fa-readme"></i> Course Details</h2>

            <label class="row-check"><input type="checkbox" name="show-course-code" id="show-course-code" checked><span>Course code</span></label>
            <input type="text" class="text-input" name="course-code" id="course-code" placeholder="e.g. ENG 0232-2417">

            <label class="row-check"><input type="checkbox" name="show-course-title" id="show-course-title" checked><span>Course title</span></label>
            <div class="richtext" data-target="course-title-html">
                <div class="richtext-toolbar">
                    <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                    <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                </div>
                <div class="richtext-input" id="course-title" contenteditable="true" data-placeholder="Enter course title"></div>
            </div>
            <input type="hidden" name="course-title-html" id="course-title-html">

            <label class="row-check"><input type="checkbox" name="show-course-teacher-name" id="show-course-teacher-name" checked><span>Teacher name</span></label>
            <input type="text" class="text-input" name="course-teacher-name" id="course-teacher-name" placeholder="Enter teacher name">

            <label class="row-check"><input type="checkbox" name="show-course-teacher-designation" id="show-course-teacher-designation" checked><span>Teacher designation</span></label>
            <input type="text" class="text-input" name="course-teacher-designation" id="course-teacher-designation" placeholder="e.g. Assistant Professor">
        </section>

        <!-- ============ TOPIC ============ -->
        <section class="card">
            <h2><i class="fa-solid fa-square-pen"></i> Topic &amp; Submission</h2>

            <label class="row-check"><input type="checkbox" name="show-topic" id="show-topic" checked><span>Topic</span></label>
            <div class="richtext" data-target="topic-html">
                <div class="richtext-toolbar">
                    <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
                    <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
                </div>
                <div class="richtext-input" id="topic" contenteditable="true" data-placeholder="Enter topic"></div>
            </div>
            <input type="hidden" name="topic-html" id="topic-html">

            <label class="row-check"><input type="checkbox" name="show-submission-date" id="show-submission-date" checked><span>Submission date</span></label>
            <input type="date" class="text-input" name="submission-date" id="submission-date">
        </section>

        <button type="submit" class="btn btn-primary btn-generate"><i class="fa-solid fa-hammer"></i> Generate PDF</button>
    </form>

    <aside class="panel preview-panel">
        <div class="preview-sticky">
            <h2 class="preview-title">Live preview</h2>
            <div class="preview-stage">
                <div class="preview-page" id="preview-page"></div>
            </div>
            <p class="hint center">This mirrors the generated PDF as closely as a browser can.</p>
        </div>
    </aside>
</main>

<footer class="site-footer">
    <p>Assignment Cover Generator — free &amp; open source under the <a href="https://www.gnu.org/licenses/agpl-3.0.html" target="_blank" rel="noopener">AGPL-3.0</a> license. | Made by <a href="https://github.com/TechZeeLand" target="_blank" rel="noopener">TechZeeLand</a></p>
</footer>

<script>window.__INITIAL_FONTS__ = <?php echo json_encode($initialFonts, JSON_UNESCAPED_SLASHES); ?>;</script>
<script src="assets/js/app.js"></script>
</body>
</html>
