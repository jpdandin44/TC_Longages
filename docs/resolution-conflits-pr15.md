---
project: TC_Longages
document_type: verification-reference
title: Résolution des conflits de la PR 15
status: active
version: git
created: 2026-10-07
updated: 2026-10-07
owner: jpdandin
tags: [git, pr, conflits, support, v2]
---

# Résolution des conflits de la PR #15

## Périmètre

Le responsable demande de corriger les conflits de la
[PR #15](https://github.com/jpdandin44/TC_Longages/pull/15).
La branche support part de `6696f39`. Après la fusion de #14, `main` est
`89489f37de748b012ce150d3618fb0139c5a54ee` : quinze fichiers sont en conflit
lors du contrôle, contre les trois signalés initialement.

Le lot reste dans Développement local pour la correction, au sein du suivi
Cadrage, Développement local, Préproduction et Mise en production existant.
Les anciennes recettes et accords restent limités à leur candidat et cible.
Aucune installation hébergée ou modification Google n’est incluse.

## Résolution retenue

- Les sections support/édition et V2 des six documents structurants sont
  conservées ensemble, avec les dates et décisions historiques.
- Le suivi JSON reçoit les deux itérations par identifiant. Les changements
  de `main` sont réunis avec les apports du support depuis leur ancêtre commun ;
  aucune approbation n’est créée. Les vues de lecture sont régénérées.
- La CI conserve `delivery-safety`, `drupal-mysql`, `support-runtime` et
  `technical-ci`. Les deux recettes natives sont indépendantes, les droits de
  lecture et actions épinglées sont inchangés. Le contrat de test et l’empreinte
  du workflow correspondent au YAML réuni.
- Les deux manifestes officiels sont régénérés depuis les sources résolues.
  Le manifeste Git est préparé après le commit source, puis vérifié.

Le formulaire conserve type et description obligatoires, e-mail facultatif,
confirmation obligatoire, objet automatique et suivi privé. L’édition Drupal
et les révisions restent disponibles. Les apports V2 proviennent de #14 déjà
fusionnée ; ils ne donnent aucune nouvelle autorisation de livraison.

## Préservation de l’espace de travail

La correction est faite dans `.worktrees/pr15-conflicts`, depuis la tête de
la PR, puis publiée sans forçage vers `feat/v1-signalement-support`.
Le checkout opérationnel `support-v1` et ses travaux non commis sont conservés.
Une vérification d’empreintes couvre les fichiers déjà modifiés ou nouveaux ;
seuls le suivi canonique et ses vues peuvent recevoir la trace de cette tâche.
La PR #17 reste séparée : son contexte agenda/photo n’est pas copié dans #15.

## Preuves et reproduction

Le [reçu support](../data/support-v1-verification.json), bloc
`conflictResolution`, indique les commits réunis, le candidat, les contrôles
et l’observation GitHub. Les preuves antérieures restent inchangées.
Le [manifeste Git](../data/framework-candidate.json) décrit les fichiers du
nouveau commit source ; les écritures de preuves suivent ses exclusions fermées.

Depuis cette copie, utiliser les dépendances Node verrouillées et les dépendances
Python de `docs/references/framework-developpement-pilote/requirements-verification.txt` :

```powershell
npm.cmd ci --ignore-scripts
npm.cmd run check
node scripts/framework.mjs build
node scripts/framework.mjs check
node scripts/framework-candidate.mjs verify
git diff --check
```

La CI exécute les installations et recettes Drupal dans ses runners jetables.
Sa réussite ne réinstalle pas le site de préproduction. Le responsable garde
la revue, les quatre confirmations de PR et la décision de fusion.
