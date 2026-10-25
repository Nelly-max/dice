/*
 |--------------------------------------------------------------------------
 | Delivery location picker (resources/views/modals/map.blade.php)
 |--------------------------------------------------------------------------
 | One screen: search (Google Places suggestions drop down over the map),
 | "Use current location", the map with a centred pin, Confirm.
 | The map opens on the last saved location, or Nairobi area by default.
 */

let map;
let geocoder;
let placesService;      // AutocompleteService
let sessionToken;
let searchTimer;
let lastQuery = '';

const DEFAULT_CENTER = { lat: -1.392, lng: 36.764 };

const $ = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const mapsReady = () => !!(window.google && google.maps && google.maps.places);

function savedCenter() {
    try {
        const saved = JSON.parse(localStorage.getItem('delivery_location') || 'null');
        const lat = Number(saved?.latitude), lng = Number(saved?.longitude);
        if (Number.isFinite(lat) && Number.isFinite(lng)) return { lat, lng };
    } catch (e) { /* ignore */ }
    return DEFAULT_CENTER;
}

/* called by modal.js every time the sheet opens */
window.initDeliveryMap = function () {
    const input = $('place-search');
    if (!input) return;

    if (!input.dataset.bound) {
        input.dataset.bound = '1';
        input.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => suggest(input.value.trim()), 220);
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('loc-results')?.querySelector('.loc-result')?.click();
            }
            if (e.key === 'Escape' && !$('loc-drop')?.hidden) {
                e.stopPropagation();
                closeDrop();
            }
        });
        $('loc-results')?.addEventListener('click', (e) => {
            const row = e.target.closest('.loc-result');
            if (row) pickPrediction(row.dataset.placeId, row.dataset.label);
        });
        // tapping elsewhere in the sheet closes the suggestions
        $('mapModal')?.addEventListener('pointerdown', (e) => {
            if (!e.target.closest('.loc-searchbox')) closeDrop();
        });
    }

    if (!mapsReady()) return;
    if (!map) {
        ensureMap(savedCenter());
    } else {
        google.maps.event.trigger(map, 'resize');
    }
};

function closeDrop() {
    const drop = $('loc-drop');
    if (drop) drop.hidden = true;
}

function renderResults(predictions, query) {
    const list = $('loc-results');
    const empty = $('loc-empty');
    const drop = $('loc-drop');
    if (!list) return;

    list.innerHTML = predictions.map((p) => {
        const main = p.structured_formatting?.main_text || p.description;
        const rest = p.structured_formatting?.secondary_text || '';
        return `<li><button type="button" class="loc-result" role="option" data-place-id="${esc(p.place_id)}" data-label="${esc(p.description)}">` +
            `<i class="fa-solid fa-location-dot" aria-hidden="true"></i>` +
            `<span><strong>${esc(main)}</strong>${rest ? `<small>${esc(rest)}</small>` : ''}</span></button></li>`;
    }).join('');

    if (empty) empty.hidden = !(query && !predictions.length);
    if (drop) drop.hidden = !query;
}

function suggest(query) {
    lastQuery = query;
    if (!query) { renderResults([], ''); return; }
    if (!mapsReady()) return;

    placesService ||= new google.maps.places.AutocompleteService();
    sessionToken ||= new google.maps.places.AutocompleteSessionToken();

    placesService.getPlacePredictions(
        { input: query, componentRestrictions: { country: 'ke' }, sessionToken },
        (predictions, status) => {
            if (query !== lastQuery) return;     // an older request finished late
            const ok = status === google.maps.places.PlacesServiceStatus.OK;
            renderResults(ok && predictions ? predictions.slice(0, 6) : [], query);
        }
    );
}

function ensureMap(center) {
    if (map) return;
    map = new google.maps.Map($('map'), {
        center,
        zoom: 17,
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        clickableIcons: false,
        gestureHandling: 'greedy',
    });
    geocoder ||= new google.maps.Geocoder();
    map.addListener('idle', () => reverseGeocode(map.getCenter()));
}

/* centres the map on `location` (the pin stays in the middle) */
function moveTo(location, label) {
    if (label) $('selected-address').textContent = label;
    ensureMap(location);
    google.maps.event.trigger(map, 'resize');
    map.setCenter(location);
    map.setZoom(18);
}

