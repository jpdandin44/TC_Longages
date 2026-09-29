(function () {
  'use strict';
  const MAX_FILE_BYTES = 8_000_000;
  const MAX_PIXELS = 40_000_000;
  const MAX_SIDE = 16_000;
  const OUTPUT_SIDE = 1600;
  const OUTPUT_BYTES = 500_000;
  function fail(message) { throw new Error(message); }
  function checkDimensions(width, height) {
    if (!width || !height || width > MAX_SIDE || height > MAX_SIDE || width * height > MAX_PIXELS) fail('Image trop grande : utilisez au maximum 40 millions de pixels, sans côté supérieur à 16 000 pixels.');
  }
  function inspect(bytes) {
    const image = window.TCLCommunication.inspectImage(bytes);
    checkDimensions(image.width, image.height);
    return image.type;
  }
  async function decode(blob) {
    if (typeof window.createImageBitmap === 'function') return window.createImageBitmap(blob);
    const url = URL.createObjectURL(blob);
    try {
      return await new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = () => reject(new Error('Image illisible. Essayez une autre image JPEG, PNG ou WebP.'));
        image.src = url;
      });
    } finally { URL.revokeObjectURL(url); }
  }
  async function prepare(file) {
    if (!file || typeof file.arrayBuffer !== 'function' || !file.size || file.size > MAX_FILE_BYTES) fail('Choisissez une image JPEG, PNG ou WebP de 8 Mo maximum.');
    const bytes = new Uint8Array(await file.arrayBuffer());
    const type = inspect(bytes);
    if (file.type && file.type !== type) fail('Le format annoncé ne correspond pas au contenu du fichier. Choisissez une image JPEG, PNG ou WebP authentique.');
    let bitmap;
    try { bitmap = await decode(new Blob([bytes], { type: type })); }
    catch (_) { fail('Image illisible. Essayez une autre image JPEG, PNG ou WebP.'); }
    try {
      const width = bitmap.width || bitmap.naturalWidth;
      const height = bitmap.height || bitmap.naturalHeight;
      checkDimensions(width, height);
      let ratio = Math.min(1, OUTPUT_SIDE / Math.max(width, height));
      const canvas = document.createElement('canvas');
      const context = canvas.getContext('2d');
      if (!context) fail('Ce navigateur ne peut pas préparer l’image. Aucun contenu n’a été changé.');
      for (let attempt = 0; attempt < 5; attempt++) {
        canvas.width = Math.max(1, Math.round(width * ratio)); canvas.height = Math.max(1, Math.round(height * ratio));
        context.fillStyle = '#ffffff'; context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        for (const quality of [.9, .8, .7, .6]) {
          const dataUrl = canvas.toDataURL('image/jpeg', quality);
          if (dataUrl.startsWith('data:image/jpeg;base64,') && (dataUrl.length - 23) * .75 <= OUTPUT_BYTES) {
            const name = String(file.name || 'affiche.jpeg').replace(/[\u0000-\u001f]/g, '').slice(0, 160) || 'affiche.jpeg';
            return { dataUrl: dataUrl, alt: '', name: name };
          }
        }
        ratio *= .8;
      }
      fail('Cette image reste trop volumineuse après préparation. Essayez une image plus légère.');
    } finally { if (bitmap && typeof bitmap.close === 'function') bitmap.close(); }
  }
  window.TCLCommunicationImages = Object.freeze({ prepare: prepare, inspect: inspect, maxFileBytes: MAX_FILE_BYTES });
})();
