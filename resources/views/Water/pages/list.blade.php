@extends('Water.layouts.app')

@section('title', $title)

@section('content')
{{--
    Water list page (WaterController@refill / @bottled / @clean / @shop): coloured header band,
    size chips, sort, then water cards (Water.partials.product-card).
    Styles: shop-pages.css (sp-*), shared with the other shops' category pages.
--}}
@php
    $here = fn (array $q) => request()->fullUrlWithQuery(array_merge($q, ['page' => null]));
    $noun = $option === 'truck' ? 'water truck' : ($option === 'bottled' ? 'bottle' : ($option === 'refill' ? 'refill' : 'item'));
@endphp

<main class="wrapper sp-page">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('water.index') }}">Water</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        @if ($shop)
            <a href="{{ route('water.shops') }}">Shops</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        @endif
        <span>{{ $title }}</span>
    </nav>

    <header class="sp-hero">
        <span class="sp-hero-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
        <div class="sp-hero-text">
            <h1>{{ $title }}</h1>
            <p>{{ $intro }}</p>
        </div>
        @if (!$hasLocation)
            <button type="button" class="sp-hero-tag" onclick="showModal('mapModal')"><i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Set location</button>
        @endif
    </header>

    @if ($shop)
        {{-- one shop: jump between what it sells --}}
        @php $sells = $offers->pluck('option')->unique(); @endphp
        @if ($sells->count() > 1)
            <div class="sp-chips sp-chips--scroll" role="group" aria-label="What this shop sells">
                @foreach ($options as $key => $opt)
                    @if ($sells->contains($key))
                        <a href="#wt-{{ $key }}" class="sp-chip"><i class="{{ $opt['icon'] }}" aria-hidden="true"></i> {{ $opt['label'] }}</a>
                    @endif
                @endforeach
            </div>
        @endif
    @endif

    <div class="sp-tools">
        @if ($sizes->count() > 1)
            <div class="sp-chips" role="group" aria-label="Sizes">
                <a href="{{ $here(['size' => null]) }}" @class(['sp-chip', 'is-active' => $size === ''])>All sizes</a>
                @foreach ($sizes as $s)
                    <a href="{{ $here(['size' => $s]) }}" @class(['sp-chip', 'is-active' => $size === $s])>{{ $s }}</a>
                @endforeach
            </div>
        @else
            <p class="sp-count">{{ $offers->count() }} {{ \Illuminate\Support\Str::plural($noun, $offers->count()) }}</p>
        @endif

        <form class="sp-sort" method="get" action="{{ url()->current() }}">
            @foreach (request()->except(['sort', 'page']) as $k => $v)
                @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
            @endforeach
            <label>
                <span>Sort by</span>
                <select name="sort" onchange="this.form.submit()">
                    @foreach ($sorts as $key => $label)
                        <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button type="submit" class="sv-btn">Go</button></noscript>
        </form>
    </div>

    @if ($offers->isEmpty())
        <div class="hm-empty">
            <i class="{{ $icon }}" aria-hidden="true"></i>
            @if ($size !== '')
                <h2>Nothing in this size</h2>
                <p>Try another size.</p>
                <a class="hm-btn" href="{{ $here(['size' => null]) }}">Clear filters</a>
            @elseif ($outOfRange)
                <h2>No water shops near you yet</h2>
                <p>No open water shop delivers to this location yet. Try a different delivery location.</p>
                <button type="button" class="hm-btn" onclick="showModal('mapModal')">Change location</button>
            @else
                <h2>Nothing here yet</h2>
                <p>Water shops near you will list their prices here soon.</p>
                <a class="hm-btn" href="{{ route('water.index') }}">Back to Water</a>
            @endif
        </div>
    @elseif ($shop)
        @foreach ($options as $key => $opt)
            @php $group = $offers->where('option', $key)->values(); @endphp
            @if ($group->isNotEmpty())
                <h2 class="sp-group" id="wt-{{ $key }}"><i class="{{ $opt['icon'] }}" aria-hidden="true"></i> {{ $opt['label'] }}</h2>
                <div class="hm-grid sp-grid">
                    @foreach ($group as $offer)
                        @include('Water.partials.product-card', ['offer' => $offer])
                    @endforeach
                </div>
            @endif
        @endforeach
    @else
        <div class="hm-grid sp-grid">
            @foreach ($offers as $offer)
                @include('Water.partials.product-card', ['offer' => $offer])
            @endforeach
        </div>
    @endif

    <section class="sp-more" aria-label="More">
        <h2>More to explore</h2>
        <div class="sp-chips">
            @foreach ($options as $key => $opt)
                @if ($shop || $option !== $key)
                    <a href="{{ route($opt['route']) }}" class="sp-chip"><i class="{{ $opt['icon'] }}" aria-hidden="true"></i> {{ $opt['label'] }}</a>
                @endif
            @endforeach
            <a href="{{ route('water.shops') }}" class="sp-chip"><i class="fa-solid fa-store" aria-hidden="true"></i> Shops near you</a>
        </div>
    </section>
</main>
@endsection
