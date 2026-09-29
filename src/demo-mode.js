(function () {
  'use strict';
  // This script is included only in the separate, shareable demonstration.
  window.TCL_DEMONSTRATION = true;
  document.addEventListener('click', function (event) {
    const target = event.target.closest('#share-whatsapp, #copy-whatsapp');
    if (!target) return;
    event.preventDefault(); event.stopImmediatePropagation();
    const feedback = document.getElementById('feedback');
    if (feedback) {
      feedback.hidden = false;
      feedback.className = 'notice';
      feedback.textContent = target.id === 'share-whatsapp'
        ? 'Simulation : le message est prêt. Dans la version réelle, vous choisirez votre groupe puis confirmerez dans WhatsApp. Aucun service externe ouvert.'
        : 'Simulation de copie : aucun texte placé dans le presse-papiers. Aucun message envoyé.';
    }
  }, true);
  document.addEventListener('change', function (event) {
    if (event.target.id !== 'import-posts') return;
    event.preventDefault(); event.stopImmediatePropagation(); event.target.value = '';
  }, true);
})();
