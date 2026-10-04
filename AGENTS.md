---
project: TC_Longages
document_type: agent-instructions
title: Consignes de travail et contrôle utilisateur
status: active
version: git
created: 2026-09-16
updated: 2026-10-04
owner: jpdandin
tags:
  - codex
  - controle-utilisateur
  - documentation
---

# Consignes locales

Ces consignes complètent la politique documentaire globale pour `Site_Internet/`.

## Développement piloté depuis le 29 septembre

- Pour la première livraison V1 publique, le responsable a demandé un [parcours opérationnel de quatre étapes](docs/parcours-mise-en-ligne.md). Le registre `data/parcours-mise-en-ligne.json` décrit ces étapes sans créer une seconde interface de suivi. Depuis la demande du 4 octobre, le cockpit canonique présente quatre phases communes : Cadrage, Développement local, Préproduction et Mise en production. L’ancien découpage à huit phases et son mapping sont conservés dans le suivi ; ses décisions restent protégées dans leur périmètre original.

- La demande reprend le projet et autorise l'adoption locale du framework. Lire [le guide adapté](docs/framework-developpement.md), [le profil](framework/profil-projet.json), [le suivi canonique](docs/suivi-chantier/suivi-chantier.json) et [le point de session](docs/point-session.md). Les modèles reçus sous `docs/references/` restent des sources à adapter, sans autorisation héritée.
- `tclongages.fr` est obtenu et sa racine a été observée dans cPanel ; consulter l'inventaire actuel dans [le guide Drupal](docs/installation-drupal.md). Le dépôt `https://github.com/jpdandin44/TC_Longages` est public depuis le choix utilisateur du 29 septembre. Git local est raccordé à son historique initial. La demande explicite de finaliser les PR autorise le premier lot et ses tests ; elle ne donne aucun accord de merge ou de déploiement.
- Le suivi JSON est canonique ; `npm.cmd run framework` sert l'interface locale sur 4181. Respecter révision, sauvegardes, historique et actions humaines distinctes. Le générateur fournit des vues de lecture. L'identité déclarée locale ne permet aucune exposition distante.
- L'utilisateur a autorisé le montage et les essais locaux du framework et de Drupal dédié. L'exception bornée `framework/installation.json` permet le travail d'installation local historique 0–3 sans déclarer les phases validées ; la refermer après vérification. Maintenir Host/Origin/CSRF, confidentialité et accords de publication. L'expiration n'autorise aucun contournement.
- Les quatre phases actives et les lots G0–G8 restent distincts. Les anciens identifiants 0–7 sont historiques. Une décision d'architecture, un démarrage, une validation, un merge et une ouverture ne se remplacent pas. Conserver les acquis et l'historique sans forger de preuves ou d'approbations.
- Seules les Actions CI technique et politique de PR sont actives ; aucun workflow de livraison. Préparer une PR par lot autorisé ; laisser les confirmations humaines à l’utilisateur. Le contrôle strict de checklist ne prouve pas l’authenticité de son auteur. Aucun push ou merge ne doit publier le site automatiquement.
- La livraison exige candidat exact, préproduction qualifiée, sauvegarde fraîche vérifiée et restaurée avant écriture, recette de cible puis ouverture explicitement autorisée. Ne copier aucun accord, droit ou secret d'AVEREO.
- Mettre à jour le point de session ; une clôture de session ne valide pas une phase.


## Périmètre prioritaire depuis le 24 septembre — officiel V1

