---
project: TC_Longages
document_type: hosting-qualification
title: Qualification isolée de la préproduction Drupal
status: active
version: git
created: 2026-09-30
updated: 2026-10-04
owner: jpdandin
tags: [drupal, o2switch, php, preproduction]
---

# Qualification isolée de la préproduction

## Qualification effective du compte principal — 4 octobre

Le [lot principal](preparer-lune-tc.md#lot-du-compte-principal) est autorisé
séparément et effectué. Le [reçu courant](../data/framework-revue-verification.json#hostingPrimaryConfiguration)
porte les observations : certificat de `preprod.tclongages.fr` seulement,
émis par DNS-01, PHP HTTP/CLI 8.3.33 et 18 extensions chargées.

La sonde générée depuis le verrou est copiée sous refus Apache, relue par
empreinte puis validée syntaxiquement par le PHP hébergé. Le modèle de test
est activé uniquement pendant le contrôle, avec sauvegarde privée du refus.
Depuis le serveur, HTTPS vérifie nom et chaîne sans exception : GET 200 avec
JSON attendu, HEAD 200 sans corps, POST 405, en-têtes noindex/no-store.
`/`, `/.htaccess`, `/.env`, `/install.php` et `/private/hosting-input.json`
restent en 403. Le refus initial est restauré dans la finalisation, puis
sonde et robots sont déplacés dans le dossier privé. Le webroot contient
seulement `.htaccess` et le `cgi-bin` vide créé par cPanel ; la sonde répond 403.

Un client Windows confirme HTTPS strict et les 403 finaux en épinglant
l’adresse qualifiée du serveur. Le DNS public Google répond correctement et
le test sur le serveur utilise sa résolution normale. Le résolveur ordinaire
Windows échoue encore ; sa cause reste inconnue. Ce résultat ne qualifie pas
encore une navigation personnelle normale depuis le poste.

La base vide UTF-8/InnoDB et les dix droits sont relus par SSO phpMyAdmin.
La sonde n’utilise aucun mot de passe ni connexion SQL ; PDO applicatif et
Drupal hébergé restent à qualifier lors du lot distinct d’installation.
Les propositions et constats suivants sont historiques.

La [lecture du 1er octobre](preparer-lune-tc.md) résout le blocage d’affichage des lunes et présente la reprise de l’option isolée. Le présent test PHP reste conservé ; ses preuves du 30 septembre sont historiques et ne qualifient pas l’hébergement actuel.

Ce dossier prépare la première intervention de qualification du [parcours V1](parcours-mise-en-ligne.md). Aucun fichier n'est envoyé par sa génération. Les constats de reprise et vérifications locales font autorité dans le [reçu](../data/reprise-deploiement-verification.json) ; les observations cPanel du 29 septembre restent datées dans l'[inventaire](../data/hebergement-inventaire.json).

## Revue avec Claude

À la demande du responsable, une revue ciblée a été obtenue via Computer Use dans la conversation Claude « Redéploiement site TC Longages », avec Sonnet 5.5 et effort Moyen. Le [prompt de référence](../prompts/revue-deploiement-claude.md) conserve le périmètre. Aucun secret, fichier privé, accès cPanel ni dossier d'adhérent n'a été transmis. Claude a fourni un avis, sans commande ni déploiement ; il indique ne pas avoir rouvert les guides officiels. Ses recommandations ont été confrontées au dépôt et au [guide officiel o2switch](https://faq.o2switch.fr/guides/php/changer-version-php-et-php-ini/).

Le verrou Composer et son contrôle de plateforme exigent PHP 8.3.0 au minimum. La sonde dérive les extensions obligatoires des dépendances verrouillées, puis ajoute `pdo_mysql` pour la cible MySQL/MariaDB. Elle ne remplace pas `composer check-platform-reqs`, les contrôles CLI, la qualification SQL ou une recette Drupal.

Trois suggestions de l'avis ne sont pas adoptées : un certificat non reconnu ne qualifie pas HTTPS ; un constat d'impossibilité de restaurer ne remplace pas une restauration éprouvée ; aucun dépôt de sonde dans la production n'est prévu. La sonde ne contacte pas SQL et ne peut donc pas en déterminer la version.

## Livrable local fermé

Depuis le checkout candidat, `npm.cmd run hosting:probe:build` crée `.local/qualification-preproduction/` :

- `web/.htaccess` : refus de toutes les requêtes avec Apache 2.4 ; configuration active par défaut.
- `web/.htaccess.test.example` : modèle inactif permettant uniquement `tcl-probe.php` et `robots.txt`, avec le gestionnaire PHP 8.3 documenté par o2switch.
- `web/tcl-probe.php` : JSON contenant version PHP, SAPI, présence des extensions et trois limites PHP ; aucun secret, chemin privé, connexion SQL ou `phpinfo()`.
- `web/robots.txt` : refus d'exploration de tout l'hôte.
- `manifest.json`, **hors de `web/`** : exigences dérivées et empreintes des fichiers.

La copie active de `.htaccess` doit rester celle qui refuse l'accès jusqu'au test explicitement autorisé. Le modèle de test n'autorise aucune ouverture de Drupal et ne doit jamais remplacer le `.htaccess` d'un site existant. Les directives d'exploration et d'indexation ne sont pas une protection d'accès.

La sonde répond 200 lorsque PHP et toutes les extensions contrôlées conviennent, 503 sinon, 405 aux méthodes autres que GET/HEAD. HEAD ne renvoie aucun corps. Une réussite locale décrit le poste ; elle ne valide pas le gestionnaire PHP hébergé ni Apache. Le [guide Drupal](installation-drupal.md) conserve la procédure d'installation ultérieure.

## Proposition initiale de test — historique du 30 septembre

**Cible proposée :** `preprod.tclongages.fr` dans le compte actuel, vers un **nouveau dossier vide hors `public_html`**, destiné à recevoir seulement les quatre fichiers de `web/`. Le chemin absolu du compte et la racine effective restent **TBD — à confirmer dans cPanel**. Le candidat Drupal, le manifeste et ses fichiers privés ne sont pas déposés lors de ce test.

**Effet :** créer un hôte de qualification isolé, initialement fermé, puis tester PHP 8.3 dans son seul dossier. La création peut ajouter un enregistrement DNS ; son effet exact et l'absence d'effet sur les autres domaines doivent être vérifiés dans l'interface. La sélection PHP globale n'est pas modifiée.

**Préalables :** session cPanel accessible, inventaire des fichiers cachés et des cibles, sauvegarde fraîche conservée hors webroot avec restauration éprouvée, racine exacte et vide confirmée, décision explicite sur les écritures, DNS, certificat et accès temporaires. La création et la restriction de cet accès restent des actes à valider au moment de leur exécution. La présence d'une sauvegarde JetBackup seule ne satisfait pas le préalable de restauration.

**Risque :** un gestionnaire PHP inexistant peut servir le fichier en clair. La sonde ne contient aucun secret, mais l'essai doit réussir avant tout code Drupal ou configuration privée. Une mauvaise racine peut remplacer l'existant ; vérifier le chemin et son contenu avant chaque copie. HTTPS doit être validé sans contourner un avertissement avant toute connexion administrative ou utilisation de secret.

**Contrôle borné :** constater d'abord le refus de l'hôte et des fichiers directs ; conserver une copie privée de la configuration fermée ; activer le modèle de test uniquement dans la racine vide confirmée ; lire le JSON et vérifier absence de code PHP en clair, version minimale, extensions, SAPI et en-têtes. Une réponse 200 sans JSON attendu ne constitue pas une réussite. Les autres chemins et les fichiers cachés doivent rester refusés. Aucune connexion SQL n'est tentée.

**Fermeture et retour arrière :** remettre immédiatement la configuration `.htaccess` de refus, vérifier le refus de la sonde et des routes directes. Retirer la sonde et les ressources de test uniquement après vérification du chemin et de l'accord applicable ; conserver les preuves dans le canal privé adapté. Une suppression du sous-domaine, du dossier ou d'une entrée DNS doit respecter la confirmation requise au moment de l'action. Aucune restauration ne doit écraser une autre application du compte.

## Passage ultérieur à Drupal

Après cette qualification : certificat reconnu, PHP web et CLI/Composer compatibles, ressources privées inscriptibles et non servies, base dédiée et paramètres via canal sécurisé, choix de configuration privée compatible avec le modèle actuel, intégrité du candidat, installation avec module `tcl_site`, accueil `/club`, comptes explicitement décidés et maintenance native avant toute requête anonyme. Vérifier les sept pages, les erreurs, GET/HEAD, connexion, courriels neutralisés et noindex en préproduction. Tester le retour arrière. La recette de préproduction, la livraison en production et l'ouverture publique demeurent des décisions séparées.

## Limites à la clôture du 30 septembre — historique

La session est close à la demande du responsable le 30 septembre. L'onglet de connexion cPanel a été fermé ; l'état de la session serveur après sa tentative de connexion reste non vérifié. À la reprise, le responsable devra rendre la session accessible, sans transmettre de mot de passe au chat. Le DNS et la consultation publique GitHub ont été relus ; aucune qualification cPanel, Apache distant, SQL, restauration ou connexion administrative distante n'a été exécutée. Les réserves et résultats exacts sont dans le reçu ; aucune étape humaine n'est validée par cette préparation.
