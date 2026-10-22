---
project: TC_Longages
document_type: workflow
title: Réinitialisation des comptes de recette
status: active
version: git
created: 2026-10-21
updated: 2026-10-21
owner: jpdandin
tags: [preproduction, comptes, courriel]
---

# Réinitialisation des comptes de recette

## Cadrage

V2-MAIL-01 : le transport natif `user_password_reset` utilisait NullMail et
n’envoyait aucun courriel. Le responsable autorise sa correction en préproduction
pour les seuls destinataires de recette existants, puis un essai Artemis.
La création du mot de passe reste personnelle ; aucun secret ni lien dans Git.

## Développement local

`RecipePasswordMail` conserve le message natif et son lien à usage unique.
Il impose l’hôte `preprod.tclongages.fr`, le type `user_password_reset`,
l’activation de la recette et la liste privée `allowed_recipients` déjà utilisée
par les invitations. CC/BCC/To supplémentaires sont refusés. Expéditeur :
support@tclongages.fr ; objet préfixé RECETTE PRÉPRODUCTION.
Les règles de validité du lien et de limitation des demandes restent celles de Drupal.

24 contrôles dans `tests/recipe-password-mail.php`, sans transport réel, intégrés
à `scripts/bureau-oauth-recette.ps1`. Candidat initial : `8fa4f2c`.

## Préproduction

Cible exclusive : `/home2/daje5127/tcl-preproduction/drupal`.
Sauvegarde privée : `../private/reset-mail-20261021/system-mail-before.json` ;
lecture JSON et copie identique vérifiées avant activation. Nouveau plugin
préparé dans ce dossier privé, avec test de garde-fous à transport simulé.

Activation réalisée le 21 octobre après six contrôles CI réussis sur `978027d` : installation du seul fichier
`web/modules/custom/tcl_bureau/src/Plugin/Mail/RecipePasswordMail.php`, reconstruction
des caches, puis affectation de la seule clé `system.mail:interface.user_password_reset`
à `tcl_recipe_password_mail`. Le défaut NullMail et les deux autres exceptions
restent inchangés. Vérifier le plugin effectif et le statut du seul essai réel.

Retour arrière : rétablir la valeur antérieure de `interface.user_password_reset`
(absente dans la sauvegarde initiale : supprimer uniquement cette clé), reconstruire
le cache et vérifier NullMail pour ce type. Le fichier du plugin peut rester inactif.
Ne pas restaurer globalement une configuration qui aurait changé entre-temps.

Empreinte du plugin livré vérifiée : `120598936352ee88d6ced1ee299420fc9acb1c145403db6c7ff7554797db8da1`. Les 24 contrôles simulés passent aussi sur le serveur. Essai unique via le service natif `_user_mail_notify` pour Artemis, accepté le 21 octobre à 17:34:14 UTC ; réception confirmée par le responsable le 21 octobre ; création personnelle du mot de passe confirmée par le responsable le 21 octobre. Le formulaire HTTP a été inspecté mais non soumis depuis le compte administrateur. Aucun lien personnel lu. [Reçu](../data/password-reset-recipe-verification.json).

## Mise en production

Exclue. Aucune autorisation de production, de modification de rôle ou d’ouverture
de destinataires supplémentaires. Un hôte différent est refusé même si le plugin
était sélectionné par erreur.

## Présentation des écrans de compte

Habillage dédié aux routes de connexion, demande de réinitialisation, confirmation du lien et modification du compte. Carte centrée, champs adaptatifs, focus visible, boutons et onglets harmonisés. Le bouton de confirmation devient « Choisir mon mot de passe ». Aucun champ natif, validateur, permission ou jeton supprimé. Le test HTTP existant couvre le parcours complet sur un compte fictif local et contrôle les deux écrans supplémentaires. Livré le 21 octobre à 19 h 52 (Paris), source `206885b`, manifeste `f20f15c`, six contrôles CI réussis. Trois fichiers seulement, sauvegarde privée restaurée en copie et empreintes vérifiées. Écran de compte inspecté dans Chrome en lecture seule ; aucun mot de passe modifié. Contrôle visuel mobile restant. [Preuves et retour arrière](../data/account-presentation-verification.json).
