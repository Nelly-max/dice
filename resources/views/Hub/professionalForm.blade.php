@extends('layouts.hub')

@section('content')
{{--
    Create / edit the professional profile (Hub\ProfessionalController@form / save).

    Layout (styles: .pf-* in hub-ui.css): the hero, then the form sections on the left and, on wide
    screens, a sticky side column with a live preview of the profile card and the list of sections
    (ticked when filled in). The save bar sticks to the bottom of the screen.

    County → town use the shared Hub picker (partials/Hub/picker, cascade in resources/js/select.js).
    Services are rows that can be added and removed; the preview and the section ticks follow what is
    typed (script below). The photo is the account's own profile photo (shown only).
--}}
@php
    use App\Support\Professionals as P;
    $v = fn ($k, $d = null) => old($k, $pro->{$k} ?? $d);
    $svc = old('services', $services->map(fn ($s) => ['name' => $s->name, 'description' => $s->description, 'price' => $s->price !== null ? preg_replace('/\.0+$|(\.\d*?)0+$/', '$1', (string) $s->price) : '', 'duration' => $s->duration_minutes])->all());
    if (!$svc) $svc = [['name' => '', 'description' => '', 'price' => '', 'duration' => '']];
    $pickedMarkets = array_map('intval', old('markets', $picked));
    $photo = P::mediaUrl($customer->profile_image);   // the account photo is the profile photo
    $tariffPick = (int) old('tariff_id', $pro->tariff_id ?? ($tariffs->first()->id ?? 0));
    $linkVal = fn ($k) => old('links.' . $k, $links[$k] ?? '');
    $placeholders = ['tiktok' => '@yourname or full link', 'instagram' => '@yourname or full link', 'facebook' => 'facebook.com/yourpage', 'youtube' => 'youtube.com/@yourchannel',
        'x' => '@yourname', 'linkedin' => 'linkedin.com/in/yourname'];
    $units = ['job' => 'per job', 'hour' => 'per hour', 'day' => 'per day', 'session' => 'per session'];
    $live = $pro && $pro->account_status === 'active';

    // for the preview card
    $pvName = $v('display_name', $customer->name);
    $pvCat = P::CATEGORIES[$v('category')] ?? null;
    $pvRate = $v('rate_from');
    $pvRate = $pvRate !== null && $pvRate !== '' ? 'From KES ' . number_format((float) $pvRate) . (($u = $units[$v('rate_unit')] ?? '') ? ' ' . $u : '') : '';
    $pvPlace = $v('area');

    $sections = [
        ['pf-about', 'About you', 'fa-solid fa-user'],
        ['pf-where', 'Where you work', 'fa-solid fa-location-dot'],
        ['pf-social', 'Social media', 'fa-solid fa-hashtag'],
        ['pf-markets', 'Markets', 'fa-solid fa-store'],
        ['pf-services', 'Services', 'fa-solid fa-list-check'],
        ['pf-tariff', 'Tariff', 'fa-solid fa-receipt'],
    ];
@endphp

