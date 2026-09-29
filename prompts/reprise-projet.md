---
project: TC_Longages
document_type: reusable-prompt
title: Reprendre le développement piloté du site
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [prompt, reprise, framework]
---

# Reprendre le projet TC Longages

## Objectif

Reprendre le lot demandé à partir de l'état réel, en conservant les acquis et le contrôle humain des décisions.

## Contexte

Le site et sa documentation résident dans `Site_Internet/`. La V1 est préparée localement ; le domaine est obtenu selon l'utilisateur. L'état Git, les environnements, les décisions et la première action actuelle se lisent dans le [point de session](../docs/point-session.md), le [profil](../framework/profil-projet.json) et le [suivi](../docs/suivi-chantier/suivi-chantier.json), sans recopier ici leurs valeurs évolutives.

## Entrées

Demande courante, AGENTS local et global, README, point de session, profil, suivi, fichiers réellement concernés, sources et preuves référencées.

## Instructions

1. Inspecter le contexte et les modifications humaines avant d'agir. Lire la branche, le commit et l'état Git réels ; conserver l'historique initial du dépôt. Le raccordement CLI a été rétabli : ne pas reprendre un ancien refus d'authentification comme fait courant sans nouvelle vérification.
2. Identifier le lot autorisé et les critères. Les phases 0–7 décrivent le processus ; G0–G8 restent les lots métier V1. Ne pas réinitialiser les acquis ni inventer d'accord.
3. Accomplir le lot local demandé, tester ses effets, mettre à jour les sources documentaires et régénérer les vues. Lire les nouveaux commentaires du suivi et consigner les réponses sans effacer les notes initiales. Pour la demande autorisée de PR, préparer le candidat et ses références réelles, vérifier les contrôles correspondant à ce commit puis rattacher la remise au suivi. Ne jamais cocher les critères, ajouter une validation personnelle ou fusionner par déduction de cette préparation.
4. Avant toute opération sensible, présenter cible, effet, risque, sauvegarde et retour puis vérifier l'accord applicable. Aucun déploiement ne découle d'un push, d'un merge ou de la possession du domaine.
5. Clore avec un point de reprise exact, les résultats réels et leurs limites ; arrêter seulement les processus identifiés que la clôture autorise à arrêter.

## Contraintes

Aucune valeur de secret dans le chat ou Git. Aucun changement d'AVEREO/CONNECT, DNS, certificat, rôle, boîte de messagerie, ressource Google ou publication hors du périmètre autorisé. Le moteur interactif local du framework permet notes, critères et décisions de revue avec identité déclarée, sans authentification distante ni endpoint de livraison. Lire [sa procédure](../docs/installation-framework.md) et l'état courant de [l'installation](../framework/installation.json) : l'exception ne dispense jamais de validation humaine, ne doit pas être réouverte implicitement après sa fermeture et n'autorise aucune publication. Les notes et critères doivent rester éditables pendant la préparation des preuves ; ne pas ajouter de verrou de saisie pour compenser l'absence d'une PR.

La CI et la politique de PR sont les deux workflows exécutables préparés ; les modèles de livraison restent inactifs. Le workflow `policy` est strict et reste rouge tant que les déclarations de checklist requises sont décochées. L'agent doit les laisser à l'utilisateur ; ne pas assouplir le workflow pour le rendre vert. `--allow-unchecked` est réservé au contrôle local de structure pendant la préparation. Une CI verte ne vaut pas accord humain et les cases ne prouvent pas une approbation indépendante par une seconde personne lorsqu'un seul compte est utilisé. Distinguer ces contrôles du verrou de branche GitHub, dont l'installation doit être vérifiée. L'utilisateur a rendu le dépôt public : ne pas traiter la limite de l'ancienne offre privée comme un blocage courant. Vérifier la liste des fichiers et leur historique avant tout envoi, sans secrets, données d'adhérents ni informations personnelles inutiles. Ce changement de visibilité ne vaut pas autorisation de mise en ligne du site. Les patrons reçus restent des références à adapter.

Le choix d'un Drupal dédié au TC Longages est confirmé. Vérifier son état réel dans le point de session et sa recette ; un serveur local ne prouve ni installation distante ni protection d'un futur bureau public. La maintenance cible relève de l'administration native Drupal ; les anciennes archives statiques restent des références distinctes.

## Sources autorisées

Fichiers du projet, demandes utilisateur, dépôt communiqué lorsqu'accessible, documentation officielle si une recherche technique est nécessaire. Préserver les pièces reçues et distinguer faits observés, déclarations et propositions.

## Format de sortie

Résultat concret, contrôles exécutés, réserves avec prochaine action, liens directs et bilan documentaire proportionné.

## Tests

Contrôler le lot dans son environnement réel. Pour le suivi : `npm.cmd run framework:check` et `node --test tests/framework-server.test.mjs` pour les écritures locales sur fixtures. Vérifier aussi la conservation du commentaire après enregistrement des critères et la nouvelle confirmation nécessaire. Pour le site : contrôles ciblés puis `npm.cmd run check` selon impact, en exposant toute dépendance manquante. Pour la PR : vérifier ses références, le commit testé et les résultats réels des workflows. Ne jamais tester les confirmations humaines dans le vrai journal avec de faux accords. Ne jamais annoncer une qualification d'hébergement à partir des tests locaux.

## Historique des versions

Créé le 29 septembre 2026 lors de l'adoption du framework, puis adapté à l'interface interactive et au raccordement Git. Métadonnée `version: git` selon convention ; la branche et le commit courants se lisent dans Git et le suivi, sans incrément documentaire manuel.
