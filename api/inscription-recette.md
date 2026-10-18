---
project: TC_Longages
document_type: integration-contract
title: Catalogue saisonnier et inscription V2 en recette
status: active
version: git
created: 2026-10-18
updated: 2026-10-18
owner: jpdandin
tags: [v2, inscriptions, catalogue, recette]
---

# Catalogue et inscription de recette

## Portée

Le responsable autorise le 18 octobre la poursuite du catalogue et du formulaire sur données fictives en préproduction. Les textes et créneaux seront complétés ensuite. Aucune collecte réelle, livraison production, licence, paiement ni notification n'est activé par ce lot.

Les quatre photos fournies dans `Adhérents.zip` ont été consultées localement pour leur structure : trois fiches adultes et une fiche école. Aucune identité, coordonnée, date de naissance, écriture manuscrite ou image n'est copiée dans les fixtures, Git ou l'hébergement. Leurs anciens tarifs imprimés et leur adresse imprimée ne remplacent pas les décisions actuelles. Ces fiches ne fournissent pas les textes définitifs d'autorisation ni les créneaux manquants.

## Points d'entrée

| Route | Acteur et contrôle | Effet |
| --- | --- | --- |
| `/bureau/saison` | OAuth TC valide et permission existante `manage tcl registrations`, contrôle renouvelé au service | Lire/modifier le brouillon, publier explicitement pour la recette après confirmation |
| `/adherer` | Mode recette actif sur localhost, 127.0.0.1 ou preprod.tclongages.fr | Saisie fictive, récapitulatif, correction et dépôt ; aucune lecture de dossier privé |
| `/bureau/adherents` et fiche existante | Garde OAuth et permission existantes inchangées | Traiter la demande fictive dans la même table privée que la saisie Bureau |

Ce sont des formulaires Drupal, pas une API JSON publique. Les POST passent par Form API. La saisie anonyme crée une session pour son jeton CSRF ; les réponses sont privées, non stockables et non indexables. Un jeton d'une autre session ne permet pas de déposer. La limite applicative est de 20 dépôts de test par heure et par adresse IP ; aucune donnée de contact n'est utilisée pour un envoi.

## Catalogue

Le seed `drupal/web/modules/custom/tcl_bureau/resources/season-seed.json` est **généré** par `node scripts/build-season-seed.mjs` depuis les tarifs sources et le correctif confirmé, avec le libellé V2. `--check` vérifie sa cohérence. Il initialise un brouillon de dix formules, sans publication, créneau ni notice inventés. Ne pas éditer ce dérivé à la main.

Après premier enregistrement, la source active devient la collection Drupal `key_value`, `tcl_bureau.seasons`, clé `state`. Elle contient la révision courante, le brouillon, la publication et l'historique des publications. Un verrou et la révision attendue empêchent deux écrans de s'écraser. Une modification du brouillon conserve la publication précédente. Une publication crée une nouvelle révision historique.

Chaque formule possède un identifiant stable, un libellé, un profil adulte/mineur et un montant entier en centimes EUR. Les montants négatifs, ambigus, supérieurs à 10 000 €, les identifiants dupliqués, les libellés vides et les saisies excessives sont refusés. Le formulaire Bureau saisit des euros, convertis en centimes sans calcul flottant. Pas de remise famille, cumul ou total automatique.

En mode recette, le tableau d'accueil et le formulaire utilisent **la même publication**. Le remplacement du tableau se fait au rendu, avec mention « RECETTE FICTIVE » ; les modèles, textes natifs et leurs révisions ne sont pas écrasés. Sans publication ou hors mode recette, le tableau antérieur reste inchangé. Un modèle tarifaire non reconnu bloque le rendu au lieu de mélanger silencieusement deux catalogues.

## Demande fictive

Identité adulte/mineur, naissance, contact, adresse, responsable légal, personne à prévenir, pratique antérieure, formule et souhait de cours. Les champs urgence/expérience restent facultatifs. Les créneaux vides affichent « À compléter ». Les autorisations sont explicitement indiquées comme en attente : aucune case n'invente une signature, un accord d'urgence ou un droit à l'image.

Pour la recette, le nom commence par `Recette`, les courriels utilisent `@example.invalid` et les téléphones sont vides ou `0600000000`. Le responsable d'un mineur suit les mêmes conventions. Le profil est rapproché de l'âge à la date de saisie ; une formule doit correspondre au profil. Ne jamais saisir de données médicales, bancaires ou issues des fiches reçues.

