@extends('Airbnb.layouts.app')

@section('title', $q !== '' ? 'Search: ' . $q : 'Short stays')

@section('content')
{{--
    Airbnb home (Airbnb\StayController@index). Same structure as Home Market's home: hero,
    distance bar (500 m … 5 km from the visitor's location), info bar, type tiles, rows of cards.
    ?q= (header search) or ?type= (header strip / tiles) show a results grid instead.
--}}
@php
    $hero = [
        ['url' => asset('img/homeCity/HOMECITY_MAIN_IMG_001.jpg'), 'alt' => ''],
        ['url' => asset('img/homeCity/HOMECITY_BEDROOM_001.jpg'), 'alt' => ''],
        ['url' => asset('img/homeCity/H001VE001.png'), 'alt' => ''],
    ];
    $radius = $radiusBar['radius'];
@endphp

<main @class(['wrapper', 'hm-results' => $filtering])>

    @if ($sample ?? false)
        @include('partials.preview-banner', ['text' => 'These are sample stays to show how Airbnb on Smart Market will work. Booking opens soon.'])
    @endif

    @if ($filtering)

        <header class="hm-results-head">
            <h1>
                @if ($q !== '') Results for <em>“{{ $q }}”</em>@if (isset($types[$type])) in {{ $types[$type][0] }}@endif
                @else {{ $types[$type][0] }}
                @endif
            </h1>
            <p>{{ $results->count() }} {{ \Illuminate\Support\Str::plural('stay', $results->count()) }}</p>
        </header>

        @include('partials.radius-bar', $radiusBar)

        @if ($results->isNotEmpty())
            <div class="hm-grid hc-grid">
                @foreach ($results as $stay)
                    @include('Airbnb.partials.stay-card', ['stay' => $stay])
                @endforeach
            </div>
        @else
            <div class="hm-empty">
                <i class="fa-solid fa-bed" aria-hidden="true"></i>
                <h2>No stays found</h2>
                <p>{{ $radius ? 'Nothing within ' . \App\Support\Distance::label($radius) . ' of you. Try a wider distance or another area.' : 'Try another area or stay type.' }}</p>
                <a class="hm-btn" href="{{ route('airbnb.index') }}">See all stays</a>
            </div>
        @endif

    @else

        @include('HomeMarket.partials.hero', ['slides' => $hero])

        @include('partials.radius-bar', $radiusBar)

        <div class="info-bar">
            <a href="#top-stays" class="info">
                <i class="fa-solid fa-calendar-check" style="--icon: #7c3aed" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Book by the night</h3>
                    <h4>Full price shown upfront</h4>
                </div>
            </a>
            <a href="#top-stays" class="info">
                <i class="fa-solid fa-images" style="--icon: #00c2d1" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Real photos</h3>
                    <h4>See every room first</h4>
                </div>
            </a>
            <a href="{{ route('page', 'help') }}" class="info">
                <i class="fa-solid fa-mobile-screen-button" style="--icon: #00d26a" aria-hidden="true"></i>
                <div class="info-txt">
                    <h3>Pay with M-Pesa</h3>
                    <h4>Confirmed instantly</h4>
                </div>
            </a>
        </div>

        <section class="category-icons main-cat-icons hc-tiles" aria-label="Stay types">
            @foreach ($types as $key => [$label, $icon])
                <a href="{{ route('airbnb.index', ['type' => $key]) }}" class="category-icon">
                    <span class="hc-tile-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </section>

        @include('Airbnb.partials.stay-row', ['id' => 'top-stays', 'title' => $radiusBar['hasLocation'] ? 'Nearest to you' : 'Popular stays', 'items' => $radiusBar['hasLocation'] ? $stays : $stays->sortByDesc(fn ($s) => [(int) !empty($s->featured), (float) ($s->rating ?? 0)])->values()])

        @include('Airbnb.partials.stay-row', ['title' => 'Under KES 5,000 a night', 'items' => $stays->where('price', '<', 5000)->values()])

        @include('Airbnb.partials.stay-row', ['title' => 'For families and groups', 'items' => $stays->filter(fn ($s) => ($s->guests ?? 0) >= 4 || ($s->bedrooms ?? 0) >= 2)->values()])

        @include('Airbnb.partials.stay-row', ['title' => 'Featured stays', 'items' => $stays->filter(fn ($s) => !empty($s->featured) || !empty($s->superhost))->values()])

        @if ($stays->isEmpty())
            <div class="hm-empty">
                <i class="fa-solid fa-bed" aria-hidden="true"></i>
                @if ($radius && $totalStays > 0)
                    <h2>Nothing within {{ \App\Support\Distance::label($radius) }}</h2>
                    <p>No stays this close to your location yet.</p>
                    <a class="hm-btn" href="{{ request()->fullUrlWithQuery(['radius' => 'any']) }}">Show all distances</a>
                @else
                    <h2>No stays yet</h2>
                    <p>New stays will appear here soon.</p>
                @endif
            </div>
        @endif

    @endif

</main>

@endsection
