---
project: TC_Longages
document_type: local-test-guide
title: Recette mobile et MariaDB du Bureau
status: active
version: git
created: 2026-10-06
updated: 2026-10-06
owner: jpdandin
tags: [bureau, drupal, mysql, recette, sauvegarde]
---

# Recette complémentaire du Bureau

Le [reçu du 6 octobre](../data/reprise-bureau-verification.json) complète les
preuves HTTP [Bureau](../data/bureau-local-verification.json) et
[Communication](../data/communication-local-verification.json), sans les modifier.
Il est rattaché à l'itération V2 du [suivi canonique](suivi-chantier/suivi-chantier.json).

## Mobile et ordinateur

Neuf observations à 390/1 280 pixels : listes, formulaire Adulte/Mineur,
fiche existante et gestion/création des comptes. Aucun champ ne déborde après
correction du minimum intrinsèque des fieldsets. Les quatre champs obligatoires
du dossier gardent leurs libellés : Prénom, Nom, Saison, Date de naissance.
Le responsable légal reste contrôlé au serveur pour un mineur.
Cette revue visuelle ne modifie ni dossier ni compte fictif.

## Base et mise à jour natives

Prérequis Windows : PHP et dépendances du verrou Composer déjà préparés,
Git avec `sh.exe`, port local 33080 libre. Le script télécharge l'archive
[MariaDB 11.4.9 officielle](https://archive.mariadb.org/mariadb-11.4.9/winx64-packages/)
et contrôle son SHA-256 avant exécution. Il ne crée pas de service Windows.

```powershell
./scripts/drupal-mysql-recette.ps1 -PhpPath (Get-Command php.exe).Source
```

Le serveur écoute seulement `127.0.0.1:33080`. Chaque exécution crée un
répertoire serveur et deux bases fictives nouvelles ; aucun SQL réel n'est importé.
Réglages et secrets restent dans `.local/`, et le multisite généré est ignoré par
Git. Le script refuse un port occupé ou un mapping multisite étranger. Il arrête
son propre serveur en fin de recette, conserve les fixtures privées pour diagnostic,
et ne change pas les settings SQLite du Bureau disponible sur 4182.

Les 26 contrôles passent sur Drupal 11.4.8/PHP 8.4.23/MariaDB 11.4.9 : installation
de Bureau sur un Drupal existant, comptes conservés, dossiers avec accents,
double soumission, refus d'anciennes révisions et des capitaines. Une ancienne
révision Bureau sans Communication est reconstruite dans la seule base jetable ;
`drush updatedb --yes` applique `11001`. Dossiers/comptes sont conservés, permission
ajoutée, JPEG conservé octet pour octet, validation/publication/retrait et répétition
de l'update vérifiés. Cette opération suit la
[séquence native Drupal](https://www.drupal.org/docs/updating-drupal/deploying-a-drupal-update).

La sauvegarde SQL est restaurée dans la deuxième base vide. Les listes et schémas
des 39 tables puis toutes leurs lignes sont comparés, valeurs binaires comprises.
Seul le reçu sans secret est versionné ; SQL, comptes et réglages restent privés.

## CI et limites

Le job `drupal-mysql` de [ci.yml](../.github/workflows/ci.yml) répète cette recette
sur un runner Windows jetable avec PHP 8.3. Les autres jobs conservent leurs
contrôles. Son exécution GitHub doit réussir sur la tête exacte de la PR ; la
préparation YAML et la réussite locale PHP 8.4 ne constituent pas cette preuve.

Le test ne qualifie pas le serveur Apache/o2switch, ses paramètres PHP, les droits
SQL réels, une sauvegarde de l'hébergement ni un retour arrière de production.
La livraison récurrente et le paquet V2 restent à qualifier avant hébergement
autorisé. La [documentation Drupal](https://www.drupal.org/docs/updating-drupal/troubleshooting-database-updates)
préconise de sauvegarder code et base avant une mise à jour.
Agenda public, collecte réelle, récupération par courriel et synchronisation FFT
conservent leurs réserves dans les guides correspondants. Aucun compte réel,
message externe, validation humaine, merge ou déploiement n'est créé par le test.
