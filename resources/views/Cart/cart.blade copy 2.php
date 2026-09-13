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
                
                // Sample data for demonstration
                $sampleData = [
                    'quick' => [
                        [
                            'image' => asset('img/homeMarket/FB001_HM.png'),
                            'name' => 'Cocacola 2liters',
                            'price' => 190,
                            'business' => 'Supermarket',
                            'quantity' => 1
                        ],
                        [
                            'image' => asset('img/homeMarket/FR001_HM.png'),
                            'name' => 'Rina vegetable oil 1liter',
                            'price' => 260,
                            'business' => 'Supermarket',
                            'quantity' => 1
                        ]
                    ],
                    'standard' => [
                        [
                            'image' => asset('img/homeMarket/FH001_HM.png'),
                            'name' => 'JIK bleach 3liters',
                            'price' => 830,
                            'business' => 'Home Store',
                            'quantity' => 1
                        ],
                        [
                            'image' => asset('img/homeMarket/FD002_HM.png'),
                            'name' => 'Aerial handwash powder 1kg',
                            'price' => 260,
                            'business' => 'Home Store',
                            'quantity' => 1
                        ]
                    ],
                    'specific_shop' => [
                        [
                            'image' => asset('img/cookingGas/totalgas-6kg.png'),
                            'name' => 'Total Gas (6KG)',
                            'price' => 850,
                            'business' => 'Gas Station',
                            'quantity' => 1
                        ],
                        [
                            'image' => asset('img/cookingGas/totalgas-13kg.png'),
                            'name' => 'Total Gas (13 KG)',
                            'price' => 1260,
                            'business' => 'Gas Station',
                            'quantity' => 1
                        ]
                    ]
                ];
                
                $shipmentInfo = [
                    'quick' => ['icon' => 'ri-e-bike-2-line', 'label' => 'Quick in 50 - 120 Mins'],
                    'standard' => ['icon' => 'ri-truck-line', 'label' => 'Standard delivery date and time'],
                    'specific_shop' => ['icon' => 'ri-riding-line', 'label' => 'Order to specific shop']
                ];
                
                $shipmentTitles = ['Shipment 1', 'Shipment 2', 'Shipment 3'];
                $shipmentIndex = 0;
                $grandTotal = 0;
                
                // Calculate totals from database
                $dbGrandTotal = $cartItems->sum(function($item) {
                    return $item->price * $item->quantity;
                });
            @endphp
            
            <!-- Display actual database items first -->
            @if($cartItems->isNotEmpty())
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
                                        <small style="color: #28a745; display: block;">(From Database)</small>
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
                                    
                                    <button class="btn-remove" data-id="{{ $item->id }}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
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
            
            <!-- Show sample data if database is empty -->
            @if($cartItems->isEmpty())
                @foreach($sampleData as $shipmentType => $items)
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
                            @foreach($items as $index => $item)
                                @php
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $shipmentSubtotal += $itemTotal;
                                    $grandTotal += $itemTotal;
                                @endphp
                                
                                <div class="cart-item" data-sample="true">
                                    <div class="cart-img">
                                        <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                                        @if($shipmentType == 'quick')
                                        <div class="ind">
                                            <i class="ri-e-bike-2-line"></i>
                                            quick
                                        </div>
                                        @endif
                                    </div>
                                    
                                    <div>
                                        <a href="#" class="product-link">{{ $item['name'] }}</a>
                                        <h4>Ksh{{ number_format($item['price'], 2) }}</h4>
                                        <small>{{ $item['business'] }}</small>
                                        <small style="color: #dc3545; display: block;">(Sample Data - Add real items to cart)</small>
                                    </div>
                                    
                                    <div class="countIcons">
                                        <i class="fa-solid fa-chevron-left dec-sample"></i>
                                        <input type="text" 
                                               value="{{ $item['quantity'] }}" 
                                               maxlength="3"
                                               class="quantity-input-sample"
                                               readonly>
                                        <i class="fa-solid fa-chevron-right inc-sample"></i>
                                    </div>
                                    
                                    <button class="btn-remove-sample">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                            
                            <div class="sub-total">
                                <div class="cost">
                                    <h4>SUB-TOTAL</h4>
                                    <h4>KSH {{ number_format($shipmentSubtotal, 2) }}</h4>
                                </div>
                                <button class="btn-checkout-shipment" disabled>Add items to enable checkout</button>
                            </div>
                        </div>
                    </div>
                    
                    @php $shipmentIndex++; @endphp
                @endforeach
            @endif
            
            @if($cartItems->isEmpty())
                <div class="empty-message">
                    <p>This is sample data. Add real items to your cart to see them here!</p>
                    <a href="/" class="btn-go-shopping">Go Shopping</a>
                </div>
            @endif
        </div>
        
        <div class="cart-left">
            <div class="cart-shipment summary-items">
                <div class="summary">
                    <h3 class="title">Cart summary</h3>
                    <div class="summary-details">
                        @if($cartItems->isNotEmpty())
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
                        @else
                            <!-- Sample summary -->
                            <div class="summary-item">
                                <span>Quick Delivery Items</span>
                                <span>KSH 450.00</span>
                            </div>
                            <div class="summary-item">
                                <span>Standard Delivery Items</span>
                                <span>KSH 1,090.00</span>
                            </div>
                            <div class="summary-item">
                                <span>Specific Shop Items</span>
                                <span>KSH 2,110.00</span>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="sub-total">
                    <div class="cost">
                        <h4>SUB-TOTAL</h4>
                        <h4>KSH {{ $cartItems->isNotEmpty() ? number_format($dbGrandTotal, 2) : '3,650.00' }}</h4>
                    </div>
                    @if($cartItems->isNotEmpty())
                        <button class="btn-checkout-all">Checkout All</button>
                    @else
                        <button class="btn-checkout-all" disabled>Add items to checkout</button>
                    @endif
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

