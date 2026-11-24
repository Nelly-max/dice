/*
|--------------------------------------------------------------------------
| Rider delivery requests on every page (not only the Hub)
|--------------------------------------------------------------------------
| Loaded by partials/rider-request.blade.php for signed-in riders. Hub pages use
| riderLocation.js for the same card (toast/toast.blade.php), so this file never
| runs there.
|
|  - checks GET /rider/delivery/request every few seconds (the server only returns
|    a request the dispatcher sent to this rider, i.e. when they meet its rules)
|  - shows the card, plays /sounds/delivery-request.mp3 on a loop (and vibrates on
|    phones) and counts down to the request's expires_at
|  - "Accept" posts /rider/delivery/{request}/accept, then opens /hub/navigate/{delivery}
|
| Browsers only allow sound after the person has tapped or typed on the site once,
| so the sound is unlocked on the first tap/key press.
*/

const POLL_MS = 8000;
const FALLBACK_SECONDS = 15;

if (!window.__riderRequestStarted) {
    window.__riderRequestStarted = true;

    const state = { req: null, timer: null, poll: null, busy: false };

    const els = () => ({
        card: document.getElementById('accept'),
        store: document.getElementById('pickupStore'),
        meta: document.getElementById('rqMeta'),
        btn: document.getElementById('acceptDeliveryBtn'),
        bar: document.getElementById('acceptProgress'),
        count: document.getElementById('acceptCountdown'),
    });

    /* ---------------- sound ---------------- */

    const sound = new Audio('/sounds/delivery-request.mp3');
    sound.loop = true;
    sound.preload = 'auto';

    const unlock = () => {
        sound.muted = true;
        sound.play().then(() => {
            sound.pause();
            sound.currentTime = 0;
            sound.muted = false;
        }).catch(() => { sound.muted = false; });
        window.removeEventListener('pointerdown', unlock);
        window.removeEventListener('keydown', unlock);
    };
    window.addEventListener('pointerdown', unlock);
    window.addEventListener('keydown', unlock);

    const startSound = () => {
        sound.currentTime = 0;
        sound.play().catch(() => { /* not allowed until the rider taps the page once */ });
        try { navigator.vibrate?.([400, 200, 400, 200, 400]); } catch (e) {}
    };
    const stopSound = () => {
        sound.pause();
        sound.currentTime = 0;
        try { navigator.vibrate?.(0); } catch (e) {}
    };

    /* ---------------- card ---------------- */

    function hide() {
        const { card, btn } = els();
        clearInterval(state.timer);
        state.timer = null;
        state.req = null;
        stopSound();
        card?.classList.remove('active');
        if (btn) btn.disabled = false;
    }

    function show(req) {
        const { card, store, meta, btn, bar, count } = els();
        if (!card) return;

        state.req = req;
        if (store) store.textContent = `Pickup: ${req.pickup_store ?? 'Shop'}`;

        if (meta) {
            const bits = [];
            if (req.distance != null && req.distance !== '') bits.push(`${Number(req.distance).toFixed(1)} km`);
            if (req.eta != null && req.eta !== '') bits.push(`~${req.eta} min`);
            meta.textContent = bits.join(' · ');
            meta.hidden = bits.length === 0;
        }

        if (btn) btn.disabled = false;
        card.classList.add('active');
        startSound();

        // countdown to the request's expiry
        const end = Date.parse(req.expires_at) || (Date.now() + FALLBACK_SECONDS * 1000);
        const total = Math.max(1000, end - Date.now());

        clearInterval(state.timer);
        const tick = () => {
            const left = end - Date.now();
            if (left <= 0) { hide(); return; }
            if (bar) bar.style.width = `${Math.max(0, (left / total) * 100)}%`;
            if (count) count.textContent = `Accept Delivery (${Math.ceil(left / 1000)})`;
        };
        tick();
        state.timer = setInterval(tick, 250);
    }

    /* ---------------- server ---------------- */

    async function check() {
        if (state.busy) return;
        try {
            const res = await fetch('/rider/delivery/request', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return;
            const data = await res.json();

            if (data.success && data.request?.id) {
                if (!state.req || state.req.id !== data.request.id) show(data.request);
            } else if (state.req) {
                hide();          // taken by someone else, cancelled or expired
            }
        } catch (e) {
            /* offline for a moment: try again on the next round */
        }
    }

    async function accept() {
        const req = state.req;
        const { btn, count } = els();
        if (!req || state.busy) return;

        state.busy = true;
        if (btn) btn.disabled = true;
        if (count) count.textContent = 'Accepting…';
        stopSound();

        try {
            const res = await fetch(`/rider/delivery/${encodeURIComponent(req.id)}/accept`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: '{}',
            });
            const data = await res.json().catch(() => ({}));

            if (!res.ok || !data.success) {
                hide();
                window.notify?.error(data.message || 'This delivery could not be accepted. It may have gone to another rider.', { title: 'Not accepted' });
                return;
            }

            try { localStorage.setItem('activeDeliveryId', String(req.delivery_id)); } catch (e) {}
            clearInterval(state.timer);
            window.notify?.success('Opening the route to the pickup…', { title: 'Delivery accepted' });
            window.location.href = `/hub/navigate/${encodeURIComponent(req.delivery_id)}`;
        } catch (e) {
            if (btn) btn.disabled = false;
            window.notify?.error('Check your connection and try again.', { title: 'Network error' });
        } finally {
            state.busy = false;
        }
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest) return;
        if (e.target.closest('[data-rider-request] #acceptDeliveryBtn')) {
            e.preventDefault();
            accept();
            return;
        }
        // floating map button (a delivery in progress): back to the map
        const open = e.target.closest('[data-rider-request] #openNavigation');
        if (open) {
            e.preventDefault();
            let id = open.dataset.deliveryId;
            if (!id) { try { id = localStorage.getItem('activeDeliveryId'); } catch (err) {} }
            if (id) window.location.href = `/hub/navigate/${encodeURIComponent(id)}`;
        }
    });

    // remember the last page so "minimise" on the map can bring the rider back here
    try { sessionStorage.setItem('riderLastPage', location.href); } catch (e) {}
    document.addEventListener('softnav:after', () => { try { sessionStorage.setItem('riderLastPage', location.href); } catch (e) {} });

    // check now, then every few seconds; straight away when the tab comes back
    const begin = () => {
        if (!document.querySelector('[data-rider-request]')) return;
        check();
        clearInterval(state.poll);
        state.poll = setInterval(check, POLL_MS);
    };
    document.addEventListener('visibilitychange', () => { if (!document.hidden) check(); });
    document.addEventListener('softnav:after', () => { if (state.req) show(state.req); else check(); });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', begin);
    else begin();
}
