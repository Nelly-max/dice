/*
|--------------------------------------------------------------------------
| DELIVERY MAP STATE
|--------------------------------------------------------------------------
*/

let directionsMap = null;
let directionsService = null;
let directionsRenderer = null;

let riderMarker = null;
let pickupMarker = null;
let dropoffMarker = null;

let riderInfoWindow = null;
let pickupInfoWindow = null;
let dropoffInfoWindow = null;

let routePath = [];
let currentRiderPosition = null;

let dropoffLatLng = null;

let animationFrameId = null;
let cameraAnimationFrameId = null;

let etaTimer = null;
let etaRefreshTimer = null;

let currentEtaSeconds = null;
let etaLastUpdatedAt = null;

let isNavigating = false;
let isInitialized = false;
let routeRequestInProgress = false;

let cameraTransitionInProgress = false;


/*
|--------------------------------------------------------------------------
| DELIVERY STATUS
|--------------------------------------------------------------------------
*/

let currentDeliveryStatus = null;


/*
|--------------------------------------------------------------------------
| ARRIVED AT SHOP STATE
|--------------------------------------------------------------------------
*/

let arrivedAtShop = false;
let arrivedAtShopRequestInProgress = false;

const ARRIVED_SHOP_DISTANCE = 100;


/*
|--------------------------------------------------------------------------
| DELIVER STATE
|--------------------------------------------------------------------------
*/

let deliverRequestInProgress = false;

const DELIVER_DISTANCE = 50;


/*
|--------------------------------------------------------------------------
| START DELIVERY STATE
|--------------------------------------------------------------------------
*/

let startDeliveryRequestInProgress = false;


/*
|--------------------------------------------------------------------------
| PICKUP STATE
|--------------------------------------------------------------------------
*/

let pickupRequestInProgress = false;


/*
|--------------------------------------------------------------------------
| Configuration
|--------------------------------------------------------------------------
*/

const NORMAL_ZOOM = 18;
const NAVIGATION_ZOOM = 19;
const NAVIGATION_TILT = 55;

const CAMERA_OFFSET = 0.00030;

const RIDER_ANIMATION_DURATION = 3000;

const NAVIGATION_CAMERA_TRANSITION = 900;

const ETA_REFRESH_INTERVAL = 30 * 1000;

const LOOK_AHEAD_DISTANCE = 8;

const DELIVERY_MAP_ID = "DELIVERY_TRACKING_MAP";

const RIDER_ICON = "/img/motorbike.png";


/*
|--------------------------------------------------------------------------
| Small Helpers
|--------------------------------------------------------------------------
*/

function getElement(id) {

    return document.getElementById(id);
}


function parseCoordinate(value) {

    const number = parseFloat(value);

    return Number.isFinite(number)
        ? number
        : null;
}


function toLatLng(position) {

    if (position instanceof google.maps.LatLng) {
        return position;
    }

    return new google.maps.LatLng(
        Number(position.lat),
        Number(position.lng)
    );
}


function getMapRenderingType() {

    if (!directionsMap) {
        return null;
    }

    try {

        return directionsMap.getRenderingType();

    } catch (error) {

        return null;
    }
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

function getCsrfToken() {

    const meta =
        document.querySelector(
            'meta[name="csrf-token"]'
        );

    if (meta) {
        return meta.getAttribute("content");
    }

    const input =
        document.querySelector(
            'input[name="_token"]'
        );

    if (input) {
        return input.value;
    }

    return null;
}


/*
|--------------------------------------------------------------------------
| Delivery Identification
|--------------------------------------------------------------------------
*/

function getDeliveryIdentifier() {

    const mapElement =
        getElement("map");

    if (!mapElement) {

        return {
            deliveryId: null,
            deliveryNumber: null
        };
    }

    return {

        deliveryId:
            mapElement.dataset.deliveryId ||
            null,

        deliveryNumber:
            mapElement.dataset.deliveryNumber ||
            null
    };
}


/*
|--------------------------------------------------------------------------
| DELIVERY STATUS
|--------------------------------------------------------------------------
*/

function getInitialDeliveryStatus() {

    const mapElement =
        getElement("map");

    if (!mapElement) {
        return null;
    }

    return (
        mapElement.dataset.deliveryStatus ||
        null
    );
}


function normalizeDeliveryStatus(status) {

    if (!status) {
        return null;
    }

    return String(status)
        .trim()
        .toLowerCase();
}


function setDeliveryStatus(status) {

    currentDeliveryStatus =
        normalizeDeliveryStatus(status);

    console.log(
        "[DELIVERY STATUS] Status updated:",
        currentDeliveryStatus
    );

    const mapElement =
        getElement("map");

    if (mapElement && currentDeliveryStatus) {

        mapElement.dataset.deliveryStatus =
            currentDeliveryStatus;
    }

    updateDeliveryActionButtons();

    /*
     * When the delivery becomes on_transit,
     * the destination changes from the shop
     * to the customer.
     */
    if (
        currentDeliveryStatus ===
        "on_transit"
    ) {

        switchToDropoffRoute();
    }
}


function getDeliveryStatus() {

    return currentDeliveryStatus;
}


/*
|--------------------------------------------------------------------------
| Delivery Action Buttons
|--------------------------------------------------------------------------
*/

function getDeliveryActionButtons() {

    return {

        arrived:
            getElement("arrivedBtn"),

        waiting:
            getElement("waitingBtn"),

        pickup:
            getElement("pickupBtn"),

        start:
            getElement("startBtn"),

        deliver:
            getElement("deliverBtn")
    };
}


/*
|--------------------------------------------------------------------------
| Hide All Delivery Action Buttons
|--------------------------------------------------------------------------
*/

function hideAllDeliveryActionButtons() {

    const buttons =
        getDeliveryActionButtons();

    Object.values(buttons).forEach(button => {

        if (!button) {
            return;
        }

        const container =
            button.closest(
                ".action-button"
            );

        if (container) {

            container.style.display =
                "none";
        }

        button.disabled = true;

        button.setAttribute(
            "aria-hidden",
            "true"
        );

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        button.style.pointerEvents =
            "none";
    });
}


/*
|--------------------------------------------------------------------------
| Show Delivery Action Button
|--------------------------------------------------------------------------
*/

function showDeliveryActionButton(button) {

    if (!button) {
        return;
    }

    const container =
        button.closest(
            ".action-button"
        );

    if (container) {

        container.style.display =
            "flex";
    }

    button.setAttribute(
        "aria-hidden",
        "false"
    );
}


/*
|--------------------------------------------------------------------------
| Update Delivery Action Buttons
|--------------------------------------------------------------------------
*/

function updateDeliveryActionButtons() {

    const buttons =
        getDeliveryActionButtons();

    hideAllDeliveryActionButtons();

    const status =
        normalizeDeliveryStatus(
            currentDeliveryStatus
        );

    if (!status) {

        console.warn(
            "[DELIVERY STATUS] No delivery status available."
        );

        return;
    }


    /*
     |--------------------------------------------------------------------------
     | RIDER ASSIGNED
     |--------------------------------------------------------------------------
     */

    if (
        status ===
        "rider_assigned"
    ) {

        showDeliveryActionButton(
            buttons.arrived
        );

        checkRiderArrivalDistance();

        return;
    }


    /*
     |--------------------------------------------------------------------------
     | RIDER ARRIVED / WAITING DISPATCH
     |--------------------------------------------------------------------------
     */

    if (
        status ===
            "rider_arrived_shop" ||
        status ===
            "waiting_dispatch"
    ) {

        showDeliveryActionButton(
            buttons.waiting
        );

        return;
    }


    /*
     |--------------------------------------------------------------------------
     | DISPATCHED
     |--------------------------------------------------------------------------
     */

    if (
        status ===
        "dispatched"
    ) {

        showDeliveryActionButton(
            buttons.pickup
        );

        updatePickupButton();

        return;
    }


    /*
     |--------------------------------------------------------------------------
     | PICKED UP
     |--------------------------------------------------------------------------
     |
     | Rider has collected the order.
     | Show START.
     |
     */

    if (
        status ===
        "picked_up"
    ) {

        showDeliveryActionButton(
            buttons.start
        );

        updateStartDeliveryButton();

        return;
    }


    /*
     |--------------------------------------------------------------------------
     | ON TRANSIT
     |--------------------------------------------------------------------------
     |
     | Rider is travelling to customer.
     | Show DELIVER.
     |
     */

    if (
        status ===
        "on_transit"
    ) {

        showDeliveryActionButton(
            buttons.deliver
        );

        checkRiderDeliveryDistance();

        return;
    }


    /*
     |--------------------------------------------------------------------------
     | COMPLETED STATES
     |--------------------------------------------------------------------------
     */

    if (
        status ===
            "delivered" ||
        status ===
            "completed" ||
        status ===
            "cancelled" ||
        status ===
            "searching_rider"
    ) {

        return;
    }


    console.warn(
        "[DELIVERY STATUS] Unknown delivery status:",
        status
    );
}


/*
|--------------------------------------------------------------------------
| Setup Delivery Status Buttons
|--------------------------------------------------------------------------
*/

function setupDeliveryStatusButtons() {

    const buttons =
        getDeliveryActionButtons();

    hideAllDeliveryActionButtons();


    /*
     * Arrived
     */

    if (buttons.arrived) {

        buttons.arrived.disabled =
            true;
    }


    /*
     * Waiting
     */

    if (buttons.waiting) {

        buttons.waiting.disabled =
            true;
    }


    /*
     * Pickup
     */

    if (buttons.pickup) {

        buttons.pickup.disabled =
            true;
    }


    /*
     * Start
     */

    if (buttons.start) {

        buttons.start.disabled =
            true;
    }


    /*
     * Deliver
     */

    if (buttons.deliver) {

        buttons.deliver.disabled =
            true;
    }


    /*
     * Configure individual actions.
     */

    setupArrivedAtShopButton();

    setupPickupButton();

    setupStartDeliveryButton();

    setupDeliverButton();


    updateDeliveryActionButtons();
}


/*
|--------------------------------------------------------------------------
| ETA
|--------------------------------------------------------------------------
*/

function updateEtaDisplay(seconds) {

    const etaElement =
        getElement("deliveryEta");

    if (!etaElement) {

        console.warn(
            "[ETA] #deliveryEta element not found."
        );

        return;
    }


    if (!Number.isFinite(seconds)) {

        etaElement.textContent =
            "Calculating...";

        return;
    }


    const remaining =
        Math.max(
            0,
            Math.round(seconds)
        );


    if (remaining <= 30) {

        etaElement.textContent =
            "Arriving";

        return;
    }


    const minutes =
        Math.ceil(
            remaining / 60
        );


    if (minutes <= 1) {

        etaElement.textContent =
            "1 Min";

        return;
    }


    etaElement.textContent =
        `${minutes} Min`;
}


function startEtaCountdown(seconds) {

    stopEtaCountdown();


    if (!Number.isFinite(seconds)) {

        currentEtaSeconds = null;
        etaLastUpdatedAt = null;

        updateEtaDisplay(null);

        return;
    }


    currentEtaSeconds =
        Math.max(
            0,
            seconds
        );

    etaLastUpdatedAt =
        Date.now();


    updateEtaDisplay(
        currentEtaSeconds
    );


    etaTimer =
        setInterval(() => {

            if (
                !Number.isFinite(
                    currentEtaSeconds
                )
            ) {
                return;
            }


            const elapsed =
                Math.floor(
                    (
                        Date.now() -
                        etaLastUpdatedAt
                    ) / 1000
                );


            const remaining =
                Math.max(
                    0,
                    currentEtaSeconds -
                    elapsed
                );


            updateEtaDisplay(
                remaining
            );

        }, 1000);
}


function stopEtaCountdown() {

    if (etaTimer) {

        clearInterval(
            etaTimer
        );

        etaTimer = null;
    }
}


/*
|--------------------------------------------------------------------------
| ETA / Route Refresh
|--------------------------------------------------------------------------
*/

function scheduleEtaRefresh() {

    if (etaRefreshTimer) {

        clearTimeout(
            etaRefreshTimer
        );
    }


    etaRefreshTimer =
        setTimeout(() => {

            refreshRouteAndEta();

            scheduleEtaRefresh();

        }, ETA_REFRESH_INTERVAL);
}


function refreshRouteAndEta() {

    if (
        !directionsService ||
        !currentRiderPosition ||
        routeRequestInProgress
    ) {
        return;
    }


    /*
     * On transit -> customer.
     */

    if (
        currentDeliveryStatus ===
        "on_transit"
    ) {

        if (!dropoffLatLng) {
            return;
        }

        drawRoute(
            currentRiderPosition,
            dropoffLatLng,
            isNavigating,
            true
        );

        return;
    }


    /*
     * Before pickup -> shop.
     */

    if (!pickupMarker) {
        return;
    }


    const pickup =
        getPickupPosition();


    if (!pickup) {
        return;
    }


    drawRoute(
        currentRiderPosition,
        pickup,
        isNavigating,
        true
    );
}


/*
|--------------------------------------------------------------------------
| Delivery Panel Controls
|--------------------------------------------------------------------------
*/

function setupDeliveryPanelControls() {

    const mainView =
        document.querySelector(
            ".main-view"
        );

    const menuView =
        document.querySelector(
            ".menu-view"
        );

    const menuButton =
        document.querySelector(
            ".main-view .menu"
        );

    const closeButton =
        document.querySelector(
            ".menu-view .close"
        );


    if (mainView && menuView) {

        if (
            !mainView.classList.contains(
                "active"
            ) &&
            !menuView.classList.contains(
                "active"
            )
        ) {

            mainView.classList.add(
                "active"
            );
        }
    }


    if (
        menuButton &&
        menuButton.dataset.menuReady !==
            "true"
    ) {

        menuButton.dataset.menuReady =
            "true";


        menuButton.addEventListener(
            "click",
            event => {

                event.preventDefault();
                event.stopPropagation();


                if (
                    !mainView ||
                    !menuView
                ) {
                    return;
                }


                mainView.classList.remove(
                    "active"
                );

                menuView.classList.add(
                    "active"
                );
            }
        );
    }


    if (
        closeButton &&
        closeButton.dataset.closeReady !==
            "true"
    ) {

        closeButton.dataset.closeReady =
            "true";


        closeButton.addEventListener(
            "click",
            event => {

                event.preventDefault();
                event.stopPropagation();


                if (
                    !mainView ||
                    !menuView
                ) {
                    return;
                }


                menuView.classList.remove(
                    "active"
                );

                mainView.classList.add(
                    "active"
                );
            }
        );
    }


    const minimizeButton =
        document.querySelector(
            ".minimize"
        );


    if (
        minimizeButton &&
        minimizeButton.dataset.minimizeReady !==
            "true"
    ) {

        minimizeButton.dataset.minimizeReady =
            "true";


        minimizeButton.addEventListener(
            "click",
            event => {

                event.preventDefault();
                event.stopPropagation();

                minimizeButton.classList.toggle(
                    "active"
                );
            }
        );
    }
}


/*
|--------------------------------------------------------------------------
| Google Maps External Navigation
|--------------------------------------------------------------------------
*/

function setupGoogleMapsButton() {

    const button =
        getElement(
            "openGoogleMapsBtn"
        );


    if (!button) {

        console.warn(
            "[GOOGLE MAPS] #openGoogleMapsBtn not found."
        );

        return;
    }


    if (
        button.dataset.googleMapsReady ===
        "true"
    ) {
        return;
    }


    button.dataset.googleMapsReady =
        "true";


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();
            event.stopPropagation();

            openGoogleMapsNavigation();
        }
    );
}


