@extends('layouts.flow')

@section('heading', 'Payment')
@section('meta', 'Secure')
@section('back', route('cart.checkout'))

@section('content')

<div class="wrapper fl-page">

    <div class="checkout">

        <div class="checkout-data order">

            <div class="order-details">

                <div class="pay-hero">
                    <span class="pay-icon" aria-hidden="true"><i class="fa-solid fa-mobile-screen-button"></i></span>
                    <h2>Pay with M-Pesa</h2>
                    <p>Invoice no. #{{ $invoice->payment_account }}</p>
                </div>

                <!-- Crucial JavaScript hook bindings: orderPayment.js reads these ids and data attributes -->
                <div id="payment-container"
                     data-invoice-uuid="{{ $invoice->uuid }}"
                     class="checkout-details mpesa-payment">

                    <div class="details">

                        <h4 class="title">Option 1 · Paybill</h4>
                        <p>Pay using the Paybill and account number below</p>

                        <div class="detail pay-rows">
                            <span><h5>Paybill</h5><h4>{{ $paybill ?? '117450' }}</h4></span>
                            <span><h5>Account</h5><h4>{{ $invoice->payment_account }}</h4></span>
                            <span><h5>Amount</h5><h4 class="amount">Ksh {{ number_format($invoice->total_amount) }}</h4></span>
                        </div>

                    </div>

                    <div class="details">

                        <h4 class="title">Option 2 · STK push</h4>
                        <p>Enter your M-Pesa number and approve the prompt on your phone</p>

                        <div class="detail">
                            <div id="stkMessage" class="stk-message"></div>

                            <div class="det">
                                <input type="hidden" id="invoice_id" value="{{ $invoice->id }}">
                                <input type="text" id="mpesa_phone" class="mobile-number" placeholder="e.g. 0712345678" inputmode="tel">

                                <button type="button" id="stkPushBtn"
                                        data-invoice-id="{{ $invoice->id }}"
                                        data-stk-url="{{ route('payment.mpesa.stk') }}"
                                        data-status-url="{{ route('payment.mpesa.status', $invoice->id) }}">
                                    Send STK push
                                </button>
                            </div>
                        </div>

                    </div>

                </div>

                <a href="{{ route('cart.checkout') }}" class="pay-cancel">Cancel and edit order</a>

            </div>

        </div>

    </div>

</div>

@endsection
