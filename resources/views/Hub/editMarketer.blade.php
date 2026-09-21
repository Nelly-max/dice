@extends('layouts.hub')

@section('content')

<div class="hub-content hub-dash no-sidebar fullground">

    <div class="left">

        <div class="account-details">

            <div class="account-detail top-area">
                <h4>Update your Details</h4>
            </div>

            <div class="account-detail">

                <h1 class="sub-heading">Personal Details</h1>

                <span>
                    <h4>Name</h4>

                    <inv class="required-input">
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $marketer->name) }}"
                            placeholder="Your Name"
                            maxlength="50"
                            pattern="[A-Za-z0-9 ]{1,50}"
                            title="Name can only contain letters, numbers and spaces."
                            oninput="this.value = this.value.replace(/[^A-Za-z0-9 ]/g, '').slice(0, 50);"
                            required
                        >

                        <h6 class="required">*</h6>
                    </inv>
                </span>


                <span>
                    <h4>Phone</h4>

                    <inv class="required-input">
                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone', $marketer->phone) }}"
                            class="mobile-number"
                            placeholder="eg. 0701234567"
                            required
                        >

                        <h6 class="required">*</h6>
                    </inv>
                </span>


                <span>
                    <h4>Email</h4>

                    <inv class="required-input">
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $marketer->email) }}"
                            placeholder="eg. myname@gmail.com"
                            maxlength="255"
                            required
                        >

                        <h6 class="required">*</h6>
                    </inv>
                </span>


                <span>
                    <h4>Gender</h4>

                    <inv class="grid-checker">

                        <div class="grid-check">
                            <input
                                type="radio"
                                name="gender"
                                value="Male"
                                {{ old('gender', $marketer->gender) === 'Male' ? 'checked' : '' }}
                            >

                            <h5>Male</h5>
                        </div>

                        <div class="grid-check">
                            <input
                                type="radio"
                                name="gender"
                                value="Female"
                                {{ old('gender', $marketer->gender) === 'Female' ? 'checked' : '' }}
                            >

                            <h5>Female</h5>
                        </div>

                    </inv>
                </span>


                <span>
                    <h4>Date Of Birth</h4>

                    <input
                        type="date"
                        name="date_of_birth"
                        class="custom-date-picker"
                        value="{{ old('date_of_birth', $marketer->date_of_birth?->format('Y-m-d')) }}"
                        max="{{ now()->subYears(15)->format('Y-m-d') }}"
                    >

                </span>

            </div>


            <div class="account-detail">

                <h4 class="sub-heading">Payment Information</h4>

                <span>
                    <h4>Mpesa Number</h4>

                    <inv class="required-input">
                        <input
                            type="text"
                            name="mpesa_number"
                            value="{{ old('mpesa_number', $marketer->mpesa_number) }}"
                            class="mobile-number"
                            placeholder="mpesa number"
                            required
                        >

                        <h6 class="required">*</h6>
                    </inv>
                </span>

            </div>

        </div>

    </div>

    <div class="right">
    </div>

    <div class="button submit-data">
        <button type="submit" id="submit-application">
            Update Details
        </button>
    </div>

</div>


@endsection
