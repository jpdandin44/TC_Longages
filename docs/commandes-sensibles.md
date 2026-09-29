---
project: TC_Longages
document_type: operating-guide
title: Commandes sensibles et décisions utilisateur
status: active
version: git
created: 2026-09-16
updated: 2026-09-29
owner: jpdandin
tags:
  - commandes
  - autorisation
  - production
---

# Commandes sensibles et décisions utilisateur

Le [framework adopté le 29 septembre](framework-developpement.md) complète ce contrôle : validation de phase, autorisation suivante, merge, livraison et ouverture restent distincts. Le premier envoi au [dépôt communiqué](preparer-depot.md) est une publication de fichiers vers sa propre audience ; sa visibilité et son contenu doivent être vérifiés avant la décision. L'URL seule n'est pas un accord d'envoi.

Les nouvelles commandes `framework:build` et `framework:check` sont locales : la première remplace ses deux tableaux dérivés et la seconde les vérifie. Elles ne modifient pas le journal de décisions, n'authentifient aucun accord et ne déploient rien. L'installation de leurs dépendances Python dans `.local/framework-venv` reste propre au poste, ignorée par Git et sans accès aux services du club.

Une commande est sensible lorsqu'elle modifie ce que le public voit, les accès, la sécurité de l'hébergement ou des données difficiles à restaurer. Le risque dépend de l'effet et de la cible, pas seulement du nom de la commande. Un clic dans cPanel ou dans une interface de publication peut être aussi sensible qu'une commande shell.

Depuis le 24 septembre, la cible officielle suit le [cadrage V1](integration-officiel.md). Le domaine prévu est `tclongages.fr` ; cette confirmation n'autorise aucun achat, changement DNS, certificat ou transfert. L'aperçu officiel garde sa règle Apache 503 inconditionnelle. La demande ultérieure prépare un **nouveau paquet V1 de présentation sur le sous-domaine**, distinct de l'aperçu et de la démo historique : son dépôt et sa bascule sont réalisés par l'utilisateur selon le [guide V1](publier-v1-sous-domaine.md). L'orientation Drupal et l'étude des composants CONNECT ne valent ni installation ni raccordement autorisé ; le choix d'instance reste à déterminer ensemble.

## Actions locales déjà incluses dans la préparation

| Action | Explication | Limite |
|---|---|---|
| Lecture et contrôle des fichiers | Identifier les sources et détecter les incohérences. | Aucun envoi externe. |
| Modification de `src/` et de la documentation | Construire le prototype demandé. | Changements locaux réversibles. |
| `npm.cmd ci --ignore-scripts` | Installer les dépendances verrouillées de la connexion expérimentale et de ses tests. | Contacte le registre npm et remplace les dépendances locales ; aucun script d'installation, compte ou secret réel créé. |
| `npm.cmd run build` | Régénérer les HTML dans les dossiers de sortie. | Écrase uniquement les sorties attendues ; vérifier le script avant une évolution de son comportement. |
| `npm.cmd run check` | Contrôler les propriétés du prototype. | Un contrôle réussi ne prouve pas une publication. |
| `npm.cmd run officiel:build` | Générer les sept pages officielles de revue depuis les sources et la configuration. | Remplace les sorties locales attendues dans `officiel/` et leur manifeste ; aucun service réel activé. |
| `npm.cmd run officiel` | Construire puis afficher l'aperçu officiel sur cet ordinateur, port 4180. | Boucle locale uniquement ; Ctrl+C pour arrêter. Aucun compte Google ou membre connecté. |
| `npm.cmd run officiel:package` | Préparer l'archive officielle de revue et son manifeste. | Remplace ces livrables locaux ; aucun transfert. Ce paquet ne peut pas devenir une bêta active en renommant `maintenance.active`. |
| `npm.cmd run officiel:demo:build` | Dériver les sept pages V1 avec la bannière de démonstration et une fermeture Apache contrôlable. | Écrit `officiel-demo-o2switch/` et son manifeste ; aucun compte, collecte ou transfert. |
| `npm.cmd run officiel:demo:package` | Reconstruire et archiver cette V1 pour le dépôt manuel sur le sous-domaine. | Remplace seulement le ZIP et le manifeste locaux correspondants ; le paquet est fermé par défaut. |
| `npm.cmd run release:package` | Préparer le ZIP de la vitrine publique seule et son manifeste de contrôle. | Régénère les sorties locales attendues et remplace ces deux livrables locaux ; aucun accès o2switch ni transfert. |
| `npm.cmd run demo:hosting:package` | Préparer le ZIP complet de présentation, avec dix fichiers et fermeture par défaut, puis son manifeste. | Régénère les sorties locales attendues ; aucun accès o2switch, compte ou transfert. |
| `npm.cmd run preview` | Rendre le prototype consultable sur cet ordinateur. | Liaison à `127.0.0.1`, sans ouverture de port publique ni tunnel. |
| `npm.cmd run bureau` | Reconstruire les pages puis démarrer la couche OIDC expérimentale sur le port 4175, avec lecture facultative de `.env`. | Liaison à `127.0.0.1` ; sans configuration valide, service fermé. Une future configuration réelle permettra des échanges avec le fournisseur et reste une décision distincte. |
| Recettes automatisées de l'espace bureau | Vérifier les routes et les parcours sur un serveur isolé avec un compte aléatoire uniquement en mémoire. | Aucun compte réel créé, aucune configuration `.local/` installée. |
| Export JSON local | Sauvegarder les actualités du navigateur. | Le fichier peut contenir des brouillons ; stockage privé. |

