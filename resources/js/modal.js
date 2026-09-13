// modal.js
document.addEventListener("DOMContentLoaded", () => {

  window.showModal = async function(modalId) {
    window.closeModal();

    const modal = document.getElementById(modalId);
    if (!modal) return console.error("Modal not found:", modalId);

    modal.style.display = "block";
    modal.classList.add("active");

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
      modal.style.display = "none";
      modal.classList.remove("active");
      if (modal._cleanup) {
        modal._cleanup();
        delete modal._cleanup;
      }
    });
  };

});
