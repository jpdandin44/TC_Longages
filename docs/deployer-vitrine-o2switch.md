---
project: TC_Longages
document_type: deployment-guide
title: Déposer la vitrine publique sur o2switch
status: active
version: git
created: 2026-09-16
updated: 2026-09-24
owner: jpdandin
tags:
  - o2switch
  - vitrine
  - publication-manuelle
---

# Déposer la vitrine publique sur o2switch

## Ce qui est préparé

L'utilisateur a choisi **la vitrine publique seule, sans les pages du bureau**. L'archive [tc-longages-vitrine-o2switch.zip](../livrables/tc-longages-vitrine-o2switch.zip) contient exactement un fichier `index.html`, directement à sa racine. Les styles, le script de navigation et le logo, la photo réelle du court et la photographie d'illustration sont intégrés. Aucun serveur Node.js, base de données ou installation WordPress n'est nécessaire pour servir ce fichier.

Les liens de contact, Ten'Up, Facebook et les crédits sont conservés. La section « Adhérer » oriente vers le club et Ten'Up ; elle ne contient pas de formulaire. L'archive exclut le bureau, la communication, la gestion des inscriptions, les exemples fictifs, les comptes et les scripts locaux. Elle n'active aucune publication sur les réseaux sociaux.

Ce guide et le [manifeste de contrôle](../livrables/tc-longages-vitrine-o2switch.manifest.json) restent sur l'ordinateur, hors de l'archive à déposer. Ne pas utiliser l'archive de visite `tc-longages-prototype.zip` ni l'ancien pack historique pour cette publication.

**État : archive préparée localement ; aucun accès à cPanel et aucun déploiement effectué par l'agent.** Le choix du contenu n'est pas un accord de déploiement par l'agent. L'utilisateur conserve la décision et réalise lui-même les étapes suivantes lorsqu'il souhaite publier.

## Avant le dépôt : identifier la bonne cible

