---
project: TC_Longages
document_type: workflow
title: Livraison du complément communication en préproduction
status: active
version: git
created: 2026-10-18
updated: 2026-10-18
owner: jpdandin
tags: [preproduction, communication, preuve]
---

# Livraison du complément communication

Ces opérateurs sont les sources exactes exécutées le 18 octobre, conservées pour audit. Ils ciblent exclusivement la préproduction TC et des chemins/candidats figés ; ne pas les rejouer pour une nouvelle livraison. Lire [le reçu](../../data/communication-pdf-canaux-verification.json) et [le guide](../../docs/communication-bureau.md).

Séquence : sauvegarde fraîche par les helpers privés existants, restauration des fichiers, `restore.py` pour la copie préfixée des 62 tables, `stage.py` pour le paquet contrôlé, `rehearsal.php` pour la migration et les essais privés CLI, contrôle de passage, puis `deliver.py prepare` et `apply`. L’installation finale conserve le Core 11.4.8, les pages, comptes, dossiers, actualités et catalogue. Elle ne charge aucune affiche personnelle sur l’hébergement.

Le retour arrière conserve les anciens fichiers dans `before-active`. La colonne additive `channels` peut rester présente si le code précédent est restauré : il l’ignore. Aucun écrasement de base active n’est nécessaire. Les secrets restent exclusivement dans les fichiers privés existants ; les scripts vérifient les empreintes et refusent la dérive de cible. Aucun accord de production ni qualification de connexion OAuth réelle n’est créé.
