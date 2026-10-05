---
project: TC_Longages
document_type: decisions
title: Décisions structurantes du projet web
status: active
version: git
created: 2026-09-16
updated: 2026-10-05
owner: jpdandin
tags:
  - decisions
  - gouvernance
  - architecture
---

# Décisions

## 2026-10-05 — Première production distincte de la préproduction

**Contexte.** Le site public est prioritaire pour la V1. La préproduction
Drupal est installée ; conserver sa racine et sa base évite que les essais
de V2 modifient le futur site public.

**Décision humaine.** Le responsable autorise le lot de préparation proposé
dans le [parcours V1](docs/parcours-mise-en-ligne.md) : même ZIP sous une
nouvelle racine non servie, base et utilisateur SQL distincts, restauration
de la sauvegarde dans cette seule base vide et certificat gratuit des deux
noms officiels (`TCL-PROD-PREP-20261005`). La saisie du mot de passe reste
personnelle ; les dix droits exigent confirmation avant attribution. Le
responsable confirme ensuite ces dix droits pour la seule base
`daje5127_tclprod` (`TCL-PROD-SQL-20261005`) ; ils sont appliqués et relus.

**Conséquences.** La copie du ZIP, la base vide et le certificat sont préparés.
L'import doit conserver l'administrateur existant et la configuration française,
avec maintenance, non-indexation et courriels neutralisés. Cet accord exclut
la bascule de racine, l'ouverture et les changements d'adresse DNS. La lune,
la page d'attente officielle et la préproduction sont conservées.

## 2026-10-05 — V1 publique, comptes et droits en V2

**Contexte :** le responsable demande une livraison rapide après la journée
incomplète du 4 octobre. Il envisage d'abord comptes Bureau/Capitaine et droits
par équipe dans la première livraison, puis revient explicitement sur ce choix.

**Décision :** publier le site public existant en V1 ; développer les comptes
et droits par équipe en V2. Conserver le travail local d'édition et les
décisions antérieures. Le suivi personnel reste hors de la livraison publique.

**Conséquences :** les fonctions internes ne sont pas des critères bloquants
de V1. La recette du site, le certificat reconnu, les données séparées, la
sauvegarde/restauration et les accords propres à livraison/ouverture restent
nécessaires. Ce changement de périmètre n'est pas une validation de phase ni
l'accord sur une bascule technique qui n'a pas encore été présentée.

## 2026-10-04 — Implantation alignée sur AVEREO, compte à qualifier

**Contexte.** Le diagnostic explique le conflit de domaine entre compte principal
et lune ; la voie du compte principal est présentée avec fichiers et base
séparés et effets éventuels du PHP partagé à inventorier.

**Décision humaine.** Le responsable répond « On suit la même organisation que
pour AVEREO ». La préproduction `preprod.tclongages.fr` sera donc préparée dans
le compte principal TC, sans déplacement du domaine officiel vers la lune.
Le [suivi](docs/suivi-chantier/suivi-chantier.json), bloc
`developmentWorkflow.preproductionPlacementReview`, conserve la citation et sa portée.

**Conséquences.** Inventaire après reconnexion personnelle ; configuration à
présenter sur ce compte, contrôle de ses réglages partagés, base propre et
adaptation des garde-fous de l'outil avant transfert. Les ressources existantes
de la lune sont conservées. Aucun accord de phase, de mise en production ou
d'ouverture n'est déduit de ce choix d'implantation.

## 2026-10-04 — Retrait d'une proposition technique non qualifiée

**Contexte.** La comparaison demandée avec AVEREO révèle que les implantations
ne sont pas identiques : même compte pour AVEREO, lune différente du domaine
principal pour TC. La documentation officielle exclut les adresses techniques
de l'outil Let's Encrypt o2switch.

**Correction de préparation.** Retirer la proposition en `universe.wf` et son
ancienne demande d'accord ; recommander la qualification du compte principal
TC, ou conserver la lune sous réserve d'une solution de rattachement confirmée
par o2switch. Le [plan](docs/preparer-lune-tc.md) porte les étapes et sources.

**Conséquences.** La décision humaine historique de lune n'est pas remplacée.
Le changement de compte reste à décider et à qualifier, notamment pour PHP
partagé, base dédiée et contrôles de l'outil. Aucune suppression, écriture
d'hébergement, installation ou publication n'est exécutée par ce diagnostic.

## 2026-10-04 — Préparer la première installation avec l'accès existant

**Contexte.** Le terminal de la lune est accessible dans la session cPanel
ouverte personnellement ; PHP CLI 8.3.33 et Python 3.6.8 sont observés. La
création d'un accès SSH n'appartient pas au lot de configuration autorisé.

**Choix de préparation.** Adapter le vérificateur mutualisé à ce Python et
préparer une extraction privée avec installation PHP non interactive, depuis
le ZIP GitHub conservé. Ne pas activer un modèle de livraison absent ou
présenter une reconstruction comme le même artefact.

