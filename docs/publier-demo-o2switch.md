---
project: TC_Longages
document_type: deployment-guide
title: Déposer et ouvrir temporairement la démonstration sur o2switch
status: active
version: git
created: 2026-09-16
updated: 2026-09-17
owner: jpdandin
tags:
  - o2switch
  - demonstration
  - publication-manuelle
  - maintenance
---

# Déposer la démonstration sur o2switch

L'utilisateur a choisi de **remplacer la vitrine par la démonstration complète** à l'adresse [tclongages.daje3540.odns.fr](http://tclongages.daje3540.odns.fr/), puis de **réaliser lui-même le dépôt**. L'agent prépare les fichiers et ce guide ; il ne se connecte pas à l'hébergement et ne publie rien.

La démonstration montre le fonctionnement avec des dossiers fictifs et les visuels fournis pour cette présentation. Elle ne reçoit aucune inscription réelle et ne propose ni compte, ni mot de passe, ni connexion OIDC. Le dépôt ne force pas HTTPS. L'utilisation de HTTP reste limitée à cette présentation fictive ; ne saisir aucune information réelle ou confidentielle.

Cette révision conserve le logo JPEG temporaire et les actualités illustrées, puis ajoute la photo réelle du court, les tarifs des deux fiches 2026–2027 dans la FAQ et un aperçu ADOC simulé. Les conditions non précisées des tarifs, dont le choix 125/150 € des cours adultes, restent affichées comme à confirmer. L'affiche fournie se charge volontairement dans l'éditeur par **Utiliser l’affiche exemple** ; sa présence dans le paquet ne crée pas d'annonce validée et n'ouvre aucune inscription réelle. Les coordonnées éventuellement visibles sur ce support appartiennent au visuel fourni, pas à des dossiers d'adhérents collectés.

## Paquet destiné au dépôt

Utiliser seulement [tc-longages-demo-o2switch.zip](../livrables/tc-longages-demo-o2switch.zip). Ce paquet est distinct de l'ancienne archive de vitrine et de l'archive de visite avec serveur local. Il contient dix fichiers à sa racine :

| Fichiers | Rôle |
|---|---|
| `index.html`, `adherer.html` | Vitrine et formulaire fictif, accessibles depuis les menus publics. |
| `bureau.html`, `inscriptions.html`, `communication.html`, `parcours.html`, `actualites-bureau.html` | Outils et visite accessibles par leur adresse directe, sans lien depuis les menus publics. |
| `robots.txt` | Consigne aux robots d'indexation ; aucune protection d'accès. |
| `.htaccess` | Règles Apache, dont fermeture de la démonstration lorsque le fichier témoin existe. |
| `maintenance.active` | Fichier témoin présent par défaut : demande au serveur de répondre **503 — service indisponible**. |

Les styles, scripts et images, dont la photo du court, le logo et l'affiche exemple, sont intégrés aux HTML. Les tarifs sont également intégrés à la construction. **Aucun JPEG, PDF ou JSON séparé n'est à téléverser** : le ZIP conserve dix fichiers. Aucun lancement Node.js, base de données ou installation WordPress n'est nécessaire. Le [manifeste de l'archive](../livrables/tc-longages-demo-o2switch.manifest.json) et le [manifeste des fichiers générés](../data/hosted-demo-manifest.json) restent hors du ZIP. La commande locale `npm.cmd run demo:hosting:package` reconstruit ce paquet sans transfert vers o2switch. La [recette dédiée](recette-demo-o2switch.md) distingue les contrôles locaux du comportement Apache, qui doit encore être vérifié sur l'hébergement.

**Une adresse non affichée dans le menu n'est pas une protection.** Pendant l'ouverture, toute personne connaissant ou découvrant les adresses peut consulter ces pages. Les imports JSON de données réelles restent bloqués ; l'ajout local d'une image dans une actualité est autorisé. Facebook, WhatsApp, ADOC et la copie du message restent simulés. Les essais, y compris les images ajoutées et le choix Ten'Up, restent dans le navigateur de chaque visiteur, sans base partagée ni téléversement serveur. Pour retrouver une saisie fictive dans le tableau de gestion, utiliser le même navigateur et la même adresse HTTP pour les deux pages.

