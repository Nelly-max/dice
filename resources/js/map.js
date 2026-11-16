/*
 |--------------------------------------------------------------------------
 | Delivery location picker (resources/views/modals/map.blade.php)
 |--------------------------------------------------------------------------
 | One screen: search (Google Places suggestions drop down over the map),
 | "Use current location", the map with a centred pin, Confirm.
 |
 | Where the map opens:
 |   1. the saved delivery location, if there is one;
 |   2. otherwise the user's current location (the browser asks once);
 |   3. if that cannot be read (blocked, no GPS, not https, timed out), the map
 |      illustration (#loc-placeholder) stays up with the reason, and the user
 |      can search, try "Use current location" again, or choose on the map.
 */

let map;
let geocoder;
let placesService;      // AutocompleteService
let sessionToken;
let searchTimer;
let lastQuery = '';
let locating = false;

const DEFAULT_CENTER = { lat: -1.392, lng: 36.764 };

const $ = (id) => document.getElementById(id);
const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const mapsReady = () => !!(window.google && google.maps && google.maps.places);

/* the Google Maps script loads async; wait for it (up to 15 s) instead of giving up */
function whenMapsReady(timeout = 15000) {
    if (mapsReady()) return Promise.resolve(true);
    return new Promise((resolve) => {
        const started = Date.now();
        const tick = setInterval(() => {
            if (mapsReady()) { clearInterval(tick); resolve(true); }
            else if (Date.now() - started > timeout) { clearInterval(tick); resolve(false); }
        }, 200);
    });
}

function savedCenter() {
    try {
        const saved = JSON.parse(localStorage.getItem('delivery_location') || 'null');
        const lat = Number(saved?.latitude), lng = Number(saved?.longitude);
        if (saved && Number.isFinite(lat) && Number.isFinite(lng) && (lat || lng)) return { lat, lng };
    } catch (e) { /* ignore */ }
    return null;
}

/* ---------- the map illustration ----------
 | Shown while the location is being read and whenever there is no location
 | (none saved, or the browser did not give one). It is just the picture:
 | no error wording; the search box and "Use current location" sit above it.
 */
function showPlaceholder(state = 'idle') {
    const ph = $('loc-placeholder');
    if (!ph) return;
    ph.dataset.state = state === 'locating' ? 'locating' : 'idle';
    ph.hidden = false;
}

function hidePlaceholder() {
    const ph = $('loc-placeholder');
    if (ph) ph.hidden = true;
}

/* ---------- reading the user's position ---------- */

/* resolves {lat, lng}; rejects with 'denied' | 'insecure' | 'timeout' | 'unavailable' | 'unsupported' */
function readPosition() {
    return new Promise(async (resolve, reject) => {
        if (!('geolocation' in navigator)) return reject('unsupported');
        if (window.isSecureContext === false) return reject('insecure');

        // already blocked: fail at once instead of waiting for a prompt that never comes
        try {
            const perm = await navigator.permissions?.query({ name: 'geolocation' });
            if (perm?.state === 'denied') return reject('denied');
        } catch (e) { /* Permissions API not available: just ask */ }

        const ok = (p) => resolve({ lat: p.coords.latitude, lng: p.coords.longitude });
        const reason = (err) => (err?.code === 1 ? 'denied' : err?.code === 3 ? 'timeout' : 'unavailable');

        // GPS first; laptops and indoor phones often time out on it, so fall back to
        // the network position (Wi-Fi / cell), accepting one up to 5 minutes old
        navigator.geolocation.getCurrentPosition(ok, (err) => {
            if (err?.code === 1) return reject('denied');
            navigator.geolocation.getCurrentPosition(ok, (err2) => reject(reason(err2)),
                { enableHighAccuracy: false, timeout: 12000, maximumAge: 300000 });
        }, { enableHighAccuracy: true, timeout: 8000, maximumAge: 60000 });
    });
}

/* map ready + position known: centre on it; when the map cannot load, keep the coordinates so Confirm still works */
async function goToPosition(pos) {
    if (await whenMapsReady()) {
        closeDrop();
        hidePlaceholder();
        moveTo(new google.maps.LatLng(pos.lat, pos.lng), 'Finding address…');
        return;
    }
    window.currentDeliveryLocation = { address: 'Your current location', latitude: pos.lat, longitude: pos.lng };
    $('latitude') && ($('latitude').value = pos.lat);
    $('longitude') && ($('longitude').value = pos.lng);
    $('selected-address') && ($('selected-address').textContent = 'Your current location');
    showPlaceholder();
}

/* first open without a saved location: try the user's position, quietly */
async function locateOnOpen() {
    if (locating) return;
    locating = true;
    showPlaceholder('locating');
    try {
        await goToPosition(await readPosition());
    } catch (why) {
        if (!map) showPlaceholder();
    } finally {
        locating = false;
    }
}

/* "Choose on the map instead": open the map on the default area */
async function showMapAnyway() {
    if (!(await whenMapsReady(5000))) { showPlaceholder(); return; }
    ensureMap(DEFAULT_CENTER);
    hidePlaceholder();
    google.maps.event.trigger(map, 'resize');
    map.setCenter(DEFAULT_CENTER);
}

/* called by modal.js every time the sheet opens */
window.initDeliveryMap = async function () {
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
        $('mapModal')?.querySelector('[data-loc-show-map]')?.addEventListener('click', showMapAnyway);
    }

    if (map) {
        hidePlaceholder();
        google.maps.event.trigger(map, 'resize');
        return;
    }

    const saved = savedCenter();
    if (saved) {
        showPlaceholder('locating');
        if (await whenMapsReady()) {
            ensureMap(saved);
            hidePlaceholder();
        } else {
            showPlaceholder();
        }
        return;
    }

    locateOnOpen();
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

async function suggest(query) {
    lastQuery = query;
    if (!query) { renderResults([], ''); return; }
    if (!(await whenMapsReady(5000))) return;

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
    hidePlaceholder();
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
window.useCurrentLocation = async function () {
    if (locating) return;
    locating = true;

    // with the map showing, a small toast; otherwise the illustration says what is happening
    const finding = map && window.notify ? window.notify.loading('Finding your location…') : null;
    if (!map) showPlaceholder('locating');

    try {
        const pos = await readPosition();
        finding?.close?.();
        await goToPosition(pos);
    } catch (why) {
        // no location: keep the picture up; a gentle hint, never an error about permissions
        if (finding) finding.info('Search for your street, estate or building instead.', { title: 'Location not found' });
        if (!map) showPlaceholder();
        $('place-search')?.focus();
    } finally {
        locating = false;
    }
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

        // the address goes with it so checkout can show the place the cart was built for
        body: JSON.stringify({
            latitude,
            longitude,
            address: locationToSave.address || null,
            county: locationToSave.county || null,
            town: locationToSave.town || null
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