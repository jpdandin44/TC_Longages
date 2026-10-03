---
project: TC_Longages
document_type: session-handoff
title: Point de session et reprise du site
status: active
version: git
created: 2026-09-29
updated: 2026-10-01
owner: jpdandin
tags: [session, reprise, framework, drupal, git]
---

# Point de session — reprise au 1er octobre 2026

## Reprise du 1er octobre — Actions partagées

La demande de reprise annule l'arrêt de travail du 30 septembre, sans effacer son état historique ci-dessous. La [préparation de livraison](../workflows/preparer-livraison.md) décrit le composant SSH réutilisable, le constructeur de candidat et les limites de première installation. Le [reçu de préparation](../data/actions-mutualisees-verification.json) conserve les tests et constats avant envoi ; le [reçu de revue](../data/framework-revue-verification.json), rubrique `deliveryPreparation`, porte les résultats GitHub du candidat ensuite figé. Aucun merge, secret, domaine, certificat, base, copie distante ou ouverture n'est exécuté par cette reprise. L'utilisateur a reconnecté cPanel directement. L'[intervention suivante](preparer-lune-tc.md) présente les constats récents et la lune gratuite isolée à activer après accord. Aucun mot de passe n'est transmis dans la conversation.

## Clôture immédiate du 30 septembre

Le responsable demande l'arrêt pour changer de projet en urgence. La session est close : aucun déploiement, DNS, certificat, base, PHP distant ou ouverture n'a été exécuté. La revue Claude est terminée, la sonde locale et son archive sont conservées, les tests sont terminés et aucun service local démarré par cette session ne reste actif. L'onglet cPanel créé pour la connexion a été fermé ; l'état d'authentification après l'intervention du responsable et une déconnexion côté serveur ne sont pas vérifiés. Aucune étape humaine n'est validée par cette clôture.

Les modifications restent locales dans le checkout candidat, sans commit ni push ; les modifications antérieures sont préservées. Reprendre par le [reçu](../data/reprise-deploiement-verification.json), puis le [test de qualification](qualification-preproduction.md). Accès cPanel, racine exacte, sauvegarde restaurable, décisions d'écriture et réserve de crédits restent à qualifier. Le contrôle documentaire a identifié six références historiques absentes du checkout isolé ; vérifier leur emplacement et leur disponibilité avant publication documentaire. Cette réserve n'affecte pas l'exécution locale de la sonde, mais interdit de déclarer tous les liens documentaires accessibles.

## Reprise historique du 30 septembre avec le connecteur Claude

La demande du 30 septembre autorise la reprise de préparation avec Claude et l'optimisation des crédits. Une revue ciblée a été obtenue dans sa conversation « Redéploiement site TC Longages » via Computer Use, avec Sonnet 5.5 et effort Moyen, sans transmission de fichiers privés ou d'accès d'hébergement. Le [guide de qualification](qualification-preproduction.md) transforme cet avis en un test PHP sans secret, fermé par défaut et préparé localement. Le [reçu de reprise](../data/reprise-deploiement-verification.json) porte les résultats exacts et leurs limites ; les faits du 29 septembre ci-dessous restent historiques.

Le checkout isolé `feat/release-four-stages` est conservé au commit `6935a7da4507b16da7cdb6c9aa312a4ba8857e13`, avec modifications non commitées antérieures préservées. Aucune décision du suivi n'est créée ou réécrite. Les nouveaux fichiers et changements restent locaux, sans commit, push ou mise à jour de la PR. L'accès GitHub authentifié renvoie encore 401 ; la lecture publique a permis de vérifier la PR et ses checks.

La page de connexion cPanel est ouverte dans le navigateur intégré, sans session authentifiée. Pour poursuivre : s'y connecter directement, rendre l'hébergement accessible en lecture, vérifier racine et sauvegarde restaurable, puis présenter l'action exacte de qualification avant les écritures. Aucun test PHP distant, transfert, certificat, base SQL, ouverture de préproduction ou production n'a été réalisé. La réserve historique de 800 dépasse le solde de reprise ; la marge de remplacement proposée reste à confirmer.

## Reprise accélérée de la V1

Le responsable a simplifié le pilotage en quatre étapes et choisi `preprod.tclongages.fr`. La première V1 vise les sept pages publiques Drupal ; Bureau, services Google et publications automatiques restent fermés. Le [parcours](parcours-mise-en-ligne.md) et son [registre](../data/parcours-mise-en-ligne.json) sont distincts du suivi historique des huit phases, dont la validation de phase 0 et l'autorisation/démarrage de phase 1 ont été reprises sans réécriture. Le candidat de pages publiques est généré localement ; `npm.cmd run check` a réussi 132 tests sur 132, la syntaxe PHP modifiée a été vérifiée et une instance Drupal isolée a confirmé maintenance 503, connexion 200, sept pages 200 lors de l'ouverture locale et indexation restreinte. La maintenance a été réactivée. Un ZIP candidat local a été inventorié (34 017 entrées) ; son manifeste précise l'empreinte des pages et l'état de la source lors de chaque assemblage. Aucune préproduction, production ou ouverture publique n'a été exécutée. Le sous-domaine de préproduction ne résolvait pas lors de la lecture DNS du 29 septembre ; son état doit être revérifié avant intervention.