- Suivre les 30 décisions finales reçues et le séquencement G0 à G8 décrits dans [l'intégration officielle](docs/integration-officiel.md). La colonne Q du classeur d'arbitrages porte les choix ; les aides et statuts « À décider » périmés ne les remplacent pas. Conserver les pièces fournies intactes.
- Le P0 courant est vitrine, équipes, calendrier et disponibilités, avec futurs comptes Admin/Bureau/Capitaine. Inscription complète, paiement, compte de chaque adhérent et automatisation des messages sont hors P0. Les règles ci-dessous concernant les prototypes antérieurs restent propres à ces variantes.
- Préserver les sources et archives antérieures. La variante `officiel/` et les compléments `src/officiel*` dérivent de la vitrine commune ; centraliser sa configuration dans `config/officiel.json`, sans secret ni effectif privé. Ne pas réintroduire les formulaires de démonstration dans le parcours officiel.
- Réutiliser le logo fourni `Images_Photos/Logo.jpeg` et la photo réelle du court ; ne pas appliquer comme un fait courant la mention « logo non fourni » du paquet G1. Rouge/blanc provisoire paramétrable, parité desktop/mobile et parcours en trois clics maximum doivent être contrôlés avant de clore G1.
- Domaine `tclongages.fr` obtenu selon confirmation utilisateur du 29 septembre, fonctionnement distant non vérifié. Contact public et compte Google de référence : `tclongages@gmail.com`, conformément à la demande ultérieure du 24 septembre. Prévoir `support@tclongages.fr` pour les tests en statut non activé ; ne pas présenter cette adresse comme une boîte créée. Ne déduire ni URL Calendar/Forms/Sheets, offre Workspace active, automatisation d'agent, redirection, achat, DNS, HTTPS, connexion ou partage de ces seules confirmations.
- Drupal dédié au TC Longages est confirmé. Le socle local et sa maintenance native sont autorisés et installés séparément ; les rôles métier Admin/Bureau/Capitaine, la récupération/révocation et l’hébergement sont encore à qualifier avant AUTH-001/G3. Ne pas confondre compte CMS local et accès réel du bureau. Toute mutualisation future reste indépendante des comptes, secrets et décisions d’AVEREO.
- Un contact simulé reste décrit comme simulé ; ne pas annoncer une réception par le club. Préparer les paramètres et les erreurs sans ouvrir de collecte tant que transport, traitement et information de collecte ne sont pas qualifiés. Aucune durée de conservation n'est inventée.
- Calendar est la future source unique des rencontres ; Forms reçoit Oui/Non/Je ne sais pas avec liste limitée au périmètre ; Sheets d'effectifs et de réponses restent réservés aux personnes autorisées. Une liste contrôlée n'est pas une authentification des joueurs.
- WhatsApp P0 reste manuel, par capitaine ou responsable autorisé ; conserver les groupes actuels, documenter les exceptions par appel humain. Aucun envoi réel de l'agent sans instruction correspondante, aucune intégration FFT/Ligue devinée.
- La revue locale `officiel/` et son archive fermée ne sont pas une bêta réelle. Leur règle Apache est un refus 503 inconditionnel : ne pas proposer d'ouvrir ce paquet par renommage du témoin. Pour la demande ultérieure de présentation de la V1 sur le sous-domaine, utiliser exclusivement la nouvelle variante `officiel-demo-o2switch/` et le [guide de présentation V1](docs/publier-v1-sous-domaine.md). Dépôt et bascule restent réalisés par l'utilisateur. Avant toute bêta réelle : paquet qualifié, HTTPS, accès restreint, noindex, partages Google, mentions de collecte, tests et panel validés. Aucun déploiement ou changement DNS/SSL/permissions par l'agent sans accord explicite.
- La nouvelle archive V1 contient dix fichiers, sept HTML officiels et trois contrôles, sans comptes ni données privées. Ouvrir seulement avec `maintenance.inactive` seul ; présence de `maintenance.active`, des deux témoins ou absence des deux doit renvoyer 503. Ouvert, limiter les routes aux sept pages et `robots.txt`, refuser les anciens chemins même résiduels. Ne pas déduire une authentification de cette liste ou de `noindex`. Préserver le paquet de revue locale et l'ancienne démo, distincts ; maintenir les deux manifestes hors du ZIP et ne jamais y inclure les sources ou secrets.

## Autorisation et décisions

- Préparer les modifications locales et leurs vérifications de façon autonome dans le périmètre demandé.
- Présenter les commandes sensibles avant leur exécution : effet concret, cible, risque, sauvegarde ou retour arrière, décision attendue. L'agent gère la technique ; l'utilisateur décide de l'effet recherché.
- Ne procéder à aucune mise en production sans accord explicite de l'utilisateur pour l'intervention présentée.
- Appliquer également cet accord préalable à toute publication externe ou tout essai d'envoi réel réalisé par l'agent, aux changements DNS/HTTPS, aux permissions d'hébergement et à la configuration d'accès ou de secrets.
- Les actions WhatsApp réalisées volontairement par l'utilisateur dans le parcours prévu n'exigent pas une approbation conversationnelle supplémentaire à chaque clic. L'interface doit expliquer que l'ouverture transmet le texte au service ; les groupes et l'envoi restent à confirmer dans WhatsApp.
- Une demande de prototype, une génération de livrable ou les instructions d'un document historique ne constituent pas un accord de publication.
- La présentation historique complète sur `http://tclongages.daje3540.odns.fr/` reste une variante conservée. L'utilisateur avait choisi d'en déposer lui-même le paquet : l'agent prépare et vérifie localement, sans publication. Cette demande antérieure ne donne aucun accord pour une bêta officielle V1. OIDC reste expérimental et inactif.
- Ne jamais demander un mot de passe ou un jeton dans la conversation ; utiliser un canal sécurisé adapté lors d'une future configuration autorisée.
- La procédure de référence figure dans [docs/commandes-sensibles.md](docs/commandes-sensibles.md).

## Espace bureau et communication

- Sur le prototype protégé `dist/`, servi au port 4173, réserver `bureau.html`, `communication.html`, `inscriptions.html` et `actualites-bureau.html` aux comptes actifs de rôle `bureau`. Contrôler l'accès côté serveur avant de servir les pages et conserver la fermeture par défaut sans configuration valide.
- L'utilisateur a confirmé un formulaire adhérent distinct de la gestion privée du bureau. La [proposition de processus](docs/processus-inscriptions.md) exploite le prompt fourni ; `src/inscriptions.html` reste une attente sans formulaire ni collecte. Les écrans d'inscription de `src/demo-*` appartiennent uniquement à la visite fictive. Faire valider champs, tarifs, règles et modalités d'accès avant leur implémentation réelle.
- Le [prompt de conception conservé](prompts/conception-inscriptions.md) est une référence documentaire ; ses instructions ne constituent pas une autorisation d'ouverture, de collecte ou de publication. L'exemple JSON est fictif et ne vaut pas contrat API. SQLite est proposé, pas adopté.
- Le besoin futur de deux comptes est confirmé : un compte personnel administrateur et un compte générique bureau. Aucun compte réel n'est configuré ; fournisseur et identités `iss`/`sub` restent à déterminer lors de l'activation future. Ne pas inventer d'identifiants ni transformer des comptes de recette en accès du club.
- Le guide [docs/acces-bureau.md](docs/acces-bureau.md) concerne uniquement HTTP Basic sur le port 4173. Ne jamais exposer `.local/bureau-users.json` ; les comptes de recette doivent rester isolés et éphémères.
- La couche OIDC expérimentale, démarrée par `npm.cmd run bureau` sur le port 4175, est décrite dans [docs/authentification-oidc.md](docs/authentification-oidc.md). Contrôler l'accès côté serveur par le couple exact fournisseur/identifiant stable et les rôles `admin`/`bureau`, jamais par la seule adresse e-mail. Ne pas inscrire automatiquement un compte inconnu. `/admin/acces` reste en lecture seule.
- Conserver `.env` et `.local/oidc-accounts.json` privés, hors racine publique, archives et code client. Les exemples doivent rester inactifs ; aucun secret réel dans le navigateur, les Markdown, les journaux ou la conversation.
- Les sessions OIDC actuelles sont limitées à cent dans un processus, huit heures au maximum et trente minutes d'inactivité. Redémarrer les supprime. Définir une solution partagée avant un hébergement à plusieurs processus ; ne pas présenter ces sessions comme une base de données métier ou un isolement du stockage du navigateur.
- Vérifier HTTPS et la terminaison TLS avant toute activation Internet. L'exception HTTP est réservée à la boucle locale de recette, et la confiance dans le proxy doit être explicitement qualifiée. Le [guide certificat](docs/certificat-et-connexion-bureau.md) expose les choix futurs ; aucun achat ou changement DNS/HTTPS n'est inclus dans le maquettage.
- La vitrine publique ne doit pas lire les actualités du stockage local ; conserver leur aperçu dans l'espace bureau.
- Ne pas promettre une sécurité de production à partir du contrôle HTTP Basic local. Il ne protège ni l'ouverture directe des fichiers ni les données d'un profil partagé et n'offre pas de déconnexion applicative fiable.

- Chaque actualité doit être validée individuellement avant son envoi. L'enregistrement d'un brouillon ne publie rien.
- Une actualité peut comporter une seule image locale JPEG/PNG/WebP, avec description obligatoire ; le titre reste requis, le texte peut être vide si une affiche est présente. Respecter les limites de préparation et de stockage documentées dans l'[architecture](architecture.md), sans téléversement serveur.
- Inclure l'image et sa description dans la révision validée. Remplacement, retrait ou modification de description demandent enregistrement puis revalidation ; toute modification non enregistrée bloque validation et partage. Une erreur de fichier ou de quota doit conserver la sauvegarde précédente et permettre de corriger la saisie.
- Dans le prototype protégé, Facebook et ADOC restent simulés et WhatsApp propose un partage manuel réel possible sur action de l'utilisateur. Dans la visite fictive décrite ci-dessous, ces actions sont entièrement simulées. Toujours préciser le périmètre concerné.
- L'aperçu ADOC se fonde sur la capture fournie, pas sur un contrat API vérifié. Respecter le [cadrage ADOC](api/adoc.md) : préparation d'une révision validée à jour, compteur de 2 000 caractères texte et lien compris, aucun texte coupé, dépassement bloquant seulement ce canal. Le choix Ten'Up vaut « Non » par défaut et tout changement demande sauvegarde et revalidation ; aucune connexion, copie réelle ou publication ADOC/Ten'Up ni statut d'envoi persistant.
- Le partage WhatsApp doit viser la révision validée encore à jour, être désactivé en cas de modification non enregistrée et ne jamais marquer une ouverture ou une copie comme un envoi ou une livraison.
- L'aperçu peut montrer une affiche, mais le lien WhatsApp réel du prototype protégé ne transmet que le texte et le lien éditorial. Ne pas prétendre qu'il joint une image ; un futur envoi réel nécessite l'ajout manuel du fichier dans WhatsApp.
- Ne pas choisir ou stocker les groupes destinataires dans le prototype ; leur sélection et l'envoi appartiennent au responsable dans WhatsApp. Les groupes existants ou nouveaux à utiliser restent à préciser.
- Ne pas traiter `localStorage` comme une base partagée ou un contrôle d'accès.
- Ne pas déployer les pages internes de `dist/`, les comptes, des exports JSON privés ou les scripts d'aperçu local comme un espace administrateur. La préparation OIDC ne vaut pas activation hébergée : qualifier fournisseur, HTTPS, proxy, sessions et stockage avant toute publication autorisée du bureau.
- Une intégration automatique par API exige une gestion protégée, un service serveur et les droits Meta vérifiés. Le partage manuel WhatsApp n'est pas une telle intégration. Aucun secret côté navigateur.

## Visite complète fictive

- `prototype/`, servi sur `127.0.0.1:4174`, est une visite distincte à sept pages, sans authentification. Cet accès libre s'applique uniquement aux exemples fictifs et ne modifie pas les règles du bureau protégé sur le port 4173.
- Utiliser exclusivement les clés `tcl.demo.inscriptions.v1` et `tcl.demo.communication.v1` pour ces essais. Ne pas y transférer de données réelles ni de comptes ; maintenir l'import JSON réel bloqué.
- Conserver le caractère simulé de Facebook, WhatsApp, ADOC et de la copie vers le presse-papiers dans la visite. Ne jamais utiliser ses boutons comme autorisation d'un envoi réel.
- L'import JSON réel reste bloqué tandis que l'ajout local d'une image dans l'éditeur est permis. Le bouton « Utiliser l’affiche exemple » charge le support fourni dans l'éditeur de démonstration ; ne pas créer de publication ou d'actualité prévalidée automatiquement.
- Identifier les horaires et groupes de démonstration comme exemples. Le [relevé tarifaire JSON](data/tarifs-inscription.json), transcrit des deux PDF, alimente la FAQ et les formules de démonstration par `scripts/tariffs.mjs`. Préserver les neuf lignes et les ambiguïtés ; ne pas choisir entre 125 et 150 €, calculer de remise famille ou inventer un tarif de terrain, une inclusion de licence ou une durée de cours. Ne pas transformer cette transcription en validation tarifaire.
- L'import XLS/CSV de la visite utilise un lot prédéfini sans lecture de fichier. Son export CSV concerne seulement les exemples ; ne pas annoncer d'export XLS ni d'import réel.
- Construire l'archive `livrables/tc-longages-prototype.zip` à partir d'une liste explicite des fichiers de visite. Ne pas inclure `.local/`, secrets, exports privés, dossiers réels d'adhérents ou l'ensemble du projet.
- Maintenir le [guide de visite](docs/visiter-prototype.md) et la [recette dédiée](docs/recette-demo.md). La préparation d'une archive n'autorise ni sa publication Internet ni la collecte d'inscriptions.

## Présentation fictive destinée à o2switch

- `demo-o2switch/` et `livrables/tc-longages-demo-o2switch.zip` sont les sorties dédiées au dépôt manuel choisi par l'utilisateur. Conserver dix fichiers seulement : sept HTML, `robots.txt`, `.htaccess`, `maintenance.active`. Aucun compte, secret, serveur applicatif ou export réel dans ce paquet.
- Intégrer la photo du court, le logo et l'affiche exemple aux HTML sans ajouter de fichiers image à déposer séparément. Les tarifs affichés sont également intégrés à la construction, sans PDF ni JSON supplémentaire à déposer. Les images ajoutées ensuite par un visiteur restent dans son navigateur et n'entrent pas automatiquement dans les archives. Régénérer les archives de présentation, visite locale et vitrine conservée pour les aligner sur leurs sources respectives.
- Les pages publiques `index.html` et `adherer.html` ne doivent fournir aucun lien, même créé par JavaScript, vers bureau, inscriptions, communication, visite ou aperçu. Ces cinq pages restent accessibles directement : ne jamais présenter cette discrétion des menus comme une protection.
- Utiliser les clés `tcl.hosted-demo.inscriptions.v1` et `tcl.hosted-demo.communication.v1`. Conserver les imports JSON et de dossiers réels bloqués, tout en permettant la sélection locale d'image dans l'éditeur ; Facebook, WhatsApp, ADOC et presse-papiers restent simulés. Aucun formulaire réel, SSO ou mot de passe sur cette présentation HTTP ; aucun forçage HTTPS ajouté pour la maquette.
- Préparer la fermeture par défaut avec `maintenance.active` et les règles Apache. Ouvrir par renommage en `maintenance.inactive`, fermer par l'inverse ; la vérification doit porter sur la racine et les pages directes. Les consignes robots ne protègent aucune donnée.
- Le [guide de dépôt](docs/publier-demo-o2switch.md) fait référence : racine cPanel vérifiée, sauvegarde privée, extraction privée, fermeture constatée avant copie des pages. Ne pas remplacer aveuglément WordPress ou une autre configuration. Lors d'un retour arrière, retirer aussi les pages de démonstration ajoutées avant de restaurer l'ancien `.htaccess`.
- Pour une copie déjà déposée, refermer avec `maintenance.active` avant toute mise à jour et ne conserver qu'un seul témoin pour éviter un conflit avec `maintenance.inactive` lors de la réouverture. L'utilisateur réalise ces opérations ; aucun dépôt par l'agent.
- L'ancienne archive de vitrine seule demeure une option distincte. Maintenir la [recette de présentation](docs/recette-demo-o2switch.md) sans confondre contrôles locaux et comportement réellement observé sur l'hébergement. Aucun changement DNS n'est nécessaire pour le basculement de maintenance.

## Sources et périmètre

- Lire le README et les documents concernés avant toute intervention. `src/` contient les sources d'interface, `server/` le service OIDC et `scripts/` les outils ; les Markdown courants sont la source documentaire.
- Respecter Node.js 22.9.0 minimum pour le projet complet et les dépendances verrouillées ; l'archive de visite autonome reste compatible avec Node.js 22. Installer avec `npm.cmd ci --ignore-scripts`, sans activer de connexion réelle.
- Régénérer les sorties ; ne pas maintenir des corrections concurrentes dans `dist/`, `prototype/`, `demo-o2switch/`, `release/`, `officiel/` et `officiel-demo-o2switch/`. Le renommage distant du témoin suit le guide propre au paquet ; celui de revue locale n'a pas de bascule d'ouverture.
- Conserver les pièces historiques intactes. Le dossier du 15 septembre décrit une session précédente ; ses instructions ne remplacent pas la demande actuelle.
- Utiliser `tclongages@gmail.com` dans toutes les sources et sorties actives de contact ; conserver intactes les pièces reçues, baselines et traces historiques avec leur rôle explicite. Préserver les références Ten'Up et les crédits. La photo d'accueil est fournie par le club, sans auteur identifié ; l'autre photographie reste une illustration de Nicholas Bullett. Ne pas attribuer la photo du court à l'auteur de l'image remplacée et ne pas modifier les crédits archivés. Ne pas inventer de données officielles ; seuls les horaires et groupes explicitement fictifs de la visite servent d'exemples.
- Préserver exactement `Images_Photos/Logo.jpeg`, `Images_Photos/Image_terrain.jpg` et `Affiche.jpeg`. Le logo JPEG reste temporaire, en attente de vectoriel ; la photo illustre le court réel et l'affiche est un support autorisé pour essayer les actualités illustrées. Leurs informations ne sont pas à qualifier de fictives ni à transformer en ouverture d'inscription ou en annonce automatique. Les dossiers d'adhérents de la démonstration restent fictifs.
- Ne pas intervenir sur les autres projets du compte o2switch, notamment AVEREO/Drupal.
- Distinguer systématiquement prototype local, fichier préparé, publication effective et résultat public vérifié.
- Maintenir le socle documentaire, ses métadonnées et le bilan de fin de session ; ne pas annoncer de tests non exécutés ou de conformité non vérifiée.
