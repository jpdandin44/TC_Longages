---
project: TC_Longages
document_type: development-guide
title: Reprise V2 pour la préproduction
status: active
version: git
created: 2026-10-18
updated: 2026-10-21
owner: jpdandin
tags: [v2, recette, saisons]
---

# Reprise V2 pour la préproduction

## Campagne de recette du 21 octobre

[Parcours, résultats attendus et grille à compléter](campagne-recette-v2.md). Campagne partiellement exécutée ; les résultats et réserves actuels sont dans la grille. Deux notifications reçues selon le responsable. Réinitialisation native V2-MAIL-01 en cours de correction ; voir [le workflow borné](../workflows/reinitialisation-recette.md). Les sections datées ci-dessous conservent les observations de leur lot ; les ambiances et préférences décrites pour le 19 octobre sont remplacées par le comportement du guide agenda courant.

## Agenda V2 — état du 20 octobre

L’agenda Bureau avec miniatures, catégories, icônes et récurrences est livré et vérifié en préproduction. Ambiance est supprimé. Les défauts de cache navigateur et de sauvegarde après aperçu sont corrigés et revérifiés. [Guide courant](evenements-bureau.md), [preuves](../data/agenda-events-verification.json), [reprise](point-session.md). Les constats du 19 octobre ci-dessous restent historiques.


## Complément courant — communications et agenda

- **Cadrage** : communications en priorité, agenda ensuite ; campagne humaine différée, production exclue.
- **Développement local** : copie du message validé, présentation des supports, vues/palettes de l’agenda et cartes d’événements publiés ; contrôles locaux réussis.
- **Préproduction** : neuf fichiers installés et vérifiés, permissions des ressources publiques corrigées ; copie, aperçu PDF et préférences agenda contrôlés dans Chrome. Campagne humaine toujours différée.
- **Mise en production** : non autorisée.

[Périmètre et réserves](communication-bureau.md).


Le suivi canonique reste dans [competitions-capitaines](../../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json), itération `tcl-v2-recette-20261018`. Cette branche `feat/v2-recette` part de `e7e71addee734317ef2da5706b290767a3b317e8` (main avec PR22 et PR23 fusionnées). Son verrou Drupal porte Core 11.4.10 et Simple OAuth 6.1.1. Aucun état privé V3 ou hébergé n'est copié.

## Organisation retenue le 18 octobre

Production V1, préproduction V2 en cours de finalisation, local V3. Les corrections V2 ont leur propre checkout local pour les contrôles avant recette hébergée. V3 Compétitions reste dans `competitions-capitaines`, avec son identifiant historique inchangé. La production est exclue des écritures de cette intervention.

## Quatre phases

| Phase | État et suite |
| --- | --- |
| Cadrage | Découpage confirmé ; inventaire saisonnier ci-dessous. Composants hébergés identifiés par cPanel : Core 11.4.8, Simple OAuth 6.1.1 ; commit complet non déterminé. |
| Développement local | Branche V2 isolée sur le socle corrigé ; bandeau de dernière actualité vérifié localement. Catalogue et formulaire fictif implémentés : 38 contrôles HTTP et 9 contrôles de catalogue réussis. Qualification hébergée encore attendue. |
| Préproduction | Catalogue fictif publié ; notification du dossier 3 reçue et invitation confirmée reçue par le responsable. Lien depuis l’accueil corrigé et parcours jusqu’au formulaire vérifié dans Chrome. Création du mot de passe et connexion du compte invité restent à vérifier. |
| Mise en production | Hors périmètre ; aucune écriture, fusion ou ouverture autorisée par ce lot. |

## Inventaire avant livraison du 18 octobre

La racine du domaine préproduction est confirmée par cPanel. Le verrou Composer, les paquets installés et le code Core concordent : Drupal 11.4.8 et Simple OAuth 6.1.1. Les empreintes échantillonnées du Bureau correspondent au socle historique ; Flash News est absent. Le relevé canonique est [la vérification des environnements](../../competitions-capitaines/data/versions-environnements-verification.json). Ce relevé initial précède la livraison décrite ci-dessous. L’absence de dépôt Git hébergé et de manifeste complet empêche d’affirmer un commit déployé exact.

Les six contrôles techniques GitHub du candidat livré `e7ec6af` sont réussis ; le contrôle policy échoue uniquement sur les quatre attestations humaines laissées non cochées. Ce résultat ne vaut ni recette hébergée ni autorisation de merge. Le paquet limité conserve Core 11.4.8 ; sa compatibilité a été testée sur la copie restaurée. Le passage à 11.4.10 reste un lot distinct à qualifier.

## Bandeau d'actualité

