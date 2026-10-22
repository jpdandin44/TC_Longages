---
project: TC_Longages
document_type: workflow
title: Qualification et livraison des aperçus V2
status: active
version: git
created: 2026-10-19
updated: 2026-10-19
owner: jpdandin
tags: [preproduction, restauration, recette]
---

# Qualification et livraison des aperçus V2

Opérateurs exécutés le 19 octobre 2026 sur la préproduction dédiée uniquement. Ils portent des chemins et empreintes exacts, et ne constituent pas une procédure réutilisable sans nouvelle qualification. Aucun secret ni destinataire privé n’est versionné.

Entrées : sauvegarde `20261019T145855Z`, restauration `restore-13dh72qp`, paquet source `8b7ab6f` SHA-256 `95540db934f92867d01a7301e428bb91712bf0adc1a0a67a439896c225f4f48f`. Dépendances : PHP/Drush hébergés, MariaDB, Python, configuration privée préexistante. Aucune connexion à la production.

1. `restore.py` copie les 62 tables sous préfixe isolé et compare schémas/données ; `--hex-blob` préserve les données binaires. Un premier essai sans cette option a échoué au décodage avant import et a été remplacé.
2. `stage.py` vérifie le paquet puis l’installe seulement dans la copie privée.
3. `rehearsal.php` vérifie 20 cas de courriel avec collecteur, sans envoi réel.
4. `deliver.py prepare` vérifie les dérives et conserve les fichiers précédents ; `apply` installe le même paquet, restaure l’état de maintenance et vérifie contenus, empreintes et protection Basic. Son retour arrière restaure les fichiers antérieurs en cas d’échec.

Sorties privées : `v2-sql-receipt.json`, `v2-rehearsal.json`, `delivery-plan.json`, `delivery-receipt.json`. Résultats non sensibles dans le [reçu du lot](../../data/v2-apercus-recette-verification.json). Les scripts refusent les répétitions de plusieurs étapes : ne pas les relancer aveuglément.

Après livraison, les paramètres privés de courriel ont été ajoutés au runtime après sauvegarde, validation PHP et reconstruction du cache. Seules les clés `tcl_bureau_account_invitation` et `tcl_bureau_registration` passent à PhpMail ; liste de deux destinataires autorisés, aucun accusé au demandeur. Revenir au fichier privé `before-active/runtime-settings.php` puis reconstruire le cache désactive ce complément. Les valeurs privées restent hors Git.

Limites lors de cette livraison initiale : contrôle visuel hébergé et essais par formulaires réels non réalisés (Basic Auth indisponible dans le navigateur agent). Catalogue hébergé non publié ; publication explicite depuis le Bureau nécessaire. Aucun courriel réel envoyé, aucune réception prouvée.

Ces réserves initiales ont ensuite évolué : recette Chrome, catalogue publié et réception des deux courriels confirmés. Voir [l’état courant](../../docs/point-session.md) et [la preuve actualisée](../../data/v2-apercus-recette-verification.json).
