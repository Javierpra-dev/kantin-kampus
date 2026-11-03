/**
 * Theme engine – plain JS, no framework dependency.
 *
 * Storage key : "theme"
 * Valid values: "light" | "dark" | "auto"
 * Fallback    : "auto" (when localStorage is unavailable or value is unknown)
 *
 * Exposes window.__themeEngine for the inline anti-flash <head> script
 * and for any page-level code that needs to read / write the preference.
 *
 * UI wiring: event delegation on document for [data-theme-choice] buttons
 * and [data-theme-toggle] buttons (opens/closes the dropdown menu).
 */
(function () {
    'use strict';

    var mq = window.matchMedia('(prefers-color-scheme: dark)');
    var _mqListener = null;

    /* ── Storage helpers ──────────────────────────────────────────────── */
    function read() {
        try {
            var v = localStorage.getItem('theme');
            return (v === 'light' || v === 'dark') ? v : 'auto';
        } catch (e) {
            return 'auto';
        }
    }

    function save(v) {
        try { localStorage.setItem('theme', v); } catch (e) {}
    }

    /* ── Apply theme to <html> ────────────────────────────────────────── */
    function applyTheme(choice) {
        var dark = (choice === 'dark') || (choice === 'auto' && mq.matches);
        document.documentElement.classList.toggle('dark', dark);
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';

        /* Listen to OS changes only while choice is "auto" */
        if (_mqListener) {
            mq.removeEventListener('change', _mqListener);
            _mqListener = null;
        }
        if (choice === 'auto') {
            _mqListener = function () {
                applyTheme('auto');
                syncUI('auto');
            };
            mq.addEventListener('change', _mqListener);
        }
    }

    /* ── Set + persist a new choice ───────────────────────────────────── */
    function setTheme(choice) {
        save(choice);
        applyTheme(choice);
        syncUI(choice);
    }

    /* ── Sync every switcher UI on the page ───────────────────────────── */
    function syncUI(choice) {
        /* Active highlight and check icon on menu items */
        document.querySelectorAll('[data-theme-choice]').forEach(function (el) {
            var isActive = (el.dataset.themeChoice === choice);
            el.setAttribute('aria-checked', isActive ? 'true' : 'false');
            if (isActive) {
                el.classList.add('bg-sage-tint', 'font-bold', 'text-brand');
                el.classList.remove('hover:bg-page');
            } else {
                el.classList.remove('bg-sage-tint', 'font-bold', 'text-brand');
                el.classList.add('hover:bg-page');
            }
            var check = el.querySelector('.theme-check');
            if (check) check.style.display = isActive ? '' : 'none';
        });

        /* Sun / moon icon swap on the toggle buttons */
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            var sun  = btn.querySelector('.theme-icon-sun');
            var moon = btn.querySelector('.theme-icon-moon');
            var isDark = (choice === 'dark') ||
                         (choice === 'auto' && mq.matches);
            if (sun)  sun.style.display  = isDark ? 'none' : '';
            if (moon) moon.style.display = isDark ? '' : 'none';
        });
    }

    /* ── Close all open dropdowns ─────────────────────────────────────── */
    function closeMenus() {
        document.querySelectorAll('.theme-menu').forEach(function (menu) {
            menu.classList.add('hidden');
        });
    }

    /* ── Event delegation ─────────────────────────────────────────────── */
    document.addEventListener('click', function (e) {
        /* 1. Click on a theme choice item */
        var choiceEl = e.target.closest('[data-theme-choice]');
        if (choiceEl) {
            setTheme(choiceEl.dataset.themeChoice);
            closeMenus();
            return;
        }

        /* 2. Click on the toggle button → open/close the nearest .theme-menu */
        var toggleEl = e.target.closest('[data-theme-toggle]');
        if (toggleEl) {
            e.stopPropagation();
            var menu = toggleEl.parentElement.querySelector('.theme-menu');
            if (!menu) {
                menu = document.getElementById(toggleEl.getAttribute('aria-controls'));
            }
            if (menu) {
                var isHidden = menu.classList.contains('hidden');
                closeMenus(); /* close others first */
                if (isHidden) menu.classList.remove('hidden');
            }
            return;
        }

        /* 3. Click anywhere else → close all menus */
        closeMenus();
    });

    /* ── Init on DOMContentLoaded ─────────────────────────────────────── */
    function init() {
        var current = read();
        applyTheme(current);
        syncUI(current);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    /* ── Expose globally (used by the inline <head> anti-flash script) ── */
    window.__themeEngine = {
        get: read,
        set: setTheme,
        apply: function () { applyTheme(read()); }
    };
}());
