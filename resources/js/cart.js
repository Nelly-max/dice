console.log('Cart JS loaded');

let currentProduct = {};

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
function setupCart(csrfToken) {

    const orderBtn = document.querySelector('.add-to-cart-btn');
    if (!orderBtn) return;

    orderBtn.addEventListener('click', function (e) {
        e.preventDefault();

        // ==============================
        // SAFE PRODUCT SOURCE
        // ==============================
        const baseProduct = window.currentProduct || {
            business_account: document.getElementById('businessAccount')?.value || '',
            stockable_id: document.getElementById('stockableId')?.value || '',
            stockable_type: document.getElementById('stockableType')?.value || '',
            subdivision_code: document.getElementById('subdivisionCode')?.value || ''
        };

        // ==============================
        // SAFE QUANTITY (CRITICAL FIX)
        // ==============================
        let quantity = 1;

        const qtyInput = document.querySelector('.quantity-val');
        if (qtyInput) {
            const parsed = parseInt(qtyInput.value, 10);
            if (!isNaN(parsed) && parsed > 0) {
                quantity = parsed;
            }
        }

        // fallback to global if needed
        if (window.currentQuantity) {
            const parsedGlobal = parseInt(window.currentQuantity, 10);
            if (!isNaN(parsedGlobal) && parsedGlobal > 0) {
                quantity = parsedGlobal;
            }
        }

        // ==============================
        // FINAL PAYLOAD (NO UNDEFINED VALUES)
        // ==============================
        const productData = {
            business_account: baseProduct.business_account || '',
            stockable_id: baseProduct.stockable_id || '',
            stockable_type: baseProduct.stockable_type || '',
            subdivision_code: baseProduct.subdivision_code || '',
            quantity: quantity,
            shipment_type: 'quick',
            _token: csrfToken
        };

        // ==============================
        // VALIDATION
        // ==============================
        if (!productData.stockable_id || !productData.stockable_type) {
            showNotification('Invalid product selection', 'error');
            return;
        }

        if (!productData.quantity || productData.quantity < 1) {
            showNotification('Invalid quantity', 'error');
            return;
        }

        // ==============================
        // LOADING STATE
        // ==============================
        const originalHTML = orderBtn.innerHTML;
        orderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        orderBtn.style.pointerEvents = 'none';

        // ==============================
        // REQUEST
        // ==============================
        fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(productData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('✓ Added to cart!', 'success');
                updateCartCount(data.cart_count || 0);

                // reset quantity safely
                window.currentQuantity = 1;
                if (qtyInput) qtyInput.value = 1;

            } else {
                showNotification(data.message || 'Failed', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Network error', 'error');
        })
        .finally(() => {
            orderBtn.innerHTML = originalHTML;
            orderBtn.style.pointerEvents = 'auto';
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
        const itemId = form.querySelector('input[name="item_id"]').value;

        decBtn.addEventListener('click', () => {
            let qty = parseInt(qtyInput.value) || 1;
            if (qty > 1) {
                qty--;
                qtyInput.value = qty;
                updateCartItem(itemId, qty, form);
            }
        });

        incBtn.addEventListener('click', () => {
            let qty = parseInt(qtyInput.value) || 1;
            qty++;
            qtyInput.value = qty;
            updateCartItem(itemId, qty, form);
        });
    });
}

function updateCartItem(itemId, quantity, form) {
    const token = document.querySelector('meta[name="csrf-token"]').content;

    fetch('/cart/update', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json'
        },
        body: JSON.stringify({ item_id: itemId, quantity })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showNotification('Cart updated!', 'success');

            // Update shipment subtotal
            if (data.item_subtotal && form) {
                const subtotalEl = form.closest('.cart-items')
                    .querySelector('.sub-total .cost h4:nth-child(2)');
                if (subtotalEl) subtotalEl.textContent = `KSH ${data.item_subtotal.toLocaleString(undefined,{minimumFractionDigits:2})}`;
            }

            // Update cart count
            if (data.cart_count !== undefined) updateCartCount(data.cart_count);

            // Update total
            if (data.total) {
                const totalEl = document.querySelector('.cart-left .sub-total .cost h4:nth-child(2)');
                if (totalEl) totalEl.textContent = `KSH ${data.total.toLocaleString(undefined,{minimumFractionDigits:2})}`;
            }
        } else {
            showNotification(data.message || 'Failed to update cart', 'error');
        }
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
    let cartCount = document.querySelector('.cart-count');

    if (!cartCount) {
        const cartLinks = document.querySelectorAll('a[href*="cart"], .cart-icon, [class*="cart"]');
        if (cartLinks.length > 0) {
            const cartLink = cartLinks[0];
            cartCount = document.createElement('span');
            cartCount.className = 'cart-count';
            cartCount.style.cssText = `
                position: absolute; top: -5px; right: -5px;
                background: #dc3545; color: white; border-radius: 50%;
                width: 18px; height: 18px; font-size: 11px;
                display: flex; align-items: center; justify-content: center;
            `;
            cartLink.style.position = 'relative';
            cartLink.appendChild(cartCount);
        }
    }

    if (cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'flex' : 'none';
    }
}
