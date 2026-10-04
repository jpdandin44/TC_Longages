---
project: TC_Longages
document_type: roadmap
title: Feuille de route du prototype et de la diffusion
status: active
version: git
created: 2026-09-16
updated: 2026-10-04
owner: jpdandin
tags:
  - roadmap
  - prototype
  - facebook
  - whatsapp
---

# Feuille de route

## Reprise de la première installation — 4 octobre

Le terminal cPanel existant répond et l'[adaptateur](docs/installation-drupal.md)
est désormais préparé localement, avec tests Linux. Prochaine action : obtenir
la décision sur l'adresse temporaire préparée, qualifier son DNS/HTTPS/PHP
servi, puis présenter le transfert et l'installation fermée du même ZIP reçu.
La recette réelle, la sauvegarde/restauration Drupal et le retour arrière
précèdent toujours la qualification et la livraison de production. Le
[suivi canonique](docs/suivi-chantier/suivi-chantier.json) conserve l'historique
humain et les blocages ; aucune phase n'est validée par cette préparation.

## État et prochaine intervention — 3 octobre

La préparation de la PR #5 est fusionnée. L'Action GitHub a construit un candidat non configuré, reçu et vérifié intégralement sur le poste. La sauvegarde privée du compte est requalifiée ; la première lune gratuite est active. Voir les [preuves de préparation](data/actions-mutualisees-verification.json) et d'[hébergement](data/activation-lune-verification.json).

La [configuration de cette lune](docs/preparer-lune-tc.md) a commencé après accord. Le lot de configuration a été autorisé et partiellement réalisé : PHP 8.3 appliqué, racine isolée créée avec fermeture Apache relue, base vide UTF-8 et utilisateur SQL dédié avec dix droits enregistrés. cPanel refuse `preprod.tclongages.fr` dans la lune parce que son domaine parent appartient au compte principal. Aucun DNS ni certificat n’est créé pour ce nom. Une adresse temporaire de la lune est proposée, non soumise ; son accord, DNS, HTTPS reconnu et PHP réellement servi restent à qualifier. Ensuite restent l'adaptateur de première installation, les paramètres privés, l'installation sous maintenance, la recette réelle et la répétition du retour arrière. La mise en production demandera la qualification de sa cible et la livraison du même artefact, puis son contrôle et l'ouverture autorisée.

L'aperçu local des sept pages a été présenté à la revue de la PR ; cette revue ne valide pas les comptes ni les services connectés. Aucune phase historique n'est validée par l'agent et aucun site n'est déployé par le merge. Les sections suivantes sont des constats datés de préparation.

## Reprise du 1er octobre — préparation partagée avec AVEREO

**Clôture du 1er octobre :** travail arrêté à la demande du responsable, formulaire d'activation annulé, cPanel déconnecté et onglet fermé. Reprendre uniquement sur nouvelle demande, depuis le [point de session](docs/point-session.md), en vérifiant l'état de la lune et la fraîcheur des sauvegardes. L'accord existant reste limité au lot gratuit ; la configuration et la mise en production attendent leurs décisions propres.

La [préparation manuelle](workflows/preparer-livraison.md) construit le candidat sans accès serveur et offre une qualification SSH distincte. Le composant partagé est préparé dans le dépôt TC, utilisable par un futur appel AVEREO épinglé sur un commit ; cette migration reste proposée. Le site AVEREO actif n'est pas modifié. Prochaine action : revue du lot et qualification cPanel récente avant toute configuration d'accès ou première installation. Le solde et les vérifications courantes sont dans le [reçu](data/actions-mutualisees-verification.json). Aucun budget de réserve chiffré n'est considéré approuvé.

## Priorité actuelle — première V1 publique

Le [parcours en quatre étapes](docs/parcours-mise-en-ligne.md) remplace les huit phases comme vue opérationnelle de cette livraison, sans effacer leur historique. Initialisation et recette locale sont en cours ; la préproduction `preprod.tclongages.fr` attend sa qualification ; la production n'est pas démarrée. La première ouverture ne comprend que les pages publiques Drupal. Les comptes Bureau, formulaires Google et envois automatiques suivent leurs recettes propres après cette V1.

