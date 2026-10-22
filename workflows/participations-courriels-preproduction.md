---
project: TC_Longages
document_type: deployment-workflow
title: Livraison ciblée des participations et courriels
status: active
version: git
created: 2026-10-22
updated: 2026-10-22
owner: jpdandin
tags: [preproduction, recette, restauration]
---

# Livraison du 22 octobre

Source, archive et empreintes : [reçu canonique du lot](../data/event-participation-mail-verification.json). Ce document décrit l'opération réellement effectuée ; il n'autorise aucune nouvelle livraison.

## Cadrage

Cible unique : préproduction, dossier `tcl-preproduction/drupal`. Production exclue. Données fictives, aucune écriture Google Agenda, aucun contact réel importé. Les paramètres Google privés restent à configurer ; aucune valeur de secret dans le dépôt.

## Développement local

55 contrôles ciblés réussis ; six contrôles techniques CI réussis sur le manifeste indiqué dans le reçu. Le compteur Contact échouait dans la base locale réutilisée ; le test inchangé a réussi en CI propre. Aucun nettoyage du collecteur après refus automatique.

## Préproduction

L'archive privée `event-mail-6d9f413-py36.zip` contient 14 fichiers applicatifs, un manifeste et un opérateur Python compatible avec la version 3.6 du serveur. Sous `private/event-mail-6d9f413`, `operator.py prepare` sauvegarde les six fichiers préexistants et le transport mail, vérifie les empreintes de copies restaurées et enregistre les huit fichiers absents. `install` refuse une dérive de la cible, pose les fichiers et le transport `tcl_bureau_member_communication=php_mail`, puis reconstruit les caches. Pas de migration de schéma.

Après extraction avec umask privé, les deux ressources publiques `css/event-response.css` et `js/event-response.js` ont nécessité le mode 0644 : les autres ressources privées restent protégées. Cette correction des permissions est une étape de livraison indispensable ; elle n'est pas intégrée dans l'archive déjà empreintée. L'opérateur `verify` a ensuite confirmé les 14 empreintes et le transport. Une future archive doit intégrer ce réglage des deux ressources et recevoir sa propre empreinte.

Recette Chrome : les trois réponses, leur modification, l'invalidation du lien remplacé, une ligne de participant et le total final de trois personnes sont vérifiés. Aperçu courriel n°3 : deux adresses autorisées non présélectionnées ; Google non configuré est refusé explicitement. Aucun courriel réel envoyé pour ce nouveau canal. Captures locales hors Git : `.local/event-response-preprod.png` et `.local/member-mail-preprod.png`. Contrôle mobile restant.

Retour arrière préparé, non exécuté : `operator.py rollback` vérifie d'abord les empreintes installées et sauvegardées, restaure les six fichiers, retire seulement les huit ajouts inventoriés, rétablit le transport précédent et reconstruit les caches. Les réponses privées sont conservées. Ne pas l'exécuter si des développements ultérieurs ont changé ces fichiers ; il refuse ce cas.

## Mise en production

Exclue. PR 24 en brouillon, checklist humaine non cochée. Ni la CI ni la recette fictive ne valent acceptation métier ou autorisation de campagne. Restent notamment la configuration Google du site, les informations officielles Beaujolais, les liens individuels dans les courriels, l'entrée depuis l'accueil et la réception réelle du nouveau canal.


## Complément inscription et miniature

Le 22 octobre, `signup-1b01be2.zip` a été vérifié et installé dans le même dossier privé de préproduction, sous `signup-1b01be2`. Source, empreinte du paquet et six fichiers dans [le reçu inscription](../data/event-signup-verification.json). Même opérateur borné, ajusté à six fichiers : cinq sauvegardes/copies restaurées vérifiées, un nouveau formulaire. Aucun nouveau fichier statique ni migration. `verify` confirme les six empreintes ; `rollback` restaure les cinq et retire uniquement le nouveau formulaire sous contrôle d’empreintes. Les associations privées sont conservées. Ce retour arrière n’a pas été exécuté.

La publication de recette n°3 reste visible sur la préproduction protégée, avec une association explicite à l’occurrence fictive du 19 novembre. Aucune affiche n’a été ajoutée à cette publication ; la miniature a été vérifiée sur le PDF Halloween déjà présent. Une demande de lien personnel a été envoyée uniquement à l’adresse de recette famille ; cPanel affiche une remise acceptée à 11 h 52, sans preuve de lecture. Ne pas lancer une campagne réelle ni modifier les informations officielles à partir de ces données fictives.


## Livraison des variantes — préparation du 22 octobre

Lot additionnel : colonne nullable `channel_texts` sur `tcl_bureau_post` (mise à jour 11004), classes et formulaire/aperçus, courriel multipart et réponse présélectionnée. Avant installation : sauvegarde des seuls fichiers remplacés, restauration en copies avec empreintes ; sauvegarde privée de la table des communications et de sa définition, copie dans une table temporaire puis comparaison intégrale. Vérifier la CI du candidat exact et la porte préproduction. Ne pas exécuter toutes les mises à jour Drupal : appeler uniquement 11004, idempotente, puis reconstruire les caches.

Retour arrière : restaurer les fichiers exacts sauvegardés et vider les caches. Conserver la colonne additive et ses valeurs (ignorées par l’ancien code) ; ne pas réinjecter une ancienne table au risque d’écraser les brouillons ultérieurs. La sauvegarde de table reste disponible pour récupération ciblée. Aucun changement du transport, des identités, des protections ou du périmètre production. Vérifier les écrans avec une actualité fictive ; aucun envoi Facebook/WhatsApp.
