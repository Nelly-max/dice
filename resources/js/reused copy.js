


const toggles = document.querySelectorAll('.toggle-items');
const toggleBtns = document.querySelectorAll('.toggle-items-btn');

toggleBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    toggles.forEach(toggle => {
        toggle.classList.toggle('active');
    });
  });
});



const showSearchs = document.querySelectorAll('.search-items');
const searchShowBtns = document.querySelectorAll('.search-show-btn');
const searchCloseBtns = document.querySelectorAll('.search-close-btn');

// Show
searchShowBtns.forEach(showBtn => {
  showBtn.addEventListener('click', () => {
    showSearchs.forEach(showSearch => {
      showSearch.classList.add('active');
    });
  });
});

// Close
searchCloseBtns.forEach(closeBtn => {
  closeBtn.addEventListener('click', () => {
    showSearchs.forEach(showSearch => {
      showSearch.classList.remove('active');
    });
  });
});



const showMs = document.querySelectorAll('.m-items');
const mshowBtns = document.querySelectorAll('.m-items-show-btn');
const mcloseBtns = document.querySelectorAll('.m-items-close-btn');

// Show
mshowBtns.forEach(showBtn => {
  showBtn.addEventListener('click', () => {
    showMs.forEach(showM => {
      showM.classList.add('active');
    });
  });
});

// Close
mcloseBtns.forEach(closeBtn => {
  closeBtn.addEventListener('click', () => {
    showMs.forEach(showM => {
      showM.classList.remove('active');
    });
  });
});




//Tabs
const tabs = document.querySelectorAll('.tab_btn');
const all_content = document.querySelectorAll('.tab');

tabs.forEach((tab, index)=>{
    tab.addEventListener('click', (e) =>{
        tabs.forEach(tab=>{tab.classList.remove('active')});
        tab.classList.add('active');
        
        var line = document.querySelector('.line');
        line.style.width = e.target.offsetWidth + "Px";
        line.style.left = e.target.offsetLeft + "px";

        all_content.forEach(content=>{content.classList.remove('active')});
        all_content[index].classList.add('active');
    })
})


const themeBtns = document.querySelectorAll('.theme-btn');
const h5Text = document.getElementById('text');
const light = document.querySelector('.light');
const dark = document.querySelector('.dark');
const body = document.body;

let getMode = localStorage.getItem("mode");

if (getMode && getMode === "dark") {
  body.classList.add("dark-mode");
//   h5Text.textContent = "Light Mode";
} else {
//   h5Text.textContent = "Dark Mode";
}

console.log(getMode);

themeBtns.forEach((themeBtn) => {
  themeBtn.addEventListener('click', () => {
    body.classList.toggle('dark-mode');
    h5Text.classList.toggle('dark-mode');
    if (!body.classList.contains("dark-mode")) {
      h5Text.textContent = "Dark Mode";
      return localStorage.setItem("mode", "light");
    }

    h5Text.textContent = "Light Mode";
    return localStorage.setItem("mode", "dark");
  });
});

var incrementButton = document.getElementsByClassName('inc');
var decrementButton = document.getElementsByClassName('dec');

//increment
for(var i = 0; i < incrementButton.length; i++){
    var button = incrementButton[i];
    button.addEventListener('click',function(event){
        var buttonClicked = event.target;
        // console.log(buttonClicked);
        var input = buttonClicked.parentElement.children[1];
        // console.log(input);
        var inputValue = input.value;
        // console.log(inputValue);
        var newValue = parseInt(inputValue) + 1;
        // console.log(newValue);
        if (newValue >= 999){ 
            input.value = 999;         
        }else{
            input.value = newValue;
        }
    })
}

//decrement

for(var i = 0; i < decrementButton.length; i++){
    var button = decrementButton[i];
    button.addEventListener('click',function(event){
        var buttonClicked = event.target;
        // console.log(buttonClicked);
        var input = buttonClicked.parentElement.children[1];
        // console.log(input);
        var inputValue = input.value;
        // console.log(inputValue);
        var newValue = parseInt(inputValue) - 1;
        // console.log(newValue);
        if (newValue >= 0){            
            input.value = newValue;
        }else{
            input.value = 0;
        }
    })
}

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



// ===============================
//  #Photo Slider
// ===============================

 document.addEventListener("DOMContentLoaded", function () {
      const photoSlider = document.getElementById("imageSlider"); // the section.photo-grid
      const counter = document.getElementById("photoCounter");
      const images = photoSlider.querySelectorAll("a");
      const total = images.length;

      function updateCounter() {
      const scrollLeft = photoSlider.scrollLeft;
      const itemWidth = photoSlider.offsetWidth;
      const index = Math.round(scrollLeft / itemWidth);
      counter.innerText = `${index + 1}/${total}`;
      }

      // Listen for scroll/swipe
      photoSlider.addEventListener("scroll", updateCounter);

      // Initial set
      updateCounter();
  });


//Hero area Slider
let list = document.querySelector('.slider .list');
let items = document.querySelectorAll('.slider .list .item');
let dots = document.querySelectorAll('.slider .dots li');
let prev = document.getElementById('prev');
let next = document.getElementById('next');

let active = 0;
let lengthItems = items.length - 1;

// Move to the next item
next.onclick = function() {
    active = (active + 1 > lengthItems) ? 0 : active + 1;
    reloadSlider();
}

