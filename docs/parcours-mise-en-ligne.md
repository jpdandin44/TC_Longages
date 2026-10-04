---
project: TC_Longages
document_type: release-process
title: Parcours simplifié de mise en ligne V1
status: active
version: git
created: 2026-09-29
updated: 2026-10-04
owner: jpdandin
tags: [drupal, recette, preproduction, production]
---

# Parcours simplifié de mise en ligne V1

Le [suivi canonique](suivi-chantier/suivi-chantier.json) porte les quatre phases communes et leurs états. Le [registre opérationnel JSON](../data/parcours-mise-en-ligne.json) y référence ses dossiers et reçus ; il ne maintient plus un second statut de phase. Les huit phases antérieures et leurs décisions sont conservées avec leur mapping. Cette simplification du pilotage de la V1 publique ne réécrit pas une validation et n'autorise pas une intervention distante.

| Étape | Critère de sortie concret | État courant au 4 octobre |
|---|---|---|
| Cadrage | Candidat Drupal identifiable, prérequis PHP/SQL/racines/HTTPS et sauvegarde qualifiés. | En cours : candidat reçu, compte principal configuré et fermé ; première installation autorisée, fichiers transférés et vérifiés ; connexion SQL applicative à qualifier. |
| Développement local | Tests automatisés et parcours public/admin sur la version exacte ; maintenance et courriels vérifiés. | En cours : sept pages contrôlées localement sur mobile et ordinateur ; validation de la présentation par le responsable encore attendue. |
| Préproduction | Même candidat installé sous maintenance sur `preprod.tclongages.fr`, HTTPS valide, tests anonymes/admin et retour arrière éprouvé. | Bloquée : fichiers Drupal transférés sous fermeture Apache ; refus SQL 1045, synchronisation du mot de passe privé, installation et recette à terminer. |
| Mise en production | Après recette et accord explicite sur l'action, sauvegarde restaurable, livraison du candidat exact sous maintenance, contrôle, puis accord distinct pour l'ouverture Drupal. | Non exécutée. |

La première V1 comprend les sept pages publiques sous Drupal. La page « Espace » reste un écran d'attente ; aucun compte Bureau, formulaire Google, publication sociale ou collecte de contact n'est activé. Le contact public affiché est `tclongages@gmail.com` ; `support@tclongages.fr` reste prévu, sans boîte attestée.

Le [suivi HTML](http://127.0.0.1:4181/) présente les quatre phases, les revues et les observations réelles de déploiement. Ce guide décrit le parcours de livraison sans créer de nouvelle interface.

## Candidat de pages

`npm.cmd run drupal:public:build` génère d'abord la V1 de revue, puis ses sept pages destinées à Drupal dans `.local/drupal-public-candidate/site-pages/`, avec manifeste SHA-256 voisin. La transformation retire le bandeau « site en préparation » et la balise `noindex` de l'aperçu ; elle refuse une source inattendue. Elle ne copie rien sur l'hébergement. Les sept fichiers doivent être placés **hors de `drupal/web/`** dans `drupal/site-pages/` lors d'une future installation. Les dépendances viennent de `drupal/composer.lock`, jamais d'un dossier de recette privée copié tel quel.

`npm.cmd run drupal:package` construit `.local/tc-longages-drupal-v1-candidat.zip` depuis les fichiers Drupal suivis, les dépendances installées localement et ces pages. L'archive ne contient volontairement ni base SQL, ni comptes, ni secret, ni `web/sites/default/settings.php` actif ; elle n'est donc **pas installable telle quelle**. Son manifeste précise le commit source et signale les modifications non commitées. Vérifier ce manifeste et les prérequis hébergés avant de considérer un candidat exact pour la préproduction.

Le module `tcl_site` impose `noindex` à défaut. Le drapeau d'environnement `TCL_PUBLIC_INDEXING=1` ne peut retirer cet en-tête que pour une réponse 200 sur les neuf chemins de vitrine autorisés ; connexion, erreurs et maintenance restent sans indexation. Sur la préproduction, garder ce drapeau absent et vérifier aussi `robots.txt` et l'accès effectif. Sur la production, la levée d'indexation est une **décision d'ouverture distincte**, après validation de la cible. La simple préparation des pages ne change pas la maintenance Drupal.

Le [modèle hébergé](../drupal/config/settings.hosting.example.php) exige `TCL_ENVIRONMENT=preproduction` ou `production`, avec hôtes de confiance différents. Il est inactif par défaut et ne contient aucun secret. Chaque cible doit disposer de sa propre base, de ses propres répertoires privés et de sa propre configuration hors Git. Les valeurs et versions réelles restent à qualifier ; ce document ne constitue pas une procédure d'installation validée chez o2switch.

## Décisions sensibles à présenter avant exécution

| Décision | Effet et risque | Retour arrière à préparer |
|---|---|---|
| Créer `preprod.tclongages.fr`, son dossier, sa base, HTTPS et ses accès | Rend la préproduction accessible depuis Internet ; une mauvaise cible ou permission peut exposer des données. | Vérifier les cibles exactes et sauvegarder la configuration antérieure ; retirer le sous-domaine et les accès après inspection si nécessaire. |
| Installer le candidat en préproduction | Écrit sur l'hébergement ; une racine ou une base erronée peut remplacer l'existant. | Sauvegarde préalable vérifiée et procédure de restauration testée. |
| Livrer en production sous maintenance | Remplace le site courant à `tclongages.fr`, toujours fermé aux visiteurs pendant les vérifications. | Sauvegarde fraîche restaurable du code, de la base et des fichiers ; retour à l'état précédent documenté. |
| Ouvrir le site et son indexation | Rend les pages publiques visibles et éventuellement indexables. | Réactiver la maintenance Drupal et `noindex`, puis vérifier depuis une session anonyme. |

Avant l'une de ces opérations, présenter la cible, l'effet, le risque et le retour arrière au responsable, et attendre son accord explicite. La PR, les tests locaux ou la création du sous-domaine ne valent pas cet accord. Les secrets ne sont ni demandés ni consignés dans la conversation.
