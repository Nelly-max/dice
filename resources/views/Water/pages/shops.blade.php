@extends('Water.layouts.app')

@section('title', 'Water shops near you')

@section('content')
{{-- Water shops (WaterController@shops): nearest to the saved delivery location first,
     "Delivers to you" inside the delivery radius. Each card opens the shop's price list (water.shop). --}}
<main class="wrapper sp-page">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('water.index') }}">Water</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>Shops</span>
    </nav>

    <header class="sp-hero">
        <span class="sp-hero-icon" aria-hidden="true"><i class="fa-solid fa-store"></i></span>
        <div class="sp-hero-text">
            <h1>Water shops near you</h1>
            <p>
                @if ($hasLocation) Nearest to your delivery location first. Shops within {{ $radiusKm }} km deliver to you.
                @else Set your delivery location to see the water shops closest to you.
                @endif
            </p>
        </div>
        @if (!$hasLocation)
            <button type="button" class="sp-hero-tag" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Set location</button>
        @endif
    </header>

    @if ($shops->isNotEmpty())
        <div class="gp-grid">
            @foreach ($shops as $s)
                <a href="{{ $s->url }}" @class(['gp-card', 'gp-card--shop', 'is-far' => $hasLocation && !$s->delivers])>
                    <span class="gp-mark" aria-hidden="true">{{ $s->initial }}@if ($s->open)<i class="gp-open"></i>@endif</span>
                    <span class="gp-body">
                        <strong>{{ $s->name }}</strong>
                        <small>{{ $s->sells->implode(' · ') }}</small>
                        <span class="gp-meta">
                            @if ($s->distance_m !== null)
                                <em class="gp-dist"><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $s->distance_m < 100 ? 'Under 100 m' : number_format($s->distance_m / 1000, 1) . ' km' }}</em>
                            @endif
                            <b>From KES {{ number_format($s->from) }}</b>
                            @if ($hasLocation)
                                <em @class(['gp-tag', 'gp-tag--ok' => $s->delivers])>{{ $s->delivers ? 'Delivers to you' : 'Too far to deliver' }}</em>
                            @endif
                            @unless ($s->open)<em class="gp-tag">Closed now</em>@endunless
                        </span>
                    </span>
                    <i class="fa-solid fa-chevron-right gp-go" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    @else
        <div class="hm-empty">
            <i class="fa-solid fa-store" aria-hidden="true"></i>
            @if ($outOfRange ?? false)
                <h2>No water shops near you yet</h2>
                <p>No open water shop delivers to this location yet. Try a different delivery location.</p>
                <button type="button" class="hm-btn" onclick="showModal('mapModal')">Change location</button>
            @else
                <h2>No water shops yet</h2>
                <p>Water shops appear here once they list their prices.</p>
                <a class="hm-btn" href="{{ route('water.index') }}">Back to Water</a>
            @endif
        </div>
    @endif
</main>
@endsection
