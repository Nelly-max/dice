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

// (bound per page in hubInitPage below so it also works after soft navigation)


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


// ==========================================================================
// PHONE DRAWER: close on the dimmed backdrop or Escape
// ==========================================================================

document.querySelectorAll("[data-hub-scrim]").forEach((scrim) => {
    scrim.addEventListener("click", () => {
        document.querySelectorAll(".sidebar.active").forEach((s) => s.classList.remove("active"));
    });
});

document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        document.querySelectorAll(".sidebar.active").forEach((s) => s.classList.remove("active"));
    }
});

// remember the collapsed desktop sidebar between pages
(() => {
    const side = document.querySelector(".sidebar");
    const btn = document.getElementById("menu");
    if (!side || !btn) return;
    try { if (localStorage.getItem("hub_sidebar") === "closed") side.classList.add("close"); } catch (e) {}
    btn.addEventListener("click", () => {
        try { localStorage.setItem("hub_sidebar", side.classList.contains("close") ? "closed" : "open"); } catch (e) {}
    });
})();


// ==========================================================================
// TABLES: instant filter + click-to-sort
// --------------------------------------------------------------------------
// <input data-table-filter="tbodyId">  hides rows that don't contain the text
// <table data-sortable> with <th data-sort="text|number|date"> sorts rows;
// a cell's data-value (if present) is used instead of its text.
// ==========================================================================

function initTables(root) {
    const rowsOf = (tbody) => [...tbody.rows].filter((r) => !r.classList.contains("hub-no-match") && !r.classList.contains("hub-empty-row"));

    root.querySelectorAll("[data-table-filter]").forEach((input) => {
        const tbody = document.getElementById(input.dataset.tableFilter);
        if (!tbody) return;
        const noMatch = tbody.querySelector(".hub-no-match");
        const info = document.querySelector(`[data-table-info="${input.dataset.tableFilter}"]`);
        const infoText = info ? info.textContent.trim() : "";

        const apply = () => {
            const term = input.value.trim().toLowerCase();
            const rows = rowsOf(tbody);
            let shown = 0;
            rows.forEach((row) => {
                const hit = !term || row.textContent.toLowerCase().includes(term);
                row.hidden = !hit;
                if (hit) shown++;
            });
            if (noMatch) noMatch.hidden = !(rows.length && shown === 0);
            if (info) info.textContent = term ? `${shown} of ${rows.length} shown` : infoText;
        };

        input.addEventListener("input", apply);
        if (input.value) apply();
    });

    root.querySelectorAll("table[data-sortable]").forEach((table) => {
        const tbody = table.tBodies[0];
        if (!tbody) return;

        table.querySelectorAll("th[data-sort]").forEach((th) => {
            th.tabIndex = 0;
            th.setAttribute("role", "button");
            th.classList.add("is-sortable");

            const sort = () => {
                const index = [...th.parentElement.children].indexOf(th);
                const type = th.dataset.sort;
                const asc = th.getAttribute("aria-sort") !== "ascending";

                table.querySelectorAll("th[aria-sort]").forEach((h) => h.removeAttribute("aria-sort"));
                th.setAttribute("aria-sort", asc ? "ascending" : "descending");

                const value = (row) => {
                    const cell = row.cells[index];
                    if (!cell) return "";
                    const raw = cell.dataset.value ?? cell.textContent.trim();
                    return type === "text" ? raw.toLowerCase() : parseFloat(String(raw).replace(/[^0-9.\-]/g, "")) || 0;
                };

                const rows = rowsOf(tbody);
                rows.sort((a, b) => {
                    const x = value(a), y = value(b);
                    return (x > y ? 1 : x < y ? -1 : 0) * (asc ? 1 : -1);
                });
                const tail = tbody.querySelector(".hub-no-match");
                rows.forEach((r) => tbody.insertBefore(r, tail));
            };

            th.addEventListener("click", sort);
            th.addEventListener("keydown", (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); sort(); } });
        });
    });
}


// ==========================================================================
// PER-PAGE SETUP (first load and after every soft navigation, see hubnav.js)
// ==========================================================================

window.hubInitPage = function (root = document) {
    // clickable table rows
    root.querySelectorAll("tr[data-href]").forEach((row) => {
        const href = row.dataset.href;
        if (!href || href === "#") return;
        row.classList.add("is-link");
        row.addEventListener("click", (e) => {
            if (e.target.closest("a, button, input, select, label")) return;
            window.hubNavigate ? window.hubNavigate(href) : (window.location.href = href);
        });
    });

    initTables(root);

    // POST forms show a waiting toast while they submit (resources/js/notify.js)
    root.querySelectorAll('form[method="post" i]:not([data-notify-loading])').forEach((form) => {
        form.setAttribute("data-notify-loading", "Saving your changes…");
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => window.hubInitPage(document.querySelector(".hub-page") || document));
} else {
    window.hubInitPage(document.querySelector(".hub-page") || document);
}


// ==========================================================================
// THEME: light / dark switch in the top bar
// --------------------------------------------------------------------------
// Same storage as Settings (localStorage "mode"), so the choice carries over
// to the shop. Until it is used, the Hub follows the device theme.
// ==========================================================================

(() => {
    const media = window.matchMedia ? window.matchMedia("(prefers-color-scheme: dark)") : null;
    const read = () => { try { return localStorage.getItem("mode"); } catch (e) { return null; } };
    const isDark = () => document.body.classList.contains("dark-mode");

    const paint = () => {
        const dark = isDark();
        document.querySelectorAll("[data-hub-theme]").forEach((btn) => {
            btn.setAttribute("aria-pressed", dark ? "true" : "false");
            btn.setAttribute("aria-label", dark ? "Switch to light mode" : "Switch to dark mode");
            btn.title = dark ? "Light mode" : "Dark mode";
        });
    };

    document.addEventListener("click", (e) => {
        const btn = e.target.closest && e.target.closest("[data-hub-theme]");
        if (!btn) return;
        const next = isDark() ? "light" : "dark";
        try { localStorage.setItem("mode", next); } catch (err) {}
        document.body.classList.add("theme-switching");
        document.body.classList.toggle("dark-mode", next === "dark");
        setTimeout(() => document.body.classList.remove("theme-switching"), 350);
        paint();
    });

    // device theme changed and no choice was made
    media?.addEventListener?.("change", () => {
        const mode = read();
        if (mode !== "dark" && mode !== "light") { document.body.classList.toggle("dark-mode", media.matches); paint(); }
    });

    paint();
})();
