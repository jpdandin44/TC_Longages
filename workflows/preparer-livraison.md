---
project: TC_Longages
document_type: workflow-documentation
title: Préparation de livraison et composants o2switch réutilisables
status: in_progress
version: git
created: 2026-10-01
updated: 2026-10-05
owner: jpdandin
tags: [github-actions, drupal, o2switch, mutualisation]
---

# Préparation de livraison

## État réel et industrialisation — 5 octobre

Le parcours de la V1 publique et son effort sont dans
[le dossier existant](../docs/parcours-mise-en-ligne.md). Le processus commun
à quatre phases reste celui du skill `developpement-github-cockpit` ; ce
document décrit son raccordement TC, sans créer un second suivi.

Drupal est installé en préproduction, SQL fonctionne et l'administration est
en français depuis le 4 octobre. Les vérifications anonymes du 5 octobre
confirment maintenance et non-indexation. Les anciens refus SQL décrivent des
tentatives résolues. Production non livrée : certificat officiel désormais
reconnu, même ZIP préparé hors domaine et nouvelle base vide créée après
accord. Fichiers de sauvegarde restaurés et vérifiés ; utilisateur SQL dédié
créé, dix droits appliqués à sa seule base et encodage Unicode vérifié.
Fichier privé, restauration de base, démarrage et adaptateur de mise à jour
non qualifiés.
Le compte cPanel donne accès à Terminal ; aucun nouvel accès SSH n'est créé.

GitHub observé le 5 octobre : trois workflows actifs, dont la préparation
manuelle. Aucun environnement GitHub ni secret/variable de dépôt TC n'est
configuré. Le YAML de qualification SSH ne suffit donc pas à l'exécuter.
L'exemple `deployer.yml.example` reste inactif et appelle un adaptateur absent ;
il ne constitue pas une livraison disponible.

Le lot local `feat/livraison-fiable` ajoute :

- refus d'écraser un ZIP existant, y compris lors de constructions concurrentes ;
- publication locale du paquet seulement après sa vérification intégrale ;
- contrôle du reçu de préparation contre le SHA source, le SHA-256 du ZIP,
  celui du manifeste et l'inventaire réel ; refus d'une source modifiée ;
- tests des garde-fous de livraison et de première installation dans chaque PR
  sur Linux, plus syntaxe PHP propre au site ; vérification du reçu dans
  l'Action de préparation avant archivage.

Commande reproductible, en lecture seule, avant un transfert vers une cible :

```text
python scripts/prepare_delivery.py verify-receipt --archive CHEMIN/tc-longages-drupal.zip --receipt CHEMIN/delivery-receipt.json
```

Un reçu cohérent atteste les octets du paquet. Il n'atteste ni recette,
sauvegarde restaurée, accord humain, transfert ou ouverture. Les contrôles
`check-gate.py` du skill commun restent applicables et leurs preuves doivent
être contrôlées à la source.

