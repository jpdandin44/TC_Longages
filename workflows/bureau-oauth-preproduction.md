---
project: TC_Longages
document_type: delivery-workflow
title: Raccorder le Bureau OAuth à la préproduction TC
status: active
version: git
created: 2026-10-12
updated: 2026-10-12
owner: jpdandin
tags: [preproduction, bureau, oauth, sauvegarde, recette]
---

# Raccorder le Bureau OAuth à la préproduction TC

Ce workflow complète le protocole commun et son suivi canonique ; il ne crée
aucune autorisation de merge, de modification du fournisseur ou de livraison.
Le candidat est décrit dans le [guide](../docs/bureau-oauth.md) et le
[contrat](../api/bureau-oauth.md). Les commandes et accès réels doivent être
requalifiés avant leur exécution distante.

## Cadrage

Reprendre `tcl-v2-bureau-oauth` dans le suivi existant. Conserver la déclaration
de V1 confirmée et l'absence de retour signalée par le responsable.
Le responsable choisit un fournisseur **TC uniquement**, dans son Drupal,
en réutilisant Simple OAuth 6.1.1 d'AVEREO. Préparer le premier compte
`Admin_TC_Longages` avec le rôle `tcl_admin`, ses clés propres et le client
confidentiel `tc-bureau-preproduction`. Aucun compte, secret ou droit AVEREO
n'est copié. L'identifiant final et le mot de passe du compte seront saisis
par le responsable dans l'interface privée, sans transmission au chat.

## Développement local

Conserver SHA de branche/PR, preuves de tests, configuration exemple inerte et
résultat navigateur. Le fournisseur local n'est pas une preuve d'OAuth hébergé.
Les outils de recette SQLite, comptes et clés fictifs restent hors du paquet.

## Préproduction

Cible connue à recontrôler : `https://preprod.tclongages.fr/`, racine
`/home2/daje5127/tcl-preproduction/drupal/web`. La protection Basic Auth des
bêta-testeurs reste distincte de l'identité OAuth Bureau. Préserver cette
protection, les textes/révisions, contacts et signalements. Aucun changement
de `public_html` ou d'une autre application AVEREO n'est inclus.

Avant installation : vérifier candidat/CI/revue, accord de cette livraison,
état actuel des modules et rôles, configuration privée dédiée, sauvegarde
fraîche fichiers/base et restauration contrôlée. L'activation de `tcl_bureau`
est additive mais refuse les collisions de rôles préexistants : les examiner
avant d'activer, sans suppression ni élargissement automatique.

Le paquet inclut Composer/lock mais pas contrib/vendor. Qualifier d'abord
`composer install --no-dev --no-interaction --prefer-dist --no-progress` dans
une copie isolée de cette cible, PHP et extensions vérifiés. Le verrou ajoute
13 packages sans mise à jour de Drupal ; conserver un retour arrière des
dépendances. N'écraser aucun settings.php, texte ou répertoire de fichiers.

Installer `tcl_identity`, puis OAuth/Bureau et exécuter les updates Drupal
avec l'outil qualifié ; l'update `11002` ajoute le droit de consentement OAuth
aux rôles existants. Reconstruire les caches/routes. Aucun import de base locale
ni réinitialisation des sept pages. La nouvelle entrée est insérée au rendu
serveur ; les HTML existants ne sont pas à remplacer pour ce bouton.
Créer le premier Admin TC uniquement sur cette cible privée ; le rôle n'est
pas super-admin technique. Le responsable définit son mot de passe dans Drupal.
Associer issuer `https://preprod.tclongages.fr/` et sub (uid TC en chaîne) à
ce uid actif dans la configuration privée. Créer une nouvelle paire RSA TC
hors web, un secret confidentiel et le client à grant authorization_code seul,
PKCE activé, callback fixe et autorisation automatique du seul client interne.
Inclure la copie privée du [modèle](../drupal/config/tc-identity.example.php).

**Prérequis HTTP à qualifier :** la protection Basic Auth actuelle peut bloquer
les appels serveur `/oauth/token` et `/oauth/userinfo`. Ne pas envoyer des
identifiants Basic à Simple OAuth : son middleware les interprète comme client
OAuth ; ne pas retirer la protection de l'ensemble du site. Préparer et recetter
une règle bornée à ces deux endpoints, protégés par client/code/PKCE ou Bearer,
avec l'accord sur la configuration d'accès. Aucun changement Apache n'est
réalisé dans ce lot local. Tester aussi les requêtes concurrentes sur l'hôte.

Recetter réellement : bouton sur sept pages, retour HTTPS, connexion du Bureau,
Capitaine sans dossier privé, refus de login natif comme contournement,
saisie/modification/rechargement, retrait de rôle/association, expiration,
déconnexion, conservation édition/agenda/photo/contact/support. Consigner les
résultats et limites dans le suivi, sans confondre succès OAuth et droits métier.

Retour arrière à préparer et tester : fermeture du nouvel accès Bureau,
conservation des tables/dossiers et des comptes, restauration du seul code et
de la configuration nécessaires depuis la sauvegarde. Ne pas restaurer une
base complète par-dessus des dossiers reçus depuis la livraison.

## Mise en production

Non autorisée dans ce lot. Préparer son propre candidat, récupération, revue
et accord exact après recette préproduction. Les accès et protections de V1
ne valent pas ouverture du Bureau aux données réelles. La suite métier conserve
équipes/matchs, partenaires et améliorations de communication demandés.
