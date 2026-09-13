console.log('Cart JS loaded');

let currentProduct = {};

let pendingDeleteItemId = null;
let pendingDeleteForm = null;


document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrfToken) {
        console.error('CSRF token not found!');
        return;
    }

    initializeWithActiveThumbnail();
    setupThumbnails();
    setupCart(csrfToken);
    setupQuantityControls();
    setupCartQuantityButtons();
    startCartAutoSync();

    console.log('Initial product:', currentProduct);
});

/* ------------------ Product Page: Thumbnail Selection ------------------ */
function initializeWithActiveThumbnail() {
    const activeThumbnail = document.querySelector('.small-img.active');

    if (activeThumbnail && activeThumbnail.dataset) {
        const titleElement = document.getElementById('productTitle');
        const productName = titleElement ? titleElement.textContent.trim() : 'Gas Cylinder';

        currentProduct = {
            stockable_id: activeThumbnail.dataset.id || '',
            stockable_type: 'App\\Models\\CookingGas\\BusinessGasStock',
            subdivision_code: 'cooking_gas',
            quantity: 1,
            price: parseFloat(activeThumbnail.dataset.price) || 0,
            product_name: productName,
            business_name: activeThumbnail.dataset.business || '',
            image: activeThumbnail.dataset.image || '',
            size: activeThumbnail.dataset.size || ''
        };
    } else {
        const stockId = document.getElementById('productStockId')?.value || '';
        currentProduct = {
            stockable_id: stockId,
            stockable_type: 'App\\Models\\CookingGas\\BusinessGasStock',
            subdivision_code: 'cooking_gas',
            quantity: 1,
            price: parseFloat(document.getElementById('productPriceValue')?.value) || 0,
            product_name: document.getElementById('productName')?.value || '',
            business_name: document.getElementById('businessName')?.value || '',
            image: document.getElementById('productImage')?.value || '',
            size: document.getElementById('productSize')?.textContent || ''
        };
    }

    updateHiddenFields();
    updateProductDisplay();
}

function setupThumbnails() {

    const thumbnails = document.querySelectorAll('.small-img-col');
    const mainImage = document.getElementById('ProductImg');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function () {

            // const targetStockableId = this.getAttribute('data-item-id');
            const targetStockableId = this.getAttribute('data-stockable-id');
            const targetImageUrl = this.getAttribute('data-full-url');
            const targetLabel = this.getAttribute('data-label');
            const targetPrice = this.getAttribute('data-price');

            if (!targetStockableId) {
                console.error('Thumbnail missing data-item-id');
                return;
            }

            // 🚨 ALWAYS overwrite fully (no merging)
            window.currentProduct = {
                stockable_id: targetStockableId,
                stockable_type: document.getElementById('stockableType')?.value || '',
                subdivision_code: document.getElementById('subdivisionCode')?.value || '',
                business_account: document.getElementById('businessAccount')?.value || '',
                price: parseFloat(targetPrice) || 0,
                image: targetImageUrl || '',
                product_name: targetLabel || ''
            };

            // update main image
            if (mainImage && targetImageUrl) {
                mainImage.src = targetImageUrl;
            }

            // active state
            thumbnails.forEach(t => t.classList.remove('active-thumbnail'));
            this.classList.add('active-thumbnail');
        });
    });
}

function syncProductToHiddenFields(product) {
    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.value = value ?? '';
    };

    set('stockableId', product.stockable_id);
    set('stockableType', product.stockable_type);
    set('subdivisionCode', product.subdivision_code);
    set('businessAccount', product.business_account);
    set('productPriceValue', product.price);
    set('productName', product.product_name);
    set('productImage', product.image);
}

function updateHiddenFields() {
    const stockIdField = document.getElementById('productStockId');
    const priceField = document.getElementById('productPriceValue');
    const nameField = document.getElementById('productName');
    const businessField = document.getElementById('businessName');
    const imageField = document.getElementById('productImage');

    if (stockIdField) stockIdField.value = currentProduct.stockable_id;
    if (priceField) priceField.value = currentProduct.price;
    if (nameField) nameField.value = currentProduct.product_name;
    if (businessField) businessField.value = currentProduct.business_name;
    if (imageField) imageField.value = currentProduct.image;
}