function openGoogleMapsNavigation() {

    /*
     * Destination depends on delivery state.
     */

    let destinationPosition = null;


    if (
        currentDeliveryStatus ===
            "on_transit" ||
        currentDeliveryStatus ===
            "delivered" ||
        currentDeliveryStatus ===
            "completed"
    ) {

        destinationPosition =
            getDropoffPosition();

    } else {

        destinationPosition =
            getPickupPosition();
    }


    if (!destinationPosition) {

        console.error(
            "[GOOGLE MAPS] Destination unavailable."
        );

        return;
    }


    const destination =
        `${destinationPosition.lat},${destinationPosition.lng}`;


    let url =
        "https://www.google.com/maps/dir/?api=1";


    url +=
        `&destination=${encodeURIComponent(
            destination
        )}`;


    url +=
        "&travelmode=driving";


    if (currentRiderPosition) {

        const origin =
            `${currentRiderPosition.lat()},${currentRiderPosition.lng()}`;


        url +=
            `&origin=${encodeURIComponent(
                origin
            )}`;
    }


    window.open(
        url,
        "_blank",
        "noopener,noreferrer"
    );
}


/*
|--------------------------------------------------------------------------
| Pickup Position
|--------------------------------------------------------------------------
*/

function getPickupPosition() {

    if (
        !pickupMarker ||
        !pickupMarker.position
    ) {
        return null;
    }


    const position =
        pickupMarker.position;


    const lat =
        typeof position.lat === "function"
            ? position.lat()
            : Number(position.lat);


    const lng =
        typeof position.lng === "function"
            ? position.lng()
            : Number(position.lng);


    if (
        !Number.isFinite(lat) ||
        !Number.isFinite(lng)
    ) {
        return null;
    }


    return {
        lat,
        lng
    };
}


/*
|--------------------------------------------------------------------------
| Drop-off Position
|--------------------------------------------------------------------------
*/

function getDropoffPosition() {

    if (!dropoffLatLng) {
        return null;
    }


    return {

        lat:
            dropoffLatLng.lat(),

        lng:
            dropoffLatLng.lng()
    };
}


/*
|--------------------------------------------------------------------------
| ARRIVED AT SHOP
|--------------------------------------------------------------------------
*/

function getArrivedAtShopButton() {

    return getElement(
        "arrivedBtn"
    );
}


function updateArrivedAtShopButton(
    distance = null
) {

    const button =
        getArrivedAtShopButton();


    if (!button) {
        return;
    }


    const heading =
        button.querySelector("h4");


    if (
        currentDeliveryStatus !==
        "rider_assigned"
    ) {

        button.disabled =
            true;

        button.classList.remove(
            "active"
        );

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        return;
    }


    if (arrivedAtShop) {

        button.disabled =
            true;

        button.classList.add(
            "active"
        );

        button.classList.add(
            "arrived"
        );

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        if (heading) {
            heading.textContent =
                "Arrived";
        }

        return;
    }


    if (!Number.isFinite(distance)) {

        button.disabled =
            true;

        button.classList.remove(
            "active"
        );

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        if (heading) {
            heading.textContent =
                "Arrived";
        }

        return;
    }


    if (
        distance <=
        ARRIVED_SHOP_DISTANCE
    ) {

        button.disabled =
            false;

        button.classList.add(
            "active"
        );

        button.style.pointerEvents =
            "auto";

        button.setAttribute(
            "aria-disabled",
            "false"
        );

        if (heading) {
            heading.textContent =
                "Arrived";
        }

        return;
    }


    button.disabled =
        true;

    button.classList.remove(
        "active"
    );

    button.style.pointerEvents =
        "none";

    button.setAttribute(
        "aria-disabled",
        "true"
    );

    if (heading) {
        heading.textContent =
            "Arrived";
    }
}


