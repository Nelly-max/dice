console.log("Custom select JS loaded");

document.addEventListener("DOMContentLoaded", () => {

    function initSelect(select) {
        const selectBtn = select.querySelector(".select-btn span");
        const searchInp = select.querySelector(".option-search");
        const optionsList = select.querySelector(".options");
        const hiddenInput = select.querySelector("input[type='hidden']");

        let dataList = JSON.parse(select.dataset.options || "[]");
        const selectedId = select.dataset.selected;

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
                    hiddenInput.value = li.dataset.id;
                    selectBtn.innerText = li.dataset.name;
                    select.classList.remove("active");
                    renderOptions();
                    hiddenInput.dispatchEvent(new Event("change"));
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

        select.updateData = function (newData) {
            dataList = newData;
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

    county?.addEventListener("change", async () => {
        const res = await fetch(`/locations/towns/${county.value}`);
        const data = await res.json();
        townSelect.updateData(data);
        townInput.value = "";
        placeSelect.updateData([]);
        placeInput.value = "";
    });

    townInput?.addEventListener("change", async () => {
        const res = await fetch(`/locations/places/${townInput.value}`);
        const data = await res.json();
        placeSelect.updateData(data);
        placeInput.value = "";
    });
});
