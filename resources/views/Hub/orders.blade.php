@extends('layouts.hub')

@section('content') 
<div class="hub-content fullbground no-sidebar"> 
    <div class="orders"> 
        <div class="table-top">

            <div class="top">
                <h2 class="table_heading">Orders</h2>

                <div class="right">
                    <div class="search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input
                            type="text"
                            id="orderSearch"
                            placeholder="search"
                        >
                    </div>
                </div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Checkout Invoice</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Delivery Note</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="ordersTableBody">

                    @forelse ($orders as $order)

                        <tr
                            data-href="#"
                            class="order-row"
                        >
                            <td>
                                {{ $order->order_id }}
                            </td>

                            <td>
                                {{ $order->checkoutInvoice?->invoice_number ?? '—' }}
                            </td>

                            <td>
                                {{ $order->placed_at?->format('d/m/Y') ?? '—' }}
                            </td>
                            
                            <td>
                                {{ $order->status ?? '—' }}
                            </td>

                            <td>
                                {{ $order->delivery_note ?? '—' }}
                            </td>

                            <td
                                class="emptyBack"
                                title="view"
                            >
                                <i class="fa-solid fa-square-arrow-up-right"></i>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" style="text-align: center;">
                                No orders found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>

            @if ($orders->hasPages())
                <div class="pagination">
                    {{ $orders->links() }}
                </div>
            @endif

        </div>
    </div>
</div>

@endsection
