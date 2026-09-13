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
| RIDER NAVIGATION / MAP STATE
|--------------------------------------------------------------------------
*/

const NavigationState = {
    initialized: false,
    navigating: false,
    recalculating: false,

    map: null,
    directionsService: null,
    directionsRenderer: null,

    riderMarker: null,
    pickupMarker: null,

    riderInfoWindow: null,
    pickupInfoWindow: null,

    routePath: [],

    currentPosition: null,

    animationFrame: null,

    lastBackendHeading: null,

    navigationButton: null,

    deliveryId: null
};


/*
|--------------------------------------------------------------------------
| NAVIGATION SETTINGS
|--------------------------------------------------------------------------
*/

const NAVIGATION_CONFIG = {

    normalZoom: 17,

    navigationZoom: 19,

    navigationTilt: 55,

    animationDuration: 2500,

    offRouteThreshold: 50,

    cameraOffset: 0.0004

};


/*
|--------------------------------------------------------------------------
| DELIVERY REQUEST SOUND
|--------------------------------------------------------------------------
|
| The sound starts immediately when a delivery toast appears.
|
| IMPORTANT:
| Browsers such as Chrome/Edge may block unmuted autoplay when the
| rider has never interacted with the page. JavaScript cannot bypass
| that browser security restriction.
|
| We therefore:
|
| 1. Attempt playback immediately.
| 2. Keep the alert ready.
| 3. Retry automatically after a user gesture if autoplay was blocked.
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DELIVERY REQUEST SOUND
|--------------------------------------------------------------------------
*/

const DELIVERY_SOUND_CONFIG = {

    src: '/sounds/delivery-request.mp3',

    volume: 0.75,

    loop: true

};


const DeliveryNotificationSound = {

    audio: null,

    autoplayBlocked: false,

    initialized: false,


    /*
    |--------------------------------------------------------------------------
    | CREATE AUDIO INSTANCE
    |--------------------------------------------------------------------------
    */

    create() {

        if (this.audio) {

            return this.audio;

        }


        this.audio = new Audio(
            DELIVERY_SOUND_CONFIG.src
        );


        this.audio.preload =
            'auto';


        this.audio.loop =
            DELIVERY_SOUND_CONFIG.loop;


        this.audio.volume =
            DELIVERY_SOUND_CONFIG.volume;


        this.audio.setAttribute(
            'playsinline',
            ''
        );


        /*
        |--------------------------------------------------------------------------
        | Start loading the sound immediately.
        |--------------------------------------------------------------------------
        */

        try {

            this.audio.load();

        }

        catch (error) {

            console.warn(
                '[DELIVERY SOUND] Audio preload failed:',
                error
            );

        }


        this.initialized =
            true;


        return this.audio;

    },


    /*
    |--------------------------------------------------------------------------
    | PLAY ALERT
    |--------------------------------------------------------------------------
    */

    async play() {

        const audio =
            this.create();


        if (!audio) {

            return false;

        }


        /*
        |--------------------------------------------------------------------------
        | Already playing
        |--------------------------------------------------------------------------
        */

        if (!audio.paused) {

            return true;

        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Always start a new alert from the beginning.
            |--------------------------------------------------------------------------
            */

            audio.currentTime =
                0;


            audio.volume =
                DELIVERY_SOUND_CONFIG.volume;


            await audio.play();


            this.autoplayBlocked =
                false;


            console.log(
                '[DELIVERY SOUND] Alert playing.'
            );


            return true;

        }

        catch (error) {

            /*
            |--------------------------------------------------------------------------
            | Browser autoplay policy blocked playback.
            |--------------------------------------------------------------------------
            */

            this.autoplayBlocked =
                true;


            console.warn(
                '[DELIVERY SOUND] Autoplay blocked:',
                error
            );


            return false;

        }

    },


    /*
    |--------------------------------------------------------------------------
    | RETRY AFTER USER INTERACTION
    |--------------------------------------------------------------------------
    */

    retryAfterInteraction() {

        if (
            !this.autoplayBlocked
        ) {

            return;

        }


        if (
            !this.audio
        ) {

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Only retry if the alert is still supposed to be playing.
        |--------------------------------------------------------------------------
        |
        | We determine this from the fact that the delivery toast is active.
        |
        */

        const toast =
            document.getElementById(
                'accept'
            );


        if (
            !toast ||
            !toast.classList.contains(
                'active'
            )
        ) {

            return;

        }


        console.log(
            '[DELIVERY SOUND] Retrying after user interaction...'
        );


        this.play();

    },


    /*
    |--------------------------------------------------------------------------
    | STOP ALERT
    |--------------------------------------------------------------------------
    */

    stop() {

        if (!this.audio) {

            return;

        }


        try {

            this.audio.pause();


            this.audio.currentTime =
                0;


            this.autoplayBlocked =
                false;

        }

        catch (error) {

            console.warn(
                '[DELIVERY SOUND] Unable to stop:',
                error
            );

        }

    }

};


/*
|--------------------------------------------------------------------------
| PREPARE AUDIO AS EARLY AS POSSIBLE
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {

        DeliveryNotificationSound.create();

    }
);


/*
|--------------------------------------------------------------------------
| RETRY AUDIO AFTER USER INTERACTION
|--------------------------------------------------------------------------
|
| If the browser blocked autoplay when the delivery arrived, the first
| interaction with the page allows us to retry the alert.
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

            DeliveryNotificationSound
                .retryAfterInteraction();

        },
        {
            passive: true
        }
    );

});


/*
|--------------------------------------------------------------------------
| RETRY DELIVERY SOUND AFTER RIDER INTERACTION
|--------------------------------------------------------------------------
|
| If Chrome/Edge initially blocked autoplay, the first interaction
| with the page gives us permission to start the audio.
|
|--------------------------------------------------------------------------
*/

[
    'click',
    'touchstart',
    'pointerdown',
    'keydown'
].forEach(eventName => {

    document.addEventListener(
        eventName,
        () => {

            DeliveryNotificationSound
                .retryAfterInteraction();

        },
        {
            passive: true
        }
    );

});




/*
|--------------------------------------------------------------------------
| DOM READY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    () => {

        RiderState.csrfToken =
            document.querySelector(
                'meta[name="csrf-token"]'
            )?.content;


        /*
        |--------------------------------------------------------------------------
        | Prepare delivery notification sound
        |--------------------------------------------------------------------------
        */

        DeliveryNotificationSound.create();

        initializeRiderStatus();

        initializeNavigation();

        initializeDeliveryRequests();

    }
);


