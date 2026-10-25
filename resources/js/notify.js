/*
 |--------------------------------------------------------------------------
 | Toast notifications
 |--------------------------------------------------------------------------
 | window.notify.success('Added to cart')
 | window.notify.error('Could not save')
 | window.notify.info('Heads up')
 | const t = window.notify.loading('Saving…');  ...  t.success('Saved') / t.error('Failed') / t.close()
 | window.notify('Message', 'success' | 'error' | 'info' | 'loading', { title, duration })
 |
 | Server messages: partials/notify-flash.blade.php drops the session flashes
 | into <div data-notify-flash='[...]'>; they are shown on load and after a
 | soft navigation.
 |
 | Forms: <form data-notify-loading="Signing you in…"> shows a waiting toast
 | while the browser submits.
 */

(() => {
    if (window.notify) return;           // loaded once, even if the bundle is re-imported

    const ICONS = {
        success: 'fa-solid fa-circle-check',
        error: 'fa-solid fa-circle-exclamation',
        info: 'fa-solid fa-circle-info',
        warning: 'fa-solid fa-triangle-exclamation',
        loading: 'fa-solid fa-spinner fa-spin',
    };
    const TITLES = { success: 'Success', error: 'Failed', info: 'Notice', warning: 'Heads up', loading: 'Please wait' };
    const DURATION = { success: 3500, info: 4000, warning: 5000, error: 6000, loading: 0 };

    const CSS = `
.sm-toasts{position:fixed;z-index:100000;top:var(--sm-toast-top,86px);right:20px;display:flex;flex-direction:column;gap:10px;width:min(380px,calc(100vw - 32px));pointer-events:none;font-family:inherit}
.sm-toast{--c:#16a34a;--soft:#e8f7ee;position:relative;display:flex;align-items:flex-start;gap:12px;padding:12px 12px 14px 14px;border-radius:14px;background:#fff;color:#14281c;
 box-shadow:0 12px 32px -10px rgba(16,24,20,.28),0 2px 6px rgba(16,24,20,.06);border:1px solid rgba(20,40,28,.06);overflow:hidden;pointer-events:auto;
 transform:translateY(-10px) scale(.98);opacity:0;transition:transform .28s cubic-bezier(.2,.8,.2,1),opacity .28s ease}
.sm-toast.is-in{transform:none;opacity:1}
.sm-toast.is-out{transform:translateX(24px);opacity:0}
.sm-toast[data-type="error"]{--c:#dc2626;--soft:#fdecec}
.sm-toast[data-type="info"]{--c:#2563eb;--soft:#e9f0fe}
.sm-toast[data-type="warning"]{--c:#d97706;--soft:#fdf3e2}
.sm-toast[data-type="loading"]{--c:#ea8a0c;--soft:#fff3e0}
.sm-toast-icon{flex:0 0 34px;height:34px;border-radius:50%;display:grid;place-items:center;background:var(--soft);color:var(--c);font-size:16px}
.sm-toast-body{flex:1;min-width:0;padding-top:1px}
.sm-toast-title{display:block;font-weight:800;font-size:.9rem;line-height:1.25;margin:0 0 2px}
.sm-toast-msg{display:block;font-size:.85rem;line-height:1.4;color:#4b5d52;font-weight:600;word-wrap:break-word}
.sm-toast-close{flex:0 0 28px;height:28px;border:0;border-radius:8px;background:transparent;color:#7a8a80;cursor:pointer;display:grid;place-items:center;font-size:14px;margin:-2px -2px 0 0}
.sm-toast-close:hover{background:rgba(0,0,0,.05);color:#14281c}
.sm-toast-bar{position:absolute;left:0;bottom:0;height:3px;width:100%;background:var(--c);opacity:.85;transform-origin:left;animation:sm-toast-bar linear forwards}
.sm-toast:hover .sm-toast-bar{animation-play-state:paused}
@keyframes sm-toast-bar{from{transform:scaleX(1)}to{transform:scaleX(0)}}
body.dark-mode .sm-toast{background:#18211c;color:#e7efe9;border-color:rgba(255,255,255,.07);box-shadow:0 14px 34px -10px rgba(0,0,0,.6)}
body.dark-mode .sm-toast-msg{color:#a9b8ae}
body.dark-mode .sm-toast-close{color:#94a39a}
body.dark-mode .sm-toast-close:hover{background:rgba(255,255,255,.07);color:#fff}
body.dark-mode .sm-toast-icon{background:color-mix(in srgb,var(--c) 18%,transparent)}
@media (max-width:640px){
 .sm-toasts{top:calc(10px + env(safe-area-inset-top));left:10px;right:10px;width:auto}
 .sm-toast{transform:translateY(-16px)}
 .sm-toast.is-out{transform:translateY(-16px)}
}
@media (prefers-reduced-motion:reduce){.sm-toast,.sm-toast.is-out{transition:opacity .15s}.sm-toast-bar{display:none}}
`;

    let stack;
    function host() {
        if (stack && document.body.contains(stack)) return stack;
        if (!document.getElementById('sm-toast-css')) {
            const st = document.createElement('style');
            st.id = 'sm-toast-css';
            st.textContent = CSS;
            document.head.appendChild(st);
        }
        stack = document.createElement('div');
        stack.className = 'sm-toasts';
        stack.setAttribute('role', 'region');
        stack.setAttribute('aria-label', 'Notifications');
        document.body.appendChild(stack);
        return stack;
    }

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    function show(message, type = 'info', opts = {}) {
        if (!ICONS[type]) type = 'info';
        const el = document.createElement('div');
        let timer = null;
        let closed = false;

        const render = (t, msg, o = {}) => {
            type = t;
            el.className = 'sm-toast' + (el.classList.contains('is-in') ? ' is-in' : '');
            el.dataset.type = t;
            el.setAttribute('role', t === 'error' ? 'alert' : 'status');
            el.setAttribute('aria-live', t === 'error' ? 'assertive' : 'polite');
            const title = o.title ?? TITLES[t];
            el.innerHTML =
                `<span class="sm-toast-icon"><i class="${ICONS[t]}" aria-hidden="true"></i></span>` +
                `<span class="sm-toast-body"><strong class="sm-toast-title">${esc(title)}</strong>` +
                (msg ? `<span class="sm-toast-msg">${esc(msg)}</span>` : '') + `</span>` +
                (t === 'loading' ? '' : `<button type="button" class="sm-toast-close" aria-label="Dismiss"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>`);
            el.querySelector('.sm-toast-close')?.addEventListener('click', close);

            clearTimeout(timer);
            const ms = o.duration ?? DURATION[t];
            if (ms > 0) {
                const bar = document.createElement('span');
                bar.className = 'sm-toast-bar';
                bar.style.animationDuration = ms + 'ms';
                bar.addEventListener('animationend', close);
                el.appendChild(bar);
            }
        };

        function close() {
            if (closed) return;
            closed = true;
            clearTimeout(timer);
            el.classList.add('is-out');
            setTimeout(() => el.remove(), 280);
        }

        render(type, message, opts);
        const box = host();
        box.appendChild(el);
        while (box.children.length > 4) box.firstElementChild.remove();
        requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-in')));

        return {
            el,
            close,
            update: (msg, t = type, o = {}) => { if (!closed) render(t, msg, o); },
            success: (msg, o = {}) => { if (!closed) render('success', msg, o); },
            error: (msg, o = {}) => { if (!closed) render('error', msg, o); },
            info: (msg, o = {}) => { if (!closed) render('info', msg, o); },
        };
    }

    const notify = (message, type, opts) => show(message, type, opts);
    notify.success = (m, o) => show(m, 'success', o);
    notify.error = (m, o) => show(m, 'error', o);
    notify.info = (m, o) => show(m, 'info', o);
    notify.warning = (m, o) => show(m, 'warning', o);
    notify.loading = (m, o) => show(m, 'loading', o);
    window.notify = notify;

    /* ---- session flashes rendered by the server ---- */
    function readFlashes() {
        document.querySelectorAll('[data-notify-flash]').forEach((node) => {
            let list = [];
            try { list = JSON.parse(node.getAttribute('data-notify-flash') || '[]'); } catch (e) {}
            node.remove();
            list.forEach((f, i) => setTimeout(() => show(f.message, f.type, f.title ? { title: f.title } : {}), i * 120));
        });
    }

    // soft navigation keeps these listeners (softnav removes page listeners between pages)
    const on = (t, type, fn, o) => (window.__snAdd ? window.__snAdd(t, type, fn, o) : t.addEventListener(type, fn, o));
    const keepStack = () => { if (stack && !document.body.contains(stack)) document.body.appendChild(stack); readFlashes(); };

    /* ---- waiting toast while a form submits ---- */
    on(document, 'submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-notify-loading')) return;
        // let other submit handlers veto first (validation etc.)
        setTimeout(() => {
            if (e.defaultPrevented || form._notifyBusy) return;
            form._notifyBusy = show(form.getAttribute('data-notify-loading') || 'Working on it…', 'loading');
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach((b) => b.setAttribute('aria-busy', 'true'));
        }, 0);
    });
    // back/forward cache: clear stale waiting toasts
    on(window, 'pageshow', (e) => {
        if (!e.persisted) return;
        document.querySelectorAll('.sm-toast[data-type="loading"]').forEach((t) => t.remove());
        document.querySelectorAll('form').forEach((f) => { f._notifyBusy = null; });
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', readFlashes);
    else readFlashes();
    on(document, 'softnav:after', keepStack);
    on(document, 'hubnav:after', keepStack);
})();
