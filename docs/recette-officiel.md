---
project: TC_Longages
document_type: validation-report
title: Recette locale de l'aperçu officiel
status: active
version: git
created: 2026-09-24
updated: 2026-09-24
owner: jpdandin
tags:
  - officiel
  - recette
  - prototype
---

# Recette locale du 24 septembre 2026

La variante officielle intègre localement l'identité et la navigation G1 et prépare les pages publiques G2. Elle conserve le logo, la photographie du court et la source tarifaire du projet existant. Cette recette ne constitue ni une validation métier par le bureau, ni un GO pour la bêta, ni une preuve de fonctionnement sur o2switch.

## Sources et génération

Les [empreintes des pièces reçues](../data/officiel-sources.json) identifient l'archive officielle, la spécification, les deux classeurs et le prototype G1. Les décisions finales sont interprétées dans le [suivi d'intégration](integration-officiel.md). Les pièces reçues ne sont pas modifiées. La [baseline](../data/baseline-officiel.json) référence les 182 fichiers sauvegardés avant intervention ; elle n'est pas une sauvegarde du serveur distant ou des données du navigateur.

La [configuration](../config/officiel.json) est la source des couleurs, coordonnées et accès rapides. `tclongages.fr` est un domaine prévu ; le compte Google de référence est renseigné sans connexion. Aucune URL Calendar, Forms ou Sheets n'a été inventée. Les équipes restent vides en attente des informations du club.

## Contrôles de l'intégration initiale

Les résultats de cette section concernent la première intégration officielle du 24 septembre, avant l'ajout ultérieur du numéro de rue. Les vérifications propres à cette correction sont consignées dans l'addendum ci-dessous ; le résultat historique de 87 tests n'est pas présenté comme un nouveau passage de la suite complète.

Les fichiers `results.json` et les captures sont réactualisés lors des recettes suivantes : ils décrivent désormais la dernière révision de messagerie. Les journaux Node initial, adresse et messagerie restent séparés. Les tailles et empreintes données dans les sections historiques correspondent à leurs lots respectifs.

| Vérification | Résultat et preuve |
|---|---|
| Construction des quatre variantes | `npm.cmd run check` réussit : vitrine, visite fictive, démonstration hébergeable et aperçu officiel. |
| Tests Node | 87 tests réussis, zéro échec. Après les derniers correctifs de configuration et de construction, la suite complète a été rejouée avec `node --test --test-concurrency=1 tests/*.test.mjs` ; [journal](recette/officiel/tests-node.txt). |
| Recette navigateur | Chromium, serveur local sur port éphémère, sept pages à 1440, 390 et 320 px. [Résultats structurés et empreintes des sept pages](recette/officiel/results.json), 25 captures et 25 observations de mise en page. |
| Navigation | Six raccourcis vérifiés chacun en un clic. Menu ouvert/fermé, touche Échap, focus et clic de destination vérifiés à 1060 px ; passage vers la navigation bureau à 1101 px. |
| Contenu | Logo exact chargé sur les sept pages ; photographie réelle du court, illustration secondaire, neuf tarifs et saison comparés aux sources existantes. |
| Contact | Refus des champs vides/blancs et coordonnées invalides ; acceptation e-mail ou téléphone ; caractères HTML affichés comme texte inerte. Modification masque l'aperçu précédent ; effacement et rechargement vident les champs. |
| Effets externes pendant la recette | Aucune erreur JavaScript/console, requête externe, écriture HTTP, ouverture de fenêtre, transmission mailto ou utilisation de Storage constatée. Les liens publics externes n'ont pas été activés pour qualifier leur service. |
| Serveur et archive | Tests de liste autorisée, routes interdites, méthodes GET/HEAD, refus des fichiers ajoutés/modifiés et concordance du contenu ZIP. Le serveur de revue ne sert ni les sources, ni les fichiers privés. |

Le script reproductible est [verify-officiel.cjs](../scripts/verify-officiel.cjs), qui nécessite Playwright disponible dans le runtime local. La commande de recette est `node scripts/verify-officiel.cjs` avec `NODE_PATH` positionné sur le dossier des bibliothèques du runtime si elles ne sont pas installées dans le projet. Aucun ajout de dépendance de production n'a été effectué pour cette recette.