Pour les versions suivantes : branche et PR cohérentes, tests puis merge
humain, construction unique depuis le verrou Composer, recette du ZIP exact
en préproduction, sauvegarde fraîche et restauration éprouvée, puis promotion
du même ZIP vers la production après accord. Les contenus, comptes, fichiers
téléversés et paramètres de production sont conservés. Une mise à jour ne
réinstalle jamais Drupal et ne remplace pas la base de production par celle
de recette. Les migrations et éventuels imports de configuration sont revus
avant application. Cette séparation suit la
[procédure Drupal](https://www.drupal.org/docs/updating-drupal/deploying-a-drupal-update).
Le transport récurrent et son adaptateur restent à terminer ; aucune
automatisation complète n'est annoncée.

### Sauvegarde privée du site installé

`scripts/backup_site.py` utilise le PHP qualifié et l'entrée PHP de Drush,
avec racines limitées au compte TC, paramètres gardés en privé et sortie
publique limitée à des empreintes. Il archive Drupal, les paramètres actifs,
les fichiers privés et la racine officielle existante ; il relit chaque
fichier du tar et le gzip SQL. Liens, fichiers spéciaux, source modifiée et
archives existantes sont refusés. Aucune restauration ni suppression n'est
exécutée par cet outil. Son reçu conserve `restorationTested: false` jusqu'à
une répétition réelle distincte. Garder le répertoire en 0700 et ses fichiers
en 0600 ; ne publier ni l'archive ni son inventaire privé dans GitHub.

### Restauration et préparation de la première production

`restore_backup_files.py` vérifie les empreintes SQL/tar contre celles du
reçu examiné, refuse les chemins sortants et liens, puis restaure chaque
fichier dans un nouveau dossier privé. Le 5 octobre, 26 819 fichiers ont été
restaurés et revérifiés sur l'hébergement ; aucun import SQL n'est déduit de
ce résultat. Le reçu de sauvegarde historique reste intact.

`stage_production.py` réutilise `verify-receipt` et exige les chemins, le
compte, la base et l'accord de préparation du lot TC. Il extrait puis relit
les 26 687 fichiers du même ZIP dans une nouvelle version propriétaire,
sans modifier cPanel ni la racine servie. Les paramètres SQL sont saisis
personnellement dans `tcl-production/private/hosting-input.json` ; aucun
nouveau compte administrateur n'est nécessaire pour le clone du site.

`restore_production.py` prépare la restauration de la sauvegarde du 5 octobre
dans cette seule base de production vide. Il exige la confirmation des droits,
contrôle les dix droits réels et la base vide, refuse une tentative déjà
commencée et les instructions SQL visant une autre base ou les accès serveur.
Le mot de passe reste dans un fichier client temporaire en 0600, sans argument
ni sortie publique. Il doit vérifier le démarrage de la copie restaurée et du
paquet de production : français, maintenance, courriels neutralisés, cron
désactivé et non-indexation. Sa recette hébergée est encore à effectuer.

Ces deux derniers outils concernent la **première production dans une cible
vide**. Ils ne constituent pas l'adaptateur de mise à jour d'une production
existante et ne doivent pas y être relancés. Ils ne basculent pas le domaine
et n'ouvrent pas Drupal. Le détail, les cibles et l'état de chaque action sont
dans le [parcours V1](../docs/parcours-mise-en-ligne.md) et le suivi canonique.
Les options du client SQL suivent la
[documentation MariaDB](https://mariadb.com/docs/server/clients-and-utilities/mariadb-client/mariadb-command-line-client).

## Complément local de première installation — 4 octobre

Le [vérificateur partagé](../scripts/delivery_shared.py) et le constructeur
restent la source de contrôle du ZIP reçu. Leur import ne dépend plus de la
directive Python apparue en 3.7 : la vérification est destinée au Python 3.6.8
observé dans cPanel ; la construction et SSH gardent leur environnement CI.
L'[adaptateur de première installation](../scripts/first_install.py) et sa
[procédure](../docs/installation-drupal.md) sont préparés à côté de cette
Action, sans changer les déclenchements ni activer de livraison automatique.
À cette étape de préparation, l'exécution MySQL/Drupal n'était pas qualifiée ;
elle est depuis réalisée en préproduction selon l'état courant ci-dessus.
Les sections datées antérieures qui mentionnent un adaptateur absent restent
l'état historique avant cette préparation.

Le responsable demande le 1er octobre de reprendre la mise en production en
réutilisant les Actions du **site Drupal AVEREO**. La chaîne de référence est
[Deploy AVEREO.fr au commit figé](https://github.com/jpdandin44/avereo-site-drupal/blob/38ff4b1f67b0bfc09158a08ba193c5584b22585c/.github/workflows/deploy-avereo.yml).
Sa provenance et ses empreintes sont dans le [reçu](../data/actions-mutualisees-verification.json).
Les scripts du monorepo d'applications statiques AVEREO ne constituent pas
l'adaptateur de ce Drupal. Aucun fichier du site AVEREO actif n'est modifié.

## Préproduction principale configurée — 4 octobre

Le [lot du compte principal](../docs/preparer-lune-tc.md#lot-du-compte-principal)
est autorisé puis terminé : PHP 8.3 CLI/HTTP, 18 extensions, racine fermée,
domaine/DNS public/HTTPS reconnu et SQL dédié vide, droits relus. Le
[reçu courant](../data/framework-revue-verification.json#hostingPrimaryConfiguration)
précise les contrôles et limites. La Lune reste conservée.

Dans la préparation antérieure à l'installation, l’outil est adapté au compte principal et au lien
exact des sept champs du reçu/profil. Son candidat est distinct du ZIP Drupal
reçu, qui n’est pas reconstruit. Transfert, paramètres privés, connexion PDO,
installation sous maintenance et recette exigeaient encore le lot correspondant.
La revue Claude précédente portait sur l’installation avant ce changement de
compte ; les nouvelles gardes sont contrôlées par tests Linux déterministes.
Aucun workflow de livraison, nouveau SSH ou ouverture automatique n’est activé.

## Fonctionnement préparé

La nouvelle [Action manuelle](../.github/workflows/preparer-deploiement.yml)
exige `main` et un `approved_sha` complet égal à la version exécutée. Elle
n'a aucun déclenchement sur push ou merge et demande seulement `contents: read`.
Sa présence sur une branche ou une PR ne prouve pas une exécution GitHub ;
un nouveau workflow manuel doit être intégré à la branche par défaut avant
son premier lancement. La revue et le merge restent humains.

| Opération | Entrées et effet | Résultat réellement attesté |
| --- | --- | --- |
| `build` — défaut | Commit examiné ; tests génériques, sept pages, Composer validate/audit/install depuis le verrou, ZIP intégralement relu. Aucun secret ou connexion serveur. | Candidat **non configuré**, sans base, comptes, paramètres privés ou autorisation d'ouverture ; empreintes ZIP/manifeste et inventaire. |
| `qualify-ssh` | Même commit ; profil TC explicite, environnement `tcl-preproduction`, confirmation `QUALIFY SSH TC Longages`, clé et hôte vérifié. | SSH et compte attendu seulement. Commande distante fixe `id -un` ; aucun transfert, installation ou changement de maintenance. |

Les deux opérations n'ont pas de matrice. Délais maximaux : 20 et 8 minutes.
L'attente du pare-feu est limitée à cinq minutes et concerne uniquement SSH.
Une préparation n'est pas annulée automatiquement par un lancement concurrent.
La rétention du candidat GitHub est de sept jours. Seuls les deux chemins
explicitement déclarés sont archivés ; jamais tout `.local/`, une sauvegarde,
une clé, une base ou un fichier de paramètres actif.

Le [constructeur portable](../scripts/prepare_delivery.py) utilise les sources
Drupal suivies par Git, les dépendances installées et les sept pages dérivées
depuis `.local/drupal-public-candidate/site-pages/`, sorties du constructeur
public. Il n'exige pas une ancienne copie locale dans `drupal/site-pages/`.
Les bibliothèques sont construites depuis `composer.lock`, sans mise à jour de
dépendance. Le ZIP comporte un manifeste exhaustif taille/SHA-256 ; tous ses
fichiers sont relus, CRC compris. Liens, chemins sortants, doublons, données
privées et limites de volume sont contrôlés. Les modes de fichiers sont fixés.
Les `settings.php` de fixtures de dépendances restent des fixtures ; le vrai
`web/sites/default/settings.php` et les fichiers de site actif sont refusés.

Construction locale après `npm.cmd run drupal:public:build` :

```text
python scripts/prepare_delivery.py build
python -m unittest discover -s tests -p test_delivery_shared.py
```

Un assemblage local avec sources modifiées indique `sourceClean: false` et
ne vaut pas qualification du commit. Sur GitHub, le constructeur exige un
checkout propre et le SHA demandé ; il s'arrête si une source a changé.

## Ce qui est mutualisé

La [bibliothèque générique](../scripts/delivery_shared.py) reprend et adapte
les garde-fous observés dans AVEREO : provenance, SSH strict et lecture complète
des archives SQL gzip/tar. L'[Action composite SSH](../.github/actions/qualified-ssh/action.yml)
est la source réutilisable ; elle ne contient aucun compte ou domaine de site.
Elle place les secrets temporairement sous `RUNNER_TEMP`, refuse les entrées
shell ambiguës, vérifie `known_hosts`, puis efface la clé même après échec.
Les tests comparent les deux dispositions Drupal, AVEREO avec `sites/` à la
racine et TC avec `web/sites/`. Le contrôle d'intégrité rend toujours
`restorationTested: false` : il ne prétend pas avoir restauré une base.

Un autre dépôt pourra appeler
`jpdandin44/TC_Longages/.github/actions/qualified-ssh@SHA_COMPLET_EXAMINÉ`, en
fournissant ses propres paramètres et secrets. La migration d'AVEREO vers
cette source est **proposée**, pas effectuée. Il n'y a pas deux copies locales
maintenues du nouveau composant ni de nouveau dépôt de composants créé.
Les adaptateurs Drupal, bases, sauvegardes/restaurations, chemins et contenus
restent propres au site ; aucun secret ou accord AVEREO n'est hérité.

## Revue de la PR et aperçu du site

La PR #5 a été fusionnée par le responsable le 3 octobre. Son lien
« l'environnement de validation » a été corrigé vers
[l'aperçu local](http://127.0.0.1:4180/), après ouverture et vérification de
celui-ci. Les quatre libellés obligatoires du modèle de PR et les coches
humaines ont été conservés ; seuls le lien et une explication adjacente ont
changé. La page GitHub de ce document sert à la revue documentaire.

Lancer `npm.cmd run officiel` sur le poste avant d'utiliser cet aperçu.
Il présente les sept pages publiques ; les comptes, les services connectés et
la recette du Drupal hébergé restent hors de sa qualification. Un lien local
ne constitue pas une préproduction distante.

L'Action de construction a réussi sur le commit fusionné `49b4ef7b975b8edff4308373732033630582da6b`.
Le ZIP reçu a été relu intégralement sur le poste et correspond au reçu GitHub.
Les empreintes et limites sont conservées dans le
[reçu de préparation](../data/actions-mutualisees-verification.json), rubrique
`postMergeBuild`. Cette construction ne configure ni ne déploie le site.

Le protocole commun GitHub et cockpit est raccordé au bloc
`developmentWorkflow` du [suivi canonique](../docs/suivi-chantier/suivi-chantier.json).
Les décisions historiques de phase restent distinctes des contrôles de livraison.

## Configuration SSH encore nécessaire

Après présentation et accord sur l'accès, configurer l'environnement GitHub
`tcl-preproduction` avec ces éléments. Aucun n'est enregistré par cette tâche.

| Nom | Type | Contenu attendu |
| --- | --- | --- |
| `TCL_SSH_PROFILE` | Variable d'environnement GitHub | JSON conforme au [modèle inactif](../config/ssh-qualification.example.json), avec référence de l'accord réel et serveur/compte/port examinés. |
| `TCL_SSH_HOST`, `TCL_SSH_USER`, `TCL_SSH_PORT` | Variables | Même cible que le profil ; aucune valeur AVEREO par défaut. |
| `TCL_SSH_KEY` | Secret | Clé autorisée pour cette intervention, fournie par canal sécurisé. |
| `TCL_KNOWN_HOSTS` | Secret | Clé publique d'hôte vérifiée indépendamment ; ne pas accepter automatiquement une clé obtenue au premier contact. |

Limiter l'environnement à `main` et configurer une revue humaine si l'offre
GitHub le permet. Le code n'annonce aucune protection déjà active. Une référence
d'accord dans le JSON est une trace à examiner, pas une signature authentifiée.
L'opérateur autorise uniquement l'IPv4 du runner indiquée au résumé du run,
puis retire uniquement cette exception. Aucun jeton cPanel ni règle générale
de pare-feu n'est créé. Les autorisations préexistantes sont conservées.

## Livraison et première installation : limites actuelles

Cette Action **ne déploie pas**. Le [modèle de déploiement](../.github/workflows/deployer.yml.example)
reste inactif : son adaptateur `scripts/run-delivery.py` n'existe pas encore.
Il ne faut ni le renommer ni annoncer son fonctionnement. Les scripts AVEREO
ne conviennent pas à la première installation TC : ils supposent un Drupal
et une base existants, une racine plate et des contenus natifs AVEREO.

Pour préparer l'adaptateur de livraison et le retour arrière du TC, il manque :

1. Compte isolé actif, PHP 8.3 sélectionné et racine fermée préparée ; le rattachement de `preprod.tclongages.fr` est refusé pour propriété du domaine parent. La proposition d'adresse technique est retirée ; qualifier l'implantation dans le compte principal désormais retenue dans le [plan corrigé](../docs/preparer-lune-tc.md), notamment PHP HTTP et HTTPS reconnu. La lecture cPanel du
   1er octobre a confirmé la racine officielle non vide, PHP 8.1, le certificat
   autosigné et huit lunes gratuites. Le [lot proposé](../docs/preparer-lune-tc.md)
   définit la suite ; PHP Apache isolé et certificat reconnu restent à qualifier.
2. Sauvegarde de l'état initial de la cible, intégrité et **restauration réelle**
   en copie privée. Pour une cible vide, preuve de son état et sauvegarde du
   périmètre de compte concerné ; ne pas substituer un reçu Drupal inexistant.
3. Bases et configuration privées dédiées, PHP compatible, installation du
   candidat sous maintenance et recette sur la préproduction réelle.
4. Candidat et dépendances qualifiés, copie exacte vers la cible de production,
   sauvegarde fraîche restaurée avant écriture, contrôle de la maintenance et
   répétition du retour arrière. Livraison et ouverture restent deux accords.

TBD — les paramètres de la future préproduction ne sont pas encore établis. La demande
de reprise ne transforme pas ces prérequis en vérifications réussies. Aucune
phase, décision humaine, livraison ou ouverture n'est créée automatiquement.

## Revue Claude et vérifications

Une seule revue a été demandée via **Computer Use**, dans « Revue technique Drupal
TC Longages », avec Sonnet 5.5 en effort Moyen. Le [prompt conservé](../prompts/revue-actions-mutualisees-claude.md)
ne contient aucun secret ou donnée d'adhérent. Il s'agit d'une revue de la
description technique, sans accès au dépôt ni exécution par Claude.

Les recommandations utiles sur SSH, archives, SHA et secrets distincts sont
intégrées. L'annulation automatique est écartée pour les opérations distantes ;
une empreinte publique d'hôte vérifiée n'est pas en elle-même un secret. La
création d'un dépôt supplémentaire et la migration d'AVEREO sont différées.
Résultats réellement obtenus, réserves et solde daté : lire le reçu lié au début.
