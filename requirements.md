---
project: TC_Longages
document_type: requirements
title: Exigences du site et de la communication
status: active
version: git
created: 2026-09-16
updated: 2026-10-08
owner: jpdandin
tags:
  - exigences
  - validation
  - prototype
---

# Exigences


## Choix de contact demandé — 8 octobre

- Conserver le formulaire du site comme moyen principal.
- Proposer Gmail dans un nouvel onglet et la messagerie habituelle de l'appareil.
- Renvoyer toutes les actions de contact des sept pages Drupal vers ce choix.
- Préremplir seulement le destinataire du lien Gmail ; compte et envoi restent
  à l'utilisateur. Ne pas transférer le texte saisi dans le formulaire.
- Préserver les contenus édités, les droits, le calendrier, la photo et le signalement.

## Correction demandée — édition et contact, 7 octobre

- Montrer **Modifier cette page** sur les sept pages lorsque la session Drupal
  possède le droit natif de modification ; aucun nouveau compte ou droit.
- Ouvrir le formulaire éditorial et revenir sur la page après sauvegarde.
- Envoyer le formulaire de contact uniquement à `tclongages@gmail.com`, avec
  validation côté serveur, information de transmission, antispam et gestion
  des erreurs sans perte de saisie ni double envoi du même POST.
- Préserver les textes, révisions, photo, calendrier Mois et signalements.
- Réserver l'activation demandée à la préproduction protégée ; sauvegarde et
  restauration testées avant remplacement. Réception Gmail à constater
  séparément du succès du transport.

## Demande complémentaire du 7 octobre — recette du site

- Afficher par défaut le calendrier en vue Mois, en français, Europe/Paris.
- Réutiliser la PR #17 et résoudre ses conflits avec `main` avant la livraison.
- Livrer le candidat vérifié à la préproduction protégée, avec conservation
  des textes édités dans Drupal.
- Conserver Signaler un problème et vérifier l'enregistrement du message et
  son acheminement. La réussite des tests locaux ne vaut pas recette hébergée.


La demande complémentaire du 7 octobre remplace la photo d’accueil par
[Image_terrain_OK.png](Images_Photos/Image_terrain_OK.png), fournie dans le dossier de la photo originale.
Le PNG de 1448 × 1086 pixels est intégré sans modification ; l’original JPEG
reste conservé. Les contrôles d’empreinte des pages sont adaptés à cette source.


## Agenda partagé — demande locale du 7 octobre

- Afficher l’agenda fourni dans la page Calendrier, avec Europe/Paris.
- Conserver la gestion des événements dans Google Agenda et proposer un lien d’ouverture.
- Garder une largeur adaptée au téléphone et un titre accessible pour l’iframe.
- Isoler cet aperçu des sorties officielles et des autres lots en cours.
- Conserver la revue des droits de partage en attente ; aucune modification Google.
- Limiter ce lot au développement local. Le rendu Drupal hébergé, les droits des
  visiteurs et la visibilité des informations restent à qualifier avant livraison.

Voir [l’intégration Google](api/google-calendar.md).

## Cockpit et partenaires V1.1 — demandes du 6 octobre

- Rendre visibles les lots actuels, leurs changements, PR, contrôles et sites
  de recette ; ne pas confondre l'historique V1 avec l'acceptation de V1.1 ou V2.
- Conserver exactement quatre phases et les critères obligatoires existants.
  Revue, Valider et Demander des corrections exigent commentaire et confirmation ;
  Valider exige tous les critères et les prérequis du candidat exact.
- Garder les accords de merge, déploiement et ouverture distincts des revues.
- Prévoir dans **V1.1** une zone **Nos partenaires** en bas des pages publiques.
  Chaque partenaire aura nom, logo, description accessible, lien facultatif,
  ordre et visibilité gérés dans Drupal ; masquer la zone sans partenaire actif.
  Conserver les proportions des logos et vérifier mobile/ordinateur.
- Logos, noms définitifs, liens et accord d'affichage : **TBD — non fournis**.
  La bannière est prévue, pas encore implémentée dans le candidat support/édition.

Le [guide du cockpit](docs/piloter-versions.md) précise l'état des fonctions.


## Édition et préproduction demandées le 6 octobre

- Modifier facilement dans Drupal les titres, présentations, rubriques, questions
  et légendes des sept pages ; préserver navigation, fonctions et sources métier.
- Conserver les textes édités et leurs révisions lors d'une livraison de code.
- Rendre l'édition et le suivi privés, sans nouveaux comptes ou grants automatiques.
- Déposer le candidat support/édition sur la seule préproduction après sauvegarde
  fraîche et restauration vérifiée ; conserver maintenance, HTTPS et non-indexation.
- Garder les requêtes web de recette en capture ; qualifier séparément une réception
  réelle à la boîte support. La production reste hors de cet accord.

## Signalements du site V1 — demande du 6 octobre

- Proposer depuis chacune des sept pages un bouton vers un formulaire de
  problème ou besoin concernant le site.
- Limiter le formulaire visible à quatre éléments : type de demande,
  description, e-mail facultatif pour la réponse et confirmation obligatoire
  de la transmission au club. Type et description restent obligatoires.
- Conserver automatiquement la page d'origine et attribuer un objet selon
  le type, sans nouvelle saisie ni modification des demandes existantes.
- Adresser la notification à `support@tclongages.fr`, créée si absente.
- Donner au responsable un suivi privé utilisable en développement, avec
  état, responsable et journal de traitement.
- Conserver une demande si le courriel échoue et distinguer enregistrement,
  capture de test, acceptation par le transport et réception en boîte.
- Ne pas exposer les coordonnées ni les notes sur le dépôt GitHub public.
- Compléter les mentions et règles de conservation de la stratégie annoncée :
  **TBD**, source absente des copies locales et de la branche principale
  observée. Les quatre champs sont précisés directement par le responsable.

Ces exigences sont traitées par un correctif V1 distinct de la V2 Bureau.
Le [guide support](docs/signalements-support.md) décrit les contrôles et le
périmètre réellement implémenté.

## Communication V2 — demande du 5 octobre