Le blocage des lunes signalé le 29 septembre est résolu côté affichage le 1er octobre : l'interface exige une largeur ordinateur. Le [plan de compte isolé](docs/preparer-lune-tc.md) porte le lot désormais autorisé. Sauvegarde et restauration des fichiers en copie privée ont réussi ; le [reçu](data/activation-lune-verification.json) précise leur périmètre. L'activation de la première lune gratuite reste à terminer à la reprise : nouveau mot de passe saisi et soumis personnellement dans cPanel, puis vérification du résultat. Le formulaire a été annulé à la clôture. L'isolation PHP par domaine du compte actuel reste désactivée. La configuration et la recette de la future lune restent à présenter après confirmation de son activation.

Prochaine séquence : qualifier et sauvegarder la cible de préproduction, puis réaliser le [test PHP isolé préparé](docs/qualification-preproduction.md) avec accord sur les changements distants ; ensuite installer le candidat sous maintenance, puis présenter la livraison de production et l'ouverture comme décisions distinctes. Le 30 septembre, le responsable demande d'optimiser les crédits restants et de garder une marge pour les corrections. L'ancienne réserve de 800 dépasse le solde observé à la reprise ; une marge de 200 est proposée, sans validation personnelle déduite. Le [reçu de reprise](data/reprise-deploiement-verification.json) conserve les résultats et limites actuels.

## Avancement au 29 septembre

L'interface de suivi locale et le socle Drupal dédié sont réalisés dans le périmètre d'installation autorisé. Les phases 0–7 du framework restent distinctes des lots métier G0–G8 ; aucune approbation humaine n'est créée pour rattraper le travail technique. Voir le [point de session](docs/point-session.md) et le [suivi canonique](docs/suivi-chantier/suivi-chantier.json).

Le raccordement Git, les contrôles CI et la première PR composent le lot courant. Le suivi doit présenter le candidat réellement testé avant validation humaine de la phase 0 et autorisation distincte de la phase 1. Le PHP et le certificat restent à adapter avant Drupal hébergé ; sauvegarde restaurable, rôles privés, Google et mise en ligne demeurent des travaux distincts. La levée temporaire des verrous d’installation est clôturée.

## Priorité actuelle — officiel V1

Depuis le 24 septembre, le travail suit le cadrage officiel G0 à G8. Les 30 décisions reçues sont conservées ; le [suivi d'intégration](docs/integration-officiel.md) donne les critères, les écarts documentaires et les dépendances. Le P0 porte sur vitrine, équipes, calendrier et disponibilités. Inscription complète et paiement restent hors bêta ; les prototypes antérieurs décrits ensuite sont conservés, sans devenir des fonctionnalités P0 à activer.

| Étape courante | Avancement et prochaine condition |
|---|---|
| G0 — Baseline | Réalisé localement : archive de 182 fichiers, manifeste, variante et configuration distinctes ; aucun Git ni état distant vérifié. |
| G1 — UX/identité | Qualifié localement : rouge/blanc, logo fourni, navigation desktop/mobile et matrice trois clics vérifiés. Six raccourcis en un clic ; validation métier utilisateur distincte. |
| G2 — Public | Vitrine et contact préparés ; contact simulé. Décider puis qualifier son transport, ses erreurs, l'antispam et l'information de collecte avant de le déclarer fonctionnel. |
| G3 — Authentification | Drupal dédié confirmé et socle CMS local installé. Qualifier les rôles Admin/Bureau/Capitaine, leur périmètre serveur, récupération et révocation puis la cible hébergée avant AUTH-001. Aucune mutualisation AVEREO ni connexion métier distante active. |
| Présentation V1 temporaire | Paquet distinct `tc-longages-v1-demo-o2switch.zip`, pour dépôt manuel par l'utilisateur sur le sous-domaine existant. Fermé par défaut ; ouvrir pour examen, contrôler les sept pages et le refus des anciennes routes, puis refermer selon le guide. Cela ne clôture aucun usage authentifié ou Google. |
| G4 à G7 — Usages équipes | Préparer modèles, gabarits et procédures ; la réalisation dépend du contrôle par équipe, des ressources Google et des responsables désignés. Aucun effectif privé, connexion Google ou message réel ajouté maintenant. |
| G8 — Bêta réelle | Futur domaine `tclongages.fr` confirmé. Racine, DNS/HTTPS, sauvegarde distante, panel, partages Google et mentions à qualifier avant toute ouverture explicitement autorisée. |

Le compte Google de référence et le contact public sont désormais `tclongages@gmail.com`, sans URL Calendar/Forms/Sheets ni autorisation de connexion fournie. L'adresse `support@tclongages.fr` est prévue pour les tests ; choisir la boîte ou l'alias, ses destinataires et son hébergeur avant toute création autorisée. Aucune redirection ni automatisation d'agent n'est activée ; le type d'offre Workspace et son rattachement au domaine restent à confirmer. Les groupes WhatsApp actuels sont conservés ; leur réorganisation est reportée après la bêta. Le [suivi d'intégration](docs/integration-officiel.md) et la [recette officielle](docs/recette-officiel.md) sont alignés : 87 tests Node réussis, sept pages contrôlées sur ordinateur et mobile, 25 captures et aucune action externe constatée. Ces résultats qualifient le lot local ; G2 demeure partiel et G3 à G8 non acquis. Les [empreintes des sources](data/officiel-sources.json) et le paquet de revue conservent la traçabilité, sans publication.

