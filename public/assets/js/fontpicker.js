/* Font pickers.
   - Replaces the old <select> font lists: every font is shown in its OWN typeface, and fonts for other
     languages (Bangla, Arabic ...) also show a sample in their script, so they are easy to tell apart.
   - One picker per field (blank = "Default", i.e. the field follows its font group) plus the three
     group defaults (header / primary / secondary).
   - The value lives in a hidden <input name="..."> inside each .fpick, so the form, the live preview,
     "Save my details" and Reset keep working exactly as with a normal field.
   - Fonts are loaded into the page with the FontFace API from /font_file.php (the same files the PDF uses). */
(function () {
    'use strict';

    var form = document.getElementById('main-form');
    if (!form) return;

    // Which hidden input holds each default group's font.
    var GROUP_INPUT = { versity: 'versity-name-font', primary: 'primary-font', secondary: 'secondary-font' };
    var GROUP_FIXED = { amiri: 'amiri' }; // the Bismillah has its own default font
    var SCRIPT_INFO = {
        latin:      { label: 'Latin',    sample: 'Abc 123',          lang: 'en', words: 'latin english' },
        bengali:    { label: 'বাংলা',    sample: 'আমার সোনার বাংলা', lang: 'bn', words: 'bangla bengali bn' },
        arabic:     { label: 'العربية',  sample: 'أبجد هوز',         lang: 'ar', rtl: true, words: 'arabic urdu persian' },
        devanagari: { label: 'देवनागरी', sample: 'अआइ कखग',          lang: 'hi', words: 'devanagari hindi nepali marathi' },
        cyrillic:   { label: 'Кирилица', sample: 'Абвгд Жзик',       lang: 'ru', words: 'cyrillic russian' },
        greek:      { label: 'Ελληνικά', sample: 'Αβγδε Ωμέγα',      lang: 'el', words: 'greek' }
    };

    var fonts = Array.isArray(window.__INITIAL_FONTS__) ? window.__INITIAL_FONTS__ : [];
    var pickers = [];
    var faces = {};      // font key -> Promise (loaded once per page)
    var open = null;     // the picker whose list is showing
    var activeIdx = -1;
    var typeBuf = '', typeTimer = null;
    var small = window.matchMedia('(max-width: 640px)');

    // ---------------------------------------------------------------- fonts

    function find(key) {
        for (var i = 0; i < fonts.length; i++) if (fonts[i].key === key) return fonts[i];
        return null;
    }
    function family(font) { return '"acg-' + font.key + '", sans-serif'; }
    function isLegacyBangla(name) { return /(\w*MJ\b|sutonny|bijoy|\bansi\b)/i.test(name || ''); }
    function hasLatin(font) { return !font.scripts || !font.scripts.length || font.scripts.indexOf('latin') !== -1; }

    /** Loads the font file once; the browser then draws any text set in "acg-<key>" with it. */
    function ensureFace(font) {
        if (faces[font.key]) return faces[font.key];
        var url = '/font_file.php?key=' + encodeURIComponent(font.key) + '&variant=regular&v=' + encodeURIComponent(font.v || '1');
        var p;
        try {
            var ff = new FontFace('acg-' + font.key, 'url("' + url + '")');
            p = ff.load().then(function () { document.fonts.add(ff); return true; }, function () { return false; });
        } catch (e) {
            p = Promise.resolve(false);
        }
        faces[font.key] = p;
        return p;
    }

    function groupKeyFor(p) {
        if (p.group && GROUP_FIXED[p.group]) return GROUP_FIXED[p.group];
        var input = p.group && GROUP_INPUT[p.group] ? form.elements[GROUP_INPUT[p.group]] : null;
        return input && input.value ? input.value : '';
    }

    // ---------------------------------------------------------------- triggers

    function paintName(el, font) {
        el.textContent = font.name + (font.custom ? ' (custom)' : '');
        if (hasLatin(font)) {
            ensureFace(font);
            el.style.fontFamily = family(font);
        } else {
            el.style.fontFamily = '';
        }
    }

    function refresh(p) {
        var value = p.input.value;
        var font = value ? find(value) : null;
        if (p.kind === 'group') {
            if (!font) { // unknown / deleted font: fall back to the default
                font = find(p.defaultKey) || fonts[0] || null;
                if (font) p.input.value = font.key;
            }
            if (font) paintName(p.nameEl, font);
            p.btn.classList.remove('is-default');
            return;
        }
        if (value && !font) { value = ''; p.input.value = ''; }
        if (!font) {
            var def = find(groupKeyFor(p));
            p.nameEl.textContent = 'Default';
            p.nameEl.style.fontFamily = '';
            p.btn.title = 'Font for this field' + (def ? ' (default: ' + def.name + ')' : '');
            p.btn.classList.add('is-default');
        } else {
            paintName(p.nameEl, font);
            p.btn.title = 'Font for this field: ' + font.name;
            p.btn.classList.remove('is-default');
        }
        p.btn.classList.toggle('is-set', !!font);
    }

    function refreshAll() { pickers.forEach(refresh); }

    // ---------------------------------------------------------------- popup

    var pop = document.createElement('div');
    pop.className = 'fpop';
    pop.hidden = true;
    pop.innerHTML = '<div class="fpop-head"><input type="search" class="fpop-search" placeholder="Search fonts or languages…" aria-label="Search fonts" autocomplete="off" spellcheck="false">' +
        '<button type="button" class="fpop-close" aria-label="Close font list">&times;</button></div>' +
        '<ul class="fpop-list" role="listbox" tabindex="-1"></ul>';
    var backdrop = document.createElement('div');
    backdrop.className = 'fpop-backdrop';
    backdrop.hidden = true;
    document.body.appendChild(backdrop);
    document.body.appendChild(pop);
    var list = pop.querySelector('.fpop-list');
    var search = pop.querySelector('.fpop-search');

    function tag(text, cls) {
        var s = document.createElement('span');
        s.className = 'fo-tag' + (cls ? ' ' + cls : '');
        s.textContent = text;
        return s;
    }

    function buildRow(font, current) {
        var li = document.createElement('li');
        li.className = 'fo';
        li.setAttribute('role', 'option');
        li.id = 'fo-' + font.key;
        li.dataset.key = font.key;
        li.setAttribute('aria-selected', font.key === current ? 'true' : 'false');
        li.dataset.search = (font.name + ' ' + (font.scripts || []).map(function (s) { var i = SCRIPT_INFO[s] || {}; return (i.label || s) + ' ' + (i.words || ''); }).join(' ') + (font.custom ? ' custom uploaded' : '') + (isLegacyBangla(font.name) ? ' bijoy ansi bangla' : '')).toLowerCase();

        var main = document.createElement('span');
        main.className = 'fo-main';
        var name = document.createElement('span');
        name.className = 'fo-name';
        name.textContent = font.name;
        if (hasLatin(font)) { ensureFace(font); name.style.fontFamily = family(font); }
        main.appendChild(name);

        // A sample in each non-Latin script the font covers, drawn in the font itself.
        var extra = (font.scripts || []).filter(function (s) { return s !== 'latin' && SCRIPT_INFO[s]; });
        extra.slice(0, 2).forEach(function (s) {
            ensureFace(font);
            var smp = document.createElement('span');
            smp.className = 'fo-sample';
            smp.textContent = SCRIPT_INFO[s].sample;
            smp.lang = SCRIPT_INFO[s].lang;
            if (SCRIPT_INFO[s].rtl) smp.dir = 'rtl';
            smp.style.fontFamily = family(font);
            main.appendChild(smp);
        });
        li.appendChild(main);

        var tags = document.createElement('span');
        tags.className = 'fo-tags';
        extra.forEach(function (s) { tags.appendChild(tag(SCRIPT_INFO[s].label, 'fo-tag-script')); });
        if (hasLatin(font) && !extra.length) tags.appendChild(tag('Latin'));
        // SutonnyMJ, Bijoy and similar legacy fonts put Bangla shapes on Latin keys: they need ANSI (Bijoy)
        // text such as "Avwg", not Unicode Bangla. Say so, since their names look like Bangla in the list.
        if (font.custom && isLegacyBangla(font.name)) tags.appendChild(tag('Bijoy / ANSI text', 'fo-tag-script'));
        if (font.custom) tags.appendChild(tag('Custom', 'fo-tag-custom'));
        li.appendChild(tags);
        return li;
    }

    function buildList(p) {
        list.textContent = '';
        var current = p.input.value;
        if (p.kind !== 'group') {
            var def = find(groupKeyFor(p));
            var li = document.createElement('li');
            li.className = 'fo fo-default';
            li.setAttribute('role', 'option');
            li.id = 'fo-default';
            li.dataset.key = '';
            li.dataset.search = 'default';
            li.setAttribute('aria-selected', current === '' ? 'true' : 'false');
            var main = document.createElement('span');
            main.className = 'fo-main';
            var n = document.createElement('span');
            n.className = 'fo-name fo-name-ui';
            n.textContent = 'Default';
            main.appendChild(n);
            if (def) {
                var hint = document.createElement('span');
                hint.className = 'fo-hint';
                hint.textContent = 'uses ' + def.name;
                main.appendChild(hint);
            }
            li.appendChild(main);
            list.appendChild(li);
        }
        fonts.forEach(function (f) { list.appendChild(buildRow(f, current)); });
        search.parentNode.hidden = fonts.length < 8;
    }

    function items() {
        return Array.prototype.filter.call(list.children, function (li) { return !li.hidden; });
    }
    function setActive(i, scroll) {
        var all = items();
        if (!all.length) { activeIdx = -1; return; }
        i = Math.max(0, Math.min(all.length - 1, i));
        Array.prototype.forEach.call(list.children, function (li) { li.classList.remove('is-active'); });
        all[i].classList.add('is-active');
        list.setAttribute('aria-activedescendant', all[i].id);
        activeIdx = i;
        if (scroll !== false) all[i].scrollIntoView({ block: 'nearest' });
    }

    function place(p) {
        if (small.matches) { pop.style.cssText = ''; return; }
        var r = p.btn.getBoundingClientRect();
        var w = Math.min(Math.max(r.width, 340), window.innerWidth - 16);
        var left = Math.max(8, Math.min(r.left, window.innerWidth - w - 8));
        var below = window.innerHeight - r.bottom - 12;
        var above = r.top - 12;
        var h = Math.min(360, Math.max(below, above) > 200 ? Math.max(below, above) : 200);
        var top = below >= Math.min(h, 260) || below >= above ? r.bottom + 6 : r.top - h - 6;
        pop.style.width = w + 'px';
        pop.style.left = left + 'px';
        pop.style.top = Math.max(8, top) + 'px';
        pop.style.maxHeight = h + 'px';
    }

    function openFor(p) {
        if (open && open !== p) closePop(false);
        open = p;
        buildList(p);
        search.value = '';
        pop.hidden = false;
        backdrop.hidden = !small.matches;
        document.body.classList.toggle('fpop-locked', small.matches);
        p.btn.setAttribute('aria-expanded', 'true');
        list.setAttribute('aria-label', 'Fonts for ' + p.label);
        place(p);
        var sel = list.querySelector('[aria-selected="true"]');
        var all = items();
        setActive(sel ? all.indexOf(sel) : 0);
        (search.parentNode.hidden ? list : search).focus({ preventScroll: true });
    }

    function closePop(refocus) {
        if (!open) return;
        var p = open;
        open = null;
        pop.hidden = true;
        backdrop.hidden = true;
        document.body.classList.remove('fpop-locked');
        p.btn.setAttribute('aria-expanded', 'false');
        if (refocus !== false) p.btn.focus({ preventScroll: true });
    }

    function choose(li) {
        if (!open || !li) return;
        var p = open;
        var key = li.dataset.key;
        closePop(true);
        if (p.input.value === key) return;
        p.input.value = key;
        refresh(p);
        // Same events a normal field fires: live preview, auto-save and "unsaved" logic listen for them.
        p.input.dispatchEvent(new Event('input', { bubbles: true }));
        p.input.dispatchEvent(new Event('change', { bubbles: true }));
        // Field pickers show "uses <default>": keep them current when a default group changes.
        if (p.kind === 'group') refreshAll();
    }

    function applyFilter() {
        var q = search.value.trim().toLowerCase();
        Array.prototype.forEach.call(list.children, function (li) {
            li.hidden = q !== '' && li.dataset.search.indexOf(q) === -1;
        });
        setActive(0, false);
    }

    search.addEventListener('input', applyFilter);
    list.addEventListener('click', function (e) { choose(e.target.closest('.fo')); });
    list.addEventListener('mousemove', function (e) {
        var li = e.target.closest('.fo');
        if (li) setActive(items().indexOf(li), false);
    });
    pop.querySelector('.fpop-close').addEventListener('click', function () { closePop(true); });
    backdrop.addEventListener('click', function () { closePop(true); });

    pop.addEventListener('keydown', function (e) {
        var k = e.key;
        if (k === 'Escape') { e.preventDefault(); e.stopPropagation(); closePop(true); }
        else if (k === 'ArrowDown') { e.preventDefault(); setActive(activeIdx + 1); }
        else if (k === 'ArrowUp') { e.preventDefault(); setActive(activeIdx - 1); }
        else if (k === 'Home' && e.target !== search) { e.preventDefault(); setActive(0); }
        else if (k === 'End' && e.target !== search) { e.preventDefault(); setActive(items().length - 1); }
        else if (k === 'Enter') { e.preventDefault(); choose(items()[activeIdx]); }
        else if (k === 'Tab') { closePop(false); }
        else if (e.target === list && k.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            // Type-ahead when there is no search box.
            clearTimeout(typeTimer);
            typeBuf += k.toLowerCase();
            typeTimer = setTimeout(function () { typeBuf = ''; }, 700);
            var all = items();
            for (var i = 0; i < all.length; i++) {
                if (all[i].dataset.search.indexOf(typeBuf) === 0) { setActive(i); break; }
            }
        }
    });

    document.addEventListener('mousedown', function (e) {
        if (!open) return;
        if (pop.contains(e.target) || open.el.contains(e.target)) return;
        closePop(false);
    });
    window.addEventListener('resize', function () { if (open) place(open); });
    // The page scrolls under a desktop popup: close it rather than leave it floating away from its button.
    window.addEventListener('scroll', function (e) {
        if (open && !small.matches && !pop.contains(e.target)) closePop(false);
    }, true);

    // ---------------------------------------------------------------- wiring

    document.querySelectorAll('[data-fpick]').forEach(function (el) {
        var p = {
            el: el,
            input: el.querySelector('input[type="hidden"]'),
            btn: el.querySelector('.fpick-btn'),
            nameEl: el.querySelector('.fpick-name'),
            kind: el.dataset.kind === 'group' ? 'group' : 'field',
            group: el.dataset.group || '',
            defaultKey: el.dataset.default || '',
            label: el.dataset.label || 'this field'
        };
        pickers.push(p);
        p.btn.addEventListener('click', function () { open === p ? closePop(true) : openFor(p); });
        p.btn.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); openFor(p); }
        });
    });

    window.ACGFonts = {
        /** Replace the font list (after an upload or a refresh from the server). */
        setFonts: function (list2) {
            if (!Array.isArray(list2)) return;
            fonts = list2;
            refreshAll();
            if (open) { var p = open; buildList(p); place(p); setActive(0); }
        },
        /** Re-read the hidden inputs (after saved details were applied to the form). */
        syncAll: refreshAll,
        /** Back to the defaults: groups to their built-in font, every field to "Default". */
        resetAll: function () {
            pickers.forEach(function (p) { p.input.value = p.kind === 'group' ? p.defaultKey : ''; });
            refreshAll();
        },
        fonts: function () { return fonts; }
    };

    refreshAll();
})();
