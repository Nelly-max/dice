{{-- Item cards. Expects $products (presented by ProductPresenter). --}}
@foreach ($products as $product)
    <div class="item-container card-data @if ($product->has_discount) active @endif"
         @if ($product->has_discount) data-content="{{ $product->discount_percentage }}%" @endif>

        <a href="{{ route('item.view', $product->id) }}">
            <img src="{{ $product->image_url }}" alt="{{ $product->product_name }}" loading="lazy">
        </a>

        <span>
            <div class="price">
                <h4>Ksh</h4>
                <h4 class="cash">{{ number_format($product->final_price, 2) }}</h4>
            </div>

            <i class="fa-solid fa-basket-shopping add-to-cart-btn"
               role="button"
               aria-label="Add {{ $product->product_name }} to cart"
               data-item-id="{{ $product->item_id }}"
               data-stockable-id="{{ $product->id }}"
               data-stockable-type="retail_inventory"
               data-business-account="{{ $product->business_account ?? '' }}"
               data-subdivision-code="{{ $product->subdivision_code ?? 'home_market' }}"
               data-product-name="{{ $product->product_name }}"
               data-image-url="{{ $product->image_url }}"
               data-price="{{ $product->final_price }}"
               data-variant-label="{{ $product->variant_label ?? '' }}"
               title="Add to cart"></i>
        </span>

        @if ($product->has_discount)
            <h5 data-discount="{{ $product->discount_percentage }}% off">
                Ksh {{ number_format($product->retail_price, 0) }}
            </h5>
        @endif

        @if (!empty($product->packaging_name))
            <span>
                <h4>{{ $product->variant_label ?: 'N/A' }} ({{ strtolower($product->packaging_name) }})</h4>
            </span>
        @endif

        <h4>{{ $product->product_name }}</h4>
    </div>
@endforeach
