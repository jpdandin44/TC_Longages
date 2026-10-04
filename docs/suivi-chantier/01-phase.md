---
project: TC_Longages
document_type: phase-specification
title: "Phase 1 — Socle et environnements"
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 1 — Socle et environnements

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

## Objectif

Préparer Git, le local, la préproduction, les accès et la stratégie de sauvegarde.

## Autorisation et périmètre

Ce dossier définit le socle à présenter à la revue. TCL-D02 autorise une installation locale encadrée sans valider les phases précédentes ; cette exception temporaire ne vaut ni acceptation de cette phase, ni installation distante, ni modification d’accès ou publication. Son état et son retour aux contrôles normaux figurent dans [la procédure d’installation](../installation-framework.md).

## Existant et sources

Le [package local](../../package.json) prévoit Node.js >=22.9.0, les commandes de construction et les recettes existantes. Le socle documentaire est présent. Le paquet de démonstration Apache a une recette locale, mais cette démonstration n’est pas une préproduction native.

Le [profil](../../framework/profil-projet.json) distingue local, CI, aperçu visuel, préproduction, répétition privée et production. Le [suivi interactif local](../installation-framework.md) permet notes, critères et décisions distinctes ; son identité déclarée n’est pas une connexion Drupal. Un [Drupal dédié est installé localement](../installation-drupal.md), avec maintenance native vérifiée. Les preuves locales et hébergées restent distinctes.

Le dépôt public TC, le raccordement local et les contrôles GitHub sont établis ; les constats datés restent dans [la préparation du dépôt](../preparer-depot.md) et ses reçus. La PR #7 est fusionnée personnellement le 4 octobre. Après un accord cPanel distinct, la préproduction du compte principal est configurée et fermée ; les résultats et limites restent centralisés dans le [reçu courant](../../data/framework-revue-verification.json#hostingPrimaryConfiguration). Aucun Drupal distant n’est installé.

## Suivi de déploiement au 4 octobre

| Étape | État vérifié et prochaine action |
|---|---|
| Configuration de préproduction | Terminée après accord : domaine, DNS public, HTTPS reconnu, PHP CLI/HTTP et base vide avec droits dédiés. Racine fermée. |
| Installation Drupal | Préparée localement, à revoir et autoriser sur le candidat exact ; paramètres privés soumis personnellement, connexion SQL applicative à tester. |
| Recette de préproduction | À réaliser après installation sous maintenance, avec sauvegarde/restauration et retour arrière. Résolution DNS ordinaire du poste à requalifier. |
| Production et ouverture | À préparer après recette ; cible, certificat officiel et accords de livraison/ouverture restent distincts. |

Le [parcours V1](../parcours-mise-en-ligne.md) et le bloc `developmentWorkflow` du suivi canonique portent ce déploiement. Ce tableau n’enregistre aucune validation de phase ; la Lune est conservée.

## Exigences et critères à préparer

Préparer le dépôt unique communiqué en conservant l’existant et en excluant .env, .local, exports privés, données d’adhérents et artefacts temporaires. Vérifier la branche réelle, les droits, la visibilité, les protections, les contrôles CI et les règles de revue avant de les déclarer actifs.

Décrire chaque environnement : runtime, version, racine, stockage, base si nécessaire, accès, secrets par nom, sorties réseau, données d’essai, sauvegarde et responsable. TCL-D03 fixe le choix d’un Drupal dédié. La réutilisation éventuelle de composants communs CONNECT et les droits Admin/Bureau/Capitaine restent à qualifier ; les comptes d’essai locaux ne valent pas comptes métier de production.

## Critères de sortie

- [ ] Dépôt et documentation prêts
- [ ] Local reproductible et données de test isolées
- [ ] Préproduction cible identifiée et séparée
- [ ] Gestion des secrets et restauration documentées

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

| Contrôle à prévoir | Preuve attendue | Limite actuelle |
|---|---|---|
| Dépôt et accès | URL, référence réellement lue, branche et protections observées | Git raccordé, PR/CI actives ; dernières preuves exactes liées au candidat dans le reçu de revue |
| Reproduction locale | Installation verrouillée, version runtime et tests sur commit du site | Recettes locales et ZIP reçu identifiés ; aucun test local ne vaut installation Drupal hébergée |
| Préproduction et répétition | Cibles séparées, droits et neutralisation des sorties | Racine et base dédiées dans le compte principal, PHP partagé ; SQL applicatif, Drupal et répétition de restauration restent à tester |
| Sauvegarde | Périmètre, inventaire et restauration privée | Export de fichiers du compte restauré en copie privée ; restauration du futur Drupal et de SQL non éprouvée |

## Revue et PR

La référence de PR et les dates de remise figurent dans le suivi. Les PR fusionnées restent historiques ; le nouveau lot d’outillage et ses preuves ont leur propre revue. Conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Qualifier les adaptateurs autour du Drupal dédié retenu et vérifier les écarts d’hébergement. Préparer les opérations sensibles en langage de décision puis obtenir l’accord applicable. Les modèles CI ne prouvent ni exécution GitHub ni protection de branche. Préserver les autres projets du compte o2switch.