Les captures [accueil ordinateur](recette/officiel/captures/index-1440.png), [accueil mobile](recette/officiel/captures/index-390.png), [contact à 320 px](recette/officiel/captures/contact-apercu-320.png) et [équipes mobile](recette/officiel/captures/equipes-390.png) ont été inspectées visuellement. Deux sauts de ligne supprimés par les styles historiques collaient des mots sur mobile ; la variante officielle les rétablit. Les captures finales confirment la correction sans modifier la vitrine historique.

## Matrice des parcours G1

Le comptage part de l'accueil, hors ouverture du navigateur. Il qualifie l'accès aux écrans, pas l'usage des services encore absents.

| Intention | Destination | Clics sur ordinateur / mobile | Limite |
|---|---|---|---|
| Découvrir le club | Rubrique du club sur l'accueil | 1 / 1 | Contenu informatif. |
| S'informer sur les compétitions | `competitions.html` | 1 / 1 | Aucune rencontre réelle inventée. |
| Consulter le calendrier | `calendrier.html` | 1 / 1 | Calendar non configuré. |
| Donner ses disponibilités | `disponibilites.html` | 1 / 1 | Aucun formulaire réel connecté. |
| Découvrir les équipes | `equipes.html` | 1 / 1 | Liste des équipes à fournir. |
| Accéder à l'espace des responsables | `espace.html` | 1 / 1 | Écran d'attente, sans connexion ni données privées. |
| Contacter le club | `contact.html` via navigation | 1 / 2 | Aperçu local ; le bouton ne transmet aucun message. |

Le résultat local G1 est acquis pour ces écrans et ces tailles. La validation utilisateur reste distincte. Le lot G2 est partiel car l'acheminement du contact, l'antispam et l'information de collecte ne sont pas qualifiés. G3 à G8 ne sont pas clôturés par ces tests.

## Archive et conditions de revue

L'[archive de revue](../livrables/tc-longages-officiel-apercu.zip) contient exactement dix fichiers : sept HTML autonomes, `robots.txt`, `.htaccess` et `maintenance.active`. Le [manifeste du paquet](../livrables/tc-longages-officiel-apercu.manifest.json) conserve leurs tailles et empreintes. L'archive est destinée à la revue locale, pas au dépôt d'une bêta active.

Le contrôle initial d'intégrité avait confirmé les dix fichiers ZIP, leurs CRC et leur concordance avec les sorties et les empreintes de la recette navigateur initiale. Les cinq pièces sources et la baseline étaient inchangées, ainsi que les 41 fichiers historiques de sources, images et livrables présents dans le périmètre comparé. Cette archive initiale pesait 777 250 octets, avec le SHA-256 `55b96d145f6200575cdd33923499712b59c4966b31a6e1f453d1f4c881579ca2`. Ces valeurs sont historiques ; la correction d'adresse entraîne une nouvelle archive et les preuves courantes figurent dans l'addendum.

La règle `.htaccess` répond **503 sans condition**. Renommer le témoin `maintenance.active` ne lève pas ce refus. Cette configuration est vérifiée statiquement ; aucun Apache distant n'a exécuté ce nouveau paquet dans cette recette. Le cycle Apache testé sur l'ancienne démonstration ne qualifie pas cette variante. Ne pas appliquer ici le guide de basculement de la démonstration HTTP historique.

Pour la revue depuis les sources, `npm.cmd run officiel` reconstruit les pages puis démarre l'aperçu sur `http://127.0.0.1:4180/`. La commande écrit uniquement les sorties locales ; Ctrl+C arrête le serveur. Elle ne modifie pas le domaine ni l'hébergement. L'ouverture d'une bêta exige un paquet adapté, une authentification qualifiée, HTTPS, les droits Google vérifiés et une autorisation explicite.

Le serveur de revue a également été démarré sur le port 4180 : accueil HTTP 200, titre attendu et en-tête `X-Robots-Tag: noindex, nofollow` constatés. Cette disponibilité reste propre à cet ordinateur pendant l'exécution du serveur.

## Révision du 24 septembre — Numéro de rue confirmé

