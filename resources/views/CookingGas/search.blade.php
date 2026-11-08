@extends('CookingGas.layouts.app')

@section('title', $term !== '' ? "Search: {$term}" : 'Search')

@section('content')
{{-- Full search results (ProductController@search), same page as Home Market's search. --}}
@php $items = collect($cylinders instanceof \Illuminate\Contracts\Pagination\Paginator ? $cylinders->items() : $cylinders); @endphp
<main class="wrapper hm-results">
    <header class="hm-results-head">
        <h1>
            @if ($term === '') Search Cooking Gas
            @else Results for <em>“{{ $term }}”</em>
            @endif
        </h1>
        @if ($term !== '')
            <p>{{ $items->count() }} {{ \Illuminate\Support\Str::plural('refill', $items->count()) }}</p>
        @endif
    </header>

    @if ($items->isNotEmpty())
        <div class="hm-grid">
            @include('CookingGas.Products.cylinders', ['cylinders' => $items])
        </div>

        @if ($cylinders instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $cylinders->hasPages())
            <div class="hm-pages">{{ $cylinders->withQueryString()->links() }}</div>
        @endif
    @else
        <div class="hm-empty">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <h2>Nothing found</h2>
            <p>We couldn’t find “{{ $term }}”. Check the spelling or try another brand.</p>
            <a class="hm-btn" href="{{ route('gas.products.index') }}">Back to Cooking Gas</a>
        </div>
    @endif
</main>
@endsection
