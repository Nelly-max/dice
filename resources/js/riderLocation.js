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
            '<i class="fa-solid fa-toggle-on" aria-hidden="true"></i><span class="rider-lbl">Go offline</span><span class="rider-lbl-short">Online</span>';
        button.setAttribute('aria-label', 'You are online. Tap to go offline');
        button.title = 'You are online. Tap to go offline';

        button.classList.add(
            status
        );

    } else {

        button.innerHTML =
            '<i class="fa-solid fa-toggle-off" aria-hidden="true"></i><span class="rider-lbl">Go online</span><span class="rider-lbl-short">Offline</span>';
        button.setAttribute('aria-label', 'You are offline. Tap to go online');
        button.title = 'You are offline. Tap to go online';

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
    RiderPermission.goOnline(button);
}


/*
|--------------------------------------------------------------------------
| LOCATION PERMISSION (how other apps do it)
|--------------------------------------------------------------------------
| 1. Not asked yet  -> explain why first ("Allow location"), then the
|                      browser's own prompt appears.
| 2. Allowed        -> straight online, no extra screens.
| 3. Blocked        -> say it is blocked and how to allow it in the
|                      browser / phone settings; "Try again" re-checks.
| 4. GPS off / weak -> ask to turn on location; retries once with
|                      network location before giving up.
| Browsers only share location on https (or localhost). If permission is
| revoked while online, the rider is taken offline.
*/

