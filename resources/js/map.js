let map;
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

    map = new google.maps.Map(
        document.getElementById("map"),
        {
            center,
            zoom: 19,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false,
            clickableIcons: false
        }
    );

    geocoder = new google.maps.Geocoder();

    const input = document.getElementById("place-search");

    if (input) {

        autocomplete = new google.maps.places.Autocomplete(
            input,
            {
                componentRestrictions: {
                    country: "ke"
                },
                fields: [
                    "geometry",
                    "place_id",
                    "name"
                ]
            }
        );

        autocomplete.bindTo("bounds", map);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        autocomplete.addListener("place_changed", () => {

            const place = autocomplete.getPlace();

            if (
                !place.geometry ||
                !place.geometry.location
            ) {

                console.warn(
                    "Selected place has no geometry."
                );

                return;
            }

            map.panTo(place.geometry.location);
            map.setZoom(19);

            reverseGeocode(
                place.geometry.location
            );
        });
    }


    /*
    |--------------------------------------------------------------------------
    | Click Map
    |--------------------------------------------------------------------------
    */

    map.addListener("click", (event) => {

        reverseGeocode(
            event.latLng
        );

    });


    /*
    |--------------------------------------------------------------------------
    | Map Center Changed
    |--------------------------------------------------------------------------
    |
    | This keeps the selected delivery location synchronized with
    | the center of the map.
    |
    */

    map.addListener("idle", () => {

        const mapCenter = map.getCenter();

        if (!mapCenter) {
            return;
        }

        reverseGeocode(mapCenter);

    });


    /*
    |--------------------------------------------------------------------------
    | Initial Location
    |--------------------------------------------------------------------------
    |
    | Do NOT use marker.getPosition().
    | The marker is no longer being created.
    |
    */

    const initialCenter = map.getCenter();

    if (initialCenter) {
        reverseGeocode(initialCenter);
    }

};


/*
|--------------------------------------------------------------------------
| Reverse Geocode
|--------------------------------------------------------------------------
*/

function reverseGeocode(location) {

    if (!geocoder || !location) {
        return;
    }

    geocoder.geocode(
        {
            location: location
        },
        (results, status) => {

            if (
                status !== "OK" ||
                !results ||
                !results.length
            ) {

                console.error(
                    "Reverse geocoding failed:",
                    status
                );

                return;
            }

            updateLocation(
                location,
                buildAddress(results[0])
            );

        }
    );

}


/*
|--------------------------------------------------------------------------
| Build Delivery Address
|--------------------------------------------------------------------------
*/

function buildAddress(result) {

    const components =
        result.address_components || [];

    const get = (...types) => {

        const component = components.find(
            c => types.some(
                type => c.types.includes(type)
            )
        );

        return component
            ? component.long_name
            : "";
    };


    /*
    |--------------------------------------------------------------------------
    | County
    |--------------------------------------------------------------------------
    */

    const county =
        get("administrative_area_level_2") ||
        get("administrative_area_level_1");


    /*
    |--------------------------------------------------------------------------
    | Town
    |--------------------------------------------------------------------------
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

    const building =
        get("premise");

    const subpremise =
        get("subpremise");

    const landmark =
        get("point_of_interest") ||
        get("establishment");

    const road =
        get("route");


    /*
    |--------------------------------------------------------------------------
    | Build Full Address
    |--------------------------------------------------------------------------
    */

    const parts = [];

    if (county) {
        parts.push(county);
    }

    if (
        town &&
        town !== county
    ) {
        parts.push(town);
    }

    if (building) {
        parts.push(building);
    } else if (subpremise) {
        parts.push(subpremise);
    } else if (landmark) {
        parts.push(landmark);
    } else if (road) {
        parts.push(road);
    }

    const full =
        parts.join(", ");


    return {

        county: county,

        town: town,

        estate: "",

        building: building || subpremise || "",

        road: road || "",

        landmark: landmark || "",

        street: road || "",

        full: full

    };

}


/*
|--------------------------------------------------------------------------
| Update Location
|--------------------------------------------------------------------------
*/

