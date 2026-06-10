@extends('layouts.app')

@section('content')

<main class="wrapper">

@if($cartItems->isEmpty())

    <div class="empty-cart">
        <h3>MY BASKET</h3>
        <p>HOME \ CART</p>

        <img src="{{ asset('img/empty-cart.gif') }}" alt="Empty Cart">

        <h2>Your Cart Is Currently Empty !</h2>

        <p>
            Your basket needs a minimum of one product so that you can proceed to check out
        </p>

        <a href="/" class="btn-continue">
            Return To Shop
        </a>
    </div>

@else

<div class="cart">

    <div class="cart-shipments">

        @foreach($shipments as $shipment)

            <div class="cart-shipment">

                <h2 class="title">
                    {{ $shipment['title'] }}
                </h2>

                <div class="shipment-detail">

                    <i class="{{ $shipment['info']['icon'] }}"></i>

                    <span>
                        <h4>{{ $shipment['info']['label'] }}</h4>

                        <h5>
                            Accurate time to be calculated at checkout
                        </h5>
                    </span>

                </div>

                <div class="cart-items">

                    @foreach($shipment['items'] as $item)

                        <form method="POST"
                              action="#"
                              class="cart-item-form">

                            @csrf

                            <input type="hidden"
                                   name="item_id"
                                   value="{{ $item->id }}">

                            <div class="cart-item">

                                <div class="cart-img">
                                    @if($item->display_image)
                                        <img src="{{ $item->display_image }}" alt="{{ $item->product_name }}">
                                    @else

                                    @endif

                                    @if($shipment['shipment_type'] === 'quick')

                                        <div class="ind">
                                            <i class="ri-e-bike-2-line"></i>
                                            quick
                                        </div>

                                    @endif

                                </div>

                                <div>

                                    <a href="#">
                                        {{ $item->product_name }}
                                    </a>

                                    <h4>
                                        Ksh{{ number_format($item->price, 2) }}
                                    </h4>

                                    @if(!empty($item->business_name))
                                        <small>
                                            {{ $item->business_name }}
                                        </small>
                                    @endif

                                </div>

                                <div class="countIcons">

                                    <button type="button"
                                            class="dec"
                                            data-cart-id="{{ $item->id }}">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </button>

                                    <input
                                        type="text"
                                        name="quantity"
                                        value="{{ $item->quantity }}"
                                        maxlength="3"
                                    >

                                    <button type="button"
                                            class="inc"
                                            data-cart-id="{{ $item->id }}">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </button>

                                </div>

                            </div>

                        </form>

                    @endforeach

                    <div class="sub-total">

                        <div class="cost">

                            <h4>SUB-TOTAL</h4>

                            <h4>
                                KSH {{ number_format($shipment['subtotal'], 2) }}
                            </h4>

                        </div>

                        <button>
                            Checkout
                        </button>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <div class="cart-left">

        <div class="cart-shipment summary-items">

            <div class="summary">
                <h3 class="title">Cart Summary</h3>
            </div>

            <div class="shipment-summary">

                @foreach($shipments as $shipment)

                    <span>

                        <h4>
                            {{ $shipment['title'] }}
                        </h4>

                        <h4>
                            {{ $shipment['info']['label'] }}
                        </h4>

                    </span>

                @endforeach

            </div>

            <div class="payment-methods">

                <h4>Payment Methods Available</h4>

                <div class="payment-method">
                    <img src="{{ asset('img/logo/mpesa-logo.png') }}"
                         alt="M-Pesa">
                </div>

            </div>

        </div>

    </div>

</div>

@endif

</main>

@endsection