function pickPrediction(placeId, label) {
    if (!placeId || !mapsReady()) return;
    geocoder ||= new google.maps.Geocoder();
    sessionToken = null;   // a pick ends the autocomplete session
    closeDrop();
    const input = $('place-search');
    if (input) { input.value = label || ''; input.blur(); }
    geocoder.geocode({ placeId }, (results, status) => {
        if (status !== 'OK' || !results?.length) {
            window.notify?.error?.('We could not open that place. Try another one.');
            return;
        }
        moveTo(results[0].geometry.location, label);
    });
}

/* "Use current location" */
window.useCurrentLocation = function () {
    if (!navigator.geolocation) {
        window.notify?.error?.('Your browser cannot share your location. Search for your street instead.');
        return;
    }
    if (!mapsReady()) return;

    const finding = window.notify ? window.notify.loading('Finding your location…') : null;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            finding?.close?.();
            closeDrop();
            moveTo(new google.maps.LatLng(position.coords.latitude, position.coords.longitude), 'Finding address…');
        },
        () => {
            if (finding) finding.error('We could not read your location. Search for your street instead.');
            $('place-search')?.focus();
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
};

/*
|--------------------------------------------------------------------------
| Reverse Geocode
|--------------------------------------------------------------------------
*/

function reverseGeocode(location) {

    geocoder ||= new google.maps.Geocoder();

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

    if (moveMap) {
        map.panTo(location);
        map.setZoom(15);
    }

    const lat = location.lat();
    const lng = location.lng();

    document.getElementById("latitude").value = lat;
    document.getElementById("longitude").value = lng;
    document.getElementById("delivery_address").value = address.full;

    const label = document.getElementById("selected-address");

    if (label) {
        label.textContent = address.full || "Unnamed place";
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

}

/*
|--------------------------------------------------------------------------
| Confirm Delivery Location
|--------------------------------------------------------------------------
*/

window.confirmDeliveryLocation = function () {

    if (!window.currentDeliveryLocation) {
        (window.notify ? window.notify.error : alert)("Please choose a delivery location.");
        return;
    }

    const latitude = Number(
        window.currentDeliveryLocation.latitude
    );

    const longitude = Number(
        window.currentDeliveryLocation.longitude
    );

    if (
        !Number.isFinite(latitude) ||
        !Number.isFinite(longitude)
    ) {
        (window.notify ? window.notify.error : alert)(
            "The selected delivery location does not have valid coordinates."
        );

        return;
    }

    const locationToSave = {
        ...window.currentDeliveryLocation,
        latitude,
        longitude,
        saved_at: new Date().toISOString()
    };

    /*
    |--------------------------------------------------------------------------
    | Browser copy
    |--------------------------------------------------------------------------
    */

    localStorage.setItem(
        "delivery_location",
        JSON.stringify(locationToSave)
    );

    /*
    |--------------------------------------------------------------------------
    | Laravel session
    |--------------------------------------------------------------------------
    */

    const csrfToken = document.querySelector(
        'meta[name="csrf-token"]'
    )?.content;

    if (!csrfToken) {
        console.error("CSRF token not found.");
        (window.notify ? window.notify.error : alert)("Unable to save delivery location.");
        return;
    }

    const saving = window.notify ? window.notify.loading("Saving your delivery location…") : null;

    fetch("/delivery-location", {
        method: "POST",

        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": csrfToken
        },

        body: JSON.stringify({
            latitude,
            longitude
        })
    })
    .then(response => {

        if (!response.ok) {
            throw new Error(
                `Location request failed: ${response.status}`
            );
        }

        return response.json();
    })
    .then(data => {

        if (!data.success) {
            throw new Error(
                data.message ||
                "Unable to save delivery location."
            );
        }

        closeModal();
        saving?.success("Showing shops that deliver to you.", { title: "Location saved" });

        /*
        |--------------------------------------------------------------------------
        | Clean old coordinates from URL
        |--------------------------------------------------------------------------
        */

        const url = new URL(
            window.location.href
        );

        url.searchParams.delete("latitude");
        url.searchParams.delete("longitude");

        // swap in the refreshed page (shops near you replace the "set location" banner)
        // without a full reload; falls back to a normal reload
        const target = url.pathname + url.search;
        if (window.softNavigate) {
            window.softNavigate(target, { replace: true });
        } else {
            window.location.replace(target);
        }
    })
    .catch(error => {

        console.error(
            "Unable to save delivery location:",
            error
        );

        if (saving) saving.error("Unable to save your delivery location. Please try again.");
        else alert("Unable to save your delivery location. Please try again.");
    });
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