@extends('CookingGas.layouts.app')

@section('content')

<main class="wrapper">
    <div class="small-container single-product">
        <div class="product-row">

            {{-- 🔹 Product Images --}}
            <div class="product-col">
                <div class="main-img">
                    <img
                        src="{{ $product->image ? config('app.media_url') . '/' . ltrim($product->image, '/') : asset('img/placeholder.png') }}"
                        id="ProductImg"
                        alt="{{ $product->cylinder->brand_name ?? 'Gas Cylinder' }}"
                    >
                </div>

                {{-- Thumbnails --}}
                <div class="small-img-row">
                    @foreach ($thumbnails as $thumb)

                        @php
                            $isActive = $thumb->gas_quantity_id == $product->gas_quantity_id;

                            $sizeLabel = ($thumb->quantity->quantity ?? '') . ($thumb->quantity->unit ?? '');

                            $imageUrl = $thumb->image
                                ? config('app.media_url') . '/' . ltrim($thumb->image, '/')
                                : asset('img/placeholder.png');

                            // IMPORTANT: raw numeric price for JS
                            $rawPrice = $thumb->refill_price;
                        @endphp

                        <div class="small-img-col
                            @if($isActive) active-thumbnail @endif"

                            data-item-id="{{ $thumb->id }}"
                            data-full-url="{{ $imageUrl }}"
                            data-label="{{ $thumb->cylinder->brand_name ?? 'Gas Cylinder' }}"
                            data-packaging-name="Gas"

                            {{-- IMPORTANT: RAW VALUES (NOT FORMATTED) --}}
                            data-final-price="{{ $rawPrice }}"
                            data-original-price="{{ $rawPrice }}"

                            data-has-discount="false"
                            data-discount-percentage="0"

                            data-route-url="{{ route('gas.cylinder.view', [
                                'cylinder' => $thumb->gas_cylinder_id,
                                'quantity' => $thumb->gas_quantity_id,
                                'business' => $thumb->business_id
                            ]) }}"

                            data-price="{{ $rawPrice }}"
                            data-variant-label="{{ $sizeLabel }}"

                            data-business-account="{{ $thumb->business->name ?? '' }}"
                        >

                            <img
                                src="{{ $imageUrl }}"
                                class="small-img"
                                alt="{{ $sizeLabel }}"
                            >

                            <span style="display:block;font-size:10px;font-weight:bold;margin-top:3px;color:#878787;text-align:center;">
                                {{ $sizeLabel }}
                            </span>

                        </div>

                    @endforeach
                </div>
            </div>

            {{-- 🔹 Product Details --}}
            <div class="product-details">

                <h2 class="tittle" id="productTitle">
                    {{ $product->cylinder->brand_name ?? 'Unknown Brand' }}
                    ({{ $product->quantity->quantity ?? '' }}{{ $product->quantity->unit ?? '' }})
                </h2>

                

                <span>
                    <h5>Size:</h5>
                    <h5 id="productSize">{{ $product->quantity->quantity ?? '' }} {{ $product->quantity->unit ?? '' }}</h5>
                </span>
                


                {{-- Price --}}
                <span id="productPrice">
                    @if(isset($product->refill_price) && $product->refill_price > 0)
                        <h3>Ksh {{ number_format($product->refill_price) }}</h3>
                    @else
                        <h3>Price not available</h3>
                    @endif
                </span>
                

                {{-- Type --}}
                <span>
                    <i class="fa-solid fa-flask-vial"></i>
                    <h5>Type :</h5>
                    <h5>Refill</h5>
                </span>

                {{-- Quantity --}}
                <span class="btns countIcons">
                    <button class="dec">-</button>
                    <input type="text" name="quantity" value="1" maxlength="3" class="quantity-input">
                    <button class="inc">+</button>
                    <h4 class="add-to-cart-btn">
                        <i class="fa-solid fa-bag-shopping"></i>
                        Order
                    </h4>
                </span>

                {{-- Hidden fields for product data --}}
                <input type="hidden" id="productStockId" value="{{ $product->id }}">
                <input type="hidden" id="productStockType" value="gas">
                <input type="hidden" id="productSubdivision" value="{{ $currentSubdivision ?? 'cooking_gas' }}">
                <input type="hidden" id="productName" value="{{ $product->cylinder->brand_name ?? '' }} ({{ $product->quantity->quantity ?? '' }}{{ $product->quantity->unit ?? '' }})">
                <input type="hidden" id="productPriceValue" value="{{ $product->refill_price }}">
                <input type="hidden" id="productImage" value="{{ $product->image ?? '' }}">
                <input type="hidden" id="businessName" value="{{ $product->business->name ?? '' }}">
                <input type="hidden" id="stockableId" value="{{ $product->id }}">
                <input type="hidden" id="stockableType" value="cooking_gas_stock">
                <input type="hidden" id="subdivisionCode" value="{{ $currentSubdivision ?? 'cooking_gas' }}">

                {{-- Actions --}}
                <div class="actions">
                    <span>
                        <i class="ri-heart-add-line"></i>
                        <h5>Add to wishlist</h5>
                    </span>
                    <span>
                        <i class="ri-share-fill"></i>
                        <h5>Share</h5>
                    </span>
                </div>

                {{-- Business --}}
                <h4 class="sub-heading">Sold By</h4>
                <p id="sellerName">{{ $product->business->name ?? 'Unknown Seller' }}</p>

            </div>
        </div>

        {{-- Delivery --}}
        <div class="select-delivery">
            <h4 class="sub-heading">Delivery Option</h4>
            <div class="delivery-options">
                <div class="delivery-option active">
                    <i class="ri-e-bike-2-line"></i>
                    <h4>10 - 60 Mins</h4>
                    <h5>Quick</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- {{-- 🔹 Other Vendors (same product, different shops) --}}
    <div class="other-vendors">
        <h4 class="heading">Other Sellers:</h4>

        @forelse($otherVendors ?? [] as $vendor)

            @php
                $route = route('gas.cylinder.view', [
                    'cylinder' => $product->gas_cylinder_id,
                    'quantity' => $product->gas_quantity_id,
                    'business' => $vendor->business_id
                ]);

                $price = $vendor->refill_price;
            @endphp

            <div class="vendor js-vendor-switch"
                data-route-url="{{ $route }}"
                data-business-id="{{ $vendor->business_id }}"
                data-business-name="{{ $vendor->business->name }}"
                data-price="{{ $price }}">

                <div class="left">
                    <div class="logo">
                        <img src="{{ asset('img/logo/cookingGas.png') }}" alt="">
                    </div>
                </div>

                <div class="right">
                    <h4>{{ $vendor->business->name }}</h4>
                    <h5>Ksh {{ number_format($price) }}</h5>

                    <span>
                        <i class="fa-solid fa-location-dot"></i>
                        <h6>{{ $vendor->business->location ?? '' }}</h6>
                    </span>
                </div>

            </div>

        @empty
            <p>No other seller with this item.</p>
        @endforelse
    </div> -->
</main>

@endsection
