---
project: TC_Longages
document_type: workflow
title: Livraison des événements de recette et catégories
status: active
version: git
created: 2026-10-20
updated: 2026-10-20
owner: jpdandin
tags: [agenda, preproduction, restauration]
---

# Livraison agenda V2

Opérateur ponctuel `patch.py`, uniquement pour `/home2/daje5127/tcl-preproduction/drupal`. Base vérifiée `daje5127_tclpreprod`, Core hébergé 11.4.8. Aucun accès aux chemins ou bases de production, aucune écriture Google. Ne pas réutiliser pour un autre candidat.

Source : `02b4112dd95f1d11cf3ea1a23d1142316c15470f`. Archive privée `agenda-events-02b4112dd95f.zip`, SHA-256 `4b03648eec02e9c8ce1def3bef5351b4f5b3a1be6d89dbf3757607f0d9539d68`. Treize fichiers applicatifs extraits des blobs Git ; huit remplacés, cinq nouveaux. Le manifeste embarqué porte les empreintes précédentes et nouvelles. Aucune migration, aucune installation de dépendance ni envoi de message.

`prepare` vérifie chemins, protections Basic, version Drupal et base, refuse toute source inattendue, sauvegarde les octets et permissions des huit fichiers et les restaure dans une copie privée pour comparaison. Il contrôle la syntaxe PHP et les empreintes des contenus, adhésions, comptes, catalogue, événements et paramètres protégés. Les nouveaux fichiers sont vérifiés absents. Cette restauration porte sur les fichiers du lot ; aucune restauration de base n’est requise car l’opérateur ne migre ni ne remplace les données.

Après contrôles locaux/CI/documentaires et passage du contrôle commun, `apply` refuse toute dérive depuis la préparation, installe les fichiers, reconstruit le cache puis compare les empreintes et les données/protections. En cas d’échec, retour automatique aux fichiers sauvegardés et retrait des seuls nouveaux fichiers explicitement listés. Sorties privées : `plan.json`, `backup`, `restored`, `candidate`, `receipt.json`.

L’enregistrement d’événements fictifs pendant la recette est une étape distincte après livraison ; il modifie seulement la collection isolée du Bureau. Les communications associées ne sont pas publiées. [Fonctions et limites](../../docs/evenements-bureau.md), [point de reprise](../../docs/point-session.md).

## Correctif de recette

Opérateur distinct `fixes.py`, source `14cf8723e4ca811295427f94a1e41e8362246bea`, archive `agenda-fixes-14cf8723e4ca.zip`, SHA-256 `0d56f14dfe93c6600fdecdb9ea520d2e0300f103ffb7e4273e38b6e1efc9ad89`. Deux fichiers remplacés : AgendaPresentation et EventForm. Même contrôle de cible, sauvegarde/restauration privée et conservation des contenus/protections. Corrige le cache des scripts après suppression Ambiance et la restauration du service après aperçu. Cette installation complète le paquet initial ; elle ne le remplace pas sur une cible vierge.