function updateProductDisplay() {
    const titleElement = document.getElementById('productTitle');
    if (titleElement) titleElement.textContent = currentProduct.product_name;

    const priceDisplay = document.getElementById('productPrice');
    if (priceDisplay) priceDisplay.innerHTML = `<h3>Ksh ${currentProduct.price.toLocaleString()}</h3>`;

    const sizeDisplay = document.getElementById('productSize');
    if (sizeDisplay) sizeDisplay.textContent = currentProduct.size;

    const businessDisplay = document.querySelector('.product-details p');
    if (businessDisplay && currentProduct.business_name) businessDisplay.textContent = currentProduct.business_name;
}

/* ------------------ Product Page: Add to Cart ------------------ */

/* ------------------ Shared Add to Cart ------------------ */
function setupCart(csrfToken) {

    /*
     * Use querySelectorAll so the same cart logic works for:
     *
     * 1. Product listing basket icons
     * 2. Product detail "Add to cart" button
     *
     * No duplicate cart implementation is required.
     */
    const orderBtns = document.querySelectorAll('.add-to-cart-btn');

    if (!orderBtns.length) return;

    orderBtns.forEach(orderBtn => {

        orderBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            /*
             * =====================================================
             * PRODUCT DATA
             * =====================================================
             *
             * First use data-* attributes from the clicked button.
             *
             * This is what the product listing uses.
             *
             * If those values are not present, fall back to the
             * existing hidden fields on the product detail page.
             */

            const data = orderBtn.dataset;

            const baseProduct = {
                business_account:
                    data.businessAccount ||
                    document.getElementById('businessAccount')?.value ||
                    '',

                stockable_id:
                    data.stockableId ||
                    document.getElementById('stockableId')?.value ||
                    '',

                stockable_type:
                    data.stockableType ||
                    document.getElementById('stockableType')?.value ||
                    'retail_inventory',

                subdivision_code:
                    data.subdivisionCode ||
                    document.getElementById('subdivisionCode')?.value ||
                    'home_market',

                product_name:
                    data.productName ||
                    document.getElementById('productName')?.value ||
                    '',

                image:
                    data.imageUrl ||
                    document.getElementById('productImage')?.value ||
                    '',

                price:
                    data.price ||
                    document.getElementById('productPriceValue')?.value ||
                    0,

                variant_label:
                    data.variantLabel ||
                    document.getElementById('variantLabel')?.value ||
                    ''
            };


            /*
             * =====================================================
             * QUANTITY
             * =====================================================
             *
             * On the product listing there is no quantity selector,
             * therefore quantity defaults to 1.
             *
             * On the product detail page, use .quantity-val.
             */

            let quantity = 1;

            const qtyInput = orderBtn.closest('.btns')?.querySelector('.quantity-val')
                || document.querySelector('.quantity-val');

            if (qtyInput) {
                const parsed = parseInt(qtyInput.value, 10);

                if (!isNaN(parsed) && parsed > 0) {
                    quantity = parsed;
                }
            }

            /*
             * Keep compatibility with your existing global quantity.
             */
            if (window.currentQuantity) {

                const parsedGlobal = parseInt(window.currentQuantity, 10);

                if (!isNaN(parsedGlobal) && parsedGlobal > 0) {
                    quantity = parsedGlobal;
                }
            }


            /*
             * =====================================================
             * FINAL PAYLOAD
             * =====================================================
             */

            const productData = {
                business_account: baseProduct.business_account,
                stockable_id: baseProduct.stockable_id,
                stockable_type: baseProduct.stockable_type,
                subdivision_code: baseProduct.subdivision_code,
                quantity: quantity,
                shipment_type: 'quick',
                _token: csrfToken
            };


            /*
             * =====================================================
             * VALIDATION
             * =====================================================
             */

            if (!productData.stockable_id || !productData.stockable_type) {

                console.error('Invalid cart product:', {
                    button: orderBtn,
                    product: baseProduct
                });

                showNotification('Invalid product selection', 'error');

                return;
            }

            if (!productData.business_account) {

                console.error(
                    'Business account missing for cart item:',
                    baseProduct
                );

                showNotification('Shop information is missing', 'error');

                return;
            }

            if (!productData.quantity || productData.quantity < 1) {

                showNotification('Invalid quantity', 'error');

                return;
            }


            /*
             * =====================================================
             * LOADING STATE
             * =====================================================
             */

            const originalHTML = orderBtn.innerHTML;

            orderBtn.innerHTML =
                '<i class="fas fa-spinner fa-spin"></i>' +
                (orderBtn.tagName === 'H4' ? ' Adding...' : '');

            orderBtn.style.pointerEvents = 'none';


            /*
             * =====================================================
             * ADD TO CART
             * =====================================================
             */

            fetch('/cart/add', {
                method: 'POST',

                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },

                body: JSON.stringify(productData)
            })

            .then(response => {

                if (!response.ok) {
                    throw new Error(
                        `Cart request failed: ${response.status}`
                    );
                }

                return response.json();
            })

            .then(data => {

                if (data.success) {

                    showNotification(
                        '✓ Added to cart!',
                        'success'
                    );

                    /*
                     * Update cart counter.
                     */
                    if (data.cart_count !== undefined) {
                        updateCartCount(data.cart_count);
                    }

                    /*
                     * Reset product-page quantity after successful
                     * addition.
                     */
                    window.currentQuantity = 1;

                    if (qtyInput) {
                        qtyInput.value = 1;
                    }

                } else {

                    showNotification(
                        data.message || 'Failed to add item to cart',
                        'error'
                    );
                }
            })

            .catch(err => {

                console.error('Add to cart error:', err);

                showNotification(
                    'Network error',
                    'error'
                );
            })

            .finally(() => {

                /*
                 * Restore the original button/icon.
                 */
                orderBtn.innerHTML = originalHTML;
                orderBtn.style.pointerEvents = 'auto';
            });

        });

    });
}

