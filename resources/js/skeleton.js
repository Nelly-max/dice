/*
|--------------------------------------------------------------------------
| Skeleton loading, every page
|--------------------------------------------------------------------------
| 1. Moving to another page (a soft navigation, or a full page load after a link click or a
|    form send): the page's content is swapped for grey placeholder blocks shaped like what is
|    coming (a product page, or a page of cards), under the same header, until the new page
|    shows. A quick page (under ~150 ms) never flashes it.
| 2. Pictures that are still downloading (cards, banners, shop and category tiles) shimmer
|    until they arrive, on the first visit to any page too.
|
| Opt a link out with data-no-skeleton. Loaded from partials/ui-controls.blade.php.
*/

if (!window.__pageSkeleton) {
    window.__pageSkeleton = true;

    // listeners that must outlive soft navigation: softnav.js removes the ones page scripts add
    // to document / window when it swaps pages, except those added through window.__snAdd
    const on = (target, type, fn, opts) => (window.__snAdd ? window.__snAdd(target, type, fn, opts) : target.addEventListener(type, fn, opts));

    const DELAY = 150;
    let timer = null;
    let failsafe = null;
    let layer = null;
    let hiddenMain = null;

    const hubPage = () => document.body.classList.contains('hub-body');
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const card = () => `
        <div class="sk-card"><span class="sk sk-thumb"></span><span class="sk sk-line sk-w60"></span><span class="sk sk-line sk-w90"></span><span class="sk sk-line sk-w40"></span></div>`;

    /* the Hub: its own cards (white, rounded, label / value rows), shaped like the page coming */
    const hubCard = (inner, cls = '') => `<div class="sk-hub-card ${cls}">${inner}</div>`;
    const hubRows = (n) => '<div class="sk-hub-rows">' + '<div class="sk-hub-row"><span class="sk sk-line sk-w30"></span><span class="sk sk-field"></span></div>'.repeat(n) + '</div>';
    function hubTemplate(path) {
        const p = path.replace(/^\/hub/i, '') || '/';
        const head = '<div class="sk-hub-head"><span class="sk sk-heading"></span><span class="sk sk-line sk-w40"></span></div>';

        // a list: deliveries, referrals, orders, finance, notifications
        if (/^\/(deliveries|referrals|orders|account\/finance|notifications|sales|payouts)\b/i.test(p) && !/\/\d+/.test(p)) {
            return head
                + `<div class="sk-hub-stats">${hubCard('<span class="sk sk-dot"></span><span class="sk sk-line sk-w50"></span><span class="sk sk-num"></span>', 'is-stat').repeat(4)}</div>`
                + hubCard('<div class="sk-hub-cardhead"><span class="sk sk-line sk-w30"></span><span class="sk sk-pill"></span></div>'
                    + '<div class="sk-hub-table">' + '<div class="sk-hub-tr"><span class="sk sk-line"></span><span class="sk sk-line"></span><span class="sk sk-line"></span><span class="sk sk-line"></span></div>'.repeat(7) + '</div>');
        }

        // the map / navigation
        if (/^\/(navigate|delivery-map|map)\b/i.test(p)) {
            return '<span class="sk sk-hub-map"></span>';
        }

        // one delivery / referral, the account, rider and marketer pages, applications: cards of rows
        if (/^\/(account|team|profile|settings|deliveries\/|referrals\/)/i.test(p)) {
            return hubCard('<div class="sk-hub-profile"><span class="sk sk-avatar"></span><div><span class="sk sk-title"></span><span class="sk sk-line sk-w40"></span></div></div>'
                    + '<div class="sk-hub-shortcuts">' + '<span class="sk sk-shortcut"></span>'.repeat(3) + '</div>')
                + hubCard('<span class="sk sk-line sk-w30"></span>' + hubRows(6) + '<span class="sk sk-btn"></span>');
        }

        // the Hub home: greeting, stats, two cards
        return head
            + `<div class="sk-hub-stats">${hubCard('<span class="sk sk-dot"></span><span class="sk sk-line sk-w50"></span><span class="sk sk-num"></span>', 'is-stat').repeat(4)}</div>`
            + '<div class="sk-hub-two">'
            + hubCard('<span class="sk sk-line sk-w30"></span><span class="sk sk-chart"></span>')
            + hubCard('<span class="sk sk-line sk-w40"></span>' + '<div class="sk-hub-item"><span class="sk sk-dot"></span><div><span class="sk sk-line sk-w60"></span><span class="sk sk-line sk-w40"></span></div></div>'.repeat(4))
            + '</div>';
    }

    function template(url) {
        const path = (url && url.pathname) || location.pathname;
        if (hubPage()) return hubTemplate(path);

        // a product / stay / listing page
        if (/\/(product|stay|listing|property)\//i.test(path)) {
            return `
                <div class="sk-pdp">
                    <span class="sk sk-media"></span>
                    <div class="sk-pdp-text">
                        <span class="sk sk-line sk-w30"></span>
                        <span class="sk sk-title"></span>
                        <span class="sk sk-line sk-w50"></span>
                        <span class="sk sk-price"></span>
                        <span class="sk sk-btn"></span>
                        <span class="sk sk-box"></span>
                    </div>
                </div>
                <span class="sk sk-heading"></span>
                <div class="sk-grid sk-grid--row">${card().repeat(6)}</div>`;
        }

        // a shop's home page: banner, tiles, rows
        const segments = path.split('/').filter(Boolean);
        const home = segments.length <= 1;
        return `
            ${home ? '<span class="sk sk-hero"></span>' : '<span class="sk sk-line sk-w20"></span><span class="sk sk-band"></span>'}
            <div class="sk-chips">${'<span class="sk sk-chip"></span>'.repeat(home ? 7 : 5)}</div>
            ${home ? '<span class="sk sk-heading"></span>' : ''}
            <div class="sk-grid">${card().repeat(home ? 6 : 12)}</div>`;
    }

    function show(url) {
        if (layer) return;
        // Hub: only the content area (its sidebar and top bar stay); shops: the page content
        const main = hubPage()
            ? (document.querySelector('.hub-page > .hub-content') || document.querySelector('.hub-page > *'))
            : document.querySelector('body > main, body > .wrapper, main');
        if (!main) return;
        // take the content's exact place on the page (its width and left edge)
        const r = main.getBoundingClientRect();
        const pr = main.parentElement.getBoundingClientRect();
        layer = document.createElement('div');
        layer.className = 'sk-page' + (hubPage() ? ' sk-page--hub' : '') + (reduce ? ' is-still' : '');
        if (hubPage()) {
            // same padding as the Hub page it stands in for; account and form pages are narrower
            layer.style.padding = getComputedStyle(main).padding;
            if (/^\/hub\/(account|team|profile|settings)/i.test((url && url.pathname) || '')) {
                layer.style.maxWidth = '880px';
                layer.style.margin = '0 auto';
                layer.style.width = '100%';
            }
        } else if (r.width > 0) {
            layer.style.cssText = `width:${Math.round(r.width)}px;max-width:none;margin:0 0 0 ${Math.max(0, Math.round(r.left - pr.left))}px;`;
        }
        layer.setAttribute('aria-hidden', 'true');
        layer.innerHTML = template(url);
        main.parentNode.insertBefore(layer, main);
        main.classList.add('sk-hidden');
        hiddenMain = main;
        document.documentElement.setAttribute('aria-busy', 'true');
        // never stay up: a cancelled load, a download, a page that never answers
        failsafe = setTimeout(hide, 15000);
        if (window.scrollY > (layer.getBoundingClientRect().top + window.scrollY)) window.scrollTo({ top: 0 });
    }

    function hide() {
        clearTimeout(timer);
        clearTimeout(failsafe);
        timer = null;
        layer?.remove();
        layer = null;
        hiddenMain?.classList.remove('sk-hidden');
        hiddenMain = null;
        document.documentElement.removeAttribute('aria-busy');
    }

    const later = (url) => {
        clearTimeout(timer);
        timer = setTimeout(() => show(url), DELAY);
    };

    window.pageSkeleton = { show, hide };

    // soft navigation (softnav.js) tells us when it starts and when the new page is in
    on(document, 'softnav:before', (e) => {
        let u = null;
        try { u = new URL(e.detail?.url, location.href); } catch (err) {}
        later(u);
    });
    on(document, 'softnav:after', hide);

    // full page loads: a link the soft navigation did not take, or a GET form
    on(document, 'click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest?.('a[href]');
        if (!a || a.hasAttribute('download') || a.hasAttribute('data-no-skeleton') || (a.target && a.target !== '_self')) return;
        let u;
        try { u = new URL(a.href, location.href); } catch (err) { return; }
        if (u.origin !== location.origin || !/^https?:$/.test(u.protocol)) return;
        if (u.pathname === location.pathname && u.search === location.search) return;   // same page / anchor
        if (a.getAttribute('href').startsWith('#') || /^(javascript|mailto|tel):/i.test(a.getAttribute('href'))) return;
        if (/\.(pdf|zip|png|jpe?g|webp|csv|xlsx?|docx?)$/i.test(u.pathname) || /\/(logout|download|export)\b/i.test(u.pathname)) return;
        // after every other click handler: one that keeps the page (opens a sheet, adds to
        // cart, soft navigation) cancels the click, and then nothing shows here
        setTimeout(() => { if (!e.defaultPrevented) later(u); }, 0);
    });
    on(document, 'submit', (e) => {
        const f = e.target;
        if (e.defaultPrevented || f.hasAttribute('data-no-skeleton') || (f.method || 'get').toLowerCase() !== 'get' || f.target) return;
        // after other submit handlers had their say
        setTimeout(() => { if (!e.defaultPrevented) later(new URL(f.action || location.href, location.href)); }, 0);
    });

    // back / forward from the browser's page cache: never leave the placeholders up
    on(window, 'pageshow', hide);

    /* ---------- pictures that are still downloading ---------- */

    const PICS = 'main img, .wrapper img, .hm-near img, .category-icons img, .slider img, .hero img, .sk-wait-here img';
    function watchImg(img) {
        if (img.dataset.skDone) return;
        img.dataset.skDone = '1';
        if (img.complete && img.naturalWidth > 0) return;
        img.classList.add('sk-wait');
        const done = () => img.classList.remove('sk-wait');
        img.addEventListener('load', done, { once: true });
        img.addEventListener('error', done, { once: true });
    }
    const scan = (root = document) => root.querySelectorAll?.(PICS).forEach(watchImg);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => scan());
    else scan();
    on(document, 'softnav:after', () => scan());
    new MutationObserver((records) => {
        for (const r of records) for (const n of r.addedNodes) {
            if (n.nodeType !== 1) continue;
            if (n.tagName === 'IMG') { if (n.matches(PICS)) watchImg(n); }
            else if (n.querySelector('img')) scan(n);
        }
    }).observe(document.documentElement, { childList: true, subtree: true });
}
