---
project: TC_Longages
document_type: feature-guide
title: Gestion des événements de recette dans le Bureau
status: active
version: git
created: 2026-10-19
updated: 2026-10-19
owner: jpdandin
tags: [agenda, bureau, recurrence, preproduction]
---

# Événements du Bureau — recette V2

## Cadrage

Le responsable confirme le 19 octobre le maintien du calendrier Google de production en consultation et la séparation des écritures de recette. Le Bureau propose `/bureau/evenements`, sans synchronisation, publication publique ou envoi. Les données doivent être fictives. Le calendrier partagé et ses identifiants restent inchangés.

## Développement local

Création et modification : titre, lieu, description, début/fin en Europe/Paris, état Programmé ou Annulé. Répétitions quotidienne, hebdomadaire, toutes les deux semaines ou mensuelle au même jour du mois. Fin inclusive obligatoire, limitée à un an ; événement individuel limité à sept jours. Les mois sans le jour demandé sont ignorés. Les horaires inexistants ou ambigus lors du changement d’heure sont refusés ; la date concernée peut être exclue.

« Vérifier les dates » calcule les occurrences sans enregistrer. « Enregistrer l’événement » exige une confirmation des données fictives et de la portée de la modification. Modifier ou annuler agit sur toute la série, passé compris. Une occurrence peut être exclue par sa date ; pour la déplacer, l’exclure puis créer un événement ponctuel. Pas de modification automatique des événements suivants seulement.

Stockage isolé dans la collection Drupal `tcl_bureau.recipe_events`, cent séries maximum, sans nouvelle table ni migration. Identifiant aléatoire, révision, dates et auteur conservés. Verrou d’écriture et comparaison de révision empêchent les écrasements concurrents ; la répétition d’une création ne crée pas de doublon. Pas de suppression définitive : une annulation est réversible.

Routes et service exigent la session OAuth active, un compte actif et la permission existante `manage tcl communication`. Le mode recette et l’hôte local/préproduction sont également obligatoires. Aucun droit Capitaine, contournement administrateur ou ouverture en production. Les formulaires Drupal conservent leur protection CSRF et les textes affichés sont échappés.

Vérifications : `tests/event-schedule-local.php` (19 contrôles métier) et `tests/events-http.py` (21 contrôles HTTP avec OAuth fictif). Ces contrôles sont raccordés au workflow de recette Bureau.

## Préproduction

Préparation en cours. Aucune livraison de ce lot attestée à ce stade. Candidat exact, CI et restauration des fichiers concernés à qualifier avant installation. Les contrôles locaux ne valent pas recette hébergée.

## Mise en production

Exclue. Le raccordement des écritures à Google, ses droits et la migration éventuelle des événements ne sont pas réalisés. Le calendrier public reste une source distincte consultée seulement. Cette recette n’est pas un deuxième calendrier officiel.
