document.getElementById('demo-reset').addEventListener('click', function () {
  if (!window.confirm('Effacer vos essais de démonstration et retrouver les exemples de départ ? Les données du serveur habituel ne sont pas concernées.')) return;
  window.TCLDemo.reset();
  document.getElementById('reset-result').textContent = 'Les exemples sont réinitialisés. Vous pouvez recommencer le parcours.';
});
