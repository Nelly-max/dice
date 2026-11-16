@extends('layouts.hub')

@section('content')
{{--
    Add a lead (Hub\LeadController@create / store): a marketer suggests a business that could
    sell on Smart Market. County / town / area are the searchable pickers (partials/Hub/picker, cascade in resources/js/select.js);
    "I'm at the business" fills the map pin (resources/js/hub.js, [data-lead-gps]).
--}}
@php
    $picker = fn ($id, $name, $label, $options, $value) => compact('id', 'name', 'label', 'options', 'value');
    $pickers = [
        'market' => $picker('marketSelect', 'sub_division_id', 'Not sure yet', $markets, old('sub_division_id')),
        'county' => $picker('countySelect', 'county_id', 'County', $counties, old('county_id')),
        'town' => $picker('townSelect', 'town_id', 'Town', $towns, old('town_id')),
        'place' => $picker('placeSelect', 'place_id', 'Area', $places, old('place_id')),
    ];
@endphp

<div class="hub-content hub-form no-sidebar fullground">
    <form action="{{ route('hub.leads.store') }}" method="POST" data-notify-loading="Saving the lead…">
        @csrf

        @include('partials.form-errors')

        <div class="hub-dash">
            <div class="left">
                <div class="account-details">

                    @include('partials.Hub.page-hero', [
                        'back' => route('hub.leads'),
                        'tone' => 'marketer',
                        'icon' => 'fa-solid fa-store',
                        'title' => 'Add a lead',
                        'text' => 'A business you think should sell on Smart Market. Its name, a phone number and where it is are enough: our team will call it.',
                        'steps' => ['The business', 'Where it is', 'Notes'],
                    ])

                    {{-- THE BUSINESS --}}
                    <div class="account-detail">
                        <h1 class="sub-heading">The business</h1>

                        <span>
                            <h4>Business name</h4>
                            <div class="required-input">
                                <input type="text" name="name" value="{{ old('name') }}" maxlength="255" placeholder="e.g. Baraka Supermarket" required>
                                <h6 class="required">*</h6>
                            </div>
                        </span>

                        <span>
                            <h4>Phone</h4>
                            <div class="required-input">
                                <input type="tel" name="phone" value="{{ old('phone') }}" inputmode="tel" class="mobile-number" placeholder="eg. 0712345678" required>
                                <h6 class="required">*</h6>
                            </div>
                        </span>

                        <span>
                            <h4>Second phone <small class="lead-opt">optional</small></h4>
                            <input type="tel" name="phone_2" value="{{ old('phone_2') }}" inputmode="tel" placeholder="eg. 0733000000">
                        </span>

                        <span>
                            <h4>Contact person <small class="lead-opt">optional</small></h4>
                            <input type="text" name="contact_name" value="{{ old('contact_name') }}" maxlength="255" placeholder="Who to ask for, e.g. Jane">
                        </span>

                        <span>
                            <h4>Email <small class="lead-opt">optional</small></h4>
                            <input type="email" name="email" value="{{ old('email') }}" maxlength="255" placeholder="e.g. hello@baraka.co.ke">
                        </span>

                        <span>
                            <h4>Type of business <small class="lead-opt">optional</small></h4>
                            <input type="text" name="business_type" value="{{ old('business_type') }}" maxlength="100" placeholder="e.g. Mini-mart, salon, gas shop">
                        </span>

                        <span>
                            <h4>Market <small class="lead-opt">optional</small></h4>
                            @include('Hub.partials.leadPicker', $pickers['market'])
                        </span>
                    </div>

                    {{-- WHERE IT IS --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Where it is</h4>

                        <span>
                            <h4>County</h4>
                            <div class="required-input">
                                @include('Hub.partials.leadPicker', $pickers['county'])
                                <h6 class="required">*</h6>
                            </div>
                        </span>

                        <span>
                            <h4>Town</h4>
                            @include('Hub.partials.leadPicker', $pickers['town'])
                        </span>

                        <span>
                            <h4>Area <small class="lead-opt">optional</small></h4>
                            @include('Hub.partials.leadPicker', $pickers['place'])
                        </span>

                        <span>
                            <h4>Street, building or landmark <small class="lead-opt">optional</small></h4>
                            <div class="lead-addr">
                                <input type="text" name="address" value="{{ old('address') }}" maxlength="255" placeholder="e.g. Next to Total petrol station, Thika Road">
                                <button type="button" class="lead-gps {{ old('latitude') ? 'is-set' : '' }}" data-lead-gps>
                                    <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                                    <b>{{ old('latitude') ? 'Pin saved' : "I'm here: save pin" }}</b>
                                </button>
                            </div>
                            <input type="hidden" name="latitude" value="{{ old('latitude') }}" data-lead-lat>
                            <input type="hidden" name="longitude" value="{{ old('longitude') }}" data-lead-lng>
                        </span>
                    </div>

                    {{-- NOTES --}}
                    <div class="account-detail">
                        <h4 class="sub-heading">Notes</h4>
                        <span class="lead-notes">
                            <h4>Anything that helps <small class="lead-opt">optional</small></h4>
                            <textarea name="notes" rows="4" maxlength="2000" placeholder="What they sell, best time to call, who you spoke to…">{{ old('notes') }}</textarea>
                        </span>
                    </div>

                </div>
            </div>
        </div>

        <div class="button submit-data">
            <button type="submit">Add lead</button>
        </div>
    </form>
</div>
@endsection
