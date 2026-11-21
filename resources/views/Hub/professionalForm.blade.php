@extends('layouts.hub')

@section('content')
{{--
    Create / edit the professional profile (Hub\ProfessionalController@form / save).
    County → town use the shared Hub picker (partials/Hub/picker, cascade in resources/js/select.js).
    Services are rows that can be added and removed (script below). Inside .account-detail only the
    label/value rows are <span>s (hub-ui.css styles every span there as a row).
--}}
@php
    use App\Support\Professionals as P;
    $v = fn ($k, $d = null) => old($k, $pro->{$k} ?? $d);
    $svc = old('services', $services->map(fn ($s) => ['name' => $s->name, 'description' => $s->description, 'price' => $s->price !== null ? preg_replace('/\.0+$|(\.\d*?)0+$/', '$1', (string) $s->price) : '', 'duration' => $s->duration_minutes])->all());
    if (!$svc) $svc = [['name' => '', 'description' => '', 'price' => '', 'duration' => '']];
    $pickedMarkets = array_map('intval', old('markets', $picked));
    $photo = $pro ? P::mediaUrl($pro->photo) : null;
    $tariffPick = (int) old('tariff_id', $pro->tariff_id ?? ($tariffs->first()->id ?? 0));
    $linkVal = fn ($k) => old('links.' . $k, $links[$k] ?? '');
    $placeholders = ['tiktok' => '@yourname or full link', 'instagram' => '@yourname or full link', 'facebook' => 'facebook.com/yourpage', 'youtube' => 'youtube.com/@yourchannel',
        'x' => '@yourname', 'linkedin' => 'linkedin.com/in/yourname', 'website' => 'yourwebsite.co.ke'];
@endphp

