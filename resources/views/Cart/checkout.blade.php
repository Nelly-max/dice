@extends($layout)

@section('content')

<main class="wrapper">
    <!-- Meta tag to handle Laravel CSRF Security for AJAX -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <div class="checkout page">

        <a href="{{ route('cart.index') }}" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to cart
        </a>

        {{-- Progress Steps --}}
        <div class="checkout-steps">
            <div class="step active">
                <div class="step-num">1</div>
                <span>Contact</span>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-num">2</div>
                <span>Delivery</span>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-num">3</div>
                <span>Payment</span>
            </div>
        </div>

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

                    <!-- CONTACT DETAILS -->
                    <div class="checkout-details">

                        <div class="section-title">
                            <div class="section-icon">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <h4>Contact Details</h4>
                        </div>

                        <div class="details">

                            <div class="detail">
                                <div class="det">
                                    <label for="customer-name">Full Name</label>
                                    <input
                                        type="text"
                                        id="customer-name"
                                        name="name"
                                        placeholder="Enter your name"
                                        required>
                                </div>

                                <div class="det">
                                    <label for="customer-email">Email (optional)</label>
                                    <input
                                        type="email"
                                        id="customer-email"
                                        name="email"
                                        placeholder="you@example.com">
                                </div>
                            </div>

                            <div class="detail">
                                <div class="det">
                                    <label for="customer-phone">Phone Number</label>
                                    <input
                                        type="text"
                                        id="customer-phone"
                                        name="phone"
                                        placeholder="0712 345 678"
                                        required>
                                </div>

                                <div class="det">
                                    <label for="customer-alt-phone">Alt Phone (optional)</label>
                                    <input
                                        type="text"
                                        id="customer-alt-phone"
                                        name="alt_phone"
                                        placeholder="Alternative number">
                                </div>
                            </div>

                            <div class="detail flex">
                                <div class="det checkbox-row">
                                    <input
                                        type="checkbox"
                                        id="use-account-details" checked>

                                    <label for="use-account-details">Use my account details</label>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- DELIVERY ADDRESS -->
                    <div class="checkout-details">

                        <div class="section-title">
                            <div class="section-icon">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <h4>Delivery Address</h4>
                        </div>

                        <div class="details">

                            <div class="detail block">

                                <div class="det address-display">

                                    <div class="address-text" id="checkout-address-text">
                                        <i class="fa-solid fa-map-pin"></i>
                                        <span>Checking your location...</span>
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                    <!-- PAYMENT -->
                    <div class="checkout-details">

                        <div class="section-title">
                            <div class="section-icon">
                                <i class="fa-solid fa-credit-card"></i>
                            </div>
                            <h4>Payment Method</h4>
                        </div>

                        <div class="payment-methods">
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

                        </div>

                    </div>

                </div>

                <!-- RIGHT SIDE -->
                <div class="right">

                    <div class="checkout-sidebar-card">

                        <h3>Order Summary</h3>

                        <div class="checkout-summary-info">

                            <div class="summary-meta">
                                <h4>
                                    <i class="fa-solid fa-basket-shopping"></i>
                                    <span id="summary-item-count">
                                        {{ $itemCount }} Item(s) in cart
                                    </span>
                                </h4>

                                <h4
                                    class="shipment-title"
                                    id="summary-shipment-title">
                                    {{ $shipmentTitle }}
                                </h4>
                            </div>

                            <div
                                id="shipment-business-name"
                                class="shipment-business">
                            </div>

                            <div class="summary-rows">

                                <div class="summary-row">
                                    <span>Subtotal</span>
                                    <span
                                        id="subtotal-display"
                                        data-subtotal="{{ $subtotal }}">
                                        KSh {{ number_format($subtotal) }}
                                    </span>
                                </div>

                                <div class="summary-row">
                                    <span>
                                        Delivery
                                        <small
                                            id="distance-km-label"
                                            class="km-label">
                                        </small>
                                    </span>

                                    <span
                                        id="delivery-cost-display"
                                        data-delivery="{{ $delivery }}">
                                        KSh {{ number_format($delivery) }}
                                    </span>
                                </div>

                                <div class="summary-row">
                                    <span>Discount</span>

                                    <span
                                        id="discount-display"
                                        data-discount="{{ $discount }}">
                                        KSh {{ number_format($discount) }}
                                    </span>
                                </div>

                            </div>

                            <div class="summary-total">
                                <span>Total</span>
                                <span id="grand-total-display">
                                    KSh {{ number_format($total) }}
                                </span>
                            </div>

                        </div>

                        <button type="submit" id="confirm-order-btn">
                            <i class="fa-solid fa-lock"></i>
                            Confirm Order
                        </button>

                    </div>

                </div>

            </div>

        </form>

    </div>

</main>

@endsection
