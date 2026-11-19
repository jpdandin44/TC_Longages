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

Branche `feat/gmail-communications`, issue de `083d89e`. Implémentation en cours, non livrée : `GmailConnection` conserve un accord d'envoi chiffré distinct de l'accord Contacts ; `GmailDelivery` prépare un message texte/HTML par destinataire et appelle Gmail. Le compte autorisé est fixe. Les boutons utilisent les liens personnels du module de participation existant.

Configuration privée prévue : `tcl_gmail.enabled`, et `tcl_gmail.recipe_send_enabled` uniquement pour la recette autorisée. Les identifiants du client proviennent de `tcl_member_contacts` dans chaque environnement. Retour OAuth : `/bureau/contacts-google/retour`. Droit demandé : `gmail.send` et identité du compte, sans lecture de la boîte. Les secrets ne sont jamais versionnés. L'accord Gmail ne remplace pas l'accord Contacts.

Le test `tests/gmail-communications.php` passe 32 contrôles le 19 novembre : identité et permissions, protection du retour OAuth, séparation des environnements, chiffrement, révocation, en-têtes et destinataire unique, conservation des trois liens, réponses HTTP et résultats incertains. Tous les échanges Google sont simulés, aucun mail n'est envoyé. La recette complète de l'envoi dans le Bureau et la CI restent à effectuer.

Un identifiant de message Gmail prouve l'acceptation par Gmail, pas la réception finale. Une erreur réseau ou une réponse ambiguë reste en attente, sans relance automatique. Quand Gmail est activé mais indisponible, aucun repli silencieux vers l'ancien expéditeur. Une relance après rejet doit conserver la tentative historique et rester ciblée.

## Préproduction

Installation ciblée effectuée le 19 novembre, après sauvegarde des fichiers et comparaison de leur restauration. Les six contrôles techniques de CI ont réussi sur le premier candidat. Le responsable a accordé l'envoi Gmail ; le Bureau confirme « Autorisation enregistrée ». La recette d'envoi sur jpdandin@gmail.com et des réponses reste à effectuer ; cette confirmation ne prouve pas un envoi.

Le message de confirmation Drupal était masqué par le menu latéral, car il est rendu hors de `main`. Le correctif réserve aux messages le même décalage que le contenu sur ordinateur, et supprime ce décalage sur mobile. Il reste limité aux pages du Bureau. Vérifier le rendu hébergé avant promotion du candidat corrigé.

## Mise en production

Le 19 novembre 2026, constat en lecture seule dans Chrome : le projet **TC Longages Production**, avec le compte du club, affiche **Gmail API — État : Activé** et le bouton « Désactiver l'API ». Cette observation confirme l'activation effectuée par le responsable ; elle ne prouve ni l'accord OAuth d'envoi, ni le raccordement effectif du site.

Aucun déploiement Gmail ni envoi effectué à ce stade. Restent : recette préalable, sauvegarde et plan de retour arrière, livraison ciblée sous maintenance sans écraser le correctif d'agenda public, accord Google d'envoi, relance limitée aux deux destinataires autorisés et contrôle de remise. Ne pas marquer les anciens envois acceptés comme livrés. Revenir en arrière ne doit pas déclencher de nouvelle tentative automatique.

Suivi canonique : `../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`, depuis la racine de ce worktree. Les observations hébergées doivent être datées et distinguées des tests simulés.
