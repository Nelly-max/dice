// Dropdown
let arrowOpen = document.querySelectorAll(".arrowOpen");

for (var i = 0; i < arrowOpen.length; i++) {
    arrowOpen[i].addEventListener("click", (e) => {
        // Remove "showMenu" class from all elements with the "arrowOpen" class
        arrowOpen.forEach((element) => {
            element.parentElement.parentElement.classList.remove("showMenu");
        });

        // Toggle the "showMenu" class on the current element
        let arrowOpenParent = e.target.parentElement.parentElement;
        arrowOpenParent.classList.toggle("showMenu");
    });
}



// ===================================================
//     Table Links
// ===================================================

document.addEventListener("DOMContentLoaded", () =>{
  const rows = document.querySelectorAll("tr[data-href");

  rows.forEach(row =>{
    row.addEventListener('click', () => {
      window.location.href = row.dataset.href;
    })
  })
})


// ===================================================
//     menu Close
// ===================================================

const menu = document.getElementById('menu'); // Use getElementById to select a single element
const sidebar = document.querySelectorAll('.sidebar'); // Use querySelectorAll to select multiple elements

// Add a click event listener to the menu element
menu.addEventListener('click', () => {
  // Toggle the 'active' class on each element with the class 'side-bar'
  sidebar.forEach(sidebarItem => {
    sidebarItem.classList.toggle('close');
  });
});




let sidebars = document.querySelectorAll(".sidebar");

let sidebarBtns = document.querySelectorAll(".close-sidebar");

let menuSidebarBtns = document.querySelectorAll(".menu-sidebar");

// ==========================================================================
// CLOSE SIDEBAR
// ==========================================================================

sidebarBtns.forEach((sidebarBtn, index) => {

    sidebarBtn.addEventListener("click", () => {

        sidebars[index]?.classList.remove("active");

        sidebars[index]?.classList.toggle("close");

    });

});


// ==========================================================================
// OPEN SIDEBAR
// ==========================================================================

menuSidebarBtns.forEach((menuSidebarBtn, index) => {

    menuSidebarBtn.addEventListener("click", () => {

        sidebars[index]?.classList.add("active");

        sidebars[index]?.classList.remove("close");

    });

});


// ==========================================================================
// REMOVE SIDEBAR ACTIVE
// ==========================================================================

document.querySelectorAll(".remove-sidebar").forEach((removeSidebarBtn) => {

    removeSidebarBtn.addEventListener("click", () => {

        const sidebar = removeSidebarBtn.closest(".sidebar");

        if (!sidebar) {
            return;
        }

        sidebar.classList.remove("active");

    });

});