---
project: TC_Longages
document_type: integration-plan
title: Préparation simulée des actualités pour ADOC
status: active
version: git
created: 2026-09-17
updated: 2026-10-05
owner: jpdandin
tags:
  - adoc
  - tenup
  - communication
  - simulation
---

# Préparation ADOC

## Périmètre V2 locale — 5 octobre

La [communication Drupal du Bureau](../docs/communication-bureau.md) reprend
ces champs et le compteur prudent de 2 000 unités UTF-16. Après validation,
elle permet d'ouvrir ADOC pour une saisie manuelle et de télécharger l'affiche
JPEG. Un dépassement bloque cette préparation, sans tronquer. Le choix Ten'Up
est conservé dans la révision et à reporter personnellement dans ADOC.
Aucune API, session FFT ou publication automatique n'est raccordée.
La description suivante conserve le comportement du prototype navigateur.

ADOC est le quatrième aperçu de la page Communication, après Site, Facebook et WhatsApp. Il prépare localement la représentation d'une actualité validée. **Aucun article n'est envoyé à ADOC ou Ten'Up** : aucun compte, connexion, API, automatisation de navigateur ou presse-papiers réel n'est utilisé par ce bouton.

## Source et portée

La référence est la capture fournie par l'utilisateur le 17 septembre 2026, montrant **Communication → Actualités du club → Création d'un article** dans ADOC. L'écran présente un titre, un éditeur de contenu avec compteur `0/2000`, une photo et un choix de visibilité sur Ten'Up. La zone photo indique un poids maximum de 5 Mo et les formats JPEG/JPG/PNG ; « Non » est sélectionné pour Ten'Up.

Ces éléments sont des observations de cette capture, pas un contrat API ni une qualification du service ADOC actuel. Aucun endpoint de publication, droit de compte ou comportement serveur n'a été vérifié. L'aperçu du projet reprend des champs simples ; il ne reproduit pas l'éditeur riche d'ADOC.

## Données préparées

| Champ | Comportement du prototype |
|---|---|
| Titre | Titre de l'actualité, obligatoire ; aucune limite propre au titre ADOC n'est déduite de la capture. |
| Contenu | Texte puis lien éventuel, séparés par une ligne vide. Maximum 2 000 caractères, lien et séparateurs compris ; aucun texte coupé automatiquement. |
| Photo | Image entière de l'actualité, si présente. JPEG ou PNG, sous le seuil de 5 Mo observé ; l'ajout local courant prépare déjà une copie JPEG de 500 ko maximum. |
| Visible sur Ten'Up | Option locale Oui/Non, « Non » par défaut. Elle ne modifie aucun réglage sur le service réel. |

Le compteur utilise la longueur JavaScript de la chaîne, en unités UTF-16 : certains caractères, notamment des émojis, peuvent compter pour deux. C'est un choix prudent du prototype, sans affirmation sur le comptage exact du serveur ADOC.

Le champ booléen facultatif `adocVisibleOnTenup` rejoint le format éditorial v1 sans changement de clé de stockage. Une ancienne actualité dépourvue du champ est interprétée comme « Non ». Le champ est conservé dans les exports ; l'import JSON du prototype protégé en vérifie le type et distingue les doublons selon cette option. L'import reste bloqué dans les démonstrations.

## Validation et simulation

1. Préparer l'actualité, choisir la visibilité souhaitée dans la maquette et consulter l'aperçu ADOC.
2. Enregistrer puis valider individuellement la révision, image et option Ten'Up comprises.
3. Déclencher la préparation simulée seulement si la révision reste valide, sans modification non enregistrée ni image en cours de préparation.
4. Lire le résultat local ; il ne correspond à aucun enregistrement ou envoi dans ADOC.

Un contenu dépassant 2 000 caractères bloque uniquement la préparation ADOC. Il n'empêche pas de conserver ou valider l'actualité pour les autres aperçus. Une image d'un format non admis pour cet aperçu demande un nouvel ajout local pour la préparer en JPEG ; elle n'est pas envoyée ni convertie silencieusement par ADOC. Toute modification de l'option Ten'Up exige enregistrement et nouvelle validation. Aucun statut « envoyé à ADOC » n'est persisté.

Le module [communication-adoc.js](../src/communication-adoc.js) est la référence exécutable de ces contrôles. Le [workflow de validation](../workflows/publication-controlee.md) conserve la décision par actualité.

## Étape réelle éventuelle

TBD avant une intégration réelle : moyen autorisé de publication disponible auprès d'ADOC, droits du club, authentification, contraintes effectives des champs et médias, effet de la visibilité Ten'Up et confirmation du résultat. Aucune possibilité d'API n'est supposée acquise.

Une connexion, un essai de publication ou une automatisation du service réel demanderait une proposition distincte et l'accord explicite de l'utilisateur. Les [commandes sensibles](../docs/commandes-sensibles.md) restent la référence ; aucune préparation locale actuelle ne vaut autorisation différée d'envoi.