**Conséquences.** Tests locaux puis qualification MySQL/Drupal réelle avant
usage ; références d'accord pour cible et installation, paramètres soumis
personnellement, reçu sans secret, conservation de l'état initial. Le transfert,
la migration officielle et l'ouverture restent des décisions humaines propres.
La [procédure](docs/installation-drupal.md) porte les commandes et leurs limites.

## Configuration isolée et maintien du domaine officiel — 3 octobre

Le responsable autorise le lot PHP/racine/domaine/base sur la lune, puis les dix droits SQL de la seule base TC. Le refus du sous-domaine inter-comptes conduit à conserver le domaine officiel en place et à proposer une autre adresse temporaire, sans considérer sa soumission autorisée. Le [plan et ses preuves](docs/preparer-lune-tc.md) portent les effets et limites. Cette décision n’autorise ni transfert Drupal ni migration officielle.

## 2026-10-03 — Reprise du lot autorisé et suivi unique

**Contexte.** Le responsable reprend la mise en production, fusionne la PR #5 et confirme l'activation personnelle de la lune. Les dernières notes de session du cockpit précédaient des décisions de phase déjà enregistrées.

**Application des décisions existantes.** Le lot de sauvegarde et d'activation gratuite du 1er octobre est exécuté après requalification des sauvegardes. Le compteur et l'accès au compte isolé sont vérifiés. Aucun nouvel accord de configuration ou d'ouverture n'est déduit de cette activation.

**Suivi.** Le protocole commun demandé par la politique globale est raccordé au bloc `developmentWorkflow` du [registre existant](docs/suivi-chantier/suivi-chantier.json). L'historique humain et la phase 0 validée sont préservés ; l'ancienne prochaine action de session est gardée comme observation datée. Le candidat réellement construit après fusion est identifié séparément des anciennes preuves.

**Conséquence.** Présenter la configuration isolée, puis qualifier la préproduction réelle et le retour arrière avant livraison. Les confirmations PR gardent exactement les libellés du modèle ; la destination de l'aperçu et la documentation sont distinctes.

## 2026-10-01 — Sauvegarde privée et première lune gratuite autorisées

**Contexte.** Le compte courant utilise un PHP incompatible avec Drupal ; une lune isolée est disponible gratuitement. Le [plan](docs/preparer-lune-tc.md) expose les effets et les limites du lot.

**Décision humaine.** Le responsable autorise la sauvegarde, la restauration en copie privée puis l'activation de la première lune gratuite si les contrôles réussissent. La création d'un mot de passe, un contrat ou une option payante impose un arrêt. Cet accord ne couvre pas la configuration PHP/DNS/HTTPS, une base, des accès SSH, l'installation ou l'ouverture du site.

**Conséquences constatées.** Les restaurations locales des fichiers ont réussi ; les paramètres non couverts et les réimports cPanel restent hors preuve. Le dialogue d'activation exige un nouveau mot de passe : saisie et soumission réservées au responsable, activation encore non confirmée dans le [reçu](data/activation-lune-verification.json).

## 2026-10-01 — Composants de préparation réutilisables

**Contexte.** Le responsable demande de réutiliser les Actions du site Drupal AVEREO et de limiter la consommation des modèles.

**Mise en œuvre locale.** Une bibliothèque générique et une Action SSH paramétrée sont préparées dans le dépôt TC, à partir de la chaîne AVEREO figée et relue. La [procédure](workflows/preparer-livraison.md) porte le contrat et les limites. AVEREO conserve sa chaîne actuelle ; sa migration vers le composant partagé reste proposée. Les secrets et adaptateurs de livraison restent propres au site.

**Conséquences.** Revue du lot et qualification hébergée distinctes ; aucune autorisation de merge, d'accès ou d'ouverture déduite. La [lecture du 1er octobre](docs/preparer-lune-tc.md) résout le blocage d'affichage des lunes, sans modifier la décision historique ci-dessous ni activer de compte.

## 2026-09-29 — Préproduction sur un sous-compte o2switch dédié

**Contexte.** Le sélecteur PHP du compte actuel applique PHP 8.1 aux trois domaines affichés et l'isolation par domaine est désactivée. Drupal 11.4 exige PHP 8.3 au minimum. Un réglage global toucherait donc potentiellement d'autres sites.

**Décision.** Le responsable choisit un sous-compte o2switch « lune » dédié au club pour préparer `preprod.tclongages.fr`. Ce choix d'architecture n'autorise pas à créer le sous-compte, saisir ses identifiants, modifier DNS/SSL ou transférer le domaine principal sans action précise présentée au responsable.

**Raisons et conséquences.** Le compte dédié sépare PHP et les fichiers du club. Il faut vérifier la disponibilité d'une lune, créer son accès sous le contrôle du responsable, puis qualifier le sous-domaine, le certificat, la base et la sauvegarde dans ce compte. La migration ultérieure de `tclongages.fr` demandera une décision séparée : o2switch indique qu'un domaine déjà rattaché au compte principal doit être retiré de celui-ci avant son rattachement au sous-compte, avec vérification des courriels concernés.

