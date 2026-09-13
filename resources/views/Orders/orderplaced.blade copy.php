@extends('layouts.app')

@section('content')

<main  class="wrapper">
    <div class="checkout">
        <a href="cart.html">
            <i class="fa-solid fa-arrow-left-long"></i>
                back to cart
        </a>
        <div class="checkout-data order message">
            <div class="order-details">                    
                <div class="checkout-details">
                    <div class="payment-methods">
                        <h4><i class="fa-regular fa-thumbs-up"></i> Order Placed Successfully !!  order no #34RE4</h4>
                    </div>
                    <img class="gif" src="../customer/public/img/gif/placeorder.gif" alt="">

                    <div class="details">
                        <p>Your order ref no RF#905GH2E has been made successfully. you can <a href="#">view order</a> to track progress on your order</p>
                        <span class="socials">
                            <a href=""><i class="fa-brands fa-youtube youtube"></i></a>
                            <a href=""><i class="fa-brands fa-tiktok tiktok"></i></a>
                            <a href=""><i class="fa-brands fa-instagram instagram"></i></a>
                        </span>
                        <p>@smartmarket_ke</p>
                        <p>Call us on 0741325632</p>
                    </div>

                    <div class="details">
                        <div class="detail">
                            <a href="{{ route('orders.show', $order->uuid) }}">
                                <button>View Order</button>
                            </a>
                        </div>
                    </div>
                </div>

            </div>                
        </div>

    </div>
</main> 

@endsection
