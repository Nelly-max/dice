/*
 |--------------------------------------------------------------------------
 | Shrink photos before upload
 |--------------------------------------------------------------------------
 | Forms marked <form data-shrink-images> have their photos resized in the
 | browser (longest side 1600px, JPEG) before they are sent. Phone camera
 | photos are often 3–8 MB; several of them together go over PHP's
 | upload_max_filesize / post_max_size limits and the form is rejected
 | before Laravel even sees it (rider application: up to 8 photos).
 |
 | Files that are already small, are not photos, or cannot be decoded by the
 | browser (e.g. HEIC on some browsers) are sent unchanged.
 | Loaded on every page through reused.js.
 */

const MAX_SIDE = 1600;
const QUALITY = 0.82;
const SMALL_ENOUGH = 900 * 1024;   // leave files under ~0.9 MB alone

async function shrink(file) {
    if (!file || !/^image\/(jpeg|png|webp|heic|heif)$/i.test(file.type) || file.size <= SMALL_ENOUGH) return file;
    try {
        const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
        const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
        const w = Math.round(bitmap.width * scale), h = Math.round(bitmap.height * scale);
        const canvas = document.createElement('canvas');
        canvas.width = w; canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';          // transparent PNGs get a white background in JPEG
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(bitmap, 0, 0, w, h);
        bitmap.close?.();
        const blob = await new Promise((res) => canvas.toBlob(res, 'image/jpeg', QUALITY));
        if (!blob || blob.size >= file.size) return file;
        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
        return new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() });
    } catch (e) {
        return file;   // browser cannot read this format: send as is
    }
}

async function shrinkForm(form) {
    const inputs = [...form.querySelectorAll('input[type="file"]')].filter((i) => i.files && i.files.length);
    for (const input of inputs) {
        const files = await Promise.all([...input.files].map(shrink));
        if (files.every((f, i) => f === input.files[i])) continue;
        try {
            const dt = new DataTransfer();
            files.forEach((f) => dt.items.add(f));
            input.files = dt.files;
        } catch (e) { /* very old browser: keep originals */ }
    }
}

const onSubmit = async (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-shrink-images')) return;
    if (form.__smShrunk) { form.__smShrunk = false; return; }   // second pass: let it go through

    e.preventDefault();
    e.stopImmediatePropagation();
    if (form.__smBusy) return;
    form.__smBusy = true;

    const submitter = e.submitter || null;
    const busy = window.notify ? window.notify.loading('Preparing your photos…') : null;
    try { await shrinkForm(form); } finally { busy?.close?.(); form.__smBusy = false; }

    form.__smShrunk = true;
    if (form.requestSubmit) form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
    else form.submit();
};

// re-runs after soft navigation: always keep exactly one listener
if (window.__smShrinkSubmit) document.removeEventListener('submit', window.__smShrinkSubmit, true);
window.__smShrinkSubmit = onSubmit;
document.addEventListener('submit', onSubmit, true);