<div class="hub-content hub-form no-sidebar fullground pro-form">
    <form action="{{ route('hub.account.professional.save') }}" method="POST" enctype="multipart/form-data" data-notify-loading="Saving your profile…" class="pf" data-pf>
        @csrf

        @include('partials.form-errors')

        <div class="account-details pf-top">
            @include('partials.Hub.page-hero', [
                'back' => route('hub.account.professional.index'),
                'tone' => 'pro',
                'icon' => 'fa-solid fa-briefcase',
                'title' => $pro ? 'Edit your professional profile' : 'Create your professional profile',
                'text' => $live
                    ? 'Changes show on your page straight away.'
                    : 'Our team checks it before customers can see it. Be clear about what you do and where.',
                'links' => [],   // the hero's own buttons; this page's $links are the social links
            ])
        </div>

        <div class="pf-layout">

            {{-- side: live preview + sections (sticky on wide screens; the preview sits on top on phones) --}}
            <aside class="pf-side" aria-label="Preview">
                <div class="pf-preview">
                    <p class="pf-kicker"><i class="fa-regular fa-eye" aria-hidden="true"></i> How customers see you</p>
                    <div class="pf-card">
                        <div class="pf-card-photo">
                            @if ($photo)<img src="{{ $photo }}" alt="">@else<i class="fa-solid fa-user" aria-hidden="true"></i>@endif
                        </div>
                        <div class="pf-card-body">
                            <b data-pv="name">{{ $pvName ?: 'Your name' }}</b>
                            <span data-pv="profession">{{ $v('profession') ?: 'What you do' }}</span>
                            <em class="pf-card-tag" data-pv="category" @if (!$pvCat) hidden @endif>
                                <i class="{{ $pvCat[1] ?? 'fa-solid fa-briefcase' }}" aria-hidden="true"></i><span>{{ $pvCat[0] ?? '' }}</span>
                            </em>
                            <small data-pv="place" @if (!$pvPlace) hidden @endif><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <span>{{ $pvPlace }}</span></small>
                            <strong data-pv="rate" @if (!$pvRate) hidden @endif>{{ $pvRate }}</strong>
                        </div>
                    </div>
                </div>

                <nav class="pf-nav" aria-label="Sections">
                    <p class="pf-kicker">Your profile</p>
                    <ol>
                        @foreach ($sections as $i => [$id, $label, $icon])
                            <li><a href="#{{ $id }}" data-pf-step="{{ $id }}"><b>{{ $i + 1 }}</b><span>{{ $label }}</span><i class="fa-solid fa-check" aria-hidden="true"></i></a></li>
                        @endforeach
                    </ol>
                </nav>
            </aside>

            <div class="pf-main">

                {{-- 1 · ABOUT YOU --}}
                <section class="pf-sec" id="pf-about" data-pf-sec aria-labelledby="pf-about-h">
                    <header class="pf-sec-head">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                        <div><h2 id="pf-about-h">About you</h2><p>Who customers are booking.</p></div>
                        <small>1 / 6</small>
                    </header>

                    <div class="pf-grid">
                        <div class="pf-field pf-wide pf-photo">
                            {{-- the account's own profile photo, shown only --}}
                            <figure class="pro-photo-view">
                                @if ($photo)<img src="{{ $photo }}" alt="Your profile photo">@else<i class="fa-solid fa-user" aria-hidden="true"></i>@endif
                                <figcaption><i class="fa-solid fa-circle-user" aria-hidden="true"></i> {{ $photo ? 'Your profile photo' : 'No profile photo yet' }}</figcaption>
                            </figure>
                        </div>

                        <label class="pf-field">
                            <span class="pf-label">Name customers see <i class="pf-req" aria-hidden="true">*</i></span>
                            <input type="text" name="display_name" value="{{ $v('display_name', $customer->name) }}" maxlength="80" placeholder="e.g. Wanjiru Glam" required data-pv-in="name">
                        </label>

                        <label class="pf-field">
                            <span class="pf-label">What you do <i class="pf-req" aria-hidden="true">*</i></span>
                            <input type="text" name="profession" value="{{ $v('profession') }}" maxlength="80" placeholder="e.g. Make-up artist, Plumber" required data-pv-in="profession">
                        </label>

                        <div class="pf-field">
                            <span class="pf-label">Kind of service <i class="pf-req" aria-hidden="true">*</i></span>
                            <select name="category" data-search="true" data-placeholder="Pick one" required data-pv-in="category" aria-label="Kind of service">
                                <option value="">Pick one</option>
                                @foreach (P::CATEGORIES as $k => [$label, $icon])<option value="{{ $k }}" data-icon="{{ $icon }}" @selected($v('category') === $k)>{{ $label }}</option>@endforeach
                            </select>
                        </div>

                        <label class="pf-field">
                            <span class="pf-label">Years of experience <small>optional</small></span>
                            <input type="number" name="years_experience" value="{{ $v('years_experience') }}" min="0" max="60" placeholder="e.g. 5">
                        </label>

                        <label class="pf-field pf-wide">
                            <span class="pf-label">About your work <small>optional</small></span>
                            <textarea name="bio" rows="4" maxlength="2000" placeholder="What you are good at, what customers get, anything that makes you stand out.">{{ $v('bio') }}</textarea>
                        </label>

                        <div class="pf-field pf-wide">
                            <span class="pf-label">Prices start from <small>optional</small></span>
                            <div class="pf-rate">
                                <div class="pf-affix"><em>KES</em><input type="number" name="rate_from" value="{{ $v('rate_from') !== null ? preg_replace('/\.0+$|(\.\d*?)0+$/', '$1', (string) $v('rate_from')) : '' }}" min="0" step="1" placeholder="0" aria-label="Price from (KES)" data-pv-in="rate"></div>
                                <select name="rate_unit" aria-label="Per" data-pv-in="unit">
                                    <option value="">—</option>
                                    @foreach ($units as $k => $label)<option value="{{ $k }}" @selected($v('rate_unit') === $k)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 2 · WHERE YOU WORK --}}
                <section class="pf-sec" id="pf-where" data-pf-sec aria-labelledby="pf-where-h">
                    <header class="pf-sec-head">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                        <div><h2 id="pf-where-h">Where you work</h2><p>How customers reach you, and where.</p></div>
                        <small>2 / 6</small>
                    </header>

                    <div class="pf-grid">
                        <label class="pf-field">
                            <span class="pf-label">Phone <i class="pf-req" aria-hidden="true">*</i></span>
                            <div class="pf-affix"><i class="fa-solid fa-phone" aria-hidden="true"></i><input type="tel" name="phone" value="{{ $v('phone', $customer->phone_number) }}" inputmode="tel" class="mobile-number" placeholder="eg. 0712345678" required></div>
                        </label>

                        <label class="pf-field">
                            <span class="pf-label">WhatsApp <small>optional</small></span>
                            <div class="pf-affix"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i><input type="tel" name="whatsapp" value="{{ $v('whatsapp') }}" inputmode="tel" placeholder="eg. 0712345678"></div>
                        </label>

                        <label class="pf-field pf-wide">
                            <span class="pf-label">Email <small>optional</small></span>
                            <div class="pf-affix"><i class="fa-regular fa-envelope" aria-hidden="true"></i><input type="email" name="email" value="{{ $v('email', $customer->email) }}" maxlength="255" placeholder="you@example.com"></div>
                        </label>

                        <div class="pf-field">
                            <span class="pf-label">County <i class="pf-req" aria-hidden="true">*</i></span>
                            @include('partials.Hub.picker', ['name' => 'county_id', 'label' => 'County', 'options' => $counties, 'value' => $v('county_id'), 'required' => true])
                        </div>

                        <div class="pf-field">
                            <span class="pf-label">Town <small>optional</small></span>
                            @include('partials.Hub.picker', ['name' => 'town_id', 'label' => 'Town', 'options' => $towns, 'value' => $v('town_id')])
                        </div>

                        <label class="pf-field pf-wide">
                            <span class="pf-label">Area <small>optional</small></span>
                            <input type="text" name="area" value="{{ $v('area') }}" maxlength="120" placeholder="e.g. Kilimani, near Yaya Centre" data-pv-in="place">
                        </label>

                        <div class="pf-field pf-wide">
                            <span class="pf-label">You work</span>
                            <div class="pf-options">
                                @foreach (P::WORKS_FROM as $k => $label)
                                    <label class="pf-option"><input type="radio" name="works_from" value="{{ $k }}" @checked($v('works_from', 'both') === $k)><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </section>

                {{-- 3 · SOCIAL MEDIA --}}
                <section class="pf-sec" id="pf-social" data-pf-sec aria-labelledby="pf-social-h">
                    <header class="pf-sec-head">
                        <i class="fa-solid fa-hashtag" aria-hidden="true"></i>
                        <div><h2 id="pf-social-h">Social media & links</h2><p>Shown on your page. Your booking link, to put back in your bios, is on your professional page once you are approved.</p></div>
                        <small>3 / 6</small>
                    </header>

                    <div class="pf-grid">
                        @foreach (\Illuminate\Support\Arr::except(P::PLATFORMS, ['website']) as $k => [$label, $icon])
                            <label class="pf-field">
                                <span class="pf-label">{{ $label }}</span>
                                <div class="pf-affix pf-affix--{{ $k }}"><i class="{{ $icon }}" aria-hidden="true"></i><input type="text" name="links[{{ $k }}]" value="{{ $linkVal($k) }}" maxlength="500" placeholder="{{ $placeholders[$k] ?? '' }}" autocomplete="off" autocapitalize="off" spellcheck="false"></div>
                            </label>
                        @endforeach
                    </div>
                </section>

                {{-- 4 · MARKETS --}}
                <section class="pf-sec" id="pf-markets" data-pf-sec aria-labelledby="pf-markets-h">
                    <header class="pf-sec-head">
                        <i class="fa-solid fa-store" aria-hidden="true"></i>
                        <div><h2 id="pf-markets-h">Markets you relate to</h2><p>Customers in these sections find you too, e.g. Glam Room for a make-up artist, Home City for a plumber.</p></div>
                        <small>4 / 6</small>
                    </header>

                    <div class="pro-markets">
                        @foreach ($markets as $id => $name)
                            <label class="pro-chip"><input type="checkbox" name="markets[]" value="{{ $id }}" @checked(in_array((int) $id, $pickedMarkets, true))><b>{{ $name }}</b></label>
                        @endforeach
                    </div>
                </section>

                {{-- 5 · SERVICES --}}
                <section class="pf-sec" id="pf-services" data-pf-sec aria-labelledby="pf-services-h">
                    <header class="pf-sec-head">
                        <i class="fa-solid fa-list-check" aria-hidden="true"></i>
                        <div><h2 id="pf-services-h">Services customers can book</h2><p>Name each one, with a price if you have one. Leave a row empty to drop it.</p></div>
                        <small>5 / 6</small>
                    </header>

                    <div class="pf-svc">
                        <div class="pf-svc-head" aria-hidden="true"><span>Service</span><span>Price (KES)</span><span>Minutes</span><span></span></div>
                        <div class="pro-svc-rows" data-svc-rows>
                            @foreach ($svc as $i => $s)
                                <div class="pro-svc-row" data-svc-row>
                                    <input type="text" name="services[{{ $i }}][name]" value="{{ $s['name'] ?? '' }}" maxlength="120" placeholder="e.g. Bridal make-up" aria-label="Service name">
                                    <input type="number" name="services[{{ $i }}][price]" value="{{ $s['price'] ?? '' }}" min="0" step="1" placeholder="Price" aria-label="Price (KES)">
                                    <input type="number" name="services[{{ $i }}][duration]" value="{{ $s['duration'] ?? '' }}" min="5" step="5" placeholder="Min" aria-label="How long, in minutes">
                                    <button type="button" class="pro-svc-del" data-svc-del aria-label="Remove this service"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
                                    <input type="text" name="services[{{ $i }}][description]" value="{{ $s['description'] ?? '' }}" maxlength="500" placeholder="Short description (optional)" class="pro-svc-desc" aria-label="Description">
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="pro-add" data-svc-add><i class="fa-solid fa-plus" aria-hidden="true"></i> Add a service</button>
                    </div>
                </section>

                {{-- 6 · TARIFF --}}
                <section class="pf-sec" id="pf-tariff" data-pf-sec aria-labelledby="pf-tariff-h">
                    <header class="pf-sec-head">
                        <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                        <div><h2 id="pf-tariff-h">Tariff</h2><p>{{ $tariffs->isEmpty() ? "Tariffs aren't set up yet, so the profile can't be sent. Please try again soon." : 'What you pay to be listed. You pay once your profile is approved.' }}</p></div>
                        <small>6 / 6</small>
                    </header>

                    @if ($tariffs->isNotEmpty())
                        <div class="pro-tariff-list">
                            @foreach ($tariffs as $t)
                                <label class="pro-tariff {{ $t->featured ? 'is-featured' : '' }}">
                                    <input type="radio" name="tariff_id" value="{{ $t->id }}" @checked($tariffPick === (int) $t->id) required>
                                    <i class="pf-tick fa-solid fa-check" aria-hidden="true"></i>
                                    @if ($t->featured)<em>Shown first</em>@endif
                                    <b>{{ $t->name }}</b>
                                    <strong>KES {{ number_format((float) $t->amount) }} <small>{{ strtolower(P::CYCLES[$t->billing_cycle][0] ?? $t->billing_cycle) }}</small></strong>
                                    <small>{{ $t->max_services ? 'Up to ' . $t->max_services . ' services' : 'Any number of services' }}</small>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>

            </div>
        </div>

        {{-- save bar (sticks to the bottom of the screen) --}}
        <div class="pf-save">
            <p><i class="fa-solid {{ $live ? 'fa-bolt' : 'fa-shield-halved' }}" aria-hidden="true"></i> {{ $live ? 'Changes show on your page straight away.' : 'Our team checks your profile before it goes live.' }}</p>
            <button type="submit" @disabled($tariffs->isEmpty())>{{ $pro ? ($pro->account_status === 'rejected' ? 'Save and send again' : 'Save profile') : 'Send my profile' }} <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </div>
    </form>
