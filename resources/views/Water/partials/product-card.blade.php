{{-- One water offer (WaterController::offersFor): an inventory row sold as a refill, a new bottle
     or a truck trip. Same card as Home Market's: photo, price + Add, size, name. Only the photo
     links to the product page. "Add" puts it in the cart (cart.js) and turns into "− qty +";
     the option is the stockable type (water_refill | water_bottled | water_truck). --}}
@php
    $opt = config('water.options.' . $offer->option);
    $inCart = \App\Support\CartQuantities::for($offer->type, $offer->id);
@endphp
<article class="item-container card-data wt-item">
    <a href="{{ $offer->url }}" class="thumb" aria-label="{{ $offer->name }}">
        @if ($offer->image)
            <img src="{{ $offer->image }}" alt="" loading="lazy">
        @else
            <span @class(['wt-art', 'wt-art--' . $offer->option]) aria-hidden="true"><i class="{{ $opt['icon'] }}"></i><b>{{ $offer->size }}</b></span>
        @endif
    </a>

    <div class="item-body">
        <div class="buy">
            <span class="prices">
                <strong class="now"><small>KES</small> {{ number_format($offer->price) }}</strong>
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
                        data-variant-label="{{ $offer->size }}">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> {{ $offer->option === 'truck' ? 'Order' : 'Add' }}
                </button>
                <div class="hm-stepper" role="group" aria-label="{{ $offer->name }} in your cart">
                    <button type="button" class="hm-step" data-cart-step="decrease" aria-label="One less"><i @class(['fa-solid', 'fa-trash-can' => ($inCart['qty'] ?? 0) <= 1, 'fa-minus' => ($inCart['qty'] ?? 0) > 1]) aria-hidden="true"></i></button>
                    <span class="hm-step-qty" data-step-qty aria-live="polite">{{ $inCart['qty'] ?? 0 }}</span>
                    <button type="button" class="hm-step" data-cart-step="increase" aria-label="One more"><i class="fa-solid fa-plus" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>

        <span class="size">{{ $offer->size }} {{ $opt['card'] }} · {{ $offer->shop }}</span>

        <p class="name">{{ $offer->name }}</p>
    </div>
</article>
