{{--
    Delivery location picker. Opened with showModal('mapModal'); behaviour lives in resources/js/map.js
    One simple screen: search (suggestions drop down over the map), use current location,
    the map with a centred pin to fine-tune, Confirm. The picked address is the sheet's title.
--}}
<div class="modal sheet loc-sheet" id="mapModal">
    <div class="modal-overlay"></div>

    <div class="sheet-panel" role="dialog" aria-modal="true" aria-labelledby="locTitle">
        <section class="loc-step">
            <header class="loc-head">
                <h2 id="locTitle" class="loc-title">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    <span id="selected-address">Move the map or search for your place</span>
                </h2>
                <button type="button" class="loc-x" onclick="closeModal()" aria-label="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
            </header>

            <div class="loc-searchbox">
                <label class="loc-search">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" id="place-search" placeholder="Search for your street, estate or building" autocomplete="off" enterkeyhint="search" aria-label="Search for a location">
                </label>

                <div class="loc-drop" id="loc-drop" hidden>
                    <ul class="loc-results" id="loc-results" role="listbox" aria-label="Suggested places"></ul>
                    <p class="loc-empty" id="loc-empty" hidden>
                        <strong>No places found</strong>
                        <span>Try a nearby road, estate or landmark.</span>
                    </p>
                </div>
            </div>

            <button type="button" class="loc-here" onclick="useCurrentLocation()">
                <i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i>
                <span>Use current location</span>
            </button>

            <div class="loc-map">
                <div id="map"></div>
                <span class="loc-pin" aria-hidden="true">
                    <small>Move the map to adjust</small>
                    <i class="fa-solid fa-location-dot"></i>
                </span>
            </div>

            <button type="button" class="loc-confirm" onclick="confirmDeliveryLocation()">Confirm location</button>

            <p class="loc-note">We show shops within {{ config('homemarket.max_radius_m', 3000) / 1000 }} km of you.</p>
        </section>

        <input type="hidden" id="latitude" name="latitude">
        <input type="hidden" id="longitude" name="longitude">
        <input type="hidden" id="delivery_address" name="delivery_address">
    </div>
</div>
