/*
 |--------------------------------------------------------------------------
 | HomeMarket live search
 |--------------------------------------------------------------------------
 | Tap the header search -> a panel opens (full screen on phones, a dropdown
 | on desktop). Typing shows suggestions from shops the buyer can order from;
 | Enter opens the full results page. Recent searches are kept on the device.
 */

const RECENT_KEY = 'hm_recent_searches';
const MIN_CHARS = 2;

const h = (tag, attrs = {}, ...kids) => {
    const el = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs)) {
        if (v == null || v === false) continue;
        if (k === 'class') el.className = v;
        else if (k === 'text') el.textContent = v;
        else el.setAttribute(k, v === true ? '' : v);
    }
    kids.flat().forEach((c) => c != null && el.append(c.nodeType ? c : document.createTextNode(c)));
    return el;
};

const money = (n) => 'KES ' + Number(n).toLocaleString('en-KE');

const store = {
    read() { try { return JSON.parse(localStorage.getItem(RECENT_KEY)) || []; } catch { return []; } },
    write(list) { try { localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, 6))); } catch { /* private mode */ } },
    add(q) { q = q.trim(); if (q.length >= MIN_CHARS) this.write([q, ...this.read().filter((x) => x.toLowerCase() !== q.toLowerCase())]); },
    remove(q) { this.write(this.read().filter((x) => x !== q)); },
    clear() { this.write([]); },
};

/** Wrap the matching part of a name in <mark>. */
function highlight(text, q) {
    const i = text.toLowerCase().indexOf(q.toLowerCase());
    if (i < 0 || !q) return [text];
    return [text.slice(0, i), h('mark', { text: text.slice(i, i + q.length) }), text.slice(i + q.length)];
}

