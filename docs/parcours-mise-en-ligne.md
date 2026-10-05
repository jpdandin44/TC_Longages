---
project: TC_Longages
document_type: release-process
title: Parcours simplifié de mise en ligne V1
status: active
version: git
created: 2026-09-29
updated: 2026-10-05
owner: jpdandin
tags: [drupal, recette, preproduction, production]
---

# Parcours simplifié de mise en ligne V1

Le [suivi canonique](suivi-chantier/suivi-chantier.json) porte les quatre phases communes et leurs états. Le [registre opérationnel JSON](../data/parcours-mise-en-ligne.json) y référence ses dossiers et reçus ; il ne maintient plus un second statut de phase. Les huit phases antérieures et leurs décisions sont conservées avec leur mapping. Cette simplification du pilotage de la V1 publique ne réécrit pas une validation et n'autorise pas une intervention distante.

| Étape | Critère de sortie concret | État observé au 5 octobre |
|---|---|---|
| Cadrage | Périmètre et candidat exact identifiés ; lot d'hébergement concret. | V1 recentrée par le responsable sur le site public. Comptes Bureau/Capitaine et droits par équipe reportés en V2. |
| Développement local | Tests automatisés et parcours public/admin sur la version exacte ; maintenance et courriels vérifiés. | Paquet installé `49b4ef7` revérifié intégralement contre son reçu ; améliorations de la chaîne préparées sur une branche distincte. |
| Préproduction | Même candidat installé sous maintenance sur `preprod.tclongages.fr`, HTTPS valide, tests anonymes/admin et retour arrière éprouvé. | Drupal installé, français, SQL opérationnel ; sept pages relues en session administrateur. Sauvegarde intègre et 26 819 fichiers restaurés dans une copie privée ; restauration SQL et démarrage encore à éprouver. |
| Mise en production | Après recette et accord explicite sur l'action, sauvegarde restaurable, livraison du candidat exact sous maintenance, contrôle, puis accord distinct pour l'ouverture Drupal. | Préparation autorisée : même ZIP copié hors domaine, base dédiée créée et certificat gratuit reconnu sur les deux noms officiels. Mot de passe du nouvel utilisateur SQL en saisie personnelle. Racine `public_html` conservée ; aucune bascule ni ouverture. |

La première V1 comprend les sept pages publiques sous Drupal. La page « Espace » reste un écran d'attente ; aucun compte Bureau, formulaire Google, publication sociale ou collecte de contact n'est activé. Le contact public affiché est `tclongages@gmail.com` ; `support@tclongages.fr` reste prévu, sans boîte attestée.

Le responsable confirme ce périmètre le 5 octobre après avoir d'abord envisagé
les comptes dans cette livraison : **le site public en V1 aujourd'hui ; les
comptes et droits par équipe en V2**. Le travail d'édition locale reste conservé
sur `feat/drupal-comptes-edition` ; il ne fait pas partie du paquet hébergé.
Les contenus changent en V1 par livraison d'un nouveau candidat. Les écrans
Calendar, disponibilités et équipes indiquent encore leurs réserves ; le lien
Forms reçu ouvre une liste de formulaires et ne permet aucun raccordement réel.
Le lien Calendar reçu identifie un calendrier, mais son partage public n'est
pas qualifié. Ces ressources ne sont pas déclarées activées.

## Exécution de la V1 et effort restant

Le détail, les dépendances et les estimations sont conservés dans
`developmentWorkflow.deliveryPlan` du [suivi canonique](suivi-chantier/suivi-chantier.json).
Après la sauvegarde, la restauration des fichiers, le certificat et la copie
du paquet, estimation restante : **1 h 30 à 3 heures**, marge de correction
de 25 % incluse. L'estimation initiale de 3 à 5 heures est conservée dans
l'historique du suivi. Les
attentes d'accès, de validation humaine, de certificat ou de DNS s'ajoutent.
Ce délai n'est pas une garantie de livraison : un contrôle échoué exige une
correction dans son périmètre avant bascule. Aucun développement du cockpit
n'est requis pour conduire ces opérations.

1. Recetter les sept pages réellement hébergées et enregistrer l'acceptation
   du candidat exact. Conserver ses réserves fonctionnelles visibles.
2. Sauvegarder fichiers, paramètres et base de la préproduction actuelle,
   ainsi que la racine officielle actuelle. Restaurer le Drupal sauvegardé
   dans une copie privée distincte ; contrôler l'intégrité et le démarrage.
