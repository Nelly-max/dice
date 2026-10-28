{{-- Item cards. Expects $products (presented by ProductPresenter). Photo, then price + Add, size, and the name at the bottom. --}}
@foreach ($products as $product)
    @php
        $size = collect([$product->variant_label ?? null, !empty($product->packaging_name) ? strtolower($product->packaging_name) : null])
            ->filter(fn ($part) => filled($part))
            ->implode(' · ');
    @endphp
    <article class="item-container card-data">
        <a href="{{ route('item.view', $product->id) }}" class="thumb" aria-label="{{ $product->product_name }}">
            <img src="{{ $product->image_url }}" alt="" loading="lazy">
            @if ($product->has_discount)
                <span class="badge">-{{ $product->discount_percentage }}%</span>
            @endif
        </a>

        <div class="item-body">
            <div class="buy">
                <span class="prices">
                    <strong @class(['now', 'deal' => $product->has_discount])><small>KES</small> {{ number_format($product->final_price) }}</strong>
                    @if ($product->has_discount)<s>{{ number_format($product->retail_price) }}</s>@endif
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
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add
                </button>
            </div>

            @if ($size !== '')
                <span class="size">{{ $size }}</span>
            @endif

            <a href="{{ route('item.view', $product->id) }}" class="name">{{ $product->product_name }}</a>
        </div>
    </article>
@endforeach
