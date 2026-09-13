@extends('layouts.app')

@section('content')

<main class="wrapper">
    <div class="cart">
        <div class="cart-shipments">
            @php
                use App\Models\Cart;
                $cartItems = Cart::where('user_id', 1)->get();
                $groupedItems = [];
                
                // Group items by shipment type
                foreach ($cartItems as $item) {
                    $groupedItems[$item->shipment_type][] = $item;
                }
                
                $shipmentInfo = [
                    'quick' => ['icon' => 'ri-e-bike-2-line', 'label' => 'Quick in 50 - 120 Mins'],
                    'standard' => ['icon' => 'ri-truck-line', 'label' => 'Standard delivery date and time'],
                    'specific_shop' => ['icon' => 'ri-riding-line', 'label' => 'Order to specific shop']
                ];
                
                $shipmentTitles = ['Shipment 1', 'Shipment 2', 'Shipment 3'];
                $shipmentIndex = 0;
                $grandTotal = 0;
            @endphp
            
            @if($cartItems->isEmpty())
                <div class="empty-cart">
                    <h3>Your cart is empty</h3>
                    <a href="/" class="btn-continue">Continue Shopping</a>
                </div>
            @else
                @foreach($groupedItems as $shipmentType => $items)
                    @php
                        $shipmentSubtotal = 0;
                    @endphp
                    
                    <div class="cart-shipment">
                        <h2 class="title">{{ $shipmentTitles[$shipmentIndex] ?? 'Shipment' }}</h2>
                        <div class="shipment-detail">
                            <i class="{{ $shipmentInfo[$shipmentType]['icon'] ?? 'ri-package-line' }}"></i>
                            <span>
                                <h4>{{ $shipmentInfo[$shipmentType]['label'] ?? 'Delivery' }}</h4>
                                <h5>Accurate time to be calculated at checkout</h5>
                            </span>
                        </div>
                        
                        <div class="cart-items">
                            @foreach($items as $item)
                                @php
                                    $itemTotal = $item->price * $item->quantity;
                                    $shipmentSubtotal += $itemTotal;
                                    $grandTotal += $itemTotal;
                                @endphp
                                
                                <div class="cart-item" data-id="{{ $item->id }}">
                                    <div class="cart-img">
                                        <img src="{{ $item->product_image ? asset($item->product_image) : asset('img/placeholder.png') }}" 
                                             alt="{{ $item->product_name }}">
                                        @if($shipmentType == 'quick')
                                        <div class="ind">
                                            <i class="ri-e-bike-2-line"></i>
                                            quick
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div>
                                        <a href="#" class="product-link">{{ $item->product_name }}</a>
                                        <h4>Ksh{{ number_format($item->price, 2) }}</h4>
                                        <small>{{ $item->business_name }}</small>
                                    </div>
                                    
                                    <div class="countIcons">
                                        <i class="fa-solid fa-chevron-left dec" data-id="{{ $item->id }}"></i>
                                        <input type="text" 
                                               value="{{ $item->quantity }}" 
                                               maxlength="3"
                                               data-id="{{ $item->id }}"
                                               class="quantity-input">
                                        <i class="fa-solid fa-chevron-right inc" data-id="{{ $item->id }}"></i>
                                    </div>
                                </div>
                            @endforeach
                            
                            <div class="sub-total">
                                <div class="cost">
                                    <h4>SUB-TOTAL</h4>
                                    <h4>KSH {{ number_format($shipmentSubtotal, 2) }}</h4>
                                </div>
                                <button class="btn-checkout-shipment">Checkout</button>
                            </div>
                        </div>
                    </div>
                    
                    @php $shipmentIndex++; @endphp
                @endforeach
            @endif
        </div>
        
        <div class="cart-left">
            <div class="cart-shipment summary-items">
                <div class="summary">
                    <h3 class="title">Cart summary</h3>
                    <div class="summary-details">
                        @foreach($groupedItems as $shipmentType => $items)
                            @php
                                $subtotal = collect($items)->sum(function($item) {
                                    return $item->price * $item->quantity;
                                });
                            @endphp
                            <div class="summary-item">
                                <span>{{ $shipmentInfo[$shipmentType]['label'] ?? 'Items' }}</span>
                                <span>KSH {{ number_format($subtotal, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="sub-total">
                    <div class="cost">
                        <h4>SUB-TOTAL</h4>
                        <h4>KSH {{ number_format($grandTotal, 2) }}</h4>
                    </div>
                    <button class="btn-checkout-all">Checkout All</button>
                </div>
                
                <div class="payment-methods">
                    <h4>Payment Methods Available</h4>
                    <div class="payment-method">
                        <img src="{{ asset('img/logo/mpesa-logo.png') }}" alt="M-Pesa">
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

@endsection