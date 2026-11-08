@extends('Airbnb.layouts.app')

@section('title', $stay->title)
@section('body_class', 'hc-has-mbar')

@section('content')
{{--
    One stay (Airbnb\StayController@show). Photos and tabs reuse Home City's listing page pieces
    (#imageSlider / #photoCounter, services-ui.css). The booking card works out the total for the
    chosen dates (services-ui.js, [data-stay-book]); booking is off while config('airbnb.preview').
--}}
@php
    $types = config('airbnb.types', []);
    $typeLabel = $types[$stay->type][0] ?? 'Stay';
    $preview = config('airbnb.preview');            // booking not open yet
    $sample = $sample ?? false;                      // sample stays from config/airbnb.php
    $guests = (int) ($stay->guests ?? 0);
    $maxGuests = $guests > 0 ? $guests : max(2, ($stay->bedrooms ?: 1) * 2);
    $hostIntl = !empty($stay->host_phone) ? preg_replace('/^0/', '254', preg_replace('/\D/', '', $stay->host_phone)) : null;
    $cleaning = (int) config('airbnb.cleaning_fee', 0);
    $servicePct = (float) config('airbnb.service_fee_percent', 0);
    $photos = collect($stay->images);
@endphp

<main class="wrapper hc-page">

    @if ($sample)
        @include('partials.preview-banner', ['text' => 'This is a sample stay. Booking opens soon.'])
    @endif

    <nav class="sv-crumbs" aria-label="Breadcrumb">
        <a href="{{ route('airbnb.index') }}">Airbnb</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <a href="{{ route('airbnb.index', ['type' => $stay->type]) }}">{{ $typeLabel }}</a>
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        <span>{{ $stay->title }}</span>
    </nav>

    <div class="hc-gallery">
        <section @class(['hc-photos', 'is-single' => $photos->count() === 1]) id="imageSlider" aria-label="Photos">
            @foreach ($photos as $src)
                <a href="{{ $src }}"><img src="{{ $src }}" alt="" @if (!$loop->first) loading="lazy" @endif></a>
            @endforeach
        </section>
        <span class="hc-counter" id="photoCounter">1/{{ $photos->count() }}</span>
        <div class="hc-gallery-bar">
            <div class="hc-gallery-actions">
                <button type="button" class="sv-link-btn" data-share
                        data-share-title="{{ $stay->title }}"
                        data-share-text="{{ $stay->title }} · KES {{ number_format($stay->price) }} a night on Smart Market"
                        data-share-url="{{ url()->current() }}">
                    <i class="fa-solid fa-share-nodes" aria-hidden="true"></i> Share
                </button>
            </div>
        </div>
    </div>

    <div class="hc-head">
        <div>
            <div class="hc-tags">
                <span class="hc-tag"><i class="fa-solid fa-bed" aria-hidden="true"></i> {{ $typeLabel }}</span>
                @if (!empty($stay->rating))
                    <span class="hc-tag hc-tag--plain"><i class="fa-solid fa-star" aria-hidden="true"></i> {{ number_format($stay->rating, 1) }} · {{ $stay->reviews }} reviews</span>
                @endif
                @if (!empty($stay->featured))
                    <span class="hc-tag hc-tag--plain"><i class="fa-solid fa-star" aria-hidden="true"></i> Featured</span>
                @endif
                @if (!empty($stay->superhost))
                    <span class="hc-tag hc-tag--plain"><i class="fa-solid fa-award" aria-hidden="true"></i> Superhost</span>
                @endif
                <span @class(['hc-avail', 'is-full' => $stay->units_free < 1])>{{ $stay->units_free < 1 ? 'Fully booked' : $stay->units_free . ' ' . \Illuminate\Support\Str::plural('unit', $stay->units_free) . ' available' }}</span>
            </div>
            <h1>{{ $stay->title }}</h1>
            @if (!empty($stay->unit_name) && $stay->unit_name !== $stay->title)
                <p class="ab-unit-name">{{ $stay->unit_name }}</p>
            @endif
            <p class="hc-location">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $stay->area }}
                @if ($stay->distance_km !== null)
                    · {{ $stay->distance_km < 1 ? round($stay->distance_km * 1000, -1) . ' m' : number_format($stay->distance_km, 1) . ' km' }} from you
                @endif
            </p>
        </div>
        <div class="hc-head-price">
            <small>Per night</small>
            <strong>KES {{ number_format($stay->price) }}</strong>
        </div>
    </div>

    <div class="hc-layout">

        <section class="sv-panel">
            <div class="ab-specs">
                @if ($guests > 0)
                    <span><i class="fa-solid fa-user-group" aria-hidden="true"></i><b>{{ $guests }}</b> {{ \Illuminate\Support\Str::plural('guest', $guests) }}</span>
                @endif
                <span><i class="fa-solid fa-door-closed" aria-hidden="true"></i><b>{{ $stay->bedrooms ?: ($stay->unit_name ?? 'Studio') }}</b> @if ($stay->bedrooms) {{ \Illuminate\Support\Str::plural('bedroom', $stay->bedrooms) }} @endif</span>
                @if (!empty($stay->beds))
                    <span><i class="fa-solid fa-bed" aria-hidden="true"></i><b>{{ $stay->beds }}</b> {{ \Illuminate\Support\Str::plural('bed', $stay->beds) }}</span>
                @endif
                @if (!empty($stay->baths))
                    <span><i class="fa-solid fa-bath" aria-hidden="true"></i><b>{{ $stay->baths }}</b> {{ \Illuminate\Support\Str::plural('bathroom', $stay->baths) }}</span>
                @endif
            </div>

            <div class="hc-tabbox">
                <h3>About this stay</h3>
                <p>{{ $stay->description }}</p>

                <h3>What this place offers</h3>
                <div class="hc-amenities">
                    @foreach ($stay->amenities as $amenity)
                        <span class="hc-amenity"><i class="fa-regular fa-circle-check" aria-hidden="true"></i> {{ $amenity }}</span>
                    @endforeach
                </div>

                <h3>House rules</h3>
                <dl class="sv-rows">
                    <div><dt>Check-in</dt><dd>From 2:00 pm</dd></div>
                    <div><dt>Check-out</dt><dd>By 11:00 am</dd></div>
                    <div><dt>Smoking</dt><dd>Not allowed</dd></div>
                </dl>
            </div>
        </section>

        {{-- booking --}}
        <aside class="sv-panel hc-lister ab-book" aria-label="Book this stay"
               data-stay-book data-price="{{ $stay->price }}" data-cleaning="{{ $cleaning }}" data-service="{{ $servicePct }}">
            <p class="ab-book-price"><strong>KES {{ number_format($stay->price) }}</strong> / night</p>

            <div class="ab-dates">
                <label><span>Check-in</span><input type="date" data-in min="{{ now()->toDateString() }}" value="{{ now()->addDays(7)->toDateString() }}"></label>
                <label><span>Check-out</span><input type="date" data-out min="{{ now()->addDay()->toDateString() }}" value="{{ now()->addDays(9)->toDateString() }}"></label>
                <label class="ab-guests"><span>Guests</span>
                    <select data-guests>
                        @for ($g = 1; $g <= $maxGuests; $g++)
                            <option value="{{ $g }}" @selected($g === min(2, $maxGuests))>{{ $g }} {{ \Illuminate\Support\Str::plural('guest', $g) }}</option>
                        @endfor
                    </select>
                </label>
            </div>

            <dl class="sv-rows ab-sum">
                <div><dt data-nights-label>KES {{ number_format($stay->price) }} × 2 nights</dt><dd data-stay-sub>KES {{ number_format($stay->price * 2) }}</dd></div>
                <div><dt>Cleaning fee</dt><dd>KES {{ number_format($cleaning) }}</dd></div>
                <div><dt>Service fee</dt><dd data-stay-fee>KES {{ number_format(round($stay->price * 2 * $servicePct / 100)) }}</dd></div>
                <div class="ab-total"><dt>Total</dt><dd data-stay-total>KES {{ number_format($stay->price * 2 + $cleaning + round($stay->price * 2 * $servicePct / 100)) }}</dd></div>
            </dl>

            <button type="button" class="sv-btn sv-btn--primary sv-btn--block" @disabled($preview || $stay->units_free < 1)>
                <i class="fa-solid fa-calendar-check" aria-hidden="true"></i> {{ $preview ? 'Booking opens soon' : ($stay->units_free < 1 ? 'Fully booked' : 'Request to book') }}
            </button>

            <div class="hc-lister-top ab-host">
                <span class="ab-host-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr($stay->host, 0, 1)) }}</span>
                <div>
                    <small>Hosted by</small>
                    <h2>{{ $stay->host }} @if (!empty($stay->superhost))<i class="fa-solid fa-award" title="Superhost" aria-label="Superhost"></i>@endif</h2>
                </div>
            </div>

            @if ($hostIntl && $stay->units_free > 0)
                <a href="https://wa.me/{{ $hostIntl }}?text={{ rawurlencode('Hi, I saw "' . $stay->title . '" (' . $stay->unit_name . ') on Smart Market Airbnb. Is it available?') }}"
                   class="sv-btn sv-btn--block" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Ask the host</a>
            @endif

            <p class="hc-lister-note">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                <span>Pay only through Smart Market. Never send money to a host directly.</span>
            </p>
        </aside>
    </div>

    <section class="pdp-related ab-related">
        @include('Airbnb.partials.stay-row', ['title' => 'Other unit types here', 'items' => $stay->siblings ?? collect()])
        @include('Airbnb.partials.stay-row', ['title' => 'Similar stays', 'items' => $similar])
        @include('Airbnb.partials.stay-row', ['title' => 'Nearby stays', 'items' => $nearby])
    </section>

    {{-- phones: price + booking always in reach --}}
    <div class="sv-mbar">
        <span>
            <small>Per night</small>
            <strong>KES {{ number_format($stay->price) }}</strong>
        </span>
        <button type="button" class="sv-btn sv-btn--primary" onclick="document.querySelector('[data-stay-book]').scrollIntoView({behavior:'smooth'})">
            {{ $preview ? 'See dates' : 'Book' }}
        </button>
    </div>

</main>

@endsection
