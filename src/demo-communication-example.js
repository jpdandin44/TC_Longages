(function () {
  'use strict';
  const button = document.getElementById('use-example-poster');
  const source = document.getElementById('demo-poster-data');
  if (!button || !source || !window.TCLCommunicationEditor) return;
  button.addEventListener('click', async () => {
    if (button.disabled) return;
    button.disabled = true;
    try {
      const encoded = source.content.textContent.trim().split(',')[1];
      const bytes = Uint8Array.from(atob(encoded), character => character.charCodeAt(0));
      const file = new File([bytes], 'Affiche.jpeg', { type: 'image/jpeg' });
      const applied = await window.TCLCommunicationEditor.attachImage(file, {
        alt: 'Affiche de rentrée du Tennis Club de Longages : séances d’essai et coordonnées de contact.'
      });
      if (applied) {
        const title = document.getElementById('post-title');
        if (!title.value.trim()) {
          title.value = 'Rentrée du tennis — affiche exemple';
          title.dispatchEvent(new Event('input', { bubbles: true }));
        }
      }
    } catch (_) {
      const feedback = document.getElementById('feedback');
      feedback.textContent = 'L’affiche exemple n’a pas pu être préparée. Vous pouvez choisir votre image avec le bouton d’ajout.';
      feedback.className = 'notice error';
      feedback.hidden = false;
    } finally { button.disabled = false; }
  });
})();
