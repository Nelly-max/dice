@extends('layouts.delivery')

@section('content')

    <div class="floating-window no-sidebar fullground">
        <div class="map-view">
            <!-- <div class="map" id="map"></div> -->

            <div class="map"
                id="map"

                data-delivery-status="{{ $delivery->status }}"

                data-rider-lat="{{ $riderLocation->latitude }}"
                data-rider-lng="{{ $riderLocation->longitude }}"

                data-shop-lat="{{ $delivery->pickup_latitude }}"
                data-shop-lng="{{ $delivery->pickup_longitude }}"

                data-dropoff-lat="{{ $delivery->dropoff_latitude }}"
                data-dropoff-lng="{{ $delivery->dropoff_longitude }}"

                data-delivery-id="{{ $delivery->id }}">
            </div>

            <div class="quick-actions">
                <div class="quick-action resize">
                    <i class="fa-solid fa-down-left-and-up-right-to-center"></i>
                </div>
                <div class="quick-action message">
                    <i class="fa-regular fa-message"></i>
                </div>
                <!-- <div class="quick-action route">
                    <i class='bx bx-navigation'></i>
                    <i class="fa-solid fa-route"></i>
                </div> -->

                <div class="quick-action route">

                    <i
                        class="bx bx-navigation"
                        id="startNavigationBtn"
                        title="Start navigation"
                        aria-label="Start navigation">
                    </i>

                    <i
                        class="fa-solid fa-route"
                        id="stopNavigationBtn"
                        title="Stop navigation"
                        aria-label="Stop navigation"
                        style="display: none;">
                    </i>

                </div>
            </div>

            
            <div class="bottom-cone">
                <div class="minimize">                        
                </div>
                <div class="main-view active">
                    <div class="menu">
                        <i class="fa-solid fa-list-ul"></i>
                    </div>
                    <div class="info">
                        <span>
                            <h4>Pickup</h4>
                        </span>
                        <span>
                            <h3 id="pickupShopName">
                                {{ $delivery->status === 'picked_up' || $delivery->status === 'on_transit'
                                    ? ($delivery->customer_name ?? 'Customer')
                                    : ($delivery->pickup_name ?? 'Pickup') }}
                            </h3>
                            ||
                            <button
                                type="button"
                                class="quick-action"
                                id="pickupContactBtn"
                                data-phone="{{ $delivery->status === 'picked_up' || $delivery->status === 'on_transit'
                                    ? ($delivery->customer_phone ?? '')
                                    : ($delivery->pickup_phone ?? '') }}"
                                aria-label="{{ $delivery->status === 'picked_up' || $delivery->status === 'on_transit'
                                    ? 'Contact customer'
                                    : 'Contact pickup shop' }}"
                            >
                                <i class="fa-regular fa-circle-user"></i>
                                <h5>Contact</h5>
                            </button>
                        </span>
                        <span>
                            <h3>ETA</h3>
                            ~
                            <h5 id="deliveryEta">Calculating...</h5>
                        </span>
                    </div>
                    <div class="action-button arrive">
                        <button id="arrivedBtn" type="button" disabled>
                            <i class="fa-solid fa-angles-up"></i>
                            <h4>Arrived</h4>
                        </button>
                    </div>                    
                    <div class="action-button waiting">
                        <button id="waitingBtn" type="button" disabled>
                            <i class="fa-solid fa-angle-down"></i>
                            <h4>Waiting</h4>
                        </button>
                    </div>                    
                    <div class="action-button pickup">
                        <button id="pickupBtn" type="button" disabled>
                            <i class="fa-solid fa-angles-down"></i>
                            <h4>Pickup</h4>
                        </button>
                    </div>                    
                    <div class="action-button start">
                        <button id="startBtn" type="button" disabled>
                            <i class="fa-solid fa-arrow-up"></i>
                            <h4>Start</h4>
                        </button>
                    </div>                    
                    <div class="action-button">
                        <button id="deliverBtn" type="button" disabled>
                            <i class="fa-solid fa-angles-right"></i>
                            <h4>Deliver</h4>
                        </button>
                    </div>                    
                </div>
                <div class="menu-view">
                    <div class="close">
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <div class="info">
                        <span>
                            <h4>Route Details</h4>
                        </span>
                        <span>
                            <h3><i class="bx bx-navigation"></i> Google Maps</h3>
                            |
                            <button
                                type="button"
                                id="openGoogleMapsBtn"
                                class="quick-action"
                            >
                                <h5>Switch</h5>
                            </button>
                        </span>
                        
                        <span>
                            <h4 class="cancel"><i class="fa-solid fa-xmark"></i> Cancel Pickup</h4>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @endsection

    @include('toast.toast')

        