@push('styles')
<style>
.empty-message {
    text-align: center;
    padding: 30px;
    background: #f8f9fa;
    border-radius: 10px;
    margin: 20px 0;
    border: 2px dashed #dee2e6;
}

.empty-message p {
    color: #666;
    margin-bottom: 15px;
}

.btn-go-shopping {
    display: inline-block;
    padding: 10px 20px;
    background: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}

.btn-go-shopping:hover {
    background: #0056b3;
}

.btn-checkout-all:disabled,
.btn-checkout-shipment:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-remove-sample {
    background: #6c757d;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: not-allowed;
}

.quantity-input-sample {
    background: #f8f9fa;
    cursor: not-allowed;
}

.dec-sample, .inc-sample {
    color: #ccc;
    cursor: not-allowed;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Only add event listeners for real items (not sample data)
    
    // Quantity increase for real items
    document.querySelectorAll('.inc:not(.inc-sample)').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.id;
            const input = this.parentElement.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (quantity < 99) {
                quantity++;
                input.value = quantity;
                updateQuantity(itemId, quantity);
            }
        });
    });
    
    // Quantity decrease for real items
    document.querySelectorAll('.dec:not(.dec-sample)').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.id;
            const input = this.parentElement.querySelector('.quantity-input');
            let quantity = parseInt(input.value);
            
            if (quantity > 1) {
                quantity--;
                input.value = quantity;
                updateQuantity(itemId, quantity);
            }
        });
    });
    
    // Direct input change for real items
    document.querySelectorAll('.quantity-input:not(.quantity-input-sample)').forEach(input => {
        input.addEventListener('change', function() {
            const itemId = this.dataset.id;
            let quantity = parseInt(this.value);
            
            if (quantity < 1) quantity = 1;
            if (quantity > 99) quantity = 99;
            
            this.value = quantity;
            updateQuantity(itemId, quantity);
        });
    });
    
    // Remove item for real items
    document.querySelectorAll('.btn-remove:not(.btn-remove-sample)').forEach(btn => {
        btn.addEventListener('click', function() {
            const itemId = this.dataset.id;
            if (confirm('Remove this item from cart?')) {
                removeItem(itemId);
            }
        });
    });
    
    // Checkout buttons
    document.querySelector('.btn-checkout-all:not(:disabled)')?.addEventListener('click', function() {
        window.location.href = '/checkout';
    });
    
    document.querySelectorAll('.btn-checkout-shipment:not(:disabled)').forEach(btn => {
        btn.addEventListener('click', function() {
            window.location.href = '/checkout';
        });
    });
    
    // Sample item buttons - show messages
    document.querySelectorAll('.btn-remove-sample').forEach(btn => {
        btn.addEventListener('click', function() {
            alert('This is sample data. Add real items to your cart to manage them.');
        });
    });
    
    document.querySelectorAll('.inc-sample, .dec-sample').forEach(btn => {
        btn.addEventListener('click', function() {
            alert('This is sample data. Add real items to your cart to manage quantities.');
        });
    });
    
    // Functions
    function updateQuantity(itemId, quantity) {
        fetch(`/cart/update/${itemId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ quantity: quantity })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating quantity');
        });
    }
    
    function removeItem(itemId) {
        fetch(`/cart/remove/${itemId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item');
        });
    }
});
</script>
@endpush

@endsection