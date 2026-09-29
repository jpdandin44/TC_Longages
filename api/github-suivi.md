---
project: TC_Longages
document_type: integration
title: Lecture des PR GitHub dans le suivi local
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [github, suivi, integration]
---

# Lecture des états de PR

Le [suivi JSON](../docs/suivi-chantier/suivi-chantier.json) définit les PR rattachées à chaque phase dans `pullRequests`. Ce rattachement est manuel et constitue la source de vérité pour choisir les PR affichées. Le champ distinct `pullRequest` indique le candidat soumis à la revue technique.

Le service local interroge en lecture seule l’API publique `GET /repos/jpdandin44/TC_Longages/pulls?state=all`, sans jeton ni droit d’écriture. Il distingue brouillon, ouverte, fusionnée et fermée sans fusion à partir de la réponse GitHub. Les titres et URL d’un autre dépôt sont écartés. Une date de vérification est affichée ; un cache de deux minutes limite les lectures. Une panne laisse les derniers états datés, ou « État à vérifier » si aucune lecture n’a abouti. Le bouton d’actualisation ne modifie pas le suivi.

La route locale `/api/pull-requests` exige le jeton de l’interface et reste limitée à la boucle locale. Elle n’enregistre ni décision ni preuve de CI. Les états GitHub ne remplacent pas les critères, la validation humaine ni l’autorisation séparée de la phase suivante.
