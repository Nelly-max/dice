@extends('LiquorHub.layouts.app')

@section('content')

<main class="wrapper pdp">
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
                    data-full-url="{{ $galleryImg->image_url }}"
                    data-label="{{ $galleryImg->variant_label }}"
                    data-packaging-name="{{ strtolower($product->packaging_name ?? '') }}"
                    data-final-price="Ksh {{ number_format($galleryImg->final_price) }}"
                    data-original-price="Ksh {{ number_format($galleryImg->retail_price) }}"
                    data-has-discount="{{ $galleryImg->has_discount ? 'true' : 'false' }}"
                    data-discount-percentage="{{ $galleryImg->discount_percentage }}% off"
                    data-route-url="{{ route('liquorhub.item.view', $galleryImg->id) }}"

                    {{-- ✅ ADDED (DO NOT CHANGE YOUR EXISTING FIELDS) --}}
                    data-stockable-id="{{ $galleryImg->id }}"
                    data-stockable-type="liquor_inventory"
                    data-business-account="{{ $galleryImg->business_account ?? $product->business_account ?? '' }}"
                    data-subdivision-code="{{ $product->subdivision_code ?? '' }}"
                    data-product-name="{{ $product->product_name }}"
                    data-image-url="{{ $galleryImg->image_url }}"
                    data-price="{{ $galleryImg->retail_price }}"
                    data-variant-label="{{ $galleryImg->variant_label }}"
                >

                    <span class="sit-pic">
                        <img src="{{ $galleryImg->image_url }}" class="small-img" alt="{{ $galleryImg->variant_label }} preview" loading="lazy">
                        @if ($galleryImg->has_discount && $galleryImg->discount_percentage)<em class="sit-off">-{{ $galleryImg->discount_percentage }}%</em>@endif
                    </span>
                    <span class="sit-label">{{ $galleryImg->variant_label ?: 'Option' }}</span>
                    <span class="sit-price">KES {{ number_format($galleryImg->final_price) }}</span>

                </div>

            @empty
                <!-- Fallback design states -->
            @endforelse

            </div>
        </div>

        <!-- Product Specific Meta Specifications Profiles Panel -->
        <div class="product-details">

            @if (!empty($product->brand))
                <small class="brand">{{ $product->brand }}</small>
            @endif

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

            <!-- Alcohol by volume -->
            @if (!empty($product->abv_label))
            <span>
            <i class="fa-solid fa-wine-glass"></i>
            <h5>Alcohol:</h5>
            <h5>{{ $product->abv_label }}</h5>
            </span>
            @endif

            <!-- Origin (products.origin) -->
            @if (!empty($product->origin))
            <span>
            <i class="fa-solid fa-location-dot"></i>
            <h5>Origin:</h5>
            <h5>{{ $product->origin }}</h5>
            </span>
            @endif

            @if (!empty($product->business_name))
                <div class="sold-by">
                    <span class="shop-chip-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($product->business_name, 0, 1)) }}<i class="sold-by-open"></i></span>
                    <span><small>Sold by</small><strong>{{ $product->business_name }}</strong></span>
                    @if (isset($product->distance_meters))
                        <em class="dist"><i class="fa-solid fa-route" aria-hidden="true"></i> {{ $product->distance_meters < 100 ? 'Under 100 m' : number_format($product->distance_meters / 1000, 1) . ' km away' }}</em>
                    @endif
                </div>
            @endif

            <!-- Basket Counter Add Actions Panel -->
            {{-- once this item is in the cart: "− qty +" for the cart instead of the quantity + Add button
                 (cart.js syncCardCart; data-cart-map lets it follow the size/variant picked) --}}
            @php
                $pdpInCart = \App\Support\CartQuantities::for('liquor_inventory', $product->id);
                $pdpCartMap = collect(\App\Support\CartQuantities::all())->filter(fn ($v, $k) => str_starts_with($k, 'liquor_inventory:'));
            @endphp
            <div @class(['hm-cart-ctl', 'pdp-cart-ctl', 'is-in-cart' => $pdpInCart]) data-cart-key="liquor_inventory:{{ $product->id }}" data-cart-id="{{ $pdpInCart['id'] ?? '' }}" data-cart-type="liquor_inventory" data-cart-map='@json($pdpCartMap)'>
                <span class="btns">

                <button type="button" class="dec" aria-label="Less" disabled><i class="fa-solid fa-minus" aria-hidden="true"></i></button>

                <input type="number" class="quantity-val" value="1" min="1" max="999" readonly aria-label="Quantity">

                <button type="button" class="inc" aria-label="More"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>

                <h4 class="add-to-cart-btn" id="AddToCartBtn" role="button" tabindex="0" data-item-id="{{ $product->item_id }}">
                    <i class="fa-solid fa-basket-shopping"></i>Add to cart
                </h4>

                </span>
                <div class="hm-stepper pdp-stepper" role="group" aria-label="In your cart">
                    <button type="button" class="hm-step" data-cart-step="decrease" aria-label="One less"><i @class(['fa-solid', 'fa-trash-can' => ($pdpInCart['qty'] ?? 0) <= 1, 'fa-minus' => ($pdpInCart['qty'] ?? 0) > 1]) aria-hidden="true"></i></button>
                    <span class="hm-step-qty"><b data-step-qty aria-live="polite">{{ $pdpInCart['qty'] ?? 0 }}</b> in your cart</span>
                    <button type="button" class="hm-step" data-cart-step="increase" aria-label="One more"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                </div>
            </div>

            <!-- ================= CART CONTRACT (ADDED ONLY, NO RENAMES) ================= -->
            <input type="hidden" id="businessAccount" value="{{ $product->business_account ?? '' }}">
            <input type="hidden" id="stockableId" value="{{ $product->id }}">
            <input type="hidden" id="stockableType" value="liquor_inventory">
            <input type="hidden" id="subdivisionCode" value="{{ $product->subdivision_code ?? 'liquor_hub' }}">

            <input type="hidden" id="productName" value="{{ $product->product_name }}">
            <input type="hidden" id="productImage" value="{{ $product->image_url }}">
            <input type="hidden" id="productPriceValue" value="{{ $product->final_price }}">
            <input type="hidden" id="variantLabel" value="{{ $product->variant_label ?? '' }}">

            <!-- Actions -->
            <div class="actions">
                <span>
                    <i class="fa-regular fa-heart"></i>
                    <h5>Add to wishlist</h5>
                </span>
    
                {{-- opens the share sheet: WhatsApp, Instagram, Facebook, X, Telegram, SMS, email, copy link (share.js) --}}
                <button type="button" class="pdp-share" data-share
                        data-share-title="{{ $product->product_name }}"
                        data-share-text="KES {{ number_format($product->final_price) }}{{ !empty($product->business_name) ? ' at ' . $product->business_name : '' }} on Smart Market"
                        data-share-url="{{ route('liquorhub.item.view', $product->id) }}">
                    <i class="fa-solid fa-share-nodes" aria-hidden="true"></i>
                    <h5>Share</h5>
                </button>
            </div>

            @php
                $specs = array_filter([
                    'Brand'     => $product->brand ?? null,
                    'Packaging' => !empty($product->packaging_name) ? ucfirst(strtolower($product->packaging_name)) : null,
                    'Weight'    => !empty($product->weight_value) ? rtrim(rtrim((string) $product->weight_value, '0'), '.') . ' ' . ($product->weight_unit ?? '') : null,
                ]);
            @endphp
            @if ($specs)
                <dl class="specs">
                    @foreach ($specs as $label => $value)
                        <div><dt>{{ $label }}</dt><dd>{{ trim($value) }}</dd></div>
                    @endforeach
                </dl>
            @endif

            <!-- Description -->
            <h4 class="sub-heading">Highlight</h4>
            <p>
            {{ $product->description ?? 'No specific detailed description highlights published for this configuration entry line item.' }}
            </p>

        </div>

    </div>

        <!-- Static Fulfillment Operations Options Blocks Grid -->
        <div class="select-delivery">
            <h4 class="sub-heading">Delivery options</h4>
            <div class="delivery-options">
                <div class="delivery-option active">
                    <i class="fa-solid fa-motorcycle"></i>
                    <h4>60 - 140 Mins</h4>
                    <h5>Quick delivery by a nearby rider</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- more to look at: same generic name, then same brand (ItemsController::related) --}}
    @if (($similar ?? collect())->isNotEmpty() || ($sameBrand ?? collect())->isNotEmpty())
        <section class="pdp-related">
            @include('LiquorHub.partials.product-row', ['title' => 'Similar items', 'products' => $similar ?? collect()])
            @include('LiquorHub.partials.product-row', ['title' => 'More from ' . ($product->brand ?? 'this brand'), 'products' => $sameBrand ?? collect()])
        </section>
    @endif
</main>

@endsection
