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

    if (uploadBtn) uploadBtn.addEventListener('click', async () => {
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
    // Design picker: keep the wireframe previews in the user's colors
    // (mirrors CoverData: accent = title/border color, or primary when
    // the "different color" switch is off).
    // ---------------------------------------------------------------
    const designGrid = document.getElementById('design-grid');
    function syncDesignColors() {
        if (!designGrid) return;
        const primary = document.getElementById('primary-color').value;
        const secondary = document.getElementById('secondary-color').value;
        const accent = useAccentCheckbox.checked
            ? document.getElementById('title-border-color').value
            : primary;
        designGrid.style.setProperty('--t-primary', primary);
        designGrid.style.setProperty('--t-secondary', secondary);
        designGrid.style.setProperty('--t-accent', accent);
    }
    ['primary-color', 'secondary-color', 'title-border-color'].forEach((id) => {
        document.getElementById(id).addEventListener('input', syncDesignColors);
    });
    useAccentCheckbox.addEventListener('change', syncDesignColors);
    syncDesignColors();

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
        'show-faculty': 'faculty',
        'show-cover-title': 'assignment-type',
        'show-student-session': 'student-session',
        'show-student-email': 'student-email',
        'show-group': 'group-members',
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
        // Paste as plain text: pasted web/Word content would otherwise bring
        // along fonts, colors and block tags the cover can't use.
        editable.addEventListener('paste', (e) => {
            e.preventDefault();
            const clip = e.clipboardData || window.clipboardData;
            const text = clip ? clip.getData('text/plain') : '';
            document.execCommand('insertText', false, text);
        });
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
    // Design picker: horizontal scroller with an "Expand all" toggle
    // ---------------------------------------------------------------
    const picker = document.getElementById('design-picker');
    const expandBtn = document.getElementById('design-expand');
    const prevBtn = document.getElementById('design-prev');
    const nextBtn = document.getElementById('design-next');

    function scrollSelectedIntoView() {
        if (!designGrid || (picker && picker.classList.contains('expanded'))) return;
        const sel = designGrid.querySelector('input[type="radio"]:checked');
        if (!sel) return;
        const card = sel.closest('.design-card');
        designGrid.scrollTo({ left: Math.max(0, card.offsetLeft - (designGrid.clientWidth - card.offsetWidth) / 2), behavior: 'auto' });
    }
    function updateArrows() {
        if (!designGrid || !prevBtn || !nextBtn) return;
        const expanded = picker.classList.contains('expanded');
        prevBtn.hidden = expanded || designGrid.scrollLeft <= 4;
        nextBtn.hidden = expanded || designGrid.scrollLeft + designGrid.clientWidth >= designGrid.scrollWidth - 4;
    }
    if (picker && designGrid) {
        designGrid.addEventListener('scroll', updateArrows, { passive: true });
        window.addEventListener('resize', updateArrows);
        prevBtn.addEventListener('click', () => designGrid.scrollBy({ left: -designGrid.clientWidth * 0.8, behavior: 'smooth' }));
        nextBtn.addEventListener('click', () => designGrid.scrollBy({ left: designGrid.clientWidth * 0.8, behavior: 'smooth' }));
        expandBtn.addEventListener('click', () => {
            const expanded = picker.classList.toggle('expanded');
            expandBtn.setAttribute('aria-expanded', String(expanded));
            expandBtn.querySelector('span').textContent = expanded ? 'Collapse' : 'Expand all';
            expandBtn.querySelector('i').className = expanded ? 'fa-solid fa-compress' : 'fa-solid fa-table-cells-large';
            updateArrows();
            if (!expanded) scrollSelectedIntoView();
        });
        scrollSelectedIntoView();
        updateArrows();
    }

    // ---------------------------------------------------------------
    // University logo: resized in the browser, sent as a data: URI
    // ---------------------------------------------------------------
    const logoFile = document.getElementById('logo-file');
    const logoData = document.getElementById('logo-data');
    const logoPreview = document.getElementById('logo-preview');
    const logoRemove = document.getElementById('logo-remove');

    function setLogo(uri) {
        logoData.value = uri || '';
        logoPreview.innerHTML = '';
        if (uri) {
            const img = new Image();
            img.src = uri;
            img.alt = 'Logo preview';
            logoPreview.appendChild(img);
        } else {
            const span = document.createElement('span');
            span.className = 'logo-empty';
            span.textContent = 'No logo';
            logoPreview.appendChild(span);
        }
        logoRemove.hidden = !uri;
    }
    function encodeLogo(img) {
        for (const max of [600, 450, 300]) {
            const scale = Math.min(1, max / Math.max(img.width, img.height));
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(img.width * scale));
            canvas.height = Math.max(1, Math.round(img.height * scale));
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
            let uri = canvas.toDataURL('image/png');
            if (uri.length <= 600000) return uri;
            ctx.globalCompositeOperation = 'destination-over';
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            uri = canvas.toDataURL('image/jpeg', 0.85);
            if (uri.length <= 600000) return uri;
        }
        return '';
    }
    logoFile.addEventListener('change', () => {
        const file = logoFile.files[0];
        if (!file) return;
        if (!/^image\/(png|jpe?g|webp)$/.test(file.type) || file.size > 10 * 1024 * 1024) {
            alert('Please choose a PNG, JPG or WebP image under 10 MB.');
            logoFile.value = '';
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => {
                const uri = encodeLogo(img);
                if (!uri) { alert('That image is too large. Please use a smaller one.'); return; }
                setLogo(uri);
            };
            img.onerror = () => alert('Could not read that image.');
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });
    logoRemove.addEventListener('click', () => { logoFile.value = ''; setLogo(''); });

    // ---------------------------------------------------------------
    // Saved details (signed-in users)
    // ---------------------------------------------------------------
    const NOT_SAVED = new Set(['topic-html', 'submission-date']);
    const acg = window.__ACG__ || {};

    function syncRichTexts() {
        document.querySelectorAll('.richtext').forEach((wrap) => {
            document.getElementById(wrap.dataset.target).value = wrap.querySelector('.richtext-input').innerHTML;
        });
    }
    function collectState() {
        syncRichTexts();
        const state = {};
        Array.from(form.elements).forEach((el) => {
            if (!el.name || NOT_SAVED.has(el.name) || ['file', 'submit', 'button', 'reset'].includes(el.type)) return;
            if (el.type === 'radio') { if (el.checked) state[el.name] = el.value; }
            else if (el.type === 'checkbox') state[el.name] = el.checked;
            else state[el.name] = el.value;
        });
        return state;
    }
    function applyState(state) {
        Object.keys(state).forEach((name) => {
            if (NOT_SAVED.has(name)) return;
            const el = form.elements[name];
            const val = state[name];
            if (!el) return;
            if (el instanceof RadioNodeList) {
                if (Array.from(el).some((r) => r.value === val)) el.value = val;
            } else if (el.type === 'checkbox') {
                el.checked = !!val;
            } else if (el.tagName === 'SELECT') {
                if (Array.from(el.options).some((o) => o.value === val)) el.value = val;
            } else if (name === 'logo-data') {
                setLogo(typeof val === 'string' ? val : '');
            } else if (name === 'course-title-html') {
                el.value = String(val);
                document.getElementById('course-title').innerHTML = String(val);
            } else if (typeof val === 'string') {
                el.value = val;
            }
        });
        form.querySelectorAll('input[type="checkbox"]').forEach((cb) => cb.dispatchEvent(new Event('change', { bubbles: true })));
        form.querySelectorAll('input[type="color"]').forEach((c) => c.dispatchEvent(new Event('input', { bubbles: true })));
        syncAccentColorState();
        syncDesignColors();
        scrollSelectedIntoView();
        updateArrows();
    }

    if (window.__SAVED_PROFILE__ && typeof window.__SAVED_PROFILE__ === 'object') {
        applyState(window.__SAVED_PROFILE__);
    }

    const saveBtn = document.getElementById('profile-save');
    const clearBtn = document.getElementById('profile-clear');
    const statusEl = document.getElementById('profile-status');
    const noteEl = document.getElementById('profile-note');

    async function profileCall(payload) {
        const res = await fetch('/api/profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': acg.csrf || '' },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({ ok: false, error: 'Unexpected response.' }));
        if (!data.ok) throw new Error(data.error || 'Could not complete that.');
        return data;
    }
    function say(msg, ok) {
        if (!statusEl) return;
        statusEl.textContent = msg;
        statusEl.className = 'profile-status ' + (ok ? 'ok' : 'error');
    }
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            saveBtn.disabled = true;
            try {
                await profileCall({ action: 'save', data: collectState() });
                say('Saved. Your details will be filled in next time you sign in.', true);
                if (noteEl) noteEl.textContent = 'Loaded automatically below.';
                if (clearBtn) clearBtn.disabled = false;
            } catch (e) {
                say(e.message, false);
            } finally {
                saveBtn.disabled = false;
            }
        });
    }
    if (clearBtn) {
        clearBtn.addEventListener('click', async () => {
            if (!confirm('Delete your saved details from this site?')) return;
            try {
                await profileCall({ action: 'delete' });
                say('Your saved details were deleted.', true);
                if (noteEl) noteEl.textContent = 'Nothing saved yet.';
                clearBtn.disabled = true;
            } catch (e) {
                say(e.message, false);
            }
        });
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
            // The browser's native reset sends the (script-filled) font
            // <select>s back to their first option; put the real defaults back.
            FONT_SELECT_IDS.forEach((id) => {
                const wanted = FONT_SELECT_DEFAULTS[id];
                if (fontsCache.some((f) => f.key === wanted)) {
                    document.getElementById(id).value = wanted;
                }
            });
            document.querySelectorAll('.color-field input[type="color"]').forEach((input) => {
                input.dispatchEvent(new Event('input'));
            });
            syncDesignColors();
            setLogo('');
            logoFile.value = '';
            scrollSelectedIntoView();
        }, 0);
    });
})();
