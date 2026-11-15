@extends($layout)

@section('title', 'Booking ' . $b->reference)

@section('content')
{{-- One booking (BookingController@show): opened from the signed link, or signed in as the customer --}}
@php
    use App\Support\Bookings as B;
    [$sLabel, $sIcon, $sColor] = B::STATUSES[$b->status] ?? [ucfirst($b->status), 'fa-regular fa-circle', '#94a3b8'];
    $viewing = $b->kind === 'viewing';
    $host = $viewing ? 'lister' : 'host';
    $phone = trim((string) $b->business_phone);
    $intl = $phone !== '' ? preg_replace('/^0/', '254', preg_replace('/\D/', '', $phone)) : null;
    $icons = ['requested' => 'fa-regular fa-calendar-plus', 'confirmed' => 'fa-solid fa-check', 'declined' => 'fa-solid fa-xmark', 'cancelled' => 'fa-solid fa-ban',
        'completed' => 'fa-solid fa-flag-checkered', 'no_show' => 'fa-regular fa-eye-slash', 'reopened' => 'fa-solid fa-rotate-left', 'paid' => 'fa-solid fa-coins'];
    $said = ['requested' => 'You asked for this ' . ($viewing ? 'viewing' : 'stay'), 'confirmed' => 'Confirmed by the ' . $host, 'declined' => 'The ' . $host . ' could not accept it',
        'cancelled' => 'Cancelled', 'completed' => $viewing ? 'Viewing done' : 'Stay completed', 'no_show' => 'Marked as missed', 'reopened' => 'Back with the ' . $host, 'paid' => 'Payment recorded'];
    $steps = $events->whereIn('action', array_keys($said));
@endphp

