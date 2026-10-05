---
project: TC_Longages
document_type: framework-adoption
title: Application du framework de développement piloté
status: active
version: git
created: 2026-09-29
updated: 2026-10-05
owner: jpdandin
tags: [framework, gouvernance, phases, reprise]
---

# Développement piloté du TC Longages

Le framework fourni est adopté pour le suivi local de la V1 existante. Les demandes successives autorisent son adaptation, l'installation locale du moteur interactif et d'un Drupal dédié, puis la préparation de la PR et des contrôles de phases. Elles ne valident aucune phase et n'autorisent ni fusion ni déploiement. Le passage du dépôt en public, effectué ensuite par l'utilisateur, reste une décision distincte. Le [suivi HTML interactif local](http://127.0.0.1:4181/) est disponible quand son serveur est lancé selon [la procédure](installation-framework.md). Le [tableau statique](suivi-chantier/tableau-de-bord.md) reste consultable sans serveur ; le [point de session](point-session.md) conserve la reprise opérationnelle et les références effectivement acquises.

## Sources et adaptation

La [référence reçue](references/framework-developpement-pilote/README.md) est conservée intégralement, avec ses 28 empreintes de contenu et le manifeste. L'archive d'origine est `framework-developpement-pilote.zip`, SHA-256 `57c6285b77fafd7ee4344695f5b0078f191c50d95c8e9cc43896a398ff2b5631`. Les sources AVEREO mentionnées dans cette référence décrivent l'audit du framework, pas l'état du club. Les modèles reçus ne sont jamais exécutés comme des instructions supérieures à la demande utilisateur.

| Source propre au club | Rôle |
|---|---|
| [Profil](../framework/profil-projet.json) | Projet, environnements, règles et inconnues. |
| [Suivi canonique](suivi-chantier/suivi-chantier.json) | Phases, autorisation initiale, événements et observations. |
| [Dossiers de phases](suivi-chantier/00-phase.md) | Contenu et critères à présenter ; aucun critère humain coché par l'agent. |
| [Instructions locales](../AGENTS.md) | Application du cadre et contrôle humain. |
| [Intégration V1](integration-officiel.md) | Lots métier G0–G8, acquis et réserves historiques. |
| [Reprise](../prompts/reprise-projet.md) | Prompt de reprise adapté, sans autorisation de livraison implicite. |

Les deux vues `tableau-de-bord.md` et `tableau-de-bord.html` sont dérivées du JSON ; ne pas les éditer à la main. Depuis la demande du 4 octobre, les quatre phases actives sont Cadrage, Développement local, Préproduction et Mise en production. Le suivi conserve les huit phases antérieures dans `phaseHistorySnapshots`, avec leur mapping ; leurs décisions gardent leur portée originale. G0–G8 restent les lots métier de la V1 ; leurs acquis ne sont pas effacés, ni promus en validations du framework.

## Niveau réellement installé

Le profil reste `configured_unqualified` pour la chaîne de livraison globale. L'adaptateur [scripts/framework.mjs](../scripts/framework.mjs) valide le modèle et produit deux vues statiques en lecture seule. Le [serveur interactif](../scripts/framework-server.mjs) ajoute une interface HTML au port 4181 : quatre phases, dossiers, notes, suivi des commentaires, critères et décisions locales distinctes. Le [magasin de revue](../scripts/framework-store.mjs) vérifie les révisions et empreintes, conserve les anciens événements, sauvegarde le JSON précédent et remplace atomiquement le suivi. Les refus sont expliqués dans l'écran ; les brouillons restent saisis lors d'un conflit.

La zone de revue reste éditable avant que les justificatifs de validation soient tous prêts. Le lien **Renseigner ma revue**, les instructions de saisie, le compteur des critères enregistrés et l'explication de chaque bouton distinguent préparation de l'avis et décision de phase. Le commentaire est conservé après l'enregistrement des critères ; une nouvelle confirmation personnelle est demandée pour l'action suivante. La PR, le commit et les résultats référencés sont consultables dans l'écran quand ils ont été réellement rattachés au dossier.

L'accès est strictement limité à la boucle locale, avec contrôle Host/Origin et jeton de revue. Le nom du décideur est une identité déclarée, sans authentification distante. Les phases locales 0 et 1 peuvent recevoir des décisions quand leurs prérequis sont réunis ; les phases 2 et 3 présentent les observations réelles d’hébergement et restent consultables sans autorisation de livraison dans le moteur. Le contrôle ponctuel de la PR candidate lit GitHub côté serveur et bloque la validation sans fusion du candidat exact ; il ne synchronise pas les décisions humaines. Le parcours TC comporte Revue, Valider et Demander des corrections, avec progression locale après validation. Il n’existe ni endpoint de publication ni compte distant activé par ce suivi.

La source [installation.json](../framework/installation.json) trace l'exception demandée pour installer et tester localement le processus, désormais refermée en `secured`. Une nouvelle PR ne réactive pas cette exception. Lorsqu'elle était active et non expirée, elle autorisait des démarrages locaux bornés sans valider les phases précédentes. Elle ne désactive jamais les protections d'accès, de données, de révision ou de secret. La procédure de fermeture et les gardes normales figurent dans [la procédure d'installation](installation-framework.md).