/* ------------------ Product Page: Quantity Controls ------------------ */
function setupQuantityControls() {
    const decBtn = document.querySelector('.quantity-dec');
    const incBtn = document.querySelector('.quantity-inc');
    const qtyInput = document.querySelector('.quantity-input');

    if (decBtn && incBtn && qtyInput) {
        decBtn.addEventListener('click', () => {
            let qty = parseInt(qtyInput.value) || 1;
            if (qty > 1) qtyInput.value = --qty;
            currentProduct.quantity = qty;
        });
        incBtn.addEventListener('click', () => {
            let qty = parseInt(qtyInput.value) || 1;
            qtyInput.value = ++qty;
            currentProduct.quantity = qty;
        });
    }
}


/* ------------------ Cart Page: Update Quantity Buttons ------------------ */
function setupCartQuantityButtons() {

    const cartForms = document.querySelectorAll('.cart-item-form');

    cartForms.forEach(form => {

        const decBtn = form.querySelector('.dec');
        const incBtn = form.querySelector('.inc');
        const qtyInput = form.querySelector('input[name="quantity"]');
        const itemId = form.querySelector('input[name="item_id"]')?.value;

        if (!decBtn || !incBtn || !qtyInput || !itemId) {
            return;
        }

        // ============================
        // DECREASE
        // ============================
        decBtn.addEventListener('click', () => {

            const qty = parseInt(qtyInput.value) || 1;

            if (qty === 1) {

                pendingDeleteItemId = itemId;
                pendingDeleteForm = form;

                showModal('deleteItem');

                return;
            }

            updateCartItem(itemId, 'decrease', form);
        });

        // ============================
        // INCREASE
        // ============================
        incBtn.addEventListener('click', () => {
            updateCartItem(itemId, 'increase', form);
        });

    });
}

function updateCartItem(itemId, action, form) {

    const token = document.querySelector('meta[name="csrf-token"]').content;

    fetch('/cart/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            item_id: itemId,
            action: action
        })
    })
    .then(res => res.json())
    .then(data => {

        if (!data.success) {
            showNotification(data.message || 'Failed to update cart', 'error');
            return;
        }

        // =====================================================
        // ITEM REMOVED
        // =====================================================
        if (data.deleted) {

            const cartItem =
                form.closest('.cart-item') ||
                form.closest('.cart-product') ||
                form.closest('[data-cart-item]');

            if (cartItem) {
                cartItem.remove();
            } else {
                location.reload();
            }

            showNotification('Item removed from cart', 'success');

            if (data.cart_count !== undefined) {
                updateCartCount(data.cart_count);
            }

            return;
        }

        // =====================================================
        // UPDATE QUANTITY INPUT
        // =====================================================
        const qtyInput = form.querySelector('input[name="quantity"]');

        if (qtyInput && data.quantity !== undefined) {
            qtyInput.value = data.quantity;
        }

        // =====================================================
        // UPDATE CART COUNT
        // =====================================================
        if (data.cart_count !== undefined) {
            updateCartCount(data.cart_count);
        }

        // =====================================================
        // UPDATE SHIPMENT SUBTOTAL
        // =====================================================
        if (data.item_subtotal) {

            const subtotalEl = form.closest('.cart-items')
                ?.querySelector('.sub-total .cost h4:nth-child(2)');

            if (subtotalEl) {
                subtotalEl.textContent =
                    `KSH ${Number(data.item_subtotal).toLocaleString(undefined,{
                        minimumFractionDigits:2
                    })}`;
            }
        }

        // =====================================================
        // UPDATE CART TOTAL
        // =====================================================
        if (data.total) {

            const totalEl = document.querySelector(
                '.cart-left .sub-total .cost h4:nth-child(2)'
            );

            if (totalEl) {
                totalEl.textContent =
                    `KSH ${Number(data.total).toLocaleString(undefined,{
                        minimumFractionDigits:2
                    })}`;
            }
        }

        showNotification('Cart updated!', 'success');
    })
    .catch(err => {
        console.error(err);
        showNotification('Network error', 'error');
    });
}

