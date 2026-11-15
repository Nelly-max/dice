@php
    $customer = auth('customer')->user();
    $abLogo = (($subdivisionName ?? 'Marketplace') !== 'Marketplace' && !empty($logoUrl)) ? $logoUrl : asset('img/logo/airbnb.png');
    $abHome = route('airbnb.index');
    $abType = request('type');
@endphp

<header class="hm-header">
    <div class="hm-bar">

        <a href="{{ $abHome }}" class="hm-logo" aria-label="Airbnb home">
            <img src="{{ $abLogo }}" alt="">
            <span>AIRBNB</span>
        </a>

        {{-- live search like the shops (resources/js/hmsearch.js): suggestions while typing, Enter shows all results (?q=) --}}
        <div class="hm-search" data-hm-search>
            <input type="search" value="{{ request('q') }}" placeholder="Search studio, 1 bedroom, Kilimani…" aria-label="Search stays" readonly>
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            @include('Airbnb.partials.search-box')
        </div>

        {{-- the visitor's location: stays within 500 m … 5 km of it --}}
        <button type="button" class="hm-deliver" onclick="showModal('mapModal')">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <span>
                <small>Stays near</small>
                <strong id="current-delivery-location">{{ $customer->delivery_address ?? 'Set your location' }}</strong>
            </span>
            <i class="fa-solid fa-chevron-down hm-deliver-caret" aria-hidden="true"></i>
        </button>

        <a href="{{ $customer ? route('hub.index') : route('login') }}" @class(['hm-account', 'hm-account--guest' => !$customer])
           aria-label="{{ $customer ? 'My account' : 'Login or register' }}">
            <i class="fa-regular fa-user" aria-hidden="true"></i>
            @if ($customer)
                <span class="hm-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($customer->name ?? 'U'), 0, 1)) }}</span>
                <span class="hm-account-full">{{ \Illuminate\Support\Str::of($customer->name ?? '')->explode(' ')->first() }}</span>
            @else
                <span class="hm-account-full">Login &amp; Register</span>
                <span class="hm-account-short">Login</span>
            @endif
        </a>
    </div>

    <nav class="hm-categories" aria-label="Stay types">
        <div class="hm-categories-inner">
            <a href="{{ $abHome }}" @class(['active' => !$abType && !request()->filled('q') && request()->routeIs('airbnb.index')])><i class="ri-hotel-bed-line" aria-hidden="true"></i> All stays</a>
            @foreach (config('airbnb.types', []) as $key => [$label])
                <a href="{{ route('airbnb.index', ['type' => $key]) }}" @class(['active' => $abType === $key])>{{ $label }}</a>
            @endforeach
        </div>
    </nav>
</header>
