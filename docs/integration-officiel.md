---
project: TC_Longages
document_type: integration-plan
title: Intégration du cadrage officiel V1 et suivi des gates
status: active
version: git
created: 2026-09-24
updated: 2026-10-06
owner: jpdandin
tags:
  - officiel
  - arbitrages
  - gates
  - reprise
---

# Intégration du site officiel V1

## État du support au 6 octobre

La boîte `support@tclongages.fr` est désormais créée et vérifiée dans cPanel.
Les descriptions de boîte prévue ci-dessous conservent le cadrage historique
du 24 septembre. La collecte du nouveau correctif V1 et son transport réel
restent à recetter ; voir [le guide support](signalements-support.md).


## Reprise du 29 septembre

Le domaine `tclongages.fr` est obtenu selon la confirmation utilisateur. La vérification DNS/HTTPS, la racine et les services réels restent à qualifier. Le [framework adopté](framework-developpement.md) suit désormais la méthode de réalisation ; ses phases 0–7 ne remplacent pas les gates métier G0–G8 ci-dessous. La phase de cadrage du framework est seule engagée, sans réinitialiser les acquis V1 ni les considérer comme une validation humaine. Le dépôt dédié est communiqué mais son accès reste à rétablir ; voir le [point de session](point-session.md).

La demande du 24 septembre est d'intégrer les éléments du dossier officiel V1 au projet existant. Elle autorise la préparation et les contrôles locaux, pas un déploiement. La cible fonctionnelle devient une vitrine simple, des équipes, un calendrier et des disponibilités, avec accès interne Admin/Bureau/Capitaine. L'ancienne démonstration d'inscriptions et de communication est conservée séparément ; elle n'est pas la bêta V1.

La demande ultérieure du même jour ajoute **un paquet de démonstration V1 pour le sous-domaine existant**, que l'utilisateur déposera lui-même avant le domaine final. Elle oriente aussi la future authentification vers Drupal sur `tclongages.fr`. Installation dédiée ou raccordement existant : l'utilisateur précise **« À déterminer ensemble »**, puis demande d'examiner la logique Drupal de CONNECT et les fonctions mutualisées. Ce choix d'orientation ne vaut ni architecture qualifiée ni module prêt, ni autorisation d'installation ou d'intervention sur un autre projet. Les détails et les droits seront déterminés ensemble avant G3.

## Confirmations et sources

L'utilisateur a confirmé **`tclongages.fr` comme futur domaine** et **`tclongages@gmail.com` comme compte Google de référence et, dans sa demande ultérieure du 24 septembre, comme contact public à remplacer partout dans le site courant**. **`support@tclongages.fr`** est prévu pour la phase de test ; sa boîte ou son alias n'est pas créé ni activé. Aucun accès au compte Google, lien Calendar/Forms/Sheets, redirection de courrier ou automatisation d'agent n'est fourni ou activé. Le type d'offre Workspace et son rattachement au domaine restent à confirmer. Le domaine confirmé ne prouve ni achat, ni DNS, ni certificat ou contenu actuellement servi.

Les sources reçues, conservées sans modification dans `../../Site_tclongages_V1_Officiel/`, sont :