function updateLocation(
    location,
    address,
    moveMap = false
) {

    if (!location) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Move Map
    |--------------------------------------------------------------------------
    */

    if (moveMap && map) {

        map.panTo(location);
        map.setZoom(15);

    }


    /*
    |--------------------------------------------------------------------------
    | Coordinates
    |--------------------------------------------------------------------------
    */

    const lat =
        typeof location.lat === "function"
            ? location.lat()
            : Number(location.lat);

    const lng =
        typeof location.lng === "function"
            ? location.lng()
            : Number(location.lng);


    if (
        !Number.isFinite(lat) ||
        !Number.isFinite(lng)
    ) {

        console.error(
            "Invalid delivery coordinates:",
            location
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Hidden Inputs
    |--------------------------------------------------------------------------
    */

    const latitudeInput =
        document.getElementById("latitude");

    const longitudeInput =
        document.getElementById("longitude");

    const deliveryAddressInput =
        document.getElementById("delivery_address");


    if (latitudeInput) {
        latitudeInput.value = lat;
    }

    if (longitudeInput) {
        longitudeInput.value = lng;
    }

    if (deliveryAddressInput) {
        deliveryAddressInput.value =
            address.full;
    }


    /*
    |--------------------------------------------------------------------------
    | Search Field
    |--------------------------------------------------------------------------
    */

    const search =
        document.getElementById("place-search");

    if (search) {
        search.value = address.full;
    }


    /*
    |--------------------------------------------------------------------------
    | Selected Address Label
    |--------------------------------------------------------------------------
    */

    const label =
        document.getElementById("selected-address");

    if (label) {

        label.innerHTML =
            `<i class="fa-solid fa-map-pin"></i> ${address.full}`;

    }


    /*
    |--------------------------------------------------------------------------
    | Keep Current Delivery Location
    |--------------------------------------------------------------------------
    */

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


    console.log(
        "Current delivery location:",
        window.currentDeliveryLocation
    );

}


/*
|--------------------------------------------------------------------------
| Confirm Delivery Location
|--------------------------------------------------------------------------
*/

window.confirmDeliveryLocation = function () {

    if (
        !window.currentDeliveryLocation
    ) {

        alert(
            "Please choose a delivery location."
        );

        return;
    }


    const latitude =
        Number(
            window.currentDeliveryLocation.latitude
        );

    const longitude =
        Number(
            window.currentDeliveryLocation.longitude
        );


    /*
    |--------------------------------------------------------------------------
    | Validate Coordinates
    |--------------------------------------------------------------------------
    */

    if (
        !Number.isFinite(latitude) ||
        !Number.isFinite(longitude)
    ) {

        alert(
            "The selected delivery location does not have valid coordinates."
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Save Delivery Location
    |--------------------------------------------------------------------------
    */

    const locationToSave = {

        ...window.currentDeliveryLocation,

        latitude: latitude,

        longitude: longitude,

        saved_at:
            new Date().toISOString()

    };


    localStorage.setItem(
        "delivery_location",
        JSON.stringify(locationToSave)
    );


    console.log(
        "Delivery location saved:",
        locationToSave
    );


    /*
    |--------------------------------------------------------------------------
    | Update Navigation Display
    |--------------------------------------------------------------------------
    */

    const display =
        document.getElementById(
            "current-delivery-location"
        );

    if (display) {

        display.textContent =
            locationToSave.address;

    }


    /*
    |--------------------------------------------------------------------------
    | Close Modal
    |--------------------------------------------------------------------------
    */

    closeModal();


    /*
    |--------------------------------------------------------------------------
    | IMPORTANT:
    |
    | Send the coordinates to Laravel.
    |
    | Laravel cannot read localStorage directly.
    |
    |--------------------------------------------------------------------------
    */

    const url =
        new URL(
            window.location.href
        );


    url.searchParams.set(
        "latitude",
        latitude
    );

    url.searchParams.set(
        "longitude",
        longitude
    );


    /*
    |--------------------------------------------------------------------------
    | If this is the HomeMarket page,
    | reload it using the new delivery location.
    |
    | If the modal is used elsewhere, the same URL
    | still carries the location coordinates.
    |--------------------------------------------------------------------------
    */

    window.location.href =
        url.toString();

};


/*
|--------------------------------------------------------------------------
| Load Saved Delivery Location In Navigation
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    () => {

        const display =
            document.getElementById(
                "current-delivery-location"
            );

        if (!display) {
            return;
        }


        const savedLocation =
            localStorage.getItem(
                "delivery_location"
            );


        if (!savedLocation) {

            /*
            |--------------------------------------------------------------------------
            | No localStorage:
            | keep the database/Blade value.
            |--------------------------------------------------------------------------
            */

            return;
        }


        try {

            const location =
                JSON.parse(
                    savedLocation
                );


            if (
                location &&
                location.address
            ) {

                display.textContent =
                    location.address;

            }

        } catch (error) {

            console.error(
                "Invalid delivery_location in localStorage.",
                error
            );

            localStorage.removeItem(
                "delivery_location"
            );

        }

    }
);
