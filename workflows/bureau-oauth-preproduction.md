---
project: TC_Longages
document_type: delivery-workflow
title: Raccorder le Bureau OAuth à la préproduction TC
status: active
version: git
created: 2026-10-12
updated: 2026-10-13
owner: jpdandin
tags: [preproduction, bureau, oauth, sauvegarde, recette]
---

# Raccorder le Bureau OAuth à la préproduction TC

Ce workflow complète le protocole commun et son suivi canonique ; il ne crée
aucune autorisation de merge, de modification du fournisseur ou de livraison.
Le candidat est décrit dans le [guide](../docs/bureau-oauth.md) et le
[contrat](../api/bureau-oauth.md). Les commandes et accès réels doivent être
requalifiés avant leur exécution distante.

Observation du 12 octobre : PR #19 fusionnée à `8bd9b8f9c8625a5811e35eab4a8d43da7c4a1306`,
paquet natif `702d4e5b697dcae2b388324e0011ad06ef0a30858d8732fea667d332cd9d7431`
conservé, 50 fichiers comparés au merge. La demande humaine autorise sa
préproduction ; confirmation à l'action reçue, accès initialisé et paquet
installé. Voir le [reçu](../data/bureau-oauth-verification.json#hostingActivation).
Le fichier d'initialisation est téléchargé ; mot de passe personnel et recette
OAuth réelle restent attendus. Aucun accès AVEREO ni production modifié.
Le 13 octobre, la correction de première saisie réussit les huit contrôles HTTP
locaux, puis l'opérateur est installé et le lien natif régénéré sur la cible.
Le nouveau fichier est remis au responsable. L'adresse du club est déjà
utilisée : il choisit une autre adresse sans modifier l'ancien compte, puis
définit son mot de passe. Voir le [point de reprise](../docs/point-session.md).

## Cadrage

Reprendre `tcl-v2-bureau-oauth` dans le suivi existant. Conserver la déclaration
de V1 confirmée et l'absence de retour signalée par le responsable.
Le responsable choisit un fournisseur **TC uniquement**, dans son Drupal,
en réutilisant Simple OAuth 6.1.1 d'AVEREO. Préparer le premier compte
`Admin_TC_Longages` avec le rôle `tcl_admin`, ses clés propres et le client
confidentiel `tc-bureau-preproduction`. Aucun compte, secret ou droit AVEREO
n'est copié. L'identifiant final est `Admin_TC_Longages`. Le mot de passe sera
saisi par le responsable dans l'interface privée, sans transmission au chat.

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
une règle bornée aux requêtes exactes POST `/oauth/token` et GET `/oauth/userinfo`,
depuis l'IP serveur observée `109.234.167.71` ou les boucles locales, en plus de
client/code/PKCE ou Bearer. Avant une activation, conserver le plan privé et ne modifier Apache
qu’après confirmation. Requalifier la règle si cPanel régénère la protection du
répertoire. Tester les appels serveur et le refus 401 depuis l'extérieur.

Les opérateurs [Python](bureau-preproduction.py) et [PHP](bureau-preproduction.php)
sont CLI uniquement et bornés à la racine et à la base de préproduction.
`stage` a réussi le 12 octobre à 20:23 UTC : sauvegarde `20261012T202257Z`,
restauration de tous les fichiers, comparaison des 50 schémas et de toutes les
lignes actives sous préfixe `sr_702d4e5b697d_`, sans écraser les 440 tables de
récupérations antérieures. Composer et l'installation native ont réussi dans
cette copie privée, avec dossier fictif et transports neutralisés. Cet essai
ne prouve pas encore une connexion OAuth du compte réel.

`plan --archive …` relit empreintes, textes/révisions, utilisateurs, runtime,
protection, verrou et dépendances avant activation. Toute dérive refuse
l'opération. `apply --archive … --security-approved` est réservé à l'accord
humain réellement recueilli : le drapeau ne crée aucun accord. Il promeut les
dépendances préparées et les 50 sources immuables, active les trois modules et
les mises à jour, puis provisionne le compte sans mot de passe, les clés RSA TC
et le client privés. La maintenance revient à son état précédent ; les pages,
révisions et transports V1 sont conservés.

Le lien natif Drupal à usage unique est enregistré uniquement dans
`private/tc-identity/initialisation-admin.html` (0600), hors web et Git. Le
responsable le télécharge par cPanel et ouvre lui-même le formulaire, puis
saisit, confirme et enregistre son mot de passe. Ne pas lire ou publier le lien
secret dans le chat et ne pas automatiser la saisie du vrai mot de passe.
Le lien doit utiliser l'entrée native complète `user_pass_reset_url($admin)`,
sans suffixe `/login` ni paramètre `destination`. L'entrée gère une session
déjà connectée : elle déconnecte le même compte avant de reprendre le parcours,
ou avertit lorsqu'un autre compte est connecté sans changer son identité.
La confirmation native mène ensuite au formulaire avec `pass-reset-token`,
comparé à la session pour autoriser la première saisie sans mot de passe actuel.
Le raccourci direct `/login` renvoie 403 lorsqu'une session Drupal existe déjà ;
ce cas a été reproduit après la capture utilisateur du 13 octobre.

Le correctif complet est préparé et testé localement, pas encore transféré.
Le fichier déjà téléchargé `initialisation-admin (1).html` contient encore le
raccourci et doit être remplacé. L'essai déclaré en fenêtre privée reste refusé ;
les journaux serveur confirment uid 2 connecté sur une route réservée aux anonymes.
La saisie réelle reste personnelle. Ne pas régénérer un lien si le mot de passe
a été enregistré entre-temps ; vérifier d'abord l'état actuel.

La commande PHP `initialise` sur la racine active effectue cette reprise
uniquement pour le compte encore sans mot de passe associé au reçu installé.
Elle recontrôle les droits du dossier privé, l'association, le secret client,
PKCE et la paire RSA avant de remplacer atomiquement le fichier en 0600.
Elle ne recrée ni compte ni client ni clé et n'imprime aucun lien secret.
La recette `tests/drupal-password-setup-http.py`, incluse dans le script de
recette OAuth, vérifie les sessions absente, identique et différente, la
reproduction du refus 403 de l'ancien raccourci, le token natif, le refus
d'adresse dupliquée, la première saisie, la connexion, le refus de rejeu et le
maintien de la protection OAuth du Bureau. Les 18 contrôles locaux réussissent.

La reprise `resume --archive … --security-approved` est bornée à une installation
marquée interrompue du même paquet. Elle vérifie les 50 sources, le runtime,
les pages, le compte sans mot de passe, l'association, le secret client haché
et la paire de clés existants. Aucun compte/client/clé n'est recréé. Le seul
répertoire actif `web/sites/default` peut recevoir temporairement le droit
d'écriture propriétaire pour une écriture atomique ; son mode initial est
restauré dans `finally`. La reprise du 12 octobre a vérifié le retour à 0555.

Recetter réellement : bouton sur sept pages, retour HTTPS, connexion du Bureau,
Capitaine sans dossier privé, refus de login natif comme contournement,
saisie/modification/rechargement, retrait de rôle/association, expiration,
déconnexion, conservation édition/agenda/photo/contact/support. Consigner les
résultats et limites dans le suivi, sans confondre succès OAuth et droits métier.

Retour arrière à préparer et tester : fermeture du nouvel accès Bureau,
conservation des tables/dossiers et des comptes, restauration du seul code et
de la configuration nécessaires depuis la sauvegarde. Ne pas restaurer une
base complète par-dessus des dossiers reçus depuis la livraison. En cas d'échec
de l'opérateur, le chargeur de configuration, la protection Basic et le
contrôleur V1 sont restaurés pour fermer l'intégration. Les modules,
dépendances et lignes ajoutées sont conservés pour diagnostic : ce mécanisme
ne constitue pas une restauration SQL complète ni une recette OAuth réussie.

## Mise en production

Non autorisée dans ce lot. Préparer son propre candidat, récupération, revue
et accord exact après recette préproduction. Les accès et protections de V1
ne valent pas ouverture du Bureau aux données réelles. La suite métier conserve
équipes/matchs, partenaires et améliorations de communication demandés.
