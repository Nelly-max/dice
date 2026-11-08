{{-- Gas refill cards, same card as Home Market's (HomeMarket/products/items): photo, price + Order, size, name.
     Only the photo links to the refill's page. "Order" adds the refill to the cart (cart.js) and turns into "− qty +" once it is in the cart, when the card
     knows its stock row (home page); search results without a shop show "View" instead.
     Expects $cylinders (ProductController: brand, size, shop, min/max price, image, id). --}}
@foreach ($cylinders as $cylinder)
    @php
        $brand = $cylinder->cylinder->brand_name ?? 'Gas';
        $size = trim((string) ($cylinder->quantity->quantity ?? ''));
        $sizeLabel = $size === '' ? '' : (is_numeric($size) ? $size . ' kg' : $size);
        $shop = $cylinder->business?->name;
        $image = $cylinder->image
            ? rtrim(config('app.media_url'), '/') . '/' . ltrim($cylinder->image, '/')
            : asset('img/placeholder.png');
        $url = route('gas.cylinder.view', array_filter([
            'business' => $cylinder->business_id ?? null,
            'cylinder' => $cylinder->gas_cylinder_id,
            'quantity' => $cylinder->gas_quantity_id,
        ]));
        $ranged = $cylinder->min_price != $cylinder->max_price;
        $canOrder = !empty($cylinder->id) && !empty($cylinder->business_id);
    @endphp
    <article class="item-container card-data">
        <a href="{{ $url }}" class="thumb" aria-label="{{ $brand }} {{ $sizeLabel }}">
            <img src="{{ $image }}" alt="" loading="lazy">
        </a>

        <div class="item-body">
            <div class="buy">
                <span class="prices">
                    <strong class="now">@if ($ranged)<small>from</small> @endif<small>KES</small> {{ number_format($cylinder->min_price) }}</strong>
                </span>

                @if ($canOrder)
                    @php $inCart = \App\Support\CartQuantities::for('cooking_gas_stock', $cylinder->id); @endphp
                    <div @class(['hm-cart-ctl', 'is-in-cart' => $inCart]) data-cart-key="cooking_gas_stock:{{ $cylinder->id }}" data-cart-id="{{ $inCart['id'] ?? '' }}">
                        <button type="button" class="add-to-cart-btn"
                                aria-label="Order {{ $brand }} {{ $sizeLabel }} refill"
                                data-stockable-id="{{ $cylinder->id }}"
                                data-stockable-type="cooking_gas_stock"
                                data-business-account="{{ $cylinder->business->account ?? '' }}"
                                data-subdivision-code="cooking_gas"
                                data-product-name="{{ $brand }} {{ $sizeLabel }} refill"
                                data-image-url="{{ $image }}"
                                data-price="{{ $cylinder->min_price }}"
                                data-variant-label="{{ $sizeLabel }}">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i> Order
                        </button>
                        <div class="hm-stepper" role="group" aria-label="{{ $brand }} {{ $sizeLabel }} in your cart">
                            <button type="button" class="hm-step" data-cart-step="decrease" aria-label="One less"><i @class(['fa-solid', 'fa-trash-can' => ($inCart['qty'] ?? 0) <= 1, 'fa-minus' => ($inCart['qty'] ?? 0) > 1]) aria-hidden="true"></i></button>
                            <span class="hm-step-qty" data-step-qty aria-live="polite">{{ $inCart['qty'] ?? 0 }}</span>
                            <button type="button" class="hm-step" data-cart-step="increase" aria-label="One more"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                        </div>
                    </div>
                @else
                    <span class="hm-card-go hm-card-go--muted">{{ $ranged ? 'Compare' : 'Refill' }}</span>
                @endif
            </div>

            <span class="size">{{ trim($sizeLabel . ' refill' . ($shop ? ' · ' . $shop : '')) }}</span>

            <p class="name">{{ $brand }} {{ $sizeLabel }}</p>
        </div>
    </article>
@endforeach
