for (const panel of document.querySelectorAll('[data-sharing-tools]')) {
  const button = panel.querySelector('[data-copy-message]');
  const field = panel.querySelector('textarea');
  const status = panel.querySelector('[data-copy-status]');
  if (!button || !field || !status) continue;
  button.hidden = false;
  button.addEventListener('click', async () => {
    button.disabled = true;
    try {
      await navigator.clipboard.writeText(field.value);
      status.textContent = 'Message copié. Aucune publication effectuée.';
    } catch {
      field.focus();
      field.select();
      status.textContent = 'Copie automatique indisponible. Le texte est sélectionné : utilisez Copier dans votre navigateur.';
    } finally {
      button.disabled = false;
    }
  });
}
