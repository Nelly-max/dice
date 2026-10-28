/*
 |--------------------------------------------------------------------------
 | Hub soft navigation
 |--------------------------------------------------------------------------
 | Moving between Hub pages swaps only the page area (.hub-page), the top-bar
 | title and the sidebar's active link. The shell stays alive, so the rider
 | online switch, location tracking and notifications keep running.
 |
 | Links:  any same-origin /hub link (and GET forms inside the page, e.g. the
 |         orders search) – opt out with data-no-soft.
 | Anything else (POST forms, other layouts such as the delivery map, errors)
 | falls back to a normal page load.
 |
 | After a swap: window.hubInitPage(root) re-binds tables (hub.js) and the
 | page scripts (calendar, custom selects, modals, account helpers) are
 | re-run against the new content. Fires 'hubnav:after'.
 */

(() => {
    if (window.hubNavigate) return;

    const PAGE = '.hub-page';
    const RERUN = /\/(calendar|select|modal|reused)[-.]/;      // page-level scripts to re-run
    const state = { token: 0, run: 0, listeners: [], intervals: [] };
    const cache = new Map();

    const sameOrigin = (u) => u.origin === location.origin;
    const isHub = (u) => /^\/hub(\/|$)/.test(u.pathname) && !/deliver(y|ies)[-_]?map/i.test(u.pathname);

    function eligible(a, e) {
        if (!a || !a.href || a.hasAttribute('download') || a.hasAttribute('data-no-soft')) return null;
        if (a.getAttribute('href').startsWith('#') || a.getAttribute('aria-disabled') === 'true') return null;
        if (a.target && a.target !== '_self') return null;
        if (e && (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.defaultPrevented)) return null;
        let u;
        try { u = new URL(a.href, location.href); } catch { return null; }
        if (!sameOrigin(u) || !isHub(u)) return null;
        if (u.pathname === location.pathname && u.search === location.search && u.hash) return null;
        return u;
    }

    /* ---- progress bar ---- */
    let bar;
    function progress(on) {
        if (!bar) { bar = document.createElement('div'); bar.className = 'hub-progress'; bar.setAttribute('aria-hidden', 'true'); }
        if (on) {
            document.body.appendChild(bar); bar.classList.remove('done'); void bar.offsetWidth; bar.classList.add('run');
            document.querySelector(PAGE)?.setAttribute('aria-busy', 'true');
        } else {
            bar.classList.add('done');
            document.querySelector(PAGE)?.removeAttribute('aria-busy');
            setTimeout(() => { bar.classList.remove('run', 'done'); bar.remove(); }, 260);
        }
    }

    /* ---- fetch (+ hover prefetch) ---- */
    function fetchPage(url) {
        return fetch(url.href, { credentials: 'same-origin', headers: { 'X-Requested-With': 'hubnav', Accept: 'text/html' } })
            .then(async (r) => {
                const type = r.headers.get('content-type') || '';
                if (!r.ok || !type.includes('text/html')) throw new Error('not html');
                const final = new URL(r.url);
                if (!sameOrigin(final) || !isHub(final)) throw new Error('left the hub');
                const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
                if (!doc.querySelector(PAGE) || !doc.body.classList.contains('hub-body')) throw new Error('other layout');
                return { final, doc };
            });
    }
    function load(url) {
        const hit = cache.get(url.href);
        cache.delete(url.href);
        if (hit && Date.now() - hit.t < 20000) return hit.p;
        return fetchPage(url);
    }
    function prefetch(url) {
        if (cache.has(url.href) || url.href === location.href) return;
        const p = fetchPage(url);
        p.catch(() => cache.delete(url.href));
        cache.set(url.href, { t: Date.now(), p });
    }

    /* ---- re-running page scripts ---- */
    function teardown() {
        state.listeners.forEach(([t, type, fn, o]) => t.removeEventListener(type, fn, o));
        state.intervals.forEach((id) => clearInterval(id));
        state.listeners = []; state.intervals = [];
    }

    async function rerunScripts() {
        const srcs = [...document.querySelectorAll('script[type="module"][src]')]
            .map((s) => s.src)
            .filter((s) => RERUN.test(s) && !s.includes('@vite/client'));
        if (!srcs.length) return;

        // record what the scripts attach to document/window so the next swap can remove it,
        // and collect their DOMContentLoaded handlers to replay once
        const replay = [];
        const patched = [document, window].map((t) => {
            const orig = t.addEventListener;
            t.addEventListener = function (type, fn, o) {
                if (type === 'DOMContentLoaded' || type === 'load') { replay.push(fn); return; }
                state.listeners.push([t, type, fn, o]);
                return orig.call(this, type, fn, o);
            };
            return [t, orig];
        });
        const si = window.setInterval;
        window.setInterval = (...a) => { const id = si(...a); state.intervals.push(id); return id; };

        const run = ++state.run;
        try {
            for (const s of srcs) {
                try { await import(/* @vite-ignore */ s + (s.includes('?') ? '&' : '?') + 'hn=' + run); }
                catch (err) { console.warn('[hubnav] script failed', s, err); }
            }
            for (const fn of replay) {
                try { await fn.call(document, new Event('DOMContentLoaded')); }
                catch (err) { console.warn('[hubnav] init failed', err); }
            }
        } finally {
            patched.forEach(([t, orig]) => { t.addEventListener = orig; });
            window.setInterval = si;
        }
    }

    /* ---- swap ---- */
    async function swap(doc, final, push, scrollY) {
        teardown();
        document.title = doc.title;
        const csrf = doc.querySelector('meta[name="csrf-token"]');
        if (csrf) document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', csrf.content);

        const page = document.querySelector(PAGE);
        page.replaceChildren(...[...doc.querySelector(PAGE).childNodes].map((n) => document.importNode(n, true)));

        // top-bar title and sidebar highlight
        const title = doc.querySelector('.hub-title');
        if (title) document.querySelector('.hub-title')?.replaceChildren(...[...title.childNodes].map((n) => document.importNode(n, true)));
        const menu = doc.querySelector('.hub-menu');
        if (menu) document.querySelector('.hub-menu')?.replaceChildren(...[...menu.childNodes].map((n) => document.importNode(n, true)));

        // server flashes (toasts) that came with the new page
        doc.querySelectorAll('[data-notify-flash]').forEach((n) => document.body.appendChild(document.importNode(n, true)));

        // phone drawer closes after picking a page
        document.querySelectorAll('.sidebar.active').forEach((s) => s.classList.remove('active'));

        if (push) history.pushState({ hn: 1 }, '', final.href);
        else if (final.href !== location.href) history.replaceState({ hn: 1 }, '', final.href);
        window.scrollTo(0, scrollY || 0);

        window.hubInitPage?.(page);
        await rerunScripts();
        page.querySelector('h1, h2')?.setAttribute('tabindex', '-1');

        document.dispatchEvent(new CustomEvent('hubnav:after', { detail: { url: final.href } }));
    }

    async function go(url, { push = true, scrollY = 0 } = {}) {
        const mine = ++state.token;
        progress(true);
        try {
            const { final, doc } = await load(url);
            if (mine !== state.token) return;
            if (push) history.replaceState({ hn: 1, y: window.scrollY }, '');
            await swap(doc, final, push, scrollY);
        } catch (err) {
            if (mine === state.token) location.assign(url.href);
            return;
        } finally {
            if (mine === state.token) progress(false);
        }
    }

    window.hubNavigate = (href, opts = {}) => {
        const url = new URL(href, location.href);
        if (!sameOrigin(url) || !isHub(url)) { location.assign(url.href); return Promise.resolve(); }
        cache.delete(url.href);
        return go(url, { push: !opts.replace });
    };

    /* ---- wiring ---- */
    if (!document.body.classList.contains('hub-body')) return;
    history.replaceState({ hn: 1 }, '');

    document.addEventListener('click', (e) => {
        const u = eligible(e.target.closest && e.target.closest('a'), e);
        if (!u) return;
        e.preventDefault();
        go(u);
    });

    // GET forms in the page (search, per-page) -> soft
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || e.defaultPrevented || form.hasAttribute('data-no-soft')) return;
        if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get' || !form.closest(PAGE)) return;
        const url = new URL(form.getAttribute('action') || location.href, location.href);
        if (!sameOrigin(url) || !isHub(url)) return;
        e.preventDefault();
        url.search = new URLSearchParams(new FormData(form)).toString();
        go(url);
    });

    const hover = (e) => { const u = eligible(e.target.closest && e.target.closest('a')); if (u) prefetch(u); };
    document.addEventListener('mouseover', hover, { passive: true });
    document.addEventListener('touchstart', hover, { passive: true });

    window.addEventListener('popstate', (e) => {
        if (!e.state || !e.state.hn) return;
        go(new URL(location.href), { push: false, scrollY: e.state.y || 0 });
    });
})();
