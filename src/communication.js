(function () {
  'use strict';

  const store = window.TCLCommunication;
  const whatsapp = window.TCLWhatsApp;
  const adoc = window.TCLADOC;
  const $ = id => document.getElementById(id);
  const form = $('post-form');
  const fields = { title: $('post-title'), body: $('post-body'), category: $('post-category'), link: $('post-link') };
  let current = null;
  let dirty = false;
  let storageBlocked = false;
  let approvalSnapshot = null;
  let previewMode = 'site';
  let draftImage = null;
  let imageBusy = false;
  let imageRequest = 0;

  function notify(message, error) {
    const target = $('feedback');
    target.textContent = message;
    target.classList.toggle('error', Boolean(error));
    target.hidden = false;
  }
  function guard(action) {
    try { return action(); }
    catch (error) { notify(error.message || 'Une erreur empêche cette opération. Vos saisies restent à l’écran.', true); return null; }
  }
  function content() {
    return { title: fields.title.value, body: fields.body.value, category: fields.category.value, link: fields.link.value, adocVisibleOnTenup: $('post-adoc-tenup-yes').checked, image: draftImage ? Object.assign({}, draftImage, { alt: $('post-image-alt').value }) : null };
  }
  function showImage(id, image) {
    const element = $(id);
    element.hidden = !image;
    if (image) { element.src = image.dataUrl; element.alt = image.alt || 'Affiche en préparation'; }
    else { element.removeAttribute('src'); element.alt = ''; }
  }
  function imageState() {
    const image = content().image;
    showImage('post-image-preview', image);
    $('post-image-details').hidden = !image;
    $('post-image-alt').required = Boolean(image);
    $('post-image-alt').disabled = !image;
    fields.body.required = !image;
    $('body-requirement').textContent = image ? 'facultatif avec une affiche' : 'obligatoire sans affiche';
    $('image-status').textContent = imageBusy ? 'Préparation de l’image dans votre navigateur…' : image ? image.name + ' · image préparée localement, affiche entière conservée' : 'Aucune image ajoutée.';
    $('post-image').disabled = storageBlocked;
    $('remove-post-image').disabled = storageBlocked;
  }
  function changed() {
    const post = content();
    dirty = current ? ['title', 'body', 'category', 'link'].some(key => post[key] !== current[key]) || post.adocVisibleOnTenup !== Boolean(current.adocVisibleOnTenup) || JSON.stringify(post.image || null) !== JSON.stringify(current.image || null) : Boolean(post.title || post.body || post.link || post.image || post.adocVisibleOnTenup || post.category !== 'Vie du club');
    checkLink(); imageState(); preview(); state();
  }
  async function attachImage(file, options) {
    if (storageBlocked) { notify('Le stockage doit être rétabli avant de préparer une affiche.', true); return false; }
    const request = ++imageRequest;
    imageBusy = true; imageState(); state();
    try {
      const image = await window.TCLCommunicationImages.prepare(file);
      if (request !== imageRequest) return false;
      const others = store.read().filter(post => !current || post.id !== current.id);
      const bytes = (image.dataUrl.length - 'data:image/jpeg;base64,'.length) * .75;
      if (others.reduce((total, post) => total + store.imageBytes(post.image), 0) + bytes > store.maxImagesBytes) throw new Error('La réserve de 2 Mo d’images est pleine. Retirez une ancienne affiche ou choisissez une image plus légère.');
      draftImage = image;
      $('post-image-alt').value = options && typeof options.alt === 'string' ? options.alt.slice(0, 240) : '';
      imageBusy = false;
      changed();
      notify('Affiche ajoutée à votre saisie. Décrivez-la puis enregistrez le brouillon ; aucun fichier n’a été transmis.');
      return true;
    } catch (error) {
      if (request === imageRequest) notify(error.message || 'Cette image ne peut pas être préparée. Votre contenu est conservé.', true);
      return false;
    } finally {
      if (request === imageRequest) { imageBusy = false; imageState(); state(); }
    }
  }
  function dateLabel(value) {
    return new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' }).format(new Date(value));
  }
  function preview() {
    const post = content();
    const isAdoc = previewMode === 'adoc';
    const article = adoc.prepare(post);
    $('preview-card').hidden = isAdoc;
    $('adoc-preview').hidden = !isAdoc;
    $('adoc-preview-title').textContent = article.title || 'Le titre de votre article';
    $('adoc-preview-body').textContent = article.body;
    $('adoc-empty-body').hidden = Boolean(article.body);
    $('adoc-preview-count').textContent = article.count.toLocaleString('fr-FR') + ' / 2 000 caractères';
    $('adoc-preview-count').classList.toggle('over-limit', article.count > adoc.maxContent);
    $('adoc-preview-tenup').textContent = article.visibleOnTenup ? 'Oui — prévu pour Ten’Up' : 'Non — visibilité Ten’Up non demandée';
    showImage('adoc-preview-image', article.image);
    $('adoc-no-image').hidden = Boolean(article.image);
    $('adoc-preview-warning').hidden = article.ready;
    $('adoc-preview-warning').textContent = article.reason;
    $('preview-title').textContent = post.title.trim() || 'Le titre de votre actualité';
    $('preview-body').textContent = post.body.trim() || 'Votre message prendra place ici. L’aperçu se met à jour pendant votre rédaction.';
    $('preview-category').textContent = post.category;
    showImage('preview-image', post.image);
    const link = $('preview-link');
    link.hidden = true;
    link.removeAttribute('href');
    try {
      const url = new URL(post.link.trim());
      if (url.protocol === 'https:' && !url.username && !url.password && !/\s/.test(post.link.trim())) {
        link.href = url.href;
        link.textContent = previewMode === 'facebook' ? post.link.trim() : 'Consulter le lien ↗';
        link.hidden = false;
      }
    } catch (_) { /* A link being typed is deliberately absent from the preview. */ }
    $('facebook-header').hidden = previewMode !== 'facebook';
    const isWhatsApp = previewMode === 'whatsapp';
    $('whatsapp-header').hidden = !isWhatsApp;
    $('whatsapp-preview-text').hidden = !isWhatsApp;
    $('whatsapp-preview-text').textContent = whatsapp.format(post) || 'Votre message prendra place ici.';
    $('preview-title').hidden = isWhatsApp;
    $('preview-body').hidden = isWhatsApp || Boolean(post.image && !post.body.trim());
    if (isWhatsApp) link.hidden = true;
    $('preview-category').hidden = previewMode !== 'site';
    $('preview-card').classList.toggle('facebook', previewMode === 'facebook');
    $('preview-card').classList.toggle('whatsapp', isWhatsApp);
    $('preview-site').setAttribute('aria-pressed', String(previewMode === 'site'));
    $('preview-facebook').setAttribute('aria-pressed', String(previewMode === 'facebook'));
    $('preview-whatsapp').setAttribute('aria-pressed', String(isWhatsApp));
    $('preview-adoc').setAttribute('aria-pressed', String(isAdoc));
    $('preview-disclaimer').textContent = isAdoc ? 'Parcours préparé d’après l’écran ADOC fourni ; saisie finale dans ADOC. Cet aperçu ne crée aucun article et ne modifie pas Ten’Up.' : previewMode === 'facebook'
      ? 'Aperçu indicatif du texte et de l’affiche ; la présentation réelle dépendra de Facebook. Aucun compte n’est connecté.'
      : isWhatsApp ? 'Aperçu du message et de l’affiche. Le raccourci WhatsApp ne transmet que le texte ; il faudra joindre l’image séparément. Aucun relais automatique.'
        : 'L’actualité n’apparaîtra dans la aperçu du bureau qu’après votre validation.';
  }
  function state() {
    const validated = current && current.status === 'validated';
    const status = $('editor-status');
    status.textContent = dirty ? 'Modifications non enregistrées' : validated ? 'Validé · Facebook simulé' : current ? 'Brouillon' : 'Non enregistré';
    status.classList.toggle('validated', Boolean(validated && !dirty));
    $('editor-kicker').textContent = current ? 'VOTRE ACTUALITÉ' : 'NOUVELLE ACTUALITÉ';
    $('save-state').textContent = dirty ? 'Pensez à enregistrer vos modifications' : current ? 'Enregistré le ' + dateLabel(current.updatedAt) : 'Aucune sauvegarde automatique';
    $('edit-warning').hidden = !validated;
    $('review-post').disabled = storageBlocked || imageBusy || !current || dirty || validated;
    $('save-post').disabled = storageBlocked || imageBusy || (Boolean(current) && !dirty);
    $('delete-post').hidden = !current || storageBlocked;
    $('new-post').disabled = storageBlocked;
    $('export-posts').disabled = storageBlocked;
    $('import-posts').disabled = storageBlocked;
    const canShare = Boolean(validated && !dirty && !storageBlocked && !imageBusy);
    const hasShareLink = canShare && Boolean(whatsapp.shareUrl(whatsapp.format(current)));
    $('share-whatsapp').disabled = !hasShareLink;
    $('copy-whatsapp').disabled = !canShare;
    $('whatsapp-copy-fallback').hidden = true;
    $('whatsapp-copy-text').value = '';
    $('whatsapp-help').textContent = canShare
      ? hasShareLink ? 'Prêt à partager. Ouvrir WhatsApp transmet ce texte à WhatsApp ; vous devrez encore choisir un groupe et confirmer Envoyer.'
        : 'Message long : copiez-le puis collez-le dans votre groupe WhatsApp. Le raccourci d’ouverture est désactivé pour éviter un lien trop long.'
      : dirty ? 'Les modifications doivent être enregistrées puis validées avant le partage.'
        : 'Enregistrez et validez l’actualité pour préparer son partage.';
    if (current && current.image && canShare) $('whatsapp-help').textContent += ' L’affiche n’est pas jointe automatiquement : ajoutez-la vous-même dans WhatsApp.';
    const adocArticle = adoc.prepare(content());
    $('prepare-adoc').disabled = !canShare || !adocArticle.ready;
    $('adoc-help').textContent = !adocArticle.ready ? adocArticle.reason
      : storageBlocked ? 'Le stockage doit être rétabli avant de préparer ADOC.'
        : imageBusy ? 'Patientez pendant la préparation de l’image.'
          : dirty ? 'Enregistrez et validez vos modifications, y compris le choix Ten’Up, avant de préparer ADOC.'
            : canShare ? 'Cette version validée est prête pour une simulation. Aucun article ne sera créé dans ADOC.'
              : 'Enregistrez puis validez cette actualité avant de simuler sa préparation pour ADOC.';
    $('approval-help').textContent = storageBlocked ? 'Le stockage doit être rétabli avant toute sauvegarde.'
      : dirty ? 'Enregistrez vos modifications avant de relire et valider.'
        : validated ? 'Visible dans la aperçu du bureau. Facebook simulé. WhatsApp : partage manuel, livraison non suivie.'
          : current ? 'La prochaine étape vous permet de relire le message et de confirmer votre accord.'
            : 'Enregistrez votre brouillon pour pouvoir le valider.';
  }
  function renderList() {
    let posts;
    try {
      posts = store.read().sort((a, b) => b.updatedAt.localeCompare(a.updatedAt));
      storageBlocked = false;
      $('storage-error').hidden = true;
    } catch (error) {
      storageBlocked = true;
      $('storage-error-text').textContent = error.message;
      $('storage-error').hidden = false;
      $('empty-library').hidden = true;
      $('post-count').textContent = '—';
      $('post-list').replaceChildren();
      state();
      return false;
    }
    $('post-count').textContent = String(posts.length);
    $('empty-library').hidden = posts.length !== 0;
    const items = posts.map(post => {
      const li = document.createElement('li');
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'post-item';
      button.setAttribute('aria-current', String(Boolean(current && current.id === post.id)));
      const badge = document.createElement('span');
      badge.className = 'badge' + (post.status === 'validated' ? ' validated' : '');
      badge.textContent = post.status === 'validated' ? 'Validé · Facebook simulé' : 'Brouillon';
      const title = document.createElement('strong');
      title.textContent = post.title;
      const meta = document.createElement('small');
      meta.textContent = post.category + ' · ' + dateLabel(post.updatedAt);
      button.append(badge, title, meta);
      button.addEventListener('click', () => {
        if (!mayLeave()) return;
        guard(() => {
          const fresh = store.read().find(item => item.id === post.id);
          if (!fresh) throw new Error('Cette actualité a été supprimée dans un autre onglet.');
          load(fresh);
          fields.title.focus();
        });
      });
      li.append(button);
      return li;
    });
    $('post-list').replaceChildren(...items);
    state();
    return true;
  }
  function mayLeave() { return !(dirty || imageBusy) || window.confirm('Vos modifications ou la préparation de l’image ne sont pas enregistrées. Les abandonner et continuer ?'); }
  function load(post) {
    imageRequest++;
    imageBusy = false;
    draftImage = post && post.image ? Object.assign({}, post.image) : null;
    $('post-image').value = '';
    $('post-image-alt').value = draftImage ? draftImage.alt : '';
    current = post || null;
    dirty = false;
    fields.title.value = post ? post.title : '';
    fields.body.value = post ? post.body : '';
    fields.category.value = post ? post.category : 'Vie du club';
    fields.link.value = post ? post.link : '';
    $('post-adoc-tenup-yes').checked = Boolean(post && post.adocVisibleOnTenup);
    $('post-adoc-tenup-no').checked = !Boolean(post && post.adocVisibleOnTenup);
    fields.link.setCustomValidity('');
    renderList();
    imageState();
    preview();
    state();
  }
  function download(data, name, mime) {
    const url = URL.createObjectURL(new Blob([data], { type: mime || 'application/json;charset=utf-8' }));
    const a = document.createElement('a');
    a.href = url;
    a.download = name;
    document.body.append(a);
    a.click();
    a.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
  }
  function checkLink() {
    fields.link.setCustomValidity('');
    const value = fields.link.value.trim();
    if (!value) return;
    try {
      const url = new URL(value);
      if (url.protocol !== 'https:' || !url.hostname || url.username || url.password || /\s/.test(value)) throw new Error();
    } catch (_) { fields.link.setCustomValidity('Indiquez une adresse HTTPS complète, sans identifiant ni mot de passe.'); }
  }
  form.addEventListener('input', event => { if (event.target.id !== 'post-image') changed(); });
  $('post-image').addEventListener('change', event => {
    const file = event.target.files[0]; event.target.value = '';
    if (file) void attachImage(file);
  });
  $('remove-post-image').addEventListener('click', () => {
    imageRequest++; imageBusy = false; draftImage = null; $('post-image-alt').value = ''; changed();
  });
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (imageBusy) { notify('Patientez pendant la préparation de l’image.'); return; }
    checkLink();
    if (!form.reportValidity()) return;
    guard(() => {
      const wasValidated = current && current.status === 'validated';
      const post = store.save(Object.assign(content(), current ? { id: current.id, updatedAt: current.updatedAt } : {}));
      load(post);
      notify(wasValidated ? 'Modifications enregistrées en brouillon. L’actualité a été retirée de la aperçu du bureau jusqu’à votre nouvelle validation.' : 'Brouillon enregistré dans ce navigateur. Vous pouvez maintenant le relire et le valider.');
    });
  });
  $('new-post').addEventListener('click', () => { if (mayLeave()) { load(null); fields.title.focus(); } });
  $('preview-site').addEventListener('click', () => { previewMode = 'site'; preview(); });
  $('preview-facebook').addEventListener('click', () => { previewMode = 'facebook'; preview(); });
  $('preview-whatsapp').addEventListener('click', () => { previewMode = 'whatsapp'; preview(); });
  $('preview-adoc').addEventListener('click', () => { previewMode = 'adoc'; preview(); });
  $('prepare-adoc').addEventListener('click', () => guard(() => {
    if (!current || dirty || storageBlocked || imageBusy) throw new Error('Enregistrez et validez cette version avant de préparer ADOC.');
    const article = adoc.approved(current.id, current.updatedAt);
    previewMode = 'adoc'; preview();
    notify('Simulation : le titre, le contenu' + (article.image ? ' et l’affiche' : '') + ' sont prêts pour une saisie dans ADOC. Visibilité Ten’Up prévue : ' + (article.visibleOnTenup ? 'Oui' : 'Non') + '. Aucun article créé, aucune connexion ni modification de Ten’Up.');
  }));
  function currentWhatsAppText() {
    if (!current || dirty || storageBlocked || imageBusy) throw new Error('Enregistrez et validez cette version avant le partage WhatsApp.');
    return whatsapp.approvedText(current.id, current.updatedAt);
  }
  $('share-whatsapp').addEventListener('click', () => guard(() => {
    const url = whatsapp.shareUrl(currentWhatsAppText());
    if (!url) throw new Error('Pour ce message long, utilisez Copier le message puis collez-le dans WhatsApp.');
    window.open(url, '_blank', 'noopener,noreferrer');
    notify('Ouverture de WhatsApp demandée. Si aucun onglet ne s’ouvre, autorisez cette ouverture ou copiez le message. Choisissez ensuite votre groupe et confirmez Envoyer dans WhatsApp ; la livraison n’est pas suivie ici.');
  }));
  $('copy-whatsapp').addEventListener('click', async () => {
    let text;
    try { text = currentWhatsAppText(); } catch (error) { notify(error.message, true); return; }
    try {
      if (!navigator.clipboard || !navigator.clipboard.writeText) throw new Error('ClipboardUnavailable');
      await navigator.clipboard.writeText(text);
      notify('Message validé copié. Collez-le dans le groupe WhatsApp de votre choix, puis confirmez Envoyer. Aucun message n’a été envoyé par cette copie.');
    } catch (_) {
      // Recheck the revision after an asynchronous clipboard denial.
      try { if (currentWhatsAppText() !== text) throw new Error('La version a changé. Revalidez avant de copier.'); }
      catch (error) { notify(error.message, true); return; }
      $('whatsapp-copy-text').value = text;
      $('whatsapp-copy-fallback').hidden = false;
      $('whatsapp-copy-text').focus();
      $('whatsapp-copy-text').select();
      notify('Le navigateur ne permet pas la copie automatique. Le texte validé est sélectionné ci-dessous pour une copie manuelle.');
    }
  });
  $('review-post').addEventListener('click', () => {
    if (!current || dirty || imageBusy || current.status !== 'draft' || storageBlocked) return;
    guard(() => {
      const fresh = store.read().find(post => post.id === current.id);
      if (!fresh || fresh.updatedAt !== current.updatedAt) throw new Error('L’actualité a changé dans un autre onglet. Sélectionnez-la à nouveau pour la relire.');
      approvalSnapshot = fresh;
      $('confirm-category').textContent = fresh.category;
      $('confirm-title').textContent = fresh.title;
      $('confirm-body').textContent = fresh.body;
      showImage('confirm-image', fresh.image);
      $('confirm-link').textContent = fresh.link;
      $('confirm-link').hidden = !fresh.link;
      const adocArticle = adoc.prepare(fresh);
      $('confirm-adoc').textContent = 'ADOC · Visibilité Ten’Up prévue : ' + (adocArticle.visibleOnTenup ? 'Oui' : 'Non') + ' · ' + adocArticle.count.toLocaleString('fr-FR') + ' / 2 000 caractères.' + (adocArticle.ready ? ' Préparation simulée uniquement.' : ' ' + adocArticle.reason + ' Les autres aperçus restent validables.');
      $('dialog-error').hidden = true;
      $('approval-dialog').showModal();
    });
  });
  $('cancel-approval').addEventListener('click', () => $('approval-dialog').close());
  $('approval-dialog').addEventListener('close', () => { approvalSnapshot = null; });
  $('confirm-approval').addEventListener('click', () => {
    if (!approvalSnapshot) return;
    try {
      const post = store.validate(approvalSnapshot.id, approvalSnapshot.updatedAt);
      $('approval-dialog').close();
      load(post);
      notify('Validation effectuée : l’actualité est visible sur la aperçu du bureau et son relais Facebook est simulé. Aucun message n’a été envoyé.');
    } catch (error) {
      $('dialog-error').textContent = error.message;
      $('dialog-error').hidden = false;
    }
  });
  $('delete-post').addEventListener('click', () => {
    if (!current) return;
    const message = 'Supprimer « ' + current.title + ' » de ce navigateur et de la aperçu du bureau ? Cette suppression est définitive sans export de sauvegarde.' + (dirty ? ' Vos modifications non enregistrées seront également perdues.' : '');
    if (!window.confirm(message)) return;
    guard(() => { store.remove(current.id); load(null); notify('Actualité supprimée du stockage et de la vitrine de ce navigateur.'); });
  });
  $('export-posts').addEventListener('click', () => guard(() => {
    download(JSON.stringify(store.exportData(), null, 2), 'tcl-actualites-' + new Date().toISOString().slice(0, 10) + '.json');
    notify('Export préparé avec les actualités enregistrées.' + (dirty ? ' Vos modifications en cours ne sont pas incluses : enregistrez-les puis exportez à nouveau.' : ' Conservez ce fichier pour retrouver vos contenus.'));
  }));
  $('raw-export').addEventListener('click', () => guard(() => {
    let raw;
    try { raw = localStorage.getItem(store.key); } catch (_) { throw new Error('Le navigateur bloque la lecture du stockage. Aucun export brut ne peut être créé.'); }
    if (raw === null) throw new Error('Aucun stockage enregistré à exporter.');
    download(raw, 'tcl-stockage-brut-' + new Date().toISOString().slice(0, 10) + '.txt', 'text/plain;charset=utf-8');
    notify('Copie brute préparée sans modification du stockage. Conservez-la pour la réparation des données.');
  }));
  $('import-posts').addEventListener('change', async event => {
    const file = event.target.files[0];
    if (!file) return;
    try {
      if (file.size > store.maxImportBytes) throw new Error('Ce fichier dépasse 8 Mo. Aucun import effectué.');
      let data;
      try { data = JSON.parse(await file.text()); } catch (_) { throw new Error('Fichier JSON illisible. Aucun import effectué.'); }
      const result = store.importData(data);
      renderList();
      notify(result.imported + ' actualité(s) ajoutée(s) en brouillon ; ' + result.skipped + ' doublon(s) ignoré(s) ; ' + result.conflicts + ' conflit(s) conservé(s) en copie. Chaque brouillon importé doit être validé avant sa simulation.' + (dirty ? ' Votre saisie en cours est conservée à l’écran.' : ''));
    } catch (error) { notify(error.message, true); }
    finally { event.target.value = ''; }
  });
  window.addEventListener('storage', event => {
    if (event.key !== store.key && event.key !== null) return;
    if (!renderList()) return;
    if (current) {
      const fresh = guard(() => store.read().find(post => post.id === current.id));
      if (dirty || imageBusy) notify('Le stockage a changé dans un autre onglet. Vos saisies restent à l’écran. Si cette actualité a changé, copiez votre texte avant de la recharger.', true);
      else load(fresh || null);
    }
  });
  window.addEventListener('beforeunload', event => {
    if (!dirty && !imageBusy) return;
    event.preventDefault();
    event.returnValue = '';
  });
  window.TCLCommunicationEditor = Object.freeze({ attachImage: attachImage });
  load(null);
})();
