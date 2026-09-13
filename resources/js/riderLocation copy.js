// State Management encapsulated in a neat object
const RiderState = {
    watcher: null,
    interval: null,
    latestPosition: null
};

document.addEventListener('DOMContentLoaded', () => {
    const activator = document.querySelector('.activator.rider');
    const button = document.getElementById('riderOnlineBtn');

    if (!activator || !button) return;

    // Cache CSRF token once to prevent repeated DOM scans
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    activator.style.display = 'none';

    // 1. Initialise Rider Configuration
    fetch('/rider/init')
        .then(res => res.json())
        .then(data => {
            if (!data.has_rider_account || !['active', 'suspended'].includes(data.account_status)) {
                return;
            }

            activator.style.display = '';

            if (data.account_status === 'suspended') {
                button.textContent = "ACCOUNT SUSPENDED";
                button.disabled = true;
                button.classList.add('suspended');
                return;
            }

            button.disabled = false;
            
            // Fixed mismatch: Using online_status consistently 
            const currentStatus = data.online_status || data.status;
            setButtonStatus(button, currentStatus);

            if (['online', 'busy'].includes(currentStatus)) {
                startRiderTracking(csrfToken);
            } else {
                stopRiderTracking();
            }
        })
        .catch(err => console.error("Rider init error", err));

    // 2. Action Button Click Event
    button.addEventListener('click', () => {
        const status = button.dataset.status;

        if (['online', 'busy'].includes(status)) {
            updateRiderStatus('offline', button, csrfToken);
        } else {
            checkLocation(button, csrfToken);
        }
    });
});

// Helper: Sync button states and classes
function setButtonStatus(button, status) {
    if (!button) return;

    button.dataset.status = status;
    button.classList.remove('online', 'offline', 'busy');

    if (['online', 'busy'].includes(status)) {
        button.textContent = "GO OFFLINE";
        button.classList.add(status);
    } else {
        button.textContent = "GO ONLINE";
        button.classList.add('offline');
    }
}

// Helper: Handle direct background geolocation check
function checkLocation(button, csrfToken) {
    if (!navigator.geolocation) {
        showLocationModal();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            RiderState.latestPosition = parseCoords(position);
            updateRiderStatus('online', button, csrfToken);
        },
        (err) => {
            console.error("GPS ERROR", err);
            showLocationModal();
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

// Helper: Formats geolocation updates cleanly
function parseCoords(position) {
    return {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        heading: position.coords.heading,
        speed: position.coords.speed,
        accuracy: position.coords.accuracy
    };
}

// 3. Network: Update Rider Presence Status
async function updateRiderStatus(status, button, csrfToken) {
    try {
        const response = await fetch('/rider/status/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ status })
        });
        
        const data = await response.json();
        console.log("STATUS RESPONSE", data);

        if (data.success) {
            setButtonStatus(button, data.status);

            if (['online', 'busy'].includes(data.status)) {
                startRiderTracking(csrfToken);
            } else {
                stopRiderTracking();
            }
        } else {
            showToast(data.message ?? "Unable to update status");
        }
    } catch (err) {
        console.error("Failed to update status", err);
    }
}

// 4. Engine: Start Realtime Tracking Pipeline
function startRiderTracking(csrfToken) {
    if (RiderState.watcher) return;

    RiderState.watcher = navigator.geolocation.watchPosition(
        (position) => {
            RiderState.latestPosition = parseCoords(position);
        },
        (err) => {
            console.error("Tracking stopped", err);
            stopRiderTracking();
            const button = document.getElementById('riderOnlineBtn');
            updateRiderStatus('offline', button, csrfToken);
            showLocationModal();
        },
        { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
    );

    RiderState.interval = setInterval(() => {
        if (RiderState.latestPosition) {
            sendLocation(RiderState.latestPosition, csrfToken);
        }
    }, 5000);
}

// Engine: Complete teardown of location tracking components
function stopRiderTracking() {
    if (RiderState.watcher) {
        navigator.geolocation.clearWatch(RiderState.watcher);
        RiderState.watcher = null;
    }
    if (RiderState.interval) {
        clearInterval(RiderState.interval);
        RiderState.interval = null;
    }
    RiderState.latestPosition = null;
}

// 5. Network: Push data upstream
async function sendLocation(location, csrfToken) {
    try {
        const response = await fetch('/rider/location/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(location)
        });
        const data = await response.json();
        console.log("LOCATION SENT", data);
    } catch (err) {
        console.error("Location push error", err);
    }
}

// UI element construction updates
function showLocationModal() {
    let modal = document.getElementById('locationModal');

    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'locationModal';
        modal.innerHTML = `
            <div class="location-box">
                <i class="fa-solid fa-circle-info"></i>
                <h3>Location Required</h3>
                <p>Please turn on your device location services to go online and receive deliveries.</p>
                <button id="locationOkBtn">OK</button>
            </div>
        `;
        document.body.appendChild(modal);
    }

    modal.style.display = 'flex';
    document.getElementById('locationOkBtn').onclick = () => {
        modal.style.display = 'none';
    };
}

