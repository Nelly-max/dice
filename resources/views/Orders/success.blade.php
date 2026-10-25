@extends('layouts.flow')

@section('heading', 'Order #' . $order->order_id)
@section('back', $shopping['homeUrl'])

@section('content')

<div class="wrapper fl-page">

    <div class="checkout">

        <div class="checkout-data order message">

            <div class="order-details">

                <div class="placed-card">
                    <span class="placed-chip"><i class="fa-solid fa-check" aria-hidden="true"></i> Order placed</span>
                    <h2>Thank you! We've got your order.</h2>
                    <p>Order no. <strong>{{ $order->order_id }}</strong></p>
                    <img class="gif" src="{{ asset('img/gif/placeorder.gif') }}" alt="" aria-hidden="true">
                </div>

                <ol class="timeline">
                    <li class="done"><b><i class="fa-solid fa-check" aria-hidden="true"></i></b><span><strong>Order confirmed</strong><small>The shop has received your order</small></span></li>
                    <li class="next"><b></b><span><strong>Shop gets your items ready</strong><small>Next step</small></span></li>
                    <li><b></b><span><strong>Rider on the way</strong><small>A rider picks up your order</small></span></li>
                    <li><b></b><span><strong>Delivered</strong><small>To your delivery address</small></span></li>
                </ol>

                <div class="checkout-details help">
                    <p>Track it any time from your orders. Need help? Call us on <strong>0741325632</strong></p>

                    <span class="socials">
                        <a href="#" aria-label="YouTube"><i class="fa-brands fa-youtube youtube"></i></a>
                        <a href="#" aria-label="TikTok"><i class="fa-brands fa-tiktok tiktok"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fa-brands fa-instagram instagram"></i></a>
                        <span>@smartmarket_ke</span>
                    </span>

                    <div class="detail">
                        <a href=""><button type="button">View order</button></a>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

@endsection
