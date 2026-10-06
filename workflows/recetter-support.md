---
project: TC_Longages
document_type: workflow-documentation
title: Recette automatisée du support V1
status: active
version: git
created: 2026-10-06
updated: 2026-10-06
owner: jpdandin
tags: [github-actions, support, recette, drupal]
---

# Recette du support V1

Le job `support-runtime` de `.github/workflows/ci.yml` installe le verrou
Composer dans un checkout Windows jetable, crée une nouvelle base SQLite,
installe le module `tcl_support` puis rejoue les tests métier PHP et HTTP
Python, dont la modification et la restauration des textes dans l'éditeur
Drupal natif des sept pages. PHP 8.3, Node et Python sont explicitement préparés par les Actions
figées déjà utilisées dans le projet. Les autres jobs restent présents.

Entrées : code de la PR, verrou Composer et pages V1 générées depuis leurs
sources. Aucun secret GitHub ni accès à cPanel n'est nécessaire.

Sorties : résultat des contrôles, reçus locaux dans `.local/` du runner.
Aucune session, base, demande ou courriel capturé n'est publié en artefact.
Le processus PHP créé pour le test est arrêté à la fin de l'étape.

Le contrôle de syntaxe inclut aussi les helpers CLI et les tests PHP.
Les détails métier et les commandes locales sont conservés dans le
[guide de support](../docs/signalements-support.md). Le job vérifie un runtime
SQLite local, pas la migration ni le courrier de la cible MariaDB o2switch.
L'état réel de sa première exécution est enregistré dans le reçu du candidat.

La livraison additive de préproduction utilise `package-support-update.py`
et `support-update-hosting.py`, depuis un commit et un ZIP identifiés. Les
entrées, sauvegardes, restaurations isolées, limites du transport et reçus
du serveur sont décrits dans le [guide d'édition](../docs/modifier-textes-drupal.md#sources-et-livraison).
Le contrôle SQL local vérifie que le préfixe de récupération modifie les noms
de tables sans changer les colonnes ni les valeurs. La restauration réelle
MariaDB reste une vérification distincte sur la cible avant application.