function showToast(message) {
    alert(message);
}





// =====================================================================
//      #Show accept order toast
// =====================================================================

// State management tracking encapsulated cleanly 
const DeliveryState = {
    currentId: null,
    handledIds: new Set(), // Set offers O(1) lookup speed instead of array scanning
    countdownTimer: null,
    csrfToken: null,
    elements: {} // Cached DOM Nodes
};

document.addEventListener('DOMContentLoaded', () => {
    // Cache persistent elements and metadata once up-front
    DeliveryState.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    DeliveryState.elements = {
        toast: document.getElementById('accept'),
        pickupStore: document.getElementById('pickupStore'),
        countdown: document.getElementById('acceptCountdown'),
        progress: document.getElementById('acceptProgress'),
        acceptButton: document.getElementById('acceptDeliveryBtn')
    };

    // Attach interaction events
    if (DeliveryState.elements.acceptButton) {
        DeliveryState.elements.acceptButton.addEventListener('click', acceptDelivery);
    }

    // Polling cycle: Immediately check, then repeat every 2 seconds
    checkDeliveryRequest();
    setInterval(checkDeliveryRequest, 2000);
});

/*
|--------------------------------------------------------------------------
| Check pending delivery request
|--------------------------------------------------------------------------
*/
async function checkDeliveryRequest() {
    try {
        const response = await fetch('/rider/delivery/request');
        const data = await response.json();

        if (!data.success || !data.request) return;

        const request = data.request;

        // Fast filtering validations
        if (request.status !== 'pending' || DeliveryState.handledIds.has(request.id)) {
            return;
        }

        // Track and lock processing for this ID
        DeliveryState.handledIds.add(request.id);
        DeliveryState.currentId = request.id;

        showDeliveryToast(request);
    } catch (error) {
        console.error('Delivery request check failed:', error);
    }
}

/*
|--------------------------------------------------------------------------
| Show toast
|--------------------------------------------------------------------------
*/
function showDeliveryToast(request) {
    const { toast, pickupStore } = DeliveryState.elements;
    if (!toast) return;

    if (pickupStore) {
        pickupStore.textContent = `Pickup Store: ${request.pickup_store ?? ''}`;
    }

    toast.classList.add('active');
    startCountdown(request.expires_at);
}

/*
|--------------------------------------------------------------------------
| Countdown
|--------------------------------------------------------------------------
*/
function startCountdown(expiresAt) {
    const { countdown, progress } = DeliveryState.elements;
    
    clearInterval(DeliveryState.countdownTimer);

    const expiry = new Date(expiresAt).getTime();
    const start = Date.now();
    const total = expiry - start;

    if (total <= 0) {
        handleExpiredState(countdown);
        return;
    }

    DeliveryState.countdownTimer = setInterval(() => {
        const now = Date.now();
        const remaining = expiry - now;
        const seconds = Math.ceil(remaining / 1000);

        if (progress && total > 0) {
            const percentage = Math.max(0, (remaining / total) * 100);
            progress.style.width = `${percentage}%`;
        }

        if (seconds > 0 && countdown) {
            countdown.textContent = `Accept Delivery (${seconds})`;
        }

        if (remaining <= 0) {
            clearInterval(DeliveryState.countdownTimer);
            handleExpiredState(countdown);
        }
    }, 100); // 100ms interval for fluid visual progress bars
}

// Sub-helper to manage expired countdown visual pipeline
function handleExpiredState(countdownElement) {
    if (countdownElement) {
        countdownElement.textContent = "Expired";
    }
    hideDeliveryToast();
}

/*
|--------------------------------------------------------------------------
| Accept delivery
|--------------------------------------------------------------------------
*/
async function acceptDelivery() {
    if (!DeliveryState.currentId) {
        console.log("No delivery request available");
        return;
    }

    try {
        const response = await fetch(`/rider/delivery/${DeliveryState.currentId}/accept`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-CSRF-TOKEN": DeliveryState.csrfToken
            }
        });
        
        const data = await response.json();

        if (data.success) {
            clearInterval(DeliveryState.countdownTimer);
            hideDeliveryToast();
            DeliveryState.currentId = null;
            console.log("Delivery accepted successfully");
        }
    } catch (error) {
        console.error("Accept delivery failed:", error);
    }
}

/*
|--------------------------------------------------------------------------
| Hide toast
|--------------------------------------------------------------------------
*/
function hideDeliveryToast() {
    const { toast, progress } = DeliveryState.elements;

    if (toast) {
        toast.classList.remove('active');
    }

    if (progress) {
        progress.style.width = "100%";
    }

    clearInterval(DeliveryState.countdownTimer);
}