**État au 29 septembre.** Le responsable indique ne pas avoir accès aux lunes sur ce compte. La lecture cPanel confirme que « Mon Univers Web » s'ouvre sur une page vide après une erreur de l'outil ; elle ne permet pas de conclure si une lune est incluse ou activable. La décision demeure historique, mais son exécution est suspendue. La voie de remplacement à qualifier est un sous-domaine dans le compte actuel avec PHP limité à son dossier ; elle ne vaut pas encore décision d'installation ou de publication.

## 2026-09-29 — Livraison V1 en quatre étapes et Drupal public d'abord

**Contexte.** Le responsable demande d'accélérer la mise en ligne et de simplifier le suivi après validation de la phase 0 du cadre initial. Il choisit `preprod.tclongages.fr` et confirme une première V1 limitée aux pages publiques sous Drupal.

**Décision.** Piloter cette livraison par initialisation, recette locale, recette en préproduction, puis mise en production. Conserver sans les réécrire les décisions du suivi historique à huit phases. Les pages Bureau, Google et envois automatiques restent fermés. La livraison distante et l'ouverture publique sont soumises à des accords explicites sur des actions concrètes.

**Raisons et conséquences.** Le plan court clarifie les étapes de livraison tout en préservant les preuves humaines déjà enregistrées. Son registre JSON et son guide ne redéveloppent pas l'interface HTML du suivi existant. La préproduction, sa sécurité et son retour arrière doivent être qualifiés avant la production. Aucun accord de publication n'est déduit de cette décision d'architecture.

## 2026-09-29 — PR, contrôles de phases et dépôt public

**Contexte.** L’utilisateur demande de finaliser les PR et d’activer les contrôles de phases, puis rend lui-même le dépôt public afin d’utiliser les protections GitHub.

**Décision.** Raccorder le site à l’historique existant, préparer une première PR de reprise, activer seulement les tests et la politique de revue. Conserver les détails internes d’hébergement hors du dépôt public. Rattacher la remise à un vrai commit et un manifeste dont les écarts de source invalident les preuves.

**Conséquences.** La protection de `main` doit exiger une PR et les deux contrôles. Un compte unique ne peut approuver sa propre PR : aucune seconde approbation GitHub impossible à obtenir n’est imposée ; les déclarations de checklist restent sous responsabilité humaine. Le contrôle strict de forme ne certifie pas l’identité du déclarant. Validation de phase, autorisation suivante, merge et production restent distincts. Le reçu technique conserve les références et résultats réellement observés.

## 2026-09-29 — Installation interactive et Drupal indépendant

**Contexte.** L'utilisateur demande un suivi HTML utilisable, une maintenance gérée par Drupal et une levée temporaire des verrous d'installation avec réactivation. Il confirme ensuite une instance Drupal dédiée au club.

**Décision.** Réaliser le moteur de suivi local et un socle Drupal indépendant ; consigner l'exception locale et son échéance sans désactiver confidentialité, contrôle des requêtes ou décision de publication. Les accords `TCL-D02` et `TCL-D03` du registre en portent les sources et la portée.

**Raisons et conséquences.** Le suivi devient manipulable et la maintenance passe par le moteur Drupal. Les HTML sont servis par des routes afin de rester sous son contrôle. Les anciens paquets restent historiques. La lecture cPanel prépare la cible ; elle ne vaut ni transfert, ni modification PHP/SSL, ni ouverture. La mutualisation éventuelle de composants CONNECT ne partage pas implicitement l'instance ou les accès.

## 2026-09-29 — Adoption du framework et reprise locale

**Contexte.** Après la mise en attente, l'utilisateur indique disposer de `tclongages.fr`, demande d'appliquer le framework joint et communique le dépôt `https://github.com/jpdandin44/TC_Longages` qu'il crée en parallèle.

**Décision.** Adopter le cadre pour le site existant : profil contextualisé, suivi canonique, dossiers des huit phases, vues dérivées, règles de revue et reprise. La demande autorise le cadrage local ; son enregistrement détaillé est `TCL-D01` dans le [suivi](docs/suivi-chantier/suivi-chantier.json). Les lots métier G0–G8 et leurs acquis sont conservés. Le sous-projet `Site_Internet/` est le périmètre candidat du dépôt, sans documents administratifs du parent.

**Raison.** Disposer d'un processus de décision et de livraison explicite sans confondre préparation locale, validation humaine et disponibilité réelle.

**Conséquences.** Référence reçue intacte ; aucune validation ou autorisation d'AVEREO importée. L'adaptateur initial est en lecture seule et la chaîne reste non qualifiée. Les Actions demeurent inactives. Le domaine est acquis selon la déclaration humaine ; aucun DNS/HTTPS vérifié. L'accès GitHub du poste a échoué, donc contenu, visibilité et protections restent inconnus. Aucune publication Git ou web autorisée par ce lot. Le choix Drupal/CONNECT reste ouvert. Voir [l'application du framework](docs/framework-developpement.md).


## 2026-09-24 — Démonstration V1 sur le sous-domaine et orientation Drupal

**Contexte.** L'utilisateur souhaite examiner la nouvelle version sur son sous-domaine avant la production et demande confirmation de la future authentification Drupal sur `tclongages.fr`. Au choix d'une installation dédiée ou d'un raccordement existant, il répond « À déterminer ensemble ». Il souhaite ensuite réutiliser la logique Drupal de CONNECT et les fonctionnalités mutualisées.