3. Préparer une installation de production séparée : même ZIP vérifié,
   base et paramètres propres, français, maintenance active et aucun courriel
   automatique. La racine de production définitive et les droits SQL sont
   à présenter après qualification ; aucune suppression de `public_html`.
4. Émettre puis contrôler le certificat gratuit de `tclongages.fr` et
   `www.tclongages.fr`. Vérifier PHP servi, racine, protection des paramètres,
   redirection HTTPS et retour à l'ancienne racine avant la bascule.
5. Après l'accord exact de livraison, basculer vers le candidat sous
   maintenance. Après recette de cette cible et accord d'ouverture, rendre
   les pages publiques, vérifier anonymement les sept parcours et conserver
   les reçus et la procédure de retour arrière.

L'adaptateur `first_install.py` est réservé à la première installation de
préproduction et refuse une cible de production. Il ne doit pas être relancé
sur la base existante ou détourné par modification du profil. L'adaptateur de
mise à jour, la restauration hébergée et la bascule réelle restent à qualifier.

La restauration des fichiers est effectuée par `scripts/restore_backup_files.py`,
dans un nouveau dossier privé, après contrôle des deux empreintes de sauvegarde.
Elle vérifie chaque fichier et n'atteste pas une restauration SQL.
`scripts/stage_production.py` a préparé et revérifié le ZIP sans écraser une
version existante ; ses fichiers restent accessibles uniquement au propriétaire.
`scripts/restore_production.py` est préparé pour importer le SQL dans la base
vide de production après la saisie personnelle et l'accord sur les droits.
Il contrôle le compte SQL et ses dix droits réels, refuse une nouvelle tentative
sur une cible partiellement importée et doit vérifier le démarrage du Drupal
restauré ainsi que celui du paquet de production. Sa recette hébergée reste à
effectuer ; ce script ne modifie ni la racine cPanel ni l'ouverture du site.

## Lot de préparation de production proposé le 5 octobre

Le responsable a autorisé ce lot dans la conversation le 5 octobre
(`TCL-PROD-PREP-20261005`). La copie du ZIP, la base et le certificat sont
préparés et vérifiés. L'utilisateur SQL est créé personnellement et ses dix
droits sont enregistrés sur cette seule base après confirmation
`TCL-PROD-SQL-20261005`. Le fichier privé et l'import restent à terminer.
Cet accord ne change pas la racine servie et n'ouvre pas Drupal.

| Élément | Cible exacte et effet |
|---|---|
| Compte | Compte principal TC `daje5127`, sans changement sur la lune ni AVEREO. |
| Code | ZIP déjà installé, SHA-256 `14c270431af12397eb882c1a3e029e05f4beaf5e2dfc0f728bbc31aafcf5f86a`, sous `/home2/daje5127/tcl-production/releases/14c270431af12397/drupal/`. |
| Racine future | `/home2/daje5127/tcl-production/releases/14c270431af12397/drupal/web`, non raccordée au domaine pendant cette préparation. |
| Paramètres | `/home2/daje5127/tcl-production/private`, hors de la racine web ; compte et mot de passe SQL distincts de la préproduction. |
| Base et utilisateur | Nouveaux `daje5127_tclprod`, réservés à cette cible. Mot de passe saisi et soumis personnellement par le responsable dans cPanel, puis conservé dans le fichier privé. |
| Droits SQL proposés | ALTER, CREATE, CREATE TEMPORARY TABLES, DELETE, DROP, INDEX, INSERT, LOCK TABLES, SELECT et UPDATE, uniquement sur `daje5127_tclprod`. Leur attribution exige confirmation au moment de l'action. |
| Données | Import du SQL de la sauvegarde privée `20261005T075955Z`, uniquement dans cette nouvelle base vide. Les données et l'administrateur de préproduction sont conservés ; aucune réinstallation Drupal. |
| HTTPS | Certificat gratuit de `tclongages.fr` et `www.tclongages.fr` seulement ; validation DNS du fournisseur, sans changement d'adresse DNS ni acceptation de conditions nouvelles. |
| Fermeture | Français, maintenance active, non-indexation et courriels désactivés pendant la recette de cette copie. |

