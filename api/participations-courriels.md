---
project: TC_Longages
document_type: api-integration
title: Participations aux événements et courriels adhérents
status: active
version: git
created: 2026-10-22
updated: 2026-10-22
owner: jpdandin
tags: [v2, evenements, courriels, google-contacts, preproduction]
---

# Périmètre

Implémentation V2 de recette uniquement : drapeau `tcl_registration_recipe_enabled` et hôtes localhost, 127.0.0.1 ou preprod.tclongages.fr. Aucune écriture Google Agenda, aucune importation V3. Déploiement et vérification hébergée suivis séparément dans [les preuves](../data/event-participation-mail-verification.json). Les dates réelles du Beaujolais, son lieu et sa clôture restent TBD ; les tests utilisent un événement explicitement fictif.

## Participations

- Gestion : `/bureau/evenements/{id}/participants?date=YYYY-MM-DD`, rôle Communication et preuve OAuth exigés côté route et service.
- Une réponse par adresse normalisée et occurrence. Le Bureau crée/remplace un lien individuel ; aucun mail automatique depuis ce formulaire.
- Lien `/evenements/reponse#<secret>` : 32 octets aléatoires, SHA-256 seul en stockage privé, index expirable sept jours, fragment retiré immédiatement de la barre d'adresse. Aucun secret dans une query, les journaux applicatifs ou le stockage navigateur.
- POST JSON `/evenements/reponse/context` : token. Retour limité à l'événement et à la propre réponse ; aucune liste ni adresse d'autre participant.
- POST JSON `/evenements/reponse/save` : token, csrf, revision, eventRevision, answer (`yes`, `no`, `maybe`), count (entier 1–99 si oui, zéro sinon). Origin strict, session et CSRF, limite de requêtes, no-store/no-referrer/noindex. Réponse 400 invalide, 403 lien/fermeture, 409 conflit, 429 limite atteinte.
- Le nombre comprend le répondant. Sans réponse est distinct d'Incertain. Toute modification de l'événement demande reconfirmation ; les réponses obsolètes ne sont plus comptées dans les personnes confirmées. La clôture est en fin de journée Europe/Paris ; une annulation interdit toute réponse.
- Stockage `tcl_bureau.event_participation` par événement/date, noms et adresses privés, 500 personnes maximum par occurrence ; `tcl_bureau.event_reply_tokens` index expirable. Verrou partagé avec les écritures d'événements.
- Les anciens liens sont invalidés lors du remplacement ; la réponse précédente est conservée. Aucun export, aucune collecte publique depuis l'accueil dans ce premier lot.

## Canal courriel

Canal `email` : « Envoyer un mail aux adhérents ». Enregistrer ne diffuse rien. Une révision validée courante est obligatoire. `/bureau/communication/{post}/courriel` prépare un aperçu serveur valable quinze minutes, puis impose sélection des destinataires et confirmation personnelle.

Deux sources affichées distinctement : liste privée de recette autorisée, ou groupe Google Contacts configuré. Toutes les sources restent filtrées par `RecipeMail::allowed()` ; maximum vingt destinataires par tentative. Messages individuels ; aucun Cc ni Bcc collectif. Les contacts hors liste sont exclus explicitement. Affiche transmise par lien vers l'actualité seulement si cette dernière est publiée sur le site ; aucune pièce jointe automatique. Le lien éditorial saisi est conservé. L'association automatique événement/courriel et les liens individuels par destinataire restent à compléter.

Clé de transport `tcl_bureau_member_communication`, explicitement PhpMail en préproduction, TestMailCollector uniquement en local. NullMail ne vaut jamais succès. Préfixe RECETTE PRÉPRODUCTION conservé. Registre privé `tcl_bureau.member_mail`, clé actualité/révision/hash-adresse ; statut pending avant appel, accepted ou failed après. Aucun réessai automatique, y compris résultat incertain. Le suivi est visible lors d'une nouvelle préparation. Accepté par le serveur ne prouve pas réception.

## Google Contacts : configuration privée requise

TBD : compte club, libellé exact et consentement OAuth du site. Aucun accès Google réel n'a été utilisé pour les tests. Le connecteur Google du chat ne configure pas Drupal.

Dans les paramètres privés hors web et hors Git : `tcl_member_contacts` avec enabled, client_id, client_secret, refresh_token, group (resourceName `contactGroups/...`). Ne pas inscrire leurs valeurs dans les documents. Autorisation à demander uniquement pour `https://www.googleapis.com/auth/contacts.readonly`. Le code ne crée/modifie aucun contact et n'accède pas à Gmail.

Jeton rafraîchi côté serveur via oauth2.googleapis.com/token ; groupe lu par GET People API, puis lots de 200 personnes maximum, uniquement names/emailAddresses. Limite 500 contacts, refus du groupe incomplet ou trop grand, délais bornés, pas de redirection HTTP. Adresse principale sinon première adresse ; invalides écartées et doublons dédupliqués. Aucun cache de jeton dans le navigateur ; erreurs prestataire non exposées.

Sources : [contactGroups.get](https://developers.google.com/people/api/rest/v1/contactGroups/get), [people.getBatchGet](https://developers.google.com/people/api/rest/v1/people/getBatchGet).

## Contrôles

`tests/event-participation-http.py` : vrai Drupal local et OAuth fictif, réponses, totaux, remplacement, conflit, clôture, annulation et droits. `tests/member-mail-local.php` : Google simulé, collecteur mail, filtrage, doublons, révisions, isolation production. `tests/member-mail-http.py` : formulaire natif, aperçu, confirmation et absence de renvoi. Inclus dans `scripts/bureau-oauth-recette.ps1`. Aucune donnée réelle ni envoi externe dans ces tests.

## Entrée inscription depuis une actualité — 22 octobre

Dans le suivi des participants, choisir « Actualité avec inscription » parmi les publications du site, puis enregistrer l’ouverture. L’association `registration_post` est stockée dans l’occurrence privée. Une association ambiguë sur plusieurs dates n’affiche pas de lien : créer une actualité par date. Retrait/archivage de l’actualité, annulation, clôture ou date supprimée rendent l’entrée indisponible.

`/actualites/{post}/inscription` présente l’événement et demande un nom fictif et une adresse de recette. Formulaire avec session/CSRF, contrôle Origin si présent, dix tentatives par IP/heure et une demande par adresse/quinze minutes. Réponse générique sans divulgation de l’éligibilité. Seule une adresse autorisée reçoit le lien personnel via le transport de recette existant. La demande ne vaut pas participation ; il faut ouvrir le courriel et enregistrer sa réponse. Un nouveau lien remplace le précédent en conservant la réponse. L’email est saisi par le demandeur ; cette étape ne consulte pas Google Contacts.

Accueil : miniature de l’image ou première page PDF, lien vers l’actualité, bouton d’inscription si disponible. Le bloc de liste du bas est retiré. L’article affiche le même bouton. Un aperçu mail préparé devient invalide si l’entrée change ou ferme. Le lecteur PDF existant reste local, aucune nouvelle bibliothèque.

Tests : `event-signup-local.php` (14 contrôles), `event-signup-http.py` (12 contrôles), collecteur local uniquement. La réception réelle reste distincte des preuves locales.
