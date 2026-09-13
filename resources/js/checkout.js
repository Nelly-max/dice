/*
|--------------------------------------------------------------------------
| Checkout Page
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", async () => {

    //------------------------------------------------------------------
    // Customer Delivery Location
    //------------------------------------------------------------------

    const deliveryLocation = JSON.parse(
        localStorage.getItem("delivery_location")
    );

    const addressDisplay = document.getElementById("checkout-address-text");

    if (!deliveryLocation) {

        console.warn("Delivery location not found.");

        if (addressDisplay) {
            addressDisplay.innerHTML =
                '<i class="fa-solid fa-map-pin"></i> No delivery location selected';
        }

        return;
    }

    //------------------------------------------------------------------
    // Display selected address
    //------------------------------------------------------------------

    if (addressDisplay) {

        addressDisplay.innerHTML =
            `<i class="fa-solid fa-map-pin"></i> ${deliveryLocation.address}`;

    }

    //------------------------------------------------------------------
    // Hidden form inputs
    //------------------------------------------------------------------

    document.getElementById("customer-lat").value =
        deliveryLocation.latitude;

    document.getElementById("customer-lng").value =
        deliveryLocation.longitude;

    document.getElementById("customer-county").value =
        deliveryLocation.county ?? "";

    document.getElementById("customer-town").value =
        deliveryLocation.town ?? "";

    //------------------------------------------------------------------
    // Calculate delivery
    //------------------------------------------------------------------

    try {

        const response = await fetch(
            `${window.location.origin}/cart/checkout/distance`,
            {
                method: "POST",

                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document
                        .querySelector('meta[name="csrf-token"]')
                        .content
                },

                body: JSON.stringify({
                    latitude: deliveryLocation.latitude,
                    longitude: deliveryLocation.longitude
                })
            }
        );

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        console.log("Delivery:", data);

        //------------------------------------------------------------------
        // Update Distance
        //------------------------------------------------------------------

        const distanceLabel = document.getElementById("distance-km-label");

        if (distanceLabel) {
            distanceLabel.textContent =
                `${Number(data.distance).toFixed(2)} km`;
        }

        //------------------------------------------------------------------
        // Update Delivery Fee
        //------------------------------------------------------------------

        const deliveryDisplay =
            document.getElementById("delivery-cost-display");

        deliveryDisplay.dataset.delivery = data.fee;

        deliveryDisplay.textContent =
            `Ksh ${Number(data.fee).toLocaleString()}`;
        
        document.getElementById("delivery-fee-input").value = data.fee;

        //------------------------------------------------------------------
        // Update Grand Total
        //------------------------------------------------------------------

        const subtotal = Number(
            document.getElementById("subtotal-display")
                .dataset.subtotal
        );

        const discount = Number(
            document.getElementById("discount-display")
                .dataset.discount
        );

        const total = subtotal + Number(data.fee) - discount;

        document.getElementById("grand-total-display").textContent =
            `Ksh ${total.toLocaleString()}`;

    } catch (error) {

        console.error("Delivery calculation failed:", error);

        const distanceLabel = document.getElementById("distance-km-label");

        if (distanceLabel) {
            distanceLabel.textContent = "Unavailable";
        }

        document.getElementById("delivery-cost-display").textContent =
            "Unavailable";
    }

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
if (checkbox.checked) {
    loadAccountDetails();
}

checkbox.addEventListener('change', function () {

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

document.getElementById('confirm-order-btn').addEventListener('click', function (e) {

    e.preventDefault();

    document.getElementById('checkout-form').submit();

});
