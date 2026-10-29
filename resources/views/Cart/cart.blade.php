@extends($layout)

@section('content')

<main class="wrapper">

    @if($expired)
        <div class="alert alert-warning">
            <p>Some items in your cart expired due to inactivity.</p>
        </div>
    @endif

    @if($cartItems->isEmpty())

        <div class="empty-cart">
            <img src="{{ asset('img/gif/empty-cart.gif') }}" alt="Empty Cart">
            <h2>Your cart is empty</h2>
            <p>Add items from stores near you to get started</p>
            <a href="/" class="btn-continue">Browse Stores</a>
        </div>

    @else

        {{-- Cart Header --}}
        <div class="cart-page-header">
            <h1>Your cart</h1>
            <p class="cart-count">{{ $cartItems->sum('quantity') }} {{ Str::plural('item', $cartItems->sum('quantity')) }} from {{ count($shipments) }} {{ Str::plural('store', count($shipments)) }}</p>
        </div>

        <div class="cart">

            {{-- Cart Items Column --}}
            <div class="cart-shipments">

                @foreach($shipments as $shipment)

                    <div class="cart-shipment">

                        {{-- Store Header --}}
                        <div class="shipment-header">
                            <div class="store-avatar">
                                {{ strtoupper(substr($shipment['title'], 0, 1)) }}
                            </div>
                            <div class="store-info">
                                <h3>{{ $shipment['title'] }}</h3>
                                <h4>Placed as its own order</h4>
                            </div>
                        </div>

                        {{-- Shipment Info --}}
                        <div class="shipment-detail">
                            <i class="{{ $shipment['info']['icon'] }}"></i>
                            <span>
                                <h4>{{ $shipment['info']['label'] }}</h4>
                                <h5>Accurate time calculated at checkout</h5>
                            </span>
                        </div>

                        {{-- Items --}}
                        <div class="cart-items">

                            @foreach($shipment['items'] as $item)

                                <form method="POST"
                                      action="#"
                                      class="cart-item-form">

                                    @csrf

                                    <input type="hidden"
                                           name="item_id"
                                           value="{{ $item->id }}">

                                    <div class="cart-item"
                                        data-cart-id="{{ $item->id }}"
                                        data-item-stockable-id="{{ $item->stockable_id }}"
                                        data-stockable-type="{{ $item->stockable_type }}"
                                        data-subdivision-id="{{ $item->sub_division_id }}"
                                        data-db-connection="{{ $item->explicit_db_connection }}"
                                        data-business-name="{{ $item->explicit_biz_name }}"
                                        data-business-account="{{ $item->business_account }}"
                                        data-business-lat="{{ $item->explicit_latitude }}"
                                        data-business-lng="{{ $item->explicit_longitude }}">

                                        {{-- Product Image --}}
                                        <div class="cart-img">
                                            @if($item->display_image)
                                                <img src="{{ $item->display_image }}" alt="{{ $item->product_name }}">
                                            @endif

                                            @if($shipment['shipment_type'] === 'quick')
                                                <div class="ind">
                                                    <i class="ri-e-bike-2-line"></i> quick
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Product Info --}}
                                        <div class="item-info">
                                            <a href="#">{{ $item->product_name }}</a>
                                            <h4>KSh {{ number_format($item->price, 2) }}</h4>
                                            @if(!empty($item->business_name))
                                                <small>{{ $item->business_name }}</small>
                                            @endif
                                        </div>

                                        {{-- Quantity Controls --}}
                                        <div class="countIcons">
                                            <button type="button"
                                                    class="dec"
                                                    data-cart-id="{{ $item->id }}">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>

                                            <input type="text"
                                                   name="quantity"
                                                   value="{{ $item->quantity }}"
                                                   maxlength="3">

                                            <button type="button"
                                                    class="inc"
                                                    data-cart-id="{{ $item->id }}">
                                                <i class="fa-solid fa-plus"></i>
                                            </button>
                                        </div>

                                        {{-- Remove --}}
                                        <span class="remove-item" data-cart-id="{{ $item->id }}">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </span>

                                    </div>

                                </form>

                            @endforeach

                        </div>

                        {{-- Subtotal Row --}}
                        <div class="sub-total">
                            <div class="cost">
                                <h4>Subtotal</h4>
                                <h4 class="subtotal-value"
                                    data-subtotal="{{ $shipment['subtotal'] }}">
                                    KSh {{ number_format($shipment['subtotal'], 2) }}
                                </h4>
                            </div>

                            {{-- Hidden values for JS --}}
                            <span class="delivery-value"
                                  data-delivery="0"
                                  style="display:none;"></span>
                            <span class="discount-value"
                                  data-discount="0"
                                  style="display:none;"></span>

                            <button type="button"
                                    class="btn-proceed-checkout"
                                    data-checkout-url="{{ route('cart.checkout') }}">
                                Checkout
                            </button>
                        </div>

                    </div>

                @endforeach

            </div>

            {{-- Sidebar --}}
            <div class="cart-left">

                <div class="cart-sidebar-card">

                    {{-- Summary --}}
                    <div class="summary-head">
                        <h3>Order Summary</h3>
                    </div>

                    <div class="shipment-summary">
                        @foreach($shipments as $shipment)
                            <div class="summary-row">
                                <span>{{ $shipment['title'] }}</span>
                                <span>KSh {{ number_format($shipment['subtotal'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- Delivery --}}
                    <div class="summary-row delivery-row">
                        <span>Delivery</span>
                        <span class="muted">Calculated at checkout</span>
                    </div>

                    {{-- Grand Total --}}
                    <div class="summary-total">
                        <span>Total</span>
                        <span class="total-amount">KSh {{ number_format($total, 2) }}</span>
                    </div>

                    <div class="info-note">
                        This creates {{ count($shipments) }} {{ Str::plural('order', count($shipments)) }}, one per store
                    </div>

                    {{-- Payment --}}
                    <div class="sidebar-payment">
                        <h5>Payment method</h5>
                        <div class="payment-method-tag">
                            <img src="{{ asset('img/logo/mpesa-logo.png') }}" alt="M-Pesa">
                            <span>M-Pesa</span>
                        </div>
                    </div>

                    <button type="button"
                            class="btn-place-order btn-proceed-checkout"
                            data-checkout-url="{{ route('cart.checkout') }}">
                        Proceed to Checkout · KSh {{ number_format($total, 2) }}
                    </button>

                </div>

            </div>

        </div>

    @endif

</main>

@endsection

{{-- Modal --}}
@include('modals.modals')
