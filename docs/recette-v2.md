---
project: TC_Longages
document_type: development-guide
title: Reprise V2 pour la préproduction
status: active
version: git
created: 2026-10-18
updated: 2026-10-18
owner: jpdandin
tags: [v2, recette, saisons]
---

# Reprise V2 pour la préproduction

Le suivi canonique reste dans [competitions-capitaines](../../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json), itération `tcl-v2-recette-20261018`. Cette branche `feat/v2-recette` part de `e7e71addee734317ef2da5706b290767a3b317e8` (main avec PR22 et PR23 fusionnées). Son verrou Drupal porte Core 11.4.10 et Simple OAuth 6.1.1. Aucun état privé V3 ou hébergé n'est copié.

## Organisation retenue le 18 octobre

Production V1, préproduction V2 en cours de finalisation, local V3. Les corrections V2 ont leur propre checkout local pour les contrôles avant recette hébergée. V3 Compétitions reste dans `competitions-capitaines`, avec son identifiant historique inchangé. La production est exclue des écritures de cette intervention.

## Quatre phases

| Phase | État et suite |
| --- | --- |
| Cadrage | Découpage confirmé ; inventaire saisonnier ci-dessous. Composants hébergés identifiés par cPanel : Core 11.4.8, Simple OAuth 6.1.1 ; commit complet non déterminé. |
| Développement local | Branche V2 isolée sur le socle corrigé ; bandeau de dernière actualité vérifié localement. Formulaire public et administration saisonnière restent à développer. |
| Préproduction | Recette demandée sur la cible existante. Sauvegarde fraîche et restauration isolée, candidat exact et protections à vérifier avant remplacement. |
| Mise en production | Hors périmètre ; aucune écriture, fusion ou ouverture autorisée par ce lot. |

## Inventaire hébergé du 18 octobre

La racine du domaine préproduction est confirmée par cPanel. Le verrou Composer, les paquets installés et le code Core concordent : Drupal 11.4.8 et Simple OAuth 6.1.1. Les empreintes échantillonnées du Bureau correspondent au socle historique ; Flash News est absent. Le relevé canonique est [la vérification des environnements](../../competitions-capitaines/data/versions-environnements-verification.json). Aucun fichier hébergé ni contenu privé n’a été modifié ou importé. L’absence de dépôt Git hébergé et de manifeste complet empêche d’affirmer un commit déployé exact.

Les six contrôles techniques GitHub du candidat `92cfec3` sont réussis ; le contrôle policy échoue uniquement sur les quatre attestations humaines laissées non cochées. Ce résultat ne vaut ni recette hébergée ni autorisation de merge. Le passage de Core 11.4.8 à 11.4.10 doit conserver les contenus, comptes et réglages OAuth après sauvegarde et restauration isolée qualifiées.

## Bandeau d'actualité

`CommunicationController::front()` réutilise `CommunicationRepository::published()` et affiche la dernière publication en tête du contenu principal : titre, extrait texte de 220 caractères maximum et liens vers le détail et toutes les actualités. Le bandeau est statique, sans image obligatoire ni défilement. Il disparaît sans publication ; retrait, archive ou nouvelle révision non validée font revenir à la publication précédente. Les cinq titres en bas de page sont conservés.

Le libellé « Actualités du club » et l'extrait du texte sont les choix de réalisation pour revue. Aucune date d'expiration n'est inventée : les événements suivent le retrait explicite existant. Une expiration automatique reste un arbitrage de recette, non implémenté. Aucune nouvelle saisie ni migration n'est nécessaire pour ce bandeau.

## Inventaire saisonnier constaté

| Contenu | Source actuelle | Travail V2 restant |
| --- | --- | --- |
| Saison et formules du dossier | Configuration `tcl_bureau.settings`, `RegistrationForm`, JSON privé du dossier | Catalogue publié commun et copie immuable du tarif au dépôt ; ne pas recalculer les dossiers anciens. |
| Tableau public des tarifs | `scripts/tariffs.mjs`, transcription historique `data/tarifs-inscription.json`, correctif ciblé V1 distinct | Migration contrôlée vers un catalogue Drupal éditable au Bureau ; préserver le relevé PDF historique. |
| Textes et rubriques des sept pages | Entités Drupal éditoriales via `PublicPageEditor` | Inventorier les valeurs hébergées et raccordements Bureau ; préserver leurs révisions. |
| Contacts, liens et paramètres publics | `config/officiel.json` et rubriques éditoriales | Séparer données publiables et réglages techniques ; aucune exposition de secrets. |
| Actualités et événements | `tcl_bureau_post`, cycle explicite validation/publication | Bandeau réalisé ; échéance à préciser. |
| Cours, disponibilités et autorisations | Proposition `processus-inscriptions.md` | Plages, textes applicables, information de collecte et durée de conservation encore non validés. |
| Documents et tarifs des saisons passées | PDF historiques et exports | Les conserver datés ; distinguer des tarifs courants, sans réécrire les pièces tierces. |

## Critères de recette

- Vérifier le bandeau après création, validation seule, publication, modification, retrait et archive ; aucun brouillon exposé, liens utilisables et accents intacts.
- Vérifier les droits OAuth Admin/Bureau/Capitaine et l'accès direct ; la nouvelle interface ne contourne aucun service privé.
- Prévoir adulte/mineur, doublons, double clic, concurrence et conservation des anciens tarifs lors du développement du formulaire.
- Vérifier le rendu à 390 et 1280 pixels sur le candidat, puis sur la cible hébergée.

Les tests, captures, empreintes et limites sont consignés dans `data/v2-recette-verification.json` une fois exécutés. Les résultats locaux ne qualifient pas la préproduction.

## Reproduire les contrôles locaux

Sur une nouvelle copie uniquement : construire les pages avec `node scripts/build-officiel.mjs`, préparer puis installer avec `scripts/drupal-local.ps1 prepare` et `scripts/drupal-local.ps1 install`. Utiliser ensuite `drupal/bin/prepare-bureau-oauth-local.php` avec le PHP et le fichier `.local/drupal-tools/php.ini` du projet. L'ancien helper `prepare-v2-local.php` exige un accès natif qui ne satisfait plus la garde OAuth ; ne pas affaiblir cette garde pour le faire passer.

Lancer le serveur Drupal en boucle locale au port 4186 et le fournisseur fictif `tests/oauth-fixture-server.mjs` au port 4190, après vérification que ces ports sont libres. Exécuter successivement (jamais en parallèle, car le scénario OAuth modifie son fournisseur fictif) :

- `python -X utf8 tests/drupal-bureau-oauth-http.py` : 66 contrôles ;
- `python -X utf8 tests/flash-news-http.py` : 28 contrôles ;
- `php -c .local/drupal-tools/php.ini tests/flash-news-local.php` : 15 contrôles avec transaction annulée.

Le test du Bureau accepte maintenant le préfixe `/fr` des routes localisées. La recette HTTP du bandeau archive ses exemples ; l'exemple visuel reste une donnée fictive locale. Aucun de ces helpers n'est destiné à l'hébergement.

La recette CI existante `scripts/bureau-oauth-recette.ps1` exécute également les deux nouveaux tests du bandeau avant de changer de fournisseur OAuth. Aucun workflow de livraison activé.

## Libellé tarifaire V2

La génération des pages V2 affiche « Cours Adultes compétition » à 170 €. Le correctif ponctuel V1, son reçu et les PDF historiques sont conservés. Aucun changement de montant ni de règle de cumul ; l’administration saisonnière et le formulaire public restent à réaliser.