**Décision.** Préparer un paquet V1 statique distinct que l'utilisateur dépose lui-même : `tc-longages-v1-demo-o2switch.zip`. Conserver la revue locale à refus 503 inconditionnel et donner à ce seul nouveau paquet un mécanisme explicite de maintenance. Retenir l'orientation Drupal pour la future authentification, en examinant la logique CONNECT réutilisable ; l'instance, les composants et le mode de mutualisation restent à qualifier ensemble.

**Raison.** Permettre l'examen de la nouvelle interface sans présenter une maquette comme un service authentifié, et réutiliser les composants réellement adaptés sans imposer une installation ou des dépendances inconnues.

**Conséquence.** La démonstration ouvre uniquement avec `maintenance.inactive` seul et ferme dans tous les autres états. Elle sert les sept pages V1 et `robots.txt`, refuse les anciens chemins même résiduels, et ne contient ni module Drupal ni compte ni transmission Contact. L'utilisateur garde le dépôt et la bascule ; l'agent ne déploie rien. Les conditions de bêta réelle restent inchangées. L'orientation Drupal complète la décision G3 ci-dessous ; elle n'autorise aucune connexion ni modification d'AVEREO/CONNECT, aucune installation externe, aucun transfert de compte ou secret. Une mutualisation éventuelle doit préserver les périmètres du club et des autres applications. L'[audit CONNECT](docs/mutualisation-connect.md) distingue évolution d'un service compatible et mise à jour d'un module embarqué ; aucun héritage global automatique n'est promis. Voir le [guide de présentation](docs/publier-v1-sous-domaine.md) et le [suivi V1](docs/integration-officiel.md).

## 2026-09-24 — Contact centralisé et adresse support de test prévue

**Contexte.** L'utilisateur remplace le contact public par le compte Google de référence pour préparer ses futurs usages Workspace et demande une adresse supplémentaire de test.

**Décision.** Utiliser `tclongages@gmail.com` comme contact de toutes les variantes actives, à partir de `club.email` dans `config/officiel.json`. Prévoir `support@tclongages.fr` dans `contact.testSupport` avec le statut `planned`, affiché sans lien d'envoi sur la page Contact officielle.

**Raison.** Disposer d'une seule source pour les adresses courantes et éviter de présenter la future assistance comme une boîte déjà disponible.

**Conséquence.** La décision antérieure de conserver l'adresse FFT est remplacée pour le contact courant ; sources reçues, baselines et preuves antérieures restent intactes. Aucune boîte/alias, redirection, configuration Workspace ou automatisation n'est créée. Le propriétaire des services Google reste renseigné séparément ; ces adresses ne créent aucun compte ni droit d'authentification. Drupal n'est pas intégré à la V1 et le choix G3 reste à valider.

## 2026-09-24 — Cadrage officiel V1 prioritaire, prototypes conservés

**Contexte.** Le dossier officiel fournit 30 arbitrages, un backlog G0 à G8 et une référence graphique G1. L'utilisateur demande leur intégration dans le projet existant, sans demande de déploiement.

**Décision.** Retenir le P0 vitrine/équipes/calendrier/disponibilités avec trois rôles internes Admin/Bureau/Capitaine. Conserver les sources et démonstrations antérieures dans leurs variantes ; préparer la sortie `officiel/` et sa configuration séparée depuis les contenus existants. Exclure l'inscription complète et le paiement du parcours officiel. Les décisions plus anciennes concernant inscriptions, deux comptes et démonstration HTTP restent l'historique de leurs prototypes, pas le périmètre de la nouvelle bêta.

**Raison.** Intégrer les arbitrages reçus sans perdre les fonctionnalités déjà préparées ni assimiler leurs limites à des garanties de production.

**Conséquence.** Navigation en trois clics, parité desktop/mobile, rouge/blanc provisoire et logo fourni sont préparables localement. Les ressources Google absentes restent non configurées ; le contact reste simulé tant que son transport n'est pas réalisé. Les [écarts des sources et états des gates](docs/integration-officiel.md) sont explicites ; une recette locale ne vaut pas GO bêta. La baseline locale du 24 septembre est conservée pour retour arrière, sans sauvegarde d'hébergement présumée.

## 2026-09-24 — Domaine et compte Google de référence confirmés

**Contexte.** La V1 prévoit un domaine officiel sécurisé et des outils Google pour les rencontres, les disponibilités et les effectifs.

**Décision.** Retenir `tclongages.fr` comme futur domaine et `tclongages@gmail.com` comme compte Google de référence, conformément à la réponse de l'utilisateur. Le maintien initial de `23310230@fft.fr` comme adresse publique est historique : il est remplacé par la décision de contact centralisé ci-dessus, prise plus tard le même jour.

**Raison.** Identifier la cible et le propriétaire de référence sans lancer une migration de messagerie ou inventer les URL de services.

**Conséquence.** Achat, DNS, racine, HTTPS et ressources Calendar/Forms/Sheets restent à vérifier ou fournir. Aucun accès au compte, partage, création de ressource, certificat ou déploiement n'est autorisé par cette confirmation.

