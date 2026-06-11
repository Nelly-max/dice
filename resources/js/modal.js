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



    if (modalId === "mapModal") {

        requestAnimationFrame(() => {
          setTimeout(() => {

              window.MapPicker.init(
                  "map",
                  'input[name="pin_location"]'
              );

              // IMPORTANT: force Google Maps resize
              if (window.MapPicker.map) {
                  google.maps.event.trigger(window.MapPicker.map, "resize");
              }

          }, 300);
      });
    }
  };


  let pendingDeleteItemId = null;
  let pendingDeleteForm = null;

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

  document
    .getElementById('confirmDeleteCartItem')
    ?.addEventListener('click', () => {

        if (!pendingDeleteItemId) {
            return;
        }

        updateCartItem(
            pendingDeleteItemId,
            'decrease',
            pendingDeleteForm
        );

        closeModal();

        pendingDeleteItemId = null;
        pendingDeleteForm = null;
    });

});
