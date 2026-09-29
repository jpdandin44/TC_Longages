---
project: TC_Longages
document_type: test-fixture-provenance
title: Sources tarifaires conservées pour les tests
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [tests, tarifs, sources]
---

# Sources tarifaires de test

Ces deux PDF sont des copies binaires intactes des fiches vierges fournies par l’utilisateur. Les documents originaux sont conservés hors du dépôt. La correspondance est :

| Copie dans ce dossier | Nom du document fourni |
|---|---|
| `fiche-ecole-tennis.pdf` | `Design sans titre_20260904_124142_0000.pdf` |
| `fiche-adultes.pdf` | `Fiche d'inscription_adulte V2.pdf_20260904_124527_0000.pdf` |

Les empreintes et références de pages ont pour source [le relevé tarifaire](../../../data/tarifs-inscription.json). Le test [tariffs.test.mjs](../../tariffs.test.mjs) relit les PDF du dépôt et vérifie leurs empreintes ; il ne dépend plus d’un dossier personnel voisin.

Les trois pages ont été relues visuellement le 29 septembre 2026 : modèles vierges, sans données d’adhérent renseignées ni signature. La lecture technique a relevé zéro champ de formulaire, annotation et pièce jointe. Le contrôle du 29 septembre a obtenu du texte extractible ; la mention contraire du relevé est le constat historique du 17 septembre et n’a pas été réécrite comme un résultat courant.

L’adresse et les tarifs portés par ces pièces sont des éléments de source historiques, pas une nouvelle confirmation des coordonnées ou du catalogue du club. Les ambiguïtés tarifaires restent conservées dans le relevé. Ne remplacer ces fixtures que dans le cadre d’une nouvelle revue de provenance et de contenu ; ne jamais y copier une fiche remplie.

Ces sources servent aux tests du dépôt et ne sont incluses dans aucune archive du site. Elles sont consultables avec les sources du dépôt public ; la qualification porte uniquement sur ces modèles vierges.
