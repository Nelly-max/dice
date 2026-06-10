@extends('HomeCity.layouts.app')

@section('content')
<div class="property-view">

    <!-- LEFT SIDE -->
    <section class="view-left">

        <div class="image-grid-holder">

            @php
                $hero = $property->display_image;
            @endphp

            <div class="image-grid">
                <section class="photo-grid" id="imageSlider">

                    {{-- HERO --}}
                    @if(!empty($hero))
                        <a class="photo-grid-col-2 photo-grid-row-2" href="#">
                            <img src="{{ $hero }}" alt="property image">
                        </a>
                    @endif

                    {{-- GALLERY --}}
                    @foreach($property->gallery_list as $image)

                        @if($image['url'] !== $hero)

                            <a class="photo-item" href="#">
                                <img src="{{ $image['url'] }}" alt="property image">

                                {{-- LABEL --}}
                                @if(!empty($image['label']))
                                    <span class="image-label">
                                        {{ ucfirst($image['label']) }}
                                    </span>
                                @endif

                            </a>

                        @endif

                    @endforeach

                </section>
            </div>

            <div class="bottom-items">
                <div class="bottom-pill">
                    <div class="pill-left">
                        <h5 id="photoCounter">
                            1/{{ max(1, count($property->gallery_list ?? []) + 1) }}
                        </h5>
                    </div>

                    <a href="#" class="pill-right">
                        <i class="fa-regular fa-images"></i>
                        <h5>view all</h5>
                    </a>
                </div>

                <div class="action-btns">
                    <div class="action-btn">
                        <i class="fa-regular fa-bookmark"></i>
                        <h5>Save</h5>
                    </div>

                    <div class="action-btn">
                        <i class="fa-solid fa-share-nodes"></i>
                        <h5>Share</h5>
                    </div>
                </div>
            </div>

            <div class="image-grid-details">
                <span>{{ $property->type_label ?? 'For Rent' }}</span>
                <span>Viewing fee</span>
                <h5 class="extra">furnished</h5>
            </div>

        </div>

        <!-- PROPERTY INFO -->
        <div class="info-area">
            <div class="property-info">

                <h2 class="title">{{ $property->title }}</h2>

                <!-- PRICE -->
                <span>
                    <h4>
                        Ksh {{ number_format($property->min_price) }}

                        @if($property->min_price != $property->max_price && $property->max_price > 0)
                            - {{ number_format($property->max_price) }}
                        @endif

                        @if(!empty($property->price_suffix))
                            <small>
                                {{ $property->price_suffix }}
                            </small>
                        @endif
                    </h4>
                </span>

                <!-- TABS -->
                <div class="tab_btns">
                    <button class="tab_btn active">Details</button>
                    <button class="tab_btn">Amenities</button>
                    <button class="tab_btn">Units</button>
                    <button class="tab_btn">Charges</button>
                    <div class="line"></div>
                </div>

                <div class="tab_box">

                    <!-- DETAILS TAB -->
                    <div class="tab active">

                        <h3>Overview</h3>

                        <div class="property-detail">
                            <div class="property-det">

                                @foreach($property->houseUnits as $unit)
                                    <span>
                                        <i class="fa-solid fa-house"></i>
                                        <h4>{{ $unit->type->name ?? 'Unit' }}</h4>
                                    </span>
                                @endforeach

                                {{-- Check for Shops --}}
                                @if($property->shopUnits->isNotEmpty())
                                    <span>
                                        <i class="fa-solid fa-store"></i>
                                        <h4>Shops</h4>
                                    </span>
                                @endif

                                {{-- Check for Offices --}}
                                @if($property->officeUnits->isNotEmpty())
                                    <span>
                                        <i class="fa-regular fa-building"></i>
                                        <h4>Offices</h4>
                                    </span>
                                @endif


                            </div>
                        </div>

                        <p>{{ $property->description }}</p>

                        <!-- LOCATION -->
                        <h3>Location</h3>
                        <div class="location">
                            <span>
                                <i class="fa-solid fa-location-dot"></i>
                                <h5>{{ $property->formatted_location }}</h5>
                            </span>
                        </div>

                    </div>

                    <!-- AMENITIES TAB -->
                    <div class="tab">
                        <div class="property-detail">
                            <div class="amenities">

                                @forelse($property->amenities as $amenity)
                                    <span>
                                        <i class="fa-regular fa-circle-check amenity-check"></i>
                                        {{ $amenity->name }}
                                    </span>
                                @empty
                                    <p>No amenities listed</p>
                                @endforelse

                            </div>
                        </div>
                    </div>

                    <!-- UNITS TAB -->
                    <div class="tab">
                        <div class="units">

                            <div class="status">
                            </div>

                            <div class="units-area">

                                @forelse($property->all_units as $unit)

                                    <div class="unit {{ $unit->css_class }}">
                                        <h4>{{ $unit->display_name }}</h4>
                                        <h4>{{ $unit->display_type }}</h4>
                                        <h4>Ksh {{ $unit->display_price }}</h4>
                                        <h4 class="status">{{ ucfirst($unit->status ?? 'Vacant') }}</h4>
                                        <button>Book</button>
                                    </div>

                                @empty
                                    <p>No units available</p>
                                @endforelse

                            </div>

                        </div>
                    </div>

                    <!-- CHARGES TAB -->
                    <div class="tab">
                        <h3>Extra Charges</h3>

                        <div class="charges">
                            <div class="charge">
                                <span><h4>Garbage Collection</h4><h4>Ksh300</h4></span>
                                <span><h4>Viewing Fee</h4><h4>Ksh100</h4></span>
                            </div>

                            <h3>Bills</h3>

                            <div class="charge">
                                <span><h4>Electricity</h4><h4>Tokens</h4></span>
                                <span><h4>Water</h4><h4>Metered</h4></span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

    </section>

    <!-- RIGHT SIDE -->
    <section class="view-right">

        <div class="lister-details">

            <a href="#">
                <img src="{{ asset('img/admin.png') }}" alt="Lister">
                <h2>{{ $listing->lister->business_name ?? 'Lister' }}</h2>
            </a>

            <h4>{{ $property->formatted_location }}</h4>

            <h3>{{ $listing->lister->phone ?? '+254...' }}</h3>

            <div class="lister-contacts">
                <button class="lister-contact">
                    <i class="ri-phone-line"></i>
                    <h5>Call</h5>
                </button>
                <button class="lister-contact">
                    <i class="ri-chat-1-line"></i>
                    <h5>Chat</h5>
                </button>
            </div>

        </div>

    </section>

</div>
@endsection
