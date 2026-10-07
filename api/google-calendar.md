---
project: TC_Longages
document_type: integration-reference
title: Agenda partagé Google en aperçu local
status: active
version: git
created: 2026-10-07
updated: 2026-10-07
owner: jpdandin
tags: [agenda, google-calendar, iframe, local]
---

# Agenda partagé Google

## Périmètre actuel

La demande du 7 octobre autorise l’intégration locale de l’iframe fournie. La
page Calendrier utilise le rendu V1 existant et affiche « Les évènements du
club ». Les événements restent créés et modifiés dans Google Agenda par les
personnes autorisées. Aucun événement, droit Google ou compte n’est modifié.

## Sources de vérité et rendu

- [config/agenda-local.json](../config/agenda-local.json) contient l’unique URL
  fournie et le statut `local-preview-only`. `sharingReviewed` reste `false`.
- [Le chargeur](../scripts/calendar-preview.mjs) vérifie l’origine HTTPS,
  le chemin d’intégration, l’agenda partagé, les paramètres et Europe/Paris.
  Il dérive la vue `AGENDA` en français et le lien d’ouverture sans numéro de compte.
- Le modèle [officiel-pages.mjs](../src/officiel-pages.mjs) et sa feuille de
  style produisent une seule iframe titrée, de largeur 100 %, avec un lien de secours.
- Les sorties restent dans `.local/agenda-preview/` ; le manifeste dérivé
  `.local/agenda-preview-manifest.json` est refusé par l’archive officielle.
  La configuration et les sorties ordinaires `officiel/` restent séparées.
- Le suivi opérationnel du cockpit dans `.worktrees/support-v1` porte le lot
  `tcl-v1-agenda-local`. Le [suivi de cette branche](../docs/suivi-chantier/suivi-chantier.json)
  en reçoit une projection dérivée, sur sa photographie Git de départ ; les
  décisions des autres lots restent conservées dans leur source opérationnelle.
  Son code demeure dans la branche `feat/v1-agenda-local`, checkout `.worktrees/agenda-local`.

La PR du lot et son état observé sont référencés dans le reçu et le suivi.
Sa base est `feat/v1-signalement-support`, tête `6696f39` de la
[PR #15](https://github.com/jpdandin44/TC_Longages/pull/15), encore ouverte
lors de la préparation. Cette base isole les changements agenda/photo de ceux
du formulaire. Après fusion de #15, remettre la PR du lot sur `main` et
requalifier son candidat si le contenu change.

## Utilisation locale

Depuis le checkout du lot :

```powershell
npm.cmd run agenda
```

Ouvrir [la page Calendrier](http://127.0.0.1:4184/calendrier.html). La commande
`npm.cmd run agenda:build` régénère l’aperçu sans démarrer le serveur. Le serveur
écoute uniquement sur la boucle locale. Seule sa route Calendrier permet une
iframe de `https://calendar.google.com` ; les autres routes, écritures et
chemins techniques gardent leurs restrictions.

Le navigateur contacte Google pour charger l’agenda. Aucune clé API, synchronisation
serveur, copie des événements ou formulaire de gestion n’est ajouté. La page
dépend de la disponibilité de Google et des droits accordés aux visiteurs.

## Qualification et suite

Les vérifications automatiques utilisent les dépendances Node verrouillées et
un environnement Python local avec `PyYAML` et `jsonschema`. Les dépendances
absentes au premier passage ont été installées sous `.venv/`, sans modifier
le Python global. Pour reproduire les contrôles dans PowerShell :

```powershell
npm.cmd ci --ignore-scripts
python -m venv .venv
.\.venv\Scripts\python.exe -m pip install PyYAML jsonschema
$env:PATH = (Join-Path (Get-Location).Path '.venv\Scripts') + ';' + $env:PATH
npm.cmd run check
```

La nouvelle photo `Images_Photos/Image_terrain_OK.png` est reprise dans ce même
lot local. Sa source et ses règles de conservation figurent dans le README et
l’architecture ; le reçu ci-dessous rattache sa vérification au candidat.

Le [reçu local](../data/agenda-local-verification.json) porte les contrôles exécutés,
les empreintes et les captures. L’ouverture directe de l’agenda a été constatée
avec son titre et son fuseau ; aucun événement n’était affiché en octobre lors
du premier contrôle. Cela ne qualifie ni le partage détaillé, ni les droits d’édition.

TBD — vérifier les droits de consultation du public visé et les informations
exposées, puis recetter une modification réelle autorisée d’événement. Avant
raccordement hébergé, reporter la ressource qualifiée dans la configuration
officielle et vérifier le rendu Drupal avec conservation des textes édités.
La préparation de la PR relève du Développement local. La revue humaine,
le merge, la préproduction et la production conservent leurs accords distincts.

Référence : [intégrer un agenda dans un site, Google](https://support.google.com/calendar/answer/41207?hl=fr).