/*
|--------------------------------------------------------------------------
| RIDER STATUS INITIALIZATION
|--------------------------------------------------------------------------
*/

function initializeRiderStatus()
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


    fetch('/rider/init')

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
                !['active', 'suspended']
                    .includes(
                        data.account_status
                    )
            ) {

                return;

            }


            activator.style.display =
                '';


            /*
            |--------------------------------------------------------------------------
            | Suspended rider
            |--------------------------------------------------------------------------
            */

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
                ['online', 'busy']
                    .includes(
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
                'Rider init error:',
                error
            );

        });


    /*
    |--------------------------------------------------------------------------
    | Online / Offline button
    |--------------------------------------------------------------------------
    */

    button.addEventListener(
        'click',
        () => {

            const status =
                button.dataset.status;


            if (
                ['online', 'busy']
                    .includes(status)
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
| SET RIDER BUTTON STATUS
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
        ['online', 'busy']
            .includes(status)
    ) {

        button.innerHTML =
            `<i class="fa-solid fa-toggle-on"></i> GO OFFLINE`;

        button.classList.add(
            status
        );

    } else {

        button.innerHTML =
            `<i class="fa-solid fa-toggle-off"></i> GO ONLINE`;

        button.classList.add(
            'offline'
        );

    }

}


/*
|--------------------------------------------------------------------------
| CHECK LOCATION BEFORE GOING ONLINE
|--------------------------------------------------------------------------
*/

function checkLocation(button)
{

    if (!navigator.geolocation) {

        showLocationModal();

        return;

    }


    navigator.geolocation.getCurrentPosition(

        position => {

            RiderState.latestPosition =
                parseCoords(position);


            /*
            |--------------------------------------------------------------------------
            | Send first GPS position immediately
            |--------------------------------------------------------------------------
            */

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
                'GPS ERROR:',
                error
            );

            showLocationModal();

        },

        {

            enableHighAccuracy: true,

            timeout: 15000,

            maximumAge: 0

        }

    );

}


