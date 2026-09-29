---
project: TC_Longages
document_type: verification-report
title: Recette du paquet de démonstration destiné à o2switch
status: active
version: git
created: 2026-09-16
updated: 2026-09-17
owner: jpdandin
tags:
  - recette
  - demonstration
  - o2switch
---

# Recette de la démonstration à déposer

La demande actuelle consiste à préparer une démonstration complète pour que l'utilisateur la dépose lui-même à l'adresse `http://tclongages.daje3540.odns.fr/`, en remplacement de la vitrine. Aucun accès authentifié à cPanel, transfert, changement DNS ou configuration TLS n'a été effectué par l'agent.

Le [guide de dépôt](publier-demo-o2switch.md) est la référence des opérations manuelles, de leur effet et du retour arrière. Masquer les liens publics ne protège pas les pages : une fois ouverte, cette copie est accessible sans authentification et doit rester exclusivement fictive.

## Paquet contrôlé

Le [ZIP](../livrables/tc-longages-demo-o2switch.zip) est construit par `scripts/package-hosted-demo.mjs`, après `scripts/build-demo.mjs` et `scripts/build-hosted-demo.mjs`. Les tailles et empreintes exactes sont conservées dans son [manifeste séparé](../livrables/tc-longages-demo-o2switch.manifest.json), sans duplication manuelle dans ce rapport.

Contrôles exécutés le 17 septembre 2026 :

- Dix entrées à la racine, exactement : sept HTML, `robots.txt`, `.htaccess` et `maintenance.active`.
- Contrôle d'intégrité ZIP/CRC réussi avec `zipfile.testzip()`.
- Chaque entrée est identique à sa sortie dans `demo-o2switch/` ; tailles et SHA-256 vérifiés par rapport au manifeste. L'empreinte du ZIP complet est également vérifiée.
- Aucun compte, secret, serveur Node, fichier de configuration OIDC ou dossier réel dans la liste des fichiers admis. Le manifeste reste hors ZIP.
- Le témoin de fermeture est présent. La génération s'arrête si des fichiers inattendus existent dans le dossier de sortie.

La vérification finale des trois archives régénérées est consignée dans les [résultats ZIP](recette/demo-o2switch/archive-results.json). Les fichiers de chaque archive correspondent aux sorties courantes ; l'affiche intégrée au bouton exemple correspond exactement au JPEG fourni.

## Contrôles automatisés

`npm.cmd run check` : **77 tests réussis, aucun échec**, après intégration de la photo du court, des tarifs et du canal ADOC. Cette commande reconstruit le prototype, la visite fictive et l'adaptation destinée à o2switch avant les tests. Les fichiers de tests sont exécutés successivement : certains reconstruisent les sorties communes ; une exécution simultanée a été observée lisant un HTML pendant sa réécriture. La commande de contrôle a été corrigée pour éviter cette course.

Les sept tests ajoutés pour cette adaptation couvrent les dix fichiers et leurs empreintes, l'absence d'adresses internes dans la vitrine et le formulaire, le dépôt fictif sans lien bureau créé dynamiquement, les ressources autonomes et liens locaux, l'isolement du stockage, les simulations de diffusion et le maintien de la fermeture par défaut. Les tests des autres variantes continuent à passer ; ils ne qualifient pas un fournisseur OIDC ni un service réel.

Les nouveaux contrôles portent sur les octets exacts du logo, sa présence dans les pages et avatars, les anciens brouillons sans image, les affiches sans texte d'accompagnement, la révalidation, les formats/dimensions/quotas et le refus des imports JSON qui tentent de contourner les limites d'image. Le JSON reste désactivé dans la démonstration.

## Mise à jour du logo et des affiches

Le JPEG du logo fourni est intégré aux HTML sans réencodage. Son cadrage est défini par `src/brand.css` ; le remplacement par le vectoriel reste prévu lorsque l'utilisateur le fournira. Les originaux `Images_Photos/Logo.jpeg` et `Affiche.jpeg` sont préservés et leurs empreintes consignées dans la [provenance](../data/source-provenance.json).

L'affiche jointe est disponible dans Communication via **Utiliser l’affiche exemple**, sans validation ni diffusion automatique. Un autre fichier JPEG, PNG ou WebP peut être choisi, avec description alternative. Les limites et le stockage local sont détaillés dans le [guide de visite](visiter-prototype.md). L'ajout d'image ne doit pas être confondu avec l'import JSON d'actualités, qui demeure bloqué dans la démonstration.

`scripts/verify-communication-images.cjs` a vérifié les parcours suivants dans Chrome sur un serveur temporaire local ; les [résultats dédiés](recette/communication-images/results.json) consignent leur réussite :

