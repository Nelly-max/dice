@foreach($listings as $listing)
<div class="main property-container">
    <a href="{{ route('viewProperty', $listing->property->id) }}" class="img-area">
        <img src="{{ $listing->display_image }}" alt="Property Image">
    </a>

    <div class="property-details">
        <h5 class="extra">{{ $listing->display_type }}</h5>

        <a href="{{ route('viewProperty', $listing->property->id) }}">
            <h3 class="title">{{ $listing->property->title }}</h3>
        </a>

        <div class="property-det">
            @foreach($listing->vacant_units->take(3) as $unit)
                <span>
                    <i class="fa-solid fa-door-open"></i> 
                    {{ $unit->type->name ?? $unit->unit_number ?? $unit->space_type ?? 'Unit' }}
                </span>
            @endforeach

        </div>

        <div class="location">
            <i class="fa-solid fa-location-dot"></i>
        </div>

        <div class="details-footer">
            <span>
                @if($listing->min_price && $listing->max_price && $listing->min_price != $listing->max_price)
                    <h4>Ksh {{ number_format($listing->min_price) }}</h4> - <h4>Ksh {{ number_format($listing->max_price) }}</h4>
                @elseif($listing->min_price)
                    <h4>Ksh {{ number_format($listing->min_price) }}</h4>
                @else
                    <h4>Price not set</h4>
                @endif
            </span>
        </div>
    </div>
</div>
@endforeach
