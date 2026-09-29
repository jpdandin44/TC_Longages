---
project: TC_Longages
document_type: workflow
title: Validation et publication contrôlée
status: active
version: git
created: 2026-09-16
updated: 2026-09-17
owner: jpdandin
tags:
  - workflow
  - facebook
  - whatsapp
  - validation
---

# Validation et publication contrôlée

## Objectif

Permettre au club de préparer sa communication et de décider de chaque diffusion. Deux décisions restent distinctes : autoriser la mise en ligne du site et valider le contenu de chaque actualité avant son relais.

L'étape active reste le maquettage : l'utilisateur a choisi de déposer lui-même la démonstration complète sur `http://tclongages.daje3540.odns.fr/`, en remplacement de la vitrine. La migration du service réel et l'activation OIDC restent reportées après l'achat du domaine ; aucun transfert d'hébergement n'est effectué par l'agent.

## Communication des pages protégées

Ce parcours concerne les pages du bureau du prototype Basic local sur 4173 et leur couche [OIDC expérimentale](../docs/authentification-oidc.md), séparée sur 4175 et non activée. La [visite fictive locale](../docs/visiter-prototype.md) sur 4174 et sa [copie destinée à o2switch](../docs/publier-demo-o2switch.md) simulent aussi WhatsApp et le presse-papiers : elles n'ouvrent aucun accès réel ni service de publication.

**Entrées :** titre, texte ou affiche, catégorie, lien HTTPS facultatif, image JPEG/PNG/WebP facultative avec description, choix local de visibilité Ten'Up pour ADOC, action de validation et éventuel fichier JSON à importer dans le prototype protégé. L'import JSON reste bloqué dans les démonstrations, où l'ajout local d'une image est néanmoins autorisé.

**Sorties :** données locales mises à jour avec au plus une image par actualité, aperçu des affiches entières dans le même navigateur, résultat de relais Facebook simulé, texte WhatsApp préparé, préparation ADOC simulée et éventuel export JSON privé incluant les images et le choix Ten'Up. La vitrine accessible sans connexion ne lit pas ces actualités.

**Intégrations :** aucune API de publication. Le lien public Facebook de la vitrine n'est pas une connexion à un compte Meta. Le lien de partage WhatsApp est une ouverture volontaire vers un service externe, avec le texte prérempli.

**Dépendances :** navigateur avec stockage local, pages de communication et d'aperçu sur la même origine, serveur et compte autorisé. Basic demande un rôle `bureau` ; OIDC prévoit les rôles `admin` et `bureau`. Aucun compte réel n'est installé et l'activation OIDC est reportée : les pages internes restent fermées en usage courant. Voir le [guide OIDC](../docs/authentification-oidc.md) et la référence de l'[ancien accès Basic](../docs/acces-bureau.md).

1. Après configuration autorisée des accès, s'authentifier sur l'espace bureau et préparer l'actualité dans `communication.html` : titre obligatoire, texte ou affiche, description obligatoire en présence d'une image.
2. Enregistrer le brouillon : aucun envoi ni publication publique.
3. Relire les quatre aperçus : site, Facebook, WhatsApp et ADOC. Pour ADOC, examiner le compteur et le choix Ten'Up, réglé sur « Non » par défaut.
4. Valider l'actualité : le prototype l'affiche dans `actualites-bureau.html`, également protégée, et simule le relais Facebook.
5. Partager manuellement dans WhatsApp si souhaité, selon le parcours ci-dessous ; le bouton ADOC prépare uniquement une simulation.
6. Vérifier l'aperçu et sauvegarder les données en JSON si nécessaire.

Un statut de simulation réussie ne prouve pas une publication sur Facebook. L'effacement des données du navigateur peut effacer les actualités ; conserver les exports hors du répertoire public. L'import reprend les actualités comme brouillons, à valider avant tout partage.

L'image est préparée puis enregistrée dans le navigateur, dans les [limites documentées](../architecture.md) ; elle n'est pas envoyée à un serveur. Un quota insuffisant bloque l'enregistrement sans remplacer la sauvegarde précédente. Remplacer ou retirer l'image, ou modifier sa description, demande un nouvel enregistrement puis une nouvelle validation. Les commandes restent bloquées tant que la révision contient des modifications non enregistrées. Dans les démonstrations, **Utiliser l’affiche exemple** charge le support fourni dans l'éditeur sans le valider ni annoncer automatiquement son contenu.

## Partage manuel WhatsApp après validation

