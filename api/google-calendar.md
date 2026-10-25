---
project: TC_Longages
document_type: integration-guide
title: Agenda partagé Google sur le site et en aperçu local
status: active
version: git
created: 2026-10-05
updated: 2026-10-25
owner: jpdandin
tags: [agenda, google-calendar, iframe, local]
---

## Écritures de recette V2 — 25 octobre

Implémentation en cours de qualification. `GoogleCalendarConnection` réutilise le client privé du club et son URI de retour existante, avec un état/PKCE et une collection de jetons distincts de Google Contacts. Le seul droit métier demandé est `calendar.app.created` : création d’un calendrier secondaire et gestion de ses événements. Aucun droit sur tous les calendriers, aucune ACL publique, aucun accès Gmail. Compte vérifié : tclongages@gmail.com.

L’administrateur ouvre `/bureau/agenda-google`, accorde le droit puis crée « TC Longages — Recette V2 ». L’ID est celui renvoyé par cette création, jamais saisi dans un formulaire. Le site vérifie avant chaque écriture cet ID, le nom, le fuseau Europe/Paris et un marqueur aléatoire privé. Une création dont la réponse est incertaine reste bloquée pour réconciliation manuelle, sans tentative automatique risquant un doublon.

Après création, les prochains enregistrements `EventRepository` transmettent une série Google puis enregistrent la confirmation locale. Les séries anciennes ne sont pas importées automatiquement. Les identifiants déterministes et une comparaison du contenu permettent de reprendre un envoi dont la réponse s’est perdue. Un échec Google conserve la saisie et empêche d’annoncer un succès local. Une modification externe est refusée sans écrasement ; `If-Match` protège la mise à jour entre lecture et écriture. Les changements Google ne sont pas importés automatiquement : édition courante depuis le site uniquement, vue locale du dernier état confirmé. Une suppression ou un conflit externe demande une réconciliation.

Récurrences : RRULE et EXDATE, fuseau Paris, fin inclusive bornée. Annulation réversible : titre Google préfixé `[ANNULÉ]`, transparence libre, sans suppression distante. Sans participants invités, rappels désactivés, `sendUpdates=none`. Les inscriptions de participants et les communications restent distinctes ; aucune donnée d’adhérent transmise à Calendar.

Stockage privé : `tcl_bureau.google_calendar` contient `grant` chiffré avec une clé dérivée du sel Drupal, `calendar` (ID/marqueur) et éventuellement `creation_pending`. Les séries conservent `google` (ID cible/événement, empreinte du contenu confirmé, date). Secrets OAuth existants hors Git dans `tcl_member_contacts`. Aucun nouveau secret demandé dans le chat. Retour partagé aiguillé uniquement par l’état de session propre à Calendar ; jeton Contacts non remplacé.

Tests : `tests/google-calendar-local.php` avec transport Google simulé, raccordé à la recette CI Bureau ; tests Contacts et événements existants conservés. Autorisation, création et recette réelles restent à confirmer. Le mode test OAuth Google peut nécessiter une nouvelle autorisation.

