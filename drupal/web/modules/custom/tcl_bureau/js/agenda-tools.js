const panel = document.querySelector('[data-agenda-panel]');
if (panel) {
  const frame = panel.querySelector('iframe');
  const tools = panel.querySelector('[data-agenda-tools]');
  const view = panel.querySelector('[data-agenda-view]');
  const status = panel.querySelector('[data-agenda-status]');
  const url = new URL(frame.src, location.href);
  const validViews = ['MONTH', 'WEEK', 'AGENDA'];
  if (url.origin === 'https://calendar.google.com' && url.pathname === '/calendar/embed') {
    tools.hidden = false;
    // A shared opening view: old browser preferences must not override it.
    view.value = 'MONTH';
    function apply(announce = true) {
      if (!validViews.includes(view.value)) return;
      const next = new URL(frame.src);
      if (next.searchParams.get('mode') !== view.value) {
        next.searchParams.set('mode', view.value);
        frame.src = next.href;
      }
      if (announce) status.textContent = 'Affichage modifié pour cette visite. Le calendrier s’ouvre en vue Mois.';
    }
    view.addEventListener('change', () => apply());
    panel.querySelector('[data-agenda-reset]').addEventListener('click', () => {
      view.value = 'MONTH'; apply();
    });
    apply(false);
  }
}
