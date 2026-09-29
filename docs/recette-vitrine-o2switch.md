---
project: TC_Longages
document_type: verification-report
title: Recette de l'archive publique pour o2switch
status: active
version: git
created: 2026-09-16
updated: 2026-09-24
owner: jpdandin
tags:
  - recette
  - archive
  - o2switch
---

# Recette de l'archive publique du 16 septembre 2026

Ce rapport conserve les résultats historiques du 16 septembre, y compris l'ancien contact FFT. Depuis le 24 septembre, le contact public est `tclongages@gmail.com` ; la [recette de messagerie courante](recette-officiel.md#révision-du-24-septembre--contact-gmail-et-support-des-tests) documente les pages et archives régénérées. Les manifestes courants correspondent à la dernière génération ; les tailles et résultats ci-dessous restent historiques.

## Périmètre

L'utilisateur a choisi la vitrine publique seule pour un dépôt qu'il réalisera sur o2switch. La [procédure de dépôt](deployer-vitrine-o2switch.md) est la référence opérationnelle. Aucun accès d'hébergement, transfert, changement DNS, modification HTTPS ou déploiement n'a été effectué pendant cette préparation.

La source de la page reste `src/index.html`, traitée par `scripts/build.mjs` pour produire `release/index.html` sans le bloc de prototype. `npm.cmd run release:package` construit le ZIP avec une liste limitée à ce fichier. Le [manifeste généré](../livrables/tc-longages-vitrine-o2switch.manifest.json) identifie les tailles et empreintes SHA-256 du ZIP et du HTML.

## Vérifications réalisées

Cette section conserve les résultats de la première recette, avant remplacement du logo. Le ZIP a ensuite été régénéré avec le JPEG fourni afin de rester aligné avec `release/index.html` ; son manifeste lié ci-dessus décrit toujours le fichier courant. Les tests actuels de la vitrine et du logo font partie des 70 tests de la [recette de mise à jour](recette-demo-o2switch.md). La vitrine seule reste une option distincte du paquet complet actuellement demandé.

| Contrôle | Résultat |
|---|---|
| `npm.cmd run check` | 25 tests réussis sur 25, y compris les contrôles existants de la vitrine, de séparation et de génération déterministe. |
| `npm.cmd run release:package` | ZIP créé localement sans accès réseau ; manifeste associé créé hors du ZIP. |
| Lecture indépendante du ZIP avec Python `zipfile` | Une seule entrée `index.html` directement à la racine ; contrôle CRC réussi. |
| Extraction et empreintes | Fichier extrait identique octet pour octet à `release/index.html` ; empreintes HTML et ZIP conformes au manifeste. |
| Inspection des 35 liens | Ancres présentes ; les autres liens sont HTTPS ou `mailto:`. Aucun lien vers une page interne du bureau ou une page de démo. |
| Ressources et contenu | Deux photos intégrées, styles, favicon et script intégrés ; sept contacts vers `23310230@fft.fr`. Aucun formulaire, stockage navigateur, script de simulation ou compte. |
| Revue indépendante en lecture seule | Reconstruction depuis les sources conforme ; aucun élément privé ni ressource locale manquante détecté dans la vitrine. |
| Ouverture du HTML extrait dans Chrome avec Playwright | Images décodées et aucun débordement horizontal aux largeurs 1 440, 390 et 320 px ; menu mobile ouvrable et refermable avec Échap. Aucun appel HTTP(S) externe ni erreur JavaScript durant cette ouverture. |

Les [résultats navigateur](recette/vitrine-o2switch-results.json) concernent l'ouverture du fichier extrait dans un profil temporaire. La vérification force le décodage des images, dont celle chargée à la demande plus bas dans la page. Aucun clic vers un service externe n'a été effectué. La recette ne remplace pas les contrôles du domaine après publication.

## Documentation et limites

Le socle documentaire, le guide de commandes sensibles et le workflow de publication sont alignés sur le choix de la vitrine seule. Le guide d'installation et ce compte rendu complètent ces références. La mention périmée du workflow qui attendait encore le contenu d'inscription a été corrigée : le processus et les écrans fictifs sont disponibles, tandis que les services réels restent à construire.

La cible exacte, la racine documentaire, le contenu déjà hébergé et le certificat restent **TBD pour ce dépôt**. L'archive n'efface pas d'anciennes pages ni de règles serveur. L'utilisateur doit vérifier et sauvegarder la cible avant extraction, puis vérifier le contenu servi et HTTPS. Les destinations externes et la réception des e-mails n'ont pas été testées pendant cette recette. Aucune disponibilité publique n'est attestée.

Aucun problème détecté dans le périmètre local vérifié. La protection du futur bureau, les inscriptions réelles et leurs échanges XLS/CSV sont hors de cette archive.