Le [guide V1 pour le sous-domaine](docs/publier-v1-sous-domaine.md) et sa [recette](docs/recette-v1-sous-domaine.md) qualifient séparément le nouveau paquet statique et la procédure sous contrôle utilisateur. Le choix Drupal dédié est désormais confirmé ; la [note de mutualisation CONNECT](docs/mutualisation-connect.md) relève les composants réellement présents, leurs limites de domaine/application et l'absence des rôles du club. Qualifier isolation et cycle de mise à jour avant tout raccordement ; la nouvelle démonstration ne contient pas Drupal.

Les listes ci-dessous documentent les étapes antérieures et leur travail potentiel. Elles ne priment pas sur ce séquencement V1 : inscription complète, ancien OIDC à deux comptes et ancienne présentation HTTP ne deviennent pas les services réels de la V1.

## Historique des prototypes conservés

## Éléments établis

- Dossier de reprise et ancien pack local inspectés ; dernier HTML corrigé annoncé non disponible.
- Demande de prototype rapide et de contrôle humain de la mise en ligne confirmée.
- Validation individuelle des actualités avant relais confirmée par l'utilisateur.
- Diffusion dans les groupes WhatsApp demandée ; parcours manuel après validation retenu pour le prototype.
- Sources, génération locale et documentation de référence séparées des archives historiques.
- Espace bureau, page d'inscriptions en attente de contenu et aperçu privé des actualités créés ; quatre routes internes contrôlées par le serveur local.
- Prompt de conception des inscriptions reçu ; séparation entre formulaire adhérents et gestion privée du bureau confirmée par l'utilisateur.
- Import et export réels des inscriptions en XLS et CSV ajoutés aux exigences confirmées ; la visite en montre un lot d'import simulé et un export CSV fictif, sans import réel ni export XLS.
- Visite complète distincte en sept pages avec exemples fictifs, serveur local sur le port 4174 et archive de partage.
- Choix explicite de la vitrine publique seule pour o2switch ; archive dédiée à un seul fichier et manifeste séparé préparés localement.
- Besoin futur confirmé de deux comptes : un compte personnel administrateur et un compte générique bureau.
- Couche OIDC expérimentale préparée sur le port 4175, avec dépendances verrouillées, autorisations serveur et sessions en mémoire ; aucun compte réel activé.
- Priorité réaffirmée au maquettage : migration sur l'hébergement réel reportée après l'achat du nom de domaine.
- Demande suivante confirmée : présenter la démonstration complète sur `http://tclongages.daje3540.odns.fr/` en remplacement de la vitrine, avec dépôt réalisé par l'utilisateur lui-même.
- Paquet de présentation séparé : sept HTML fictifs et trois fichiers de contrôle, fermé par défaut ; ancienne archive de vitrine conservée comme option.
- Logo JPEG et affiche fournis : logo temporaire intégré en attendant la version vectorielle ; une image locale par actualité, aperçu entier et nouvelle validation après modification.
- Photo réelle du court fournie et retenue pour l'accueil ; seconde photographie d'illustration et son crédit conservés.
- Neuf formules 2026–2027 transcrites visuellement des deux fiches PDF dans un relevé JSON commun à la FAQ et au parcours fictif ; conditions absentes laissées à confirmer.
- Quatrième aperçu ADOC et préparation simulée après validation : contenu limité à 2 000 caractères, option Ten'Up « Non » par défaut, sans connexion au service.

