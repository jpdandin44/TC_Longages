---
project: TC_Longages
document_type: ai-prompt
title: Revue de première installation Drupal sur la lune TC
status: active
version: git
created: 2026-10-04
updated: 2026-10-04
owner: jpdandin
tags: [claude, drupal, installation, o2switch]
---

# Revue de première installation Drupal

## Objectif

Revoir le chemin de première installation encore manquant pour aboutir à la
mise en production du site du TC Longages après recette en préproduction.

## Contexte et entrées

Drupal 11.4.8, projet Composer `drupal/`, document root `drupal/web`,
`vendor/` et sept pages dérivées `site-pages/` hors racine publique, module
`tcl_site`. L’Action de construction mutualise les contrôles génériques
d’AVEREO sans ses comptes ni secrets. Un ZIP non configuré de 26 687 fichiers
est reçu et vérifié intégralement ; il doit être promu sans reconstruction
présentée comme identique. L’adaptateur de première installation manque.

La lune isolée est active ; PHP 8.3 est sélectionné dans cPanel, dossier
Composer créé et `.htaccess` fermé relu. Base vide en `utf8mb4_unicode_ci`,
MariaDB 11.4.13 observé, utilisateur dédié créé personnellement et dix droits
accordés : ALTER, CREATE, CREATE TEMPORARY TABLES, DELETE, DROP, INDEX, INSERT,
LOCK TABLES, SELECT, UPDATE. Runtime HTTP et connexion SQL non testés.

cPanel refuse `preprod.tclongages.fr` sur la lune car le domaine parent
appartient au compte principal. Une adresse temporaire sous le domaine
technique de la lune est proposée, sans accord ni création. Aucun domaine
officiel déplacé, DNS, certificat, SSH ou Drupal distant installé. Sauvegarde
du compte restaurée en copie privée, distincte du retour arrière Drupal.

## Instructions

Revue technique ciblée en français, au plus 600 mots, un seul échange,
Sonnet en effort moyen. Aucun outil, accès aux fichiers ou changement externe.

1. Évaluer le choix de cible temporaire et les prérequis DNS/HTTPS sans
   déplacer le domaine officiel ni contourner un certificat non reconnu.
2. Proposer le chemin concret de première installation du même ZIP :
   extraction contrôlée hors webroot, vérification des empreintes et de la
   cible vide, configuration privée fournie personnellement, installation
   Drupal et activation de `tcl_site`, maintenance conservée, reçu et retour
   arrière. Distinguer terminal cPanel et SSH futur ; ne présumer aucun accès.
3. Relever les pièges de la fermeture Apache pendant une recette web ou une
   émission ACME, et les tests indispensables avant ouverture.
4. Indiquer les trois défauts les plus probables d’un adaptateur Python/Linux
   de première installation et les contrôles utiles pour les éviter.

## Contraintes et sources autorisées

Cette description publique uniquement. Aucun mot de passe, clé, session,
chemin de compte, donnée d’adhérent ou accès AVEREO. Une recommandation ne
constitue ni résultat de test ni accord de merge, transfert, DNS ou ouverture.
Ne proposer aucune simplification du site en remplacement du Drupal demandé.

## Format de sortie

Ordre des opérations, limites des deux moyens d’exécution, risques et tests.
Distinguer les faits fournis des hypothèses qui restent à vérifier.

## Tests

Confronter la réponse au code, à la cible réelle et aux contrôles locaux avant
intégration. Conserver le texte et le périmètre de la revue dans le reçu.

## Historique des versions

Git fait autorité. Revue reçue le 4 octobre 2026 dans « Drupal installation
review », avec Sonnet 5.5 et effort Moyen, un seul échange. Les captures sont
privées ; confrontation et choix retenus dans le [guide d'installation](../docs/installation-drupal.md).
Les recommandations ne valent ni essai hébergé ni accord d'écriture.