L'utilisateur a confirmé le numéro **35**. L'adresse retenue est désormais **35, chemin de Muret, 31410 Longages**. Cette correction concerne la source de la vitrine, la configuration officielle, l'affichage du contact, les métadonnées d'adresse et la destination du lien d'itinéraire. Elle ne modifie ni les coordonnées électroniques ni les services encore à configurer.

Les contrôles exécutés après cette correction sont les suivants :

| Vérification | Résultat et preuve courante |
|---|---|
| Tests ciblés | Les dix tests de `officiel.test.mjs` et `officiel-server.test.mjs` réussissent, sans échec ; [journal de la correction](recette/officiel/tests-adresse.txt). La suite historique complète de 87 tests n'a pas été rejouée pour ce changement d'adresse. |
| Recette navigateur | Recette complète rejouée avec succès à 14:24:54 UTC : sept pages à 1440, 390 et 320 px, menu à 1060/1101 px et 25 captures actualisées ; [résultats](recette/officiel/results.json). |
| Adresse et itinéraire | Numéro 35 vérifié dans l'accueil, la FAQ, le contact, les métadonnées des sept pages et la destination Google Maps. L'adresse est retrouvée dans la réponse HTTP 200 de l'aperçu sur le port 4180. Aucun itinéraire externe n'a été activé pour ce contrôle. |
| Archive et conservation des sources | Les dix entrées du ZIP ont leurs CRC et empreintes vérifiés ; les sept empreintes HTML de la recette navigateur correspondent aux sorties finales. Les cinq pièces reçues et la baseline restent inchangées. Parmi les 41 fichiers historiques comparés, 40 sont inchangés et `src/index.html` porte uniquement la correction d'adresse autorisée ; [rapport d'intégrité](recette/officiel/integrity.json). |

L'archive issue de cette correction contenait **777 305 octets**, avec le SHA-256 `a61eafc9e4c87fe281fcb602dbab0b3f7483ad8546da8985f24599ce5cc02d18`. Une [copie avant changement de messagerie](../archives/tc-longages-officiel-avant-contact-gmail.zip) conserve cette génération. Elle conserve la fermeture Apache 503 sans condition. Les originaux reçus et la baseline n'ont pas été remplacés. Aucune publication, modification DNS/HTTPS ou opération d'hébergement n'a été effectuée.

Le contrôle documentaire de cette correction porte sur les deux documents modifiés, `changelog.md` et cette recette : métadonnées obligatoires conservées, 37 liens locaux vérifiés et séparation explicite des preuves initiales et des contrôles rejoués. Aucun problème détecté dans ce périmètre.

## Révision du 24 septembre — Contact Gmail et support des tests

L'utilisateur demande de remplacer le contact du club par **tclongages@gmail.com** partout dans les versions courantes et de prévoir **support@tclongages.fr** pour les tests. `config/officiel.json` est la source unique de l'adresse publique via `club.email` ; le marqueur `{{CLUB_EMAIL}}` de la vitrine est résolu par le générateur commun dans toutes les variantes. Le compte Google de référence garde sa valeur. Le contact public est maintenant visible dans les HTML, sans sérialiser les paramètres du compte Google ou une autorisation d'accès.

`contact.testSupport` conserve l'adresse de test avec le statut `planned`. La page Contact l'affiche en texte seul, sans `mailto:`, avec « Adresse prévue, activation à confirmer ». Le contact Gmail reste utilisable par les liens de messagerie ; le formulaire lui-même reste un aperçu sans envoi. Aucune boîte, alias, redirection, connexion Google Workspace ou automatisation d'agent n'a été créée.

