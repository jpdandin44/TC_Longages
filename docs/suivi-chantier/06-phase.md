---
project: TC_Longages
document_type: phase-specification
title: "Phase 6 — Déploiement et ouverture"
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 6 — Déploiement et ouverture

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

## Objectif

Livrer la version acceptée, contrôler la cible puis ouvrir sur décision explicite.

## Autorisation et périmètre

Livrer exige un accord explicite portant sur le candidat exact, la cible, les effets et le retour. Ouvrir au public est une décision distincte. Le souhait d’appliquer le framework et l’acquisition du domaine n’autorisent aucun de ces actes.

## Existant et sources

Aucun reçu du framework n’atteste de déploiement ou d’ouverture pour tclongages.fr. L’inventaire cPanel en lecture seule a établi des constats partiels ; le [guide Drupal](../installation-drupal.md) les distingue de la qualification d’une cible prête au déploiement. Les [commandes sensibles](../commandes-sensibles.md) encadrent les changements de DNS, certificat, hébergement, permissions, secrets et visibilité.

## Exigences et critères à préparer

Avant la première écriture : reconfirmer version et cible, conserver une sauvegarde fraîche et réussir la restauration de cette sauvegarde exacte. Installer fermé selon l’adaptateur qualifié, vérifier l’application et documenter tout incident. Utiliser une seule livraison à la fois.

Le guide d’ouverture d’un paquet de démonstration ne doit pas être appliqué à l’archive d’aperçu officiel fermée inconditionnellement. Le Drupal dédié local utilise sa maintenance native, décrite dans [son guide](../installation-drupal.md). Qualifier ce mécanisme sur la cible du vrai candidat avant d’en faire la procédure de livraison. Les changements DNS/HTTPS et l’ouverture doivent chacun avoir un accord applicable ; aucun push ou merge ne les déclenche.

## Critères de sortie

- [ ] Accord de déploiement lié au candidat et à la cible
- [ ] Nouvelle sauvegarde restaurée avant écriture
- [ ] Recette sur domaine ou cible officielle réalisée
- [ ] Ouverture explicitement autorisée et contrôlée

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

Contrôler la version installée, les ressources, les parcours critiques, l’accès anonyme fermé puis le comportement public après accord, les droits internes et les intégrations dans leur portée autorisée. En cas de défaut, appliquer le retour prévu et vérifier le résultat. Un code HTTP 200 ne vaut pas recette métier.

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Candidat, qualification de la cible et de DNS/HTTPS, préproduction, sauvegarde éprouvée et autorisations restent à établir. Toute nouvelle version ou cible impose de réexaminer les preuves et accords impactés. Ne pas annoncer le site publié avant observation du résultat réel.
