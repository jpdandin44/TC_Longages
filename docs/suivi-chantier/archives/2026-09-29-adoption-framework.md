---
project: TC_Longages
document_type: session-archive
title: Archive de la session d’adoption du framework
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [session, reprise, framework]
---

# Point de session — 29 septembre 2026

## Portée et état

Le projet reprend après la demande de mise en attente. L'utilisateur indique disposer de `tclongages.fr`, demande l'application du framework joint puis communique le dépôt dédié. Le présent lot applique le cadre au travail local et prépare la suite. La phase 0 reste en cours, sans validation ou remise en PR ; aucune phase suivante, publication Git ou mise en production n'est autorisée par ce lot.

La clôture précédente n'avait pas de preuve d'arrêt effectif du serveur ou de documentation de standby. Cette limite historique est conservée. Aucun écouteur sur `127.0.0.1:4180` n'a été constaté lors de cette reprise ; aucun processus n'a été arrêté ni lancé pour servir le site dans ce lot.

## Réalisé

Référence du framework conservée intacte, profil et suivi contextualisés, huit dossiers de phases, adaptateur et tableaux en lecture seule, modèles de revue, exclusions Git, guide d'adoption et prompt de reprise. Le domaine est maintenant `obtained-user-confirmed` dans la configuration ; DNS/HTTPS et racine restent non vérifiés. Le site garde son contact `tclongages@gmail.com`, le support prévu non activé, les intégrations Google absentes et le choix Drupal/CONNECT ouvert.

Les décisions et observations sourcées se trouvent dans le [suivi canonique](../suivi-chantier.json). Aucune autorisation AVEREO n'a été copiée. La V1, les anciens prototypes et leurs sources restent conservés. L'archive de présentation est toujours [tc-longages-v1-demo-o2switch.zip](../../../livrables/tc-longages-v1-demo-o2switch.zip), fermée par défaut ; ce n'est pas une livraison officielle sur le nouveau domaine.

## Vérifications

Le [rapport de cette adoption](../../../data/framework-verification.json) consigne les contrôles réellement effectués et leurs limites. Les recettes du 24 septembre restent historiques : 93 tests Node, 121 contrôles Apache et parcours navigateur, sans nouvelle validation humaine ou hébergement déduit de ces résultats. Aucun nouvel essai Apache distant ou connexion métier réelle n'est compris dans le lot.

## Réserves et prochaine action

- Accès au [dépôt communiqué](https://github.com/jpdandin44/TC_Longages) refusé par les identifiants Git et CLI du poste : contenu, visibilité, branche et protections inconnus. Rétablir l'accès puis inspecter avant tout raccordement ou envoi. Aucun `git init`, remote, commit ou push effectué.
- Deux PDF de vérification tarifaire sont hors du futur dépôt ; les recettes navigateur dépendent d'outils non installés par npm. Qualifier leur conservation et la reproductibilité avant activation de CI. Voir [préparer le dépôt](../../preparer-depot.md).
- Adaptateur de suivi limité à la lecture et au cadrage ; moteur interactif, adaptateurs de préproduction/livraison/restauration et protections Git non qualifiés. Les modèles d'Actions restent inactifs.
- Domaine obtenu selon déclaration, mais DNS/HTTPS/cible inconnus ; vérifier avant toute future préparation d'hébergement. Drupal/CONNECT, ressources Google, support et règles d'exploitation restent à décider dans leur lot.

La première action est le rétablissement de l'accès GitHub, puis l'inventaire du dépôt et la préparation du lot initial avec ses fichiers exacts. Présenter les résultats du cadrage et le périmètre du socle avant de demander les décisions correspondantes. Ne pas déployer à la reprise par déduction de cette note.

## Clôture du lot local

La session d’adoption est terminée et son état est conservé dans une archive dérivée du présent point. La phase de cadrage reste en cours : cette clôture ne valide aucun critère humain et n’autorise pas de phase suivante. Aucun processus de service du site n’a été lancé ou arrêté.

## Bilan documentaire

Le socle reste présent. Le README, l'architecture, les exigences, la feuille de route, les décisions, le changelog et les instructions locales renvoient au cadre adopté. Les documents de phase, guides, workflow et prompt sont propres au club ; les sources reçues restent des références intactes. Les contrôles de métadonnées, liens et vues sont détaillés dans le rapport. La politique globale est appliquée, sans modification de son fichier global.
