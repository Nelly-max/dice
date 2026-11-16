@extends('layouts.hub')

@section('content')
{{-- One of the marketer's leads (Hub\LeadController@show): the business, where it is and how far our team has got with it. --}}
@php
    $S = \App\Http\Controllers\Web\Hub\LeadController::STATUSES;
    $l = $lead;
    $flow = array_diff_key($S, ['not_interested' => 1]);
    $keys = array_keys($flow);
    $at = array_search($l->status, $keys, true);   // false when not interested
    $map = $l->latitude && $l->longitude ? 'https://www.google.com/maps?q=' . $l->latitude . ',' . $l->longitude : null;
    $added = $l->created_at ? \Illuminate\Support\Carbon::parse($l->created_at) : null;
@endphp
<div class="hub-content no-sidebar hd-page">

    <a href="{{ route('hub.leads') }}" class="hd-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> My leads</a>

    <header class="hd-head">
        <span class="hd-head-icon"><i class="fa-solid fa-store" aria-hidden="true"></i></span>
        <div>
            <h1>{{ $l->name }}</h1>
            <p>{{ collect([$l->business_type, $l->market_name, $added ? 'added ' . $added->format('d M Y') : null])->filter()->implode(' · ') }}</p>
        </div>
        <span class="hub-pill lead-pill lead-pill--{{ $l->status }}">{{ $S[$l->status][0] ?? ucfirst($l->status) }}</span>
    </header>

    <section class="hub-card lead-flow-card">
        <h2 class="hub-card-title">Where it is up to</h2>
        @if ($l->status === 'not_interested')
            <p class="lead-out"><i class="fa-regular fa-circle-xmark" aria-hidden="true"></i> Our team spoke to this business and it did not want to join for now.</p>
        @endif
        <ol class="lead-flow" aria-label="Stage">
            @foreach ($flow as $k => [$label, $icon, $hint])
                @php $i = $loop->index; $state = $at === false ? 'is-todo' : ($i < $at ? 'is-done' : ($i === $at ? 'is-current' : 'is-todo')); @endphp
                <li class="{{ $state }}" @if ($state === 'is-current') aria-current="step" @endif>
                    <span class="lead-flow-dot">@if ($state === 'is-done')<i class="fa-solid fa-check" aria-hidden="true"></i>@else<i class="{{ $icon }}" aria-hidden="true"></i>@endif</span>
                    <span class="lead-flow-text"><b>{{ $label }}</b><small>{{ $hint }}</small></span>
                </li>
            @endforeach
        </ol>
        <p class="hd-text">Our team updates this as they talk to the business. Once it joins, ask it to sign up with your referral code so you earn on it.</p>
    </section>

    <div class="hd-grid">
        <section class="hub-card">
            <h2 class="hub-card-title">The business</h2>
            <dl class="hd-list">
                <div><dt>Phone</dt><dd><a href="tel:{{ $l->phone }}">{{ $l->phone }}</a></dd></div>
                @if ($l->phone_2)<div><dt>Second phone</dt><dd><a href="tel:{{ $l->phone_2 }}">{{ $l->phone_2 }}</a></dd></div>@endif
                <div><dt>Contact person</dt><dd>{{ $l->contact_name ?: '—' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $l->email ?: '—' }}</dd></div>
                <div><dt>Type</dt><dd>{{ $l->business_type ?: '—' }}</dd></div>
                <div><dt>Market</dt><dd>{{ $l->market_name ?: 'Not sure' }}</dd></div>
            </dl>
        </section>

        <section class="hub-card">
            <h2 class="hub-card-title">Where it is</h2>
            <dl class="hd-list">
                <div><dt>County</dt><dd>{{ $l->county_name ?: '—' }}</dd></div>
                <div><dt>Town</dt><dd>{{ $l->town_name ?: '—' }}</dd></div>
                <div><dt>Area</dt><dd>{{ $l->place_name ?: '—' }}</dd></div>
                <div><dt>Address</dt><dd>{{ $l->address ?: '—' }}</dd></div>
                <div><dt>Map pin</dt><dd>@if ($map)<a href="{{ $map }}" target="_blank" rel="noopener">Open map</a>@else — @endif</dd></div>
            </dl>
        </section>
    </div>

    @if ($l->notes)
        <section class="hub-card">
            <h2 class="hub-card-title">Your notes</h2>
            <p class="hd-text">{!! nl2br(e($l->notes)) !!}</p>
        </section>
    @endif
</div>
@endsection