/* ------------------ Notifications ------------------ */
function showNotification(message, type = 'success') {
    const oldNotification = document.getElementById('cart-notification');
    if (oldNotification) oldNotification.remove();

    const notification = document.createElement('div');
    notification.id = 'cart-notification';
    notification.style.cssText = `
        position: fixed; top: 20px; right: 20px;
        background: ${type==='success'?'#28a745':type==='error'?'#dc3545':'#007bff'};
        color: white; padding: 15px 20px; border-radius: 5px;
        display: flex; align-items: center; gap: 10px; z-index: 9999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas ${type==='success'?'fa-check-circle':type==='error'?'fa-exclamation-circle':'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn { from {transform: translateX(100%); opacity:0;} to {transform: translateX(0); opacity:1;} }
    `;
    document.head.appendChild(style);
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(()=>{notification.remove(); style.remove();}, 300);
    }, 3000);
}

/* ------------------ Cart Count ------------------ */
function updateCartCount(count) {
    const cartCountEl = document.querySelector('.cart-count');
    
    if (!cartCountEl) return;
    
    cartCountEl.textContent = count;
    
    if (count > 0) {
        cartCountEl.style.display = 'flex';
    } else {
        cartCountEl.style.display = 'none';
    }
    
    window.cartCount = count;
}

/* ------------------ Auto sync ------------------ */
let cartSyncInterval = null;

function startCartAutoSync() {
    if (cartSyncInterval) return; // prevent duplicates

    cartSyncInterval = setInterval(() => {
        fetch('/cart/count')
            .then(res => res.json())
            .then(data => updateCartCount(data.count))
            .catch(console.error);
    }, 5000);
}






// ================================================================
//   #CHECKOUT BUTTON
// ================================================================

// document.addEventListener("DOMContentLoaded", () => {

//     const checkoutButtons = document.querySelectorAll(".btn-proceed-checkout");

//     checkoutButtons.forEach(button => {

//         button.addEventListener("click", (e) => {

//             e.preventDefault();

//             const shipmentContainer = button.closest(".cart-items");

//             if (!shipmentContainer) {
//                 console.warn("Shipment container not found.");
//                 return;
//             }

//             // ----------------------------------------------------
//             // Shipment Information
//             // ----------------------------------------------------

//             const itemRows = shipmentContainer.querySelectorAll(".cart-item");

//             const shipmentTitle =
//                 shipmentContainer.querySelector(".shipment-title")?.textContent.trim() ??
//                 "Shipment";

//             const subtotal =
//                 Number(
//                     shipmentContainer
//                         .querySelector(".subtotal-value")
//                         ?.dataset.subtotal ?? 0
//                 );

//             const delivery =
//                 Number(
//                     shipmentContainer
//                         .querySelector(".delivery-value")
//                         ?.dataset.delivery ?? 0
//                 );

//             const discount =
//                 Number(
//                     shipmentContainer
//                         .querySelector(".discount-value")
//                         ?.dataset.discount ?? 0
//                 );

//             const total = subtotal + delivery - discount;

//             // ----------------------------------------------------
//             // Build subdivision payload
//             // ----------------------------------------------------

//             const uniqueSubdivisions = {};
//             const globalStockIds = new Set();

//             itemRows.forEach(item => {

//                 const subId = item.dataset.subdivisionId;
//                 const dbConn = item.dataset.dbConnection || "mysql";
//                 const name = item.dataset.businessName;
//                 const lat = item.dataset.businessLat;
//                 const lng = item.dataset.businessLng;
//                 const stockId = item.dataset.itemStockableId;

//                 if (!subId) {
//                     return;
//                 }

//                 if (stockId) {
//                     globalStockIds.add(Number(stockId));
//                 }

//                 if (!uniqueSubdivisions[subId]) {

