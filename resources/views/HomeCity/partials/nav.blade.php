@php
    $customer = auth('customer')->user();
    $hcLogo = (($subdivisionName ?? 'Marketplace') !== 'Marketplace' && !empty($logoUrl)) ? $logoUrl : asset('img/logo/homecity.png');
    $hcHome = route('homecity.index');
    $hcType = request('type');
    // edit the strip here; "type" is matched in HomeCity/home.blade.php
    $hcTypes = [
        'rent' => 'For rent',
        'sale' => 'For sale',
        'home' => 'Homes',
        'hostel' => 'Hostels',
        'commercial' => 'Shops & offices',
        'warehouse' => 'Warehouses',
        'container' => 'Containers',
        'land' => 'Land',
    ];
@endphp

<header class="hm-header">
    <div class="hm-bar">

        <a href="{{ $hcHome }}" class="hm-logo" aria-label="Home City home">
            <img src="{{ $hcLogo }}" alt="">
            <span>HOME CITY</span>
        </a>

        {{-- live search like the shops (resources/js/hmsearch.js): suggestions while typing, Enter shows all results (?q=) --}}
        <div class="hm-search" data-hm-search>
            <input type="search" value="{{ request('q') }}" placeholder="Search bedsitter, 2 bedroom, Kilimani…" aria-label="Search homes and properties" readonly>
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            @include('HomeCity.partials.search-box')
        </div>

        {{-- the visitor's location: Home City shows listings within 500 m … 5 km of it --}}
        <button type="button" class="hm-deliver" onclick="showModal('mapModal')">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            <span>
                <small>Homes near</small>
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

    <nav class="hm-categories" aria-label="Property types">
        <div class="hm-categories-inner">
            <a href="{{ $hcHome }}" @class(['active' => !$hcType && !request()->filled('q') && request()->routeIs('homecity.index')])><i class="ri-building-line" aria-hidden="true"></i> All listings</a>
            @foreach ($hcTypes as $key => $label)
                <a href="{{ route('homecity.index', ['type' => $key]) }}" @class(['active' => $hcType === $key])>{{ $label }}</a>
            @endforeach
        </div>
    </nav>
</header>