function getDistanceToPickup() {

    if (
        !currentRiderPosition ||
        !pickupMarker
    ) {
        return null;
    }


    const pickup =
        getPickupPosition();


    if (!pickup) {
        return null;
    }


    const riderPosition =
        toLatLng(
            currentRiderPosition
        );


    const pickupPosition =
        new google.maps.LatLng(
            pickup.lat,
            pickup.lng
        );


    if (
        !google.maps.geometry ||
        !google.maps.geometry.spherical
    ) {
        return null;
    }


    return google.maps.geometry.spherical
        .computeDistanceBetween(
            riderPosition,
            pickupPosition
        );
}


function checkRiderArrivalDistance() {

    if (
        currentDeliveryStatus !==
        "rider_assigned"
    ) {

        updateArrivedAtShopButton();

        return;
    }


    if (arrivedAtShop) {

        updateArrivedAtShopButton();

        return;
    }


    const distance =
        getDistanceToPickup();


    if (!Number.isFinite(distance)) {

        updateArrivedAtShopButton(
            null
        );

        return;
    }


    updateArrivedAtShopButton(
        distance
    );
}


/*
|--------------------------------------------------------------------------
| Setup Arrived Button
|--------------------------------------------------------------------------
*/

function setupArrivedAtShopButton() {

    const button =
        getArrivedAtShopButton();


    if (!button) {
        return;
    }


    if (
        button.dataset.arrivedReady ===
        "true"
    ) {
        return;
    }


    button.dataset.arrivedReady =
        "true";


    button.disabled =
        true;

    button.style.pointerEvents =
        "none";

    button.setAttribute(
        "aria-disabled",
        "true"
    );


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();
            event.stopPropagation();


            if (
                button.disabled ||
                arrivedAtShop ||
                arrivedAtShopRequestInProgress
            ) {
                return;
            }


            riderArrivedAtShop();
        }
    );


    button.addEventListener(
        "touchend",
        function (event) {

            if (
                button.disabled ||
                arrivedAtShop ||
                arrivedAtShopRequestInProgress
            ) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            riderArrivedAtShop();

        },
        {
            passive: false
        }
    );
}


/*
|--------------------------------------------------------------------------
| Rider Arrived At Shop Request
|--------------------------------------------------------------------------
*/

async function riderArrivedAtShop() {

    if (
        arrivedAtShop ||
        arrivedAtShopRequestInProgress
    ) {
        return;
    }


    if (
        currentDeliveryStatus !==
        "rider_assigned"
    ) {
        return;
    }


    if (!currentRiderPosition) {
        return;
    }


    const distance =
        getDistanceToPickup();


    if (
        !Number.isFinite(distance) ||
        distance >
            ARRIVED_SHOP_DISTANCE
    ) {

        updateArrivedAtShopButton(
            distance
        );

        return;
    }


    const identifiers =
        getDeliveryIdentifier();


    if (
        !identifiers.deliveryId
    ) {

        console.error(
            "[ARRIVED] Delivery ID unavailable."
        );

        return;
    }


    const csrfToken =
        getCsrfToken();


    if (!csrfToken) {
        return;
    }


    arrivedAtShopRequestInProgress =
        true;


    const button =
        getArrivedAtShopButton();


    const heading =
        button?.querySelector(
            "h4"
        );


    if (button) {

        button.disabled =
            true;

        button.style.pointerEvents =
            "none";

        button.classList.add(
            "loading"
        );
    }


    if (heading) {
        heading.textContent =
            "Updating...";
    }


    const payload = {

        delivery_number:
            identifiers.deliveryNumber,

        status:
            "rider_arrived_shop",

        latitude:
            currentRiderPosition.lat(),

        longitude:
            currentRiderPosition.lng(),

        distance:
            Math.round(distance)
    };


    try {

        const response =
            await fetch(
                `/rider/delivery/${encodeURIComponent(
                    identifiers.deliveryId
                )}/arrived-shop`,
                {

                    method:
                        "POST",

                    headers: {

                        "Content-Type":
                            "application/json",

                        "Accept":
                            "application/json",

                        "X-CSRF-TOKEN":
                            csrfToken,

                        "X-Requested-With":
                            "XMLHttpRequest"
                    },

                    credentials:
                        "same-origin",

                    body:
                        JSON.stringify(
                            payload
                        )
                }
            );


        let result = null;


        try {
            result =
                await response.json();
        } catch (error) {
            result = null;
        }


        if (
            !response.ok ||
            !result?.success
        ) {

            throw new Error(
                result?.message ||
                `Request failed with status ${response.status}`
            );
        }


        arrivedAtShop =
            true;


        arrivedAtShopRequestInProgress =
            false;


        setDeliveryStatus(
            result.status ||
            "rider_arrived_shop"
        );


        if (button) {

            button.classList.remove(
                "loading"
            );

            button.classList.add(
                "active"
            );

            button.classList.add(
                "arrived"
            );
        }


        if (heading) {
            heading.textContent =
                "Arrived";
        }


    } catch (error) {

        console.error(
            "[ARRIVED] Failed:",
            error
        );


        arrivedAtShopRequestInProgress =
            false;


        const latestDistance =
            getDistanceToPickup();


        updateArrivedAtShopButton(
            latestDistance
        );


        if (button) {
            button.classList.remove(
                "loading"
            );
        }


        if (heading) {
            heading.textContent =
                "Arrived";
        }
    }
}


/*
|--------------------------------------------------------------------------
| PICKUP
|--------------------------------------------------------------------------
*/

function getPickupButton() {

    return getElement(
        "pickupBtn"
    );
}


/*
|--------------------------------------------------------------------------
| Pickup Button UI
|--------------------------------------------------------------------------
*/

function updatePickupButton() {

    const button =
        getPickupButton();


    if (!button) {
        return;
    }


    const heading =
        button.querySelector("h4");


    if (
        currentDeliveryStatus !==
        "dispatched"
    ) {

        button.disabled =
            true;

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        return;
    }


    if (pickupRequestInProgress) {

        button.disabled =
            true;

        button.classList.add(
            "loading"
        );

        button.style.pointerEvents =
            "none";

        if (heading) {
            heading.textContent =
                "Updating...";
        }

        return;
    }


    button.disabled =
        false;

    button.style.pointerEvents =
        "auto";

    button.setAttribute(
        "aria-disabled",
        "false"
    );

    if (heading) {
        heading.textContent =
            "Pickup";
    }
}


/*
|--------------------------------------------------------------------------
| Setup Pickup Button
|--------------------------------------------------------------------------
*/

function setupPickupButton() {

    const button =
        getPickupButton();


    if (!button) {

        console.warn(
            "[PICKUP] #pickupBtn not found."
        );

        return;
    }


    if (
        button.dataset.pickupReady ===
        "true"
    ) {
        return;
    }


    button.dataset.pickupReady =
        "true";


    button.disabled =
        true;


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();
            event.stopPropagation();


            if (
                button.disabled ||
                pickupRequestInProgress
            ) {
                return;
            }


            pickupDelivery();
        }
    );


    button.addEventListener(
        "touchend",
        function (event) {

            if (
                button.disabled ||
                pickupRequestInProgress
            ) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            pickupDelivery();

        },
        {
            passive: false
        }
    );
}


/*
|--------------------------------------------------------------------------
| Pickup Delivery
|--------------------------------------------------------------------------
*/

async function pickupDelivery() {

    if (
        pickupRequestInProgress
    ) {
        return;
    }


    if (
        currentDeliveryStatus !==
        "dispatched"
    ) {

        console.warn(
            "[PICKUP] Invalid delivery status:",
            currentDeliveryStatus
        );

        return;
    }


    const identifiers =
        getDeliveryIdentifier();


    if (!identifiers.deliveryId) {

        console.error(
            "[PICKUP] Delivery ID unavailable."
        );

        return;
    }


    const csrfToken =
        getCsrfToken();


    if (!csrfToken) {

        console.error(
            "[PICKUP] CSRF token unavailable."
        );

        return;
    }


    pickupRequestInProgress =
        true;


    const button =
        getPickupButton();


    const heading =
        button?.querySelector(
            "h4"
        );


    updatePickupButton();


    if (heading) {
        heading.textContent =
            "Updating...";
    }


    const payload = {

        delivery_number:
            identifiers.deliveryNumber,

        status:
            "picked_up"
    };


    if (currentRiderPosition) {

        payload.latitude =
            currentRiderPosition.lat();

        payload.longitude =
            currentRiderPosition.lng();
    }


    try {

        const response =
            await fetch(
                `/rider/delivery/${encodeURIComponent(
                    identifiers.deliveryId
                )}/pickup`,
                {

                    method:
                        "POST",

                    headers: {

                        "Content-Type":
                            "application/json",

                        "Accept":
                            "application/json",

                        "X-CSRF-TOKEN":
                            csrfToken,

                        "X-Requested-With":
                            "XMLHttpRequest"
                    },

                    credentials:
                        "same-origin",

                    body:
                        JSON.stringify(
                            payload
                        )
                }
            );


        let result = null;


        try {
            result =
                await response.json();
        } catch (error) {
            result = null;
        }


        if (
            !response.ok ||
            !result?.success
        ) {

            throw new Error(
                result?.message ||
                `Request failed with status ${response.status}`
            );
        }


        pickupRequestInProgress =
            false;


        /*
         * This changes:
         *
         * dispatched
         *      ↓
         * picked_up
         *
         * and automatically shows START.
         */
        setDeliveryStatus(
            result.status ||
            "picked_up"
        );


        console.log(
            "[PICKUP] Delivery picked up successfully.",
            result
        );


    } catch (error) {

        console.error(
            "[PICKUP] Failed to pickup delivery:",
            error
        );


        pickupRequestInProgress =
            false;


        updatePickupButton();
    }
}


