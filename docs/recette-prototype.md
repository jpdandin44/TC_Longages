---
project: TC_Longages
document_type: verification-report
title: Recette locale du prototype
status: active
version: git
created: 2026-09-16
updated: 2026-09-24
owner: jpdandin
tags:
  - recette
  - prototype
  - verification
---

# Recette locale du 16 septembre 2026

Ce rapport conserve les résultats historiques du 16 septembre, y compris l'ancien contact FFT. Depuis le 24 septembre, le contact public est `tclongages@gmail.com` ; la [recette de messagerie courante](recette-officiel.md#révision-du-24-septembre--contact-gmail-et-support-des-tests) documente les pages et archives régénérées. Les manifestes et captures courants peuvent correspondre à cette nouvelle génération ; les résultats ci-dessous restent ceux de leur date.

## Résultat et périmètre

Le prototype comprend une vitrine publique locale et un espace bureau : accueil, communication, inscriptions en attente de contenu et aperçu des actualités. Le serveur contrôle l'accès aux quatre pages internes. Aucun compte réel n'est encore configuré : l'accès interne est fermé par défaut. Les actualités restent dans le navigateur ; la vitrine publique ne charge plus leur stockage. Chaque validation alimente l'aperçu privé et simule le relais Facebook ; elle rend également disponible le partage manuel WhatsApp. Aucun déploiement, compte Meta connecté ou message réel Facebook/WhatsApp n'a été effectué pendant les vérifications.

La source se trouve dans `src/`. Les livrables et leurs empreintes sont identifiés dans [le manifeste généré](../data/build-manifest.json). La [provenance](../data/source-provenance.json) distingue l'ancien pack réellement disponible du dernier HTML corrigé absent. Les photographies copiées correspondent octet pour octet aux empreintes historiques.

## Contrôles effectués

Lors de cette recette initiale, `npm.cmd run check` a réussi **21 tests sur 21**, avec les contrôles d'accès bureau, du stockage et du partage WhatsApp. La suite a ensuite été portée à 25 tests par la [démonstration partageable](recette-demo.md), dont la recette est consignée séparément.

| Périmètre | Résultat observé |
|---|---|
| Vitrine autonome | Deux images WebP intégrées, empreintes exactes, sept liens vers `23310230@fft.fr`, absence de Hotmail et d'alias inventé. |
| Sources et sorties | Génération déterministe ; six HTML sans ressources de chargement externes : cinq dans `dist/`, vitrine seule dans `release/`. |
| Vitrine prête à examiner | `release/` ne contient que `index.html`, sans page de gestion, stockage local, brouillon ou script de simulation. |
| Validation | Brouillon invisible dans l'aperçu des actualités réservé au bureau ; validation séparée ; modification enregistrée retirant l'actualité jusqu'à nouvel accord. |
| Import et stockage | Imports en brouillon, conflits conservés en copie, doublons ignorés, erreurs explicites sans effacement. |
| Entrées et concurrence | Liens dangereux refusés, texte traité comme texte, révision périmée refusée. |
| Volume | 200 longs textes Unicode ou caractères JSON échappés sauvegardés, exportés et réimportés ; limite harmonisée et contrôlée avant écriture. |
| WhatsApp | Message exact encodé sans destinataire imposé, partage limité à la révision validée, imports/brouillons refusés, aucun statut de livraison ajouté, texte long toujours copiable. |
| Accès bureau | Quatre routes protégées en GET et HEAD ; compte bureau actif accepté, anonymat/mauvais mot de passe/rôle non autorisé/compte désactivé refusés. |
| Fermeture et révocation | Configuration absente ou invalide fermée ; révocation prise en compte à la prochaine requête ; tentatives répétées et concurrentes limitées. |
| Séparation | Vitrine sans lecteur de brouillons, fichiers privés non servis, hôtes non autorisés et méthodes non prises en charge refusés. |
| Inscriptions | Page « Contenu à venir », sans formulaire ni champ personnel. |

La recette navigateur a été exécutée avec Playwright et Chrome installé, dans un profil temporaire distinct de celui de l'utilisateur, sur un serveur isolé à port éphémère. Un compte de recette est généré en mémoire uniquement, sans installation dans `.local/` et sans affichage des identifiants. Son résultat est conservé dans [browser-results.json](recette/browser-results.json). Elle vérifie les nouvelles pages bureau et inscriptions, puis création, annulation et confirmation de validation, statut simulé, aperçu privé, retrait après édition, export puis réimport effectif, absence d'erreur JavaScript et d'appel réseau externe. Aucun brouillon utilisateur n'a été touché.

Le contrôle spécifique `scripts/verify-whatsapp.cjs` utilise des remplacements de `window.open` et du presse-papiers : aucun lien WhatsApp n'est réellement ouvert. Il vérifie l'aperçu, les boutons inactifs avant validation, l'absence d'ouverture automatique, la copie, l'encodage exact du lien, le repli en cas de presse-papiers refusé, le blocage d'une révision périmée, le traitement d'un long message et les largeurs 1 440, 390 et 320 px. [Résultats WhatsApp](recette/whatsapp-results.json), [vue bureau](recette/whatsapp-bureau.png), [vue mobile](recette/whatsapp-mobile.png). Les deux vues ont été inspectées visuellement.

