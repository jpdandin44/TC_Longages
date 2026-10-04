---
project: TC_Longages
document_type: installation-guide
title: Drupal dédié — installation locale et préparation de l’hébergement
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [drupal, maintenance, installation, hebergement, securite]
---

# Drupal dédié au TC Longages

## Première installation sur le compte principal — état du 4 octobre

Le [lot d’hébergement](preparer-lune-tc.md#lot-du-compte-principal) est terminé
après accord : racine dédiée fermée, `preprod.tclongages.fr`, DNS public et
certificat reconnu, PHP CLI/HTTP 8.3.33 avec 18 extensions, base dédiée vide
UTF-8/InnoDB et dix droits relus. La connexion PDO avec l’utilisateur
applicatif a d’abord été refusée (1045), puis a réussi après synchronisation
personnelle du fichier privé enregistré à 19:57:33 UTC. Les archives et outils sont transférés,
leurs empreintes relues ; Python 3.6.8 a exécuté le plan et l’extraction de
26 687 fichiers avec contrôle intégral. Le helper PHP 8.3.33 passe le contrôle
de syntaxe. La qualification DNS/HTTPS/PHP HTTP est rafraîchie à 19:59:37 UTC
avant installation. Drupal 11.4.8 est installé à 20:00:15 UTC, avec un contrôle
neuf de la maintenance, des courriels neutralisés, de l’inscription réservée à
l’administration et du cron arrêté. Les pages publiques restent fermées par
la maintenance ; la [connexion native](https://preprod.tclongages.fr/user/login)
répond 200 en HTTPS. Le [reçu courant](../data/framework-revue-verification.json#primaryAccountFirstInstallation)
conserve ces preuves sans secret. La recette connectée et le retour arrière
ne sont pas attestés par cette seule installation.
La Lune antérieure reste conservée.

L’[adaptateur](../scripts/first_install.py) et le [profil inactif](../config/first-install.example.json)
sont adaptés au rôle `primary_tc`, au compte principal et à l’hôte exact
`preprod.tclongages.fr`. Une preuve de Lune, un autre compte ou l’hôte officiel
sont refusés. Quatorze tests passent sur Linux local ; deux contrôlent le
refus d’une identité de preuve différente et d’un mauvais utilisateur runtime
avant modification de la cible. Le ZIP Drupal construit sur `49b4ef7` est
conservé sans reconstruction. Les preuves des anciens outils de Lune restent
historiques ; aucun déploiement automatique n’est activé.

## Lot de première installation sur le compte principal

**Cible et version :** uniquement la préproduction dédiée du compte principal
TC, racine `tcl-preproduction/drupal/web`, base/utilisateur de suffixes
`tclpreprod`/`tcl`. Même ZIP non configuré source
`49b4ef7b975b8edff4308373732033630582da6b`, empreinte
`14c270431af12397eb882c1a3e029e05f4beaf5e2dfc0f728bbc31aafcf5f86a`.
Le candidat exact d’outillage et son ZIP privé sont identifiés dans le
[reçu de revue](../data/framework-revue-verification.json#primaryAccountInstallerAdaptation)
après gel des sources ; ils exigent leur revue humaine. L’accord de
configuration est distinct. L’accord de première installation
`TCL-PRIMARY-FIRST-INSTALL-20261004` est reçu et borné à cette cible et ces
artefacts ; il est conservé dans le reçu avec son message original.

**Effet proposé après accord :** transférer les outils revus et le ZIP en
privé via cPanel, relire leurs empreintes et rafraîchir la qualification de
cible ; extraire et relire tous les fichiers, conserver la racine vide initiale
puis placer Drupal sous refus Apache. Le responsable crée personnellement
le fichier privé de paramètres SQL/administrateur. Contrôler l’utilisateur,
base vide, UTF-8, moteur et versions ; installer le profil minimal puis
`tcl_site`. Un processus neuf vérifie maintenance, courriels neutralisés,
inscription réservée à l’administration et cron arrêté avant de rendre la
connexion native disponible pour la recette. Les visiteurs restent en
maintenance ; aucun espace Bureau ni service connecté n’est activé.

**Risque et récupération :** cette intervention écrit le code et initialise
la seule base neuve. Mauvaise cible, intégrité ou preuve expirée arrêtent le
processus. La racine initiale et les reçus restent en privé ; un échec conserve
le refus Apache et l’état pour analyse. Aucun effacement SQL ou répétition
aveugle. La restauration du Drupal et de SQL n’est pas encore éprouvée et
reste obligatoire avant production. Le fichier privé contenant le mot de
passe administrateur devra être nettoyé personnellement après conservation
des accès ; il n’est jamais lu par l’agent.

**Limites de ce lot :** pas de changement du domaine, DNS, certificat ou
racine officiels, pas de nouvel accès SSH, pas de transfert de compte/secret
AVEREO ou de Lune, pas d’ouverture publique ni de retrait de la Lune.
La résolution DNS ordinaire sur le poste reste à requalifier avant recette
personnelle. L’accord portant sur cette intervention et le candidat exact
est demandé après ses vérifications locales, avant toute écriture Drupal.

### Entrées et opérations

Le [profil d'exemple](../config/first-install.example.json) contient un compte
fictif et un accord vide : il ne permet aucune écriture tel quel. Son adaptation
privée doit porter le compte, l'hôte et les racines réellement observés, la base
et l'utilisateur dédiés, les empreintes du ZIP reçu et la référence de l'accord
de transfert/installation. Le lot de configuration précédent exclut ce transfert.
Vérifier également les empreintes des scripts transférés après leur revue.

Le reçu privé de qualification porte le même hôte, DNS, HTTPS reconnu, PHP HTTP
8.3 et fermeture réelle de la racine. Les sept champs `accountRole`, `account`,
`home`, `composerRoot`, `documentRoot`, `database` et `databaseUser` doivent
correspondre exactement au profil. Il exige une référence de preuve et un
horodatage UTC `YYYY-MM-DDTHH:MM:SSZ` datant de moins d'une heure à l'exécution.
Ne renseigner ces résultats qu'après essais observés.

```text
python3 first_install.py plan --profile PROFIL_PRIVE --archive ZIP_RECU
python3 first_install.py stage --profile PROFIL_PRIVE --qualification RECU_CIBLE --archive ZIP_RECU
python3 first_install.py install --profile PROFIL_PRIVE --qualification RECU_CIBLE
python3 first_install.py check --profile PROFIL_PRIVE --qualification RECU_CIBLE
```

`plan` relit provenance et empreintes sans modifier de cible. `stage` refuse
tout contenu autre que la fermeture Apache prévue et un `cgi-bin` vide. Il
extrait dans un dossier privé neuf, relit chaque empreinte, conserve la racine
initiale puis remplace seulement cette racine vide par deux renommages bornés.
Le reçu de tentative précède la bascule ; une interruption bloque une reprise
aveugle. Dépendances et sept pages dérivées restent hors du dossier Web.

Après `stage`, le responsable prépare et soumet personnellement
`hosting-input.json` dans le dossier privé, depuis le modèle sans secret créé
sur le serveur : mot de passe SQL existant, identifiant/courriel et nouveau mot
de passe administrateur Drupal. Garder ces accès dans son gestionnaire
personnel. Aucun secret dans le chat, Git ou les arguments de commande.
Les fichiers privés sont `0600`, leurs dossiers `0700` ; l'agent ne lit pas leurs
valeurs. L'entrée contenant le mot de passe initial administrateur reste
privée ; son nettoyage sera une action humaine distincte, après conservation
des accès, et n'est pas automatisé.

`install` contrôle connexion SQL, base/utilisateur, zéro table, version SQL,
UTF-8 et InnoDB avant configuration active. Il appelle l'[installateur non
interactif Drupal](https://api.drupal.org/api/drupal/core%21includes%21install.core.inc/function/install_drupal/11.x)
avec le profil minimal : administration initialement anglaise, pages publiques
françaises. Le collecteur du cœur neutralise les courriels avant disponibilité
de `tcl_site`. Puis sont configurés module TC, transport `tcl_null_mail`,
maintenance, inscriptions réservées à l'administration et arrêt du cron
automatique. Un processus PHP neuf vérifie ce résultat avant remplacement de
la fermeture Apache par les règles Drupal : visiteurs en maintenance,
`install.php` et `update.php` toujours refusés, connexion native disponible
pour la recette personnelle des pages sous maintenance.

### Reçus, récupération et limites

Les reçus portent candidat, outils, versions et états observés, sans secret
ni lien de connexion à usage unique. Un code retour nul seul ne suffit pas.
Les sorties brutes PHP ne sont pas affichées, car elles pourraient contenir
une entrée privée. En cas d'échec d'installation ou de sa vérification, Apache
reste fermé. L'outil ne supprime ni table, ni base, ni version extraite.

La racine initiale conservée hors Web ne prouve pas un retour arrière SQL.
Après installation, qualifier sauvegarde du Drupal, restauration dans une
copie privée puis retour arrière borné avant livraison. Restent les essais
HTTPS, maintenance anonyme 503, pages authentifiées, refus d'accès aux
paramètres/dépendances/pages dérivées et de PHP dans les fichiers publics,
journaux, ordinateur/mobile. La cible officielle, sa migration, sa livraison
et son ouverture ont leurs accords propres. Cet outil refuse la production.

La [revue Claude ciblée](../prompts/revue-premiere-installation-claude.md),
reçue avant ce changement de compte, est terminée avec Sonnet 5.5, effort Moyen, un échange. Ses conseils sont confrontés
au code : Drush 13.8.0 est bien dans le ZIP ; aucune suppression automatique
des tables proposée dans la réponse n'est adoptée. Les captures restent privées.

## Configuration du 3 octobre — historique conservé

Le lot de configuration a été autorisé et partiellement réalisé : PHP 8.3 appliqué, racine isolée créée avec fermeture Apache relue, base vide UTF-8 et utilisateur SQL dédié avec dix droits enregistrés. cPanel refuse `preprod.tclongages.fr` dans la lune parce que son domaine parent appartient au compte principal. Aucun DNS ni certificat n’est créé pour ce nom. Une adresse temporaire de la lune est proposée, non soumise ; son accord, DNS, HTTPS reconnu et PHP réellement servi restent à qualifier.

Le [reçu d’hébergement](../data/activation-lune-verification.json) est la source courante. Les inventaires datés plus bas restent historiques. Les [droits recommandés par Drupal](https://www.drupal.org/docs/getting-started/installing-drupal/create-a-database), plus `LOCK TABLES` pour une restauration Drush, sont limités à la base dédiée. La connexion applicative, InnoDB, le PHP servi et HTTPS restent à qualifier avant installation.

## Préparation GitHub du 1er octobre

Le [workflow de préparation](../workflows/preparer-livraison.md) reprend les composants génériques de la chaîne AVEREO. Il produit un candidat non configuré ou vérifie SSH seul. Il ne réalise ni première installation, ni import SQL, ni ouverture. Les prérequis et l'adaptateur de première livraison restent à qualifier ; le modèle de déploiement demeure inactif.

## Candidat public et préproduction — préparation locale

La [qualification PHP isolée](qualification-preproduction.md) prépare le test sans secret à réaliser avant tout code Drupal. Elle décrit son livrable fermé, ses préalables et le retour à la fermeture ; aucun test hébergé n'est déduit de sa préparation locale.

La [procédure de mise en ligne V1](parcours-mise-en-ligne.md) définit quatre étapes. `npm.cmd run drupal:public:build` produit les sept pages publiques candidates dans `.local/drupal-public-candidate/site-pages/`, hors Git et hors racine publique. La V1 de revue `officiel/` reste marquée `noindex` et ne doit pas être copiée telle quelle en production. Le modèle hébergé exige désormais `TCL_ENVIRONMENT=preproduction` ou `production` et applique les hôtes de confiance correspondants. Le module conserve `noindex` par défaut ; `TCL_PUBLIC_INDEXING=1` est réservé à une ouverture de production décidée et vérifiée. Ceci n'installe ni ne configure encore o2switch.

Le paquet produit par `npm.cmd run drupal:package` est un candidat de code avec dépendances et manifeste sous `.local/` ; il exclut `settings.php`, base, comptes et secrets. Un dépôt de ce ZIP seul ne crée pas un site utilisable. Le filtre d'indexation refuse explicitement son activation lorsque `TCL_ENVIRONMENT=preproduction` dans le modèle hébergé.

## État et périmètre

L’utilisateur a confirmé une installation Drupal **dédiée au club**. Une installation réelle fonctionne localement sur `http://127.0.0.1:4182`, avec la maintenance native activée à la fin de la recette. Elle ne constitue pas une installation sur o2switch ni une autorisation de publication. Le compte administrateur de recette est fictif ; aucun compte personnel, compte Bureau réel ou raccordement CONNECT n’est configuré ici.

Le [module local `tcl_site`](../drupal/web/modules/custom/tcl_site/tcl_site.info.yml) sert les sept pages V1 par des routes Drupal. Leurs copies générées résident dans `drupal/site-pages/`, **hors du dossier public `drupal/web/`**. Toutes passent ainsi par le contrôle natif de maintenance. Les variantes statiques et leurs archives antérieures restent conservées. Elles ne sont pas transformées à distance et leur ancienne logique de témoins reste propre à ces anciens paquets.

Le [framework](framework-developpement.md) reste un composant distinct : le mode maintenance de Drupal ne remplace ni ses validations, ni l’autorisation de publier, ni les droits métier futurs du Bureau. Il n’existe pas encore de raccordement entre le compte local Drupal et le tableau interactif du framework.

## Composants vérifiés

| Élément | Valeur vérifiée localement le 29 septembre |
|---|---|
| Drupal | 11.4.8, dépendances figées dans [composer.lock](../drupal/composer.lock). |
| Drush | 13.8.0, dépendance propre au projet. |
| Composer | 2.10.3, PHAR placé sous `.local/drupal-tools/` après vérification SHA256. |
| PHP | 8.4.23 ; configuration isolée, aucune modification du `php.ini` global. |
| SQLite | 3.53.2, pilote PDO activé dans la configuration locale. |
| Courriels | Transport `tcl_null_mail` sans envoi ; fonction PHP `mail` également désactivée localement. |
| Inscription publique | Désactivée ; création de comptes réservée à l’administration. |
| Indexation et cache | En-têtes `noindex, nofollow, noarchive` et `no-store`, y compris pour connexion et maintenance. |

Sources primaires consultées : [Drupal 11.4.8](https://www.drupal.org/project/drupal/releases/11.4.8), [Drush 13.8.0](https://github.com/drush-ops/drush/releases/tag/13.8.0), [Composer](https://getcomposer.org/download/). L’empreinte attendue de Composer 2.10.3 est conservée dans le [script de préparation](../scripts/drupal-local.ps1) ; aucune exécution n’a lieu si elle diffère.

Les [exigences PHP de Drupal](https://www.drupal.org/docs/getting-started/system-requirements/php-requirements), ses [exigences de base de données](https://www.drupal.org/docs/getting-started/system-requirements/database-server-requirements) et les [exigences Composer](https://www.drupal.org/docs/getting-started/system-requirements/composer-requirements) constituent les références avant toute nouvelle installation. Pour Drupal 11 : PHP 8.3 minimum, MySQL 8.0 ou MariaDB 10.6 minimum ; SQLite 3.45 minimum pour l’évaluation locale. La version SQL hébergée actuelle est observée dans le reçu du 4 octobre ; la connexion applicative reste à vérifier.

## Utilisation locale

Depuis `Site_Internet/`, dans PowerShell :

```powershell
.\scripts\drupal-local.ps1 status
.\scripts\drupal-local.ps1 start
```

`status` décrit le serveur local sans afficher d’identifiants. `start` ouvre exclusivement le port 4182 sur la boucle locale et refuse de remplacer un processus déjà présent. Si le serveur est déjà actif, conserver celui-ci. Pour l’arrêter :

```powershell
.\scripts\drupal-local.ps1 stop
```

L’arrêt vérifie l’identité du processus enregistré et ne touche pas aux autres serveurs.

Les accès sont : [site local](http://127.0.0.1:4182/), [connexion](http://127.0.0.1:4182/user/login) et [réglage de maintenance](http://127.0.0.1:4182/admin/config/development/maintenance). Ces adresses ne sont accessibles que sur ce poste.

Le fichier privé `.local/drupal-admin.json` contient l’identifiant et le mot de passe aléatoire du compte de recette. Le consulter uniquement sur le poste pour se connecter. **Ne pas copier son contenu dans une conversation, une capture, Git ou une archive à partager.** Il ne s’agit pas du futur compte personnel d’administration.

## Français et accès au site de recette

La préproduction est désormais réglée en français : modules natifs **Language**
et **Interface Translation**, avec la dépendance **File**, puis ajout de
**French** et sélection du français par défaut dans
`/admin/config/regional/language`. L’import natif a ajouté 10 480 traductions,
soit 99,92 % de l’interface recensée. La détection par URL est désactivée dans
`/admin/config/regional/language/detection` ; seule la langue sélectionnée est
active. Les adresses usuelles restent utilisables sans préfixe `/fr`.
Ce réglage est observé sur l’hébergement ; le profil minimal d’installation
et l’instance locale antérieure restent initialement en anglais.

Pour la recette, ouvrir [le site TC Longages](https://preprod.tclongages.fr/).
Pendant la maintenance, se connecter personnellement via
[la connexion Drupal](https://preprod.tclongages.fr/user/login), puis revenir
au site. Les sept pages sont parcourues en session administrateur ; ce contrôle
ne couvre ni les droits des autres comptes ni l’édition, encore absente du ZIP.
Le lien de recette des PR ouvre ce site ; le suivi reste un outil de pilotage.
Un contrôle HTTPS anonyme postérieur confirme accueil 503 et connexion 200,
en français et non indexables. Aucun compte ni permission n’a été modifié
pour ce réglage de langue ; la maintenance est restée active.

## Activer ou désactiver la maintenance

Après connexion, ouvrir `/admin/config/development/maintenance`. Les libellés
ci-dessous sont vérifiés sur la préproduction française ; l’ancienne instance
locale peut encore afficher leurs équivalents anglais :

| Libellé Drupal | Sens et action |
|---|---|
| Mettre le site en mode maintenance | Cocher pour afficher la maintenance aux visiteurs ; décocher pour rouvrir les routes du site. |
| Message à afficher en mode maintenance | Texte affiché aux visiteurs pendant la maintenance. |
| Enregistrer la configuration | Enregistrer la décision. |

La permission `access site in maintenance mode` permet à l’administrateur de parcourir le site pendant la maintenance. Elle ne doit pas être attribuée aux visiteurs anonymes. Les autorisations de gérer la configuration restent réservées à l’administrateur.

Après chaque bascule, vérifier l’effet dans une fenêtre privée sans connexion : la maintenance doit retourner **503**, l’ouverture doit afficher la V1. Le compte administrateur reste capable de se connecter pendant la maintenance. Source : [guide natif Drupal](https://www.drupal.org/docs/user_guide/en/extend-maintenance.html).

Il n’y a **aucun fichier à renommer** pour cette installation Drupal. Le réglage est conservé en base de données. La maintenance seule ne protège pas les anciens HTML ou des documents laissés dans la racine publique ; le [.htaccess officiel](https://raw.githubusercontent.com/drupal/drupal/11.4.8/.htaccess) laisse les fichiers existants être servis directement. C’est pourquoi l’arborescence Drupal publique doit rester propre.

## Reproduction sur un poste Windows propre

Préconditions : PHP 8.3+ et ses DLL requises, accès HTTPS aux sources officielles, code du projet disponible, V1 construite dans `officiel/`. Le script suppose que `php.exe` est accessible et que les extensions sont dans son sous-dossier `ext`. Le moteur Docker n’est pas nécessaire.

```powershell
npm.cmd run officiel:build
.\scripts\drupal-local.ps1 prepare
.\scripts\drupal-local.ps1 install
.\scripts\drupal-local.ps1 start
```

- `prepare` crée la configuration PHP propre au projet, télécharge Composer officiel si absent et vérifie son empreinte, génère un compte local aléatoire s’il n’existe pas et copie uniquement les sept pages V1 hors du webroot. Un compte existant est conservé.
- `install` installe les dépendances verrouillées, initialise SQLite et applique le module local, les courriels inertes et la maintenance. Il **refuse une base existante** : aucune réinstallation destructrice automatique.
- `start` lance le serveur local. La préparation ne publie rien sur Internet.

Les données SQLite, fichiers privés, fichiers temporaires et secrets restent sous `.local/`. Les fichiers publics de cache Drupal sont sous `drupal/web/sites/default/files/`. Ces sorties et les dépendances ne doivent pas entrer dans le dépôt ni dans un paquet public. Le [paramétrage local](../drupal/config/settings.local.php), avec ses chemins Windows et son SQLite, n’est pas une configuration o2switch.

Si `install` échoue, son diagnostic local expurgé est dans `.local/drupal-runtime/install-error.log`. Ne pas effacer une base existante pour contourner un échec : analyser l’état avant toute reprise.

## Recette et preuves

Le [test d’intégration](../tests/drupal-local.cjs) a exécuté **45 contrôles réussis** le 29 septembre 2026, au moyen d’Edge sans interface et de requêtes HTTP locales :

- fermeture des sept pages et connexion possible ;
- connexion du compte de recette et exemption administrateur pendant la maintenance ;
- ouverture par le vrai formulaire Drupal ;
- sept contenus identiques aux copies V1, en-têtes de non-indexation et absence de cache ;
- refus anonyme de la page de réglage, refus des POST publics, refus des chemins privés et anciennes pages ;
- réactivation dans Drupal puis retour des sept pages en 503.

Le résultat est dans `.local/drupal-http-results.json`, et la configuration vérifiée sans secret dans `.local/drupal-result.json`. Les PHP personnalisés et le script PowerShell ont passé leur validation syntaxique. Le manifeste Composer est cohérent avec son verrouillage ; son avertissement sur Drush figé exactement est un choix de reproductibilité, pas une erreur de résolution. L’audit Composer n’a remonté aucune alerte lors de cette installation.

Le test utilise Playwright installé et le navigateur Edge. Il ouvre puis referme **la démonstration locale uniquement** et restaure la maintenance dans son bloc de finalisation. Il ne fait pas partie d’une procédure de recette distante autorisée. Une nouvelle exécution locale peut se faire avec `node tests/drupal-local.cjs` lorsque Playwright est résolu par Node et le serveur local démarré.

Les tests locaux ne démontrent pas les règles Apache/cPanel, le certificat HTTPS, une restauration d’hébergement, l’envoi de courriels ou l’authentification métier réelle. L’interface administrative française est désormais vérifiée séparément sur la préproduction. Les comptes réels, les rôles Bureau/Capitaine, le périmètre des équipes et le raccordement éventuel à CONNECT restent à qualifier séparément.

## Préparation initiale de l’hébergement — historique du 29 septembre

L’[inventaire d’hébergement](../data/hebergement-inventaire.json) est la source canonique des constats et de leurs dates ; ne pas déduire l’état distant à partir du serveur local. La lecture cPanel du 29 septembre montre `tclongages.fr` sur `public_html` et aucun `preprod.tclongages.fr` configuré ; le sous-domaine ne résout pas non plus dans la lecture DNS. Les fichiers cachés ne sont pas inventoriés. PHP natif 8.1 est le défaut des trois domaines affichés et l’interface indique que l’isolation par domaine est désactivée par l’administrateur du serveur : une bascule globale pourrait affecter les autres sites du compte. Le certificat de `tclongages.fr` et `www` est autosigné. L’outil Let's Encrypt propose le domaine principal, mais aucun certificat n'est émis ; la préproduction n'a pas encore de certificat à qualifier. Aucune base ni utilisateur MySQL n’existe. JetBackup affiche une sauvegarde quotidienne du 29 septembre à 07 h 33, non téléchargée ni restaurée. Ce n’est pas encore une cible Drupal 11 qualifiée.

Arborescence **proposée**, sans création distante :

```text
<repertoire-compte>/apps/tclongages-drupal/
  composer.json, composer.lock, vendor/
  config/                  configuration serveur privée
  site-pages/              pages servies par les routes Drupal
  web/                     seule racine publique proposée
<repertoire-compte>/private/tclongages/
  files/                   fichiers privés
  temp/                    fichiers temporaires
  config-sync/             configuration exportée
```

La racine publique proposée est donc `<repertoire-compte>/apps/tclongages-drupal/web`, à la place de la cible actuelle `public_html`. La possibilité de changer la racine du domaine principal doit être confirmée dans cPanel ou auprès de l’hébergeur. Cette proposition ne prouve ni l’existence des répertoires, ni la disponibilité d’une commande de bascule. Aucun changement n’a été exécuté.

La sélection PHP actuellement visible est globale pour le compte. Le [guide officiel o2switch du sélecteur PHP](https://faq.o2switch.fr/cpanel/logiciels/hebergement-php-multi-version/) confirme cet effet global. Le [guide officiel de sélection par dossier](https://faq.o2switch.fr/guides/php/changer-version-php-et-php-ini/) décrit une autre piste, via un gestionnaire `.htaccess`, mais sa syntaxe doit être confirmée pour cet hébergement et un essai incorrect peut exposer le code PHP. Ne modifier ni le réglage global ni le gestionnaire `.htaccess` sans inventaire des autres sites, sauvegarde et vérification sur un dossier isolé. Le responsable indique désormais ne pas avoir accès aux lunes sur ce compte ; la page cPanel « Mon Univers Web » reste vide après une erreur de l'outil, sans permettre de qualifier le quota ou l'activation. La voie dédiée choisie précédemment est suspendue. La lecture des « Sous-domaines » confirme que `preprod.tclongages.fr` n'est pas créé et qu'un champ de racine documentaire distincte est proposé. Ce sous-domaine dans le compte actuel est une solution de remplacement à qualifier, sans changement exécuté à ce stade.

Une nouvelle lecture du sélecteur cPanel confirme trois domaines sur le PHP natif 8.1 du compte et l'isolation par domaine désactivée par l'administrateur. Pour choisir la cible de préproduction, deux voies restent à décider :

| Voie | Effet concret | Réserve avant exécution |
|---|---|---|
| Sous-domaine dans le compte actuel, avec PHP 8.3 limité à son dossier | Garde la démonstration et les autres domaines sur leur réglage actuel ; permet une préproduction sans déplacer `tclongages.fr`. | Le [gestionnaire par dossier d'o2switch](https://faq.o2switch.fr/guides/php/changer-version-php-et-php-ini/) est moins simple à exploiter. Un nom de gestionnaire erroné peut servir le code PHP en clair : préparer une racine vide, tester la réponse et ses extensions avant d'y placer Drupal ou un secret. |
| Sous-compte « lune » dédié au club | Isole l'environnement PHP et les fichiers du club du compte principal. [o2switch décrit cette isolation](https://faq.o2switch.fr/cpanel/o2switch/univers-web-sous-comptes/). | Création et identifiants propres, quota à vérifier. La future migration de `tclongages.fr` depuis le compte actuel exige de retirer puis recréer son rattachement et de revoir les éventuels courriels ; ne pas engager cette bascule avec la simple création de préproduction. |

Le PHP 8.1 actuel est inférieur au [minimum PHP 8.3 de Drupal 11.4](https://www.drupal.org/docs/getting-started/system-requirements/php-requirements). Aucun réglage PHP global, gestionnaire de dossier ou sous-compte n'a été changé. Le choix d'un sous-compte dédié est consigné dans les [décisions](../decisions.md) ; il ne crée pas encore la cible exacte et n'autorise pas le dépôt du candidat.

phpMyAdmin affiche **MariaDB 11.4.13** pour le serveur de données du compte, ce qui dépasse le minimum MariaDB 10.6 de Drupal 11. Cette observation qualifie la **version du moteur**, pas une future base : aucune base ni utilisateur SQL du club n'est créé, et son jeu de caractères `utf8mb4` reste à vérifier. Le PHP 8.4 affiché dans phpMyAdmin est celui de cet outil ; il ne remplace pas la version PHP 8.1 actuellement choisie pour les domaines du compte. Le sélecteur ne montre pas les extensions d'une version alternative avant de la sélectionner ; leurs disponibilités restent inconnues.

Le [modèle d’hébergement](../drupal/config/settings.hosting.example.php) est **inactif**, sans secret et non chargé par l’installation locale. Il refuse son activation et les paramètres manquants par défaut. Il prévoit un stockage MySQL/MariaDB dédié, des hôtes de confiance `preprod.tclongages.fr` pour la préproduction ou `tclongages.fr` et `www.tclongages.fr` pour la production, des dossiers privés hors webroot, aucune inscription libre et un transport de courriels inerte. Il refuse l’indexation en préproduction. Il ne qualifie pas la version SQL ni le certificat, et n’est pas un paquet prêt à déployer.

Les paramètres à fournir ultérieurement dans un canal privé sont `TCL_HOSTING_ENABLE`, `TCL_ENVIRONMENT`, `TCL_DB_HOST`, `TCL_DB_PORT`, `TCL_DB_NAME`, `TCL_DB_USER`, `TCL_DB_PASSWORD`, `TCL_HASH_SALT`, `TCL_PRIVATE_FILES`, `TCL_TEMP_FILES`, `TCL_CONFIG_SYNC`. `TCL_PUBLIC_INDEXING` reste absent sur préproduction et avant l’ouverture de production. Les valeurs sensibles ne sont pas à transmettre dans la conversation. L’activation des courriels, les comptes et la maintenance initiale se préparent séparément ; la maintenance doit rester un état Drupal modifiable depuis son interface.

## Interventions sensibles à présenter avant accord

| Intervention proposée | Effet concret et risque | Préparation du retour arrière avant accord |
|---|---|---|
| Sélectionner un PHP compatible et ses extensions | Rend Drupal exécutable ; un réglage commun peut affecter d’autres sites du compte. | Inventorier la portée exacte et conserver la configuration antérieure ; isoler le club si nécessaire. |
| Émettre/installer un certificat reconnu et activer HTTPS | Rend les connexions utilisables sur le domaine ; mauvaise cible ou redirection peut bloquer le site. | Valider DNS et noms couverts, conserver les réglages précédents et tester avant toute obligation HTTPS. |
| Créer la base et l’utilisateur SQL du club | Crée un stockage et des droits propres au site. | Privilèges limités à cette base, paramètres privés ; aucune suppression d’une base inconnue. |
| Installer les fichiers et initialiser Drupal | Écrit du code, une configuration et des données sur l’hébergement. | Inventaire des fichiers cachés, sauvegarde ciblée fichiers/base qualifiée et restauration testée avant remplacement. |
| Changer la racine publique du domaine | Oriente les visiteurs vers Drupal ; une erreur peut rendre le site indisponible ou exposer de mauvais fichiers. | Conserver la racine et les règles initiales, tester la nouvelle cible fermée et préparer le retour à l’ancienne. |
| Créer les comptes et attribuer les rôles | Accorde l’administration et, ultérieurement, l’accès aux espaces du Bureau. | Valider les titulaires et périmètres ; conserver une voie de récupération et de révocation. |
| Désactiver la maintenance sur le domaine | Rend les pages disponibles aux visiteurs. | Recette distante préalable, validation explicite d’ouverture, possibilité de remettre la maintenance depuis Drupal. |

Une présence de sauvegarde JetBackup n’atteste pas encore sa fraîcheur, son contenu utile ou une restauration réussie. Les contrôles de livraison du framework doivent être opérationnels avant l’ouverture. Les règles d’autorisation continuent de s’appliquer pendant l’installation.

Références hébergeur : [PHP et extensions](https://faq.o2switch.fr/cpanel/logiciels/hebergement-php-multi-version/), [Composer et distinction de la racine publique](https://faq.o2switch.fr/guides/php/heberger-application-symfony/), [restauration ciblée JetBackup](https://faq.o2switch.fr/cpanel/fichiers/sauvegarde-jetbackup/). Ces guides ne remplacent pas la qualification du compte du club.
