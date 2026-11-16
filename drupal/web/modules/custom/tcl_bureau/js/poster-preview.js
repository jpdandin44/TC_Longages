import { getDocument, GlobalWorkerOptions } from '../vendor/pdfjs/pdf.js';
GlobalWorkerOptions.workerSrc = new URL('../vendor/pdfjs/pdf.worker.js', import.meta.url).href;

async function renderPdf(container, source) {
  container.textContent = 'Chargement de l’aperçu PDF…';
  const task = getDocument({ ...source, isEvalSupported: false, enableXfa: false,
    cMapUrl: new URL('../vendor/pdfjs/cmaps/', import.meta.url).href, cMapPacked: true,
    standardFontDataUrl: new URL('../vendor/pdfjs/standard_fonts/', import.meta.url).href,
    wasmUrl: new URL('../vendor/pdfjs/wasm/', import.meta.url).href });
  try {
    const pdf = await task.promise;
    const page = await pdf.getPage(1);
    const original = page.getViewport({ scale: 1 });
    const viewport = page.getViewport({ scale: Math.min(1200 / original.width, 1600 / original.height, 2) });
    const canvas = document.createElement('canvas');
    canvas.width = Math.ceil(viewport.width); canvas.height = Math.ceil(viewport.height);
    canvas.setAttribute('role', 'img'); canvas.setAttribute('aria-label', 'Aperçu de la première page de l’affiche PDF');
    await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
    const note = document.createElement('p');
    note.textContent = `Aperçu — page 1 sur ${pdf.numPages}. Le téléchargement conserve le PDF complet.`;
    container.replaceChildren(canvas, note);
  } catch {
    container.textContent = source.data ? 'Aperçu PDF indisponible. Vérifiez le fichier sélectionné ou choisissez une autre affiche.' : 'Aperçu PDF indisponible. Utilisez le téléchargement pour consulter le document.';
  } finally { await task.destroy(); }
}

// Only the protected/public route already present in the rendered page is fetched.
for (const container of document.querySelectorAll('[data-pdf-url]')) {
  const url = new URL(container.dataset.pdfUrl, location.href);
  if (url.origin === location.origin) renderPdf(container, { url: url.href });
}
const form = document.querySelector('form[id^="tcl-bureau-communication-form"]');
if (form) {
  const preview = document.createElement('section'); preview.className = 'tcl-preview-card tcl-live-preview';
  const heading = document.createElement('h2'); heading.textContent = 'Aperçu de votre saisie';
  const notice = document.createElement('p'); notice.textContent = 'Non enregistré — enregistrez le brouillon puis relisez les aperçus par canal avant validation.';
  const title = document.createElement('h3'); const body = document.createElement('p'); body.className = 'tcl-post-body';
  const poster = document.createElement('div'); poster.className = 'tcl-pdf-preview';
  preview.append(heading, notice, title, body, poster);
  form.querySelector('.form-actions')?.before(preview);
  const update = () => { title.textContent = form.elements.title?.value || 'Titre de la communication'; body.textContent = form.elements.body?.value || ''; };
  form.addEventListener('input', update); update();
  const existingPdf = form.querySelector('[data-pdf-url]')?.dataset.pdfUrl;
  const existingImage = form.querySelector('img.tcl-post-image');
  const fileInput = form.querySelector('input[type=file]');
  const removeInput = form.querySelector('input[name=remove_image]');
  let imageUrl; let sequence = 0;
  const updatePoster = async () => {
    const current = ++sequence; const file = fileInput?.files?.[0];
    if (imageUrl) { URL.revokeObjectURL(imageUrl); imageUrl = undefined; }
    poster.replaceChildren();
    if (!file) {
      if (removeInput?.checked) { poster.textContent = 'L’affiche sera retirée à l’enregistrement.'; return; }
      if (existingPdf) {
        const url = new URL(existingPdf, location.href);
        if (url.origin === location.origin) { const result = document.createElement('div'); poster.append(result); await renderPdf(result, { url: url.href }); }
      } else if (existingImage) { poster.append(existingImage.cloneNode(true)); }
      return;
    }
    if (file.size > 8 * 1024 * 1024) { poster.textContent = 'Fichier trop volumineux : 8 Mo maximum.'; return; }
    if (file.type === 'application/pdf') {
      let bytes;
      try { bytes = new Uint8Array(await file.arrayBuffer()); }
      catch { if (sequence === current) poster.textContent = 'Lecture impossible. Sélectionnez à nouveau le fichier.'; return; }
      if (sequence !== current) return;
      const result = document.createElement('div'); poster.append(result);
      await renderPdf(result, { data: bytes });
    } else if (['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      const img = document.createElement('img'); img.className = 'tcl-post-image'; img.alt = 'Aperçu de l’affiche sélectionnée';
      imageUrl = URL.createObjectURL(file); img.src = imageUrl; poster.append(img);
    } else { poster.textContent = 'Choisissez un PDF ou une image JPEG, PNG ou WebP.'; }
  };
  fileInput?.addEventListener('change', updatePoster);
  removeInput?.addEventListener('change', updatePoster);
  updatePoster();
}

