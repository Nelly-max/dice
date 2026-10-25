/*
 |--------------------------------------------------------------------------
 | Smart Market main site (home, settings, information pages)
 |--------------------------------------------------------------------------
 | [data-share]          opens the share sheet (resources/js/share.js)
 | [data-theme-choice]   Light / Dark / Device buttons on the settings page.
 |                       Same localStorage "mode" as the shops and Hub, so the
 |                       choice applies everywhere.
 */

/* ---------- share: handled by resources/js/share.js (loaded through reused.js) ---------- */

/* ---------- theme: Light / Dark / Device ---------- */
const media = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
const readMode = () => { try { return localStorage.getItem('mode'); } catch (e) { return null; } };
const writeMode = (v) => { try { v ? localStorage.setItem('mode', v) : localStorage.removeItem('mode'); } catch (e) { /* private mode */ } };

function paintTheme() {
    const mode = readMode();
    const dark = mode === 'dark' || (mode !== 'light' && !!media && media.matches);
    document.body.classList.toggle('dark-mode', dark);

    const current = mode === 'dark' || mode === 'light' ? mode : 'system';
    document.querySelectorAll('[data-theme-choice]').forEach((b) => {
        const on = b.dataset.themeChoice === current;
        b.classList.toggle('active', on);
        b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest && e.target.closest('[data-theme-choice]');
    if (!btn) return;
    const choice = btn.dataset.themeChoice;
    writeMode(choice === 'system' ? null : choice);
    document.body.classList.add('theme-switching');
    paintTheme();
    setTimeout(() => document.body.classList.remove('theme-switching'), 350);
});

media?.addEventListener?.('change', paintTheme);

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', paintTheme);
else paintTheme();