function init() {
    const root = document.querySelector('[data-hm-search]');
    if (!root) return;

    const topInput = root.querySelector(':scope > input');
    const panel = root.querySelector('.hm-sp');
    const scrim = root.querySelector('.hm-sp-scrim');
    if (!topInput || !panel) return;

    const bar = panel.querySelector('.hm-sp-input');
    const body = panel.querySelector('.hm-sp-body');
    const clearBtn = panel.querySelector('.hm-sp-clear');
    const backBtn = panel.querySelector('.hm-sp-back');
    const popular = panel.querySelector('.hm-sp-popular');
    const endpoint = panel.dataset.endpoint;

    const wide = window.matchMedia('(min-width: 900px)');
    const cache = new Map();
    let timer = null, ctrl = null, active = -1, isOpen = false;

    // The header field only opens the panel; the panel has its own input right above the results,
    // so you see suggestions while you type (full screen on phones, a card over the field on desktop).
    const input = () => bar;
    topInput.readOnly = true;

    const items = () => [...panel.querySelectorAll('[data-sp-item]')];
    const setActive = (i) => {
        const list = items();
        list.forEach((el) => el.classList.remove('is-active'));
        active = list.length ? (i + list.length) % list.length : -1;
        if (active >= 0) { list[active].classList.add('is-active'); list[active].scrollIntoView({ block: 'nearest' }); }
    };

    function open() {
        if (isOpen) return;
        isOpen = true;
        panel.hidden = false;
        scrim.hidden = false;
        root.classList.add('is-open');
        document.body.classList.add('hm-sp-open');
        topInput.setAttribute('aria-expanded', 'true');
        bar.value = topInput.value;
        render();
        bar.focus(); const n = bar.value.length; bar.setSelectionRange(n, n);
    }

    function close() {
        if (!isOpen) return;
        isOpen = false;
        clearTimeout(timer); ctrl?.abort();
        panel.hidden = true; scrim.hidden = true;
        root.classList.remove('is-open');
        document.body.classList.remove('hm-sp-open');
        topInput.setAttribute('aria-expanded', 'false');
        topInput.value = bar.value;
        active = -1;
    }

    const query = () => input().value.trim();

    function go(q) {
        q = (q ?? query()).trim();
        if (!q) return;
        store.add(q);
        const url = `${endpoint}?q=${encodeURIComponent(q)}`;
        close();
        window.softNavigate ? window.softNavigate(url) : (location.href = url);
    }

    /* ---------- rendering ---------- */

    function section(title, ...kids) {
        return h('section', { class: 'hm-sp-sec' }, h('h4', { text: title }), ...kids);
    }

    function renderIdle() {
        body.replaceChildren();
        const recent = store.read();
        if (recent.length) {
            const head = h('h4', {}, 'Recent searches ', h('button', { type: 'button', class: 'hm-sp-link', 'data-clear-recent': true, text: 'Clear' }));
            const list = h('ul', { class: 'hm-sp-recent' }, recent.map((q) => h('li', {},
                h('button', { type: 'button', class: 'hm-sp-recent-item', 'data-sp-item': true, 'data-q': q }, h('i', { class: 'fa-solid fa-clock-rotate-left', 'aria-hidden': true }), h('span', { text: q })),
                h('button', { type: 'button', class: 'hm-sp-x', 'aria-label': `Remove ${q}`, 'data-remove': q }, h('i', { class: 'fa-solid fa-xmark', 'aria-hidden': true })))));
            body.append(h('section', { class: 'hm-sp-sec' }, head, list));
        }
        const links = popular ? [...popular.content.querySelectorAll('a')].map((a) => h('a', { href: a.getAttribute('href'), class: 'hm-sp-chip', 'data-sp-item': true, text: a.textContent })) : [];
        if (links.length) body.append(section('Browse', h('div', { class: 'hm-sp-chips' }, links)));
        if (!recent.length && !links.length) body.append(h('p', { class: 'hm-sp-hint', text: 'Search by product, brand or shop name.' }));
    }

    function renderLoading() {
        body.replaceChildren(h('div', { class: 'hm-sp-skel', 'aria-hidden': true }, [0, 1, 2, 3].map(() => h('div', {}, h('i'), h('b'), h('b')))));
    }

    function renderResults(q, data) {
        body.replaceChildren();
        const { products = [], shops = [], categories = [] } = data;

        if (!products.length && !shops.length && !categories.length) {
            body.append(h('div', { class: 'hm-sp-empty' },
                h('i', { class: 'fa-solid fa-magnifying-glass', 'aria-hidden': true }),
                h('strong', { text: `No results for “${q}”` }),
                h('span', { text: 'Check the spelling, or try a shorter word.' })));
            return;
        }

        if (categories.length || shops.length) {
            body.append(h('div', { class: 'hm-sp-chips hm-sp-top' },
                categories.map((c) => h('a', { href: c.url, class: 'hm-sp-chip', 'data-sp-item': true }, h('i', { class: 'fa-solid fa-table-cells-large', 'aria-hidden': true }), c.label)),
                shops.map((s) => h('a', { href: s.url, class: 'hm-sp-chip hm-sp-shop', 'data-sp-item': true }, h('i', { class: 'fa-solid fa-store', 'aria-hidden': true }), `${s.name} · ${s.km} km`))));
        }

        if (products.length) {
            body.append(h('ul', { class: 'hm-sp-list' }, products.map((p) => h('li', {},
                h('a', { href: p.url, class: 'hm-sp-item', 'data-sp-item': true, 'data-q': q },
                    h('img', { src: p.image, alt: '', loading: 'lazy' }),
                    h('span', { class: 'hm-sp-info' },
                        h('strong', {}, highlight(p.name, q)),
                        h('small', {}, [p.variant, p.shop, p.km != null ? `${p.km} km` : null].filter(Boolean).join(' · '))),
                    h('span', { class: 'hm-sp-price' },
                        h('b', { text: money(p.price) }),
                        p.was ? h('s', { text: money(p.was) }) : null,
                        p.discount ? h('em', { text: `-${p.discount}%` }) : null))))));
            body.append(h('a', { href: `${endpoint}?q=${encodeURIComponent(q)}`, class: 'hm-sp-all', 'data-sp-item': true, 'data-all': true },
                `See all results for “${q}”`, h('i', { class: 'fa-solid fa-arrow-right', 'aria-hidden': true })));
        }
    }

    function render() {
        const q = query();
        clearBtn.hidden = !q;
        setActive(-1);
        if (q.length < MIN_CHARS) { clearTimeout(timer); ctrl?.abort(); renderIdle(); return; }
        if (cache.has(q)) { renderResults(q, cache.get(q)); return; }
        if (!body.querySelector('.hm-sp-list')) renderLoading();
        clearTimeout(timer);
        timer = setTimeout(() => fetchResults(q), 220);
    }

    async function fetchResults(q) {
        ctrl?.abort();
        ctrl = new AbortController();
        try {
            const r = await fetch(`${endpoint}?q=${encodeURIComponent(q)}`, { signal: ctrl.signal, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!r.ok) throw new Error(r.status);
            const data = await r.json();
            cache.set(q, data);
            if (q === query()) renderResults(q, data);
        } catch (e) {
            if (e.name === 'AbortError') return;
            body.replaceChildren(h('div', { class: 'hm-sp-empty' }, h('i', { class: 'fa-solid fa-wifi', 'aria-hidden': true }), h('strong', { text: 'Couldn’t search right now' }),
                h('button', { type: 'button', class: 'hm-sp-link', 'data-retry': true, text: 'Try again' })));
        }
    }

    /* ---------- wiring ---------- */

    topInput.setAttribute('aria-haspopup', 'listbox');
    topInput.setAttribute('aria-expanded', 'false');
    topInput.addEventListener('focus', open);
    topInput.addEventListener('click', open);
    bar.addEventListener('input', () => { topInput.value = bar.value; render(); });
    clearBtn.addEventListener('click', () => { input().value = ''; topInput.value = ''; bar.value = ''; render(); input().focus(); });
    backBtn.addEventListener('click', close);
    scrim.addEventListener('click', close);

    panel.addEventListener('submit', (e) => { e.preventDefault(); go(); });

    const keys = (e) => {
        if (!isOpen) return;
        if (e.key === 'Escape') { close(); }
        else if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
        else if (e.key === 'Enter') {
            e.preventDefault();
            const el = items()[active];
            if (!el) return go();
            if (el.dataset.q && el.tagName === 'BUTTON') return go(el.dataset.q);
            if (el.dataset.q) store.add(el.dataset.q);
            el.click();
        }
    };
    topInput.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });
    bar.addEventListener('keydown', keys);

    panel.addEventListener('click', (e) => {
        const t = e.target.closest('button, a');
        if (!t) return;
        if (t.dataset.clearRecent !== undefined) { store.clear(); renderIdle(); }
        else if (t.dataset.remove) { store.remove(t.dataset.remove); renderIdle(); }
        else if (t.dataset.retry !== undefined) { render(); }
        else if (t.classList.contains('hm-sp-recent-item')) { go(t.dataset.q); }
        else if (t.tagName === 'A') { if (t.dataset.q) store.add(t.dataset.q); else if (t.dataset.all) store.add(query()); close(); }
    });

    // Click outside the search (desktop dropdown) closes it
    document.addEventListener('pointerdown', (e) => { if (isOpen && wide.matches && !root.contains(e.target)) close(); });
    document.addEventListener('softnav:before', close);
    // "/" focuses search like most shops do
    document.addEventListener('keydown', (e) => {
        if (e.key === '/' && !/^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement?.tagName) && !e.metaKey && !e.ctrlKey) {
            e.preventDefault(); open();
        }
    });

    // Landing on the results page: show the term in the header field
    const q0 = new URLSearchParams(location.search).get('q');
    if (q0 && location.pathname.endsWith('/search')) { topInput.value = q0; bar.value = q0; }
}

