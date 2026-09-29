(function () {
  'use strict';
  const KEY = 'tcl.demo.inscriptions.v1';
  const config = {
    season: /* TCL_SEASON */ '',
    slots: [
      { id: 'ex-mer-14', label: 'Mercredi · 14 h – 15 h (exemple)', day: 'Mercredi', start: '14:00', end: '15:00' },
      { id: 'ex-mer-15', label: 'Mercredi · 15 h – 16 h (exemple)', day: 'Mercredi', start: '15:00', end: '16:00' },
      { id: 'ex-sam-10', label: 'Samedi · 10 h – 11 h (exemple)', day: 'Samedi', start: '10:00', end: '11:00' }
    ],
    formulas: /* TCL_TARIFFS */ [],
    groups: [
      { id: 'g-ex-jeunes', label: 'Groupe jeunes · exemple', slotId: 'ex-mer-14', capacity: 4 },
      { id: 'g-ex-adultes', label: 'Groupe adultes · exemple', slotId: 'ex-sam-10', capacity: 4 }
    ]
  };
  const clone = value => JSON.parse(JSON.stringify(value));
  const base = { phone: '', guardianName: '', guardianEmail: '', constraints: '', permissions: { emergency: 'pending', photo: 'pending', publish: 'pending' }, status: 'recu', groupId: null, revision: 1, createdAt: '2026-09-16T10:00:00.000Z', updatedAt: '2026-09-16T10:00:00.000Z' };
  const seed = [
    { id: 'DEMO-001', firstName: 'Camille', lastName: 'Exemple', birthDate: '1990-05-12', kind: 'adulte', email: 'camille@example.invalid', experience: 'Débutant', formulaId: 'adulte-loisirs', training: false, availability: {}, source: 'web' },
    { id: 'DEMO-002', firstName: 'Noé', lastName: 'Démonstration', birthDate: '2015-04-20', kind: 'mineur', email: 'parent@example.invalid', guardianName: 'Alex Exemple', guardianEmail: 'parent@example.invalid', experience: '2 ans', formulaId: 'initiation', training: true, availability: { 'ex-mer-14': 'preferred', 'ex-mer-15': 'yes', 'ex-sam-10': 'no' }, source: 'web' },
    { id: 'DEMO-003', firstName: 'Lou', lastName: 'Fictif', birthDate: '2013-10-09', kind: 'mineur', email: 'famille@example.invalid', guardianName: 'Sam Exemple', guardianEmail: 'famille@example.invalid', experience: '3 ans', formulaId: 'perfectionnement', training: true, availability: { 'ex-mer-14': 'yes', 'ex-mer-15': 'preferred', 'ex-sam-10': 'no' }, source: 'papier', status: 'a_completer', constraints: 'Exemple : autorisation papier à vérifier.' },
    { id: 'DEMO-004', firstName: 'Sacha', lastName: 'Exemple', birthDate: '1984-02-18', kind: 'adulte', email: 'sacha@example.invalid', experience: '5 ans', formulaId: 'cours-adultes', training: true, availability: { 'ex-mer-14': 'no', 'ex-mer-15': 'no', 'ex-sam-10': 'preferred' }, source: 'web' },
    { id: 'DEMO-005', firstName: 'Charlie', lastName: 'Fictif', birthDate: '2000-08-24', kind: 'adulte', email: 'charlie@example.invalid', experience: '1 an', formulaId: 'cours-adultes', training: true, availability: {}, source: 'papier', status: 'a_completer' }
  ].map(item => ({ ...clone(base), ...item }));
  let memory = clone(seed);
  let persistent = true;
  function write(records) {
    const serialized = JSON.stringify(records);
    if (serialized.length > 1000000 || records.length > 200) throw new Error('Limite de démonstration atteinte. Réinitialisez les exemples depuis la visite guidée.');
    memory = clone(records);
    try { localStorage.setItem(KEY, serialized); } catch (_) { persistent = false; }
  }
  function all() {
    try {
      const raw = localStorage.getItem(KEY);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (raw.length <= 1000000 && Array.isArray(parsed) && parsed.length <= 200 && parsed.every(x => x && typeof x.id === 'string' && typeof x.firstName === 'string' && typeof x.lastName === 'string' && x.availability && typeof x.availability === 'object')) memory = parsed;
      }
    } catch (_) { persistent = false; }
    return clone(memory);
  }
  function normalize(input) {
    const out = {};
    for (const key of ['firstName','lastName','birthDate','email','phone','guardianName','guardianEmail','experience','formulaId','constraints']) out[key] = String(input[key] || '').trim().slice(0, key === 'constraints' ? 1000 : 150);
    out.kind = input.kind === 'mineur' ? 'mineur' : 'adulte';
    out.training = input.training === true;
    out.availability = {};
    for (const slot of config.slots) out.availability[slot.id] = ['no','yes','preferred'].includes(input.availability?.[slot.id]) ? input.availability[slot.id] : 'unknown';
    out.permissions = {};
    for (const key of ['emergency','photo','publish']) out.permissions[key] = ['yes','no'].includes(input.permissions?.[key]) ? input.permissions[key] : 'pending';
    out.source = ['web','papier','autre'].includes(input.source) ? input.source : 'autre';
    if (!out.firstName || !out.lastName) throw new Error('Renseignez un prénom et un nom fictifs.');
    if (!config.formulas.some(x => x.id === out.formulaId)) throw new Error('Choisissez une formule proposée.');
    return out;
  }
  function add(input) {
    const list = all();
    const now = new Date().toISOString();
    const next = Math.max(0, ...list.map(x => Number(x.id.replace('DEMO-', '')) || 0)) + 1;
    const record = { ...clone(base), ...normalize(input), id: 'DEMO-' + String(next).padStart(3,'0'), createdAt: now, updatedAt: now };
    list.push(record); write(list); return clone(record);
  }
  function update(id, patch) {
    const list = all(); const index = list.findIndex(x => x.id === id);
    if (index < 0) throw new Error('Dossier de démonstration introuvable.');
    const current = list[index];
    const next = { ...current };
    if (patch.status !== undefined) {
      if (!['recu','a_completer','finalise','annule'].includes(patch.status)) throw new Error('Statut inconnu.');
      next.status = patch.status;
    }
    if (patch.groupId !== undefined) {
      if (patch.groupId !== null && !config.groups.some(x => x.id === patch.groupId)) throw new Error('Groupe inconnu.');
      next.groupId = patch.groupId;
    }
    if (next.groupId) {
      const group = config.groups.find(x => x.id === next.groupId);
      if (!next.training || !['yes','preferred'].includes(next.availability[group.slotId])) throw new Error('Ce groupe ne correspond pas aux disponibilités de ce dossier.');
      if (list.filter(x => x.id !== id && x.groupId === next.groupId && x.status !== 'annule').length >= group.capacity) throw new Error('Ce groupe de démonstration est complet.');
    }
    if (next.status === 'finalise' && next.training && !next.groupId) throw new Error('Affectez un groupe compatible avant de finaliser cet exemple.');
    next.revision = current.revision + 1; next.updatedAt = new Date().toISOString();
    list[index] = next; write(list); return clone(next);
  }
  function trainingState(record) {
    if (!record.training) return 'Sans entraînement';
    if (record.groupId) return 'Affecté';
    const values = config.slots.map(x => record.availability[x.id] || 'unknown');
    if (values.includes('unknown')) return 'Disponibilités à compléter';
    if (!values.some(x => ['yes','preferred'].includes(x))) return 'À examiner par le club';
    return 'À affecter';
  }
  function reset() {
    write(seed);
    try { localStorage.removeItem('tcl.demo.communication.v1'); } catch (_) { persistent = false; }
  }
  all();
  window.TCLDemo = Object.freeze({ config: clone(config), all, add, update, reset, trainingState, formula: id => clone(config.formulas.find(x => x.id === id) || { id, label: 'À préciser', priceLabel: 'À confirmer' }), isPersistent: () => persistent });
})();
