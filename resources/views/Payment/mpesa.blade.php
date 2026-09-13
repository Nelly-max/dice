@extends('layouts.app')

@section('content')

<main class="wrapper">

    <div class="checkout">

        <a href="{{ route('cart.checkout') }}">
            <i class="fa-solid fa-arrow-left-long"></i>
            back to checkout
        </a>


        <div class="checkout-data order">

            <div class="order-details">

                <h2>Place Order</h2>

                <p>
                    Order invoice no 
                    #{{ $invoice->payment_account }}
                </p>


                <div class="checkout-details">

                    <div class="payment-methods">

                        <h4>
                            Payment Method Selected
                        </h4>


                        <div class="payment-method">

                            <img
                                src="{{ asset('img/logo/mpesa-logo.png') }}"
                                alt="M-Pesa">

                        </div>

                    </div>

                </div>


                <!-- ✨ Crucial JavaScript Hook Bindings Injected Here -->
                <div id="payment-container" 
                     data-invoice-uuid="{{ $invoice->uuid }}" 
                     class="checkout-details mpesa-payment">


                    <div class="payment-methods">

                        <h4>
                            M-Pesa Payment Details
                        </h4>

                    </div>



                    <div class="details">

                        <h4 class="title">
                            1. Paybill Option
                        </h4>


                        <p>
                            Pay using the Paybill and account number below
                        </p>



                        <div class="detail">


                            <span>

                                <h5>
                                    Paybill
                                </h5>

                                <h4>
                                    {{ $paybill ?? '117450' }}
                                </h4>

                            </span>



                            <span>

                                <h5>
                                    Account
                                </h5>

                                <h4>
                                    {{ $invoice->payment_account }}
                                </h4>

                            </span>



                            <span>

                                <h5>
                                    Amount
                                </h5>

                                <h4>
                                    Ksh {{ number_format($invoice->total_amount) }}
                                </h4>

                            </span>


                        </div>

                    </div>





                    <div class="details">

                        <h4 class="title">
                            2. STK Push
                        </h4>


                        <p>
                            Enter your M-Pesa number
                        </p>



                        <div class="detail">
                            <div
                                id="stkMessage"
                                class="stk-message"
                                style="margin-top:15px;">
                            </div>
                            
                            <div class="det">
                                <input
                                    type="hidden"
                                    id="invoice_id"
                                    value="{{ $invoice->id }}">
                                <input
                                    type="text"
                                    id="mpesa_phone"
                                    class="mobile-number"
                                    placeholder="e.g. 0712345678">

                                <button
                                    type="button"
                                    id="stkPushBtn"
                                    data-invoice-id="{{ $invoice->id }}"
                                    data-stk-url="{{ route('payment.mpesa.stk') }}"
                                    data-status-url="{{ route('payment.mpesa.status', $invoice->id) }}">
                                    STK Push
                                </button>

                            </div>

                        </div>


                    </div>


                </div>


            </div>

        </div>


    </div>

</main>

@endsection
