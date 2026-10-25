---
project: TC_Longages
document_type: operational-evidence
title: Opérateurs de la recette inscription en préproduction
status: archived
version: git
created: 2026-10-18
updated: 2026-10-18
owner: jpdandin
tags: [preproduction, recette, sauvegarde]
---

# Opérateurs de la recette inscription en préproduction

Ces sources conservent les opérations ponctuelles exécutées le 18 octobre. Elles sont liées à un paquet, une sauvegarde et une restauration précis ; elles ne constituent ni un déploiement générique ni une autorisation de les relancer. Les marqueurs de collision et empreintes sont intentionnels. Une nouvelle livraison exige un nouveau cadrage et des preuves actuelles selon le protocole commun.

- `restore.py` : copie des 62 tables actives dans le préfixe de restauration privé, comparaison des lignes et schémas, copie privée des cinq fichiers d'identité et isolement du runtime. Les anciennes copies SQL sont conservées. Le premier essai s'est arrêté avant toute écriture SQL car ces anciennes tables n'avaient pas encore été distinguées ; cette source contient l'inventaire corrigé.
- `stage.py` : vérifie le ZIP et ses 38 fichiers, les installe dans la copie privée uniquement et reconstruit son cache. Core reste 11.4.8.
- `rehearsal.php` : 11 contrôles dans cette seule copie CLI et son préfixe. Le double OAuth est limité au processus de test ; aucune preuve de connexion OAuth réelle n'en est déduite.
- `deliver.py` : préparation du retour fichier/réglage, comparaison avec sauvegarde, contrôle du contenu avant livraison, installation avec maintenance temporaire préproduction, vérification des empreintes et de Basic, conservation des données. En cas d'échec, retour du code et du réglage sans restauration SQL sur les saisies actives.

Entrées : paquet privé `tcl-v2-recipe-patch.zip`, sauvegarde `20261018T203227Z`, restauration `restore-_vypl584`, réglages privés déjà présents. Dépendances : Python 3.6, PHP/Drush, MariaDB et les helpers existants vérifiés `backup_site.py`, `restore_backup_files.py`, `support-update-hosting.py`. Aucun secret n'est conservé dans ces sources ; les secrets requis restent exclusivement dans le stockage privé de la préproduction.

Sorties : reçus privés de restauration, répétition, plan et livraison ; synthèse sans secret dans [le reçu versionné](../../data/v2-preproduction-livraison.json). Une sauvegarde complète SQL/fichiers précède les opérations ; la copie SQL active complémentaire a été restaurée et comparée. Aucun chemin de production n'est cible d'écriture. Aucune migration de schéma ni publication de catalogue n'a été exécutée sur la cible active.

Le [contrat d'inscription](../../api/inscription-recette.md) décrit le comportement et les limites. La recette navigateur Basic/OAuth et la publication depuis le Bureau restent à réaliser. Les opérateurs sont des preuves d'exécution, pas une validation humaine de la V2.
