---
project: TC_Longages
document_type: development-workflow
title: Revue et livraison par phases
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [workflow, revue, git, livraison]
---

# Revue et livraison du site

Le processus est décrit dans [le guide adopté](../docs/framework-developpement.md) ; le [suivi JSON](../docs/suivi-chantier/suivi-chantier.json) porte les états. Entrées : demande explicite, périmètre du lot, critères et sources. Sorties : livrables, observations de contrôles, revue humaine puis autorisation distincte de la suite. Un test réussi n'est pas une validation humaine.

Le [moteur interactif local](../docs/installation-framework.md) au port 4181 enregistre notes, critères et décisions dans ce suivi avec sauvegarde, empreintes et contrôle de concurrence. Le responsable est déclaré localement ; aucune identité distante n'est authentifiée. Les notes et critères restent éditables avant la remise complète du dossier. Soumission, validation, autorisation suivante et démarrage sont distincts. Le texte du commentaire reste disponible après enregistrement des critères, mais chaque nouvel acte exige sa confirmation personnelle. Les phases 4 à 7 restent consultables sans action d'exécution ou d'autorisation distante dans cette interface.

L'exception d'installation portée par [framework/installation.json](../framework/installation.json) était bornée aux travaux locaux des phases 0 à 3 et à une échéance. Elle est refermée en `secured`, avec sa preuve de qualification. Les gardes de progression sont rétablies ; les protections d'accès et d'écriture sont restées actives. La préparation d'une PR ne réouvre pas cette exception. Les accords historiques et les commentaires sont préservés.

Deux workflows exécutables sont préparés sous `.github/workflows/` : [CI](../.github/workflows/ci.yml) et [politique de PR](../.github/workflows/pr-policy.yml). La CI construit les variantes et vérifie le framework sur Windows ; le contrôle `policy` examine le titre, les sections et la checklist de la PR. Les actions sont référencées par empreinte et les permissions limitées à la lecture. Leurs déclencheurs ne déploient rien. Une réussite distante doit être constatée sur la version exacte avant d'être consignée comme preuve.

Le workflow `policy` utilise le mode **strict** : les cases de la checklist doivent être renseignées pour réussir. L'agent laisse les déclarations humaines décochées, donc ce contrôle reste rouge jusqu'à leur examen par le responsable. `--allow-unchecked` sert uniquement à vérifier localement la structure lors de la préparation de la PR ; cette option n'est pas utilisée dans le workflow actif. Le contrôle vérifie les déclarations, sans authentifier une revue indépendante ni la remplacer. Avec un compte responsable unique, aucune approbation par une seconde personne n'est présumée. Les modèles de préproduction, préparation et livraison restent inactifs, suffixés `.example` ; leurs opérations génériques ne constituent pas un adaptateur implémenté.

La portabilité des sources tarifaires et des recettes est contrôlée selon [la préparation du dépôt](../docs/preparer-depot.md). L'utilisateur a rendu le dépôt public ; cela rend possible la préparation des protections de branche sans la limite d'offre constatée précédemment. Une protection empêchant effectivement une fusion est un réglage séparé, dont l'application doit être vérifiée selon [le guide du framework](../docs/framework-developpement.md). La visibilité publique impose une revue des fichiers et de leur historique avant envoi ; elle ne publie pas le site Drupal.

Pour remettre le lot demandé : conserver l'historique initial, préparer le commit du candidat, créer sa PR, relever les résultats correspondant à cette version et rattacher ces références à la phase. La remise technique peut rendre le dossier disponible à la revue ; elle n'ajoute ni validation personnelle ni autorisation de fusion. Les numéros de PR, empreintes et résultats réels sont conservés dans le registre, sans valeurs inventées dans cette procédure.

Le parcours de livraison cible est : construire le candidat exact, relire le lot, installer en préproduction autorisée, vérifier les usages, préparer une sauvegarde et restaurer cette copie, obtenir l'accord de livraison, refaire une sauvegarde fraîche restaurée avant toute écriture, déployer fermé, contrôler la cible, puis ouvrir sur accord explicite. Un Drupal dédié au club est retenu, avec maintenance administrée par les options natives du moteur. La procédure exacte dépend encore de la cible d'hébergement, des accès et de leur recette. Une installation Drupal locale ne vaut pas livraison de la V1.

Aucune valeur de secret n'est définie dans ce lot. Les futurs noms, portées, titulaires, restrictions réseau et moyens de retrait seront documentés au moment du choix des adaptateurs ; aucune clé AVEREO ne sera reprise implicitement. Le cadre conserve les accords déjà acquis dans leur portée et n'en crée pas à partir de cases cochées automatiquement.
