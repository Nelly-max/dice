@extends('HomeMarket.layouts.app')

@section('content')

<main class="wrapper">
    <div class="small-container single-product">
        <div class="product-row">

    <!-- Media Album Block (Main frame + dynamic item thumbnails) -->
    <div class="product-col">

        <!-- Main Display Media -->
        <div class="main-img">
            <img src="{{ $product->image_url }}" id="ProductImg" alt="{{ $product->product_name }}">
        </div>

        <!-- Thumbnail Variants Selection Rows -->
        <div class="small-img-row">

            @forelse($product->gallery_images ?? [] as $galleryImg)

                <div class="small-img-col
                    @if($galleryImg->item_id == $product->item_id) active-thumbnail @endif"

                    data-item-id="{{ $galleryImg->item_id }}"
                    data-full-url="{{ $galleryImg->full_url }}"
                    data-label="{{ $galleryImg->size_label }}"
                    data-packaging-name="{{ strtolower($product->packaging_name ?? '') }}"
                    data-final-price="Ksh {{ $galleryImg->final_price_formatted }}"
                    data-original-price="Ksh {{ $galleryImg->original_price_formatted }}"
                    data-has-discount="{{ $galleryImg->has_discount ? 'true' : 'false' }}"
                    data-discount-percentage="{{ $galleryImg->discount_percentage }}% off"
                    data-route-url="{{ route('item.view', $galleryImg->inventory_id) }}"

                    {{-- ✅ ADDED (DO NOT CHANGE YOUR EXISTING FIELDS) --}}
                    data-stockable-id="{{ $galleryImg->inventory_id }}"
                    data-stockable-type="{{ get_class($product) }}"
                    data-business-account="{{ $galleryImg->business_account ?? $product->business_account ?? '' }}"
                    data-subdivision-code="{{ $product->subdivision_code ?? '' }}"
                    data-product-name="{{ $product->product_name }}"
                    data-image-url="{{ $galleryImg->full_url }}"
                    data-price="{{ $galleryImg->retail_price }}"
                    data-variant-label="{{ $galleryImg->size_label }}"
                >

                    <img src="{{ $galleryImg->full_url }}"
                         class="small-img"
                         alt="{{ $galleryImg->size_label }} preview"
                         width="100%">

                    <span style="display: block; font-size: 10px; font-weight: bold; margin-top: 3px; color: #878787;">
                        {{ $galleryImg->size_label }}
                    </span>

                </div>

            @empty
                <!-- Fallback design states -->
            @endforelse

        </div>
    </div>

    <!-- Product Specific Meta Specifications Profiles Panel -->
    <div class="product-details">

        <h2 class="tittle">{{ $product->product_name }}</h2>

        <!-- Dynamic Variant Dimension Matrix Display -->
        <span>
            <h5>size:</h5>
            <h5>
                <h0 class="js-variant-label">{{ $product->variant_label ?? 'N/A' }}</h0>
                <h0 class="js-packaging-name">
                    @if(!empty($product->packaging_name))
                        ({{ strtolower($product->packaging_name) }})
                    @endif
                </h0>
            </h5>
        </span>

        <!-- Pricing -->
        <span>
            <h3 class="js-final-price">Ksh {{ number_format($product->final_price, 0) }}</h3>

            <?php $displayStyle = (isset($product) && $product->has_discount) ? 'inline-flex' : 'none'; ?>

            <span class="js-discount-wrapper"
                  style="display: <?php echo $displayStyle; ?>; gap: 10px; align-items: center;">

                <h4 class="discount js-original-price">
                    Ksh {{ number_format($product->retail_price, 0) }}
                </h4>

                <h4 class="js-discount-percentage">
                    {{ $product->discount_percentage }}% off
                </h4>

            </span>
        </span>

        <!-- Origin -->
        <span>
            <i class="fa-solid fa-location-dot"></i>
            <h5>Origin:</h5>
            <h5>{{ $product->origin ?? 'Kenya' }}</h5>
        </span>

        <!-- Basket Counter Add Actions Panel -->
        <span class="btns">

            <button type="button" class="dec">-</button>

            <input type="number" class="quantity-val" value="1" min="1" max="999" readonly>

            <button type="button" class="inc">+</button>

            <h4 class="add-to-cart-btn" id="AddToCartBtn" data-item-id="{{ $product->item_id }}">
                <i class="fa-solid fa-basket-shopping"></i>Add to cart
            </h4>

        </span>

        <!-- ================= CART CONTRACT (ADDED ONLY, NO RENAMES) ================= -->
        <input type="hidden" id="businessAccount" value="{{ $product->business_account ?? '' }}">
        <input type="hidden" id="stockableId" value="{{ $product->id }}">
        <input type="hidden" id="stockableType" value="retail_inventory">
        <input type="hidden" id="subdivisionCode" value="{{ $product->subdivision_code ?? 'home_market' }}">

        <input type="hidden" id="productName" value="{{ $product->product_name }}">
        <input type="hidden" id="productImage" value="{{ $product->image_url }}">
        <input type="hidden" id="productPriceValue" value="{{ $product->final_price }}">
        <input type="hidden" id="variantLabel" value="{{ $product->variant_label ?? '' }}">

        <!-- Actions -->
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

        <!-- Description -->
        <h4 class="sub-heading">Highlight</h4>
        <p>
            {{ $product->description ?? 'No specific detailed description highlights published for this configuration entry line item.' }}
        </p>

    </div>

</div>

        <!-- Static Fulfillment Operations Options Blocks Grid -->
        <div class="select-delivery">
            <h4 class="sub-heading">Delivery Options</h4>
            <div class="delivery-options">
                <div class="delivery-option">
                    <i class="ri-truck-line"></i>
                    <h4>Today 10PM - 11PM</h4>
                    <h5>Scheduled</h5>
                </div>
                <div class="delivery-option active">
                    <i class="ri-e-bike-2-line"></i>
                    <h4>60 - 140 Mins</h4>
                    <h5>Quick</h5>
                </div>
            </div>
        </div>
    </div>
    <!-- <h4 class="sub-heading">Best Seller</h4> -->
    <div class="items-slider">
        <h3 class="sub-heading">Best Sellers</h3>
    </div>
</main>

@endsection