## 2026-09-24 — Choix d'authentification à valider avant G3

**Contexte.** Les arbitrages imposent les rôles et le contrôle serveur par équipe mais ne choisissent pas de technologie. La spécification §7 exige une validation avant AUTH-001.

**Décision.** Préparer une proposition adaptée à l'hébergement réel avec dépendances, stockage, récupération, révocation, maintenance et risques ; attendre la validation de ce choix avant l'implémentation G3. PHP avec sessions est une option recommandée à étudier, pas une décision adoptée. L'OIDC expérimental existant n'est pas activé ni étendu automatiquement.

**Évolution ultérieure du même jour.** La demande utilisateur oriente l'authentification vers Drupal et la logique mutualisable de CONNECT. L'instance et le déploiement restent à déterminer ensemble ; cela remplace le choix ouvert d'une simple solution PHP comme prochaine proposition, sans lever la qualification préalable à G3.

**Raison.** Le modèle antérieur à deux comptes ne couvre ni les capitaines ni leurs périmètres et ne prouve aucune compatibilité de production.

**Conséquence.** G0/G1 et la préparation publique peuvent avancer sans secret ou compte réel. Aucun affichage de connexion ou lien privé ne doit être présenté comme une authentification opérationnelle avant les tests négatifs par équipe et la révocation.

## 2026-09-17 — Photo du club et tarifs tirés des pièces fournies

**Contexte.** L'utilisateur fournit une photo du court et demande de reprendre les tarifs des deux fiches d'inscription dans la FAQ.

**Décision.** Utiliser `Images_Photos/Image_terrain.jpg` pour l'accueil, conserver l'original et l'attribuer au club sans inventer de photographe. Conserver l'autre illustration avec son crédit Nicholas Bullett. Transcrire les trois pages des deux PDF dans [un relevé JSON commun](data/tarifs-inscription.json), puis générer la FAQ et les formules de démonstration à partir de ce relevé.

**Raison.** Afficher une image réelle du club et éviter deux listes tarifaires maintenues séparément. Les PDF composés d'images ont été lus visuellement ; les pièces originales restent conservées.

**Conséquence.** Les neuf lignes de tarifs sont reprises sans arbitrer les 125/150 € des cours adultes ni chiffrer la remise famille. Licence, durée, horaires, cumul cours/adhésion et tarifs de terrain ne sont pas déduits des documents. Le relevé ne vaut pas validation d'une campagne réelle. Les HTML restent autonomes, sans PDF supplémentaire dans le paquet à dix fichiers.

## 2026-09-17 — Préparation ADOC simulée et visibilité Ten'Up explicite

**Contexte.** La capture fournie montre les champs d'un article ADOC : titre, contenu, photo et visibilité Ten'Up.

**Décision.** Ajouter un quatrième aperçu et un bouton de préparation simulée sur une actualité validée, sans connexion au service. Le [cadrage ADOC](api/adoc.md) conserve les limites observées sur cette capture et celles appliquées localement ; « Visible sur Ten'Up » vaut « Non » par défaut.

**Raison.** Montrer le fonctionnement attendu sans supposer de droits, d'API ou de publication réelle. Le contrôle des 2 000 caractères texte et lien compris concerne uniquement ADOC et ne coupe jamais le contenu.

**Conséquence.** Le choix Ten'Up est un booléen facultatif compatible avec le format local v1 ; sa modification exige sauvegarde et revalidation. Aucun statut d'envoi ADOC n'est enregistré, aucune copie réelle ni action Ten'Up n'est exécutée. Les moyens autorisés d'une future intégration restent à vérifier séparément.

## 2026-09-16 — Logo temporaire et images des actualités

**Contexte.** L'utilisateur fournit un logo JPEG avant une future version vectorielle, demande les images et affiches dans la communication et souhaite un nouveau paquet complet à déposer lui-même. Il fournit aussi une affiche pour essayer ce parcours.

**Décision.** Conserver les deux originaux et dériver leur affichage dans les HTML autonomes. Ajouter une seule image locale JPEG/PNG/WebP par actualité, préparée en JPEG dans le navigateur et stockée dans un budget borné. Le titre et la description de l'image sont obligatoires ; le texte peut être vide lorsqu'une affiche est présente. La validation porte sur le texte, l'image et sa description. Proposer l'affiche fournie par un bouton de l'éditeur de démonstration, sans actualité automatiquement validée.

**Raison.** Montrer la communication illustrée dans la maquette actuelle sans introduire une bibliothèque serveur, une collecte réelle ou une publication sociale.

**Conséquence.** Les images restent propres au navigateur et sont incluses dans ses exports éditoriaux ; les anciennes actualités sans image restent lisibles. L'import JSON demeure bloqué dans la démonstration, mais la sélection locale d'image est permise. Facebook et les actions WhatsApp y restent simulés. Le vrai lien WhatsApp du prototype protégé ne joint pas le visuel : un envoi réel demanderait un ajout manuel dans le client. Les visuels fournis peuvent porter des informations du club et sont distingués des dossiers fictifs. Le paquet o2switch reste à dix fichiers, médias intégrés et maintenance active ; l'utilisateur réalise lui-même son dépôt. Voir les [limites de stockage](architecture.md) et le [guide de mise à jour](docs/publier-demo-o2switch.md).

