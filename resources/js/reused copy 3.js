document.addEventListener("DOMContentLoaded", () => {
    const sliderWrapper = document.querySelector(".items-slider");
    if (!sliderWrapper) return;

    const track = sliderWrapper.querySelector(".column-items");
    const prevBtn = sliderWrapper.querySelector(".fa-angle-left");
    const nextBtn = sliderWrapper.querySelector(".fa-angle-right");
    
    let originalCards = [...track.querySelectorAll(".item-container")];
    if (originalCards.length === 0) return;

    const itemSpacing = 8; 
    const padding = 7;     
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
    let allCards = track.querySelectorAll(".item-container");

    // 📱 Step 2: Proportional Width Dimension Matrix Calculator
    const calculateLayoutDimensions = () => {
        const wrapperWidth = sliderWrapper.offsetWidth;
        if (wrapperWidth > 0) {
            cardPerView = Math.floor((wrapperWidth - padding * 2) / 160) || 1;
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
        }, 2500); // 2.5 second intervals
    };

    // 🎮 Step 4: Control Arrows Click Handler (FIXED: Safely restarts autoplay)
    const handleArrowClick = (direction) => {
        clearTimeout(timeoutId); // Stop current timer instantly
        
        const step = cardWidth + itemSpacing;
        track.scrollLeft += direction === "left" ? -step : step;
        
        // Force-start a clean 2.5s autoplay countdown right after the manual click action
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
