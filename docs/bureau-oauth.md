---
project: TC_Longages
document_type: development-guide
title: Bureau Drupal et connexion OAuth
status: active
version: git
created: 2026-10-12
updated: 2026-10-12
owner: jpdandin
tags: [bureau, inscriptions, oauth, drupal, v2]
---

# Bureau Drupal et connexion OAuth

Le 12 octobre, le responsable confirme V1 et indique n'avoir reçu aucun retour
des bêta-testeurs. Cette déclaration autorise le nouveau développement Bureau ;
elle ne constitue pas une observation indépendante des retours de campagne.

Le lot reprend le module natif `tcl_bureau` de `main` à `e0ec292`.
Il ajoute **Bureau** au menu des sept pages lorsqu'il est activé, sans remplacer
les modèles HTML ni réinitialiser les textes et révisions Drupal. La page
`espace.html` reste une page d'information V1. Le nouvel accès ouvre
`/bureau/connexion`, puis le fournisseur OAuth et enfin `/bureau`.

## Utilisation

1. Ouvrir **Menu → Bureau**, puis se connecter auprès du fournisseur autorisé.
2. Choisir **Enregistrer un nouvel adhérent** : identité, saison, coordonnées,
   dossier adulte ou mineur et responsable légal, formule reçue et notes internes.
3. Rechercher le dossier par nom/prénom, l'ouvrir et compléter ses informations.
4. Suivre les états **Reçu**, **À compléter**, **Vérifié par le Bureau**.
5. Utiliser **Communication** pour le parcours natif existant, ou **Se déconnecter**.

Les dossiers restent dans la base privée Drupal. Le contrôle CSRF, l'unicité
personne/saison, les soumissions répétées et le verrouillage des révisions
restent assurés par le module existant. Le Capitaine n'accède pas aux dossiers
d'adhésion ; la gestion des équipes reste désactivée. Aucun paiement ni création
de licence FFT n'est déclenché. Le formulaire public d'adhésion et les imports
ne sont pas inclus dans ce lot.

## Connexion et habilitations

Le responsable demande une **connexion indépendante pour le TC Longages** et
un premier accès **Admin TC Longages**. `tcl_identity` installe un fournisseur
dans le Drupal du club, avec Simple OAuth **6.1.1**, version utilisée par AVEREO.
Les dépendances sont verrouillées dans Composer ; Drupal reste en 11.4.6.
Les comptes, base, client, secret et paire RSA sont propres au TC. Aucun compte
AVEREO n'est nécessaire et aucune donnée ou clé AVEREO n'est copiée.