La sauvegarde contient Drupal, ses paramètres privés, sa base et la page
d'attente actuelle. Son intégrité et la restauration des fichiers ont été
vérifiées ; la restauration SQL et le démarrage restent à éprouver avant
bascule. Le reçu technique est référencé
dans le suivi canonique. En cas d'échec, conserver les copies privées pour
diagnostic et laisser la racine officielle `public_html` et la préproduction
inchangées. Une émission de certificat réussie n'autorise ni bascule de racine,
ni ouverture, ni indexation. La bascule et l'ouverture feront l'objet d'un lot
exact présenté après la recette et le retour arrière testés.

Le [suivi HTML](http://127.0.0.1:4181/) présente les quatre phases, les revues et les observations réelles de déploiement. Ce guide décrit le parcours de livraison sans créer de nouvelle interface.

## Candidat de pages

`npm.cmd run drupal:public:build` génère d'abord la V1 de revue, puis ses sept pages destinées à Drupal dans `.local/drupal-public-candidate/site-pages/`, avec manifeste SHA-256 voisin. La transformation retire le bandeau « site en préparation » et la balise `noindex` de l'aperçu ; elle refuse une source inattendue. Elle ne copie rien sur l'hébergement. Les sept fichiers doivent être placés **hors de `drupal/web/`** dans `drupal/site-pages/` lors d'une future installation. Les dépendances viennent de `drupal/composer.lock`, jamais d'un dossier de recette privée copié tel quel.

La chaîne de livraison de référence est l'Action manuelle et le constructeur
Python décrits dans [le workflow de livraison](../workflows/preparer-livraison.md).
`npm.cmd run drupal:package` reste un ancien outil de préparation locale ; ne
pas le substituer au paquet GitHub déjà testé. L'archive ne contient ni base,
comptes, secret ou `settings.php` actif. Elle exige des paramètres propres à
chaque cible et n'est pas installable seule. Promouvoir le **même ZIP** et son
reçu vérifié ; une reconstruction produit un autre candidat à recetter.

Le module `tcl_site` impose `noindex` à défaut. Le drapeau d'environnement `TCL_PUBLIC_INDEXING=1` ne peut retirer cet en-tête que pour une réponse 200 sur les neuf chemins de vitrine autorisés ; connexion, erreurs et maintenance restent sans indexation. Sur la préproduction, garder ce drapeau absent et vérifier aussi `robots.txt` et l'accès effectif. Sur la production, la levée d'indexation est une **décision d'ouverture distincte**, après validation de la cible. La simple préparation des pages ne change pas la maintenance Drupal.

Le [modèle hébergé](../drupal/config/settings.hosting.example.php) exige `TCL_ENVIRONMENT=preproduction` ou `production`, avec hôtes de confiance différents. Il est inactif par défaut et ne contient aucun secret. Chaque cible doit disposer de sa propre base, de ses propres répertoires privés et de sa propre configuration hors Git. Les valeurs et versions réelles restent à qualifier ; ce document ne constitue pas une procédure d'installation validée chez o2switch.

## Décisions sensibles à présenter avant exécution

| Décision | Effet et risque | Retour arrière à préparer |
|---|---|---|
| Créer `preprod.tclongages.fr`, son dossier, sa base, HTTPS et ses accès | Rend la préproduction accessible depuis Internet ; une mauvaise cible ou permission peut exposer des données. | Vérifier les cibles exactes et sauvegarder la configuration antérieure ; retirer le sous-domaine et les accès après inspection si nécessaire. |
| Installer le candidat en préproduction | Écrit sur l'hébergement ; une racine ou une base erronée peut remplacer l'existant. | Sauvegarde préalable vérifiée et procédure de restauration testée. |
| Livrer en production sous maintenance | Remplace le site courant à `tclongages.fr`, toujours fermé aux visiteurs pendant les vérifications. | Sauvegarde fraîche restaurable du code, de la base et des fichiers ; retour à l'état précédent documenté. |
| Ouvrir le site et son indexation | Rend les pages publiques visibles et éventuellement indexables. | Réactiver la maintenance Drupal et `noindex`, puis vérifier depuis une session anonyme. |

Avant l'une de ces opérations, présenter la cible, l'effet, le risque et le retour arrière au responsable, et attendre son accord explicite. La PR, les tests locaux ou la création du sous-domaine ne valent pas cet accord. Les secrets ne sont ni demandés ni consignés dans la conversation.
