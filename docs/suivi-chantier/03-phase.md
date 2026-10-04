---
project: TC_Longages
document_type: phase-specification
title: "Phase 3 — Recette locale et revue"
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 3 — Recette locale et revue

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

Ce dossier conserve l’ancien identifiant 3 ; il appartient désormais à la phase commune 1. Les critères et décisions historiques gardent leur portée. Le [suivi canonique](suivi-chantier.json) conserve le mapping.

## Objectif

Qualifier les parcours locaux et présenter une version candidate cohérente.

## Autorisation et périmètre

La recette d’ensemble intervient sur les lots autorisés et un candidat cohérent. L’acceptation locale et l’autorisation d’installer en préproduction seront distinctes.

## Existant et sources

Les [recettes V1](../recette-officiel.md) et [de la démonstration](../recette-v1-sous-domaine.md) sont des points de comparaison datés. Elles attestent la navigation et la démonstration observées, sans rendre actifs Contact, Google ou les comptes. Les nouvelles vérifications du [moteur interactif](../installation-framework.md) et de [Drupal local](../installation-drupal.md) couvrent leur installation et leurs protections propres ; elles ne constituent ni recette complète de la V1 intégrée à Drupal, ni validation humaine de cette phase.

## Exigences et critères à préparer

Vérifier les parcours réellement livrés sur le commit candidat : navigation, identité et médias, mobile/desktop, formulaires et erreurs, droits lorsque implémentés, conservation des données, références de contact et liens. Distinguer les écrans de simulation des services actifs.

Identifier la version du candidat, ses fichiers, empreintes et écarts de configuration. Réconcilier les commentaires humains sans écraser de brouillons. Les tests hérités OIDC concernent l’expérimentation générique ; ils ne qualifient pas l’intégration métier au Drupal dédié installé ni un futur raccordement CONNECT.

## Critères de sortie

- [ ] Parcours représentatifs vérifiés
- [ ] Régression et données préservées
- [ ] Revue visuelle ou métier disponible
- [ ] Version candidate et réserves identifiées

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

Prévoir les tests Node ciblés, la recette navigateur aux largeurs pertinentes, les contrôles clavier et les règles Apache adaptées au candidat réel. Consigner les captures utiles et les résultats avec version, environnement, acteur, date et limites. Un message « envoyé » affiché n’est pas une preuve de réception ; un lien Calendar non configuré ne clôture pas son parcours.

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Sans commit du site et candidat identifiés, aucun résultat historique ne devient une preuve passed du framework. Le commit initial distant du README ne constitue pas une version des sources locales testées. Les réserves et fonctions non livrées sont présentées au responsable avec la PR. Une réussite technique ne valide pas automatiquement la phase ni la préproduction.
