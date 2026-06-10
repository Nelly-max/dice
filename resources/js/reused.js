


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
//  #Thumbnails
// ===============================
document.addEventListener('DOMContentLoaded', function () {
    const thumbnails = document.querySelectorAll('.small-img-col');
    const mainImage = document.getElementById('ProductImg');
    const variantLabel = document.querySelector('.js-variant-label');
    const packagingName = document.querySelector('.js-packaging-name');
    
    // Aligned to your exact custom markup bindings
    const finalPriceDisplay = document.querySelector('.js-final-price');
    const originalPriceDisplay = document.querySelector('.js-original-price');
    const discountPercentage = document.querySelector('.js-discount-percentage');
    const discountWrapper = document.querySelector('.js-discount-wrapper'); 
    
    const addToCartBtn = document.getElementById('AddToCartBtn');

    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function () {
            // 1. Toggle Active Visual Selection Outline States
            thumbnails.forEach(t => t.classList.remove('active-thumbnail'));
            this.classList.add('active-thumbnail');

            // 2. Pull Properties from Thumbnail Data Matrix Attributes
            const targetImageUrl = this.getAttribute('data-full-url');
            const targetLabel = this.getAttribute('data-label');
            const targetPackaging = this.getAttribute('data-packaging-name');
            const targetFinalPrice = this.getAttribute('data-full-url') ? this.getAttribute('data-final-price') : '';
            const targetOriginalPrice = this.getAttribute('data-original-price');
            const targetDiscountPct = this.getAttribute('data-discount-percentage');
            const hasDiscount = this.getAttribute('data-has-discount') === 'true';
            const targetRoute = this.getAttribute('data-route-url');
            const targetItemId = this.getAttribute('data-item-id');

            // 3. Update DOM Elements Instantly with Core Prefixes Preserved
            if (mainImage) mainImage.src = targetImageUrl;
            if (variantLabel) variantLabel.textContent = targetLabel;
            
            if (finalPriceDisplay) finalPriceDisplay.textContent = `${targetFinalPrice}`;
            if (originalPriceDisplay) originalPriceDisplay.textContent = `${targetOriginalPrice}`;
            if (discountPercentage) discountPercentage.textContent = `${targetDiscountPct}`;

            // 4. Update Packaging Meta Labels Format
            if (packagingName) {
                packagingName.textContent = targetPackaging ? `(${targetPackaging})` : '';
            }

            // 5. Manage Sale Box Element Visibility Rules
            if (discountWrapper) {
                discountWrapper.style.display = hasDiscount ? 'inline-flex' : 'none';
            }

            // 6. Synchronize Checkout Button Identifiers
            if (addToCartBtn) {
                addToCartBtn.setAttribute('data-item-id', targetItemId);
            }

            // 7. CRITICAL FIX: Overwrite the current history state instead of piling up new pages
            if (targetRoute) {
                window.history.replaceState({ path: targetRoute }, '', targetRoute);
            }

            // 8. Sync hidden form inputs if rendering on your theme layout
            const hiddenCartInput = document.querySelector('input[name="item_id"]');
            if (hiddenCartInput) {
                hiddenCartInput.value = targetItemId;
            }


            window.currentProduct = {
                stockable_id: document.getElementById('stockableId')?.value || '', // PRODUCT ID (KEEP THIS FIXED)
                stockable_type: document.getElementById('stockableType')?.value || '',
                business_account: document.getElementById('businessAccount')?.value || '',
                subdivision_code: document.getElementById('subdivisionCode')?.value || '',

                // ADD VARIANT SEPARATELY
                inventory_id: targetItemId,

                price: parseFloat(targetFinalPrice?.replace(/[^0-9.]/g, '')) || 0,
                image: targetImageUrl,
                product_name: targetLabel
            };
        });
    });
});


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
//             Search
// ====================================================
/**
 * Global variables for state management and optimization
 */
// let searchTimeout = null;
// let abortController = null;

// document.addEventListener('DOMContentLoaded', function() {
//     const searchInput = document.getElementById('searchInput');
//     const searchResults = document.getElementById('searchResults');

