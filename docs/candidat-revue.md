---
project: TC_Longages
document_type: verification-contract
title: Candidat Git et portée des preuves de revue
status: active
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [framework, git, preuves, revue]
---

# Rattacher les preuves à une version réelle

Le [contrôle du candidat](../scripts/framework-candidate.mjs) relie la revue locale à des fichiers réellement enregistrés dans Git. Il ne crée aucune validation humaine, aucune autorisation de phase et aucune publication. Les fixtures historiques dépourvues de politique Git restent utilisables dans leurs tests isolés ; le projet réel active l’exigence dans `framework/review-policy.json`, avec `project: "tclongages"` et `candidateBindingRequired: true`.

## Périmètre exact

Le manifeste `data/framework-candidate.json` contient le commit source, la liste triée de ses fichiers réguliers, leurs modes Git, identifiants de blobs, tailles et empreintes SHA256. Son empreinte globale est calculée sur une représentation JSON déterministe. Il n’existe pas d’exclusion générale des documents ou des résultats de tests.

Les cinq seules exclusions permettent d’enregistrer ensuite la revue du candidat sans changer son code :

- `docs/suivi-chantier/suivi-chantier.json` : décisions et contexte de la revue ;
- `docs/suivi-chantier/tableau-de-bord.md` et `docs/suivi-chantier/tableau-de-bord.html` : vues générées de ce registre ;
- `data/framework-candidate.json` : manifeste qui ne peut pas s’inclure lui-même ;
- `data/framework-revue-verification.json` : reçu technique des contrôles exécutés sur le commit source.

Un changement de README, de dossier de phase, de configuration, de test, de dépendance verrouillée ou de code produit donc un autre candidat. Les fichiers privés et sorties déjà ignorés par Git restent hors de la liste des sources.

## Préparer et vérifier

Depuis la racine du dépôt, après avoir enregistré les sources dans un commit :

```powershell
node scripts/framework-candidate.mjs prepare HEAD
node scripts/framework-candidate.mjs verify
```

La première commande prépare seulement un manifeste technique. Elle refuse un état source différent du commit ou des fichiers nouveaux non ignorés qui n’y sont pas inclus. La seconde ne modifie aucun fichier et retourne un échec si le candidat ne correspond plus.

Ordre de travail : commit source **C1**, vérification et exécution des tests sur C1, puis reçu et contexte de revue dans un commit **C2** limité aux cinq exclusions. Les résultats désignent C1, et ne prétendent pas que C2 ou l’hébergement ont été testés. Le reçu conserve les dates réelles, les commandes, les résultats et leurs limites ; les résultats historiques ne reçoivent pas rétroactivement un nouveau SHA.

Le champ `reviewContext.candidateManifest` doit désigner exactement `data/framework-candidate.json`. `sourceCommit` et `artifactDigest` doivent correspondre au manifeste, ainsi qu’aux preuves et exécutions de tests référencées. Les empreintes des critères et documents sont calculées séparément par le [moteur de revue](../scripts/framework-store.mjs).

## Contrôles à chaque lecture et décision

Le moteur vérifie que le commit existe et appartient à l’historique de HEAD, recalcule le manifeste depuis ses blobs Git, compare les sources de HEAD, de l’index et du dossier de travail et relève les nouveaux fichiers non ignorés. Il lit aussi les fichiers de travail via `git hash-object` pour ne pas se fier seulement au cache de dates de Git ou au drapeau `assume-unchanged`. Les conversions de fin de ligne déclarées par Git sont respectées : un passage LF/CRLF normalisé ne crée pas une fausse différence.

Les liens symboliques, sous-modules et fichiers sortant du dossier du projet ne sont pas acceptés dans ce contrat initial. La comparaison utilise quelques appels Git groupés, sans téléchargement ni dépendance de CI. Une absence de Git, de manifeste, de fichier ou d’historique requis laisse la revue bloquée ; elle n’efface pas les décisions antérieures.

La révision transmise au navigateur inclut le résultat de cette vérification. Une modification source invalide la portée courante des preuves et retire les actions de validation qui en dépendent, tout en préservant la décision historique et les brouillons du responsable.

## Limites et validation humaine

Le manifeste prouve une correspondance de contenu, pas à lui seul la qualité des tests ni l’identité de la personne qui les a exécutés. Le reçu reste une déclaration technique documentée. La revue locale ne signe pas les décisions et ne se substitue pas à l’authentification future, aux protections du dépôt ou à la recette d’hébergement.

Les critères humains restent à cocher par le responsable. La remise, la validation, l’autorisation de la phase suivante et l’ouverture publique demeurent des actes distincts. Les [tests du contrat](../tests/framework-candidate.test.mjs) emploient des dépôts temporaires isolés et des preuves fictives explicitement nommées ; ils ne modifient pas le vrai registre de suivi.
