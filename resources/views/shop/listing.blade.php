{{--
    Category / Hot sales / Online exclusives page for the product shops (Web\Concerns\ShopPages).
    Uses the shop's own layout and item cards. Expects $meta (ShopCatalog::meta), $kind
    ('category' | 'deals' | 'exclusives'), $title, $icon, $products (simple paginator of
    presented rows), $sort, $sorts, $endsAt, $categories, $subs, $sub, $brands, $brand; for a
    category also $category.

    Layout (blending Carrefour's category pages with ours): coloured header band, filters in a
    sidebar on wide screens (categories, sub-categories, brands, on offer) and as chips on phones,
    a sort bar, the product grid, and "Load more" (resources/js/shop-pages.js appends the next
    page in place; without JS it is an ordinary link).
--}}
@extends($meta['views'] . '.layouts.app')

@section('title', $title . ' || ' . $meta['name'])

@section('content')
@php
    $route = $meta['route'];
    $home = route($route . '.home');
    $here = fn (array $q) => request()->fullUrlWithQuery(array_merge($q, ['page' => null]));
    $offersOnly = request()->boolean('offers');
    $isCategory = $kind === 'category';
    $activeFilters = ($sub ? 1 : 0) + ($brand !== '' ? 1 : 0) + ($offersOnly ? 1 : 0);
@endphp

<main class="wrapper sp-page">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ $home }}">{{ $meta['name'] }}</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        @if ($isCategory && $sub && ($subName = optional($subs->firstWhere('id', $sub))->name))
            <a href="{{ $here(['sub' => null]) }}">{{ $title }}</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span>{{ $subName }}</span>
        @else
            <span>{{ $title }}</span>
        @endif
    </nav>

    <header @class(['sp-hero', 'sp-hero--' . $kind])>
        <span class="sp-hero-icon" aria-hidden="true"><i class="{{ $icon }}"></i></span>
        <div class="sp-hero-text">
            <h1>{{ $title }}</h1>
            @if (!empty($intro))
                <p>{{ $intro }}</p>
            @else
                <p>
                    @if ($cartShopName) From {{ $cartShopName }}, the shop your cart is from.
                    @elseif ($hasLocation) From shops that deliver to you.
                    @else Set your delivery location to see what shops near you have.
                    @endif
                </p>
            @endif
        </div>
        @if ($endsAt)
            <span class="sp-countdown sp-countdown--big" data-countdown="{{ $endsAt->toIso8601String() }}">Ends soon</span>
        @elseif (!$isCategory)
            <span class="sp-hero-tag"><i class="fa-solid fa-tag" aria-hidden="true"></i> Limited time</span>
        @endif
    </header>

    {{-- phones: sub-categories as chips --}}
    @if ($isCategory && $subs->isNotEmpty())
        <div class="sp-chips sp-chips--scroll sp-only-narrow" role="group" aria-label="Sub-categories">
            <a href="{{ $here(['sub' => null]) }}" @class(['sp-chip', 'is-active' => !$sub])>All</a>
            @foreach ($subs as $s)
                <a href="{{ $here(['sub' => $s->id]) }}" @class(['sp-chip', 'is-active' => $sub === $s->id])>{{ $s->name }}</a>
            @endforeach
        </div>
    @endif

    <div class="sp-layout">

        {{-- filters (sidebar on wide screens, a sheet behind "Filters" on phones) --}}
        <aside class="sp-filters" id="spFilters" aria-label="Filters">
            <div class="sp-filters-head sp-only-narrow">
                <strong>Filters</strong>
                <button type="button" class="sp-x" data-sp-filters-close aria-label="Close filters"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </div>

            <section class="sp-fgroup">
                <h2>Offers</h2>
                <a href="{{ route($route . '.deals') }}" @class(['sp-flink', 'is-active' => $kind === 'deals'])><i class="fa-solid fa-fire" aria-hidden="true"></i> Hot sales</a>
                <a href="{{ route($route . '.exclusives') }}" @class(['sp-flink', 'is-active' => $kind === 'exclusives'])><i class="fa-solid fa-bolt" aria-hidden="true"></i> Online exclusives</a>
                @if ($isCategory)
                    <a href="{{ $here(['offers' => $offersOnly ? null : 1]) }}" @class(['sp-toggle', 'is-on' => $offersOnly]) role="switch" aria-checked="{{ $offersOnly ? 'true' : 'false' }}">
                        <span class="sp-toggle-track" aria-hidden="true"></span> On offer only
                    </a>
                @endif
            </section>

            @if ($isCategory && $subs->isNotEmpty())
                <section class="sp-fgroup">
                    <h2>{{ $title }}</h2>
                    <a href="{{ $here(['sub' => null]) }}" @class(['sp-flink', 'is-active' => !$sub])>All {{ $title }}</a>
                    @foreach ($subs as $s)
                        <a href="{{ $here(['sub' => $s->id]) }}" @class(['sp-flink', 'is-active' => $sub === $s->id])>
                            {{ $s->name }} @if (!empty($s->count))<small>{{ $s->count }}</small>@endif
                        </a>
                    @endforeach
                </section>
            @endif

            @if ($isCategory && $brands->isNotEmpty())
                <section class="sp-fgroup">
                    <h2>Brands</h2>
                    <a href="{{ $here(['brand' => null]) }}" @class(['sp-flink', 'is-active' => $brand === ''])>All brands</a>
                    @foreach ($brands as $name => $n)
                        <a href="{{ $here(['brand' => $name]) }}" @class(['sp-flink', 'is-active' => $brand === $name])>{{ $name }} <small>{{ $n }}</small></a>
                    @endforeach
                </section>
            @endif

            @if ($categories->isNotEmpty())
                <section class="sp-fgroup">
                    <h2>Categories</h2>
                    @foreach ($categories as $c)
                        <a href="{{ route($route . '.category', $c->id) }}" @class(['sp-flink', 'is-active' => isset($category) && $c->id === $category->id])>
                            <i class="{{ \App\Support\ShopCatalog::icon($c->name) }}" aria-hidden="true"></i> {{ $c->name }}
                        </a>
                    @endforeach
                </section>
            @endif
        </aside>
        <div class="sp-scrim sp-only-narrow" data-sp-filters-close hidden></div>

        <section class="sp-results" aria-label="{{ $title }}">
            {{-- sort bar --}}
            <div class="sp-tools">
                <button type="button" class="sp-chip sp-only-narrow" data-sp-filters-open aria-controls="spFilters">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i> Filters @if ($activeFilters)<b>{{ $activeFilters }}</b>@endif
                </button>

                <p class="sp-count">
                    {{ $products->count() }}{{ $products->hasMorePages() ? '+' : '' }} {{ \Illuminate\Support\Str::plural('item', $products->count()) }}
                    @if ($brand !== '') · {{ $brand }} <a href="{{ $here(['brand' => null]) }}" aria-label="Remove brand filter">✕</a>@endif
                    @if ($offersOnly) · on offer <a href="{{ $here(['offers' => null]) }}" aria-label="Remove offer filter">✕</a>@endif
                </p>

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

            @if ($products->count())
                <div class="hm-grid sp-grid" data-sp-grid>
                    @include($meta['views'] . '.products.items', ['products' => collect($products->items())])
                </div>

                @if ($products->nextPageUrl())
                    <div class="sp-more-wrap">
                        <a href="{{ $products->nextPageUrl() }}" class="sv-btn sp-load-more" data-sp-more>
                            Load more <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
                        </a>
                    </div>
                @endif
            @else
                <div class="hm-empty">
                    <i class="{{ $icon }}" aria-hidden="true"></i>
                    <h2>{{ $isCategory ? 'Nothing here yet' : 'No offers right now' }}</h2>
                    <p>
                        @if ($kind === 'deals') Shops near you have no discounts running at the moment. Check back soon.
                        @elseif ($kind === 'exclusives') No shop near you has an online offer running right now. Check back soon.
                        @elseif ($offersOnly || $brand !== '') Nothing matches these filters. Try removing one.
                        @else Shops near you don’t have anything in {{ $title }} yet.
                        @endif
                    </p>
                    <a class="hm-btn" href="{{ ($offersOnly || $brand !== '') ? $here(['offers' => null, 'brand' => null]) : $home }}">
                        {{ ($offersOnly || $brand !== '') ? 'Clear filters' : 'Back to ' . $meta['name'] }}
                    </a>
                </div>
            @endif
        </section>
    </div>

    {{-- other categories --}}
    @if ($categories->count() > 1)
        <section class="sp-more" aria-label="More categories">
            <h2>More to explore</h2>
            <div class="sp-chips">
                @if ($kind !== 'deals')
                    <a href="{{ route($route . '.deals') }}" class="sp-chip sp-chip--hot"><i class="fa-solid fa-fire" aria-hidden="true"></i> Hot sales</a>
                @endif
                @if ($kind !== 'exclusives')
                    <a href="{{ route($route . '.exclusives') }}" class="sp-chip sp-chip--excl"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Online exclusives</a>
                @endif
                @foreach ($categories as $c)
                    @if (!isset($category) || $c->id !== $category->id)
                        <a href="{{ route($route . '.category', $c->id) }}" class="sp-chip"><i class="{{ \App\Support\ShopCatalog::icon($c->name) }}" aria-hidden="true"></i> {{ $c->name }}</a>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
</main>
@endsection
