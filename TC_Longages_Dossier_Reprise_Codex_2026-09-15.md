---
title: "Tennis Club de Longages — Dossier complet de reprise pour Codex"
project: TC_Longages
owner: jpdandin
version: "1.0"
date: "2026-09-15"
language: fr
type: project-handoff
status: "Correction préparée ; publication o2switch restante"
deployment_target: o2switch
last_live_check_utc: "2026-09-15T20:38:37Z"
---

# Tennis Club de Longages — Dossier complet de reprise pour Codex

Copie publique expurgée le 29 septembre 2026 : les identifiants techniques internes et chemins de compte restent dans l’original privé conservé sur le poste. Le contenu ci-dessous est historique.

Ce document centralise le contexte, les demandes, les décisions, l’architecture, les fichiers, les incidents, les corrections et la suite du travail. Il permet à un nouvel agent Codex de reprendre le projet sans relire la conversation.

**Le point essentiel : la version corrigée existe, mais elle n’est pas encore publiée sur o2switch au dernier contrôle. Le dossier `dist/` du dépôt initial contient encore l’ancienne version.**

## 1. Commencer ici : fichiers à transmettre et mission de reprise

### 1.1 Pièces à joindre à Codex

Transmettre ensemble :

1. Ce fichier Markdown.
2. **`TCL_Longages_SITE_A_EXTRAIRE.zip` dans sa dernière version**, décrite ci-dessous : 326 400 octets, contenant uniquement un `index.html` de 449 087 octets.

Alternative au ZIP : transmettre directement le dernier **`index.html` autonome de 449 087 octets**.

Le Markdown fournit le contexte et les procédures. Le ZIP ou le HTML apporte le code réel et les octets des photographies. Les anciennes URL de fichiers de conversation et les anciens chemins de travail ne seront pas nécessairement accessibles dans une autre session Codex : télécharger les pièces et les joindre au nouveau projet.

**Ne pas utiliser comme point de départ le vieux pack `TCL_Longages_Migration_o2switch.zip` ni un `index.html` de 14 600 octets.**

### 1.2 Prompt de démarrage à donner à Codex

```text
Tu reprends le projet TC_Longages pour Jean-Philippe Dandin, owner jpdandin.

Lis intégralement le dossier de reprise joint et inspecte les fichiers réellement
disponibles avant de modifier quoi que ce soit.

Objectif prioritaire : finaliser le site du Tennis Club de Longages sur le
sous-domaine o2switch tclongages.daje3540.odns.fr, avec les deux photos visibles
et tous les liens e-mail dirigés vers 23310230@fft.fr.

La version fonctionnelle à reprendre est le dernier index.html autonome,
de 449 087 octets, ou le ZIP associé de 326 400 octets. Vérifie leurs empreintes
indiquées dans le dossier. Ce HTML intègre les images, le CSS et le JavaScript.
Le dossier dist/ du dépôt historique contient encore une ancienne version :
ne l'utilise pas pour écraser les corrections.

Préserve le contenu, les couleurs, les crédits photographiques, le menu mobile
et les liens Ten'Up/Facebook. Les photos sont des illustrations, pas des photos
des installations de Longages. N'invente pas de tarifs, horaires, coordonnées,
logo officiel ou alias de messagerie.

Commence par vérifier l'état réel du site, la version des fichiers locaux,
l'accès disponible à l'hébergement et la racine exacte du sous-domaine.
Prépare une sauvegarde avant remplacement. Si l'accès autorisé est disponible,
publie la correction puis vérifie réellement le résultat. Si l'accès manque,
indique précisément ce qui bloque ; ne déclare pas la publication terminée.

Respecte le choix o2switch. Ne déploie pas automatiquement sur Sites pour
remplacer cette destination. Le site Sites existant reste une référence
historique dont la visibilité était privée.

Ne demande pas de mot de passe dans la conversation. Utilise le mécanisme
sécurisé disponible dans ton environnement. Aucun accès cPanel n'est présumé
encore connecté.

Termine par un état clair : réalisé, vérifié, restant à faire. Mets à jour
la documentation du projet et conserve une source de référence cohérente.
```

## 2. Synthèse de l’état du projet

| Sujet | État documenté |
|---|---|
| Club | Tennis Club de Longages, 31410, Haute-Garonne |
| Demandeur | Jean-Philippe Dandin — `jpdandin` |
| Type de site | Site vitrine statique, en français, une page |
| Création initiale | Réalisée avec Sites ; première publication annoncée en accès privé |
| Hébergement retenu ensuite | o2switch, sous-domaine déjà créé par l’utilisateur |
| Adresse cible | `http://tclongages.daje3540.odns.fr/` |
| Page en production au dernier contrôle | HTTP 200, ancienne version, 14 600 octets |
| Photos séparées en production | Les deux URL WebP répondent HTTP 403 |
| E-mail en production au dernier contrôle | Ancienne adresse Hotmail encore présente |
| E-mail demandé par l’utilisateur | **`23310230@fft.fr`** |
| Solution préparée | HTML autonome : images, styles et JavaScript intégrés |
| Vérification de cette solution | Structure, présence des ressources, identité des images et liens e-mail contrôlées localement |
| Publication de cette solution | **Non effectuée par l’agent** |
| Blocage de publication | Connexion sécurisée à cPanel initiée, puis interrompue ; session d’exécution déconnectée pendant la tentative |
| Dernier contrôle public | 15 septembre 2026, **20:38:37 UTC**, soit 22:38:37 en France métropolitaine |

