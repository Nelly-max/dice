let map;
let marker;
let geocoder;
let autocomplete;

window.initDeliveryMap = function () {

    if (map) {
        google.maps.event.trigger(map, "resize");
        return;
    }

    const center = {
        lat: -1.392,
        lng: 36.764
    };

    map = new google.maps.Map(document.getElementById("map"), {
        center,
        zoom: 19,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        clickableIcons: false
    });

    // marker = new google.maps.Marker({
    //     map,
    //     position: center,
    //     draggable: true
    // });

    geocoder = new google.maps.Geocoder();

    const input = document.getElementById("place-search");

    autocomplete = new google.maps.places.Autocomplete(input, {
        componentRestrictions: {
            country: "ke"
        },
        fields: [
            "geometry",
            "place_id",
            "name"
        ]
    });

    autocomplete.bindTo("bounds", map);

    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */
    autocomplete.addListener("place_changed", () => {

        const place = autocomplete.getPlace();

        // if (!place.geometry || !place.geometry.location) {
        //     alert("Please select one of the suggested locations.");
        //     return;
        // }

        map.panTo(place.geometry.location);
        map.setZoom(19);

    });

    /*
    |--------------------------------------------------------------------------
    | Click map
    |--------------------------------------------------------------------------
    */
    map.addListener("click", (event) => {
        reverseGeocode(event.latLng);
    });

    /*
    |--------------------------------------------------------------------------
    | Drag marker
    |--------------------------------------------------------------------------
    */
    map.addListener("idle", () => {
        const center = map.getCenter();
        reverseGeocode(center);
    });

    // Load the initial location
    reverseGeocode(marker.getPosition());

};

/*
|--------------------------------------------------------------------------
| Reverse Geocode
|--------------------------------------------------------------------------
*/

function reverseGeocode(location) {

    geocoder.geocode({
        location
    }, (results, status) => {

        if (status !== "OK" || !results.length) {
            console.error(status);
            return;
        }

        updateLocation(location, buildAddress(results[0]));

    });

}

/*
|--------------------------------------------------------------------------
| Build Delivery Address (Kenya Friendly)
|--------------------------------------------------------------------------
*/

function buildAddress(result) {

    const components = result.address_components;

    const get = (...types) => {

        const component = components.find(c =>
            types.some(type => c.types.includes(type))
        );

        return component ? component.long_name : "";
    };

    /*
    |--------------------------------------------------------------------------
    | County
    |--------------------------------------------------------------------------
    |
    | Nairobi
    | Kajiado
    | Kiambu
    |
    */

    const county =
        get("administrative_area_level_2") ||
        get("administrative_area_level_1");

    /*
    |--------------------------------------------------------------------------
    | Town
    |--------------------------------------------------------------------------
    |
    | Kenya is inconsistent.
    | We try the most common values first.
    |
    */

    const town =
        get("postal_town") ||
        get("locality") ||
        get("administrative_area_level_3") ||
        get("administrative_area_level_4") ||
        get("sublocality_level_1") ||
        get("sublocality");

    /*
    |--------------------------------------------------------------------------
    | Building / Landmark / Road
    |--------------------------------------------------------------------------
    */

    const street =
        get("premise") ||
        get("subpremise") ||
        get("point_of_interest") ||
        get("establishment") ||
        get("route");

    /*
    |--------------------------------------------------------------------------
    | Build address
    |--------------------------------------------------------------------------
    */

    const parts = [];

    if (county)
        parts.push(county);

    if (town && town !== county)
        parts.push(town);

    let full = parts.join(", ");

    if (street)
        full += " > " + street;

    return {

        county,

        town,

        street,

        full

    };

}

/*
|--------------------------------------------------------------------------
| Update UI
|--------------------------------------------------------------------------
*/

function updateLocation(location, address, moveMap = false) {

    // marker.setPosition(location);

    if (moveMap) {
        map.panTo(location);
        map.setZoom(15);
    }

    const lat = location.lat();
    const lng = location.lng();

    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;
    document.getElementById("delivery_address").value = address.full;

    const search = document.getElementById("place-search");

    if (search) {
        search.value = address.full;
    }

    const label = document.getElementById("selected-address");

    if (label) {
        label.innerHTML =
            `<i class="fa-solid fa-map-pin"></i> ${address.full}`;
    }

    // Keep for saving later
    window.currentDeliveryLocation = {
        county: address.county,
        town: address.town,
        estate: address.estate,
        building: address.building,
        road: address.road,
        address: address.full,
        latitude: lat,
        longitude: lng
    };

    console.log(window.currentDeliveryLocation);

}

/*
|--------------------------------------------------------------------------
| Confirm Delivery Location
|--------------------------------------------------------------------------
*/

window.confirmDeliveryLocation = function () {

    if (!window.currentDeliveryLocation) {
        alert("Please choose a delivery location.");
        return;
    }

    localStorage.setItem(
        "delivery_location",
        JSON.stringify({
            ...window.currentDeliveryLocation,
            saved_at: new Date().toISOString()
        })
    );

    closeModal();

    const display = document.getElementById("current-delivery-location");

    if (display) {
        display.textContent = window.currentDeliveryLocation.address;
    }

};

/*
|--------------------------------------------------------------------------
| Load Delivery Location on the Nav
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", () => {

    const display = document.getElementById("current-delivery-location");

    if (!display) return;

    const savedLocation = localStorage.getItem("delivery_location");

    if (!savedLocation) {
        // No local storage, keep the database value already rendered by Blade
        return;
    }

    try {

        const location = JSON.parse(savedLocation);

        if (location && location.address) {
            display.textContent = location.address;
        }

    } catch (error) {
        console.error("Invalid delivery_location in localStorage.", error);

        // Optional: remove the corrupted data
        localStorage.removeItem("delivery_location");
    }

});