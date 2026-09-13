let directionsMap;
let directionsService;
let directionsRenderer;

let riderMarker;
let pickupMarker; // Will hold our FontAwesome Advanced Marker Element

let riderInfoWindow;
let pickupInfoWindow;

let routePath = [];

// --- Animation & State Variables ---
let animationFrameId = null;
let currentRiderPosition = null; 

/*
|--------------------------------------------------------------------------
| Initialize Delivery Directions
|--------------------------------------------------------------------------
*/
window.initDeliveryDirections = async function () {
    const mapElement = document.getElementById("map");
    if (!mapElement) {
        console.error("Map container not found");
        return;
    }

    const rider = {
        lat: parseFloat(mapElement.dataset.riderLat),
        lng: parseFloat(mapElement.dataset.riderLng)
    };

    const pickup = {
        lat: parseFloat(mapElement.dataset.shopLat),
        lng: parseFloat(mapElement.dataset.shopLng)
    };

    if (Number.isNaN(rider.lat) || Number.isNaN(rider.lng) || Number.isNaN(pickup.lat) || Number.isNaN(pickup.lng)) {
        console.error("Invalid coordinates", { rider, pickup });
        return;
    }

    currentRiderPosition = new google.maps.LatLng(rider.lat, rider.lng);

    // Modern Advanced Markers require a mapId string (even a generic/placeholder one works)
    directionsMap = new google.maps.Map(mapElement, {
        center: rider,
        zoom: 18,
        tilt: 45,
        heading: 0,
        mapId: "DELIVERY_TRACKING_MAP", 
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false,
        clickableIcons: false,
        gestureHandling: "greedy"
    });

    // Asynchronously import the newer Google Maps Marker Engine
    const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        map: directionsMap,
        suppressMarkers: true,
        preserveViewport: true,
        polylineOptions: {
            strokeColor: "#f25112",
            strokeOpacity: 1,
            strokeWeight: 6
        }
    });

    // Standard legacy style fallback used for the moving rider element
    riderMarker = new google.maps.Marker({
        position: rider,
        map: directionsMap,
        title: "Your Location",
        zIndex: 999,
        icon: {
            url: "/img/motorbike.png",
            scaledSize: new google.maps.Size(42, 42),
            anchor: new google.maps.Point(21, 21)
        }
    });

    /*
    |--------------------------------------------------------------------------
    | Custom Orange FontAwesome Store Element Marker
    |--------------------------------------------------------------------------
    */
    const storePinContainer = document.createElement("div");
    storePinContainer.style.color = "#f25112"; // Your theme orange color
    storePinContainer.style.fontSize = "20px"; // Icon presentation sizing scale
    storePinContainer.style.filter = "drop-shadow(0px 2px 4px rgba(0,0,0,0.3))"; // Map floating shadow effect
    
    // Inject the raw icon class string requested
    const storeIcon = document.createElement("i");
    storeIcon.className = "fa-solid fa-store";
    storePinContainer.appendChild(storeIcon);

    pickupMarker = new AdvancedMarkerElement({
        position: pickup,
        map: directionsMap,
        title: "Pickup Point",
        content: storePinContainer, // Binds the raw DOM tree right to the coordinates
        zIndex: 998
    });

    riderInfoWindow = new google.maps.InfoWindow({
        content: `<div style="color:#6b7280; font-weight:600; font-size:13px; padding:1px 2px; white-space:nowrap;">Your Location</div>`
    });
    riderInfoWindow.open({ anchor: riderMarker, map: directionsMap });

    pickupInfoWindow = new google.maps.InfoWindow({
        content: `<div style="color:#6b7280; font-weight:600; font-size:13px; padding:1px 2px; white-space:nowrap;">Pickup Point</div>`
    });
    pickupInfoWindow.open({ anchor: pickupMarker, map: directionsMap });

    drawRoute(rider, pickup);
};

/*
|--------------------------------------------------------------------------
| Draw Route
|--------------------------------------------------------------------------
*/
function drawRoute(origin, destination) {
    directionsService.route({
        origin: origin,
        destination: destination,
        travelMode: google.maps.TravelMode.DRIVING
    }, function (result, status) {
        if (status !== "OK") {
            console.error("Directions failed:", status);
            return;
        }

        directionsRenderer.setDirections(result);
        routePath = result.routes[0].overview_path || [];

        const leg = result.routes[0].legs[0];
        const info = document.getElementById("route-info");
        if (info) {
            info.innerHTML = `<strong>${leg.distance.text}</strong><br>${leg.duration.text}`;
        }

        const heading = getRouteHeading(origin);
        moveCamera(origin, heading);
    });
}

/*
|--------------------------------------------------------------------------
| Get Route Heading
|--------------------------------------------------------------------------
*/
function getRouteHeading(position) {
    const pos = position instanceof google.maps.LatLng ? position : new google.maps.LatLng(position.lat, position.lng);
    
    if (routePath.length < 2) return 0;

    let closestIndex = 0;
    let closestDistance = Infinity;

    for (let i = 0; i < routePath.length; i++) {
        const distance = google.maps.geometry.spherical.computeDistanceBetween(pos, routePath[i]);
        if (distance < closestDistance) {
            closestDistance = distance;
            closestIndex = i;
        }
    }

    const nextIndex = (closestIndex + 1 < routePath.length) ? closestIndex + 1 : closestIndex;
    if (closestIndex === nextIndex) {
        return closestIndex > 0 ? google.maps.geometry.spherical.computeHeading(routePath[closestIndex - 1], routePath[closestIndex]) : 0;
    }

    return google.maps.geometry.spherical.computeHeading(routePath[closestIndex], routePath[nextIndex]);
}

/*
|--------------------------------------------------------------------------
| Move Camera
|--------------------------------------------------------------------------
*/
function moveCamera(position, heading) {
    if (!directionsMap) return;
    directionsMap.moveCamera({
        center: position,
        heading: heading
    });
}

/*
|--------------------------------------------------------------------------
| Live Ingestion Update Hook
|--------------------------------------------------------------------------
*/
window.updateRiderPosition = function (newLat, newLng, backendHeading = null, speed = 0) {
    if (!riderMarker || !directionsMap) return;

    const targetPosition = new google.maps.LatLng(parseFloat(newLat), parseFloat(newLng));
    const startPosition = currentRiderPosition;
    
    const targetHeading = (backendHeading !== null) ? backendHeading : getRouteHeading(targetPosition);

    const duration = 3000; 
    const startTime = performance.now();

    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
    }

    function animate(currentTime) {
        const elapsedTime = currentTime - startTime;
        const progress = Math.min(elapsedTime / duration, 1);

        const lat = startPosition.lat() + (targetPosition.lat() - startPosition.lat()) * progress;
        const lng = startPosition.lng() + (targetPosition.lng() - startPosition.lng()) * progress;
        const interpolatedPos = new google.maps.LatLng(lat, lng);

        riderMarker.setPosition(interpolatedPos);
        currentRiderPosition = interpolatedPos;

        directionsMap.moveCamera({
            center: interpolatedPos,
            heading: targetHeading
        });

        if (progress < 1) {
            animationFrameId = requestAnimationFrame(animate);
        }
    }

    animationFrameId = requestAnimationFrame(animate);
};
