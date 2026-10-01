---
project: TC_Longages
document_type: repository-handoff
title: Dépôt, pull requests et contrôles de fusion
status: active
version: git
created: 2026-09-29
updated: 2026-10-01
owner: jpdandin
tags: [git, revue, perimetre, confidentialite]
---

# Dépôt et revue du site

Le dépôt [jpdandin44/TC_Longages](https://github.com/jpdandin44/TC_Longages) est **public**, après le changement effectué par l’utilisateur le 29 septembre. La racine Git est `Site_Internet/` ; le parent administratif et les dossiers réels d’adhérents restent hors dépôt. Le commit initial `e8797723f04b592285484b21a7915e895e128696` et son README sont préservés dans l’historique.

La demande de finaliser les PR autorise le raccordement, l’envoi du lot et l’activation des contrôles. Elle ne donne aucun accord de fusion, de changement d’hébergement ou de publication du site. La branche de reprise est `codex/suivi-revue-phases`. La référence effective de PR, le candidat testé et les résultats sont consignés dans le [registre canonique](suivi-chantier/suivi-chantier.json) et le [reçu de revue](../data/framework-revue-verification.json).

## Protection de main

Configuration enregistrée par GitHub le 29 septembre :

- passage obligatoire par une PR ; branche à jour par rapport à `main` ;
- contrôles requis `technical-ci` et `policy` ;
- règles également appliquées aux administrateurs ;
- discussions résolues avant fusion ;
- envoi forcé et suppression de `main` interdits.

Le propriétaire travaille seul : zéro approbation indépendante est exigée, car un compte ne peut pas approuver sa propre PR. Le contrôle `policy` exige les déclarations de la checklist mais ne certifie pas l’identité de leur auteur. L’agent laisse toutes les cases humaines décochées et ne fusionne pas. La protection en vigueur peut être consultée dans les [paramètres des branches](https://github.com/jpdandin44/TC_Longages/settings/branches) ; son observation datée figure dans le reçu.

La limite GitHub Pro rencontrée lorsque le dépôt était privé est historique. Le dépôt public permet les [branches protégées avec GitHub Free](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches).

## Ce que font les contrôles

Le [guide CI](../workflows/ci-et-revue.md) documente CI et politique, ainsi que la préparation manuelle ajoutée au candidat. `technical-ci` construit et teste les variantes sur Windows, puis contrôle le framework. Les deux PDF vierges de tarifs sont conservés dans [les fixtures](../tests/fixtures/tarifs/README.md), avec les empreintes des originaux, pour permettre une recette autonome après clone. Les recettes navigateur, Apache et Drupal local restent distinctes de cette CI et ne sont pas déclarées exécutées par elle.

`policy` contrôle strictement la description et ses confirmations humaines. Tant que les quatre cases restent décochées, un échec de ce contrôle est attendu et empêche la fusion ; il ne signale pas une panne du site. La commande locale avec `--allow-unchecked` sert uniquement à vérifier la structure avant remise. Aucun succès structurel n’accorde de validation.

Le workflow [Préparer la livraison](../.github/workflows/preparer-deploiement.yml) est ajouté au candidat le 1er octobre : lancement manuel après intégration humaine, construction sans accès par défaut ou qualification SSH seule dans un environnement TC distinct. Il ne livre pas le site. Les workflows de livraison restent des modèles `.example`, non exécutables. Aucun push ou merge ne livre le site, ne change sa maintenance ou n’utilise un secret de production. `CODEOWNERS.example` demeure un exemple ; aucune revue indépendante fictive n’est annoncée.

## Périmètre public et retour

Le [.gitignore](../.gitignore) exclut secrets, comptes de recette, bases locales, dépendances, sauvegardes et sorties générées. Les contenus du site, scripts, documentation, tests et recettes fictives constituent le lot source. Les détails internes de compte, chemins d’hébergement et chemins personnels ont été retirés du contenu public ; les originaux d’exploitation restent dans `.local/publication-backup/`, exclu de Git. L’inventaire partagé porte explicitement `public_sanitized`.

L’envoi de cette branche rend son contenu consultable publiquement et lance les contrôles de PR. Avant fusion, fermer la PR laisse `main` inchangée ; cela ne garantit pas l’effacement des copies déjà consultées. Toute mise en ligne web reste une autre opération. L’authentification Git utilise le compte déjà connecté sur le poste, sans secret ajouté aux sources ni valeur copiée dans la conversation.

## Revue d’un lot

Préparer un commit source, exécuter ses contrôles puis rattacher le [manifeste](candidat-revue.md), les résultats et la PR au suivi. Les reçus ajoutés ensuite ne doivent changer aucune source du candidat. Une modification de source rend les preuves périmées et nécessite une nouvelle qualification. La revue locale distingue validation de phase et autorisation de démarrer la suivante. La checklist GitHub et la fusion restent également des actions personnelles distinctes.