//                     uniqueSubdivisions[subId] = {
//                         subdivision_id: Number(subId),
//                         db_connection: dbConn,
//                         business_name: name || "",
//                         latitude: lat ? Number(lat) : null,
//                         longitude: lng ? Number(lng) : null,
//                         stockable_ids: new Set()
//                     };

//                 }

//                 if (stockId) {
//                     uniqueSubdivisions[subId]
//                         .stockable_ids
//                         .add(Number(stockId));
//                 }

//             });

//             const shipmentsArrayPayload = Object.values(uniqueSubdivisions).map(subdivision => ({
//                 subdivision_id: subdivision.subdivision_id,
//                 db_connection: subdivision.db_connection,
//                 business_name: subdivision.business_name,
//                 latitude: subdivision.latitude,
//                 longitude: subdivision.longitude,
//                 stockable_ids: [...subdivision.stockable_ids]
//             }));

//             if (!shipmentsArrayPayload.length) {

//                 alert(
//                     "Fulfillment error: Could not trace business coordinates for any item inside this shipment."
//                 );

//                 return;

//             }

//             // ----------------------------------------------------
//             // Save shipment breakdown
//             // ----------------------------------------------------

//             localStorage.setItem(
//                 "checkout_shipment_breakdown",
//                 JSON.stringify(shipmentsArrayPayload)
//             );

//             localStorage.setItem(
//                 "checkout_stockable_ids",
//                 JSON.stringify([...globalStockIds])
//             );

//             // ----------------------------------------------------
//             // Save order summary
//             // ----------------------------------------------------

//             localStorage.setItem(
//                 "checkout_summary",
//                 JSON.stringify({

//                     shipmentTitle: shipmentTitle,

//                     itemCount: itemRows.length,

//                     subtotal: subtotal,

//                     delivery: delivery,

//                     discount: discount,

//                     total: total

//                 })
//             );

//             // ----------------------------------------------------
//             // Redirect
//             // ----------------------------------------------------

//             const targetCheckoutUrl = button.dataset.checkoutUrl;

//             if (targetCheckoutUrl) {
//                 window.location.href = targetCheckoutUrl;
//             }

//         });

//     });

// });



// ================================================================
// ================================================================





// ================================================================
//   #CHECKOUT BUTTON
// ================================================================

document.addEventListener("DOMContentLoaded", () => {

    const checkoutButtons = document.querySelectorAll(".btn-proceed-checkout");

    checkoutButtons.forEach(button => {

        button.addEventListener("click", async (e) => {

            e.preventDefault();

            const shipmentContainer = button.closest(".cart-items");

            if (!shipmentContainer) {
                console.warn("Shipment container not found.");
                return;
            }

            //------------------------------------------------------------------
            // Shipment Information
            //------------------------------------------------------------------

            const itemRows = shipmentContainer.querySelectorAll(".cart-item");

            const businesses = {};

            itemRows.forEach(item => {

                const cartId = Number(item.dataset.cartId);
                const stockableId = Number(item.dataset.itemStockableId);
                const stockableType = item.dataset.stockableType;
                const subdivisionId = Number(item.dataset.subdivisionId);
                const dbConnection = item.dataset.dbConnection;
                const businessAccount = item.dataset.businessAccount;

                if (
                    !cartId ||
                    !stockableId ||
                    !stockableType ||
                    !businessAccount
                ) {
                    return;
                }

                if (!businesses[businessAccount]) {

                    businesses[businessAccount] = {
                        business_account: businessAccount,
                        subdivision_id: subdivisionId,
                        db_connection: dbConnection,
                        items: []
                    };

                }

                businesses[businessAccount].items.push({
                    cart_id: cartId,
                    stockable_id: stockableId,
                    stockable_type: stockableType,
                    subdivision_id: subdivisionId
                });

            });

            const shipmentPayload = {
                shipment: Object.values(businesses)
            };

            console.log("Checkout Payload:");
            console.log(JSON.stringify(shipmentPayload, null, 2));

            try {

                const response = await fetch(
                    `${window.location.origin}/cart/checkout/select`,
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document
                                .querySelector('meta[name="csrf-token"]')
                                .content
                        },
                        body: JSON.stringify(shipmentPayload)
                    }
                );

                if (!response.ok) {

                    const text = await response.text();

                    console.error(text);

                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (!data.success) {
                    throw new Error(
                        data.message ?? "Unable to prepare checkout."
                    );
                }

                const targetCheckoutUrl = button.dataset.checkoutUrl;

                if (targetCheckoutUrl) {
                    window.location.href = targetCheckoutUrl;
                }

            } catch (error) {

                console.error(error);

                alert(
                    "Unable to proceed to checkout. Please try again."
                );

            }

        });

    });

});














