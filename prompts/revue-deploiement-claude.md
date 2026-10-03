---
project: TC_Longages
document_type: reusable-prompt
title: Revue ciblée du déploiement Drupal avec Claude
status: active
version: git
created: 2026-09-30
updated: 2026-09-30
owner: jpdandin
tags: [prompt, claude, drupal, deploiement]
---

# Revue ciblée du déploiement avec Claude

## Objectif

Obtenir en un échange une revue des obstacles et une prochaine intervention bornée pour qualifier la préproduction de la V1 publique. Le résultat est un avis à vérifier, jamais une autorisation de déploiement.

## Contexte et entrées

Les états évolutifs font autorité dans le [parcours](../data/parcours-mise-en-ligne.json), le [point de session](../docs/point-session.md) et le [guide Drupal](../docs/installation-drupal.md). Le [modèle hébergé](../drupal/config/settings.hosting.example.php) reste inactif. Fournir à Claude uniquement un résumé technique utile, sans secrets, identifiants de compte, données d'adhérents, historique personnel ni chemins du poste.

## Instructions à transmettre

Tu aides à reprendre le déploiement du site du TC Longages. Travaille uniquement à partir des faits suivants et des documentations officielles citées ; ne lance aucune commande, n'utilise aucun terminal, ne modifie ni compte ni fichier et ne déploie rien. Réponds en français, en un seul résultat de 800 mots maximum. Le but est d'économiser les échanges tout en préparant une opération vérifiable.

La première V1 comporte sept pages publiques servies par un module Drupal dédié. Espace Bureau, Google, contact réel et publications sociales restent inactifs. Les pages sont hors webroot ; maintenance native Drupal et noindex sont actifs par défaut. Les sources Drupal et leurs dépendances sont verrouillées par composer.lock. Le ZIP candidat est préparé localement, mais exclut volontairement settings.php actif, base SQL, secrets et comptes ; le ZIP seul n'installe donc pas un site. Le modèle settings.hosting.example.php exige TCL_HOSTING_ENABLE=1, TCL_ENVIRONMENT=preproduction ou production, paramètres SQL, sel et trois répertoires privés existants hors webroot. En préproduction, il interdit TCL_PUBLIC_INDEXING=1. La façon de fournir les variables privées sur l'hébergement reste à qualifier. Le contrôleur lit sept fichiers HTML prédéfinis dans drupal/site-pages ; les routes GET/HEAD sont /club et les sept noms .html ; l'accueil doit être configuré sur /club.

Les constats d'hébergement datent du 29 septembre 2026 et doivent être relus : tclongages.fr sur public_html ; PHP 8.1 global, sélecteur sans isolation par domaine ; MariaDB 11.4.13 indiquée ; aucune base dédiée ; certificat autosigné ; JetBackup visible sans restauration testée ; preprod.tclongages.fr absent. Les sous-comptes « lunes » sont indisponibles selon le responsable, sans quota techniquement confirmé. La piste actuelle est une préproduction dans un dossier isolé du compte existant, avec PHP compatible limité à ce dossier. Ne propose aucune bascule du PHP global ni migration de la production dans cette étape.

Le guide officiel o2switch https://faq.o2switch.fr/guides/php/changer-version-php-et-php-ini/ décrit PHP par dossier, dont PHP 8.3, et avertit qu'un handler inconnu peut servir le PHP en clair. Un essai doit donc précéder tout dépôt de code ou secret. Référence Drupal : https://www.drupal.org/docs/getting-started/system-requirements/php-requirements . La version réellement verrouillée dans le dépôt et ses contraintes Composer doivent primer sur une généralité.

Fournis : 1) les obstacles prioritaires et leur preuve de sortie ; 2) le contenu minimal d'une sonde PHP sans phpinfo, secret ou donnée personnelle, couvrant version, SAPI et extensions requises ; 3) la première opération distante proposée avec cible, effet, risque, fermeture et retour arrière ; 4) les vérifications manquantes pour transformer le candidat de code en installation sous maintenance. Distingue faits, hypothèses et contrôles non exécutés. Toute écriture distante, création de domaine/base/accès/certificat et ouverture doit rester sous le contrôle du responsable ; un avis Claude ne vaut aucun accord humain. N'ajoute pas de nouveau suivi projet ni de fonctionnalité hors V1.

## Contraintes et sources autorisées

Résumé technique ci-dessus, sources publiques officielles o2switch et Drupal. Aucun fichier privé ni accès d'hébergement n'est transmis. Respecter les [consignes locales](../AGENTS.md), notamment les actes humains distincts. Les résultats de Claude doivent être confrontés au code et aux contraintes verrouillées avant adoption.

## Format de sortie et tests

Avis concis et prochaine opération concrète. Vérifier les contraintes de la version Drupal verrouillée, le modèle hébergé, les routes et tout fichier proposé. Une vérification locale ne qualifie jamais PHP, TLS ou restauration distants.

## Historique des versions

Création le 30 septembre 2026 à la demande de reprise avec le connecteur Claude. Versionnement par Git, sans numéro dans le nom de fichier.
