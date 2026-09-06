(function () {
    'use strict';

    const form = document.getElementById('main-form');
    const previewPage = document.getElementById('preview-page');

    // ---------------------------------------------------------------
    // Font handling
    // ---------------------------------------------------------------
    const FONT_SELECT_IDS = ['versity-name-font', 'primary-font', 'secondary-font'];
    let fontsCache = Array.isArray(window.__INITIAL_FONTS__) ? window.__INITIAL_FONTS__ : [];

    function builtInCssFamily(key) {
        switch (key) {
            case 'serif': return '"Times New Roman", Times, serif';
            case 'mono': return '"Courier New", Courier, monospace';
            case 'sans':
            default: return 'Arial, Helvetica, sans-serif';
        }
    }

    function cssFamilyFor(font) {
        if (!font.custom) return builtInCssFamily(font.key);
        return `"pv-${font.key}", Arial, sans-serif`;
    }

    function refreshCustomFontFaces() {
        let styleEl = document.getElementById('custom-font-faces');
        if (!styleEl) {
            styleEl = document.createElement('style');
            styleEl.id = 'custom-font-faces';
            document.head.appendChild(styleEl);
        }
        const rules = fontsCache
            .filter((f) => f.custom)
            .map((f) => `@font-face { font-family: "pv-${f.key}"; src: url("font_file.php?key=${encodeURIComponent(f.key)}&variant=regular") format("truetype"); }`)
            .join('\n');
        styleEl.textContent = rules;
    }

    function populateFontSelects() {
        FONT_SELECT_IDS.forEach((id) => {
            const select = document.getElementById(id);
            const previousValue = select.value;
            select.innerHTML = '';
            fontsCache.forEach((font) => {
                const opt = document.createElement('option');
                opt.value = font.key;
                opt.textContent = font.custom ? `${font.name} (custom)` : font.name;
                opt.style.fontFamily = cssFamilyFor(font);
                select.appendChild(opt);
            });
            const stillExists = fontsCache.some((f) => f.key === previousValue);
            select.value = stillExists ? previousValue : (fontsCache[0] ? fontsCache[0].key : 'sans');
        });
        refreshCustomFontFaces();
    }

    async function loadFonts() {
        try {
            const res = await fetch('fonts.php');
            const data = await res.json();
            if (data.ok) {
                fontsCache = data.fonts;
                populateFontSelects();
                renderPreview();
            }
        } catch (e) {
            // Non-fatal: built-in fonts still work from the embedded initial list.
            console.warn('Could not refresh font list', e);
        }
    }

    function fontFamilyForKey(key) {
        const font = fontsCache.find((f) => f.key === key);
        return font ? cssFamilyFor(font) : builtInCssFamily('sans');
    }

    // ---------------------------------------------------------------
    // Custom font upload
    // ---------------------------------------------------------------
    const uploadBtn = document.getElementById('upload-font-btn');
    const uploadMsg = document.getElementById('upload-font-msg');

    uploadBtn.addEventListener('click', async () => {
        const name = document.getElementById('font-name').value.trim();
        const regular = document.getElementById('font-regular').files[0];
        uploadMsg.textContent = '';
        uploadMsg.className = 'upload-msg';

        if (!name) {
            uploadMsg.textContent = 'Please enter a font name.';
            uploadMsg.className = 'upload-msg error';
            return;
        }
        if (!regular) {
            uploadMsg.textContent = 'Please choose a "Regular" font file.';
            uploadMsg.className = 'upload-msg error';
            return;
        }

        const fd = new FormData();
        fd.append('font-name', name);
        fd.append('font-regular', regular);
        ['font-bold', 'font-italic', 'font-bolditalic'].forEach((id) => {
            const f = document.getElementById(id).files[0];
            if (f) fd.append(id, f);
        });

        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading…';
        try {
            const res = await fetch('upload_font.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.ok) {
                uploadMsg.textContent = `"${data.font.name}" added! It's now available to everyone in the font lists.`;
                uploadMsg.className = 'upload-msg success';
                fontsCache = data.fonts;
                populateFontSelects();
                renderPreview();
                document.getElementById('font-name').value = '';
                ['font-regular', 'font-bold', 'font-italic', 'font-bolditalic'].forEach((id) => {
                    document.getElementById(id).value = '';
                });
            } else {
                uploadMsg.textContent = data.error || 'Upload failed.';
                uploadMsg.className = 'upload-msg error';
            }
        } catch (e) {
            uploadMsg.textContent = 'Upload failed. Please try again.';
            uploadMsg.className = 'upload-msg error';
        } finally {
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Upload font';
        }
    });

    // ---------------------------------------------------------------
    // Title/border color toggle
    // ---------------------------------------------------------------
    const useAccentCheckbox = document.getElementById('use-title-border-color');
    const accentColorInput = document.getElementById('title-border-color');
    function syncAccentColorState() {
        accentColorInput.disabled = !useAccentCheckbox.checked;
    }
    useAccentCheckbox.addEventListener('change', () => { syncAccentColorState(); renderPreview(); });
    syncAccentColorState();

    // ---------------------------------------------------------------
    // Show/hide toggle -> fades the paired text field for clarity
    // ---------------------------------------------------------------
    document.querySelectorAll('.row-check input[type="checkbox"]').forEach((cb) => {
        if (cb.id === 'border' || cb.id === 'use-title-border-color' || cb.id === 'bismillah') return;
        const pairedInputId = cb.id.replace(/^show-/, '');
        const paired = document.getElementById(pairedInputId) || (document.querySelector(`.richtext[data-target="${pairedInputId}-html"]`));
        function sync() {
            if (!paired) return;
            paired.closest ? null : null;
            const target = paired.classList && paired.classList.contains('richtext') ? paired : paired;
            target.classList.toggle('field-disabled', !cb.checked);
        }
        cb.addEventListener('change', () => { sync(); renderPreview(); });
        sync();
    });

    // ---------------------------------------------------------------
    // Rich text editors (Course Title, Topic)
    // ---------------------------------------------------------------
    document.querySelectorAll('.richtext').forEach((wrap) => {
        const editable = wrap.querySelector('.richtext-input');
        const hidden = document.getElementById(wrap.dataset.target);
        wrap.querySelectorAll('.richtext-toolbar button').forEach((btn) => {
            btn.addEventListener('click', () => {
                editable.focus();
                document.execCommand(btn.dataset.cmd, false);
                btn.classList.toggle('active', document.queryCommandState(btn.dataset.cmd));
                syncRichText();
            });
        });
        function syncRichText() {
            hidden.value = editable.innerHTML;
            renderPreview();
        }
        editable.addEventListener('input', syncRichText);
        editable.addEventListener('keyup', () => {
            wrap.querySelectorAll('.richtext-toolbar button').forEach((btn) => {
                btn.classList.toggle('active', document.queryCommandState(btn.dataset.cmd));
            });
        });
        syncRichText();
    });

    // Keep hidden rich text inputs in sync right before submit, too.
    form.addEventListener('submit', () => {
        document.querySelectorAll('.richtext').forEach((wrap) => {
            const editable = wrap.querySelector('.richtext-input');
            const hidden = document.getElementById(wrap.dataset.target);
            hidden.value = editable.innerHTML;
        });
    });

    // ---------------------------------------------------------------
    // Date formatting (mirrors PdfService::formatDate on the server)
    // ---------------------------------------------------------------
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    function formatDate(isoStr) {
        if (!isoStr) return '';
        const parts = isoStr.split('-');
        if (parts.length !== 3) return isoStr;
        const [y, m, d] = parts.map(Number);
        if (!y || !m || !d) return isoStr;
        return `${MONTHS[m - 1]} ${String(d).padStart(2, '0')}, ${y}`;
    }

    // ---------------------------------------------------------------
    // Live preview
    // ---------------------------------------------------------------
    function esc(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }
    function val(id) { const el = document.getElementById(id); return el ? el.value : ''; }
    function checked(id) { const el = document.getElementById(id); return el ? el.checked : false; }

    function buildRow(labelText, valueHtml, primaryColor, secondaryColor) {
        return `<p class="pv-row">
            <span class="pv-label" style="color:${primaryColor}">${esc(labelText)}</span>
            <span class="pv-colon">:</span>
            <span class="pv-value" style="color:${secondaryColor}">${valueHtml}</span>
        </p>`;
    }

    function renderPreview() {
        const showBorder = checked('border');
        const versityFamily = fontFamilyForKey(val('versity-name-font'));
        const primaryFamily = fontFamilyForKey(val('primary-font'));
        const secondaryFamily = fontFamilyForKey(val('secondary-font'));
        const primaryColor = val('primary-color') || '#000000';
        const secondaryColor = val('secondary-color') || '#000000';
        const accentColor = checked('use-title-border-color') ? (val('title-border-color') || primaryColor) : primaryColor;
        const suffix = val('header-suffix').trim() || '---';

        let studentRows = '';
        if (checked('show-student-name')) studentRows += buildRow('Name', esc(val('student-name')), primaryColor, secondaryColor);
        if (checked('show-student-id')) studentRows += buildRow('ID', esc(val('student-id')), primaryColor, secondaryColor);
        if (checked('show-student-section')) studentRows += buildRow('Section', esc(val('student-section')), primaryColor, secondaryColor);
        if (checked('show-student-batch')) studentRows += buildRow('Batch', esc(val('student-batch')), primaryColor, secondaryColor);
        if (checked('show-student-program')) studentRows += buildRow('Program', esc(val('student-program')), primaryColor, secondaryColor);
        if (checked('show-semester')) studentRows += buildRow(val('semester-type'), esc(val('semester')), primaryColor, secondaryColor);

        let courseRows = '';
        if (checked('show-course-code')) courseRows += buildRow('Code', esc(val('course-code')), primaryColor, secondaryColor);
        if (checked('show-course-title')) courseRows += buildRow('Title', document.getElementById('course-title').innerHTML, primaryColor, secondaryColor);
        if (checked('show-course-teacher-name')) courseRows += buildRow('Teacher', esc(val('course-teacher-name')), primaryColor, secondaryColor);

        const designationHtml = (checked('show-course-teacher-designation') && val('course-teacher-designation').trim() !== '')
            ? `<p class="pv-designation">${esc(val('course-teacher-designation'))}</p>` : '';

        const bismillahHtml = checked('bismillah') ? `<p class="pv-bismillah" style="color:${secondaryColor}">&#xFDFD;</p>` : '';
        const versityHtml = checked('show-versity-name') ? `<h1 class="pv-versity" style="font-family:${versityFamily};color:${accentColor}">${esc(val('versity'))}</h1>` : '';
        const deptHtml = checked('show-dept-name') ? `<p class="pv-dept" style="font-family:${secondaryFamily};color:${secondaryColor}">${esc(val('dept-name'))}</p>` : '';

        const studentSection = studentRows ? `
            <h2 class="pv-h2" style="font-family:${primaryFamily};color:${primaryColor}">Student Details ${esc(suffix)}</h2>
            <div class="pv-grid-wrap" style="font-family:${secondaryFamily}">${studentRows}</div>` : '';

        const courseSection = (courseRows || designationHtml) ? `
            <h2 class="pv-h2" style="font-family:${primaryFamily};color:${primaryColor};margin-top:8px;">Course Details ${esc(suffix)}</h2>
            <div class="pv-grid-wrap" style="font-family:${secondaryFamily}">${courseRows}</div>
            ${designationHtml}` : '';

        const topicHtml = checked('show-topic') ? `
            <p class="pv-topic-row" style="font-family:${secondaryFamily}">
                <span class="pv-topic-label" style="color:${primaryColor}">Topic</span>
                <span class="pv-topic-colon">:</span>
                <span class="pv-topic-value" style="color:${secondaryColor}">${document.getElementById('topic').innerHTML}</span>
            </p>` : '';

        const dateDisplay = formatDate(val('submission-date'));
        const submissionHtml = (checked('show-submission-date') && dateDisplay) ? `
            <p class="pv-submission" style="font-family:${secondaryFamily};color:${secondaryColor}"><b>Submission Date:</b> ${esc(dateDisplay)}</p>` : '';

        const borderStyle = showBorder ? `border:8px solid ${accentColor};padding:8px;` : 'padding:0;';

        previewPage.innerHTML = `
            <div class="pv-canvas" style="font-family:${secondaryFamily};color:${secondaryColor}">
                <div class="pv-page-pad">
                    <div class="pv-border-box" style="${borderStyle}">
                        <header class="pv-header">
                            ${bismillahHtml}
                            ${versityHtml}
                            ${deptHtml}
                        </header>
                        ${studentSection}
                        ${courseSection}
                        ${topicHtml}
                        ${submissionHtml}
                    </div>
                </div>
            </div>`;

        scalePreview();
    }

    // Scale the fixed 595x842 canvas to fit the (responsive) preview box.
    function scalePreview() {
        const canvas = previewPage.querySelector('.pv-canvas');
        if (!canvas) return;
        const scale = previewPage.clientWidth / 595;
        canvas.style.transform = `scale(${scale})`;
    }
    window.addEventListener('resize', scalePreview);

    // Wire every input/select/textarea to re-render the preview live.
    form.querySelectorAll('input, select').forEach((el) => {
        el.addEventListener('input', renderPreview);
        el.addEventListener('change', renderPreview);
    });

    populateFontSelects();
    renderPreview();
    loadFonts();
})();
