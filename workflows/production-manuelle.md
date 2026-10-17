---
project: TC_Longages
document_type: workflow
title: Preparation de la livraison manuelle GitHub
status: in_progress
version: git
created: 2026-10-17
updated: 2026-10-17
owner: jpdandin
tags: [github, production, sauvegarde, retour-arriere]
---

# Livraison manuelle GitHub

## Etat reel et perimetre

**PR de preparation, pas une automatisation exploitable en production.**
Le workflow, ses controles et le controleur de transaction sont implementes.
L'adaptateur Drupal/o2switch `hosting_adapter.py` reste a developper et a
qualifier : le pilote ponctuel du lancement V1 ne doit pas etre reutilise
tel quel pour les futures mises a jour. Les tests de cette PR utilisent des
fichiers et une base SQLite fictifs, sans PHP, MariaDB ni hebergement reel.

Le 17 octobre, la lecture de l'API GitHub du depot public confirme **zero
environnement configure**. Aucun acces SSH, secret, environnement ni regle
de protection n'est cree par ce lot. Le
[catalogue](../config/production-action.json) est desactive, sans candidat ni
empreinte d'operateur. Un merge ne permet donc aucun deploiement.

La V1 et la preproduction ne sont pas modifiees. Les sources beta figees
restent intactes. L'action n'assemble jamais la branche courante en une
nouvelle V1 et ne reconstruit pas un paquet deja qualifie.

## Quatre phases

| Phase | Situation |
| --- | --- |
| Cadrage | Action manuelle, sauvegardes privees, retour code, sans fusion ni livraison pour ce lot. |
| Développement local | Workflow et controleur testes sur fixtures ; PR de preparation a examiner. |
| Préproduction | A venir : implementer l'adaptateur et repeter son fonctionnement dans une copie isolee autorisee, sans remplacer la preproduction actuelle. |
| Mise en production | Bloquee : raccordement, protections, candidat exact, sauvegarde restauree et accord de livraison requis. |

Le suivi canonique reste `docs/suivi-chantier/suivi-chantier.json` du checkout
`competitions-capitaines`, iteration `tcl-action-production-manuelle-20261017`.
Le [recu du lot](../data/production-action-verification.json) est une preuve
de preparation, pas un second registre d'approbations.

## Entrees et sorties

Le workflow [production-manuelle.yml](../.github/workflows/production-manuelle.yml)
n'ecoute que `workflow_dispatch`, depuis `main` au SHA complet examine.
Les entrees passent par des variables d'environnement, jamais par une
interpolation dans une commande shell. Options :

- `plan`, par defaut : verifier le contexte sans contacter l'hebergement ;
- `deploy` : promouvoir le candidat exact du catalogue apres qualification ;
- `rollback` : revenir a un code qualifie, par identifiant de sauvegarde privee.

`approved_sha` identifie le commit du workflow. `release_id` designe une
entree revue du catalogue, avec source, empreinte du ZIP, version active
attendue, preuve de recette et note de version. Un retour utilise lui aussi
une entree specifique, avec les empreintes dans le sens du retour.
`backup_id` est obligatoire uniquement pour ce retour. La confirmation
exacte est `PRODUCTION TC Longages`. Un rerun GitHub est refuse : apres une
panne, examiner le journal avant un nouveau declenchement.

La sortie GitHub ne contient que des etats bornes et identifiants de run/version.
**Aucune archive, base SQL, configuration, cle, inventaire prive ou journal
d'hebergement n'est envoye en artefact GitHub**, le depot etant public.
Les erreurs brutes SSH/Drush ne sont pas imprimees.

## Controles de l'operation future

1. Verifier le catalogue active et l'environnement `tcl-production` :
   approbateur obligatoire et seule branche `main` autorisee. Le premier job
   s'arrete avant toute creation implicite d'environnement. Le second
   reverifie ces protections. Le contournement administrateur doit etre
   desactive dans l'interface et sa revue referencee dans `environmentReview` :
   ce reglage n'est pas expose par l'API REST consultee et n'est donc pas
   pretendu verifie automatiquement.
