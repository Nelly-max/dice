@extends('CookingGas.layouts.app')

@section('title', 'Gas brands')

@section('content')
{{-- Gas brands (GasPagesController@brands): one card per brand → its refills (gas.cylinders?brand=) --}}
<main class="wrapper sp-page">

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('gas.products.index') }}">Cooking Gas</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>Brands</span>
    </nav>

    <header class="sp-hero">
        <span class="sp-hero-icon" aria-hidden="true"><i class="fa-solid fa-tags"></i></span>
        <div class="sp-hero-text">
            <h1>Gas brands</h1>
            <p>Pick a brand to see its refills and the shops that have them.</p>
        </div>
    </header>

    @if ($brands->isNotEmpty())
        <div class="gp-grid">
            @foreach ($brands as $b)
                <a href="{{ $b->url }}" class="gp-card">
                    <span class="gp-pic" aria-hidden="true">
                        @if ($b->image)
                            <img src="{{ $b->image }}" alt="" loading="lazy" onerror="this.replaceWith(Object.assign(document.createElement('i'),{className:'fa-solid fa-fire-flame-simple'}))">
                        @else
                            <i class="fa-solid fa-fire-flame-simple"></i>
                        @endif
                    </span>
                    <span class="gp-body">
                        <strong>{{ $b->name }}</strong>
                        <small>{{ $b->sizes->implode(' · ') }}</small>
                        <span class="gp-meta">
                            <b>From KES {{ number_format($b->from) }}</b>
                            <em>{{ $b->shops }} {{ \Illuminate\Support\Str::plural('shop', $b->shops) }}</em>
                        </span>
                    </span>
                    <i class="fa-solid fa-chevron-right gp-go" aria-hidden="true"></i>
                </a>
            @endforeach
        </div>
    @else
        <div class="hm-empty">
            <i class="fa-solid fa-tags" aria-hidden="true"></i>
            <h2>No brands yet</h2>
            <p>Gas brands appear here once shops list their refills.</p>
            <a class="hm-btn" href="{{ route('gas.products.index') }}">Back to Cooking Gas</a>
        </div>
    @endif
</main>
@endsection
