---
project: TC_Longages
document_type: correction
title: Agenda public et événements du Bureau
status: active
version: git
created: 2026-11-18
updated: 2026-11-18
owner: jpdandin
tags: [agenda, production, correction]
---

# Agenda public et événements du Bureau

## Cadrage

Après PR33, le Bureau affiche la collection locale des événements du club. Le visiteur voit seulement un iframe Google distinct. La production n’a pas de cible Google Calendar connectée : l’absence du Beaujolais dans cet iframe n’est donc pas une suppression de données. La demande du responsable autorise une correction en production sous maintenance, sans courriel.

## Développement local

`PublicAgenda` compose une vue mensuelle en lecture seule à partir des événements liés par `poster_post` à une communication actuellement publiée sur le site et approuvée à la révision courante. Le filtrage réutilise `CommunicationRepository::published()`. Aucun droit Bureau n’est accordé aux visiteurs. Seuls titre public, horaires, catégorie, annulation et lien vers l’actualité sont rendus ; notes internes, lieux privés, contacts et réponses restent absents. Une communication retirée ne fournit plus de rendez-vous public. Les récurrences et exclusions utilisent `EventSchedule`.

L’agenda Google existant est conservé sous la grille, nommé comme agenda complémentaire. Aucune écriture Google, modification d’événement ni émission de courriel. Les utilisateurs Bureau conservent leur grille de gestion. Le rendu public reste sans cache partagé comme les pages dynamiques existantes.

Tests : `php -c CHEMIN_PHP_INI tests/public-agenda.php CHEMIN_VENDOR_AUTOLOAD` ; 12 contrôles ciblés passés localement et sur le serveur avec données synthétiques, sans base. Syntaxe PHP contrôlée pour les deux fichiers modifiés.

## Préproduction

Cette correction n’est pas déclarée livrée ni validée en préproduction. Elle est isolée sur la branche `fix/public-club-agenda`, issue de main `083d89e`, pour réintégration Git et report ultérieur. Le diagnostic Tiger Protect reste distinct.

## Mise en production

Installation ciblée de `PublicAgenda.php` et `AgendaPresentation.php` après maintenance, sauvegarde privée de l’ancien fichier dans `tcl-production/private/calendar-public-20261118/rollback`. Aucune migration de données. Retour arrière : restaurer l’ancien AgendaPresentation, retirer PublicAgenda ajouté (le conserver en archive privée), reconstruire les caches. Les preuves et la réouverture sont consignées dans le suivi canonique `../competitions-capitaines/data/public-agenda-verification.json`.

Le Beaujolais est actuellement enregistré au Bureau le 20 novembre de 20:30 à 22:00. Ces horaires ont été lus et conservés, non corrigés à partir d’anciennes instructions.
