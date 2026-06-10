@extends('CookingGas.layouts.app')

@section('content')

<main class="wrapper">

    {{-- 🔹 HERO SLIDER --}}
    <div class="slider">
        <div class="list">
            @foreach([1,2,3,1,2] as $i)
                <div class="item">
                    <img src="{{ asset("img/homeMarket/Hero_Banner_{$i}.jpg") }}" alt="">
                </div>
            @endforeach
        </div>

        <div class="buttons">
            <button id="prev">&lt;</button>
            <button id="next">&gt;</button>
        </div>

        <ul class="dots">
            @for($i = 0; $i < 5; $i++)
                <li class="{{ $i === 0 ? 'active' : '' }}"></li>
            @endfor
        </ul>
    </div>

    {{-- 🔹 PRODUCTS --}}
    <div class="products-container">

        @forelse($products as $product)
            <div class="product-container">

                <a href="{{ route('gas.products.show', [ 'cylinder' => $product->gas_cylinder_id, 'quantity' => $product->gas_quantity_id, ]) }}" class="img-area">

                    <img src="{{ $product->image ? config('app.media_url') . '/' . ltrim($product->image, '/') : asset('img/placeholder.png') }}" alt="{{ $product->cylinder->brand_name ?? 'Gas Brand' }}">
                </a>

                <div class="product-details">

                    <div class="product-det">
                        <span>Refill</span>
                    </div>

                    <h3 class="title">
                        {{ $product->cylinder->brand_name ?? 'Unknown Brand' }}
                        {{ $product->quantity->quantity ?? '' }}
                    </h3>

                    <div class="location">
                        <h5>{{ $product->business->name ?? 'Unknown Business' }}</h5>
                    </div>

                    <div class="details-footer">
                        @if($product->min_price == $product->max_price)
                            <h4>Ksh {{ number_format($product->min_price) }}</h4>
                        @else
                            <h4>
                                Ksh {{ number_format($product->min_price) }}
                                –
                                {{ number_format($product->max_price) }}
                            </h4>
                        @endif
                    </div>

                </div>
            </div>
        @empty
            <p>No gas products available.</p>
        @endforelse

    </div>

    {{-- 🔹 LEAFLET CAROUSEL --}}
    <section class="carousel-container">
        <i id="left" class="fa-solid fa-angle-left"></i>

        <ul class="carousel">
            @for($i = 1; $i <= 6; $i++)
                <li class="card">
                    <div class="img">
                        <img
                            src="{{ asset("img/homeMarket/LEAFLET_CARD_{$i}.png") }}"
                            draggable="false"
                        >
                    </div>
                </li>
            @endfor
        </ul>

        <i id="right" class="fa-solid fa-angle-right"></i>
    </section>

</main>

@endsection