## 2026-09-16 — Démonstration complète déposée manuellement par l'utilisateur

**Contexte.** L'utilisateur souhaite montrer toute la maquette sur `http://tclongages.daje3540.odns.fr/`, confirme le remplacement de la vitrine, puis choisit de déposer lui-même les fichiers.

**Décision.** Préparer un paquet statique distinct contenant les sept pages fictives, `robots.txt`, `.htaccess` et `maintenance.active`. La vitrine et le formulaire restent dans les menus publics ; les outils sont accessibles par adresse directe sans authentification. Le témoin présent par défaut ferme toutes les requêtes avec 503 ; son renommage ouvre temporairement la démonstration, l'inverse la referme.

**Raison.** Permettre la présentation complète sur l'adresse disponible en conservant au responsable le contrôle concret du dépôt, de l'ouverture et de la fermeture.

**Conséquence.** Aucun dépôt par l'agent, compte, SSO ou donnée réelle dans cette présentation HTTP. Imports réels bloqués et réseaux sociaux simulés ; stockage propre à chaque navigateur, sans base commune. Masquer les liens ne protège pas les pages. Le [guide de dépôt](docs/publier-demo-o2switch.md) impose l'identification de la racine, la sauvegarde privée, l'extraction privée et le contrôle de fermeture avant copie des pages. Le retour arrière retire aussi les pages ajoutées avant restauration de l'ancienne configuration. L'archive de vitrine seule reste une option ; OIDC et la migration du service réel restent différés après l'achat du domaine.

## 2026-09-16 — Maintien en maquette, activation réelle reportée

**Contexte.** Après la préparation de la connexion, l'utilisateur souhaite rester au maquettage pour montrer le fonctionnement ; la migration sur l'hébergement réel viendra après l'achat du nom de domaine.

**Décision.** Conserver la visite fictive comme parcours actif et le code OIDC comme préparation expérimentale locale. Reporter le choix du fournisseur, la configuration réelle des accès et la migration du service réel. La décision suivante de présenter la copie fictive sur l'adresse HTTP existante est consignée ci-dessus.

**Raison.** Examiner les parcours avant d'engager l'hébergement et les comptes réels.

**Conséquence.** Aucun domaine acheté, certificat installé, DNS modifié, fournisseur connecté ou accès réel ouvert. Les décisions futures ne bloquent pas la visite. Tout déploiement conserve son accord explicite distinct.

## 2026-09-16 — Deux accès OIDC et rôles contrôlés par le serveur

**Contexte.** L'utilisateur confirme un compte personnel administrateur et un compte générique bureau. Le contrôle HTTP Basic local ne fournit pas la connexion envisagée pour un futur hébergement.

**Décision.** Préparer une couche OIDC séparée sur le port 4175, avec `openid-client` 6.8.8, Authorization Code et PKCE. Autoriser exactement deux identités par leur couple `iss`/`sub` dans un fichier privé. Le rôle `admin` hérite des quatre pages bureau et consulte `/admin/acces` en lecture seule ; le rôle `bureau` reste limité aux pages du bureau. Un compte inconnu reste refusé, même après une connexion vérifiée chez le fournisseur.

**Raison.** Déléguer la vérification des identifiants au fournisseur et conserver la décision d'autorisation dans le serveur du club, sans inscription automatique ni dépendance à une adresse e-mail comme identifiant d'accès.

**Conséquence.** Exemples inactifs, aucun compte ni secret réel créé. Sessions limitées à cent en mémoire d'un seul processus, huit heures au maximum et trente minutes d'inactivité ; redémarrer déconnecte les utilisateurs. Le compte générique permet seulement une attribution collective des actions. Fournisseur, domaine, HTTPS, proxy et gestion partagée des sessions en cas de plusieurs processus restent à qualifier avant une activation ultérieure autorisée. La visite sur 4174, Basic sur 4173 et le ZIP public restent séparés. Voir le [guide OIDC](docs/authentification-oidc.md).

## 2026-09-16 — Archive o2switch limitée à la vitrine publique

**Contexte.** L'utilisateur choisit explicitement la vitrine publique seule pour la préparation du site à déposer chez o2switch.

**Décision.** Générer `livrables/tc-longages-vitrine-o2switch.zip` avec uniquement `release/index.html`, renommé `index.html` directement à la racine du ZIP. Conserver le manifeste de contrôle à côté du paquet, hors de celui-ci. Le [guide de dépôt manuel](docs/deployer-vitrine-o2switch.md) devient la référence de cette publication préparée.

**Raison.** Fournir la page publique demandée avec ses ressources intégrées, sans exposer le bureau, les inscriptions ou les exemples de la visite.