</div>

<script>
(function () {
    if (window.__proForm) return; window.__proForm = true;
    const on = (t, ty, fn) => document.addEventListener(ty, (e) => { const el = e.target.closest && e.target.closest(t); if (el) fn(e, el); });

    /* services: add / remove rows */
    let next = 1000;
    on('[data-svc-add]', 'click', (e, btn) => {
        const rows = btn.parentElement.querySelector('[data-svc-rows]');
        const first = rows.querySelector('[data-svc-row]');
        const row = first.cloneNode(true);
        const i = next++;
        row.querySelectorAll('input').forEach((inp) => { inp.value = ''; inp.name = inp.name.replace(/services\[\d+\]/, `services[${i}]`); });
        rows.appendChild(row);
        row.querySelector('input').focus();
        refresh(btn.closest('[data-pf]'));
    });
    on('[data-svc-del]', 'click', (e, btn) => {
        const rows = btn.closest('[data-svc-rows]');
        const row = btn.closest('[data-svc-row]');
        if (rows.querySelectorAll('[data-svc-row]').length > 1) row.remove();
        else row.querySelectorAll('input').forEach((inp) => { inp.value = ''; });
        refresh(rows.closest('[data-pf]'));
    });

    /* live preview card + section ticks */
    const val = (form, key) => form.querySelector(`[data-pv-in="${key}"]`);
    const setText = (el, text) => { if (!el) return; const t = el.querySelector('span') || el; t.textContent = text; el.hidden = !text; };
    function preview(form) {
        const pv = (k) => form.querySelector(`[data-pv="${k}"]`);
        const name = val(form, 'name')?.value.trim();
        const prof = val(form, 'profession')?.value.trim();
        pv('name').textContent = name || 'Your name';
        pv('profession').textContent = prof || 'What you do';
        const cat = val(form, 'category');
        const opt = cat && cat.selectedOptions[0];
        const tag = pv('category');
        if (tag) {
            tag.hidden = !(opt && opt.value);
            if (opt && opt.value) { tag.querySelector('span').textContent = opt.textContent; tag.querySelector('i').className = opt.dataset.icon || 'fa-solid fa-briefcase'; }
        }
        setText(pv('place'), val(form, 'place')?.value.trim() || '');
        const rate = val(form, 'rate')?.value;
        const unitSel = val(form, 'unit');
        const unit = unitSel && unitSel.value ? ' ' + unitSel.selectedOptions[0].textContent : '';
        const r = pv('rate');
        if (r) { r.hidden = !rate; r.textContent = rate ? 'From KES ' + Number(rate).toLocaleString('en-KE') + unit : ''; }
    }
    function filled(el) {
        if (el.type === 'radio' || el.type === 'checkbox') return el.checked;
        return String(el.value || '').trim() !== '';
    }
    function ticks(form) {
        form.querySelectorAll('[data-pf-sec]').forEach((sec) => {
            const req = [...sec.querySelectorAll('[required]')];
            const radios = req.filter((el) => el.type === 'radio');
            const others = req.filter((el) => el.type !== 'radio');
            const fields = [...sec.querySelectorAll('input:not([type=hidden]), select, textarea')];
            const done = req.length
                ? others.every(filled) && (!radios.length || radios.some(filled))
                : fields.some(filled);
            const step = form.querySelector(`[data-pf-step="${sec.id}"]`);
            if (step) step.classList.toggle('is-done', done);
            sec.classList.toggle('is-done', done);
        });
    }
    function refresh(form) { if (!form) return; preview(form); ticks(form); }

    ['input', 'change'].forEach((t) => document.addEventListener(t, (e) => {
        const form = e.target.closest && e.target.closest('[data-pf]');
        if (form) refresh(form);
    }));
    const init = () => document.querySelectorAll('[data-pf]').forEach(refresh);
    document.addEventListener('hubnav:after', init);
    init();
})();
</script>
@endsection
