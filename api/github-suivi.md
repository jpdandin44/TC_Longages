---
project: TC_Longages
document_type: integration
title: Lecture des PR GitHub dans le suivi local
status: active
version: git
created: 2026-09-29
updated: 2026-10-05
owner: jpdandin
tags: [github, suivi, integration]
---

# Lecture des états de PR

Le [suivi JSON](../docs/suivi-chantier/suivi-chantier.json) définit les PR rattachées à chaque phase dans `pullRequests`. Ce rattachement est manuel et constitue la source de vérité pour choisir les PR affichées. Le champ distinct `pullRequest` indique le candidat soumis à la revue technique.

Le service local interroge en lecture seule l’API publique `GET /repos/jpdandin44/TC_Longages/pulls?state=all`, sans jeton ni droit d’écriture. Il distingue brouillon, ouverte, fusionnée et fermée sans fusion à partir de la réponse GitHub. Les titres et URL d’un autre dépôt sont écartés. Une date de vérification est affichée ; un cache de deux minutes limite les lectures. Une panne laisse les derniers états datés, ou « État à vérifier » si aucune lecture n’a abouti. Le bouton d’actualisation ne modifie pas le suivi.

La route locale `/api/pull-requests` exige le jeton de l’interface et reste limitée à la boucle locale. Elle n’enregistre ni décision ni preuve de CI. La liste générale reste informative. Le contrôle séparé de la PR candidate utilise
`GET /repos/jpdandin44/TC_Longages/pulls/{number}`, puis si nécessaire la comparaison
`GET /repos/jpdandin44/TC_Longages/compare/{source}...{head}`. Le numéro et l’URL doivent
appartenir au dépôt TC ; seuls les commits complets sont acceptés. La PR doit être
fusionnée et son contenu correspondre au candidat qualifié. Les cinq exclusions
fermées du manifeste sont les seules différences acceptées ; une comparaison
incomplète ou divergente est refusée.

Une lecture candidate peut être conservée trente secondes pour l’affichage. Le
serveur exige une nouvelle lecture sans cache lors de **Valider** ; une panne ne
réutilise jamais une preuve ancienne pour accepter. Le détail vérifié est joint à
la décision humaine locale. Le statut candidat affiché vient du même contrôle que
le bouton de validation. `/api/state?refresh=1` renouvelle cette vérification sous
les mêmes protections locales. Aucune route ne fusionne de PR ni ne modifie GitHub.

Les critères, le commentaire et la confirmation personnelle restent requis. Revue
peut ouvrir un dossier dont la PR reste à examiner ; Valider exige sa fusion exacte.
Une validation historique dépourvue de preuve GitHub n’est pas un accord actuel.
La progression de la phase locale suivante remplace l’autorisation de suite ; les
accords de déploiement et d’ouverture restent distincts.
