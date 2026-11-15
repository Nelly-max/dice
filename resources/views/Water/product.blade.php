@extends('Water.layouts.app')

@section('title', $first->name)

@section('content')
{{--
    One container from one water shop (Water\WaterController@show). Same page as Home Market's
    product page: picture, sold by, then one line per option the shop sells for it
    (purified water refill / new bottle, or a truck trip), each with its own Add / "− qty +"
    (cart.js .hm-cart-ctl; the option is the cart's stockable type).
--}}
@php
    $shopName = $row->business->name ?? 'Water shop';
    $brand = $first->brand;
    $size = $first->size;
    $title = $first->option === 'truck' ? $first->name : trim(($brand ?: 'Water') . ' ' . $size);
@endphp

<main class="wrapper pdp">

    <div class="small-container single-product">
        <div class="product-row">

            <div class="product-col">
                <div class="main-img">
                    @if ($first->image)
                        <img src="{{ $first->image }}" id="ProductImg" alt="{{ $title }}">
                    @else
                        <span @class(['wt-art', 'wt-art--big', 'wt-art--' . $first->option]) aria-hidden="true"><i class="{{ $options[$first->option]['icon'] }}"></i><b>{{ $size }}</b></span>
                    @endif
                </div>
            </div>

            <div class="product-details">
                @if ($brand)<small class="brand">{{ $brand }}</small>@endif

                <h2 class="tittle">{{ $title }}</h2>

                <span>
                    <h5>Container:</h5>
                    <h5>{{ trim(($row->container->name ?? '') . ' · ' . $size, ' ·') }}</h5>
                </span>

                <span>
                    <h3>@if ($offers->count() > 1)<small>from</small>@endif Ksh {{ number_format($offers->min('price')) }}</h3>
                </span>

                <div class="wt-opts" role="list" aria-label="Choose how you want it">
                    @foreach ($offers as $offer)
                        @php
                            $opt = $options[$offer->option];
                            $inCart = \App\Support\CartQuantities::for($offer->type, $offer->id);
                        @endphp
                        <div class="wt-opt" role="listitem">
                            <span class="wt-opt-icon" aria-hidden="true"><i class="{{ $opt['icon'] }}"></i></span>
                            <span class="wt-opt-text">
                                <b>{{ $offer->option === 'bottled' ? 'New bottle' : ($offer->option === 'truck' ? 'Clean water by truck' : 'Refill') }}</b>
                                <small>
                                    @switch($offer->option)
                                        @case('refill') Your empty {{ $size }} bottle refilled with purified water @break
                                        @case('bottled') A new, sealed {{ $size }} bottle of water @break
                                        @default {{ $size }} of clean water to your tank, per trip
                                    @endswitch
                                </small>
                                <strong>KES {{ number_format($offer->price) }}</strong>
                            </span>

                            <div @class(['hm-cart-ctl', 'is-in-cart' => $inCart]) data-cart-key="{{ $offer->type }}:{{ $offer->id }}" data-cart-id="{{ $inCart['id'] ?? '' }}">
                                <button type="button" class="add-to-cart-btn"
                                        aria-label="Add {{ $offer->name }} to cart"
                                        data-stockable-id="{{ $offer->id }}"
                                        data-stockable-type="{{ $offer->type }}"
                                        data-business-account="{{ $offer->shop_account }}"
                                        data-subdivision-code="water"
                                        data-product-name="{{ $offer->name }}"
                                        data-image-url="{{ $offer->image }}"
                                        data-price="{{ $offer->price }}"
                                        data-variant-label="{{ $size }}">
                                    <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $offer->option === 'truck' ? 'Order' : 'Add' }}
                                </button>
                                <div class="hm-stepper" role="group" aria-label="{{ $offer->name }} in your cart">
                                    <button type="button" class="hm-step" data-cart-step="decrease" aria-label="One less"><i @class(['fa-solid', 'fa-trash-can' => ($inCart['qty'] ?? 0) <= 1, 'fa-minus' => ($inCart['qty'] ?? 0) > 1]) aria-hidden="true"></i></button>
                                    <span class="hm-step-qty" data-step-qty aria-live="polite">{{ $inCart['qty'] ?? 0 }}</span>
                                    <button type="button" class="hm-step" data-cart-step="increase" aria-label="One more"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- sold by (same block as the other shops' product pages) --}}
                <a href="{{ route('water.shop', $row->business->id) }}" class="sold-by" style="color:inherit;text-decoration:none">
                    <span class="shop-chip-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim($shopName) ?: 'W', 0, 1)) }}<i class="sold-by-open"></i></span>
                    <span><small>Sold by</small><strong>{{ $shopName }}</strong></span>
                    @if ($distance !== null)
                        <em class="dist"><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $distance < 100 ? 'Under 100 m' : number_format($distance / 1000, 1) . ' km away' }}</em>
                    @endif
                </a>

                <div class="actions">
                    <button type="button" class="pdp-share" data-share
                            data-share-title="{{ $title }}"
                            data-share-text="{{ $title }} from KES {{ number_format($offers->min('price')) }} on Smart Market"
                            data-share-url="{{ url()->current() }}">
                        <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                        <h5>Share</h5>
                    </button>
                </div>
            </div>

        </div>

        <div class="select-delivery">
            <h4 class="sub-heading">Delivery options</h4>
            <div class="delivery-options">
                <div class="delivery-option active">
                    <i class="fa-solid {{ $first->option === 'truck' ? 'fa-truck-droplet' : 'fa-motorcycle' }}"></i>
                    <h4>{{ $first->option === 'truck' ? 'The shop confirms the time' : config('water.delivery_eta') }}</h4>
                    <h5>From {{ $shopName }}, within {{ config('water.max_radius_m', 3000) / 1000 }} km</h5>
                </div>
            </div>
        </div>
    </div>

    <section class="pdp-related">
        @include('Water.partials.product-row', ['title' => 'More from ' . $shopName, 'items' => $sameShop, 'more' => route('water.shop', $row->business->id)])
        @include('Water.partials.product-row', ['title' => $size . ' from other shops', 'items' => $sameSize])
    </section>
</main>

@endsection
