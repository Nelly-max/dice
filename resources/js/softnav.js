/*
 |--------------------------------------------------------------------------
 | Soft navigation
 |--------------------------------------------------------------------------
 | Turns ordinary link clicks into fetch + swap so the page does not fully
 | reload. Pages and scripts stay exactly as they are: after the swap the
 | page scripts are re-imported (their top-level code re-runs against the new
 | DOM) and their DOMContentLoaded handlers are replayed.
 |
 | Anything unusual (forms, downloads, other layouts, errors) falls back to a
 | normal full page load. Opt a link out with data-no-soft.
 | Programmatic use: window.softNavigate('/url').
 */

const state = { token: 0, importId: 0, intervals: new Set(), listeners: [], capture: null };
const inits = [];                       // listeners added by softnav itself - never removed

/* ---- lifecycle shim: remember what page scripts attach to document/window/timers ---- */
(function patch() {
    const wrap = (target) => {
        const add = target.addEventListener.bind(target);
        target.addEventListener = function (type, fn, opts) {
            if (state.capture && (type === 'DOMContentLoaded' || type === 'load')) {
                state.capture.push(fn);               // replayed once, not registered
                return;
            }
            if (!inits.includes(fn)) state.listeners.push([target, type, fn, opts]);
            return add(type, fn, opts);
        };
        return add;
    };
    const docAdd = wrap(document);
    const winAdd = wrap(window);
    window.__snAdd = (t, type, fn, opts) => { inits.push(fn); (t === document ? docAdd : winAdd)(type, fn, opts); };

    const si = window.setInterval.bind(window);
    window.setInterval = (...a) => { const id = si(...a); state.intervals.add(id); return id; };
})();

const sameOrigin = (u) => u.origin === location.origin;
const SKIP_PATH = /^\/(logout|login|register|password|auth|admin|hub|broadcasting|storage|build)\b|\.(?!html?$|php$)[a-z0-9]{2,5}$/i;

function eligible(a, e) {
    if (!a || !a.href || a.hasAttribute('download') || a.hasAttribute('data-no-soft')) return null;
    if (a.target && a.target !== '_self') return null;
    if (e && (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.defaultPrevented)) return null;
    let u;
    try { u = new URL(a.href, location.href); } catch { return null; }
    if (!/^https?:$/.test(u.protocol) || !sameOrigin(u)) return null;
    if (SKIP_PATH.test(u.pathname)) return null;
    if (u.pathname === location.pathname && u.search === location.search && u.hash) return null;   // in-page anchor
    if (a.getAttribute('href').startsWith('#')) return null;
    return u;
}

/* ---- progress bar ---- */
let bar;
function progress(on) {
    if (!bar) { bar = document.createElement('div'); bar.className = 'sn-bar'; bar.setAttribute('aria-hidden', 'true'); }
    if (on) { document.documentElement.appendChild(bar); bar.classList.remove('done'); void bar.offsetWidth; bar.classList.add('run'); document.documentElement.setAttribute('aria-busy', 'true'); }
    else { bar.classList.add('done'); document.documentElement.removeAttribute('aria-busy'); setTimeout(() => { bar.classList.remove('run', 'done'); bar.remove(); }, 260); }
}

/* ---- fetching (with hover prefetch) ---- */
const cache = new Map();
function load(url) {
    const key = url.href;
    const hit = cache.get(key);
    if (hit && Date.now() - hit.t < 30000) { cache.delete(key); return hit.p; }
    return fetchPage(url);
}
function fetchPage(url) {
    return fetch(url.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'softnav', Accept: 'text/html' } })
        .then(async (r) => {
            const type = r.headers.get('content-type') || '';
            if (!r.ok || !type.includes('text/html')) throw new Error('not html');
            const final = new URL(r.url);
            if (!sameOrigin(final) || SKIP_PATH.test(final.pathname)) throw new Error('redirected away');
            return { final, doc: new DOMParser().parseFromString(await r.text(), 'text/html') };
        });
}
function prefetch(url) {
    if (cache.has(url.href)) return;
    const p = fetchPage(url); p.catch(() => cache.delete(url.href));
    cache.set(url.href, { t: Date.now(), p });
}

/* ---- swapping ---- */
const abs = (el, attr) => new URL(el.getAttribute(attr), location.href).href;

async function syncStyles(doc) {
    const wanted = [...doc.querySelectorAll('head link[rel="stylesheet"]')].map((l) => [abs(l, 'href'), l]);
    const have = new Map([...document.head.querySelectorAll('link[rel="stylesheet"]')].map((l) => [abs(l, 'href'), l]));
    const loading = [];
    for (const [href, l] of wanted) {
        if (have.has(href)) continue;
        const n = document.createElement('link');
        n.rel = 'stylesheet'; n.href = href;
        loading.push(new Promise((res) => { n.onload = n.onerror = res; setTimeout(res, 3000); }));
        document.head.appendChild(n);
    }
    await Promise.all(loading);
    const keep = new Set(wanted.map(([h]) => h));
    return () => have.forEach((l, h) => { if (!keep.has(h)) l.remove(); });      // after the swap
}

