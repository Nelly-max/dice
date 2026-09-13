/*
|--------------------------------------------------------------------------
| RIDER ONLINE / LOCATION TRACKING
|--------------------------------------------------------------------------
*/

const RiderState = {
    watcher: null,
    interval: null,
    latestPosition: null,
    csrfToken: null
};


/*
|--------------------------------------------------------------------------
| RIDER DELIVERY STATE
|--------------------------------------------------------------------------
*/

const DeliveryState = {

    currentRequestId: null,

    currentDeliveryId: null,

    handledIds: new Set(),

    countdownTimer: null,

    pollingTimer: null,

    csrfToken: null,

    elements: {},

    storageKey: 'rider_current_delivery_id'

};


/*
|--------------------------------------------------------------------------
| DELIVERY REQUEST SOUND
|--------------------------------------------------------------------------
*/

const DeliverySound = {

    audio: null,

    blocked: false,

    create() {

        if (this.audio) {
            return this.audio;
        }

        this.audio = new Audio(
            '/sounds/delivery-request.mp3'
        );

        this.audio.preload = 'auto';

        this.audio.loop = true;

        this.audio.volume = 0.75;

        this.audio.setAttribute(
            'playsinline',
            ''
        );

        try {
            this.audio.load();
        } catch (error) {
            console.warn(
                '[DELIVERY SOUND] Preload failed:',
                error
            );
        }

        return this.audio;
    },


    async play() {

        const audio = this.create();

        if (!audio) {
            return false;
        }

        if (!audio.paused) {
            return true;
        }

        try {

            audio.currentTime = 0;

            await audio.play();

            this.blocked = false;

            console.log(
                '[DELIVERY SOUND] Playing'
            );

            return true;

        } catch (error) {

            this.blocked = true;

            console.warn(
                '[DELIVERY SOUND] Browser blocked autoplay:',
                error
            );

            return false;
        }
    },


    retry() {

        if (!this.blocked) {
            return;
        }

        const toast =
            document.getElementById('accept');

        if (
            !toast ||
            !toast.classList.contains('active')
        ) {
            return;
        }

        this.play();

    },


    stop() {

        if (!this.audio) {
            return;
        }

        try {

            this.audio.pause();

            this.audio.currentTime = 0;

            this.blocked = false;

        } catch (error) {

            console.warn(
                '[DELIVERY SOUND] Stop failed:',
                error
            );

        }

    }

};


/*
|--------------------------------------------------------------------------
| PREPARE SOUND
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {

        DeliverySound.create();

    }
);


/*
|--------------------------------------------------------------------------
| RETRY SOUND AFTER USER INTERACTION
|--------------------------------------------------------------------------
|
| This does NOT create the delivery alert.
| It only retries if the browser previously blocked autoplay.
|
|--------------------------------------------------------------------------
*/

[
    'click',
    'pointerdown',
    'touchstart',
    'keydown'
].forEach(eventName => {

    document.addEventListener(
        eventName,
        () => {

            DeliverySound.retry();

        },
        {
            passive: true
        }
    );

});


/*
|--------------------------------------------------------------------------
| RIDER INITIALIZATION
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {

        RiderState.csrfToken =
            document.querySelector(
                'meta[name="csrf-token"]'
            )?.content || null;


        DeliveryState.csrfToken =
            RiderState.csrfToken;


        /*
        |--------------------------------------------------------------------------
        | Restore accepted delivery
        |--------------------------------------------------------------------------
        */

        restoreCurrentDelivery();


        /*
        |--------------------------------------------------------------------------
        | Rider status
        |--------------------------------------------------------------------------
        */

        initializeRider();


        /*
        |--------------------------------------------------------------------------
        | Delivery requests
        |--------------------------------------------------------------------------
        */

        initializeDeliveryRequests();

    }
);


/*
|--------------------------------------------------------------------------
| RIDER INITIALIZATION
|--------------------------------------------------------------------------
*/