- [Spécification détaillée V1](../../Site_tclongages_V1_Officiel/TC_Longages_Specifications_Evolution_Prototype_Codex_V1.md), 33 sections après la règle de lecture initiale ; lue intégralement.
- [Classeur des arbitrages](<../../Site_tclongages_V1_Officiel/TC_Longages_Arbitrages_Etape1_V1_complétée(1).xlsx>), trois feuilles lues en lecture seule. La colonne **Q — Décision finale** constitue le choix renseigné ; la colonne I est une aide, pas une décision.
- [Backlog et plan d'exécution](<../../Site_tclongages_V1_Officiel/TC_Longages_Etape2_Backlog_Plan_Execution_V1 (1).xlsx>), six feuilles lues en lecture seule. Il définit tâches, dépendances et critères, sans attester leur réalisation dans ce projet.
- [Archive G1 fournie](../../Site_tclongages_V1_Officiel/TC_Longages_G1_Prototype_V1.zip), référence du travail visuel à intégrer ; son nom « G1 » ne constitue pas une recette acquise.

Les 30 choix finaux concordent entre les deux classeurs et le tableau de la spécification : 24 arbitrages P0, cinq P1 et un P2. Le backlog contient 52 tâches, dont 46 P0, cinq P1 et une P2 ; 51 sont marquées « À faire » et le POC FFT/Ligue P1-005 est « Bloqué ». Les 25 tests fournis sont « À exécuter ». Les valeurs de pilotage décrivent ces sources, pas l'avancement nouvellement constaté.

Les [empreintes des pièces reçues et les confirmations utilisateur](../data/officiel-sources.json) assurent leur traçabilité. Leurs statuts d'origine restent intacts ; les résultats de l'intégration sont consignés séparément dans la [recette officielle](recette-officiel.md).

## Arbitrages à appliquer

| Sujet | Décision V1 |
|---|---|
| P0 | Vitrine, équipes, calendrier, disponibilités et accès bureau/capitaines. |
| Navigation | Accueil concentré, cinq à six accès rapides, parcours prioritaires en trois clics maximum ; parité desktop/mobile. |
| Identité | Rouge/blanc provisoire paramétrable ; logo historique fourni, sans recréation graphique. |
| Contact | Nom, e-mail ou téléphone et message seulement ; `tclongages@gmail.com` selon la demande ultérieure du 24 septembre. Support de test `support@tclongages.fr` prévu et inactif. |
| Comptes | Utilisateurs internes uniquement ; rôles Admin, Bureau et Capitaine, ce dernier limité à ses équipes. Aucun compte adhérent général. |
| Effectifs | Fichier/Google Sheet importable ; équipes et catégories/tags ; aucune liste complète publique. |
| Calendrier | Google Calendar comme référence unique des rencontres. |
| Disponibilités | Google Form ; Oui, Non, Je ne sais pas ; nom dans une liste limitée au périmètre. Sheet de pilotage réservé aux responsables, distinguant aussi Non répondu. |
| Communication P0 | WhatsApp manuel, émetteur désigné par équipe, lien vers le bon parcours ; appel manuel pour les exceptions. Groupes actuels conservés. |
| Hors P0 | Inscription complète, paiement, comptes de tous les adhérents, automatisation des messages, communauté WhatsApp, blog complexe et intégration FFT non autorisée. |
| Bêta réelle | Domaine officiel, HTTPS, accès restreint, partages Google vérifiés, information RGPD, noindex et panel de huit à douze personnes. |

Les actualités administrables, appels depuis une fiche privée, semi-automatisation et étude d'une intégration officielle FFT/Ligue relèvent de P1 après bêta. Les prototypes Facebook et ADOC restent disponibles dans la démonstration antérieure sans devenir des envois P0 de la V1.

## Écarts des sources et traitement retenu

- **Contact révisé après réception :** la mention de conservation de l'adresse FFT dans le dossier fourni est remplacée par la demande utilisateur explicite d'utiliser `tclongages@gmail.com`. Les pièces reçues et les preuves historiques restent intactes ; seules les sources courantes, sorties dérivées et instructions opérationnelles sont actualisées.
- **Statuts d'arbitrage périmés :** les 30 cellules de statut restent « À décider », bien que les 30 décisions finales soient renseignées et reprises par la spécification. Les décisions finales prévalent ; les classeurs reçus ne sont pas réécrits.
- **Aides différentes du choix final :** ARB-05 conseille le logo modernisé mais retient le logo historique ; ARB-06 conseille mobile-first mais retient la parité desktop/mobile. La mention « Mobile prioritaire » dans l'onglet Pilotage est une ancienne aide, pas le choix final.
- **Logo et authentification déclarés absents :** ces constats décrivent le paquet G1 fourni. Le projet courant possède déjà `Images_Photos/Logo.jpeg`, la photo réelle du court et des expérimentations Basic/OIDC. Leur présence ne vaut ni authentification V1 ni qualification hébergée.
- **Ancien périmètre :** les deux comptes Admin/Bureau et l'inscription complète du prototype de septembre ne suffisent pas à la V1. Préserver leur code et leurs preuves ; ne pas les présenter comme le modèle validé Admin/Bureau/Capitaine ou comme des fonctions P0 à activer.
- **Adhérer/Jouer :** la section 8.4 autorise une rubrique informative orientant vers Ten'Up ou le contact, tandis que PUB-004 emploie la formulation plus large « aucun CTA ou écran de paiement/adhésion ». L'intégration retient une information pour jouer, sans formulaire complet, paiement ou lien vers la démonstration d'inscription dans le parcours officiel.
- **Exceptions WhatsApp :** ARB-20 est classé P1, mais COM-003 exige la procédure d'appel manuel en P0. Documenter ce traitement humain dès P0 ; le bouton d'appel depuis une fiche reste P1.
- **Présentation HTTP antérieure :** le paquet ancien sans comptes ne satisfait pas les exigences de bêta V1. La nouvelle demande autorise la préparation d'une autre démonstration statique V1 pour le même sous-domaine, avec dépôt par l'utilisateur selon son guide propre. Cela ne rend pas une collecte réelle ou une connexion bureau admissible sur HTTP et ne vaut pas ouverture de la bêta sécurisée.

## Intégration locale et préservation

La source existante `src/index.html` reste la base de la vitrine et de ses contenus vérifiés. Les compléments `src/officiel*` et la [configuration centrale](../config/officiel.json) constituent la variante officielle ; ils ne remplacent pas les sources de la démonstration. `scripts/build-officiel.mjs` produit sept pages autonomes dans `officiel/` : accueil, compétitions, calendrier, disponibilités, équipes, espace interne en attente et contact simulé. Le serveur d'aperçu de `npm.cmd run officiel` utilise la boucle locale `127.0.0.1:4180` et sert uniquement les pages prévues et `robots.txt`, sans écriture ou requête applicative externe.

La configuration garde Calendar à `null`, les équipes et Forms vides, la connexion en attente de décision et le contact en mode aperçu. `scripts/official-config.mjs` contrôle ces contraintes et refuse des données d'effectif privé dans les équipes publiques. La configuration conserve le compte Google de référence sans sérialiser ses paramètres d'intégration dans les HTML ; la même adresse est désormais affichée dans les liens de contact publics. `club.email` alimente toutes les variantes par le marqueur commun `{{CLUB_EMAIL}}`. `contact.testSupport` vaut `{ email: 'support@tclongages.fr', status: 'planned' }` : la page Contact affiche une assistance prévue sans lien d'envoi et invite à utiliser l'adresse du club pour le moment. Aucun Sheet privé, secret ou liste de joueurs n'est rendu public.

Le [paquet officiel de revue](../livrables/tc-longages-officiel-apercu.zip) est créé et distinct des archives précédentes. Ses dix fichiers sont les sept HTML, `robots.txt`, `.htaccess` et `maintenance.active`. **Sa règle Apache répond 503 sans condition : renommer le témoin ne l'ouvre pas.** La règle est vérifiée statiquement ; aucun Apache distant n'a exécuté ce nouveau paquet lors de la recette. Le lancement local sur le port 4180 permet la revue ; l'ouverture d'une bêta réelle nécessitera un autre paquet, les contrôles requis et un accord explicite. Ne pas appliquer à cette archive la procédure de basculement d'une démonstration.

Le nouveau **[paquet V1 de présentation sur le sous-domaine](../livrables/tc-longages-v1-demo-o2switch.zip)** dérive les sept HTML dans `officiel-demo-o2switch/`, avec la bannière « DÉMONSTRATION V1 » et une fermeture volontairement contrôlable. `maintenance.inactive` seul permet l'ouverture ; `maintenance.active`, les deux témoins ou leur absence ferment avec 503. Ouvert, le site sert seulement les sept pages V1 et `robots.txt` ; les anciennes routes du bureau restent refusées même si des fichiers subsistent. Il ne contient aucun module Drupal, compte ou transmission Contact. La préparation utilise `npm.cmd run officiel:demo:build` et `npm.cmd run officiel:demo:package`, sans transfert. Les deux manifestes restent hors du ZIP. Le [guide V1 de dépôt](publier-v1-sous-domaine.md) organise sauvegarde, extraction privée, fermeture vérifiée avant copie, ouverture, fermeture et retour arrière complet ; la [recette de ce paquet](recette-v1-sous-domaine.md) distingue preuves locales et contrôles o2switch à réaliser par l'utilisateur. La démonstration temporaire ne remplace pas les exigences de bêta réelle.

`npm.cmd run officiel:build` régénère la variante ; `npm.cmd run officiel:package` la reconstruit et archive la liste explicite des fichiers. Ces commandes écrivent localement les sorties et manifestes, sans transfert ou connexion externe. Elles ne fournissent pas l'authentification ou les services Google de la future bêta. La [recette locale](recette-officiel.md) qualifie G1 dans son périmètre d'identité et de navigation ; elle ne clôture pas les usages futurs ni la bêta.

Une [archive locale de référence](../archives/reference-avant-officiel-20260924.zip) a été créée avant intégration : 182 fichiers, empreinte et périmètre consignés dans [le manifeste de baseline](../data/baseline-officiel.json). Elle ne contient pas de sauvegarde du serveur distant ou des secrets. Git n'est pas initialisé ; aucune branche ou aucun commit n'est annoncé.

Pour un retour arrière local, extraire cette référence dans un dossier distinct, comparer les fichiers concernés, puis restaurer uniquement ceux du lot à annuler. Les données de navigateur et les fichiers privés ne sont pas couverts par cette archive. Tout retour arrière d'hébergement demanderait une sauvegarde et une procédure propres à la cible, puis une autorisation explicite.

## État et critères par gate

| Gate | Lot et critère essentiel | État de cette intégration |
|---|---|---|
| G0 | Cinq tâches : inventaire, sauvegarde/rollback, version isolée, configuration et périmètre IN/OUT. | Réalisé localement : référence sauvegardée, variante et configuration distinctes. Aucun état distant confirmé. |
| G1 | Cinq tâches : accueil, navigation, couleurs, logo et matrice des parcours en trois clics maximum. | Qualifié localement pour l'identité et la navigation aux tailles contrôlées. Six raccourcis en un clic ; la validation métier utilisateur reste distincte. |
| G2 | Quatre tâches : vitrine publique, contact minimal, adresse officielle et exclusion inscription/paiement. | Préparation locale ; contact simulé, sans preuve d'acheminement ni collecte réelle. G2 complet non acquis. |
| G3 | Quatre tâches : authentification, trois rôles, limitation serveur par équipe et révocation. | Non commencé pour V1. Orientation Drupal et réutilisation de la logique CONNECT à qualifier ; instance, modules, protocole et exploitation à déterminer ensemble **avant AUTH-001**. |
| G4 | Quatre tâches : modèle équipes/joueurs, import, vue autorisée et mise à jour indépendante de FFT. | Non livré. Modèle et gabarit peuvent être préparés sur données fictives ; service privé conditionné par G3. |
| G5 | Trois tâches : Calendar, identification des rencontres et source unique. | Point d'accès/configuration préparables ; URL et calendrier réels absents, donc aucun parcours Google qualifié. |
| G6 | Cinq tâches : Form, identification limitée, Sheet restreint, non-réponses et lien contextualisé. | Non livré. Ressources Google et permissions à définir et vérifier, avec plusieurs joueurs et au moins deux rencontres. |
| G7 | Quatre tâches : message manuel, émetteur unique, exceptions et contrôle humain. | Modèle et procédure préparables ; aucun envoi par l'agent, aucun bon lien réel ou émetteur d'équipe encore qualifié. |
| G8 | Douze tâches : sécurité, domaine/HTTPS, accès bêta, Google/RGPD, tests, panel et documentation. | Non ouvert. Aucune qualification de production ou autorisation de déploiement déduite de la préparation. |

La [recette officielle](recette-officiel.md) consigne 87 tests Node réussis après les derniers correctifs, les sept pages contrôlées à 1440, 390 et 320 px, 25 captures et le menu vérifié à 1060/1101 px. Les résultats structurés indiquent zéro erreur JavaScript/console, requête externe, écriture HTTP ou effet d'envoi. G1 est qualifié pour la navigation vers les écrans d'action sans disposer d'URLs Google ; cela ne clôture pas leurs usages G5/G6. Les 25 observations de mise en page ne sont pas les 25 scénarios complets du plan V1. G2 reste partiel ; G3 à G8 ne sont pas acquis. Aucun « GO » métier ou bêta ne doit être déduit d'un affichage correct ou d'un bouton menant à une fonction non configurée.

## Décisions ou informations encore nécessaires

1. **Avant G3 :** qualifier l'architecture Drupal après examen de l'hébergement et des composants CONNECT réutilisables, avec dépendances, stockage des comptes/rôles, récupération, révocation, maintenance et risques. Installation dédiée ou raccordement existant restent à déterminer ensemble. La recommandation PHP de la source ne constitue plus l'orientation de la prochaine proposition ; l'OIDC générique expérimental précédent n'est pas adopté automatiquement. Aucun Drupal n'est configuré ou actif pour le club. Définir personnes, rôles, équipes autorisées, droits du Bureau et isolation vis-à-vis des autres applications.
2. **Contact réel :** décider et qualifier l'acheminement, l'antispam et le traitement des messages. Une confirmation de simulation ne vaut pas réception par le club. Responsable, information de collecte et durée de conservation restent à valider ; aucune durée inventée.
3. **Google :** fournir les ressources exactes et leur propriétaire, décider la convention des rencontres, les périmètres des Forms et les partages des Sheets. Le compte de référence confirmé ne fournit ni autorisation de connexion ni ressources actives. Qualifier l'offre Workspace, le domaine et les droits minimaux des futurs agents avant toute automatisation. Pour le support de test, définir hébergeur, boîte ou alias et destinataires, puis autoriser sa création séparément.
4. **Équipes :** identifier les équipes, catégories, capitaines/émetteurs autorisés et source des effectifs ; valider le gabarit et la gestion des corrections/doublons. La liste proposée par la spécification reste une recommandation de schéma, pas des données reçues.
5. **Avant bêta :** constater l'achat et la configuration du domaine, relever sa racine réelle, préparer sauvegarde/rollback, valider HTTPS et les accès du panel. Fixer les responsabilités et les mentions nécessaires avant toute collecte. Le panel de huit à douze personnes reste à constituer.

Le choix des 125/150 € des cours adultes et les conditions tarifaires du relevé antérieur ne sont pas tranchés par les nouveaux documents. Ils restent des réserves de contenu ; ils ne justifient pas d'ajouter une inscription complète au P0.

## Documentation de référence

- [README](../README.md) : démarrage, variantes et livrables.
- [Recette officielle](recette-officiel.md) : preuves locales, matrice des parcours et limites du lot qualifié.
- [Présentation V1 sur le sous-domaine](publier-v1-sous-domaine.md) et [recette du paquet dédié](recette-v1-sous-domaine.md) : nouvelle variante à déposer manuellement, sans authentification ni collecte.
- [Mutualisation avec CONNECT](mutualisation-connect.md) : audit des composants locaux et limites d'un futur raccordement ou héritage, sans choix d'instance ni activation.
- [Architecture](../architecture.md) : responsabilités et séparation de la V1 et des prototypes antérieurs.
- [Exigences](../requirements.md) et [roadmap](../roadmap.md) : périmètre courant et étapes restantes.
- [Décisions](../decisions.md) et [changelog](../changelog.md) : raisons et lots intégrés.
- [Consignes locales](../AGENTS.md) et [commandes sensibles](commandes-sensibles.md) : préparation autonome, décisions utilisateur et absence de publication implicite.

Les documents historiques, les classeurs et l'archive G1 reçus restent des sources intactes. Cette page trace leur intégration et les écarts ; elle ne remplace pas le backlog complet ni ses critères d'acceptation.