L’état décrit ici est une photographie de fin de session. Le recontrôler à la reprise : l’utilisateur peut avoir remplacé le fichier depuis ce constat.

## 3. Objectif et périmètre

### 3.1 Objectif du site

Présenter le club, expliquer les possibilités de pratique, faciliter les demandes de renseignements, orienter vers l’adhésion et donner accès aux informations pratiques et aux services existants.

Le public visé est constitué des habitants de Longages et des environs, des familles, des débutants et des joueurs souhaitant reprendre ou poursuivre leur pratique.

### 3.2 Demandes exprimées dans la conversation

1. Créer un site pour le club de tennis.
2. Le personnaliser pour le **Tennis Club de Longages, 31410**.
3. Permettre sa migration vers un sous-domaine o2switch.
4. Aider à l’installation sur `tclongages.daje3540.odns.fr`.
5. Corriger une erreur 403 sur la page, puis les images absentes.
6. Remplacer l’adresse Hotmail par **`23310230@fft.fr`**.
7. Examiner la possibilité d’un affichage plus compréhensible, notamment `TCLongages@fft.fr`.
8. Publier le site avec un affichage correct des images.
9. Préparer ce dossier Markdown pour transférer la suite du travail dans Codex.

La dernière demande porte sur la passation documentaire. Aucune nouvelle publication n’a été lancée pendant la rédaction de ce dossier.

### 3.3 Fonctionnalités déjà présentes

- Navigation par ancres dans une seule page.
- Menu adapté aux petits écrans.
- Présentation du club.
- Rubriques école de tennis, loisir et compétition.
- Parcours expliquant comment préparer une inscription.
- Contact par liens `mailto:` avec plusieurs objets préremplis.
- Liens vers la fiche et les offres Ten’Up.
- Lien vers Facebook.
- Lien d’itinéraire Google Maps.
- Questions pratiques dépliantes.
- Crédits et sources accessibles en pied de page.

### 3.4 Ce qui n’est pas développé

Pas de base de données, de paiement, de compte adhérent propre au site, de réservation interne, de formulaire serveur, d’envoi automatique de courriel ni d’administration éditoriale. Les inscriptions et réservations suivent les modalités du club et des services externes.

Le projet n’est pas un site Drupal. Il ne nécessite pas une installation de Drupal, WordPress, Node.js ou PHP applicatif chez o2switch.

## 4. Informations du club et hiérarchie des sources

| Information | Valeur retenue | Provenance / limite |
|---|---|---|
| Nom | Tennis Club de Longages | Confirmé par l’utilisateur |
| Commune | Longages, 31410 | Confirmé par l’utilisateur |
| Adresse des courts affichée | Chemin de Muret, 31410 Longages | Information reprise des sources publiques lors de la création |
| E-mail officiel | `23310230@fft.fr` | Correction explicite fournie par l’utilisateur ; priorité sur l’ancien annuaire |
| Ancien e-mail | `tclongages@hotmail.fr` | Ancienne donnée utilisée à la création ; à retirer des liens actifs |
| Ten’Up | `https://tenup.fft.fr/club/60310230` | Lien présent dans le site initial |
| Offres Ten’Up | `https://tenup.fft.fr/club/60310230/offres` | Lien présent dans le site initial |
| Facebook | `https://www.facebook.com/tc.longages.31` | Lien repris de l’annuaire municipal |
| HelloAsso | `https://www.helloasso.com/associations/tennis-club-de-longages` | Source de présentation de l’association |
| Téléphone | Non intégré | Pas de numéro confirmé à utiliser |
| Tarifs et horaires | À confirmer auprès du club | Aucun montant ni planning inventé |
| Logo officiel | Non fourni | Le monogramme TCL actuel est une proposition graphique |
| Photos réelles du club | Non fournies | Les photos actuelles sont des illustrations créditées |

**Ne pas déduire le code Ten’Up de l’adresse e-mail.** Le lien Ten’Up contient `60310230`, tandis que l’adresse fournie par l’utilisateur commence par `23310230`. Conserver les valeurs documentées sans tenter de les uniformiser.

Sources publiques présentes dans le projet :

