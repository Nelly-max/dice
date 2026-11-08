/*
|--------------------------------------------------------------------------
| Product shops: category / offer pages and home page offer bits
|--------------------------------------------------------------------------
| (Home Market, Liquor Hub, Glam Room, Farm Market — views in resources/views/shop)
|
|  [data-countdown="ISO date"]  "Ends in 2d 04:13:09", ticking; "Ended" after
|  [data-sp-more]               "Load more": fetches the next page and appends its cards
|                               to [data-sp-grid] (falls back to a normal link)
|  [data-sp-filters-open/close] phone filters sheet (#spFilters)
|  [data-sp-expand="id"]        "See all" on the category tiles
|
| Soft navigation re-imports this file after each swap: clicks are delegated once
| (window flag) and the countdown timer is restarted for the new page.
*/

const pad = (n) => String(n).padStart(2, '0');

function tick() {
    const now = Date.now();
    document.querySelectorAll('[data-countdown]').forEach((el) => {
        const end = Date.parse(el.dataset.countdown);
        if (!end) return;
        let s = Math.floor((end - now) / 1000);
        if (s <= 0) {
            el.textContent = 'Ended';
            el.classList.add('is-ended');
            return;
        }
        const d = Math.floor(s / 86400); s %= 86400;
        const h = Math.floor(s / 3600); s %= 3600;
        const m = Math.floor(s / 60); s %= 60;
        el.textContent = 'Ends in ' + (d ? d + 'd ' : '') + pad(h) + ':' + pad(m) + ':' + pad(s);
    });
}

function startCountdowns() {
    clearInterval(window.__spTimer);
    if (!document.querySelector('[data-countdown]')) return;
    tick();
    window.__spTimer = setInterval(() => {
        if (!document.querySelector('[data-countdown]')) { clearInterval(window.__spTimer); return; }
        tick();
    }, 1000);
}

async function loadMore(link) {
    const grid = document.querySelector('[data-sp-grid]');
    if (!grid || link.dataset.loading) return;
    link.dataset.loading = '1';
    link.classList.add('is-loading');
    const label = link.innerHTML;
    link.textContent = 'Loading…';

    try {
        const res = await fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!res.ok) throw new Error(res.status);
        const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
        const cards = doc.querySelector('[data-sp-grid]');
        cards?.children && [...cards.children].forEach((c) => grid.appendChild(document.importNode(c, true)));

        const next = doc.querySelector('[data-sp-more]');
        if (next) {
            link.href = next.href;
            link.innerHTML = label;
            delete link.dataset.loading;
            link.classList.remove('is-loading');
        } else {
            link.closest('.sp-more-wrap')?.remove();
        }
        history.replaceState(history.state, '', link.href);
    } catch (e) {
        window.location.href = link.href;     // plain page change as a fallback
    }
}

/*
 * Phone filters sheet. While open, the sheet and its scrim are moved under <body>: inside the
 * page wrapper the sticky site header was drawn over them whatever their z-index. They go
 * back to their place in the page when closed (on wide screens the sheet is the sidebar).
 */
function toggleFilters(open) {
    const panel = document.getElementById('spFilters');
    const scrim = document.querySelector('.sp-scrim');
    if (!panel) return;

    if (open && panel.parentElement !== document.body) {
        panel.__spHome = { parent: panel.parentElement, next: panel.nextSibling };
        document.body.append(panel);
        if (scrim) { scrim.__spHome = { parent: scrim.parentElement, next: scrim.nextSibling }; document.body.append(scrim); }
        void panel.offsetWidth;            // let the slide-up transition run
    }

    panel.classList.toggle('is-open', open);
    if (scrim) scrim.hidden = !open;
    document.documentElement.classList.toggle('sp-lock', open);

    if (!open && panel.__spHome) {
        const back = () => {
            for (const el of [panel, scrim]) {
                if (el?.__spHome?.parent?.isConnected) el.__spHome.parent.insertBefore(el, el.__spHome.next);
                if (el) delete el.__spHome;
            }
        };
        setTimeout(back, 260);
    }
}

function bindOnce() {
    if (window.__spBound) return;
    window.__spBound = true;

    document.addEventListener('click', (e) => {
        const more = e.target.closest('[data-sp-more]');
        if (more) { e.preventDefault(); loadMore(more); return; }

        if (e.target.closest('[data-sp-filters-open]')) { toggleFilters(true); return; }
        if (e.target.closest('[data-sp-filters-close]')) { toggleFilters(false); return; }

        const expand = e.target.closest('[data-sp-expand]');
        if (expand) {
            const grid = document.getElementById(expand.dataset.spExpand);
            const open = grid?.classList.toggle('is-expanded');
            expand.setAttribute('aria-expanded', open ? 'true' : 'false');
            const span = expand.querySelector('span');
            if (span) span.textContent = open ? 'Show less' : 'See all';
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') toggleFilters(false);
    });

    document.addEventListener('softnav:before', () => {
        clearInterval(window.__spTimer);
        toggleFilters(false);
    });
}

function init() {
    bindOnce();
    startCountdowns();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
