(function () {
  'use strict';
  // Sharing opens WhatsApp only on an explicit click; this module never sends anything.
  const MAX_SHARE_URL_LENGTH = 1800;
  function format(post) {
    return ['Tennis Club de Longages', (post.title || '').trim(), (post.body || '').trim(), (post.link || '').trim()].filter(Boolean).join('\n\n');
  }
  function approvedText(id, revision) {
    const post = window.TCLCommunication.read().find(item => item.id === id);
    if (!post || post.status !== 'validated' || post.updatedAt !== revision) {
      throw new Error('Cette version n’est plus validée. Rechargez l’actualité, relisez-la puis validez-la avant de préparer son partage WhatsApp.');
    }
    return format(post);
  }
  function shareUrl(text) {
    let url;
    try { url = 'https://wa.me/?text=' + encodeURIComponent(text); }
    catch (_) { return null; }
    // A conservative convenience limit, not a claimed WhatsApp API limit.
    return url.length <= MAX_SHARE_URL_LENGTH ? url : null;
  }
  window.TCLWhatsApp = Object.freeze({ format: format, approvedText: approvedText, shareUrl: shareUrl });
})();