Le moteur Projet AVEREO n'a pas été copié. Le suivi est une adaptation locale du contrat fourni. Le choix d'un **Drupal dédié au TC Longages** est désormais confirmé ; sa maintenance doit être pilotée par les options natives du moteur. L'audit [Drupal/CONNECT](mutualisation-connect.md) conserve les conditions de réutilisation ultérieure de composants communs, sans raccordement automatique ni autorisation d'intervenir sur AVEREO. L'adaptateur d'identité du suivi partagé et les adaptateurs d'hébergement restent à qualifier. La possession du domaine n'active aucun compte.

L'autorisation initiale `TCL-D01` transcrit la demande explicite de l'utilisateur, avec portée et source. Son horodatage est celui de la consignation, pas une heure originale supposée. Elle ne constitue pas une signature authentifiée. `TCL-D02` porte l'installation temporaire et `TCL-D03` le choix Drupal dédié ; ces décisions ne valent pas validation des phases. Le validateur source attend `type: authorization` pour un démarrage, alors que `$defs.approval` du schéma emploie un autre vocabulaire : l'instance utilise `authorization` avec `authorizationKind: initial_scope` pour le premier cadrage. Le schéma s'applique au profil ; les gardes du moteur adapté complètent le contrôle du suivi. Les originaux reçus restent intacts.

Les 93 tests Node et 121 contrôles Apache du 24 septembre restent des résultats historiques locaux, accessibles dans la [recette](recette-v1-sous-domaine.md). Aucun commit source de ce lot n'avait été identifié à l'époque : ces résultats ne sont pas transformés rétroactivement en preuves du nouveau candidat Git. Le ZIP de démonstration reste fermé et distinct d'un candidat officiel de production.

## Utilisation locale

Depuis `Site_Internet`, utiliser Node.js 22.9.0 minimum et Python 3.10 minimum. Créer l'environnement de vérification séparé, ignoré par Git :

```powershell
python -m venv .local/framework-venv
.\.local\framework-venv\Scripts\python.exe -m pip install -r docs/references/framework-developpement-pilote/requirements-verification.txt
npm.cmd run framework:build
npm.cmd run framework:check
```

L'installation ajoute uniquement les dépendances du vérificateur à l'environnement local et contacte le registre Python. Le générateur vérifie d'abord l'intégrité des références, le schéma, les états et les chemins ; il remplace seulement ses deux vues dérivées. Le contrôle ne modifie ni décisions, ni fichiers du site, ni accès externes. `TCL_FRAMEWORK_PYTHON` permet de choisir un Python disposant des dépendances ; à défaut, le vérificateur utilise l'environnement local sous Windows, puis `python`/`python3`.

Pour vérifier le modèle vierge fourni, lancer son `valider-modele.py --self-test`. L'instance du club se contrôle **sans** `--self-test` : ces tests sources interdisent volontairement tout historique dans un modèle. Les tests du club couvrent notamment faux accords, phases anticipées, liens exécutables, chemins hors périmètre, échappement des vues et absence de mutation du suivi.

