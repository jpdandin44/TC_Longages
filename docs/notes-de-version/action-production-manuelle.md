---
project: TC_Longages
document_type: release-note
title: Preparation de l'action de production manuelle
status: in_review
version: git
created: 2026-10-17
updated: 2026-10-17
owner: jpdandin
tags: [release-note, github, production]
---

# Action de production manuelle

## Contenu de cette livraison de sources

- Nouveau workflow manuel uniquement, plan sans hebergement par defaut,
  branche main et SHA precis, confirmation et environnement protege requis.
- Catalogue des candidats et notes de version, laisse desactive et vide.
- Controleur SSH a empreintes verifiees, sans installation distante,
  sans reconstruction du candidat ni publication d'archives privees.
- Controleur de transaction : maintenance native, sauvegarde complete,
  restauration isolee exigee, bascule code, controles et retour code sur panne.
- Journal persistant, verrou serveur, refus de replay ou d'etat incertain,
  conservation des donnees SQL et de la maintenance anterieure.
- Tests locaux de garde-fous, succes, sauvegarde/restauration sur fixture,
  interruptions, protection des donnees et nettoyage de la cle temporaire.

## Exclusions et limites

**L'adaptateur Drupal/o2switch reste a implementer et qualifier.** Aucune
execution hebergee, aucun secret configure, aucun environnement GitHub cree,
aucune fusion ni livraison. Cette note ne modifie pas la version publique V1.
La preproduction actuelle, Bureau, adherents, communication, equipes et repas
ne changent pas. Les mises a jour de schema et imports de configuration ne
sont pas pris en charge par cette premiere orchestration.

Les [instructions et preconditions](../../workflows/production-manuelle.md)
expliquent la suite. Les preuves de ce lot se trouvent dans le
[recu de verification](../../data/production-action-verification.json).
