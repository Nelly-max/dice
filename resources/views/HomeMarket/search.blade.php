@extends('HomeMarket.layouts.app')

@section('title', $term !== '' ? "Search: {$term} || HomeMarket" : 'Search || HomeMarket')

@section('content')
<main class="wrapper hm-results">
    <header class="hm-results-head">
        <h1>
            @if ($term === '') Search HomeMarket
            @else Results for <em>“{{ $term }}”</em>
            @endif
        </h1>
        @if ($term !== '' && mb_strlen($term) >= 2)
            <p>{{ $products->count() }} {{ \Illuminate\Support\Str::plural('item', $products->count()) }}
                @if ($cartShopName) from {{ $cartShopName }} @elseif ($hasLocation) from shops near you @endif</p>
        @endif
    </header>

    @if ($products->isNotEmpty())
        <div class="hm-grid">
            @include('HomeMarket.products.items')
        </div>
    @else
        <div class="hm-empty">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <h2>{{ mb_strlen($term) < 2 ? 'Type at least 2 letters' : 'Nothing found' }}</h2>
            <p>
                @if (mb_strlen($term) >= 2)
                    We couldn’t find “{{ $term }}” in {{ $cartShopName ?: ($hasLocation ? 'shops near you' : 'any shop') }}.
                    Check the spelling or try a shorter word.
                @else
                    Search by product, brand or shop name.
                @endif
            </p>
            <a class="hm-btn" href="{{ route('homemarket.home') }}">Back to shop</a>
        </div>
    @endif
</main>
@endsection