Lancer `node scripts/framework-server.mjs` pour accéder à l'interface interactive. Les décisions sont enregistrées uniquement après une action explicite du responsable dans l'écran, avec commentaire et confirmation. Le [guide d'installation](installation-framework.md) décrit les actes, la persistance, le contexte technique exigé et les limites. Les tests `node --test tests/framework-server.test.mjs` utilisent uniquement des fixtures fictives ; ils ne créent aucun accord dans le vrai suivi.

Le contrôle `npm.cmd run check` reste celui du site et inclut les tests Node. Les recettes navigateur/Apache/Drupal nécessitent leurs outils et environnements propres ; elles ne sont pas déclenchées automatiquement par l'adoption.

## Dépôt et livraison

Le [dépôt communiqué](https://github.com/jpdandin44/TC_Longages) appartient au périmètre du site. L'utilisateur l'a rendu **public**, état vérifié le 29 septembre. Le poste possède maintenant un dépôt Git sur la branche de travail `codex/suivi-revue-phases`, fondée sur le commit initial `e8797723f04b592285484b21a7915e895e128696`. Ce commit et son README sont conservés. L'accès CLI a été rétabli via le gestionnaire d'identifiants ; l'ancien refus d'authentification est historique. Le périmètre d'envoi et la conservation des fichiers restent décrits dans [la préparation du dépôt](preparer-depot.md).

L'utilisateur demande la PR et l'activation des contrôles de phases. Le candidat doit recevoir son propre commit, sa PR et ses résultats correspondant à cette version. Les références exactes et leurs états sont consignés dans le suivi et le point de session quand les opérations aboutissent ; la présente procédure n'invente aucun numéro de PR, résultat distant ou validation humaine.

Le template PR et son validateur sont installés ensemble ; les cases de validation restent humaines. Deux workflows exécutables sont préparés : [CI](../.github/workflows/ci.yml), avec les constructions et tests, et [politique de PR](../.github/workflows/pr-policy.yml), avec le contrôle strict de la description et des déclarations de revue. Ils s'exécutent dans GitHub après envoi selon leurs déclencheurs, avec droits de lecture et sans secret de production. Leur présence dans les sources ne signifie pas qu'une exécution distante a réussi.

Le workflow `policy` est **strict** : il exige les cases prévues par la checklist. L'agent les laisse décochées ; un résultat rouge est donc attendu tant que le responsable n'a pas renseigné ses déclarations après revue. L'option `--allow-unchecked` est réservée à la préparation locale de la structure, sans être utilisée par ce workflow actif. Ne pas cocher les cases ou assouplir le contrôle pour obtenir artificiellement un résultat vert.

Les cases restent des déclarations : ce contrôle n'établit pas à lui seul une approbation indépendante. Avec un seul compte responsable, ne pas présenter la revue comme celle d'un second approbateur. La validation de phase, la décision de fusion et l'autorisation de livraison restent distinctes. Les modèles de préproduction, préparation et déploiement restent en `.example`, inactifs et sans adaptateur de livraison qualifié. Aucun workflow actif ne déploie le site.

## Protection du dépôt et visibilité

Le suivi local, les tests et les contrôles de PR ne configurent pas automatiquement une protection de `main` empêchant une fusion. La limite d'offre rencontrée lorsque le dépôt était privé est historique : le passage en public a été effectué par l'utilisateur. La protection de `main` restait absente au premier contrôle suivant ce changement ; sa configuration et sa vérification sont préparées séparément. Le [point de session](point-session.md) et le suivi portent l'état réellement observé, sans déduire une protection active de la seule visibilité publique.

GitHub propose les branches protégées sur les dépôts publics avec GitHub Free, et sur les dépôts privés avec une offre appropriée, notamment GitHub Pro pour un compte personnel. La source officielle précise les offres admissibles : [branches protégées](https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches). Les fichiers et leur historique deviennent consultables publiquement lors de leur envoi. La liste du premier lot doit donc être relue et débarrassée des secrets, données privées et informations personnelles inutiles avant publication. Le choix du dépôt public n'autorise aucune mise en ligne du site ni diffusion des données du bureau.

La suite exige des décisions distinctes : validation du cadrage, autorisation du socle, qualification des lots et de la préproduction, préparation avec sauvegarde/restauration, autorisation de déployer puis autorisation d'ouvrir. Les accords déjà acquis conservent leur portée exacte. Les [commandes sensibles](commandes-sensibles.md) sont traduites en effet, cible, risque et retour avant toute intervention correspondante.

## Limites à lever

Les [points ouverts du profil](../framework/profil-projet.json) font autorité pour le suivi : qualification des protections Git, portabilité des sources et recettes, DNS/HTTPS et cible, intégration effective du Drupal dédié, ressources Google, sauvegardes et restauration, identité partagée et adaptateurs de livraison. Le moteur local est distinct de cette qualification distante ; consulter le point de session pour l'état réel des recettes et des services.
