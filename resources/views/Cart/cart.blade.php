@extends('layouts.flow')

@section('heading', 'My cart')
@section('meta', $cartItems->sum('quantity') . ' ' . \Illuminate\Support\Str::plural('item', $cartItems->sum('quantity')))
@section('back', $shopping['homeUrl'])

@section('content')
{{--
    Cart: one shipment per shop (App\Services\ShipmentService).
    Hooks used by resources/js/cart.js:
      [data-shipment]                     a shop's shipment (its items are checked out together)
      form.cart-item-form > [data-cart-row] one item: data-cart-id, data-price and the checkout data-* fields
      .dec / .inc / input[name=quantity]  quantity stepper     [data-remove]  bin button
      .btn-proceed-checkout               data-checkout-url, data-shipment (which shipment to check out)
      [data-line-total] [data-shipment-subtotal] [data-cart-total] [data-cart-count-label]   live totals
--}}
@php
    $shipmentCount = count($shipments);
    $grandTotal = collect($shipments)->sum('subtotal');
    $grandDiscount = collect($shipments)->sum('discount');
    $quantityTotal = $cartItems->sum('quantity');
@endphp

<div class="wrapper fl-page cx-page">

    @if ($expired)
        <div class="cx-notice" role="status">
            <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
            <p>Some items were removed from your cart because it was inactive for a while.</p>
        </div>
    @endif

    @if ($cartItems->isEmpty())

        <div class="empty-cart">
            <span class="empty-cart-icon" aria-hidden="true">
                <i class="fa-solid fa-basket-shopping"></i>
            </span>
            <h2>Your cart is empty</h2>
            <p>Add at least one item to continue to checkout.</p>
            <a href="{{ $shopping['homeUrl'] }}" class="btn-continue">
                <i class="fa-solid fa-store" aria-hidden="true"></i>
                Shop {{ $shopping['name'] }}
            </a>
        </div>

        {{-- premium-listing items from the subdivision the customer came from (App\Services\CartPicks) --}}
        @foreach (($picks ?? collect()) as $row)
            <section class="cart-picks" aria-label="{{ $row['title'] }}">
                @include('HomeMarket.partials.product-row', ['title' => $row['title'], 'products' => $row['products']])
            </section>
        @endforeach

    @else

        <div class="cx-head">
            <div>
                <h1>My cart</h1>
                <p data-cart-count-label>
                    {{ $quantityTotal }} {{ \Illuminate\Support\Str::plural('item', $quantityTotal) }}
                    @if ($shipmentCount > 1) from {{ $shipmentCount }} shops @endif
                </p>
            </div>
            <a href="{{ $shopping['homeUrl'] }}" class="cx-link">
                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add more items
            </a>
        </div>

        <div class="cx-layout">

            {{-- ===================== shipments (one per shop) ===================== --}}
            <div class="cx-shipments cart-shipments">

                @if ($shipmentCount > 1)
                    <p class="cx-hint">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Your cart has items from different sections. Each one is checked out and delivered on its own.
                    </p>
                @endif

                @foreach ($shipments as $shipment)
                    <section class="cx-card cx-shipment" data-shipment="{{ $shipment['number'] }}" aria-label="Shipment from {{ $shipment['shop_name'] }}">

                        <div class="cx-shop">
                            <span class="cx-shop-mark" aria-hidden="true">{{ $shipment['shop_initial'] }}</span>
                            <div class="cx-shop-text">
                                <small>
                                    @if ($shipmentCount > 1) Shipment {{ $shipment['number'] }} of {{ $shipmentCount }} · @endif
                                    {{ $shipment['subdivision_name'] ?? 'Shop' }}
                                </small>
                                <h2>{{ $shipment['shop_name'] }}</h2>
                            </div>
                            <span class="cx-pill">
                                <i class="{{ $shipment['info']['icon'] }}" aria-hidden="true"></i>
                                <span>{{ $shipment['info']['label'] }}@if (!empty($shipment['info']['eta']))<b> · {{ $shipment['info']['eta'] }}</b>@endif</span>
                            </span>
                        </div>

                        <ul class="cx-items">
                            @foreach ($shipment['items'] as $item)
                                <li>
                                    <form method="POST" action="#" class="cart-item-form" onsubmit="return false">
                                        @csrf
                                        <input type="hidden" name="item_id" value="{{ $item->id }}">

                                        <div class="cx-item"
                                             data-cart-row
                                             data-cart-id="{{ $item->id }}"
                                             data-price="{{ (float) $item->price }}"
                                             data-item-stockable-id="{{ $item->stockable_id }}"
                                             data-stockable-type="{{ $item->stockable_type }}"
                                             data-subdivision-id="{{ $item->sub_division_id }}"
                                             data-db-connection="{{ $item->explicit_db_connection }}"
                                             data-business-name="{{ $item->explicit_biz_name }}"
                                             data-business-account="{{ $item->business_account }}"
                                             data-business-lat="{{ $item->explicit_latitude }}"
                                             data-business-lng="{{ $item->explicit_longitude }}">

                                            <span class="cx-thumb">
                                                @if ($item->display_image)
                                                    <img src="{{ $item->display_image }}" alt="" loading="lazy">
                                                @else
                                                    <i class="fa-solid fa-image" aria-hidden="true"></i>
                                                @endif
                                            </span>

                                            <div class="cx-item-text">
                                                <h3>{{ $item->product_name }}</h3>
                                                <small>KES {{ number_format($item->price) }} each</small>
                                            </div>

                                            <div class="cx-stepper" role="group" aria-label="Quantity of {{ $item->product_name }}">
                                                <button type="button" class="dec" data-cart-id="{{ $item->id }}" aria-label="Fewer">
                                                    <i class="fa-solid {{ $item->quantity > 1 ? 'fa-minus' : 'fa-trash-can' }}" aria-hidden="true"></i>
                                                </button>
                                                <input type="text" name="quantity" value="{{ $item->quantity }}" maxlength="3" readonly aria-label="Quantity">
                                                <button type="button" class="inc" data-cart-id="{{ $item->id }}" aria-label="More">
                                                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                </button>
                                            </div>

                                            <strong class="cx-line" data-line-total>KES {{ number_format($item->price * $item->quantity) }}</strong>

                                            <button type="button" class="cx-remove" data-remove="{{ $item->id }}" aria-label="Remove {{ $item->product_name }}">
                                                <i class="fa-regular fa-trash-can" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </form>
                                </li>
                            @endforeach
                        </ul>

                        <div class="cx-shipment-foot">
                            <span>
                                <small>Subtotal</small>
                                <strong data-shipment-subtotal>KES {{ number_format($shipment['subtotal']) }}</strong>
                            </span>

                            @if ($shipmentCount > 1)
                                <button type="button" class="btn-proceed-checkout cx-btn cx-btn--soft"
                                        data-shipment="{{ $shipment['number'] }}"
                                        data-checkout-url="{{ route('cart.checkout') }}">
                                    Checkout {{ $shipment['subdivision_name'] ?? $shipment['shop_name'] }}
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>

                    </section>
                @endforeach

            </div>

            {{-- ===================== summary ===================== --}}
            <aside class="cx-summary" aria-label="Cart summary">
                <div class="cx-card">
                    <h2 class="cx-summary-title">Summary</h2>

                    <dl class="cx-rows">
                        @if ($shipmentCount > 1)
                            @foreach ($shipments as $shipment)
                                <div>
                                    <dt>{{ $shipment['shop_name'] }}</dt>
                                    <dd data-summary-shipment="{{ $shipment['number'] }}">KES {{ number_format($shipment['subtotal']) }}</dd>
                                </div>
                            @endforeach
                        @else
                            <div>
                                <dt>Items (<span data-cart-qty>{{ $quantityTotal }}</span>)</dt>
                                <dd data-cart-total>KES {{ number_format($grandTotal) }}</dd>
                            </div>
                        @endif

                        @if ($grandDiscount > 0)
                            <div class="is-saving">
                                <dt>You save</dt>
                                <dd>– KES {{ number_format($grandDiscount) }}</dd>
                            </div>
                        @endif

                        <div class="is-muted">
                            <dt>Delivery</dt>
                            <dd>At checkout</dd>
                        </div>
                    </dl>

                    @if ($shipmentCount === 1)
                        <div class="cx-total">
                            <span>Total</span>
                            <strong data-cart-total>KES {{ number_format($grandTotal - $grandDiscount) }}</strong>
                        </div>

                        <button type="button" class="btn-proceed-checkout cx-btn cx-btn--primary"
                                data-shipment="{{ $shipments[0]['number'] }}"
                                data-checkout-url="{{ route('cart.checkout') }}">
                            Go to checkout
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    @else
                        <p class="cx-note">Choose <b>Checkout</b> under the section you want delivered first.</p>
                    @endif

                    <div class="cx-pay">
                        <img src="{{ asset('img/logo/mpesa-logo.png') }}" alt="M-Pesa">
                        <span>Pay with M-Pesa at checkout</span>
                    </div>
                </div>
            </aside>

        </div>

        {{-- phones: total + checkout always in reach (one shop only) --}}
        @if ($shipmentCount === 1)
            <div class="cx-bar">
                <span>
                    <small>Total</small>
                    <strong data-cart-total>KES {{ number_format($grandTotal - $grandDiscount) }}</strong>
                </span>
                <button type="button" class="btn-proceed-checkout cx-btn cx-btn--primary"
                        data-shipment="{{ $shipments[0]['number'] }}"
                        data-checkout-url="{{ route('cart.checkout') }}">
                    Checkout
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
        @endif

    @endif

</div>

@endsection

@push('sheets')
    {{-- confirm before removing an item --}}
    <div class="modal sheet cx-confirm" id="deleteItem">
        <div class="modal-overlay"></div>
        <div class="sheet-panel" role="dialog" aria-modal="true" aria-labelledby="cxConfirmTitle">
            <span class="cx-confirm-icon" aria-hidden="true"><i class="fa-regular fa-trash-can"></i></span>
            <h3 id="cxConfirmTitle">Remove this item?</h3>
            <p data-confirm-name>It will be taken out of your cart.</p>
            <div class="cx-confirm-btns">
                <button type="button" class="cx-btn cx-btn--ghost" onclick="closeModal()">Keep it</button>
                <button type="button" class="cx-btn cx-btn--danger" id="confirmDeleteCartItem">Remove</button>
            </div>
        </div>
    </div>
@endpush