1. Vérifier que l'actualité est validée et que le formulaire ne contient aucune modification non enregistrée. Le prototype contrôle à nouveau la révision courante avant l'ouverture ou la copie.
2. Choisir **Ouvrir WhatsApp** pour transmettre le texte validé au service et préparer un message, ou **Copier le message** pour le coller soi-même. Un lien trop long laisse disponible la copie ; si le presse-papiers échoue, sélectionner et copier manuellement le texte proposé.
3. Dans WhatsApp, choisir les groupes destinataires, joindre soi-même l'image si souhaité, relire le message et confirmer l'envoi. Le lien du prototype préremplit seulement le texte : il n'attache pas l'affiche. Si le client ne propose pas un groupe dans le sélecteur, coller le texte dans sa conversation.
4. Vérifier le résultat directement dans WhatsApp. Le prototype ne connaît ni les destinataires, ni l'envoi effectif, ni la livraison et n'ajoute aucun statut correspondant.

L'utilisateur réalise lui-même ces actions volontaires ; aucune approbation conversationnelle supplémentaire de l'agent n'est requise pour chaque clic. Un agent qui effectuerait un essai externe à sa place doit disposer d'une instruction explicite pour cet essai. Les groupes existants ou nouveaux à utiliser restent TBD. Voir le [cadrage WhatsApp](../api/whatsapp.md) pour les références et limites.

## Préparation ADOC après validation — simulation uniquement

1. Consulter l'aperçu ADOC, qui réunit titre, texte et lien, image entière et choix de visibilité Ten'Up.
2. Enregistrer puis valider la révision courante. Une modification du choix Ten'Up, du contenu ou de l'image exige une nouvelle validation ; une modification non enregistrée ou une préparation d'image en cours bloque l'action.
3. Utiliser le bouton de simulation ADOC si le contenu respecte sa limite de 2 000 caractères, lien compris. Au-delà, le contenu reste entier et les autres usages restent disponibles ; seul ADOC est bloqué.
4. Lire le résultat comme une préparation locale : aucun article enregistré, aucune visibilité réelle changée dans Ten'Up, aucune copie vers le presse-papiers et aucun statut d'envoi ajouté au stockage.

Le [cadrage ADOC](../api/adoc.md) précise les limites issues de la capture et le comptage local. Une future intégration réelle suppose de vérifier les moyens autorisés et les droits ; aucune API disponible n'est présumée.

## Relais automatique envisagé — non implémenté

La couche OIDC prépare la connexion et des sessions en mémoire, sans activation chez un fournisseur réel. Le service futur doit encore qualifier cette connexion sur l'hébergement et assurer : stockage central, validation d'une version précise du contenu, envoi serveur vers la Page autorisée, suivi de la réponse, gestion des erreurs et prévention des doublons. Ni HTTP Basic local ni OIDC ne fournissent ces composants de publication. Toute modification du contenu validé doit demander une nouvelle validation avant son envoi.

Avant d'implémenter et d'activer ce service :

- Identifier la Page cible et les droits d'administration effectivement disponibles.
- Vérifier dans les sources officielles Meta les permissions, les conditions applicables et le parcours de configuration au moment de l'intégration.
- Choisir le service serveur et son hébergement avec l'utilisateur.
- Définir la gestion des secrets et des accès ; les noms exacts des variables sont **TBD**, aucune valeur secrète n'est attendue dans la documentation ou le navigateur.
- Préparer un test et obtenir l'accord explicite pour tout envoi réel.

La validation d'une actualité dans le prototype actuel ne constitue pas une autorisation différée de l'envoyer automatiquement lorsqu'une connexion Facebook sera ajoutée.

Une éventuelle automatisation WhatsApp nécessite un cadrage distinct : groupes concernés, conditions officielles, compatibilité, accès et coût. Le partage manuel livré ne prouve pas la disponibilité d'un relais automatique vers les groupes actuels du club.

## Présentation complète sur o2switch — dépôt par l'utilisateur

**Entrée :** `livrables/tc-longages-demo-o2switch.zip`, reconstruit localement par `npm.cmd run demo:hosting:package` à partir de la copie fictive avec le logo fourni et l'affiche exemple intégrés. Le paquet conserve exactement sept HTML, `robots.txt`, `.htaccess` et `maintenance.active`, avec manifeste séparé ; aucun JPEG n'est à déposer séparément.

**Sortie prévue :** une présentation temporaire à l'adresse HTTP confirmée, fermée par défaut. La vitrine et le formulaire sont liés dans les menus publics ; les cinq pages d'outils et de visite restent accessibles directement, sans authentification. Les imports JSON réels sont bloqués, les réseaux sociaux simulés et les essais, images ajoutées comprises, restent seulement dans le navigateur de chaque visiteur. Les visuels expressément fournis sont des supports de présentation ; ils n'ouvrent aucune inscription réelle.