- [Annuaire de la mairie de Longages](https://www.longages.fr/associations/tennis-club-longages/).
- [Fiche du club sur Ten’Up](https://tenup.fft.fr/club/60310230).
- [Offres du club sur Ten’Up](https://tenup.fft.fr/club/60310230/offres).
- [Présentation HelloAsso](https://www.helloasso.com/associations/tennis-club-de-longages).

Ces références documentent la création initiale. Leur maintien en ligne ne remplace pas une validation par le club des informations de saison.

## 5. Structure de la page et comportement

| Surface | Ancre / repère | Contenu ou action |
|---|---|---|
| Bandeau supérieur | Début de page | Identité locale et accès Ten’Up |
| En-tête | `main-nav` | Monogramme TCL, navigation, bouton Ten’Up |
| Accueil | `accueil` | « Le tennis se partage. », photo d’illustration, rejoindre / découvrir |
| Valeurs | Bandeau intermédiaire | Apprendre, jouer, se retrouver |
| Le club | `le-club` | Présentation conviviale et prise de contact |
| Jouer et progresser | `jouer` | École de tennis, loisir, compétition |
| Adhérer | `adherer` | Exprimer son envie, trouver une formule, préparer sa première partie |
| Vie du club | `life-title` | Seconde photo et lien Facebook |
| Informations pratiques | `contact` | Adresse, itinéraire, e-mail, Ten’Up |
| Questions fréquentes | Dans la rubrique contact | Tarifs / horaires, accès aux courts, découverte / reprise |
| Pied de page | `credits` | Sources, photographes, licence et précisions de fonctionnement |

### Comportement JavaScript

- Le bouton du menu mobile bascule la classe `is-open` et l’attribut `aria-expanded`.
- Le libellé passe de « Menu » à « Fermer ».
- Le menu se referme au clic sur un lien, au clic en dehors de l’en-tête ou avec Échap.
- Le passage à une largeur d’au moins 761 px referme le menu mobile.
- Le lien vers les crédits ouvre la section `<details>` correspondante.

### Éléments d’accessibilité présents dans le code

- Langue de document `fr` et encodage UTF-8.
- Lien d’évitement vers `contenu`.
- Titres structurés et libellés de navigation.
- Textes alternatifs des deux images.
- Focus visible pour les liens et contrôles.
- Prise en compte de `prefers-reduced-motion`.

Ces éléments sont présents dans le code. Ils ne constituent pas une déclaration de conformité ni un audit d’accessibilité complet.

## 6. Direction graphique à préserver

L’ambiance associe vert profond, couleur terre battue et accents clairs. La page est destinée à un club de village accueillant, sans changement de marque demandé.

| Variable CSS | Valeur | Usage |
|---|---|---|
| `--green` | `#153c32` | Couleur principale |
| `--green-deep` | `#102e27` | Vert foncé |
| `--clay` | `#ad5235` | Accent terre battue |
| `--lime` | `#d6e798` | Accent clair |
| `--ink` | `#203b32` | Texte principal |
| `--muted` | `#5d6962` | Texte secondaire |
| `--paper` | `#fcfcf9` | Fond principal |
| `--tint` | `#f1f3ee` | Fond de sections |
| `--line` | `#dce2d9` | Séparations |

La police principale est `Arial, Helvetica, sans-serif`, avec des accents typographiques utilisant notamment Georgia. Aucune police externe n’est nécessaire au chargement.

Des règles de mise en page existent pour les seuils 1 500, 1 190, 1 060, 760 et 380 px. Le titre de page est « Tennis Club de Longages — Le tennis se partage ». Le favicon est intégré sous forme de SVG dans une URL de données.

## 7. Architecture : version historique et version à publier

### 7.1 Version historique à fichiers séparés

| Fichier | Rôle |
|---|---|
| `dist/index.html` | Structure et contenu de la page |
| `dist/styles.css` | Styles et adaptation aux écrans |
| `dist/script.js` | Menu mobile et ouverture des crédits |
| `dist/assets/tennis-hero.webp` | Première photographie |
| `dist/assets/tennis-life.webp` | Seconde photographie |
| `README.md` | Présentation technique initiale |
| `ASSETS.md` | Provenance des images |
| `.openai/hosting.json` | Identité du projet Sites et répertoire statique |

Les chemins historiques commencent par `/`, par exemple `/assets/tennis-hero.webp`. Cette version était prévue pour la racine du sous-domaine.

### 7.2 Dernière version autonome

**Un seul `index.html` contient la totalité des ressources nécessaires à la présentation et au fonctionnement de la page.**

- Le CSS est intégré dans un bloc `<style>`.
- Le JavaScript est intégré en fin de `<body>`, après les éléments auxquels il s’attache.
- Les deux photographies WebP sont intégrées dans les attributs `src` sous forme `data:image/webp;base64,...`.
- Le favicon reste intégré.
- Les liens vers les services externes et la messagerie sont conservés.

Cette version ne demande plus le téléchargement de `/styles.css`, `/script.js` ou `/assets/*.webp` pour afficher la page.

### 7.3 Pourquoi cette solution a été retenue

Les URL des images renvoyaient 403 sur o2switch. En incorporant leurs octets dans le HTML, leur affichage ne dépend plus des permissions ou du chemin du dossier `assets`.

Il s’agit d’un changement de **mode de chargement**, pas d’un remplacement des photographies : les deux images sont identiques, octet pour octet, aux images d’origine.

Cette solution ne répare pas les permissions du serveur sur les anciens fichiers. Ces URL peuvent continuer à renvoyer 403 sans affecter la nouvelle page puisqu’elle ne les utilise plus.

### 7.4 Limites connues

- Le fichier HTML atteint 449 087 octets, car il embarque les photographies.
- Les images n’ont plus de cache séparé du HTML.
- Une maintenance régulière sera plus simple avec des sources séparées et une génération déterministe de la version autonome.
- Ne pas revenir aux ressources séparées tant que leur accès public n’a pas été corrigé et vérifié.
- Aucun nouveau besoin de framework ou de base de données n’a été identifié.

## 8. Images : contenu, provenance et crédits

| Image | Photographe | Dimensions intégrées | Taille binaire |
|---|---|---:|---:|
| Première photo, balles et filet sur terre battue | Darko Nesic | 1 400 × 1 050 px | 239 798 octets |
| Seconde photo, court vert, lignes et ombres | Nicholas Bullett | 1 000 × 1 250 px | 71 268 octets |

Sources :

- [Photo de Darko Nesic](https://unsplash.com/photos/yellow-tennis-ball-on-brown-sand-VZEnVM6c1lY).
- [Photo de Nicholas Bullett](https://unsplash.com/photos/a-person-on-a-tennis-court-with-a-racket-lueeREH1oTY).
- [Licence Unsplash référencée par le projet](https://unsplash.com/license).

Les crédits sont présents dans le pied de page. Le texte précise que les photographies **ne représentent pas les installations du Tennis Club de Longages**. Conserver ces indications tant que les photos n’ont pas été remplacées par des visuels du club validés pour publication.

Les illustrations actuelles sont des photographies existantes, pas des images générées. Aucun logo officiel n’a été fourni.

## 9. Adresse officielle et question de l’alias

### 9.1 Correction effectuée

Tous les sept liens `mailto:` de la version autonome pointent vers **`23310230@fft.fr`** :

| Emplacement | Objet prérempli |
|---|---|
| Présentation du club | Faire connaissance avec le Tennis Club de Longages |
| École de tennis | Renseignements école de tennis — Longages |
| Compétition | Renseignements compétition — Longages |
| Adhésion | Rejoindre le Tennis Club de Longages |
| Carte de contact | Aucun |
| Question sur les tarifs / horaires | Aucun |
| Pied de page | Aucun |

Dans la carte de contact, le texte visible est **« Écrire au club »**. Son attribut `title` indique l’adresse officielle. L’adresse numérique reste également disponible dans la question pratique correspondante.

### 9.2 Ce qui n’a pas été fait

`TCLongages@fft.fr` **n’a pas été créé, configuré ni validé**.

Afficher une autre adresse dans une page HTML ne crée pas une boîte aux lettres ni un alias. La possibilité d’obtenir cet alias doit être confirmée auprès du gestionnaire de la messagerie FFT. Ne pas le mettre dans un lien `mailto:` ou le présenter comme opérationnel sans confirmation.

Aucun message de test n’a été envoyé à la boîte officielle. La validité de cette adresse est fondée sur l’instruction de l’utilisateur ; sa délivrabilité n’a pas été testée.

## 10. Historique des incidents et des corrections

| Étape | Constat | Action / résultat |
|---|---|---|
| Création initiale | Première version réalisée avec Sites | Site vitrine publié initialement en accès privé |
| Préparation o2switch | Premier pack comprenant une archive imbriquée | Simplification par fourniture d’un ZIP directement extractible |
| Installation | Page « 403 Forbidden » | Analyse des permissions des fichiers |
| Contrôle de capture cPanel | `index.html`, `styles.css`, `script.js` en `0600` ; dossier `assets` en `0755` | Consigne de passer les fichiers en `0644` |
| Après intervention utilisateur | La page s’affiche | L’utilisateur confirme la disparition de la 403 de la page |
| Images absentes | Les deux URL WebP répondent encore 403 | Vérification directe des URL ; permissions des images à contrôler |
| E-mail | Ancienne adresse Hotmail | Sept liens corrigés vers l’adresse FFT dans l’archive |
| Correction autonome | Dépendance aux fichiers images encore problématique | Images, CSS et JavaScript incorporés dans `index.html` |
| Tentative de publication | Accès technique `/cpanel` avec erreur de certificat | L’adresse officielle du serveur cPanel est atteinte ensuite |
| Connexion cPanel | Formulaire identifiant / mot de passe affiché | Demande sécurisée initiée, puis interrompue |
| Après interruption | Aucune publication exécutée par l’agent | Fichier autonome et ZIP mis à disposition |
| Préparation de cette passation | Contrôle du site et des fichiers | Ancienne page et erreurs 403 des images encore constatées |

La 403 des images est constatée. Des droits `0600` sur les fichiers images constituent une hypothèse cohérente avec l’incident initial, mais leurs permissions réelles après les dernières manipulations n’ont pas été observées dans cPanel. Ne pas présenter cette hypothèse comme une inspection réalisée.

## 11. Dernier contrôle public : faits mesurés

Contrôle réalisé le **15 septembre 2026 à 20:38:37 UTC**.

| Ressource | Réponse / contenu constaté |
|---|---|
| `http://tclongages.daje3540.odns.fr/` | HTTP 200 ; 14 600 octets ; `text/html` |
| `/assets/tennis-hero.webp` | HTTP 403 |
| `/assets/tennis-life.webp` | HTTP 403 |
| Présence de `tclongages@hotmail.fr` dans le HTML public | Oui |
| Présence de `23310230@fft.fr` dans le HTML public | Non |
| Images WebP intégrées dans le HTML public | 0 |

L’empreinte SHA-256 du HTML public correspond à celle du `dist/index.html` historique. Ce résultat établit que la correction autonome n’était pas servie à cette heure.

Le HTTPS du site cible n’est pas validé. L’accès au formulaire cPanel officiel en HTTPS ne prouve pas que le sous-domaine du site dispose d’un certificat valide.

## 12. Inventaire des fichiers et empreintes de référence

### 12.1 Chemins de travail de la session d’origine

Répertoire du dépôt initial :

```text
/workspace/scratch/8cbabfed5fe6
```

| Chemin relatif | Taille | Utilisation |
|---|---:|---|
| `transfert-o2switch/index.html` | **449 087 octets** | **Version autonome corrigée à publier** |
| `transfert-o2switch/TCL_Longages_SITE_A_EXTRAIRE.zip` | **326 400 octets** | **Dernier ZIP, contenant uniquement ce `index.html`** |
| `dist/index.html` | 14 600 octets | Ancienne version, avec Hotmail et ressources séparées |
| `dist/styles.css` | 18 355 octets | Styles historiques, intégrés dans la version autonome |
| `dist/script.js` | 1 435 octets | JavaScript historique, intégré dans la version autonome |
| `dist/assets/tennis-hero.webp` | 239 798 octets | Photo source conservée |
| `dist/assets/tennis-life.webp` | 71 268 octets | Photo source conservée |
| `transfert-o2switch/TCL_Longages_Migration_o2switch.zip` | 326 393 octets | Ancien pack de migration, à ne pas redéployer |
| `README.md` | — | Documentation initiale, à actualiser à la reprise |
| `ASSETS.md` | — | Crédits des photographies |

L’ancien pack de migration contient un guide, un ZIP imbriqué, les crédits et un fichier d’empreintes. Ses instructions ne constituent plus la procédure de référence pour la version autonome.

### 12.2 Empreintes SHA-256

```text
Version autonome corrigée — index.html
9accdd1f4de2a0642502c5cd1bde798d704bf9652325daf39bd9ec92824674d5

Dernier ZIP — TCL_Longages_SITE_A_EXTRAIRE.zip
feb195773fad6d539c2ca4925b841be001404e09b312bac30692fa35d3e47526

Ancienne page — dist/index.html / page publique au dernier contrôle
ddea5b09c8b65668df5cc74600d56e0c264d587c72db2b422f33b5f9eb75e216

Photo source — tennis-hero.webp
b366e13ab66fe22f241ad1907ff78662d806e95a2a160c71503af088ce52c6d1

Photo source — tennis-life.webp
af1d7ac46b533fe7f2fb734f0afe921bf86e46a542cd4b7a000e95d618d7772c
```

Ces empreintes identifient les fichiers à la date de cette passation. Les actualiser après une modification volontaire du contenu.

### 12.3 Vérifications déjà réalisées sur la version autonome

- Présence de deux images WebP intégrées.
- Décodage Base64 identique aux deux fichiers images d’origine.
- Présence d’un bloc de styles et d’un bloc JavaScript.
- Absence de dépendance aux anciens fichiers `styles.css`, `script.js` et `assets/*.webp` dans les ressources de chargement.
- Sept liens de messagerie utilisant tous `23310230@fft.fr`.
- Absence de l’ancienne adresse Hotmail dans le HTML corrigé.
- ZIP valide, contenant uniquement le HTML, avec permissions UNIX `0644` enregistrées.

Il n’y a pas eu de nouvelle recette visuelle complète de cette version autonome sur o2switch. Elle reste à réaliser après publication.

## 13. Dépôt Git et référence Sites : éviter une régression

### 13.1 Situation du dépôt initial

Branche observée : `main`.

Dernier commit observé :

```text
b5b39c71849da9305eca3f02918138763cf8191a
Create Tennis Club de Longages website
```

Avant la rédaction du dossier, `transfert-o2switch/` et `upload/` apparaissaient comme non suivis par Git. Les corrections préparées pour o2switch n’avaient pas été réintégrées dans le commit initial.

**Conséquence : cloner ou republier uniquement le dépôt initial peut rétablir l’ancienne adresse et les images séparées.**

À la reprise :

1. Inspecter les instructions du dépôt et son état réel.
2. Conserver une copie identifiable de la version autonome corrigée.
3. Réconcilier les sources avec les changements de contact et de chargement des images.
4. Mettre à jour `README.md` et le journal de modifications.
5. Ne committer que les fichiers du projet et les changements utiles ; conserver les secrets et sauvegardes d’hébergement hors du dépôt.

### 13.2 Site initial

URL de référence de la première publication :

[Tennis Club de Longages sur Sites](https://tennis-club-longages.jpdandin.chatgpt.site)

Cette première publication avait été annoncée en accès privé. Son contenu actuel et son audience n’ont pas été revérifiés pendant cette passation. Les corrections o2switch n’ont pas été redéployées sur Sites dans les dernières étapes documentées.

Configuration observée dans le dépôt :

```json
{
  "static": {
    "directory": "dist"
  },
  "project_id": "expurge-copie-publique"
}
```

Cette identité sert uniquement si une reprise du Site d’origine est nécessaire. Elle n’est pas requise pour servir le HTML chez o2switch. Ne pas créer un nouveau Site à la place de celui-ci ni modifier son audience sans demande correspondante.

## 14. Hébergement et accès : ce qui est connu, ce qui manque

| Paramètre | État |
|---|---|
| Hébergeur | o2switch |
| Compte visible dans les captures | Expurgé de la copie publique |
| Sous-domaine créé | `tclongages.daje3540.odns.fr` |
| Racine exacte des documents | **À lire dans cPanel ; non établie avec certitude dans la conversation** |
| Accès d’administration atteint | `adresse cPanel conservée hors dépôt` |
| Connexion authentifiée utilisable | Non confirmée ; aucune session à présumer active |
| Identifiants secrets | Non présents dans ce dossier |
| Accès SSH/SFTP de cette session | Aucun accès de déploiement établi pour le site tennis |
| HTTP du site | Accessible lors du dernier contrôle |
| HTTPS du sous-domaine | Non validé ; problème de certificat rencontré sur l’accès technique essayé |

Le répertoire `<repertoire-compte-historique>` est le compte d’hébergement, pas la racine vérifiée du site tennis. **Ne pas utiliser automatiquement `~/www`** : ce compte héberge aussi d’autres projets. Le projet AVEREO/Drupal est distinct et doit rester hors du périmètre des changements TCL.

### HTTPS

La discussion a identifié une particularité des domaines techniques `.odns.fr` pour la délivrance de certificats. La configuration effective et les règles applicables devront être revérifiées avant de promettre du HTTPS sur cette adresse.

Références officielles utilisées pendant le travail :

- [Connexion à cPanel chez o2switch](https://faq.o2switch.fr/guides/cpanel/comment-se-connecter/).
- [Certificats Let’s Encrypt chez o2switch](https://faq.o2switch.fr/cpanel/securite/lets-encrypt-ssl-gratuit/).

Ne pas forcer une redirection HTTP vers HTTPS tant que le certificat de la destination n’est pas valide. Une future adresse sur un domaine maîtrisé pourra être étudiée avec le club ; aucun nouveau domaine n’a été choisi ici.

## 15. Procédure de publication de référence

### 15.1 Préparer et identifier la cible

1. Vérifier si l’utilisateur a déjà déposé le nouveau fichier depuis le dernier contrôle.
2. Vérifier l’empreinte de l’archive ou du HTML corrigé disponible localement.
3. Ouvrir l’administration avec le mécanisme sécurisé disponible.
4. Dans cPanel, retrouver le sous-domaine exact et lire sa **racine des documents**.
5. Ouvrir ce dossier dans le gestionnaire de fichiers ou avec un accès SFTP/SSH déjà autorisé.
6. Sauvegarder l’ancien `index.html` dans un emplacement privé, hors du dossier public, avec un nom daté permettant le retour arrière.

### 15.2 Déposer la correction

Méthode la plus simple : téléverser le **dernier `index.html` autonome** dans la racine du sous-domaine et remplacer l’ancien fichier.

Autre possibilité : téléverser puis extraire le dernier `TCL_Longages_SITE_A_EXTRAIRE.zip` dans cette même racine. Il ne contient qu’un `index.html`.

Dans les deux cas :

- Confirmer que le fichier servi se trouve directement à la racine du sous-domaine.
- Vérifier les permissions **`0644`** du fichier déposé.
- Ne pas supprimer les anciens fichiers CSS, JavaScript ou images pendant cette première correction : ils peuvent rester en place et ne sont plus sollicités par la version autonome.
- Ne pas modifier les autres domaines, le site Drupal, les bases de données ou la configuration générale du compte.
- Conserver la sauvegarde tant que la recette n’est pas terminée.

### 15.3 Vérifier la publication

1. Ouvrir la page HTTP cible et effectuer un rechargement complet.
2. Contrôler que les deux photos sont visibles.
3. Contrôler le menu mobile, les ancres et les questions dépliantes.
4. Vérifier que les sept liens `mailto:` ciblent l’adresse FFT et que Hotmail a disparu du HTML actif.
5. Vérifier les liens Ten’Up, Facebook et itinéraire.
6. Contrôler une largeur mobile et une largeur bureau, sans débordement horizontal.
7. Comparer le HTML public à la version attendue. Si le serveur transforme le HTML, utiliser des marqueurs de contenu et l’état des images en complément de l’empreinte.

Un ancien accès direct à `/assets/tennis-hero.webp` peut rester en 403 après la publication autonome. Le critère pertinent est l’affichage des images utilisées par la **nouvelle page**.

### 15.4 Retour arrière

En cas de régression réelle, remettre le fichier sauvegardé dans la même racine et vérifier son accès. Cela restaure l’état précédent, y compris ses limites connues. Documenter le motif du retour arrière et conserver la version corrigée pour analyse.

## 16. Commandes simples pour la reprise dans Codex

Les commandes suivantes s’exécutent localement dans le dossier contenant les pièces téléchargées. Elles ne publient rien et n’établissent aucune connexion à cPanel.

### 16.1 Vérifier l’identité des pièces

Sous Linux/macOS avec `sha256sum` disponible :

```bash
sha256sum TCL_Longages_SITE_A_EXTRAIRE.zip
sha256sum index.html
```

Sous PowerShell :

```powershell
Get-FileHash -Algorithm SHA256 .\TCL_Longages_SITE_A_EXTRAIRE.zip
Get-FileHash -Algorithm SHA256 .\index.html
```

Comparer aux empreintes de la section 12. Si elles diffèrent, inspecter la différence avant de remplacer un fichier : une modification plus récente de l’utilisateur peut être légitime.

### 16.2 Extraire dans un nouveau dossier local

Choisir un dossier neuf, sans écraser un projet existant :

```bash
python3 -m zipfile -e TCL_Longages_SITE_A_EXTRAIRE.zip tcl-site-corrige
```

Le fichier à examiner sera `tcl-site-corrige/index.html`. Un simple navigateur peut ouvrir ce fichier localement pour contrôler la présentation ; les liens de messagerie et les destinations externes restent dépendants des applications ou services concernés.

### 16.3 Contrôler la structure de la version autonome

Exécuter ce contrôle Python depuis le dossier qui contient le `index.html` à vérifier :

```python
from pathlib import Path
from html.parser import HTMLParser
import base64
import hashlib

class Inspection(HTMLParser):
    def __init__(self):
        super().__init__()
        self.images = []
        self.mailto = []
        self.external_assets = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "img":
            self.images.append(attrs.get("src", ""))
        if attrs.get("href", "").startswith("mailto:"):
            self.mailto.append(attrs["href"])
        src = attrs.get("src", "")
        if src and not src.startswith("data:"):
            self.external_assets.append(src)
        if tag == "link" and attrs.get("rel") == "stylesheet":
            self.external_assets.append(attrs.get("href", ""))

page = Path("index.html").read_bytes()
html = page.decode("utf-8")
inspection = Inspection()
inspection.feed(html)

assert "tclongages@hotmail.fr" not in html
assert len(inspection.mailto) == 7
assert all(link.split("?", 1)[0] == "mailto:23310230@fft.fr"
           for link in inspection.mailto)
assert len(inspection.images) == 2
assert not inspection.external_assets

for source in inspection.images:
    assert source.startswith("data:image/webp;base64,")
    image = base64.b64decode(source.split(",", 1)[1], validate=True)
    assert image[:4] == b"RIFF" and image[8:12] == b"WEBP"

print("Structure autonome et contacts vérifiés.")
print("SHA-256 :", hashlib.sha256(page).hexdigest())
```

Ce contrôle vérifie des propriétés ciblées de la correction. Il ne remplace pas un contrôle visuel dans le navigateur ni une vérification du résultat sur l’hébergement.

## 17. Organisation du code à adopter à la reprise

Deux situations possibles :

| Situation | Action |
|---|---|
| Codex dispose du dépôt initial complet | Garder son historique, comparer la version autonome avec les sources, puis réintégrer les corrections de manière cohérente |
| Codex dispose uniquement du dossier de passation et du dernier ZIP | Partir du HTML autonome, qui contient toutes les ressources ; créer un dossier de projet propre avant d’y organiser la maintenance |

Pour la maintenance, une séparation entre sources éditables et livrable autonome est recommandée. Ce travail de réorganisation **n’a pas encore été réalisé**.

Organisation proposée, à adapter à l’existant :

| Emplacement proposé | Rôle |
|---|---|
| `src/index.html` | HTML éditable, avec l’adresse officielle |
| `src/styles.css` | CSS séparé pour la maintenance |
| `src/script.js` | JavaScript séparé pour la maintenance |
| `src/assets/` | Deux photographies et futurs visuels validés |
| `dist/index.html` | Livrable autonome généré |
| `scripts/` | Éventuel générateur déterministe du livrable |
| `README.md` | Installation, édition, validation et publication |
| `ASSETS.md` | Origine, droits et crédits des images |
| `CHANGELOG.md` | Journal des corrections |

Ne pas créer cette structure en parallèle d’un dépôt existant sans choisir clairement la source de référence. Le premier objectif reste de publier la correction déjà préparée et de la vérifier.

## 18. Plan d’action priorisé

| Priorité | Action | Critère de fin |
|---|---|---|
| P0 | Identifier le dernier HTML et contrôler son empreinte | Bonne version confirmée |
| P0 | Vérifier l’état public actuel | Écart entre fichiers prêts et production établi |
| P0 | Obtenir un accès sécurisé utilisable et confirmer la racine du sous-domaine | Cible de déploiement certaine |
| P0 | Sauvegarder puis remplacer `index.html` | Nouveau fichier effectivement servi |
| P0 | Vérifier les photos et l’adresse FFT | Deux images visibles, sept liens corrects, Hotmail absent |
| P0 | Contrôler mobile, navigation et liens essentiels | Aucun blocage du parcours principal |
| P1 | Réconcilier le dépôt et la version autonome | Pas de risque de redéployer la version ancienne |
| P1 | Actualiser README, crédits et journal | Documentation conforme au résultat publié |
| P1 | Clarifier le HTTPS et l’adresse publique durable | Solution décidée puis vérifiée, sans redirection cassée |
| P1 | Valider les informations de saison avec le club | Horaires / tarifs / modalités publiables disponibles |
| P1 | Demander le logo et des photos réelles utilisables | Visuels fournis et validés pour publication |
| P2 | Étudier un alias FFT simplifié | Accord et fonctionnement confirmés avant affichage |

Les fonctionnalités nouvelles, telles qu’un agenda administrable ou un formulaire serveur, ne font pas partie de la correction demandée. Les cadrer séparément si l’utilisateur les demande.

## 19. Critères d’acceptation de la reprise

- [ ] La racine o2switch du bon sous-domaine est identifiée.
- [ ] L’ancien fichier est sauvegardé.
- [ ] Le site répond avec la nouvelle page.
- [ ] Les deux images sont visibles sur mobile et sur ordinateur.
- [ ] Le site n’utilise plus les URL des images bloquées pour son affichage.
- [ ] Les sept liens de contact utilisent `23310230@fft.fr`.
- [ ] Aucune adresse `TCLongages@fft.fr` non validée n’est publiée.
- [ ] L’ancien Hotmail n’est plus dans la page active.
- [ ] Le menu, les ancres et les questions dépliantes fonctionnent.
- [ ] Les liens Ten’Up et Facebook sont conservés.
- [ ] Les crédits des photographies et la mention d’illustration restent visibles.
- [ ] Aucun autre site du compte o2switch n’a été modifié.
- [ ] La version publiée et la source de référence sont identifiées dans la documentation.
- [ ] Le compte rendu final distingue les contrôles réalisés des points encore non vérifiés.

## 20. Questions restantes réellement utiles

Pour terminer la mise en ligne :

1. Quelle est la racine exacte indiquée pour le sous-domaine dans cPanel ?
2. Quel accès sécurisé est disponible dans la nouvelle session Codex ?
3. Le fichier autonome a-t-il déjà été déposé manuellement depuis le dernier contrôle ?

Pour les évolutions suivantes :

4. Quelle adresse publique durable et quelle configuration HTTPS le club souhaite-t-il ?
5. Quels logo, photos, horaires, tarifs et informations de saison sont validés ?
6. La FFT permet-elle la création de l’alias souhaité ?

Ne pas redemander le nom du club, sa commune, l’adresse officielle fournie ou la préférence o2switch : ces points sont déjà établis.

## 21. Sources techniques et limites de cette passation

### Références documentaires

- [Gestionnaire de fichiers cPanel — permissions](https://docs.cpanel.net/cpanel/files/file-manager/#update-file-or-folder-permissions).
- [Gestionnaire de fichiers o2switch](https://faq.o2switch.fr/cpanel/fichiers/gestionnaire-fichiers-web/).
- [Configuration d’un sous-domaine o2switch](https://faq.o2switch.fr/cpanel/domaines/configuration-sous-domaine/).
- [Connexion cPanel o2switch](https://faq.o2switch.fr/guides/cpanel/comment-se-connecter/).
- [Let’s Encrypt o2switch](https://faq.o2switch.fr/cpanel/securite/lets-encrypt-ssl-gratuit/).

### Niveau de preuve

Le dossier s’appuie sur les demandes de l’utilisateur, les échanges de la session, les fichiers locaux inspectés, les archives effectivement produites, les contrôles structurels et le dernier contrôle HTTP daté.

Il ne prouve pas une connexion cPanel réussie, une publication de la correction, une configuration HTTPS valide, la délivrabilité de la boîte FFT, la disponibilité d’un alias ni une validation officielle des photos ou du logo par le club.

### Règle de clôture

Ne déclarer « publié et corrigé » qu’après observation de la nouvelle page sur la destination convenue et vérification des deux images et des liens de contact. Si une connexion ou un accès manque, conserver le travail préparé et nommer précisément le blocage.

---

**Fin du dossier de reprise — TC_Longages — 15 septembre 2026.**