// Move to the previous item
prev.onclick = function() {
    active = (active - 1 < 0) ? lengthItems : active - 1;
    reloadSlider();
}

// Auto-slide every 3 seconds
let refreshSlider = setInterval(() => { next.click(); }, 3000);

// Update the slider position
function reloadSlider() {
    let checkLeft = items[active].offsetLeft;
    list.style.left = -checkLeft + 'px';

    // Update active dot
    document.querySelector('.slider .dots li.active').classList.remove('active');
    dots[active].classList.add('active');

    // Reset auto-slide interval
    clearInterval(refreshSlider);
    refreshSlider = setInterval(() => { next.click(); }, 3000);
}

// Handle dot clicks
dots.forEach((li, key) => {
    li.addEventListener('click', function() {
        active = key;
        reloadSlider();
    });
});

// Handle window resize to adjust the slider
window.addEventListener('resize', reloadSlider);




// ====================================================
//             Courousell
// ====================================================

const carouselContainers = document.querySelectorAll(".carousel-container");

carouselContainers.forEach(carouselContainer => {
    const carousel = carouselContainer.querySelector(".carousel");
    const arrowBtns = carouselContainer.querySelectorAll("i");
    const firstCardWidth = carousel.querySelector(".card").offsetWidth;
    const carouselChildrens = [...carousel.children];

    let isDragging = false, startX, startScrollLeft, timeoutId;

    //Get the number of cards that can fit in the carousel at once
    let cardPerView = Math.round(carousel.offsetWidth / firstCardWidth);

    //insert copies of the last few cards to beginning of carousel for infinite scrolling
    carouselChildrens.slice(-cardPerView).reverse().forEach(card => {
        carousel.insertAdjacentHTML("afterbegin", card.outerHTML);
    });

    //insert copies of the first few cards to end of carousel for infinite scrolling
    carouselChildrens.slice(0, cardPerView).forEach(card => {
        carousel.insertAdjacentHTML("beforeend", card.outerHTML);
    });

    //Add event listeners for the arrow buttons to scroll the carousel left and right
    arrowBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            carousel.scrollLeft += btn.id == "left" ? -firstCardWidth : firstCardWidth;
        });
    });

    const dragStart = (e) => {
        isDragging = true;
        carousel.classList.add('dragging');
        //Records the initial cursor and scroll position of the carousel
        startX = e.pageX;
        startScrollLeft = carousel.scrollLeft;
    };

    const dragging = (e) => {
        if (!isDragging) return; //if isDragging is false return from here
        //updates the scroll position of the carousel based on the cursor movement
        carousel.scrollLeft = startScrollLeft - (e.pageX - startX);
    };

    const dragStop = () => {
        isDragging = false;
        carousel.classList.remove('dragging');
    };

    const autoPlay = () => {
        if (window.innerWidth < 800) return; // Return if window is smaller than 800
        // Clear any existing timeout
        clearTimeout(timeoutId);
        // Autoplay the carousel after every 2500ms
        timeoutId = setTimeout(() => carousel.scrollLeft += firstCardWidth, 2500);
    };

    autoPlay();

    const infiniteScroll = () => {
        // if the carousel is at the beginning, scroll to the end
        if (Math.abs(carousel.scrollLeft) < 1) {
            carousel.classList.add("no-transition");
            carousel.scrollLeft = carousel.scrollWidth - (2 * carousel.offsetWidth);
            carousel.classList.remove("no-transition");
        }
        // if the carousel is at the end, scroll to the beginning
        else if (Math.abs(carousel.scrollLeft - (carousel.scrollWidth - carousel.offsetWidth)) < 1) {
            carousel.classList.add("no-transition");
            carousel.scrollLeft = carousel.offsetWidth;
            carousel.classList.remove("no-transition");
            // console.log("You have reached to the right end");
        }
        //clear existing timeout & start autoplay if mouse is not hovering over carousel
        clearTimeout(timeoutId);
        if (!carouselContainer.matches(":hover")) autoPlay();
    };

    carousel.addEventListener("mousedown", dragStart);
    carousel.addEventListener("mousemove", dragging);
    document.addEventListener("mouseup", dragStop);
    carousel.addEventListener("scroll", infiniteScroll);
    carouselContainer.addEventListener("mouseenter", () => clearTimeout(timeoutId));
    carouselContainer.addEventListener("mouseleave", autoPlay);
});


const viewExtra = document.querySelectorAll('.viewExtra');
const viewExtraBtns = document.querySelectorAll('.viewExtraBtns');
const hideBtns = document.querySelectorAll('.hideBtns');

// Loop through each button and attach an event listener
viewExtraBtns.forEach((btn, index) => {
  btn.addEventListener('click', () => {
    // Toggle the 'active' class on the corresponding .viewExtra element
    if (viewExtra[index]) {
      viewExtra[index].classList.toggle('active');
    }
  });

});

// Loop through each button and attach an event listener
hideBtns.forEach((btn, index) => {
  btn.addEventListener('click', () => {
    // Toggle the 'active' class on the corresponding .viewExtra element
    if (viewExtra[index]) {
      viewExtra[index].classList.remove('active');
    }
  });
});


  

const today = new Date().toISOString().split('T')[0];
    document.getElementById('datePicker').setAttribute('min', today);


