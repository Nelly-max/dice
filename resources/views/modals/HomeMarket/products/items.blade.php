{{-- Item cards. Expects $products (presented by ProductPresenter). --}}
@foreach ($products as $product)
    <article class="item-container card-data">
        @if ($product->has_discount)
            <span class="badge">-{{ $product->discount_percentage }}%</span>
        @endif

        <a href="{{ route('item.view', $product->id) }}" class="thumb">
            <img src="{{ $product->image_url }}" alt="" loading="lazy">
        </a>

        <a href="{{ route('item.view', $product->id) }}" class="name">{{ $product->product_name }}</a>

        <span class="size">
            {{ $product->variant_label }}@if (!empty($product->packaging_name)) ({{ strtolower($product->packaging_name) }})@endif
        </span>

        <div class="buy">
            <span class="prices">
                <s>@if ($product->has_discount)KES {{ number_format($product->retail_price) }}@endif</s>
                <strong @class(['now', 'deal' => $product->has_discount])>KES {{ number_format($product->final_price) }}</strong>
            </span>

            <button type="button" class="add-to-cart-btn"
                    aria-label="Add {{ $product->product_name }} to cart"
                    data-item-id="{{ $product->item_id }}"
                    data-stockable-id="{{ $product->id }}"
                    data-stockable-type="retail_inventory"
                    data-business-account="{{ $product->business_account ?? '' }}"
                    data-subdivision-code="{{ $product->subdivision_code ?? 'home_market' }}"
                    data-product-name="{{ $product->product_name }}"
                    data-image-url="{{ $product->image_url }}"
                    data-price="{{ $product->final_price }}"
                    data-variant-label="{{ $product->variant_label ?? '' }}">
                <i class="ri-add-line" aria-hidden="true"></i>
            </button>
        </div>
    </article>
@endforeach
