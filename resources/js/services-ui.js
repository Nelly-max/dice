/*
|--------------------------------------------------------------------------
| Service pages (Cooking Gas, Home City) – small behaviours
|--------------------------------------------------------------------------
|
| 1. Live search from the header      <div data-sv-search="remote" data-url="/gas/search">
|                                         <input> … <div data-sv-results hidden></div></div>
|    The server answers with an HTML list (CookingGas/partials/search).
|
| 2. Filter a card grid on the page    <input data-sv-search="local">
|                                       <button data-sv-filter="kind" data-value="rent">
|                                       cards: [data-sv-item] data-search="…" data-kind="…"
|                                       [data-sv-count] [data-sv-empty]
|    ?q=… in the address fills the search box on load.
|
| 3. Listing photos: [data-hc-expand] shows every photo of #imageSlider.
*/

const debounce = (fn, ms) => {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
    };
};

/* ---------------- 1. live search ---------------- */
function initRemoteSearch(box) {
    const input = box.querySelector('input');
    const results = box.querySelector('[data-sv-results]');
    const url = box.dataset.url;
    if (!input || !results || !url) return;

    let controller = null;

    const close = () => { results.hidden = true; };

    const run = debounce(async () => {
        const term = input.value.trim();
        if (term.length < 1) {
            close();
            return;
        }

        controller?.abort();
        controller = new AbortController();

        try {
            const res = await fetch(`${url}?term=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
                signal: controller.signal,
            });
            if (!res.ok) throw new Error(res.status);
            results.innerHTML = await res.text();
            results.hidden = false;
        } catch (e) {
            if (e.name === 'AbortError') return;
            results.innerHTML = '<p class="sv-result-empty">Search is not available right now.</p>';
            results.hidden = false;
        }
    }, 250);

    input.addEventListener('input', run);
    input.addEventListener('focus', () => { if (results.innerHTML.trim() && input.value.trim()) results.hidden = false; });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') { close(); input.blur(); }
        if (e.key === 'Enter' && input.value.trim()) {
            window.location.href = `${url}?term=${encodeURIComponent(input.value.trim())}`;
        }
    });
    document.addEventListener('click', (e) => { if (!box.contains(e.target)) close(); });
}

/* ---------------- 2. card filter ---------------- */
function initLocalFilter() {
    const items = [...document.querySelectorAll('[data-sv-item]')];
    if (!items.length) return;

    const inputs = [...document.querySelectorAll('[data-sv-search="local"] input, input[data-sv-search="local"]')];
    const chips = [...document.querySelectorAll('[data-sv-filter]')];
    const count = document.querySelector('[data-sv-count]');
    const empty = document.querySelector('[data-sv-empty]');
    const noun = count?.dataset.noun || 'item';

    const state = { q: '', filters: {} };

    const apply = () => {
        const words = state.q.toLowerCase().split(/\s+/).filter(Boolean);
        let shown = 0;

        items.forEach((item) => {
            const text = (item.dataset.search || '').toLowerCase();
            const textOk = words.every((w) => text.includes(w));
            const filterOk = Object.entries(state.filters).every(([key, value]) =>
                !value || (item.dataset[key] || '').split(' ').includes(value)
            );
            const ok = textOk && filterOk;
            item.hidden = !ok;
            if (ok) shown++;
        });

        if (count) count.textContent = `${shown} ${shown === 1 ? noun : noun + 's'}`;
        if (empty) empty.hidden = shown > 0;
    };

    inputs.forEach((input) => {
        input.addEventListener('input', () => {
            state.q = input.value;
            inputs.forEach((other) => { if (other !== input) other.value = input.value; });
            apply();
        });
    });

    chips.forEach((chip) => {
        chip.addEventListener('click', () => {
            const key = chip.dataset.svFilter;
            chips.filter((c) => c.dataset.svFilter === key).forEach((c) => {
                c.classList.remove('active');
                c.setAttribute('aria-pressed', 'false');
            });
            chip.classList.add('active');
            chip.setAttribute('aria-pressed', 'true');
            state.filters[key] = chip.dataset.value || '';
            apply();
        });
    });

    document.querySelectorAll('[data-sv-reset]').forEach((btn) => {
        btn.addEventListener('click', () => {
            state.q = '';
            inputs.forEach((i) => { i.value = ''; });
            chips.forEach((c) => {
                const first = !c.dataset.value;
                c.classList.toggle('active', first);
                c.setAttribute('aria-pressed', first ? 'true' : 'false');
            });
            state.filters = {};
            apply();
        });
    });

    const q = new URLSearchParams(window.location.search).get('q');
    if (q) {
        state.q = q;
        inputs.forEach((i) => { i.value = q; });
    }
    apply();
}

/* Home City listing page: the header search sends you back to the listings with ?q= */
function initSearchRedirect() {
    document.querySelectorAll('[data-sv-search="redirect"]').forEach((box) => {
        const input = box.querySelector('input');
        input?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && input.value.trim()) {
                window.location.href = `${box.dataset.url}?q=${encodeURIComponent(input.value.trim())}`;
            }
        });
    });
}

/* ---------------- 3. listing photos ---------------- */
function initGallery() {
    const grid = document.getElementById('imageSlider');
    document.querySelectorAll('[data-hc-expand]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!grid) return;
            const open = grid.classList.toggle('is-expanded');
            btn.querySelector('span').textContent = open ? 'Show fewer' : btn.dataset.label;
            if (!open) grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    // photos are not links anywhere else; tapping one opens the full grid
    grid?.querySelectorAll('a').forEach((a) => a.addEventListener('click', (e) => e.preventDefault()));
}

/* Gas product page: minus starts disabled at quantity 1 (cart.js keeps it in step) */
function initStepper() {
    document.querySelectorAll('.pdp .btns').forEach((wrap) => {
        const input = wrap.querySelector('.quantity-val');
        const dec = wrap.querySelector('.dec');
        if (input && dec) dec.toggleAttribute('disabled', (parseInt(input.value, 10) || 1) <= 1);
    });
}

/* Gas product page: the phone bar's price follows the chosen size */
function initMbarPrice() {
    const price = document.querySelector('[data-mbar-price]');
    if (!price) return;
    document.querySelectorAll('.small-img-col').forEach((thumb) => {
        const pick = () => {
            const n = Number(thumb.dataset.finalPrice) || 0;
            price.textContent = 'Ksh ' + n.toLocaleString('en-KE');
            document.querySelectorAll('.small-img-col').forEach((t) => t.removeAttribute('aria-current'));
            thumb.setAttribute('aria-current', 'true');
        };
        thumb.addEventListener('click', pick);
        thumb.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); thumb.click(); }
        });
    });
}

/* Home City tabs: put reused.js's .line under the active tab on load */
function initTabLine() {
    const active = document.querySelector('.hc-tabs .tab_btn.active');
    const line = document.querySelector('.hc-tabs .line');
    if (!active || !line) return;
    const place = () => {
        const current = document.querySelector('.hc-tabs .tab_btn.active') || active;
        line.style.width = current.offsetWidth + 'px';
        line.style.left = current.offsetLeft + 'px';
    };
    place();
    window.addEventListener('resize', place);
}

/* Airbnb stay page: nights × price + cleaning + service fee for the chosen dates */
function initStayBooking() {
    const box = document.querySelector('[data-stay-book]');
    if (!box) return;
    const price = Number(box.dataset.price) || 0;
    const cleaning = Number(box.dataset.cleaning) || 0;
    const servicePct = Number(box.dataset.service) || 0;
    const inEl = box.querySelector('[data-in]');
    const outEl = box.querySelector('[data-out]');
    const kes = (n) => 'KES ' + Math.round(n).toLocaleString('en-KE');

    const update = () => {
        const a = new Date(inEl.value);
        const b = new Date(outEl.value);
        let nights = Math.round((b - a) / 86400000);
        if (!Number.isFinite(nights) || nights < 1) {
            nights = 1;
            if (!Number.isNaN(a.getTime())) {
                const next = new Date(a.getTime() + 86400000);
                outEl.value = next.toISOString().slice(0, 10);
            }
        }
        if (inEl.value) outEl.min = new Date(new Date(inEl.value).getTime() + 86400000).toISOString().slice(0, 10);
        const sub = price * nights;
        const fee = Math.round(sub * servicePct / 100);
        box.querySelector('[data-nights-label]').textContent = `${kes(price)} × ${nights} ${nights === 1 ? 'night' : 'nights'}`;
        box.querySelector('[data-stay-sub]').textContent = kes(sub);
        box.querySelector('[data-stay-fee]').textContent = kes(fee);
        box.querySelector('[data-stay-total]').textContent = kes(sub + cleaning + fee);
    };

    inEl?.addEventListener('change', update);
    outEl?.addEventListener('change', update);
    update();
}

/* Water product page: choosing a size updates the price */
function initWaterSizes() {
    const sizes = [...document.querySelectorAll('[data-wt-size]')];
    const price = document.querySelector('[data-wt-price]');
    if (!sizes.length || !price) return;
    sizes.forEach((btn) => btn.addEventListener('click', () => {
        sizes.forEach((b) => { b.classList.remove('is-active'); b.setAttribute('aria-checked', 'false'); });
        btn.classList.add('is-active');
        btn.setAttribute('aria-checked', 'true');
        price.textContent = 'Ksh ' + Number(btn.dataset.price).toLocaleString('en-KE');
    }));
}

function init() {
    document.querySelectorAll('[data-sv-search="remote"]').forEach(initRemoteSearch);
    initLocalFilter();
    initSearchRedirect();
    initGallery();
    initStepper();
    initMbarPrice();
    initTabLine();
    initStayBooking();
    initWaterSizes();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