const RiderPermission = {

    status: null,          // PermissionStatus, when the browser supports it
    pendingButton: null,

    async state()
    {
        if (!navigator.geolocation) return 'unsupported';
        if (!window.isSecureContext) return 'insecure';
        try {
            if (!this.status && navigator.permissions?.query) {
                this.status = await navigator.permissions.query({ name: 'geolocation' });
                this.status.onchange = () => this.changed();
            }
            return this.status ? this.status.state : 'unknown';   // granted | prompt | denied
        } catch (e) {
            return 'unknown';   // e.g. older Safari: just ask
        }
    },

    async goOnline(button)
    {
        this.pendingButton = button;
        const state = await this.state();

        if (state === 'unsupported') return showLocationModal('unsupported');
        if (state === 'insecure') return showLocationModal('insecure');
        if (state === 'denied') return showLocationModal('denied');
        if (state === 'granted') return this.locate(button);

        // prompt / unknown: explain first; the browser asks after "Allow"
        showLocationModal('ask');
    },

    online(button, position)
    {
        setButtonBusy(button, false);
        hideLocationModal();
        RiderState.latestPosition = parseCoords(position);
        sendLocation(RiderState.latestPosition);
        updateRiderStatus('online', button);
    },

    locate(button)
    {
        setButtonBusy(button, true);

        navigator.geolocation.getCurrentPosition(
            position => this.online(button, position),
            error => {
                if (error.code === 1) {            // denied at the prompt
                    setButtonBusy(button, false);
                    return this.failed(error);
                }
                // weak GPS (indoors): one more try with network location
                navigator.geolocation.getCurrentPosition(
                    position => this.online(button, position),
                    err2 => { setButtonBusy(button, false); this.failed(err2); },
                    { enableHighAccuracy: false, timeout: 20000, maximumAge: 60000 }
                );
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
        );
    },

    failed(error)
    {
        console.error('[GPS] Error:', error);
        if (error && error.code === 1) return showLocationModal('denied');
        if (error && error.code === 3) return showLocationModal('timeout');
        showLocationModal('off');
    },

    // permission changed in the browser settings while the page is open
    changed()
    {
        const state = this.status && this.status.state;
        const button = document.getElementById('riderOnlineBtn');

        if (state === 'denied' && button && ['online', 'busy'].includes(button.dataset.status)) {
            stopRiderTracking();
            updateRiderStatus('offline', button);
            showLocationModal('revoked');
        }
        if (state === 'granted' && document.getElementById('locationModal')?.classList.contains('is-open')) {
            hideLocationModal();
            if (this.pendingButton) this.locate(this.pendingButton);
        }
    },
};

function setButtonBusy(button, busy)
{
    if (!button) return;
    button.disabled = !!busy;
    button.classList.toggle('is-locating', !!busy);
    if (busy) {
        if (!button.dataset.prevHtml) button.dataset.prevHtml = button.innerHTML;
        button.innerHTML = '<i class="fa-solid fa-location-crosshairs fa-beat-fade" aria-hidden="true"></i><span class="rider-lbl">Locating…</span><span class="rider-lbl-short">…</span>';
    } else if (button.dataset.prevHtml) {
        button.innerHTML = button.dataset.prevHtml;
        delete button.dataset.prevHtml;
    }
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

        showLocationModal('unsupported');

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

                // permission revoked: go offline and explain
                if (error.code === 1) {
                    stopRiderTracking();
                    const button = document.getElementById('riderOnlineBtn');
                    if (button) updateRiderStatus('offline', button);
                    showLocationModal('revoked');
                    return;
                }

                // weak signal / timeout: stay online with the last known position, warn once
                if (!RiderState.gpsWarned && window.notify) {
                    RiderState.gpsWarned = true;
                    window.notify.warning('Your GPS signal is weak. Keep location on so customers can follow you.', { title: 'Weak location signal' });
                }

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

function showLocationModal(kind = 'off')
{
    const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
    const steps = isIOS
        ? '<ol><li>Open <b>Settings</b> → <b>Privacy &amp; Security</b> → <b>Location Services</b>.</li><li>Turn it on, then choose your browser and set it to <b>While Using</b>.</li><li>Come back and tap <b>Try again</b>.</li></ol>'
        : '<ol><li>Tap the <b>lock</b> or <b>ⓘ</b> icon next to the web address.</li><li>Open <b>Permissions</b> / <b>Site settings</b> → <b>Location</b> → <b>Allow</b>.</li><li>Come back and tap <b>Try again</b>.</li></ol>';

    const views = {
        ask: {
            icon: 'fa-location-dot', title: 'Allow location to go online',
            body: '<p>Smart Market uses your location while you are online to send you nearby delivery requests and show customers where their order is.</p><p class="lm-small">Your browser will ask next. Location is only shared while you are online.</p>',
            primary: ['Allow', 'allow'], secondary: ['Not now', 'close'],
        },
        denied: {
            icon: 'fa-location-pin-lock', title: 'Location is blocked',
            body: '<p>Your browser is blocking location for Smart Market, so you can not go online yet.</p>' + steps,
            primary: ['Try again', 'retry'], secondary: ['Close', 'close'],
        },
        revoked: {
            icon: 'fa-location-pin-lock', title: 'You are now offline',
            body: '<p>Location access was turned off, so we took you offline. Allow location again to keep receiving deliveries.</p>' + steps,
            primary: ['Try again', 'retry'], secondary: ['Close', 'close'],
        },
        off: {
            icon: 'fa-satellite-dish', title: 'Turn on your location',
            body: '<p>We could not get your location. Turn on your phone\'s <b>Location / GPS</b>, then try again.</p>',
            primary: ['Try again', 'retry'], secondary: ['Close', 'close'],
        },
        timeout: {
            icon: 'fa-satellite-dish', title: 'Still looking for you…',
            body: '<p>Finding your location is taking too long. Check that <b>Location / GPS</b> is on, move near a window or outside, then try again.</p>',
            primary: ['Try again', 'retry'], secondary: ['Close', 'close'],
        },
        insecure: {
            icon: 'fa-lock-open', title: 'Location needs a secure connection',
            body: '<p>Browsers only share location on secure (<b>https://</b>) pages. Open Smart Market with its https address to go online.</p>',
            primary: ['OK', 'close'],
        },
        unsupported: {
            icon: 'fa-circle-info', title: 'Location not available',
            body: '<p>This browser can not share your location. Use an up-to-date Chrome, Safari or Firefox to go online.</p>',
            primary: ['OK', 'close'],
        },
    };
    const v = views[kind] || views.off;

    if (!document.getElementById('lm-css')) {
        const st = document.createElement('style');
        st.id = 'lm-css';
        st.textContent = `
#locationModal{position:fixed;inset:0;z-index:100004;display:none;align-items:flex-end;justify-content:center;background:rgba(10,14,12,.45);font-family:"Nunito",system-ui,sans-serif}
#locationModal.is-open{display:flex}
#locationModal .lm-box{box-sizing:border-box;width:min(26rem,100%);margin:0;padding:1.4rem 1.25rem calc(1.25rem + env(safe-area-inset-bottom));border-radius:1.25rem 1.25rem 0 0;background:#fff;color:#17201b;box-shadow:0 -10px 40px rgba(0,0,0,.2);animation:lmUp .25s cubic-bezier(.2,.8,.2,1)}
@media (min-width:640px){#locationModal{align-items:center}#locationModal .lm-box{border-radius:1.25rem}}
@keyframes lmUp{from{transform:translateY(30px);opacity:0}to{transform:none;opacity:1}}
#locationModal .lm-ico{display:grid;place-items:center;width:3.2rem;height:3.2rem;margin:0 0 .8rem;border-radius:50%;background:#e8f7ee;color:#16a34a;font-size:1.3rem}
#locationModal h3{margin:0 0 .45rem;font-size:1.15rem;font-weight:900}
#locationModal p{margin:0 0 .6rem;color:#4b5b52;font-size:.92rem;line-height:1.5}
#locationModal .lm-small{font-size:.8rem;color:#7a867f}
#locationModal ol{margin:.2rem 0 .8rem;padding-left:1.2rem;color:#4b5b52;font-size:.88rem;line-height:1.55}
#locationModal .lm-btns{display:flex;gap:.6rem;margin-top:1rem}
#locationModal .lm-btns button{flex:1;height:2.85rem;border:0;border-radius:.8rem;font:inherit;font-size:.95rem;font-weight:800;cursor:pointer}
#locationModal .lm-primary{background:#16a34a;color:#fff}
#locationModal .lm-secondary{background:#f1f4f2;color:#33413a}
body.dark-mode #locationModal .lm-box{background:#18211c;color:#e7efe9}
body.dark-mode #locationModal p,body.dark-mode #locationModal ol{color:#b6c3bb}
body.dark-mode #locationModal .lm-ico{background:#0f2a1a;color:#4ade80}
body.dark-mode #locationModal .lm-secondary{background:#243028;color:#d6e2da}`;
        document.head.appendChild(st);
    }

    let modal = document.getElementById('locationModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'locationModal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        document.body.appendChild(modal);
        modal.addEventListener('click', (e) => { if (e.target === modal) hideLocationModal(); });
    }

    modal.innerHTML =
        `<div class="lm-box">
            <span class="lm-ico"><i class="fa-solid ${v.icon}" aria-hidden="true"></i></span>
            <h3>${v.title}</h3>
            ${v.body}
            <div class="lm-btns">
                ${v.secondary ? `<button type="button" class="lm-secondary" data-act="${v.secondary[1]}">${v.secondary[0]}</button>` : ''}
                <button type="button" class="lm-primary" data-act="${v.primary[1]}">${v.primary[0]}</button>
            </div>
        </div>`;
    modal.classList.add('is-open');
    modal.style.display = '';

    modal.querySelectorAll('[data-act]').forEach((b) => {
        b.onclick = () => {
            const act = b.dataset.act;
            const button = RiderPermission.pendingButton || document.getElementById('riderOnlineBtn');
            hideLocationModal();
            if (act === 'allow') return RiderPermission.locate(button);   // the browser's own prompt appears now
            if (act === 'retry') return RiderPermission.goOnline(button);
        };
    });
    modal.querySelector('.lm-primary')?.focus();
}

function hideLocationModal()
{
    const modal = document.getElementById('locationModal');
    if (modal) {
        modal.classList.remove('is-open');
        modal.style.display = 'none';
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

    if (window.notify) {
        window.notify(message, 'info');
        return;
    }

    alert(
        message
    );

}