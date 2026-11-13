(function () {
    'use strict';

    // Live preview: posts the form to /preview.php (same data as "Generate
    // PDF") and shows the PNG of page 1 it returns. One image per place it is
    // shown - the side pane on wide screens, the modal opened by the floating
    // button on phones/tablets.
    const form = document.getElementById('main-form');
    const pane = document.getElementById('preview-pane');
    const modal = document.getElementById('preview-modal');
    const fab = document.getElementById('preview-fab');
    const closeBtn = document.getElementById('preview-close');
    if (!form || (!pane && !modal)) return;

    const views = Array.from(document.querySelectorAll('.preview-frame')).map((frame) => ({
        frame,
        img: frame.querySelector('.preview-img'),
        msg: frame.querySelector('.preview-msg'),
        status: frame.closest('aside, dialog').querySelector('.preview-status'),
    }));

    let timer = null;
    let controller = null;
    let objectUrl = '';
    let seq = 0;
    let lastKey = '';
    let unavailable = false;

    function paneVisible() {
        return !!pane && pane.offsetParent !== null;
    }
    function modalOpen() {
        return !!modal && modal.open;
    }
    function wanted() {
        return !unavailable && (paneVisible() || modalOpen());
    }

    function setState(state, text) {
        views.forEach((v) => {
            v.frame.dataset.state = state;
            if (v.status) v.status.textContent = state === 'loading' ? 'Updating\u2026' : '';
            if (state === 'error' || state === 'idle') {
                v.msg.textContent = text || '';
                v.msg.hidden = !text;
            } else {
                v.msg.hidden = true;
            }
        });
    }

    function syncRichText() {
        document.querySelectorAll('.richtext').forEach((wrap) => {
            const hidden = document.getElementById(wrap.dataset.target);
            const editable = wrap.querySelector('.richtext-input');
            if (hidden && editable) hidden.value = editable.innerHTML;
        });
    }

    function formKey(fd) {
        const parts = [];
        fd.forEach((value, name) => { parts.push(name + '=' + value); });
        return parts.join('&');
    }

    async function refresh(force) {
        if (!wanted()) return;
        syncRichText();
        const fd = new FormData(form);
        const key = formKey(fd);
        if (!force && key === lastKey) return;
        lastKey = key;

        if (controller) controller.abort();
        controller = new AbortController();
        const mine = ++seq;
        setState('loading');

        try {
            const res = await fetch('/preview.php', { method: 'POST', body: fd, signal: controller.signal });
            if (mine !== seq) return;
            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                if (res.status === 501) {
                    unavailable = true;
                    setState('error', 'Live preview is not available on this server. You can still generate the PDF.');
                } else {
                    lastKey = '';
                    setState('error', data.error || 'Could not update the preview.');
                }
                return;
            }
            const blob = await res.blob();
            if (mine !== seq) return;
            const next = URL.createObjectURL(blob);
            views.forEach((v) => { v.img.src = next; v.img.hidden = false; });
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = next;
            setState('ready');
        } catch (e) {
            if (e && e.name === 'AbortError') return;
            lastKey = '';
            setState('error', 'Could not update the preview. Check your connection.');
        }
    }

    function schedule(delay) {
        clearTimeout(timer);
        if (!wanted()) return;
        timer = setTimeout(() => refresh(false), typeof delay === 'number' ? delay : 700);
    }

    form.addEventListener('input', () => schedule());
    form.addEventListener('change', () => schedule());
    form.addEventListener('reset', () => schedule(150));
    window.addEventListener('resize', () => schedule(400));

    if (fab && modal) {
        fab.addEventListener('click', () => {
            if (typeof modal.showModal === 'function') modal.showModal();
            else modal.setAttribute('open', '');
            document.documentElement.classList.add('preview-open');
            refresh(false);
        });
        const close = () => {
            if (typeof modal.close === 'function') modal.close();
            else modal.removeAttribute('open');
            document.documentElement.classList.remove('preview-open');
        };
        closeBtn.addEventListener('click', close);
        modal.addEventListener('close', () => document.documentElement.classList.remove('preview-open'));
        // Tap on the dark backdrop closes it.
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });
        // "Generate PDF" inside the modal submits the form in a new tab; keep the modal open.
    }

    // First picture once app.js has filled in saved details.
    setTimeout(() => refresh(true), 250);
})();
