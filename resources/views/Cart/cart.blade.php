@extends('layouts.flow')

@section('heading', 'My cart')
@section('meta', $cartItems->count() . ' ' . \Illuminate\Support\Str::plural('item', $cartItems->count()))
@section('back', $shopping['homeUrl'])

@section('content')

<div class="wrapper fl-page">

    @if($expired)
        <div class="alert alert-warning">
        <p> Some items in your cart expired due to inactivity.</p>
        </div>
    @endif
    
    @if($cartItems->isEmpty())

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

                @php $shopName = optional($shipment['items']->first())->business_name; @endphp
                @if (!empty($shopName))
                    <div class="shop-chip">
                        <span class="shop-chip-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($shopName, 0, 1)) }}</span>
                        <span><small>Shopping from</small><strong>{{ $shopName }}</strong></span>
                    </div>
                @endif

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
                                data-business-lng="{{ $item->explicit_longitude }}"
                                data-item-stockable-id="{{ $item->stockable_id }}">


                                <div class="cart-img">
                                    @if($item->display_image)
                                        <img src="{{ $item->display_image }}" alt="{{ $item->product_name }}">
                                    @else

                                    @endif

                                    @if($shipment['shipment_type'] === 'quick')

                                        <div class="ind">
                                            <i class="fa-solid fa-motorcycle"></i>
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

                            <h4
                                class="subtotal-value"
                                data-subtotal="{{ $shipment['subtotal'] }}">

                                KSH {{ number_format($shipment['subtotal'], 2) }}

                            </h4>

                        </div>

                        {{-- Hidden values for JS --}}
                        <span
                            class="delivery-value"
                            data-delivery="0"
                            style="display:none;">
                        </span>

                        <span
                            class="discount-value"
                            data-discount="0"
                            style="display:none;">
                        </span>

                        <button
                            type="button"
                            class="btn-proceed-checkout"
                            data-checkout-url="{{ route('cart.checkout') }}">

                            Go to checkout

                        </button>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <div class="cart-left">

        <div class="cart-shipment summary-items">

            <div class="summary">
                <h3 class="title">Cart summary</h3>
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

</div>

@endsection

{{-- Modal --}}
@include('modals.modals')