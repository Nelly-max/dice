@extends('layouts.hub')

@section('content')
<div class="hub-content no-sidebar">
    <section class="table-top hub-table-card">

        {{-- Header: title, count, search, rows per page --}}
        <div class="top hub-table-head">
            <div class="hub-table-title">
                <h2 class="table_heading">Orders</h2>
                <span class="hub-count">{{ $orders->total() }}</span>
            </div>

            <form method="GET" action="{{ route('hub.orders') }}" class="right hub-table-tools" role="search">
                <div class="search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    {{-- typing filters this page instantly; Enter searches all orders --}}
                    <input
                        type="search"
                        name="search"
                        id="orderSearch"
                        value="{{ $search ?? '' }}"
                        placeholder="Search order, invoice, status…"
                        data-table-filter="ordersTableBody"
                        autocomplete="off"
                    >
                </div>

                <label class="hub-per-page">
                    <span>Show</span>
                    <select name="per_page" onchange="this.form.requestSubmit ? this.form.requestSubmit() : this.form.submit()">
                        @foreach ([10, 15, 25, 50] as $size)
                            <option value="{{ $size }}" @selected(($perPage ?? 15) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>

        <div class="hub-table-wrap">
            <table class="table hub-table" data-sortable>
                <thead>
                    <tr>
                        <th data-sort="text">Order</th>
                        <th data-sort="text">Invoice</th>
                        <th data-sort="date">Date</th>
                        <th data-sort="number" class="is-num">Total</th>
                        <th data-sort="text">Status</th>
                        <th>Delivery note</th>
                        <th class="is-action"><span class="sr-only">View</span></th>
                    </tr>
                </thead>

                <tbody id="ordersTableBody">
                    @forelse ($orders as $order)
                        @php $orderStatus = $order->status ?? 'pending'; @endphp
                        <tr data-href="#" class="order-row">
                            <td data-label="Order"><strong>{{ $order->order_id }}</strong></td>
                            <td data-label="Invoice">{{ $order->checkoutInvoice?->invoice_number ?? '—' }}</td>
                            <td data-label="Date" data-value="{{ $order->placed_at?->timestamp ?? 0 }}">
                                {{ $order->placed_at?->format('d M Y') ?? '—' }}
                                @if ($order->placed_at)<small>{{ $order->placed_at->format('g:i A') }}</small>@endif
                            </td>
                            <td data-label="Total" class="is-num" data-value="{{ (float) ($order->total_amount ?? 0) }}">
                                @if (!is_null($order->total_amount ?? null))
                                    KES {{ number_format((float) $order->total_amount) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td data-label="Status">
                                <span class="hub-pill hub-pill--{{ \Illuminate\Support\Str::slug($orderStatus) }}">{{ ucwords(str_replace('_', ' ', $orderStatus)) }}</span>
                            </td>
                            <td data-label="Delivery note" class="is-wrap">{{ $order->delivery_note ?? '—' }}</td>
                            <td class="emptyBack is-action" title="View">
                                <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </td>
                        </tr>
                    @empty
                        <tr class="hub-empty-row">
                            <td colspan="7">
                                <div class="hub-empty">
                                    <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                                    <strong>{{ ($search ?? '') !== '' ? 'No orders match “' . $search . '”' : 'No orders yet' }}</strong>
                                    <span>{{ ($search ?? '') !== '' ? 'Try a different order number, invoice or status.' : 'Orders you place will show up here.' }}</span>
                                    @if (($search ?? '') !== '')
                                        <a href="{{ route('hub.orders') }}" class="hub-link-btn">Clear search</a>
                                    @else
                                        <a href="{{ route('home') }}" class="hub-link-btn">Start shopping</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    {{-- shown by the instant filter when nothing on this page matches --}}
                    <tr class="hub-no-match" hidden>
                        <td colspan="7"><div class="hub-empty"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><strong>Nothing on this page matches</strong><span>Press Enter to search all your orders.</span></div></td>
                    </tr>
                </tbody>
            </table>
        </div>

        @include('partials.Hub.table-footer', ['paginator' => $orders, 'label' => 'orders'])
    </section>
</div>
@endsection
