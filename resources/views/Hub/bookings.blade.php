@extends('layouts.hub')

{{-- Hub → Bookings: the signed-in customer's Airbnb stays and Home City viewings (BookingController@index) --}}
@php use App\Support\Bookings as B; @endphp

@section('content')
<div class="hub-content no-sidebar">
    <section class="table-top hub-table-card">
        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">Bookings</h2>
                <span class="hub-count">{{ $bookings->total() }}</span>
            </div>
            <form method="GET" action="{{ route('hub.bookings') }}" class="right hub-table-tools">
                <label class="hub-per-page">
                    <span>Show</span>
                    <select name="status" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                        <option value="">All</option>
                        @foreach (B::STATUSES as $k => [$label])
                            <option value="{{ $k }}" @selected($status === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Place</th>
                        <th>When</th>
                        <th class="is-num">Total</th>
                        <th>Status</th>
                        <th class="is-action"><span class="sr-only">View</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $b)
                        @php [$sLabel, , $sColor] = B::STATUSES[$b->status] ?? [ucfirst($b->status), '', '#94a3b8']; @endphp
                        <tr>
                            <td data-label="Booking">
                                <strong>{{ $b->reference }}</strong>
                                <small>{{ $b->subdivision === 'homecity' ? 'Home City viewing' : 'Airbnb stay' }}</small>
                            </td>
                            <td data-label="Place" class="is-wrap">{{ $b->property_title }}@if ($b->unit_label)<small>{{ $b->unit_label }}@if ($b->unit_number) · {{ $b->unit_number }}@endif</small>@endif</td>
                            <td data-label="When">{{ B::when($b) }}</td>
                            <td data-label="Total" class="is-num">{{ (float) $b->amount > 0 ? 'KES ' . number_format($b->amount) : '—' }}</td>
                            <td data-label="Status"><span class="hub-pill" style="color: {{ $sColor }}; background: color-mix(in srgb, {{ $sColor }} 13%, transparent)">{{ $sLabel }}</span></td>
                            <td class="is-action"><a href="{{ $links[$b->id] }}" title="Open" aria-label="Open {{ $b->reference }}"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="6">
                                <div class="hub-empty">
                                    <i class="fa-regular fa-calendar-check" aria-hidden="true"></i>
                                    <strong>{{ !$ready ? 'Bookings are not open yet' : ($status ? 'No bookings here' : 'No bookings yet') }}</strong>
                                    <span>Stays you book on Airbnb and viewings you book on Home City show up here.</span>
                                    <a href="{{ route('airbnb.index') }}" class="hub-link-btn">Find a stay</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @include('partials.Hub.table-footer', ['paginator' => $bookings, 'label' => 'bookings'])
    </section>
</div>
@endsection
