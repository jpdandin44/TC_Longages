---
project: TC_Longages
document_type: phase-specification
title: "Phase 0 — Cadrage et audit"
status: active
version: git
created: 2026-09-29
updated: 2026-10-05
owner: jpdandin
tags: [framework, phase, revue, gouvernance]
---

# Phase 0 — Cadrage et audit

## Périmètre actuel et conservation de la revue antérieure

Le 5 octobre, le responsable limite la livraison à la vitrine publique des
sept pages. Les comptes Bureau/Capitaine, les droits par équipe et leur
édition sont reportés en V2. Le [parcours de mise en ligne](../parcours-mise-en-ligne.md)
et le bloc `developmentWorkflow` du suivi canonique portent ce périmètre,
les réserves et les preuves courantes. Les lots fonctionnels décrits plus bas
restent conservés ; ils ne conditionnent plus cette livraison V1.

Ce dossier relève de **Cadrage**, première des quatre phases actuelles.
Le découpage 0–7 décrit ci-dessous reste historique, avec son mapping dans le
[suivi canonique](suivi-chantier.json). La validation locale du 29 septembre
est conservée ; elle ne couvre pas automatiquement l’élargissement du périmètre.

Le responsable a depuis autorisé les lots distincts du compte principal TC
et de première installation. La préproduction est installée sous maintenance,
avec SQL fonctionnel et connexion HTTPS native ; le domaine officiel et la
Lune sont conservés. Les [preuves d’installation](../../data/framework-revue-verification.json#primaryAccountFirstInstallation)
sont des constats techniques, sans validation de phase ni accord de production.
Comptes natifs : recette connectée à effectuer. Édition des pages et rôles
Bureau/Capitaine : absents du ZIP livré, lot fonctionnel à examiner. Avant
production : sauvegarde/restauration, retour arrière et recette qualifiés.

Le [guide du cockpit](../installation-framework.md) permet une reprise de revue
personnelle de l’ancienne validation. Préparer les preuves courantes et une
nouvelle PR conserve les critères obligatoires et l’ancienne décision ; seule
une nouvelle action humaine peut accepter ce périmètre actualisé.

Le [suivi JSON](suivi-chantier.json) est l’unique source des états, dates et décisions. Ce dossier expose le périmètre et les critères ; sa présence ne constitue pas une remise ou une validation. Les phases 0–7 ne remplacent pas les lots métier G0–G8.

## Objectif

Définir le besoin, les sources, les limites et les critères vérifiables.

## Autorisation et périmètre

La demande utilisateur du 29 septembre (« Peux-tu appliquer le framework de developpement joint stp ? ») est consignée par TCL-D01 dans le [suivi canonique](suivi-chantier.json). Elle permet l’adaptation locale du cadre et l’audit de reprise. Elle ne valide pas les dossiers produits et n’autorise ni phase suivante, publication, modification DNS/HTTPS, connexion fournisseur, secret ou merge. L’horodatage de la décision est celui de sa consignation, pas une heure originale reconstituée.

La demande suivante est consignée par TCL-D02 : elle autorise les travaux d’installation et les essais locaux des phases 0 à 3 dans une exception temporaire, ainsi que l’inventaire cPanel en lecture seule. TCL-D03 confirme un Drupal dédié au club. Ces décisions ne valident aucune phase ; la fermeture de l’exception et les protections conservées sont décrites dans [l’installation du framework](../installation-framework.md).

## Existant et sources

Le projet possède une V1 locale de sept pages, un logo fourni, la photographie réelle du court, une grille tarifaire transcrite et un paquet de démonstration fermé par défaut. La [configuration V1](../../config/officiel.json) garde le contact simulé, les services Google non raccordés, les équipes vides et l’authentification V1 inactive. Le choix d’un Drupal dédié est désormais confirmé et son instance locale est distincte de cette configuration statique ; voir [l’installation Drupal](../installation-drupal.md). Les prototypes antérieurs sont conservés séparément.

L’utilisateur dispose de **tclongages.fr**. Le connecteur GitHub avait permis de lire le dépôt [jpdandin44/TC_Longages](https://github.com/jpdandin44/TC_Longages), privé, sur sa branche `main` ; son README et son commit initial sont à préserver. Le dossier du site est maintenant raccordé à cet historique ; l’accès CLI a été vérifié et l’utilisateur a rendu le dépôt public. Les protections et les modalités du premier envoi sont détaillées dans [la préparation du dépôt](../preparer-depot.md).

L’inventaire cPanel a identifié la cible du domaine et des prérequis d’hébergement encore à résoudre. Cette lecture ne qualifie ni HTTPS ni une installation de production ; les constats et les écarts avec l’instance locale figurent dans [l’installation Drupal](../installation-drupal.md).

Les [lots historiques G0–G8](../integration-officiel.md) restent le découpage métier de la V1. Les phases 0–7 présentes ici décrivent le processus de réalisation. G0 réalisé localement et G1 qualifié localement n’équivalent pas à des phases validées par un humain ; leurs acquis sont préservés. G2 reste partiel, G3–G8 ne sont pas acquis.

## Exigences et critères à préparer

La [spécification courante](../../requirements.md) et [l’intégration V1](../integration-officiel.md) restent les sources du besoin. Les identifiants ci-dessous servent au cadrage du processus sans renuméroter le backlog métier reçu.

| ID | Besoin et critère observable | Vérification pertinente / preuve attendue |
|---|---|---|
| TCL-FW-01 | Conserver les acquis V1 et séparer prototype, démonstration et production. | Rapprocher configuration, [tests officiels](../../tests/officiel.test.mjs), paquets et [recette historique](../recette-v1-sous-domaine.md) ; revue du diff. |
| TCL-FW-02 | Aucun passage de phase, accord humain ou résultat de publication ne doit être inventé. | Vérifier le suivi, la provenance des décisions TCL-D01 à TCL-D03 et les refus du contrôleur de politique ; aucune autorisation héritée d’AVEREO. |
| TCL-FW-03 | Conserver des vues issues du JSON et des critères explicitement présentés. | Régénérer les vues, contrôler leur alignement, les liens locaux et les métadonnées ; aucun pourcentage d’effort déduit. |
| TCL-FW-04 | Identifier le candidat et le dépôt avant revue et livraison. | Lecture Git/GitHub authentifiée, commit du site, manifeste du candidat et PR ; la remise référence le commit réellement testé, distinct du commit initial et des reçus de revue. |
| TCL-FW-05 | Garder la décision humaine sur les effets externes et l’ouverture. | Vérifier absence de déploiement automatique ; procédure avec cible, effet, risque et retour dans [les commandes sensibles](../commandes-sensibles.md). |
| TCL-FW-06 | Préserver les limites d’identité et les données privées. | Tableau de bord sans commande distante ; qualification future de l’authentification, des rôles et des droits par équipe avant G3. |

Cette matrice cadre l’adoption. Elle ne remplace pas la traçabilité exhaustive des tâches et tests du backlog V1, qui reste à compléter lors de la revue du cadrage.

## Critères de sortie

- [ ] Périmètre et exclusions confirmés
- [ ] État existant et risques inventoriés
- [ ] Exigences reliées à des tests
- [ ] Responsables et règles de décision identifiés

Ces cases décrivent la revue à conduire ; leur affichage n’enregistre aucune décision. Le journal canonique doit relier toute future acceptation aux versions présentées et aux preuves recevables.

## Vérifications

Les [rapports du 24 septembre](../recette-v1-sous-domaine.md) conservent leurs résultats datés : 93 tests Node, 121 contrôles Apache locaux et observations de sept pages aux trois largeurs contrôlées. Ils n’ont pas de commit source enregistré et ne sont pas convertis en nouvelles preuves passed du framework. Les contrôles d’installation du moteur interactif et de Drupal local sont documentés dans [le point de session](../point-session.md) et les guides d’installation. Ils ne qualifient ni les droits métier du futur bureau ni une cible distante. Les journaux du suivi restent la référence des preuves effectivement rattachées à une version.

## Revue et PR

La référence de PR et les dates de remise figurent uniquement dans le suivi. Aucune PR n’est attestée à l’initialisation. Préparer une PR lors du passage en revue, après lecture du dépôt communiqué ; conserver les commentaires, la version relue et les décisions humaines. Le merge reste humain.

## Réserves et prochaine étape

Le raccordement conserve le README initial dans l’historique. Les PDF vierges de recette sont inclus avec leurs empreintes. La demande de finaliser les PR autorise l’envoi du lot technique ; elle ne valide pas cette phase. Le reçu associé au suivi expose les tests courants et la configuration effective des protections. Les prérequis PHP, HTTPS et de restauration sont des réserves pour l’environnement hébergé, pas des blocages pour terminer son audit.

Le responsable doit encore examiner le périmètre, les réserves et les critères. Son acceptation de cette phase et l’autorisation de la suivante seront deux décisions distinctes. Les travaux locaux autorisés par TCL-D02 ne remplacent pas ces décisions. La réutilisation ultérieure de composants [Drupal/CONNECT](../mutualisation-connect.md), les ressources Google, la qualification des cibles et la restauration demeurent à traiter.
