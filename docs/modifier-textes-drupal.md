---
project: TC_Longages
document_type: user-guide
title: Modifier les textes du club dans Drupal
status: in_progress
version: git
created: 2026-10-06
updated: 2026-10-06
owner: jpdandin
tags: [drupal, edition, contenus, v1, preproduction]
---

# Modifier les textes du club

## Utilisation

1. Se connecter à Drupal sur l'instance concernée.
2. Ouvrir **Contenu → Pages du club** : [recette locale](http://127.0.0.1:4183/admin/content/tcl-pages)
   ou [préproduction](https://preprod.tclongages.fr/admin/content/tcl-pages).
3. Cliquer **Modifier** en face de la page.
4. Modifier le titre, le texte de présentation ou un champ dans **Rubriques et légendes**.
5. Décrire la modification dans le commentaire de révision puis **Enregistrer**.
6. Utiliser **Voir** pour contrôler la page. **Révisions** conserve les versions précédentes.

Enregistrer un contenu publié change cette instance immédiatement. En préproduction,
cela ne modifie pas la production. Les traductions Drupal existantes déterminent les
libellés natifs ; la recette locale anglaise affiche aussi Save et Revisions.
La légende correcte est **Le court de Longages · photo fournie par le club**.
Elle est vérifiée dans le modèle et la page locale ; le champ Légende permet de la modifier.

## Périmètre de cette édition

Les sept pages sont rattachées à des contenus natifs **Page publique TC**, avec
révisions Drupal. Le mécanisme reprend le travail préparé dans la branche
`feat/drupal-comptes-edition`, complété et recetté dans le correctif support V1.
Les titres de rubriques, textes simples, questions et légendes du contenu principal
sont éditables. Le balisage d'origine reste identique tant qu'un texte n'est pas changé.
Les textes saisis sont échappés : aucun HTML ou script libre n'est exécuté.

Les menus, destinations des liens, images, tarifs structurés, crédits du pied de page
et fonctions des formulaires gardent leurs sources existantes. Cette édition n'est
pas le constructeur Paragraphs du site AVEREO, dont seul le principe de contenus
natifs avec historique a été réutilisé. Aucun compte ni permission AVEREO n'est copié.

## Sources et livraison

Les HTML générés restent les modèles de présentation. Après initialisation, la
base Drupal est la source des textes édités ; `tcl_site.public_pages` rattache les
identifiants des contenus aux modèles. Les textes sont stockés dans le titre,
`field_tcl_intro` et `field_tcl_textes`, présentés par un widget à champs nommés.
Une initialisation répétée vérifie le rattachement et n'écrase aucun texte.
Un changement de modèle incompatible exige un rapprochement explicite avant livraison.

Le correctif est construit par `scripts/package-support-update.py`, depuis un commit
identifié et les pages publiques générées. Le ZIP ne contient ni core/vendor,
ni base, ni paramètres actifs, ni secret. Il complète le Drupal installé ; il ne
réinstalle pas le site. L'empreinte du ZIP est distincte du manifeste Git du candidat.

Le responsable a demandé le dépôt de cette nouvelle version en préproduction le
6 octobre. `support-update-hosting.py` impose cible TC, SHA et empreinte, maintenance,
sauvegarde fraîche, restauration des fichiers et restauration SQL dans un préfixe
de récupération neuf de la seule base de préproduction, puis démarrage du Drupal restauré.
Le préfixe de restauration ne remplace aucune table existante. La copie privée et
les tables de récupération sont conservées pour le retour arrière ; leur nettoyage
ultérieur doit être borné à cette seule tentative et décidé séparément.

La configuration privée reste en capture pour les requêtes web de préproduction.
Un seul essai CLI identifié peut qualifier le transport PHP vers `support@tclongages.fr`.
L'acceptation du transport ne prouve pas la réception : celle-ci doit être constatée
dans la boîte. La production et l'ouverture anonyme de la préproduction sont exclues.

## État et limites

Le [reçu support](../data/support-v1-verification.json) indique le candidat courant,
la sauvegarde, les contrôles et l'installation réellement observée. Une URL dans ce
guide ne prouve pas l'installation. La [procédure de support](signalements-support.md)
conserve le périmètre du formulaire et le fichier bêta-test manquant.
Les modifications éditoriales faites en préproduction ne sont pas automatiquement
recopiées en production : leur promotion demande un plan de contenu distinct,
avec révisions et sauvegarde, avant la publication autorisée.