/*
|--------------------------------------------------------------------------
| START DELIVERY
|--------------------------------------------------------------------------
*/

function getStartDeliveryButton() {

    return getElement(
        "startBtn"
    );
}


/*
|--------------------------------------------------------------------------
| Start Button UI
|--------------------------------------------------------------------------
*/

function updateStartDeliveryButton() {

    const button =
        getStartDeliveryButton();


    if (!button) {
        return;
    }


    const heading =
        button.querySelector("h4");


    if (
        currentDeliveryStatus !==
        "picked_up"
    ) {

        button.disabled =
            true;

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        return;
    }


    if (
        startDeliveryRequestInProgress
    ) {

        button.disabled =
            true;

        button.classList.add(
            "loading"
        );

        button.style.pointerEvents =
            "none";

        if (heading) {
            heading.textContent =
                "Starting...";
        }

        return;
    }


    button.disabled =
        false;

    button.style.pointerEvents =
        "auto";

    button.setAttribute(
        "aria-disabled",
        "false"
    );

    if (heading) {
        heading.textContent =
            "Start";
    }
}


/*
|--------------------------------------------------------------------------
| Setup Start Delivery Button
|--------------------------------------------------------------------------
*/

function setupStartDeliveryButton() {

    const button =
        getStartDeliveryButton();


    if (!button) {

        console.warn(
            "[START DELIVERY] #startBtn not found."
        );

        return;
    }


    if (
        button.dataset.startReady ===
        "true"
    ) {
        return;
    }


    button.dataset.startReady =
        "true";


    button.disabled =
        true;


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();
            event.stopPropagation();


            if (
                button.disabled ||
                startDeliveryRequestInProgress
            ) {
                return;
            }


            startDelivery();
        }
    );


    button.addEventListener(
        "touchend",
        function (event) {

            if (
                button.disabled ||
                startDeliveryRequestInProgress
            ) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            startDelivery();

        },
        {
            passive: false
        }
    );
}


/*
|--------------------------------------------------------------------------
| Start Delivery
|--------------------------------------------------------------------------
*/

async function startDelivery() {

    if (
        startDeliveryRequestInProgress
    ) {
        return;
    }


    if (
        currentDeliveryStatus !==
        "picked_up"
    ) {

        console.warn(
            "[START DELIVERY] Invalid status:",
            currentDeliveryStatus
        );

        return;
    }


    const identifiers =
        getDeliveryIdentifier();


    if (!identifiers.deliveryId) {

        console.error(
            "[START DELIVERY] Delivery ID unavailable."
        );

        return;
    }


    const csrfToken =
        getCsrfToken();


    if (!csrfToken) {

        console.error(
            "[START DELIVERY] CSRF token unavailable."
        );

        return;
    }


    startDeliveryRequestInProgress =
        true;


    const button =
        getStartDeliveryButton();


    const heading =
        button?.querySelector(
            "h4"
        );


    if (button) {

        button.disabled =
            true;

        button.classList.add(
            "loading"
        );

        button.style.pointerEvents =
            "none";
    }


    if (heading) {
        heading.textContent =
            "Starting...";
    }


    const payload = {

        delivery_number:
            identifiers.deliveryNumber,

        status:
            "on_transit"
    };


    if (currentRiderPosition) {

        payload.latitude =
            currentRiderPosition.lat();

        payload.longitude =
            currentRiderPosition.lng();
    }


    try {

        const response =
            await fetch(
                `/rider/delivery/${encodeURIComponent(
                    identifiers.deliveryId
                )}/start`,
                {

                    method:
                        "POST",

                    headers: {

                        "Content-Type":
                            "application/json",

                        "Accept":
                            "application/json",

                        "X-CSRF-TOKEN":
                            csrfToken,

                        "X-Requested-With":
                            "XMLHttpRequest"
                    },

                    credentials:
                        "same-origin",

                    body:
                        JSON.stringify(
                            payload
                        )
                }
            );


        let result = null;


        try {

            result =
                await response.json();

        } catch (error) {

            result = null;
        }


        if (
            !response.ok ||
            !result?.success
        ) {

            throw new Error(
                result?.message ||
                `Request failed with status ${response.status}`
            );
        }


        startDeliveryRequestInProgress =
            false;


        /*
         * Change status to on_transit.
         */
        setDeliveryStatus(
            result.status ||
            "on_transit"
        );


        if (button) {

            button.classList.remove(
                "loading"
            );
        }


        /*
         * Switch route to customer.
         */
        switchToDropoffRoute();


        /*
         * Fit rider and customer into
         * the device viewport before
         * navigation camera takes over.
         */
        if (!isNavigating) {

            fitRiderAndDropoffInView();
        }


        console.log(
            "[START DELIVERY] Delivery started successfully.",
            result
        );


    } catch (error) {

        console.error(
            "[START DELIVERY] Failed to start delivery:",
            error
        );


        startDeliveryRequestInProgress =
            false;


        if (button) {

            button.classList.remove(
                "loading"
            );
        }


        updateStartDeliveryButton();
    }
}


/*
|--------------------------------------------------------------------------
| DELIVER
|--------------------------------------------------------------------------
*/

function getDeliverButton() {

    return getElement(
        "deliverBtn"
    );
}


/*
|--------------------------------------------------------------------------
| Distance To Customer
|--------------------------------------------------------------------------
*/

function getDistanceToDropoff() {

    if (
        !currentRiderPosition ||
        !dropoffLatLng
    ) {
        return null;
    }


    if (
        !google.maps.geometry ||
        !google.maps.geometry.spherical
    ) {

        console.warn(
            "[DELIVER] Google geometry library unavailable."
        );

        return null;
    }


    return google.maps.geometry.spherical
        .computeDistanceBetween(
            toLatLng(
                currentRiderPosition
            ),
            dropoffLatLng
        );
}


/*
|--------------------------------------------------------------------------
| Deliver Button UI
|--------------------------------------------------------------------------
*/

function updateDeliverButton(
    distance = null
) {

    const button =
        getDeliverButton();


    if (!button) {
        return;
    }


    const heading =
        button.querySelector(
            "h4"
        );


    if (
        currentDeliveryStatus !==
        "on_transit"
    ) {

        button.disabled =
            true;

        button.classList.remove(
            "active"
        );

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        if (heading) {
            heading.textContent =
                "Deliver";
        }

        return;
    }


    if (
        deliverRequestInProgress
    ) {

        button.disabled =
            true;

        button.classList.add(
            "loading"
        );

        button.style.pointerEvents =
            "none";

        if (heading) {
            heading.textContent =
                "Updating...";
        }

        return;
    }


    if (!Number.isFinite(distance)) {

        button.disabled =
            true;

        button.classList.remove(
            "active"
        );

        button.style.pointerEvents =
            "none";

        button.setAttribute(
            "aria-disabled",
            "true"
        );

        if (heading) {
            heading.textContent =
                "Deliver";
        }

        return;
    }


    /*
     * Rider is within 50 metres.
     */
    if (
        distance <=
        DELIVER_DISTANCE
    ) {

        button.disabled =
            false;

        button.classList.add(
            "active"
        );

        button.style.pointerEvents =
            "auto";

        button.setAttribute(
            "aria-disabled",
            "false"
        );

        if (heading) {
            heading.textContent =
                "Deliver";
        }

        return;
    }


    /*
     * Rider is still too far away.
     */
    button.disabled =
        true;

    button.classList.remove(
        "active"
    );

    button.style.pointerEvents =
        "none";

    button.setAttribute(
        "aria-disabled",
        "true"
    );

    if (heading) {
        heading.textContent =
            "Deliver";
    }
}


/*
|--------------------------------------------------------------------------
| Check Rider Delivery Distance
|--------------------------------------------------------------------------
*/

function checkRiderDeliveryDistance() {

    if (
        currentDeliveryStatus !==
        "on_transit"
    ) {

        updateDeliverButton();

        return;
    }


    const distance =
        getDistanceToDropoff();


    if (!Number.isFinite(distance)) {

        updateDeliverButton(
            null
        );

        return;
    }


    console.log(
        "[DELIVER] Distance to customer:",
        `${Math.round(distance)}m`
    );


    updateDeliverButton(
        distance
    );
}


/*
|--------------------------------------------------------------------------
| Setup Deliver Button
|--------------------------------------------------------------------------
*/

function setupDeliverButton() {

    const button =
        getDeliverButton();


    if (!button) {

        console.warn(
            "[DELIVER] #deliverBtn not found."
        );

        return;
    }


    if (
        button.dataset.deliverReady ===
        "true"
    ) {
        return;
    }


    button.dataset.deliverReady =
        "true";


    button.disabled =
        true;


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();
            event.stopPropagation();


            if (
                button.disabled ||
                deliverRequestInProgress
            ) {
                return;
            }


            completeDelivery();
        }
    );


    button.addEventListener(
        "touchend",
        function (event) {

            if (
                button.disabled ||
                deliverRequestInProgress
            ) {
                return;
            }


            event.preventDefault();
            event.stopPropagation();


            completeDelivery();

        },
        {
            passive: false
        }
    );
}


