(function () {
    'use strict';

    var STORAGE_KEY = 'acg-theme';
    var root = document.documentElement;
    var toggle = document.getElementById('theme-toggle');
    var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    function currentTheme() {
        return root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme, persist) {
        root.setAttribute('data-theme', theme);
        if (toggle) {
            var isDark = theme === 'dark';
            toggle.setAttribute('aria-pressed', String(isDark));
            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }
        if (persist) {
            try { localStorage.setItem(STORAGE_KEY, theme); } catch (e) { /* ignore (private mode etc.) */ }
        }
    }

    // Sync the toggle button's ARIA state with whatever the blocking
    // head script already applied before paint.
    applyTheme(currentTheme(), false);

    if (toggle) {
        toggle.addEventListener('click', function () {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark', true);
        });
    }

    // If the visitor never explicitly chose a theme on this device, keep
    // following the OS-level preference as it changes.
    if (mql) {
        mql.addEventListener('change', function (e) {
            var hasExplicitChoice = false;
            try { hasExplicitChoice = !!localStorage.getItem(STORAGE_KEY); } catch (err) { /* ignore */ }
            if (!hasExplicitChoice) {
                applyTheme(e.matches ? 'dark' : 'light', false);
            }
        });
    }
})();
