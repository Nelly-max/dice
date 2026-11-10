@extends('layouts.flow')

@section('heading', 'Payment')
@section('meta', 'M-Pesa')
@section('back', route('cart.checkout'))

@section('content')
{{--
    M-Pesa payment for one checkout invoice (PaymentController@mpesa).
    resources/js/orderPayment.js:
      - sends the STK prompt  (POST route('payment.mpesa.stk'), #stkPushBtn, #mpesa_phone, #invoice_id)
      - polls route('payment.status', uuid) and moves the tracker [data-pay-track] through its steps
      - goes to the order page once the invoice is paid (by STK or by Paybill)
--}}
@php
    $total = (float) $invoice->total_amount;
    $paid = (float) ($invoice->amount_paid ?? 0);
    $balance = max(0, $total - $paid);
    $paybillNo = $paybill ?? '117450';
    $phone = preg_replace('/^(?:\+?254)/', '0', (string) ($invoice->phone_number ?? ''));
@endphp

<div class="wrapper fl-page cx-page px-page">

    <ol class="cx-steps" aria-label="Progress">
        <li class="is-done"><i class="fa-solid fa-check" aria-hidden="true"></i><span>Cart</span></li>
        <li class="is-done"><i class="fa-solid fa-check" aria-hidden="true"></i><span>Details</span></li>
        <li class="is-current" aria-current="step"><b>3</b><span>Pay</span></li>
    </ol>

    <div class="px-layout"
         id="payment-container"
         data-invoice-uuid="{{ $invoice->uuid }}"
         data-status-url="{{ route('payment.status', $invoice->uuid) }}"
         data-total="{{ $total }}"
         data-paid="{{ $paid }}">

        {{-- ===================== amount ===================== --}}
        <section class="cx-card px-amount" aria-label="Amount to pay">
            <img class="px-mpesa" src="{{ asset('img/logo/mpesa-logo.png') }}" alt="M-Pesa">
            <small>Amount to pay</small>
            <strong data-pay-balance>KES {{ number_format($balance) }}</strong>
            <p>Invoice <b>#{{ $invoice->payment_account }}</b></p>

            <div class="px-part" data-pay-part @if ($paid <= 0) hidden @endif>
                <i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i>
                <span>Received <b data-pay-received>KES {{ number_format($paid) }}</b> of KES {{ number_format($total) }}. Pay the balance to place your order.</span>
            </div>
        </section>

        <div class="px-main">

            {{-- ===================== choose how to pay ===================== --}}
            <div class="px-switch" role="tablist" aria-label="How to pay">
                <button type="button" role="tab" id="px-tab-stk" class="is-active" aria-selected="true" aria-controls="px-stk" data-pay-tab="stk">
                    <i class="fa-solid fa-mobile-screen-button" aria-hidden="true"></i>
                    <span>Phone prompt</span>
                </button>
                <button type="button" role="tab" id="px-tab-paybill" aria-selected="false" aria-controls="px-paybill" data-pay-tab="paybill" tabindex="-1">
                    <i class="fa-solid fa-building-columns" aria-hidden="true"></i>
                    <span>Paybill</span>
                </button>
            </div>

            {{-- STK push --}}
            <section class="cx-card px-panel" id="px-stk" role="tabpanel" aria-labelledby="px-tab-stk" data-pay-panel="stk">
                <h2>Get a payment prompt</h2>
                <p class="px-lead">We'll send an M-Pesa prompt to this number. Enter your M-Pesa PIN on your phone to pay.</p>

                <input type="hidden" id="invoice_id" value="{{ $invoice->id }}">

                <label class="cx-field px-phone">
                    <span>M-Pesa number</span>
                    <span class="px-phone-box">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                        <input type="tel" id="mpesa_phone" class="mobile-number" value="{{ $phone }}"
                               placeholder="0712 345 678" inputmode="tel" autocomplete="tel" maxlength="16">
                    </span>
                </label>
                <p class="px-error" id="px-phone-error" hidden></p>

                <button type="button" id="stkPushBtn" class="cx-btn cx-btn--primary px-send"
                        data-invoice-id="{{ $invoice->id }}"
                        data-stk-url="{{ route('payment.mpesa.stk') }}">
                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    <span>Send prompt · <b data-pay-balance>KES {{ number_format($balance) }}</b></span>
                </button>
            </section>

            {{-- Paybill --}}
            <section class="cx-card px-panel" id="px-paybill" role="tabpanel" aria-labelledby="px-tab-paybill" data-pay-panel="paybill" hidden>
                <h2>Pay with Paybill</h2>
                <p class="px-lead">Use these details from the M-Pesa menu on your phone. Keep this page open; we'll confirm the payment here.</p>

                <dl class="px-copy">
                    <div>
                        <dt>Business number</dt>
                        <dd><span>{{ $paybillNo }}</span>
                            <button type="button" class="px-copy-btn" data-copy="{{ $paybillNo }}" aria-label="Copy business number"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copy</button></dd>
                    </div>
                    <div>
                        <dt>Account number</dt>
                        <dd><span>{{ $invoice->payment_account }}</span>
                            <button type="button" class="px-copy-btn" data-copy="{{ $invoice->payment_account }}" aria-label="Copy account number"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copy</button></dd>
                    </div>
                    <div>
                        <dt>Amount</dt>
                        <dd><span data-pay-balance>KES {{ number_format($balance) }}</span>
                            <button type="button" class="px-copy-btn" data-copy="{{ (int) ceil($balance) }}" data-copy-balance aria-label="Copy amount"><i class="fa-regular fa-copy" aria-hidden="true"></i> Copy</button></dd>
                    </div>
                </dl>

                <ol class="px-howto">
                    <li><span>Open <b>M-PESA</b> and choose <b>Lipa na M-PESA</b></span></li>
                    <li><span>Choose <b>Pay Bill</b> and enter business number <b>{{ $paybillNo }}</b></span></li>
                    <li><span>Enter account number <b>{{ $invoice->payment_account }}</b></span></li>
                    <li><span>Enter the amount, then your PIN, and send</span></li>
                </ol>

                <button type="button" class="cx-btn cx-btn--soft px-check" data-pay-check>
                    <i class="fa-solid fa-rotate" aria-hidden="true"></i>
                    <span>I've paid – check now</span>
                </button>
            </section>

            {{-- ===================== live progress ===================== --}}
            <section class="cx-card px-track" data-pay-track data-state="idle" aria-label="Payment progress">
                <div class="px-track-head">
                    <span class="px-track-icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div>
                        <h3 data-pay-title>Waiting for your payment</h3>
                        <p data-pay-text role="status" aria-live="polite">Choose how you'd like to pay. This page updates by itself.</p>
                    </div>
                    <span class="px-timer" data-pay-timer hidden>0:00</span>
                </div>

                <ol class="px-stages" data-pay-stages hidden>
                    <li data-stage="sent"><b aria-hidden="true"></b><span><strong>Prompt sent</strong><small data-stage-note>To your phone</small></span></li>
                    <li data-stage="pin"><b aria-hidden="true"></b><span><strong>Enter your M-Pesa PIN</strong><small>Check your phone and approve the payment</small></span></li>
                    <li data-stage="confirm"><b aria-hidden="true"></b><span><strong>Confirming with M-Pesa</strong><small>This takes a few seconds</small></span></li>
                    <li data-stage="done"><b aria-hidden="true"></b><span><strong>Payment received</strong><small>Placing your order</small></span></li>
                </ol>

                <div class="px-track-actions" data-pay-actions hidden>
                    <button type="button" class="cx-btn cx-btn--primary cx-btn--sm" data-pay-retry>
                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Send the prompt again
                    </button>
                    <button type="button" class="cx-btn cx-btn--ghost cx-btn--sm" data-pay-tab="paybill">
                        Use Paybill instead
                    </button>
                </div>
            </section>

            <a href="{{ route('cart.checkout') }}" class="px-cancel">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to checkout
            </a>

            <p class="px-secure"><i class="fa-solid fa-lock" aria-hidden="true"></i> Payments go straight to M-Pesa. We never see your PIN.</p>
        </div>

    </div>

</div>

@endsection