/*
|--------------------------------------------------------------------------
| Complete Delivery
|--------------------------------------------------------------------------
*/

async function completeDelivery() {

    if (
        deliverRequestInProgress
    ) {
        return;
    }


    if (
        currentDeliveryStatus !==
        "on_transit"
    ) {
        return;
    }


    const distance =
        getDistanceToDropoff();


    if (
        !Number.isFinite(distance) ||
        distance >
            DELIVER_DISTANCE
    ) {

        console.warn(
            "[DELIVER] Rider is not within 50m of customer.",
            distance
        );

        updateDeliverButton(
            distance
        );

        return;
    }


    const identifiers =
        getDeliveryIdentifier();


    if (!identifiers.deliveryId) {

        console.error(
            "[DELIVER] Delivery ID unavailable."
        );

        return;
    }


    const csrfToken =
        getCsrfToken();


    if (!csrfToken) {

        console.error(
            "[DELIVER] CSRF token unavailable."
        );

        return;
    }


    deliverRequestInProgress =
        true;


    const button =
        getDeliverButton();


    const heading =
        button?.querySelector(
            "h4"
        );


    updateDeliverButton(
        distance
    );


    if (heading) {
        heading.textContent =
            "Updating...";
    }


    const payload = {

        delivery_number:
            identifiers.deliveryNumber,

        status:
            "delivered",

        distance:
            Math.round(distance)
    };


    if (currentRiderPosition) {

        payload.latitude =
            currentRiderPosition.lat();

        payload.longitude =
            currentRiderPosition.lng();
    }


    try {

        /*
         * Change this endpoint if your
         * Laravel deliver route has another URI.
         */
        const response =
            await fetch(
                `/rider/delivery/${encodeURIComponent(
                    identifiers.deliveryId
                )}/deliver`,
                {

                    method:
                        "POST",

                    headers: {

                        "Content-Type":
                            "application/json",

                        "Accept":
                            "application/json",

                        "X-CSRF-TOKEN":
                            csrfToken,

                        "X-Requested-With":
                            "XMLHttpRequest"
                    },

                    credentials:
                        "same-origin",

                    body:
                        JSON.stringify(
                            payload
                        )
                }
            );


        let result = null;


        try {
            result =
                await response.json();
        } catch (error) {
            result = null;
        }


        if (
            !response.ok ||
            !result?.success
        ) {

            throw new Error(
                result?.message ||
                `Request failed with status ${response.status}`
            );
        }


        deliverRequestInProgress =
            false;


        setDeliveryStatus(
            result.status ||
            "delivered"
        );


        console.log(
            "[DELIVER] Delivery completed successfully.",
            result
        );


    } catch (error) {

        console.error(
            "[DELIVER] Failed to complete delivery:",
            error
        );


        deliverRequestInProgress =
            false;


        updateDeliverButton(
            getDistanceToDropoff()
        );
    }
}


/*
|--------------------------------------------------------------------------
| Fit Rider + Customer Into Device View
|--------------------------------------------------------------------------
*/

function fitRiderAndDropoffInView() {

    if (
        !directionsMap ||
        !currentRiderPosition ||
        !dropoffLatLng
    ) {
        return;
    }


    /*
     * Do not interfere with active navigation.
     */
    if (isNavigating) {
        return;
    }


    const bounds =
        new google.maps.LatLngBounds();


    bounds.extend(
        currentRiderPosition
    );

    bounds.extend(
        dropoffLatLng
    );


    /*
     * Padding is deliberately larger at
     * the bottom because the rider's
     * action panel occupies that area.
     */
    directionsMap.fitBounds(
        bounds,
        {
            top: 70,
            right: 35,
            bottom: 260,
            left: 35
        }
    );


    console.log(
        "[MAP] Rider and customer fitted into viewport."
    );
}


/*
|--------------------------------------------------------------------------
| Switch Route To Drop-off
|--------------------------------------------------------------------------
*/

function switchToDropoffRoute() {

    if (
        !directionsService ||
        !currentRiderPosition ||
        !dropoffLatLng
    ) {

        console.warn(
            "[ROUTE] Drop-off route unavailable."
        );

        return;
    }


    /*
     * Hide pickup marker once rider has
     * started travelling to customer.
     */
    if (pickupMarker) {

        pickupMarker.map =
            null;
    }


    if (pickupInfoWindow) {

        pickupInfoWindow.close();
    }


    /*
     * Keep customer visible.
     */
    if (dropoffMarker) {

        dropoffMarker.map =
            directionsMap;
    }


    drawRoute(
        currentRiderPosition,
        dropoffLatLng,
        isNavigating,
        true
    );


    /*
     * Fit both points only when navigation
     * is not active.
     */
    if (!isNavigating) {

        setTimeout(
            () => {

                fitRiderAndDropoffInView();

            },
            100
        );
    }
}


/*
|--------------------------------------------------------------------------
| Camera Easing
|--------------------------------------------------------------------------
*/

function cameraEasing(t) {

    return t < 0.5
        ? 4 * t * t * t
        : 1 -
          Math.pow(
              -2 * t + 2,
              3
          ) / 2;
}


/*
|--------------------------------------------------------------------------
| Camera Transition
|--------------------------------------------------------------------------
*/

function stopCameraTransition() {

    if (cameraAnimationFrameId) {

        cancelAnimationFrame(
            cameraAnimationFrameId
        );

        cameraAnimationFrameId = null;
    }


    cameraTransitionInProgress =
        false;
}


function getShortestHeadingDelta(
    from,
    to
) {

    return (
        (to - from + 540) %
        360
    ) - 180;
}


function animateCameraTransition({
    fromCenter,
    toCenter,
    fromZoom,
    toZoom,
    fromTilt,
    toTilt,
    fromHeading,
    toHeading,
    duration =
        NAVIGATION_CAMERA_TRANSITION,
    onComplete = null
}) {

    if (!directionsMap) {
        return;
    }


    stopCameraTransition();


    cameraTransitionInProgress =
        true;


    const startTime =
        performance.now();


    const headingDelta =
        getShortestHeadingDelta(
            fromHeading,
            toHeading
        );


    function animate(currentTime) {

        if (!directionsMap) {

            stopCameraTransition();

            return;
        }


        const elapsed =
            currentTime -
            startTime;


        const progress =
            Math.min(
                elapsed / duration,
                1
            );


        const eased =
            cameraEasing(
                progress
            );


        const lat =
            fromCenter.lat() +
            (
                toCenter.lat() -
                fromCenter.lat()
            ) *
            eased;


        const lng =
            fromCenter.lng() +
            (
                toCenter.lng() -
                fromCenter.lng()
            ) *
            eased;


        const zoom =
            fromZoom +
            (
                toZoom -
                fromZoom
            ) *
            eased;


        const tilt =
            fromTilt +
            (
                toTilt -
                fromTilt
            ) *
            eased;


        const heading =
            normalizeHeading(
                fromHeading +
                headingDelta *
                eased
            );


        directionsMap.moveCamera({

            center: {
                lat,
                lng
            },

            zoom,

            tilt,

            heading
        });


        if (progress < 1) {

            cameraAnimationFrameId =
                requestAnimationFrame(
                    animate
                );

            return;
        }


        cameraAnimationFrameId =
            null;


        cameraTransitionInProgress =
            false;


        directionsMap.moveCamera({

            center:
                toCenter,

            zoom:
                toZoom,

            tilt:
                toTilt,

            heading:
                normalizeHeading(
                    toHeading
                )
        });


        if (
            typeof onComplete ===
            "function"
        ) {

            onComplete();
        }
    }


    cameraAnimationFrameId =
        requestAnimationFrame(
            animate
        );
}


/*
|--------------------------------------------------------------------------
| Navigation Camera Center
|--------------------------------------------------------------------------
*/

function getNavigationCameraCenter(
    position,
    heading
) {

    const normalizedHeading =
        normalizeHeading(
            heading
        );


    const radians =
        normalizedHeading *
        Math.PI /
        180;


    return new google.maps.LatLng(

        position.lat() -
            Math.cos(radians) *
            CAMERA_OFFSET,

        position.lng() -
            Math.sin(radians) *
            CAMERA_OFFSET
    );
}


/*
|--------------------------------------------------------------------------
| Initialize Delivery Directions
|--------------------------------------------------------------------------
*/