//     if (searchInput && searchResults) {
//         searchInput.addEventListener('input', function (e) {
//             const term = e.target.value.trim();

//             // 1. CLEAR LOGIC: Only hide if the search box is totally empty
//             if (term.length === 0) {
//                 searchResults.style.display = 'none';
//                 searchResults.innerHTML = '';
//                 return;
//             }

//             // 2. VISIBILITY LOGIC: Keep it visible while typing
//             // This ensures the box stays open even before the new results arrive
//             searchResults.style.display = 'block';

//             // 3. DEBOUNCED SEARCH: Only hit the server after 2+ characters
//             clearTimeout(searchTimeout);
//             if (term.length >= 2) {
//                 searchTimeout = setTimeout(() => {
//                     performSearch(term);
//                 }, 400); 
//             }
//         });
//     }
// });

// function performSearch(term) {
//     const searchResults = document.getElementById('searchResults');

//     if (abortController) abortController.abort();
//     abortController = new AbortController();

//     fetch(`/gas/search?term=${encodeURIComponent(term)}`, {
//         method: 'GET',
//         signal: abortController.signal,
//         headers: {
//             // 2. THIS IS CRITICAL for $request->ajax() to work in Laravel
//             'X-Requested-With': 'XMLHttpRequest', 
//             'Accept': 'text/html'
//         }
//     })
//     .then(response => response.text())
//     .then(html => {
//         if (searchResults) {
//             // Update content without closing the box
//             searchResults.innerHTML = html;
//         }
//     })
//     .catch(error => {
//         if (error.name !== 'AbortError') console.error('Search error:', error);
//     });
// }






// ====================================================
//             Courousell
// ====================================================

const carouselContainers = document.querySelectorAll(".carousel-container");

