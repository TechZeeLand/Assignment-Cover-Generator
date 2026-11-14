/* Live preview. Posts the form to /preview (the same data /generate takes) and shows page 1 of the real PDF as a PNG.
   Desktop: sticky pane beside the form. Phones/tablets: floating "Preview" button that opens the pane as a modal. */
(function () {
    'use strict';

    var pane = document.getElementById('preview-pane');
    if (!pane || !window.ACG) return;

    var form = window.ACG.form;
    var img = document.getElementById('preview-img');
    var empty = document.getElementById('preview-empty');
    var frame = document.getElementById('preview-frame');
    var stateEl = document.getElementById('preview-state');
    var fab = document.getElementById('preview-fab');
    var backdrop = document.getElementById('preview-backdrop');
    var closeBtn = document.getElementById('preview-close');
    var wide = window.matchMedia('(min-width: 1100px)');

    // Keep the sticky pane just below the sticky section ribbon, whatever its height.
    var ribbon = document.querySelector('.section-nav');
    function syncRibbon() {
        if (ribbon) document.documentElement.style.setProperty('--ribbon-h', ribbon.offsetHeight + 'px');
    }
    syncRibbon();
    window.addEventListener('resize', syncRibbon);
    window.addEventListener('load', syncRibbon);

    var timer = null;
    var inflight = null;
    var seq = 0;
    var lastUrl = '';
    var lastSig = '';
    var dirty = true;

    function isModalMode() { return !wide.matches; }
    function isVisible() { return wide.matches || pane.classList.contains('is-open'); }

    function setState(text, isError) {
        stateEl.textContent = text || '';
        stateEl.classList.toggle('error', !!isError);
    }

    function openModal() {
        pane.classList.add('is-open');
        backdrop.classList.add('is-open');
        document.body.classList.add('preview-locked');
        fab.setAttribute('aria-expanded', 'true');
        closeBtn.focus();
        refresh(true);
    }
    function closeModal() {
        pane.classList.remove('is-open');
        backdrop.classList.remove('is-open');
        document.body.classList.remove('preview-locked');
        fab.setAttribute('aria-expanded', 'false');
        if (inflight) { inflight.abort(); inflight = null; }
        fab.focus();
    }
    if (fab) fab.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (backdrop) backdrop.addEventListener('click', closeModal);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && pane.classList.contains('is-open')) closeModal();
    });
    wide.addEventListener('change', function () {
        if (wide.matches) { closeModal(); refresh(false); }
    });

    function buildBody() {
        window.ACG.syncRichTexts();
        return new FormData(form);
    }

    // A signature of the form so identical states are not re-rendered.
    function signature(fd) {
        var parts = [];
        fd.forEach(function (v, k) { if (typeof v === 'string') parts.push(k + '=' + v); });
        return parts.join('&');
    }

    function refresh(force) {
        if (!isVisible()) { dirty = true; return; }
        var fd = buildBody();
        var sig = signature(fd);
        if (!force && sig === lastSig && !dirty) return;
        lastSig = sig;
        dirty = false;

        if (inflight) inflight.abort();
        var ctl = new AbortController();
        inflight = ctl;
        var mine = ++seq;
        frame.classList.add('is-loading');
        setState('Updating…');

        fetch('/preview', { method: 'POST', body: fd, signal: ctl.signal, credentials: 'same-origin' })
            .then(function (res) {
                var type = res.headers.get('Content-Type') || '';
                if (!res.ok || type.indexOf('image/png') !== 0) {
                    return res.json().catch(function () { return {}; }).then(function (d) {
                        var err = new Error(d && d.error ? d.error : 'Could not draw the preview.');
                        err.status = res.status;
                        throw err;
                    });
                }
                return res.blob();
            })
            .then(function (blob) {
                if (mine !== seq) return;
                var url = URL.createObjectURL(blob);
                img.onload = function () { if (lastUrl) URL.revokeObjectURL(lastUrl); lastUrl = url; };
                img.src = url;
                img.hidden = false;
                empty.hidden = true;
                setState('Up to date');
            })
            .catch(function (err) {
                if (err && err.name === 'AbortError') return;
                if (mine !== seq) return;
                if (err && err.status === 501) {
                    // pdftoppm missing on the server: hide the feature entirely.
                    if (fab) fab.remove();
                    pane.remove();
                    return;
                }
                setState(err && err.message ? err.message : 'Could not draw the preview.', true);
            })
            .then(function () {
                if (mine === seq) { frame.classList.remove('is-loading'); inflight = null; }
            });
    }

    function schedule() {
        dirty = true;
        clearTimeout(timer);
        timer = setTimeout(function () { refresh(false); }, 900);
    }

    form.addEventListener('input', schedule);
    form.addEventListener('change', schedule);
    form.addEventListener('reset', function () { setTimeout(schedule, 50); });
    document.addEventListener('tzl:theme', function () { /* preview is a PDF: theme does not matter */ });

    // First render once the page and the saved profile are in place.
    if (wide.matches) { setTimeout(function () { refresh(true); }, 300); }
})();
