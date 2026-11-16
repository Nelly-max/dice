{{-- Item cards. Expects $products (presented by ProductPresenter). Photo (the only link to the product page), then price + Add (or "− qty +" once it is in the cart, App\Support\CartQuantities), size, and the name at the bottom. --}}
@foreach ($products as $product)
    @php
        $size = collect([$product->variant_label ?? null, !empty($product->packaging_name) ? strtolower($product->packaging_name) : null, $product->abv_label ?? null])
            ->filter(fn ($part) => filled($part))
            ->implode(' · ');
    @endphp
    <article class="item-container card-data">
        <a href="{{ route('liquorhub.item.view', $product->id) }}" class="thumb" aria-label="{{ $product->product_name }}">
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

                @php $inCart = \App\Support\CartQuantities::for('liquor_inventory', $product->id); @endphp
                <div @class(['hm-cart-ctl', 'is-in-cart' => $inCart]) data-cart-key="liquor_inventory:{{ $product->id }}" data-cart-id="{{ $inCart['id'] ?? '' }}">
                    <button type="button" class="add-to-cart-btn"
                            aria-label="Add {{ $product->product_name }} to cart"
                            data-item-id="{{ $product->item_id }}"
                            data-stockable-id="{{ $product->id }}"
                            data-stockable-type="liquor_inventory"
                            data-business-account="{{ $product->business_account ?? '' }}"
                            data-subdivision-code="{{ $product->subdivision_code ?? 'liquor_hub' }}"
                            data-product-name="{{ $product->product_name }}"
                            data-image-url="{{ $product->image_url }}"
                            data-price="{{ $product->final_price }}"
                            data-variant-label="{{ $product->variant_label ?? '' }}">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Add
                    </button>
                    <div class="hm-stepper" role="group" aria-label="{{ $product->product_name }} in your cart">
                        <button type="button" class="hm-step" data-cart-step="decrease" aria-label="One less"><i @class(['fa-solid', 'fa-trash-can' => ($inCart['qty'] ?? 0) <= 1, 'fa-minus' => ($inCart['qty'] ?? 0) > 1]) aria-hidden="true"></i></button>
                        <span class="hm-step-qty" data-step-qty aria-live="polite">{{ $inCart['qty'] ?? 0 }}</span>
                        <button type="button" class="hm-step" data-cart-step="increase" aria-label="One more"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                    </div>
                </div>
            </div>

            @if ($size !== '')
                <span class="size">{{ $size }}</span>
            @endif

            <p class="name">{{ $product->product_name }}</p>
        </div>
    </article>
@endforeach
