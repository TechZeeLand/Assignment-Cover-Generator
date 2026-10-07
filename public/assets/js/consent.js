(function () {
    'use strict';

    const cfg = window.__ACG__ || {};
    const KEY = 'acg-consent';
    const banner = document.getElementById('cookie-banner');

    function stored() {
        try { return localStorage.getItem(KEY); } catch (e) { return null; }
    }
    function store(value) {
        try { localStorage.setItem(KEY, value); } catch (e) { /* private mode: choice lasts for this page only */ }
    }

    // Ads need a consent decision only when the admin asked for one AND the
    // banner (the only way to give it) is switched on.
    const consentNeeded = !!(cfg.adsConsent && cfg.cookieBanner);

    function adsAllowed() {
        if (!cfg.adsEnabled || !cfg.adsClient) return false;
        return !consentNeeded || stored() === 'accepted';
    }

    let adsLoaded = false;
    function loadAds() {
        if (adsLoaded || !adsAllowed()) return;
        const wraps = document.querySelectorAll('.ad-wrap');
        if (!wraps.length) return;
        adsLoaded = true;
        const s = document.createElement('script');
        s.async = true;
        s.crossOrigin = 'anonymous';
        s.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(cfg.adsClient);
        document.head.appendChild(s);
        wraps.forEach((wrap) => {
            wrap.hidden = false;
            try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (e) { /* ad blocked */ }
        });
    }

    function showBanner() { if (banner) banner.hidden = false; }
    function hideBanner() { if (banner) banner.hidden = true; }

    if (banner) {
        banner.querySelectorAll('[data-consent]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const previous = stored();
                const choice = btn.getAttribute('data-consent');
                store(choice);
                hideBanner();
                if (choice === 'accepted') {
                    loadAds();
                } else if (previous === 'accepted' && adsLoaded) {
                    window.location.reload(); // drop ad scripts that are already running
                }
            });
        });
    }
    document.querySelectorAll('[data-cookie-settings]').forEach((el) => {
        el.addEventListener('click', showBanner);
    });

    if (cfg.cookieBanner && !stored()) showBanner();
    loadAds();
})();
