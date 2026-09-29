(function () {
  'use strict';

  const KEY = 'tcl.communication.v1';
  const CATEGORIES = ['Vie du club', 'Événement', 'Compétition', 'Informations pratiques'];
  const FIELDS = ['id', 'title', 'body', 'category', 'link', 'status', 'createdAt', 'updatedAt', 'validatedAt', 'facebookStatus'];
  const LIMIT = 200;
  const MAX_DATA_SIZE = 8_000_000;
  const MAX_IMAGE_BYTES = 500_000;
  const MAX_IMAGES_BYTES = 2_000_000;

  function fail(message) { throw new Error(message); }
  function plain(value) { return value !== null && typeof value === 'object' && !Array.isArray(value); }
  function date(value) { return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(value) && !Number.isNaN(Date.parse(value)) && new Date(value).toISOString() === value; }
  function text(value, min, max, label) {
    if (typeof value !== 'string' || value.trim().length < min || value.length > max || /[\u0000-\u0008\u000b\u000c\u000e-\u001f]/.test(value)) fail(label + ' : contenu invalide.');
  }
  function inspectImage(bytes) {
    if (bytes.length < 16) fail('Ce fichier n’est pas une image JPEG, PNG ou WebP lisible.');
    const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
    const word = (start, length) => String.fromCharCode(...bytes.slice(start, start + length));
    let type, width, height;
    if (bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255) {
      type = 'image/jpeg';
      let offset = 2;
      while (offset + 8 < bytes.length) {
        if (bytes[offset++] !== 255) break;
        while (bytes[offset] === 255) offset++;
        const marker = bytes[offset++];
        if (marker === 217 || marker === 218) break;
        if (marker === 0 || marker === 1 || (marker >= 208 && marker <= 215)) continue;
        if (offset + 2 > bytes.length) break;
        const length = view.getUint16(offset);
        if (length < 2 || offset + length > bytes.length) break;
        if ([192, 193, 194, 195, 197, 198, 199, 201, 202, 203, 205, 206, 207].includes(marker) && length >= 8) {
          height = view.getUint16(offset + 3); width = view.getUint16(offset + 5); break;
        }
        offset += length;
      }
    } else if (bytes.slice(0, 8).join(',') === '137,80,78,71,13,10,26,10' && bytes.length >= 24 && word(12, 4) === 'IHDR') {
      type = 'image/png'; width = view.getUint32(16); height = view.getUint32(20);
    } else if (word(0, 4) === 'RIFF' && word(8, 4) === 'WEBP' && bytes.length >= 30) {
      type = 'image/webp';
      const chunk = word(12, 4);
      if (chunk === 'VP8X') { width = 1 + bytes[24] + (bytes[25] << 8) + (bytes[26] << 16); height = 1 + bytes[27] + (bytes[28] << 8) + (bytes[29] << 16); }
      else if (chunk === 'VP8 ' && bytes[23] === 157 && bytes[24] === 1 && bytes[25] === 42) { width = view.getUint16(26, true) & 16383; height = view.getUint16(28, true) & 16383; }
      else if (chunk === 'VP8L' && bytes[20] === 47) { width = 1 + bytes[21] + ((bytes[22] & 63) << 8); height = 1 + (bytes[22] >> 6) + (bytes[23] << 2) + ((bytes[24] & 15) << 10); }
    }
    if (!type || !width || !height) fail('Le contenu du fichier n’est pas une image JPEG, PNG ou WebP reconnue.');
    return { type: type, width: width, height: height };
  }
  function imageBytes(image) {
    if (image === undefined || image === null) return 0;
    if (!plain(image) || Object.keys(image).length !== 3 || !['dataUrl', 'alt', 'name'].every(key => Object.prototype.hasOwnProperty.call(image, key))) fail('Structure de l’image invalide.');
    text(image.alt, 1, 240, 'Description de l’image');
    text(image.name, 1, 160, 'Nom de l’image');
    if (typeof image.dataUrl !== 'string' || image.dataUrl.length > Math.ceil(MAX_IMAGE_BYTES / 3) * 4 + 40) fail('L’image préparée dépasse 500 Ko.');
    const match = /^data:image\/(jpeg|png|webp);base64,([A-Za-z0-9+/]+={0,2})$/.exec(image.dataUrl);
    if (!match || match[2].length % 4 !== 0) fail('Image invalide : seuls JPEG, PNG et WebP intégrés sont acceptés.');
    const bytes = match[2].length * 3 / 4 - (match[2].endsWith('==') ? 2 : match[2].endsWith('=') ? 1 : 0);
    if (bytes > MAX_IMAGE_BYTES || bytes < 12) fail('L’image préparée est vide ou dépasse 500 Ko.');
    const decoded = new Uint8Array(bytes);
    if (typeof atob === 'function') {
      const binary = atob(match[2]);
      for (let i = 0; i < bytes; i++) decoded[i] = binary.charCodeAt(i);
    } else {
      const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
      let output = 0;
      for (let i = 0; i < match[2].length; i += 4) {
        const number = (alphabet.indexOf(match[2][i]) << 18) | (alphabet.indexOf(match[2][i + 1]) << 12) | (Math.max(0, alphabet.indexOf(match[2][i + 2])) << 6) | Math.max(0, alphabet.indexOf(match[2][i + 3]));
        decoded[output++] = (number >> 16) & 255;
        if (output < bytes) decoded[output++] = (number >> 8) & 255;
        if (output < bytes) decoded[output++] = number & 255;
      }
    }
    const details = inspectImage(decoded);
    if (details.type !== 'image/' + match[1]) fail('Le contenu de l’image ne correspond pas à son format.');
    if (details.width > 1600 || details.height > 1600) fail('Une image enregistrée doit être préparée à 1 600 pixels maximum. Réajoutez le fichier depuis le formulaire.');
    return bytes;
  }
  function checkContent(post) {
    text(post.title, 1, 120, 'Titre');
    if (post.adocVisibleOnTenup !== undefined && typeof post.adocVisibleOnTenup !== 'boolean') fail('La visibilité Ten’Up doit être Oui ou Non.');
    const bytes = imageBytes(post.image);
    text(post.body, post.image ? 0 : 1, 5000, 'Texte');
    if (!CATEGORIES.includes(post.category)) fail('Choisissez une catégorie proposée.');
    text(post.link, 0, 1000, 'Lien');
    if (post.link) {
      let url;
      try { url = new URL(post.link); } catch (_) { fail('Le lien doit être une adresse HTTPS complète.'); }
      if (url.protocol !== 'https:' || !url.hostname || url.username || url.password || /\s/.test(post.link)) fail('Le lien doit être une adresse HTTPS complète, sans identifiant ni mot de passe.');
    }
    return bytes;
  }
  function checkPost(post) {
    if (!plain(post) || Object.keys(post).some(field => !FIELDS.includes(field) && !['image', 'adocVisibleOnTenup'].includes(field)) || !FIELDS.every(field => Object.prototype.hasOwnProperty.call(post, field))) fail('Structure d’actualité invalide.');
    if (typeof post.id !== 'string' || !/^[a-zA-Z0-9_-]{1,80}$/.test(post.id)) fail('Identifiant d’actualité invalide.');
    const bytes = checkContent(post);
    if (!date(post.createdAt) || !date(post.updatedAt) || post.updatedAt < post.createdAt) fail('Dates d’actualité invalides.');
    if (post.status === 'draft') {
      if (post.validatedAt !== null || post.facebookStatus !== 'not-sent') fail('État du brouillon incohérent.');
    } else if (post.status === 'validated') {
      if (!date(post.validatedAt) || post.validatedAt < post.createdAt || post.validatedAt !== post.updatedAt || post.facebookStatus !== 'simulated') fail('État de validation incohérent.');
    } else fail('Statut d’actualité inconnu.');
    return bytes;
  }
  function checkData(data, importing) {
    const allowed = importing ? ['version', 'posts', 'exportedAt'] : ['version', 'posts'];
    if (!plain(data) || data.version !== 1 || !Array.isArray(data.posts) || Object.keys(data).some(key => !allowed.includes(key)) || data.posts.length > LIMIT) fail('Format non reconnu ou limite de 200 actualités dépassée.');
    if (data.exportedAt !== undefined && !date(data.exportedAt)) fail('Date d’export invalide.');
    const ids = new Set();
    let totalImageBytes = 0;
    data.posts.forEach(post => {
      totalImageBytes += checkPost(post);
      if (ids.has(post.id)) fail('Le fichier contient plusieurs actualités de même identifiant.');
      ids.add(post.id);
    });
    if (totalImageBytes > MAX_IMAGES_BYTES) fail('La réserve de 2 Mo d’images est pleine. Retirez une ancienne affiche ou utilisez une image plus légère. Aucun contenu n’a été remplacé.');
    return data.posts;
  }
  function read() {
    let raw;
    try { raw = localStorage.getItem(KEY); } catch (_) { fail('Le stockage du navigateur est inaccessible. Vos saisies restent à l’écran ; autorisez le stockage pour les enregistrer.'); }
    if (raw === null) return [];
    try {
      if (raw.length > MAX_DATA_SIZE) fail('Stockage trop volumineux.');
      return checkData(JSON.parse(raw), false);
    } catch (_) { fail('Le stockage local est illisible ou incohérent. Aucun contenu n’a été écrasé. Conservez une copie brute avant toute réparation.'); }
  }
  function write(posts) {
    checkData({ version: 1, posts: posts }, false);
    const raw = JSON.stringify({ version: 1, posts: posts });
    if (raw.length > MAX_DATA_SIZE) fail('Le stockage dépasserait la taille maximale. Aucun contenu n’a été remplacé.');
    try { localStorage.setItem(KEY, raw); }
    catch (_) { fail('Enregistrement impossible : stockage bloqué ou espace insuffisant. La dernière sauvegarde reste intacte et vos saisies restent à l’écran.'); }
  }
  function id() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
    if (!window.crypto || !window.crypto.getRandomValues) fail('Ce navigateur ne permet pas de créer un identifiant sûr.');
    return 'tcl-' + Array.from(window.crypto.getRandomValues(new Uint32Array(4)), value => value.toString(16).padStart(8, '0')).join('');
  }
  function nextTimestamp(previous) { return new Date(Math.max(Date.now(), previous ? Date.parse(previous.updatedAt) + 1 : 0)).toISOString(); }
  function save(input) {
    if (!plain(input)) fail('Actualité invalide.');
    const content = { title: typeof input.title === 'string' ? input.title.trim() : input.title, body: typeof input.body === 'string' ? input.body.trim() : input.body, category: input.category, link: typeof input.link === 'string' ? input.link.trim() : input.link, adocVisibleOnTenup: input.adocVisibleOnTenup === undefined ? false : input.adocVisibleOnTenup, image: input.image ? { dataUrl: input.image.dataUrl, alt: typeof input.image.alt === 'string' ? input.image.alt.trim() : input.image.alt, name: input.image.name } : null };
    checkContent(content);
    const posts = read();
    const index = input.id ? posts.findIndex(post => post.id === input.id) : -1;
    if (input.id && index < 0) fail('Cette actualité a été supprimée dans un autre onglet. Copiez votre texte puis créez un nouveau brouillon.');
    const previous = index < 0 ? null : posts[index];
    if (previous && input.updatedAt !== previous.updatedAt) fail('Cette actualité a changé dans un autre onglet. Vos saisies sont conservées : copiez-les puis rechargez l’actualité avant de poursuivre.');
    const now = nextTimestamp(previous);
    const post = Object.assign({ id: previous ? previous.id : id() }, content, { status: 'draft', createdAt: previous ? previous.createdAt : now, updatedAt: now, validatedAt: null, facebookStatus: 'not-sent' });
    if (index < 0) posts.push(post); else posts[index] = post;
    write(posts);
    return post;
  }
  function validate(postId, expectedUpdatedAt) {
    const posts = read();
    const post = posts.find(item => item.id === postId);
    if (!post) fail('Actualité introuvable. Rechargez la liste.');
    if (expectedUpdatedAt && post.updatedAt !== expectedUpdatedAt) fail('L’actualité a changé depuis votre aperçu. Rechargez-la et relisez-la avant de valider.');
    if (post.status !== 'draft') fail('Cette actualité est déjà validée pour la démonstration.');
    post.status = 'validated';
    post.updatedAt = post.validatedAt = nextTimestamp(post);
    post.facebookStatus = 'simulated';
    write(posts);
    return post;
  }
  function remove(postId) {
    const posts = read();
    if (!posts.some(post => post.id === postId)) fail('Actualité introuvable.');
    write(posts.filter(post => post.id !== postId));
  }
  function exportData() { return { version: 1, exportedAt: new Date().toISOString(), posts: read() }; }
  function sameContent(a, b) { return ['title', 'body', 'category', 'link'].every(key => a[key] === b[key]) && Boolean(a.adocVisibleOnTenup) === Boolean(b.adocVisibleOnTenup) && ((!a.image && !b.image) || (a.image && b.image && ['dataUrl', 'alt', 'name'].every(key => a.image[key] === b.image[key]))); }
  function importData(data) {
    const incoming = checkData(data, true);
    const posts = read();
    const result = { imported: 0, skipped: 0, conflicts: 0 };
    incoming.forEach(original => {
      const existing = posts.find(post => post.id === original.id);
      if (existing && sameContent(existing, original)) { result.skipped++; return; }
      const now = new Date().toISOString();
      const post = Object.assign({}, original, { status: 'draft', updatedAt: now, createdAt: original.createdAt > now ? now : original.createdAt, validatedAt: null, facebookStatus: 'not-sent' });
      if (existing) { post.id = id(); result.conflicts++; }
      posts.push(post);
      result.imported++;
    });
    if (posts.length > LIMIT) fail('La fusion dépasserait 200 actualités. Aucun import effectué.');
    write(posts);
    return result;
  }
  function publicPosts() { return read().filter(post => post.status === 'validated').sort((a, b) => b.validatedAt.localeCompare(a.validatedAt)); }
  window.TCLCommunication = Object.freeze({ read: read, save: save, validate: validate, remove: remove, exportData: exportData, importData: importData, publicPosts: publicPosts, imageBytes: imageBytes, inspectImage: inspectImage, key: KEY, maxImageBytes: MAX_IMAGE_BYTES, maxImagesBytes: MAX_IMAGES_BYTES, maxImportBytes: MAX_DATA_SIZE, categories: Object.freeze(CATEGORIES.slice()) });
})();
