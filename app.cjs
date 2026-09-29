// CommonJS entry point for a future, explicitly authorized Passenger setup.
import('./server/start.mjs').catch(() => {
  console.error('Le service bureau ne peut pas démarrer. Vérifier la configuration privée.');
  process.exitCode = 1;
});