/*
|--------------------------------------------------------------------------
| PARSE GPS COORDINATES
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
| UPDATE RIDER ONLINE STATUS
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

                    method: 'POST',

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
                            status: status
                        })

                }
            );


        const data =
            await response.json();


        console.log(
            'STATUS RESPONSE:',
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
            ['online', 'busy']
                .includes(data.status)
        ) {

            startRiderTracking();

        } else {

            stopRiderTracking();

        }

    }

    catch (error) {

        console.error(
            'Failed to update status:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| START GPS TRACKING
|--------------------------------------------------------------------------
*/

function startRiderTracking()
{

    if (
        RiderState.watcher
    ) {

        return;

    }


    if (!navigator.geolocation) {

        showLocationModal();

        return;

    }


    console.log(
        'Starting rider GPS tracking...'
    );


    RiderState.watcher =
        navigator.geolocation.watchPosition(

            position => {

                const location =
                    parseCoords(
                        position
                    );


                RiderState.latestPosition =
                    location;


                /*
                |--------------------------------------------------------------------------
                | Move map immediately when GPS changes
                |--------------------------------------------------------------------------
                */

                updateNavigationRiderPosition(
                    location.latitude,
                    location.longitude,
                    location.heading,
                    location.speed
                );

            },

            error => {

                console.error(
                    'GPS tracking error:',
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

                enableHighAccuracy: true,

                maximumAge: 0,

                timeout: 10000

            }

        );


    /*
    |--------------------------------------------------------------------------
    | Send GPS to database every 5 seconds
    |--------------------------------------------------------------------------
    */

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
| STOP GPS TRACKING
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
| SEND GPS TO SERVER
|--------------------------------------------------------------------------
*/

async function sendLocation(
    location
)
{

    try {

        const response =
            await fetch(
                '/rider/location/update',
                {

                    method: 'POST',

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
                'Location update failed:',
                data
            );

            return;

        }


        console.log(
            'RIDER LOCATION UPDATED:',
            location.latitude,
            location.longitude
        );

    }

    catch (error) {

        console.error(
            'Location push error:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| NAVIGATION MAP INITIALIZATION
|--------------------------------------------------------------------------
*/

async function initializeNavigation()
{

    const mapElement =
        document.getElementById(
            'map'
        );


    if (!mapElement) {

        return;

    }


    if (
        NavigationState.initialized
    ) {

        return;

    }


    if (
        typeof google === 'undefined' ||
        !google.maps
    ) {

        console.error(
            'Google Maps has not loaded yet.'
        );

        return;

    }


    const riderLat =
        parseFloat(
            mapElement.dataset.riderLat
        );


    const riderLng =
        parseFloat(
            mapElement.dataset.riderLng
        );


    const shopLat =
        parseFloat(
            mapElement.dataset.shopLat
        );


    const shopLng =
        parseFloat(
            mapElement.dataset.shopLng
        );


    if (
        Number.isNaN(riderLat) ||
        Number.isNaN(riderLng) ||
        Number.isNaN(shopLat) ||
        Number.isNaN(shopLng)
    ) {

        console.error(
            'Invalid navigation coordinates:',
            {
                riderLat,
                riderLng,
                shopLat,
                shopLng
            }
        );

        return;

    }


    NavigationState.deliveryId =
        mapElement.dataset.deliveryId ||
        null;


    NavigationState.currentPosition =
        new google.maps.LatLng(
            riderLat,
            riderLng
        );


    /*
    |--------------------------------------------------------------------------
    | Create map
    |--------------------------------------------------------------------------
    */

    NavigationState.map =
        new google.maps.Map(
            mapElement,
            {

                center: {

                    lat: riderLat,

                    lng: riderLng

                },

                zoom:
                    NAVIGATION_CONFIG.normalZoom,

                tilt: 0,

                heading: 0,

                mapId:
                    'DELIVERY_TRACKING_MAP',

                mapTypeControl: false,

                streetViewControl: false,

                fullscreenControl: false,

                clickableIcons: false,

                gestureHandling: 'greedy'

            }
        );


    /*
    |--------------------------------------------------------------------------
    | Import libraries
    |--------------------------------------------------------------------------
    */

    const markerLibrary =
        await google.maps.importLibrary(
            'marker'
        );


    await google.maps.importLibrary(
        'geometry'
    );


    const AdvancedMarkerElement =
        markerLibrary.AdvancedMarkerElement;


    /*
    |--------------------------------------------------------------------------
    | Directions Service
    |--------------------------------------------------------------------------
    */

    NavigationState.directionsService =
        new google.maps.DirectionsService();


    /*
    |--------------------------------------------------------------------------
    | Directions Renderer
    |--------------------------------------------------------------------------
    */

    NavigationState.directionsRenderer =
        new google.maps.DirectionsRenderer({

            map:
                NavigationState.map,

            suppressMarkers:
                true,

            preserveViewport:
                true,

            polylineOptions: {

                strokeColor:
                    '#f25112',

                strokeOpacity:
                    1,

                strokeWeight:
                    6

            }

        });


    /*
    |--------------------------------------------------------------------------
    | Rider marker
    |--------------------------------------------------------------------------
    */

    NavigationState.riderMarker =
        new google.maps.Marker({

            position: {

                lat: riderLat,

                lng: riderLng

            },

            map:
                NavigationState.map,

            title:
                'Your Location',

            zIndex:
                999,

            icon: {

                url:
                    '/img/motorbike.png',

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
    |--------------------------------------------------------------------------
    | Pickup marker
    |--------------------------------------------------------------------------
    */

    const storeContainer =
        document.createElement(
            'div'
        );


    storeContainer.style.color =
        '#f25112';


    storeContainer.style.fontSize =
        '22px';


    storeContainer.style.filter =
        'drop-shadow(0 2px 4px rgba(0,0,0,.3))';


    const storeIcon =
        document.createElement(
            'i'
        );


    storeIcon.className =
        'fa-solid fa-store';


    storeContainer.appendChild(
        storeIcon
    );


    NavigationState.pickupMarker =
        new AdvancedMarkerElement({

            position: {

                lat: shopLat,

                lng: shopLng

            },

            map:
                NavigationState.map,

            title:
                'Pickup Point',

            content:
                storeContainer,

            zIndex:
                998

        });


    /*
    |--------------------------------------------------------------------------
    | Rider Info Window
    |--------------------------------------------------------------------------
    */

    NavigationState.riderInfoWindow =
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


    /*
    |--------------------------------------------------------------------------
    | Pickup Info Window
    |--------------------------------------------------------------------------
    */

    NavigationState.pickupInfoWindow =
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


    NavigationState.riderInfoWindow.open({

        anchor:
            NavigationState.riderMarker,

        map:
            NavigationState.map

    });


    NavigationState.pickupInfoWindow.open({

        anchor:
            NavigationState.pickupMarker,

        map:
            NavigationState.map

    });


    /*
    |--------------------------------------------------------------------------
    | Navigation button
    |--------------------------------------------------------------------------
    */

    NavigationState.navigationButton =
        document.getElementById(
            'startNavigationBtn'
        );


    if (
        NavigationState.navigationButton
    ) {

        NavigationState.navigationButton
            .addEventListener(
                'click',
                toggleNavigation
            );

    }


    NavigationState.initialized =
        true;


    console.log(
        'Delivery navigation map initialized.'
    );

}


/*
|--------------------------------------------------------------------------
| TOGGLE NAVIGATION
|--------------------------------------------------------------------------
*/

function toggleNavigation(event)
{

    if (event) {

        event.preventDefault();

        event.stopPropagation();

    }


    if (
        NavigationState.navigating
    ) {

        stopNavigation();

    } else {

        startNavigation();

    }

}


/*
|--------------------------------------------------------------------------
| START NAVIGATION
|--------------------------------------------------------------------------
*/

function startNavigation()
{

    if (
        !NavigationState.initialized
    ) {

        console.error(
            'Navigation map is not initialized.'
        );

        return;

    }


    if (
        !NavigationState.currentPosition
    ) {

        console.error(
            'Current rider position unavailable.'
        );

        return;

    }


    const pickup =
        NavigationState.pickupMarker.position;


    if (!pickup) {

        console.error(
            'Pickup position unavailable.'
        );

        return;

    }


    NavigationState.navigating =
        true;


    NavigationState.recalculating =
        false;


    setNavigationButton(
        true
    );


    requestNavigationRoute(
        NavigationState.currentPosition,
        {

            lat:
                pickup.lat,

            lng:
                pickup.lng

        }
    );


    console.log(
        'Navigation started.'
    );

}


/*
|--------------------------------------------------------------------------
| STOP NAVIGATION
|--------------------------------------------------------------------------
*/

function stopNavigation()
{

    NavigationState.navigating =
        false;


    NavigationState.recalculating =
        false;


    if (
        NavigationState.animationFrame
    ) {

        cancelAnimationFrame(
            NavigationState.animationFrame
        );

        NavigationState.animationFrame =
            null;

    }


    /*
    |--------------------------------------------------------------------------
    | Remove route
    |--------------------------------------------------------------------------
    */

    if (
        NavigationState.directionsRenderer
    ) {

        NavigationState.directionsRenderer
            .setDirections({
                routes: []
            });

    }


    NavigationState.routePath =
        [];


    /*
    |--------------------------------------------------------------------------
    | Restore map
    |--------------------------------------------------------------------------
    */

    if (
        NavigationState.map &&
        NavigationState.currentPosition
    ) {

        NavigationState.map.moveCamera({

            center:
                NavigationState.currentPosition,

            zoom:
                NAVIGATION_CONFIG.normalZoom,

            tilt: 0,

            heading: 0

        });

    }


    setNavigationButton(
        false
    );


    console.log(
        'Navigation stopped.'
    );

}


/*
|--------------------------------------------------------------------------
| NAVIGATION BUTTON VISUAL STATE
|--------------------------------------------------------------------------
*/

function setNavigationButton(
    active
)
{

    const button =
        NavigationState.navigationButton ||
        document.getElementById(
            'startNavigationBtn'
        );


    if (!button) {

        return;

    }


    if (active) {

        button.classList.add(
            'active'
        );


        button.setAttribute(
            'title',
            'Stop navigation'
        );


        button.setAttribute(
            'aria-label',
            'Stop navigation'
        );

    } else {

        button.classList.remove(
            'active'
        );


        button.setAttribute(
            'title',
            'Start navigation'
        );


        button.setAttribute(
            'aria-label',
            'Start navigation'
        );

    }

}


/*
|--------------------------------------------------------------------------
| REQUEST GOOGLE ROUTE
|--------------------------------------------------------------------------
*/

function requestNavigationRoute(
    origin,
    destination
)
{

    if (
        !NavigationState.directionsService
    ) {

        console.error(
            'Directions service unavailable.'
        );

        NavigationState.recalculating =
            false;

        return;

    }


    NavigationState.directionsService.route({

        origin:
            origin,

        destination:
            destination,

        travelMode:
            google.maps.TravelMode.DRIVING,

        provideRouteAlternatives:
            false

    }, (result, status) => {

        if (
            status !==
            'OK'
        ) {

            console.error(
                'Directions failed:',
                status
            );

            NavigationState.recalculating =
                false;

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Draw route
        |--------------------------------------------------------------------------
        */

        NavigationState.directionsRenderer
            .setDirections(
                result
            );


        /*
        |--------------------------------------------------------------------------
        | Save route path
        |--------------------------------------------------------------------------
        */

        NavigationState.routePath =
            result.routes[0]
                .overview_path || [];


        /*
        |--------------------------------------------------------------------------
        | Update route information
        |--------------------------------------------------------------------------
        */

        const leg =
            result.routes[0]
                .legs[0];


        const routeInfo =
            document.getElementById(
                'route-info'
            );


        if (routeInfo) {

            routeInfo.innerHTML = `

                <strong>
                    ${leg.distance.text}
                </strong>

                <br>

                ${leg.duration.text}

            `;

        }


        /*
        |--------------------------------------------------------------------------
        | Calculate heading
        |--------------------------------------------------------------------------
        */

        const heading =
            getRouteHeading(
                origin
            );


        /*
        |--------------------------------------------------------------------------
        | Move camera into navigation mode
        |--------------------------------------------------------------------------
        */

        if (
            NavigationState.navigating
        ) {

            moveNavigationCamera(
                origin,
                heading
            );

        }


        NavigationState.recalculating =
            false;


        console.log(
            'Navigation route ready:',
            leg.distance.text,
            leg.duration.text
        );

    });

}


/*
|--------------------------------------------------------------------------
| GET ROUTE HEADING
|--------------------------------------------------------------------------
*/

function getRouteHeading(
    position
)
{

    if (
        NavigationState.routePath.length <
        2
    ) {

        return 0;

    }


    const pos =
        position instanceof
        google.maps.LatLng

            ? position

            : new google.maps.LatLng(
                position.lat,
                position.lng
            );


    let closestIndex =
        0;


    let closestDistance =
        Infinity;


    for (
        let i = 0;
        i < NavigationState.routePath.length;
        i++
    ) {

        const distance =
            google.maps.geometry.spherical
                .computeDistanceBetween(
                    pos,
                    NavigationState.routePath[i]
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


    const lookAhead =
        Math.min(
            closestIndex + 5,
            NavigationState.routePath.length - 1
        );


    if (
        closestIndex ===
        lookAhead
    ) {

        if (
            closestIndex > 0
        ) {

            return google.maps.geometry.spherical
                .computeHeading(
                    NavigationState.routePath[
                        closestIndex - 1
                    ],
                    NavigationState.routePath[
                        closestIndex
                    ]
                );

        }


        return 0;

    }


    return google.maps.geometry.spherical
        .computeHeading(

            NavigationState.routePath[
                closestIndex
            ],

            NavigationState.routePath[
                lookAhead
            ]

        );

}


/*
|--------------------------------------------------------------------------
| NORMAL CAMERA
|--------------------------------------------------------------------------
*/

function moveNormalCamera(
    position,
    heading = 0
)
{

    if (
        !NavigationState.map
    ) {

        return;

    }


    NavigationState.map.moveCamera({

        center:
            position,

        zoom:
            NAVIGATION_CONFIG.normalZoom,

        tilt: 0,

        heading:
            heading

    });

}


/*
|--------------------------------------------------------------------------
| NAVIGATION CAMERA
|--------------------------------------------------------------------------
*/

function moveNavigationCamera(
    position,
    heading
)
{

    if (
        !NavigationState.map
    ) {

        return;

    }


    const radians =
        heading *
        Math.PI /
        180;


    /*
    |--------------------------------------------------------------------------
    | Put rider slightly below center
    |--------------------------------------------------------------------------
    */

    const center = {

        lat:
            position.lat() -
            Math.cos(radians) *
            NAVIGATION_CONFIG.cameraOffset,

        lng:
            position.lng() -
            Math.sin(radians) *
            NAVIGATION_CONFIG.cameraOffset

    };


    NavigationState.map.moveCamera({

        center:
            center,

        zoom:
            NAVIGATION_CONFIG.navigationZoom,

        tilt:
            NAVIGATION_CONFIG.navigationTilt,

        heading:
            heading

    });

}


/*
|--------------------------------------------------------------------------
| CHECK WHETHER RIDER IS OFF ROUTE
|--------------------------------------------------------------------------
*/

function isRiderOffRoute(
    position
)
{

    if (
        NavigationState.routePath.length === 0
    ) {

        return false;

    }


    let minimumDistance =
        Infinity;


    for (
        const routePoint
        of NavigationState.routePath
    ) {

        const distance =
            google.maps.geometry.spherical
                .computeDistanceBetween(
                    position,
                    routePoint
                );


        if (
            distance <
            minimumDistance
        ) {

            minimumDistance =
                distance;

        }

    }


    return (
        minimumDistance >
        NAVIGATION_CONFIG.offRouteThreshold
    );

}


/*
|--------------------------------------------------------------------------
| RECALCULATE ROUTE
|--------------------------------------------------------------------------
*/

function recalculateNavigation(
    riderPosition
)
{

    if (
        !NavigationState.navigating
    ) {

        return;

    }


    if (
        NavigationState.recalculating
    ) {

        return;

    }


    const pickup =
        NavigationState.pickupMarker
            ?.position;


    if (!pickup) {

        return;

    }


    NavigationState.recalculating =
        true;


    console.log(
        'Rider is off route. Recalculating...'
    );


    requestNavigationRoute(
        riderPosition,
        {

            lat:
                pickup.lat,

            lng:
                pickup.lng

        }
    );

}


/*
|--------------------------------------------------------------------------
| LIVE RIDER POSITION UPDATE
|--------------------------------------------------------------------------
*/

window.updateNavigationRiderPosition =
function (
    latitude,
    longitude,
    backendHeading = null,
    speed = 0
)
{

    if (
        !NavigationState.initialized ||
        !NavigationState.riderMarker
    ) {

        return;

    }


    latitude =
        parseFloat(
            latitude
        );


    longitude =
        parseFloat(
            longitude
        );


    if (
        Number.isNaN(latitude) ||
        Number.isNaN(longitude)
    ) {

        return;

    }


    const targetPosition =
        new google.maps.LatLng(
            latitude,
            longitude
        );


    /*
    |--------------------------------------------------------------------------
    | First position
    |--------------------------------------------------------------------------
    */

    if (
        !NavigationState.currentPosition
    ) {

        NavigationState.currentPosition =
            targetPosition;

    }


    const startPosition =
        NavigationState.currentPosition;


    /*
    |--------------------------------------------------------------------------
    | Backend GPS heading
    |--------------------------------------------------------------------------
    */

    let targetHeading =
        null;


    if (
        backendHeading !== null &&
        backendHeading !== undefined &&
        backendHeading !== '' &&
        !Number.isNaN(
            parseFloat(
                backendHeading
            )
        )
    ) {

        targetHeading =
            parseFloat(
                backendHeading
            );

    }


    /*
    |--------------------------------------------------------------------------
    | Otherwise use road heading
    |--------------------------------------------------------------------------
    */

    if (
        targetHeading === null
    ) {

        targetHeading =
            getRouteHeading(
                targetPosition
            );

    }


    /*
    |--------------------------------------------------------------------------
    | Off-route check
    |--------------------------------------------------------------------------
    */

    if (
        NavigationState.navigating &&
        !NavigationState.recalculating &&
        isRiderOffRoute(
            targetPosition
        )
    ) {

        recalculateNavigation(
            targetPosition
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Cancel previous marker animation
    |--------------------------------------------------------------------------
    */

    if (
        NavigationState.animationFrame
    ) {

        cancelAnimationFrame(
            NavigationState.animationFrame
        );

        NavigationState.animationFrame =
            null;

    }


    /*
    |--------------------------------------------------------------------------
    | Animate marker
    |--------------------------------------------------------------------------
    */

    const startTime =
        performance.now();


    function animate(
        currentTime
    )
    {

        const elapsed =
            currentTime -
            startTime;


        const progress =
            Math.min(
                elapsed /
                NAVIGATION_CONFIG.animationDuration,
                1
            );


        /*
        |--------------------------------------------------------------------------
        | Smooth easing
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | Move motorcycle
        |--------------------------------------------------------------------------
        */

        NavigationState.riderMarker
            .setPosition(
                position
            );


        NavigationState.currentPosition =
            position;


        /*
        |--------------------------------------------------------------------------
        | Navigation camera follows rider
        |--------------------------------------------------------------------------
        */

        if (
            NavigationState.navigating
        ) {

            moveNavigationCamera(
                position,
                targetHeading
            );

        }


        /*
        |--------------------------------------------------------------------------
        | Continue animation
        |--------------------------------------------------------------------------
        */

        if (
            progress < 1
        ) {

            NavigationState.animationFrame =
                requestAnimationFrame(
                    animate
                );

        } else {

            NavigationState.animationFrame =
                null;

        }

    }


    NavigationState.animationFrame =
        requestAnimationFrame(
            animate
        );

};


/*
|--------------------------------------------------------------------------
| BACKWARD COMPATIBILITY
|--------------------------------------------------------------------------
*/

window.updateRiderPosition =
function (
    newLat,
    newLng,
    backendHeading = null,
    speed = 0
)
{

    updateNavigationRiderPosition(
        newLat,
        newLng,
        backendHeading,
        speed
    );

};


/*
|--------------------------------------------------------------------------
| OPTIONAL GLOBAL NAVIGATION CONTROLS
|--------------------------------------------------------------------------
*/

window.startDeliveryNavigation =
function ()
{

    startNavigation();

};


window.stopDeliveryNavigation =
function ()
{

    stopNavigation();

};


window.isDeliveryNavigationActive =
function ()
{

    return NavigationState.navigating;

};


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
| SIMPLE TOAST
|--------------------------------------------------------------------------
*/

function showToast(
    message
)
{

    alert(
        message
    );

}


/*
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
| DELIVERY REQUEST SYSTEM
|--------------------------------------------------------------------------
|--------------------------------------------------------------------------
*/

const DeliveryState = {

    currentId:
        null,

    handledIds:
        new Set(),

    countdownTimer:
        null,

    csrfToken:
        null,

    elements:
        {}

};


/*
|--------------------------------------------------------------------------
| INITIALIZE DELIVERY REQUESTS
|--------------------------------------------------------------------------
*/

function initializeDeliveryRequests()
{

    DeliveryState.csrfToken =
        document.querySelector(
            'meta[name="csrf-token"]'
        )?.content;


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
            )

    };


    if (
        DeliveryState.elements.acceptButton
    ) {

        DeliveryState.elements.acceptButton
            .addEventListener(
                'click',
                acceptDelivery
            );

    }


    /*
    |--------------------------------------------------------------------------
    | Start polling
    |--------------------------------------------------------------------------
    */

    checkDeliveryRequest();


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
                '/rider/delivery/request'
            );


        const data =
            await response.json();


        if (
            !data.success ||
            !data.request
        ) {

            return;

        }


        const request =
            data.request;


        if (
            request.status !==
            'pending'
        ) {

            return;

        }


        if (
            DeliveryState.handledIds
                .has(request.id)
        ) {

            return;

        }


        DeliveryState.handledIds.add(
            request.id
        );


        DeliveryState.currentId =
            request.id;


        showDeliveryToast(
            request
        );

    }

    catch (error) {

        console.error(
            'Delivery request check failed:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| SHOW DELIVERY TOAST
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


    /*
    |--------------------------------------------------------------------------
    | SHOW TOAST
    |--------------------------------------------------------------------------
    */

    toast.classList.add(
        'active'
    );


    /*
    |--------------------------------------------------------------------------
    | PLAY DELIVERY ALERT
    |--------------------------------------------------------------------------
    |
    | This starts immediately when the toast appears.
    |
    */

    DeliveryNotificationSound.play();


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
| COUNTDOWN
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

                const now =
                    Date.now();


                const remaining =
                    expiry -
                    now;


                const seconds =
                    Math.ceil(
                        remaining /
                        1000
                    );


                if (
                    progress &&
                    total > 0
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
                    seconds > 0 &&
                    countdown
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
| HANDLE EXPIRED DELIVERY
|--------------------------------------------------------------------------
*/

function handleExpiredState(
    countdownElement
)
{

    if (
        countdownElement
    ) {

        countdownElement.textContent =
            'Expired';

    }


    hideDeliveryToast();

}


/*
|--------------------------------------------------------------------------
| ACCEPT DELIVERY
|--------------------------------------------------------------------------
*/

async function acceptDelivery()
{

    if (
        !DeliveryState.currentId
    ) {

        console.log(
            'No delivery request available'
        );

        return;

    }


    try {

        const response =
            await fetch(
                `/rider/delivery/${
                    DeliveryState.currentId
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
                            DeliveryState.csrfToken

                    }

                }
            );


        const data =
            await response.json();


        if (
            data.success
        ) {

            clearInterval(
                DeliveryState.countdownTimer
            );


            /*
            |--------------------------------------------------------------------------
            | Stop notification sound immediately
            |--------------------------------------------------------------------------
            */

            DeliveryNotificationSound.stop();


            hideDeliveryToast();


            DeliveryState.currentId =
                null;


            console.log(
                'Delivery accepted successfully'
            );


            /*
            |--------------------------------------------------------------------------
            | IMPORTANT
            |--------------------------------------------------------------------------
            |
            | Do NOT automatically start navigation here.
            |
            | The rider controls navigation using:
            |
            | #startNavigationBtn
            |
            |--------------------------------------------------------------------------
            */

        }

    }

    catch (error) {

        console.error(
            'Accept delivery failed:',
            error
        );

    }

}


/*
|--------------------------------------------------------------------------
| HIDE DELIVERY TOAST
|--------------------------------------------------------------------------
*/

function hideDeliveryToast()
{

    const {
        toast,
        progress
    } =
        DeliveryState.elements;


    /*
    |--------------------------------------------------------------------------
    | STOP DELIVERY SOUND
    |--------------------------------------------------------------------------
    */

    DeliveryNotificationSound.stop();


    if (toast) {

        toast.classList.remove(
            'active'
        );

    }


    if (progress) {

        progress.style.width =
            '100%';

    }


    clearInterval(
        DeliveryState.countdownTimer
    );


    DeliveryState.countdownTimer =
        null;

}
