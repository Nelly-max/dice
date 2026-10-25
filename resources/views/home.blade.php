@extends('layouts.main')

@section('content')
@php
    $customer = auth('customer')->user();
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = $customer ? \Illuminate\Support\Str::of($customer->name ?? '')->explode(' ')->first() : null;
    $mediaBase = rtrim(config('app.media_url'), '/');

    $heroes = [
        asset('img/Hero_Banner_1.png'),
        asset('img/homeMarket/Hero_Banner_2.jpg'),
        asset('img/homeMarket/Hero_Banner_3.jpg'),
        asset('img/homeMarket/Hero_Banner_1.jpg'),
    ];

    @endphp

<div class="sm-page">

    {{-- Greeting --}}
    <section class="sm-intro">
        <p class="sm-greeting">{{ $greeting }}{{ $firstName ? ', ' . $firstName : '' }}</p>
        <h1>What do you need today?</h1>
    </section>

    {{-- Fast delivery --}}
    <section class="sm-deal" aria-label="Fast delivery">
        <div class="sm-deal-text">
            <span class="sm-deal-kicker">Fast delivery</span>
            <h2>From shops near you to your door</h2>
            <p>We only show shops within {{ config('homemarket.max_radius_m', 3000) / 1000 }} km of you, so a local rider brings your order quickly. Pay with M-Pesa.</p>
        </div>
        <a href="{{ route('homemarket.home') }}" class="sm-deal-btn">Start shopping</a>
        <i class="fa-solid fa-motorcycle sm-deal-art" aria-hidden="true"></i>
    </section>

    {{-- Hero slider: reused.js drives .slider .list .item, #prev / #next and .dots --}}
    <section class="slider sm-hero" aria-label="Offers">
        <div class="list">
            @foreach ($heroes as $src)
                <div class="item"><img src="{{ $src }}" alt="" @if (!$loop->first) loading="lazy" @endif></div>
            @endforeach
        </div>
        <div class="buttons">
            <button type="button" id="prev" aria-label="Previous offer"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
            <button type="button" id="next" aria-label="Next offer"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <ul class="dots" aria-hidden="true">
            @foreach ($heroes as $src)
                <li @class(['active' => $loop->first])></li>
            @endforeach
        </ul>
    </section>

    {{-- Near you --}}
    <section class="sm-section" aria-labelledby="near-you">
        <header class="sm-section-head">
            <h2 id="near-you">Near you</h2>
        </header>
        <div class="sm-rows">
            <a href="{{ route('homemarket.home') }}" class="sm-row">
                <span class="sm-row-mark"><i class="fa-solid fa-store" aria-hidden="true"></i></span>
                <span class="sm-row-text">
                    <strong>Stores near you</strong>
                    <small>Shops that deliver to your location</small>
                </span>
                <i class="fa-solid fa-chevron-right sm-go" aria-hidden="true"></i>
            </a>
            <div class="sm-row is-soon">
                <span class="sm-row-mark"><i class="fa-regular fa-heart" aria-hidden="true"></i></span>
                <span class="sm-row-text">
                    <strong>Followed stores <em>Soon</em></strong>
                    <small>Save your favourite shops</small>
                </span>
            </div>
        </div>
    </section>

    {{-- Divisions and their services --}}
    <div id="services" class="sm-services-anchor"></div>
    @forelse ($majorDivisions as $major)
        <section class="sm-division" id="division-{{ $major->id }}" data-sm-division>
            <header class="sm-division-head">
                <h2>{{ $major->name }}</h2>
                <a href="{{ route('products.byMajorDivision', $major->id) }}" class="sm-view-all">See all</a>
            </header>

            <div class="sm-tiles">
                @foreach ($major->subDivisions as $sub)
                    <a href="{{ route('subdivision.route', $sub->weblink) }}" class="sm-tile" data-sm-tile
                       data-search="{{ \Illuminate\Support\Str::lower($sub->name . ' ' . $major->name . ' ' . $sub->weblink) }}">
                        <span class="sm-tile-logo">
                            @if (!empty($sub->logo))
                                <img src="{{ $mediaBase . '/' . ltrim($sub->logo, '/') }}" alt="" loading="lazy">
                            @else
                                <b>{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($sub->name, 0, 1)) }}</b>
                            @endif
                        </span>
                        <span class="sm-tile-name">{{ $sub->name }}</span>
                        <i class="fa-solid fa-chevron-right sm-tile-go" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <p class="sm-empty">No services are open yet. Please check back soon.</p>
    @endforelse

</div>
@endsection
