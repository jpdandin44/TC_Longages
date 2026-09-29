---
project: TC_Longages
document_type: phase-specification
title: "Phase 2 — Réalisation par lots"
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 2 — Réalisation par lots

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

## Objectif

Réaliser les changements autorisés, avec tests et PR pour chaque lot.

## Autorisation et périmètre

La réalisation se propose par lots après acceptation du socle et autorisation de cette phase. La seule adoption du framework n’autorisait pas de développement fonctionnel supplémentaire. TCL-D02 a ensuite autorisé le moteur interactif et le socle Drupal locaux ; cette portée est décrite dans leurs guides d’installation. Les autres lots gardent leur autorisation propre. Chaque lot explicite son résultat, ses critères et ses exclusions.

## Existant et sources

La V1 locale et les prototypes antérieurs existent déjà. Le détail des acquis, des limites et des lots restants appartient à [l’intégration V1](../integration-officiel.md), et non à un nouveau pourcentage de progression. Le [moteur de suivi interactif](../installation-framework.md) et le [Drupal dédié local](../installation-drupal.md) fournissent désormais un socle de travail ; ils ne rendent pas encore les parcours métier du club opérationnels.

## Exigences et critères à préparer

Préserver la vitrine existante et proposer les manques dans l’ordre des dépendances métier : contact réel qualifié (G2), identité et droits internes (G3), équipes/effectifs (G4), calendrier (G5), disponibilités (G6), communication manuelle (G7) et bêta (G8).

Pour AUTH-001/G3, compléter l’architecture Drupal dédiée retenue avec identité, rôles, périmètres d’équipe, récupération, révocation et exploitation. Distinguer cette authentification métier du nom déclaré dans l’outil local de suivi. Un compte connecté ne reçoit pas implicitement un droit Bureau ou Capitaine. Réutiliser du code impose versionnement, adaptation et tests ; aucun héritage automatique de droits ou de fonctions n’est présumé.

Les inscriptions complètes, paiements, comptes de tous les adhérents et automatisations Facebook/ADOC/WhatsApp restent hors P0. Les anciens écrans restent disponibles comme prototypes, sans devenir un service réel.

## Critères de sortie

- [ ] Changements conformes au périmètre
- [ ] Tests ciblés réussis sur la version présentée
- [ ] Documentation et impacts à jour
- [ ] PR de chaque lot référencée

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

Chaque lot relie les exigences à ses tests pertinents : refus d’accès direct pour un autre rôle ou une autre équipe, conflits d’édition, conservation des données, erreurs de service et absence d’envoi implicite. Conserver le commit testé, les entrées synthétiques, le résultat et les limites. Préparer une PR par lot présenté ; aucune case d’acceptation humaine ne doit être cochée par l’agent.

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Le choix d’instance est confirmé par TCL-D03 ; les droits métier, données d’équipe, liens Google et modalités Contact restent à préciser. La source [des tarifs](../../data/tarifs-inscription.json) garde ses ambiguïtés ; ne pas inventer de tarif ou de condition. Une demande sortant du périmètre du lot exige un changement tracé, pas la réécriture de l’historique.
