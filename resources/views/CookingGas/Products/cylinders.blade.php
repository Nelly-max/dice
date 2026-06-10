@foreach($cylinders as $cylinder)
    <div class="product-container card-data">
        <a href="{{ route('view-gas', ['cylinder' => $cylinder->gas_cylinder_id, 'quantity' => $cylinder->gas_quantity_id]) }}" class="img-area">
            <img src="{{ $cylinder->image ? config('app.media_url') . '/' . ltrim($cylinder->image, '/') : asset('img/placeholder.png') }}" 
                 alt="{{ $cylinder->cylinder->brand_name ?? 'Gas Brand' }}">
        </a>

        <div class="product-details">
            <div class="product-det"><span>Refill</span></div>
            <h3 class="title">
                {{ $cylinder->cylinder->brand_name ?? 'Unknown' }} 
                {{ $cylinder->quantity->quantity ?? '' }}kg
            </h3>
            
            <div class="details-footer">
                <span>
                    @if($cylinder->min_price == $cylinder->max_price)
                        <h4>Ksh {{ number_format($cylinder->min_price) }}</h4>
                    @else
                        <h4>Ksh {{ number_format($cylinder->min_price) }} – {{ number_format($cylinder->max_price) }}</h4>
                    @endif
                </span>
            </div>
        </div>
    </div>
@endforeach