2. Attendre la revue de cet environnement, verifier aussi l'approbation
   enregistree pour ce run par l'API GitHub, puis ouvrir le canal SSH avec
   une cle dediee et une empreinte serveur verifiee independamment.
3. Verifier les octets de l'operateur prive et de son adaptateur, le catalogue
   prive identique et le SHA de workflow autorise. Aucun transfert de code
   ni installation de l'operateur n'est effectue par l'action.
4. Prendre le verrou heberge, refuser un ancien journal non resolu, verifier
   candidat, version active, compatibilite sans migration, espace disponible,
   isolement et recuperation du code precedent sans regression de securite.
5. Memoriser la maintenance native Drupal, fermer les ecritures, creer une
   sauvegarde complete fraiche et la restaurer en copie isolee. Exiger le
   controle fichiers, SQL et demarrage Drupal sur cette meme sauvegarde.
6. Basculer uniquement le code, verifier le site ferme et la preservation
   des donnees, parametres et preproduction, puis retablir l'etat de maintenance
   anterieur et controler de nouveau le site. Un site deja ferme reste ferme.
7. En echec apres debut de bascule, tenter la restauration du code sauvegarde,
   conserver la maintenance et laisser le workflow en echec. **Aucun retour
   SQL automatique**, meme pour l'option `rollback`.

Le controleur journalise avant chaque effet et synchronise le journal sur
disque. Une coupure SSH, annulation GitHub ou panne machine n'atteste pas un
retour reussi : le prochain run est bloque par le journal incomplet. Le
verrou serveur complete la concurrence GitHub, qui n'annule pas le run actif.
Ne pas supprimer le verrou, les journaux ou les sauvegardes pour forcer une
relance ; une reprise manuelle documentee est necessaire.

## Raccordement restant

TBD avant activation, dans un lot ulterieur avec recette et autorisation :

- Implementer et revoir l'adaptateur selon le [contrat](../api/production-adapter.md).
  Ses preuves doivent venir des octets et operations reels, pas de booleens fixes.
- Qualifier SSH depuis GitHub et le pare-feu o2switch. Aucun assouplissement
  global du pare-feu ni reutilisation de secrets AVEREO n'est prevu.
- Creer `tcl-production`, definir l'approbateur et sa politique de self-review,
  interdire le bypass et limiter les branches a `main` uniquement.
- Configurer dans cet environnement les variables `TCL_SSH_HOST`, `TCL_SSH_USER`,
  `TCL_SSH_PORT` et secrets `TCL_SSH_KEY`, `TCL_KNOWN_HOSTS`, sans les publier.
- Installer l'operateur et l'adaptateur revus en prive, avec leur profil et
  catalogue, permissions 0700/0600. Qualifier leur compte, chemins, capacite
  disque, sauvegardes, cible SQL de restauration dediee et fermeture des ecritures.
- Repeter les echecs, coupures et retours sur une cible isolee, incluant les
  modifications de donnees apres ouverture et l'administration maintenance.
- Ajouter le candidat exact et sa note au catalogue, ses preuves et empreintes,
  puis faire revoir l'activation. Ni V2 complete ni migration de schema ne
  sont couvertes par cette premiere orchestration limitee aux mises a jour du code.

La [documentation GitHub sur les environnements](https://docs.github.com/en/actions/how-tos/deploy/configure-and-manage-deployments/manage-environments)
explique les protections et la creation implicite d'un environnement absent.
La [syntaxe workflow_dispatch](https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax#onworkflow_dispatch)
decrit le declenchement manuel. Ces mecanismes ne prouvent pas leur installation.

## Revue locale

```text
python -m unittest discover -s tests -p test_production_action.py -v
```

Examiner le workflow, les deux controleurs, le catalogue inactif, les tests
et la note de livraison. Il n'existe pas de nouvelle interface web a tester.
Les cases humaines de la PR restent decochees ; la politique de revue reste
en attente tant que le responsable ne les a pas renseignees.
