/*
|--------------------------------------------------------------------------
| Custom select and date picker for every page
|--------------------------------------------------------------------------
| Loaded on every layout (partials/ui-controls.blade.php). It upgrades, in place:
|
|   <select>              -> a styled dropdown with search (search shows when there are
|                            more than 7 options, or always with data-search; data-search="false" hides it)
|   <input type="date">   -> a styled calendar (month grid, month and year pickers, Today, Clear)
|
| The original element stays in the form (invisible, underneath), so names, values,
| `required`, form submit, `onchange="this.form.submit()"` and existing JS listeners keep
| working: every pick sets the original's value and fires `input` and `change` on it.
| Setting `.value` from JS updates the display too, and options added or removed later
| (e.g. towns loaded for a county) are picked up automatically.
|
| Opt out: data-native on the element (or on a parent). <select multiple> and list boxes
| (size > 1) stay native.
|
| Keyboard: Enter / Space / Down opens, arrows move, Enter picks, Esc closes, typing searches.
| Calendar: arrows move by day / week, PageUp / PageDown by month, Enter picks.
*/

if (!window.__uiControls) {
    window.__uiControls = true;

    const SEARCH_FROM = 8;               // options before the search box shows
    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const MON = MONTHS.map((m) => m.slice(0, 3));
    const DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];
    const phone = () => window.matchMedia('(max-width: 560px)').matches;
    let uid = 0;
    let openCtl = null;                  // the one control that is open

    const skip = (el) => el.closest('[data-native]') || el.dataset.uiDone === '1';
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const fire = (el) => {
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    /* the field's name for the popup title and screen readers: <label for>, aria-label,
       the wrapping <label>, or a heading / label just before it (Hub forms use <h4>) */
    function fieldTitle(el, fallback) {
        const byFor = el.id && document.querySelector(`label[for="${CSS.escape(el.id)}"]`);
        if (byFor) return byFor.textContent.trim();
        if (el.getAttribute('aria-label')) return el.getAttribute('aria-label');
        const wrapLabel = el.closest('label');
        if (wrapLabel) {
            const t = (wrapLabel.querySelector('span, b, strong')?.textContent || wrapLabel.childNodes[0]?.textContent || '').trim();
            if (t) return t;
        }
        let prev = (el.closest('.ui-select, .ui-date') || el).previousElementSibling;
        while (prev && prev.tagName === 'BR') prev = prev.previousElementSibling;
        if (prev && /^(H[1-6]|LABEL|SPAN|P|STRONG|B|SMALL)$/.test(prev.tagName) && prev.textContent.trim().length < 60) return prev.textContent.trim().replace(/\s*\*$/, '');
        return fallback;
    }

    /* keep the custom display in step when page code sets el.value (or selectedIndex) */
    function watchProperty(el, prop, after) {
        const proto = Object.getPrototypeOf(el);
        const desc = Object.getOwnPropertyDescriptor(proto, prop)
            || Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, prop);
        if (!desc || !desc.set) return;
        Object.defineProperty(el, prop, {
            configurable: true,
            get() { return desc.get.call(this); },
            set(v) { desc.set.call(this, v); after(); },
        });
    }

    /*
     * The trigger looks like the field it replaces: copy the box (height, corners, font,
     * padding) and colours the page's CSS gave the original. Colours are copied again when
     * light / dark mode changes.
     */
    const BOX = ['height', 'minHeight', 'borderRadius', 'fontSize', 'fontWeight', 'fontFamily', 'paddingLeft', 'borderTopWidth', 'borderTopStyle'];
    const PAINT = ['backgroundColor', 'color', 'borderTopColor'];
    const painted = new Set();

    // browser defaults for a select / date input (in a shadow root no page CSS reaches),
    // so only what the page's CSS set is copied, not the browser's own grey look
    const defaults = {};
    function browserDefault(el) {
        const key = el.tagName + (el.type || '');
        if (defaults[key]) return defaults[key];
        const host = document.createElement('div');
        host.style.cssText = 'position:absolute;width:0;height:0;overflow:hidden;visibility:hidden';
        document.body.appendChild(host);
        const root = host.attachShadow({ mode: 'open' });
        const twin = document.createElement(el.tagName);
        if (el.tagName === 'INPUT') twin.type = 'date';
        root.appendChild(twin);
        const cs = getComputedStyle(twin);
        defaults[key] = Object.fromEntries([...BOX, ...PAINT].map((p) => [p, cs[p]]));
        host.remove();
        return defaults[key];
    }

    function copyLook(from, to, props) {
        const cs = getComputedStyle(from);
        const ua = browserDefault(from);
        for (const p of props) {
            const v = cs[p];
            if (!v || v === 'auto' || v === 'normal' || v === ua[p]) continue;
            if ((p === 'height' || p === 'minHeight') && (parseFloat(v) < 24 || parseFloat(v) > 80)) continue;
            if (p === 'backgroundColor' && /rgba\(0, 0, 0, 0\)|transparent/.test(v)) continue;
            if (p === 'borderTopWidth') { to.style.borderWidth = v; continue; }
            if (p === 'borderTopStyle') { if (v !== 'none') to.style.borderStyle = v; continue; }
            if (p === 'borderTopColor') { to.style.borderColor = v; continue; }
            to.style[p] = v;
        }
    }

    function adopt(from, to) {
        copyLook(from, to, BOX);
        copyLook(from, to, PAINT);
        painted.add([from, to]);
    }
    new MutationObserver(() => requestAnimationFrame(() => {
        for (const pair of painted) {
            const [from, to] = pair;
            if (!from.isConnected) { painted.delete(pair); continue; }
            to.style.backgroundColor = ''; to.style.color = ''; to.style.borderColor = '';
            from.classList.remove('ui-native');
            copyLook(from, to, PAINT);
            from.classList.add('ui-native');
        }
    })).observe(document.body, { attributes: true, attributeFilter: ['class'] });

    /* position a popup under (or above) its trigger; on phones it is a bottom sheet (CSS) */
    function place(pop, trigger) {
        if (phone()) { pop.style.cssText = ''; return; }
        const r = trigger.getBoundingClientRect();
        // lists are at least as wide as their field; the calendar keeps its own width (CSS)
        if (!pop.classList.contains('ui-cal')) pop.style.minWidth = `${r.width}px`;
        pop.style.left = '0px';
        pop.style.top = '0px';
        const ph = pop.offsetHeight;
        const pw = pop.offsetWidth;
        const below = window.innerHeight - r.bottom;
        const up = below < ph + 12 && r.top > below;
        let left = r.left;
        if (left + pw > window.innerWidth - 8) left = Math.max(8, window.innerWidth - pw - 8);
        pop.style.left = `${left}px`;
        pop.style.top = `${up ? r.top - ph - 6 : r.bottom + 6}px`;
        pop.classList.toggle('is-up', up);
    }

    // a popup opened from inside a modal <dialog> must live in that dialog (top layer)
    const host = (el) => el.closest('dialog[open]') || document.body;

    function closeOpen() {
        if (openCtl) openCtl.close();
    }

    let backdrop = null;
    function showBackdrop(on) {
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'ui-pop-backdrop';
            backdrop.addEventListener('click', closeOpen);
        }
        if (on && phone()) { (openCtl?.host || document.body).appendChild(backdrop); requestAnimationFrame(() => backdrop.classList.add('is-on')); }
        else { backdrop.classList.remove('is-on'); backdrop.remove(); }
    }

    /* ======================================================================
       Select
       ====================================================================== */

    function enhanceSelect(select) {
        if (skip(select) || select.multiple || select.size > 1) return;
        select.dataset.uiDone = '1';
        const id = `ui-sel-${++uid}`;

        const wrap = document.createElement('div');
        wrap.className = 'ui-select';
        if (select.className) wrap.className += ' ' + select.className.split(/\s+/).filter(Boolean).map((c) => `ui-from-${c}`).join(' ');
        const inline = select.getAttribute('style');

        // same width as the select had: full width when it filled its box (or is hidden now)
        const parentW = select.parentElement ? select.parentElement.clientWidth : 0;
        const w = select.offsetWidth;
        const cssW = getComputedStyle(select).width;

        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);
        select.classList.add('ui-native');
        select.tabIndex = -1;
        select.setAttribute('aria-hidden', 'true');

        if (!w || (parentW && w >= parentW - 4) || cssW === '100%') wrap.classList.add('is-block');
        else wrap.style.minWidth = `${w}px`;
        if (inline && /width/.test(inline)) wrap.style.cssText += ';' + (inline.match(/(?:^|;)\s*(?:min-|max-)?width[^;]*/g) || []).join(';');

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ui-select-btn';
        btn.id = id;
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        select.classList.remove('ui-native');
        adopt(select, btn);
        select.classList.add('ui-native');
        btn.innerHTML = '<span class="ui-select-label"></span><svg class="ui-chev" viewBox="0 0 20 20" aria-hidden="true"><path d="M5 7.5l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        wrap.appendChild(btn);

        // the visible label of the original (for screen readers)
        const lbl = select.id && document.querySelector(`label[for="${CSS.escape(select.id)}"]`);
        if (lbl) {
            lbl.id ||= `${id}-lbl`;
            btn.setAttribute('aria-labelledby', `${lbl.id} ${id}`);
            lbl.addEventListener('click', (e) => { e.preventDefault(); btn.focus(); });
        } else if (select.getAttribute('aria-label')) {
            btn.setAttribute('aria-label', select.getAttribute('aria-label'));
        }

        const pop = document.createElement('div');
        pop.className = 'ui-pop ui-select-pop';
        pop.hidden = true;
        pop.innerHTML = `
            <div class="ui-pop-head"><strong></strong><button type="button" class="ui-pop-x" aria-label="Close">&times;</button></div>
            <div class="ui-select-search" hidden>
                <svg viewBox="0 0 20 20" aria-hidden="true"><circle cx="9" cy="9" r="6" fill="none" stroke="currentColor" stroke-width="2"/><path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input type="search" placeholder="Search…" autocomplete="off" aria-label="Search options">
            </div>
            <ul class="ui-select-list" role="listbox" id="${id}-list" tabindex="-1"></ul>
            <p class="ui-select-empty" hidden>No matches</p>`;
        const list = pop.querySelector('ul');
        const search = pop.querySelector('input');
        const searchRow = pop.querySelector('.ui-select-search');
        const empty = pop.querySelector('.ui-select-empty');
        const name = fieldTitle(select, 'Choose');
        pop.querySelector('.ui-pop-head strong').textContent = name;
        if (!lbl && !select.getAttribute('aria-label')) btn.setAttribute('aria-label', name);
        pop.querySelector('.ui-pop-x').addEventListener('click', () => ctl.close(true));
        btn.setAttribute('aria-controls', `${id}-list`);

        let items = [];        // [{ el: li, opt: option }]
        let active = -1;

        function label() {
            const opt = select.options[select.selectedIndex];
            const text = opt ? opt.textContent.trim() : '';
            const placeholder = !opt || opt.value === '';
            btn.querySelector('.ui-select-label').textContent = text || ' ';
            wrap.classList.toggle('is-placeholder', placeholder);
            wrap.classList.toggle('is-disabled', select.disabled);
            btn.disabled = select.disabled;
        }

        function build() {
            list.innerHTML = '';
            items = [];
            const add = (opt, group) => {
                if (opt.hidden) return;
                const li = document.createElement('li');
                li.setAttribute('role', 'option');
                li.id = `${id}-o${items.length}`;
                li.textContent = opt.textContent.trim() || ' ';
                if (group) li.classList.add('in-group');
                if (opt.value === '') li.classList.add('is-placeholder');
                if (opt.disabled) { li.setAttribute('aria-disabled', 'true'); li.classList.add('is-disabled'); }
                const i = items.length;
                li.addEventListener('mousemove', () => highlight(i, false));
                li.addEventListener('click', () => pick(i));
                list.appendChild(li);
                items.push({ el: li, opt });
            };
            for (const child of select.children) {
                if (child.tagName === 'OPTGROUP') {
                    const h = document.createElement('li');
                    h.className = 'ui-select-group';
                    h.setAttribute('role', 'presentation');
                    h.textContent = child.label;
                    list.appendChild(h);
                    for (const o of child.children) add(o, true);
                } else if (child.tagName === 'OPTION') add(child, false);
            }
            const wantSearch = select.dataset.search === 'true' || (select.dataset.search !== 'false' && items.length >= SEARCH_FROM);
            searchRow.hidden = !wantSearch;
            label();
        }

        function marks() {
            items.forEach(({ el, opt }) => {
                el.classList.toggle('is-selected', opt.selected);
                el.setAttribute('aria-selected', opt.selected ? 'true' : 'false');
            });
        }

        function visible() {
            return items.map((x, i) => i).filter((i) => !items[i].el.hidden && !items[i].opt.disabled);
        }

        function highlight(i, scroll = true) {
            items.forEach((x) => x.el.classList.remove('is-active'));
            active = i;
            const it = items[i];
            if (!it) { btn.removeAttribute('aria-activedescendant'); return; }
            it.el.classList.add('is-active');
            search.setAttribute('aria-activedescendant', it.el.id);
            if (scroll) it.el.scrollIntoView({ block: 'nearest' });
        }

        function filter(q) {
            const s = q.trim().toLowerCase();
            let shown = 0;
            items.forEach(({ el, opt }) => {
                const hit = !s || opt.textContent.toLowerCase().includes(s);
                el.hidden = !hit;
                if (hit) shown++;
            });
            list.querySelectorAll('.ui-select-group').forEach((g) => {
                let n = g.nextElementSibling, any = false;
                while (n && !n.classList.contains('ui-select-group')) { if (!n.hidden) any = true; n = n.nextElementSibling; }
                g.hidden = !any;
            });
            empty.hidden = shown > 0;
            const v = visible();
            highlight(v.length ? v[0] : -1);
        }

        function pick(i) {
            const it = items[i];
            if (!it || it.opt.disabled) return;
            const changed = select.value !== it.opt.value || select.selectedIndex !== it.opt.index;
            it.opt.selected = true;
            label();
            ctl.close(true);
            if (changed) fire(select);
        }

        const ctl = {
            open() {
                if (select.disabled) return;
                if (openCtl && openCtl !== ctl) openCtl.close();
                openCtl = ctl;
                marks();
                search.value = '';
                filter('');
                host(btn).appendChild(pop);
                pop.hidden = false;
                place(pop, btn);
                showBackdrop(true);
                wrap.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
                const sel = items.findIndex((x) => x.opt.selected);
                highlight(sel >= 0 ? sel : (visible()[0] ?? -1));
                requestAnimationFrame(() => pop.classList.add('is-on'));
                if (!searchRow.hidden && !phone()) search.focus();
                else list.focus({ preventScroll: true });
            },
            close(focusBack) {
                if (openCtl === ctl) openCtl = null;
                pop.classList.remove('is-on');
                pop.hidden = true;
                pop.remove();
                showBackdrop(false);
                wrap.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
                if (focusBack) btn.focus({ preventScroll: true });
            },
            reposition() { place(pop, btn); },
            get host() { return host(btn); },
        };

        function keys(e) {
            const v = visible();
            const at = v.indexOf(active);
            if (e.key === 'ArrowDown') { e.preventDefault(); highlight(v[Math.min(v.length - 1, at + 1)] ?? v[0]); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(v[Math.max(0, at - 1)] ?? v[0]); }
            else if (e.key === 'Home') { e.preventDefault(); highlight(v[0]); }
            else if (e.key === 'End') { e.preventDefault(); highlight(v[v.length - 1]); }
            else if (e.key === 'Enter') { e.preventDefault(); if (active >= 0) pick(active); }
            else if (e.key === 'Escape') { e.preventDefault(); ctl.close(true); }
            else if (e.key === 'Tab') { ctl.close(false); }
        }

        // type-ahead on the list when there is no search box
        let typed = '', typedAt = 0;
        function typeahead(e) {
            if (e.key.length !== 1 || e.ctrlKey || e.metaKey || e.altKey) return false;
            if (!searchRow.hidden) {
                search.focus();
                return false;
            }
            const now = Date.now();
            typed = now - typedAt > 700 ? e.key.toLowerCase() : typed + e.key.toLowerCase();
            typedAt = now;
            const hit = items.findIndex((x) => !x.opt.disabled && x.opt.textContent.trim().toLowerCase().startsWith(typed));
            if (hit >= 0) highlight(hit);
            return true;
        }

        btn.addEventListener('click', () => (openCtl === ctl ? ctl.close(true) : ctl.open()));
        btn.addEventListener('keydown', (e) => {
            if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) { e.preventDefault(); ctl.open(); }
            else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                // pick by first letters without opening, like a native select
                const now = Date.now();
                typed = now - typedAt > 700 ? e.key.toLowerCase() : typed + e.key.toLowerCase();
                typedAt = now;
                const hit = items.find((x) => !x.opt.disabled && x.opt.value !== '' && x.opt.textContent.trim().toLowerCase().startsWith(typed));
                if (hit && !hit.opt.selected) { hit.opt.selected = true; label(); fire(select); }
            }
        });
        search.addEventListener('input', () => filter(search.value));
        search.addEventListener('keydown', keys);
        list.addEventListener('keydown', (e) => { if (!typeahead(e)) keys(e); });

        // stay in step with the page's own code
        select.addEventListener('change', label);
        select.addEventListener('invalid', () => wrap.classList.add('is-invalid'));
        select.addEventListener('focus', () => btn.focus());
        select.addEventListener('input', () => wrap.classList.remove('is-invalid'));
        select.form?.addEventListener('reset', () => setTimeout(label));
        watchProperty(select, 'value', label);
        watchProperty(select, 'selectedIndex', label);
        new MutationObserver(() => { build(); if (openCtl === ctl) { marks(); filter(search.value); ctl.reposition(); } })
            .observe(select, { childList: true, subtree: true, attributes: true, attributeFilter: ['disabled', 'selected', 'label', 'hidden'] });

        build();
    }

    /* ======================================================================
       Date picker
       ====================================================================== */

    const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    const parse = (s) => {
        const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || '');
        if (!m) return null;
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        return isNaN(d) ? null : d;
    };
    const pretty = (d) => `${d.getDate()} ${MON[d.getMonth()]} ${d.getFullYear()}`;
    const same = (a, b) => a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

    function enhanceDate(input) {
        if (skip(input)) return;
        input.dataset.uiDone = '1';
        const id = `ui-date-${++uid}`;

        const wrap = document.createElement('div');
        wrap.className = 'ui-date';
        const parentW = input.parentElement ? input.parentElement.clientWidth : 0;
        const w = input.offsetWidth;
        if (!w || (parentW && w >= parentW - 4)) wrap.classList.add('is-block');
        else wrap.style.minWidth = `${w}px`;
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        const btn = document.createElement('button');
        adopt(input, btn);

        // keep the value as YYYY-MM-DD in a plain text field underneath (so `required` still works)
        input.type = 'text';
        input.classList.add('ui-native');
        input.tabIndex = -1;
        input.setAttribute('aria-hidden', 'true');
        input.setAttribute('autocomplete', 'off');
        input.inputMode = 'none';
        const placeholder = input.getAttribute('placeholder') || 'Select a date';

        btn.type = 'button';
        btn.className = 'ui-date-btn';
        btn.id = id;
        btn.setAttribute('aria-haspopup', 'dialog');
        btn.setAttribute('aria-expanded', 'false');
        btn.innerHTML = '<svg class="ui-cal-ico" viewBox="0 0 20 20" aria-hidden="true"><rect x="3" y="4.5" width="14" height="12.5" rx="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M3 8.5h14M7 2.8v3.4M13 2.8v3.4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg><span class="ui-date-label"></span>';
        wrap.appendChild(btn);

        const lbl = input.id && document.querySelector(`label[for="${CSS.escape(input.id)}"]`);
        const title = fieldTitle(input, 'Pick a date');
        if (lbl) {
            lbl.id ||= `${id}-lbl`;
            btn.setAttribute('aria-labelledby', `${lbl.id} ${id}`);
            lbl.addEventListener('click', (e) => { e.preventDefault(); btn.focus(); });
        } else btn.setAttribute('aria-label', title);

        const pop = document.createElement('div');
        pop.className = 'ui-pop ui-cal';
        pop.hidden = true;
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', title);
        pop.innerHTML = `
            <div class="ui-pop-head"><strong>${esc(title)}</strong><button type="button" class="ui-pop-x" aria-label="Close">&times;</button></div>
            <div class="ui-cal-nav">
                <button type="button" class="ui-cal-arrow" data-step="-1" aria-label="Previous"><svg viewBox="0 0 20 20"><path d="M12.5 5l-5 5 5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                <button type="button" class="ui-cal-title" aria-live="polite"></button>
                <button type="button" class="ui-cal-arrow" data-step="1" aria-label="Next"><svg viewBox="0 0 20 20"><path d="M7.5 5l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
            <div class="ui-cal-body"></div>
            <div class="ui-cal-foot">
                <button type="button" class="ui-cal-link" data-act="clear">Clear</button>
                <button type="button" class="ui-cal-link is-strong" data-act="today">Today</button>
            </div>`;
        const body = pop.querySelector('.ui-cal-body');
        const titleBtn = pop.querySelector('.ui-cal-title');
        const clearBtn = pop.querySelector('[data-act="clear"]');
        const todayBtn = pop.querySelector('[data-act="today"]');

        let view = 'days';            // days | months | years
        let cursor = new Date();      // month / year shown, and the focused day
        cursor.setHours(0, 0, 0, 0);

        const min = () => parse(input.getAttribute('min'));
        const max = () => parse(input.getAttribute('max'));
        const allowed = (d) => !((min() && d < min()) || (max() && d > max()));

        function label() {
            const d = parse(input.value);
            btn.querySelector('.ui-date-label').textContent = d ? pretty(d) : placeholder;
            wrap.classList.toggle('is-placeholder', !d);
            wrap.classList.toggle('is-disabled', input.disabled);
            btn.disabled = input.disabled || input.readOnly;
        }

        function render() {
            const sel = parse(input.value);
            const today = new Date(); today.setHours(0, 0, 0, 0);
            const y = cursor.getFullYear(), m = cursor.getMonth();
            body.dataset.view = view;

            if (view === 'days') {
                titleBtn.textContent = `${MONTHS[m]} ${y}`;
                const first = new Date(y, m, 1);
                const lead = (first.getDay() + 6) % 7;               // Monday first
                const start = new Date(y, m, 1 - lead);
                let html = '<div class="ui-cal-week">' + DAYS.map((d) => `<span>${d}</span>`).join('') + '</div><div class="ui-cal-grid" role="grid">';
                for (let i = 0; i < 42; i++) {
                    const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
                    if (i === 35 && d.getMonth() !== m) break;        // no empty 6th row
                    const cls = ['ui-day'];
                    if (d.getMonth() !== m) cls.push('is-out');
                    if (same(d, today)) cls.push('is-today');
                    if (same(d, sel)) cls.push('is-selected');
                    if (same(d, cursor)) cls.push('is-focus');
                    const ok = allowed(d);
                    html += `<button type="button" class="${cls.join(' ')}" data-date="${iso(d)}" ${ok ? '' : 'disabled'} tabindex="${same(d, cursor) ? 0 : -1}" aria-label="${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}"${same(d, sel) ? ' aria-pressed="true"' : ''}>${d.getDate()}</button>`;
                }
                body.innerHTML = html + '</div>';
            } else if (view === 'months') {
                titleBtn.textContent = String(y);
                body.innerHTML = '<div class="ui-cal-tiles">' + MON.map((n, i) => {
                    const last = new Date(y, i + 1, 0), firstD = new Date(y, i, 1);
                    const ok = !((min() && last < min()) || (max() && firstD > max()));
                    const cls = ['ui-tile'];
                    if (sel && sel.getFullYear() === y && sel.getMonth() === i) cls.push('is-selected');
                    if (today.getFullYear() === y && today.getMonth() === i) cls.push('is-today');
                    return `<button type="button" class="${cls.join(' ')}" data-month="${i}" ${ok ? '' : 'disabled'}>${n}</button>`;
                }).join('') + '</div>';
            } else {
                // every year that can be picked, newest first, in one scrolling list
                const now = today.getFullYear();
                const top = max() ? max().getFullYear() : now + 20;
                const bottom = min() ? min().getFullYear() : Math.min(top, now) - 100;
                titleBtn.textContent = 'Choose a year';
                let html = '<div class="ui-cal-tiles is-years">';
                for (let yr = top; yr >= bottom; yr--) {
                    const cls = ['ui-tile'];
                    if (sel && sel.getFullYear() === yr) cls.push('is-selected');
                    if (now === yr) cls.push('is-today');
                    if (yr === y) cls.push('is-focus');
                    html += `<button type="button" class="${cls.join(' ')}" data-year="${yr}">${yr}</button>`;
                }
                body.innerHTML = html + '</div>';
                const mark = body.querySelector('.ui-tile.is-focus') || body.querySelector('.ui-tile.is-selected');
                if (mark) requestAnimationFrame(() => { const box = body.querySelector('.is-years'); box.scrollTop = mark.offsetTop - box.clientHeight / 2 + mark.offsetHeight / 2; });
            }

            const prevOk = view === 'days' ? !min() || new Date(y, m, 0) >= min() : !min() || y - 1 >= min().getFullYear();
            const nextOk = view === 'days' ? !max() || new Date(y, m + 1, 1) <= max() : !max() || y + 1 <= max().getFullYear();
            pop.querySelectorAll('.ui-cal-arrow').forEach((a) => { a.hidden = view === 'years'; });
            pop.querySelector('[data-step="-1"]').disabled = !prevOk;
            pop.querySelector('[data-step="1"]').disabled = !nextOk;
            titleBtn.disabled = view === 'years';
            clearBtn.hidden = input.required || !input.value;
            todayBtn.hidden = !allowed(today);
            pop.querySelector('.ui-cal-foot').hidden = clearBtn.hidden && todayBtn.hidden;
            titleBtn.setAttribute('aria-label', view === 'years' ? 'Choose a year' : view === 'months' ? `${y}, choose a month` : `${MONTHS[m]} ${y}, choose a month or year`);
        }

        function focusDay() {
            const b = body.querySelector('.ui-day.is-focus') || body.querySelector('.ui-day:not([disabled])');
            b?.focus({ preventScroll: true });
        }

        function choose(d) {
            const v = iso(d);
            const changed = input.value !== v;
            input.value = v;
            label();
            ctl.close(true);
            wrap.classList.remove('is-invalid');
            if (changed) fire(input);
        }

        function step(n) {
            if (view === 'days') cursor = new Date(cursor.getFullYear(), cursor.getMonth() + n, 1);
            else if (view === 'months') cursor = new Date(cursor.getFullYear() + n, cursor.getMonth(), 1);
            render();
        }

        pop.addEventListener('click', (e) => {
            const t = e.target.closest('button');
            if (!t || t.disabled) return;
            if (t.matches('.ui-pop-x')) ctl.close(true);
            else if (t.dataset.step) step(Number(t.dataset.step));
            else if (t === titleBtn) { view = view === 'days' ? 'months' : 'years'; render(); }
            else if (t.dataset.date) choose(parse(t.dataset.date));
            else if (t.dataset.month) { cursor = new Date(cursor.getFullYear(), Number(t.dataset.month), 1); view = 'days'; render(); focusDay(); }
            else if (t.dataset.year) { cursor = new Date(Number(t.dataset.year), cursor.getMonth(), 1); view = 'months'; render(); }
            else if (t.dataset.act === 'today') choose(new Date());
            else if (t.dataset.act === 'clear') {
                const changed = input.value !== '';
                input.value = '';
                label();
                ctl.close(true);
                if (changed) fire(input);
            }
        });

        pop.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { e.preventDefault(); ctl.close(true); return; }
            if (view !== 'days' || !e.target.classList.contains('ui-day')) return;
            const moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
            let next = null;
            if (moves[e.key] !== undefined) next = new Date(cursor.getFullYear(), cursor.getMonth(), cursor.getDate() + moves[e.key]);
            else if (e.key === 'PageUp') next = new Date(cursor.getFullYear(), cursor.getMonth() - 1, Math.min(cursor.getDate(), 28));
            else if (e.key === 'PageDown') next = new Date(cursor.getFullYear(), cursor.getMonth() + 1, Math.min(cursor.getDate(), 28));
            else if (e.key === 'Home') next = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
            else if (e.key === 'End') next = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0);
            if (!next) return;
            e.preventDefault();
            cursor = next;
            render();
            focusDay();
        });

        // keep keyboard focus inside the calendar while it is open
        pop.addEventListener('focusout', (e) => {
            if (openCtl === ctl && e.relatedTarget && !pop.contains(e.relatedTarget) && e.relatedTarget !== btn) ctl.close(false);
        });

        const ctl = {
            open() {
                if (btn.disabled) return;
                if (openCtl && openCtl !== ctl) openCtl.close();
                openCtl = ctl;
                const sel = parse(input.value);
                const today = new Date(); today.setHours(0, 0, 0, 0);
                cursor = sel || (allowed(today) ? today : (min() && today < min() ? min() : max() || today));
                // a date of birth (max set years back, nothing chosen): start on the year list
                view = !sel && max() && (today - max()) / 31557600000 > 5 ? 'years' : 'days';
                if (view === 'years') cursor = new Date(max().getFullYear() - 10, 0, 1);
                render();
                host(btn).appendChild(pop);
                pop.hidden = false;
                place(pop, btn);
                showBackdrop(true);
                wrap.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
                requestAnimationFrame(() => pop.classList.add('is-on'));
                if (view === 'days') focusDay(); else (body.querySelector('.ui-tile.is-focus') || body.querySelector('.ui-tile'))?.focus({ preventScroll: true });
            },
            close(focusBack) {
                if (openCtl === ctl) openCtl = null;
                pop.classList.remove('is-on');
                pop.hidden = true;
                pop.remove();
                showBackdrop(false);
                wrap.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
                if (focusBack) btn.focus({ preventScroll: true });
            },
            reposition() { place(pop, btn); },
            get host() { return host(btn); },
        };

        btn.addEventListener('click', () => (openCtl === ctl ? ctl.close(true) : ctl.open()));
        btn.addEventListener('keydown', (e) => { if (e.key === 'ArrowDown') { e.preventDefault(); ctl.open(); } });
        input.addEventListener('change', label);
        input.addEventListener('invalid', () => wrap.classList.add('is-invalid'));
        input.addEventListener('focus', () => btn.focus());
        input.form?.addEventListener('reset', () => setTimeout(label));
        watchProperty(input, 'value', label);
        new MutationObserver(label).observe(input, { attributes: true, attributeFilter: ['value', 'disabled', 'readonly', 'min', 'max'] });

        label();
    }

    /* ======================================================================
       Wiring
       ====================================================================== */

    function enhance(root = document) {
        if (root.nodeType !== 1 && root !== document) return;
        const q = (sel) => (root.matches?.(sel) ? [root] : []).concat([...root.querySelectorAll(sel)]);
        q('select').forEach((el) => { try { enhanceSelect(el); } catch (err) { console.warn('[ui-controls] select', err); } });
        q('input[type="date"]').forEach((el) => { try { enhanceDate(el); } catch (err) { console.warn('[ui-controls] date', err); } });
    }

    // anything added later (modals, rows loaded by fetch, soft navigation)
    new MutationObserver((records) => {
        for (const r of records) for (const n of r.addedNodes) {
            if (n.nodeType === 1 && !n.classList.contains('ui-pop') && (n.matches('select, input[type="date"]') || n.querySelector('select, input[type="date"]'))) enhance(n);
        }
    }).observe(document.documentElement, { childList: true, subtree: true });

    document.addEventListener('pointerdown', (e) => {
        if (!openCtl) return;
        if (e.target.closest('.ui-pop') || e.target.closest('.ui-select-btn, .ui-date-btn')?.parentElement?.classList.contains('is-open')) return;
        closeOpen();
    }, true);
    window.addEventListener('resize', () => openCtl?.reposition());
    window.addEventListener('scroll', (e) => {
        if (!openCtl || phone()) return;
        if (e.target instanceof Element && e.target.closest('.ui-pop')) return;
        openCtl.reposition();
    }, true);
    document.addEventListener('softnav:before', closeOpen);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => enhance());
    else enhance();

    window.uiControls = { enhance, close: closeOpen };
}
