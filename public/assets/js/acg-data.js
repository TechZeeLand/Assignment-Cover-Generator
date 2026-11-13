/* Reads the JSON data block that the server prints in the footer (inline scripts are blocked by the strict CSP). */
(function () {
    'use strict';
    var el = document.getElementById('acg-data');
    var d = {};
    if (el) { try { d = JSON.parse(el.textContent || '{}'); } catch (e) { d = {}; } }
    window.__ACG__ = d.acg || {};
    if (Array.isArray(d.fonts)) { window.__INITIAL_FONTS__ = d.fonts; }
    window.__SAVED_PROFILE__ = d.profile || null;
    window.__FONT_UPLOADS__ = !!d.fontUploads;
})();
