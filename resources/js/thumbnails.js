// ===============================
//  #Thumbnails - ULTRA FAST VERSION
// ===============================

const ProductImg = document.getElementById("ProductImg");
const productPrice = document.getElementById('productPrice');
const productBusiness = document.getElementById('productBusiness');
let isFetching = false; // Prevent multiple simultaneous requests

// Store ALL data from current page load for immediate updates
const pageData = {
    thumbnails: {},
    currentCylinder: '{{ $product->gas_cylinder_id }}',
    currentQuantity: '{{ $product->gas_quantity_id }}'
};

// Initialize thumbnail data on page load
document.querySelectorAll('.small-img').forEach((img, index) => {
    const cylinder = img.dataset.cylinder;
    const quantity = img.dataset.quantity;
    const key = `${cylinder}_${quantity}`;
    
    pageData.thumbnails[key] = {
        src: img.src,
        cylinder: cylinder,
        quantity: quantity,
        size: img.dataset.size,
        unit: img.dataset.unit || '',
        price: img.dataset.price || null,
        business: img.dataset.business || '',
        isActive: img.classList.contains('active')
    };
});

// Simple image switcher
document.querySelectorAll('.small-img').forEach(img => {
    img.onclick = function() {
        ProductImg.src = this.src;
    };
});

// Main click handler
document.querySelectorAll('.small-img').forEach(img => {
    img.addEventListener('click', async function (e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isFetching) return; // Prevent multiple clicks
        
        const cylinder = this.dataset.cylinder;
        const quantity = this.dataset.quantity;
        const key = `${cylinder}_${quantity}`;
        const cachedData = pageData.thumbnails[key];
        
        // ⚡ STEP 1: IMMEDIATE UI UPDATE (0ms delay)
        updateImmediately(this, cachedData);
        
        // ⚡ STEP 2: Fetch updated data (async, doesn't block UI)
        await fetchVariantData(cylinder, quantity);
        
        // Update URL
        history.replaceState(null, '', `?cylinder=${cylinder}&quantity=${quantity}`);
    });
});

// ⚡ IMMEDIATE UPDATE FUNCTION (No waiting)
function updateImmediately(clickedImg, cachedData) {
    // 1. Update active state
    document.querySelectorAll('.small-img').forEach(i => i.classList.remove('active'));
    clickedImg.classList.add('active');
    
    // 2. Update size
    document.getElementById('productSize').textContent = 
        `${cachedData.size} ${cachedData.unit}`.trim();
    
    // 3. Update title
    const titleEl = document.getElementById('productTitle');
    const brand = titleEl.textContent.split('(')[0].trim();
    titleEl.textContent = `${brand} (${cachedData.size}${cachedData.unit})`;
    
    // 4. ⚡ IMMEDIATE PRICE UPDATE (from cached data)
    if (cachedData.price) {
        productPrice.innerHTML = `<h3>Ksh ${parseInt(cachedData.price).toLocaleString()}</h3>`;
    }
    
    // 5. ⚡ IMMEDIATE BUSINESS UPDATE
    if (cachedData.business) {
        productBusiness.textContent = cachedData.business;
    }
    
    // 6. Show loading for vendors
    const vendorBox = document.querySelector('.other-vendors');
    vendorBox.innerHTML = `
        <h4 class="heading">Other Sellers:</h4>
        <div class="loading">
            <i class="fas fa-spinner fa-spin"></i> Loading other sellers...
        </div>
    `;
}

// ⚡ ASYNC DATA FETCH (Background)
async function fetchVariantData(cylinder, quantity) {
    isFetching = true;
    
    try {
        // Add cache buster
        const timestamp = Date.now();
        const response = await fetch(
            `/gas/product-variant?cylinder=${cylinder}&quantity=${quantity}&_=${timestamp}`, 
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );
        
        if (!response.ok) throw new Error('Network error');
        
        const data = await response.json();
        
        // Update with fetched data (more accurate)
        if (data.price.cheapest) {
            productPrice.innerHTML = `<h3>Ksh ${data.price.cheapest}</h3>`;
            productBusiness.textContent = data.price.cheapest_business;
            
            // Update cached data for next time
            const key = `${cylinder}_${quantity}`;
            if (pageData.thumbnails[key]) {
                pageData.thumbnails[key].price = data.price.cheapest;
                pageData.thumbnails[key].business = data.price.cheapest_business;
            }
        }
        
        // Update vendors list
        updateVendorsList(data.vendors, data.price.cheapest_stock_id);
        
    } catch (error) {
        console.error('Fetch error:', error);
        // Keep the immediate updates if fetch fails
        document.querySelector('.other-vendors').innerHTML = `
            <h4 class="heading">Other Sellers:</h4>
            <p class="text-muted">Unable to refresh seller list.</p>
        `;
    } finally {
        isFetching = false;
    }
}

// Update vendors list
function updateVendorsList(vendors, cheapestStockId) {
    const vendorBox = document.querySelector('.other-vendors');
    vendorBox.innerHTML = '<h4 class="heading">Other Sellers:</h4>';
    
    if (!vendors || vendors.length === 0) {
        vendorBox.innerHTML += '<p>No other sellers available.</p>';
        return;
    }
    
    // Filter out the cheapest vendor (already displayed)
    const otherVendors = vendors.filter(v => v.id != cheapestStockId);
    
    if (otherVendors.length === 0) {
        vendorBox.innerHTML += '<p>No other sellers for this item.</p>';
        return;
    }
    
    // Sort by price (optional)
    otherVendors.sort((a, b) => a.raw_price - b.raw_price);
    
    // Create HTML
    otherVendors.forEach(v => {
        vendorBox.innerHTML += `
            <div class="vendor">
                <div class="right">
                    <h4>${v.name}</h4>
                    <h5>Ksh ${v.price}</h5>
                    <span>
                        <i class="fa-solid fa-location-dot"></i>
                        <h6>${v.location || 'Location not specified'}</h6>
                    </span>
                </div>
            </div>
        `;
    });
}
