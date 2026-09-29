---
project: TC_Longages
document_type: deployment-guide
title: Présenter la V1 sur le sous-domaine avec ouverture contrôlée
status: active
version: git
created: 2026-09-24
updated: 2026-09-24
owner: jpdandin
tags:
  - officiel
  - o2switch
  - demonstration
  - publication-manuelle
---

# Présenter la V1 sur le sous-domaine

L'utilisateur demande une archive adaptée pour examiner la nouvelle V1 sur [tclongages.daje3540.odns.fr](http://tclongages.daje3540.odns.fr/) avant la future production sur `tclongages.fr`. **Il réalise lui-même le dépôt, l'ouverture et la fermeture.** La préparation locale n'autorise aucune intervention de l'agent sur cPanel, le DNS, le certificat ou les comptes.

Cette présentation contient les sept pages de la V1, ses images et ses tarifs. Elle reste sans compte connecté, sans donnée privée, sans envoi par le formulaire Contact et sans raccordement Google. Les liens publics externes et le lien de messagerie du club restent utilisables volontairement. L'adresse `support@tclongages.fr` est seulement prévue, sans boîte ou alias actif confirmé. N'utiliser que des saisies fictives pour examiner le contact.

L'authentification **Drupal est l'orientation souhaitée pour le futur site sur `tclongages.fr`**. Elle n'est ni livrée dans ce ZIP ni activée. L'utilisateur souhaite examiner la logique CONNECT et les fonctions mutualisées, et précise que l'installation dédiée ou le raccordement existant sont **« À déterminer ensemble »**. L'instance, les modules, le mode de connexion, les trois rôles et les contrôles par équipe doivent donc être qualifiés avant réalisation et recette. Aucun raccordement effectif au Drupal d'un autre projet n'est présumé. L'achat du domaine ne suffira pas à installer cette fonction : voir le [suivi V1](integration-officiel.md).

## Choisir le bon paquet

Utiliser **[tc-longages-v1-demo-o2switch.zip](../livrables/tc-longages-v1-demo-o2switch.zip)** pour cette opération.

| Variante | Usage et comportement |
|---|---|
| `tc-longages-v1-demo-o2switch.zip` | Nouvelle démonstration V1 : sept pages, fermée par défaut, ouverture manuelle selon ce guide. |
| `tc-longages-officiel-apercu.zip` | Revue locale : refus Apache 503 inconditionnel, sans ouverture par renommage. Ne pas le déposer pour cette présentation. |
| `tc-longages-demo-o2switch.zip` | Ancienne démo d'inscriptions et communication. Ce n'est pas la nouvelle V1. |

Le paquet V1 contient exactement dix fichiers à la racine :

- `index.html`, `competitions.html`, `calendrier.html`, `disponibilites.html`, `equipes.html`, `espace.html`, `contact.html` ;
- `robots.txt`, `.htaccess`, `maintenance.active`.

Les photographies, le logo, les styles et les scripts sont intégrés aux HTML. Aucun dossier d'images, serveur Node.js, PHP, module Drupal, compte ou fichier de configuration privé n'est à déposer. L'espace interne est une page d'attente publique, pas un accès authentifié. `robots.txt` et `noindex` demandent aux moteurs de ne pas indexer ; ils ne rendent pas le site privé. Pendant l'ouverture, toute personne disposant de l'adresse peut consulter la démonstration.

