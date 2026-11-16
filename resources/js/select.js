/*
|--------------------------------------------------------------------------
| County -> town -> place, for the Hub forms (rider application, Add a lead, ...)
|--------------------------------------------------------------------------
| The fields are plain <select>s (partials/Hub/picker.blade.php) that ui-controls.js turns
| into the searchable dropdown; it picks up the options added here by itself.
|
|   #county_id  changes -> #town_id gets that county's towns, #place_id is emptied
|   #town_id    changes -> #place_id gets that town's places
|
| On load, a county / town that is already picked (editing, or the form came back with
| errors) gets its towns / places loaded, and the saved town / place (data-selected) picked.
| A slow, older answer never overwrites a newer one.
*/

const LOADERS = {
    town: (countyId) => `/locations/towns/${countyId}`,
    place: (townId) => `/locations/places/${townId}`,
};

async function load(url) {
    try {
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        return res.ok ? await res.json() : [];
    } catch (e) {
        return [];
    }
}

// replace a select's options: the empty "Town" / "Place" option, then the rows; `keep` is
// the id to leave picked (if it is in the list)
function fill(select, rows, keep = '') {
    const placeholder = select.dataset.placeholder || select.options[0]?.textContent || '';
    const frag = document.createDocumentFragment();
    frag.appendChild(new Option(placeholder, ''));
    (Array.isArray(rows) ? rows : []).forEach((row) => {
        const id = String(row.id);
        frag.appendChild(new Option(row.name, id, false, id === String(keep)));
    });
    select.replaceChildren(frag);
    if (!keep || select.value !== String(keep)) select.value = '';
}

function init(root = document) {
    const county = root.querySelector('select#county_id');
    const town = root.querySelector('select#town_id');
    const place = root.querySelector('select#place_id');
    if (!county || county.dataset.cascade === '1') return;
    county.dataset.cascade = '1';

    let townReq = 0;
    let placeReq = 0;

    const loadTowns = async (keep = '') => {
        const req = ++townReq;
        placeReq++;                                   // a place list still loading is now stale
        if (town) fill(town, [], '');
        if (place) fill(place, [], '');
        if (!town || !county.value) return;
        town.disabled = true;
        const rows = await load(LOADERS.town(county.value));
        if (req !== townReq) return;
        town.disabled = false;
        fill(town, rows, keep);
    };

    const loadPlaces = async (keep = '') => {
        const req = ++placeReq;
        if (place) fill(place, [], '');
        if (!place || !town?.value) return;
        place.disabled = true;
        const rows = await load(LOADERS.place(town.value));
        if (req !== placeReq) return;
        place.disabled = false;
        fill(place, rows, keep);
    };

    county.addEventListener('change', () => loadTowns());
    town?.addEventListener('change', () => loadPlaces());

    // already picked on load: fill the lists below it and keep the saved choices
    (async () => {
        const savedTown = town?.dataset.selected || town?.value || '';
        const savedPlace = place?.dataset.selected || place?.value || '';
        if (county.value && town && town.options.length <= 1) await loadTowns(savedTown);
        if (town?.value && place && place.options.length <= 1) await loadPlaces(savedPlace);
    })();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => init());
else init();
