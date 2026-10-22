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

Deux sources affichées distinctement : liste privée de recette autorisée, ou groupe Google Contacts configuré. Toutes les sources restent filtrées par `RecipeMail::allowed()` ; maximum vingt destinataires par tentative. Messages individuels ; aucun Cc ni Bcc collectif. Les contacts hors liste sont exclus explicitement. Affiche transmise par lien vers l'actualité seulement si cette dernière est publiée sur le site ; aucune pièce jointe automatique. Le lien éditorial saisi est conservé. Le courriel inclut désormais l’entrée d’inscription lorsque l’actualité est associée à une occurrence ouverte ; le destinataire y demande son lien personnel, sans secret dans le message partagé.

Clé de transport `tcl_bureau_member_communication`, explicitement PhpMail en préproduction, TestMailCollector uniquement en local. NullMail ne vaut jamais succès. Préfixe RECETTE PRÉPRODUCTION conservé. Registre privé `tcl_bureau.member_mail`, clé actualité/révision/hash-adresse ; statut pending avant appel, accepted ou failed après. Aucun réessai automatique, y compris résultat incertain. Le suivi est visible lors d'une nouvelle préparation. Accepté par le serveur ne prouve pas réception.

## Google Contacts : configuration privée requise

Compte confirmé par le responsable le 22 octobre : `tclongages@gmail.com`. Libellé confirmé et observé dans Google Contacts : `Adhérents_2026_2027`, 21 contacts. Cette lecture navigateur ne qualifie pas l’accès People API du site. Conditions d’accueil, People API et règlement OAuth acceptés par le responsable le 22 octobre. Projet `tc-longages-preproduction`, People API activée et configuration OAuth créée, vérifiés dans Chrome. Client Web préparé avec retour exclusif `https://preprod.tclongages.fr/bureau/contacts-google/retour` ; création personnelle du client et fichier privé attendus. Consentement de lecture et qualification People API du groupe restent à effectuer. Aucun contact importé dans Drupal, aucune campagne envoyée. Le connecteur Google du chat ne configure pas Drupal.

Dans les paramètres privés hors web et hors Git : `tcl_member_contacts` avec enabled, client_id, client_secret, account et label. L’ancien raccordement explicite refresh_token/group reste compatible pour les fixtures. Ne pas inscrire leurs valeurs dans les documents. Autorisation à demander uniquement pour `https://www.googleapis.com/auth/contacts.readonly`. Le code ne crée/modifie aucun contact et n'accède pas à Gmail.

Jeton rafraîchi côté serveur via oauth2.googleapis.com/token ; groupe lu par GET People API, puis lots de 200 personnes maximum, uniquement names/emailAddresses. Limite 500 contacts, refus du groupe incomplet ou trop grand, délais bornés, pas de redirection HTTP. Adresse principale sinon première adresse ; invalides écartées et doublons dédupliqués. Aucun cache de jeton dans le navigateur ; erreurs prestataire non exposées.

