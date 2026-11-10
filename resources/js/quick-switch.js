/*
|--------------------------------------------------------------------------
| Quick switch (resources/views/partials/quick-switch.blade.php)
|--------------------------------------------------------------------------
| Opens / closes the floating panel with the other subdivisions and quick
| links. Closes on the scrim, the ✕, Esc, or when a link is followed.
| Soft navigation (softnav.js) re-imports this file after each page swap, so
| init() runs again for the new page's markup; the [data-qs-ready] guard stops
| double binding.
*/

function initQuickSwitch() {
    const root = document.querySelector('[data-qs]');
    if (!root || root.dataset.qsReady) return;
    root.dataset.qsReady = '1';

    const fab = root.querySelector('[data-qs-toggle]');
    const panel = root.querySelector('.qs-panel');
    const scrim = root.querySelector('.qs-scrim');
    let closeTimer = null;

    const isOpen = () => root.classList.contains('is-open');

    function open() {
        clearTimeout(closeTimer);
        panel.hidden = false;
        scrim.hidden = false;
        // next frame, so the opening transition runs
        requestAnimationFrame(() => root.classList.add('is-open'));
        fab.setAttribute('aria-expanded', 'true');
        (panel.querySelector('.qs-tile:not(.is-here)') || panel.querySelector('a, button'))?.focus({ preventScroll: true });
    }

    function close(focusFab = true) {
        if (!isOpen()) return;
        root.classList.remove('is-open');
        fab.setAttribute('aria-expanded', 'false');
        closeTimer = setTimeout(() => {
            panel.hidden = true;
            scrim.hidden = true;
        }, 220);
        if (focusFab) fab.focus({ preventScroll: true });
    }

    fab.addEventListener('click', () => (isOpen() ? close() : open()));

    root.addEventListener('click', (e) => {
        if (e.target.closest('[data-qs-close]')) {
            close();
            return;
        }

        // delivery location: the shop layouts have the map sheet (#mapModal)
        if (e.target.closest('[data-qs-location]')) {
            close(false);
            if (typeof window.showModal === 'function' && document.getElementById('mapModal')) {
                window.showModal('mapModal');
            } else {
                window.location.href = '/';
            }
            return;
        }

        // following a link: close straight away (the page may be swapped by soft navigation)
        if (e.target.closest('a[href]')) close(false);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isOpen()) close();
    });

    // keep Tab inside the panel while it is open
    panel.addEventListener('keydown', (e) => {
        if (e.key !== 'Tab') return;
        const items = [...panel.querySelectorAll('a[href], button:not([disabled])')];
        if (!items.length) return;
        const first = items[0];
        const last = items[items.length - 1];
        if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
        else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    });

    document.addEventListener('softnav:before', () => close(false));
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initQuickSwitch);
else initQuickSwitch();