<main class="wrapper hc-page bk-page">
    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ $back }}">{{ $b->property_title ?: ($viewing ? 'Home City' : 'Airbnb') }}</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>Booking {{ $b->reference }}</span>
    </nav>

    <header class="bk-head">
        <h1>{{ $viewing ? 'Viewing' : 'Stay' }} · {{ $b->reference }}</h1>
        <span class="bk-state" style="--clr: {{ $sColor }}">{{ $sLabel }}</span>
        <p>{{ $b->property_title }}@if ($b->unit_label) · {{ $b->unit_label }}@endif @if ($b->unit_number) ({{ $b->unit_number }})@endif</p>
    </header>

    @guest('customer')
        <p class="bk-save"><i class="fa-regular fa-bookmark" aria-hidden="true"></i>
            <span>Keep this page's link to come back to your booking. If you sign in with {{ $b->customer_phone }}, it also shows under Hub → Bookings.</span></p>
    @endguest

    <div class="bk-grid">
        <div>
            <section class="sv-panel">
                <h2 class="bk-panel-title"><i class="fa-regular fa-calendar" aria-hidden="true"></i> {{ $viewing ? 'Your viewing' : 'Your stay' }}</h2>
                <dl class="sv-rows">
                    @if ($viewing)
                        <div><dt>When</dt><dd>{{ B::when($b) }}</dd></div>
                    @else
                        <div><dt>Check-in</dt><dd>{{ \Carbon\Carbon::parse($b->check_in)->format('D j M Y') }} · from 2:00 pm</dd></div>
                        <div><dt>Check-out</dt><dd>{{ \Carbon\Carbon::parse($b->check_out)->format('D j M Y') }} · by 11:00 am</dd></div>
                        <div><dt>Guests</dt><dd>{{ $b->guests }}</dd></div>
                    @endif
                    <div><dt>Booked by</dt><dd>{{ $b->customer_name }} · {{ $b->customer_phone }}</dd></div>
                </dl>
                @if ($b->notes)<p class="bk-note"><b>Your message:</b> {{ $b->notes }}</p>@endif
                @if ($b->business_note)<p class="bk-note"><b>From the {{ $host }}:</b> {{ $b->business_note }}</p>@endif
                @if ($b->cancel_reason && in_array($b->status, ['cancelled', 'declined'], true))<p class="bk-note"><b>Reason:</b> {{ $b->cancel_reason }}</p>@endif

                @if (B::cancellable($b))
                    <details class="bk-cancel">
                        <summary>Cancel this booking</summary>
                        <form method="POST" action="{{ $cancelUrl }}">
                            @csrf
                            <input type="text" name="reason" maxlength="255" placeholder="Reason (optional)">
                            <button type="submit" class="sv-btn sv-btn--danger" onclick="return confirm('Cancel booking {{ $b->reference }}? The {{ $host }} will be told.')">Yes, cancel it</button>
                        </form>
                    </details>
                @endif
            </section>

            <section class="sv-panel">
                <h2 class="bk-panel-title"><i class="fa-solid fa-list-check" aria-hidden="true"></i> What happened</h2>
                <ol class="bk-steps">
                    @foreach ($steps as $e)
                        <li>
                            <i class="{{ $icons[$e->action] ?? 'fa-regular fa-circle' }}" aria-hidden="true"></i>
                            <div>
                                <b>{{ $said[$e->action] ?? ucfirst($e->action) }}</b>
                                <small>{{ \Carbon\Carbon::parse($e->created_at)->timezone(config('app.display_timezone', 'Africa/Nairobi'))->format('D j M, g:i A') }}</small>
                                @if ($e->note && $e->action !== 'paid')<p>{{ $e->note }}</p>@endif
                            </div>
                        </li>
                    @endforeach
                    @if ($b->status === 'pending')
                        <li><i class="fa-regular fa-hourglass-half" aria-hidden="true"></i><div><b>Waiting for the {{ $host }}</b><small>You'll get an SMS when they answer.</small></div></li>
                    @endif
                </ol>
            </section>
        </div>

        <aside>
            <section class="sv-panel">
                <h2 class="bk-panel-title"><i class="fa-solid fa-receipt" aria-hidden="true"></i> {{ $viewing ? 'Price' : 'Cost' }}</h2>
                <dl class="sv-rows ab-sum">
                    @if ($viewing)
                        <div><dt>Asking price</dt><dd>{{ (float) $b->unit_price > 0 ? 'KES ' . number_format($b->unit_price) : 'On request' }}</dd></div>
                    @else
                        <div><dt>KES {{ number_format($b->unit_price) }} × {{ $b->nights }} {{ $b->nights == 1 ? 'night' : 'nights' }}</dt><dd>KES {{ number_format($b->unit_price * $b->nights) }}</dd></div>
                        <div><dt>Cleaning fee</dt><dd>KES {{ number_format($b->cleaning_fee) }}</dd></div>
                        <div><dt>Service fee</dt><dd>KES {{ number_format($b->service_fee) }}</dd></div>
                        <div class="ab-total"><dt>Total</dt><dd>KES {{ number_format($b->amount) }}</dd></div>
                        <div><dt>Paid</dt><dd>{{ B::PAYMENT[$b->payment_status] ?? $b->payment_status }}@if ((float) $b->paid_amount > 0) · KES {{ number_format($b->paid_amount) }}@endif</dd></div>
                    @endif
                </dl>
            </section>

            <section class="sv-panel hc-lister-top ab-host">
                <span class="ab-host-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($b->business_name ?: 'H', 0, 1)) }}</span>
                <div>
                    <small>{{ $viewing ? 'Listed by' : 'Hosted by' }}</small>
                    <h2>{{ $b->business_name ?: ucfirst($host) }}</h2>
                </div>
            </section>
            @if ($intl && in_array($b->status, ['pending', 'confirmed'], true))
                <div class="hc-lister-btns" style="display:flex;gap:.5rem;margin-top:.6rem">
                    <a href="tel:+{{ $intl }}" class="sv-btn sv-btn--primary"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call</a>
                    <a href="https://wa.me/{{ $intl }}?text={{ rawurlencode('Hi, about my Smart Market booking ' . $b->reference . ' (' . $b->property_title . ', ' . B::when($b) . ').') }}" class="sv-btn" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
                </div>
            @endif
            <p class="hc-lister-note">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span>Pay only through Smart Market or the {{ $host }}'s official till / paybill, never to a personal number you were not given here.</span>
            </p>
        </aside>
    </div>
</main>

@include('Bookings._styles')
@endsection
