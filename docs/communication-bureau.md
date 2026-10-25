---
project: TC_Longages
document_type: development-guide
title: Communication du Bureau dans la V2
status: active
version: git
created: 2026-10-05
updated: 2026-10-25
owner: jpdandin
tags: [communication, drupal, bureau, validation, actualites]
---

## Navigation et personnalisation — 25 octobre

Amélioration livrée en préproduction : navigation en boutons, repère actif dans les communications, statut séparé des actions et champs arrondis avec focus clavier visible. L’éditeur conserve les onglets natifs Drupal, présentés horizontalement sur ordinateur et sur deux colonnes sur téléphone. Le contenu de chaque canal et les règles de validation restent identiques ; changer d’onglet n’enregistre ni ne publie rien. Les champs vides reprennent le contenu commun. Liens de retour au Bureau et aux communications ajoutés dans l’éditeur.

Qualification locale : 23 contrôles HTTP existants réussis ; rendu inspecté sur ordinateur et à 390 pixels, valeur conservée après changement d’onglet. Préproduction vérifiée sur ordinateur et à 390 pixels : onglets et aperçu PDF fonctionnels, aucun débordement horizontal. Six contrôles CI réussis, quatre fichiers installés et empreintes contrôlées ; communications, dossiers et catalogue inchangés. [Preuve de livraison](../data/bureau-ui-verification.json). Validation humaine globale distincte ; aucun changement de production.

## Courriel aux adhérents — développement du 22 octobre

Sélectionner le nouveau canal, enregistrer puis valider la révision. « Préparer le courriel aux adhérents » permet de choisir une source, relire le message et sélectionner explicitement les destinataires. En préproduction, seuls les destinataires autorisés de recette peuvent recevoir un message. Google Contacts doit être connecté au site séparément ; tant que la configuration manque, l’écran le signale. Affiche par lien si publication sur le site, aucun fichier joint. [Contrat et configuration](../api/participations-courriels.md), [preuves](../data/event-participation-mail-verification.json).


# Communication du Bureau

## Référence courante pour la campagne du 21 octobre

Voir la [campagne V2](campagne-recette-v2.md). Les paragraphes datés ci-dessous décrivent leurs étapes historiques. Depuis le 20 octobre, Ambiance est supprimé, la vue Mois revient à chaque ouverture et les événements/récurrences du Bureau sont livrés en préproduction : [guide agenda](evenements-bureau.md). Les confirmations ultérieures de réception des courriels et le lien accueil sont consignés dans le [point de reprise](point-session.md).

## Optimisations communications et agenda — livrées le 19 octobre

Développement local vérifié : bouton « Copier le message » sur la révision validée, repli par sélection manuelle, explications du partage et affiches cadrées sans déformation. Aucun envoi automatique vers Facebook ou WhatsApp.

L’agenda conserve son intégration Google existante. Les commandes proposent Mois/Semaine/Liste et trois ambiances (bordeaux, vert, bleu clair), mémorisées dans le navigateur avec réinitialisation. Les couleurs internes du calendrier restent gérées par Google. Jusqu’à six communications de catégorie Événement, publiées sur le site et approuvées dans leur révision courante, peuvent apparaître avec leur affiche et un lien vers le détail. Il s’agit des annonces publiées, sans synchronisation ni calcul des prochaines dates.

Les préférences ne sont pas des paramètres partagés par tous les visiteurs. Création/édition depuis le Bureau, récurrences, exceptions et thème global restent à développer après choix de la source des événements. La campagne humaine de tests est différée à la demande du responsable ; elle ne bloque pas ce développement.

Contrôles locaux : 22 PDF/canaux, 13 catalogue, 11 intégration agenda et 3 tests JavaScript réussis. Copie dans Chrome et mémorisation des préférences après rechargement vérifiées sur la copie locale. Le modèle d’agenda local a été temporairement équipé d’un iframe de recette puis restauré exactement. Installation hébergée désormais vérifiée dans Chrome : commandes et mémorisation de l’agenda, copie du message, affiches PDF et logo. Correction ciblée des permissions de trois dossiers publics ; Basic et dossiers privés conservés. La galerie d’affiches a été contrôlée visuellement sur une fixture locale, sans publication d’actualité en préproduction. [Reçu et limites](../data/communications-agenda-verification.json). Production exclue.


## Aperçus et affichage — complément local non livré, clôture du 18 octobre

Le Bureau dispose localement d’un aperçu de la première page des PDF enregistrés et d’un aperçu du texte et du support pendant la saisie, avant enregistrement. Le fichier original et son téléchargement sont conservés. PDF.js `6.4.299` est embarqué avec ses ressources locales ; le rendu utilise un canvas et les URL du même site, sans service de conversion externe. Les contrôles d’accès au support restent ceux du contrôleur existant. La vue ne rend ni annotations ni scripts du PDF. Le détail des dépendances figure dans [la provenance PDF.js](../drupal/web/modules/custom/tcl_bureau/vendor/pdfjs/README.md).

État à la clôture du 18 octobre : changements non commités et non installés en préproduction. Les aperçus PDF enregistré et PDF sélectionné ont été vérifiés visuellement en local ; la régression complète et le parcours protégé hébergé restent à effectuer. Le tableau des tarifs est également corrigé localement pour bureau/mobile. Voir [la reprise](point-session.md).


## Vérification locale du 19 octobre

