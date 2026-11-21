@extends('layouts.flow')

@section('heading', 'Checkout')
@section('meta', $itemCount . ' ' . \Illuminate\Support\Str::plural('item', $itemCount))
@section('back', route('cart.index'))

@section('content')
{{--
    Checkout for the shop(s) chosen on the cart page (CheckoutController@checkout).
    resources/js/checkout.js fills the address, coordinates and delivery fee and keeps
    the totals in step; the ids below are its hooks, keep them.
--}}
@php
    $shipments = $shipments ?? [];
@endphp

<div class="wrapper fl-page cx-page cx-checkout">

    <ol class="cx-steps" aria-label="Progress">
        <li class="is-done"><i class="fa-solid fa-check" aria-hidden="true"></i><span>Cart</span></li>
        <li class="is-current" aria-current="step"><b>2</b><span>Details</span></li>
        <li><b>3</b><span>Pay</span></li>
    </ol>

    <form id="checkout-form" action="{{ route('invoice') }}" method="POST" class="cx-layout">
        @csrf

        {{-- the delivery location saved while shopping (session); fixed on this page.
             The server uses the session copy and its own delivery quote, these are only labels. --}}
        <input type="hidden" id="customer-lat" name="latitude" value="{{ $deliveryLocation['latitude'] ?? '' }}">
        <input type="hidden" id="customer-lng" name="longitude" value="{{ $deliveryLocation['longitude'] ?? '' }}">
        <input type="hidden" id="customer-county" name="county" value="{{ $deliveryLocation['county'] ?? '' }}">
        <input type="hidden" id="customer-town" name="town" value="{{ $deliveryLocation['town'] ?? '' }}">
        <input type="hidden" name="delivery_fee" id="delivery-fee-input">

        <div class="cx-steps-body">

            {{-- 1. contact --}}
            <section class="cx-card cx-step">
                <div class="cx-step-head">
                    <span class="cx-step-num" aria-hidden="true">1</span>
                    <h2>Contact details</h2>
                    <label class="cx-switch" for="use-account-details">
                        <span>Use my account</span>
                        <input type="checkbox" id="use-account-details" checked>
                        <i aria-hidden="true"></i>
                    </label>
                </div>

                <div class="cx-fields">
                    <label class="cx-field">
                        <span>Full name</span>
                        <input type="text" id="customer-name" name="name" placeholder="Your name" autocomplete="name" required>
                    </label>
                    <label class="cx-field">
                        <span>Phone number</span>
                        <input type="tel" id="customer-phone" name="phone" placeholder="0712 345 678" autocomplete="tel" inputmode="tel" required>
                    </label>
                    <label class="cx-field">
                        <span>Email <em>optional</em></span>
                        <input type="email" id="customer-email" name="email" placeholder="name@example.com" autocomplete="email">
                    </label>
                    <label class="cx-field">
                        <span>Another phone <em>optional</em></span>
                        <input type="tel" id="customer-alt-phone" name="alt_phone" placeholder="0700 000 000" inputmode="tel">
                    </label>
                </div>
            </section>

            {{-- 2. delivery --}}
            <section class="cx-card cx-step">
                <div class="cx-step-head">
                    <span class="cx-step-num" aria-hidden="true">2</span>
                    <h2>Delivery</h2>
                </div>

                {{-- fixed: the cart and its shops were chosen for this location, so it isn't changed here --}}
                <div @class(['cx-address', 'is-locked', 'is-missing' => !$deliveryLocation]) data-has-location="{{ $deliveryLocation ? '1' : '0' }}">
                    <span class="cx-address-pin" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>
                    <div class="cx-address-text">
                        <small>Deliver to</small>
                        @if ($deliveryLocation)
                            <p id="checkout-address-text" data-address="{{ $deliveryLocation['address'] ?? '' }}">{{ $deliveryLocation['address'] ?: 'Your pinned location' }}</p>
                        @else
                            <p id="checkout-address-text" class="is-missing">No delivery location set</p>
                        @endif
                    </div>
                    @if ($deliveryLocation)
                        <span class="cx-address-lock" title="Chosen while shopping"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
                    @else
                        <a class="cx-btn cx-btn--ghost cx-btn--sm" href="{{ route('cart.index') }}">Back to cart</a>
                    @endif
                </div>
                <p class="cx-address-note">
                    @if ($deliveryLocation)
                        This is the location you chose while shopping. To deliver somewhere else, change it in the shop before checking out — prices and shops depend on it.
                    @else
                        Go back to the shop and set your delivery location, then check out again.
                    @endif
                </p>

                <ul class="cx-routes">
                    @foreach ($shipments as $shipment)
                        <li data-route-account="{{ $shipment['business_account'] }}">
                            <span class="cx-shop-mark cx-shop-mark--sm" aria-hidden="true">{{ $shipment['shop_initial'] }}</span>
                            <span class="cx-route-text">
                                <strong>{{ $shipment['shop_name'] }}</strong>
                                <small>
                                    <i class="{{ $shipment['info']['icon'] }}" aria-hidden="true"></i>
                                    {{ $shipment['info']['label'] }}@if (!empty($shipment['info']['eta'])) · {{ $shipment['info']['eta'] }}@endif
                                </small>
                            </span>
                            <span class="cx-route-km" data-route-km @if ($loop->first) id="distance-km-label" @endif></span>
                        </li>
                    @endforeach
                </ul>

                <label class="cx-field cx-field--wide">
                    <span>Note for the rider <em>optional</em></span>
                    <textarea name="delivery_note" rows="2" maxlength="250" placeholder="Gate colour, floor, landmark…"></textarea>
                </label>
            </section>

            {{-- 3. payment --}}
            <section class="cx-card cx-step">
                <div class="cx-step-head">
                    <span class="cx-step-num" aria-hidden="true">3</span>
                    <h2>Payment</h2>
                </div>

                <div class="payment-method cx-method active">
                    <input type="radio" name="payment_method" value="mpesa" checked hidden>
                    <i class="cx-radio" aria-hidden="true"></i>
                    <span class="cx-method-text">
                        <strong>M-Pesa</strong>
                        <small>You'll get a payment prompt on your phone on the next step</small>
                    </span>
                    <img src="{{ asset('img/logo/mpesa-logo.png') }}" alt="M-Pesa">
                </div>
            </section>

        </div>

        {{-- summary --}}
        <aside class="cx-summary" aria-label="Amount to pay">
            <div class="cx-card">
                <h2 class="cx-summary-title">To pay</h2>

                <dl class="cx-rows">
                    <div>
                        <dt>Subtotal</dt>
                        <dd id="subtotal-display" data-subtotal="{{ $subtotal }}">KES {{ number_format($subtotal) }}</dd>
                    </div>
                    <div>
                        <dt>Delivery</dt>
                        <dd id="delivery-cost-display" data-delivery="{{ $delivery }}">Calculating…</dd>
                    </div>
                    <div class="{{ $discount > 0 ? 'is-saving' : 'is-muted' }}">
                        <dt>Discounts</dt>
                        <dd id="discount-display" data-discount="{{ $discount }}">{{ $discount > 0 ? '– ' : '' }}KES {{ number_format($discount) }}</dd>
                    </div>
                </dl>

                <div class="cx-total">
                    <span>Total</span>
                    <strong id="grand-total-display">KES {{ number_format($total) }}</strong>
                </div>

                <button type="submit" id="confirm-order-btn" class="cx-btn cx-btn--primary">
                    <span>Place order</span>
                    <b class="js-confirm-total">KES {{ number_format($total) }}</b>
                </button>

                <p class="cx-secure"><i class="fa-solid fa-lock" aria-hidden="true"></i> Secure checkout · Pay with M-Pesa</p>
            </div>
        </aside>

        {{-- phones: total + place order always in reach --}}
        <div class="cx-bar">
            <span>
                <small>Total</small>
                <strong class="js-confirm-total">KES {{ number_format($total) }}</strong>
            </span>
            <button type="submit" class="cx-btn cx-btn--primary" form="checkout-form">
                Place order <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </div>
    </form>

</div>

@endsection