window.initDeliveryDirections =
async function () {

    if (isInitialized) {

        console.warn(
            "[DELIVERY MAP] Already initialized."
        );

        return;
    }


    console.log(
        "[DELIVERY MAP] Initializing..."
    );


    const mapElement =
        getElement("map");


    if (!mapElement) {

        console.error(
            "[DELIVERY MAP] #map element not found."
        );

        return;
    }


    currentDeliveryStatus =
        normalizeDeliveryStatus(
            mapElement.dataset.deliveryStatus
        );


    console.log(
        "[DELIVERY MAP] Initial delivery status:",
        currentDeliveryStatus
    );


    const riderLat =
        parseCoordinate(
            mapElement.dataset.riderLat
        );


    const riderLng =
        parseCoordinate(
            mapElement.dataset.riderLng
        );


    const shopLat =
        parseCoordinate(
            mapElement.dataset.shopLat
        );


    const shopLng =
        parseCoordinate(
            mapElement.dataset.shopLng
        );


    const customerLat =
        parseCoordinate(
            mapElement.dataset.dropoffLat ||
            mapElement.dataset.customerLat
        );


    const customerLng =
        parseCoordinate(
            mapElement.dataset.dropoffLng ||
            mapElement.dataset.customerLng
        );


    if (
        riderLat === null ||
        riderLng === null ||
        shopLat === null ||
        shopLng === null ||
        customerLat === null ||
        customerLng === null
    ) {

        console.error(
            "[DELIVERY MAP] Invalid coordinates.",
            {
                riderLat,
                riderLng,
                shopLat,
                shopLng,
                customerLat,
                customerLng
            }
        );


        updateEtaDisplay(null);

        return;
    }


    const rider = {

        lat:
            riderLat,

        lng:
            riderLng
    };


    const pickup = {

        lat:
            shopLat,

        lng:
            shopLng
    };


    const dropoff = {

        lat:
            customerLat,

        lng:
            customerLng
    };


    currentRiderPosition =
        new google.maps.LatLng(
            rider.lat,
            rider.lng
        );


    dropoffLatLng =
        new google.maps.LatLng(
            dropoff.lat,
            dropoff.lng
        );


    /*
     * Once rider has already arrived,
     * pickup or later, the rider is no
     * longer considered en route to shop.
     */
    arrivedAtShop =
        currentDeliveryStatus ===
        "rider_arrived_shop" ||
        currentDeliveryStatus ===
        "waiting_dispatch" ||
        currentDeliveryStatus ===
        "dispatched" ||
        currentDeliveryStatus ===
        "picked_up" ||
        currentDeliveryStatus ===
        "on_transit" ||
        currentDeliveryStatus ===
        "delivered" ||
        currentDeliveryStatus ===
        "completed";


    arrivedAtShopRequestInProgress =
        false;


    /*
     * Load Google Maps libraries.
     */
    try {

        await Promise.all([

            google.maps.importLibrary(
                "maps"
            ),

            google.maps.importLibrary(
                "geometry"
            ),

            google.maps.importLibrary(
                "marker"
            )

        ]);

    } catch (error) {

        console.error(
            "[DELIVERY MAP] Failed to load Google Maps libraries.",
            error
        );

        return;
    }


    /*
     * Map options.
     */
    const mapOptions = {

        center:
            rider,

        zoom:
            NORMAL_ZOOM,

        mapId:
            DELIVERY_MAP_ID,

        mapTypeControl:
            false,

        streetViewControl:
            false,

        fullscreenControl:
            false,

        clickableIcons:
            false,

        gestureHandling:
            "greedy"
    };


    if (
        google.maps.RenderingType &&
        google.maps.RenderingType.VECTOR
    ) {

        mapOptions.renderingType =
            google.maps.RenderingType.VECTOR;
    }


    /*
     * Create map.
     */
    directionsMap =
        new google.maps.Map(
            mapElement,
            mapOptions
        );


    /*
     * Directions.
     */
    directionsService =
        new google.maps.DirectionsService();


    directionsRenderer =
        new google.maps.DirectionsRenderer({

            map:
                directionsMap,

            suppressMarkers:
                true,

            preserveViewport:
                true,

            polylineOptions: {

                strokeColor:
                    "#f25112",

                strokeOpacity:
                    1,

                strokeWeight:
                    6
            }
        });


    /*
     * Advanced marker library.
     */
    const {
        AdvancedMarkerElement
    } =
        await google.maps.importLibrary(
            "marker"
        );


    /*
     * Rider marker.
     */
    riderMarker =
        new google.maps.Marker({

            position:
                rider,

            map:
                directionsMap,

            title:
                "Your Location",

            zIndex:
                999,

            icon: {

                url:
                    RIDER_ICON,

                scaledSize:
                    new google.maps.Size(
                        42,
                        42
                    ),

                anchor:
                    new google.maps.Point(
                        21,
                        21
                    )
            }
        });


    /*
     * Pickup marker.
     */
    const storePinContainer =
        document.createElement(
            "div"
        );


    Object.assign(
        storePinContainer.style,
        {

            color:
                "#f25112",

            fontSize:
                "20px",

            filter:
                "drop-shadow(0px 2px 4px rgba(0,0,0,0.3))"
        }
    );


    const storeIcon =
        document.createElement(
            "i"
        );


    storeIcon.className =
        "fa-solid fa-store";


    storePinContainer.appendChild(
        storeIcon
    );


    pickupMarker =
        new AdvancedMarkerElement({

            position:
                pickup,

            map:
                directionsMap,

            title:
                "Pickup Point",

            content:
                storePinContainer,

            zIndex:
                998
        });


    /*
     * Customer marker.
     */
    const customerPinContainer =
        document.createElement(
            "div"
        );


    Object.assign(
        customerPinContainer.style,
        {

            color:
                "#111827",

            fontSize:
                "20px",

            filter:
                "drop-shadow(0px 2px 4px rgba(0,0,0,0.3))"
        }
    );


    const customerIcon =
        document.createElement(
            "i"
        );


    customerIcon.className =
        "fa-solid fa-location-dot";


    customerPinContainer.appendChild(
        customerIcon
    );


    dropoffMarker =
        new AdvancedMarkerElement({

            position:
                dropoff,

            map:
                directionsMap,

            title:
                "Customer",

            content:
                customerPinContainer,

            zIndex:
                997
        });


    /*
     * Info windows.
     */
    riderInfoWindow =
        new google.maps.InfoWindow({

            content: `
                <div style="
                    color:#6b7280;
                    font-weight:600;
                    font-size:13px;
                    padding:1px 2px;
                    white-space:nowrap;
                ">
                    Your Location
                </div>
            `
        });


    pickupInfoWindow =
        new google.maps.InfoWindow({

            content: `
                <div style="
                    color:#6b7280;
                    font-weight:600;
                    font-size:13px;
                    padding:1px 2px;
                    white-space:nowrap;
                ">
                    Pickup Point
                </div>
            `
        });


    dropoffInfoWindow =
        new google.maps.InfoWindow({

            content: `
                <div style="
                    color:#6b7280;
                    font-weight:600;
                    font-size:13px;
                    padding:1px 2px;
                    white-space:nowrap;
                ">
                    Customer
                </div>
            `
        });


    riderInfoWindow.open({

        anchor:
            riderMarker,

        map:
            directionsMap
    });


    pickupInfoWindow.open({

        anchor:
            pickupMarker,

        map:
            directionsMap
    });


    /*
     * Customer info window only becomes
     * visible when travelling to customer.
     */
    if (
        currentDeliveryStatus ===
            "on_transit"
    ) {

        pickupMarker.map =
            null;

        pickupInfoWindow.close();

        dropoffInfoWindow.open({

            anchor:
                dropoffMarker,

            map:
                directionsMap
        });

    } else {

        dropoffMarker.map =
            null;

        dropoffInfoWindow.close();
    }


    /*
     * Controls.
     */
    setupNavigationButton();

    setupGoogleMapsButton();

    setupDeliveryPanelControls();

    setupPickupContactButton();

    setupDeliveryStatusButtons();


    /*
     * Initial distance checks.
     */
    checkRiderArrivalDistance();

    checkRiderDeliveryDistance();


    /*
     * Initial route.
     */
    if (
        currentDeliveryStatus ===
        "on_transit"
    ) {

        switchToDropoffRoute();

    } else {

        drawRoute(
            rider,
            pickup,
            false,
            true
        );
    }


    setNavigationButtonActive(
        false
    );


    scheduleEtaRefresh();


    isInitialized =
        true;


    console.log(
        "[DELIVERY MAP] Initialization complete."
    );
};


/*
|--------------------------------------------------------------------------
| Navigation Buttons
|--------------------------------------------------------------------------
*/

function setupNavigationButton() {

    const startButton =
        getElement(
            "startNavigationBtn"
        );


    const stopButton =
        getElement(
            "stopNavigationBtn"
        );


    if (
        startButton &&
        startButton.dataset.navigationReady !==
            "true"
    ) {

        startButton.dataset.navigationReady =
            "true";


        startButton.addEventListener(
            "click",
            event => {

                event.preventDefault();
                event.stopPropagation();


                if (!isNavigating) {

                    startNavigation();
                }
            }
        );
    }


    if (
        stopButton &&
        stopButton.dataset.navigationReady !==
            "true"
    ) {

        stopButton.dataset.navigationReady =
            "true";


        stopButton.addEventListener(
            "click",
            event => {

                event.preventDefault();
                event.stopPropagation();


                if (isNavigating) {

                    stopNavigation();
                }
            }
        );
    }
}


/*
|--------------------------------------------------------------------------
| Start Navigation
|--------------------------------------------------------------------------
*/

