---
project: TC_Longages
document_type: intervention-plan
title: Préparation du compte isolé de préproduction TC
status: proposed
version: git
created: 2026-10-01
updated: 2026-10-01
owner: jpdandin
tags: [o2switch, preproduction, sauvegarde, autorisation]
---

# Intervention suivante proposée

L'utilisateur a reconnecté cPanel le 1er octobre. La lecture confirme :

| Élément | Constat actuel | Conséquence |
| --- | --- | --- |
| Domaine officiel | `tclongages.fr` sur `public_html`, avec `cgi-bin/` et `index.php` affichés ; fichiers cachés non inventoriés. | Préserver un état initial complet avant tout remplacement ; ne pas qualifier la racine de vide. |
| Préproduction | `preprod.tclongages.fr` absent du tableau des sous-domaines. | Compte, dossier, sous-domaine et certificat restent à préparer. |
| PHP | PHP natif 8.1 ; isolation par domaine explicitement refusée par l'administrateur. | Incompatible avec le runtime verrouillé Drupal ; aucune modification du PHP actuel. |
| HTTPS officiel | Certificat autosigné, expiration affichée au 28 septembre 2027. | HTTPS reconnu non qualifié ; ne pas contourner la validation TLS. |
| SQL | Zéro base indiqué dans les statistiques du compte. | Base et paramètres privés du futur Drupal restent à créer et vérifier. |
| JetBackup | Trois sauvegardes quotidiennes incrémentales `Journalier-Externe` : 29/09 07:33, 30/09 07:39, 01/10 07:57, heures affichées par le serveur. | Existence observée ; téléchargement, périmètre complet, intégrité et restauration non testés. |
| Mon Univers Web | Huit lunes gratuites disponibles, zéro active. L'interface fonctionne avec une fenêtre de 1 280 pixels ; à petite largeur elle exige un ordinateur. | L'ancienne page vide n'établissait pas l'indisponibilité du service. Une lune isolée peut être proposée sans bascule PHP du compte courant. |
| GitHub | Aucun environnement configuré dans le dépôt TC au moment de la lecture. | Secrets et protections TC restent à établir après accord sur l'accès. |

Les chemins absolus de compte et l'inventaire privé sont conservés hors Git.
Aucune donnée de session cPanel, clé privée ou valeur de mot de passe n'est
enregistrée dans ce document. La lecture ne donne aucun accord d'activation.

## Lot à autoriser

**Cible :** compte cPanel du club actuellement connecté, puis première lune
gratuite disponible de ce compte, destinée uniquement à la préproduction TC.

**Effet demandé :** conserver une copie privée de l'état initial, vérifier
son intégrité et sa restauration en copie privée, puis activer cette lune.
Le nom de compte exact est celui affiché dans cPanel. L'action d'activation
doit s'arrêter devant une saisie de nouveaux identifiants, une acceptation
contractuelle ou une option payante, pour laisser la main au responsable.

**Sauvegarde :** vérifier que le dernier instantané JetBackup couvre les
fichiers actuels et les paramètres concernés ; sinon établir une copie
fraîche. Télécharger uniquement dans un dossier privé ignoré par Git.
Relire l'archive et son inventaire, restaurer dans un dossier privé séparé,
comparer les empreintes, garder le reçu. Ne jamais lancer une restauration
JetBackup remplaçant le compte actif comme simple test. Tout échec arrête
l'activation. Les bases, utilisateurs SQL, certificats et DNS ne sont pas
réputés restaurés à partir d'un contrôle de fichiers seuls.

**Risque :** création d'un nouvel accès d'hébergement et occupation d'une
lune gratuite. Il faut conserver les paramètres du compte courant et ne
pas toucher aux autres projets. Aucun coût affiché n'est accepté au-delà
de l'activation gratuite présentée.

**Retour :** interrompre la préparation tant que la lune n'a pas reçu de
site ou de données ; sa désactivation éventuelle sera présentée séparément.
Le site actuel conserve sa racine et ses fichiers pendant ce lot. Le reçu
de restauration conserve le moyen de revenir à l'état initial si une
intervention ultérieure autorisée modifie cette cible.

## Lot ultérieur à présenter

Après activation et inventaire de la lune : proposer les paramètres PHP
compatibles sur **ce compte isolé**, le dossier Composer avec son seul
`web/` exposé, le sous-domaine, le certificat, les bases TC et les accès
SSH/GitHub limités à la cible. Présenter ces effets avant leur exécution.
La [préparation GitHub](../workflows/preparer-livraison.md) pourra alors être
qualifiée ; son résultat SSH ne vaut pas sauvegarde ou recette Drupal.
Installation de préproduction, livraison de production et ouverture sont
des décisions suivantes, chacune avec son candidat et ses preuves.

Ce découpage applique [AGENTS.md](../AGENTS.md) et le
[contrôle des commandes sensibles](commandes-sensibles.md). Il ne demande
pas une nouvelle validation des changements locaux déjà autorisés.
