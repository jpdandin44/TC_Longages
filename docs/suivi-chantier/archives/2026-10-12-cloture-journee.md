---
project: TC_Longages
document_type: session-archive
title: Clôture de session et reprise du Bureau TC
status: active
version: git
created: 2026-10-12
updated: 2026-10-12
owner: jpdandin
tags: [session, reprise, bureau, oauth, preproduction]
---

# Clôture — 12 octobre 2026

Le responsable demande de clore la session sans attendre de finaliser le mot
de passe. Cette clôture arrête les mutations applicatives ; elle ne valide ni
phase, ni recette complète, ni production. Aucune reprise automatique.

## État réel conservé

- PR #19 fusionnée ; merge `8bd9b8f9c8625a5811e35eab4a8d43da7c4a1306`.
- Même paquet natif `702d4e5b697dcae2b388324e0011ad06ef0a30858d8732fea667d332cd9d7431`,
  source `5fcd2a63e42573b2e8e31c4e1086dff4ba63557d`, 50 fichiers comparés au merge.
- Préproduction protégée uniquement : Drupal 11.4.8, Simple OAuth 6.1.1,
  trois modules TC installés ; aucun changement du cœur ou de production.
- Sauvegarde `20261012T202257Z` restaurée sur copie privée : fichiers, 50
  schémas et toutes les lignes actives comparés, récupérations antérieures gardées.
- Admin_TC_Longages, uid 2, rôle métier tcl_admin ; clés/client/association TC
  privés vérifiés, aucun compte AVEREO requis. Mot de passe encore vide au
  contrôle natif de 21:40 UTC, aucune saisie de mot de passe par l'agent.
- Contenus/révisions des sept pages, calendrier Mois, photo, contact et support
  conservés. Répertoire Drupal de configuration revenu à 0555.
- HTTP serveur : racine 401, token invalide 400, userinfo sans Bearer 403.
  Cinq sondes extérieures restent en 401 Basic. Aucun envoi réel de l'agent.

## Blocages précis

Le fichier d'initialisation remis a forcé `destination=/user/2/edit`. Cette
redirection fait perdre le `pass-reset-token` ajouté par UserController et
requis par AccountForm pour masquer le mot de passe actuel. Le responsable
voit ce champ et la validation échoue. La correction du générateur est locale,
non réinstallée ; l'ancien fichier téléchargé ne doit pas être réutilisé.

L'adresse publique du club est déjà utilisée par un autre compte Drupal.
Le nouveau compte doit recevoir une adresse distincte choisie par le responsable ;
aucun compte, adresse ou mot de passe existant n'est modifié par l'agent.
La connexion OAuth réelle et la recette métier hébergée restent non qualifiées.

## Reprise exacte

1. Ouvrir `.worktrees/bureau-oauth`, branche `feat/bureau-preproduction`, et lire
   le [point de session](../../point-session.md) ainsi que le
   [suivi canonique](../suivi-chantier.json), itération `tcl-v2-bureau-oauth`.
2. Recontrôler Git et la cible active, uid 2/absence de mot de passe, modules,
   configuration et conservation V1. Réutiliser les accords déjà reçus pour
   ce même compte/cible/effet ; ne pas recréer les clés ou le client.
3. Tester avec un compte fictif le lien natif sans `destination`, conservant
   la redirection Drupal et son token de formulaire. Réinstaller la correction
   de l'[opérateur](../../../workflows/bureau-preproduction.php) et régénérer
   seulement le lien privé d'initialisation du compte existant.
4. Remettre ce nouveau fichier au responsable. Il choisit l'adresse distincte,
   saisit/confirme/enregistre son mot de passe lui-même, sans secret au chat.
5. Qualifier la vraie connexion Menu → Bureau et les opérations métier, puis
   mettre à jour le [reçu](../../../data/bureau-oauth-verification.json).
   Toute production conserve son accord et sa qualification propres.

## Sources et sauvegarde locale

Le [workflow](../../../workflows/bureau-oauth-preproduction.md) et le reçu
conservent les opérations, contrôles, erreurs résolues et réserve actuelle.
Le code applicatif fusionné reste inchangé ; opérateurs, corrections locales et
documents de cette session sont sur la branche de préparation. La correction
de première saisie n'est pas une validation de sa recette.

Une archive locale de reprise des sources/documentation, hors identifiants,
clés, téléchargements et répertoires `.local`, est préparée dans
`.local/cloture/2026-10-12/sauvegarde-session.zip`. Son reçu est dans
`data/cloture-journee-verification.json`. Il ne prouve aucune restauration
hébergée ; les preuves de celle-ci restent attachées au paquet et à la cible.