function startNavigation() {

    if (
        !directionsMap ||
        !directionsService ||
        !currentRiderPosition
    ) {

        console.error(
            "[NAVIGATION] Required map data unavailable."
        );

        return;
    }


    /*
     * Destination changes based on status.
     */
    const destination =
        currentDeliveryStatus ===
        "on_transit"

            ? dropoffLatLng

            : getPickupPosition();


    if (!destination) {

        console.error(
            "[NAVIGATION] Destination unavailable."
        );

        return;
    }


    const rider =
        currentRiderPosition;


    const heading =
        getRouteHeading(
            rider
        );


    const currentCenter =
        directionsMap.getCenter() ||
        rider;


    const currentZoom =
        directionsMap.getZoom() ||
        NORMAL_ZOOM;


    const currentTilt =
        typeof directionsMap.getTilt ===
        "function"

            ? directionsMap.getTilt() || 0

            : 0;


    const currentHeading =
        typeof directionsMap.getHeading ===
        "function"

            ? directionsMap.getHeading() || 0

            : 0;


    isNavigating =
        true;


    setNavigationButtonActive(
        true
    );


    animateCameraTransition({

        fromCenter:
            currentCenter,

        toCenter:
            getNavigationCameraCenter(
                rider,
                heading
            ),

        fromZoom:
            currentZoom,

        toZoom:
            NAVIGATION_ZOOM,

        fromTilt:
            currentTilt,

        toTilt:
            NAVIGATION_TILT,

        fromHeading:
            currentHeading,

        toHeading:
            heading,

        duration:
            NAVIGATION_CAMERA_TRANSITION
    });


    drawRoute(

        {
            lat:
                rider.lat(),

            lng:
                rider.lng()
        },

        destination,

        true,

        true
    );
}


/*
|--------------------------------------------------------------------------
| Stop Navigation
|--------------------------------------------------------------------------
*/

function stopNavigation() {

    if (!directionsMap) {

        isNavigating =
            false;

        setNavigationButtonActive(
            false
        );

        return;
    }


    isNavigating =
        false;


    setNavigationButtonActive(
        false
    );


    stopCameraTransition();


    if (animationFrameId) {

        cancelAnimationFrame(
            animationFrameId
        );

        animationFrameId =
            null;
    }


    const currentCenter =
        directionsMap.getCenter() ||
        currentRiderPosition;


    const currentZoom =
        directionsMap.getZoom() ||
        NAVIGATION_ZOOM;


    const currentTilt =
        typeof directionsMap.getTilt ===
        "function"

            ? directionsMap.getTilt() ||
              NAVIGATION_TILT

            : NAVIGATION_TILT;


    const currentHeading =
        typeof directionsMap.getHeading ===
        "function"

            ? directionsMap.getHeading() || 0

            : 0;


    const targetCenter =
        currentRiderPosition ||
        currentCenter;


    animateCameraTransition({

        fromCenter:
            currentCenter,

        toCenter:
            targetCenter,

        fromZoom:
            currentZoom,

        toZoom:
            NORMAL_ZOOM,

        fromTilt:
            currentTilt,

        toTilt:
            0,

        fromHeading:
            currentHeading,

        toHeading:
            0,

        duration:
            NAVIGATION_CAMERA_TRANSITION
    });


    /*
     * Refit rider/customer after navigation
     * stops if travelling to customer.
     */
    if (
        currentDeliveryStatus ===
        "on_transit"
    ) {

        setTimeout(
            () => {

                fitRiderAndDropoffInView();

            },
            NAVIGATION_CAMERA_TRANSITION + 50
        );
    }
}


/*
|--------------------------------------------------------------------------
| Navigation Button State
|--------------------------------------------------------------------------
*/

