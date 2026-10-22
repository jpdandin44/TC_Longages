(() => {
  const token = location.hash.slice(1);
  history.replaceState(null, '', location.pathname);
  const form = document.getElementById('reply-form'), status = document.getElementById('reply-status');
  let csrf = '', revision = 0, eventRevision = 0, opened = false;
  function countState() {
    const yes = form.elements.answer.value === 'yes';
    document.getElementById('reply-count').hidden = !yes;
    form.elements.count.disabled = !yes || !opened;
    form.elements.count.required = yes;
  }
  function show(c) {
    revision = c.revision; eventRevision = c.eventRevision; opened = c.open;
    document.getElementById('reply-title').textContent = c.title;
    document.getElementById('reply-details').textContent = `${c.date.split('-').reverse().join('/')} · ${c.time} · ${c.location} — Réponses jusqu’au ${c.deadline || 'non défini'}`;
    document.getElementById('reply-name').textContent = c.name;
    for (const radio of form.elements.answer) { radio.checked = radio.value === c.answer; radio.disabled = !opened; }
    form.elements.count.value = c.count || 1;
    form.querySelector('button').disabled = !opened;
    countState(); document.getElementById('reply-context').hidden = false;
    if (!opened) status.textContent = 'Les réponses sont closes.';
    else if (c.needsConfirmation) status.textContent = 'L’événement a changé. Vérifiez les informations et confirmez à nouveau votre réponse.';
  }
  async function request(action, values = {}) {
    const r = await fetch(`/evenements/reponse/${action}`, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json'},body:JSON.stringify({token, csrf, revision, eventRevision, ...values})});
    const b = await r.json(); if (!r.ok) throw new Error(b.error || 'Service indisponible.');
    csrf = b.csrf; show(b.context); return b.context;
  }
  form.addEventListener('change', countState);
  form.addEventListener('submit', async e => {
    e.preventDefault(); const button = form.querySelector('button'); button.disabled = true;
    try { await request('save', {answer:form.elements.answer.value,count:form.elements.answer.value === 'yes' ? Number(form.elements.count.value) : 0}); status.textContent = 'Votre réponse est enregistrée. Vous pourrez la modifier avec ce même lien avant la clôture.'; }
    catch (error) { status.textContent = error.message; }
    finally { button.disabled = !opened; }
  });
  async function refresh() { status.textContent = ''; try { await request('context'); } catch(error) { status.textContent = error.message; } }
  document.getElementById('refresh').addEventListener('click', refresh);
  if (/^[a-f0-9]{64}$/.test(token)) refresh(); else status.textContent = 'Lien personnel incomplet. Demandez votre lien au Bureau.';
})();
