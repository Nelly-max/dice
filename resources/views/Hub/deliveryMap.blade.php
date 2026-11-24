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
                {{-- minimise: leave the map; a floating map button on every page brings the rider back --}}
                <button type="button" class="quick-action resize" id="minimizeMapBtn" aria-label="Minimise the map" title="Minimise the map">
                    <i class="fa-solid fa-down-left-and-up-right-to-center" aria-hidden="true"></i>
                </button>
                {{-- buyer's note for the rider: a red dot when there is one; tap to read it --}}
                <button type="button"
                        @class(['quick-action', 'message', 'has-note' => !empty($deliveryNote)])
                        id="deliveryNoteBtn"
                        aria-label="{{ !empty($deliveryNote) ? 'Read the delivery note' : 'No delivery note' }}"
                        title="{{ !empty($deliveryNote) ? 'Delivery note' : 'No delivery note' }}"
                        aria-haspopup="dialog" aria-controls="deliveryNoteSheet">
                    <i class="fa-regular fa-message" aria-hidden="true"></i>
                    @if (!empty($deliveryNote))<span class="dn-dot" aria-hidden="true"></span>@endif
                </button>
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

    {{-- the delivery note (opened by #deliveryNoteBtn) --}}
    <dialog class="dn-sheet" id="deliveryNoteSheet" aria-labelledby="dnTitle">
        <div class="dn-head">
            <span class="dn-icon" aria-hidden="true"><i class="fa-regular fa-message"></i></span>
            <strong id="dnTitle">Delivery note</strong>
            <button type="button" class="dn-close" data-dn-close aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
        </div>
        @if (!empty($deliveryNote))
            <p class="dn-text">{{ $deliveryNote }}</p>
            <small class="dn-from">From {{ $delivery->customer_name ?? 'the customer' }}</small>
        @else
            <p class="dn-text dn-empty">The customer didn't leave a note for this delivery.</p>
        @endif
        <button type="button" class="dn-ok" data-dn-close>OK</button>
    </dialog>

    <script>
        (() => {
            // minimise: back to the page the rider came from (or the Hub); the floating
            // map button there (toast.openDeliveryMap) reopens this map
            document.getElementById('minimizeMapBtn')?.addEventListener('click', () => {
                let back = null;
                try {
                    const ref = document.referrer ? new URL(document.referrer) : null;
                    if (ref && ref.origin === location.origin && !ref.pathname.startsWith('/hub/navigate')) back = ref.href;
                } catch (e) {}
                if (!back) { try { back = sessionStorage.getItem('riderLastPage'); } catch (e) {} }
                window.location.href = back || '/hub';
            });

            const btn = document.getElementById('deliveryNoteBtn');
            const sheet = document.getElementById('deliveryNoteSheet');
            if (!btn || !sheet) return;
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                if (sheet.showModal) sheet.showModal(); else sheet.setAttribute('open', '');
                btn.classList.add('is-read');
            });
            sheet.addEventListener('click', (e) => {
                if (e.target === sheet || e.target.closest('[data-dn-close]')) sheet.close ? sheet.close() : sheet.removeAttribute('open');
            });
        })();
    </script>
    @endsection

    @include('toast.toast')

        