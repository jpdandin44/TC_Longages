---
project: TC_Longages
document_type: integration-contract
title: Contrat du futur adaptateur prive de production
status: draft
version: git
created: 2026-10-17
updated: 2026-10-17
owner: jpdandin
tags: [drupal, o2switch, sauvegarde, integration]
---

# Adaptateur prive de production

**Non implemente et non installe par cette PR.** Le controleur
[production_operator.py](../scripts/production_operator.py) orchestre une classe
`Backend(directory)` exposee par `hosting_adapter.py`. La fixture des tests
n'est pas cet adaptateur et ne doit jamais etre installee sur un serveur.
Ce contrat constitue la source du raccordement restant, pas une preuve de
fonctionnement Drupal/o2switch.

## Installation future

Emplacement fixe hors web :
`/home2/daje5127/tcl-production/private/github-deployment/`.
Compte Linux `daje5127`, dossier 0700, fichiers 0600, aucun lien symbolique.
Le profil prive `profile.json` doit contenir `enabled: true` et
`approvedWorkflowSha`, SHA complet du workflow examine. `catalog.json` est
une copie exacte semantiquement du catalogue Git active apres recette.
L'entree SSH controle le SHA-256 de l'operateur, puis celui de l'adaptateur.
Ces empreintes et le catalogue lient l'execution a une implementation revue.

L'adaptateur doit etre autonome ou verifier toutes ses dependances avant
usage ; aucune dependance locale non epinglee ne doit pouvoir changer le
comportement. Aucun secret dans le catalogue versionne. Les acces SQL et
reglages Drupal demeurent dans les fichiers prives du serveur existants.

## Contrat Backend

Les champs attendus et leur verification sont implementes dans le controleur.
Toutes les methodes doivent lever une exception en echec ou resultat ambigu.
Elles doivent borner duree, tailles et chemins, capturer les sorties de leurs
sous-processus et ne jamais ecrire de secret dans stdout/stderr.

| Methode | Effet et preuve obligatoire |
| --- | --- |
| `preflight(request)` | Lecture seule : identifier la vraie version active, verifier tous les octets du candidat et sa provenance, la note, la preuve hebergee designee par `qualificationSha256`, les racines et identites, l'absence de migration et la compatibilite du retour. Verifier espace disque et cible isolee. Retourner l'etat natif de maintenance. |
| `enter_maintenance()` | Activer la maintenance native, vider les caches necessaires et verifier le refus des ecritures y compris sessions autorisees, taches de fond et autres producteurs. Idempotent. Aucun remplacement du reglage par une surcharge permanente. |
| `backup(run_id)` | Creer une nouvelle sauvegarde privee complete fichiers, parametres, uploads et SQL coherent. Relire les archives et calculer leurs empreintes. Retourner `id`, `createdAt` Unix, `sqlSha256`, `filesSha256`, `integrityVerified`, `complete`. Aucune rotation/destruction implicite. |
| `restore_verify(backup)` | Restaurer exactement ces empreintes vers un dossier et une base dedies, distincts des sites actifs, demarrer Drupal avec tous les envois neutralises, comparer les contenus. Renvoyer les memes identifiants/empreintes et les preuves exigees. Ne jamais restaurer dans la base de production ou la preproduction active. |
| `switch_code(request, backup)` | Promouvoir le ZIP deja qualifie sans reconstruction. `rollback` exige que `backupId` designe une sauvegarde propre a ce site, contenant exactement le code cible du catalogue. Ne toucher ni schema, comptes, contenus, uploads, chargeurs, secrets, protections HTTPS ni racines actives. Exclure tout retour au coeur vulnerable 11.4.8. |
| `health(archive, maintenance)` | Verifier l'inventaire reel, le demarrage Drupal, HTTP/HTTPS, accueil, agenda, ressources, protections, conservation metier/configuration/preproduction et etat natif attendu. Ne pas envoyer de courriels ni publier de donnees de test. |
| `finish_maintenance(previous)` | Restaurer l'etat natif observe avant l'operation, sans ouvrir un site initialement ferme, et liberer les autres blocages d'ecriture temporaires. |
| `restore_code(backup)` | Restaurer uniquement les fichiers de code verifies de la sauvegarde de cette tentative, apres bascule partielle ou controle en echec. Conserver SQL et nouveaux fichiers metier, garder la maintenance. |

Les champs de preuve ne sont **jamais des constantes de succes**. Une archive
lisible ne prouve pas une restauration SQL, et SQLite ne qualifie pas MariaDB.
Les preconditions de la premiere installation dans `restore_production.py`
ne conviennent pas a une production deja ouverte. Les anciens recus V1
restent historiques et ne qualifient pas ce nouvel adaptateur.

## Conservation et incidents

Conserver les sauvegardes sous une racine privee de production. Prevoir une
copie hors hebergement selon une procedure distincte et qualifiee ; aucune
copie contenant les donnees du club dans les artefacts du depot public.
La retention, le quota et le chiffrement de cette seconde copie restent TBD.

Les journaux `journals/<runId>.json` et diagnostics `.log` sont prives et
persistants. Un journal interrompu, un retour non confirme ou un retour code
reussi en maintenance bloque les executions suivantes jusqu'a inspection.
Le controleur ne propose ni purge du journal ni restauration SQL de production.
Un incident avec schema incompatible exige un autre plan de recuperation,
pas une case de confirmation supplementaire dans cette action.
