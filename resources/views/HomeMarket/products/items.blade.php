@foreach($products as $product)
    <!-- 
      1. Toggles the '.active' styling layout class context dynamically if a discount is live
      2. Appends your exact visual badge string percentage marker (e.g. data-content="10%") 
    -->
    <div class="item-container card-data {{ $product->has_discount ? 'active' : '' }}" 
         @if($product->has_discount) data-content="{{ $product->discount_percentage }}%" @endif>

        <a href="{{ route('item.view', $product->id) }}">
            <img src="{{ $product->image_url }}" alt="{{ $product->product_name }}">
        </a>

        <span>
            <div class="price">
                <h4>Ksh</h4>
                <h4 class="cash">
                    <!-- Displays the calculated final markdown price value -->
                    {{ number_format($product->final_price, 2) }}
                </h4>
            </div>
            <i class="fa-solid fa-basket-shopping"></i>
        </span>

        <!-- 3. Renders the crossed-out original baseline retail price box ONLY when a promo markdown is live -->
        @if($product->has_discount)
            <h5 data-discount="{{ $product->discount_percentage }}% Off">
                Ksh {{ number_format($product->retail_price, 0) }}
            </h5>
        @endif

        @if(!empty($product->packaging_name))
            <span>
                <!-- <h4>Size:</h4> -->
                <h4>
                    <!-- Displays '2kg (Bale)' or '500G (Packet)' based on real database records -->
                    {{ $product->variant_label ?? 'N/A' }} 
                    ({{ strtolower($product->packaging_name) }})
                </h4>
            </span>
        @endif

        <h4>
            {{ $product->product_name }}
        </h4>
    </div>
@endforeach