## Travail réalisé ou engagé au 17 septembre

La révision du 17 septembre ajoute la photo réelle du court, la FAQ et les formules issues du [relevé tarifaire commun](data/tarifs-inscription.json), ainsi que la [préparation ADOC simulée](api/adoc.md). Le paquet complet reste destiné au dépôt manuel de l'utilisateur à l'adresse HTTP confirmée. Il conserve dix fichiers et la fermeture par défaut ; les médias et les tarifs affichés sont intégrés aux HTML. Le [guide](docs/publier-demo-o2switch.md) précise aussi la mise à jour d'une copie déjà déposée : refermer avant remplacement et conserver un seul témoin. La [recette du paquet](docs/recette-demo-o2switch.md) consigne les résultats effectivement obtenus et distingue les contrôles locaux du comportement d'o2switch. Aucune publication par l'agent n'est réalisée.

La connexion OIDC reste du code expérimental conservé localement ; son activation et la migration du service réel restent reportées après l'achat du domaine. Fournisseur et identités réelles seront choisis ultérieurement, sans bloquer cette présentation fictive sans compte. La [documentation OIDC](docs/authentification-oidc.md) et sa [recette](docs/recette-oidc.md) décrivent séparément cette préparation.

La vitrine et les pages internes ont été contrôlées localement ; voir la [recette du prototype](docs/recette-prototype.md). Les tests et parcours de cette version utilisent un compte de recette éphémère uniquement en mémoire ; le contrôle WhatsApp remplace l'ouverture et le presse-papiers, sans essai Meta connecté ni message réel. Aucun compte bureau réel n'est configuré : les pages privées du serveur habituel restent fermées. Aucune étape de publication réelle par l'agent n'est autorisée par cette feuille de route.

La [visite guidée](docs/visiter-prototype.md) permet d'examiner le formulaire en cinq étapes, le tableau d'inscriptions, les disponibilités, les groupes et la communication à partir de données fictives. Lors de sa préparation, les 25 tests locaux, dont quatre propres à la démonstration, ont réussi ; ses parcours navigateur ont également été vérifiés. Les résultats et limites, dont le contrôle de l'archive, sont suivis dans la [recette de démonstration](docs/recette-demo.md). Ces résultats antérieurs ne qualifient pas OIDC. La visite ne crée aucun compte et ne modifie pas le serveur protégé sur le port 4173.

