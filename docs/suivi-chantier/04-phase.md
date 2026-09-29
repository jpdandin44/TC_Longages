---
project: TC_Longages
document_type: phase-specification
title: "Phase 4 — Préproduction réelle"
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 4 — Préproduction réelle

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

## Objectif

Vérifier le candidat sur le moteur et les intégrations représentatifs de la production.

## Autorisation et périmètre

Cette phase exige une cible isolée désignée, un candidat identifié et une autorisation de mutation applicable avant toute installation. Une préproduction réelle est distincte de l’aperçu local et de l’ancienne démonstration HTTP.

## Existant et sources

Aucune cible native de préproduction n’est qualifiée pour le club. La lecture cPanel a identifié la cible du domaine et ses prérequis ; elle n’établit pas un environnement isolé prêt à recevoir le candidat. Le [guide Drupal](../installation-drupal.md) centralise les constats d’hébergement et les écarts avec l’installation locale. Le [guide de dépôt de la démonstration](../publier-v1-sous-domaine.md) concerne un paquet statique sans authentification ou collecte.

## Exigences et critères à préparer

Qualifier le runtime finalement retenu, les dépendances, le stockage/base, les accès restreints, HTTPS, les secrets dédiés, les partages Google et la neutralisation des communications. Drupal dédié étant retenu, vérifier l’édition réelle, les révisions, les droits, les sessions et les écarts avec le local. Tester Admin, Bureau et Capitaine avec refus hors équipe ; ne pas considérer le seul administrateur comme représentatif.

Des essais d’intégration réels nécessitent des destinataires, ressources et effets autorisés. Les données d’effectifs restent privées. noindex n’est pas un contrôle d’accès.

## Critères de sortie

- [ ] Candidat exact identifié dans le vrai runtime
- [ ] Comptes, droits et édition vérifiés si applicables
- [ ] Intégrations réelles vérifiées dans leur périmètre autorisé
- [ ] Réserves traitées ou dérogations explicites compatibles avec la livraison

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

| Parcours | Preuve attendue |
|---|---|
| Candidat et cible | Commit, manifeste, version installée, URL et environnement identifiés |
| Droits | Comptes/rôles représentatifs, accès permis et refusés, révocation |
| Contact | Envoi et réception par destinataire autorisé, erreurs et données de test nettoyées |
| Google | Bon formulaire/calendrier, portée d’équipe, partages du Sheet et cas de non-réponse |
| Édition Drupal | Sauvegarde depuis le vrai navigateur, persistance, conflit et retour |

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Les ressources d’intégration, la cible isolée et les modalités d’authentification métier restent à qualifier sur l’architecture Drupal dédiée. L’accès à des composants CONNECT existants ne vaut pas autorisation de modifier AVEREO. Le responsable examine les réserves avant acceptation du candidat et autorisation séparée de préparer la livraison.
