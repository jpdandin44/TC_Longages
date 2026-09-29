import { createPreviewServer } from './preview-server.mjs';

const server = createPreviewServer();
server.on('error', error => {
  console.error(error.code === 'EADDRINUSE' ? 'Le port 4173 est déjà occupé. Vérifiez le service en cours, sans arrêter un autre projet.' : 'Le serveur local ne peut pas démarrer.');
  process.exitCode = 1;
});
server.listen(4173, '127.0.0.1', () => console.log('Prototype local : http://127.0.0.1:4173\nEspace bureau : http://127.0.0.1:4173/bureau.html\nLes pages internes exigent un compte bureau actif. Sans configuration, elles restent fermées.\nCtrl+C pour arrêter. Aucun déploiement.'));
