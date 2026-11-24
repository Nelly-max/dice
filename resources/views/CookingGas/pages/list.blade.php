@extends('CookingGas.layouts.app')

@section('title', $title)

@section('content')
{{--
    Cooking Gas list page (GasPagesController@cylinders and @shop): coloured header band,
    brand and size chips, sort, then the usual gas cards (CookingGas.Products.cylinders).
    Styles: shop-pages.css (sp-*), shared with the product shops' category pages.
--}}
@php
    $here = fn (array $q) => request()->fullUrlWithQuery(array_merge($q, ['page' => null]));
@endphp

<main class="wrapper sp-page">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('gas.products.index') }}">Cooking Gas</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        @if ($kind === 'shop')
            <a href="{{ route('gas.shops') }}">Shops</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        @elseif ($brandId)
            <a href="{{ route('gas.brands') }}">Brands</a>
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

    @if ($brands->count() > 1)
        <div class="sp-chips sp-chips--scroll" role="group" aria-label="Brands">
            <a href="{{ $here(['brand' => null]) }}" @class(['sp-chip', 'is-active' => !$brandId])>All brands</a>
            @foreach ($brands as $b)
                <a href="{{ $here(['brand' => $b->id]) }}" @class(['sp-chip', 'is-active' => $brandId === $b->id])>{{ $b->brand_name }}</a>
            @endforeach
        </div>
    @endif

    <div class="sp-tools">
        @if ($sizes->count() > 1)
            <div class="sp-chips" role="group" aria-label="Cylinder sizes">
                <a href="{{ $here(['size' => null]) }}" @class(['sp-chip', 'is-active' => !$sizeId])>All sizes</a>
                @foreach ($sizes as $s)
                    <a href="{{ $here(['size' => $s->id]) }}" @class(['sp-chip', 'is-active' => $sizeId === $s->id])>{{ is_numeric($s->quantity) ? $s->quantity . ' kg' : $s->quantity }}</a>
                @endforeach
            </div>
        @else
            <p class="sp-count">{{ $cylinders->count() }} {{ \Illuminate\Support\Str::plural('refill', $cylinders->count()) }}</p>
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

    @if ($cylinders->isNotEmpty())
        <div class="hm-grid sp-grid">
            @include('CookingGas.Products.cylinders', ['cylinders' => $cylinders])
        </div>
    @else
        <div class="hm-empty">
            <i class="fa-solid fa-fire-flame-simple" aria-hidden="true"></i>
            <h2>No refills here yet</h2>
            <p>{{ ($brandId || $sizeId) ? 'Nothing matches these filters. Try another brand or size.' : 'Gas shops will list their refills here soon.' }}</p>
            <a class="hm-btn" href="{{ ($brandId || $sizeId) ? $here(['brand' => null, 'size' => null]) : route('gas.products.index') }}">
                {{ ($brandId || $sizeId) ? 'Clear filters' : 'Back to Cooking Gas' }}
            </a>
        </div>
    @endif

    <section class="sp-more" aria-label="More">
        <h2>More to explore</h2>
        <div class="sp-chips">
            @if ($kind !== 'cylinders' || $brandId || $sizeId)
                <a href="{{ route('gas.cylinders') }}" class="sp-chip"><i class="fa-solid fa-fire-flame-simple" aria-hidden="true"></i> All gas cylinders</a>
            @endif
            <a href="{{ route('gas.brands') }}" class="sp-chip"><i class="fa-solid fa-tags" aria-hidden="true"></i> Brands</a>
            <a href="{{ route('gas.shops') }}" class="sp-chip"><i class="fa-solid fa-store" aria-hidden="true"></i> Shops near you</a>
        </div>
    </section>
</main>
@endsection
