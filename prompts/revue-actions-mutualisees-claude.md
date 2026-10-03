---
project: TC_Longages
document_type: ai-prompt
title: Revue ciblée des Actions o2switch partagées avec AVEREO
status: active
version: git
created: 2026-10-01
updated: 2026-10-01
owner: jpdandin
tags: [claude, github-actions, mutualisation, cout]
---

# Revue des Actions o2switch

## Objectif

Examiner le périmètre minimal de réutilisation des Actions du site Drupal
AVEREO pour TC Longages, sans accès serveur ou modification externe.

## Contexte et entrées

Drupal TC 11.4.8 ; projet Composer `drupal/`, document root `drupal/web`,
`vendor/` et `site-pages/` hors document root, sept pages et module `tcl_site`.
Préproduction, PHP Apache ≥ 8.3, TLS, base, racine cPanel et restauration de
sauvegarde ne sont pas encore qualifiés. La chaîne AVEREO est manuelle sur
`main`, liée au SHA examiné ; SSH strict, attente bornée de l'exception IPv4
cPanel, outils privés, sauvegarde/restauration avant livraison en maintenance.
Ses comptes, chemins, bases et contenus sont spécifiques à AVEREO.

## Instructions

Revue courte, sans exécution, outil externe ou accès aux fichiers. Répondre
en français, maximum 500 mots. Examiner :

1. Périmètre minimal de mutualisation compatible avec un nouveau site.
2. Risques précis et tests utiles : injection, archives, provenance SHA,
   sauvegarde et retour arrière.
3. Source partagée unique sans modifier le site AVEREO actif ou hériter de ses secrets.

Proposition soumise : extraire contrôles génériques d'archives et SSH strict ;
garder les adaptateurs Drupal distincts. Préparation TC sans accès par défaut,
qualification distante seulement sur cible et accord explicites. Refuser tout
profil inconnu et premier hébergement sans sauvegarde initiale qualifiée.
Conserver la maintenance jusqu'à une ouverture explicitement autorisée.

## Contraintes et sources autorisées

Description technique ci-dessus uniquement. Aucun secret, chemin personnel,
fichier privé ou donnée d'adhérent. Aucun accord d'écriture serveur, merge,
secret, DNS ou publication n'est donné par la revue. Sonnet en effort moyen,
un seul échange, sans recours à un modèle plus coûteux.

## Format de sortie et tests

Réponse courte distinguant composants partageables, adaptateurs spécifiques,
tests et prérequis ouverts. Les recommandations doivent être confrontées au
code et aux contrôles locaux. Une proposition ne vaut pas résultat de test.

## Historique des versions

Git fait autorité. Utilisé le 1er octobre dans « Revue technique Drupal TC
Longages », Sonnet 5.5 Moyen. Cette source reprend les instructions du message
envoyé ; la conversation contient sa formulation en prose. Aucun second
échange n'a été demandé.
