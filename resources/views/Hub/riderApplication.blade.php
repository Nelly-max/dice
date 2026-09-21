@extends('layouts.hub')

@section('content')

<div class="hub-content no-sidebar fullground">

    <form action="{{ route('hub.account.rider-application.apply') }}"
          method="POST"
          enctype="multipart/form-data">

        @csrf

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <div class="hub-dash">
            <div class="left">
    
                <div class="account-details">
    
                    <div class="account-detail top-area">
                        <h4>Fill your personal Details</h4>
                    </div>
    
    
                    <!-- PERSONAL DETAILS -->
                    <div class="account-detail">
    
                        <h1 class="sub-heading">Personal Details</h1>
    
    
                        <span>
                            <h4>Name</h4>
    
                            <div class="required-input">
                                <input type="text"
                                       name="name"
                                       value="{{ old('name') }}"
                                       placeholder="Your Name"
                                       required>
    
                                <h6 class="required">*</h6>
                            </div>
                        </span>
    
    
                        <span>
                            <h4>Phone</h4>
    
                            <div class="required-input">
                                <input type="text"
                                       name="phone"
                                       value="{{ old('phone') }}"
                                       class="mobile-number"
                                       placeholder="eg. 0701234567"
                                       required>
    
                                <h6 class="required">*</h6>
                            </div>
                        </span>
    
    
                        <span>
                            <h4>Email</h4>
    
                            <div class="required-input">
                                <input type="email"
                                       name="email"
                                       value="{{ old('email') }}"
                                       placeholder="eg. myname@gmail.com"
                                       required>
    
                                <h6 class="required">*</h6>
                            </div>
                        </span>
    
    
                        <span>
                            <h4>ID Number</h4>
    
                            <div class="required-input">
                                <input type="text"
                                       name="national_id"
                                       value="{{ old('national_id') }}"
                                       placeholder="National ID No."
                                       required>
    
                                <h6 class="required">*</h6>
                            </div>
                        </span>
    
    
                        <span>
                            <h4>Licence No.</h4>
    
                            <div class="required-input">
                                <input type="text"
                                    name="license_number"
                                    value="{{ old('license_number') }}"
                                    placeholder="your driver's licence no.">
                            </div>
    
                        </span>
    
    
                        <span>
                            <h4>Date Of Birth</h4>
    
                            <input type="date"
                                   name="date_of_birth"
                                   value="{{ old('date_of_birth') }}"
                                   class="custom-date-picker"
                                   min="{{ now()->subYears(80)->format('Y-m-d') }}"
                                   max="{{ now()->subYears(18)->format('Y-m-d') }}">
    
                        </span>
    
    
                    </div>
    
    
    
                    <!-- PAYMENT INFORMATION -->
                    <div class="account-detail">
    
                        <h4 class="sub-heading">
                            Payment Information
                        </h4>
    
    
                        <span>
                            <h4>Mpesa Number</h4>
    
                            <div class="required-input">
    
                                <input type="text"
                                       name="mpesa_number"
                                       value="{{ old('mpesa_number') }}"
                                       class="mobile-number"
                                       placeholder="Mpesa Number">
    
                                <h6 class="required">*</h6>
    
                            </div>
    
                        </span>
    
    
                    </div>
    
    
    
    
                    <!-- VEHICLE DETAILS -->
                    <div class="account-detail">
    
                        <h4 class="sub-heading">
                            Vehicle Details
                        </h4>
    
    
    
                        <span>
    
                            <h4>Plate Number</h4>
    
                            <div class="required-input">
    
                                <input type="text"
                                    name="plate_number"
                                    value="{{ old('plate_number') }}"
                                    placeholder="Vehicle Plate Number"
                                    oninput="this.value = this.value.toUpperCase()"
                                    required>

                                <h6 class="required">*</h6>
    
                            </div>
    
                        </span>    
    
                        <span>
    
                            <h4>Vehicle Make</h4>
    
                            <div class="required-input">
    
                                <input type="text"
                                    name="vehicle_make"
                                    value="{{ old('vehicle_make') }}"
                                    placeholder="eg. Honda">
    
                            </div>
    
                        </span>
    
    
    
    
                        <span>
    
                            <h4>Vehicle Model</h4>
    
                            <div class="required-input">
    
                                <input type="text"
                                    name="vehicle_model"
                                    value="{{ old('vehicle_model') }}"
                                    placeholder="eg. Boxer 150">    
                            </div>
    
                        </span>
    
    
    
    
                        <span>
    
                            <h4>Vehicle Type</h4>
    
    
                            <select name="vehicle_type" required>
    
                                <option value="">
                                    Select Vehicle Type
                                </option>
    
    
                                <option value="motorcycle"
                                    {{ old('vehicle_type') == 'motorcycle' ? 'selected' : '' }}>
                                    Motorcycle
                                </option>
    
    
                                <!-- <option value="car"
                                    {{ old('vehicle_type') == 'car' ? 'selected' : '' }}>
                                    Car
                                </option>     -->
    
                            </select>  
    
                        </span>
    
    
                    </div>
    
    
                    <!-- LOCALITY DETAILS -->
                    <div class="account-detail">
    
                        <h4 class="sub-heading">
                            Your locality
                        </h4>
    
    
    
                        <span>
    
                            <h4>County</h4>
    
                            <div class="required-input">
    
                                {{-- County --}}
                                <div class="custom-select"
                                    id="countySelect"
                                    data-options='@json($counties)'
                                    data-selected="{{ $business->county_id ?? '' }}">

                                    <div class="select-btn">
                                        <span>County</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>

                                    <div class="select-options">
                                        <div class="search">
                                            <input class="option-search" type="text" placeholder="Search...">
                                        </div>
                                        <ul class="options"></ul>
                                    </div>
                                    <input type="hidden" name="county_id" id="county_id" value="{{ old('county_id') }}" required>
                                </div>
                                @error('county_id') <small class="error">{{ $message }}</small> @enderror
                                <h6 class="required">*</h6>
    
                            </div>
    
                        </span>

                        
                        
    
    
    
                        <span>
    
                            <h4>Town</h4>
    
                            <div class="required-input">
                                {{-- Town --}}
                                <div class="custom-select" 
                                    id="townSelect" 
                                    data-options='[]'
                                    data-selected="{{ $business->town_id ?? '' }}">

                                    <div class="select-btn">
                                        <span>Town</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>

                                    <div class="select-options">
                                        <div class="search">
                                            <input class="option-search" type="text" placeholder="Search...">
                                        </div>
                                        <ul class="options"></ul>
                                    </div>

                                    <input type="hidden" name="town_id" id="town_id" value="{{ old('town_id') }}" required>
                                </div>
                                @error('town_id') <small class="error">{{ $message }}</small> @enderror 
                                <h6 class="required">*</h6>
    
                            </div>
    
                        </span>
    
    
    
    
                        <span>
    
                            <h4>Place</h4>

                            <div class="required-input">
                                {{-- Place --}}
                                <div class="custom-select"
                                    id="placeSelect"
                                    data-options='[]'
                                    data-selected="{{ $business->place_id ?? '' }}">
    
                                    <div class="select-btn">
                                        <span>Place</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </div>
    
                                    <div class="select-options">
                                        <div class="search">
                                            <input class="option-search" type="text" placeholder="Search...">
                                        </div>
                                        <ul class="options"></ul>
                                    </div>
    
                                    <input type="hidden" name="place_id" id="place_id" value="{{ old('place_id') }}">
                                </div>
                                @error('town_id') <small class="error">{{ $message }}</small> @enderror 
                                <h6 class="required">*</h6>
                            </div>
    
    
                        </span>
    
    
                    </div>
    
    
                </div>
    
            </div>
    
    
            <div class="right">
    
                <div class="account-details">
    
                    <!-- Upload sections continue in Part 2 -->
                                     <!-- PASSPORT PHOTO -->
                    <div class="account-detail">
    
                        <h4 class="sub-heading">
                            Upload Documents
                        </h4>
    
                        <h4 class="head">
                            Passport
                        </h4>
    
    
                        <div class="uploads">
    
                            <span>
    
                                <h4>
                                    Passport size photo
                                </h4>
    
    
                                <label class="file-select"
                                       data-storage="passport_photo">
    
                                    <i class="fa-regular fa-image"></i>
    
                                    <h6>
                                        passport
                                    </h6>
    
    
                                    <input type="file"
                                           name="passport_photo"
                                           accept="image/*">
    
                                </label>
    
    
                            </span>
    
    
                        </div>
    
    
                    </div>
    
    
    
    
                    <!-- NATIONAL ID -->
                    <div class="account-detail">
    
    
                        <h4 class="head">
                            National Identification
                        </h4>
    
    
                        <div class="uploads">
    
    
                            <span>
    
    
                                <div class="required-input">
    
                                    <h4>
                                        National ID (front)
                                    </h4>
    
                                    <h6 class="required">
                                        *
                                    </h6>
    
                                </div>
    
    
                                <label class="file-select"
                                       data-storage="id_front">
    
    
                                    <i class="fa-regular fa-image"></i>
    
    
                                    <h6>
                                        National ID (front)
                                    </h6>
    
    
                                    <input type="file"
                                           name="id_front_photo"
                                           accept="image/*"
                                           required>
    
    
                                </label>
    
    
                            </span>
    
    
    
    
    
                            <span>
    
    
                                <div class="required-input">
    
                                    <h4>
                                        National ID (back)
                                    </h4>
    
                                    <h6 class="required">
                                        *
                                    </h6>
    
                                </div>
    
    
                                <label class="file-select"
                                       data-storage="id_back">
    
    
                                    <i class="fa-regular fa-image"></i>
    
    
                                    <h6>
                                        National ID (back)
                                    </h6>
    
    
                                    <input type="file"
                                           name="id_back_photo"
                                           accept="image/*"
                                           required>
    
    
                                </label>
    
    
                            </span>
    
    
                        </div>
    
    
                    </div>
    
    
    
    
    
                    <!-- LICENCE PHOTO -->
                    <div class="account-detail">
    
    
                        <h4 class="head">
                            Driver's Licence
                        </h4>
    
    
                        <div class="uploads">
    
    
                            <span>
    
                                <h4>
                                    Licence Photo
                                </h4>
    
    
                                <label class="file-select"
                                       data-storage="license_photo">
    
    
                                    <i class="fa-regular fa-image"></i>
    
    
                                    <h6>
                                        Licence
                                    </h6>
    
    
                                    <input type="file"
                                           name="license_photo"
                                           accept="image/*">
    
    
                                </label>
    
    
                            </span>
    
    
                        </div>
    
    
                    </div>
    
    
    
    
    
    
                    <!-- VEHICLE PHOTOS -->
                    <div class="account-detail">
    
    
                        <h4 class="head">
                            Vehicle Photos
                        </h4>
    
    
                        <div class="uploads">
    
    
                            <span>
    
    
                                <div class="required-input">
    
                                    <h4>
                                        Vehicle (front)
                                    </h4>
    
                                    <h6 class="required">
                                        *
                                    </h6>
    
                                </div>
    
    
                                <label class="file-select"
                                       data-storage="front_photo">
    
    
                                    <i class="fa-regular fa-image"></i>
    
    
                                    <h6>
                                        Vehicle Front
                                    </h6>
    
    
                                    <input type="file"
                                           name="front_photo"
                                           accept="image/*"
                                           required>
    
    
                                </label>
    
    
                            </span>
    
    
    
    
    
                            <span>
    
    
                                <div class="required-input">
    
                                    <h4>
                                        Vehicle (side)
                                    </h4>
    
                                    <h6 class="required">
                                        *
                                    </h6>
    
                                </div>
    
    
                                <label class="file-select"
                                       data-storage="side_photo">
    
    
                                    <i class="fa-regular fa-image"></i>
    
    
                                    <h6>
                                        Vehicle Side
                                    </h6>
    
    
                                    <input type="file"
                                           name="side_photo"
                                           accept="image/*"
                                           required>
    
    
                                </label>
    
    
                            </span>
    
    
    
    
    
                            <span>
    
    
                                <div class="required-input">
    
                                    <h4>
                                        Vehicle (back)
                                    </h4>
    
                                    <h6 class="required">
                                        *
                                    </h6>
    
                                </div>
    
    
                                <label class="file-select"
                                       data-storage="back_photo">
    
    
                                    <i class="fa-regular fa-image"></i>
    
    
                                    <h6>
                                        Vehicle Back
                                    </h6>
    
    
                                    <input type="file"
                                           name="back_photo"
                                           accept="image/*"
                                           required>
    
    
                                </label>
    
    
                            </span>
    
    
                        </div>
    
    
                    </div>
    
    
    
    
    
    
    
                    <!-- LOGBOOK -->
                    <div class="account-detail">
    
    
                        <h4 class="head">
                            Vehicle Logbook
                        </h4>
    
    
                        <div class="uploads">
    
    
                            <span>
    
    
                                <h4>
                                    Vehicle Logbook
                                </h4>
    
    
                                <label class="file-select"
                                       data-storage="logbook_photo">
    
    
                                    <i class="fa-regularly fa-image"></i>
    
    
                                    <h6>
                                        Logbook
                                    </h6>
    
    
                                    <input type="file"
                                           name="logbook_photo"
                                           accept="image/*">
    
    
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
                Apply
            </button>
        </div>

    </form>
</div>


@endsection