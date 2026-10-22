---
project: TC_Longages
document_type: workflow
title: Complément de présentation communications et agenda
status: active
version: git
created: 2026-10-19
updated: 2026-10-19
owner: jpdandin
tags: [preproduction, communication, agenda]
---

# Complément communications et agenda

Opérateur ponctuel `patch.py`, réservé à la racine de préproduction indiquée dans le code. Aucun accès à la production. Ne pas réutiliser cet opérateur pour un autre candidat.

Entrée : archive privée `communications-agenda-9cd0b5a47d51.zip`, source applicative `9cd0b5a47d5187fb2488ecee93eca2dbdf15a5c8`, SHA-256 `2aabd94fd014543844b5f44460f212e19f849048b9e14f4aaf5f6d7f3452cec6`. Les neuf fichiers proviennent des blobs Git de ce commit ; manifeste avec empreintes nouvelles et précédentes. Quatre nouveaux fichiers et cinq fichiers existants. L’archive originale est conservée localement et dans le dossier privé hébergé ; ne pas substituer une reconstruction.

Le paquet conserve Core, OAuth, paramètres privés, modèles éditoriaux et base. Aucune migration ni installation de dépendance. Le lien d’adhésion déjà corrigé est inclus dans son état actuel. Le contrôleur `tcl_site` est ajouté explicitement au périmètre : l’ancien paquet limité aux seuls modules Bureau/OAuth ne suffit pas pour cet agenda.

`prepare` refuse une source active inattendue, sauvegarde les fichiers concernés avec leurs modes, les restaure dans un dossier privé et compare leurs octets. Il contrôle la syntaxe PHP hébergée et conserve les empreintes des contenus, comptes, catalogue, expéditeur et protections. Ce retour arrière porte sur les neuf fichiers applicatifs ; aucune restauration de base n’est nécessaire à ce complément sans migration. Les sauvegardes complètes du lot précédent restent historiques.

Le rendu de `AgendaPresentation` préparé est également exécuté dans un processus CLI hébergé, sans remplacement des fichiers actifs : présence des commandes, conservation de l’iframe et idempotence vérifiées. Ce contrôle ne remplace pas la recette navigateur après installation.

Après réussite des contrôles locaux, CI et documentaires, et du contrôle de passage du protocole commun, `apply` vérifie de nouveau l’absence de dérive, installe les dépendances avant les contrôleurs, reconstruit le cache et compare les contenus/protections. En cas d’échec, il restaure les anciens fichiers et retire seulement les nouveaux fichiers de sa liste explicite. Aucun changement d’accès, de configuration ou envoi de message.

Sorties privées : `plan.json`, répertoires `backup`, `restored`, `candidate`, puis `receipt.json` après installation réussie. État réel et preuves : [vérification du complément](../../data/communications-agenda-verification.json), [reprise](../../docs/point-session.md). La campagne humaine et la gestion complète des événements restent distinctes de ces contrôles techniques.

## Résultat et correction des ressources

Paquet installé le 19 octobre à 21:24:57 UTC ; neuf empreintes comparées et contenus/protections conservés. Le contrôle navigateur a ensuite identifié des dossiers publics en 0700 : le script, les styles et le logo répondaient 404. `asset-permissions.py` a corrigé uniquement la racine `tcl_bureau` et `css` en 0755 après consignation des modes ; le dossier `images` a reçu la même correction, consignée séparément dans `logo-permissions.json`. Aucun changement des règles Basic ni des dossiers privés. Revenir aux modes antérieurs reproduirait le défaut d’accès aux ressources ; ne le faire que dans une restauration explicitement motivée.

Le code applicatif livré reste celui de l’archive qualifiée. Recette Chrome et protection anonyme 401 vérifiées après correction. Les fichiers supplémentaires de documentation/opération ne font pas partie du paquet applicatif.
