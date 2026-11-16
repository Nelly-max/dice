/*
|--------------------------------------------------------------------------
| Cart page: the hold countdown and live stock (resources/views/Cart/cart.blade.php [data-cart-hold])
|--------------------------------------------------------------------------
| What a buyer adds is held for them for a few minutes (App\Services\Cart\CartStock).
| - The ring and "Held for you for 9:41" count down to the end of that hold; then the banner says
|   the hold ended (the items stay in the cart, other buyers can now buy them).
| - Every 30 s while the page is visible it asks GET /cart/status: a shop sold out or has less
|   now? The cart is brought in line on the server and the page reloads to show it.
| Imported by cart.js. Survives soft navigation (softnav.js) through window.__snAdd.
*/

if (!window.__cartHold) {
    window.__cartHold = true;

    const on = (target, type, fn, opts) => (window.__snAdd ? window.__snAdd(target, type, fn, opts) : target.addEventListener(type, fn, opts));
    const CHECK_EVERY = 30000;

    let tick = null;
    let poll = null;
    let box = null;

    const pad = (n) => String(n).padStart(2, '0');

    function stop() {
        clearInterval(tick);
        clearInterval(poll);
        tick = poll = null;
        box = null;
    }

    function render() {
        if (!box || !box.isConnected) return stop();
        const until = Date.parse(box.dataset.holdUntil || '');
        const total = (parseInt(box.dataset.holdMinutes, 10) || 10) * 60000;
        const left = Number.isFinite(until) ? until - Date.now() : 0;

        if (left <= 0) {
            box.classList.add('is-over');
            box.style.setProperty('--hold', 0);
            clearInterval(tick);
            tick = null;
            return;
        }

        box.classList.remove('is-over');
        box.classList.toggle('is-ending', left < 60000);
        box.style.setProperty('--hold', Math.max(0, Math.min(1, left / total)).toFixed(4));
        const s = Math.ceil(left / 1000);
        const out = box.querySelector('[data-hold-left]');
        if (out) out.textContent = `${Math.floor(s / 60)}:${pad(s % 60)}`;
    }

    async function check() {
        if (!box || !box.isConnected) return stop();
        if (document.visibilityState !== 'visible') return;
        try {
            const url = new URL(box.dataset.statusUrl, location.href);
            const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            if (data.changed) {
                stop();
                if (window.softNavigate) window.softNavigate(location.href, { replace: true });
                else location.reload();
                return;
            }
            // a quantity changed on this page holds it again: follow the new end time
            if ((data.hold_until || '') !== (box.dataset.holdUntil || '')) {
                box.dataset.holdUntil = data.hold_until || '';
                render();
                if (!tick && !box.classList.contains('is-over')) tick = setInterval(render, 1000);
            }
        } catch (e) { /* offline: try again next time */ }
    }

    function start() {
        stop();
        box = document.querySelector('[data-cart-hold]');
        if (!box) return;
        render();
        if (!box.classList.contains('is-over')) tick = setInterval(render, 1000);
        poll = setInterval(check, CHECK_EVERY);
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
    on(document, 'softnav:after', start);
    on(document, 'softnav:before', stop);
    // coming back to the tab: check straight away
    on(document, 'visibilitychange', () => { if (document.visibilityState === 'visible' && box) { render(); check(); } });
}