Sources : [contactGroups.get](https://developers.google.com/people/api/rest/v1/contactGroups/get), [people.getBatchGet](https://developers.google.com/people/api/rest/v1/people/getBatchGet).

## Contrôles

`tests/event-participation-http.py` : vrai Drupal local et OAuth fictif, réponses, totaux, remplacement, conflit, clôture, annulation et droits. `tests/member-mail-local.php` : Google simulé, collecteur mail, filtrage, doublons, révisions, isolation production. `tests/member-mail-http.py` : formulaire natif, aperçu, confirmation et absence de renvoi. Inclus dans `scripts/bureau-oauth-recette.ps1`. Aucune donnée réelle ni envoi externe dans ces tests.

## Entrée inscription depuis une actualité — 22 octobre

Dans le suivi des participants, choisir « Actualité avec inscription » parmi les publications du site, puis enregistrer l’ouverture. L’association `registration_post` est stockée dans l’occurrence privée. Une association ambiguë sur plusieurs dates n’affiche pas de lien : créer une actualité par date. Retrait/archivage de l’actualité, annulation, clôture ou date supprimée rendent l’entrée indisponible.

`/actualites/{post}/inscription` présente l’événement et demande un nom fictif et une adresse de recette. Formulaire avec session/CSRF, contrôle Origin si présent, dix tentatives par IP/heure et une demande par adresse/quinze minutes. Réponse générique sans divulgation de l’éligibilité. Seule une adresse autorisée reçoit le lien personnel via le transport de recette existant. La demande ne vaut pas participation ; il faut ouvrir le courriel et enregistrer sa réponse. Un nouveau lien remplace le précédent en conservant la réponse. L’email est saisi par le demandeur ; cette étape ne consulte pas Google Contacts.

Accueil : miniature de l’image ou première page PDF, lien vers l’actualité, bouton d’inscription si disponible. Le bloc de liste du bas est retiré. L’article affiche le même bouton. Un aperçu mail préparé devient invalide si l’entrée change ou ferme. Le lecteur PDF existant reste local, aucune nouvelle bibliothèque.

Tests : `event-signup-local.php` (14 contrôles), `event-signup-http.py` (12 contrôles), collecteur local uniquement. La réception réelle reste distincte des preuves locales.

## Connexion administrateur Google — 22 octobre

Code livré en préproduction ; connexion Google réelle en attente des identifiants et du consentement : `/bureau/contacts-google`, accessible uniquement à l’administrateur avec preuve OAuth Bureau ; lien depuis Gestion des comptes. Le formulaire POST protégé amorce une autorisation Google avec état de session à usage unique (dix minutes), identité administrateur, empreinte de configuration et PKCE S256. Le retour HTTPS est fixe et limité à la préproduction. Les portées demandées sont openid, email et contacts.readonly : l’adresse vérifiée doit être celle du club ; aucun accès Gmail.

Le retour recherche le libellé exact dans People API (dix pages de cent groupes maximum), refuse absence, ambiguïté ou plus de 500 contacts. Le jeton de renouvellement est chiffré avec sodium secretbox et une clé dérivée du sel privé Drupal, puis stocké dans la collection privée `tcl_bureau.google_contacts`. Aucun jeton en clair dans Git, formulaire ou message. Changement de client/secret/libellé invalide la lecture du jeton. Les erreurs du fournisseur restent génériques. La connexion ne déclenche ni import ni envoi ; le filtrage de recette du canal courriel est inchangé.

Google en mode Test peut faire expirer l’autorisation après sept jours : prévoir une reconnexion. Le passage de l’application Google en publication reste hors de ce lot. Sources : [OAuth Google](https://developers.google.com/identity/protocols/oauth2), [audience de test](https://support.google.com/cloud/answer/15549945).


### Qualification du 22 octobre — complément

État courant et preuves : [reçu Google et comptes](../data/google-accounts-verification.json). Configuration Google privée installée et consentement personnel accordé : connexion et lecture effective vérifiées en préproduction, libellé de 21 contacts. Aperçu Google réussi, 20 exclusions affichées après normalisation/filtrage, zéro destinataire éligible, envoi désactivé. Aucun courriel envoyé par ce canal. Bouton de réinitialisation séparé et stylé livré, CI 39840747992 réussie ; aucun nouvel envoi pour cette retouche. Production inchangée.


Complément de recette du 22 octobre : adresse famille ajoutée au libellé sur demande explicite (22 contacts). Un courriel Google-source envoyé uniquement à cette adresse, accepté par le serveur ; réception à confirmer. Lien vers l’inscription fictive vérifié. Voir le reçu Google et comptes ; aucune modification de production.


## Personnalisation des communications — 22 octobre

Les courriels de communication peuvent porter trois URL personnelles `/evenements/reponse#JETON:yes|no|maybe`. Le jeton reste en fragment puis est effacé de la barre ; la présélection est appliquée uniquement au premier contexte. Le contexte POST ne modifie pas la réponse ; seule la confirmation avec CSRF, révisions et total 1–99 si Oui écrit. L’aperçu reste sans jeton ; la génération lors de chaque nouvel envoi invalide l’ancien lien en conservant la réponse. HTML échappé, alternative texte, transport existant et garde de recette inchangés.
