@extends('layouts.app')

@section('content')

<main class="wrapper">
    <!-- Meta tag to handle Laravel CSRF Security for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="checkout page">
        <a href="{{ route('cart.index') }}">
            <i class="fa-solid fa-arrow-left-long"></i>
            back to cart
        </a>

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

                    <h2>Checkout</h2>
                    <p>Checkout data needed</p>

                    <!-- CONTACT DETAILS -->
                    <div class="checkout-details">

                        <h4 class="title">1. Contact Details</h4>

                        <div class="details">

                            <div class="detail">
                                <div class="det">
                                    <input
                                        type="text"
                                        id="customer-name"
                                        name="name"
                                        placeholder="Your Name"
                                        required>
                                </div>

                                <div class="det">
                                    <input
                                        type="email"
                                        id="customer-email"
                                        name="email"
                                        placeholder="Email (optional)">
                                </div>
                            </div>

                            <div class="detail">
                                <div class="det">
                                    <input
                                        type="text"
                                        id="customer-phone"
                                        name="phone"
                                        placeholder="Mobile Number"
                                        required>
                                </div>

                                <div class="det">
                                    <input
                                        type="text"
                                        id="customer-alt-phone"
                                        name="alt_phone"
                                        placeholder="Alternative Contact(optional)">
                                </div>
                            </div>

                            <div class="detail flex">
                                <div class="det">
                                    <input
                                        type="checkbox"
                                        id="use-account-details" checked>

                                    <p>use my account details</p>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- DELIVERY ADDRESS -->
                    <div class="checkout-details">

                        <h4 class="title">2. Delivery Address</h4>

                        <div class="details flex">

                            <div class="detail block">

                                <div class="det">

                                    <h4>Selected Delivery Location</h4>

                                    <p id="checkout-address-text">
                                        <i class="fa-solid fa-map-pin"></i>
                                        Checking your location...
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- PAYMENT -->
                    <div class="checkout-details">

                        <h4 class="title">3. Select Payment Option</h4>

                        <div class="payment-methods">
                            <h4>Payment Methods Available</h4>
                            <span>
                                <div class="payment-method active">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="mpesa"
                                        checked
                                        hidden>
    
                                    <img
                                        src="{{ asset('img/logo/mpesa-logo.png') }}"
                                        alt="M-Pesa">
                                    <label>M-Pesa</label>
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

                                <h3>Order Summary</h3>

                                <hr>

                                <div class="head">

                                    <h4 style="display:flex; align-items:center; gap: .5em">
                                        <i class="fa-solid fa-basket-shopping"></i>
                                        <div id="summary-item-count">
                                            {{ $itemCount }} Item(s) in cart
                                        </div>
                                    </h4>

                                    <h4
                                        class="title"
                                        id="summary-shipment-title">
                                        {{ $shipmentTitle }}
                                    </h4>

                                </div>

                                <div
                                    id="shipment-business-name"
                                    class="shipment-business">
                                </div>

                                <div class="summary-holder">

                                    <span>
                                        <h4>Order Subtotal</h4>
                                        <h3
                                            id="subtotal-display"
                                            data-subtotal="{{ $subtotal }}">
                                            Ksh {{ number_format($subtotal) }}
                                        </h3>
                                    </span>

                                    <span>
                                        <h4>
                                            Delivery
                                            <small
                                                id="distance-km-label"
                                                style="font-size:11px; color:#666;">
                                            </small>
                                        </h4>

                                        <h3
                                            id="delivery-cost-display"
                                            data-delivery="{{ $delivery }}">
                                            Ksh {{ number_format($delivery) }}
                                        </h3>
                                    </span>

                                    <span>
                                        <h4>Discount</h4>

                                        <h3
                                            id="discount-display"
                                            data-discount="{{ $discount }}">
                                            Ksh {{ number_format($discount) }}
                                        </h3>
                                    </span>

                                </div>

                                <span class="total">
                                    <h4>Total Cost</h4>

                                    <h3 id="grand-total-display">
                                        Ksh {{ number_format($total) }}
                                    </h3>
                                </span>

                            </div>

                            <button type="submit" id="confirm-order-btn">
                                Confirm Order
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>

</main>

@endsection