function teardown() {
    state.listeners.forEach(([t, type, fn, o]) => t.removeEventListener(type, fn, o));
    state.listeners = [];
    state.intervals.forEach((id) => clearInterval(id));
    state.intervals.clear();
}

async function swap(doc, final, { push, scroll }) {
    const dropOld = await syncStyles(doc);
    teardown();
    const knownScripts = new Set([...document.querySelectorAll('script[src]')].map((s) => abs(s, 'src')));

    document.title = doc.title;
    const csrf = doc.querySelector('meta[name="csrf-token"]');
    if (csrf) document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrf.content);

    // body: keep our classes (dark-mode), take the new data-* attributes
    for (const a of [...document.body.attributes]) if (a.name.startsWith('data-')) document.body.removeAttribute(a.name);
    for (const a of [...doc.body.attributes]) if (a.name.startsWith('data-')) document.body.setAttribute(a.name, a.value);
    document.body.style.overflow = '';

    const fresh = [...doc.body.childNodes].filter((n) => n.nodeName !== 'SCRIPT').map((n) => document.importNode(n, true));
    document.body.replaceChildren(...fresh);
    dropOld();

    // classic external scripts that are not loaded yet (e.g. Google Maps)
    doc.body.querySelectorAll('script[src]').forEach((s) => {
        const href = abs(s, 'src');
        if (knownScripts.has(href)) return;
        const n = document.createElement('script'); n.src = href; n.async = true; document.body.appendChild(n);
    });

    if (push) history.pushState({ sn: 1 }, '', final.href);
    if (scroll) {
        if (final.hash && document.getElementById(decodeURIComponent(final.hash.slice(1)))) document.getElementById(decodeURIComponent(final.hash.slice(1))).scrollIntoView();
        else window.scrollTo(0, scroll.y || 0);
    }

    // re-run page scripts against the new DOM, then replay their DOMContentLoaded handlers
    const srcs = [...doc.querySelectorAll('script[type="module"][src]')].map((s) => abs(s, 'src')).filter((s) => !s.includes('@vite/client') && !/softnav/.test(s));
    const replay = [];
    state.capture = replay;
    const id = ++state.importId;
    for (const s of srcs) {
        try { await import(/* @vite-ignore */ s + (s.includes('?') ? '&' : '?') + 'sn=' + id); }
        catch (err) { console.warn('[softnav] script failed', s, err); }
    }
    state.capture = null;
    for (const fn of replay) { try { await fn.call(document, new Event('DOMContentLoaded')); } catch (err) { console.warn('[softnav] init failed', err); } }

    document.dispatchEvent(new CustomEvent('softnav:after', { detail: { url: final.href } }));
}

async function go(url, { push = true, scroll = null } = {}) {
    const mine = ++state.token;
    progress(true);
    document.dispatchEvent(new CustomEvent('softnav:before', { detail: { url: url.href } }));
    try {
        const { final, doc } = await load(url);
        if (mine !== state.token) return;
        const here = document.body.dataset.subdivision;
        const optedIn = doc.querySelector('script[src*="softnav"]');
        if (!optedIn || !doc.body || doc.body.dataset.subdivision !== here) throw new Error('different shell');
        if (push) history.replaceState({ sn: 1, y: window.scrollY }, '');
        await swap(doc, final, { push, scroll: scroll || { y: 0 } });
        progress(false);
    } catch (err) {
        if (mine === state.token) location.assign(url.href);        // graceful full load
    }
}

window.softNavigate = (href, opts = {}) => {
    const url = new URL(href, location.href);
    if (opts.replace) {
        cache.delete(url.href);                 // always fetch fresh (e.g. after saving a location)
        return go(url, { push: false, scroll: { y: 0 } }).then(() => history.replaceState({ sn: 1 }, '', url.href));
    }
    return go(url);
};

/* ---- wiring ---- */
if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
history.replaceState({ sn: 1 }, '');

window.__snAdd(document, 'click', (e) => {
    const a = e.target.closest && e.target.closest('a');
    const u = eligible(a, e);
    if (!u) return;
    e.preventDefault();
    go(u);
});
const hover = (e) => { const u = eligible(e.target.closest && e.target.closest('a')); if (u) prefetch(u); };
window.__snAdd(document, 'mouseover', hover, { passive: true });
window.__snAdd(document, 'touchstart', hover, { passive: true });
window.__snAdd(window, 'popstate', (e) => {
    if (!e.state || !e.state.sn) return;
    go(new URL(location.href), { push: false, scroll: { y: e.state.y || 0 } });
});
