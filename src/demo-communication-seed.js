(function () {
  'use strict';
  const store = window.TCLCommunication;
  if (!store) return;
  try {
    if (!store.read().length) {
      store.save({ title: 'Bienvenue dans la démonstration', body: 'Ceci est une actualité fictive pour essayer la rédaction, les trois aperçus et la validation. Aucune publication réelle.', category: 'Vie du club', link: '' });
    }
  } catch (_) { /* The existing editor shows storage errors without overwriting data. */ }
})();
