```blade
@extends('layouts.hub')

@section('content')

<div class="hub-content hub-dash no-sidebar fullground">

    <form
        action="#"
        method="POST"
        id="riderUpdateForm"
    >
        @csrf
        @method('PUT')

        <div class="left">

            <div class="account-details">

                {{-- HEADER --}}
                <div class="account-detail top-area">

                    <h4>Update your Details</h4>

                </div>


                {{-- PERSONAL DETAILS --}}
                <div class="account-detail">

                    <h1 class="sub-heading">Personal Details</h1>


                    {{-- NAME --}}
                    <span>

                        <h4>Name</h4>

                        <div class="required-input">

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $rider->name ?? '') }}"
                                placeholder="Your Name"
                                maxlength="50"
                                pattern="[A-Za-z0-9 ]{1,50}"
                                title="Name can only contain letters, numbers and spaces."
                                oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '').slice(0, 50);"
                                required
                            >

                            <h6 class="required">*</h6>

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

                            <h6 class="required">*</h6>

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
                                maxlength="255"
                                required
                            >

                            <h6 class="required">*</h6>

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
                                maxlength="20"
                                required
                            >

                            <h6 class="required">*</h6>

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
                                placeholder="Your driver's licence no."
                                maxlength="50"
                            >

                        </div>

                    </span>


                    {{-- DATE OF BIRTH --}}
                    <span>

                        <h4>Date Of Birth</h4>

                        <input
                            type="date"
                            name="date_of_birth"
                            class="custom-date-picker"
                            value="{{ old('date_of_birth', $rider->date_of_birth?->format('Y-m-d')) }}"
                            min="{{ now()->subYears(80)->format('Y-m-d') }}"
                            max="{{ now()->subYears(18)->format('Y-m-d') }}"
                            required
                        >

                    </span>

                </div>


                {{-- PAYMENT INFORMATION --}}
                <div class="account-detail">

                    <h4 class="sub-heading">Payment Information</h4>


                    {{-- MPESA --}}
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

                            <h6 class="required">*</h6>

                        </div>

                    </span>

                </div>


                {{-- SUBMIT --}}
                <div class="button submit-data">

                    <button
                        type="submit"
                        id="submit-application"
                    >
                        Update Details
                    </button>

                </div>

            </div>

        </div>

        <div class="right">
        </div>

    </form>

</div>

@endsection
```
