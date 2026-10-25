@extends('layouts.flow')

@section('heading', 'Checkout')
@section('meta', '3 steps')
@section('back', route('cart.index'))

@section('content')

@php
    $shops = app(\App\Services\HomeMarket\ShopDiscovery::class);
    $checkoutShop = $shops->shopName($shops->cartShop());
@endphp

<div class="wrapper fl-page">
    <!-- Meta tag to handle Laravel CSRF Security for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="checkout page">

        <form id="checkout-form" action="{{ route('invoice') }}" method="POST">
            @csrf

            <!-- Hidden inputs to hold the parsed localStorage coordinates for final order submission -->
            <input type="hidden" id="customer-lat" name="latitude">
            <input type="hidden" id="customer-lng" name="longitude">
            <input type="hidden" id="customer-county" name="county">
            <input type="hidden" id="customer-town" name="town">

            <input type="hidden" name="delivery_fee" id="delivery-fee-input">

            <div class="checkout-data">

                <div class="left">

                    <!-- 1. CONTACT DETAILS -->
                    <div class="checkout-details">

                        <h4 class="title"><b class="num">1</b> Contact details</h4>

                        <div class="details">

                            <label class="switch-row" for="use-account-details">
                                <span>Use my account details</span>
                                <input type="checkbox" id="use-account-details" checked>
                                <i class="switch" aria-hidden="true"></i>
                            </label>

                            <div class="detail">
                                <label class="det field">
                                    <small>Your name</small>
                                    <input type="text" id="customer-name" name="name" placeholder="Your name" required>
                                </label>

                                <label class="det field">
                                    <small>Mobile number</small>
                                    <input type="text" id="customer-phone" name="phone" placeholder="0712 345 678" inputmode="tel" required>
                                </label>
                            </div>

                            <div class="detail">
                                <label class="det field">
                                    <small>Email <em>(optional)</em></small>
                                    <input type="email" id="customer-email" name="email" placeholder="name@example.com">
                                </label>

                                <label class="det field">
                                    <small>Alternative contact <em>(optional)</em></small>
                                    <input type="text" id="customer-alt-phone" name="alt_phone" placeholder="0700 000 000" inputmode="tel">
                                </label>
                            </div>

                        </div>

                    </div>

                    <!-- 2. DELIVERY ADDRESS -->
                    <div class="checkout-details">

                        <h4 class="title"><b class="num">2</b> Delivery address</h4>

                        <div class="address-card">
                            <span class="address-map" aria-hidden="true"><i class="fa-solid fa-location-dot"></i></span>

                            <div class="address-text">
                                <small>Delivering to</small>
                                {{-- checkout.js fills this from the saved delivery location --}}
                                <p id="checkout-address-text">
                                    <i class="fa-solid fa-map-pin"></i>
                                    Checking your location...
                                </p>
                            </div>

                            <button type="button" class="change-btn" onclick="showModal('mapModal')">Change</button>
                        </div>

                    </div>

                    <!-- 3. PAYMENT -->
                    <div class="checkout-details">

                        <h4 class="title"><b class="num">3</b> Payment</h4>

                        <div class="payment-methods">
                            <span>
                                <div class="payment-method active">
                                    <input type="radio" name="payment_method" value="mpesa" checked hidden>

                                    <i class="radio-dot" aria-hidden="true"></i>
                                    <span class="pm-text">
                                        <label>M-Pesa</label>
                                        <small>Pay by STK push or Paybill on the next step</small>
                                    </span>
                                    <img src="{{ asset('img/logo/mpesa-logo.png') }}" alt="M-Pesa">
                                </div>
                            </span>
                        </div>

                    </div>

                </div>

                <!-- RIGHT SIDE -->
                <div class="right">

                    <div class="summary">

                        <div class="checkout-summary">

                            <div class="checkout-details">

                                <h3>Order summary</h3>

                                @if ($checkoutShop)
                                    <div class="shop-chip">
                                        <span class="shop-chip-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($checkoutShop, 0, 1)) }}</span>
                                        <span><small>Shopping from</small><strong>{{ $checkoutShop }}</strong></span>
                                    </div>
                                @endif

                                <div class="head">
                                    <h4>
                                        <i class="fa-solid fa-basket-shopping" aria-hidden="true"></i>
                                        <span id="summary-item-count">{{ $itemCount }} Item(s) in cart</span>
                                    </h4>

                                    <h4 class="title" id="summary-shipment-title">{{ $shipmentTitle }}</h4>
                                </div>

                                <div id="shipment-business-name" class="shipment-business"></div>

                                <div class="summary-holder">

                                    <span>
                                        <h4>Subtotal</h4>
                                        <h3 id="subtotal-display" data-subtotal="{{ $subtotal }}">Ksh {{ number_format($subtotal) }}</h3>
                                    </span>

                                    <span>
                                        <h4>Delivery <small id="distance-km-label"></small></h4>
                                        <h3 id="delivery-cost-display" data-delivery="{{ $delivery }}">Ksh {{ number_format($delivery) }}</h3>
                                    </span>

                                    <span>
                                        <h4>Discounts</h4>
                                        <h3 id="discount-display" data-discount="{{ $discount }}">Ksh {{ number_format($discount) }}</h3>
                                    </span>

                                </div>

                                <span class="total">
                                    <h4>Total</h4>
                                    <h3 id="grand-total-display">Ksh {{ number_format($total) }}</h3>
                                </span>

                            </div>

                            <button type="submit" id="confirm-order-btn">
                                <span>Confirm order</span>
                                <b class="js-confirm-total">Ksh {{ number_format($total) }}</b>
                            </button>

                            <p class="secure-note"><i class="fa-solid fa-lock" aria-hidden="true"></i> Secure checkout</p>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>

@endsection

@push('sheets')
    @include('modals.map')
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.api_key') }}&libraries=places" async defer></script>
@endpush
