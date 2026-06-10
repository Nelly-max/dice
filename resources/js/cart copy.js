console.log('Cart JS loaded');

let currentProduct = {
    stockable_id: '',
    stockable_type: 'App\\Models\\CookingGas\\BusinessGasStock',
    subdivision_code: 'cooking_gas',
    quantity: 1,
    price: 0,
    product_name: '',
    business_name: '',
    image: '',
    size: ''
};

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
    const thumbnails = document.querySelectorAll('.small-img');
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const titleElement = document.getElementById('productTitle');
            const brandName = titleElement ? titleElement.textContent.split('(')[0]?.trim() || 'Gas Cylinder' : 'Gas Cylinder';
            const size = this.dataset.size || '';
            const productName = `${brandName} (${size})`;

            currentProduct = {
                stockable_id: this.dataset.id || '',
                stockable_type: 'App\\Models\\CookingGas\\BusinessGasStock',
                subdivision_code: 'cooking_gas',
                quantity: currentProduct.quantity,
                price: parseFloat(this.dataset.price) || 0,
                product_name: productName,
                business_name: this.dataset.business || '',
                image: this.dataset.image || '',
                size: size
            };

            updateHiddenFields();
            updateProductDisplay();

            const mainImg = document.getElementById('ProductImg');
            if (mainImg) mainImg.src = this.src;

            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
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

    orderBtn.addEventListener('click', function(e) {
        e.preventDefault();
        const quantityInput = document.querySelector('.quantity-input');
        const quantity = quantityInput ? parseInt(quantityInput.value) : 1;

        if (!currentProduct.stockable_id) {
            showNotification('Please select a product variant first', 'error');
            return;
        }

        const productData = {
            stockable_id: currentProduct.stockable_id,
            stockable_type: currentProduct.stockable_type,
            subdivision_code: currentProduct.subdivision_code,
            quantity: quantity,
            shipment_type: 'quick',
            _token: csrfToken
        };

        const originalHTML = orderBtn.innerHTML;
        orderBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        orderBtn.style.pointerEvents = 'none';

        fetch('/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(productData)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showNotification(`✓ Added to cart!`, 'success');
                updateCartCount(data.cart_count || 1);
                if (quantityInput) {
                    quantityInput.value = 1;
                    currentProduct.quantity = 1;
                }
            } else {
                showNotification(data.message || 'Failed to add to cart', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showNotification('Network error: ' + err.message, 'error');
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