La [proposition du processus d'inscription](docs/processus-inscriptions.md) reste la référence du fonctionnement cible. L'API métier, le stockage central, la reprise sécurisée et les échanges XLS/CSV réels restent à réaliser. La proposition et l'exemple JSON doivent être validés avant de devenir un contrat de réalisation ; l'existence des écrans fictifs ne tranche pas les décisions métier.

L'ancien paquet de vitrine seule reste disponible selon son [guide de dépôt](docs/deployer-vitrine-o2switch.md). Les 25 tests locaux avaient réussi lors de sa préparation ; la [recette dédiée](docs/recette-vitrine-o2switch.md) conserve ce résultat historique. Il ne représente plus le paquet choisi pour la présentation complète et ses contrôles ne valident pas cette extension.

## Étapes envisagées avant le nouveau cadrage V1

| Priorité | Étape | Condition de passage |
|---|---|---|
| P0 | Partager et examiner la visite complète avec l'utilisateur. | Parcours fictifs adhérent, bureau et communication compris ; remarques et décisions recueillies. |
| P0 | Examiner les actualités avec affiche. | Logo provisoire et affiche fournie visibles, import local d'image, description, conservation et revalidation compris ; aucun envoi social réel. |
| P0 | Examiner le quatrième aperçu ADOC. | Compteur, blocage propre au canal au-delà de 2 000 caractères, option Ten'Up et nouvelle validation compris ; aucune préparation simulée assimilée à une publication. |
| P0 | Déposer et qualifier la démonstration sur la cible — utilisateur. | Paquet et guide prêts ; racine confirmée dans cPanel, sauvegarde privée, comportement Apache et 503 vérifiés avant ouverture. Aucune intervention d'hébergement par l'agent. |
| P0 | Vérifier puis refermer la présentation après validation. | Parcours fictifs examinés, retour 503 constaté aussi sur les pages directes ; pas de simple renommage de l'index ni de changement DNS. |
| Ultérieur | Activer la connexion des deux comptes confirmés. | Après la phase de maquette : fournisseur, identités et domaine choisis ; certificat HTTPS reconnu, proxy et sessions qualifiés ; procédure sécurisée présentée avant toute configuration réelle autorisée. |
| P0 | Valider le processus d'inscription et ses conditions tarifaires. | Relevé des montants disponible ; choix 125/150 €, remise famille, licence et éventuel cumul cours/adhésion précisés, champs et disponibilités validés, accès et reprise décidés par le club. |
| P1 | Relier les écrans validés à de vrais services d'inscription. | Périmètre accepté, hébergement compatible, stockage, comptes et reprise décidés ; SQLite reste une proposition. Recette sur données fictives avant toute collecte réelle. |
| P1 | Réaliser les imports/exports XLS et CSV du bureau. | Modèles et colonnes définis ; essais fictifs des erreurs, doublons, droits et réimports avant utilisation réelle. |
| P0 | Préciser les groupes WhatsApp à utiliser et examiner le partage manuel. | Groupes existants ou nouveaux identifiés, droits d'écriture confirmés ; choix des destinataires et envoi effectués par le responsable dans WhatsApp. |
| Ultérieur | Préparer la cible du dépôt manuel de la vitrine seule. | Après l'achat du domaine décidé par l'utilisateur ; archive prête, adresse et racine exactes, contenu existant, sauvegarde et HTTPS à vérifier selon le guide. Aucun déploiement par l'agent sans accord explicite. |
| Ultérieur | Vérifier le résultat public après une publication autorisée. | Bonne page, images, contacts, navigation et HTTPS observés. |
| P1 | Identifier la destination Facebook. | Page administrable et droits d'accès confirmés. |
| P1 | Préparer les services partagés après validation de la maquette. | OIDC expérimental à qualifier sur l'hébergement ; stockage métier central, secrets et sessions partagées si plusieurs processus à définir. Aucun résultat local ne vaut validation de production. |
| P1 | Implémenter le relais après validation. | Permissions Meta vérifiées, tests contrôlés et accord explicite pour les envois réels. |
| P1 | Étudier une éventuelle automatisation WhatsApp si elle est souhaitée. | Besoin confirmé, conditions officielles et compatibilité des groupes vérifiées avant décision ; aucune API active dans le prototype. |
| Ultérieur | Étudier une diffusion ADOC réelle si elle est souhaitée. | Moyens d'intégration autorisés, droits et effet Ten'Up vérifiés ; aucune disponibilité d'API supposée, aucun essai réel sans instruction explicite. |
| P1 | Compléter les informations du club. | Conditions tarifaires, horaires et modalités validés, version vectorielle du logo fournie ; photo du court reçue et JPEG conservé comme logo temporaire. |

Les dates de livraison, la racine et la configuration Apache distantes ainsi que le choix d'une éventuelle automatisation restent TBD. Le besoin d'un futur domaine pour le service réel est retenu ; la présentation fictive utilise l'adresse HTTP existante choisie. Aucun achat, fournisseur de connexion, changement DNS/HTTPS ni activation de comptes réels n'est réalisé dans cette étape.
