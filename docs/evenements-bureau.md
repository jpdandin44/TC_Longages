---
project: TC_Longages
document_type: feature-guide
title: Gestion des événements de recette dans le Bureau
status: active
version: git
created: 2026-10-19
updated: 2026-10-22
owner: jpdandin
tags: [agenda, bureau, recurrence, preproduction]
---

## Participations — développement du 22 octobre

Depuis « Participants et réponses », choisir une date de la série, ouvrir les réponses et fixer leur date limite. Créer un lien avec un nom fictif et une adresse de recette. Le participant répond Oui/Non/Incertain ; Oui demande le total des personnes, lui compris. Le tableau distingue personnes confirmées, incertains, absents de réponse et réponses à reconfirmer. Aucun mail automatique depuis cet écran. [Règles et limites](../api/participations-courriels.md), [état de livraison](../data/event-participation-mail-verification.json).


# Événements du Bureau — recette V2

## Cadrage

Le responsable confirme le 19 octobre le maintien du calendrier Google de production en consultation et la séparation des écritures de recette. Le Bureau propose `/bureau/evenements`, sans synchronisation, publication publique ou envoi. Les données doivent être fictives. Le calendrier partagé et ses identifiants restent inchangés.

## Développement local

Création et modification : titre, lieu, description, début/fin en Europe/Paris, état Programmé ou Annulé. Répétitions quotidienne, hebdomadaire, toutes les deux semaines ou mensuelle au même jour du mois. Fin inclusive obligatoire, limitée à un an ; événement individuel limité à sept jours. Les mois sans le jour demandé sont ignorés. Les horaires inexistants ou ambigus lors du changement d’heure sont refusés ; la date concernée peut être exclue.

« Vérifier les dates » calcule les occurrences sans enregistrer. « Enregistrer l’événement » exige une confirmation des données fictives et de la portée de la modification. Modifier ou annuler agit sur toute la série, passé compris. Une occurrence peut être exclue par sa date ; pour la déplacer, l’exclure puis créer un événement ponctuel. Pas de modification automatique des événements suivants seulement.

Stockage isolé dans la collection Drupal `tcl_bureau.recipe_events`, cent séries maximum, sans nouvelle table ni migration. Identifiant aléatoire, révision, dates et auteur conservés. Verrou d’écriture et comparaison de révision empêchent les écrasements concurrents ; la répétition d’une création ne crée pas de doublon. Pas de suppression définitive : une annulation est réversible.

Routes et service exigent la session OAuth active, un compte actif et la permission existante `manage tcl communication`. Le mode recette et l’hôte local/préproduction sont également obligatoires. Aucun droit Capitaine, contournement administrateur ou ouverture en production. Les formulaires Drupal conservent leur protection CSRF et les textes affichés sont échappés.

Vérifications : `tests/event-schedule-local.php` (19 contrôles métier) et `tests/events-http.py` (29 contrôles HTTP avec OAuth fictif). Ces contrôles sont raccordés au workflow de recette Bureau.

### Catégories et affiches

Sept types de rendez-vous associent une couleur, une icône et un libellé : cours de tennis, manifestations, réservations, repas, assemblées générales, compétitions et autres rendez-vous. La couleur n’est jamais le seul repère. Les filtres permettent de consulter chaque catégorie. Les événements antérieurs sans catégorie utilisent « Autre rendez-vous ».

Le formulaire réutilise une affiche déjà importée dans Communication, y compris un brouillon privé. Les cartes montrent l’image ou la première page du PDF et un lien vers la préparation du partage. Une affiche archivée disparaît des cartes ; sa modification dans Communication se répercute ici. Cette association ne valide ni ne publie la communication. Sans affiche, une grande icône remplace la miniature.

Le sélecteur « Ambiance » est supprimé de la page Calendrier. À la demande du responsable le 20 octobre, chaque ouverture utilise désormais Mois pour tous les visiteurs, y compris ceux qui avaient mémorisé Semaine ou Liste. Le changement de vue reste possible pendant la visite, sans mémorisation. Les anciennes préférences locales sont ignorées. Ce complément est livré et vérifié en préproduction le 20 octobre. Les couleurs du calendrier Google intégré restent propres à Google : les nouvelles cartes colorées sont celles de l’agenda Bureau indépendant.

## Préproduction

Livrée et vérifiée le 20 octobre : paquet initial de treize fichiers puis correctif de deux fichiers. Sauvegardes restaurées et comparées avant chaque installation ; empreintes et conservation des contenus/protections vérifiées après. Recette Chrome : création avec PDF existant, aperçu puis enregistrement d’une série avec exclusion, modification/annulation, filtres et largeur mobile 390 px. Affiche liée et préparation du partage vérifiées sans publication. « Ambiance » absent ; le premier contrôle Mois/Liste avec persistance a été remplacé par le complément Mois systématique décrit plus haut. Accès non authentifié : 401.

Deux événements fictifs conservés pour la campagne humaine différée. [Preuves](../data/agenda-events-verification.json), [installation et retour](../workflows/agenda-events-preproduction/README.md).

## Mise en production

Exclue. Le raccordement des écritures à Google, ses droits et la migration éventuelle des événements ne sont pas réalisés. Le calendrier public reste une source distincte consultée seulement. Cette recette n’est pas un deuxième calendrier officiel.

## Qualification du 20 octobre

Le premier paquet de treize fichiers et son correctif ont été installés et leurs empreintes vérifiées. La recette hébergée a révélé deux défauts : script Ambiance conservé dans le cache du navigateur et service du formulaire non restauré après « Vérifier les dates ». Correction livrée et vérifiée : adresses des ressources liées à leur empreinte et propriété du service compatible avec la sérialisation Drupal. Le test HTTP enregistre le formulaire renvoyé par l’aperçu, sans rouvrir un formulaire neuf. Aucun calendrier Google ni donnée de production modifié.


## Lier une actualité aux inscriptions

En préproduction, publier l’actualité de recette, puis ouvrir l’événement dans Agenda de recette → Participants et réponses. Choisir la date, ouvrir les réponses, fixer la date limite et sélectionner « Actualité avec inscription ». Enregistrer l’ouverture. Une actualité doit correspondre à une seule occurrence ouverte.

L’accueil, l’article et l’aperçu courriel présentent alors le lien d’inscription. Le participant demande son lien personnel par email, puis répond depuis ce lien. En recette, seules les adresses autorisées reçoivent un message. La demande seule ne compte pas comme présence. La miniature reprend l’affiche déjà enregistrée dans la communication, y compris la première page d’un PDF.