function initializeRider()
{

    const activator =
        document.querySelector(
            '.activator.rider'
        );


    const button =
        document.getElementById(
            'riderOnlineBtn'
        );


    if (
        !activator ||
        !button
    ) {

        return;

    }


    activator.style.display =
        'none';


    fetch(
        '/rider/init',
        {
            headers: {
                'Accept':
                    'application/json'
            }
        }
    )
        .then(response => {

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}`
                );
            }

            return response.json();

        })
        .then(data => {

            if (
                !data.has_rider_account ||
                ![
                    'active',
                    'suspended'
                ].includes(
                    data.account_status
                )
            ) {

                return;

            }


            activator.style.display =
                '';


            if (
                data.account_status ===
                'suspended'
            ) {

                button.textContent =
                    'ACCOUNT SUSPENDED';

                button.disabled =
                    true;

                button.classList.add(
                    'suspended'
                );

                return;

            }


            button.disabled =
                false;


            const currentStatus =
                data.online_status ||
                data.status ||
                'offline';


            setButtonStatus(
                button,
                currentStatus
            );


            if (
                [
                    'online',
                    'busy'
                ].includes(
                    currentStatus
                )
            ) {

                startRiderTracking();

            } else {

                stopRiderTracking();

            }

        })
        .catch(error => {

            console.error(
                '[RIDER] Init error:',
                error
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Rider online/offline button
    |--------------------------------------------------------------------------
    */

    button.addEventListener(
        'click',
        () => {

            const status =
                button.dataset.status;


            if (
                [
                    'online',
                    'busy'
                ].includes(
                    status
                )
            ) {

                updateRiderStatus(
                    'offline',
                    button
                );

            } else {

                checkLocation(
                    button
                );

            }

        }
    );

}


/*
|--------------------------------------------------------------------------
| RIDER BUTTON STATUS
|--------------------------------------------------------------------------
*/

function setButtonStatus(
    button,
    status
)
{

    if (!button) {
        return;
    }


    button.dataset.status =
        status;


    button.classList.remove(
        'online',
        'offline',
        'busy'
    );


    if (
        [
            'online',
            'busy'
        ].includes(
            status
        )
    ) {

        button.innerHTML =
            '<i class="fa-solid fa-toggle-on"></i> GO OFFLINE';

        button.classList.add(
            status
        );

    } else {

        button.innerHTML =
            '<i class="fa-solid fa-toggle-off"></i> GO ONLINE';

        button.classList.add(
            'offline'
        );

    }

}


/*
|--------------------------------------------------------------------------
| CHECK LOCATION
|--------------------------------------------------------------------------
*/

function checkLocation(button)
{

    if (
        !navigator.geolocation
    ) {

        showLocationModal();

        return;

    }


    navigator.geolocation.getCurrentPosition(

        position => {

            RiderState.latestPosition =
                parseCoords(position);


            sendLocation(
                RiderState.latestPosition
            );


            updateRiderStatus(
                'online',
                button
            );

        },

        error => {

            console.error(
                '[GPS] Error:',
                error
            );


            showLocationModal();

        },

        {
            enableHighAccuracy:
                true,

            timeout:
                15000,

            maximumAge:
                0
        }

    );

}


/*
|--------------------------------------------------------------------------
| PARSE GPS
|--------------------------------------------------------------------------
*/

function parseCoords(position)
{

    return {

        latitude:
            position.coords.latitude,

        longitude:
            position.coords.longitude,

        heading:
            position.coords.heading,

        speed:
            position.coords.speed,

        accuracy:
            position.coords.accuracy

    };

}


/*
|--------------------------------------------------------------------------
| UPDATE RIDER STATUS
|--------------------------------------------------------------------------
*/

async function updateRiderStatus(
    status,
    button
)
{

    try {

        const response =
            await fetch(
                '/rider/status/update',
                {

                    method:
                        'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            RiderState.csrfToken

                    },

                    body:
                        JSON.stringify({
                            status
                        })

                }
            );


        const data =
            await response.json();


        console.log(
            '[RIDER] STATUS:',
            data
        );


        if (!data.success) {

            showToast(
                data.message ??
                'Unable to update status'
            );

            return;

        }


        setButtonStatus(
            button,
            data.status
        );


        if (
            [
                'online',
                'busy'
            ].includes(
                data.status
            )
        ) {

            startRiderTracking();

        } else {

            stopRiderTracking();

        }

    }

    catch (error) {

        console.error(
            '[RIDER] Status update failed:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| START RIDER TRACKING
|--------------------------------------------------------------------------
*/

function startRiderTracking()
{

    if (
        RiderState.watcher
    ) {

        return;

    }


    if (
        !navigator.geolocation
    ) {

        showLocationModal();

        return;

    }


    RiderState.watcher =
        navigator.geolocation.watchPosition(

            position => {

                RiderState.latestPosition =
                    parseCoords(position);

            },

            error => {

                console.error(
                    '[RIDER] GPS tracking error:',
                    error
                );


                stopRiderTracking();


                const button =
                    document.getElementById(
                        'riderOnlineBtn'
                    );


                if (button) {

                    updateRiderStatus(
                        'offline',
                        button
                    );

                }


                showLocationModal();

            },

            {

                enableHighAccuracy:
                    true,

                maximumAge:
                    0,

                timeout:
                    10000

            }

        );


    RiderState.interval =
        setInterval(
            () => {

                if (
                    RiderState.latestPosition
                ) {

                    sendLocation(
                        RiderState.latestPosition
                    );

                }

            },
            5000
        );

}


/*
|--------------------------------------------------------------------------
| STOP RIDER TRACKING
|--------------------------------------------------------------------------
*/

function stopRiderTracking()
{

    if (
        RiderState.watcher
    ) {

        navigator.geolocation.clearWatch(
            RiderState.watcher
        );

        RiderState.watcher =
            null;

    }


    if (
        RiderState.interval
    ) {

        clearInterval(
            RiderState.interval
        );

        RiderState.interval =
            null;

    }


    RiderState.latestPosition =
        null;

}


/*
|--------------------------------------------------------------------------
| SEND RIDER LOCATION
|--------------------------------------------------------------------------
*/

async function sendLocation(location)
{

    try {

        const response =
            await fetch(
                '/rider/location/update',
                {

                    method:
                        'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            RiderState.csrfToken

                    },

                    body:
                        JSON.stringify(
                            location
                        )

                }
            );


        const data =
            await response.json();


        if (!data.success) {

            console.error(
                '[RIDER] Location update failed:',
                data
            );

        }

    }

    catch (error) {

        console.error(
            '[RIDER] Location push error:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| DELIVERY REQUEST INITIALIZATION
|--------------------------------------------------------------------------
*/

function initializeDeliveryRequests()
{

    DeliveryState.elements = {

        toast:
            document.getElementById(
                'accept'
            ),

        pickupStore:
            document.getElementById(
                'pickupStore'
            ),

        countdown:
            document.getElementById(
                'acceptCountdown'
            ),

        progress:
            document.getElementById(
                'acceptProgress'
            ),

        acceptButton:
            document.getElementById(
                'acceptDeliveryBtn'
            ),

        activeDeliveryToast:
            document.getElementById(
                'activeDeliveryToast'
            )

    };


    const acceptButton =
        DeliveryState.elements.acceptButton;


    if (acceptButton) {

        acceptButton.addEventListener(
            'click',
            acceptDelivery
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Check immediately
    |--------------------------------------------------------------------------
    */

    checkDeliveryRequest();


    /*
    |--------------------------------------------------------------------------
    | Continue polling
    |--------------------------------------------------------------------------
    */

    DeliveryState.pollingTimer =
        setInterval(
            checkDeliveryRequest,
            2000
        );

}


/*
|--------------------------------------------------------------------------
| CHECK DELIVERY REQUEST
|--------------------------------------------------------------------------
*/

async function checkDeliveryRequest()
{

    try {

        const response =
            await fetch(
                '/rider/delivery/request',
                {

                    headers: {

                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'

                    },

                    cache:
                        'no-store'

                }
            );


        if (!response.ok) {

            throw new Error(
                `HTTP ${response.status}`
            );

        }


        const data =
            await response.json();


        console.log(
            '[DELIVERY] REQUEST:',
            data
        );


        /*
        |--------------------------------------------------------------------------
        | No request
        |--------------------------------------------------------------------------
        */

        if (
            !data.success ||
            !data.request
        ) {

            return;

        }


        const request =
            data.request;


        /*
        |--------------------------------------------------------------------------
        | Only pending requests
        |--------------------------------------------------------------------------
        */

        if (
            request.status !==
            'pending'
        ) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate toast
        |--------------------------------------------------------------------------
        */

        if (
            DeliveryState.handledIds.has(
                request.id
            )
        ) {

            return;

        }


        DeliveryState.handledIds.add(
            request.id
        );


        DeliveryState.currentRequestId =
            request.id;


        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | This is deliveries.id.
        |
        | Your pendingRequest() returns:
        |
        | request.delivery_id
        |
        |--------------------------------------------------------------------------
        */

        if (
            request.delivery_id
        ) {

            DeliveryState.currentDeliveryId =
                String(
                    request.delivery_id
                );

        }


        /*
        |--------------------------------------------------------------------------
        | Show request toast
        |--------------------------------------------------------------------------
        */

        showDeliveryToast(
            request
        );

    }

    catch (error) {

        console.error(
            '[DELIVERY] Request check failed:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| SHOW DELIVERY REQUEST TOAST
|--------------------------------------------------------------------------
*/

function showDeliveryToast(request)
{

    const {
        toast,
        pickupStore
    } =
        DeliveryState.elements;


    if (!toast) {

        return;

    }


    if (pickupStore) {

        pickupStore.textContent =
            `Pickup Store: ${
                request.pickup_store ?? ''
            }`;

    }


    toast.classList.add(
        'active'
    );


    /*
    |--------------------------------------------------------------------------
    | PLAY DELIVERY SOUND
    |--------------------------------------------------------------------------
    */

    DeliverySound.play();


    /*
    |--------------------------------------------------------------------------
    | START COUNTDOWN
    |--------------------------------------------------------------------------
    */

    startCountdown(
        request.expires_at
    );

}


/*
|--------------------------------------------------------------------------
| START COUNTDOWN
|--------------------------------------------------------------------------
*/

function startCountdown(
    expiresAt
)
{

    const {
        countdown,
        progress
    } =
        DeliveryState.elements;


    clearInterval(
        DeliveryState.countdownTimer
    );


    const expiry =
        new Date(
            expiresAt
        ).getTime();


    const start =
        Date.now();


    const total =
        expiry -
        start;


    if (
        total <= 0
    ) {

        handleExpiredState(
            countdown
        );

        return;

    }


    DeliveryState.countdownTimer =
        setInterval(
            () => {

                const remaining =
                    expiry -
                    Date.now();


                const seconds =
                    Math.ceil(
                        remaining /
                        1000
                    );


                if (
                    progress
                ) {

                    const percentage =
                        Math.max(
                            0,
                            (
                                remaining /
                                total
                            ) *
                            100
                        );


                    progress.style.width =
                        `${percentage}%`;

                }


                if (
                    countdown &&
                    seconds > 0
                ) {

                    countdown.textContent =
                        `Accept Delivery (${seconds})`;

                }


                if (
                    remaining <= 0
                ) {

                    clearInterval(
                        DeliveryState.countdownTimer
                    );


                    DeliveryState.countdownTimer =
                        null;


                    handleExpiredState(
                        countdown
                    );

                }

            },
            100
        );

}


/*
|--------------------------------------------------------------------------
| EXPIRED DELIVERY REQUEST
|--------------------------------------------------------------------------
*/

function handleExpiredState(
    countdown
)
{

    if (countdown) {

        countdown.textContent =
            'Expired';

    }


    DeliveryState.currentRequestId =
        null;


    DeliveryState.currentDeliveryId =
        null;


    DeliverySound.stop();


    hideDeliveryToast();

}


/*
|--------------------------------------------------------------------------
| ACCEPT DELIVERY
|--------------------------------------------------------------------------
*/

async function acceptDelivery()
{
    const requestId =
        DeliveryState.currentRequestId;

    if (!requestId) {

        console.warn(
            '[DELIVERY] No request to accept.'
        );

        return;
    }

    const acceptButton =
        DeliveryState.elements.acceptButton;

    if (acceptButton) {

        acceptButton.disabled =
            true;

    }

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT
    |--------------------------------------------------------------------------
    | This is deliveries.id, captured from:
    |
    | request.delivery_id
    |--------------------------------------------------------------------------
    */

    const deliveryId =
        DeliveryState.currentDeliveryId;

    if (!deliveryId) {

        console.error(
            '[DELIVERY] No delivery ID available.'
        );

        if (acceptButton) {
            acceptButton.disabled = false;
        }

        showToast(
            'Unable to identify this delivery.'
        );

        return;
    }

    console.log(
        '[DELIVERY] Accepting:',
        {
            requestId,
            deliveryId
        }
    );

    try {

        const response =
            await fetch(
                `/rider/delivery/${
                    encodeURIComponent(
                        requestId
                    )
                }/accept`,
                {

                    method:
                        'POST',

                    headers: {

                        'Content-Type':
                            'application/json',

                        'Accept':
                            'application/json',

                        'X-CSRF-TOKEN':
                            DeliveryState.csrfToken,

                        'X-Requested-With':
                            'XMLHttpRequest'

                    },

                    body:
                        JSON.stringify({})

                }
            );


        const data =
            await response.json();


        console.log(
            '[DELIVERY] ACCEPT RESPONSE:',
            data
        );


        /*
        |--------------------------------------------------------------------------
        | Acceptance failed
        |--------------------------------------------------------------------------
        */

        if (
            !response.ok ||
            !data.success
        ) {

            if (acceptButton) {

                acceptButton.disabled =
                    false;

            }

            showToast(
                data.message ??
                'Unable to accept delivery.'
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | SAVE ACTIVE DELIVERY
        |--------------------------------------------------------------------------
        */

        saveActiveDelivery(
            deliveryId
        );


        /*
        |--------------------------------------------------------------------------
        | STOP COUNTDOWN
        |--------------------------------------------------------------------------
        */

        clearInterval(
            DeliveryState.countdownTimer
        );

        DeliveryState.countdownTimer =
            null;


        /*
        |--------------------------------------------------------------------------
        | STOP DELIVERY SOUND
        |--------------------------------------------------------------------------
        */

        DeliverySound.stop();


        /*
        |--------------------------------------------------------------------------
        | HIDE ACCEPT REQUEST
        |--------------------------------------------------------------------------
        */

        hideDeliveryToast();


        /*
        |--------------------------------------------------------------------------
        | CLEAR REQUEST ID
        |--------------------------------------------------------------------------
        */

        DeliveryState.currentRequestId =
            null;


        /*
        |--------------------------------------------------------------------------
        | REDIRECT TO NAVIGATION
        |--------------------------------------------------------------------------
        |
        | Laravel route:
        |
        | Route::get(
        |     'hub/navigate/{delivery}',
        |     [RiderLocationController::class, 'navigate']
        | );
        |
        |--------------------------------------------------------------------------
        */

        window.location.href =
            `/hub/navigate/${
                encodeURIComponent(
                    deliveryId
                )
            }`;

    }

    catch (error) {

        console.error(
            '[DELIVERY] Accept failed:',
            error
        );

        if (acceptButton) {

            acceptButton.disabled =
                false;

        }

    }
}


/*
|--------------------------------------------------------------------------
| SAVE ACTIVE DELIVERY
|--------------------------------------------------------------------------
|
| This controls:
|
| <div class="toast" id="activeDeliveryToast">
|
| The active class is added when the rider has accepted a delivery.
|--------------------------------------------------------------------------
*/

function saveActiveDelivery(
    deliveryId
)
{

    if (
        !deliveryId
    ) {

        return;

    }


    deliveryId =
        String(
            deliveryId
        );


    DeliveryState.currentDeliveryId =
        deliveryId;


    try {

        localStorage.setItem(
            DeliveryState.storageKey,
            deliveryId
        );

    }

    catch (error) {

        console.warn(
            '[DELIVERY] Could not save delivery:',
            error
        );

    }


    showActiveDeliveryToast();


    console.log(
        '[DELIVERY] Active delivery:',
        deliveryId
    );

}


/*
|--------------------------------------------------------------------------
| RESTORE ACTIVE DELIVERY
|--------------------------------------------------------------------------
*/

function restoreCurrentDelivery()
{

    let deliveryId =
        null;


    try {

        deliveryId =
            localStorage.getItem(
                DeliveryState.storageKey
            );

    }

    catch (error) {

        console.warn(
            '[DELIVERY] Could not restore delivery:',
            error
        );

    }


    if (!deliveryId) {

        return;

    }


    DeliveryState.currentDeliveryId =
        String(
            deliveryId
        );


    /*
    |--------------------------------------------------------------------------
    | Show navigation button
    |--------------------------------------------------------------------------
    */

    showActiveDeliveryToast();


    console.log(
        '[DELIVERY] Restored active delivery:',
        deliveryId
    );

}


/*
|--------------------------------------------------------------------------
| SHOW ACTIVE DELIVERY NAVIGATION TOAST
|--------------------------------------------------------------------------
*/

function showActiveDeliveryToast()
{

    const toast =
        document.getElementById(
            'activeDeliveryToast'
        );


    if (!toast) {

        return;

    }


    /*
    |--------------------------------------------------------------------------
    | THIS IS THE IMPORTANT LINE
    |--------------------------------------------------------------------------
    */

    toast.classList.add(
        'active'
    );

}


/*
|--------------------------------------------------------------------------
| HIDE ACTIVE DELIVERY NAVIGATION TOAST
|--------------------------------------------------------------------------
*/

function hideActiveDeliveryToast()
{

    const toast =
        document.getElementById(
            'activeDeliveryToast'
        );


    if (!toast) {

        return;

    }


    toast.classList.remove(
        'active'
    );

}


/*
|--------------------------------------------------------------------------
| CLEAR ACTIVE DELIVERY
|--------------------------------------------------------------------------
|
| Call this only when the delivery has actually been completed
| or cancelled.
|--------------------------------------------------------------------------
*/

function clearActiveDelivery()
{

    DeliveryState.currentDeliveryId =
        null;


    try {

        localStorage.removeItem(
            DeliveryState.storageKey
        );

    }

    catch (error) {

        console.warn(
            '[DELIVERY] Could not clear delivery:',
            error
        );

    }


    hideActiveDeliveryToast();


    console.log(
        '[DELIVERY] Active delivery cleared.'
    );

}


/*
|--------------------------------------------------------------------------
| OPEN CURRENT DELIVERY
|--------------------------------------------------------------------------
*/

function openCurrentDelivery()
{
    const deliveryId =
        DeliveryState.currentDeliveryId;

    if (!deliveryId) {

        showToast(
            'No active delivery found.'
        );

        return;
    }

    console.log(
        '[DELIVERY] Opening current delivery:',
        deliveryId
    );

    window.location.href =
        `/hub/navigate/${encodeURIComponent(
            deliveryId
        )}`;
}


/*
|--------------------------------------------------------------------------
| NAVIGATION BUTTON
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {

        const button =
            document.getElementById(
                'openNavigation'
            );

        if (!button) {
            return;
        }

        button.addEventListener(
            'click',
            event => {

                event.preventDefault();

                openCurrentDelivery();

            }
        );

    }
);


/*
|--------------------------------------------------------------------------
| LOCATION MODAL
|--------------------------------------------------------------------------
*/

function showLocationModal()
{

    let modal =
        document.getElementById(
            'locationModal'
        );


    if (!modal) {

        modal =
            document.createElement(
                'div'
            );


        modal.id =
            'locationModal';


        modal.innerHTML = `

            <div class="location-box">

                <i class="fa-solid fa-circle-info"></i>

                <h3>
                    Location Required
                </h3>

                <p>
                    Please turn on your device
                    location services to go online
                    and receive deliveries.
                </p>

                <button id="locationOkBtn">
                    OK
                </button>

            </div>

        `;


        document.body.appendChild(
            modal
        );

    }


    modal.style.display =
        'flex';


    const button =
        document.getElementById(
            'locationOkBtn'
        );


    if (button) {

        button.onclick =
            () => {

                modal.style.display =
                    'none';

            };

    }

}


/*
|--------------------------------------------------------------------------
| HIDE DELIVERY REQUEST TOAST
|--------------------------------------------------------------------------
*/

function hideDeliveryToast()
{

    const {
        toast,
        progress,
        acceptButton
    } =
        DeliveryState.elements;


    if (toast) {

        toast.classList.remove(
            'active'
        );

    }


    if (progress) {

        progress.style.width =
            '100%';

    }


    if (acceptButton) {

        acceptButton.disabled =
            false;

    }


    clearInterval(
        DeliveryState.countdownTimer
    );


    DeliveryState.countdownTimer =
        null;

}


/*
|--------------------------------------------------------------------------
| SIMPLE TOAST
|--------------------------------------------------------------------------
*/

function showToast(message)
{

    alert(
        message
    );

}