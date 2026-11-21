@extends('layouts.main')

@section('title', $p->display_name . ' · ' . $p->profession . ' · Smart Market')
@section('description', \Illuminate\Support\Str::limit($p->bio ?: ('Book ' . $p->display_name . ', ' . $p->profession . ', on Smart Market.'), 155))

@section('content')
{{-- A professional's page (ProsController@show): the link they share on TikTok etc. (?via=tiktok), and booking them. --}}
@php
    use App\Support\Professionals as P;
    $photo = P::mediaUrl($p->photo);
    $money = fn ($v) => $v === null ? null : 'KES ' . number_format((float) $v);
    $wa = $p->whatsapp ? 'https://wa.me/' . preg_replace('/^0/', '254', preg_replace('/\D/', '', $p->whatsapp)) . '?text=' . rawurlencode('Hi ' . $p->display_name . ', I found you on Smart Market.') : null;
    $booked = session('booked');
    $pickService = (int) old('service_id', request('service'));
    $mins = fn ($m) => $m ? ($m >= 60 ? rtrim(rtrim(number_format($m / 60, 1), '0'), '.') . ' h' : $m . ' min') : null;
@endphp
<div class="sm-page pr pr-profile">
    <a href="{{ route('pros.index') }}" class="pr-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Professionals</a>

    <section class="pr-hero">
        <span class="pr-face is-big">@if ($photo)<img src="{{ $photo }}" alt="{{ $p->display_name }}">@endif<i class="{{ P::CATEGORIES[$p->category][1] ?? 'fa-solid fa-briefcase' }}" aria-hidden="true"></i></span>
        <div class="pr-hero-text">
            <h1>{{ $p->display_name }}</h1>
            <p class="pr-prof">{{ $p->profession }}</p>
            <p class="pr-facts">
                @if ($place)<span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $place }}</span>@endif
                <span><i class="fa-solid fa-house-user" aria-hidden="true"></i> {{ P::WORKS_FROM[$p->works_from] ?? '' }}</span>
                @if ($p->years_experience)<span><i class="fa-solid fa-award" aria-hidden="true"></i> {{ $p->years_experience }} {{ \Illuminate\Support\Str::plural('year', (int) $p->years_experience) }} experience</span>@endif
                @if ($done)<span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> {{ $done }} {{ \Illuminate\Support\Str::plural('job', $done) }} done on Smart Market</span>@endif
            </p>
            @if ($links->isNotEmpty())
                <div class="pr-socials">
                    @foreach ($links as $l)
                        <a href="{{ $l->url }}" target="_blank" rel="noopener nofollow" title="{{ P::PLATFORMS[$l->platform][0] ?? $l->platform }}" class="is-{{ $l->platform }}"><i class="{{ P::PLATFORMS[$l->platform][1] ?? 'fa-solid fa-link' }}" aria-hidden="true"></i><span>{{ P::PLATFORMS[$l->platform][0] ?? $l->platform }}</span></a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="pr-hero-cta">
            <a href="#book" class="pr-btn is-solid"><i class="fa-solid fa-calendar-plus" aria-hidden="true"></i> Book</a>
            <a href="tel:{{ $p->phone }}" class="pr-btn"><i class="fa-solid fa-phone" aria-hidden="true"></i> Call</a>
            @if ($wa)<a href="{{ $wa }}" target="_blank" rel="noopener" class="pr-btn is-wa"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>@endif
        </div>
    </section>

    @if ($booked)
        <div class="pr-booked" role="status">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <div><b>Booking sent ({{ $booked }})</b><span>{{ $p->display_name }} will call you to confirm.@auth('customer') You'll also get a notification in your Hub.@endauth</span></div>
        </div>
    @endif

    <div class="pr-cols">
        <div class="pr-main">
            @if ($p->bio)
                <section class="pr-card-box">
                    <h2>About</h2>
                    <p>{!! nl2br(e($p->bio)) !!}</p>
                    @if ($markets->isNotEmpty())<p class="pr-tags">@foreach ($markets as $m)<span>{{ $m }}</span>@endforeach</p>@endif
                </section>
            @endif

            <section class="pr-card-box">
                <h2>Services</h2>
                @if ($services->isEmpty())
                    <p class="pr-muted">Ask {{ $p->display_name }} about what they offer when you book.@if ($p->rate_from !== null) Prices start from {{ $money($p->rate_from) }}{{ $p->rate_unit ? ' per ' . $p->rate_unit : '' }}.@endif</p>
                @else
                    <ul class="pr-services">
                        @foreach ($services as $s)
                            <li>
                                <div><b>{{ $s->name }}</b>@if ($s->description)<small>{{ $s->description }}</small>@endif</div>
                                <div class="pr-svc-side">
                                    <strong>{{ $money($s->price) ?? 'Ask' }}</strong>@if ($mins($s->duration_minutes))<small>{{ $mins($s->duration_minutes) }}</small>@endif
                                    <a href="{{ request()->fullUrlWithQuery(['service' => $s->id]) }}#book" class="pr-mini" data-pick-service="{{ $s->id }}">Book</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="pr-side">
            <section class="pr-card-box pr-book" id="book">
                <h2>Book {{ $p->display_name }}</h2>
                @if ($errors->any())<p class="pr-error">{{ $errors->first() }}</p>@endif
                <form method="POST" action="{{ route('pros.book', $p->account) }}">
                    @csrf
                    <input type="hidden" name="via" value="{{ old('via', $via) }}">
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="pr-hp" aria-hidden="true">
                    @if ($services->isNotEmpty())
                        <label><span>Service</span>
                            <select name="service_id" data-service-select>
                                <option value="">Not sure yet</option>
                                @foreach ($services as $s)<option value="{{ $s->id }}" @selected($pickService === (int) $s->id)>{{ $s->name }}{{ $s->price !== null ? ' · KES ' . number_format((float) $s->price) : '' }}</option>@endforeach
                            </select>
                        </label>
                    @endif
                    <label><span>Day and time</span><input type="datetime-local" name="wanted_at" value="{{ old('wanted_at') }}" min="{{ now()->addHour()->format('Y-m-d\TH:00') }}" required></label>
                    <label><span>Your name</span><input type="text" name="name" value="{{ old('name', $customer->name ?? '') }}" maxlength="80" required></label>
                    <label><span>Your phone</span><input type="tel" name="phone" value="{{ old('phone', $customer->phone_number ?? '') }}" inputmode="tel" placeholder="eg. 0712345678" required></label>
                    @if (in_array($p->works_from, ['clients_place', 'both'], true))
                        <label><span>Where <small>if they come to you</small></span><input type="text" name="location" value="{{ old('location') }}" maxlength="255" placeholder="e.g. Kilimani, Argwings Kodhek Rd"></label>
                    @endif
                    <label><span>Anything else <small>optional</small></span><textarea name="note" rows="3" maxlength="1000" placeholder="What you need, the occasion, questions…">{{ old('note') }}</textarea></label>
                    <button type="submit" class="pr-btn is-solid is-wide"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send booking</button>
                    <p class="pr-muted">No payment now. {{ $p->display_name }} confirms with you and you agree on the price.</p>
                </form>
            </section>
        </aside>
    </div>
</div>

<script>
    // "Book" next to a service picks it in the form
    document.addEventListener('click', (e) => {
        const a = e.target.closest && e.target.closest('[data-pick-service]');
        const sel = document.querySelector('[data-service-select]');
        if (!a || !sel) return;
        e.preventDefault();
        sel.value = a.dataset.pickService;
        sel.dispatchEvent(new Event('change', { bubbles: true }));
        document.getElementById('book').scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>
@endsection