function setNavigationButtonActive(
    active
) {

    const startButton =
        getElement(
            "startNavigationBtn"
        );


    const stopButton =
        getElement(
            "stopNavigationBtn"
        );


    if (startButton) {

        startButton.style.display =
            active
                ? "none"
                : "inline-block";


        startButton.classList.toggle(
            "active",
            false
        );


        startButton.setAttribute(
            "aria-hidden",
            active
                ? "true"
                : "false"
        );


        if (!active) {

            startButton.setAttribute(
                "title",
                "Start navigation"
            );


            startButton.setAttribute(
                "aria-label",
                "Start navigation"
            );
        }
    }


    if (stopButton) {

        stopButton.style.display =
            active
                ? "inline-block"
                : "none";


        stopButton.classList.toggle(
            "active",
            active
        );


        stopButton.setAttribute(
            "aria-hidden",
            active
                ? "false"
                : "true"
        );


        if (active) {

            stopButton.setAttribute(
                "title",
                "Stop navigation"
            );


            stopButton.setAttribute(
                "aria-label",
                "Stop navigation"
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| Draw Route
|--------------------------------------------------------------------------
*/

function drawRoute(
    origin,
    destination,
    navigationMode = false,
    updateEta = true
) {

    if (
        !directionsService ||
        routeRequestInProgress
    ) {
        return;
    }


    if (!destination) {
        return;
    }


    routeRequestInProgress =
        true;


    directionsService.route(

        {

            origin,

            destination,

            travelMode:
                google.maps.TravelMode.DRIVING,

            drivingOptions: {

                departureTime:
                    new Date(),

                trafficModel:
                    google.maps.TrafficModel.BEST_GUESS
            }
        },


        (
            result,
            status
        ) => {

            routeRequestInProgress =
                false;


            if (
                status !== "OK" ||
                !result
            ) {

                console.error(
                    "[ROUTE] Directions failed:",
                    status
                );

                return;
            }


            const route =
                result.routes[0];


            const leg =
                route?.legs?.[0];


            if (!leg) {
                return;
            }


            directionsRenderer.setDirections(
                result
            );


            routePath =
                getDetailedRoutePath(
                    route
                );


            if (updateEta) {

                const duration =
                    leg.duration_in_traffic ||
                    leg.duration;


                if (duration) {

                    startEtaCountdown(
                        duration.value
                    );
                }
            }


            updateRouteInfo(
                leg
            );


            if (
                (
                    navigationMode ||
                    isNavigating
                ) &&
                !cameraTransitionInProgress
            ) {

                const position =
                    toLatLng(
                        origin
                    );


                const heading =
                    getRouteHeading(
                        position
                    );


                moveNavigationCamera(
                    position,
                    heading
                );
            }


            console.log(
                "[ROUTE] Route ready:",
                leg.distance?.text,
                leg.duration?.text
            );
        }
    );
}


/*
|--------------------------------------------------------------------------
| Detailed Route Path
|--------------------------------------------------------------------------
*/

function getDetailedRoutePath(
    route
) {

    if (!route) {
        return [];
    }


    const overview =
        route.overview_path ||
        [];


    const detailed =
        [];


    for (
        const leg of route.legs || []
    ) {

        for (
            const step of leg.steps || []
        ) {

            if (
                step.path?.length
            ) {

                detailed.push(
                    ...step.path
                );
            }
        }
    }


    return detailed.length >= 2
        ? detailed
        : overview;
}


/*
|--------------------------------------------------------------------------
| Route Information
|--------------------------------------------------------------------------
*/

function updateRouteInfo(
    leg
) {

    const info =
        getElement(
            "route-info"
        );


    if (!info) {
        return;
    }


    info.innerHTML = `
        <strong>
            ${leg.distance?.text || ""}
        </strong>
        <br>
        ${
            leg.duration_in_traffic?.text ||
            leg.duration?.text ||
            ""
        }
    `;
}


/*
|--------------------------------------------------------------------------
| Get Route Heading
|--------------------------------------------------------------------------
*/

function getRouteHeading(
    position
) {

    if (
        routePath.length < 2
    ) {
        return 0;
    }


    const pos =
        toLatLng(
            position
        );


    let closestIndex =
        0;


    let closestDistance =
        Infinity;


    for (
        let i = 0;
        i < routePath.length;
        i++
    ) {

        const distance =
            google.maps.geometry.spherical
                .computeDistanceBetween(
                    pos,
                    routePath[i]
                );


        if (
            distance <
            closestDistance
        ) {

            closestDistance =
                distance;

            closestIndex =
                i;
        }
    }


    let lookAheadIndex =
        closestIndex;


    let accumulatedDistance =
        0;


    for (
        let i = closestIndex;
        i < routePath.length - 1;
        i++
    ) {

        accumulatedDistance +=
            google.maps.geometry.spherical
                .computeDistanceBetween(
                    routePath[i],
                    routePath[i + 1]
                );


        lookAheadIndex =
            i + 1;


        if (
            accumulatedDistance >=
            LOOK_AHEAD_DISTANCE
        ) {
            break;
        }
    }


    if (
        lookAheadIndex ===
        closestIndex
    ) {

        if (
            closestIndex > 0
        ) {

            return google.maps.geometry.spherical
                .computeHeading(
                    routePath[
                        closestIndex - 1
                    ],
                    routePath[
                        closestIndex
                    ]
                );
        }


        return 0;
    }


    return google.maps.geometry.spherical
        .computeHeading(
            routePath[
                closestIndex
            ],
            routePath[
                lookAheadIndex
            ]
        );
}


/*
|--------------------------------------------------------------------------
| Normalize Heading
|--------------------------------------------------------------------------
*/

function normalizeHeading(
    heading
) {

    let value =
        Number(heading);


    if (
        !Number.isFinite(value)
    ) {
        value = 0;
    }


    return (
        value + 360
    ) % 360;
}


/*
|--------------------------------------------------------------------------
| Navigation Camera
|--------------------------------------------------------------------------
*/

function moveNavigationCamera(
    position,
    heading
) {

    if (
        !directionsMap ||
        cameraTransitionInProgress
    ) {
        return;
    }


    const renderingType =
        getMapRenderingType();


    if (
        google.maps.RenderingType &&
        renderingType !==
            google.maps.RenderingType.VECTOR
    ) {

        directionsMap.panTo(
            position
        );


        directionsMap.setZoom(
            NAVIGATION_ZOOM
        );


        return;
    }


    const normalizedHeading =
        normalizeHeading(
            heading
        );


    const center =
        getNavigationCameraCenter(
            position,
            normalizedHeading
        );


    directionsMap.moveCamera({

        center,

        zoom:
            NAVIGATION_ZOOM,

        heading:
            normalizedHeading,

        tilt:
            NAVIGATION_TILT
    });
}


/*
|--------------------------------------------------------------------------
| Update Rider Position
|--------------------------------------------------------------------------
*/

window.updateRiderPosition =
function (
    newLat,
    newLng,
    backendHeading = null,
    speed = 0
) {

    if (
        !riderMarker ||
        !directionsMap
    ) {
        return;
    }


    const latitude =
        parseCoordinate(
            newLat
        );


    const longitude =
        parseCoordinate(
            newLng
        );


    if (
        latitude === null ||
        longitude === null
    ) {

        console.error(
            "[RIDER] Invalid coordinates:",
            newLat,
            newLng
        );

        return;
    }


    const targetPosition =
        new google.maps.LatLng(
            latitude,
            longitude
        );


    if (
        !currentRiderPosition
    ) {

        currentRiderPosition =
            targetPosition;


        riderMarker.setPosition(
            targetPosition
        );


        checkRiderArrivalDistance();

        checkRiderDeliveryDistance();

        return;
    }


    const startPosition =
        currentRiderPosition;


    if (animationFrameId) {

        cancelAnimationFrame(
            animationFrameId
        );

        animationFrameId =
            null;
    }


    const startTime =
        performance.now();


    function animate(
        currentTime
    ) {

        const progress =
            Math.min(

                (
                    currentTime -
                    startTime
                ) /
                RIDER_ANIMATION_DURATION,

                1
            );


        const eased =
            progress < 0.5

                ? 2 *
                  progress *
                  progress

                : 1 -
                  Math.pow(
                      -2 *
                          progress +
                          2,
                      2
                  ) /
                  2;


        const lat =
            startPosition.lat() +
            (
                targetPosition.lat() -
                startPosition.lat()
            ) *
            eased;


        const lng =
            startPosition.lng() +
            (
                targetPosition.lng() -
                startPosition.lng()
            ) *
            eased;


        const position =
            new google.maps.LatLng(
                lat,
                lng
            );


        riderMarker.setPosition(
            position
        );


        currentRiderPosition =
            position;


        /*
         * Check BOTH arrival and delivery
         * distances continuously.
         */
        checkRiderArrivalDistance();

        checkRiderDeliveryDistance();


        if (
            isNavigating &&
            !cameraTransitionInProgress
        ) {

            /*
             * On transit, route heading is
             * automatically based on the
             * customer route.
             */
            moveNavigationCamera(

                position,

                getRouteHeading(
                    position
                )
            );
        }


        if (
            progress <
            1
        ) {

            animationFrameId =
                requestAnimationFrame(
                    animate
                );

        } else {

            animationFrameId =
                null;


            currentRiderPosition =
                targetPosition;


            riderMarker.setPosition(
                targetPosition
            );


            checkRiderArrivalDistance();

            checkRiderDeliveryDistance();
        }
    }


    animationFrameId =
        requestAnimationFrame(
            animate
        );
};


/*
|--------------------------------------------------------------------------
| Expose Navigation Controls
|--------------------------------------------------------------------------
*/

window.startDeliveryNavigation =
function () {

    startNavigation();
};


window.stopDeliveryNavigation =
function () {

    stopNavigation();
};


window.isDeliveryNavigationActive =
function () {

    return isNavigating;
};


/*
|--------------------------------------------------------------------------
| Expose Delivery Status
|--------------------------------------------------------------------------
*/

window.updateDeliveryStatus =
function (status) {

    setDeliveryStatus(
        status
    );
};


window.getDeliveryStatus =
function () {

    return getDeliveryStatus();
};


/*
|--------------------------------------------------------------------------
| Expose Arrival Function
|--------------------------------------------------------------------------
*/

window.checkDeliveryArrival =
function () {

    checkRiderArrivalDistance();
};


window.markRiderArrivedAtShop =
function () {

    riderArrivedAtShop();
};


/*
|--------------------------------------------------------------------------
| Expose Delivery Actions
|--------------------------------------------------------------------------
*/

window.pickupDelivery =
function () {

    pickupDelivery();
};


window.startDelivery =
function () {

    startDelivery();
};


window.completeDelivery =
function () {

    completeDelivery();
};


window.checkDeliveryDistance =
function () {

    checkRiderDeliveryDistance();
};


/*
|--------------------------------------------------------------------------
| Cleanup
|--------------------------------------------------------------------------
*/

window.destroyDeliveryDirections =
function () {

    stopNavigation();

    stopCameraTransition();

    stopEtaCountdown();


    if (etaRefreshTimer) {

        clearTimeout(
            etaRefreshTimer
        );

        etaRefreshTimer =
            null;
    }


    if (animationFrameId) {

        cancelAnimationFrame(
            animationFrameId
        );

        animationFrameId =
            null;
    }


    if (riderInfoWindow) {
        riderInfoWindow.close();
    }


    if (pickupInfoWindow) {
        pickupInfoWindow.close();
    }


    if (dropoffInfoWindow) {
        dropoffInfoWindow.close();
    }


    if (riderMarker) {

        riderMarker.setMap(
            null
        );
    }


    if (pickupMarker) {

        pickupMarker.map =
            null;
    }


    if (dropoffMarker) {

        dropoffMarker.map =
            null;
    }


    if (directionsRenderer) {

        directionsRenderer.setMap(
            null
        );
    }


    directionsMap =
        null;

    directionsService =
        null;

    directionsRenderer =
        null;


    riderMarker =
        null;

    pickupMarker =
        null;

    dropoffMarker =
        null;


    riderInfoWindow =
        null;

    pickupInfoWindow =
        null;

    dropoffInfoWindow =
        null;


    routePath =
        [];


    currentRiderPosition =
        null;


    dropoffLatLng =
        null;


    currentEtaSeconds =
        null;

    etaLastUpdatedAt =
        null;


    isNavigating =
        false;

    cameraTransitionInProgress =
        false;

    routeRequestInProgress =
        false;

    isInitialized =
        false;


    currentDeliveryStatus =
        null;


    arrivedAtShop =
        false;

    arrivedAtShopRequestInProgress =
        false;


    pickupRequestInProgress =
        false;

    startDeliveryRequestInProgress =
        false;

    deliverRequestInProgress =
        false;


    updateEtaDisplay(
        null
    );


    hideAllDeliveryActionButtons();
};


/*
|--------------------------------------------------------------------------
| Pickup Shop Contact
|--------------------------------------------------------------------------
*/

function setupPickupContactButton() {

    const button =
        getElement(
            "pickupContactBtn"
        );


    if (!button) {
        return;
    }


    if (
        button.dataset.contactReady ===
        "true"
    ) {
        return;
    }


    button.dataset.contactReady =
        "true";


    button.addEventListener(
        "click",
        function (event) {

            event.preventDefault();
            event.stopPropagation();


            const phone =
                button.dataset.phone?.trim();


            if (!phone) {
                return;
            }


            window.location.href =
                `tel:${phone}`;
        }
    );
}


/*
|--------------------------------------------------------------------------
| Bottom Cone Menu
|--------------------------------------------------------------------------
*/

function setupBottomConeMenu() {

    const bottomCone =
        document.querySelector(
            ".bottom-cone"
        );


    if (!bottomCone) {
        return;
    }


    const mainView =
        bottomCone.querySelector(
            ".main-view"
        );


    const menuView =
        bottomCone.querySelector(
            ".menu-view"
        );


    const menuButton =
        bottomCone.querySelector(
            ".main-view .menu"
        );


    const closeButton =
        bottomCone.querySelector(
            ".menu-view .close"
        );


    if (
        !mainView ||
        !menuView ||
        !menuButton ||
        !closeButton
    ) {
        return;
    }


    if (
        menuButton.dataset.menuReady !==
        "true"
    ) {

        menuButton.dataset.menuReady =
            "true";


        menuButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();


                mainView.classList.remove(
                    "active"
                );


                menuView.classList.add(
                    "active"
                );
            }
        );
    }


    if (
        closeButton.dataset.closeReady !==
        "true"
    ) {

        closeButton.dataset.closeReady =
            "true";


        closeButton.addEventListener(
            "click",
            function (event) {

                event.preventDefault();
                event.stopPropagation();


                menuView.classList.remove(
                    "active"
                );


                mainView.classList.add(
                    "active"
                );
            }
        );
    }


    mainView.classList.add(
        "active"
    );


    menuView.classList.remove(
        "active"
    );
}


/*
|--------------------------------------------------------------------------
| Initialize Bottom Cone
|--------------------------------------------------------------------------
*/

if (
    document.readyState ===
    "loading"
) {

    document.addEventListener(
        "DOMContentLoaded",
        setupBottomConeMenu
    );

} else {

    setupBottomConeMenu();
}


/*
|--------------------------------------------------------------------------
| Minimize
|--------------------------------------------------------------------------
*/

function setupMinimizeButton() {

    const minimizeButton =
        document.querySelector(
            ".minimize"
        );


    if (!minimizeButton) {
        return;
    }


    if (
        minimizeButton.dataset.minimizeReady ===
        "true"
    ) {
        return;
    }


    minimizeButton.dataset.minimizeReady =
        "true";


    minimizeButton.addEventListener(
        "click",
        function (event) {

            event.preventDefault();

            event.stopPropagation();


            this.classList.toggle(
                "active"
            );
        }
    );
}