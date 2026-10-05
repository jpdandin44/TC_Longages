---
project: TC_Longages
document_type: generated-project-dashboard
title: Tableau de bord du développement piloté
status: active
version: git
created: 2026-09-29
updated: 2026-10-05
owner: jpdandin
tags: [framework, suivi, vue-generee]
---

# TC Longages — suivi du projet

<!-- Vue générée par scripts/framework.mjs ; les décisions passent par le moteur interactif. -->

**Phase actuelle : 2 — PrÃ©production. 2 validation\(s\) consignée\(s\), 0 encore recevable\(s\) selon le moteur. Aucune livraison attestée par ce suivi local.**

[Ouvrir le suivi interactif local](http://127.0.0.1:4181/) · [Ouvrir Drupal local](http://127.0.0.1:4182/)

Ces adresses nécessitent le démarrage des services locaux ; cette vue ne certifie pas leur disponibilité. La revue interactive permet notes, critères et décisions locales distinctes. Elle ne déploie ni n’ouvre le site au public.

Domaine : **tclongages.fr**, obtenu selon confirmation utilisateur ; DNS/HTTPS non vérifiés par ce générateur. Dépôt : [TC_Longages](https://github.com/jpdandin44/TC_Longages).

PR #5 fusionnÃ©e par le responsable ; Action de construction rÃ©ussie sur le commit de fusion et candidat ZIP reÃ§u vÃ©rifiÃ©. La lune isolÃ©e est active aprÃ¨s sauvegarde privÃ©e requalifiÃ©e. Aucune validation de phase ou recette hÃ©bergÃ©e dÃ©duite ; lâ€™ancienne prochaine action de session est conservÃ©e ci-dessous comme historique.

**Installation :** Installation clôturée : revue et validation du candidat restent contrôlées.

**Architecture retenue ou à décider :** Drupal dÃ©diÃ© au TC Longages, maintenance par lâ€™administration native, composants communs rÃ©utilisables ultÃ©rieurement. L’état d’installation et la qualification sont documentés séparément ; une décision d’architecture ne constitue pas une preuve d’authentification effective.

## Phases

| Phase | État | Livrable | Prochaine action |
|---|---|---|---|
| 0 — Cadrage | Validée historiquement — à requalifier | [Dossier](00-phase.md) | PR #13 fusionnÃ©e ; Revue puis confirmation personnelle Valider si une nouvelle acceptation de cette correction est souhaitÃ©e. CritÃ¨res, commentaires et dÃ©cisions antÃ©rieures conservÃ©s. |
| 1 — DÃ©veloppement local | Validée historiquement — à requalifier | [Dossier](01-phase.md) | PR #13 fusionnÃ©e ; conserver les douze critÃ¨res, le commentaire et la confirmation personnelle pour la revue de cette correction locale. Aucune opÃ©ration dâ€™hÃ©bergement ne dÃ©coule de cette revue. |
| 2 — PrÃ©production | En cours | [Dossier](04-phase.md) | Terminer la qualification de la racine officielle et du retour rÃ©el sous maintenance dans le lot autorisÃ©, puis conserver les reÃ§us. |
| 3 — Mise en production | Non dÃ©marrÃ©e | [Dossier](06-phase.md) | ExÃ©cuter le raccordement autorisÃ© sous maintenance ; qualifier la cible et le retour rÃ©el ; ouvrir le mÃªme candidat aprÃ¨s rÃ©ussite des contrÃ´les. |

## Points à résoudre

| Référence | Sujet | Conséquence | Action |
|---|---|---|---|
| TCL-TBD-01 | Protection Git et première revue | Protection main enregistrée : PR, technical-ci/policy obligatoires, administrateurs inclus, refus force/delete. Première revue humaine à conduire. | Consulter le reçu de revue puis réaliser personnellement la revue et la fusion éventuelle. |
| TCL-TBD-02 | Domaine, DNS, HTTPS et cibles | DNS et racine observés ; SSL autosigné et PHP8.1 insuffisants pour Drupal11. Détails d’exploitation conservés hors dépôt public. | Préparer puis autoriser PHP8.4, certificat reconnu et racine dédiée sans ouverture automatique. |
| TCL-TBD-03 | Drupal dédié et droits internes | Drupal dédié installé et maintenance native vérifiée localement ; aucun rôle métier privé hébergé qualifié. | Qualifier hébergement et droits métier après accord pour la cible isolée. |
| TCL-TBD-04 | Google, Contact et support | Calendar/Forms absents, aucun message envoyé et support prévu seulement | Identifier ressources, propriétaires, destinataires, permissions et traitement des données avant activation |
| TCL-TBD-05 | Sauvegardes et exploitation | RPO/RTO, rétention, copie hors hébergeur et restauration réelle inconnus | Fixer les objectifs avec le responsable puis éprouver une sauvegarde exacte en environnement privé |
| TCL-TBD-06 | Revue du cadrage et qualification du framework | Le travail technique ne valide aucune phase ; la remise doit rattacher PR, commit, manifeste et tests avant acceptation humaine. | Examiner puis fusionner la PR candidate ; reprendre Revue et confirmer personnellement Valider. La phase locale suivante passe en cours sans autorisation supplémentaire. |
| TCL-TBD-CI | Sources tarifaires et recettes portables | PDF vierges de recette inclus avec empreintes ; les recettes navigateur et Drupal restent distinctes de la CI Node. | Exécuter la CI Windows et conserver séparément les résultats locaux navigateur/Drupal et la future recette hébergée. |

## Lire ce suivi

Les quatre phases regroupent le cadrage, le développement local, la préproduction et la mise en production ; G0–G8 restent les lots métier de la V1. Les recettes historiques ne créent pas de validation automatique. Une validation peut rester consignée tout en devenant à requalifier après changement de documents ou de preuves. Le suivi ne calcule aucun pourcentage d’effort.

Cette vue statique ne modifie aucune décision. Le moteur interactif en boucle locale propose Revue, Valider et Demander des corrections ; la phase locale suivante passe en cours après validation. L’identité y reste déclarée, sans authentification distante. Le mode d’installation n’accorde aucun déploiement, aucune ouverture publique et aucune permission de modifier les accès distants. Les adaptations de préproduction, livraison et restauration restent à qualifier.

[Guide du framework](../framework-developpement.md) · [Point de session](../point-session.md) · [Suivi canonique](suivi-chantier.json) · [Profil](../../framework/profil-projet.json)

Empreinte du profil, du suivi, de l’état d’installation et de la revue courante : `3837c1b7159ed05ddd33c11161b7a188599736344927c3bd6a13b4bc994e5aef`.