La lecture cPanel du 29 septembre a confirmé que `preprod.tclongages.fr` n'est pas créé, que PHP 8.1 est le réglage global et que la séparation par domaine est désactivée dans le sélecteur. Le domaine principal possède un certificat autosigné ; aucune base MySQL ni utilisateur n'est créé. Une sauvegarde JetBackup apparaît, mais sa restauration n'a pas été éprouvée. Le formulaire cPanel permet de proposer une racine documentaire propre au sous-domaine, sans que cette racine ait été créée. Le [guide Drupal](installation-drupal.md) expose les options PHP sans en choisir une avant qualification. La PR de préparation [#5](https://github.com/jpdandin44/TC_Longages/pull/5) reste un brouillon soumis à revue humaine ; elle ne déclenche aucun dépôt distant. Son premier contrôle technique a signalé un ancien manifeste de candidat ; celui-ci a été réaligné et le contrôle technique a ensuite réussi. Le contrôle de politique PR reste rouge tant que les cases réservées à l'utilisateur ne sont pas confirmées.

La relecture cPanel confirme que les trois domaines existants utilisent toujours le PHP 8.1 commun. Le [guide d'installation](installation-drupal.md) compare désormais la préproduction dans le compte actuel avec PHP limité à son dossier, et le sous-compte « lune » isolé proposé par o2switch. Le premier demande un test strict du gestionnaire PHP avant tout dépôt ; le second reporte une migration distincte du domaine principal. La PR #5 recentrée sur le site a un contrôle technique GitHub réussi sur `007a044` ; sa politique de revue reste volontairement en attente des confirmations du responsable. Aucun sous-domaine, certificat, compte, base ou fichier distant n'a été créé pendant cette relecture.

Le responsable a ensuite retenu le sous-compte « lune » dédié au club pour la préproduction. L'application « Mon Univers Web » n'a pas affiché son quota dans le navigateur intégré, même en affichage ordinateur ; la disponibilité d'une lune reste donc à constater dans cPanel. Son éventuelle activation exigera la création d'un accès propre par le responsable, sans transmettre le mot de passe dans cette conversation. Le choix n'est pas une autorisation de transfert du domaine principal ni de publication.

Une recette navigateur supplémentaire a ouvert les sept pages de l'instance Drupal isolée à 390 et 1 280 pixels : chaque page possède son titre principal et aucune n'a présenté de débordement horizontal selon la largeur du document. Les sept fichiers servis correspondent aux empreintes de l'archive locale. Le formulaire de contact reste un aperçu sans `action` d'envoi ni appel `fetch` dans le HTML vérifié. La maintenance Drupal a été rétablie ; un accès anonyme à l'accueil a de nouveau répondu 503. Cette recette technique ne vaut pas validation de la présentation par le responsable du club.

Une lecture supplémentaire de phpMyAdmin indique MariaDB 11.4.13 : la version du serveur SQL convient au minimum exigé par Drupal 11. Le PHP 8.4 de phpMyAdmin ne prouve pas que les domaines du club l'utilisent ; leur sélecteur reste en 8.1. Aucune extension alternative n'a été activée et aucune base n'a été créée. Le jeu de caractères de la future base reste à vérifier.

Le responsable précise que la création d'un second tableau de suivi ne faisait pas partie de sa demande. La page expérimentale `/parcours` est retirée de la branche candidate ; les quatre étapes restent décrites dans le registre et le guide, et le suivi HTML existant reste consacré aux décisions historiques. Le prototype G1 joint le 29 septembre a la même empreinte SHA-256 que la source déjà inventoriée le 24 septembre. Son contenu est une référence visuelle antérieure : il conserve l'adresse FFT et ne contient pas les coordonnées ni le logo fournis ensuite. La variante officielle locale reprend son identité et ses accès rapides, avec les corrections ultérieures. Le site de démonstration distant n'a pas pu être comparé directement : le navigateur signale un certificat non reconnu et la lecture web ne récupère pas la page. Aucune écriture hébergée n'a été effectuée.

L'archive Drupal candidate a été relue sans dépôt : ses 34 017 entrées passent le contrôle CRC ; aucun chemin de traversée, lien symbolique, `.env`, base SQLite, identifiant local, route de bureau ou `settings.php` actif n'y a été trouvé. Trois fichiers nommés `settings.php` appartiennent uniquement à des fixtures de test de Drupal et d'une dépendance ; ils ne sont pas chargés comme configuration du site. Le diff Git depuis le commit source inscrit dans l'archive ne montre aucune modification sous `drupal/`. Cette vérification structurelle ne démontre pas encore le comportement Apache de l'hébergeur.

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

La phrase historique qui demandait de terminer la revue de phase 0 est dépassée : l'utilisateur a depuis validé cette phase et autorisé/démarré la phase 1. Pour la V1 actuelle, suivre le parcours simplifié. Sur l’hébergement, la version PHP compatible, la base SQL et un certificat reconnu restent à confirmer. Ensuite : sauvegarde restaurable, base dédiée, secrets saisis dans un canal adapté, racine Drupal propre, installation fermée et recette avant ouverture explicite. Le modèle `settings.hosting.example.php` est inactif. Rétention, restauration, droits métier, ressources Google et support restent à qualifier.

## Bilan documentaire

La politique documentaire globale est appliquée sans modification de son fichier global. Profil, registre, inventaire et reçu technique sont les sources structurées ; Markdown conserve procédures et décisions ; les deux tableaux de bord dérivent du suivi. Le reçu indique les vérifications réellement effectuées. Les limites d’hébergement restent des réserves ouvertes et ne sont pas résolues par la CI ou la création d’une PR.
