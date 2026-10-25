@extends('layouts.main')

@section('title', 'Settings · Smart Market')

@section('content')
@php
    $customer = auth('customer')->user();
    $socials = array_filter(config('smartmarket.socials', []));
    $icons = ['instagram' => 'fa-brands fa-instagram', 'tiktok' => 'fa-brands fa-tiktok', 'youtube' => 'fa-brands fa-youtube', 'facebook' => 'fa-brands fa-facebook', 'x' => 'fa-brands fa-x-twitter'];
    $names = ['instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube', 'facebook' => 'Facebook', 'x' => 'X'];
@endphp

<div class="sm-page sm-settings">
    <header class="sm-settings-head">
        <h1>Settings</h1>
        <p>Your account, appearance and information about Smart Market.</p>
    </header>

    {{-- Account --}}
    <section class="sm-set-group" aria-labelledby="set-account">
        <h2 id="set-account">Account</h2>
        <div class="sm-set-card">
            @if ($customer)
                <a href="{{ route('hub.index') }}" class="sm-set-profile">
                    <span class="sm-avatar is-lg" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($customer->name ?? 'U'), 0, 1)) }}</span>
                    <span class="sm-set-text">
                        <strong>{{ $customer->name }}</strong>
                        <small>{{ $customer->account }}@if ($customer->email) · {{ $customer->email }}@endif</small>
                    </span>
                    <i class="fa-solid fa-chevron-right sm-go" aria-hidden="true"></i>
                </a>
                <a href="{{ route('hub.account.index') }}" class="sm-set-row">
                    <span class="sm-set-icon"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                    <span class="sm-set-text"><strong>Profile</strong><small>Name, phone, email and photo</small></span>
                    <i class="fa-solid fa-chevron-right sm-go" aria-hidden="true"></i>
                </a>
                <a href="{{ route('hub.orders') }}" class="sm-set-row">
                    <span class="sm-set-icon"><i class="fa-solid fa-receipt" aria-hidden="true"></i></span>
                    <span class="sm-set-text"><strong>My orders</strong><small>Track and review your orders</small></span>
                    <i class="fa-solid fa-chevron-right sm-go" aria-hidden="true"></i>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="sm-set-form" data-notify-loading="Signing you out…">
                    @csrf
                    <button type="submit" class="sm-set-row is-danger">
                        <span class="sm-set-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
                        <span class="sm-set-text"><strong>Log out</strong></span>
                    </button>
                </form>
            @else
                <div class="sm-set-guest">
                    <span class="sm-set-icon is-lg"><i class="fa-regular fa-user" aria-hidden="true"></i></span>
                    <div>
                        <strong>You're not signed in</strong>
                        <small>Sign in to track orders and checkout faster.</small>
                    </div>
                    <div class="sm-set-guest-btns">
                        <a href="{{ route('login') }}" class="sm-btn is-primary">Login</a>
                        <a href="{{ route('account.register') }}" class="sm-btn">Create account</a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- Appearance: Light / Dark / Device (smart-home.js; same "mode" setting as the shops) --}}
    <section class="sm-set-group" aria-labelledby="set-look">
        <h2 id="set-look">Appearance</h2>
        <div class="sm-set-card">
            <div class="sm-set-row is-static">
                <span class="sm-set-icon"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></span>
                <span class="sm-set-text"><strong>Theme</strong><small>Device follows your phone or computer setting</small></span>
                <div class="sm-segment" role="radiogroup" aria-label="Theme">
                    <button type="button" role="radio" data-theme-choice="light"><i class="fa-regular fa-sun" aria-hidden="true"></i> Light</button>
                    <button type="button" role="radio" data-theme-choice="dark"><i class="fa-regular fa-moon" aria-hidden="true"></i> Dark</button>
                    <button type="button" role="radio" data-theme-choice="system"><i class="fa-solid fa-mobile-screen" aria-hidden="true"></i> Device</button>
                </div>
            </div>
        </div>
    </section>

    {{-- Information pages (shareable links) --}}
    <section class="sm-set-group" aria-labelledby="set-info">
        <h2 id="set-info">Help &amp; information</h2>
        <div class="sm-set-card">
            @foreach (config('smartmarket.pages') as $slug => $item)
                <a href="{{ route('page', $slug) }}" class="sm-set-row">
                    <span class="sm-set-icon"><i class="{{ $item['icon'] }}" aria-hidden="true"></i></span>
                    <span class="sm-set-text"><strong>{{ $item['menu'] }}</strong></span>
                    <i class="fa-solid fa-chevron-right sm-go" aria-hidden="true"></i>
                </a>
            @endforeach
            <button type="button" class="sm-set-row" data-share data-share-title="Smart Market" data-share-text="{{ config('smartmarket.tagline') }}" data-share-url="{{ route('home') }}">
                <span class="sm-set-icon"><i class="fa-solid fa-share-nodes" aria-hidden="true"></i></span>
                <span class="sm-set-text"><strong>Share Smart Market</strong><small>Send the link to friends and family</small></span>
            </button>
        </div>
    </section>

    @if ($socials)
        <section class="sm-set-group" aria-labelledby="set-social">
            <h2 id="set-social">Follow us</h2>
            <div class="sm-socials">
                @foreach ($socials as $key => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="sm-social">
                        <i class="{{ $icons[$key] ?? 'fa-solid fa-link' }}" aria-hidden="true"></i>
                        <span>{{ $names[$key] ?? ucfirst($key) }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <p class="sm-set-foot">© {{ now()->year }} {{ config('smartmarket.company') }}</p>
</div>
@endsection
