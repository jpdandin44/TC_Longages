(function () {
  'use strict';
  const api = window.TCLDemo;
  if (!api) return;
  const config = api.config;
  const $ = id => document.getElementById(id);
  const states = { recu: 'Reçu', a_completer: 'À compléter', finalise: 'Finalisé', annule: 'Annulé' };
  const availabilityLabels = { preferred: 'Préféré', yes: 'Disponible', no: 'Indisponible', unknown: 'Non renseigné' };
  let selectedId = new URLSearchParams(window.location.search).get('dossier');
  let importBatch = null;
  let pendingAction = null;

  function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined) node.textContent = String(text);
    return node;
  }
  const name = record => [record.firstName, record.lastName].filter(Boolean).join(' ') || 'Dossier sans nom';
  const activeRecords = () => api.all().filter(record => record.status !== 'annule');
  const groupById = id => config.groups.find(group => group.id === id);
  const slotById = id => config.slots.find(slot => slot.id === id);
  const availability = (record, id) => availabilityLabels[record.availability?.[id]] ? record.availability[id] : 'unknown';
  function shortDate(value) {
    const match = /^([0-9]{4})-([0-9]{2})-([0-9]{2})$/.exec(value || '');
    return match ? `${match[3]}/${match[2]}/${match[1]}` : 'Non renseignée';
  }
  function badge(text, className = '') { return el('span', `pill ${className}`, text); }
  function action(text, handler, className = 'secondary') {
    const button = el('button', `action ${className}`, text);
    button.type = 'button';
    button.addEventListener('click', handler);
    return button;
  }
  function notify(text, error = false) {
    $('feedback').textContent = text;
    $('feedback').className = `feedback${error ? ' error' : ''}`;
    $('feedback').hidden = false;
  }
  function confirmAction(title, text, handler) {
    $('dialog-title').textContent = title;
    $('dialog-text').textContent = text;
    pendingAction = handler;
    $('action-dialog').returnValue = '';
    $('action-dialog').showModal();
  }
  $('action-dialog').addEventListener('close', () => {
    const handler = pendingAction;
    pendingAction = null;
    if ($('action-dialog').returnValue === 'confirm' && handler) {
      try { handler(); } catch { notify('La modification n’a pas pu être enregistrée. Vérifiez que le stockage du navigateur est disponible.', true); }
      if (!$('feedback').hidden) { $('feedback').tabIndex = -1; $('feedback').focus({ preventScroll: true }); }
    }
  });
  function switchView(view, focus = false) {
    document.querySelectorAll('[data-view]').forEach(button => {
      const selected = button.dataset.view === view;
      button.setAttribute('aria-selected', String(selected));
      button.tabIndex = selected ? 0 : -1;
      $(`view-${button.dataset.view}`).hidden = !selected;
      if (selected && focus) button.focus();
    });
  }
  const tabs = [...document.querySelectorAll('[data-view]')];
  tabs.forEach((button, index) => {
    button.addEventListener('click', () => switchView(button.dataset.view));
    button.addEventListener('keydown', event => {
      let target;
      if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
      if (event.key === 'ArrowLeft') target = (index + tabs.length - 1) % tabs.length;
      if (event.key === 'Home') target = 0;
      if (event.key === 'End') target = tabs.length - 1;
      if (target === undefined) return;
      event.preventDefault();
      switchView(tabs[target].dataset.view, true);
    });
  });
  function filteredRecords() {
    const search = $('filter-search').value.trim().toLocaleLowerCase('fr');
    return api.all().filter(record => {
      if (search && !`${name(record)} ${record.id}`.toLocaleLowerCase('fr').includes(search)) return false;
      if ($('filter-kind').value && record.kind !== $('filter-kind').value) return false;
      if ($('filter-status').value && record.status !== $('filter-status').value) return false;
      if ($('filter-training').value && Boolean(record.training) !== ($('filter-training').value === 'yes')) return false;
      return true;
    });
  }
  function openDossier(id) {
    selectedId = id;
    ['filter-search', 'filter-kind', 'filter-status', 'filter-training'].forEach(field => { $(field).value = ''; });
    switchView('dossiers');
    render();
    $('dossier-detail').scrollIntoView({ block: 'nearest', behavior: 'auto' });
  }
  function metrics() {
    const records = api.all();
    const values = [
      [records.length, 'dossiers fictifs'],
      [records.filter(r => r.status === 'recu' || r.status === 'a_completer').length, 'à examiner'],
      [records.filter(r => r.status !== 'annule' && r.training && !r.groupId).length, 'en attente de groupe'],
      [records.filter(r => r.status === 'finalise').length, 'finalisés dans la démo']
    ];
    $('metrics').replaceChildren(...values.map(([value, label]) => {
      const node = el('div', 'metric'); node.append(el('strong', '', value), el('span', '', label)); return node;
    }));
    $('dossier-count').textContent = records.length;
  }
  function renderList() {
    const records = filteredRecords();
    $('list-caption').textContent = `${records.length} dossier${records.length === 1 ? '' : 's'} affiché${records.length === 1 ? '' : 's'} · Sélectionnez une demande`;
    $('export-summary').textContent = `${records.length} dossier${records.length === 1 ? '' : 's'} fictif${records.length === 1 ? '' : 's'} dans l’export actuel.`;
    $('export-csv').disabled = !records.length;
    if (!records.some(record => record.id === selectedId)) selectedId = records[0]?.id || null;
    $('dossier-list').replaceChildren(...records.map(record => {
      const button = el('button', 'record-button');
      button.type = 'button'; button.setAttribute('aria-pressed', String(record.id === selectedId));
      const content = el('span', 'record-content');
      content.append(el('span', 'record-name', name(record)), el('span', 'record-meta', `${record.id} · ${record.kind === 'mineur' ? 'Mineur' : 'Adulte'}`));
      const right = el('span', 'record-right');
      right.append(badge(states[record.status] || record.status, `status-${record.status}`), el('small', '', record.training ? 'Avec entraînement' : 'Sans entraînement'));
      const initials = `${record.firstName?.slice(0, 1) || ''}${record.lastName?.slice(0, 1) || ''}`;
      button.append(el('span', 'record-icon', initials), content, right);
      button.addEventListener('click', () => { selectedId = record.id; renderList(); renderDetail(); });
      return button;
    }));
    if (!records.length) $('dossier-list').append(el('p', 'empty-state', 'Aucun dossier ne correspond à ces filtres.'));
  }
  function groupError(record, groupId, requireGroup = false) {
    if (!groupId) return requireGroup && record.training ? 'Choisissez un groupe compatible avant de finaliser ce dossier avec entraînement.' : '';
    if (!record.training) return 'Ce dossier ne prévoit pas d’entraînement : aucun groupe ne peut lui être affecté.';
    const group = groupById(groupId);
    if (!group) return 'Ce groupe n’existe plus dans la démonstration.';
    const slot = slotById(group.slotId);
    if (!slot) return 'Le créneau de ce groupe est inconnu.';
    if (!['yes', 'preferred'].includes(availability(record, group.slotId))) return `Le créneau « ${slot.label} » n’est pas déclaré disponible. Choisissez un autre groupe.`;
    const occupied = activeRecords().filter(r => r.id !== record.id && r.groupId === groupId).length;
    if (occupied >= group.capacity) return 'Ce groupe a atteint sa capacité fictive. Choisissez un autre groupe.';
    return '';
  }
  function liveRecord(id) { return api.all().find(record => record.id === id); }
  function mutateStatus(record, status) {
    const isFinal = status === 'finalise';
    const error = isFinal ? groupError(record, record.groupId, true) : '';
    if (error) { notify(error, true); return; }
    confirmAction(isFinal ? 'Finaliser ce dossier fictif ?' : 'Signaler un dossier à compléter ?', isFinal
      ? `${name(record)} passera à l’état « Finalisé » dans cette démonstration. Aucun paiement, licence ou inscription réelle ne sera validé et aucun message ne sera envoyé.`
      : `${name(record)} passera à l’état « À compléter ». Le suivi reste local ; aucune relance ne sera envoyée.`, () => {
      const current = liveRecord(record.id);
      if (!current || current.revision !== record.revision) { notify('Ce dossier a changé. Vérifiez sa version actuelle avant de recommencer.', true); render(); return; }
      const currentError = isFinal ? groupError(current, current.groupId, true) : '';
      if (currentError) { notify(currentError, true); render(); return; }
      api.update(record.id, { status });
      render(); notify(`Dossier fictif ${record.id} : ${states[status].toLocaleLowerCase('fr')}. Aucun envoi effectué.`);
    });
  }
  function renderDetail() {
    const host = $('dossier-detail'); host.replaceChildren();
    const record = liveRecord(selectedId);
    if (!record) { host.append(el('p', 'empty-state', 'Sélectionnez un dossier pour consulter son parcours.')); return; }
    const top = el('div', 'detail-top'); const title = el('div');
    title.append(el('p', 'eyebrow', `DOSSIER FICTIF · ${record.id}`), el('h3', '', name(record)));
    top.append(title, badge(states[record.status] || record.status, `status-${record.status}`)); host.append(top);
    const data = el('dl', 'detail-data');
    const details = [
      ['Date de naissance', shortDate(record.birthDate)], ['Public', record.kind === 'mineur' ? 'Mineur' : 'Adulte'],
      ['Courriel', record.email || record.guardianEmail || 'Non renseigné'], ['Téléphone', record.phone || 'Non renseigné'],
      ['Formule souhaitée', api.formula(record.formulaId)?.label || 'À définir'], ['Pratique', record.experience || 'Non renseignée'],
      ['Origine', ({ web: 'Formulaire de démonstration', papier: 'Fiche papier (exemple)', autre: 'Import fictif' })[record.source] || 'Démonstration'],
      ['Entraînement', record.training ? 'Souhaité' : 'Sans entraînement']
    ];
    if (record.kind === 'mineur') details.push(['Responsable légal', record.guardianName || 'Non renseigné'], ['Courriel du responsable', record.guardianEmail || 'Non renseigné']);
    for (const [label, value] of details) { const cell = el('div'); cell.append(el('dt', '', label), el('dd', '', value)); data.append(cell); }
    host.append(data);
    host.append(el('h4', 'detail-subtitle', 'Disponibilités déclarées'));
    if (record.training) {
      const slots = el('ul', 'slot-list');
      config.slots.forEach(slot => { const state = availability(record, slot.id); const li = el('li'); li.append(el('span', '', slot.label), el('span', `availability ${state}`, availabilityLabels[state])); slots.append(li); });
      host.append(slots);
    } else host.append(el('p', 'detail-note', 'Aucun entraînement demandé. L’affectation à un groupe n’est pas nécessaire.'));
    if (record.constraints) host.append(el('p', 'detail-note', `Contraintes : ${record.constraints}`));
    host.append(el('h4', 'detail-subtitle', 'Réponses aux autorisations'));
    const permissionText = [ ['Prise en charge en urgence', 'emergency'], ['Photos', 'photo'], ['Diffusion', 'publish'] ].map(([label, key]) => `${label} : ${[true, 'yes'].includes(record.permissions?.[key]) ? 'oui' : [false, 'no'].includes(record.permissions?.[key]) ? 'non' : 'non renseigné'}`).join(' · ');
    host.append(el('p', 'detail-note', `${permissionText}. Libellés de démonstration à valider.`));
    if (record.training || record.groupId) {
      const groupField = el('div', 'group-field');
      const label = el('label', '', 'Affectation manuelle à un groupe'); const select = el('select'); select.id = 'dossier-group';
      const empty = el('option', '', 'Sans groupe pour le moment'); empty.value = ''; select.append(empty);
      config.groups.forEach(group => { const option = el('option', '', `${group.label} · ${slotById(group.slotId)?.label || 'Créneau à définir'}`); option.value = group.id; select.append(option); });
      select.value = record.groupId || ''; select.disabled = record.status === 'annule'; label.append(select); groupField.append(label);
      const apply = action('Confirmer le choix du groupe', () => {
        const groupId = select.value || null;
        const error = groupError(record, groupId);
        if (error) { notify(error, true); return; }
        if (groupId === (record.groupId || null)) { notify('Le groupe sélectionné est déjà enregistré.'); return; }
        confirmAction('Modifier le groupe fictif ?', `${name(record)} ${groupId ? `sera affecté à « ${groupById(groupId).label} »` : 'restera sans groupe'}. Une finalisation précédente sera annulée pour permettre une nouvelle vérification. Aucun message ne sera envoyé.`, () => {
          const current = liveRecord(record.id);
          if (!current || current.revision !== record.revision) { notify('Le dossier a changé. Vérifiez-le avant une nouvelle affectation.', true); render(); return; }
          const currentError = groupError(current, groupId);
          if (currentError) { notify(currentError, true); render(); return; }
          api.update(record.id, { groupId, status: current.status === 'finalise' ? 'recu' : current.status });
          render(); notify(`Groupe mis à jour pour ${name(record)} dans la démonstration.`);
        });
      });
      apply.disabled = record.status === 'annule'; groupField.append(apply); host.append(groupField);
    }
    const error = groupError(record, record.groupId, true);
    if (error && record.status !== 'annule') host.append(el('p', 'warning', error));
    const actions = el('div', 'actions detail-actions');
    const complete = action('Marquer « À compléter »', () => mutateStatus(record, 'a_completer'));
    complete.disabled = record.status === 'a_completer' || record.status === 'annule';
    const finalise = action('Finaliser le dossier fictif', () => mutateStatus(record, 'finalise'), 'primary');
    finalise.disabled = record.status === 'finalise' || record.status === 'annule' || Boolean(error);
    actions.append(complete, finalise); host.append(actions);
    host.append(el('p', 'detail-note', 'Les critères de finalisation, les justificatifs et le suivi des règlements seront définis avec le bureau.'));
  }
  function renderAvailability() {
    const records = activeRecords().filter(record => record.training);
    const host = $('availability-table'); host.replaceChildren();
    if (!records.length) { host.append(el('p', 'empty-state', 'Aucune demande avec entraînement à afficher.')); return; }
    const table = el('table'); const caption = el('caption', 'list-caption', 'Disponibilités des dossiers fictifs avec entraînement');
    const head = el('thead'); const titles = el('tr');
    const first = el('th', '', 'Adhérent fictif'); first.scope = 'col'; titles.append(first);
    config.slots.forEach(slot => { const th = el('th', '', slot.label); th.scope = 'col'; titles.append(th); }); head.append(titles);
    const body = el('tbody');
    records.forEach(record => {
      const row = el('tr'); const person = el('td'); person.append(action(name(record), () => openDossier(record.id), 'text')); row.append(person);
      config.slots.forEach(slot => { const value = availability(record, slot.id); const cell = el('td'); cell.append(el('span', `availability ${value}`, availabilityLabels[value])); row.append(cell); }); body.append(row);
    }); table.append(caption, head, body); host.append(table);
  }
  function renderGroups() {
    const records = activeRecords();
    $('group-list').replaceChildren(...config.groups.map(group => {
      const members = records.filter(record => record.groupId === group.id);
      const card = el('article', 'group-card'); const top = el('div', 'group-card-top');
      top.append(el('h3', '', group.label), badge(`${members.length} / ${group.capacity} places`));
      card.append(top, el('p', '', slotById(group.slotId)?.label || 'Créneau à définir'));
      const track = el('div', 'capacity-track'); const fill = el('div', 'capacity-fill');
      fill.style.width = `${Math.min(100, group.capacity > 0 ? members.length / group.capacity * 100 : 100)}%`; track.append(fill); card.append(track);
      if (!members.length) card.append(el('p', 'group-empty', 'Ce groupe attend ses premiers dossiers fictifs.'));
      else { const list = el('ul', 'group-members'); members.forEach(record => { const li = el('li'); li.append(action(name(record), () => openDossier(record.id), 'text'), badge(states[record.status], `status-${record.status}`)); list.append(li); }); card.append(list); }
      return card;
    }));
  }
  function importRows() {
    const firstSlot = config.slots[0]; const formula = config.formulas.find(item => item.id === 'adulte-loisirs') || config.formulas.find(item => /adulte/i.test(item.label)) || config.formulas[0];
    const valid = { firstName: 'Zoé', lastName: 'Exemple-Import', birthDate: '1993-05-18', kind: 'adulte', email: 'zoe.import@example.invalid', phone: '00 00 00 00 00', guardianName: '', guardianEmail: '', experience: 'Loisir · exemple', formulaId: formula?.id || '', training: false, availability: Object.fromEntries(config.slots.map(slot => [slot.id, 'unknown'])), constraints: 'Dossier issu du lot fictif de démonstration.', permissions: { emergency: 'yes', photo: 'no', publish: 'no' }, source: 'autre', status: 'recu', groupId: null };
    const existing = api.all()[0];
    const duplicate = existing ? { ...existing } : { ...valid };
    const invalid = { ...valid, firstName: '', lastName: 'Ligne incomplète', email: 'adresse-invalide', training: true, availability: { [firstSlot?.id || 'inconnu']: 'unknown' } };
    return [valid, duplicate, invalid];
  }
  function rowResult(record) {
    if (!record.firstName?.trim() || !record.lastName?.trim() || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(record.email || '')) return { status: 'invalid', label: 'À corriger', reason: 'Prénom manquant ou courriel invalide. Ligne exclue.' };
    const duplicate = api.all().some(existing => (record.id && record.id === existing.id) || (record.email && record.email.toLowerCase() === existing.email?.toLowerCase()) || (record.firstName.toLowerCase() === existing.firstName?.toLowerCase() && record.lastName.toLowerCase() === existing.lastName?.toLowerCase() && record.birthDate === existing.birthDate));
    if (duplicate) return { status: 'duplicate', label: 'Doublon', reason: 'Dossier déjà présent. Aucun écrasement autorisé.' };
    return { status: 'valid', label: 'Prêt à ajouter', reason: 'Contrôles de démonstration réussis. Ajout après confirmation.' };
  }
  function renderImport() {
    if (!importBatch) return;
    $('import-preview').hidden = false;
    let count = 0;
    $('import-rows').replaceChildren(...importBatch.map((record, index) => {
      const result = rowResult(record); if (result.status === 'valid') count++;
      const row = el('div', 'import-row'); const left = el('div');
      left.append(el('strong', '', `Ligne ${index + 1} · ${name(record)}`), el('small', '', record.email || 'Courriel absent'));
      const right = el('div'); right.append(badge(result.label, result.status === 'valid' ? 'status-finalise' : 'status-a_completer'), el('small', '', result.reason)); row.append(left, right); return row;
    }));
    $('import-summary').textContent = `${count} ajout possible · ${importBatch.length - count} ligne${importBatch.length - count > 1 ? 's' : ''} exclue${importBatch.length - count > 1 ? 's' : ''}`;
    $('confirm-import').disabled = count === 0;
  }
  $('simulate-import').addEventListener('click', () => { importBatch = importRows(); renderImport(); $('import-preview').scrollIntoView({ block: 'nearest' }); });
  $('confirm-import').addEventListener('click', () => {
    if (!importBatch) return;
    const validCount = importBatch.filter(record => rowResult(record).status === 'valid').length;
    if (!validCount) return;
    confirmAction('Ajouter les lignes fictives valides ?', `${validCount} dossier fictif sera ajouté à cette démonstration. Les doublons et les lignes invalides resteront exclus. Aucun fichier ni dossier réel n’est concerné.`, () => {
      let added = 0;
      for (const record of importBatch) {
        if (rowResult(record).status !== 'valid') continue;
        const created = api.add(record); selectedId = created.id; added++;
      }
      render(); renderImport(); notify(`${added} dossier fictif ajouté. Les dossiers existants n’ont pas été modifiés.`);
    });
  });
  function csvCell(value) {
    let text = String(value ?? '');
    if (/^[\s]*[=+@\-]/.test(text) || /^[\t\r\n]/.test(text)) text = `'${text}`;
    return `"${text.replaceAll('"', '""')}"`;
  }
  $('export-csv').addEventListener('click', () => {
    const records = filteredRecords();
    if (!records.length) return;
    const header = ['Référence fictive', 'Prénom', 'Nom', 'Public', 'Date de naissance', 'Courriel fictif', 'Formule', 'Entraînement', 'État', 'Groupe fictif', 'Origine', ...config.slots.map(slot => `Disponibilité : ${slot.label}`)];
    const lines = [header, ...records.map(record => [record.id, record.firstName, record.lastName, record.kind === 'mineur' ? 'Mineur' : 'Adulte', record.birthDate, record.email || record.guardianEmail || '', api.formula(record.formulaId)?.label || '', record.training ? 'Oui' : 'Non', states[record.status], groupById(record.groupId)?.label || '', record.source, ...config.slots.map(slot => record.training ? availabilityLabels[availability(record, slot.id)] : 'Sans entraînement')])];
    const blob = new Blob(['\uFEFF' + lines.map(row => row.map(csvCell).join(';')).join('\r\n') + '\r\n'], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob); const link = el('a'); link.href = url; link.download = 'tcl-inscriptions-donnees-fictives.csv'; document.body.append(link); link.click(); link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 30000);
    notify(`CSV préparé : ${records.length} dossier${records.length > 1 ? 's' : ''} fictif${records.length > 1 ? 's' : ''}, selon les filtres courants.`);
  });
  $('export-xls').addEventListener('click', () => notify('L’export au format XLS sera développé après validation des colonnes. Pour cette démonstration, le CSV fictif est disponible.'));
  ['filter-search', 'filter-kind', 'filter-status', 'filter-training'].forEach(id => $(id).addEventListener(id === 'filter-search' ? 'input' : 'change', () => { renderList(); renderDetail(); }));
  function render() { metrics(); renderList(); renderDetail(); renderAvailability(); renderGroups(); if (importBatch) renderImport(); }
  window.addEventListener('storage', render);
  $('season-label').textContent = config.season ? `· ${config.season}` : '';
  render();
})();
