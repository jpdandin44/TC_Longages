---
project: TC_Longages
document_type: readme
title: Site et communication du Tennis Club de Longages
status: active
version: git
created: 2026-09-16
updated: 2026-10-04
owner: jpdandin
tags:
  - site-internet
  - prototype
  - communication
---

# Tennis Club de Longages

## Mise en ligne V1 — parcours actuel

Le lot du compte principal TC est explicitement autorisé puis terminé le
4 octobre : préproduction dédiée fermée, DNS public, certificat reconnu,
PHP CLI/HTTP 8.3.33 et 18 extensions, base vide UTF-8 et dix droits SQL relus.
Le PHP est partagé au compte ; la page d’attente officielle est conservée.
La Lune reste active, avec ses ressources. Le [reçu courant](data/framework-revue-verification.json#hostingPrimaryConfiguration)
porte les résultats datés de configuration ; les preuves d’installation
et de connexion applicative sont désormais distinctes.

Après l’accord de première installation et la synchronisation personnelle
du mot de passe SQL, **Drupal 11.4.8 est installé en préproduction** avec le
ZIP source `49b4ef7` inchangé. La [connexion HTTPS](https://preprod.tclongages.fr/user/login)
est disponible ; les pages publiques restent en maintenance et non indexables.
Le [site de recette](https://preprod.tclongages.fr/) affiche les sept pages du
club dans la session administrateur connectée. Ce site est la destination du
lien « environnement de validation » des PR ; le suivi reste réservé au pilotage.
L’administration et la connexion Drupal sont désormais en français.
Les courriels sont neutralisés et l’inscription libre désactivée. Le
[reçu d’installation](data/framework-revue-verification.json#primaryAccountFirstInstallation)
porte les résultats réels. L’accueil et les six autres pages ont été parcourus
en session connectée. Recette des comptes et droits, édition des pages et rôles métier,
sauvegarde/restauration puis retour arrière restent à traiter avant production.
Le [point courant](docs/point-session.md) porte la reprise exacte ; le domaine
officiel et la Lune sont conservés.

La reprise du 3 octobre confirme la fusion humaine de la [PR #5](https://github.com/jpdandin44/TC_Longages/pull/5). L'Action de construction a réussi sur le commit de fusion ; le ZIP reçu a été vérifié intégralement sur le poste. Le [reçu de préparation](data/actions-mutualisees-verification.json), rubrique `postMergeBuild`, identifie ce candidat non configuré. Aucun déploiement ne découle du merge.

La première lune gratuite est désormais active, après renouvellement des sauvegardes et restauration en copie privée : une lune active, sept restantes. Le responsable a créé et soumis son mot de passe directement dans cPanel. Le [reçu d'hébergement](data/activation-lune-verification.json) conserve ces preuves et la clôture historique ; l’état de configuration est décrit ci-dessous.

Le lot de configuration de la lune a été autorisé et partiellement réalisé : PHP 8.3 appliqué, racine isolée créée avec fermeture Apache relue, base vide UTF-8 et utilisateur SQL dédié avec dix droits enregistrés. cPanel refuse `preprod.tclongages.fr` dans cette lune parce que son domaine parent appartient au compte principal. Aucun DNS ni certificat n'est créé pour ce nom. Les ressources de la lune sont conservées ; la correction de cible et les contrôles restants figurent dans le plan courant.

Pour revoir les sept pages publiques, lancer `npm.cmd run officiel`, puis ouvrir [l'aperçu local](http://127.0.0.1:4180/). Le lien de validation de la PR #5 y pointe désormais ; les libellés obligatoires et les coches humaines ont été conservés. Cet aperçu ne qualifie pas les comptes, les services connectés ou l'installation distante. Le [point de session courant](docs/point-session.md) et le bloc `developmentWorkflow` du [suivi canonique](docs/suivi-chantier/suivi-chantier.json) portent la prochaine action.

Les paragraphes datés du 1er octobre ci-dessous conservent l'historique de préparation et de clôture ; leur état de lune en attente est remplacé par le reçu courant.


Reprise le 1er octobre à la demande du responsable : [préparation GitHub et composants o2switch réutilisables](workflows/preparer-livraison.md), issus de la chaîne du site Drupal AVEREO. Une seule revue Claude avec Sonnet Moyen ; accès GitHub rétabli sur le compte déjà connecté. Le [reçu daté](data/actions-mutualisees-verification.json) distingue résultats locaux, PR et prérequis distants. Aucun site installé ou ouvert par cette reprise.

Le lot de sauvegarde et d'activation gratuite a ensuite été autorisé. Les archives du compte et une copie fraîche de la racine publique ont été restaurées et vérifiées dans un dossier privé ; ce test n'est pas un réimport sur l'hébergement. L'activation attend la saisie personnelle d'un nouveau mot de passe dans cPanel. Le [reçu d'hébergement](data/activation-lune-verification.json) et le [plan de reprise](docs/preparer-lune-tc.md) portent ce résultat et ses limites.

La journée du 1er octobre est close à la demande du responsable : formulaire annulé, déconnexion cPanel confirmée et onglet fermé. Le [point de session](docs/point-session.md) conserve la reprise exacte. Les derniers documents restent locaux, à intégrer au candidat avant une nouvelle remise ; la clôture ne lance aucune installation.

La [qualification de préproduction](docs/qualification-preproduction.md) prépare la prochaine intervention après la revue ciblée avec Claude. `npm.cmd run hosting:probe:build` génère une sonde sans secret et fermée par défaut dans `.local/qualification-preproduction/`. Le [reçu de reprise](data/reprise-deploiement-verification.json) distingue vérifications locales, lectures publiques et prérequis distants encore ouverts. Aucun transfert ne découle de cette commande.

Le parcours opérationnel demandé est maintenant ramené à quatre étapes : initialisation, recette locale, recette sur `preprod.tclongages.fr`, puis mise en production sur `tclongages.fr` après accord explicite. Le [registre](data/parcours-mise-en-ligne.json) et le [guide](docs/parcours-mise-en-ligne.md) décrivent les quatre étapes, leurs critères et les décisions sensibles. Le suivi HTML existant conserve l'historique des revues déjà saisies, sans nouvelle interface pour ce parcours. Aucune livraison distante n'a été exécutée.

`npm.cmd run drupal:public:build` prépare localement les sept pages du candidat Drupal dans `.local/drupal-public-candidate/`, sans inclure de compte ni de configuration privée. Le mode maintenance du futur site reste géré par Drupal. La préproduction, son certificat, ses ressources et sa sauvegarde doivent encore être qualifiés.

`npm.cmd run drupal:package` assemble aussi un ZIP local du code Drupal, des dépendances verrouillées et de ces pages dans `.local/tc-longages-drupal-v1-candidat.zip`. Ce paquet ne contient volontairement ni base, ni identifiants, ni `settings.php` actif ; il ne doit pas être déposé tel quel comme site fonctionnel. Le manifeste inclus précise le commit et indique si les sources étaient encore modifiées lors de l'assemblage. La procédure de configuration et la recette hébergée restent à établir sur la cible réelle.

## Suivi historique interactif et Drupal dédié — 29 septembre

Le suivi possède désormais une interface utilisable : lancer `npm.cmd run framework`, puis ouvrir [le suivi local](http://127.0.0.1:4181/). Les quatre phases communes, documents, critères et commentaires sont visibles ; les notes sont enregistrées sur le poste. Soumettre, valider, autoriser la suite et démarrer sont des actes distincts. L'identité y est déclarée localement : ce service n'est pas à exposer sur Internet. Voir [le fonctionnement](docs/installation-framework.md) et [le point de session](docs/point-session.md).

L'utilisateur a retenu un **Drupal dédié au club**, indépendant d'AVEREO. Le socle local peut être lancé sur [127.0.0.1:4182](http://127.0.0.1:4182/) avec la maintenance native activée. Les sept pages passent par Drupal ; les anciens fichiers de maintenance ne commandent pas cette variante. La connexion administrative locale existe, mais les rôles métier Bureau/Capitaine et les services Google restent à réaliser. Voir [l'installation Drupal](docs/installation-drupal.md).

L'exception d'installation est tracée dans [installation.json](framework/installation.json) ; son état et sa clôture font autorité. Elle n'autorise aucun déploiement. Les Actions CI technique et politique de PR sont activées. Une Action manuelle de préparation est ajoutée au candidat ; les modèles de livraison restent inactifs. La préparation ne livre ni ne retire la maintenance. Aucune phase humaine n’a été validée par l’agent. `npm.cmd run framework:build` génère les vues de lecture du suivi existant ; `npm.cmd run framework:check` vérifie le générateur et le serveur interactif.

Le [dépôt public du club](https://github.com/jpdandin44/TC_Longages) est raccordé au Git local en conservant le commit initial. La demande de finaliser les PR autorise le lot de raccordement. [Préparation du dépôt](docs/preparer-depot.md) décrit les deux contrôles de fusion et leurs limites ; le [suivi](docs/suivi-chantier/suivi-chantier.json) conserve la référence de PR et la remise effective. L’inventaire cPanel reste une lecture, pas une installation distante.

## Orientation actuelle — site officiel V1

La reprise du 24 septembre intègre le dossier officiel V1 : **vitrine, équipes, calendrier et disponibilités**, avec un futur espace Admin/Bureau/Capitaine. L'inscription complète et le paiement sont hors du P0. L'[état d'intégration V1](docs/integration-officiel.md) conserve les sources, les 30 arbitrages, les écarts et les critères G0 à G8.

Le domaine **`tclongages.fr`** est obtenu selon la confirmation utilisateur du 29 septembre ; son fonctionnement distant reste à vérifier. Depuis la demande de mise à jour du 24 septembre, **`tclongages@gmail.com`** sert à la fois de contact public du club et de compte Google de référence. **`support@tclongages.fr`** est l'adresse supplémentaire prévue pour les tests ; sa boîte ou son alias n'est pas créé ni activé. Aucune URL Calendar/Forms/Sheets, connexion Google, redirection de messagerie, automatisation d'agent, configuration de domaine ou publication réelle n'est activée par ces paramètres. Le type d'offre Google Workspace et son rattachement au domaine restent à confirmer. Le contact unique est défini par `club.email` dans `config/officiel.json` ; `scripts/inline-html.mjs` résout le marqueur `{{CLUB_EMAIL}}` de la vitrine commune pour toutes les variantes. La page Contact affiche l'adresse support comme prévue, sans lien d'envoi, avec renvoi vers le contact du club tant qu'elle n'est pas activée.

La variante statique officielle reste préparée dans `officiel/`, depuis la vitrine existante, les compléments `src/officiel*` et la [configuration centrale](config/officiel.json). Elle comporte sept pages : accueil, compétitions, calendrier, disponibilités, équipes, espace interne en attente et contact simulé. Son aperçu utilise le port 4180. Le nouveau socle Drupal reprend ces pages comme réponses de routes, sur 4182, afin que sa maintenance native les gouverne toutes. Le choix d'une instance dédiée est confirmé ; la qualification des rôles V1 reste nécessaire avant G3. L'OIDC générique antérieur est conservé comme expérimentation inactive, sans fournisseur Drupal raccordé.

| Commande V1 | Effet concret |
|---|---|
| `npm.cmd run officiel:build` | Régénère les sept pages autonomes et leurs fichiers de contrôle dans `officiel/`, sans publication. |
| `npm.cmd run officiel` | Régénère les pages et lance la revue sur [127.0.0.1:4180](http://127.0.0.1:4180/), uniquement sur cet ordinateur ; `Ctrl+C` arrête le serveur. |
| `npm.cmd run officiel:package` | Prépare le ZIP local de revue et son manifeste, sans transfert d'hébergement. |
| `npm.cmd run officiel:demo:build` | Produit les sept pages V1 et la fermeture Apache contrôlable dans `officiel-demo-o2switch/`, pour le dépôt manuel sur le sous-domaine. |
| `npm.cmd run officiel:demo:package` | Reconstruit puis archive cette démonstration V1, fermée par défaut ; aucun dépôt sur o2switch. |

La [baseline locale du 24 septembre](data/baseline-officiel.json) identifie les 182 fichiers sauvegardés avant intégration ; la [provenance V1](data/officiel-sources.json) conserve les empreintes des pièces reçues et les confirmations utilisateur. L'archive officielle d'aperçu `livrables/tc-longages-officiel-apercu.zip` contient dix fichiers pour la revue locale, distincte de la bêta réelle. **Sa fermeture Apache est inconditionnelle : renommer `maintenance.active` ne permet pas de l'ouvrir.** Cette règle est vérifiée statiquement, sans essai Apache distant de ce paquet. Utiliser le serveur local pour la revue ; une future bêta nécessitera un autre paquet et son autorisation.

Pour reproduire la présentation statique historique sur le sous-domaine, utiliser **`livrables/tc-longages-v1-demo-o2switch.zip`** avec le [guide de dépôt V1](docs/publier-v1-sous-domaine.md). Cette nouvelle variante conserve les sept pages officielles, sans compte ni envoi de contact, et ajoute une ouverture manuelle : `maintenance.active` vers `maintenance.inactive`, fermeture par l'inverse. Les deux témoins ou leur absence ferment le site. Après sauvegarde de la racine exacte, installer et constater la fermeture avant de copier les pages ; le ZIP seul ne remplace pas le site. Pendant l'ouverture, seules les pages V1 et `robots.txt` sont servis ; les anciennes routes sont refusées. La [recette du paquet V1](docs/recette-v1-sous-domaine.md) distingue les résultats locaux des contrôles que l'utilisateur doit encore réaliser sur o2switch. Aucune intervention distante de l'agent n'est autorisée par cette préparation.

La [recette officielle](docs/recette-officiel.md) consigne **87 tests Node réussis** et la vérification des sept pages à 1440, 390 et 320 px, avec 25 captures. Les six raccourcis ouvrent chacun leur écran en un clic ; le menu a aussi été contrôlé à 1060 et 1101 px. **G0 est réalisé localement et G1 qualifié pour l'identité et l'accès aux écrans**, sans valoir validation métier par l'utilisateur. G2 reste partiel avec contact simulé ; G3 à G8 ne sont pas acquis. Aucun service Google, accès privé V1 ou déploiement n'est validé par ces résultats.

Les sections suivantes décrivent les **prototypes antérieurs conservés** et leurs outils. Leurs inscriptions fictives, deux comptes expérimentaux et présentation HTTP libre ne définissent plus le P0 officiel. Aucun paquet antérieur n'est à assimiler à la future bêta sécurisée.

## Prototypes antérieurs conservés
Les archives `livrables/*.zip` citées ci-dessous sont des sorties locales générées, non versionnées et absentes d'un checkout neuf. Utiliser les commandes de génération de ce README avant de les rechercher ; leurs noms sont conservés sans lien de téléchargement inexistant. Le pack historique externe reste une source à retrouver pour ses anciens crédits.


Le projet prépare une vitrine du club et un espace réservé au bureau pour la communication et la gestion des inscriptions. Une **visite complète avec données fictives** permet maintenant d'examiner le formulaire adhérent, la gestion bureau et la communication, sans compte ni collecte réelle. Elle est séparée du prototype bureau protégé et ne remplace pas les services à réaliser pour une utilisation réelle.

**L'étape précédente portait sur le maquettage**, avec une démonstration complète que l'utilisateur avait choisi de déposer lui-même sur [tclongages.daje3540.odns.fr](http://tclongages.daje3540.odns.fr/), en remplacement de la vitrine. Son code OIDC reste expérimental et inactif ; cette présentation HTTP n'utilise aucun compte ni dossier réel d'adhérent. Le logo et l'affiche fournis par l'utilisateur sont des supports de présentation autorisés, sans ouvrir d'inscription ni déclencher d'annonce. Cette variante conservée ne satisfait pas les conditions de bêta V1.

Pour découvrir l'ensemble, utiliser le [guide de visite](docs/visiter-prototype.md), puis ouvrir [la démonstration locale](http://127.0.0.1:4174/) après son démarrage. N'y saisir que des dossiers fictifs. Dans cette visite, Facebook, WhatsApp, ADOC et la copie de message sont entièrement simulés.

**La mise en ligne reste soumise à l'accord explicite de l'utilisateur.** La création des fichiers locaux ne constitue ni une publication ni une autorisation de publier.

Le paquet de présentation antérieure `livrables/tc-longages-demo-o2switch.zip`, absent lors de la première intégration V1, a été régénéré le 24 septembre pour aligner le contact avec les autres variantes actives. Les copies archivées antérieures restent intactes. Son [guide de dépôt et de fermeture](docs/publier-demo-o2switch.md) reste conservé. Sa structure contient sept pages fictives, `robots.txt`, `.htaccess` et `maintenance.active` : la démonstration est préparée fermée par défaut. Seules la vitrine et l'adhésion figurent dans les menus publics ; les outils restent accessibles par adresse directe, **sans protection ni authentification** pendant l'ouverture. Aucun dépôt n'est effectué par l'agent.

L'ancienne archive de vitrine seule `livrables/tc-longages-vitrine-o2switch.zip` reste une option distincte, documentée dans son [guide](docs/deployer-vitrine-o2switch.md). Elle ne remplace pas le nouveau paquet de présentation complète.

## État et sources de vérité

- `officiel-demo-o2switch/` dérive les sept HTML de `officiel/` avec une fermeture Apache contrôlable ; `livrables/tc-longages-v1-demo-o2switch.zip` est la nouvelle démonstration V1 à déposer manuellement. Les manifestes restent hors du ZIP.
- `src/` contient les sources éditables du prototype et la photographie d'illustration restante. Les originaux `Images_Photos/Logo.jpeg`, `Affiche.jpeg` et `Images_Photos/Image_terrain.jpg`, fournis par le club, sont conservés ; leurs usages dans les pages sont dérivés par la construction.
- `prototype/` contient sept pages de visite fictive, accessibles sans compte sur le serveur local de démonstration. Les sources spécifiques sont nommées `src/demo-*`.
- `demo-o2switch/` dérive ces sept pages pour la présentation HTTP choisie : menus publics limités à la vitrine et au formulaire, clés de stockage propres et fermeture Apache par fichier témoin.
- `livrables/tc-longages-prototype.zip` est l'archive de visite, constituée à partir d'une liste explicite des fichiers nécessaires. Elle ne contient ni comptes ni dossiers réels d'adhérents.
- `dist/` contient cinq pages autonomes : la vitrine `index.html` et les pages internes `bureau.html`, `communication.html`, `inscriptions.html`, `actualites-bureau.html`.
- `release/index.html` est la vitrine préparée pour une éventuelle publication autorisée. Elle exclut la page de gestion et les mécanismes de démonstration utilisant les actualités stockées dans le navigateur.
- `livrables/tc-longages-demo-o2switch.zip` est le paquet choisi pour la présentation complète, avec dix fichiers à la racine ; son manifeste frère reste hors du ZIP. `livrables/tc-longages-vitrine-o2switch.zip` conserve l'option vitrine seule et son propre manifeste séparé.
- Les documents Markdown de cette racine décrivent l'état courant. Le [dossier du 15 septembre](TC_Longages_Dossier_Reprise_Codex_2026-09-15.md) et `TCL_Longages_Migration_o2switch/` sont des références historiques conservées intactes, pas les instructions opérationnelles actuelles.

Le dernier HTML autonome de 449 087 octets annoncé dans le dossier de reprise n'était pas présent dans les pièces locales examinées. La reprise utilise les sources et images réellement disponibles dans l'ancien pack, en réappliquant la correction de contact et l'intégration des ressources. Les empreintes du HTML ancien et des deux images correspondent à celles de la passation ; elles sont consignées dans [la provenance des sources](data/source-provenance.json). Le nouveau livrable ne doit pas être présenté comme identique à l'archive corrigée manquante.

L'adresse de contact retenue est **tclongages@gmail.com** ; l'ancienne adresse FFT n'est plus la consigne courante. Les liens Ten'Up et Facebook sont conservés. La FAQ et les formules de démonstration sont générées depuis le [relevé tarifaire commun](data/tarifs-inscription.json), transcrit des deux fiches PDF 2026–2027 fournies. Il ne tranche pas le choix de 125 ou 150 € pour les cours adultes et n'invente ni remise familiale ni tarif de terrain. Le [processus d'inscription](docs/processus-inscriptions.md) conserve le cadrage métier ; les horaires et groupes de la visite restent des exemples fictifs. Le [logo JPEG fourni](Images_Photos/Logo.jpeg) reste temporaire en attendant le vectoriel. La [photo réelle du court](Images_Photos/Image_terrain.jpg) remplace l'illustration d'accueil, avec attribution « photo fournie par le club », sans auteur supposé. La seconde photographie demeure une illustration de Nicholas Bullett, créditée comme telle. Aucune redirection depuis l'ancienne adresse n'est présumée configurée.

## Prérequis et installation

Utiliser Node.js **22.9.0 ou plus récent** avec npm pour le projet complet, notamment le lancement OIDC avec lecture facultative de `.env`. La couche de connexion OIDC dépend de `openid-client` 6.8.8 ; `package-lock.json` fixe les dépendances. Après récupération du projet, `npm.cmd ci --ignore-scripts` les installe sans exécuter leurs scripts d'installation. Cette commande contacte le registre npm et remplace les dépendances locales ; elle ne crée aucun compte ni accès d'hébergement. L'archive de visite autonome reste utilisable avec Node.js 22 ; la vitrine seule dans `release/` reste statique et n'exige pas Node.js sur l'hébergement.

Ouvrir PowerShell dans `Site_Internet/`. Aucun compte n'est nécessaire pour visiter la démonstration fictive sur le port 4174. Le prototype protégé sur le port 4173 exige un compte bureau actif pour ses pages internes ; aucun compte réel n'est encore configuré. Le partage manuel WhatsApp de cette version protégée requiert le propre accès WhatsApp du responsable, contrairement à la simulation de la visite.

## Commandes principales

```powershell
npm.cmd run demo
```

Cette commande reconstruit la visite puis démarre le serveur local sur `http://127.0.0.1:4174/`. L'archive de visite fournit son propre lancement, décrit dans le guide. Pour travailler sur le prototype protégé :

```powershell
npm.cmd run build
npm.cmd run check
npm.cmd run preview
```

| Commande | Effet concret |
|---|---|
| `npm.cmd ci --ignore-scripts` | Installe les dépendances verrouillées nécessaires à OIDC et à ses tests, sans script d'installation ni publication. |
| `npm.cmd run bureau` | Reconstruit les pages puis démarre le service OIDC sur `127.0.0.1:4175`, avec lecture de `.env` s'il existe. Sans configuration valide, le service reste fermé. Arrêt avec `Ctrl+C`. |
| `npm.cmd run demo:build` | Régénère les sept pages fictives et leurs fichiers de lancement dans `prototype/`, sans publication. |
| `npm.cmd run demo` | Régénère puis ouvre le service local de visite sur le port 4174 ; arrêt avec `Ctrl+C`. |
| `npm.cmd run demo:hosting:package` | Reconstruit les sept pages destinées à la présentation HTTP puis crée le ZIP à dix fichiers, fermé par défaut ; aucun transfert vers o2switch. |
| `npm.cmd run release:package` | Reconstruit la vitrine puis crée le ZIP public à un fichier et son manifeste séparé ; aucune connexion à o2switch. |
| `npm.cmd run build` | Régénère les livrables locaux depuis `src/` ; écrase les fichiers générés concernés, sans transfert Internet. |
| `npm.cmd run check` | Régénère les livrables et exécute les contrôles locaux définis dans le projet ; ne publie rien. |
| `npm.cmd run preview` | Lance l'aperçu sur `http://127.0.0.1:4173/`, accessible depuis cet ordinateur. Arrêt avec `Ctrl+C`. |
| `start-local.cmd` | Régénère les livrables puis démarre l'aperçu local sous Windows. |

La création des archives utilise ZIP/.NET sous Windows. Les outils de construction restent sur l'ordinateur : aucun serveur Node.js ni lanceur n'est inclus dans les paquets o2switch. Le paquet de démonstration contient `.htaccess` pour fermer l'ensemble des pages ; celui de vitrine seule n'en contient pas. L'adresse HTTP de présentation est confirmée ; sa racine dans cPanel, l'existant et le fonctionnement Apache restent à vérifier avant ouverture. Aucun forçage HTTPS n'est ajouté à cette démonstration fictive.

Ouvrir la vitrine à [127.0.0.1:4173](http://127.0.0.1:4173/) et l'espace interne à [127.0.0.1:4173/bureau.html](http://127.0.0.1:4173/bureau.html). Les pages internes restent fermées tant que les comptes ne sont pas configurés. Utiliser la même adresse et le même navigateur pour les pages de communication et d'aperçu ; changer de nom d'hôte, de port ou de profil crée un autre stockage local.

## Espaces et services distincts

| Espace | Usage | Limite |
|---|---|---|
| Démonstration V1 `officiel-demo-o2switch/` | Nouvelle V1 statique à examiner sur le sous-domaine, dépôt et bascule réalisés par l'utilisateur. | Sept pages publiques pendant l'ouverture ; aucune authentification Drupal, collecte de contact ou connexion Google. Fermée par défaut. |
| Démonstration `prototype/`, port 4174 | Sept pages fictives sans connexion : visite, vitrine, formulaire, bureau, inscriptions, communication et aperçu des actualités. | Aucun compte, service métier ou envoi réel ; tous les visiteurs locaux peuvent voir les exemples. |
| Présentation `demo-o2switch/` | Sept pages fictives adaptées à l'adresse HTTP confirmée, que l'utilisateur dépose lui-même. | Fermée par défaut ; pendant l'ouverture, toutes les pages sont accessibles sans compte, même sans lien public. |
| Prototype protégé `dist/`, port 4173 | Contrôle serveur des pages du bureau ; communication et aperçu existants. | Pages internes fermées sans comptes ; page inscriptions encore en attente, pas de collecte. |
| Connexion OIDC, port 4175 | Couche expérimentale pour les quatre pages du bureau, préparant le futur compte personnel administrateur et le compte générique bureau. | Activation reportée ; aucun fournisseur ni compte réel activé, sessions en mémoire et données éditoriales encore locales. |
| Candidat `release/index.html` | Vitrine seule préparée pour une éventuelle publication. | Déploiement distinct, soumis à accord explicite. |

Le formulaire de visite comporte cinq étapes et alimente uniquement le jeu d'exemples du navigateur. La gestion présente filtres, disponibilités et groupes fictifs. L'import XLS/CSV montre un lot prédéfini, sans lecture de fichier ; l'export CSV produit réellement un fichier des données de démo. Aucun export XLS, import réel d'inscriptions, API métier, base centrale ou reprise sécurisée de dossier n'est livré. L'import JSON d'actualités réelles est bloqué dans la visite.

La visite utilise `tcl.demo.inscriptions.v1` et `tcl.demo.communication.v1` sur une origine distincte du port 4173. Les essais peuvent être réinitialisés selon le guide ; ils ne constituent pas des demandes reçues par le club.

La présentation destinée à o2switch emploie `tcl.hosted-demo.inscriptions.v1` et `tcl.hosted-demo.communication.v1`. Les essais restent propres au navigateur, sans base commune. Utiliser la même origine HTTP et le même profil pour passer du formulaire à la gestion. Les imports JSON ou de dossiers réels et les diffusions restent bloqués ou simulés comme dans la visite locale ; la sélection locale d'une image est décrite ci-dessous.

La communication permet d'ajouter **une image ou affiche par actualité**, depuis un fichier JPEG, PNG ou WebP. Un titre et une description de l'image sont requis ; le texte peut rester vide lorsqu'une affiche est jointe. L'image est préparée et enregistrée dans ce navigateur, sans téléversement serveur. Les aperçus affichent l'affiche entière ; son remplacement, son retrait ou la modification de sa description demandent une nouvelle validation après enregistrement. Le [guide de visite](docs/visiter-prototype.md) détaille les limites et le parcours.

Dans la démonstration, **Utiliser l’affiche exemple** charge le [visuel fourni](Affiche.jpeg) dans l'éditeur pour l'essayer ; il ne crée pas une actualité déjà validée. L'ajout local d'une image est permis alors que l'import JSON de données réelles reste bloqué. Facebook, WhatsApp et ADOC restent simulés dans la visite, y compris avec une affiche.

Le quatrième aperçu, **ADOC**, reprend titre, contenu, photo et visibilité sur Ten'Up d'après la capture fournie. Il prépare au plus 2 000 caractères de texte et de lien, sans couper le contenu. L'option Ten'Up vaut « Non » par défaut et toute modification impose une nouvelle validation. Le bouton ADOC simule seulement la préparation : aucune connexion ni publication sur ADOC ou Ten'Up. Voir le [cadrage ADOC](api/adoc.md).

## Connexion du bureau et utilisation des pages protégées

L'utilisateur a confirmé le besoin futur de **deux comptes : un compte personnel administrateur et un compte générique bureau**. La connexion OIDC est préparée à titre expérimental dans `server/`, séparément sur le port 4175. Son activation est reportée ; le choix du fournisseur et du domaine n'est pas nécessaire pour poursuivre la maquette. `.env.example` et `data/oidc-accounts.example.json` sont inactifs ; aucun secret, compte réel ou fichier privé d'autorisation OIDC n'est créé. Le service répond `503` sans configuration valide. Voir [l'authentification OIDC](docs/authentification-oidc.md) et le [guide certificat et connexion](docs/certificat-et-connexion-bureau.md).

Après activation autorisée, seules les deux identités explicitement inscrites par fournisseur et identifiant stable (`iss`/`sub`) pourront entrer. Le compte administrateur accède aussi au bureau et consulte `/admin/acces`, une page en lecture seule. Un compte reconnu par le fournisseur mais absent de cette liste n'est pas inscrit automatiquement. Les sessions durent au maximum huit heures, expirent après trente minutes d'inactivité et sont perdues au redémarrage ; elles restent limitées à cent dans un seul processus. Un hébergement à plusieurs processus demandera une gestion partagée des sessions.

L'ancien serveur du port 4173 conserve HTTP Basic pour ses quatre pages internes. Son fichier `.local/bureau-users.json` est absent : l'accès interne reçoit `503`, puis recevrait `401` sans authentification valable si une configuration active était installée. Le [guide Basic local](docs/acces-bureau.md) décrit uniquement ce mécanisme, qui n'est pas la connexion cible pour Internet.

Il n'y a aucun secret Facebook ou WhatsApp, aucun accès d'hébergement configuré ni automatisation de déploiement. Les contrôles serveur ne protègent pas les HTML ouverts directement depuis le disque. La préparation OIDC ne vaut pas activation Internet : domaine, certificat HTTPS reconnu, fournisseur, configuration du proxy et hébergement restent à vérifier. La page **Inscriptions** demeure une page d'attente sans formulaire ni collecte de données. Le contenu fourni est traité dans la [proposition de processus d'inscription](docs/processus-inscriptions.md), à valider avant développement réel.

La future gestion réelle des inscriptions inclura les imports et exports **XLS et CSV**, réservés au bureau, avec contrôle des erreurs et doublons avant confirmation. Ce besoin demeure à réaliser : la simulation de lot et le CSV fictif de la visite n'en constituent pas l'implémentation complète. Ces échanges sont distincts du JSON d'actualités du prototype protégé.

Après configuration autorisée d'un accès bureau :

1. Ouvrir l'espace bureau, saisir un titre, puis rédiger le texte ou ajouter une affiche avec sa description ; enregistrer le brouillon.
2. Relire les quatre aperçus : Site, Facebook, WhatsApp et ADOC, avec l'option de visibilité Ten'Up si ce canal est utilisé.
3. Valider explicitement l'actualité pour la consulter dans l'aperçu privé du bureau et tester le relais Facebook simulé. La vitrine accessible sans connexion ne lit pas ces actualités.
4. Pour WhatsApp, utiliser **Ouvrir WhatsApp** ou **Copier le message**, puis choisir les groupes et confirmer l'envoi dans WhatsApp. L'ouverture transmet le texte au service WhatsApp pour préremplissage ; elle n'envoie pas le message aux groupes. Si la copie automatique est indisponible, le texte peut être sélectionné et copié manuellement.
5. Exporter régulièrement les données en JSON si elles doivent être conservées. L'import reprend les actualités comme brouillons à valider de nouveau.

Le partage WhatsApp est disponible seulement pour la révision validée ; modifier le formulaire le désactive jusqu'à l'enregistrement et à une nouvelle validation. Pour un texte produisant un lien trop long, la copie reste disponible. L'ouverture ou la copie ne vaut pas livraison : le prototype n'enregistre aucun statut « envoyé » ou « livré » WhatsApp.

Dans le prototype protégé du port 4173, le lien WhatsApp et la copie ne transmettent que le texte et le lien de l'actualité ; **l'image n'est pas jointe automatiquement**. Un futur partage réel d'affiche demandera de joindre soi-même le fichier dans WhatsApp avant confirmation de l'envoi.

Les données sont stockées dans le navigateur avec `localStorage`. Elles ne sont pas synchronisées entre appareils ou profils et peuvent disparaître lors d'un effacement des données du navigateur. Elles ne sont pas isolées par compte bureau : deux personnes partageant le même profil partagent ce stockage. Les exports peuvent contenir des brouillons : les conserver dans un emplacement privé, hors des fichiers à publier.

Les images enregistrées occupent aussi ce stockage et sont incluses dans l'export JSON éditorial. Un dépassement du budget prévu ou du quota du navigateur bloque l'enregistrement sans remplacer la sauvegarde précédente ; la saisie reste à l'écran. Les actualités déjà présentes sans image restent lisibles. Une image ajoutée après votre dépôt n'est pas partagée avec les autres visiteurs et n'entre pas automatiquement dans un nouveau ZIP.

## Organisation

| Emplacement | Rôle |
|---|---|
| `src/` | Sources HTML, CSS, JavaScript et images. |
| `src/demo-*` | Sources et données fictives propres à la visite partagée. |
| `src/communication-whatsapp.js` | Préparation et partage manuel du texte WhatsApp après validation. |
| `src/communication-images.js` | Lecture et préparation locale des images avant sauvegarde dans le navigateur. |
| `src/communication-adoc.js` | Préparation locale de l'aperçu ADOC après validation, sans appel au service réel. |
| `Images_Photos/Logo.jpeg`, `Affiche.jpeg` | Originaux fournis conservés : logo JPEG temporaire et affiche utilisable comme exemple dans l'éditeur de démonstration. |
| `data/tarifs-inscription.json`, `scripts/tariffs.mjs` | Relevé des deux PDF et génération commune des tableaux, notes de FAQ et formules fictives ; conditions inconnues conservées. |
| `Images_Photos/Image_terrain.jpg` | Photo réelle du court fournie par le club et intégrée à l'accueil, sans auteur inventé. |
| `src/brand.css`, `src/demo-communication-example.js` | Présentation du logo et chargement volontaire de l'affiche exemple dans la démonstration. |
| `scripts/` | Construction, serveur d'aperçu local et contrôle des accès bureau. |
| `server/`, `app.cjs` | Service de connexion OIDC distinct ; vérification des identités, autorisations et sessions côté serveur. |
| `package.json`, `package-lock.json` | Commandes, Node.js 22.9.0 minimum et dépendances verrouillées. |
| `.env.example`, `data/oidc-accounts.example.json` | Exemples inactifs de configuration OIDC et des deux rôles. |
| `.env`, `.local/oidc-accounts.json` | Emplacements privés prévus pour la configuration réelle ; absents, exclus du dépôt et des livrables publics. |
| `tests/` | Contrôles du prototype. |
| `dist/` | Cinq pages autonomes ; quatre routes réservées au bureau par le serveur local. |
| `prototype/` | Sept pages fictives et lancement de la visite sans compte sur le port 4174. |
| `demo-o2switch/` | Sept HTML fictifs, `robots.txt`, `.htaccess` et `maintenance.active`, destinés à la présentation manuelle. |
| `scripts/build-hosted-demo.mjs`, `scripts/package-hosted-demo.mjs` | Adaptation de la visite et archivage des dix seuls fichiers autorisés. |
| `livrables/tc-longages-demo-o2switch.zip` | Paquet de présentation complète choisi ; manifeste frère conservé hors archive. |
| `data/hosted-demo-manifest.json` | Empreintes des dix fichiers générés pour cette présentation. |
| `livrables/tc-longages-prototype.zip` | Archive limitée aux fichiers de visite explicitement sélectionnés. |
| `livrables/tc-longages-vitrine-o2switch.zip` | Archive publique : exactement `index.html` à la racine, sans fichier serveur ni espace bureau. |
| `livrables/tc-longages-vitrine-o2switch.manifest.json` | Tailles et empreintes de l'archive publique et du HTML ; à conserver localement, hors ZIP. |
| `release/` | Vitrine seule pour préparer une éventuelle publication. |
| `docs/` | Consignes opérationnelles. |
| `workflows/` | Processus de validation et de diffusion. |
| `api/` | Cadrage du futur relais Facebook, du partage manuel WhatsApp et exemple fictif d'inscription ; aucun service métier implémenté. |
| `data/build-manifest.json` | Empreintes et tailles des livrables générés ; les brouillons restent dans le navigateur. |
| `data/source-provenance.json` | Provenance des pièces historiques et des visuels fournis, empreintes des originaux et limites du constat initial. |
| `data/bureau-users.example.json` | Exemple vide de configuration des comptes, sans accès utilisable. |
| `.local/bureau-users.json` | Futur fichier privé des comptes avec empreintes de mots de passe ; absent à ce stade et exclu des livrables. |
| `prompts/` | Référence du prompt de conception des inscriptions fourni par l'utilisateur ; aucun agent IA applicatif implémenté. |

Ne pas modifier `dist/`, `prototype/`, `demo-o2switch/` ou `release/` à la main : corriger les sources ou leur adaptation de génération, puis régénérer la sortie concernée. Pour les anciennes variantes statiques seulement, le renommage du témoin suit leur guide. Le nouveau socle Drupal utilise sa maintenance native. Ne pas envoyer tout le projet ni utiliser la démonstration pour recevoir de vraies inscriptions.

## Documentation et vérification

- [Intégration officielle, arbitrages et gates](docs/integration-officiel.md)
- [Recette locale de l'aperçu officiel](docs/recette-officiel.md)
- [Architecture](architecture.md)
- [Exigences](requirements.md)
- [Feuille de route](roadmap.md)
- [Décisions](decisions.md)
- [Journal des évolutions](changelog.md)
- [Commandes sensibles et contrôle utilisateur](docs/commandes-sensibles.md)
- [Présenter la V1 sur le sous-domaine](docs/publier-v1-sous-domaine.md) et [recette de son paquet](docs/recette-v1-sous-domaine.md)
- [Mutualisation de la connexion et des services CONNECT](docs/mutualisation-connect.md)
- [Connexion OIDC du bureau](docs/authentification-oidc.md)
- [Recette OIDC](docs/recette-oidc.md)
- [Certificat et choix de connexion](docs/certificat-et-connexion-bureau.md)
- [Ancien accès Basic local](docs/acces-bureau.md)
- [Visiter et partager la démonstration](docs/visiter-prototype.md)
- [Recette de la démonstration](docs/recette-demo.md)
- [Déposer, ouvrir et refermer la démonstration sur o2switch](docs/publier-demo-o2switch.md)
- [Recette du paquet de démonstration o2switch](docs/recette-demo-o2switch.md)
- [Déposer la vitrine seule sur o2switch](docs/deployer-vitrine-o2switch.md)
- [Recette de l'archive publique](docs/recette-vitrine-o2switch.md)
- [Proposition du processus d'inscription](docs/processus-inscriptions.md)
- [Référence du prompt de conception](prompts/conception-inscriptions.md)
- [Publication contrôlée](workflows/publication-controlee.md)
- [Préparation de l'intégration Facebook](api/facebook.md)
- [Partage dans les groupes WhatsApp](api/whatsapp.md)
- [Préparation ADOC simulée](api/adoc.md)
- [Consignes pour les interventions suivantes](AGENTS.md)
- Crédits des photographies du pack historique : source externe `TCL_Longages_Migration_o2switch/CREDITS_IMAGES.md`, absente de ce checkout ; les crédits courants sont intégrés à la vitrine.

La [recette du prototype protégé](docs/recette-prototype.md) consigne ses contrôles, dont les accès testés avec des identifiants éphémères en mémoire. La [recette de la visite fictive](docs/recette-demo.md) décrit séparément les vérifications effectivement réalisées sur les sept pages et l'archive ; les tests antérieurs ne valent pas validation de cette extension. Aucun compte réel n'est ouvert. Le site public, son HTTPS, la délivrabilité de la messagerie et la réception dans les groupes WhatsApp ne sont pas validés par un contrôle local. Les groupes réels existants ou nouveaux à utiliser restent à préciser avec l'utilisateur.

La [recette de l'archive o2switch](docs/recette-vitrine-o2switch.md) documente la préparation publique, dont les 25 tests locaux réussis. Elle n'atteste aucune installation ni disponibilité sur l'hébergement.

La [recette de la présentation o2switch](docs/recette-demo-o2switch.md) traite séparément le nouveau paquet complet. Une préparation locale ne prouve pas que la fermeture Apache fonctionne sur la cible ; l'utilisateur doit constater les réponses 503 avant ouverture et après fermeture selon le guide.

La [recette OIDC](docs/recette-oidc.md) suit séparément les contrôles de la nouvelle connexion. Les résultats des prototypes précédents ne valent pas qualification d'un fournisseur réel ni d'une installation publique.

Git est initialisé dans ce dossier et rattaché à l’historique du dépôt du club. Les décisions humaines restent distinctes des commits et des contrôles automatiques.
