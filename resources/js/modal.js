// modal.js
document.addEventListener("DOMContentLoaded", () => {

  window.showModal = async function(modalId) {
    window.closeModal();

    const modal = document.getElementById(modalId);
    if (!modal) return console.error("Modal not found:", modalId);

    modal.classList.remove("closing");
    modal.style.display = "block";
    modal.classList.add("active");

    if (modal.classList.contains("sheet")) {
      document.body.style.overflow = "hidden";
      enableSheetSwipe(modal);
    }

    const overlay = modal.querySelector(".modal-overlay");
    if (overlay) overlay.onclick = () => window.closeModal();

    function onKey(e) {
      if (e.key === "Escape") window.closeModal();
    }
    document.addEventListener("keydown", onKey);

    modal._cleanup = () => {
      document.removeEventListener("keydown", onKey);
      if (overlay) overlay.onclick = null;
    };


    if (modalId === "mapModal") {

        requestAnimationFrame(() => {
          setTimeout(() => {

              initDeliveryMap();

              // IMPORTANT: force Google Maps resize
              if (map) {
                  google.maps.event.trigger(map, "resize");
              }

          }, 300);
      });
    }
  };

  window.closeModal = function() {
    document.querySelectorAll(".modal").forEach(modal => {
      const isSheet = modal.classList.contains("sheet");

      // a sheet that is open rolls back down before it disappears
      if (isSheet && modal.classList.contains("active") && !modal.classList.contains("closing")) {
        modal.classList.add("closing");
        modal.classList.remove("active");
        setTimeout(() => {
          if (modal.classList.contains("closing")) {
            modal.classList.remove("closing");
            modal.style.display = "none";
          }
        }, 240);
      } else if (!isSheet) {
        modal.style.display = "none";
        modal.classList.remove("active");
      }

      if (modal._cleanup) {
        modal._cleanup();
        delete modal._cleanup;
      }
    });

    document.body.style.overflow = "";
  };

  /* Drag the handle down to dismiss a sheet. */
  function enableSheetSwipe(modal) {
    const head = modal.querySelector(".sheet-head");
    const panel = modal.querySelector(".sheet-panel");
    if (!head || !panel || head._swipe) return;
    head._swipe = true;

    let startY = null;

    head.addEventListener("touchstart", e => { startY = e.touches[0].clientY; }, { passive: true });

    head.addEventListener("touchmove", e => {
      if (startY === null) return;
      const dy = Math.max(0, e.touches[0].clientY - startY);
      panel.style.animation = "none";
      panel.style.transform = `translateY(${dy}px)`;
    }, { passive: true });

    head.addEventListener("touchend", e => {
      if (startY === null) return;
      const dy = e.changedTouches[0].clientY - startY;
      startY = null;
      panel.style.transform = "";
      panel.style.animation = "";
      if (dy > 90) window.closeModal();
    });
  }

});
