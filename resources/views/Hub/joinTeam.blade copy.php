@extends('layouts.hub')

@section('content')

    <div class="hub-content hub-dash no-sidebar fullground">
        <div class="left">
            <div class="account-details">
                <div class="account-detail top-area">
                    <h4>Fill your personal Details</h4>
                </div>

                <div class="account-detail">
                    <h1 class="sub-heading">Personal Details</h1>
                    <span>
                        <h4>Name</h4>
                        <inv class="required-input">
                            <input type="text" placeholder="Your Name">
                            <h6 class="required">*</h6>
                        </inv>
                    </span>
                    <span>
                        <h4>Phone</h4>
                        <inv class="required-input">
                            <input type="number" class="mobile-number" placeholder="eg. 0701234567">
                            <h6 class="required">*</h6>
                        </inv>
                    </span>
                    <span>
                        <h4>Email</h4>
                        <inv class="required-input">
                            <input type="text" placeholder="eg. myname@gmail.com">
                            <h6 class="required">*</h6>
                        </inv>
                    </span>

                    <span>
                        <h4>Gender</h4>
                        <inv class="grid-checker">
                            <div class="grid-check">
                                <input type="radio">
                                <h5>Male</h5>
                            </div>
                            <div class="grid-check">
                                <input type="radio">
                                <h5>Female</h5>
                            </div>
                        </inv>
                    </span>
                    <span>
                        <h4>Date Of Birth</h4>
                        <input type="date" class="custom-date-picker" >
                    </span>
                </div>

                <div class="account-detail">
                    <h4 class="sub-heading">Payment Information</h4>
                    <span>
                        <h4>Mpesa Number</h4>
                        <inv class="required-input">
                            <input type="text" class="mobile-number" placeholder="mpesa number">
                            <h6 class="required">*</h6>
                        </inv>
                    </span>
                </div>

            </div>
        </div>
        <div class="right">
        </div>

        <div class="button submit-data">
            <button id="submit-application">Join</button>
        </div>
    </div>

@endsection