Ces actions n'exigent pas de redemander l'autorisation déjà donnée pour préparer le prototype.

Pour la **maquette historique**, l'utilisateur avait choisi de déposer lui-même la présentation complète sur `http://tclongages.daje3540.odns.fr/`, en remplacement de la vitrine. OIDC reste expérimental et inactif dans cette variante ; aucun achat, fournisseur ou compte n'est requis pour cette présentation fictive. Ce choix ne constitue pas une autorisation de publier l'aperçu officiel ni une qualification de la future bêta.

Le [guide de présentation complète](publier-demo-o2switch.md) décrit les actions que l'utilisateur effectue lui-même : sauvegarder, extraire en privé, installer et tester la fermeture, déposer les pages puis ouvrir volontairement. Les pages d'outils ne figurent pas dans les menus publics mais restent accessibles directement pendant l'ouverture ; elles ne contiennent que des exemples. L'ancien [guide de vitrine seule](deployer-vitrine-o2switch.md) reste une option distincte. Aucun de ces choix n'autorise un déploiement par l'agent.

## Ouverture et fermeture manuelles de la nouvelle présentation V1

| Votre décision | Action sensible et effet | Vérification et retour arrière |
|---|---|---|
| Remplacer l'ancienne présentation par la V1 | Après inventaire, sauvegarde et extraction privés, copier `maintenance.active`, puis remplacer `.htaccess` dans la seule racine tennis vérifiée. Cela ferme les nouvelles requêtes et change les routes autorisées. | Constater 503 avant de copier les sept HTML et `robots.txt`. Si 500 ou page encore accessible, arrêter. Conserver l'ancien état pour restauration complète. |
| Montrer la V1 maintenant | Renommer `maintenance.active` en `maintenance.inactive`. Les sept pages deviennent publiques, sans authentification. | Vérifier 200 sur les pages V1 et 404 sur les anciens chemins, même encore présents. L'inverse referme. Les deux témoins ou leur absence doivent rester fermés. |
| Terminer l'examen | Renommer `maintenance.inactive` en `maintenance.active`. | Constater 503 à la racine et sur les pages directes en nouvelle session sans cache. Les copies déjà chargées ne sont pas retirées. |
| Revenir à la présentation précédente | Refermer ; retirer les fichiers ajoutés par ce lot et restaurer l'état sauvegardé du seul site tennis ; garder `maintenance.active` jusqu'au rétablissement de l'ancien `.htaccess` en dernier, puis rétablir volontairement son état d'ouverture. | Si l'ancienne configuration ignore la maintenance, la restauration de `.htaccess` remet elle-même l'ancien site à disposition. Contrôler le contenu et le retrait des routes ajoutées ; remplacer uniquement l'index ne suffit pas. |

L'utilisateur a choisi de réaliser ces actions lui-même ; le guide est exécutable sans approbation conversationnelle supplémentaire. L'agent ne les effectue pas à sa place sans instruction explicite. Ne jamais appliquer ce basculement au ZIP d'aperçu local, dont la fermeture reste inconditionnelle. La démonstration V1 ne contient ni module Drupal ni connexion Google ni transmission Contact.

## Ouverture et fermeture manuelles de la présentation historique