Dans cPanel, relever le domaine ou sous-domaine du site tennis et sa **Racine des documents**. C'est le dossier qui sert les fichiers de ce site. Ne pas présumer que c'est `public_html` : le compte peut héberger plusieurs sites. La documentation [Domaines configurés d'o2switch](https://faq.o2switch.fr/cpanel/domaines/configuration-domaine/) explique cette association entre domaine et dossier.

À renseigner avant de déposer le fichier :

| Élément | État de cette préparation |
|---|---|
| Adresse exacte choisie pour la vitrine | TBD — non confirmée pour ce dépôt. |
| Racine des documents correspondante | TBD — à relever dans cPanel. |
| Contenu déjà présent et page actuellement servie | Non inspectés sur l'hébergement. |
| Certificat HTTPS pour cette adresse | Non vérifié. |
| Copie privée de sauvegarde de l'existant | À réaliser avant tout remplacement. |

Ouvrir l'adresse en HTTPS et vérifier que le navigateur ne signale pas d'erreur de certificat. Si le certificat, le domaine ou la racine ne sont pas corrects, faire préparer cette correction séparément avant le dépôt. Aucun changement DNS ou HTTPS n'est inclus dans l'archive.

## Dépôt manuel avec le gestionnaire de fichiers

1. Ouvrir le **Gestionnaire de fichiers** de cPanel et naviguer dans la racine confirmée du site tennis.
2. Examiner les fichiers déjà présents, notamment `index.html`, `index.php` et `.htaccess` (afficher les fichiers cachés si nécessaire). Télécharger une copie de l'existant concerné sur l'ordinateur, dans un dossier privé identifié. Ne pas laisser une copie `.bak` ou une sauvegarde dans le dossier public.
3. Si le dossier contient un autre site, WordPress, une redirection ou des règles de réécriture, suspendre le remplacement et faire examiner cette configuration. Le ZIP n'efface pas l'ancien site et ne modifie ni la priorité des pages d'accueil ni les règles serveur.
4. Lorsque la cible est prête et que vous décidez de publier, utiliser **Charger / Téléverser** pour envoyer uniquement `tc-longages-vitrine-o2switch.zip` dans ce dossier.
5. Sélectionner l'archive puis **Extraire**, en gardant comme destination cette même racine. `index.html` doit apparaître directement dans le dossier servi, sans sous-dossier supplémentaire. **Cette extraction crée ou remplace la page visible : c'est le moment de la mise en ligne.** Si un remplacement est proposé, ne le confirmer qu'après la sauvegarde et la vérification de la cible.
6. Contrôler le résultat selon la liste ci-dessous, puis retirer du serveur uniquement le ZIP qui vient d'être téléversé. Conserver l'archive et le manifeste sur l'ordinateur.

L'envoi puis l'extraction d'un ZIP via le gestionnaire de fichiers est décrit dans la [procédure officielle o2switch](https://faq.o2switch.fr/guides/base-de-donnees/phpmyadmin-independant/#installation-manuelle). Cette référence illustre l'outil ; aucune installation de phpMyAdmin n'est requise ici.

## Actions sensibles, traduites en décisions

| Action | Ce que vous décidez | Point à vérifier |
|---|---|---|
| Extraire ou remplacer `index.html` | Rendre cette vitrine visible à l'adresse choisie. | Bon domaine, bon dossier et copie privée de l'ancien fichier. |
| Retirer une ancienne page ou modifier `.htaccess` | Changer la façon dont le serveur choisit et affiche le site. | Pas nécessaire sur un dossier statique prêt ; diagnostic distinct si conflit. Aucun fichier serveur fourni dans le ZIP. |
| Changer DNS, certificat ou racine documentaire | Changer le routage ou l'accès sécurisé au site. | Hors de cette préparation ; présenter l'effet et le retour arrière avant toute intervention. |
| Restaurer la sauvegarde | Remettre la page précédente en ligne. | Restaurer uniquement les fichiers concernés du site tennis. |

La génération locale `npm.cmd run release:package` régénère les HTML attendus et remplace seulement le ZIP et son manifeste locaux. Elle n'utilise aucun accès d'hébergement et ne transfère rien. Les règles de référence sont dans [Commandes sensibles](commandes-sensibles.md).

## Vérification après votre publication

- Ouvrir l'adresse choisie **à la racine**, puis `/index.html`, dans une fenêtre privée : les deux doivent afficher la même vitrine « Tennis Club de Longages ».
- Vérifier HTTPS sans alerte, les deux photos et leurs crédits, puis le menu sur mobile.
- Vérifier les liens de contact vers `tclongages@gmail.com`, Ten'Up et Facebook. Leur présence dans le fichier ne prouve pas la réception d'un e-mail ni la disponibilité des services externes.
- Vérifier que `/bureau.html`, `/communication.html`, `/inscriptions.html`, `/adherer.html`, `/parcours.html` et `/actualites-bureau.html` ne donnent accès à aucune ancienne page interne. L'archive n'en contient aucune, mais elle ne supprime pas d'éventuels fichiers déjà présents sur le serveur.
- Si l'ancienne page reste affichée, contrôler le cache et la racine du domaine. Si `/index.html` fonctionne mais pas `/`, faire examiner la priorité d'index ou les règles existantes ; ne pas supprimer `index.php` ou `.htaccess` à l'aveugle.

En cas de problème, restaurer le fichier sauvegardé dans la même racine, puis vérifier de nouveau la page réellement servie. Si aucun `index.html` n'existait avant, retirer seulement celui ajouté par ce dépôt. Ne pas toucher aux autres sites du compte.

## Contrôles locaux et limites

Les résultats de préparation sont conservés dans la [recette de l'archive publique](recette-vitrine-o2switch.md). Ils portent sur les fichiers et la génération locale. L'adresse finale, l'état du serveur et son HTTPS restent à vérifier au moment de votre dépôt. Le futur espace bureau, la collecte réelle des inscriptions et les échanges XLS/CSV ne sont pas livrés dans cette archive.
