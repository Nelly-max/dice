@extends('layouts.app')

@section('content')

<main class="wrapper">

    <div class="checkout">

        <a href="{{ route('home') }}">
            <i class="fa-solid fa-arrow-left-long"></i>
            Continue Shopping
        </a>

        <div class="checkout-data order message">

            <div class="order-details">

                <div class="checkout-details">

                    <div class="payment-methods">

                        <h4>
                            <i class="fa-regular fa-thumbs-up"></i>
                            Order Placed Successfully!
                        </h4>

                        <p>
                            Order No:
                            <strong>{{ $order->order_id }}</strong>
                        </p>

                    </div>

                    <img
                        class="gif"
                        src="{{ asset('img/gif/placeorder.gif') }}"
                        alt="Order Placed">

                    <div class="details">

                        <p>

                            Your order reference

                            <strong>{{ $order->order_id }}</strong>

                            has been placed successfully.

                        </p>

                        <p>

                            You can
                            <a href="">
                                view your order
                            </a>

                            at any time to track its progress.

                        </p>

                        <span class="socials">
                            <a href="#"><i class="fa-brands fa-youtube youtube"></i></a>
                            <a href="#"><i class="fa-brands fa-tiktok tiktok"></i></a>
                            <a href="#"><i class="fa-brands fa-instagram instagram"></i></a>
                        </span>

                        <p>@smartmarket_ke</p>

                        <p>Need help? Call us on <strong>0741325632</strong></p>

                    </div>

                    <div class="details">

                        <div class="detail">

                            <a href="">
                                <button>
                                    View Order
                                </button>
                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</main>

@endsection