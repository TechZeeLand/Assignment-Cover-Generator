(function () {
    'use strict';

    const form = document.getElementById('main-form');

    // ---------------------------------------------------------------
    // Font handling
    // ---------------------------------------------------------------
    const FONT_SELECT_IDS = ['versity-name-font', 'primary-font', 'secondary-font'];
    const FONT_SELECT_DEFAULTS = {
        'versity-name-font': 'oldenglish',
        'primary-font': 'alata',
        'secondary-font': 'gandhiserif',
    };
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

    // Renders each custom font's own name in that font inside the <select>
    // dropdowns, so users can preview the look before picking it.
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
            const wantedDefault = FONT_SELECT_DEFAULTS[id];
            const defaultAvailable = fontsCache.some((f) => f.key === wantedDefault);
            const fallback = defaultAvailable ? wantedDefault : (fontsCache[0] ? fontsCache[0].key : 'sans');
            select.value = stillExists ? previousValue : fallback;
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
            }
        } catch (e) {
            // Non-fatal: built-in fonts still work from the embedded initial list.
            console.warn('Could not refresh font list', e);
        }
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
    useAccentCheckbox.addEventListener('change', syncAccentColorState);
    syncAccentColorState();

    // ---------------------------------------------------------------
    // Show/hide toggle -> fades the paired field for clarity
    // ---------------------------------------------------------------
    const SHOW_CHECKBOX_TARGETS = {
        'show-versity-name': 'versity',
        'show-dept-name': 'dept-name',
        'show-student-name': 'student-name',
        'show-student-id': 'student-id',
        'show-student-section': 'student-section',
        'show-student-batch': 'student-batch',
        'show-student-program': 'student-program',
        'show-semester': 'semester',
        'show-course-code': 'course-code',
        'show-course-title': 'course-title-html', // resolved to the .richtext wrapper below
        'show-course-teacher-name': 'course-teacher-name',
        'show-course-teacher-designation': 'course-teacher-designation',
        'show-topic': 'topic-html', // resolved to the .richtext wrapper below
        'show-submission-date': 'submission-date',
    };
    Object.keys(SHOW_CHECKBOX_TARGETS).forEach((cbId) => {
        const cb = document.getElementById(cbId);
        if (!cb) return;
        const targetId = SHOW_CHECKBOX_TARGETS[cbId];
        const richtextWrap = document.querySelector(`.richtext[data-target="${targetId}"]`);
        const target = richtextWrap || document.getElementById(targetId);
        if (!target) return;
        function sync() { target.classList.toggle('field-disabled', !cb.checked); }
        cb.addEventListener('change', sync);
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

    populateFontSelects();
    loadFonts();

    // ---------------------------------------------------------------
    // Color swatch hex readout
    // ---------------------------------------------------------------
    document.querySelectorAll('.color-field input[type="color"]').forEach((input) => {
        const hex = document.querySelector(`.color-hex[data-for="${input.id}"]`);
        if (!hex) return;
        function sync() { hex.textContent = input.value.toUpperCase(); }
        input.addEventListener('input', sync);
        sync();
    });

    // ---------------------------------------------------------------
    // Section jump nav: smooth-scroll + highlight the active section
    // ---------------------------------------------------------------
    const navLinks = Array.from(document.querySelectorAll('.section-nav-link'));
    if (navLinks.length) {
        const sections = navLinks
            .map((link) => document.querySelector(link.getAttribute('href')))
            .filter(Boolean);

        function setActive(id) {
            navLinks.forEach((link) => {
                link.classList.toggle('active', link.getAttribute('href') === `#${id}`);
            });
        }

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) setActive(entry.target.id);
                });
            }, { rootMargin: '-30% 0px -55% 0px', threshold: 0 });
            sections.forEach((section) => observer.observe(section));
        }

        if (sections[0]) setActive(sections[0].id);
    }

    // ---------------------------------------------------------------
    // Reset button: also clear the rich-text fields and disabled state,
    // since the browser's native "reset" only restores form controls.
    // ---------------------------------------------------------------
    form.addEventListener('reset', () => {
        setTimeout(() => {
            document.querySelectorAll('.richtext').forEach((wrap) => {
                const editable = wrap.querySelector('.richtext-input');
                const hidden = document.getElementById(wrap.dataset.target);
                editable.innerHTML = '';
                hidden.value = '';
            });
            Object.keys(SHOW_CHECKBOX_TARGETS).forEach((cbId) => {
                const cb = document.getElementById(cbId);
                if (!cb) return;
                cb.dispatchEvent(new Event('change'));
            });
            syncAccentColorState();
            document.querySelectorAll('.color-field input[type="color"]').forEach((input) => {
                input.dispatchEvent(new Event('input'));
            });
        }, 0);
    });
})();