Aperçu du PDF enregistré et sélectionné, remplacement par une image, texte modifié sans sauvegarde et retrait de l’affiche contrôlés dans le navigateur. Une sélection illisible affiche un message explicite ; elle ne supprime pas le brouillon enregistré. Tableau tarifaire contrôlé sur bureau et mobile (375 pixels utiles, sans débordement horizontal). Livraison de ce complément encore à effectuer.

Le paquet limité est produit par `python scripts/package-v2-update.py` depuis les seuls fichiers commités des modules Bureau et OAuth. Il ne contient ni Core, ni base, ni paramètres privés. La présence de fichiers applicatifs non commités bloque sa préparation. Les anciens opérateurs de livraison restent historiques : préparer des opérateurs liés à la nouvelle empreinte et à la restauration fraîche.

## Complément courant — PDF et canaux

L’actualité accepte désormais une affiche PDF existante (8 Mo maximum), en alternative à une image. Le PDF est conservé intégralement, métadonnées comprises, en base privée ; il est servi uniquement en pièce jointe téléchargeable, avec `nosniff`, `no-store` et CSP sandbox. Les contrôles portent sur la taille, la signature, le type MIME et la fin du fichier : ils ne constituent ni une analyse antivirus ni une réécriture du document. Aucun convertisseur PDF, éditeur d’affiche ou export image-vers-PDF n’est ajouté.

Les cases **Site internet**, **Facebook** et **WhatsApp** sont indépendantes ; au moins une est requise. Un nouveau brouillon propose seulement Site internet par défaut. Les anciennes actualités conservent les trois possibilités par l’update additive `11003`, sans changer leur état ou leur révision. Toute modification du choix retire la publication et invalide l’approbation, comme les autres modifications.

Seul le choix Site internet permet l’action de publication et l’accès public au contenu ou au fichier. Facebook et WhatsApp restent manuels après validation : copier le texte, télécharger la pièce, puis joindre celle-ci et confirmer dans le service choisi. Le lien WhatsApp ne joint jamais le PDF. Les outils FFT historiques sont conservés dans un volet complémentaire fermé. Aucun envoi réel ni statut de livraison automatique.

**Cadrage** : import PDF existant confirmé par le responsable. **Développement local** : implémenté, recette HTTP fictive et essai privé du PDF Halloween fourni. **Préproduction** : complément livré le 18 octobre à 23 h 23 (Paris), recette navigateur protégée encore attendue. **Mise en production** : exclue de cette demande.

Le reçu du complément est [communication-pdf-canaux-verification.json](../data/communication-pdf-canaux-verification.json). Les observations ci-dessous du 5–12 octobre restent historiques ; le catalogue et le formulaire de recette ont depuis été livrés en préproduction, selon [leur reçu distinct](../data/v2-preproduction-livraison.json).


## Bandeau V2 — 18 octobre

La dernière publication apparaît désormais en tête du contenu principal. Voir [le fonctionnement, les contrôles et limites](recette-v2.md) et [le reçu](../data/v2-recette-verification.json). Le retrait explicite reste nécessaire ; aucune expiration automatique ajoutée.

## Accès courant — 12 octobre

L'accès à cette communication exige désormais la
[connexion OAuth Bureau](bureau-oauth.md) et les droits Drupal locaux.
Les fonctionnalités ci-dessous restent celles du module natif existant ;
le fournisseur TC dédié est testé en local avec le premier Admin de recette,
mais son installation hébergée reste à qualifier. Les anciennes adresses
locales et recettes du 5–6 octobre sont conservées comme historique.

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

## Livraison du 19 octobre 2026

Les aperçus et corrections tarifaires sont installés en préproduction, avec 244 fichiers vérifiés. La recette visuelle est démontrée en local uniquement ; le navigateur hébergé reste bloqué par l’accès Basic. Le catalogue saisonnier doit encore être publié depuis le Bureau pour activer le formulaire sur l’accueil. Les courriels sont configurés en privé, sans envoi réel effectué. Voir le [reçu](../data/v2-apercus-recette-verification.json).


## Textes par canal et réponse personnelle — 22 octobre

Livré et vérifié en préproduction le 22 octobre : titre/objet et texte facultatifs par canal Site, Facebook, WhatsApp et Courriel. Vide reprend la valeur commune ; affiche commune conservée. Chaque modification repasse en brouillon et retire la publication. Aperçus et copie manuelle utilisent la variante correspondante ; aucun envoi social automatique.

Le courriel est multipart texte/HTML classique, sans AMP ni enregistrement expéditeur AMP. Pour une actualité liée à une occurrence ouverte, trois liens personnels sont créés à l’envoi : Oui, Non, Incertain. Ils ouvrent la réponse avec le choix présélectionné et une liste de 1 à 10 personnes si Oui ; seule la confirmation POST enregistre. Jeton dans le fragment, validité sept jours limitée par la clôture, ancien lien remplacé sans effacer la réponse. Aperçu Bureau sans jeton ; chaque destinataire est envoyé séparément, liste de recette maintenue.

État de recette et limites : [preuve du lot](../data/channel-rsvp-verification.json). Réception du nouveau courriel HTML confirmée. Après correction du cache navigateur, les trois liens sont vérifiés sur identité fictive ; réessai personnel confirmé et réponse de six personnes vérifiée. Rouvrir le bouton du courriel après une page en erreur, le fragment personnel étant retiré de la barre d’adresse après lecture.
