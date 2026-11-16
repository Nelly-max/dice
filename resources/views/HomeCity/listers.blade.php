@extends('HomeCity.layouts.app')

@section('title', 'Listers')

@section('content')
{{-- Listers (PropertyController@listers): everyone with listings on Home City, nearest to the
     visitor's location first (or premium first, then by name). Each card opens the lister's
     profile with all their listings (homecity.lister). Same cards as the gas "Shops near you". --}}
<main class="wrapper hm-results">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('homecity.index') }}">Home City</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>Listers</span>
    </nav>

    <header class="hm-results-head lf-head">
        <div>
            <h1>Listers</h1>
            <p>
                {{ $listers->count() }} {{ \Illuminate\Support\Str::plural('lister', $listers->count()) }}
                · {{ $hasLocation ? 'nearest to you first' : 'agents, landlords and developers' }}
            </p>
        </div>
        @unless ($hasLocation)
            <button type="button" class="hm-btn lf-locate" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Set location</button>
        @endunless
    </header>

    @if ($listers->isNotEmpty())
        <div class="gp-grid">
            @foreach ($listers as $l)
                <a href="{{ $l->url }}" class="gp-card gp-card--shop">
                    <span class="gp-mark lf-lister-mark" aria-hidden="true">
                        @if ($l->logo)<img src="{{ $l->logo }}" alt="" loading="lazy" onerror="this.remove()">@endif
                        {{ $l->initial }}
                    </span>
                    <span class="gp-body">
                        <strong>{{ $l->name }}</strong>
                        <small>{{ $l->kinds->implode(' · ') }}@if ($l->area !== '') · {{ $l->area }}@endif</small>
                        <span class="gp-meta">
                            @if ($l->distance_km !== null)
                                <em class="gp-dist"><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $l->distance_km < 0.1 ? 'Under 100 m' : number_format($l->distance_km, 1) . ' km' }}</em>
                            @endif
                            <b>{{ $l->listings }} {{ \Illuminate\Support\Str::plural('listing', $l->listings) }}</b>
                            @if ($l->from > 0)<em>From KES {{ number_format($l->from) }}</em>@endif
                            @if ($l->premium)<em class="gp-tag gp-tag--ok">Premium</em>@endif
                        </span>
                    </span>
                    <i class="fa-solid fa-chevron-right gp-go" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    @else
        <div class="hm-empty">
            <i class="fa-solid fa-user-tie" aria-hidden="true"></i>
            <h2>No listers yet</h2>
            <p>Agents and landlords appear here once they list on Home City.</p>
            <a class="hm-btn" href="{{ route('homecity.index') }}">See all listings</a>
        </div>
    @endif
</main>
@endsection