`CommunicationController::front()` réutilise `CommunicationRepository::published()` et affiche la dernière publication en tête du contenu principal : titre, extrait texte de 220 caractères maximum et liens vers le détail et toutes les actualités. Le bandeau est statique, sans image obligatoire ni défilement. Il disparaît sans publication ; retrait, archive ou nouvelle révision non validée font revenir à la publication précédente. Les cinq titres en bas de page sont conservés.

Le libellé « Actualités du club » et l'extrait du texte sont les choix de réalisation pour revue. Aucune date d'expiration n'est inventée : les événements suivent le retrait explicite existant. Une expiration automatique reste un arbitrage de recette, non implémenté. Aucune nouvelle saisie ni migration n'est nécessaire pour ce bandeau.

## Inventaire saisonnier constaté

| Contenu | Source actuelle | État V2 et suite |
| --- | --- | --- |
| Saison et formules du dossier | Catalogue privé `tcl_bureau.seasons`, instantané dans le JSON du dossier | Implémenté et testé localement ; installation hébergée à qualifier. |
| Tableau public des tarifs | `scripts/tariffs.mjs`, transcription historique `data/tarifs-inscription.json`, correctif ciblé V1 distinct | Catalogue éditable au Bureau raccordé au tableau et au formulaire en mode recette ; PDF historique préservé. |
| Textes et rubriques des sept pages | Entités Drupal éditoriales via `PublicPageEditor` | Inventorier les valeurs hébergées et raccordements Bureau ; préserver leurs révisions. |
| Contacts, liens et paramètres publics | `config/officiel.json` et rubriques éditoriales | Séparer données publiables et réglages techniques ; aucune exposition de secrets. |
| Actualités et événements | `tcl_bureau_post`, cycle explicite validation/publication | Bandeau réalisé ; échéance à préciser. |
| Cours, disponibilités et autorisations | Proposition `processus-inscriptions.md` | Plages, textes applicables, information de collecte et durée de conservation encore non validés. |
| Documents et tarifs des saisons passées | PDF historiques et exports | Les conserver datés ; distinguer des tarifs courants, sans réécrire les pièces tierces. |

## Critères de recette

- Vérifier le bandeau après création, validation seule, publication, modification, retrait et archive ; aucun brouillon exposé, liens utilisables et accents intacts.
- Vérifier les droits OAuth Admin/Bureau/Capitaine et l'accès direct ; la nouvelle interface ne contourne aucun service privé.
- Rejouer en préproduction les contrôles adulte/mineur, doublons, double clic, concurrence et conservation des anciens tarifs déjà réussis localement.
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

La génération des pages V2 affiche « Cours Adultes compétition » à 170 €. Le correctif ponctuel V1, son reçu et les PDF historiques sont conservés. Aucun changement de montant ni de règle de cumul ; le catalogue et le formulaire fictif sont désormais implémentés ; leur qualification hébergée reste à réaliser.

## Catalogue et formulaire — poursuite du 18 octobre

Le [contrat de recette](../api/inscription-recette.md) décrit les nouvelles routes, la source saisonnière, les instantanés, les garde-fous et les limites. Les fiches récentes ont été examinées pour leurs champs uniquement ; aucune donnée réelle importée. La demande autorise de compléter textes et créneaux ensuite.

Contrôles complémentaires : `python -X utf8 tests/registration-recipe-http.py` (38), `php -c .local/drupal-tools/php.ini tests/season-catalogue-local.php` (9), `node scripts/build-season-seed.mjs --check`. Régression Node : 174 réussis, un test PHP initialement ignoré puis les quatre contrôles du fichier concerné réussis avec le bon fichier PHP ini. Formulaire et récapitulatif inspectés à 390 et 761 pixels effectifs, sans débordement ; 1280 pixels reste à confirmer pour ce formulaire.

## Livraison de préproduction vérifiée

Le paquet limité V2 est installé le 18 octobre à 22 h 47 (Paris) : catalogue, formulaire fictif et bandeau, 38 fichiers vérifiés (12 différents). Core hébergé 11.4.8 et OAuth 6.1.1 conservés ; local 11.4.10. Sauvegarde/restauration privée vérifiée (27 449 fichiers, 62 tables, démarrage Drupal), puis 11 contrôles sur cette copie réussis. Les sept pages, trois comptes et le dossier existant sont conservés. Basic répond 401, aucune production ni collecte réelle modifiée. Catalogue encore non publié : connexion Bureau/OAuth, publication de recette puis parcours navigateur à réaliser. [Reçu de livraison](../data/v2-preproduction-livraison.json).

Prochaine action : franchir l’accès préproduction puis OAuth sur `/bureau`, ouvrir `/bureau/saison`, publier explicitement le catalogue pour recette, tester `/adherer` avec des identités `Recette…` et des courriels `@example.invalid`. Aucun texte ni créneau définitif exigé pour ces tests. L’accès cPanel ne constitue pas une connexion au Bureau.
