---
project: TC_Longages
document_type: local-framework-operation
title: Utiliser le suivi interactif local et terminer son installation
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [framework, installation, suivi, controle-utilisateur]
---

# Le suivi interactif du projet

L’interface HTML permet de consulter les huit phases, lire leurs dossiers, enregistrer des notes et préparer les décisions. Elle fonctionne sur ce poste à [l’adresse locale du suivi](http://127.0.0.1:4181/). Le suivi est distinct de l’aperçu du site V1 au port 4180 et du Drupal local au port 4182. Le fichier `tableau-de-bord.html` est une vue statique de consultation : pour saisir une revue, utiliser l’adresse du suivi interactif.

Le moteur interactif complète les vues statiques de [suivi](suivi-chantier/tableau-de-bord.md). L’identité du responsable est **déclarée localement, sans authentification distante**. Toute personne disposant de ce compte Windows peut accéder aux fichiers et à cet outil. Le moteur ne fournit ni comptes Drupal, ni collaboration distante, ni bouton de livraison.

## Démarrer et arrêter

Depuis le dossier `Site_Internet`, avec Node.js 22.9.0 minimum :

```powershell
node scripts/framework-server.mjs
```

Ouvrir ensuite `http://127.0.0.1:4181/`. Le processus reste dans le terminal ; `Ctrl+C` l’arrête et libère son verrou. Le serveur écoute uniquement `127.0.0.1`. Ne pas publier son port sur Internet et ne pas transférer ses sources, son profil ou son suivi dans la racine publique d’o2switch.

Un seul moteur peut écrire. Si un arrêt brutal laisse `.local/framework-runtime.lock`, vérifier d’abord que le processus indiqué dans ce fichier est arrêté, puis retirer uniquement ce verrou local périmé. Ne jamais retirer le verrou d’un moteur encore actif.

La recette automatisée dédiée utilise des dossiers temporaires fictifs, indépendants du vrai suivi :

```powershell
node --test tests/framework-server.test.mjs
```

Ces tests couvrent les routes locales, les refus d’origine et de jeton, les écritures et sauvegardes, les conflits de révision, la séparation des décisions, l’exception d’installation et l’absence de livraison. La recette visuelle et la recette sur l’hébergement sont des vérifications distinctes ; ce guide n’atteste pas leur réussite.

## Préparer une revue

1. Choisir une phase et consulter ses dossiers. Les fichiers Markdown déclarés s’ouvrent en lecture seule. Le lien **Renseigner ma revue** mène directement à la zone de saisie.
2. Cocher les critères effectivement vérifiés, puis écrire un commentaire d’au moins **trois caractères** pour expliquer son retour ou sa décision. Le compteur distingue les cases cochées dans la saisie des critères déjà enregistrés.
3. Cocher la confirmation personnelle puis utiliser **Enregistrer les critères**. Les cases enregistrées sont liées à l’empreinte des critères et des documents ; leur modification demande une nouvelle revue. Le commentaire reste dans la zone de saisie pour faciliter l’étape suivante, mais la confirmation personnelle est décochée.
4. Si le dossier est remis et complet, relire les justificatifs, confirmer personnellement la nouvelle action, puis utiliser **Valider cette phase**. La validation ne démarre pas la phase suivante.

Les notes et critères sont modifiables même quand la préparation technique du dossier est incomplète. Chaque bouton de progression indique son effet et ce qui manque éventuellement pour le rendre disponible. Une PR, un commit ou des tests manquants peuvent empêcher la validation ; ils n’empêchent pas de renseigner son avis.

Pour une question ou une correction sans décision de phase, utiliser **Enregistrer la note**. Son texte initial est conservé dans le journal et retiré de la zone de saisie après succès. Les cases en cours de saisie restent cochées mais ne deviennent pas des critères enregistrés par cette seule action. Le suivi d’une note ajoute une réponse, un état et, pour une résolution, une référence de preuve.

Les états d’une note sont : enregistrée, lue, en cours, décision nécessaire et résolue. Enregistrer une note ne signifie pas qu’un agent l’a lue ou traitée. Aucun agent permanent ni aucune synchronisation GitHub ne sont lancés par ces boutons.

## Quatre décisions distinctes

| Action | Effet enregistré | Conditions principales |
|---|---|---|
| Soumettre à revue | Date de remise et état « En revue ». | Phase en cours, dépendances valides, documents disponibles, PR et preuves techniques liées à la version courante. |
| Valider cette phase | Décision humaine locale, critères, empreintes et date de validation. | Revue ouverte, critères tous vérifiés et reconfirmés, preuves encore recevables, commentaire et confirmation personnelle. |
| Autoriser la phase suivante | Accord de démarrage pour la phase locale suivante. | Phase précédente validée sur les critères, documents et preuves actuels. La phase suivante reste non démarrée. |
| Démarrer la phase | Date de début et état « En cours ». | Autorisation valable et dépendances valides ; exception locale d’installation décrite ci-dessous. |

**Demander des corrections** remet une phase en revue à l’état « En cours » et conserve la demande dans le journal. Les décisions historiques ne sont jamais effacées. Si des documents ou preuves évoluent après validation, l’accord historique reste visible mais ne permet plus automatiquement de poursuivre.

Les notes et critères peuvent être enregistrés avant la remise d’une PR. La validation de phase exige un contexte technique explicite : commit source, empreinte du candidat, empreintes des documents et critères, PR du dépôt TC_Longages, références de preuves et résultats de tests locaux correspondants. La section **Justificatifs et version présentés** affiche les résultats, leurs auteurs, dates et références. Les liens de PR et de commit sont cliquables lorsqu’ils visent le dépôt du club.

L’interface explique les éléments manquants. Elle ne contacte pas GitHub ou une CI pour vérifier ces déclarations : les références techniques doivent être qualifiées lors du travail sur le lot, puis rattachées à la bonne version. Un résultat de CI réussi, une PR créée ou une remise technique n’ajoutent aucune validation personnelle. Consulter le [suivi canonique](suivi-chantier/suivi-chantier.json) et le [point de session](point-session.md) pour les références réellement enregistrées ; ne pas supposer qu’une PR ou une preuve est prête à partir de sa seule préparation.

## Installation temporaire et retour aux gardes normales

La source [framework/installation.json](../framework/installation.json) conserve la portée de l’exception demandée par l’utilisateur, son auteur déclaré, sa référence, son échéance et les critères de réactivation.

Quand cette exception est active et non expirée, elle permet seulement de **démarrer les travaux locaux d’installation des phases 0 à 3** avant validation de toutes les dépendances. Elle ne coche aucun critère, ne valide aucune phase et ne crée aucun accord de publication. Chaque démarrage effectué par l’utilisateur dans l’écran produit sa propre trace liée à l’autorisation d’installation.

Les protections de boucle locale, d’origine, de jeton, des données et des révisions restent actives pendant toute l’installation. Les phases 4 à 7 sont consultables ; le moteur n’offre aucune action d’autorisation ou d’exécution distante pour ces phases.

Après qualification de l’installation locale, l’opérateur remet le fichier d’installation en état `secured` et y consigne la preuve et la date de fermeture. Cette opération restaure les gardes normales de progression. L’exception d’installation a été refermée ; l’état canonique reste dans le fichier lié ci-dessus. La préparation de la PR ne la réactive pas. Une exception expirée n’autorise plus de nouveau contournement, même si son état reste `active`. Toute intervention externe reste soumise à ses propres accords, accès, sauvegardes et vérifications selon [les commandes sensibles](commandes-sensibles.md).

## Conservation et conflits

| Source | Responsabilité |
|---|---|
| [Profil](../framework/profil-projet.json) | Projet, responsables déclarés, environnements et garde-fous. |
| [Suivi JSON](suivi-chantier/suivi-chantier.json) | Phases, décisions, notes, réponses, événements et preuves. |
| [Installation](../framework/installation.json) | Exception temporaire d’installation et sa fermeture. |
| Dossiers Markdown déclarés dans le suivi | Versions des livrables examinés. |
| `.local/framework-backups/` | Copie privée du suivi précédent avant chaque enregistrement. |

Le navigateur transmet une révision couvrant le suivi, le profil, l’installation et les dossiers. Le serveur vérifie cette révision, sauvegarde le JSON précédent, puis remplace la source par une écriture atomique. Pendant la requête, les commandes et la saisie sont brièvement figées pour éviter de perdre un changement de phase ou de texte. Le message de résultat apparaît aussi près des boutons de revue.

Une modification concurrente renvoie un conflit : aucune décision n’est ajoutée, et les textes et cases saisis restent dans l’écran. Utiliser **Actualiser en conservant ma saisie**, relire les changements, puis cocher de nouveau la confirmation personnelle. Cette confirmation est décochée quand la révision a changé. Les brouillons non enregistrés restent en mémoire dans l’onglet ; ils ne constituent pas une sauvegarde après fermeture du navigateur.

Après une écriture, les vues statiques sont régénérées. Si cette génération échoue, le message indique que l’enregistrement a réussi mais que la vue doit être régénérée : **ne pas confirmer une seconde fois**. Corriger la cause puis utiliser les commandes de génération décrites dans [le guide du framework](framework-developpement.md).

Le jeton de revue change à chaque lancement du serveur. Une ancienne page ouverte doit être rechargée après redémarrage. Les fichiers `.local/`, les sauvegardes de suivi et les secrets restent hors Git et hors des archives publiques. Aucun mot de passe ou jeton externe ne doit être saisi dans les commentaires.

## Limites et suite

L’interface rend opérationnels les notes et les décisions de revue **sur ce poste**. Elle ne qualifie ni Drupal, ni l’hébergement, ni la protection des branches, ni les preuves techniques déclarées. Les contrôles de CI et le contrôle strict de la checklist de PR sont distincts des règles GitHub qui empêchent effectivement une fusion. Le contrôle `policy` reste rouge tant que les déclarations humaines requises restent décochées ; l’agent ne les remplit pas à la place du responsable. Ces déclarations ne constituent pas une approbation indépendante par une seconde personne. L’état des protections après le passage du dépôt en public par l’utilisateur est décrit dans [le guide du framework](framework-developpement.md). Les contrôles externes, les droits serveur, le stockage partagé, le journal protégé et l’authentification des personnes restent à réaliser avant tout usage collectif distant. La maintenance native du Drupal local reste un mécanisme séparé du moteur de suivi.