- Chargement de l'affiche fournie, titre avec texte facultatif, aperçu Site/Facebook/WhatsApp et affichage intégral.
- Annulation puis confirmation de la validation, aperçu bureau et ouverture de l'affiche en grand.
- Rechargement avec conservation de l'image ; modification de description retirant la validation.
- Choix du JPEG local, décodage de PNG/WebP, refus d'un faux JPEG et d'un SVG sans perte de l'image précédente.
- Quota simulé sans écrasement de la dernière sauvegarde ; saisie conservée puis enregistrée après rétablissement.
- Ajout encore en cours ignoré après changement d'actualité, puis retrait volontaire d'une image.
- Rendu avec affiche à 1440, 390 et 320 pixels, sans débordement horizontal, erreur JavaScript ni requête externe.

Lors de la reprise du 17 septembre, un délai de chargement d’affiche a échoué de façon intermittente dans la recette. Les attentes ont été affinées pour suivre la préparation propre à chaque fichier et afficher son diagnostic ; cinq exécutions complètes successives ont ensuite réussi. Aucun défaut applicatif n’a été confirmé et la cause exacte du premier délai reste non établie.

Les captures ordinateur et mobile ont été inspectées. Un texte indicatif restait affiché sous les affiches sans message : il a été retiré avant la dernière recette. La revue a également détecté puis fait corriger l'absence de contrôle des dimensions des images dans les imports JSON de la variante protégée. Aucun problème restant dans ce périmètre vérifié ; aucun audit complet d'accessibilité ni essai sur téléphone physique n'est revendiqué.

## Photo réelle, tarifs et ADOC — 17 septembre 2026

Le visuel principal utilise exactement les octets de `Images_Photos/Image_terrain.jpg`, intégrés aux pages autonomes. Le crédit indique désormais une photo fournie par le club. La seconde photographie reste une illustration extérieure identifiée. Le logo était rangé dans `Images_Photos/Logo.jpeg` ; le chemin de construction a été réaligné, sans modifier ni dupliquer le fichier.

Les deux PDF d'inscription ont été rendus puis contrôlés visuellement sur leurs trois pages. Le [relevé JSON](../data/tarifs-inscription.json) est la source unique des neuf lignes tarifaires de la FAQ et du formulaire fictif. Les deux prix des cours adultes restent sans règle de choix ; remises famille, inclusion de licence et horaires ne sont pas inventés. Les empreintes des PDF sources sont testées. Le [générateur des tarifs](../scripts/tariffs.mjs) injecte les tableaux, les réserves et les formules au build, sans chargement externe ni PDF ajouté à l'archive.

La [recette photo et tarifs](recette/court-tarifs/results.json) vérifie les deux tableaux, neuf lignes, mentions à confirmer, décodage de la photo et absence de débordement à 1440, 390 et 320 pixels. Les captures de la photo et des tableaux ont été inspectées sur ordinateur et mobile.

Le quatrième aperçu ADOC est décrit dans le [contrat de maquette](../api/adoc.md). La [recette ADOC](recette/communication-adoc/results.json) contrôle les quatre aperçus aux trois largeurs, l'affiche entière, le choix Ten'Up Non par défaut puis Oui avec sauvegarde/rechargement et revalidation. La limite de 2 000 caractères est acceptée exactement ; 2 001 caractères restent visibles mais bloquent la préparation ADOC uniquement. Les simulations ne créent aucune requête externe, ouverture, copie ni écriture du stockage. Treize captures ont été produites ; les vues ADOC ordinateur/mobile et WhatsApp mobile ont été inspectées.

La capture ADOC fournie demeure une référence de contexte : ni son identité de compte ni son image ne sont intégrées au site. Ces essais ne qualifient pas ADOC réel. Les comptes, droits et moyens éventuels de publication restent à examiner avant tout service réel.

## Ouverture et fermeture sur Apache local

Lors de la préparation initiale, la configuration a été exécutée sur Apache 2.4, dans un conteneur temporaire accessible uniquement sur `127.0.0.1`, avec `mod_rewrite`, `mod_headers` et `AllowOverride All`. Les fichiers testés étaient une copie du paquet ; le témoin du ZIP demeure actif. Le conteneur a été arrêté après la recette. Les règles et témoins sont inchangés pour ces ajustements ; cette recette Apache antérieure est conservée, sans prétendre à une nouvelle exécution.

| État de la copie testée | Résultat constaté |
|---|---|
| `maintenance.active` présent initialement | `/` et les sept pages répondent 503, en GET et HEAD. |
| Renommage en `maintenance.inactive` | Les mêmes adresses répondent 200, en GET et HEAD. |
| Remise de `maintenance.active` | Les mêmes adresses répondent de nouveau 503, en GET et HEAD. |
| Pendant l'ouverture | `.htaccess` et les deux noms de témoin répondent 403 ; `robots.txt` est accessible. |

