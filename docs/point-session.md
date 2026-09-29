---
project: TC_Longages
document_type: session-handoff
title: Point de session et reprise du site
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [session, reprise, framework, drupal, git]
---

# Point de session — 29 septembre 2026

## Revue et dépôt

La [PR initiale no 1](https://github.com/jpdandin44/TC_Longages/pull/1) a été fusionnée par l’utilisateur le 29 septembre à 10 h 25 UTC ; le commit de fusion observé est `d4e0fdbaf9c05ca510202dad9233d944771118d1`. Les contrôles technique et de checklist ont réussi avant fusion. Cela intègre le socle local dans `main`, sans installation hébergée ni validation de phase déduite.

Le refus **« Requête extérieure refusée »** à l’ouverture du suivi depuis un lien externe a été reproduit : la garde locale bloquait aussi une navigation volontaire. Le correctif permet uniquement l’ouverture de l’accueil par un clic de premier niveau et maintient les refus sur les API, écritures et pages incorporées. La branche `fix/navigation-revue` porte ce correctif ; ses références de revue et résultats sont dans le reçu technique ci-dessous.

L’utilisateur demande de finaliser les PR et les contrôles de phases. Ses deux notes de revue sont conservées dans le [registre canonique](suivi-chantier/suivi-chantier.json). La saisie était déjà possible ; la remise et la validation étaient bloquées faute de PR, version source et preuves rattachées. L’interface explique désormais chaque étape, les causes de blocage et les critères enregistrés ; elle conserve le commentaire après enregistrement des critères et redemande une confirmation personnelle pour une décision.

Git local est raccordé au dépôt, avec préservation du commit initial. L’utilisateur a rendu le dépôt **public**. Les détails internes d’hébergement et chemins personnels ont été séparés des sources publiables, avec originaux privés conservés sur le poste. La protection de `main` a été enregistrée : PR obligatoire, checks `technical-ci` et `policy`, application aux administrateurs, discussions résolues et refus des envois forcés et suppressions. Voir [le fonctionnement du dépôt](preparer-depot.md).

Le [reçu technique](../data/framework-revue-verification.json) est la source des références effectives de PR, commit testé, tests et observation des protections. Le registre est la source des remises et décisions. Lire ces deux fichiers avant de conclure à un succès distant ou à une validation ; une description de procédure ne remplace pas un résultat. Le [candidat de revue](candidat-revue.md) distingue le commit source testé des reçus et vues ajoutés ensuite.

Les PR #1, #2 et #3 ont été fusionnées par l’utilisateur et concernent la phase 0. La #3 synchronise le suivi après la #2 ; elle n’a pas clos la phase. Le suivi interactif doit présenter les seules PR rattachées à la phase sélectionnée, avant les dossiers, et lire leur état public sur GitHub. La phase 0 demeure en revue tant que ses quatre critères et sa validation personnelle ne sont pas enregistrés ; le passage à la phase 1 demande une autorisation distincte.

Les contrôles GitHub exécutent les tests et la politique stricte de PR, sans accès de production. Les quatre cases humaines restent à renseigner par le responsable : `policy` échoue tant qu’elles ne sont pas confirmées. Ce contrôle porte sur les déclarations écrites, pas sur l’authenticité d’une signature ; aucune seconde revue GitHub impossible à obtenir avec un compte unique n’est imposée. Ni phase, ni merge ne sont acceptés à la place de l’utilisateur.

## Services locaux et sécurité

Le [suivi local](http://127.0.0.1:4181/) permet notes, critères, remise, validation et autorisation suivante distinctes. Son identité est déclarée sur le poste, sans authentification Drupal partagée. Les garde-fous Host/Origin, jeton, révision, sauvegarde et boucle locale restent actifs. L’exception d’installation TCL-EX01 est refermée : [installation.json](../framework/installation.json) reste `secured`.

Le [Drupal local](http://127.0.0.1:4182/) est laissé en maintenance, avec [connexion administrative](http://127.0.0.1:4182/user/login) et réglage natif `/admin/config/development/maintenance`. Identifiant de recette, SQLite et configuration privée restent sous `.local/`, hors Git et racine publique. Les rôles Bureau/Capitaine et les intégrations Google ne sont pas réalisés. Les [guides framework](installation-framework.md) et [Drupal](installation-drupal.md) donnent les commandes locales. Vérifier les processus à la reprise ; ne retirer un verrou qu’après avoir constaté l’arrêt de son propriétaire.

## Recettes et preuves

Les tests courants liés au candidat et les limites de leur périmètre sont inscrits dans le reçu technique. Les contrôles de transitions utilisent des fixtures isolées : aucune décision humaine de test n’est écrite dans le vrai suivi.

Les [115 tests Node et 45 contrôles Drupal de l’installation](../data/framework-interactif-verification.json), les [preuves d’adoption](../data/framework-verification.json) et son [point archivé](suivi-chantier/archives/2026-09-29-adoption-framework.md) sont des résultats historiques sans commit source de l’époque. Ils ne sont pas réétiquetés comme preuves d’un nouveau commit. La recette courante Node ne vaut pas un nouvel essai Drupal ou une qualification hébergée. L’archive V1 historique n’est pas devenue un paquet Drupal.

## Réserves d’hébergement et prochaine décision

L’[inventaire public expurgé](../data/hebergement-inventaire.json) conserve les constats utiles : domaine et racine observés, PHP natif 8.1, certificat autosigné, absence de base et un enregistrement JetBackup visible. La sauvegarde n’est ni téléchargée ni restaurée, les fichiers cachés et versions CLI/SQL restent à inventorier. L’original détaillé est conservé hors dépôt. Aucun changement PHP/SSL/DNS, création de base, transfert de site ou ouverture n’a été exécuté.

Terminer d’abord la revue de phase 0 depuis le suivi, puis décider séparément d’autoriser la phase 1. Pour l’hébergement, l’accord sur PHP 8.4 et un certificat reconnu reste à recueillir. Ensuite : sauvegarde restaurable, base dédiée, secrets saisis dans un canal adapté, racine Drupal propre, installation fermée et recette avant ouverture explicite. Le modèle `settings.hosting.example.php` est inactif. Rétention, restauration, droits métier, ressources Google et support restent à qualifier.

## Bilan documentaire

La politique documentaire globale est appliquée sans modification de son fichier global. Profil, registre, inventaire et reçu technique sont les sources structurées ; Markdown conserve procédures et décisions ; les deux tableaux de bord dérivent du suivi. Le reçu indique les vérifications réellement effectuées. Les limites d’hébergement restent des réserves ouvertes et ne sont pas résolues par la CI ou la création d’une PR.
