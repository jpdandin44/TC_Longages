---
project: TC_Longages
document_type: phase-specification
title: "Phase 1 — Socle et environnements"
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
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

Le [profil](../../framework/profil-projet.json) distingue local, CI, aperçu visuel, préproduction, répétition privée et production. Le [suivi interactif local](../installation-framework.md) permet notes, critères et décisions distinctes ; son identité déclarée n’est pas une connexion Drupal. Un [Drupal dédié est installé localement](../installation-drupal.md), avec maintenance native vérifiée. Aucun environnement distant n’est qualifié par ces installations.

L’inventaire cPanel décrit une cible et ses écarts de runtime, de certificat et de base ; les détails restent centralisés dans le guide Drupal. Le dépôt privé et sa branche `main` sont observés par le connecteur, tandis que le raccordement local et les protections restent à traiter selon [la préparation du dépôt](../preparer-depot.md).

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
| Dépôt et accès | URL, référence réellement lue, branche et protections observées | Lecture par connecteur réalisée ; raccordement CLI et protections non qualifiés |
| Reproduction locale | Installation verrouillée, version runtime et tests sur commit du site | Suivi et Drupal locaux testés selon leurs guides ; aucun commit du site local encore établi |
| Préproduction et répétition | Cibles séparées, droits et neutralisation des sorties | Cibles non désignées |
| Sauvegarde | Périmètre, inventaire et restauration privée | Archive locale de sources ≠ sauvegarde hébergeur |

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Qualifier les adaptateurs autour du Drupal dédié retenu et vérifier les écarts d’hébergement. Préparer les opérations sensibles en langage de décision puis obtenir l’accord applicable. Les modèles CI ne prouvent ni exécution GitHub ni protection de branche. Préserver les autres projets du compte o2switch.
