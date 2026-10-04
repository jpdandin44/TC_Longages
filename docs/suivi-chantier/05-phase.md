---
project: TC_Longages
document_type: phase-specification
title: "Phase 5 — Préparation au déploiement"
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 5 — Préparation au déploiement

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

Ce dossier conserve l’ancien identifiant 5 ; il appartient désormais à la phase commune 2. Les critères et décisions historiques gardent leur portée. Le [suivi canonique](suivi-chantier.json) conserve le mapping.

## Objectif

Qualifier les prérequis, la sauvegarde, la restauration et le retour arrière.

## Autorisation et périmètre

La préparation est une opération distincte du déploiement. Elle exige l’acceptation du candidat et les accords d’accès ou de sauvegarde correspondants. Aucune copie en production ni ouverture publique ne découle de cette phase.

## Existant et sources

La [baseline locale](../../data/baseline-officiel.json) conserve les sources antérieures à la V1. Elle ne contient ni serveur distant, ni compte, ni secret, ni données de navigateur. Les ZIP de démonstration ne sont pas des candidats de production approuvés.

## Exigences et critères à préparer

Documenter la cible exacte et vérifier les accès minimum. Construire le manifeste du même candidat qualifié. Définir le périmètre de sauvegarde après inventaire réel : fichiers et, si présente, base applicative. Contrôler intégrité et confidentialité, conserver une copie hors hébergeur et restaurer cette sauvegarde exacte en environnement privé.

Répéter l’application du candidat et le retour arrière avec sorties neutralisées. Identifier les nouvelles données possibles après ouverture et leur politique de réconciliation ; ne pas rétablir une base ancienne en écrasant des données vivantes. Définir RPO, RTO, rétention et responsabilités sans inventer de valeurs.

## Critères de sortie

- [ ] Version et manifeste acceptés
- [ ] Sauvegarde intègre effectivement restaurée
- [ ] Application et retour arrière répétés hors production
- [ ] Accès réseau, exploitation et dossier de livraison qualifiés

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

La preuve attendue inclut candidat, commit, cible, manifeste, sauvegarde exacte, empreintes, inventaire, restauration effective, démarrage, recette et retour vérifiés. Tester les échecs significatifs : archive tronquée, droit insuffisant, cible incorrecte, version modifiée. Toute erreur de sauvegarde ou restauration bloque l’écriture de production.

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Les adaptateurs de livraison, sauvegarde et restauration ne sont pas fournis par les prototypes du club. Les scripts ou accords AVEREO ne sont pas transférables sans adaptation ni nouvelle portée d’autorisation. Présenter un dossier concret au responsable avant toute décision de livraison.
