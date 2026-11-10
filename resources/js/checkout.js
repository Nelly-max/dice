/*
|--------------------------------------------------------------------------
| Checkout Page
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", async () => {

    // this file loads on every cart/checkout/payment page; only the checkout page has the form
    const form = document.getElementById("checkout-form");
    if (!form) return;

    //------------------------------------------------------------------
    // Delivery location: the one saved while shopping (the server put it
    // in the page). It can't be changed here.
    //------------------------------------------------------------------

    const money = (n) => `KES ${Math.round(Number(n) || 0).toLocaleString()}`;
    const deliveryDisplay = document.getElementById("delivery-cost-display");
    const hasLocation = document.querySelector("[data-has-location]")?.dataset.hasLocation === "1";

    const setTotals = (fee) => {
        const subtotal = Number(document.getElementById("subtotal-display").dataset.subtotal);
        const discount = Number(document.getElementById("discount-display").dataset.discount);
        const total = subtotal + Number(fee || 0) - discount;
        document.getElementById("grand-total-display").textContent = money(total);
        document.querySelectorAll(".js-confirm-total").forEach(el => { el.textContent = money(total); });
    };

    if (!hasLocation) {
        if (deliveryDisplay) deliveryDisplay.textContent = "Set location";
        form.dataset.deliveryReady = "";
        return;
    }

    //------------------------------------------------------------------
    // Delivery cost: road distance from each shop to the saved location,
    // priced by the distance bands (CheckoutController@calculateDistance)
    //------------------------------------------------------------------

    try {

        const response = await fetch(
            `${window.location.origin}/cart/checkout/distance`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: "{}"
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        // distance per shop
        (data.businesses || []).forEach(biz => {
            const km = document.querySelector(`[data-route-account="${CSS.escape(String(biz.account))}"] [data-route-km]`);
            if (km) km.textContent = `${Number(biz.distance).toFixed(1)} km`;
        });

        if (!data.complete || data.fee === null || data.fee === undefined) {
            throw new Error("Road distance unavailable for a shop");
        }

        deliveryDisplay.dataset.delivery = data.fee;
        deliveryDisplay.textContent = Number(data.fee) > 0 ? money(data.fee) : "Free";
        document.getElementById("delivery-fee-input").value = data.fee;
        form.dataset.deliveryReady = "1";

        setTotals(data.fee);

    } catch (error) {

        console.error("Delivery calculation failed:", error);

        form.dataset.deliveryReady = "";
        if (deliveryDisplay) {
            deliveryDisplay.innerHTML = 'Unavailable <button type="button" class="cx-link" data-retry-delivery>Retry</button>';
        }
    }

});

// "Retry" after a failed delivery cost: reload the page (cheap, the route is cached on the server)
document.addEventListener("click", (e) => {
    if (e.target.closest && e.target.closest("[data-retry-delivery]")) location.reload();
});


/*
|--------------------------------------------------------------------------
| Customer Details
|--------------------------------------------------------------------------
*/

const checkbox = document.getElementById('use-account-details');

const name = document.getElementById('customer-name');
const email = document.getElementById('customer-email');
const phone = document.getElementById('customer-phone');
const altPhone = document.getElementById('customer-alt-phone');

function clearFields() {
    name.value = '';
    email.value = '';
    phone.value = '';
    altPhone.value = '';
}

function loadAccountDetails() {

    clearFields();

    fetch('/account-details',{
        method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {

            name.value = data.name ?? '';
            email.value = data.email ?? '';
            phone.value = data.phone ?? '';

        })
        .catch(error => {
            console.error(error);
        });

}

// Load automatically if checked by default
if (checkbox?.checked) {
    loadAccountDetails();
}

checkbox?.addEventListener('change', function () {

    if (this.checked) {
        loadAccountDetails();
    } else {
        clearFields();
    }

});


/*
|--------------------------------------------------------------------------
| SELECT PAYMENT METHOD
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {

    const methods = document.querySelectorAll('.payment-method');

    methods.forEach(method => {

        method.addEventListener('click', () => {

            methods.forEach(item => {
                item.classList.remove('active');
            });

            method.classList.add('active');

            method.querySelector('input[type="radio"]').checked = true;

        });

    });

});

/*
|--------------------------------------------------------------------------
| CREATE CHECKOUT INVOICE
|--------------------------------------------------------------------------
*/

document.getElementById('checkout-form')?.addEventListener('submit', function (e) {

    // no delivery location, or its cost isn't worked out yet: don't send the order
    if (!document.getElementById('customer-lat')?.value) {
        e.preventDefault();
        if (window.notify) window.notify.error('Go back to the shop and set your delivery location first.');
        return;
    }
    if (this.dataset.deliveryReady !== '1') {
        e.preventDefault();
        if (window.notify) window.notify.error('The delivery cost is still being worked out. Please wait a moment or tap Retry.');
        return;
    }

    if (!this.reportValidity()) {
        e.preventDefault();
        return;
    }

    // one order per tap
    if (this.dataset.sending) {
        e.preventDefault();
        return;
    }
    this.dataset.sending = '1';
    this.querySelectorAll('button[type="submit"], button[form="checkout-form"]').forEach(btn => {
        btn.disabled = true;
        btn.classList.add('is-loading');
    });
    document.querySelectorAll('button[form="checkout-form"]').forEach(btn => {
        btn.disabled = true;
        btn.classList.add('is-loading');
    });
});