Intégrer la communication du prototype au Bureau Drupal, liens externes compris.
Conserver brouillons et affiches en base ; réserver édition, validation et
publication au Bureau/administrateur actif. Relire les quatre aperçus avant
validation ; confirmer séparément la publication sur le site. Retirer la
publication à toute modification et refuser une ancienne révision. Exiger titre,
texte ou affiche, description d'image et confirmation personnelle des actions.
Permettre retrait, archivage et restauration sans publication. Préparer le
partage manuel de la révision validée, sans envoi automatique ni statut de
livraison inventé ; respecter la limite ADOC sans tronquer. Le
[guide V2](docs/communication-bureau.md) décrit réalisation et limites.

## V2 locale — comptes et Bureau, après livraison V1

La demande du 5 octobre ouvre une itération locale avec comptes Drupal et
espace Bureau. La précision suivante donne priorité à **l'enregistrement des
nouveaux adhérents** et reporte les équipes. Permettre au Bureau de créer et
modifier un dossier Adulte/Mineur par saison, rechercher une personne et suivre
les états Reçu, À compléter et Vérifié par le Bureau. Pour un mineur, demander
responsable légal et moyen de contact. Ne pas identifier une personne par son
seul email familial ni créer automatiquement une licence FFT ou un paiement.

Contrôler URL directes, CSRF, blocage de compte, doublons et anciennes fiches ;
refuser les dossiers aux visiteurs et capitaines. Stocker en base et recetter sur
des exemples fictifs. La gestion native des comptes permet création, modification,
blocage/réactivation et remplacement administratif du mot de passe : le Bureau
gère seulement les capitaines ; l'administrateur peut aussi gérer le Bureau.
Aucune élévation de rôle ni modification de soi-même par ces formulaires métier.
Le [guide du candidat](docs/comptes-et-bureau.md) précise les limites. Les champs
complets, habilitations réelles, consentements, import/export et modalités de
collecte restent à examiner avant utilisation avec des dossiers réels.

Afficher les compétitions publiques et les événements de l'agenda Google sur
le site. Le responsable choisit un agenda dédié aux événements publics ; le
compte Gmail peut rester identique à celui du club. Le calendrier principal
fourni refuse aujourd'hui l'accès public. Le composant doit rester désactivé
tant que l'ID et le partage du nouvel agenda ne sont pas vérifiés. Pour la suite,
les capitaines renseignent présence/absence dans FFT ; qualifier sa lecture et
son périmètre privé avant raccordement, sans déduire une API de pages visibles.

## Priorité V1 publique — 5 octobre

Le responsable reporte en V2 les comptes Bureau/Capitaine et les droits par
équipe. La V1 livre les sept pages publiques existantes sous Drupal, avec les
liens réels déjà raccordés et le contact du club. Ne pas déclarer actifs
Calendar, Forms, effectifs ou espace interne tant que leurs ressources et
droits ne sont pas qualifiés. L'édition locale en préparation ne vaut pas
édition hébergée. Le cockpit personnel reste hors du site public.

Chaque livraison doit identifier le paquet exact, préserver les données et
paramètres propres à la cible et disposer d'un retour arrière éprouvé. Pour
les versions suivantes, conserver le verrou des dépendances, recetter le même
ZIP et effectuer une mise à jour ; ne pas relancer la première installation.

## Site de recette et langue — 4 octobre

