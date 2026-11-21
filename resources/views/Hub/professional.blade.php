@extends('layouts.hub')

@section('content')
{{--
    Hub → My account → Professional (Hub\ProfessionalController@index). Without a profile: what it is,
    the tariffs, and "Create my profile". With one: whether the team approved it, the booking link to put
    on TikTok / Instagram, jobs so far, and the profile as customers see it.
--}}
@php
    use App\Support\Professionals as P;
    $money = fn ($v) => $v === null ? null : 'KES ' . number_format((float) $v);
    $cycle = fn ($c) => strtolower(P::CYCLES[$c][0] ?? $c);
    $pillTone = ['pending' => 'pending', 'active' => 'active', 'rejected' => 'rejected', 'deactivated' => 'inactive', 'suspended' => 'pending', 'banned' => 'blocked'];
@endphp
<div class="hub-content no-sidebar hd-page pro-page">

    @include('partials.Hub.back', ['fallback' => route('hub.account.index'), 'class' => 'hd-back'])

    @if (!$pro)
        <header class="hd-head">
            <span class="hd-head-icon pro-icon"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span>
            <div>
                <h1>Offer your services on Smart Market</h1>
                <p>Make-up, plumbing, photography, tutoring… create a profile and customers book you.</p>
            </div>
        </header>

        <section class="pro-how" aria-label="How it works">
            <div><i class="fa-solid fa-id-card" aria-hidden="true"></i><b>1. Create your profile</b><small>What you do, where you work, your prices and your TikTok or Instagram.</small></div>
            <div><i class="fa-solid fa-user-check" aria-hidden="true"></i><b>2. We approve it</b><small>Our team checks it and switches it on, usually within a day.</small></div>
            <div><i class="fa-solid fa-share-nodes" aria-hidden="true"></i><b>3. Share your link</b><small>Put your booking link in your TikTok bio. Customers book you from it.</small></div>
            <div><i class="fa-solid fa-calendar-check" aria-hidden="true"></i><b>4. Get jobs</b><small>Bookings land in Jobs. Accept them, do the work, mark them done.</small></div>
        </section>

        <section class="hub-card">
            <h2 class="hub-card-title">Tariffs</h2>
            @if ($tariffs->isEmpty())
                <p class="hd-text">Tariffs aren't set up yet. Please check again soon.</p>
            @else
                <div class="pro-tariff-list">
                    @foreach ($tariffs as $t)
                        <div class="pro-tariff {{ $t->featured ? 'is-featured' : '' }}">
                            @if ($t->featured)<em>Shown first</em>@endif
                            <b>{{ $t->name }}</b>
                            <strong>{{ $money($t->amount) }} <small>{{ $cycle($t->billing_cycle) }}</small></strong>
                            <ul>
                                <li><i class="fa-solid fa-check" aria-hidden="true"></i> {{ $t->max_services ? 'Up to ' . $t->max_services . ' services' : 'Any number of services' }}</li>
                                <li><i class="fa-solid fa-check" aria-hidden="true"></i> {{ $t->featured ? 'Top of the list customers see' : 'Listed for customers to find' }}</li>
                                <li><i class="fa-solid fa-check" aria-hidden="true"></i> Your own booking link</li>
                            </ul>
                            @if ($t->description)<small class="pro-tariff-desc">{{ $t->description }}</small>@endif
                        </div>
                    @endforeach
                </div>
                <p class="hd-text">You pick one while creating your profile and pay once it is approved.</p>
            @endif
            <a href="{{ route('hub.account.professional.form') }}" class="pro-cta"><i class="fa-solid fa-plus" aria-hidden="true"></i> Create my profile</a>
        </section>
    @else
        @php
            [$sLabel] = P::STATUSES[$pro->account_status] ?? [ucfirst($pro->account_status)];
            $photo = P::mediaUrl($pro->photo);
            $link = P::publicUrl($pro->account);
            $live = $pro->account_status === 'active';
        @endphp
        <header class="hd-head">
            <span class="hd-head-icon pro-icon pro-photo">@if ($photo)<img src="{{ $photo }}" alt="">@else<i class="fa-solid fa-briefcase" aria-hidden="true"></i>@endif</span>
            <div>
                <h1>{{ $pro->display_name }}</h1>
                <p>{{ $pro->profession }} · {{ $pro->account }}</p>
            </div>
            <span class="hub-pill hub-pill--{{ $pillTone[$pro->account_status] ?? 'pending' }}">{{ $sLabel }}</span>
        </header>

        <section class="pro-status is-{{ $pro->account_status }}">
            <i class="{{ P::STATUSES[$pro->account_status][3] ?? 'fa-solid fa-circle-info' }}" aria-hidden="true"></i>
            <div>
                <b>{{ $sLabel }}</b>
                <p>{{ P::MINE[$pro->account_status] ?? '' }}</p>
                @if ($pro->comment)
                    <blockquote><small>Note from the Smart Market team · {{ $pro->comment_at?->format('d M Y') }}</small>{!! nl2br(e($pro->comment)) !!}</blockquote>
                @endif
            </div>
        </section>

        <div class="pro-actions">
            @if ($pro->account_status !== 'banned')
                <a href="{{ route('hub.account.professional.form') }}" class="pro-btn"><i class="fa-solid fa-user-pen" aria-hidden="true"></i> {{ $pro->account_status === 'rejected' ? 'Fix and send again' : 'Edit profile' }}</a>
            @endif
            <a href="{{ route('hub.jobs') }}" class="pro-btn is-solid"><i class="fa-solid fa-briefcase" aria-hidden="true"></i> Jobs @if (($jobCounts['new'] ?? 0))<b>{{ $jobCounts['new'] }} new</b>@endif</a>
        </div>

        <section class="hd-stats fin-stats" aria-label="Jobs">
            <div class="hd-stat is-wait"><span class="hd-stat-icon"><i class="fa-solid fa-bell" aria-hidden="true"></i></span><div><small>New</small><strong>{{ (int) ($jobCounts['new'] ?? 0) }}</strong></div></div>
            <div class="hd-stat"><span class="hd-stat-icon"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i></span><div><small>Accepted</small><strong>{{ (int) ($jobCounts['accepted'] ?? 0) }}</strong></div></div>
            <div class="hd-stat is-good"><span class="hd-stat-icon"><i class="fa-solid fa-circle-check" aria-hidden="true"></i></span><div><small>Done</small><strong>{{ (int) ($jobCounts['done'] ?? 0) }}</strong></div></div>
        </section>

        <section class="hub-card pro-share">
            <h2 class="hub-card-title">Your booking link</h2>
            @if ($live)
                <p class="hd-text">Put this link in your TikTok, Instagram or WhatsApp bio. Anyone who opens it can book you, and the booking shows in Jobs.</p>
                <div class="pro-link-box">
                    <a href="{{ $link }}" target="_blank" rel="noopener" data-share data-share-open data-share-title="Book {{ $pro->display_name }}" data-share-text="Book {{ $pro->profession }} {{ $pro->display_name }} on Smart Market" data-share-url="{{ $link }}">{{ preg_replace('#^https?://#', '', $link) }}</a>
                    <button type="button" class="pro-btn" data-copy="{{ $link }}"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copy</button>
                </div>
                @if ($links->isNotEmpty())
                    <p class="hd-text">A link for each platform tells you where a booking came from:</p>
                    <ul class="pro-via">
                        @foreach ($links as $l)
                            @php $u = P::publicUrl($pro->account, $l->platform); @endphp
                            <li><i class="{{ P::PLATFORMS[$l->platform][1] ?? 'fa-solid fa-link' }}" aria-hidden="true"></i><span>{{ P::PLATFORMS[$l->platform][0] ?? $l->platform }}</span><button type="button" data-copy="{{ $u }}" aria-label="Copy the {{ P::PLATFORMS[$l->platform][0] ?? $l->platform }} link"><i class="fa-regular fa-copy" aria-hidden="true"></i></button></li>
                        @endforeach
                    </ul>
                @endif
            @else
                <p class="hd-text">Your link works once your profile is active.</p>
            @endif
        </section>

        <div class="hd-grid">
            <section class="hub-card">
                <h2 class="hub-card-title">Profile</h2>
                <dl class="hd-list">
                    <div><dt>Kind</dt><dd>{{ P::CATEGORIES[$pro->category][0] ?? '—' }}</dd></div>
                    <div><dt>Works</dt><dd>{{ P::WORKS_FROM[$pro->works_from] ?? '—' }}</dd></div>
                    <div><dt>From</dt><dd>{{ $pro->rate_from !== null ? $money($pro->rate_from) . ($pro->rate_unit ? ' / ' . $pro->rate_unit : '') : '—' }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $pro->phone }}</dd></div>
                    <div><dt>WhatsApp</dt><dd>{{ $pro->whatsapp ?: '—' }}</dd></div>
                    <div><dt>Markets</dt><dd>{{ $markets->implode(', ') ?: '—' }}</dd></div>
                </dl>
            </section>
            <section class="hub-card">
                <h2 class="hub-card-title">Tariff</h2>
                <dl class="hd-list">
                    <div><dt>Tariff</dt><dd>{{ $tariff ? $tariff->name : '—' }}</dd></div>
                    <div><dt>Price</dt><dd>{{ $tariff ? $money($tariff->amount) . ' ' . $cycle($tariff->billing_cycle) : '—' }}</dd></div>
                    <div><dt>Paid up to</dt><dd>{{ $paidUntil ? \Illuminate\Support\Carbon::parse($paidUntil)->format('d M Y') : 'Not paid yet' }}</dd></div>
                </dl>
                @unless ($paidUntil)<p class="hd-text">Our team will tell you how to pay once your profile is approved.</p>@endunless
            </section>
        </div>

        <section class="hub-card">
            <h2 class="hub-card-title">Services ({{ $services->count() }})</h2>
            @if ($services->isEmpty())
                <p class="hd-text">No services yet. <a href="{{ route('hub.account.professional.form') }}">Add what customers can book</a>.</p>
            @else
                <ul class="pro-svc">
                    @foreach ($services as $s)
                        <li><span><b>{{ $s->name }}</b>@if ($s->description)<small>{{ $s->description }}</small>@endif</span><strong>{{ $money($s->price) ?? 'Ask' }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>

<script>
    // copy buttons on this page ([data-copy])
    if (!window.__proCopy) {
        window.__proCopy = true;
        document.addEventListener('click', async (e) => {
            const b = e.target.closest && e.target.closest('[data-copy]');
            if (!b || b.tagName === 'A') return;
            const text = b.dataset.copy;
            let ok = false;
            try { await navigator.clipboard.writeText(text); ok = true; } catch (err) {
                const t = Object.assign(document.createElement('textarea'), { value: text });
                document.body.appendChild(t); t.select();
                try { ok = document.execCommand('copy'); } catch (e2) { ok = false; }
                t.remove();
            }
            if (window.notify) ok ? window.notify.success('Link copied. Paste it in your bio.') : window.notify.error('Could not copy. Press and hold the link to copy it.');
        });
    }
</script>
@endsection