Les réponses des pages comportent `Cache-Control: no-store` et `X-Robots-Tag: noindex, nofollow`. Ces consignes ne constituent ni une authentification ni un moyen d'effacer une page déjà chargée. Les [résultats HTTP détaillés](recette/demo-o2switch/apache-results.json) documentent les 51 requêtes de contrôle enregistrées ; le contrôle de `robots.txt` est exécuté séparément.

Cette recette confirme le fonctionnement des règles dans cet environnement local. Elle ne prouve pas la configuration effective d'o2switch, d'un cache intermédiaire ou d'une éventuelle règle héritée du compte.

## Recette navigateur

`scripts/verify-hosted-demo.cjs` a été exécuté avec Chromium/Chrome sur un serveur statique temporaire lié à `127.0.0.1`. Ce serveur ne traite pas `.htaccess` ; la recette Apache ci-dessus couvre séparément ces règles. Les [résultats navigateur et la liste des captures](recette/demo-o2switch/results.json) sont disponibles.

- Aucun lien vers les pages de gestion sur la vitrine et le formulaire, avant comme après dépôt fictif ; le bouton public mène uniquement au formulaire.
- Dossiers adulte et mineur retrouvés par accès direct à `inscriptions.html` dans le même navigateur. Finalisation après confirmation ; pour le mineur, finalisation bloquée tant qu'aucun groupe compatible n'est affecté.
- Aperçu Facebook et validation locale ; WhatsApp et copie interceptés sans onglet externe ni écriture dans le presse-papiers. Import réel désactivé.
- Clés `tcl.hosted-demo.*` utilisées ; les exemples placés dans les espaces de stockage du bureau et de la visite locale restent inchangés.
- Sept pages, étapes du formulaire et confirmations sans débordement horizontal à 390 et 320 pixels ; captures également réalisées sur ordinateur.
- Aucune erreur JavaScript ni tentative de requête externe pendant les parcours testés.

Ces contrôles concernent les parcours simulés. Ils ne qualifient aucun envoi Facebook/WhatsApp/ADOC ni réception réelle d'inscription.

## Cohérence documentaire

Politique documentaire globale appliquée : le socle comporte les six fichiers et six répertoires attendus. Les métadonnées des 25 documents Markdown courants et leurs 247 liens locaux ont été contrôlés, sans lien manquant ni anomalie détectée dans ce périmètre. Les documents historiques sont conservés comme références, hors de cette vérification des métadonnées courantes.

Le [guide de dépôt](publier-demo-o2switch.md) et la présente recette sont maintenus. Les documents actualisés sont le [README](../README.md), l'[architecture](../architecture.md), les [exigences](../requirements.md), la [feuille de route](../roadmap.md), les [décisions](../decisions.md), le [changelog](../changelog.md), les [consignes locales](../AGENTS.md), les [commandes sensibles](commandes-sensibles.md) et le [workflow de publication](../workflows/publication-controlee.md). Ils distinguent désormais le paquet de présentation choisi, la vitrine seule conservée comme option, l'authentification future inactive et les contrôles distants restant à réaliser. La politique globale elle-même n'est pas modifiée.

Pour l'évolution des visuels, ces références ont été réalignées lorsqu'elles étaient impactées, ainsi que le [guide de visite](visiter-prototype.md) et le [contrat de partage WhatsApp](../api/whatsapp.md). Les archives de visite locale et de vitrine seule ont également été régénérées pour rester alignées avec leurs sources ; leurs recettes initiales restent identifiées comme antérieures. Le paquet à déposer demandé par l'utilisateur demeure exclusivement `tc-longages-demo-o2switch.zip`. Le [contrat ADOC](../api/adoc.md) et le relevé tarifaire sont créés pour ces ajustements ; les documents concernés sont alignés sur le code et les limites de la maquette.

## Vérifications restant sur l'hébergement

La racine documentaire exacte, le contenu existant et les éventuelles règles du compte restent **TBD — à relever dans cPanel par l'utilisateur**. Leur impact est le choix du bon dossier, la sauvegarde et la compatibilité du nouveau `.htaccess`.

Avant toute ouverture, l'utilisateur doit vérifier les réponses 503 après dépôt du témoin et de `.htaccess`, puis copier les pages selon le guide. Après son ouverture volontaire, vérifier les parcours ; après fermeture, contrôler de nouveau les accès directs. Une erreur 500 ou une réponse encore mise en cache exige un diagnostic et ne vaut pas fermeture validée.

Le certificat, les comptes du futur service, la base partagée et les imports/exports métier XLS/CSV restent des travaux distincts. Cette archive ne comprend que les exemples, le CSV fictif et l'import simulé de la maquette.
