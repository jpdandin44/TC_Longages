---
project: TC_Longages
document_type: local-framework-operation
title: Utiliser le suivi interactif local et terminer son installation
status: active
version: git
created: 2026-09-29
updated: 2026-10-05
owner: jpdandin
tags: [framework, installation, suivi, controle-utilisateur]
---

# Le suivi interactif du projet

L’interface HTML permet de consulter les quatre phases communes, lire leurs dossiers, enregistrer des notes et préparer les décisions. Elle fonctionne sur ce poste à [l’adresse locale du suivi](http://127.0.0.1:4181/). Le suivi est distinct de l’aperçu du site V1 au port 4180 et du Drupal local au port 4182. Le fichier `tableau-de-bord.html` est une vue statique de consultation : pour saisir une revue, utiliser l’adresse du suivi interactif.

Le moteur interactif complète les vues statiques de [suivi](suivi-chantier/tableau-de-bord.md). L’identité du responsable est **déclarée localement, sans authentification distante**. Toute personne disposant de ce compte Windows peut accéder aux fichiers et à cet outil. Le moteur ne fournit ni comptes Drupal, ni collaboration distante, ni bouton de livraison.

## Démarrer et arrêter

Depuis le dossier `Site_Internet`, avec Node.js 22.9.0 minimum :

```powershell
node scripts/framework-server.mjs
```

Ouvrir ensuite `http://127.0.0.1:4181/`. Le processus reste dans le terminal ; `Ctrl+C` l’arrête et libère son verrou. Le serveur écoute uniquement `127.0.0.1`. Ne pas publier son port sur Internet et ne pas transférer ses sources, son profil ou son suivi dans la racine publique d’o2switch.

Un seul moteur peut écrire. Si un arrêt brutal laisse `.local/framework-runtime.lock`, vérifier d’abord que le processus indiqué dans ce fichier est arrêté, puis retirer uniquement ce verrou local périmé. Ne jamais retirer le verrou d’un moteur encore actif.

Un clic volontaire depuis GitHub peut ouvrir l’accueil du suivi. Seule cette navigation de premier niveau vers `/` bénéficie de l’exception : les lectures API, écritures, ressources et incorporations dans une autre page restent refusées depuis une origine externe. Si une ancienne version affiche **« Requête extérieure refusée »**, saisir directement `http://127.0.0.1:4181/` dans la barre d’adresse, puis redémarrer le serveur avec la version corrigée. Cette erreur locale est indépendante de l’installation de Drupal sur l’hébergement.

La recette automatisée dédiée utilise des dossiers temporaires fictifs, indépendants du vrai suivi :

```powershell
node --test tests/framework-server.test.mjs
```

Ces tests couvrent les routes locales, les refus d’origine et de jeton, les écritures et sauvegardes, les conflits de révision, la séparation des décisions, l’exception d’installation et l’absence de livraison. La recette visuelle et la recette sur l’hébergement sont des vérifications distinctes ; ce guide n’atteste pas leur réussite.

## Préparer une revue

1. Choisir une phase et consulter ses PR puis ses dossiers. La liste placée avant **Les dossiers à consulter** contient uniquement les PR explicitement rattachées à cette phase dans le suivi JSON ; leurs états sont lus sur GitHub, avec date de contrôle et actualisation manuelle. Si GitHub est indisponible, un état non vérifié est signalé. Les fichiers Markdown déclarés s’ouvrent en lecture seule. Le lien **Renseigner ma revue** mène directement à la zone de saisie.
2. Cocher les critères effectivement vérifiés, puis écrire un commentaire d’au moins **trois caractères** pour expliquer son retour ou sa décision. Le compteur distingue les cases cochées dans la saisie des critères déjà enregistrés.
3. Cocher la confirmation personnelle puis utiliser **Valider** quand le dossier est remis et complet. Ce seul clic enregistre ensemble les critères cochés et la décision, liés à la version et aux empreintes présentées. Il n'est plus nécessaire d'enregistrer les critères avant de valider. Le serveur refuse les critères incomplets, le commentaire absent, une confirmation manquante ou une version périmée.
4. Utiliser **Revue** pour ouvrir ou reprendre le dossier, puis **Valider** après vérification de la PR candidate fusionnée. La validation passe la phase locale suivante à **En cours**, avec une trace liée à la décision précédente. **Demander des corrections** conserve le retour et remet le dossier en cours. Aucun bouton de démarrage ou d’autorisation de phase ne subsiste.

Les notes et critères sont modifiables même quand la préparation technique du dossier est incomplète. Chaque bouton de progression indique son effet et ce qui manque éventuellement pour le rendre disponible. Une PR, un commit ou des tests manquants peuvent empêcher la validation ; ils n’empêchent pas de renseigner son avis.

Pour une question ou une correction sans décision de phase, utiliser **Enregistrer une simple note**. **Enregistrer les critères** reste disponible pour sauvegarder une revue incomplète sans valider. Le texte initial de la note est conservé dans le journal et retiré de la zone de saisie après succès. Les cases en cours de saisie restent cochées mais ne deviennent pas des critères enregistrés par cette seule action. Le suivi d’une note ajoute une réponse, un état et, pour une résolution, une référence de preuve.

Les états d’une note sont : enregistrée, lue, en cours, décision nécessaire et résolue. Enregistrer une note ne signifie pas qu’un agent l’a lue ou traitée. Aucun agent permanent ni aucune synchronisation GitHub ne sont lancés par ces boutons.

## Décisions et progression

| Action | Effet enregistré | Conditions principales |
|---|---|---|
| Revue | Date de remise et état « En revue » ; peut reprendre une acceptation devenue historique. | Phase en cours ou à requalifier, dépendances valides, documents et preuves techniques liés au candidat. Une PR ouverte peut être examinée. |
| Valider | Décision humaine locale, critères, empreintes, preuve GitHub et date ; passage de la phase locale suivante en cours. | Revue ouverte, PR candidate fusionnée correspondant au candidat exact, tous les critères, commentaire et confirmation personnelle. |
| Demander des corrections | Demande datée, retour du dossier à « En cours », décision antérieure conservée. | Dossier en revue ou validé, commentaire et confirmation personnelle. |

La demande la plus récente du 5 octobre remplace le parcours avec autorisation
de suite : seules ces trois décisions restent proposées. Les anciens événements
de démarrage et d’autorisation sont conservés dans l’historique. Les phases d'hébergement gardent
leurs accords spécifiques ; aucune transition locale n'exécute un déploiement
ou une ouverture publique.

**Demander des corrections** remet une phase en revue à l’état « En cours » et conserve la demande dans le journal. Si une phase locale validée n’est plus recevable pour la version ou le périmètre actuel, elle affiche **Validée historiquement · à requalifier** et propose **Revue**. Cette action exige le commentaire et la confirmation personnels ; elle conserve la décision antérieure et sa référence. Elle n’approuve aucune nouvelle version. Les preuves doivent ensuite être actualisées avant une nouvelle remise et une nouvelle validation humaine. Les dépendances bloquantes indiquent la phase concernée ; les libellés obligatoires des critères restent inchangés.

Les notes et critères peuvent être enregistrés avant la remise d’une PR. La validation de phase exige un contexte technique explicite : commit source, empreinte du candidat, empreintes des documents et critères, PR du dépôt TC_Longages, références de preuves et résultats de tests locaux correspondants. La section **Justificatifs et version présentés** affiche les résultats, leurs auteurs, dates et références. Les liens de PR et de commit sont cliquables lorsqu’ils visent le dépôt du club.

L’interface explique les éléments manquants. Le serveur lit le détail de la PR candidate sur GitHub et le contrôle à nouveau sans cache au moment de valider. Une PR inconnue, en brouillon, ouverte, fermée sans fusion ou impossible à vérifier bloque la validation. Le commit fusionné doit correspondre au candidat ; seuls les cinq reçus et vues opérationnels explicitement exclus du manifeste peuvent différer. La comparaison incomplète est refusée. Une acceptation ancienne sans cette preuve reste historique et doit être reconfirmée après revue. La liste des autres PR est informative ; aucun contrôle de CI n’est déduit de ce statut GitHub. Les références doivent être qualifiées lors du travail sur le lot, puis rattachées à la bonne version. Un résultat de CI réussi, une PR créée, fusionnée ou une remise technique n’ajoutent aucune validation personnelle. Consulter le [suivi canonique](suivi-chantier/suivi-chantier.json) et le [point de session](point-session.md) pour les références réellement enregistrées ; ne pas supposer qu’une PR ou une preuve est prête à partir de sa seule préparation.

## Installation temporaire et retour aux gardes normales

La source [framework/installation.json](../framework/installation.json) conserve la portée de l’exception demandée par l’utilisateur, son auteur déclaré, sa référence, son échéance et les critères de réactivation.

L’ancienne exception couvrait les phases locales historiques 0 à 3. Elle est fermée. Dans le découpage actuel, une éventuelle exception explicitement autorisée peut couvrir seulement les phases locales 0 et 1 ; elle ne peut activer la préproduction ou la production. Elle ne coche aucun critère, ne valide aucune phase et ne crée aucun accord de publication. Les démarrages historiques restent tracés ; cette exception ne rétablit plus les actions retirées du parcours.

Les protections de boucle locale, d’origine, de jeton, des données et des révisions restent actives pendant toute l’installation. Les phases 2 et 3 sont consultables ; le moteur n’offre aucune action d’autorisation ou d’exécution distante pour ces phases.

Après qualification de l’installation locale, l’opérateur remet le fichier d’installation en état `secured` et y consigne la preuve et la date de fermeture. Les gardes de revue, de candidat et de confirmation personnelle restent actives. L’exception d’installation a été refermée ; l’état canonique reste dans le fichier lié ci-dessus. La préparation de la PR ne la réactive pas. Une exception expirée n’autorise plus de nouveau contournement, même si son état reste `active`. Toute intervention externe reste soumise à ses propres accords, accès, sauvegardes et vérifications selon [les commandes sensibles](commandes-sensibles.md).

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
