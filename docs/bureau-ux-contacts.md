---
project: TC_Longages
document_type: candidate-guide
title: Candidate Contacts et navigation du Bureau
status: draft
version: git
created: 2026-11-18
updated: 2026-11-18
owner: jpdandin
tags: [bureau, ux, contacts, candidate]
---

# Candidate Contacts et navigation du Bureau

## Cadrage

Demande du 18 novembre : intégrer les améliorations UX/UI avant de livrer Contacts en production. Au cadrage initial, PR 30 était non fusionnée ; les PR 30 et 29 sont désormais fusionnées dans main au 18 novembre. Branche `feat/bureau-ux-contacts` issue de `5866756`. Cette candidate élargie ne reprend aucune validation humaine de la PR 30. L'ancien paquet Contacts seul reste historique et ne doit pas être déployé à la place du nouveau lot.

## Développement local

- Navigation commune par rôle : Agenda, Événements, Suivi des réponses, Communications, Historique des envois, Contacts Google, équipes et comptes selon les permissions existantes. Adhérents reste en construction.
- Agenda mensuel dans le Bureau pour les gestionnaires autorisés ; liens de navigation entre mois restent dans cet espace. Les capitaines gardent le parcours des équipes et leurs restrictions.
- Tableaux, filtres, formulaires et navigation adaptés à un écran étroit. Actions contextuelles telles que Archives et Créer une équipe préservées.
- Communication : titre et texte communs, adaptations facultatives dans des panneaux repliables ; variantes déjà enregistrées signalées et conservées. Aucun changement de diffusion ou de validation.
- Suivi des réponses : événement, occurrence, filtre ; indicateurs séparant réponses et personnes confirmées, réponses obsolètes à reconfirmer. Adresses de recette masquées dans cette vue.
- Historique : lecture des traces existantes uniquement, statut « accepté par le serveur » distinct de la réception. Les anciennes traces n'ont pas d'adresse, d'auteur ou de détail d'erreur ; l'écran indique ces limites. Pas de reconstitution fictive de données manquantes.
- Contacts : conserve l'actualisation en lecture seule de la PR 30, dernier bilan, libellé strict, dédoublonnage et conservation du dernier succès en cas d'échec. Aucun envoi lors de l'actualisation.

Contrôles du 18 novembre : syntaxe PHP ; 12 contrôles Drupal/SQLite des rapports, droits et chemins des valeurs de formulaire ; 8 contrôles du formulaire Contacts et de l'annuaire avec services simulés ; 15 contrôles de connexion Google simulée ; 18 tests des communications du prototype. Aucun appel Google réel ni courriel dans ces tests. Aperçus du rendu Drupal inspectés sur ordinateur et à 390 pixels ; les pages statiques de démonstration ne prouvent pas une session HTTP authentifiée.

Commandes : `php -c .local/drupal-tools/php.ini tests/workspace-ux-local.php`, `php -c .local/drupal-tools/php.ini tests/contacts-refresh-unit.php`. Ces tests sont ajoutés à la recette OAuth de CI. Le premier refuse toute base autre que la SQLite privée de ce checkout et annule ses données de test.

Aperçu local : http://127.0.0.1:4197/reponses.html et http://127.0.0.1:4197/communication.html. Données fictives, export de rendu sans enregistrement fonctionnel ; ne pas soumettre les formulaires de cet aperçu. Les routes applicatives sont `/bureau/reponses` et `/bureau/envois`. Les URL synthétiques de PR 29 sont préservées sous des noms de routes distincts, toujours réservées à la préproduction. Le menu commun utilise les rapports sur données enregistrées.

## Préproduction

À effectuer sur le nouveau candidat : installation ciblée après sauvegarde et restauration sur copie, reconstruction du cache de routes, contrôle des menus et URL selon les rôles, enregistrement et relecture des variantes, navigation mensuelle et retour après édition, Contacts actualisés sans envoyer, réponses et historique sur données fictives. Vérifier les permissions avec et sans OAuth. La preuve préproduction de PR 30 ne couvre pas cette refonte. Ni nouvelle livraison ni validation humaine à ce stade.

## Mise en production

Non autorisée par la demande d'intégration. Attendre la recette préproduction, les validations et l'accord de livraison de ce candidat exact. Maintenance obligatoire avant modification, sauvegarde fraîche et retour arrière testé sur copie. Conserver les fichiers remplacés, supprimer au retour arrière seulement les nouveaux fichiers identifiés par le manifeste, reconstruire les caches. Aucune migration métier ; conserver comptes, secrets Google, communications, événements, réponses et contacts réels. Aucun import de fixtures. Vérifier accueil, agenda, Bureau et Contacts sans envoyer, puis attendre l'accord distinct de réouverture.

## Réserves

CI de la branche élargie et recette HTTP complète : à qualifier. Connexion Google réelle et dernière liste de production : pas relues dans ce lot UX. Historique détaillé des futures tentatives (destinataires, initiateur, erreurs) : lot de stockage séparé, non implémenté ici. Préproduction et production inchangées.

## Réconciliation avec main après PR 29 — 18 novembre 2026

Base main `ca134dc`, comprenant PR 29 et PR 30. Les deux routes de maquette conservent leurs URL `/bureau/suivi-reponses` et `/bureau/historique-envois`, leur contrôleur et leur garde préproduction ; leurs noms internes portent le suffixe `_recipe`. Les rapports de PR 31 conservent `/bureau/reponses` et `/bureau/envois`. Les liens ajoutés dans les anciens menus par PR 29 ne sont pas dupliqués : la navigation commune de PR 31 les remplace. Aucun changement de droits, données ou transport. Cinq assertions de coexistence des routes s’ajoutent aux douze contrôles UX ; les cinq frontières d’accès de PR 29 sont aussi réexécutées.

Cadrage et Développement local : réconciliation et contrôles du nouveau candidat. Préproduction : aucune nouvelle installation dans cette intervention. Mise en production : exclue. La CI précédente reste historique ; consulter la PR 31 pour les contrôles du commit réconcilié. Les quatre validations humaines demeurent non cochées.