| Vérification de la messagerie | Résultat et preuve |
|---|---|
| Construction et tests | `npm.cmd run check` rejoué intégralement : 87 tests réussis, zéro échec ; [journal messagerie](recette/officiel/tests-messagerie.txt). Les tests existants suivent désormais le contact Gmail et le statut prévu du support. |
| Remplacement exhaustif courant | 27 HTML contrôlés dans `dist/`, `release/`, `prototype/`, `demo-o2switch/` et `officiel/` : 43 liens `mailto:` pointent tous vers Gmail, aucun marqueur non résolu ni ancienne adresse. Ancien contact absent des sources de code, scripts, tests et configuration actifs ; [rapport de messagerie](recette/officiel/messagerie.json). |
| Recette navigateur | Sept pages à 1440/390/320 px, navigation 1060/1101 px, 25 captures actualisées. Aucune erreur JavaScript/console, requête externe, écriture HTTP ou effet d'envoi ; [résultats finaux](recette/officiel/results.json). |
| Contact et support | Les trois liens mail de la page Contact ciblent Gmail. Support visible en texte seul et marqué à activer, sans lien mail ni débordement à 1440/390/320 px ; [contrôle dédié](recette/officiel/contact-email-support.json). La capture Contact à 390 px a été inspectée visuellement. |
| Livrables | Les quatre ZIP courants ont été régénérés : aperçu officiel, visite prototype, démo o2switch et vitrine seule. CRC, contenu comparé aux sorties et absence de l'ancien contact contrôlés pour chaque entrée ; empreintes dans le rapport de messagerie. |
| Préservation | Cinq pièces sources reçues et archive de baseline inchangées. Les sources historiques, rapports datés, copies temporaires et archives de référence conservent leurs valeurs de l'époque ; les anciennes adresses n'y constituent plus une consigne de contact. |

L'archive officielle courante pèse **777 449 octets**, SHA-256 `124c36cec9e104089bd1d7f3bde83176005150cc5fd6dfc0487a34e51b2309f2`. Son refus Apache 503 inconditionnel est conservé. Les autres ZIP courants conservent chacun leur périmètre et leurs règles propres ; aucune archive n'est transférée vers l'hébergement.

L'audit de l'authentification confirme **l'absence d'intégration Drupal dans ce projet**. `server/oidc-client.mjs` contient un client OIDC générique expérimental ; `server/settings.mjs` limite le modèle existant à Admin/Bureau, sans rôle Capitaine ou périmètre par équipe. La V1 reste `decision-pending` avec `technology: null`. Aucun secret n'a été lu, aucune configuration de fournisseur ni connexion externe n'a été effectuée. L'adresse e-mail publique ne donne aucun droit de connexion.

Le contrôle final de cette révision porte sur 14 documents modifiés, 233 liens locaux valides, les métadonnées obligatoires et les douze éléments du socle. Les empreintes navigateur correspondent aux sept HTML livrés. La page Contact du serveur local sur 4180 répond HTTP 200 avec Gmail et l'adresse de support, sans l'ancien contact. Le rapport `docs/recette/officiel/documentation-messagerie.json` consigne le périmètre documentaire. Aucun problème détecté dans ce périmètre vérifié ; les activations externes ci-dessous restent explicitement à traiter.

## Limites et suites

- Choix technique de G3 à valider avant implémentation ; les essais OIDC antérieurs ne fournissent pas les rôles Admin/Bureau/Capitaine avec périmètre par équipe.
- Calendar/Forms/Sheets, équipes, responsables et panel de test à fournir ou définir. Les contrôles de partage restent à réaliser sur les ressources réelles.
- Acheminement et traitement du contact, antispam, responsable et durée de conservation à décider. Aucun message ni dossier réel collecté ici.
- Achat du domaine, DNS, HTTPS et configuration o2switch non vérifiés ; aucune publication effectuée.
- Ambiguïté historique des cours adultes à 125/150 € conservée dans la FAQ. Les nouveaux documents ne donnent pas de condition permettant de la résoudre.
- `support@tclongages.fr` reste une adresse prévue : création de boîte ou d'alias, prestataire, destinataires et réception à confirmer avant activation. La messagerie Google Workspace et les permissions des futurs agents restent à qualifier.

Lors de l'intégration initiale, le socle documentaire et les documents impactés ont été actualisés et contrôlés : README, architecture, exigences, roadmap, décisions, changelog, consignes locales, intégration officielle, commandes sensibles et présente recette. Les liens locaux et métadonnées obligatoires étaient valides et les douze éléments du socle existaient. Aucun problème n'avait été détecté dans ce périmètre documentaire. Le contrôle propre à la correction d'adresse est limité aux documents indiqués dans l'addendum ; les fonctions et informations manquantes restent explicitement consignées ci-dessus.
