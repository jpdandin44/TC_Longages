(function () {
  'use strict';
  // Local preparation only, based on the user-supplied ADOC creation screen.
  const MAX_CONTENT = 2000;
  const MAX_PHOTO_BYTES = 5_000_000;
  function prepare(post) {
    const title = (post.title || '').trim();
    const body = [(post.body || '').trim(), (post.link || '').trim()].filter(Boolean).join('\n\n');
    const image = post.image || null;
    let reason = '';
    if (body.length > MAX_CONTENT) reason = 'Le contenu ADOC dépasse 2 000 caractères, lien compris. Raccourcissez le message ou le lien pour préparer ce canal ; aucun texte n’est coupé.';
    else if (image && !/^data:image\/(?:jpeg|png);base64,/.test(image.dataUrl)) reason = 'Pour ADOC, réajoutez votre image au formulaire afin de la préparer en JPEG. L’écran fourni indique JPEG, JPG ou PNG.';
    else if (image && (image.dataUrl.split(',')[1] || '').length * .75 > MAX_PHOTO_BYTES) reason = 'L’image dépasse les 5 Mo indiqués dans l’écran ADOC fourni. Choisissez une image plus légère.';
    else if (!title || (!body && !image)) reason = 'Ajoutez un titre et un message ou une affiche avant de préparer l’article.';
    return { title: title, body: body, image: image, visibleOnTenup: Boolean(post.adocVisibleOnTenup), count: body.length, ready: !reason, reason: reason };
  }
  function approved(id, revision) {
    const post = window.TCLCommunication.read().find(item => item.id === id);
    if (!post || post.status !== 'validated' || post.updatedAt !== revision) throw new Error('Cette version n’est plus validée. Rechargez l’actualité, relisez-la puis validez-la avant de préparer ADOC.');
    const result = prepare(post);
    if (!result.ready) throw new Error(result.reason);
    return result;
  }
  window.TCLADOC = Object.freeze({ prepare: prepare, approved: approved, maxContent: MAX_CONTENT, maxPhotoBytes: MAX_PHOTO_BYTES });
})();