La commande locale `npm.cmd run officiel:demo:package` reconstruit les pages et prépare l'archive ; `npm.cmd run officiel:demo:build` permet de régénérer seulement les fichiers. Ces opérations écrivent les sorties locales `officiel-demo-o2switch/` et l'archive ; elles ne contactent pas o2switch. Les résultats réellement obtenus sont consignés dans la [recette de cette variante](recette-v1-sous-domaine.md), séparément de la [recette de l'aperçu local](recette-officiel.md).

## 1. Identifier et sauvegarder le dossier cible

Dans cPanel, relever la **racine des documents** associée précisément à `tclongages.daje3540.odns.fr`. Son chemin n'est pas connu dans le projet : ne pas supposer qu'il s'agit de `public_html`. Dans le gestionnaire de fichiers, activer l'affichage des fichiers cachés afin de voir `.htaccess`.

Avant toute modification, faire un inventaire des fichiers et sous-dossiers et sauvegarder en privé l'état du site tennis : HTML, `index.php` s'il existe, `.htaccess`, `robots.txt`, témoins de maintenance et tout fichier susceptible d'être remplacé ou retiré. Conserver une copie téléchargée ou hors de **toutes** les racines publiques. Une archive, un manifeste ou une copie `.bak` ne doit pas rester publiquement téléchargeable.

Si le dossier contient WordPress, une application PHP, des règles particulières ou des fichiers partagés avec un autre projet, suspendre le remplacement et faire examiner cette cible. Le nouveau `.htaccess` restreint les routes et peut bloquer ces services. Ne rien modifier dans AVEREO/Drupal ou dans les autres sites du compte.

## 2. Installer la fermeture avant les pages

Si l'ancienne démonstration est déjà ouverte, commencer par son renommage `maintenance.inactive` vers `maintenance.active` et vérifier sa fermeture avant la mise à jour. Si ces témoins ne commandent pas l'existant, ne pas présumer la fermeture : les étapes suivantes l'établissent avec la nouvelle règle.

1. Téléverser et extraire le nouveau ZIP dans un dossier temporaire **hors de toutes les racines publiques**. Vérifier les dix fichiers, notamment le `.htaccess` caché. Ne pas extraire directement dans la racine du site : l'ordre d'extraction pourrait exposer une partie des pages.
2. Dans la racine vérifiée du seul site tennis, copier d'abord `maintenance.active`. Après sauvegarde, remplacer ensuite `.htaccess` par celui du nouveau paquet. Laisser le témoin actif ; si un ancien `maintenance.inactive` existe, le retirer de cette racine seulement après avoir conservé son état dans la sauvegarde.
3. Dans une nouvelle fenêtre privée, ouvrir la racine et `contact.html`, avec le cache désactivé dans les outils du navigateur (F12, onglet Réseau). **Constater HTTP 503** sur ces deux adresses. Contrôler également une ancienne page comme `communication.html`. Si une page reste servie normalement ou si la réponse est 500, arrêter la copie et faire vérifier dossier, règles et cache. Une erreur 500 n'est pas une maintenance qualifiée.
4. Après constat du 503, copier les sept HTML et `robots.txt`, directement dans la racine du site, sans dossier englobant. Confirmer uniquement les remplacements identifiés et sauvegardés.
5. D'après l'inventaire, déplacer en privé les anciens fichiers devenus inutiles du seul site tennis, notamment `adherer.html`, `bureau.html`, `inscriptions.html`, `communication.html`, `parcours.html`, `actualites-bureau.html` s'ils appartiennent bien à l'ancienne démo. Ne pas supprimer un fichier dont le rôle ou l'appartenance est incertain. Même encore présents, ces chemins sont refusés par la nouvelle liste de routes à l'ouverture ; ce refus ne remplace pas un inventaire propre.
6. Vérifier à nouveau 503 sur la racine et les pages directes. Conserver `maintenance.active` jusqu'à votre décision d'ouverture.

Ne pas copier le projet complet, `config/`, `server/`, `.env`, `.local/`, les sources, les comptes ou les manifestes. Le ZIP et la sauvegarde restent dans un emplacement privé.

## 3. Ouvrir volontairement et vérifier

**L'action sensible qui rend cette démonstration visible est le renommage de `maintenance.active` en `maintenance.inactive`.** Elle ne modifie ni le DNS ni le nom de domaine et n'active aucun compte. Le `.htaccess` doit rester présent et inchangé.

| État des témoins | Résultat attendu avec la nouvelle configuration |
|---|---|
| `maintenance.active` seul | Fermé : HTTP 503. |
| `maintenance.inactive` seul | Ouvert : seules les sept pages V1 et `robots.txt` sont servis. |
| Les deux témoins ou aucun | Fermé : HTTP 503. Corriger l'état avant de tenter une ouverture. |

Une fois l'ouverture décidée, vérifier dans une session privée sans cache :

- HTTP 200 sur la racine, les six autres pages et `robots.txt` ; navigation, logo, photo, adresse **35, chemin de Muret**, tableaux de tarifs et contacts conformes ;
- les six accès rapides fonctionnent sur ordinateur et téléphone ; Calendar, Forms et connexion restent clairement en attente ;
- Contact affiche un aperçu pour une saisie fictive, sans envoyer de message ; le support est signalé comme non activé ;
- HTTP 404 sur les anciennes routes `communication.html`, `bureau.html`, `inscriptions.html`, `adherer.html`, `parcours.html`, `actualites-bureau.html` et sur un chemin inconnu, même si d'anciens fichiers ont subsisté ;
- aucun compte Drupal ou formulaire de mot de passe n'est ouvert sur cette présentation.

Le refus des autres chemins est une restriction de la démonstration statique, pas l'authentification du futur bureau. Les versions locales et les contrôles Apache locaux ne prouvent pas le comportement de l'hébergement : les vérifications ci-dessus restent nécessaires après le dépôt.

## 4. Refermer après examen

Renommer `maintenance.inactive` en `maintenance.active`. Vérifier **HTTP 503** sur la racine, `contact.html`, `espace.html` et une ancienne route, dans une nouvelle fenêtre privée sans cache. Ne pas fermer seulement l'accueil : les URL directes doivent aussi être refusées.

Une page déjà chargée peut rester visible sur un appareil. Le retour 503 ferme les nouvelles requêtes, pas les copies déjà affichées. Si une couche de cache sert encore une page, faire qualifier ce cache avant de considérer la fermeture comme effective. Ne pas retirer `.htaccess` pour résoudre un incident et ne pas modifier le DNS pour effectuer ce basculement.

## Incident et retour à l'état précédent

Refermer et constater le refus des nouvelles requêtes. À partir de l'inventaire sauvegardé, retirer en privé les fichiers ajoutés par ce lot et restaurer les fichiers remplacés ou déplacés du site tennis. **Restaurer uniquement `index.html` laisserait d'autres pages V1 accessibles : ce n'est pas un retour arrière complet.**

Restaurer les anciens HTML et `robots.txt` selon leur état d'origine, en gardant `maintenance.active` pendant cette opération. Rétablir l'ancien `.htaccess` **en dernier**, une fois les nouvelles pages retirées et l'ancien ensemble restauré. Si cette ancienne version prend en charge la maintenance, la conserver fermée puis rétablir volontairement son ancien état d'ouverture ; ne pas remettre son témoin `maintenance.inactive` avant la fin de la restauration. Si l'ancienne configuration ne comporte pas de maintenance, la restauration de son `.htaccess` est elle-même l'action qui remettra l'ancien site à disposition. Si aucun `.htaccess` n'existait, retirer celui du paquet seulement après retrait des pages ajoutées, puis retirer les témoins introduits. Vérifier le résultat public et l'absence des routes introduites par cette V1. Ne restaurer ni tout le compte cPanel ni un autre projet.

Si le nouveau `.htaccess` produit 500, suspendre le dépôt. Revenir à la sauvegarde selon cet ordre ou faire diagnostiquer la compatibilité ; ne pas supprimer la règle en laissant les nouvelles pages exposées. Tout éventuel accès de l'agent à l'hébergement demanderait une instruction explicite distincte.

## Avant le futur site réel

Ce ZIP permet une présentation statique temporaire. Il ne constitue pas le paquet de production. Pour `tclongages.fr`, il reste à qualifier domaine/racine/HTTPS, réalisation Drupal, comptes et restrictions Admin/Bureau/Capitaine, services Google et partages, traitement du contact, tests et accès de la bêta. La publication de ce futur lot sera une décision explicite après recette ; elle ne découle ni du nom de domaine retenu ni de la demande de ce ZIP.

Les [commandes sensibles](commandes-sensibles.md) détaillent les décisions, risques et retours arrière. Aucun dépôt distant, certificat, état DNS ou service de messagerie n'est attesté par cette procédure.
