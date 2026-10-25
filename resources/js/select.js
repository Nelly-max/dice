console.log("Custom select JS loaded");

document.addEventListener("DOMContentLoaded", () => {

    function initSelect(select) {
        const selectBtn = select.querySelector(".select-btn span");
        const searchInp = select.querySelector(".option-search");
        const optionsList = select.querySelector(".options");
        const hiddenInput = select.querySelector("input[type='hidden']");

        let dataList = JSON.parse(select.dataset.options || "[]");
        const selectedId = select.dataset.selected;
        const placeholder = selectBtn.innerText;      // e.g. "County", "Town", "Place"

        function renderOptions() {
            optionsList.innerHTML = "";

            dataList.forEach(item => {
                const selected = String(item.id) === String(hiddenInput.value);
                if (selected) selectBtn.innerText = item.name;

                optionsList.insertAdjacentHTML(
                    "beforeend",
                    `<li class="${selected ? 'selected' : ''}"
                        data-id="${item.id}"
                        data-name="${item.name}">
                        ${item.name}
                     </li>`
                );
            });

            attachEvents();
        }

        function attachEvents() {
            optionsList.querySelectorAll("li").forEach(li => {
                li.onclick = () => {
                    const changed = hiddenInput.value !== li.dataset.id;
                    hiddenInput.value = li.dataset.id;
                    selectBtn.innerText = li.dataset.name;
                    select.classList.remove("active");
                    if (searchInp) searchInp.value = "";
                    renderOptions();
                    // picking the same option again must not reset the selects below it
                    if (changed) hiddenInput.dispatchEvent(new Event("change"));
                };
            });
        }

        searchInp?.addEventListener("keyup", () => {
            const search = searchInp.value.toLowerCase();
            optionsList.innerHTML = "";
            dataList
                .filter(i => i.name.toLowerCase().includes(search))
                .forEach(item => {
                    optionsList.insertAdjacentHTML(
                        "beforeend",
                        `<li data-id="${item.id}" data-name="${item.name}">
                            ${item.name}
                         </li>`
                    );
                });
            attachEvents();
        });

        select.querySelector(".select-btn").onclick = () =>
            select.classList.toggle("active");

        document.addEventListener("click", e => {
            if (!select.contains(e.target)) select.classList.remove("active");
        });

        // new list of options; the current choice is cleared unless keep = true
        select.updateData = function (newData, keep = false) {
            dataList = Array.isArray(newData) ? newData : [];
            if (!keep) {
                hiddenInput.value = "";
                selectBtn.innerText = placeholder;
            }
            if (searchInp) searchInp.value = "";
            renderOptions();
        };

        // clear the choice (and optionally the options) and show the placeholder again
        select.reset = function (clearOptions = true) {
            if (clearOptions) dataList = [];
            hiddenInput.value = "";
            selectBtn.innerText = placeholder;
            if (searchInp) searchInp.value = "";
            select.classList.remove("active");
            renderOptions();
        };

        if (selectedId && hiddenInput.value === "") {
            hiddenInput.value = selectedId;
        }

        renderOptions();
    }

    document.querySelectorAll(".custom-select").forEach(initSelect);

    // Cascading
    const county = document.querySelector("#county_id");
    const townSelect = document.querySelector("#townSelect");
    const townInput = document.querySelector("#town_id");
    const placeSelect = document.querySelector("#placeSelect");
    const placeInput = document.querySelector("#place_id");

    // Changing the county clears the town and place; changing the town clears the place.
    // Cleared straight away (before the new list arrives), and a slow older request can
    // never overwrite a newer one.
    let townReq = 0, placeReq = 0;

    const load = async (url) => {
        try {
            const res = await fetch(url, { headers: { Accept: "application/json" } });
            return res.ok ? await res.json() : [];
        } catch (e) {
            return [];
        }
    };

    county?.addEventListener("change", async () => {
        const req = ++townReq;
        placeReq++;                      // cancel any place list still loading
        townSelect?.reset();
        placeSelect?.reset();
        if (!county.value) return;
        const data = await load(`/locations/towns/${county.value}`);
        if (req === townReq) townSelect?.updateData(data);
    });

    townInput?.addEventListener("change", async () => {
        const req = ++placeReq;
        placeSelect?.reset();
        if (!townInput.value) return;
        const data = await load(`/locations/places/${townInput.value}`);
        if (req === placeReq) placeSelect?.updateData(data);
    });
});
