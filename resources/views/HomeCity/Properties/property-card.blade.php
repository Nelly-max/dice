<div class="property-container card-data">

    <a href="{{ route('homecity.listing.view', $property->listing_id) }}" class="img-area">
        <img src="{{ $property->display_image }}" alt="{{ $property->display_title }}">
    </a>



    <div class="property-details">
        <h5 class="extra">{{ $property->type_label }}</h5>
        <h3 class="title">{{ $property->display_title }}</h3>

        <div class="property-det">
            <span>
                <i class="{{ $property->icon ?? 'fa-solid fa-bed' }}"></i> 
                {{ $property->unit_type }}
            </span>
        </div>

        <div class="location">
            <i class="fa-solid fa-location-dot"></i>
            <h5>{{ $property->location }}</h5>
        </div>

        <div class="details-footer">
            <span>
                @if($property->min_price != $property->max_price && $property->max_price > 0)
                    <h4>
                        Ksh {{ number_format($property->min_price) }}
                        - {{ number_format($property->max_price) }}
                        {{ $property->price_suffix }}
                    </h4>
                @else
                    <h4>
                        Ksh {{ number_format($property->min_price) }}
                        {{ $property->price_suffix }}
                    </h4>
                @endif
            </span>
        </div>
    </div>
</div>