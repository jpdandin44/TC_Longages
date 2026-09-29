---
project: TC_Longages
document_type: installation-guide
title: Drupal dédié — installation locale et préparation de l’hébergement
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [drupal, maintenance, installation, hebergement, securite]
---

# Drupal dédié au TC Longages

## Candidat public et préproduction — préparation locale

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

Les [exigences PHP de Drupal](https://www.drupal.org/docs/getting-started/system-requirements/php-requirements), ses [exigences de base de données](https://www.drupal.org/docs/getting-started/system-requirements/database-server-requirements) et les [exigences Composer](https://www.drupal.org/docs/getting-started/system-requirements/composer-requirements) constituent les références avant toute nouvelle installation. Pour Drupal 11 : PHP 8.3 minimum, MySQL 8.0 ou MariaDB 10.6 minimum ; SQLite 3.45 minimum pour l’évaluation locale. La version réelle du moteur SQL hébergé reste **à vérifier**.

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

## Activer ou désactiver la maintenance

Après connexion, ouvrir `/admin/config/development/maintenance`. L’interface native est actuellement en anglais :

| Libellé Drupal | Sens et action |
|---|---|
| Put site into maintenance mode | Cocher pour afficher la maintenance aux visiteurs ; décocher pour rouvrir les routes du site. |
| Message to display when in maintenance mode | Texte affiché aux visiteurs pendant la maintenance. |
| Save configuration | Enregistrer la décision. |

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

Les tests locaux ne démontrent pas les règles Apache/cPanel, le certificat HTTPS, une restauration d’hébergement, l’envoi de courriels ou l’authentification métier réelle. L’interface administrative française, les comptes réels, les rôles Bureau/Capitaine, le périmètre des équipes et le raccordement éventuel à CONNECT restent à qualifier séparément.

## Préparation de l’hébergement — proposition inactive

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

La sélection PHP actuellement visible est globale pour le compte. Le [guide officiel o2switch du sélecteur PHP](https://faq.o2switch.fr/cpanel/logiciels/hebergement-php-multi-version/) confirme cet effet global. Le [guide officiel de sélection par dossier](https://faq.o2switch.fr/guides/php/changer-version-php-et-php-ini/) décrit une autre piste, via un gestionnaire `.htaccess`, mais sa syntaxe doit être confirmée pour cet hébergement et un essai incorrect peut exposer le code PHP. Ne modifier ni le réglage global ni le gestionnaire `.htaccess` sans inventaire des autres sites, sauvegarde et vérification sur un dossier isolé. La séparation dans un compte d'hébergement dédié reste une autre option à chiffrer et vérifier. La méthode retenue est **TBD**.

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
