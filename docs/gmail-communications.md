---
project: TC_Longages
document_type: integration
title: Communications du club via Gmail
status: active
version: git
created: 2026-11-19
updated: 2026-11-19
owner: jpdandin
tags: [gmail, communications, recette]
---

# Communications du club via Gmail

## Cadrage

Demande du responsable : envoyer les communications depuis tclongages@gmail.com, conserver support@tclongages.fr pour les comptes et le support. Préparer localement, tester en préproduction avec les boutons de réponse, puis livrer sous maintenance. La relance autorisée concerne uniquement les deux destinataires Wanadoo identifiés dans le diagnostic privé, sans diffusion générale. Le signalement o2switch ne doit pas être envoyé.

## Développement local

Branche `feat/gmail-communications`, issue de `083d89e`. Implémentation livrée en préproduction, pas en production : `GmailConnection` conserve un accord d'envoi chiffré distinct de l'accord Contacts ; `GmailDelivery` prépare un message texte/HTML par destinataire et appelle Gmail. Le compte autorisé est fixe. Les boutons utilisent les liens personnels du module de participation existant.

Configuration privée prévue : `tcl_gmail.enabled`, et `tcl_gmail.recipe_send_enabled` uniquement pour la recette autorisée. Les identifiants du client proviennent de `tcl_member_contacts` dans chaque environnement. Retour OAuth : `/bureau/contacts-google/retour`. Droit demandé : `gmail.send` et identité du compte, sans lecture de la boîte. Les secrets ne sont jamais versionnés. L'accord Gmail ne remplace pas l'accord Contacts.

Le test `tests/gmail-communications.php` passe 32 contrôles le 19 novembre : identité et permissions, protection du retour OAuth, séparation des environnements, chiffrement, révocation, en-têtes et destinataire unique, conservation des trois liens, réponses HTTP et résultats incertains. Tous les échanges Google sont simulés, aucun mail n'est envoyé. La recette hébergée et la CI sont consignées ci-dessous.

Un identifiant de message Gmail prouve l'acceptation par Gmail, pas la réception finale. Une erreur réseau ou une réponse ambiguë reste en attente, sans relance automatique. Quand Gmail est activé mais indisponible, aucun repli silencieux vers l'ancien expéditeur. Une relance après rejet doit conserver la tentative historique et rester ciblée.

## Préproduction

Installation ciblée effectuée le 19 novembre, après sauvegarde des fichiers et comparaison de leur restauration. Les six contrôles techniques de CI ont réussi sur le premier candidat. Le responsable a accordé l'envoi Gmail ; le Bureau confirme « Autorisation enregistrée ». Un courriel a été reçu sur l’adresse de recette autorisée. La réponse Je participe pour quatre personnes est enregistrée ; elle est toujours visible après rechargement du Bureau le 19 novembre. Aucun nouvel envoi lors de cette vérification.

Le message de confirmation Drupal était masqué par le menu latéral, car il est rendu hors de `main`. Le correctif réserve aux messages le même décalage que le contenu sur ordinateur, et supprime ce décalage sur mobile. Il reste limité aux pages du Bureau. Le rendu hébergé a été vérifié sur ordinateur et mobile.

## Mise en production

Le 19 novembre 2026, constat en lecture seule dans Chrome : le projet **TC Longages Production**, avec le compte du club, affiche **Gmail API — État : Activé** et le bouton « Désactiver l'API ». Cette observation confirme l'activation effectuée par le responsable ; elle ne prouve ni l'accord OAuth d'envoi, ni le raccordement effectif du site.

Aucun déploiement Gmail ni envoi effectué à ce stade. Restent : validation du candidat réaligné, sauvegarde fraîche et plan de retour arrière, livraison ciblée sous maintenance sans écraser le correctif d'agenda public, accord Google d'envoi, relance limitée aux deux destinataires autorisés et contrôle de remise. Ne pas marquer les anciens envois acceptés comme livrés. Revenir en arrière ne doit pas déclencher de nouvelle tentative automatique.

Suivi canonique : `../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`, depuis la racine de ce worktree. Les observations hébergées doivent être datées et distinguées des tests simulés.

## Correction du chargement de la réponse — 19 novembre

La réception Gmail est confirmée par le responsable. Son profil Chrome reçoit une réponse Drupal 404 HTML pour le script et du HTML pour la feuille de style, malgré les fichiers présents ; la réponse reste `unknown`, nombre zéro. Le contrôleur inclut maintenant les deux petites ressources fixes dans la réponse HTML, avec des empreintes CSP SHA-256 exactes, sans `unsafe-inline`. Les sources JS/CSS restent uniques. Origin, CSRF, lien personnel, absence de mutation à l’ouverture et plafond dix sont conservés. Trois contrôles du rendu PHP et six tests des boutons réussissent localement ; la preuve de confirmation hébergée est conservée dans le suivi opérationnel. Aucun envoi supplémentaire ni changement de production autorisé par cette correction.

## Réalignement après fusion de la PR 34

Le 19 novembre, intégration de main `ae9da51` dans la branche de la PR 35. Les sections Gmail et agenda public sont conservées ; le manifeste est régénéré. Les deux séries de tests PHP sont conservées dans la recette OAuth. La participation existante est contrôlée en lecture seule : une réponse Oui, quatre personnes, conservées après rechargement. Aucun envoi, aucune modification de production. La CI du nouveau candidat est à vérifier sur GitHub avant fusion.
