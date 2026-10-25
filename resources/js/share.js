/*
 |--------------------------------------------------------------------------
 | Share sheet
 |--------------------------------------------------------------------------
 | Any element with [data-share] opens a sheet with WhatsApp, Instagram,
 | TikTok, Facebook, X, SMS, Email, "More apps" (the phone's own share
 | menu, when available) and Copy link.
 |
 |   <button data-share data-share-title="…" data-share-text="…" data-share-url="…">
 |   Add data-share-open to also offer "Open link" (opens the url in a new tab).
 |
 | Url defaults to the current page. Also available as window.openShareSheet({title, text, url, open}).
 | Loaded on every page through reused.js.
 */

(() => {
    if (window.openShareSheet) return;

    const CSS = `
.sm-share-scrim{position:fixed;inset:0;z-index:100002;background:rgba(10,14,12,.45);opacity:0;transition:opacity .2s ease}
.sm-share-scrim.is-in{opacity:1}
.sm-share-sheet{position:fixed;z-index:100003;left:50%;bottom:0;width:min(30rem,100%);box-sizing:border-box;transform:translate(-50%,105%);
 padding:.6rem 1.1rem calc(1.1rem + env(safe-area-inset-bottom));border-radius:1.25rem 1.25rem 0 0;background:#fff;color:#17201b;
 font-family:"Nunito",system-ui,sans-serif;box-shadow:0 -10px 40px rgba(0,0,0,.18);transition:transform .28s cubic-bezier(.2,.8,.2,1)}
.sm-share-sheet.is-in{transform:translate(-50%,0)}
@media (min-width:640px){.sm-share-sheet{bottom:50%;border-radius:1.25rem;transform:translate(-50%,calc(50% + 2rem));opacity:0;transition:transform .25s ease,opacity .2s ease}
 .sm-share-sheet.is-in{transform:translate(-50%,50%);opacity:1}}
.sm-share-grip{width:2.6rem;height:.3rem;margin:0 auto .7rem;border-radius:999px;background:#d9dfdb}
@media (min-width:640px){.sm-share-grip{display:none}}
.sm-share-head{display:flex;align-items:center;gap:.75rem;margin:0 0 1rem}
.sm-share-head div{flex:1;min-width:0}
.sm-share-head strong{display:block;font-size:1.05rem;font-weight:900}
.sm-share-head small{display:block;color:#5b6b62;font-size:.8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.sm-share-x{display:grid;place-items:center;width:2.2rem;height:2.2rem;border:0;border-radius:50%;background:#f1f4f2;color:#5b6b62;cursor:pointer;font-size:.95rem}
.sm-share-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:.9rem .4rem;margin:0 0 1rem}
.sm-share-opt{display:flex;flex-direction:column;align-items:center;gap:.4rem;padding:0;border:0;background:none;color:#17201b;text-decoration:none;cursor:pointer;font:inherit;font-size:.74rem;font-weight:700}
.sm-share-opt span{display:grid;place-items:center;width:3.1rem;height:3.1rem;border-radius:50%;color:#fff;font-size:1.35rem;transition:transform .15s ease}
.sm-share-opt:hover span{transform:translateY(-2px)}
.sm-share-copy{display:flex;align-items:center;gap:.5rem;padding:.4rem .4rem .4rem .85rem;border:1px solid #dce5df;border-radius:.8rem;background:#f7faf8}
.sm-share-copy input{flex:1;min-width:0;border:0;background:none;color:#5b6b62;font:inherit;font-size:.82rem;outline:0}
.sm-share-copy button{flex:none;height:2.2rem;padding:0 .9rem;border:0;border-radius:.6rem;background:#16a34a;color:#fff;font:inherit;font-size:.82rem;font-weight:800;cursor:pointer}
body.dark-mode .sm-share-sheet{background:#18211c;color:#e7efe9}
body.dark-mode .sm-share-head small{color:#a9b8ae}
body.dark-mode .sm-share-x{background:#243028;color:#c5d2ca}
body.dark-mode .sm-share-opt{color:#e7efe9}
body.dark-mode .sm-share-copy{background:#111814;border-color:#2b3a31}
body.dark-mode .sm-share-copy input{color:#a9b8ae}
@media (prefers-reduced-motion:reduce){.sm-share-sheet,.sm-share-scrim{transition:none}}
`;

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const toast = (msg, type = 'success', title) => (window.notify ? window.notify(msg, type, title ? { title } : {}) : null);

    async function copy(text) {
        try { await navigator.clipboard.writeText(text); return true; } catch (e) { /* fall back below */ }
        const t = document.createElement('textarea');
        t.value = text; t.setAttribute('readonly', ''); t.style.cssText = 'position:fixed;opacity:0;top:0;left:0';
        document.body.appendChild(t); t.select();
        let ok = false; try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
        t.remove();
        return ok;
    }

    let open = null;

    function close() {
        if (!open) return;
        const { sheet, scrim, onKey, back } = open;
        open = null;
        sheet.classList.remove('is-in'); scrim.classList.remove('is-in');
        document.removeEventListener('keydown', onKey);
        setTimeout(() => { sheet.remove(); scrim.remove(); }, 280);
        back?.focus?.();
    }

    window.openShareSheet = function ({ title = document.title, text = '', url = location.href, open: offerOpen = false } = {}) {
        close();
        if (!document.getElementById('sm-share-css')) {
            const st = document.createElement('style'); st.id = 'sm-share-css'; st.textContent = CSS; document.head.appendChild(st);
        }

        const u = encodeURIComponent(url);
        const msg = encodeURIComponent(text ? `${title} – ${text}` : title);
        const msgUrl = encodeURIComponent(`${text ? `${title} – ${text}` : title} ${url}`);

        const options = [
            { key: 'whatsapp',  label: 'WhatsApp',  icon: 'fa-brands fa-whatsapp',      bg: '#25d366', href: `https://wa.me/?text=${msgUrl}` },
            { key: 'instagram', label: 'Instagram', icon: 'fa-brands fa-instagram',     bg: '#d62976' },
            { key: 'facebook',  label: 'Facebook',  icon: 'fa-brands fa-facebook-f',    bg: '#1877f2', href: `https://www.facebook.com/sharer/sharer.php?u=${u}` },
            { key: 'x',         label: 'X',         icon: 'fa-brands fa-x-twitter',     bg: '#111111', href: `https://twitter.com/intent/tweet?url=${u}&text=${msg}` },
            { key: 'tiktok',    label: 'TikTok',    icon: 'fa-brands fa-tiktok',        bg: '#000000' },
            { key: 'sms',       label: 'SMS',       icon: 'fa-solid fa-comment-sms',    bg: '#0ea5e9', href: `sms:?&body=${msgUrl}` },
            { key: 'email',     label: 'Email',     icon: 'fa-solid fa-envelope',       bg: '#6b7280', href: `mailto:?subject=${encodeURIComponent(title)}&body=${msgUrl}` },
        ];
        if (navigator.share) options.push({ key: 'more', label: 'More apps', icon: 'fa-solid fa-ellipsis', bg: '#16a34a' });
        if (offerOpen) options.unshift({ key: 'open', label: 'Open link', icon: 'fa-solid fa-arrow-up-right-from-square', bg: '#0f766e', href: url });

        const scrim = document.createElement('div');
        scrim.className = 'sm-share-scrim';

        const sheet = document.createElement('div');
        sheet.className = 'sm-share-sheet';
        sheet.setAttribute('role', 'dialog');
        sheet.setAttribute('aria-modal', 'true');
        sheet.setAttribute('aria-label', 'Share');
        sheet.innerHTML =
            `<div class="sm-share-grip" aria-hidden="true"></div>` +
            `<div class="sm-share-head"><div><strong>Share</strong><small>${esc(title)}</small></div>` +
            `<button type="button" class="sm-share-x" aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div>` +
            `<div class="sm-share-grid">` +
            options.map((o) => o.href
                ? `<a class="sm-share-opt" data-key="${o.key}" href="${o.href}" target="_blank" rel="noopener"><span style="background:${o.bg}"><i class="${o.icon}" aria-hidden="true"></i></span>${o.label}</a>`
                : `<button type="button" class="sm-share-opt" data-key="${o.key}"><span style="background:${o.bg}"><i class="${o.icon}" aria-hidden="true"></i></span>${o.label}</button>`
            ).join('') +
            `</div>` +
            `<div class="sm-share-copy"><input type="text" readonly value="${esc(url)}" aria-label="Link"><button type="button" data-key="copy">Copy link</button></div>`;

        document.body.append(scrim, sheet);

        const onKey = (e) => { if (e.key === 'Escape') close(); };
        open = { sheet, scrim, onKey, back: document.activeElement };
        document.addEventListener('keydown', onKey);
        scrim.addEventListener('click', close);
        sheet.querySelector('.sm-share-x').addEventListener('click', close);

        sheet.addEventListener('click', async (e) => {
            const el = e.target.closest('[data-key]');
            if (!el) return;
            const key = el.dataset.key;

            if (key === 'copy') {
                e.preventDefault();
                if (await copy(url)) { toast('Paste it anywhere to share.', 'success', 'Link copied'); close(); }
                else { sheet.querySelector('.sm-share-copy input').select(); toast('Press and hold the link to copy it.', 'info', 'Copy link'); }
                return;
            }
            if (key === 'instagram' || key === 'tiktok') {
                // neither app accepts a link from a website: copy it, then open the app to paste it
                e.preventDefault();
                await copy(url);
                const app = key === 'instagram'
                    ? { name: 'Instagram', where: 'an Instagram chat or story', url: 'https://www.instagram.com/direct/inbox/' }
                    : { name: 'TikTok', where: 'a TikTok message or bio', url: 'https://www.tiktok.com/messages' };
                toast(`Paste it in ${app.where}.`, 'success', 'Link copied');
                window.open(app.url, '_blank', 'noopener');
                close();
                return;
            }
            if (key === 'more') {
                e.preventDefault();
                try { await navigator.share({ title, text, url }); close(); } catch (err) { /* closed the menu */ }
                return;
            }
            setTimeout(close, 150);   // links open in a new tab / app
        });

        requestAnimationFrame(() => requestAnimationFrame(() => { scrim.classList.add('is-in'); sheet.classList.add('is-in'); }));
        sheet.querySelector('.sm-share-opt')?.focus({ preventScroll: true });
    };

    const on = (t, type, fn) => (window.__snAdd ? window.__snAdd(t, type, fn) : t.addEventListener(type, fn));
    on(document, 'click', (e) => {
        const btn = e.target.closest && e.target.closest('[data-share]');
        if (!btn) return;
        e.preventDefault();
        window.openShareSheet({
            title: btn.dataset.shareTitle || document.title,
            text: btn.dataset.shareText || '',
            url: btn.dataset.shareUrl || btn.getAttribute('href') || location.href,
            open: btn.hasAttribute('data-share-open'),
        });
    });
})();
