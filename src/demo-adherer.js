/* Parcours de démonstration : aucune API et aucune transmission. */
(() => {
  'use strict';
  const demo = window.TCLDemo;
  const form = document.getElementById('registration-form');
  const errorBox = document.getElementById('form-error');
  const byId = (id) => document.getElementById(id);
  if (!demo || !demo.config || typeof demo.add !== 'function') {
    errorBox.hidden = false;
    errorBox.textContent = 'Les données de démonstration ne sont pas disponibles. Revenez à la présentation du processus.';
    byId('next-step').disabled = true;
    return;
  }
  const slots = demo.config.slots || [];
  const formulas = demo.config.formulas || [];
  const sections = Array.from(document.querySelectorAll('.form-step'));
  const stepItems = Array.from(document.querySelectorAll('.step-list li'));
  const availability = Object.fromEntries(slots.map((slot) => [slot.id, 'unknown']));
  const permissionLabels = { yes: 'Oui', no: 'Non', pending: 'À préciser' };
  const answerLabels = { unknown: 'À renseigner', no: 'Indisponible', yes: 'Disponible', preferred: 'Préféré' };
  let currentStep = 0;
  let submitted = false;
  let hasChanges = false;

  byId('season-label').textContent = demo.config.season || '2026-2027';
  byId('birthDate').max = new Date().toISOString().slice(0, 10);
  formulas.forEach((formula) => {
    const option = document.createElement('option');
    option.value = formula.id;
    option.textContent = formula.label;
    byId('formulaId').appendChild(option);
  });
  slots.forEach((slot, index) => {
    const card = document.createElement('label');
    card.className = 'slot-card';
    card.dataset.answer = 'unknown';
    const title = document.createElement('span');
    title.className = 'slot-title';
    title.append(document.createTextNode(slot.label || `${slot.day} · ${slot.start}–${slot.end}`));
    const small = document.createElement('small');
    small.textContent = 'PLAGE FICTIVE';
    title.appendChild(small);
    const select = document.createElement('select');
    select.id = `availability-${index}`;
    select.dataset.slotId = slot.id;
    select.setAttribute('aria-label', `Disponibilité : ${slot.label || slot.day}`);
    Object.entries(answerLabels).forEach(([value, label]) => {
      const option = document.createElement('option'); option.value = value; option.textContent = label; select.appendChild(option);
    });
    select.addEventListener('change', () => { availability[slot.id] = select.value; card.dataset.answer = select.value; updateAvailability(); });
    card.append(title, select);
    byId('availability-slots').appendChild(card);
  });

  function getKind() { return form.elements.kind.value; }
  function hasTraining() { return form.elements.training.value === 'yes'; }
  function selectedFormula() { return formulas.find((item) => item.id === byId('formulaId').value); }
  function clearError() { errorBox.hidden = true; errorBox.textContent = ''; form.querySelectorAll('[aria-invalid]').forEach((el) => el.removeAttribute('aria-invalid')); }
  function showError(message, target) { errorBox.textContent = message; errorBox.hidden = false; if (target) { target.setAttribute('aria-invalid', 'true'); target.focus(); } }
  function updateKind() {
    const isMinor = getKind() === 'mineur';
    byId('guardian-fields').hidden = !isMinor;
    byId('guardian-fields').disabled = !isMinor;
    byId('adult-contact').hidden = isMinor;
    byId('email').disabled = isMinor;
    byId('phone').disabled = isMinor;
  }
  function updateFormula() {
    const formula = selectedFormula();
    byId('formula-price').textContent = formula ? formula.priceLabel : 'Choisissez une formule';
    byId('formula-uncertainty').hidden = formula?.id !== 'cours-adultes';
    updateTraining();
  }
  function updateTraining() {
    byId('training-grid').hidden = !hasTraining();
    byId('no-training-note').hidden = hasTraining();
    const courseFormulas = ['mini-tennis', 'initiation', 'perfectionnement', 'perfectionnement-plus', 'cours-adultes'];
    byId('training-formula-warning').hidden = hasTraining() || !courseFormulas.includes(byId('formulaId').value);
    updateAvailability();
  }
  function updateAvailability() {
    const values = Object.values(availability);
    const available = values.filter((answer) => answer === 'yes' || answer === 'preferred').length;
    const unknown = values.filter((answer) => answer === 'unknown').length;
    byId('availability-feedback').textContent = unknown
      ? `${available} plage${available > 1 ? 's' : ''} possible${available > 1 ? 's' : ''} · ${unknown} réponse${unknown > 1 ? 's' : ''} à compléter. Vous pouvez continuer : le bureau verra les informations manquantes.`
      : available ? `${available} plage${available > 1 ? 's' : ''} possible${available > 1 ? 's' : ''}. Le club choisira le groupe et confirmera le créneau.`
        : 'Aucune plage compatible dans cet exemple. Vous pouvez déposer la demande : le bureau devra vous proposer une solution.';
  }
  function collect() {
    const minor = getKind() === 'mineur';
    return {
      firstName: byId('firstName').value.trim(), lastName: byId('lastName').value.trim(), birthDate: byId('birthDate').value,
      kind: getKind(), email: (minor ? byId('guardianEmail') : byId('email')).value.trim(), phone: minor ? '' : byId('phone').value.trim(),
      guardianName: minor ? byId('guardianName').value.trim() : '', guardianEmail: minor ? byId('guardianEmail').value.trim() : '',
      experience: byId('experience').value, formulaId: byId('formulaId').value, training: hasTraining(),
      availability: hasTraining() ? { ...availability } : {}, constraints: hasTraining() ? byId('constraints').value.trim() : '',
      permissions: { emergency: byId('permission-emergency').value, photo: byId('permission-photo').value, publish: byId('permission-publish').value }, source: 'web'
    };
  }
  function validateStep(step) {
    clearError();
    const fields = Array.from(sections[step].querySelectorAll('input,select,textarea'));
    const invalid = fields.find((field) => !field.disabled && !field.closest('fieldset[disabled]') && (!field.checkValidity() || (field.required && !field.value.trim())));
    if (invalid) {
      const emailField = invalid.type === 'email';
      showError(emailField ? 'Saisissez une adresse e-mail fictive au format nom@example.invalid.' : invalid.type === 'date' && invalid.validity.rangeOverflow ? 'La date de naissance ne peut pas être dans le futur.' : 'Complétez le champ indiqué pour poursuivre cet exemple.', invalid);
      return false;
    }
    return true;
  }
  function summaryBlock(title, step, lines) {
    const block = document.createElement('section'); block.className = 'summary-section';
    const heading = document.createElement('div'); heading.className = 'summary-heading';
    const h3 = document.createElement('h3'); h3.textContent = title;
    const edit = document.createElement('button'); edit.type = 'button'; edit.className = 'edit-step'; edit.textContent = 'Modifier'; edit.setAttribute('aria-label', `Modifier : ${title}`); edit.addEventListener('click', () => showStep(step));
    heading.append(h3, edit); block.appendChild(heading);
    lines.forEach((line, index) => { const p = document.createElement('p'); p.textContent = line; if (index > 0) p.className = 'summary-muted'; block.appendChild(p); });
    return block;
  }
  function renderSummary() {
    const input = collect(); const formula = selectedFormula();
    const date = input.birthDate ? new Date(`${input.birthDate}T12:00:00`).toLocaleDateString('fr-FR') : 'À préciser';
    const host = byId('registration-summary'); host.replaceChildren();
    host.appendChild(summaryBlock('La personne', 0, [
      `${input.firstName} ${input.lastName} · ${input.kind === 'mineur' ? 'Mineur' : 'Adulte'}`,
      `Naissance : ${date}`,
      input.kind === 'mineur' ? `Responsable : ${input.guardianName} · ${input.guardianEmail}` : input.email,
      ...(input.phone ? [input.phone] : [])
    ]));
    host.appendChild(summaryBlock('La pratique', 1, [formula?.label || 'À préciser', `${formula?.priceLabel || 'Tarif à préciser'} · tarif à confirmer par le club`, byId('experience').selectedOptions[0].textContent]));
    const trainingBlock = summaryBlock('Les entraînements', 2, [input.training ? 'Cours souhaités · horaires d’exemple' : 'Sans entraînement']);
    if (input.training) {
      const list = document.createElement('ul');
      slots.forEach((slot) => { const li = document.createElement('li'); li.textContent = `${slot.label || slot.day} : ${answerLabels[input.availability[slot.id]] || answerLabels.unknown}`; list.appendChild(li); });
      trainingBlock.appendChild(list);
      if (input.constraints) { const p = document.createElement('p'); p.className = 'summary-muted'; p.textContent = `Contrainte : ${input.constraints}`; trainingBlock.appendChild(p); }
      if (typeof demo.trainingState === 'function') { const p = document.createElement('p'); p.className = 'inline-notice'; p.textContent = demo.trainingState(input); trainingBlock.appendChild(p); }
    } else if (!byId('training-formula-warning').hidden) {
      const p = document.createElement('p'); p.className = 'inline-notice'; p.textContent = byId('training-formula-warning').textContent; trainingBlock.appendChild(p);
    }
    host.appendChild(trainingBlock);
    host.appendChild(summaryBlock('Les autorisations illustratives', 3, [`Urgence : ${permissionLabels[input.permissions.emergency]}`, `Prise de photo : ${permissionLabels[input.permissions.photo]}`, `Diffusion de photo : ${permissionLabels[input.permissions.publish]}`, 'Textes et signature à valider avant la version finale.']));
  }
  function showStep(step) {
    currentStep = step; clearError();
    sections.forEach((section, index) => { section.hidden = index !== step; });
    stepItems.forEach((item, index) => { item.classList.toggle('is-current', index === step); item.classList.toggle('is-complete', index < step); if (index === step) item.setAttribute('aria-current', 'step'); else item.removeAttribute('aria-current'); });
    byId('step-count').textContent = `ÉTAPE ${step + 1} SUR 5`;
    byId('previous-step').hidden = step === 0;
    byId('next-step').textContent = step === 4 ? 'Déposer la demande fictive →' : 'Continuer →';
    byId('autosave-note').hidden = step === 4;
    if (step === 4) renderSummary();
    sections[step].querySelector('h2').focus({ preventScroll: true });
    document.querySelector('.form-card').scrollIntoView({ block: 'start', behavior: 'instant' });
  }
  function fillExample(minor) {
    form.elements.kind.value = minor ? 'mineur' : 'adulte';
    byId('firstName').value = minor ? 'Lou' : 'Camille';
    byId('lastName').value = 'Exemple';
    byId('birthDate').value = minor ? '2015-04-12' : '1992-05-14';
    byId('email').value = 'camille@example.invalid'; byId('phone').value = '';
    byId('guardianName').value = 'Alex Exemple'; byId('guardianEmail').value = 'alex@example.invalid';
    byId('experience').value = minor ? 'debutant' : 'occasionnel';
    const preferredFormula = minor ? 'initiation' : 'adulte-loisirs';
    byId('formulaId').value = formulas.some((item) => item.id === preferredFormula) ? preferredFormula : formulas[0]?.id || '';
    form.elements.training.value = minor ? 'yes' : 'no';
    slots.forEach((slot, index) => { availability[slot.id] = index === 0 ? 'preferred' : index === 1 ? 'yes' : 'no'; const select = byId(`availability-${index}`); select.value = availability[slot.id]; select.closest('.slot-card').dataset.answer = availability[slot.id]; });
    byId('constraints').value = '';
    byId('permission-emergency').value = 'pending'; byId('permission-photo').value = 'yes'; byId('permission-publish').value = 'no';
    byId('confirm-fiction').checked = false;
    hasChanges = true;
    updateKind(); updateFormula(); clearError();
    byId('example-feedback').textContent = minor ? 'Exemple Lou : un mineur, un responsable, des cours et plusieurs disponibilités.' : 'Exemple Camille : un adulte en loisirs, sans entraînement.';
  }
  byId('example-adult').addEventListener('click', () => fillExample(false));
  byId('example-child').addEventListener('click', () => fillExample(true));
  form.querySelectorAll('input[name=kind]').forEach((input) => input.addEventListener('change', updateKind));
  form.querySelectorAll('input[name=training]').forEach((input) => input.addEventListener('change', updateTraining));
  byId('formulaId').addEventListener('change', updateFormula);
  byId('previous-step').addEventListener('click', () => showStep(Math.max(0, currentStep - 1)));
  form.addEventListener('input', () => { hasChanges = true; byId('confirm-fiction').checked = false; });
  byId('confirm-fiction').addEventListener('input', (event) => { event.stopPropagation(); });
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (submitted || !validateStep(currentStep)) return;
    if (currentStep < 4) { showStep(currentStep + 1); return; }
    for (let index = 0; index < 4; index++) {
      if (!validateStep(index)) { showStep(index); validateStep(index); return; }
    }
    if (!byId('confirm-fiction').checked) { showError('Confirmez que vous utilisez uniquement des données fictives.', byId('confirm-fiction')); return; }
    byId('next-step').disabled = true;
    try {
      const registration = await Promise.resolve(demo.add(collect()));
      if (!registration || !registration.id) throw new Error('missing-id');
      submitted = true; hasChanges = false;
      byId('success-reference').textContent = registration.id;
      byId('success-bureau-link').href = `./inscriptions.html?dossier=${encodeURIComponent(registration.id)}`;
      if (typeof demo.isPersistent === 'function' && !demo.isPersistent()) {
        byId('success-title').textContent = 'La demande fictive est prête.';
        document.querySelector('.success-next').textContent = 'Le navigateur bloque le stockage local : ce dossier reste seulement en mémoire dans cette page.';
        byId('storage-warning').hidden = false;
        byId('storage-warning').textContent = 'Vous ne retrouverez pas ce dossier après avoir quitté la page. Autorisez le stockage local pour essayer la continuité avec le tableau du bureau.';
        byId('success-bureau-link').removeAttribute('href');
        byId('success-bureau-link').setAttribute('aria-disabled', 'true');
      }
      byId('form-workspace').hidden = true;
      byId('registration-success').hidden = false;
      byId('registration-success').focus();
      byId('registration-success').scrollIntoView({ block: 'start', behavior: 'instant' });
    } catch (error) {
      showError(`Le dossier fictif n’a pas pu être créé. ${error instanceof Error && error.message !== 'missing-id' ? error.message : 'Réessayez depuis la visite guidée.'}`);
    } finally { byId('next-step').disabled = false; }
  });
  window.addEventListener('beforeunload', (event) => { if (hasChanges && !submitted) { event.preventDefault(); event.returnValue = ''; } });
  updateKind(); updateFormula();
})();
