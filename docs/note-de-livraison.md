---
project: TC_Longages
document_type: release-note
title: Note de livraison du site public TC Longages
status: active
version: git
created: 2026-10-05
updated: 2026-10-05
owner: jpdandin
tags: [livraison, drupal, production]
---

# TC Longages V1 — livraison publique

Le site est ouvert sur [tclongages.fr](https://tclongages.fr/) et
[www.tclongages.fr](https://www.tclongages.fr/) le **5 octobre 2026 à 18:14:45 UTC**
(20:14:45 à Paris), après l'accord explicite `TCL-PROD-EXECUTION-20261005`.
Cette note restitue le [reçu de publication](../data/industrialisation-verification.json#publication).
Le reçu structuré fait autorité pour les identités, observations et limites ;
le [suivi canonique](suivi-chantier/suivi-chantier.json) conserve les décisions humaines.

## Livrable et identité

- Version applicative : **TC Longages V1**, site public Drupal dédié.
- Source : `49b4ef7b975b8edff4308373732033630582da6b`, issue de la [PR #5](https://github.com/jpdandin44/TC_Longages/pull/5).
- ZIP promu : SHA-256 `14c270431af12397eb882c1a3e029e05f4beaf5e2dfc0f728bbc31aafcf5f86a`, sans reconstruction.
- Construction : [Action GitHub vérifiée](https://github.com/jpdandin44/TC_Longages/actions/runs/37151085511).
- Hébergement : racine `/home2/daje5127/tcl-production/releases/14c270431af12397/drupal/web`,
  base distincte `daje5127_tclprod`. Paramètres et sauvegardes hors de la racine publique.
- Configuration [Apache de production](../config/production-https.htaccess) distincte du ZIP :
  modèle fermé à 0, drapeau actif à 1 après ouverture. Les empreintes du modèle,
  du `.htaccess` original et du fichier effectif sont consignées dans le reçu.

## Périmètre livré et contrôlé

| Page | Adresse |
|---|---|
| Accueil et club | `/` et `/index.html` |
| Compétitions | `/competitions.html` |
| Calendrier | `/calendrier.html` |
| Disponibilités | `/disponibilites.html` |
| Équipes | `/equipes.html` |
| Espace en attente | `/espace.html` |
| Contact | `/contact.html` |

Les sept réponses anonymes correspondent exactement aux pages du candidat.
Dix-neuf lectures HTTP externes vérifient ces pages, les deux hôtes, la redirection
HTTP vers HTTPS, les cinq refus de chemins privés, la connexion, les refus
d'inscription et d'administration anonymes, ainsi que la fermeture de préproduction.
Les pages publiques répondent 200 et leur consigne de non-indexation est retirée.
Connexion et réponses Drupal protégées restent non indexables ; les fichiers privés
sont refusés 403 avant Drupal. Les réponses 403 natives Apache n'émettent pas
l'en-tête applicatif de non-indexation ; aucun contenu privé n'est servi.

PHP HTTP 8.3.33 et Drupal 11.4.8 sont confirmés, avec le français par défaut.
Le dernier contrôle d'intégrité revérifie les 26 686 fichiers applicatifs inchangés
du manifeste et identifie séparément le seul `.htaccess` d'hébergement modifié.
La base contient les 43 tables restaurées et une table de cache native ajoutée
par la reconstruction Drupal. Maintenance désactivée en production, inscription
libre désactivée et courriels automatiques neutralisés. Les diagnostics sont retirés.
Affichage bureau et sept pages à 390 pixels vérifiés, menu mobile et images visibles
inclus ; aucun débordement horizontal constaté. Aucun formulaire réel n'est envoyé.

## Réserves acceptées pour V1

Calendar et Forms ne sont pas raccordés ; les contenus des équipes restent à
compléter. Le contact est une prévisualisation, avec liens de courriel disponibles,
sans transport serveur qualifié. L'espace Bureau/Capitaine reste un écran d'attente.
Comptes et droits limités par équipe sont reportés en V2. Ces fonctions ne sont pas
annoncées comme actives par cette publication.

## Sauvegarde et retour arrière

La sauvegarde de référence du 5 octobre est intégralement vérifiée puis restaurée
en copie privée : 26 819 fichiers, 43 tables SQL et deux démarrages Drupal en français.
La sauvegarde fraîche précédant le raccordement est conservée dans
`/home2/daje5127/tcl-production/private/deployment-backups/20261005T171758Z` ;
son intégrité est contrôlée. Ce dernier dump n'a pas été réimporté séparément.
Le [reçu de sauvegarde](../data/industrialisation-verification.json#backup) distingue
ces deux preuves.

Pour refermer cette V1, remettre `TCL_PUBLIC_INDEXING` à 0 dans les paramètres
privés et `TCL_APACHE_PUBLIC_INDEXING` à 0 dans le fichier Apache effectif,
puis réactiver la maintenance native Drupal. Vérifier anonymement le refus 503
et la non-indexation. Reconstruire les caches avec la commande native Drush
`cache:rebuild` dans la seule racine de production, sans réinstallation ni import SQL.
Le modèle Apache versionné conserve toutes les directives Drupal originales.

Si un retour du routage est nécessaire, remettre la racine officielle dans cPanel
sur `/home2/daje5127/public_html`. Ce retour réel a été effectué à 17:22 UTC :
les deux noms ont rendu 200 avec l'empreinte attendue de la page d'attente.
Drupal a ensuite été reconnecté sous maintenance avant cette publication.
Conserver les bases et les copies ; le retour de racine ne restaure pas de nouvelles
données SQL et ne doit pas être confondu avec un import.

## Suite de développement

La préproduction garde sa maintenance et sa base propre. Qualifier l'adaptateur
des mises à jour avant une prochaine version ; ne pas relancer les outils de
première installation ou de restauration sur une base de production existante.
Le backlog V2 porte les comptes, droits par équipe et intégrations qualifiées.
Les améliorations demandées de versioning, cockpit, pilotage/synchronisation
GitHub Actions et notes de version sont consignées dans la [roadmap](../roadmap.md).
Leur automatisation n'est pas installée par cette livraison.

La livraison technique et l'accord de production sont enregistrés sans modifier
les critères, commentaires, confirmations ou statuts des validations humaines.
L'accord ici acquis ne vaut pas déploiement d'un futur candidat V2.