Les vues bureau de 1 440 px et mobile de 390 px ont été inspectées visuellement. Le menu mobile et Échap fonctionnent, les photos sont décodées, aucun débordement horizontal n'a été constaté à 390 px ; la page communication a également été contrôlée à 320 px. Ce contrôle ne constitue pas un audit complet d'accessibilité ni un essai sur téléphone physique.

- [Vitrine sur ordinateur](recette/vitrine-bureau.png).
- [Vitrine sur mobile](recette/vitrine-mobile.png).
- [Communication sur ordinateur](recette/communication-bureau.png).
- [Communication sur mobile](recette/communication-mobile.png).
- [Espace bureau sur ordinateur](recette/bureau-bureau.png).
- [Espace bureau sur mobile](recette/bureau-mobile.png).
- [Inscriptions sur ordinateur](recette/inscriptions-bureau.png).
- [Inscriptions sur mobile](recette/inscriptions-mobile.png).

Le serveur d'aperçu du projet a été redémarré sur `127.0.0.1:4173` avec les nouveaux contrôles. Vérification sur cette instance : `/` répond 200 ; `/bureau.html`, `/communication.html`, `/inscriptions.html` et `/actualites-bureau.html` répondent 503 car les comptes ne sont pas configurés ; `/.local/bureau-users.json` répond 404. Le fichier de comptes est absent. Les tests isolés confirment 401 sans identifiants et 200 avec un compte bureau actif, ainsi que 405 pour POST et 403 pour un hôte non autorisé. Le serveur n'est pas destiné à la production.

Les recettes navigateur optionnelles se relancent avec `node scripts/verify-browser.cjs` et `node scripts/verify-whatsapp.cjs`, dans un environnement où `require('playwright')` résout Playwright. Elles démarrent et arrêtent leur propre serveur de recette avec compte éphémère en mémoire. Elles utilisent Chrome par défaut ; `TCL_BROWSER_CHANNEL` peut désigner un autre canal Chromium installé. Elles ne font pas partie des prérequis de consultation ni des tests Node standard.

## Corrections issues de la revue

Une incohérence de plafond pouvait empêcher de lire ou réimporter un grand carnet produit par l'application. Les limites ont été harmonisées, un contrôle ajouté avant écriture et les cas Unicode/échappement couverts par un test. La correction a passé les tests et la recette navigateur.

La documentation décrit maintenant trois aperçus fixes : site, Facebook et WhatsApp, sans prétendre proposer une sélection de réseaux. Le socle, les métadonnées et les liens internes des documents courants ont été contrôlés. Les archives historiques sont identifiées comme telles et conservées intactes.

La revue du contrôle d'accès a détecté un sous-comptage des échecs de connexion concurrents. Le compteur est maintenant relu après la vérification du mot de passe ; un test couvre ce cas. La vitrine a également été séparée du lecteur de stockage local : l'aperçu éditorial se trouve désormais derrière le contrôle bureau.

## Limites et prochaine action

| Élément restant | Impact | Prochaine action |
|---|---|---|
| Site public actuel et HTTPS non vérifiés | Impossible d'attester sa disponibilité ou sa version actuelle. | Refaire une lecture publique depuis un accès réseau fonctionnel, puis vérifier la racine et le certificat avant toute publication autorisée. |
| Sondes publiques bloquées | L'outil web a échoué ; les requêtes HTTP et HTTPS du terminal ont renvoyé `EACCES`. Cela ne prouve pas une panne du site. | Utiliser un accès autorisé fonctionnel lors de la préparation de mise en ligne. |
| Page Facebook et droits non confirmés | Aucun relais réel disponible. | Confirmer la Page administrée, puis définir le serveur protégé et préparer la connexion Meta selon [le cadrage](../api/facebook.md). |
| Comptes du bureau non configurés | Les pages internes restent fermées sur le serveur local courant. | Confirmer les membres autorisés et le mode de comptes, puis préparer leur configuration par un canal sécurisé ; aucun mot de passe dans la conversation. |
| Protection locale limitée | HTTP Basic et stockage de navigateur ne constituent pas un espace de gestion prêt à héberger ; les fichiers locaux et le profil restent accessibles à leur propriétaire. | Décider d'une authentification hébergée, de sessions et d'un stockage central avant tout déploiement privé. Voir [l'accès bureau](acces-bureau.md). |
| Processus d'inscription proposé, non implémenté | La page reste un emplacement réservé sans collecte ; le prompt et les fiches ont désormais permis une conception. | Examiner [la proposition](processus-inscriptions.md), valider les champs et règles, puis réaliser le module. Les tests présents ne couvrent pas ce futur module. |
| Livraison WhatsApp non vérifiée | Le clic prépare un message mais aucun accusé de livraison n'est disponible ; aucun essai connecté n'a été réalisé. | Le responsable choisit son groupe, vérifie le message et confirme lui-même l'envoi dans WhatsApp ; automatisation future à étudier selon [le cadrage](../api/whatsapp.md). |
| Sources non rattachées à un dépôt Git local | Pas d'historique Git pour cette reconstruction. | Retrouver le dépôt existant ou décider d'initialiser un dépôt limité au site. |
| Informations saisonnières et visuels officiels | Horaires, tarifs, logo et photos du club non complétés. | Fournir et valider les éléments avant leur publication. |

L'accord de mise en production reste à obtenir pour une intervention précisément préparée. La validation locale seule ne déclenche ni n'autorise un envoi automatique ultérieur. L'ouverture ou la copie WhatsApp est une action distincte, suivie du choix du groupe et de la confirmation d'envoi par l'utilisateur dans WhatsApp.
