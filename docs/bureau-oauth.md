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

Le nouvel adaptateur `tcl_bureau_oauth` reprend le **contrat OAuth/PKCE du
fournisseur Drupal utilisé par AVEREO CONNECT**, avec des paramètres propres au
TC. Il ne déploie ni un second fournisseur d'identité, ni une copie du backend
CONNECT. Il ne consomme pas les tickets HMAC du catalogue CONNECT : ce catalogue
ne possède actuellement aucune entrée TC dans la source examinée.

Le module validé en préproduction est indiqué par le responsable. La validation
du nouveau client TC reste distincte. Le code de référence consulté est
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

La connexion native Drupal permet toujours les opérations éditoriales autorisées
du site. Elle ne suffit plus pour accéder au Bureau, même pour un administrateur.
La gestion métier des comptes ne propose plus de mot de passe : créer un compte
local ne lui donne pas une identité OAuth ; l'administrateur doit réaliser
l'association privée avant sa première connexion.

## Recette locale et état réel

Le [site local](http://127.0.0.1:4186/) et le [Bureau](http://127.0.0.1:4186/bureau)
utilisent uniquement une base SQLite neuve et un fournisseur fictif sur
`127.0.0.1:4190`. Le bandeau **connexion OAuth simulée et données fictives**
est obligatoire dans cette recette. Ne pas saisir de dossier réel.
Les comptes et clés de recette restent dans `.local/`, hors Git et archives.
Le fournisseur fictif permet d'essayer le parcours sans compte ni mot de passe.

Depuis un checkout sans base locale, avec PHP, Node et Python installés :

```powershell
./scripts/bureau-oauth-recette.ps1 -Prepare
```

Le script prépare les modèles en vue Mois **avant** l'initialisation éditoriale,
installe le Drupal du verrou Composer, neutralise les courriels, exécute les
tests puis arrête ses deux serveurs. Il refuse une base préexistante et un port
de fournisseur déjà occupé. La recette ne prouve aucune connexion hébergée.
Le [reçu](../data/bureau-oauth-verification.json) distingue tests, navigateur,
consultation de Claude et prérequis encore manquants.

## Phases et prochaine action

| Phase | Situation du lot |
| --- | --- |
| Cadrage | V1 confirmée par le responsable ; Bureau, bouton dédié et OAuth demandés. |
| Développement local | Adaptateur, menus, droits et recette isolée réalisés ; PR et CI à suivre dans le suivi canonique. |
| Préproduction | Client OAuth TC, identités autorisées, configuration privée, récupération et recette réelle à qualifier avant installation. |
| Mise en production | Non autorisée pour ce candidat ; accord et contrôles propres à cette livraison nécessaires. |

Le [suivi canonique](suivi-chantier/suivi-chantier.json#developmentIterations)
porte l'itération `tcl-v2-bureau-oauth`. Le cockpit n'est ni ouvert ni développé.
Le [workflow de raccordement](../workflows/bureau-oauth-preproduction.md)
précise le passage du candidat local à la cible TC protégée.

Le paquet de modules se prépare depuis la source commitée avec
`python scripts/package-bureau-oauth.py`. Il conserve ses empreintes dans
`.local/bureau-oauth-package.json`, exclut modèles éditoriaux, base et fixtures,
et contient seulement les modules et le contrôleur concernés, plus l'exemple
de configuration inactif. La préparation du paquet n'active aucun service.

**TBD avant utilisation réelle :** issuer et endpoints exacts du fournisseur,
client TC dédié et secret privé, clé publique, identités `sub` autorisées et uid
locaux associés, comportement Simple OAuth concernant le nonce, connexion et
déconnexion réelles, validation des champs avant collecte d'adhérents.
