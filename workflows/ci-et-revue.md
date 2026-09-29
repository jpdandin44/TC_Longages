---
project: TC_Longages
document_type: workflow-documentation
title: Contrôles automatiques et revue humaine
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [ci, github, revue, framework]
---

# Contrôles automatiques et revue humaine

Deux workflows exécutables sont préparés dans `.github/workflows/` : [CI](../.github/workflows/ci.yml) construit et teste le candidat ; [PR Policy](../.github/workflows/pr-policy.yml) contrôle sa description et la présence des déclarations de revue humaine. Leur présence dans le code ne prouve ni une exécution GitHub réussie ni l’activation d’une protection de branche. Les résultats distants se lisent sur la PR concernée.

## Entrées, sorties et dépendances

La CI reçoit le checkout de la PR ou un push sur `main`, installe les dépendances du verrou npm sans scripts d’installation et les versions Python du [fichier de référence](../docs/references/framework-developpement-pilote/requirements-verification.txt). Le runner Windows couvre les tests d’archives .NET/PowerShell. Les deux [PDF vierges conservés](../tests/fixtures/tarifs/README.md) rendent le contrôle des sources tarifaires indépendant du poste initial.

Le workflow exécute `npm.cmd run check`, puis `framework:build` et `framework:check`. La génération ne touche que les vues dérivées dans le checkout éphémère de CI : elle évite qu’une ancienne vue fasse échouer un candidat dont le profil ou le suivi vient d’évoluer. Aucun commit de vues, push de résultat ou modification des décisions n’est exécuté. Les sorties sont les journaux et statuts GitHub ; aucun artefact n’est publié sur un hébergement.

Après ces contrôles, la CI vérifie `data/framework-candidate.json` lorsqu’il existe. Le checkout conserve l’historique Git complet pour retrouver le commit source et vérifier son appartenance à l’historique courant. Le [contrôle du candidat](../scripts/framework-candidate.mjs) compare les sources réelles au commit présenté, avec les seules exclusions documentaires déclarées par ce script ; la régénération des deux vues ne crée donc pas de changement de candidat. Un manifeste incohérent ou une différence de source fait échouer la CI. Pour le premier bootstrap sans manifeste, un message indique explicitement que la preuve reste à préparer : cette étape n’atteste alors aucune vérification du candidat.

Les versions exactes de Node, Python et des Actions sont déclarées dans les workflows. Les SHAs des Actions ont été vérifiés sur leurs références officielles le 29 septembre 2026 : [checkout v7.0.1](https://github.com/actions/checkout/releases/tag/v7.0.1), [setup-node v7.0.0](https://github.com/actions/setup-node/releases/tag/v7.0.0) et [setup-python v7.0.0](https://github.com/actions/setup-python/releases/tag/v7.0.0). Les seuls droits demandés sont `contents: read` ; checkout ne conserve pas les identifiants Git. Aucun secret de déploiement, environnement de production ou connexion à l’hébergeur n’est demandé.

## Portée de la politique de PR

La politique reçoit titre et description via des variables d’environnement, sans interpoler leur texte dans une commande. Elle vérifie le titre, les sections, les libellés de checklist et l’absence de liens temporaires. Sur GitHub, le mode strict exige également que les quatre déclarations de revue humaine soient cochées. Une PR préparée avec ces cases décochées a donc volontairement un contrôle `policy` non réussi, dans l’attente du responsable. Aucun workflow ni agent ne les coche automatiquement.

Seul le responsable peut compléter ces confirmations après sa revue. Le mode local `--allow-unchecked` contrôle uniquement la structure avant cette étape. Le mode strict vérifie des déclarations écrites, sans prouver l’identité ni l’authenticité de la personne ayant coché. Un résultat vert ne vaut donc pas approbation GitHub indépendante ou validation d’une phase. La validation de phase, l’autorisation de phase suivante et la revue de PR restent distinctes. L’interface de suivi locale ne dispose pas d’une identité GitHub authentifiée et les workflows n’y créent aucune décision.

Le dépôt est désormais public après décision utilisateur ; la restriction d’offre rencontrée précédemment sur le dépôt privé ne décrit plus cette situation. L’état courant du raccordement et des protections est consigné dans [le guide du dépôt](../docs/preparer-depot.md). Tant qu’une protection n’a pas été vérifiée, ces statuts ne constituent pas un verrou de merge imposé par GitHub. L’auteur d’une PR ne peut pas se substituer à une revue GitHub indépendante.

## Limites et évolution contrôlée

Les recettes nécessitant des navigateurs, le Drupal local vivant ou un serveur Apache ne sont pas exécutées par ces workflows. Une CI verte ne confirme ni DNS, certificat, comptes, hébergement, intégrations Google/FFT ni ouverture publique.

Le contrôle du framework autorise uniquement les deux noms de workflows et leurs empreintes de contenu normalisé. Une modification de leur logique ou l’ajout d’un autre workflow échoue tant que cette liste contrôlée n’est pas revue dans [le script](../scripts/framework.mjs), avec ses tests. Cette barrière protège contre une activation involontaire ; elle ne remplace pas la revue humaine d’une PR capable de modifier le code de contrôle.

Les autres workflows restent en `.example`, notamment préproduction, préparation et déploiement. Les deux fichiers `.yml` sont les seules sources exécutables des contrôles CI et policy ; les modèles reçus sont conservés intacts dans les références du framework. Aucun push ou merge ne déploie le site. Toute future chaîne de livraison doit être qualifiée et explicitement autorisée séparément.