**Conséquence.** Aucun `.htaccess`, lanceur ou serveur Node.js n'est livré et aucune configuration d'hébergement n'est modifiée. Le choix du contenu n'autorise pas un déploiement par l'agent. La cible exacte, sa racine, l'existant, la sauvegarde et HTTPS restent à vérifier avant que l'utilisateur décide de publier. La préparation et sa recette sont locales uniquement.

## 2026-09-16 — Visite complète fictive, séparée du bureau protégé

**Contexte.** L'utilisateur souhaite partager le prototype et voir l'ensemble du processus avant sa mise en place réelle.

**Décision.** Préparer sept pages de visite accessibles sans compte sur une origine locale distincte, avec formulaire fictif, gestion d'exemples et communication simulée. Distribuer les seuls fichiers nécessaires dans une archive construite par liste explicite. Conserver le serveur protégé du port 4173, ses pages et sa fermeture sans comptes.

**Raison.** Permettre un examen commun de tous les écrans sans ouvrir un accès bureau réel ni transformer les exemples en collecte d'adhérents.

**Conséquence.** La visite utilise uniquement des données fictives et des clés de stockage dédiées. Horaires et groupes sont des exemples ; les tarifs gardent les réserves des fiches. L'import XLS/CSV est un lot simulé sans fichier, l'export CSV télécharge des exemples et aucun export XLS n'est livré. Facebook, WhatsApp et presse-papiers sont simulés dans cette visite et l'import JSON réel y est bloqué. Aucun compte, API métier, stockage central ou reprise sécurisée n'est créé. Le prompt de conception et les pièces d'origine restent intacts.

## 2026-09-16 — Formulaire adhérent distinct de la gestion privée

**Contexte.** Après la préparation de la page privée d'inscriptions, l'utilisateur fournit un contexte et un prompt pour concevoir le parcours d'adhésion et l'organisation des entraînements.

**Décision.** Retenir deux interfaces distinctes : un formulaire destiné aux adhérents et une gestion réservée au bureau. Ce choix a été explicitement confirmé par l'utilisateur. La demande actuelle produit une proposition de processus, sans ouverture du formulaire ni collecte réelle.

**Raison.** Permettre la saisie par l'adhérent tout en conservant au bureau les données administratives et la décision de constitution des groupes.

**Conséquence.** La [proposition de processus](docs/processus-inscriptions.md) sert de base à la validation du contenu et du modèle de données. La page du prototype protégé reste inchangée ; les écrans ajoutés ensuite appartiennent à la visite fictive séparée. Aucune API ni base centrale d'inscriptions n'est créée. SQLite est proposé mais n'est pas une décision adoptée. Les modes d'accès des adhérents à leurs dossiers, tarifs, champs et disponibilités restent à confirmer. Les deux rôles du bureau sont désormais confirmés dans la décision OIDC ci-dessus ; toute mise en production exige toujours un accord explicite.

## 2026-09-16 — L'utilisateur décide des actions sensibles

**Contexte.** L'utilisateur veut avancer rapidement tout en conservant le contrôle de la mise en ligne et des commandes sensibles.

**Décision.** L'agent prépare et vérifie les changements locaux, explique les effets des actions sensibles en termes de décisions, puis attend un accord explicite avant toute mise en production ou publication externe réelle. Les anciennes consignes de déploiement de la passation ne valent pas autorisation actuelle.

**Raison.** Rendre les conséquences compréhensibles et laisser au responsable du club la décision d'exposition et d'envoi.

**Conséquence.** Aucun déploiement automatique ; procédure dans [les commandes sensibles](docs/commandes-sensibles.md). Le parcours manuel WhatsApp décrit ci-dessous laisse le responsable ouvrir le service, choisir les groupes et confirmer l'envoi lui-même ; il ne requiert pas de nouvelle approbation de l'agent à chacun de ses clics.

## 2026-09-16 — Validation individuelle avant relais

**Contexte.** Une nouvelle page doit faciliter la communication du club, son relais vers Facebook et son partage dans les groupes WhatsApp.

**Décision.** Chaque actualité doit être validée avant tout envoi. Enregistrer un brouillon ne déclenche pas de publication.

**Raison.** Choix explicite de l'utilisateur lors de la reprise.

**Conséquence.** La simulation Facebook, le partage manuel WhatsApp et le futur service automatique doivent conserver cette séparation. Une modification du contenu destiné à être diffusé nécessite une nouvelle validation ; une erreur d'envoi ne doit pas être présentée comme une réussite.

## 2026-09-16 — Prototype local avant intégration réelle

**Contexte.** Le prototype doit être disponible rapidement ; aucun accès ou droit Meta n'est confirmé.

**Décision.** Utiliser un stockage de navigateur et un relais Facebook simulé pour examiner le parcours. Réserver le relais automatique par API à un service serveur sécurisé, à définir ensuite.

**Raison.** Permettre la validation fonctionnelle locale sans secret ni publication automatique externe. Le partage manuel WhatsApp ajouté ensuite est décrit dans la décision suivante.

**Conséquence.** La page de gestion reste locale, sans collaboration multi-utilisateur ; l'export JSON sert de sauvegarde manuelle. Le contrôle d'accès local ajouté pour le bureau est décrit dans la décision suivante et ne fournit pas de stockage partagé ni d'authentification de production.

