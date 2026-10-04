---
project: TC_Longages
document_type: phase-specification
title: "Phase 4 — Préproduction réelle"
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 4 — Préproduction réelle

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

Ce dossier conserve l’ancien identifiant 4 ; il appartient désormais à la phase commune 2. Les critères et décisions historiques gardent leur portée. Le [suivi canonique](suivi-chantier.json) conserve le mapping.

## Objectif

Vérifier le candidat sur le moteur et les intégrations représentatifs de la production.

## Autorisation et périmètre

Cette phase exige une cible isolée désignée, un candidat identifié et une autorisation de mutation applicable avant toute installation. Une préproduction réelle est distincte de l’aperçu local et de l’ancienne démonstration HTTP.

## Existant et sources

Le compte principal dispose d’une racine et d’une base propres pour `preprod.tclongages.fr`. Le lot d’environnement est réalisé ; le ZIP Drupal est extrait et vérifié sous fermeture Apache avant installation. La première installation a réussi après synchronisation personnelle du mot de passe SQL ; Drupal reste sous maintenance, avec connexion native HTTPS accessible. Le responsable s’est connecté personnellement ; les sept pages du site ont été parcourues dans sa session. L’administration et la connexion sont en français. La recette des comptes et droits, l’édition et le retour arrière restent à conduire. Le [point de session](../point-session.md) et le [reçu d’installation](../../data/framework-revue-verification.json#primaryAccountFirstInstallation) portent la dernière observation. Le [guide Drupal](../installation-drupal.md) centralise la procédure et les écarts avec le local. Le [guide de dépôt de la démonstration](../publier-v1-sous-domaine.md) concerne un paquet statique distinct.

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

Les références de PR et leurs états figurent dans le suivi canonique. Une PR de configuration de l’hébergement et une PR d’installation ne prouvent pas l’existence de comptes métier ou d’un éditeur. Conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

### Périmètre du critère « Comptes, droits et édition vérifiés si applicables »

| Élément | Réalisation constatée | Test à effectuer avant acceptation |
|---|---|---|
| Utilisateur et droits SQL | Utilisateur dédié et dix privilèges appliqués à la seule base de préproduction ; lot d’environnement documenté. | Connexion de l’application réussie avec le paramètre privé synchronisé. Ces droits SQL ne sont pas les droits des membres du club. |
| Administrateur Drupal | Créé par la première installation autorisée du 4 octobre, avec inscription publique désactivée et maintenance active. Connexion HTTPS accessible ; recette connectée non encore attestée. | Connexion et déconnexion dans Drupal hébergé, création puis désactivation d’un compte de recette, refus des écrans administratifs au visiteur anonyme. |
| Bureau et Capitaine | Rôles et restrictions par équipe absents du module Drupal livré. Les anciens prototypes restent distincts. | TBD — préciser le périmètre du lot, développer une PR fonctionnelle, puis vérifier accès autorisés, refus hors équipe et révocation. |
| Édition des sept pages publiques | `ClubPageController` lit des fichiers HTML hors de la racine Web. Aucun éditeur, contenu Drupal ni révision éditoriale n’est implémenté pour ces pages. | TBD — confirmer le périmètre de l’édition, livrer une PR fonctionnelle et tester sauvegarde, persistance, droits, conflit et retour à une révision. |

Le responsable signale le 4 octobre qu’il ne peut pas tester et approuver ces éléments sans PR. Le rattachement des PR existantes est corrigé dans le suivi ; le périmètre fonctionnel supplémentaire attend sa réponse. Le libellé obligatoire est conservé. Aucun « non applicable », résultat réussi ou accord de report n’est déduit de l’absence de fonctionnalité. Ce critère reste à vérifier.

## Réserves et prochaine étape

Les ressources d’intégration, la cible isolée et les modalités d’authentification métier restent à qualifier sur l’architecture Drupal dédiée. L’accès à des composants CONNECT existants ne vaut pas autorisation de modifier AVEREO. Le responsable examine les réserves avant acceptation du candidat et autorisation séparée de préparer la livraison.
