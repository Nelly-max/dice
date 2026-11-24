@extends($meta['views'] . '.layouts.app')

@section('title', 'Shops near you || ' . $meta['name'])

@section('content')
{{-- Shops near you for the product shops (Web\Concerns\ShopPages@shops): open shops selling
     online that deliver to the buyer, nearest first. Each card opens the shop's own page
     ({route}.shop) with everything it sells. Card styles: gp-* in shop-pages.css. --}}
@php $route = $meta['route']; @endphp
<main class="wrapper sp-page">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route($route . '.home') }}">{{ $meta['name'] }}</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>Shops</span>
    </nav>

    <header class="sp-hero">
        <span class="sp-hero-icon" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
        <div class="sp-hero-text">
            <h1>Shops near you</h1>
            <p>
                @if ($hasLocation) Nearest to your delivery location first. Shops within {{ $radiusKm }} km deliver to you.
                @else Set your delivery location to see the shops closest to you.
                @endif
                @if ($cartShopName) Your cart is from {{ $cartShopName }}. @endif
            </p>
        </div>
        @if (!$hasLocation)
            <button type="button" class="sp-hero-tag" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Set location</button>
        @endif
    </header>

    @if ($shops->isNotEmpty())
        <div class="gp-grid">
            @foreach ($shops as $s)
                <a href="{{ $s->url }}" class="gp-card gp-card--shop">
                    <span class="gp-mark" aria-hidden="true">{{ $s->initial }}<i class="gp-open"></i></span>
                    <span class="gp-body">
                        <strong>{{ $s->name }}</strong>
                        <span class="gp-meta">
                            @if ($s->distance_km !== null)
                                <em class="gp-dist"><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $s->distance_km < 0.1 ? 'Under 100 m' : $s->distance_km . ' km' }}</em>
                                <em class="gp-dist"><i class="fa-regular fa-clock" aria-hidden="true"></i> ~{{ $s->eta_min }} min</em>
                            @endif
                            @if ($s->in_cart)
                                <em class="gp-tag gp-tag--ok">Your cart</em>
                            @elseif ($hasLocation)
                                <em class="gp-tag gp-tag--ok">Delivers to you</em>
                            @endif
                        </span>
                    </span>
                    <i class="fa-solid fa-chevron-right gp-go" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    @else
        @include('partials.shops-empty', [
            'what' => $meta['name'] . ' shops',
            'state' => $hasLocation ? 'far' : 'none',
            'radiusKm' => $radiusKm ?? null,
            'homeUrl' => route($route . '.home'),
            'homeLabel' => $meta['name'],
        ])
    @endif
</main>
@endsection
