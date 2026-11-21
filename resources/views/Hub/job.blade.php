@extends('layouts.hub')

@section('content')
{{-- One job (Hub\ProfessionalController@job): who booked, for what and when; accept / decline / done / cancel. --}}
@php
    use App\Support\Professionals as P;
    $j = $job;
    $when = $j->wanted_at ? \Illuminate\Support\Carbon::parse($j->wanted_at) : null;
    $pill = ['new' => 'pending', 'accepted' => 'confirmed', 'declined' => 'rejected', 'done' => 'completed', 'cancelled' => 'cancelled'];
    $wa = 'https://wa.me/' . preg_replace('/^0/', '254', preg_replace('/\D/', '', $j->customer_phone));
@endphp
<div class="hub-content no-sidebar hd-page">

    <a href="{{ route('hub.jobs') }}" class="hd-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Jobs</a>

    <header class="hd-head">
        <span class="hd-head-icon pro-icon"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>
        <div>
            <h1>{{ $j->service_name ?: 'General booking' }}</h1>
            <p>{{ $j->reference }} · booked {{ \Illuminate\Support\Carbon::parse($j->created_at)->diffForHumans() }}{{ $j->source && $j->source !== 'smartmarket' ? ' · via ' . (P::PLATFORMS[$j->source][0] ?? $j->source) : '' }}</p>
        </div>
        <span class="hub-pill hub-pill--{{ $pill[$j->status] ?? 'pending' }}">{{ P::JOB_STATUSES[$j->status][0] ?? ucfirst($j->status) }}</span>
    </header>

    @if (in_array($j->status, ['new', 'accepted'], true))
        <section class="hub-card pro-job-actions">
            <h2 class="hub-card-title">{{ $j->status === 'new' ? 'Can you take this job?' : 'How did it go?' }}</h2>
            <form method="POST" action="{{ route('hub.jobs.update', $j->id) }}" class="pro-job-form">
                @csrf @method('PATCH')
                @if ($j->status === 'new')
                    <label class="pro-field"><b>Price <small>change it if you agreed another one</small></b>
                        <input type="number" name="price" value="{{ $j->price !== null ? preg_replace('/\.0+$|(\.\d*?)0+$/', '$1', (string) $j->price) : '' }}" min="0" step="1" placeholder="KES">
                    </label>
                @endif
                <label class="pro-field"><b>Message to the customer <small>needed to decline or cancel</small></b>
                    <textarea name="reply" rows="2" maxlength="500" placeholder="{{ $j->status === 'new' ? 'e.g. See you on Saturday at 10. Bring your own lashes.' : 'e.g. Thank you! Book me again any time.' }}">{{ old('reply') }}</textarea>
                </label>
                @error('reply')<small class="form-error">{{ $message }}</small>@enderror
                <div class="pro-job-btns">
                    @if ($j->status === 'new')
                        <button type="submit" name="status" value="accepted" class="pro-btn is-solid"><i class="fa-solid fa-check" aria-hidden="true"></i> Accept</button>
                        <button type="submit" name="status" value="declined" class="pro-btn is-danger"><i class="fa-solid fa-xmark" aria-hidden="true"></i> Decline</button>
                    @else
                        <button type="submit" name="status" value="done" class="pro-btn is-solid"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Mark done</button>
                        <button type="submit" name="status" value="cancelled" class="pro-btn is-danger"><i class="fa-solid fa-ban" aria-hidden="true"></i> Cancel job</button>
                    @endif
                </div>
            </form>
        </section>
    @endif

    <div class="hd-grid">
        <section class="hub-card">
            <h2 class="hub-card-title">Customer</h2>
            <dl class="hd-list">
                <div><dt>Name</dt><dd>{{ $j->customer_name }}</dd></div>
                <div><dt>Phone</dt><dd><a href="tel:{{ $j->customer_phone }}">{{ $j->customer_phone }}</a></dd></div>
                <div><dt>WhatsApp</dt><dd><a href="{{ $wa }}" target="_blank" rel="noopener">Message on WhatsApp</a></dd></div>
            </dl>
        </section>
        <section class="hub-card">
            <h2 class="hub-card-title">The job</h2>
            <dl class="hd-list">
                <div><dt>When</dt><dd>{{ $when?->format('l d M Y, H:i') ?? '—' }}</dd></div>
                <div><dt>Where</dt><dd>{{ $j->location ?: '—' }}</dd></div>
                <div><dt>Price</dt><dd>{{ $j->price !== null ? 'KES ' . number_format((float) $j->price) : 'To agree' }}</dd></div>
                @if ($j->responded_at)<div><dt>Answered</dt><dd>{{ \Illuminate\Support\Carbon::parse($j->responded_at)->format('d M Y, H:i') }}</dd></div>@endif
                @if ($j->completed_at)<div><dt>Done</dt><dd>{{ \Illuminate\Support\Carbon::parse($j->completed_at)->format('d M Y, H:i') }}</dd></div>@endif
            </dl>
        </section>
    </div>

    @if ($j->note || $j->reply)
        <section class="hub-card">
            <h2 class="hub-card-title">Messages</h2>
            @if ($j->note)<p class="hd-text"><b>{{ $j->customer_name }}:</b> {!! nl2br(e($j->note)) !!}</p>@endif
            @if ($j->reply)<p class="hd-text"><b>You:</b> {!! nl2br(e($j->reply)) !!}</p>@endif
        </section>
    @endif
</div>
@endsection