| Action de l'utilisateur | Effet concret | Vérification attendue |
|---|---|---|
| Remplacer `.htaccess` après sauvegarde, avec `maintenance.active` déjà présent | Fermer les requêtes du site tennis avant de copier les pages de démonstration. | Bonne racine cPanel ; réponses 503 sur la racine et `/inscriptions.html`. Si le résultat diffère, suspendre la copie. |
| Renommer `maintenance.active` en `maintenance.inactive` | Rendre les sept pages fictives consultables à l'adresse choisie. | Le contenu devient public, y compris les pages accessibles seulement par lien direct ; aucun compte ni donnée réelle. |
| Faire le renommage inverse | Fermer les nouvelles requêtes, y compris celles vers les pages directes. | Réponses 503 en nouvelle fenêtre privée, cache désactivé ; les copies déjà chargées ne sont pas effacées. |
| Revenir à la vitrine précédente | Retirer les pages ajoutées et restaurer uniquement les fichiers sauvegardés du site tennis. | Refermer avant le retrait ; rétablir l'ancien `.htaccess` en dernier. Ne pas laisser des pages de démo accessibles après restauration du seul index. |

Ces décisions n'exigent pas d'échange supplémentaire avec l'agent lorsque l'utilisateur les réalise lui-même selon le guide. L'agent conserve l'interdiction de les exécuter à sa place sans instruction explicite correspondante. Aucun changement DNS ni mot de passe sur HTTP n'est nécessaire à ce basculement.

## Partage WhatsApp par le responsable

Après validation individuelle de l'actualité, le responsable peut choisir **Copier le message** ou **Ouvrir WhatsApp**. La copie place le texte validé dans le presse-papiers ; si elle échoue, un texte sélectionnable permet une copie manuelle. L'ouverture du lien transmet ce texte au service WhatsApp pour préremplissage. Elle ne sélectionne aucun groupe et n'envoie pas le message : le responsable choisit les destinataires, relit puis confirme l'envoi dans WhatsApp.

Ces actions intentionnelles de l'utilisateur ne nécessitent pas une nouvelle approbation conversationnelle de l'agent à chaque clic. En revanche, l'agent ne doit pas ouvrir un message réel ni effectuer un essai d'envoi à la place de l'utilisateur sans instruction explicite correspondante. L'ouverture ou la copie ne prouve jamais que le groupe a reçu le message.

## Actions à expliquer et soumettre à accord explicite

