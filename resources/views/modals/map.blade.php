   @extends('layouts.modal')

    @section('modal')
    <main>

        <div class="modal" id="mapModal">
            <div class="modal-overlay"></div>

            <div class="large-box no-sidebar">
                <i class="fa-solid fa-xmark close" onclick="closeModal()"></i>

                <div class="pop-up-data">
                    <div class="map-container">

                        <div class="map-head">
                            <h4>Enter your delivery location</h4>
                        </div>
                        <input
                            type="text"
                            id="place-search"
                            placeholder="Search for road or house ...">

                        <!-- <div id="map-placeholder">
                            <img src="/images/map_vector.svg" alt="Map">

                            <h4>Where should we deliver?</h4>

                            <p>Search for your delivery location to begin.</p>
                        </div> -->

                        <div class="map" id="map"></div>

                        <div class="map-pin">
                            <div class="pin-label">
                                <h4>Deliver my order here</h4>
                                <h5>drag map to the exact position</h5>
                            </div>

                            <i class="fa-solid fa-location-dot"></i>
                        </div>

                        <!-- Hidden values -->
                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                        <input type="hidden" id="delivery_address" name="delivery_address">
                            
                        <div class="map-location">
                            <h3>Deliver to</h3>

                            <h4 id="selected-address">
                                <i class="fa-solid fa-map-pin"></i>
                                Kajiado, Ngong
                            </h4>
                        </div>

                        <div>
                            <button
                                type="button"
                                onclick="confirmDeliveryLocation()">
                                Confirm Location
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </main>



<!-- <script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDvSf1irTRm6LmY2FhzvszcN40JQBb7xQc&libraries=places&callback=initMap"
    async
    defer>
</script> -->
<!-- 
<script
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDuUztelzx_tx_oe8vSW4HYXvtL_DITbZo&libraries=places&callback=initMap"
    async
    defer>
</script> -->
    <!-- <script src="https://googleapis.com{{ config('services.google.maps_api_key') }}&libraries=places&callback=initMap" async defer></script> -->

    @endsection
