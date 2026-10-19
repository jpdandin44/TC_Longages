const panel = document.querySelector('[data-agenda-panel]');
if (panel) {
  const frame = panel.querySelector('iframe');
  const tools = panel.querySelector('[data-agenda-tools]');
  const view = panel.querySelector('[data-agenda-view]');
  const theme = panel.querySelector('[data-agenda-theme]');
  const status = panel.querySelector('[data-agenda-status]');
  const url = new URL(frame.src, location.href);
  const key = 'tcl-agenda-presentation';
  const validViews = ['MONTH', 'WEEK', 'AGENDA'];
  const validThemes = ['club', 'nature', 'clair'];
  if (url.origin === 'https://calendar.google.com' && url.pathname === '/calendar/embed') {
    tools.hidden = false;
    let saved = {};
    try { saved = JSON.parse(localStorage.getItem(key) || '{}') || {}; } catch { /* Preferences are optional. */ }
    view.value = validViews.includes(saved.view) ? saved.view : 'MONTH';
    theme.value = validThemes.includes(saved.theme) ? saved.theme : 'club';
    function apply(persist = true) {
      if (!validViews.includes(view.value) || !validThemes.includes(theme.value)) return;
      panel.dataset.theme = theme.value;
      const next = new URL(frame.src);
      if (next.searchParams.get('mode') !== view.value) {
        next.searchParams.set('mode', view.value);
        frame.src = next.href;
      }
      if (persist) {
        try {
          localStorage.setItem(key, JSON.stringify({ view: view.value, theme: theme.value }));
          status.textContent = 'Préférences enregistrées dans ce navigateur.';
        } catch { status.textContent = 'Affichage modifié pour cette visite uniquement.'; }
      }
    }
    view.addEventListener('change', () => apply());
    theme.addEventListener('change', () => apply());
    panel.querySelector('[data-agenda-reset]').addEventListener('click', () => {
      view.value = 'MONTH'; theme.value = 'club'; apply();
    });
    apply(false);
  }
}
