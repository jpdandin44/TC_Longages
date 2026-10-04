---
project: TC_Longages
document_type: generated-project-dashboard
title: Tableau de bord du développement piloté
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [framework, suivi, vue-generee]
---

# TC Longages — suivi du projet

<!-- Vue générée par scripts/framework.mjs ; les décisions passent par le moteur interactif. -->

**Phase actuelle : 1 — Socle et environnements. 1 validation\(s\) consignée\(s\), 0 encore recevable\(s\) selon le moteur. Aucune livraison attestée par ce suivi local.**

[Ouvrir le suivi interactif local](http://127.0.0.1:4181/) · [Ouvrir Drupal local](http://127.0.0.1:4182/)

Ces adresses nécessitent le démarrage des services locaux ; cette vue ne certifie pas leur disponibilité. La revue interactive permet notes, critères et décisions locales distinctes. Elle ne déploie ni n’ouvre le site au public.

Domaine : **tclongages.fr**, obtenu selon confirmation utilisateur ; DNS/HTTPS non vérifiés par ce générateur. Dépôt : [TC_Longages](https://github.com/jpdandin44/TC_Longages).

PR #5 fusionnée par le responsable ; Action de construction réussie sur le commit de fusion et candidat ZIP reçu vérifié. La lune isolée est active après sauvegarde privée requalifiée. Aucune validation de phase ou recette hébergée déduite ; l’ancienne prochaine action de session est conservée ci-dessous comme historique.

**Installation :** Installation clôturée : les autorisations normales de progression sont réactivées.

**Architecture retenue ou à décider :** Drupal dédié au TC Longages, maintenance par l’administration native, composants communs réutilisables ultérieurement. L’état d’installation et la qualification sont documentés séparément ; une décision d’architecture ne constitue pas une preuve d’authentification effective.

## Phases

| Phase | État | Livrable | Prochaine action |
|---|---|---|---|
| 0 — Cadrage et audit | Validée historiquement — à requalifier | [Dossier](00-phase.md) | PR #4 ouverte et contrôle technique GitHub réussi. Le responsable relit les fichiers, coche lui-même les confirmations de la PR et décide de la fusion. Il enregistre ensuite les quatre critères et valide la phase 0 ; la phase 1 demande une autorisation distincte. |
| 1 — Socle et environnements | En cours | [Dossier](01-phase.md) | Reconnexion personnelle au compte principal TC, inventaire puis choix d’implantation ; présenter le lot de configuration correspondant. |
| 2 — Réalisation par lots | Non démarrée | [Dossier](02-phase.md) | Après acceptation du socle et autorisation : proposer les lots manquants de la V1 en conservant G0–G8 et leurs acquis locaux. |
| 3 — Recette locale et revue | Non démarrée | [Dossier](03-phase.md) | Après autorisation : rejouer les parcours pertinents sur un commit identifié et soumettre la recette et ses réserves à revue. |
| 4 — Préproduction réelle | Non démarrée | [Dossier](04-phase.md) | Après autorisation de phase et mutation explicite : installer un candidat identifié dans une cible isolée, puis tester les intégrations autorisées. |
| 5 — Préparation au déploiement | Non démarrée | [Dossier](05-phase.md) | Après autorisation : qualifier sauvegarde fraîche, restauration privée et retour arrière sur les cibles explicitement retenues. |
| 6 — Déploiement et ouverture | Non démarrée | [Dossier](06-phase.md) | Attendre une autorisation de livraison liée au candidat et à la cible ; conserver l’ouverture publique comme décision séparée. |
| 7 — Observation et transfert | Non démarrée | [Dossier](07-phase.md) | Après livraison contrôlée et autorisation : définir observation, support, sauvegardes et point de transfert sans créer de surveillance permanente implicite. |

## Points à résoudre

| Référence | Sujet | Conséquence | Action |
|---|---|---|---|
| TCL-TBD-01 | Protection Git et première revue | Protection main enregistrée : PR, technical-ci/policy obligatoires, administrateurs inclus, refus force/delete. Première revue humaine à conduire. | Consulter le reçu de revue puis réaliser personnellement la revue et la fusion éventuelle. |
| TCL-TBD-02 | Domaine, DNS, HTTPS et cibles | DNS et racine observés ; SSL autosigné et PHP8.1 insuffisants pour Drupal11. Détails d’exploitation conservés hors dépôt public. | Préparer puis autoriser PHP8.4, certificat reconnu et racine dédiée sans ouverture automatique. |
| TCL-TBD-03 | Drupal dédié et droits internes | Drupal dédié installé et maintenance native vérifiée localement ; aucun rôle métier privé hébergé qualifié. | Qualifier hébergement et droits métier après accord pour la cible isolée. |
| TCL-TBD-04 | Google, Contact et support | Calendar/Forms absents, aucun message envoyé et support prévu seulement | Identifier ressources, propriétaires, destinataires, permissions et traitement des données avant activation |
| TCL-TBD-05 | Sauvegardes et exploitation | RPO/RTO, rétention, copie hors hébergeur et restauration réelle inconnus | Fixer les objectifs avec le responsable puis éprouver une sauvegarde exacte en environnement privé |
| TCL-TBD-06 | Revue du cadrage et qualification du framework | Le travail technique ne valide aucune phase ; la remise doit rattacher PR, commit, manifeste et tests avant acceptation humaine. | Compléter la traçabilité et présenter une PR du cadrage quand le dépôt est prêt ; validation humaine et autorisation suivante séparées |
| TCL-TBD-CI | Sources tarifaires et recettes portables | PDF vierges de recette inclus avec empreintes ; les recettes navigateur et Drupal restent distinctes de la CI Node. | Exécuter la CI Windows et conserver séparément les résultats locaux navigateur/Drupal et la future recette hébergée. |

## Lire ce suivi

Les huit phases décrivent la méthode de travail ; G0–G8 restent les lots métier de la V1. Les recettes historiques ne créent pas de validation automatique. Une validation peut rester consignée tout en devenant à requalifier après changement de documents ou de preuves. Le suivi ne calcule aucun pourcentage d’effort.

Cette vue statique ne modifie aucune décision. Le moteur interactif en boucle locale distingue validation et autorisation suivante ; l’identité y reste déclarée, sans authentification distante. Le mode d’installation n’accorde aucun déploiement, aucune ouverture publique et aucune permission de modifier les accès distants. Les adaptations de préproduction, livraison et restauration restent à qualifier.

[Guide du framework](../framework-developpement.md) · [Point de session](../point-session.md) · [Suivi canonique](suivi-chantier.json) · [Profil](../../framework/profil-projet.json)

Empreinte du profil, du suivi, de l’état d’installation et de la revue courante : `2c696a45e9dbceb15502672ef5cc355ac3f4f1ebec542b1e27df16e0859b7cee`.
