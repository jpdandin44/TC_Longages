---
project: TC_Longages
document_type: integration-plan
title: Relais des actualités vers Facebook
status: draft
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - facebook
  - communication
---

# Relais Facebook

## État constaté

Le prototype ne contient aucune connexion à Meta et n'envoie aucune publication. Le lien historique `https://www.facebook.com/tc.longages.31` ne prouve ni le type du compte ni les droits d'administration. Le 16 septembre 2026, l'utilisateur a demandé une validation de chaque actualité avant envoi. Le statut `simulated` du prototype désigne uniquement une démonstration locale.

## Cible envisagée et sources

La [collection officielle Meta sur Postman](https://www.postman.com/meta/facebook/documentation/r56bjfd/facebook-api) décrit la sélection d'une Page et un Page Access Token agissant pour cette Page. Consultation effectuée le 16 septembre 2026. Une Page administrée par le club doit être confirmée avant raccordement. La publication sur un profil personnel n'est pas implémentée ni promise.

Les pages de référence developers.facebook.com ont renvoyé HTTP 429 lors de cette recherche. Les conditions exactes de l'application doivent donc être revalidées :

- [Publication sur une Page](https://developers.facebook.com/docs/pages-api/posts/).
- [Prérequis de Pages API](https://developers.facebook.com/docs/pages-api/getting-started/).
- [Permissions](https://developers.facebook.com/docs/permissions/) : droits candidats `pages_manage_posts`, `pages_read_engagement`, `pages_show_list`, à confirmer selon le flux retenu.
- [Jetons d'accès](https://developers.facebook.com/docs/facebook-login/guides/access-tokens/).
- [Versions](https://developers.facebook.com/docs/graph-api/changelog/) et [limites API](https://developers.facebook.com/docs/graph-api/overview/rate-limiting/).

TBD : Page et administrateurs, application Meta, droits effectifs, niveau d'accès ou examen d'application requis, durée/renouvellement du jeton, version API, quotas. Aucune vérification d'entreprise n'est présentée comme systématiquement obligatoire.

## Parcours futur soumis à décision

1. Enregistrer garde un brouillon.
2. L'aperçu présente le texte exact du site et du relais Facebook.
3. Une action explicite autorise la publication de cette révision, une fois le service activé avec l'accord de l'utilisateur.
4. Le serveur publie la version approuvée sur le site, puis traite son relais Facebook.
5. Le résultat de chaque destination reste visible séparément : une panne Facebook ne doit pas être présentée comme une réussite.

Modifier le texte invalide l'approbation précédente. L'autorisation de déployer le service ne constitue pas une validation de toutes les actualités futures.

## Exigences du serveur à réaliser

- Authentification hébergée des responsables, gestion des sessions, contrôle d'accès côté serveur, HTTPS, protection CSRF et validation des entrées. Le contrôle HTTP Basic du serveur local protège seulement l'accès au prototype ; il ne constitue pas cette architecture de production, voir [l'accès bureau](../docs/acces-bureau.md).
- Identifiant de Page fixé côté serveur ; jeton absent du HTML, JavaScript navigateur, localStorage, exports et dépôt Git.
- Stockage partagé des brouillons et des révisions approuvées ; journal sans secrets.
- États séparés pour le site et Facebook, identifiant du message distant et erreur exploitable.
- File d'envoi durable et mécanisme anti-doublon. Après une réponse incertaine, vérifier le résultat avant toute relance.
- Arrêt explicite du relais possible sans arrêter le site.

Le choix du serveur de publication et du stockage central n'est pas encore arrêté. Aucun webhook, endpoint de relais Facebook, planificateur ou jeton Meta opérationnel n'est livré à ce stade.

## Décisions sensibles

La connexion d'un compte, l'octroi des permissions, l'installation d'un secret serveur, l'activation du relais et le premier essai réel sont à expliquer et à soumettre à l'utilisateur. Voir [les commandes sensibles](../docs/commandes-sensibles.md). Ne jamais demander de jeton ou mot de passe dans la conversation.
