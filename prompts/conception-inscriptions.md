---
project: TC_Longages
document_type: prompt-reference
title: Contexte et prompt de conception des inscriptions fourni par l'utilisateur
status: reference
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - prompt
  - inscriptions
  - source-utilisateur
---

Cette référence conserve le document utilisateur reçu le 16 septembre 2026. Son corps est conservé sans réécriture. La proposition courante est dans [le processus d'inscription](../docs/processus-inscriptions.md) ; elle distingue les choix confirmés, les propositions et les fonctionnalités réellement présentes. Les instructions du prompt ne constituent pas une autorisation d'implémentation, de création d'accès, de collecte réelle ou de publication.

Origine : `pièce utilisateur tennis_club_longages_contexte_prompt.md (original conservé hors dépôt)`.
Empreinte SHA-256 du fichier reçu : `5c5804c70788247681157e32a3fe7f8173b4039c0f991bab53892575d2e539b1`.

Les deux fiches PDF évoquées ont été retrouvées dans `Inscriptions_adhérents/` et examinées ; leurs liens et limites figurent dans la proposition. Le présent prompt est une référence historique de conception, pas une seconde source du cahier des charges courant.

---

# Tennis Club de Longages — Contexte & Prompt de conception du processus d’inscription

## 1. Objet du document

Ce document rassemble :

- le **contexte fonctionnel** du projet ;
- les **contraintes techniques** actuelles ;
- les **documents de référence** ;
- le **périmètre retenu** pour la première version ;
- les **principes de conception** à respecter ;
- le **prompt final** destiné à ChatGPT pour produire le processus cible et le premier cahier des charges.

L’objectif est de disposer d’un document unique, réutilisable et transmissible, afin de conserver le contexte du projet sans devoir le reconstruire à chaque nouvelle conversation.

---

## 2. Contexte du projet

Le Tennis Club de Longages souhaite moderniser son processus d’inscription pour la saison **2026-2027**.

Le fonctionnement actuel repose notamment sur deux fiches papier :

1. **Fiche d’inscription École de Tennis**
2. **Fiche d’inscription Adultes**

Ces deux documents servent de référence pour les données à collecter auprès des adhérents.

Le projet vise à **centraliser les demandes d’adhésion**, tout en facilitant l’organisation des cours et entraînements.

### Objectifs principaux

Le futur système doit permettre de :

- centraliser les inscriptions dans une source de données unique ;
- distinguer les adhérents souhaitant uniquement adhérer de ceux souhaitant participer aux entraînements ;
- recueillir les préférences et disponibilités des adhérents souhaitant participer aux entraînements ;
- proposer une saisie de disponibilités de type **Doodle**, directement sur le site du club ;
- permettre à la référente de l’École de Tennis de consulter et filtrer les demandes ;
- aider à constituer les groupes d’entraînement ;
- conserver temporairement le processus papier ;
- préparer l’intégration future d’un agent IA capable de lire les fiches papier à partir d’une photo.

---

## 3. Documents de référence

### 3.1 Fiche École de Tennis

La fiche École de Tennis comprend notamment :

- identité de l’enfant ;
- date de naissance ;
- adresse ;
- email ;
- téléphone ;
- statut de licencié ;
- dernière année de licence ;
- nombre d’années de pratique ;
- informations du père ;
- informations de la mère ;
- formules proposées ;
- tarifs ;
- règlement ;
- autorisation en cas d’urgence ;
- droit à l’image ;
- données administratives réservées au club.

### 3.2 Fiche Adultes

La fiche Adultes comprend notamment :

- identité ;
- nom de jeune fille ;
- date de naissance ;
- adresse ;
- email ;
- téléphone ;
- statut de licencié ;
- dernière année de licence ;
- nombre d’années de pratique ;
- personne à contacter en cas d’urgence ;
- formules proposées ;
- tarifs ;
- règlement ;
- données administratives réservées au club.

---

## 4. Contexte technique existant

Le site actuel fonctionne avec :

### Front-end

- HTML
- CSS
- JavaScript vanilla
- aucun framework

### Back-end / accès bureau

- serveur local **Node.js** ;
- certaines pages du bureau sont protégées ;
- le serveur Node.js contrôle l’accès aux pages réservées aux responsables du club.

### Conséquence

La solution proposée doit rester compatible avec cette architecture.

Il n’est pas prévu, à ce stade, de migrer vers :

- React ;
- Vue ;
- Angular ;
- une application mobile ;
- un framework front-end complexe.

---

## 5. Périmètre fonctionnel retenu

### Inclus dans la première version

- inscription depuis le site internet ;
- centralisation des inscriptions ;
- saisie des données des adhérents ;
- gestion des données adultes et mineurs ;
- gestion des responsables légaux ;
- gestion des contacts d’urgence ;
- gestion des informations de licence ;
- gestion des formules ;
- gestion des autorisations ;
- indication du souhait de participer aux entraînements ;
- saisie des disponibilités ;
- saisie de plusieurs plages horaires ;
- gestion des préférences ;
- tableau de pilotage pour la référente ;
- filtrage des inscrits ;
- affectation manuelle dans des groupes ;
- stockage de la source de l’inscription ;
- maintien de la fiche papier.

### Hors périmètre pour l’instant

- paiement en ligne ;
- envoi automatique d’emails ;
- envoi de SMS ;
- affectation automatique aux groupes ;
- agent OCR / lecture automatique des fiches ;
- application mobile ;
- migration vers un framework ;
- optimisation automatique des groupes par IA.

---

## 6. Gestion des entraînements

Une question centrale doit être posée dans le parcours :

> **Souhaitez-vous participer aux cours / entraînements organisés par le club ?**

### Si NON

L’adhérent poursuit directement la fin de son inscription.

### Si OUI

Une section supplémentaire est affichée pour recueillir :

- les jours possibles ;
- les plages horaires possibles ;
- les plages préférées ;
- les plages impossibles ;
- les éventuelles contraintes ;
- la flexibilité ;
- des commentaires éventuels.

### Principe important

Les adhérents ne choisissent **pas directement un cours définitif**.

Ils indiquent leurs disponibilités.

La référente de l’École de Tennis utilise ensuite ces informations pour constituer les groupes.

---

## 7. Saisie des disponibilités

Le club souhaite fonctionner avec des **plages horaires**, et non avec des cours prédéfinis.

Exemple de principe :

```text
Lundi
[ ] 16h00 - 17h00
[ ] 17h00 - 18h00
[ ] 18h00 - 19h00
[ ] 19h00 - 20h00
```

Les véritables plages devront rester **paramétrables**.

Pour chaque plage, une logique simple peut être utilisée :

- Indisponible
- Disponible
- Préféré

L’interface doit être pensée pour un usage sur smartphone.

---

## 8. Tableau de pilotage

La référente de l’École de Tennis doit disposer d’un tableau accessible depuis une page protégée du bureau.

Le tableau doit notamment pouvoir afficher :

- nom ;
- prénom ;
- âge ;
- adulte / mineur ;
- formule ;
- niveau / expérience ;
- ancien licencié ou non ;
- souhait d’entraînement ;
- jours disponibles ;
- plages horaires disponibles ;
- plages préférées ;
- contraintes ;
- statut du dossier ;
- groupe attribué ;
- jour du groupe ;
- horaire du groupe ;
- commentaire interne ;
- source de l’inscription.

### Filtres utiles

- jeunes / adultes ;
- âge ;
- formule ;
- niveau ;
- jour disponible ;
- plage horaire ;
- entraînement demandé ;
- dossier complet / incomplet ;
- affecté / non affecté ;
- source web / papier.

---

## 9. Maintien du papier

Le processus papier est conservé.

À court terme :

```text
Fiche papier
→ saisie / traitement manuel
→ base centrale
```

À plus long terme :

```text
Fiche papier
→ photo
→ agent IA
→ extraction
→ vérification humaine
→ JSON normalisé
→ API Node.js
→ base centrale
```

L’agent IA ne doit pas être développé maintenant.

En revanche, le modèle de données doit être conçu pour permettre cette évolution future.

Une propriété de provenance est donc à prévoir, par exemple :

```text
source_inscription = web | papier | autre
```

---

## 10. Principe d’architecture cible

Architecture de référence :

```text
Navigateur
HTML / CSS / JavaScript
        ↓
API Node.js
        ↓
Stockage central
        ↓
Pages protégées du bureau
```

La solution de stockage reste à confirmer.

Les options envisagées peuvent notamment inclure :

- fichier JSON ;
- SQLite ;
- autre solution légère adaptée à une petite association.

Le choix doit être fait en fonction de :

- simplicité ;
- fiabilité ;
- maintenance ;
- sauvegarde ;
- évolutivité ;
- facilité d’utilisation depuis Node.js.

---

## 11. Philosophie du projet

La solution doit rester adaptée à une **petite association sportive**, avec une organisation essentiellement bénévole.

Il faut donc privilégier :

- simplicité ;
- peu de dépendances ;
- maintenance facile ;
- interfaces lisibles ;
- peu d’étapes ;
- pas de duplication des données ;
- architecture légère ;
- évolutivité progressive.

---

# 12. Prompt final à utiliser dans ChatGPT

```text
Tu es un expert senior en conception de processus métier, UX de formulaires, gestion associative et architecture web légère.

Ta mission est de concevoir le PROCESSUS CIBLE D'INSCRIPTION ET DE GESTION DES ENTRAÎNEMENTS du Tennis Club de Longages pour la saison 2026-2027.

Le résultat doit être suffisamment précis pour servir ensuite de cahier des charges à la réalisation du système.

==================================================
1. SOURCES À UTILISER
==================================================

Deux documents sont joints à cette conversation :

- Fiche d'inscription École de Tennis – Saison 2026-2027
- Fiche d'inscription Adultes – Saison 2026-2027

Commence impérativement par analyser ces deux documents.

Ils constituent la source de référence pour :

- les informations personnelles demandées ;
- les coordonnées ;
- les données relatives aux licences ;
- l'expérience du tennis ;
- les parents / responsables légaux ;
- les contacts d'urgence ;
- les différentes formules ;
- les tarifs ;
- les autorisations ;
- les informations actuellement réservées au club.

RÈGLES :

1. Ne modifie aucun tarif provenant des documents.
2. Ne crée pas de nouvelle formule d'adhésion sans la signaler explicitement.
3. Si une information est ambiguë ou illisible, indique-le.
4. Distingue clairement :
   - les données saisies par l'adhérent ;
   - les données calculées automatiquement ;
   - les données renseignées uniquement par le club.

==================================================
2. OBJECTIF DU PROJET
==================================================

Le club souhaite centraliser les demandes d'adhésion et faciliter l'organisation des entraînements.

Le futur système doit permettre :

1. l'inscription des adhérents depuis le site internet ;
2. la centralisation de toutes les inscriptions dans une base unique ;
3. l'identification des adhérents souhaitant uniquement adhérer ;
4. l'identification des adhérents souhaitant également participer à des cours / entraînements ;
5. la collecte de leurs disponibilités sous forme de PLAGES HORAIRES ;
6. la consultation de ces informations par la référente de l'École de Tennis ;
7. la constitution manuelle des groupes à l'aide d'un tableau de pilotage ;
8. le maintien temporaire des fiches papier.

Le système ne doit PAS gérer actuellement :

- le paiement en ligne ;
- l'envoi automatisé des confirmations ;
- l'affectation automatique des adhérents aux groupes.

Ces fonctions pourront être ajoutées ultérieurement.

==================================================
3. CONTEXTE TECHNIQUE EXISTANT
==================================================

Le site actuel fonctionne avec :

FRONT-END
- HTML
- CSS
- JavaScript vanilla
- aucun framework

BACK-END / ACCÈS BUREAU
- serveur local Node.js ;
- certaines pages du bureau sont protégées et accessibles uniquement aux responsables autorisés.

Le processus proposé doit être compatible avec cette architecture.

Évite de proposer une refonte reposant obligatoirement sur React, Vue, Angular ou un autre framework.

Tu peux proposer des composants supplémentaires côté serveur uniquement s'ils sont réellement utiles.

==================================================
4. PRINCIPE FONDAMENTAL
==================================================

Toutes les inscriptions doivent finir dans UNE MÊME SOURCE DE DONNÉES.

Il existe cependant deux canaux d'entrée :

CANAL A
Inscription directement depuis le site internet.

CANAL B
Fiche papier remplie manuellement.

À court terme, les fiches papier continueront d'exister.

À plus long terme, un agent IA sera développé.

La référente prendra une photo de la fiche papier.

L'agent devra :

Photo
→ extraction des informations
→ contrôle
→ transformation dans le même format que le formulaire web
→ intégration dans la base centrale.

NE CONÇOIS PAS cet agent maintenant.

En revanche, conçois dès aujourd'hui la structure des données pour que cette intégration soit possible plus tard sans refaire tout le système.

Prévois notamment un champ :

source_inscription =
- web
- papier
- autre

==================================================
5. CONÇOIS LE PARCOURS D'INSCRIPTION
==================================================

Construis le parcours idéal.

Point de départ :

SITE INTERNET
      ↓
S'INSCRIRE AU CLUB

Le parcours doit déterminer progressivement :

- adulte ou mineur ;
- identité ;
- coordonnées ;
- informations de licence ;
- expérience du tennis ;
- responsable légal si nécessaire ;
- contact d'urgence ;
- formule souhaitée ;
- autorisations lorsqu'elles sont nécessaires ;
- souhait ou non de participer aux entraînements.

Utilise autant que possible une logique conditionnelle.

Exemple :

Adhérent mineur ?
    ↓ OUI
Informations responsables légaux
    ↓
Autorisations
    ↓
Suite du formulaire

Évite de présenter à l'utilisateur des champs qui ne le concernent pas.

==================================================
6. QUESTION CENTRALE : ENTRAÎNEMENTS
==================================================

Une étape spécifique doit demander :

"Souhaitez-vous participer aux cours / entraînements organisés par le club ?"

Si NON :

→ l'utilisateur termine son inscription.

Si OUI :

→ afficher une nouvelle section permettant de renseigner ses préférences et disponibilités.

Cette section doit être pensée comme un Doodle intégré au site.

L'adhérent NE CHOISIT PAS directement son cours définitif.

Il communique ses possibilités.

==================================================
7. DISPONIBILITÉS : PRINCIPE
==================================================

Le club souhaite collecter des PLAGES HORAIRES et non des créneaux de cours définitifs.

Exemple :

Lundi :
□ 16h00 - 17h00
□ 17h00 - 18h00
□ 18h00 - 19h00
□ 19h00 - 20h00

Mardi :
...

Les véritables horaires seront définis ultérieurement par le club.

Ne les invente donc pas.

Conçois un système dans lequel les jours et les plages horaires pourront être configurés facilement.

Pour chaque plage horaire, envisage une logique simple du type :

- Indisponible
- Disponible
- Préféré

L'interface doit être particulièrement simple sur smartphone.

L'adhérent doit pouvoir sélectionner plusieurs possibilités.

Prévois également :

- contraintes éventuelles ;
- commentaires ;
- niveau / expérience si utile à l'organisation des groupes ;
- souplesse horaire éventuelle.

Évite toutefois les informations inutiles ou déjà demandées dans l'inscription.

==================================================
8. PARCOURS UTILISATEUR À PRODUIRE
==================================================

Présente le processus complet sous cette forme :

ÉTAPE

Objectif :
...

Utilisateur :
...

Système :
...

Club :
...

Données générées :
...

Utilise cette structure pour chaque étape importante.

==================================================
9. FORMULAIRE NUMÉRIQUE
==================================================

Construis ensuite la structure complète du formulaire.

Présente-la dans un tableau :

| Section | Champ | Type | Obligatoire | Condition d'affichage | Stockage |

Exemples de types :

- texte
- email
- téléphone
- date
- case à cocher
- bouton radio
- liste
- zone de texte
- choix multiple

Identifie notamment :

CHAMPS COMMUNS

CHAMPS ADULTES

CHAMPS MINEURS

RESPONSABLES LÉGAUX

LICENCE

EXPÉRIENCE TENNIS

CONTACT D'URGENCE

FORMULE

AUTORISATIONS

ENTRAÎNEMENTS

DISPONIBILITÉS

INFORMATIONS ADMINISTRATIVES

==================================================
10. SÉPARER FRONT-OFFICE ET BACK-OFFICE
==================================================

Détermine précisément quelles informations sont :

A — visibles et modifiables par l'adhérent ;

B — créées automatiquement par le système ;

C — accessibles uniquement depuis le bureau du club.

Par exemple :

date_inscription → automatique

statut_dossier → bureau

groupe_attribue → bureau

commentaire_interne → bureau

source_inscription → automatique ou bureau

==================================================
11. TABLEAU DE PILOTAGE
==================================================

La référente de l'École de Tennis doit disposer d'une page protégée du bureau.

Cette page doit devenir son outil principal d'organisation.

Conçois le tableau idéal.

Il doit notamment permettre de voir :

- Nom
- Prénom
- Âge
- Mineur / adulte
- Formule
- Niveau / expérience
- Ancien licencié ou non
- Entraînement demandé ou non
- Jours disponibles
- Plages horaires disponibles
- Plages préférées
- Contraintes
- Statut du dossier
- Groupe attribué
- Jour du groupe
- Horaire du groupe
- commentaire interne
- source de l'inscription : web / papier

Propose également les filtres réellement utiles.

Exemples :

- adultes / jeunes ;
- âge ;
- formule ;
- niveau ;
- disponibilité un jour donné ;
- disponibilité sur une plage donnée ;
- entraînement demandé ;
- dossier complet / incomplet ;
- affecté / non affecté ;
- source web / papier.

Le tableau doit rester simple.

Évite un outil surchargé.

==================================================
12. CRÉATION DES GROUPES
==================================================

La référente reste décisionnaire.

Le système doit seulement l'aider.

Décris donc une méthode semi-assistée.

Exemple :

ÉTAPE 1
Filtrer par âge / catégorie.

ÉTAPE 2
Regrouper les niveaux compatibles.

ÉTAPE 3
Comparer les disponibilités communes.

ÉTAPE 4
Identifier les plages permettant de réunir le plus de joueurs compatibles.

ÉTAPE 5
Créer manuellement un groupe.

ÉTAPE 6
Affecter les adhérents.

ÉTAPE 7
Enregistrer jour et horaire.

Indique également quelles informations pourraient à l'avenir permettre à un agent IA de suggérer des groupes.

Ne crée pas cet agent maintenant.

==================================================
13. STATUTS
==================================================

Propose une liste MINIMALE de statuts.

Ils doivent permettre de savoir immédiatement où en est une inscription.

Évite plus de 6 à 8 statuts si ce n'est pas indispensable.

Prends en compte notamment :

- dossier reçu ;
- dossier incomplet ;
- disponibilités à renseigner ;
- prêt pour affectation ;
- groupe affecté ;
- inscription finalisée.

Tu peux améliorer cette organisation.

==================================================
14. MODIFICATION DES DISPONIBILITÉS
==================================================

Prévois que l'adhérent puisse modifier ses disponibilités jusqu'à une date limite décidée par le club.

Décris :

- comment retrouver son inscription ;
- comment éviter qu'un autre utilisateur puisse modifier son dossier ;
- comment enregistrer la dernière modification ;
- comment signaler dans le tableau de pilotage qu'une disponibilité a été modifiée.

Reste compatible avec l'architecture Node.js existante.

==================================================
15. BASE DE DONNÉES / MODÈLE DE DONNÉES
==================================================

Propose un modèle de données propre et évolutif.

Ne duplique pas inutilement les informations.

Présente les principales entités.

Par exemple :

ADHERENT

INSCRIPTION

FORMULE

RESPONSABLE_LEGAL

CONTACT_URGENCE

DISPONIBILITE

GROUPE_ENTRAINEMENT

AFFECTATION_GROUPE

AUTORISATION

Ne considère pas ces noms comme imposés : améliore le modèle si nécessaire.

Pour chaque entité indique :

- rôle ;
- principales données ;
- relation avec les autres entités.

Puis donne un exemple JSON simplifié représentant UNE inscription.

Cet exemple JSON est important car il servira plus tard de format cible pour l'agent chargé de récupérer les informations depuis les fiches papier.

==================================================
16. ARCHITECTURE TECHNIQUE
==================================================

Propose une architecture adaptée au contexte existant :

Navigateur
HTML / CSS / JavaScript
        ↓
API Node.js
        ↓
Stockage central
        ↓
Pages protégées du bureau

Précise :

- ce que fait le navigateur ;
- ce que doit faire Node.js ;
- comment stocker les inscriptions ;
- comment charger le tableau de pilotage ;
- comment protéger les actions administratives.

Si plusieurs méthodes de stockage sont possibles, compare uniquement les options adaptées à une petite association.

Par exemple :

- fichier JSON ;
- SQLite ;
- autre solution légère pertinente.

Pour chacune :

Avantages
Limites
Évolutivité

Puis indique laquelle semble la plus adaptée AU CONTEXTE TECHNIQUE décrit, avec tes critères.

==================================================
17. COMPATIBILITÉ AVEC LES FICHES PAPIER
==================================================

Ajoute une section dédiée au futur processus :

FICHE PAPIER
      ↓
PHOTO
      ↓
AGENT IA
      ↓
EXTRACTION
      ↓
VÉRIFICATION HUMAINE
      ↓
JSON NORMALISÉ
      ↓
MÊME API NODE.JS
      ↓
MÊME BASE

Explique uniquement quelles précautions de conception prendre aujourd'hui pour rendre cela possible.

Ne développe pas le fonctionnement de reconnaissance d'image.

==================================================
18. EXPÉRIENCE MOBILE
==================================================

Le formulaire sera probablement souvent utilisé depuis un smartphone.

Définis les règles UX principales :

- formulaire découpé en étapes ;
- gros boutons ;
- champs adaptés au clavier mobile ;
- pas de tableau horizontal difficile à utiliser ;
- sélection facile des disponibilités ;
- affichage progressif des champs conditionnels ;
- résumé avant validation ;
- possibilité de revenir en arrière sans perdre les données.

Propose notamment une interface mobile adaptée à la saisie des plages horaires.

==================================================
19. POINTS DE VIGILANCE
==================================================

Identifie séparément :

- données personnelles ;
- données concernant les mineurs ;
- droit à l'image ;
- autorisations ;
- sécurité des pages du bureau ;
- sauvegarde des inscriptions ;
- possibilité de corriger une erreur ;
- traçabilité minimale.

N'invente pas de règles juridiques.

Indique clairement les points devant faire l'objet d'une validation par le club.

==================================================
20. CE QU'IL NE FAUT PAS CONCEVOIR MAINTENANT
==================================================

Ne développe pas :

- paiement en ligne ;
- emailing automatique ;
- SMS ;
- agent OCR ;
- création automatique des groupes ;
- application mobile ;
- migration vers un framework.

Ils pourront constituer des phases ultérieures.

==================================================
21. LIVRABLE ATTENDU
==================================================

Produis la réponse exactement dans cet ordre :

1. Synthèse du processus cible

2. Diagramme global du parcours

3. Parcours détaillé de l'adhérent

4. Structure complète du formulaire

5. Gestion des disponibilités

6. Tableau de pilotage de la référente

7. Processus de création des groupes

8. Statuts d'une inscription

9. Gestion des modifications

10. Modèle de données

11. Exemple JSON d'une inscription

12. Architecture HTML / JavaScript / Node.js

13. Comparaison des solutions de stockage

14. Compatibilité future avec les fiches papier et l'agent IA

15. Recommandations UX mobile

16. Points de vigilance

17. Plan de mise en œuvre

Pour le plan de mise en œuvre, propose des phases du type :

PHASE 1
Modèle de données

PHASE 2
Formulaire d'inscription

PHASE 3
Collecte des disponibilités

PHASE 4
API et stockage

PHASE 5
Tableau de pilotage

PHASE 6
Tests

PHASE 7
Mise en production

Adapte ces phases si nécessaire.

==================================================
22. CONTRAINTE DE RÉPONSE
==================================================

Je veux une proposition CONCRÈTE.

Évite :

- les généralités ;
- les formulations vagues ;
- les architectures disproportionnées ;
- les technologies inutiles ;
- la complexité destinée à une grande entreprise.

Raisonne comme si le système devait être maintenu par une petite association sportive et des bénévoles.

Lorsqu'un choix reste à faire par le club, utilise clairement :

[À DÉCIDER PAR LE CLUB]

Lorsqu'une information doit être paramétrable, utilise :

[PARAMÉTRABLE]

Lorsqu'il s'agit d'une amélioration future :

[PHASE ULTÉRIEURE]

Termine ta réponse par :

"PROCHAINE ÉTAPE RECOMMANDÉE"

et indique quelle partie doit être conçue ou développée en premier avant d'écrire le code.
```

---

## 13. Utilisation recommandée du prompt

Pour obtenir le meilleur résultat :

1. ouvrir une nouvelle conversation ChatGPT ;
2. joindre les deux fiches d’inscription PDF ;
3. coller le prompt ci-dessus ;
4. demander à ChatGPT de produire la totalité du livrable ;
5. conserver la réponse comme base du cahier des charges ;
6. traiter ensuite séparément :
   - le modèle de données ;
   - l’API Node.js ;
   - le formulaire ;
   - le tableau de pilotage.

---

## 14. Prochaine étape logique

La prochaine étape recommandée est de produire le **modèle de données détaillé** avant de commencer le développement.

Ce modèle servira de contrat commun entre :

- le formulaire web ;
- l’API Node.js ;
- le tableau de pilotage ;
- les fiches papier ;
- le futur agent IA.

Une fois le modèle stabilisé, il sera possible de définir proprement les routes API et la structure du stockage.