carouselContainers.forEach(carouselContainer => {
    const carousel = carouselContainer.querySelector(".carousel");
    const arrowBtns = carouselContainer.querySelectorAll("i");
    
    let firstCardWidth = carousel.querySelector(".card").offsetWidth;
    let carouselChildrens = [...carousel.children];
    let isDragging = false, startX, startScrollLeft, timeoutId;
    let cardPerView = Math.round(carousel.offsetWidth / firstCardWidth);
    
    // Explicit tracking states for user touch/hover positions
    let isMouseHovering = false;
    let isTouchHolding = false;

    // Build seamless infinite scrolling boundaries via card element clones
    carouselChildrens.slice(-cardPerView).reverse().forEach(card => {
        carousel.insertAdjacentHTML("afterbegin", card.outerHTML);
    });
    carouselChildrens.slice(0, cardPerView).forEach(card => {
        carousel.insertAdjacentHTML("beforeend", card.outerHTML);
    });

    // Handle responsive layouts and mobile view orientation snaps
    window.addEventListener("resize", () => {
        const cardElement = carousel.querySelector(".card");
        if (cardElement) {
            firstCardWidth = cardElement.offsetWidth;
            cardPerView = Math.round(carousel.offsetWidth / firstCardWidth);
        }
    });

    // Global Autoplay Timing Controller
    const autoPlay = () => {
        // Clear any active timers to prevent double-scroll speed bugs
        clearTimeout(timeoutId);
        
        // Stop execution if a human is interacting with the element area
        if (isMouseHovering || isTouchHolding || isDragging) return;
        
        // Set loop ticker
        timeoutId = setTimeout(() => {
            carousel.scrollLeft += firstCardWidth;
        }, 2500);
    };

    // Control Arrows Click Handler (Forces immediate autoplay refresh cycle)
    arrowBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            // Halt any current movements instantly
            clearTimeout(timeoutId); 

            // Scroll the track left/right
            carousel.scrollLeft += btn.id == "left" ? -firstCardWidth : firstCardWidth;
            
            // Re-queue the automated scroll system 2.5 seconds from this click
            autoPlay(); 
        });
    });

    const getPageX = (e) => e.type.includes('touch') ? e.touches.pageX : e.pageX;

    // Pointer Input Interaction Activation Handler
    const dragStart = (e) => {
        isDragging = true;
        if (e.type.includes('touch')) isTouchHolding = true;
        
        carousel.classList.add('dragging');
        startX = getPageX(e);
        startScrollLeft = carousel.scrollLeft;
        
        clearTimeout(timeoutId); // Pause loop execution immediately on contact
    };

    const dragging = (e) => {
        if (!isDragging) return;
        carousel.scrollLeft = startScrollLeft - (getPageX(e) - startX);
    };

    // Pointer Input Interaction Release Handler
    const dragStop = () => {
        if (!isDragging) return;
        isDragging = false;
        isTouchHolding = false;
        
        carousel.classList.remove('dragging');
        autoPlay(); // Safely wake up the autoplay tracking loop
    };

    // Initialize carousel loop engine on script compile execution
    autoPlay();

    // Reset loop boundary tracking loops on container tracking scroll canvas updates
    const infiniteScroll = () => {
        if (Math.abs(carousel.scrollLeft) < 1) {
            carousel.classList.add("no-transition");
            carousel.scrollLeft = carousel.scrollWidth - (2 * carousel.offsetWidth);
            carousel.classList.remove("no-transition");
        }
        else if (Math.abs(carousel.scrollLeft - (carousel.scrollWidth - carousel.offsetWidth)) < 1) {
            carousel.classList.add("no-transition");
            carousel.scrollLeft = carousel.offsetWidth;
            carousel.classList.remove("no-transition");
        }
        
        // Restart the autoplay loop timer after the scroll action finishes
        autoPlay();
    };

    // Desktop Mouse Event Hooks
    carousel.addEventListener("mousedown", dragStart);
    carousel.addEventListener("mousemove", dragging);
    document.addEventListener("mouseup", dragStop);

    carouselContainer.addEventListener("mouseenter", () => {
        isMouseHovering = true;
        clearTimeout(timeoutId);
    });
    carouselContainer.addEventListener("mouseleave", () => {
        isMouseHovering = false;
        autoPlay();
    });
    
    // Mobile Touch Gesture Event Hooks
    carousel.addEventListener("touchstart", dragStart, { passive: true });
    carousel.addEventListener("touchmove", dragging, { passive: true });
    document.addEventListener("touchend", dragStop);

    carousel.addEventListener("scroll", infiniteScroll);
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





// ====================================================
//             ITEMS SLIDER
// ====================================================

