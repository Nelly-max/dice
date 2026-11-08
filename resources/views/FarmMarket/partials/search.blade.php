 @forelse($cylinders as $cylinder)
    <a href="{{ route('view-gas', ['cylinder' => $cylinder->gas_cylinder_id, 'quantity' => $cylinder->gas_quantity_id]) }}" class="search-result" style="text-decoration: none; color: inherit;">
        <img src="{{ $cylinder->image ? config('app.media_url') . '/' . ltrim($cylinder->image, '/') : asset('img/placeholder.png') }}" 
            alt="{{ $cylinder->cylinder->brand_name }}">
        <div>
            <h3>{{ $cylinder->cylinder->brand_name }} {{ $cylinder->quantity->quantity }}kg</h3>
            <h4>
                @if($cylinder->min_price == $cylinder->max_price)
                    Ksh {{ number_format($cylinder->min_price) }}
                @else
                    Ksh {{ number_format($cylinder->min_price) }} – {{ number_format($cylinder->max_price) }}
                @endif
            </h4>
        </div>
    </a>
@empty
    <div class="search-result">
        <p style="padding: 10px; color: #888;">No results found for your search.</p>
    </div>
@endforelse