## 1. Identifier et sauvegarder la cible

Dans cPanel, relever la **Racine des documents** associée exactement à `tclongages.daje3540.odns.fr`. Ne pas supposer qu'il s'agit de `public_html`, ni modifier un autre site du compte. o2switch explique le lien entre un sous-domaine et son dossier dans sa [documentation des sous-domaines](https://faq.o2switch.fr/cpanel/domaines/configuration-sous-domaine/).

Ouvrir ce dossier dans le Gestionnaire de fichiers et afficher les fichiers cachés pour voir `.htaccess`. L'outil permet l'envoi, l'extraction, la copie, le déplacement et le renommage des fichiers : voir le [guide officiel du gestionnaire](https://faq.o2switch.fr/cpanel/fichiers/gestionnaire-fichiers-web/).

Avant tout remplacement, télécharger une sauvegarde privée de l'existant concerné, notamment `index.html`, `index.php`, `.htaccess` et `robots.txt`, avec la liste des fichiers présents. Conserver cette sauvegarde sur l'ordinateur ou hors de toute racine publique ; aucune copie `.bak` ne doit rester accessible sur le site.

Si WordPress, une redirection, des règles particulières ou des fichiers d'un autre site sont présents, faire examiner le dossier avant de les remplacer. Le paquet ne supprime pas les anciens fichiers et ne permet pas d'en déduire lesquels retirer. La racine exacte, l'existant et les règles Apache restent à vérifier dans cPanel.

## 2. Déposer en gardant le site fermé

**Si une version de la démonstration est déjà déposée**, la fermer avant toute mise à jour : renommer le témoin `maintenance.inactive` en `maintenance.active`, puis vérifier les réponses 503. Il ne doit rester qu'un seul témoin. Si les deux noms existent déjà, conserver `maintenance.active` et retirer uniquement l'ancien `maintenance.inactive`, après avoir confirmé la fermeture. Cela évite un conflit de nom lors de la prochaine ouverture. Garder une sauvegarde privée de la version précédente et poursuivre les étapes ci-dessous.

1. Dans le Gestionnaire de fichiers, préparer un dossier temporaire **hors de toutes les racines publiques**, y téléverser le ZIP puis l'extraire. Vérifier les dix fichiers, y compris `.htaccess`. Cette extraction privée évite une exposition dépendant de l'ordre d'extraction du ZIP.
2. Dans la racine confirmée du site tennis, copier d'abord `maintenance.active`, puis le nouveau `.htaccess`, après sauvegarde de l'ancien. **Le remplacement de `.htaccess` est une action sensible : il doit fermer le site à cette adresse.** Ne pas renommer le témoin à ce stade.
3. Vérifier dans une nouvelle fenêtre privée que la [racine du site](http://tclongages.daje3540.odns.fr/) et [la page des inscriptions](http://tclongages.daje3540.odns.fr/inscriptions.html) répondent toutes deux **503**. Pour lire le code, ouvrir les outils du navigateur (`F12`), onglet **Réseau**, désactiver le cache puis recharger. Si une page s'affiche normalement, arrêter le dépôt et faire vérifier la racine et les règles ; ne pas copier les pages de démonstration.
4. Une fois la fermeture constatée, copier les sept HTML et `robots.txt` dans cette même racine, directement, sans sous-dossier supplémentaire. Confirmer uniquement les remplacements déjà identifiés et sauvegardés.
5. Vérifier à nouveau le statut 503 sur les deux adresses. Garder `maintenance.active` présent jusqu'au moment choisi pour la présentation.

Ne pas déposer le projet complet, `.env`, `.local/`, `server/`, des exports privés ou le manifeste. Conserver le ZIP dans le dossier privé de préparation, pas dans la racine publique.

## 3. Ouvrir puis refermer la présentation

| Votre décision | Action dans la racine du site | Résultat attendu |
|---|---|---|
| Ouvrir la démonstration maintenant | Renommer `maintenance.active` en `maintenance.inactive`. | Les sept pages deviennent consultables par leur adresse. |
| Fermer la démonstration | Renommer `maintenance.inactive` en `maintenance.active`. | Les nouvelles requêtes du site répondent 503. |

Le renommage d'ouverture est **le moment où la démonstration devient publique**. Une fois ouverte, contrôler la vitrine, le menu et le formulaire fictif sur [Adhérer](http://tclongages.daje3540.odns.fr/adherer.html). Pour la présentation, utiliser les adresses directes : [Bureau](http://tclongages.daje3540.odns.fr/bureau.html), [Inscriptions](http://tclongages.daje3540.odns.fr/inscriptions.html), [Communication](http://tclongages.daje3540.odns.fr/communication.html), [Visite guidée](http://tclongages.daje3540.odns.fr/parcours.html) et [Aperçu des actualités](http://tclongages.daje3540.odns.fr/actualites-bureau.html).

Essayer une inscription fictive, la retrouver dans la gestion du même navigateur et vérifier que les actions de communication restent annoncées comme simulées. Charger l'affiche exemple, enregistrer et valider une actualité, puis vérifier ses quatre aperçus et sa conservation après rechargement ; cela ne doit déclencher aucun envoi externe. Examiner le compteur ADOC et le choix Ten'Up « Non » par défaut : une simulation ne modifie rien dans ces services. Contrôler également la photo du court, la FAQ tarifaire, le logo, le menu sur téléphone et l'absence de liens publics vers les outils du bureau.

Après la présentation ou la validation, **refermer avec `maintenance.active`**, puis contrôler de nouveau les statuts 503 sur `/`, `/adherer.html`, `/inscriptions.html` et `/communication.html` dans une nouvelle fenêtre privée, cache désactivé. Une page déjà chargée peut rester visible sur un appareil ; la fermeture ne supprime ni ces copies ni les essais enregistrés dans son navigateur. Si un cache serveur continue de servir une page, faire vérifier ce cache avant de considérer la fermeture comme effective.

Renommer seulement `index.html` ne ferme pas les pages accessibles directement. Supprimer ou modifier le DNS n'est pas nécessaire pour ce basculement et ne constitue pas un moyen de fermeture immédiate.

## Incident et retour à la vitrine précédente

Une erreur **500** peut signaler une incompatibilité des règles `.htaccess`. Ne pas la considérer comme une maintenance réussie : conserver le site fermé, ne pas ouvrir le témoin et demander un diagnostic. Ne pas simplement retirer `.htaccess` en laissant les sept HTML exposés.

Pour revenir à l'état sauvegardé, refermer d'abord avec `maintenance.active` et vérifier le refus des nouvelles requêtes. Retirer ensuite de la racine publique les seuls fichiers de démonstration introduits ou remplacés, vers un emplacement privé : en particulier les pages `adherer.html`, `bureau.html`, `communication.html`, `inscriptions.html`, `actualites-bureau.html` et `parcours.html`, selon l'inventaire sauvegardé. Restaurer les fichiers initiaux du site tennis, dont `index.html` et `robots.txt` s'ils existaient, et son ancien `.htaccess` en dernier. Si aucun `.htaccess` n'existait, retirer celui du paquet seulement lorsque les pages de démonstration ont quitté la racine publique. Retirer les témoins de maintenance de ce paquet puis vérifier la vitrine restaurée et l'absence d'accès aux pages de démonstration.

Ce retour arrière concerne uniquement le site tennis. Ne restaurer ni tout le compte cPanel ni les sites AVEREO/Drupal. Les [règles sur les commandes sensibles](commandes-sensibles.md) restent applicables à toute intervention qui serait ultérieurement confiée à l'agent.

## Limites de cette préparation

La [recette du paquet](recette-demo-o2switch.md) conserve les résultats et le périmètre exact des contrôles locaux, dont les essais Apache et ceux de la révision illustrée. Aucun accès cPanel, contenu distant, code de réponse Apache ou certificat de cette adresse n'est validé par ces résultats. Les tests 503, ouverture et fermeture restent à réaliser par l'utilisateur sur la cible. La migration du service réel, les comptes et HTTPS restent une étape distincte après le choix du domaine.