/*
|--------------------------------------------------------------------------
| OPEN PAYMENT PAGE
|--------------------------------------------------------------------------
*/

// document.getElementById('confirm-order-btn').addEventListener('click', function (e) {

//     e.preventDefault();

//     const paymentMethod = document.querySelector(
//         'input[name="payment_method"]:checked'
//     ).value;

//     switch (paymentMethod) {

//         case 'mpesa':
//             window.location.href = '/payment/mpesa';
//             break;

//         case 'card':
//             window.location.href = '/payment/card';
//             break;

//         case 'cash':
//             window.location.href = '/payment/cash';
//             break;
//     }

// });




/*
|--------------------------------------------------------------------------
| Delivery Location
|--------------------------------------------------------------------------
*/

// document.addEventListener("DOMContentLoaded", () => {
//     // 1. Fetch your user location data string from local storage
//     // If your app stores this under a single parent key string (e.g. 'user_location_object'), 
//     // update the string key inside the getItem wrapper below:
//     const locationDataString = localStorage.getItem('delivery_location') || localStorage.getItem('location');

//     if (locationDataString) {
//         try {
//             // Parse the JSON string into a readable JavaScript dictionary object
//             const locationData = JSON.parse(locationDataString);

//             const lat = locationData.latitude;
//             const lng = locationData.longitude;
//             const address = locationData.address || `${locationData.town}, ${locationData.county}`;

//             if (lat && lng) {
//                 // Populate the hidden layout tracking variables for database persistence later
//                 document.getElementById("customer-lat").value = lat;
//                 document.getElementById("customer-lng").value = lng;
//                 document.getElementById("customer-county").value = locationData.county || '';
//                 document.getElementById("customer-town").value = locationData.town || '';

//                 // Update text display UI elements
//                 document.getElementById("checkout-address-text").innerHTML = `<i class="fa-solid fa-map-pin"></i> ${address}`;

//                 // Automatically trigger the road distance routing price calculation check
//                 fetchRoadDeliveryFee(parseFloat(lat), parseFloat(lng));
//             } else {
//                 handleMissingLocation();
//             }
//         } catch (e) {
//             console.error("Error decoding storage variables syntax:", e);
//             handleMissingLocation();
//         }
//     } else {
//         handleMissingLocation();
//     }
// });

// function handleMissingLocation() {
//     document.getElementById("checkout-address-text").innerHTML = 
//         `<div style="color:red;"><i class="fa-solid fa-triangle-exclamation"></i> You have not selected a delivery location.</div>`;
    
//     const confirmBtn = document.getElementById("confirm-order-btn");
//     if (confirmBtn) confirmBtn.disabled = true;
// }

// function fetchRoadDeliveryFee(lat, lng) {
//     const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
//     const confirmBtn = document.getElementById("confirm-order-btn");
    
//     if (confirmBtn) confirmBtn.disabled = true;

//     // Send payload targets asynchronously to your Laravel Controller
//     fetch("/checkout/calculate-delivery", {
//         method: "POST",
//         headers: {
//             "Content-Type": "application/json",
//             "X-CSRF-TOKEN": csrfToken
//         },
//         body: JSON.stringify({
//             customer_lat: lat,
//             customer_lng: lng
//         })
//     })
//     .then(response => response.json())
//     .then(data => {
//         if (confirmBtn) confirmBtn.disabled = false;

//         if (data.success) {
//             const fee = parseFloat(data.delivery_fee);
//             const distance = data.distance_km;

//             // Dynamically populate order summary targets 
//             document.getElementById("delivery-cost-display").innerText = `Ksh ${fee.toFixed(0)}`;
//             document.getElementById("distance-km-label").innerText = `(${distance} km away via road)`;

//             // Accumulate invoice sum updates
//             const subtotal = parseFloat(document.getElementById("subtotal-display").getAttribute("data-subtotal"));
//             const grandTotal = subtotal + fee;

//             document.getElementById("grand-total-display").innerText = `Ksh ${grandTotal.toFixed(0)}`;
//         } else {
//             alert(data.error || "Could not map a driving route to your saved location.");
//         }
//     })
//     .catch(error => {
//         if (confirmBtn) confirmBtn.disabled = false;
//         console.error("AJAX Calculation Request Failed:", error);
//     });
// }