Le service fixe côté serveur la saison, le statut `received`, le libellé et le tarif provenant du catalogue. Il ignore les tentatives de définir un statut interne, une note Bureau, un montant ou un numéro de licence. Un catalogue republié depuis l'ouverture du formulaire exige un nouveau récapitulatif.

Le dossier stocke dans son JSON privé `catalogue_snapshot` (saison, révision, formule, montant, devise et notice), `origin=web_recipe`, `recipe=true` et les disponibilités. Le Bureau voit la mention fictive et le tarif au dépôt. Une modification Bureau conserve ces éléments, même après publication d'un autre tarif. La clef de soumission rend le double clic idempotent ; la contrainte identité/saison empêche un second dossier concurrent. Deux personnes peuvent partager un courriel.

## Activation et récupération

Le réglage privé `tcl_registration_recipe_enabled` vaut faux par défaut. Le code refuse toujours le domaine de production même si ce réglage est vrai. Le fichier local `.local/registration-recipe.enabled` ne concerne que la configuration loopback. Le helper `tests/registration-recipe-setup.php` contrôle la base SQLite locale avant toute action et n'est jamais à exécuter sur l'hébergement.

Pas de nouvelle table, rôle, compte ou secret. Le déploiement doit conserver le site, les fichiers privés, les contenus, les comptes, la base et les réglages OAuth/Basic existants. Sauvegarder et restaurer sur une cible isolée avant remplacement. Un retour de code ou la désactivation du réglage ferme le formulaire ; il ne supprime aucun dossier reçu ni aucune publication de catalogue. Ne pas restaurer une base ancienne par-dessus des saisies plus récentes.

## Vérification et limites

Les suites `tests/registration-recipe-http.py` et `tests/season-catalogue-local.php` testent le parcours, les droits, CSRF, doublons, révisions, instantanés et fermeture. Elles utilisent exclusivement la base loopback et des identités fictives. Elles sont raccordées à la CI Bureau/OAuth. Les résultats locaux ne constituent pas une recette de l'hébergement.

Restent hors de ce lot : collecte réelle, textes définitifs, signature, règles de conservation, créneaux officiels, remises, paiements, import des fiches papier, XLS/CSV, constitution des groupes et affectations. Les contenus éditoriaux des sept pages gardent leur circuit existant ; ce catalogue ne constitue pas un nouvel éditeur de toutes les pages.

## État hébergé du 18 octobre

Le paquet limité V2 est installé le 18 octobre à 22 h 47 (Paris) : catalogue, formulaire fictif et bandeau, 38 fichiers vérifiés (12 différents). Core hébergé 11.4.8 et OAuth 6.1.1 conservés ; local 11.4.10. Sauvegarde/restauration privée vérifiée (27 449 fichiers, 62 tables, démarrage Drupal), puis 11 contrôles sur cette copie réussis. Les sept pages, trois comptes et le dossier existant sont conservés. Basic répond 401, aucune production ni collecte réelle modifiée. Catalogue encore non publié : connexion Bureau/OAuth, publication de recette puis parcours navigateur à réaliser. [Preuve structurée](../data/v2-preproduction-livraison.json).

## Courriels réels de recette — 18 octobre

Le responsable autorise des destinataires réels explicitement désignés en préproduction. Le réglage privé `tcl_bureau_recipe_mail` contient `enabled`, `allowed_recipients` et `registration_to` ; aucun destinataire personnel n’est versionné. Hors localhost et `preprod.tclongages.fr`, ce réglage est ignoré. Les informations personnelles restent fictives ; une adresse réelle est acceptée uniquement si elle est dans cette liste.

Après dépôt réussi, une notification au club reprend le dossier fictif. Le demandeur ne reçoit pas d’accusé. Un verrou et un état privé par numéro de dossier empêchent les renvois sur double soumission ; un résultat incertain n’est pas réessayé automatiquement. L’enregistrement est conservé si le transport échoue. `accepted` signifie seulement acceptation par le serveur, jamais réception en boîte.

Configurer uniquement `system.mail.interface.tcl_bureau_registration` et `tcl_bureau_account_invitation` sur `php_mail` pour la cible autorisée. Le transport global nul reste inchangé. En local, les essais utilisent `test_mail_collector` sans réseau. Le transport nul ne peut pas produire de réussite dans ces parcours.