<div class="hub-content hub-form no-sidebar fullground pro-form">
    <form action="{{ route('hub.account.professional.save') }}" method="POST" enctype="multipart/form-data" data-notify-loading="Saving your profile…">
        @csrf

        @include('partials.form-errors')

        <div class="hub-dash">
            <div class="left">
                <div class="account-details">

                    @include('partials.Hub.page-hero', [
                        'back' => route('hub.account.professional.index'),
                        'tone' => 'pro',
                        'icon' => 'fa-solid fa-briefcase',
                        'title' => $pro ? 'Edit your professional profile' : 'Create your professional profile',
                        'text' => $pro && $pro->account_status === 'active'
                            ? 'Changes show on your page straight away.'
                            : 'Our team checks it before customers can see it. Be clear about what you do and where.',
                        'steps' => ['About you', 'Where you work', 'Social media', 'Markets', 'Services', 'Tariff'],
                        'links' => [],   // the hero's own buttons; this page's $links are the social links
                    ])

                    {{-- ABOUT YOU --}}
                    <div class="account-detail">
                        <h1 class="sub-heading">About you</h1>

                        <span>
                            <h4>Photo <small class="lead-opt">a clear photo of you or your work</small></h4>
                            <label class="pro-photo-pick">
                                <img src="{{ $photo ?: '' }}" alt="" data-pro-preview @if (!$photo) hidden @endif>
                                <i class="fa-solid fa-camera" aria-hidden="true" @if ($photo) hidden @endif data-pro-cam></i>
                                <b>{{ $photo ? 'Change photo' : 'Add a photo' }}</b>
                                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-pro-photo>
                            </label>
                        </span>

                        <span>
                            <h4>Name customers see</h4>
                            <div class="required-input">
                                <input type="text" name="display_name" value="{{ $v('display_name', $customer->name) }}" maxlength="80" placeholder="e.g. Wanjiru Glam" required>
                                <h6 class="required">*</h6>
                            </div>
                        </span>

                        <span>
                            <h4>What you do</h4>
                            <div class="required-input">
                                <input type="text" name="profession" value="{{ $v('profession') }}" maxlength="80" placeholder="e.g. Make-up artist, Plumber, Photographer" required>
                                <h6 class="required">*</h6>
                            </div>
                        </span>

                        <span>
                            <h4>Kind of service</h4>
                            <select name="category" data-search="true" data-placeholder="Pick one" required>
                                <option value="">Pick one</option>
                                @foreach (P::CATEGORIES as $k => [$label])<option value="{{ $k }}" @selected($v('category') === $k)>{{ $label }}</option>@endforeach
                            </select>
                        </span>

                        <span class="pro-wide">
                            <h4>About your work <small class="lead-opt">optional</small></h4>
                            <textarea name="bio" rows="4" maxlength="2000" placeholder="What you are good at, what customers get, anything that makes you stand out.">{{ $v('bio') }}</textarea>
                        </span>

                        <span>
                            <h4>Years of experience <small class="lead-opt">optional</small></h4>
                            <input type="number" name="years_experience" value="{{ $v('years_experience') }}" min="0" max="60" placeholder="e.g. 5">
                        </span>

                        <span>
                            <h4>Prices start from <small class="lead-opt">optional</small></h4>
                            <div class="pro-rate">
                                <input type="number" name="rate_from" value="{{ $v('rate_from') !== null ? preg_replace('/\.0+$|(\.\d*?)0+$/', '$1', (string) $v('rate_from')) : '' }}" min="0" step="1" placeholder="KES">
                                <select name="rate_unit" aria-label="Per">
                                    <option value="">—</option>
                                    @foreach (['job' => 'per job', 'hour' => 'per hour', 'day' => 'per day', 'session' => 'per session'] as $k => $label)<option value="{{ $k }}" @selected($v('rate_unit') === $k)>{{ $label }}</option>@endforeach
                                </select>
                            </div>
                        </span>
                    </div>

                    {{-- WHERE YOU WORK --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Where you work</h4>

                        <span>
                            <h4>Phone</h4>
                            <div class="required-input">
                                <input type="tel" name="phone" value="{{ $v('phone', $customer->phone_number) }}" inputmode="tel" class="mobile-number" placeholder="eg. 0712345678" required>
                                <h6 class="required">*</h6>
                            </div>
                        </span>

                        <span>
                            <h4>WhatsApp <small class="lead-opt">optional</small></h4>
                            <input type="tel" name="whatsapp" value="{{ $v('whatsapp') }}" inputmode="tel" placeholder="eg. 0712345678">
                        </span>

                        <span>
                            <h4>Email <small class="lead-opt">optional</small></h4>
                            <input type="email" name="email" value="{{ $v('email', $customer->email) }}" maxlength="255">
                        </span>

                        <span>
                            <h4>County</h4>
                            @include('partials.Hub.picker', ['name' => 'county_id', 'label' => 'County', 'options' => $counties, 'value' => $v('county_id'), 'required' => true])
                        </span>

                        <span>
                            <h4>Town <small class="lead-opt">optional</small></h4>
                            @include('partials.Hub.picker', ['name' => 'town_id', 'label' => 'Town', 'options' => $towns, 'value' => $v('town_id')])
                        </span>

                        <span>
                            <h4>Area <small class="lead-opt">optional</small></h4>
                            <input type="text" name="area" value="{{ $v('area') }}" maxlength="120" placeholder="e.g. Kilimani, near Yaya Centre">
                        </span>

                        <span>
                            <h4>You work</h4>
                            <div class="grid-checker">
                                @foreach (P::WORKS_FROM as $k => $label)
                                    <label class="grid-check"><input type="radio" name="works_from" value="{{ $k }}" @checked($v('works_from', 'both') === $k)><h5>{{ $label }}</h5></label>
                                @endforeach
                            </div>
                        </span>
                    </div>

                    {{-- SOCIAL MEDIA --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Social media & links</h4>
                        <p class="pro-hint">Customers see these on your page. Your booking link, to put back in your bios, is on your professional page once you are approved.</p>
                        @foreach (P::PLATFORMS as $k => [$label, $icon])
                            <span>
                                <h4><i class="{{ $icon }} pro-plat" aria-hidden="true"></i> {{ $label }} <small class="lead-opt">optional</small></h4>
                                <input type="text" name="links[{{ $k }}]" value="{{ $linkVal($k) }}" maxlength="500" placeholder="{{ $placeholders[$k] ?? '' }}" autocomplete="off" autocapitalize="off" spellcheck="false">
                            </span>
                        @endforeach
                    </div>

                    {{-- MARKETS --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Markets you relate to</h4>
                        <p class="pro-hint">Pick the Smart Market sections your work goes with, so customers there can find you too (e.g. Glam Room for a make-up artist, Home City for a plumber).</p>
                        <div class="pro-markets">
                            @foreach ($markets as $id => $name)
                                <label class="pro-chip"><input type="checkbox" name="markets[]" value="{{ $id }}" @checked(in_array((int) $id, $pickedMarkets, true))><b>{{ $name }}</b></label>
                            @endforeach
                        </div>
                    </div>

                    {{-- SERVICES --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Services customers can book</h4>
                        <p class="pro-hint">Name each one, with a price if you have one. Leave a row empty to drop it.</p>
                        <div class="pro-svc-rows" data-svc-rows>
                            @foreach ($svc as $i => $s)
                                <div class="pro-svc-row" data-svc-row>
                                    <input type="text" name="services[{{ $i }}][name]" value="{{ $s['name'] ?? '' }}" maxlength="120" placeholder="Service, e.g. Bridal make-up" aria-label="Service name">
                                    <input type="number" name="services[{{ $i }}][price]" value="{{ $s['price'] ?? '' }}" min="0" step="1" placeholder="Price (KES)" aria-label="Price">
                                    <input type="number" name="services[{{ $i }}][duration]" value="{{ $s['duration'] ?? '' }}" min="5" step="5" placeholder="Minutes" aria-label="How long, in minutes">
                                    <button type="button" class="pro-svc-del" data-svc-del aria-label="Remove this service"><i class="fa-regular fa-trash-can" aria-hidden="true"></i></button>
                                    <input type="text" name="services[{{ $i }}][description]" value="{{ $s['description'] ?? '' }}" maxlength="500" placeholder="Short description (optional)" class="pro-svc-desc" aria-label="Description">
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="pro-add" data-svc-add><i class="fa-solid fa-plus" aria-hidden="true"></i> Add a service</button>
                    </div>

                    {{-- TARIFF --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Tariff</h4>
                        @if ($tariffs->isEmpty())
                            <p class="pro-hint">Tariffs aren't set up yet, so the profile can't be sent. Please try again soon.</p>
                        @else
                            <p class="pro-hint">What you pay to be listed. You pay once your profile is approved.</p>
                            <div class="pro-tariff-list">
                                @foreach ($tariffs as $t)
                                    <label class="pro-tariff {{ $t->featured ? 'is-featured' : '' }}">
                                        <input type="radio" name="tariff_id" value="{{ $t->id }}" @checked($tariffPick === (int) $t->id) required>
                                        @if ($t->featured)<em>Shown first</em>@endif
                                        <b>{{ $t->name }}</b>
                                        <strong>KES {{ number_format((float) $t->amount) }} <small>{{ strtolower(P::CYCLES[$t->billing_cycle][0] ?? $t->billing_cycle) }}</small></strong>
                                        <small>{{ $t->max_services ? 'Up to ' . $t->max_services . ' services' : 'Any number of services' }}</small>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        <div class="button submit-data">
            <button type="submit" @disabled($tariffs->isEmpty())>{{ $pro ? ($pro->account_status === 'rejected' ? 'Save and send again' : 'Save profile') : 'Send my profile' }}</button>
        </div>
    </form>
</div>

<script>
(function () {
    if (window.__proForm) return; window.__proForm = true;
    const on = (t, ty, fn) => document.addEventListener(ty, (e) => { const el = e.target.closest && e.target.closest(t); if (el) fn(e, el); });
    let next = 1000;
    on('[data-svc-add]', 'click', (e, btn) => {
        const rows = btn.parentElement.querySelector('[data-svc-rows]');
        const first = rows.querySelector('[data-svc-row]');
        const row = first.cloneNode(true);
        const i = next++;
        row.querySelectorAll('input').forEach((inp) => { inp.value = ''; inp.name = inp.name.replace(/services\[\d+\]/, `services[${i}]`); });
        rows.appendChild(row);
        row.querySelector('input').focus();
    });
    on('[data-svc-del]', 'click', (e, btn) => {
        const rows = btn.closest('[data-svc-rows]');
        const row = btn.closest('[data-svc-row]');
        if (rows.querySelectorAll('[data-svc-row]').length > 1) row.remove();
        else row.querySelectorAll('input').forEach((inp) => { inp.value = ''; });
    });
    document.addEventListener('change', (e) => {
        if (!e.target.matches || !e.target.matches('[data-pro-photo]')) return;
        const f = e.target.files && e.target.files[0];
        const wrap = e.target.closest('.pro-photo-pick');
        if (!f || !wrap) return;
        const img = wrap.querySelector('[data-pro-preview]');
        img.src = URL.createObjectURL(f); img.hidden = false;
        const cam = wrap.querySelector('[data-pro-cam]'); if (cam) cam.hidden = true;
        wrap.querySelector('b').textContent = 'Change photo';
    });
})();
</script>
@endsection