Le lien « environnement de validation » des PR doit ouvrir le site TC Longages,
actuellement [sa préproduction](https://preprod.tclongages.fr/). Pendant la
maintenance, le responsable se connecte dans Drupal puis revient à l’accueil.
Le suivi sert au pilotage personnel et ne remplace pas la recette du site.
Conserver les libellés obligatoires et les coches humaines lors de toute
correction de lien ; identifier séparément le candidat du site et celui d’un
éventuel changement de l’outil de suivi. L’administration Drupal et la page
de connexion doivent être proposées en français.

## Reprise d’une validation devenue historique

Depuis la demande du 5 octobre, la validation locale doit enregistrer en un
seul clic tous les critères cochés, le commentaire et la confirmation
personnelle, sans sauvegarde intermédiaire obligatoire. Ces trois éléments
restent requis et contrôlés côté serveur ; conserver leurs libellés.
La dernière demande du 5 octobre retire aussi l’autorisation de suite : garder
exactement Revue, Valider et Demander des corrections. Après validation, la phase
locale suivante passe en cours sans décision supplémentaire. Conserver les traces
antérieures et les accords propres à l’hébergement. Refuser côté serveur toute
validation tant que la PR candidate n’est pas fusionnée et vérifiée sur sa version
exacte ; revérifier GitHub sans cache lors du clic. Un état inconnu ou indisponible
reste bloquant. Les acceptations antérieures restent historiques à requalifier.

Le cockpit doit permettre de reprendre personnellement la revue d’une phase
locale dont la validation ne couvre plus la version ou le périmètre courant.
Conserver la décision précédente, les critères et les journaux ; exiger de
nouvelles preuves puis une nouvelle confirmation avant approbation. La reprise
ne doit accorder ni préproduction, ni mise en production, ni ouverture.
L’enregistrement d’une note et celui des cases de recette doivent rester
distincts et clairement indiqués, sans changer les libellés obligatoires.

## Recette des comptes et de l’édition — clarification du 4 octobre

Le responsable demande des PR identifiables pour tester la création des comptes,
leurs droits et l’édition avant approbation. Le [dossier de préproduction](docs/suivi-chantier/04-phase.md)
précise la réalisation constatée et les essais attendus. L’utilisateur SQL,
l’administrateur Drupal et les comptes métier du club ont des rôles distincts.
Le paquet V1 actuel n’inclut ni rôles Bureau/Capitaine ni édition des sept pages
dans Drupal. TBD — périmètre de la PR fonctionnelle à confirmer ; aucun accord
de report ni caractère non applicable n’est supposé. Préserver le libellé
« Comptes, droits et édition vérifiés si applicables » dans la checklist.

## Transport et choix de compte — correction du 4 octobre

La préproduction doit disposer d'un HTTPS reconnu. L'adresse technique
proposée en `universe.wf` est retirée : l'outil Let's Encrypt o2switch ne
l'accepte pas. Le [plan courant](docs/preparer-lune-tc.md) porte les sources et
les voies à qualifier. Un choix du compte principal doit expliciter la
séparation des fichiers et bases, la perte du cloisonnement entre comptes,
les effets éventuels du PHP partagé et l'adaptation de l'outil avant écriture.
Aucun compte, privilège, secret ou accord de la lune n'est transposé par défaut.

## Qualification du compte principal — 4 octobre

Le [lot courant](docs/preparer-lune-tc.md#lot-du-compte-principal) est autorisé
et effectué. Les 18 extensions dérivées du verrou Composer/pilote SQL sont
chargées sous PHP CLI et HTTP 8.3.33. La sélection PHP est partagée au compte.
La base dédiée vide est en `utf8mb4_unicode_ci`, avec moteur par défaut InnoDB
et dix droits limités ; les identifiants restent privés et le mot de passe
est soumis personnellement. La connexion PDO a réussi après synchronisation
personnelle du fichier privé ; Drupal est installé en maintenance. La recette
connectée des comptes et droits reste distincte de ce précontrôle SQL.

L’adaptateur doit lier le rôle, compte, home, racines Composer/Web, base et
utilisateur SQL du reçu à ceux du profil. Refuser une preuve de Lune, un
autre compte ou l’hôte officiel avant toute écriture. Rafraîchir DNS, HTTPS,
PHP HTTP et fermeture de moins d’une heure avant son utilisation. La
restauration privée des fichiers actuels ne vaut pas restauration du futur
Drupal, SQL, DNS ou certificat.

## Runtimes observés avant changement de cible — historique du 4 octobre

Le vérificateur et l'outil de première installation doivent pouvoir utiliser
Python 3.6.8 et PHP CLI 8.3.33 constatés dans le terminal cPanel existant. La
construction GitHub conserve son environnement propre. Le profil d'écriture
est limité à la préproduction vide ; mot de passe SQL et nouveau compte Drupal
sont soumis personnellement sur le serveur, sans argument ou sortie sensible.
HTTPS et le PHP HTTP restent des conditions distinctes. Les tests Linux locaux
ne prouvent ni cette exécution hébergée ni le retour arrière SQL.

## Exigence de reprise — Actions GitHub, 1er octobre

La demande du 1er octobre exige la réutilisation des fonctionnalités des Actions du site Drupal AVEREO et une consommation de modèles limitée. Les comptes, secrets et cibles TC doivent rester distincts ; la préparation doit identifier le commit et le candidat exacts sans valoir autorisation de mise en production. La réalisation et ses limites sont dans le [workflow de référence](workflows/preparer-livraison.md).

## Contraintes constatées le 3 octobre

Un sous-domaine du compte principal ne peut pas être rattaché à une lune distincte avec la configuration o2switch observée. `preprod.tclongages.fr` reste une intention non réalisable sur cette lune à ce stade ; une adresse alternative exige un accord sur sa cible et la qualification DNS/HTTPS. Le réglage PHP du nouveau compte et les droits SQL doivent conserver leur périmètre isolé. Voir le [reçu](data/activation-lune-verification.json).

## Parcours de mise en ligne simplifié

- Suivre la V1 publique dans quatre étapes opérationnelles, avec état et preuves séparés des décisions historiques des huit phases ; rattacher ces étapes au suivi existant, sans nouvelle interface.
- Déployer d'abord les sept pages publiques sous Drupal. Garder fermés Bureau, formulaires Google, automatisations de communication et collecte tant que leurs propres recettes ne sont pas terminées.
- Utiliser `preprod.tclongages.fr` comme cible de recette envisagée ; vérifier DNS, HTTPS, racine, PHP, SQL, sauvegarde et restauration avant écriture distante.
- Préparer un candidat reproductible depuis les sources V1 et `composer.lock` ; garder les pages hors webroot, la maintenance native et l'absence d'indexation par défaut.
- Exiger une décision explicite sur la livraison en production puis sur l'ouverture publique du site exact, avec retour arrière vérifiable.

## Pilotage adopté le 29 septembre

- suivre le projet dans un profil JSON et un registre de phases/événements distincts des lots métier G0–G8 ; conserver les acquis et les sources.
- distinguer exécution technique et validation humaine ; aucun accord AVEREO transposé au club.
- préparer la revue dans le dépôt dédié communiqué, sans publication au push/merge, sans cocher les confirmations humaines. Git local est raccordé au dépôt public ; les protections et exécutions effectives se vérifient dans le reçu de revue.
- qualifier la préproduction, le candidat, la sauvegarde fraîche et sa restauration avant écriture de production ; autoriser séparément livraison et ouverture.
- interface HTML avec commentaires persistants, critères et trois décisions Revue, Valider et Demander des corrections ; révision concurrente et historique ; identité déclarée uniquement en boucle locale, authentification à qualifier avant partage.
- présenter dans chaque phase uniquement les PR qui lui sont rattachées, avec leur état public vérifié sur GitHub ; une PR fusionnée ne vaut pas validation de la phase.
- installation Drupal dédiée au club et maintenance par le moteur natif sur toutes les pages, sans renommage de fichier pour cette nouvelle variante.
- exception d’installation bornée et tracée, puis retour aux contrôles normaux ; ni secret, ni contrôle des requêtes, ni autorisation de production désactivés.

Ces exigences sont reliées aux critères des [dossiers de phases](docs/suivi-chantier/00-phase.md), aux [tests de l'adaptateur](tests/framework.test.mjs) et aux contrôles décrits dans le [guide](docs/framework-developpement.md). La chaîne complète n'est pas qualifiée. Le domaine et sa racine sont identifiés dans cPanel ; le certificat initialement autosigné est remplacé le 5 octobre par un certificat gratuit reconnu sur les deux noms officiels. La copie distincte du paquet et sa base vide sont préparées après accord ; restauration SQL, recette et bascule restent à qualifier. Voir [le parcours actuel](docs/parcours-mise-en-ligne.md).


## Périmètre courant — officiel V1

Les 30 arbitrages reçus le 24 septembre définissent le P0 courant ; leur provenance et les écarts de sources figurent dans [l'intégration officielle](docs/integration-officiel.md). Les exigences F01 à F42 plus bas décrivent le prototype antérieur conservé. Elles ne rendent pas l'inscription complète, le modèle à deux comptes ou la présentation HTTP sans authentification admissibles pour la bêta V1.

| Référence | Exigence V1 | État ou condition |
|---|---|---|
| V1-01 | Vitrine, équipes, calendrier et disponibilités ; accueil concentré et cinq à six accès rapides en trois clics maximum. | G0 réalisé localement ; G1 qualifié pour l'identité et l'accès aux écrans selon la [recette officielle](docs/recette-officiel.md), sans validation métier ou bêta acquise. Une destination non configurée reste signalée. |
| V1-02 | Rouge/blanc provisoire paramétrable, logo historique reçu et parité desktop/mobile. | Fichier fourni réutilisé ; aucune recréation, couleurs finales non figées. |
| V1-03 | Contact minimal : nom, e-mail ou téléphone, message. | Simulation d'interface pour G2 ; transport réel, antispam et information de collecte à qualifier. |
| V1-04 | Admin/Bureau/Capitaine ; comptes internes seulement, autorisation serveur et périmètre d'équipe, révocation possible. | Drupal dédié confirmé ; socle CMS local installé. Droits des trois rôles, périmètres par équipe, récupération et exploitation hébergée à qualifier avant G3 ; aucun compte métier réel ajouté. |
| V1-05 | Équipes et catégories/tags ; source d'effectifs fichier/Google Sheet importable, aucune liste complète publique. | Gabarit et import réels à réaliser après validation et contrôles d'accès. |
| V1-06 | Google Calendar comme référence des rencontres ; Form contextualisé avec Oui/Non/Je ne sais pas et nom contrôlé. | URLs absentes, aucune ressource Google active ; ne pas créer de calendrier concurrent. |
| V1-07 | Sheet de pilotage réservé aux responsables, distinguant Oui/Non/Je ne sais pas/Non répondu. | Partages Google et périmètres à vérifier avant toute donnée réelle. |
| V1-08 | WhatsApp manuel par capitaine ou responsable désigné ; exceptions traitées par appel humain et groupes actuels conservés. | Modèles préparables ; aucun message externe envoyé par l'agent. Automatisation et appel depuis une fiche en P1. |
| V1-09 | Aucun paiement ni dossier d'adhésion complet dans le P0 officiel. | Rubrique informative pour jouer autorisée ; démonstration ancienne isolée. Actualités administrables après bêta. |
| V1-10 | Bêta restreinte sur domaine officiel, HTTPS, noindex, RGPD, tests et panel de huit à douze personnes. | Domaine futur `tclongages.fr` confirmé ; activation, certificat, racine, panel et durées de conservation non validés. |
| V1-11 | Utiliser `tclongages@gmail.com` pour tous les contacts courants et comme compte Google de référence. Prévoir `support@tclongages.fr` pour les tests. | Contact actualisé dans les sources et variantes régénérées ; support planifié et inactif. Aucune création de boîte/alias, redirection, connexion Google ou automatisation déduite des adresses. Offre Workspace et ressources exactes à confirmer. |
| V1-12 | Préserver les originaux, isoler la variante officielle et conserver un retour arrière local identifiable. | Baseline locale du 24 septembre, sans sauvegarde distante ni secrets ; aucun déploiement implicite. |
| V1-13 | Distribuer un aperçu local distinct de la bêta à activer. | Sept HTML et trois fichiers de contrôle ; règle Apache 503 inconditionnelle, sans ouverture par renommage du témoin. Consultation par serveur local sur 4180 ; aucune donnée privée, compte ou ressource Google active. |
| V1-14 | Fournir une archive V1 séparée pour le sous-domaine, avec dépôt, ouverture et fermeture réalisés par l'utilisateur avant la future production. | `tc-longages-v1-demo-o2switch.zip`, dix fichiers, fermé par défaut. `maintenance.inactive` seul ouvre ; tous les autres états ferment avec 503. Ouvert, servir seulement les sept pages et `robots.txt`, refuser les anciennes routes même résiduelles. Aucune authentification, donnée privée ou transmission Contact ; qualification distante à effectuer après le dépôt. |

La [spécification V1 inventoriée](data/officiel-sources.json) (source externe `TC_Longages_Specifications_Evolution_Prototype_Codex_V1.md`, non incluse dans ce checkout) et ses 25 scénarios décrivent l'acceptation complète. Une recette de navigation ou de démonstration statique ne valide pas Google, l'accès d'un capitaine, l'acheminement du contact ou l'ouverture HTTPS. La recommandation PHP de la source précède l'orientation Drupal demandée ultérieurement ; aucun schéma d'authentification n'est implémenté par cette orientation. La présentation temporaire du sous-domaine suit son [guide spécifique](docs/publier-v1-sous-domaine.md), sans assouplir les conditions de bêta réelle.

## Exigences des prototypes antérieurs conservés

## Besoins confirmés pour les prototypes antérieurs

L'utilisateur demande un prototype disponible rapidement, une seconde page pour gérer la communication du club, le relais des informations sur Facebook et la diffusion dans les groupes WhatsApp. Il conserve la décision de mise en ligne et a confirmé la **validation de chaque actualité avant son envoi**. Le prototype ajoute un partage manuel WhatsApp ; le relais automatique Facebook reste à réaliser.

Il avait également demandé de réserver la gestion des inscriptions et la communication aux membres du bureau, puis fourni le contexte et le prompt de conception. L'utilisateur avait confirmé un **formulaire destiné aux adhérents distinct de la gestion privée du bureau**, puis le besoin de **deux comptes : un compte personnel administrateur et un compte générique bureau**. Ces exigences expliquent les expérimentations conservées ; elles ne limitent pas les rôles de la nouvelle V1 et n'ouvrent pas l'inscription complète en P0.

La [proposition de processus d'inscription](docs/processus-inscriptions.md) porte sur la saison 2026-2027 : demandes web et papier dans une source commune, disponibilités paramétrables, puis constitution manuelle des groupes par le club. Il s'agit d'un cadrage à valider, pas d'une collecte ouverte. Paiement en ligne, confirmations automatiques, SMS, OCR et affectation automatique des groupes sont hors périmètre de cette première proposition.

L'utilisateur avait demandé de partager et parcourir le prototype complet. Une visite fictive séparée permet d'examiner les écrans d'inscription, de bureau et de communication sans créer de comptes réels ni ouvrir le service de production.

Pour o2switch, le premier choix de vitrine seule est conservé comme option distincte. La demande de septembre était de **remplacer cette vitrine par la démonstration complète** sur `http://tclongages.daje3540.odns.fr/`. L'utilisateur avait choisi de **réaliser lui-même le dépôt** ; aucun déploiement par l'agent n'est autorisé.

Cette variante sert à **montrer la maquette avec des dossiers fictifs**, par une copie statique destinée à l'adresse HTTP choisie alors. Le code OIDC préparé reste expérimental et inactif ; aucun compte, choix de fournisseur ou certificat n'est requis pour cette seule présentation sans dossier réel d'adhérent. Cette exception ne s'applique pas à la bêta officielle V1.

L'utilisateur fournit ensuite le logo conservé dans `Images_Photos/Logo.jpeg` pour remplacer temporairement le signe graphique avant une version vectorielle, ainsi qu'`Affiche.jpeg` pour essayer les images dans la communication. Il demande un nouveau ZIP complet qu'il déposera lui-même. Ces visuels sont autorisés comme supports fournis ; ils ne rendent pas réels les dossiers de démonstration et ne constituent pas une publication d'actualité déjà validée.

Le 17 septembre, la photo réelle du court fournie remplace l'image d'accueil. Les deux fiches PDF alimentent une FAQ tarifaire et les formules de démonstration depuis un relevé commun, en conservant les conditions non précisées. La communication ajoute un quatrième aperçu ADOC et une préparation simulée d'après la capture fournie, sans accès à ADOC ni publication sur Ten'Up.

## Exigences fonctionnelles

| Référence | Exigence | Périmètre |
|---|---|---|
| F01 | Présenter le club avec une navigation utilisable sur ordinateur et mobile. | Vitrine locale. |
| F02 | Utiliser `tclongages@gmail.com` pour les contacts, conserver Ten'Up et Facebook. | Consigne actualisée le 24 septembre pour toutes les variantes actives ; les pièces et preuves historiques restent intactes. |
| F03 | Afficher la photo du court fournie et la seconde photographie d'illustration sans dépendance aux anciens chemins externes. | Génération autonome, attribution de la photo fournie au club sans auteur inventé et crédit Nicholas Bullett conservé pour l'illustration. |
| F04 | Préparer des actualités sur une seconde page. | Prototype local. |
| F05 | Distinguer enregistrement d'un brouillon et validation d'une actualité. | Prototype local et futur service réel. |
| F06 | Montrer l'effet de la validation dans un aperçu privé et sur le relais Facebook simulé. | Aperçu réservé au bureau ; aucune lecture des actualités locales sur la vitrine publique. |
| F07 | Exporter et importer les données éditoriales en JSON. | Sauvegarde manuelle locale. |
| F08 | Relayer réellement une actualité validée vers Facebook. | À implémenter après choix techniques et autorisation. |
| F09 | Ne pas publier de brouillon privé ou de page de gestion réelle non protégée. | Toute future exploitation réelle. Les outils de démonstration sont librement accessibles pendant leur présentation, avec dossiers fictifs et supports fournis pour la présentation. |
| F10 | Présenter un aperçu WhatsApp et préparer le partage de la révision validée. | Prototype protégé : ouverture de WhatsApp ou copie du message ; sélection des groupes et envoi par le responsable. Visite fictive : ouverture et copie simulées. |
| F11 | Bloquer le partage d'une révision modifiée ou devenue obsolète. | Vérification avant copie ou ouverture ; nouvelle validation après modification. |
| F12 | Prévoir un repli si le lien est trop long ou si le presse-papiers est indisponible. | Copie du message et texte sélectionnable. |
| F13 | Ne pas présenter une ouverture ou une copie WhatsApp comme un envoi réussi. | Aucun statut de livraison ni destinataire enregistré. |
| F14 | Réserver communication et gestion réelle des inscriptions au bureau. | HTTP Basic local sur 4173 ; couche OIDC expérimentale distincte sur 4175, activation reportée. La visite sur 4174 est libre et n'accueille aucun dossier réel d'adhérent. |
| F15 | Enrichir les inscriptions à partir des sources fournies et de leur validation par le club. | Proposition et écrans fictifs disponibles ; page protégée toujours sans formulaire ni collecte. |
| F16 | Regrouper les pages internes dans un accueil du bureau. | Hub local et aperçu des actualités également protégés. |
| F17 | Fermer les pages internes sans configuration de comptes valide. | Basic : compte actif de rôle `bureau`. OIDC : identité explicitement autorisée, rôle `admin` ou `bureau` ; aucun accès réel configuré. |
| F18 | Distinguer le formulaire adhérent de la gestion réservée au bureau. | Choix confirmé, représenté par deux écrans dans la visite fictive ; services réels non implémentés. |
| F19 | Importer et exporter les inscriptions en XLS et CSV depuis l'espace bureau. | Exigence réelle à réaliser. Visite : import d'un lot prédéfini simulé, sans fichier, et export CSV des exemples ; aucun export XLS. XLSX proposé en complément. |
| F20 | Permettre une visite complète et le partage d'une archive autonome. | Sept pages fictives dans `prototype/`, archive constituée par liste explicite, aucun compte nécessaire. |
| F21 | Isoler les essais de visite des données du prototype protégé. | Origine sur le port 4174 et clés de stockage dédiées ; aucune lecture de comptes ou de dossiers réels. |
| F22 | Empêcher la visite de déclencher une diffusion réelle. | Facebook, WhatsApp, ADOC et copie simulés ; import JSON réel désactivé. |
| F23 | Conserver une archive o2switch limitée à la vitrine publique comme option distincte. | Un seul `index.html` à la racine, issu de `release/`, avec ressources intégrées ; aucun bureau, formulaire ou contenu fictif. Le dépôt envisagé à cette étape visait le paquet de démonstration complète. |
| F24 | Identifier le paquet public et documenter son dépôt manuel. | Manifeste séparé hors ZIP, guide et recette locale ; cible exacte, racine et HTTPS à confirmer avant dépôt. |
| F25 | Préparer la connexion des deux comptes confirmés sans les activer pendant le maquettage. | Service OIDC expérimental séparé sur 4175 ; exemples inactifs, aucun compte réel créé. |
| F26 | Autoriser chaque identité par le couple exact fournisseur/identifiant stable, avec contrôle serveur des rôles. | Liste de deux entrées `iss`/`sub` ; `admin` hérite des quatre pages bureau et consulte `/admin/acces` en lecture seule. Aucun enregistrement automatique d'un compte inconnu. |
| F27 | Borner les sessions et permettre la déconnexion du site. | Préparation OIDC : cent sessions maximum en mémoire, durée maximale huit heures, inactivité trente minutes ; redémarrage déconnectant et session fournisseur distincte. |
| F28 | Reporter la migration du service réel jusqu'à la phase décidée après l'achat du domaine. | Présentation fictive prioritaire ; aucun achat, changement DNS/HTTPS ou accès réel activé par la préparation. |
| F29 | Préparer la démonstration complète pour le dépôt manuel de l'utilisateur à l'adresse HTTP confirmée. | ZIP de dix fichiers : sept HTML, `robots.txt`, `.htaccess`, `maintenance.active` ; aucun dépôt par l'agent. |
| F30 | Garder vitrine et formulaire dans les menus publics, avec les outils accessibles uniquement par adresse directe depuis l'extérieur. | Aucun lien public vers bureau, inscriptions, communication, visite ou aperçu ; aucune authentification, aucune confidentialité promise. |
| F31 | Fermer la présentation par défaut et permettre son ouverture temporaire. | `.htaccess` répond 503 en présence de `maintenance.active` ; renommage en `.inactive` pour ouvrir, inverse pour fermer, contrôles distants avant et après ouverture. |
| F32 | Isoler les essais de la copie hébergée et conserver les garde-fous fictifs. | Clés `tcl.hosted-demo.*`, imports JSON ou de dossiers réels bloqués, réseaux sociaux simulés ; ajout local d'image permis, aucune base partagée ni connexion OIDC. |
| F33 | Utiliser le logo JPEG fourni en attendant la version vectorielle. | Original conservé, présentation par CSS et ressource intégrée aux HTML ; aucune vectorisation prétendue. |
| F34 | Ajouter, remplacer ou retirer une image par actualité. | Fichier JPEG/PNG/WebP préparé localement, aperçu entier, description obligatoire ; limites définies dans l'[architecture](architecture.md). |
| F35 | Valider l'image avec le contenu éditorial. | Titre obligatoire, texte facultatif en présence d'une affiche ; toute modification d'image ou de description demande enregistrement puis nouvelle validation. |
| F36 | Conserver les actualités illustrées dans le navigateur avec un budget borné. | Pas de téléversement serveur ; refus de dépassement sans remplacer la sauvegarde, compatibilité avec les actualités sans image et image incluse dans l'export JSON. |
| F37 | Proposer volontairement l'affiche fournie dans l'éditeur de démonstration. | Bouton « Utiliser l’affiche exemple », sans actualité prévalidée ni annonce automatique ; import JSON réel toujours bloqué. |
| F38 | Distinguer aperçu d'image et partage effectif sur un service externe. | Facebook et ADOC toujours simulés, WhatsApp également dans les démonstrations ; lien WhatsApp du prototype protégé limité au texte, image à joindre manuellement lors d'un futur envoi réel. |
| F39 | Présenter les tarifs des fiches 2026–2027 sans ajouter de conditions absentes. | FAQ et formules fictives générées depuis [le relevé commun](data/tarifs-inscription.json) ; neuf lignes, choix 125/150 € et remise famille laissés à confirmer. Aucun tarif de terrain ou de licence inventé. |
| F40 | Préparer une actualité validée pour l'aperçu ADOC. | Quatrième aperçu et bouton de simulation seulement ; titre, contenu avec lien de 2 000 caractères maximum, photo et choix Ten'Up. Aucune connexion, API, copie ni publication ADOC réelle. |
| F41 | Limiter la préparation ADOC sans bloquer les autres usages de l'actualité. | Dépassement de 2 000 caractères : seul le bouton ADOC est bloqué, sans coupe du texte. Contraintes observées et limites de la simulation dans [api/adoc.md](api/adoc.md). |
| F42 | Conserver et valider le choix local de visibilité Ten'Up. | Valeur « Non » par défaut, compatible avec les anciennes actualités ; une modification demande enregistrement puis nouvelle validation. Aucune visibilité réelle modifiée. |

## Exigences techniques et contraintes

- Sources éditables distinctes des sorties générées ; Markdown comme référence documentaire.
- Génération locale reproductible depuis les sources, sans transfert automatique.
- Node.js 22.9.0 minimum pour le projet complet ; `openid-client` 6.8.8 et dépendances verrouillées, installation par `npm.cmd ci --ignore-scripts`. La visite autonome demeure compatible avec Node.js 22.
- Aperçu lié uniquement à l'interface locale de l'ordinateur.
- Contrôle des routes bureau côté serveur local ; jamais un simple masquage de lien ou un mot de passe dans le JavaScript client.
- HTTP Basic local : configuration privée des comptes avec mots de passe dérivés par scrypt. OIDC : secrets dans la configuration serveur privée et liste d'identités séparée, jamais de mot de passe fournisseur conservé par le site. Aucun compte réel créé sans définition des accès et accord correspondant.
- OIDC : Authorization Code avec PKCE, contrôle de `state`, `nonce`, signature et émetteur ; aucune autorisation fondée sur la seule adresse e-mail. Liste privée relue à chaque requête protégée pour appliquer les retraits d'accès.
- HTTPS reconnu avant activation Internet ; exception HTTP limitée à une recette explicitement configurée sur la boucle locale. Vérifier le proxy TLS et séparer la racine applicative de la racine publique avant hébergement.
- Sessions OIDC actuelles limitées à un processus ; prévoir une solution partagée avant un hébergement utilisant plusieurs processus. La connexion ne transforme pas `localStorage` en base commune ni en cloisonnement individuel.
- Ne pas assimiler HTTP Basic local à une protection prête à déployer ou à un cloisonnement des données de `localStorage` par personne.
- Aucune clé ou aucun jeton de publication dans le code client, les exports éditoriaux ou le stockage du navigateur.
- Préservation des originaux et des crédits photographiques.
- Préservation exacte des fichiers `Images_Photos/Logo.jpeg`, `Images_Photos/Image_terrain.jpg` et `Affiche.jpeg` fournis ; leurs versions d'affichage sont dérivées. Le JPEG du logo est temporaire, pas une version vectorielle.
- Une image par actualité, préparée dans le navigateur dans les limites de l'architecture ; aucune image SVG, HTML ou distante importée par ce parcours. Les dossiers d'adhérents restent fictifs, tandis que les supports explicitement fournis peuvent être présentés comme exemples.
- Ne pas inventer de données officielles. Les horaires et groupes fictifs de la visite doivent porter une mention d'exemple ; les tarifs issus des fiches gardent leurs ambiguïtés jusqu'à validation du club.
- Instructions sensibles expliquées en effets concrets avant exécution ; utilisateur décisionnaire.
- Accord explicite préalable pour toute production, modification DNS/HTTPS, configuration d'accès/secrets et publication Facebook réelle.
- Partage WhatsApp déclenché volontairement par le responsable après validation, puis destinataires et envoi confirmés dans WhatsApp. Ce parcours ne requiert pas une nouvelle approbation conversationnelle de l'agent pour chaque clic.
- Un agent ne doit pas déclencher lui-même un essai d'envoi externe sans instruction explicite correspondante.
- Aucun changement des autres sites ou données du compte o2switch.
- L'archive de visite doit être construite à partir d'une liste explicite, sans `.local/`, secrets, exports privés ou fichiers réels d'adhérents.
- L'archive de vitrine seule doit contenir uniquement `index.html`, sans `.htaccess` ni lanceur. Le paquet distinct de présentation complète contient les dix fichiers explicitement autorisés, dont sa fermeture Apache, mais aucun serveur Node.js, compte ou dossier réel d’adhérent. Leur génération n'utilise aucun accès d'hébergement.
- Dépôt de présentation : racine cPanel confirmée, sauvegarde privée de l'existant et extraction hors racine publique ; règle de fermeture installée et contrôlée avant copie des pages. Ne pas remplacer aveuglément une configuration WordPress ou celle d'un autre site.
- La fermeture et le retour arrière concernent toutes les pages de démonstration, pas seulement l'index. Aucun changement DNS n'est requis pour l'ouverture ou la fermeture temporaire.

## Hypothèses et inconnues

| Sujet | État et effet sur la suite |
|---|---|
| HTML corrigé annoncé dans la passation | Absent des fichiers disponibles : reconstruction depuis l'ancien pack, sans revendication d'identité avec ce HTML. |
| Production actuelle | À vérifier séparément ; la passation fournit uniquement un état historique daté. |
| Hébergement | Présentation confirmée sur `http://tclongages.daje3540.odns.fr/`, dépôt par l'utilisateur. Racine cPanel, existant, compatibilité Apache et cache distant à vérifier. |
| HTTPS et adresse durable | Aucun forçage HTTPS dans la présentation fictive ; domaine et HTTPS reconnus à définir avant service réel. |
| Facebook | Page cible et droits d'administration TBD ; lien public seul insuffisant. |
| WhatsApp | Groupes existants ou nouveaux à utiliser TBD ; droits d'écriture et parcours dans le client à confirmer par le responsable. Aucune automatisation par API promise. |
| Comptes bureau | Besoin confirmé : un compte personnel administrateur et un compte générique bureau. Fournisseur et couples `iss`/`sub` TBD lors de l'activation future ; aucun compte réel configuré. |
| Inscriptions | Processus proposé et écrans fictifs de visite ; champs, tarifs, autorisations, disponibilités et reprise réelle à valider. Aucune API ni base métier centrale implémentée. |
| Stockage des inscriptions | SQLite proposé, choix non adopté ; vérifier l'hébergement et arrêter le modèle de données avant développement. |
| Travail à plusieurs | Non couvert par `localStorage`. OIDC et sessions en mémoire préparés à titre expérimental ; activation reportée, stockage partagé des sessions en cas de plusieurs processus et base métier centrale TBD. |
| Informations du club | Logo JPEG temporaire, affiche et photo réelle du court fournis ; version vectorielle future. Montants des deux fiches relevés ; choix des cours adultes, remise famille, licence, durée et horaires réels restent à confirmer. |
| ADOC / Ten'Up | Capture utilisée comme référence d'interface, sans qualification d'une API. Moyens autorisés d'intégration, droits et comportement du service réel TBD avant tout projet d'envoi. |

## Critères de recette du prototype protégé sur le port 4173

- La vitrine s'ouvre sans connexion et les images sont visibles ; elle ne lit pas les actualités du stockage local.
- Les quatre pages du bureau refusent l'accès sans configuration active ou sans authentification valable ; un compte actif de rôle `bureau` permet leur consultation.
- Un compte désactivé ou d'un autre rôle ne permet pas l'accès ; les changements de configuration sont pris en compte aux requêtes suivantes.
- La page inscriptions ne comporte ni formulaire ni collecte de données.
- Les six HTML sont générés ; `release/` contient seulement la vitrine.
- Les contacts actifs sont cohérents avec l'adresse officielle.
- Un brouillon enregistré n'est pas présenté comme envoyé.
- La validation est une action distincte et aucun appel de publication Facebook réelle n'existe.
- L'aperçu WhatsApp reprend le contenu attendu et son lien encode correctement le texte.
- Le partage WhatsApp exige une révision validée encore à jour et reste désactivé en présence de modifications non sauvegardées.
- Le repli pour lien long et l'indisponibilité du presse-papiers conservent un texte copiable.
- Ouvrir WhatsApp ou copier le texte ne déclenche aucun statut « envoyé » ou « livré » dans le prototype.
- L'export/import conserve les données attendues et les entrées invalides sont traitées sans injection de HTML.
- L'import conserve le format v1 et remet les actualités à l'état brouillon avant toute nouvelle validation.
- L'image choisie et sa description persistent après rechargement ; remplacement ou retrait demande une nouvelle validation, et un fichier invalide ou un quota dépassé ne détruit pas l'enregistrement précédent.
- Les images longues restent entièrement visibles ; un aperçu WhatsApp illustré ne vaut pas pièce jointe transmise par le lien de partage.
- L'aperçu ADOC compte le texte et le lien sans les couper, refuse sa préparation au-delà de 2 000 caractères et garde les autres usages disponibles. Son action exige une révision validée à jour et n'écrit aucun statut d'envoi.
- Le choix Ten'Up est « Non » pour une ancienne actualité sans ce champ ; sa modification demande une nouvelle validation.
- La sortie `release/` ne contient ni page d'administration ni données privées ni scripts de simulation locale.
- La navigation et le formulaire sont contrôlés sur mobile et ordinateur.

Le résultat effectif de ces contrôles doit être consigné après exécution ; cette liste n'est pas une attestation de réussite.

## Critères de recette de la connexion OIDC expérimentale

- Sans configuration valide, le service reste fermé ; les exemples ne créent aucun accès utilisable.
- Un retour de connexion vérifié n'ouvre une session que pour une des deux identités actives autorisées ; l'identité inconnue reste refusée et n'est pas enregistrée automatiquement.
- Le rôle `bureau` accède aux quatre pages internes mais pas à `/admin/acces` ; le rôle `admin` accède aux deux périmètres. La page d'administration reste en lecture seule.
- Expiration, inactivité, déconnexion, retrait d'accès et redémarrage ferment les sessions concernées ; les contrôles d'origine, d'état de connexion et de jeton anti-CSRF restent effectifs.
- Les fichiers privés, secrets et jetons ne sont pas servis au navigateur ; le paquet de vitrine publique reste inchangé.
- Les résultats des tests isolés sont distingués d'un essai chez un fournisseur réel et d'une qualification HTTPS sur l'hébergement.

La [recette OIDC](docs/recette-oidc.md) consigne les résultats effectifs. Cette préparation expérimentale ne vaut pas activation réelle ; le maquettage peut continuer sans fournisseur ni domaine configuré.

## Critères de recette de la visite fictive sur le port 4174

- Les sept pages sont reliées par une visite guidée et indiquent leur caractère fictif.
- Le formulaire en cinq étapes permet d'examiner les parcours proposés sans envoyer une demande au club.
- La gestion montre dossiers d'exemple, filtres, disponibilités et affectation manuelle aux groupes fictifs.
- Horaires et groupes restent identifiés comme exemples ; les ambiguïtés tarifaires des fiches sont conservées.
- L'import utilise uniquement un lot prédéfini, sans fichier réel ; l'export CSV contient les seules données de démo et aucun export XLS n'est annoncé comme disponible.
- La communication simule Facebook, WhatsApp, ADOC et presse-papiers ; l'import JSON réel est bloqué.
- L'affiche fournie se charge volontairement dans l'éditeur ; l'ajout local d'image fonctionne sans lever le blocage de l'import JSON ni déclencher de publication.
- Les clés de stockage fictif et l'origine de la visite restent distinctes du prototype protégé.
- L'archive ne contient que les fichiers de visite prévus, sans comptes ni dossiers réels ; le port 4173 reste protégé.

Les résultats effectivement obtenus figurent dans la [recette de démonstration](docs/recette-demo.md). Les critères de production du futur parcours adhérent relèvent toujours de la [proposition de processus](docs/processus-inscriptions.md) ; une visite fictive ne valide ni API, ni contrôle d'accès hébergé, ni reprise sécurisée d'un dossier réel.

## Critères de recette de l'archive publique o2switch

- Le ZIP contient exactement `index.html` à sa racine et ses octets correspondent à `release/index.html`.
- Le manifeste placé à côté du ZIP correspond aux tailles et empreintes des fichiers, sans être inclus dans l'archive.
- Les photographies, le logo, les styles et le script sont intégrés ; aucune ressource de chargement externe ni ancre locale cassée.
- Aucun lien vers les pages internes ou la visite, aucun formulaire, stockage navigateur, compte ou donnée privée.
- Les contacts vers `tclongages@gmail.com` et les crédits restent présents.
- Les constats locaux sont distingués de la cible, du contenu distant et du certificat HTTPS encore non vérifiés.

La [recette dédiée](docs/recette-vitrine-o2switch.md) conserve les résultats ; le [guide de dépôt](docs/deployer-vitrine-o2switch.md) est la procédure de référence.

## Critères de recette de la présentation complète o2switch

- Le ZIP contient exactement les dix fichiers prévus à la racine, avec le témoin de maintenance actif ; ses empreintes correspondent aux sorties.
- Les deux pages publiques ne contiennent aucun lien vers les cinq pages d'outils, y compris après validation du formulaire fictif. Les cinq pages restent utilisables directement lorsque la présentation est ouverte.
- Les essais utilisent les clés propres à cette copie ; aucun compte, import JSON ou envoi sur les réseaux sociaux n'est activé.
- Photo du court, logo et affiche exemple sont intégrés aux HTML sans ajout de fichier au ZIP à dix entrées ; les images choisies après dépôt restent dans le navigateur du visiteur. La FAQ et les formules reprennent le relevé tarifaire commun sans exposer les PDF comme fichiers supplémentaires.
- La fermeture répond 503 à la racine et aux pages directes avant ouverture ; le renommage ouvre les pages et son inverse rétablit 503. Le comportement distant doit être vérifié dans une nouvelle session de navigateur sans cache.
- La procédure explique sauvegarde, extraction privée et retrait des pages ajoutées lors du retour arrière, sans modifier les autres sites du compte.

Le [guide de présentation](docs/publier-demo-o2switch.md) décrit les opérations choisies par l'utilisateur ; la [recette correspondante](docs/recette-demo-o2switch.md) consigne les vérifications réellement exécutées, sans assimiler un essai local à un dépôt effectif.

## Suivi commun depuis le 4 octobre

Présenter exactement quatre phases selon le skill commun. Regrouper les dossiers détaillés, conserver les anciens identifiants et critères, ne pas étendre les accords historiques. Les opérations distantes des phases 2 et 3 restent hors des boutons locaux de validation.