Sources officielles : [portées](https://developers.google.com/workspace/calendar/api/auth), [création de calendrier](https://developers.google.com/workspace/calendar/api/v3/reference/calendars/insert), [événements](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert), [récurrences](https://developers.google.com/workspace/calendar/api/guides/recurringevents).


# Agenda partagé Google

## Périmètre actuel — 7 octobre

Le responsable fournit le code d’intégration d’un agenda partagé et demande
son intégration locale dans le site, avec la nouvelle photo d’accueil.
La [PR #17](https://github.com/jpdandin44/TC_Longages/pull/17) porte ce lot.
La PR #15 ayant été fusionnée, la branche agenda est réunie avec `main` et
la PR est remise sur cette base. La PR #16 concerne le cockpit ; sa validation
n’est pas une condition préalable à la revue du site.

Les événements sont créés et modifiés dans Google Agenda par les personnes
autorisées. Le navigateur les consulte au chargement : aucune clé API, copie
d’événements, synchronisation serveur ou formulaire de gestion n’est ajouté.
Aucun compte, événement ou droit Google n’est modifié par ce lot.

## Sources de vérité et deux usages du composant

[config/agenda-local.json](../config/agenda-local.json) conserve l’URL fournie,
le statut `local-preview-only` et `sharingReviewed: false`.
[calendar-preview.mjs](../scripts/calendar-preview.mjs) contrôle l’origine HTTPS,
le chemin d’intégration, l’ID d’agenda partagé et le fuseau Europe/Paris.
Il dérive une vue Mois en français et le lien d’ouverture Google.

Le modèle [officiel-pages.mjs](../src/officiel-pages.mjs) produit une seule iframe
titrée sur Calendrier, de largeur 100 %, avec un lien de secours. L’aperçu explicite
prime sur la configuration officielle pour cette construction seulement.
Ses pages et son manifeste sont générés dans `.local/agenda-preview/` et
`.local/agenda-preview-manifest.json` ; l’archive officielle refuse ce manifeste.

Le composant officiel déjà intégré dans `main` reste disponible :
[config/officiel.json](../config/officiel.json) conserve `calendarEmbedId: null`
et `calendarSharingReviewed: false`. Quand l’ID et son partage auront été
qualifiés, ce composant affichera l’agenda dans la construction officielle,
en français et à l’heure de Paris. La construction locale ne modifie ni cette
configuration, ni les sorties et le manifeste ordinaires `officiel/`.
Les générateurs n’autorisent que l’iframe validée Google Calendar ; les
autres ressources externes restent refusées.

Le [suivi de branche](../docs/suivi-chantier/suivi-chantier.json) est une projection
versionnée du lot `tcl-v1-agenda-local` depuis le suivi opérationnel conservé
sous `.worktrees/support-v1`. Les décisions et brouillons des autres lots
restent dans leur source opérationnelle. Le code de ce lot se trouve dans
`feat/v1-agenda-local`, checkout `.worktrees/agenda-local`.

## Préparation de la préproduction privée

La demande du 7 octobre autorise le passage du site en recette protégée.
`npm.cmd run drupal:preproduction:build` dérive les sept modèles de l'aperçu
validé, avec vue `MONTH`, vers `.local/drupal-public-candidate/site-pages/`.
Le manifeste précise `targetHost: preprod.tclongages.fr`,
`productionAllowed: false` et `calendarSharingReviewed: false`.
Les octets sources sont comparés au manifeste d'aperçu avant construction ;
un cadre est autorisé uniquement sur Calendrier. La construction ne livre
rien sur l'hébergement. Le partage Google public demeure à qualifier.

Avant installation : sauvegarde et restauration vérifiées, rapprochement
avec les textes Drupal enregistrés, conservation du transport support,
puis recette de la cible. La PR #17 reste la même ; aucune PR supplémentaire
n'est créée pour cette livraison.

## Utilisation locale

Depuis le checkout du lot :

```powershell
npm.cmd run agenda
```

Ouvrir [Calendrier](http://127.0.0.1:4184/calendrier.html) ou
[l’accueil avec la nouvelle photo](http://127.0.0.1:4184/index.html).
`npm.cmd run agenda:build` régénère les pages sans lancer de serveur.
Celui-ci écoute uniquement sur la boucle locale ; seule la route Calendrier
permet un cadre Google Calendar. Les écritures et chemins techniques restent
inaccessibles depuis cet aperçu.

Les contrôles utilisent les dépendances Node verrouillées, PHP et un environnement
Python local avec `PyYAML` et `jsonschema` :

```powershell
npm.cmd ci --ignore-scripts
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install PyYAML jsonschema
$env:PATH = (Join-Path (Get-Location).Path '.venv\Scripts') + ';' + $env:PATH
npm.cmd run check
```

## Édition des textes Drupal et photo

La source `Images_Photos/Image_terrain_OK.png` est reprise sans transformation.
Elle porte la page d’accueil au-delà de 3 Mo. La première CI a révélé que
l’extraction précédente de son contenu principal dépassait la limite PCRE.
`PublicPageText` utilise désormais les positions des balises et conserve
les octets de la photo au rendu. Le [contrôle de régression](../tests/public-page-text.php)
vérifie la vraie page, ses rubriques, sa légende, l’échappement des titres et
le rendu sans édition. La CI Drupal contrôle aussi l’édition dans son
installation jetable. Ces contrôles ne prouvent pas l’installation du
correctif ni la sauvegarde d’une modification sur `preprod.tclongages.fr`.
La qualification de ce problème hébergé reste à terminer avec l’accès Drupal.

## Qualification et suite

Le [reçu](../data/agenda-local-verification.json) conserve les contrôles,
empreintes, captures et l’historique des candidats. L’ouverture de l’agenda
avec son titre et son fuseau a été constatée ; aucun événement n’était affiché
en octobre lors du premier contrôle. Ce constat ne qualifie pas le partage
détaillé ni les droits d’édition.

TBD — contrôler sans session Google les informations visibles par le public
visé, puis recetter une modification d’événement autorisée. Avant raccordement
hébergé, reporter la ressource qualifiée dans la configuration officielle et
vérifier le rendu Drupal avec conservation des textes édités. Les accords
de revue, merge, préproduction et production gardent leur portée distincte.

## Historique du choix d’agenda

Le 5 octobre, l’ancien lien visait `tclongages@gmail.com` et sa consultation
sans connexion refusait l’accès ; le [reçu FFT/Google](../data/fft-sources-verification.json#googleCalendar)
conserve ce constat. Le 6 octobre, le responsable choisissait un agenda dédié
aux événements publics, encore non créé. L’iframe du 7 octobre fournit un
nouvel ID partagé pour l’aperçu local ; elle ne constitue pas une confirmation
supplémentaire de publication des détails. Une catégorie ou une couleur
ne modifie aucun droit de partage. Le raccordement automatique des compétitions
FFT et des présences reste à concevoir.

Référence : [intégrer un agenda dans un site, Google](https://support.google.com/calendar/answer/41207?hl=fr).
