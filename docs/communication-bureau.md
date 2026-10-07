---
project: TC_Longages
document_type: development-guide
title: Communication du Bureau dans la V2 locale
status: active
version: git
created: 2026-10-05
updated: 2026-10-06
owner: jpdandin
tags: [communication, drupal, bureau, validation, actualites]
---

# Communication du Bureau

La demande du 5 octobre intègre la communication du prototype au Bureau Drupal
V2, liens externes compris. Le lot fonctionne sur [le site local](http://127.0.0.1:4182/).
Il n'est pas déployé sur les domaines officiel ou de préproduction. Le prototype
4174 et ses brouillons navigateur sont conservés, sans import automatique.

## Accès et parcours

Utiliser le compte Bureau de la [recette locale](comptes-et-bureau.md), puis
**Communication** dans le menu des adhérents, ou ouvrir directement
[Communication](http://127.0.0.1:4182/fr/bureau/communication).
L'administrateur peut également gérer ces contenus ; visiteurs et capitaines
n'accèdent ni aux brouillons, ni aux images privées, ni aux actions.

1. **Créer une actualité** : titre, catégorie, texte ou affiche, description
   obligatoire de l'image, lien HTTPS facultatif et choix Ten'Up « Non » par défaut.
2. **Enregistrer le brouillon** : conservation en base, puis revue des aperçus
   Site, Facebook, WhatsApp et ADOC. Aucun envoi ou publication à cet enregistrement.
3. **Valider cette actualité** : relire la révision présentée et cocher la
   confirmation personnelle. Cette action ne publie pas sur le site.
4. **Publier sur le site** : confirmer séparément ; la révision devient visible
   sur l'accueil local et dans [les actualités](http://127.0.0.1:4182/fr/actualites).
5. **Modifier le brouillon** : tout enregistrement retire la publication et
   annule la validation précédente. Une nouvelle revue est requise.
6. **Retirer du site** conserve la révision validée hors publication.
   **Archiver** retire le contenu des vues actives ; **Archives → Restaurer en
   brouillon** le retrouve sans publication ni validation automatique.

Les formulaires contrôlent CSRF, activité du compte et permission avant écriture.
Ouvrir un lien d'action ne modifie rien. Une ancienne fiche ou confirmation est
refusée après changement de révision. Les titres/textes sont rendus comme texte ;
le HTML saisi ne s'exécute pas. Les listes affichent les 100 contenus les plus récents.

## Affiches et conservation

Une image JPEG, PNG ou WebP de 8 Mo et 20 millions de pixels maximum peut être
téléversée. Le serveur conserve en base une copie JPEG sans métadonnées, de
1 600 pixels sur son grand côté et de 500 ko maximum ; transparence blanche.
L'original reste sur l'ordinateur. Les limites PHP locales sont 8 Mo par fichier
et 12 Mo par requête. Les anciens stockages navigateur ne sont pas utilisés.

L'image reste privée jusqu'à publication ; son URL publique devient inaccessible
après modification, retrait ou archivage. Un fichier invalide ou l'absence de
description bloque l'enregistrement et conserve la révision précédente.
La rotation des photographies selon EXIF n'est pas gérée ; examiner l'aperçu.

## Liens et partage manuel

Après validation, **Ouvrir WhatsApp** préremplit titre, texte et lien utile.
Le responsable choisit le destinataire et confirme l'envoi dans WhatsApp.
L'affiche est téléchargée puis jointe manuellement. Facebook du club, ADOC et
Ten'Up disposent de liens explicites. Le message validé peut être sélectionné
et copié ; connexion et saisie finale restent dans le service choisi.
Aucun destinataire, jeton Meta, mot de passe FFT ou statut de livraison n'est
enregistré ; aucune ouverture ou publication externe n'est automatique.

Le contenu ADOC compte texte et lien en unités UTF-16, avec limite prudente de
2 000 d'après [la référence fournie](../api/adoc.md). Un dépassement bloque sa
préparation et son lien depuis cette révision, sans tronquer le contenu ni bloquer
le site et WhatsApp. Le choix Ten'Up est à reporter personnellement dans ADOC.

## Mise à jour et recette

Le [schéma](../drupal/web/modules/custom/tcl_bureau/tcl_bureau.install) ajoute
une table d'actualités et une permission Bureau, sans réinstallation de Drupal.
L'update `11001` et les installations neuves utilisent la même définition.
Dans ce checkout déjà installé, après une sauvegarde SQLite privée :

```powershell
php -c .local/drupal-tools/php.ini drupal/bin/update-v2-communication-local.php
python tests/drupal-communication-http.py
```

Le helper refuse toute base autre que le SQLite privé du checkout. Il ne crée
aucun compte et ne réinitialise aucun mot de passe ou dossier d'adhésion.
Le [reçu](../data/communication-local-verification.json) porte **50 contrôles
HTTP réels réussis**, dont formulaires, accès, images, révisions et restauration.
Les preuves sont rattachées à la V2 dans le [suivi canonique](suivi-chantier/suivi-chantier.json).
Les quatre aperçus ont aussi été examinés dans le navigateur à 1 280 et
390 pixels : logo du club chargé, cartes lisibles et aucun débordement horizontal.
Ce contrôle porte sur Communication. La [recette complémentaire du Bureau](recette-bureau-mysql.md)
qualifie désormais ses écrans mobiles et la mise à jour MariaDB, dont les images
binaires et la restauration intégrale d'une base locale fictive. Les reçus
précédents restent des preuves datées ; ce complément ne les réécrit pas.

## Suite dans les quatre phases

**Cadrage** : communication et partage manuel rattachés à la demande V2.
**Développement local** : module, recette et PR candidate à examiner.
**Préproduction** : qualifier update MySQL, sauvegarde/restauration,
configuration PHP et routage Apache des nouvelles pages et images.
**Mise en production** : après recette et accords propres à V2 ; contrôle
après livraison, reçu, retour arrière et note de version.

TBD : cible hébergée, nouvelles routes Apache et reprise de ses données.
Le test MariaDB local ne remplace pas ces contrôles. Import JSON des anciens brouillons,
éditeur riche et publication automatique externe ne sont pas réalisés dans ce lot.