## 2026-09-16 — Espace bureau et inscriptions préparées sans contenu inventé

**Contexte.** L'utilisateur demande de réserver communication et inscriptions aux membres du bureau et fournira le contenu des inscriptions ultérieurement.

**Décision.** Ajouter un accueil du bureau, une page d'inscriptions en attente et un aperçu privé des actualités. Contrôler les quatre pages internes avant leur réponse par le serveur local, avec HTTP Basic, compte actif de rôle `bureau` et mot de passe dérivé par scrypt. Fermer l'accès en l'absence de configuration utilisable. Retirer la lecture des actualités locales de la vitrine publique.

**Raison.** Respecter la séparation demandée par un contrôle serveur effectif, tout en laissant les champs et processus d'inscription à la définition future de l'utilisateur.

**Conséquence.** Aucun formulaire ni collecte d'inscription dans le prototype protégé. Aucun compte réel ni fichier privé installé ; les tests utilisent un compte éphémère en mémoire. Le modèle de comptes initialement laissé ouvert a ensuite été précisé : un compte personnel administrateur et un compte générique bureau, préparés par la couche OIDC distincte. La protection Basic reste limitée aux réponses du serveur local : fichiers ouverts directement, profils de navigateur partagés et conservation des identifiants Basic restent des limites explicites. Voir [l'accès Basic local](docs/acces-bureau.md) ; l'activation hébergée et le stockage central restent ultérieurs.

## 2026-09-16 — Partage manuel dans les groupes WhatsApp

**Contexte.** L'utilisateur souhaite aussi diffuser les informations dans les groupes WhatsApp du club. Les groupes concernés et les possibilités d'une automatisation restent à déterminer.

**Décision.** Ajouter un aperçu WhatsApp et, pour une révision validée encore à jour, l'ouverture d'un message prérempli ou sa copie. Le responsable choisit les groupes et confirme l'envoi dans WhatsApp. Prévoir un texte sélectionnable si le presse-papiers est indisponible et une copie seule lorsque le lien dépasse le seuil prudent de l'interface.

**Raison.** Permettre un usage manuel immédiat sans compte connecté au site, sans secret et sans promettre un relais automatique vers des groupes dont les conditions ne sont pas vérifiées.

**Conséquence.** Ouvrir le lien transmet le texte à WhatsApp pour préremplissage. Le prototype ne choisit aucun destinataire, n'envoie pas de message et ne confirme aucune livraison. Les groupes ne sont pas stockés ; le format local v1 reste inchangé et les imports redeviennent des brouillons. Le responsable conserve la décision d'envoi, sans approbation conversationnelle supplémentaire pour chaque action qu'il réalise lui-même.

## 2026-09-16 — Sources séparées et vitrine autonome générée

**Contexte.** Le dossier annonce une correction autonome, mais le fichier correspondant manque ; les sources anciennes et images sont disponibles.

**Décision.** Reconstruire une source éditable dans `src/`, réappliquer les corrections documentées et générer les ressources intégrées. Conserver les archives intactes. Séparer la démonstration locale (`dist/`, désormais cinq pages avec l'espace bureau) de la vitrine seule préparée (`release/`).

**Raison.** Faciliter la maintenance et éviter qu'une démonstration de gestion locale soit déposée par erreur sur l'hébergement.

**Conséquence.** Les sorties générées sont dérivées ; toute correction part des sources. Le nouveau HTML ne correspond pas nécessairement à l'empreinte historique. Aucun contenu éditorial privé du navigateur n'est inclus dans la vitrine préparée.

## 2026-10-04 — Regroupement du suivi en quatre phases

À la demande du responsable, le cockpit applique le modèle du skill commun : Cadrage, Développement local, Préproduction et Mise en production. Les anciennes phases sont conservées en instantané : 0 → 0 ; 1–3 → 1 ; 4–5 → 2 ; 6–7 → 3. Les décisions, commentaires et libellés obligatoires gardent leur portée. Le brouillon personnel est restitué avec confirmation à renouveler ; aucune approbation n’est créée. Les observations d’hébergement sont distinctes des validations humaines.

## 2026-10-05 — Validation locale en un clic et passage en cours à l'autorisation

**Contexte.** Le responsable demande de simplifier la validation et de retirer
le démarrage séparé, en maintenant explicitement les critères cochés, le
commentaire et la confirmation personnelle.

**Décision.** Enregistrer atomiquement les critères et l'acceptation sur
« Valider cette phase ». Après validation courante de la précédente,
« Autoriser la phase suivante » enregistre son autorisation et la passe
directement en cours. Retirer le bouton de démarrage du parcours normal.

**Raison.** Supprimer la sauvegarde intermédiaire obligatoire et une action
de progression redondante, sans supprimer les éléments de validation.

**Conséquences.** Les contrôles de candidat, de révision, de dépendances,
d'identité déclarée et de confirmation restent actifs. Les anciennes traces
de démarrage sont conservées. Ce changement du moteur local ne crée aucun
accord d'hébergement, déploiement, merge ou ouverture publique ; les phases
distantes restent soumises à leurs contrôles et accords spécifiques.