`tcl_bureau_oauth` réutilise le contrat OAuth/PKCE du consommateur CONNECT.
Le fournisseur TC reprend le point d'extension des claims du module
`avereo_rapport_oauth`, adapte `sub` au format chaîne et ajoute un nonce signé
à usage unique. Cette extension utilise les services Simple OAuth sans modifier
le code contrib. Le backend et le catalogue HMAC CONNECT restent indépendants.
La [version officielle](https://www.drupal.org/project/simple_oauth/releases/6.1.1)
est compatible Drupal 11 ; sa disponibilité ne vaut pas qualification hébergée.

Le module validé en préproduction est indiqué par le responsable. La validation
de l'installation dédiée TC reste distincte. Le code de référence consulté est
`Avereo\Connect\Identity\OAuthFlow` dans `connect-rapport-local`, commit
`fbf395b31917ad46565896ae46317e11db0b611e`, identique pour ce composant à la source
AVEREO `3d70b530acc236f36ade6c06a553d3c9f7ed07f1`.
Le [contrat TC](../api/bureau-oauth.md) décrit l'adaptation et ses limites.

Une identité exacte **issuer + sub** doit être associée explicitement à un uid
Drupal local actif disposant du droit Bureau. Aucun compte inconnu n'est créé,
aucun email ne sert à associer des comptes et aucun rôle externe ne devient
automatiquement un rôle local. Les associations sont uniques et privées.
Les droits locaux sont relus à chaque accès. Blocage du compte, retrait du rôle,
retrait d'association ou changement de configuration invalident l'accès courant.

Le rôle **Admin TC Longages** (`tcl_admin`) possède les droits des inscriptions,
communication, comptes Bureau/Capitaine et édition des sept pages. Il n'est pas
un super-administrateur technique Drupal. L'installation crée le rôle, sans
créer de compte, mot de passe ou clé réels. Le premier compte hébergé prévu est
`Admin_TC_Longages` ; son identifiant final et son secret restent à renseigner
sur la cible par le responsable, dans l'interface privée.

La connexion native Drupal permet toujours les opérations éditoriales autorisées
du site et sert de connexion au fournisseur TC. Elle ne suffit pas seule pour
accéder au Bureau : le parcours OAuth doit encore établir sa preuve.
La gestion métier des comptes ne propose plus de mot de passe : créer un compte
local ne l'autorise pas automatiquement à accéder au Bureau ; l'administrateur
doit initialiser son accès privé au fournisseur TC et son association avant
sa première connexion. Le compte Admin n'est pas modifiable par les formulaires
métier de gestion des comptes.

## Recette locale et état réel

Le [site local](http://127.0.0.1:4186/) et le [Bureau](http://127.0.0.1:4186/bureau)
utilisent une base SQLite de recette et le **véritable module Simple OAuth** TC
sur `127.0.0.1:4191`, avec le premier compte fictif `Admin_TC_Longages_local`.
Deux serveurs PHP sont nécessaires en local sous Windows pour l'échange HTTP
du code ; ils utilisent le même Drupal et sa session. Sur l'hébergement, les
requêtes concurrentes et leur routage restent à qualifier.
Le bandeau **OAuth TC Longages, comptes et données de test uniquement** distingue
cette recette du service hébergé. Ne pas saisir de dossier réel. Les identifiants
locaux sont conservés uniquement dans `.local/tc-identity-local.json` ; clés et
configurations restent dans `.local/`, hors Git et archives.

Le fournisseur fictif sur `127.0.0.1:4190` reste utilisé pour injecter des jetons
invalides, puis le script teste le fournisseur TC réel et le compte Admin local.

Depuis un checkout sans base locale, avec PHP, Node et Python installés :

```powershell
./scripts/bureau-oauth-recette.ps1 -Prepare
```

Le script prépare les modèles en vue Mois **avant** l'initialisation éditoriale,
installe le Drupal du verrou Composer, neutralise les courriels, exécute les
tests puis arrête ses serveurs. Il refuse une base préexistante et un port
de fournisseur déjà occupé. La recette ne prouve aucune connexion hébergée.
Le [reçu](../data/bureau-oauth-verification.json) distingue tests, navigateur,
consultation de Claude et prérequis encore manquants.

## Phases et prochaine action

| Phase | Situation du lot |
| --- | --- |
| Cadrage | V1 confirmée ; Bureau, OAuth TC indépendant et premier Admin demandés. |
| Développement local | Fournisseur TC, adaptateur, menus, rôle Admin et recettes locales réalisés ; PR #19 et CI dans le suivi canonique. |
| Préproduction | Dépendances, clés/client TC, premier compte Admin, protection HTTP, récupération et recette hébergée à qualifier. |
| Mise en production | Non autorisée pour ce candidat ; accord et contrôles propres à cette livraison nécessaires. |

Le [suivi canonique](suivi-chantier/suivi-chantier.json#developmentIterations)
porte l'itération `tcl-v2-bureau-oauth`. Le cockpit n'est ni ouvert ni développé.
Le [workflow de raccordement](../workflows/bureau-oauth-preproduction.md)
précise le passage du candidat local à la cible TC protégée.

Le paquet de modules se prépare depuis la source commitée avec
`python scripts/package-bureau-oauth.py`. Il conserve ses empreintes dans
`.local/bureau-oauth-package.json`, exclut modèles éditoriaux, base et fixtures,
et contient les modules/contrôleur concernés, les exemples inactifs et les
fichiers Composer verrouillés. Les dépendances contrib/vendor ne sont pas
incluses : `composer install --no-dev` doit être qualifié dans une copie isolée
avant déploiement. Le manifeste indique `readyForDirectDeployment: false`.
La préparation du paquet n'active aucun service.

**TBD avant utilisation réelle :** installation des dépendances sur la cible,
clés et client TC privés, compte Admin et association avec son uid, appels HTTP
côté serveur sous Basic Auth, sauvegarde/restauration, connexion/déconnexion
hébergées et validation des champs avant collecte d'adhérents. Le nonce signé
du fournisseur TC est implémenté et testé localement, sans option de tolérance.
