---
project: TC_Longages
document_type: verification-report
title: Recette locale de la préparation OIDC
status: experimental
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - oidc
  - recette
  - maquette
---

# Recette OIDC du 16 septembre 2026

## Résultat et décision actuelle

L'utilisateur maintient le projet au **maquettage** et reporte l'activation réelle jusqu'à la décision d'acquérir un nom de domaine. Le code OIDC est conservé comme préparation expérimentale inactive. Les deux comptes futurs sont définis (administrateur personnel et bureau générique), mais aucune identité réelle ni secret client n'est configuré. Aucun certificat, DNS, accès fournisseur ou hébergement n'a été modifié.

La [connexion OIDC](authentification-oidc.md) décrit les composants et la configuration future. Le [guide certificat](certificat-et-connexion-bureau.md) distingue certificat valide et redirection HTTPS. La visite fictive sur le port 4174 reste le parcours de présentation actif ; l'archive de vitrine publique demeure inchangée.

## Vérifications locales

`npm.cmd run check` réussit **54 tests sur 54** : les 25 tests existants, 17 tests HTTP/configuration du serveur OIDC et 12 vérifications du client OIDC, sous-tests compris.

| Périmètre | Résultat |
|---|---|
| Protocole | Fournisseur fictif injecté sans réseau, vrais JWT RS256 et clés publiques de recette ; PKCE, state, nonce, issuer, audience, expiration et signature vérifiés. Valeurs altérées et code réutilisé refusés. |
| Autorisation | Liste exacte de deux rôles, identité par issuer/subject, aucune attribution par e-mail ou rôle déclaré par le fournisseur. Bureau refusé sur la page administrateur. |
| Routes | Anonymat redirigé vers la connexion, configuration absente fermée, chemins privés inconnus refusés, méthodes et hôtes contrôlés. |
| Sessions | Cookies opaques, Secure en HTTPS, HttpOnly, SameSite=Lax ; transaction liée au navigateur et consommée avant échange, rejeu et concurrence refusés. |
| Fin d'accès | Révocation à la requête suivante ; expiration après 30 minutes d'inactivité ou huit heures ; déconnexion POST avec origine exacte et CSRF. |
| HTTPS | HTTP distant refusé ; en-tête de proxy seul insuffisant. Confiance explicite dans un proxy local requise. |
| Navigateur | `scripts/verify-oidc.cjs` réussit avec client de connexion fictif en mémoire et retour local : cinq pages privées aux largeurs 1 440, 390 et 320 px, rôles, déconnexion et révocation. Aucune erreur JavaScript ni requête fournisseur sur cette recette finale. |

Les [résultats navigateur](recette/oidc/results.json) et les vues [connexion](recette/oidc/connexion.png) et [accès mobile](recette/oidc/acces-mobile.png) sont conservés. Ce parcours teste le navigateur et le serveur, avec un remplacement du client OIDC ; les validations cryptographiques sont testées séparément. Il ne constitue pas une connexion complète à Google, Microsoft ou un autre fournisseur réel.

## Corrections et limites

La recette navigateur a détecté un en-tête `Referrer-Policy: no-referrer` produisant une origine `null` pour le formulaire de connexion et bloquant son contrôle d'origine. Le serveur utilise maintenant `same-origin`, avec `no-referrer` sur le retour OIDC contenant le code. La politique de formulaire autorise le fournisseur configuré sur les routes de connexion ; le point d'autorisation doit appartenir à l'origine de cet émetteur. Un débordement du tableau des comptes à 320 px a également été corrigé.

Un essai d'interception navigateur d'une redirection vers un fournisseur fictif externe a atteint le délai d'attente ; cette interception ne constitue donc pas une recette réussie du parcours externe. Le contrôle navigateur final utilise un retour fictif local, sans réseau fournisseur. Le parcours externe réel reste à qualifier lors d'une future activation.

Restent différés : choix du domaine et du fournisseur, HTTPS réel, configuration des deux identités, essais connectés, routage et proxy Passenger, instance unique ou stockage de sessions partagé. L'authentification ne fournit ni stockage métier central ni synchronisation des actualités. Les données du profil de navigateur ne sont pas effacées par la fin de session.

Le socle, les métadonnées et les liens des documents courants sont contrôlés après intégration des documents. Aucune disponibilité ni sécurité d'un service hébergé n'est attestée. L'ouverture réelle reste une étape distincte à décider par l'utilisateur.