document.addEventListener("DOMContentLoaded", () => {
    const sliderWrapper = document.querySelector(".cards-slider");
    if (!sliderWrapper) return;

    const track = sliderWrapper.querySelector(".column-cards");
    const prevBtn = sliderWrapper.querySelector(".fa-angle-left");
    const nextBtn = sliderWrapper.querySelector(".fa-angle-right");
    
    let originalCards = [...track.querySelectorAll(".card-data")];
    if (originalCards.length === 0) return;

    const itemSpacing = 6; 
    const padding = 6;     
    let cardWidth = 160;   
    let timeoutId = null;
    let isDragging = false, startX, startScrollLeft;
    let isTouchHolding = false;

    // Get number of cards per view to determine clone count
    let cardPerView = Math.round(track.offsetWidth / cardWidth) || 1;

    // 🔄 Step 1: Clone cards for infinite scrolling loops
    originalCards.slice(-cardPerView).reverse().forEach(card => {
        track.insertAdjacentHTML("afterbegin", card.outerHTML);
    });
    originalCards.slice(0, cardPerView).forEach(card => {
        track.insertAdjacentHTML("beforeend", card.outerHTML);
    });

    // Fetch newly updated cards list (including clones)
    let allCards = track.querySelectorAll(".card-data");

    // 📱 Step 2: Proportional Width Dimension Matrix Calculator
    const calculateLayoutDimensions = () => {
        const wrapperWidth = sliderWrapper.offsetWidth;
        if (wrapperWidth > 0) {
            cardPerView = Math.floor((wrapperWidth - padding *2) / 160) || 2;
            cardWidth = (wrapperWidth - padding * 2 - itemSpacing * (cardPerView - 1)) / cardPerView;

            allCards.forEach(card => {
                card.style.width = `${cardWidth}px`;
                card.style.marginRight = `${itemSpacing}px`;
                card.style.flexShrink = "0"; 
            });
        }
    };

    // ⏳ Step 3: Global Autoplay Timing Controller
    const autoPlay = () => {
        clearTimeout(timeoutId);
        
        // Stop execution IF a human is actively dragging or holding their finger on the track
        if (isTouchHolding || isDragging) return;
        
        timeoutId = setTimeout(() => {
            track.scrollLeft += (cardWidth + itemSpacing);
        }, 3000); // 3 second intervals
    };

    // 🎮 Step 4: Control Arrows Click Handler (FIXED: Safely restarts autoplay)
    const handleArrowClick = (direction) => {
        clearTimeout(timeoutId); // Stop current timer instantly
        
        const step = cardWidth + itemSpacing;
        track.scrollLeft += direction === "left" ? -step : step;
        
        // Force-start a clean 3s autoplay countdown right after the manual click action
        autoPlay(); 
    };

    prevBtn.addEventListener("click", () => handleArrowClick("left"));
    nextBtn.addEventListener("click", () => handleArrowClick("right"));

    // 👆 Step 5: Touch & Drag Input Handlers
    const getPageX = (e) => e.type.includes('touch') ? e.touches.pageX : e.pageX;

    const dragStart = (e) => {
        isDragging = true;
        if (e.type.includes('touch')) isTouchHolding = true;
        
        track.classList.add('dragging');
        startX = getPageX(e);
        startScrollLeft = track.scrollLeft;
        clearTimeout(timeoutId); 
    };

    const dragging = (e) => {
        if (!isDragging) return;
        track.scrollLeft = startScrollLeft - (getPageX(e) - startX);
    };

    const dragStop = () => {
        if (!isDragging) return;
        isDragging = false;
        isTouchHolding = false;
        track.classList.remove('dragging');
        autoPlay(); 
    };

    // 🔄 Step 6: Infinite Scroll Boundary Reset (FIXED: Continues autoplay sequence)
    const infiniteScroll = () => {
        // If at the beginning clone zone, jump seamlessly to the end
        if (Math.abs(track.scrollLeft) < 1) {
            track.classList.add("no-transition");
            track.scrollLeft = track.scrollWidth - (2 * track.offsetWidth);
            track.classList.remove("no-transition");
        }
        // If at the end clone zone, jump seamlessly to the beginning
        else if (Math.abs(track.scrollLeft - (track.scrollWidth - track.offsetWidth)) < 1) {
            track.classList.add("no-transition");
            track.scrollLeft = track.offsetWidth;
            track.classList.remove("no-transition");
        }
        
        // Keep the autoplay clock alive anytime a scroll action finishes
        autoPlay();
    };

    // Desktop Mouse Event Hooks
    track.addEventListener("mousedown", dragStart);
    track.addEventListener("mousemove", dragging);
    document.addEventListener("mouseup", dragStop);

    // Mobile Touch Gesture Event Hooks
    track.addEventListener("touchstart", dragStart, { passive: true });
    track.addEventListener("touchmove", dragging, { passive: true });
    document.addEventListener("touchend", dragStop);

    // Structural Resize & Loop Watchers
    window.addEventListener("resize", () => {
        calculateLayoutDimensions();
        autoPlay();
    });
    track.addEventListener("scroll", infiniteScroll);

    // Mouse hovering over the container will pause the loop, leaving resumes it
    sliderWrapper.addEventListener("mouseenter", () => clearTimeout(timeoutId));
    sliderWrapper.addEventListener("mouseleave", autoPlay);

    // Initial Layout Configurations
    calculateLayoutDimensions();
    
    // Position initial track view past start-clones instantly
    track.scrollLeft = track.offsetWidth; 
    autoPlay();
});


const today = new Date().toISOString().split('T')[0];
    document.getElementById('datePicker').setAttribute('min', today);