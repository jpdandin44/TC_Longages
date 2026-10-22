---
project: TC_Longages
document_type: workflow
title: Réinitialisation des comptes de recette
status: active
version: git
created: 2026-10-21
updated: 2026-10-22
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

## Action gestionnaire explicite — 22 octobre

Ajout livré en préproduction de « Envoyer un lien de réinitialisation du mot de passe » dans la fiche d’un compte actif, distinct de l’invitation initiale. Route `/bureau/comptes/{user}/reinitialiser-mot-de-passe` et confirmation avec adresse enregistrée. Les modifications de fiche doivent être enregistrées avant l’envoi. Même transport privé account_invitation, mêmes contrôles OAuth/droits, liste de recette, verrou et limite de cinq minutes. La réinitialisation exige une identité TC déjà liée, n’ajoute aucune association et ne change pas le mot de passe. Objet et texte du courriel distincts ; trace privée password_reset par compte. Aucun envoi au chargement de la fiche.

Contrôles locaux : 28 service mail et 20 HTTP invitation/réinitialisation réussis, collecteur fictif seulement. Action vérifiée dans Chrome en préproduction ; essai Artemis accepté par le serveur, réception non confirmée. Aucun mot de passe changé.

### Livraison ciblée du complément

Le candidat source `8272e9f` et son manifeste `3095317` regroupent l’action de réinitialisation et le raccordement Google Contacts. [Preuves et état courant](../data/google-accounts-verification.json). Archive privée `google-reset-8272e9f.zip`, dix fichiers applicatifs ; opérateur limité à `/home2/daje5127/tcl-preproduction/drupal`, aucun changement de transport ni migration. Sodium doit être disponible. Six fichiers sauvegardés et restaurés dans des copies vérifiées, quatre nouveaux fichiers inventoriés avant installation.

L’opérateur privé `google-reset-8272e9f/operator.py` accepte prepare/install/verify/rollback. Installation refusée si la cible diverge de la sauvegarde. Retour arrière refusé si les fichiers livrés ont été modifiés ensuite ; il restaure les six fichiers antérieurs et retire seulement les quatre ajouts inventoriés. Les comptes et les données privées sont conservés. Le retour arrière complet n’a pas été exécuté. La configuration Google privée et le consentement sont des étapes séparées de la livraison du code.

Correction de libellé après contrôle hébergé : archive `reset-label-369a2f3.zip`, un formulaire, sauvegarde et copie restaurée vérifiées ; CI 39839326721 six réussites sur manifeste `3f69a69`. Opérateur privé `reset-label-369a2f3/operator.py`, mêmes commandes et contraintes. Pour revenir avant le lot complet, annuler d’abord ce correctif, puis le paquet précédent ; chacun refuse une dérive ultérieure. Aucun retour arrière exécuté. Les preuves portent les deux empreintes et candidats.


### Qualification du 22 octobre — complément

État courant et preuves : [reçu Google et comptes](../data/google-accounts-verification.json). Configuration Google privée installée et consentement personnel accordé : connexion et lecture effective vérifiées en préproduction, libellé de 21 contacts. Aperçu Google réussi, 20 exclusions affichées après normalisation/filtrage, zéro destinataire éligible, envoi désactivé. Aucun courriel envoyé par ce canal. Bouton de réinitialisation séparé et stylé livré, CI 39840747992 réussie ; aucun nouvel envoi pour cette retouche. Production inchangée.
