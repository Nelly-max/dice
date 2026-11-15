@extends('CookingGas.layouts.app')

@section('title', ($product->cylinder->brand_name ?? 'Gas') . ' refill')

@section('content')
{{--
    One gas refill from one shop (ProductController@viewCylinder). Same page as Home Market's
    product page (HomeMarket/products/viewItem). Hooks kept for the scripts:
      .small-img-col (+ data-*)                 size switch (reused.js / cart.js)
      #ProductImg #productTitle #productSize #productPrice #sellerName   updated on size switch
      .pdp .btns .dec / .inc / .quantity-val    quantity stepper (cart.js)
      #AddToCartBtn.add-to-cart-btn + hidden #product… / #stockable… inputs   add to cart (cart.js)
--}}
@php
    $brand = $product->cylinder->brand_name ?? 'Gas Cylinder';
    $size = trim(($product->quantity->quantity ?? '') . ' ' . ($product->quantity->unit ?? ''));
    $shop = $product->business->name ?? null;
    $mainImage = $product->image
        ? rtrim(config('app.media_url'), '/') . '/' . ltrim($product->image, '/')
        : asset('img/placeholder.png');
@endphp

<main class="wrapper pdp">
    <div class="small-container single-product">
        <div class="product-row">

            <div class="product-col">
                <div class="main-img">
                    <img src="{{ $mainImage }}" id="ProductImg" alt="{{ $brand }}">
                </div>

                {{-- other sizes from the same shop --}}
                <div class="small-img-row">
                    @foreach ($thumbnails as $thumb)
                        @php
                            $sizeLabel = ($thumb->quantity->quantity ?? '') . ($thumb->quantity->unit ?? '');
                            $imageUrl = $thumb->image
                                ? rtrim(config('app.media_url'), '/') . '/' . ltrim($thumb->image, '/')
                                : asset('img/placeholder.png');
                            $rawPrice = $thumb->refill_price;   // raw number for the scripts
                        @endphp

                        <div @class(['small-img-col', 'active-thumbnail' => $thumb->gas_quantity_id == $product->gas_quantity_id])
                             data-item-id="{{ $thumb->id }}"
                             data-full-url="{{ $imageUrl }}"
                             data-label="{{ $thumb->cylinder->brand_name ?? 'Gas Cylinder' }}"
                             data-packaging-name="Gas"
                             data-final-price="{{ $rawPrice }}"
                             data-original-price="{{ $rawPrice }}"
                             data-has-discount="false"
                             data-discount-percentage="0"
                             data-route-url="{{ route('gas.cylinder.view', ['cylinder' => $thumb->gas_cylinder_id, 'quantity' => $thumb->gas_quantity_id, 'business' => $thumb->business_id]) }}"
                             data-price="{{ $rawPrice }}"
                             data-variant-label="{{ $sizeLabel }}"
                             data-business-account="{{ $thumb->business->name ?? '' }}">
                            <span class="sit-pic"><img src="{{ $imageUrl }}" class="small-img" alt="{{ $sizeLabel }}" loading="lazy"></span>
                            <span class="sit-label">{{ $sizeLabel }}</span>
                            <span class="sit-price">KES {{ number_format($rawPrice) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="product-details">
                <small class="brand">Refill</small>

                <h2 class="tittle" id="productTitle">{{ $brand }} ({{ $size }})</h2>

                <span>
                    <h5>size:</h5>
                    <h5 id="productSize">{{ $size }}</h5>
                </span>

                {{-- reused.js rewrites #productPrice as <h3>Ksh …</h3> on a size switch --}}
                <span id="productPrice">
                    @if (isset($product->refill_price) && $product->refill_price > 0)
                        <h3>Ksh {{ number_format($product->refill_price) }}</h3>
                    @else
                        <h3>Price not available</h3>
                    @endif
                </span>

                <span>
                    <i class="fa-solid fa-flask-vial"></i>
                    <h5>Type:</h5>
                    <h5>Refill (swap your empty cylinder)</h5>
                </span>

                <div class="sold-by">
                    <span class="shop-chip-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($shop ?? 'G', 0, 1)) }}</span>
                    <span><small>Sold by</small><strong id="sellerName">{{ $shop ?? 'Unknown Seller' }}</strong></span>
                </div>

                {{-- once this item is in the cart: "− qty +" for the cart instead of the quantity + Add button
                     (cart.js syncCardCart; data-cart-map lets it follow the size/variant picked) --}}
                @php
                    $pdpInCart = \App\Support\CartQuantities::for('cooking_gas_stock', $product->id);
                    $pdpCartMap = collect(\App\Support\CartQuantities::all())->filter(fn ($v, $k) => str_starts_with($k, 'cooking_gas_stock:'));
                @endphp
                <div @class(['hm-cart-ctl', 'pdp-cart-ctl', 'is-in-cart' => $pdpInCart]) data-cart-key="cooking_gas_stock:{{ $product->id }}" data-cart-id="{{ $pdpInCart['id'] ?? '' }}" data-cart-type="cooking_gas_stock" data-cart-map='@json($pdpCartMap)'>
                    <span class="btns">
                        <button type="button" class="dec" aria-label="Less" disabled><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
                        <input type="number" class="quantity-val quantity-input" name="quantity" value="1" min="1" max="20" readonly aria-label="Quantity">
                        <button type="button" class="inc" aria-label="More"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>

                        <h4 class="add-to-cart-btn" id="AddToCartBtn" role="button" tabindex="0" data-item-id="{{ $product->id }}">
                            <i class="fa-solid fa-basket-shopping"></i>Order refill
                        </h4>
                    </span>
                    <div class="hm-stepper pdp-stepper" role="group" aria-label="In your cart">
                        <button type="button" class="hm-step" data-cart-step="decrease" aria-label="One less"><i @class(['fa-solid', 'fa-trash-can' => ($pdpInCart['qty'] ?? 0) <= 1, 'fa-minus' => ($pdpInCart['qty'] ?? 0) > 1]) aria-hidden="true"></i></button>
                        <span class="hm-step-qty"><b data-step-qty aria-live="polite">{{ $pdpInCart['qty'] ?? 0 }}</b> in your cart</span>
                        <button type="button" class="hm-step" data-cart-step="increase" aria-label="One more"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                    </div>
                </div>

                {{-- product data for cart.js --}}
                <input type="hidden" id="productStockId" value="{{ $product->id }}">
                <input type="hidden" id="productStockType" value="gas">
                <input type="hidden" id="productSubdivision" value="{{ $currentSubdivision ?? 'cooking_gas' }}">
                <input type="hidden" id="productName" value="{{ $brand }} ({{ $size }})">
                <input type="hidden" id="productPriceValue" value="{{ $product->refill_price }}">
                <input type="hidden" id="productImage" value="{{ $product->image ?? '' }}">
                <input type="hidden" id="businessName" value="{{ $shop ?? '' }}">
                <input type="hidden" id="stockableId" value="{{ $product->id }}">
                <input type="hidden" id="stockableType" value="cooking_gas_stock">
                <input type="hidden" id="subdivisionCode" value="{{ $currentSubdivision ?? 'cooking_gas' }}">

                <div class="actions">
                    <span>
                        <i class="fa-regular fa-heart"></i>
                        <h5>Add to wishlist</h5>
                    </span>

                    <button type="button" class="pdp-share" data-share
                            data-share-title="{{ $brand }} {{ $size }} refill"
                            data-share-text="KES {{ number_format($product->refill_price ?? 0) }}{{ $shop ? ' at ' . $shop : '' }} on Smart Market"
                            data-share-url="{{ url()->full() }}">
                        <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                        <h5>Share</h5>
                    </button>
                </div>

                <dl class="specs">
                    <div><dt>Brand</dt><dd>{{ $brand }}</dd></div>
                    <div><dt>Size</dt><dd>{{ $size }}</dd></div>
                    <div><dt>Type</dt><dd>Refill</dd></div>
                </dl>

                <h4 class="sub-heading">How it works</h4>
                <p>A rider brings a full {{ $brand }} cylinder and takes your empty {{ $brand }} cylinder of the same size. Have your empty cylinder ready.</p>
            </div>

        </div>

        <div class="select-delivery">
            <h4 class="sub-heading">Delivery options</h4>
            <div class="delivery-options">
                <div class="delivery-option active">
                    <i class="fa-solid fa-motorcycle"></i>
                    <h4>10 - 60 Mins</h4>
                    <h5>Quick delivery by a nearby rider</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- the same refill from other gas shops --}}
    @if (($otherVendors ?? collect())->isNotEmpty())
        <section class="pdp-related">
            <div class="cards-slider" data-gap="16" data-gap-sm="8" data-pad="0" data-min="176" data-min-sm="130">
                <div class="head">
                    <h3 class="sub-heading">Other shops with this refill</h3>
                    <span>
                        <i class="fa-solid fa-angle-left" role="button" aria-label="Previous"></i>
                        <i class="fa-solid fa-angle-right" role="button" aria-label="Next"></i>
                    </span>
                </div>
                <div class="items-container cards-container column-cards">
                    @foreach ($otherVendors as $vendor)
                        @php $vurl = route('gas.cylinder.view', ['cylinder' => $product->gas_cylinder_id, 'quantity' => $product->gas_quantity_id, 'business' => $vendor->business_id]); @endphp
                        <article class="item-container card-data">
                            <a href="{{ $vurl }}" class="thumb" aria-label="{{ $vendor->business->name ?? 'Gas shop' }}">
                                <img src="{{ $mainImage }}" alt="" loading="lazy">
                            </a>
                            <div class="item-body">
                                <div class="buy">
                                    <span class="prices"><strong class="now"><small>KES</small> {{ number_format($vendor->refill_price) }}</strong></span>
                                    
                                </div>
                                <span class="size">{{ $size }} refill</span>
                                <p class="name">{{ $vendor->business->name ?? 'Gas shop' }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>

@endsection
