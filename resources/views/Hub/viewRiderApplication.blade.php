@extends('layouts.hub')

@section('content')

<div class="hub-content no-sidebar fullground">
    <form
        action="{{ route('hub.account.rider-application.apply') }}"
        method="POST"
        enctype="multipart/form-data"
        id="riderApplicationForm"
    >

        @csrf

        <div class="hub-dash">

            {{-- =========================================================
                 LEFT SIDE
            ========================================================== --}}
            <div class="left">

                <div class="account-details">

                    {{-- APPLICATION STATUS --}}
                    <div class="account-detail top-area">

                        <h4>
                            Status -
                            {{ ucfirst($rider->account_status ?? 'Pending') }}
                        </h4>

                    </div>


                    {{-- =================================================
                         PERSONAL DETAILS
                    ================================================== --}}
                    <div class="account-detail">

                        <h1 class="sub-heading">
                            Personal Details
                        </h1>


                        {{-- NAME --}}
                        <span>

                            <h4>Name</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="name"
                                    value="{{ old('name', $rider->name ?? '') }}"
                                    placeholder="Your Name"
                                    required
                                >

                            </div>

                        </span>


                        {{-- PHONE --}}
                        <span>

                            <h4>Phone</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="phone"
                                    value="{{ old('phone', $rider->phone ?? '') }}"
                                    class="mobile-number"
                                    placeholder="eg. 0701234567"
                                    required
                                >

                            </div>

                        </span>


                        {{-- EMAIL --}}
                        <span>

                            <h4>Email</h4>

                            <div class="required-input">

                                <input
                                    type="email"
                                    name="email"
                                    value="{{ old('email', $rider->email ?? '') }}"
                                    placeholder="eg. myname@gmail.com"
                                >

                            </div>

                        </span>


                        {{-- NATIONAL ID --}}
                        <span>

                            <h4>ID Number</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="national_id"
                                    value="{{ old('national_id', $rider->national_id ?? '') }}"
                                    placeholder="National ID No."
                                    required
                                >

                            </div>

                        </span>


                        {{-- LICENCE NUMBER --}}
                        <span>

                            <h4>Licence No.</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="license_number"
                                    value="{{ old('license_number', $rider->license_number ?? '') }}"
                                    placeholder="your driver's licence no."
                                >

                            </div>

                        </span>


                        {{-- DATE OF BIRTH --}}
                        <span>

                            <h4>Date Of Birth</h4>

                            <input
                                type="date"
                                name="date_of_birth"
                                value="{{ $rider->date_of_birth?->format('Y-m-d') }}"
                                class="custom-date-picker"
                                min="{{ now()->subYears(80)->format('Y-m-d') }}"
                                max="{{ now()->subYears(18)->format('Y-m-d') }}"
                                required
                            >

                        </span>

                    </div>


                    {{-- =================================================
                         PAYMENT INFORMATION
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="sub-heading">
                            Payment Information
                        </h4>


                        <span>

                            <h4>Mpesa Number</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="mpesa_number"
                                    value="{{ old('mpesa_number', $rider->mpesa_number ?? '') }}"
                                    class="mobile-number"
                                    placeholder="Mpesa Number"
                                    required
                                >

                            </div>

                        </span>

                    </div>


                    {{-- =================================================
                         VEHICLE DETAILS
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="sub-heading">
                            Vehicle Details
                        </h4>


                        {{-- PLATE NUMBER --}}
                        <span>

                            <h4>Plate Number</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="plate_number"
                                    value="{{ old('plate_number', $carDetails->plate_number ?? '') }}"
                                    placeholder="Vehicle Plate Number"
                                    required
                                >

                            </div>

                        </span>


                        {{-- VEHICLE MAKE --}}
                        <span>

                            <h4>Vehicle Make</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="vehicle_make"
                                    value="{{ old('vehicle_make', $carDetails->vehicle_make ?? '') }}"
                                    placeholder="eg. Honda"
                                    required
                                >

                            </div>

                        </span>


                        {{-- VEHICLE MODEL --}}
                        <span>

                            <h4>Vehicle Model</h4>

                            <div class="required-input">

                                <input
                                    type="text"
                                    name="vehicle_model"
                                    value="{{ old('vehicle_model', $carDetails->vehicle_model ?? '') }}"
                                    placeholder="eg. Boxer 150"
                                    required
                                >

                            </div>

                        </span>


                        {{-- VEHICLE TYPE --}}
                        <span>

                            <h4>Vehicle Type</h4>

                            <select
                                name="vehicle_type"
                                required
                            >

                                <option value="">
                                    Select Vehicle Type
                                </option>

                                <option
                                    value="motorcycle"
                                    {{ old('vehicle_type', $carDetails->vehicle_type ?? '') === 'motorcycle' ? 'selected' : '' }}
                                >
                                    Motorcycle
                                </option>

                                <option
                                    value="car"
                                    {{ old('vehicle_type', $carDetails->vehicle_type ?? '') === 'car' ? 'selected' : '' }}
                                >
                                    Car
                                </option>

                            </select>

                        </span>

                    </div>


                    {{-- =================================================
                         LOCALITY DETAILS
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="sub-heading">
                            Your locality
                        </h4>


                        {{-- COUNTY --}}
                        <span>

                            <h4>County</h4>

                            <div class="required-input">

                                @php
                                    $selectedCountyId = old(
                                        'county_id',
                                        $rider->county_id ?? ''
                                    );

                                    $selectedCounty = $counties->firstWhere(
                                        'id',
                                        $selectedCountyId
                                    );
                                @endphp

                                <div
                                    class="custom-select"
                                    id="countySelect"
                                    data-options='@json($counties)'
                                    data-selected="{{ $selectedCountyId }}"
                                >

                                    <div class="select-btn">

                                        <span>
                                            {{ $selectedCounty->name ?? 'County' }}
                                        </span>

                                        <i class="fa-solid fa-chevron-down"></i>

                                    </div>


                                    <div class="select-options">

                                        <div class="search">

                                            <input
                                                class="option-search"
                                                type="text"
                                                placeholder="Search..."
                                            >

                                        </div>

                                        <ul class="options"></ul>

                                    </div>


                                    <input
                                        type="hidden"
                                        name="county_id"
                                        id="county_id"
                                        value="{{ $selectedCountyId }}"
                                    >

                                </div>

                            </div>

                        </span>


                        {{-- TOWN --}}
                        <span>

                            <h4>Town</h4>

                            <div class="required-input">

                                <div
                                    class="custom-select"
                                    id="townSelect"
                                    data-options='@json($towns ?? [])'
                                    data-selected="{{ old('town_id', $rider->town_id ?? '') }}"
                                >

                                    <div class="select-btn">

                                        <span>
                                            {{ $town->name ?? 'Town' }}
                                        </span>

                                        <i class="fa-solid fa-chevron-down"></i>

                                    </div>


                                    <div class="select-options">

                                        <div class="search">

                                            <input
                                                class="option-search"
                                                type="text"
                                                placeholder="Search..."
                                            >

                                        </div>

                                        <ul class="options"></ul>

                                    </div>


                                    <input
                                        type="hidden"
                                        name="town_id"
                                        id="town_id"
                                        value="{{ old('town_id', $rider->town_id ?? '') }}"
                                    >

                                </div>

                            </div>

                        </span>


                        {{-- PLACE --}}
                        <span>

                            <h4>Place</h4>

                            <div class="required-input">

                                <div
                                    class="custom-select"
                                    id="placeSelect"
                                    data-options='@json($places ?? [])'
                                    data-selected="{{ old('place_id', $rider->place_id ?? '') }}"
                                >

                                    <div class="select-btn">

                                        <span>
                                            {{ $place->name ?? 'Place' }}
                                        </span>

                                        <i class="fa-solid fa-chevron-down"></i>

                                    </div>


                                    <div class="select-options">

                                        <div class="search">

                                            <input
                                                class="option-search"
                                                type="text"
                                                placeholder="Search..."
                                            >

                                        </div>

                                        <ul class="options"></ul>

                                    </div>


                                    <input
                                        type="hidden"
                                        name="place_id"
                                        id="place_id"
                                        value="{{ old('place_id', $rider->place_id ?? '') }}"
                                    >

                                </div>

                            </div>

                        </span>

                    </div>

                </div>

            </div>


            {{-- =========================================================
                 RIGHT SIDE
            ========================================================== --}}
            <div class="right">

                <div class="account-details">


                    {{-- =================================================
                         APPLICATION DOCUMENTS
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="sub-heading">
                            Application Documents
                        </h4>


                        {{-- PASSPORT --}}
                        <h4 class="head">
                            Passport
                        </h4>

                        <div class="uploads">

                            <span>

                                <h4>
                                    Passport size photo
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="passport_photo"
                                >

                                    @if(!empty($personalDetails?->passport_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($personalDetails->passport_photo, '/') }}"
                                            alt="Passport Photo"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select passport photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="passport_photo"
                                        accept="image/*"
                                    >

                                </label>

                            </span>

                        </div>

                    </div>


                    {{-- =================================================
                         NATIONAL ID
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="head">
                            National Identification
                        </h4>

                        <div class="uploads">


                            {{-- ID FRONT --}}
                            <span>

                                <h4>
                                    National ID (front)
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="id_front"
                                >

                                    @if(!empty($personalDetails?->id_front_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($personalDetails->id_front_photo, '/') }}"
                                            alt="National ID Front"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select ID front photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="id_front_photo"
                                        accept="image/*"
                                        {{ empty($personalDetails?->id_front_photo) ? 'required' : '' }}
                                    >

                                </label>

                            </span>


                            {{-- ID BACK --}}
                            <span>

                                <h4>
                                    National ID (back)
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="id_back"
                                >

                                    @if(!empty($personalDetails?->id_back_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($personalDetails->id_back_photo, '/') }}"
                                            alt="National ID Back"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select ID back photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="id_back_photo"
                                        accept="image/*"
                                        {{ empty($personalDetails?->id_back_photo) ? 'required' : '' }}
                                    >

                                </label>

                            </span>

                        </div>

                    </div>


                    {{-- =================================================
                         DRIVER'S LICENCE
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="head">
                            Driver's Licence
                        </h4>

                        <div class="uploads">

                            <span>

                                <h4>
                                    Licence Photo
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="license_photo"
                                >

                                    @if(!empty($personalDetails?->license_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($personalDetails->license_photo, '/') }}"
                                            alt="Licence Photo"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select licence photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="license_photo"
                                        accept="image/*"
                                    >

                                </label>

                            </span>

                        </div>

                    </div>


                    {{-- =================================================
                         VEHICLE PHOTOS
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="head">
                            Vehicle Photos
                        </h4>

                        <div class="uploads">


                            {{-- FRONT --}}
                            <span>

                                <h4>
                                    Vehicle (front)
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="front_photo"
                                >

                                    @if(!empty($carDetails?->front_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($carDetails->front_photo, '/') }}"
                                            alt="Vehicle Front"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select vehicle front photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="front_photo"
                                        accept="image/*"
                                        {{ empty($carDetails?->front_photo) ? 'required' : '' }}
                                    >

                                </label>

                            </span>


                            {{-- SIDE --}}
                            <span>

                                <h4>
                                    Vehicle (side)
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="side_photo"
                                >

                                    @if(!empty($carDetails?->side_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($carDetails->side_photo, '/') }}"
                                            alt="Vehicle Side"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select vehicle side photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="side_photo"
                                        accept="image/*"
                                        {{ empty($carDetails?->side_photo) ? 'required' : '' }}
                                    >

                                </label>

                            </span>


                            {{-- BACK --}}
                            <span>

                                <h4>
                                    Vehicle (back)
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="back_photo"
                                >

                                    @if(!empty($carDetails?->back_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($carDetails->back_photo, '/') }}"
                                            alt="Vehicle Back"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select vehicle back photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="back_photo"
                                        accept="image/*"
                                        {{ empty($carDetails?->back_photo) ? 'required' : '' }}
                                    >

                                </label>

                            </span>

                        </div>

                    </div>


                    {{-- =================================================
                         VEHICLE LOGBOOK
                    ================================================== --}}
                    <div class="account-detail">

                        <h4 class="head">
                            Vehicle Logbook
                        </h4>

                        <div class="uploads">

                            <span>

                                <h4>
                                    Vehicle Logbook
                                </h4>

                                <label
                                    class="file-select"
                                    data-storage="logbook_photo"
                                >

                                    @if(!empty($carDetails?->logbook_photo))

                                        <img
                                            src="{{ rtrim(config('app.media_url'), '/') . '/' . ltrim($carDetails->logbook_photo, '/') }}"
                                            alt="Vehicle Logbook"
                                        >

                                    @else

                                        <i class="fa-regular fa-image"></i>

                                        <h6>
                                            Select logbook photo
                                        </h6>

                                    @endif

                                    <input
                                        type="file"
                                        name="logbook_photo"
                                        accept="image/*"
                                    >

                                </label>

                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="button submit-data">
            <button type="submit"
                    id="submit-application">
                Resubmit
            </button>
        </div>
    </form>
</div>

@endsection