Le [guide de dépôt manuel](../docs/publier-demo-o2switch.md) est la procédure de référence. L'utilisateur relève la racine cPanel, sauvegarde l'existant en privé et extrait le ZIP hors de la racine publique. Il installe le témoin actif et les règles de fermeture, vérifie 503, puis copie les pages. Il décide de l'ouverture en renommant le témoin en `maintenance.inactive`, et referme par le renommage inverse après la présentation. Les contrôles concernent aussi les pages directes, en nouvelle fenêtre privée sans cache.

Pour mettre à jour une démonstration déjà déposée, l'utilisateur la referme avant la copie : `maintenance.inactive` devient `maintenance.active`. Il conserve un seul témoin pour éviter un conflit au prochain renommage ; les étapes exactes et le retour arrière restent dans le guide.

Le dépôt remplace la vitrine à cette adresse ; l'utilisateur a explicitement choisi de le réaliser lui-même. L'agent ne dépose, n'ouvre et ne ferme aucun fichier distant. La [recette du paquet](../docs/recette-demo-o2switch.md) distingue les vérifications locales du fonctionnement Apache qui doit être observé sur l'hébergement. Aucun compte, SSO, collecte réelle ou forçage HTTPS n'est ajouté pour cette présentation HTTP fictive.

## Option conservée — vitrine seule

L'ancien choix de **vitrine publique seule** reste une option distincte. La commande locale `npm.cmd run release:package` reconstruit le HTML puis prépare `livrables/tc-longages-vitrine-o2switch.zip`. Ce ZIP contient exactement `index.html` à sa racine, avec images, styles et navigation intégrés. Son manifeste reste séparé ; aucun bureau, formulaire, exemple fictif, `.htaccess` ou lanceur Node.js n'est inclus. Ce paquet n'est pas celui choisi pour la présentation complète actuelle.

La procédure de référence est le [guide de dépôt manuel o2switch](../docs/deployer-vitrine-o2switch.md) : il détaille la cible, la sauvegarde, le moment où le remplacement devient visible, les contrôles après dépôt et le retour arrière. La [recette du paquet](../docs/recette-vitrine-o2switch.md) consigne uniquement les vérifications locales.

Pour une future publication durable de cette vitrine, le domaine, la racine serveur, l'existant et HTTPS restent à vérifier. Le choix d'un contenu n'est pas un accord de déploiement par l'agent ; une intervention de l'agent sur l'hébergement reste soumise à son accord explicite. Aucun déploiement n'est exécuté par les commandes de préparation.

## Inscriptions et accès futurs

Le contenu de conception a été reçu et intégré à la [proposition du processus d'inscription](../docs/processus-inscriptions.md). Les visites fictives montrent le formulaire adhérent et le tableau bureau ; le service réel, son API, sa base et la reprise sécurisée restent non implémentés. La page `dist/inscriptions.html` du prototype protégé reste une attente sans collecte et n'est jamais incluse dans le paquet de présentation, qui contient uniquement sa copie fictive séparée.

Le besoin de deux comptes est confirmé : un compte personnel administrateur et un compte générique bureau. Leur activation réelle est reportée avec la migration : choisir ultérieurement le fournisseur et le domaine, vérifier les identités exactes et préparer une procédure sécurisée avant l'accord correspondant. Aucun identifiant de recette ne doit devenir un compte du club. Un compte inconnu après connexion OIDC doit rester refusé, sans inscription automatique.

Une éventuelle mise en ligne des pages internes demande la qualification d'OIDC, du certificat HTTPS et du proxy, des sessions adaptées à l'hébergement et un stockage métier central, puis une décision de publication distincte. Les sessions préparées sont limitées à un processus et perdues au redémarrage ; elles ne suffisent pas à un hébergement à plusieurs processus sans adaptation. Déposer les HTML seuls ne transfère pas la protection du serveur. Le [guide certificat](../docs/certificat-et-connexion-bureau.md) explique les décisions futures ; le maquettage local peut continuer sans cette activation.

## Retour arrière

Pour la présentation complète, suivre le [guide dédié](../docs/publier-demo-o2switch.md) : refermer, retirer les pages fictives introduites de la racine publique, restaurer les fichiers initiaux et rétablir l'ancien `.htaccess` en dernier. Restaurer l'index seul laisserait les autres pages accessibles. Pour l'option de vitrine seule, suivre son [guide](../docs/deployer-vitrine-o2switch.md). Limiter toute restauration au site tennis, sans modifier AVEREO/Drupal ni le compte entier. Pour les données éditoriales locales, utiliser un export identifié après examen de l'effet de l'import. Conserver les sauvegardes jusqu'à la fin de la recette correspondante.
