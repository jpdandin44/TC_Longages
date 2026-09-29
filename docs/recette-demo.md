---
project: TC_Longages
document_type: verification-report
title: Recette de la démonstration partageable
status: active
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - demonstration
  - recette
  - archive
---

# Recette de la visite complète

La démonstration comprend sept pages autonomes dans `prototype/` : vitrine, visite guidée, formulaire adhérent, accueil bureau, gestion des inscriptions, communication et aperçu des actualités. Elle est distincte du prototype protégé de `dist/` et de la vitrine de `release/`. Le [guide de visite](visiter-prototype.md) décrit l'ouverture et le partage.

## Vérifications effectuées

Les résultats ci-dessous décrivent la première recette, avant intégration des visuels fournis. Le paquet a ensuite été régénéré avec le logo JPEG et la communication par affiches. La [recette de mise à jour](recette-demo-o2switch.md) consigne les 70 tests courants et les contrôles navigateur de cette évolution ; la présente section conserve les preuves initiales.

- `npm.cmd run check` : **25 tests réussis sur 25**, dont les 21 contrôles du prototype précédent et quatre contrôles propres à la démonstration (stockage isolé, transitions de dossier/groupe, pages autonomes/liens, routes du serveur).
- `scripts/verify-demo.cjs` exécuté avec Playwright et Chrome sur un serveur local éphémère, dans un profil séparé. [Résultats détaillés](recette/demo/results.json).
- Parcours adulte sans entraînement : champs requis, dépôt fictif, continuité vers le dossier bureau, confirmation et finalisation simulée.
- Parcours mineur : responsable, disponibilités, refus de finalisation sans groupe, puis affectation manuelle compatible.
- Disponibilités et groupes consultés ; lot d'import fictif analysé sans écriture, confirmation ajoutant une seule ligne valide, doublon refusé au second passage.
- Téléchargement d'un vrai CSV de démonstration vérifié, comprenant le dossier fictif importé. Aucun export XLS annoncé comme réalisé.
- Communication : exemple fictif, validation, aperçu ; clics WhatsApp et copie vérifiés sans ouverture de service ni écriture au presse-papiers. Import JSON réel désactivé dans cette visite ; aucune lecture de la clé de stockage habituelle.
- Sept pages sans débordement horizontal à 390 et 320 px. Aucune erreur JavaScript ni requête réseau externe observée pendant ces scénarios.
- Vues de la visite, du tableau bureau, du formulaire mobile et du bureau mobile inspectées visuellement. Ce contrôle ne constitue pas un audit complet d'accessibilité ou un essai sur téléphone physique.

Captures : [visite](recette/demo/parcours-bureau.png), [formulaire](recette/demo/formulaire-bureau.png), [gestion](recette/demo/inscriptions-bureau.png), [échanges](recette/demo/echanges-bureau.png), [formulaire mobile](recette/demo/adherer-mobile.png), [gestion mobile](recette/demo/inscriptions-mobile.png).

## Archive et serveurs

Lors de la première recette, `npm.cmd run demo:package` avait produit `livrables/tc-longages-prototype.zip`, d'une taille constatée de **401 797 octets**. Cette taille historique ne désigne plus le ZIP régénéré avec les visuels. La création utilise toujours une liste explicite de onze fichiers : sept HTML, serveur, lanceur Windows, guide et manifeste. Aucun dossier de comptes, source papier, donnée de navigateur, export privé ou secret ne rejoint l'archive ; le logo et l'affiche exemple expressément fournis sont désormais intégrés aux HTML.

Le ZIP a été rouvert avec la bibliothèque Python indépendante : onze entrées attendues uniquement, contrôle d'intégrité réussi et empreintes des sept pages identiques au manifeste et aux pages générées. Une extraction de recette a servi la visite, le formulaire, le bureau, les inscriptions et la communication avec des réponses 200. Le serveur extrait a été testé sur un port éphémère.

Contrôle des instances actives : `http://127.0.0.1:4174/` répond 200 et ouvre la démonstration ; `http://127.0.0.1:4173/bureau.html` reste en 503, faute de comptes réels. Aucun accès privé n'a été ouvert. Aucun déploiement ni transfert à un tiers n'a été effectué.

Le premier essai d'archivage par script PowerShell était bloqué par la politique d'exécution du poste, et le module `Compress-Archive` n'a pas pu être chargé. Le script final utilise la bibliothèque ZIP .NET sans modification de cette politique. L'archive résultante a passé les contrôles ci-dessus.

## Limites et suite

Le formulaire est une maquette du parcours : identité/contact principal, responsable unique pour l'exemple mineur, formule, expérience, disponibilités et autorisations illustratives. Le dictionnaire complet (adresse, historique de licence, urgence, autres responsables et preuves notamment) reste à intégrer après validation du club. Les champs illustratifs et textes d'autorisation ne valent pas validation des règles réelles.

Les horaires, groupes et capacités sont des exemples. Les actions « Finaliser » et « Affecter » modifient seulement l'état de la démonstration. Les niveaux et choix pédagogiques ne sont pas vérifiés automatiquement. Le stockage n'est ni central ni une authentification ; un profil partagé peut lire ses essais.

L'import XLS/CSV utilise exclusivement un lot fictif intégré. Il ne lit aucun fichier réel. Le véritable importeur, les modèles de fichiers, l'export XLS, les comptes, la reprise sécurisée, les signatures, la base centrale, les sauvegardes métier et les envois automatiques restent à réaliser. Le futur service et l'ouverture réelle exigent des choix et un accord séparés.

Les essais n'ont pas utilisé les données du navigateur de l'utilisateur. Le serveur réel de démonstration reste disponible localement pour sa visite. Le lien local ne permet pas un accès depuis un autre ordinateur ; le destinataire d'une archive lance sa propre copie.

Le socle documentaire et ses liens sont contrôlés en fin de tâche ; les décisions, exigences et descriptions distinguent la maquette livrée et les fonctions prévues. Aucun problème détecté dans le périmètre vérifié ; les limites fonctionnelles ci-dessus constituent les prochaines étapes.