document.addEventListener('DOMContentLoaded', init);

/*
 |--------------------------------------------------------------------------
 | Fixed header on phones & tablets
 |--------------------------------------------------------------------------
 | Below 900px the header is position: fixed (homemarket-ui.css). The page is
 | pushed down by the header's full height (--hm-header-h), and once you
 | scroll the "Deliver to" strip and category pills tuck away (.is-compact),
 | leaving the nav row and the search bar.
 */
function initHeader() {
    const header = document.querySelector('.hm-header');
    if (!header) return;

    const small = window.matchMedia('(max-width: 899.98px)');
    let compact = false, ticking = false;

    const measure = () => {
        if (!small.matches) { document.body.style.removeProperty('--hm-header-h'); return; }
        if (!compact) document.body.style.setProperty('--hm-header-h', header.offsetHeight + 'px');
    };

    const update = () => {
        ticking = false;
        // a little leeway so the header does not flicker around one scroll position
        const y = window.scrollY;
        const next = small.matches && (compact ? y > 24 : y > 56);
        if (next === compact) return;
        compact = next;
        header.classList.toggle('is-compact', compact);
        if (!compact) measure();
    };

    measure();
    update();
    window.addEventListener('scroll', () => { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
    window.addEventListener('resize', () => { measure(); update(); });
    small.addEventListener?.('change', () => { compact = false; header.classList.remove('is-compact'); measure(); update(); });
    if ('ResizeObserver' in window) new ResizeObserver(measure).observe(header);
    document.fonts?.ready.then(measure);
    window.addEventListener('load', measure);
    setTimeout(measure, 400);
}

document.addEventListener('DOMContentLoaded', initHeader);