| Action technique | Décision formulée simplement | Risque et préparation requise |
|---|---|---|
| Transfert SFTP/SSH, `scp`, `rsync`, dépôt ou remplacement dans cPanel | « Remplacer la page visible du club par ce fichier validé à cette adresse. » | Mauvais dossier ou écrasement de l'existant ; confirmer la racine exacte et préparer une sauvegarde privée. |
| Modification DNS | « Faire arriver les visiteurs de ce sous-domaine sur cet hébergement. » | Mauvais pointage, propagation ou impact sur un autre service ; lire les valeurs actuelles et documenter le retour arrière. |
| Certificat et redirection HTTPS | « Permettre l'accès sécurisé puis diriger les visiteurs vers celui-ci. » | Vérifier un certificat reconnu avant la redirection ; forcer un lien HTTPS ne crée pas de certificat. Voir le [guide certificat](certificat-et-connexion-bureau.md) avant toute décision d'achat ou modification. |
| Permissions de fichiers, `chmod`, protection de répertoire | « Autoriser le serveur à lire ces fichiers ou protéger cet accès. » | Exposition de données ou blocage ; identifier les fichiers et le résultat attendu sans changer tout le compte. |
| Création de compte, installation d'un service ou configuration d'un secret | « Donner à ce service les droits nécessaires pour cette fonction. » | Accès trop large ou secret exposé ; définir des droits minimaux et un canal sécurisé. |
| Installation Drupal, activation de modules ou raccordement à CONNECT | « Fournir la connexion du club sur cette instance, avec ces rôles et ces services mutualisés. » | Orientation retenue, architecture à déterminer ensemble. Examiner les composants réels, isoler le club des autres applications, préciser base/stockage, URLs, secrets, révocation, maintenance et retour arrière. Aucun droit ni secret AVEREO n'est réutilisé implicitement. |
| Création de `support@tclongages.fr`, alias, redirection ou modification DNS de messagerie | « Permettre la réception des demandes de test dans cette boîte ou chez ces destinataires. » | Adresse seulement prévue : choisir hébergeur, boîte ou alias et destinataires ; vérifier les réglages existants et le retour arrière. Aucun MX, droit Workspace ou redirection n'est déduit de son inscription dans le site. |
| Connexion Google Workspace ou activation d'un agent automatisé | « Autoriser cet agent à lire ou agir sur ces ressources du club. » | Définir l'offre, les ressources, les permissions minimales et les actions validées ; le contact public n'est ni un jeton d'accès ni une autorisation d'envoi. Aucun secret dans les pages ou la conversation. |
| Création ou modification des partages Calendar/Forms/Sheets | « Donner à ces personnes l'accès au calendrier, aux formulaires ou aux réponses de leur équipe. » | Un lien peut exposer des noms ou réponses ; identifier ressource, audience et droits, conserver les réglages précédents et vérifier les refus avant activation. Le compte de référence seul n'autorise aucun partage. |
| Installation ou modification de `.local/bureau-users.json` | « Donner, retirer ou modifier l'accès local de ces membres aux pages du bureau. » | Confirmer les personnes et les rôles ; ne conserver que des empreintes scrypt, protéger le fichier et vérifier les refus d'accès. Voir [l'accès bureau](acces-bureau.md). |
| Enregistrement d'une application OIDC et configuration de `.env` | « Autoriser ce fournisseur à vérifier la connexion sur cette adresse précise du club. » | Fournisseur et adresse encore non choisis ; vérifier URL de retour, droits, certificat et proxy. Conserver le secret serveur hors racine publique, sans le transmettre dans la conversation. |
| Installation ou modification de `.local/oidc-accounts.json` | « Autoriser ou retirer ces deux identités : l'administrateur personnel et le compte du bureau. » | Vérifier les couples exacts `iss`/`sub`, les rôles et l'état actif ; aucune inscription automatique. Une modification peut retirer l'accès aux sessions existantes. Voir le [guide OIDC](authentification-oidc.md). |
| Exposition du serveur local ou publication des pages de gestion réelle | « Permettre un accès au bureau depuis d'autres appareils ou Internet. » | Activation reportée ; qualifier OIDC, HTTPS, proxy, sessions et stockage avant hébergement. La présentation fictive sans compte est un paquet distinct ; le dépôt des HTML réels seuls ne conserve pas la protection serveur. |
| Publication réelle vers l'API Facebook | « Envoyer ce contenu validé sur cette Page du club. » | Message public et potentiels doublons ; prévisualiser le texte et vérifier la cible et l'état des envois. |
| Ouverture d'un message WhatsApp ou essai d'envoi effectué par l'agent | « Transmettre ce texte au service WhatsApp, puis éventuellement l'envoyer à ces destinataires précis. » | Le texte quitte l'aperçu local ; distinguer ouverture et envoi, identifier les destinataires avant toute confirmation dans le client. |
| Connexion ou automatisation WhatsApp par API | « Permettre à ce service de relayer des messages selon ce périmètre. » | Conditions d'accès, compatibilité des groupes, droits et coûts à vérifier ; aucun raccordement automatique inclus dans le prototype. |
| Suppression, import remplaçant des données ou effacement de stockage | « Remplacer ou supprimer ces informations précisément identifiées. » | Perte de brouillons ou de sauvegardes ; exporter d'abord, préciser le périmètre et permettre l'annulation. |
| Publication d'un dépôt, `git push`, modification de visibilité | « Rendre accessibles ces fichiers à cette destination et à cette audience. » | Fuite de données ou déclenchement d'une automatisation ; examiner fichiers, audience et effets avant accord. |

Les noms de commande ci-dessus servent à reconnaître les opérations ; aucune commande de production prête à exécuter n'est fournie tant que la cible et le plan concret ne sont pas vérifiés.

## Présentation attendue avant intervention

1. **Résultat proposé :** décrire ce qui changera pour les visiteurs ou les responsables du club.
2. **Cible exacte :** nom du service, adresse et fichiers ou données concernés.
3. **État prêt à examiner :** montrer la version ou le contenu préparé et les contrôles déjà réalisés.
4. **Risques et récupération :** indiquer la sauvegarde et la méthode de retour arrière.
5. **Décision :** demander un accord explicite sur cette intervention, puis attendre la réponse.

Le silence, une approbation du design ou la présence d'une ancienne procédure ne valent pas accord. Après une intervention autorisée, contrôler le résultat réel et distinguer succès technique, contenu effectivement visible et limites restantes.
