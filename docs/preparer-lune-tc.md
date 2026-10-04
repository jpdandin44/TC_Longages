---
project: TC_Longages
document_type: intervention-plan
title: Préparation du compte isolé de préproduction TC
status: active
version: git
created: 2026-10-01
updated: 2026-10-04
owner: jpdandin
tags: [o2switch, preproduction, sauvegarde, autorisation]
---

# Préparation de la lune de préproduction

## Correction du plan — 4 octobre

La demande de déblocage conduit à comparer les configurations réelles décrites
dans les deux projets. Le dossier AVEREO place production et préproduction dans
deux dossiers du même compte cPanel, avec PHP 8.3 partagé. Sa préproduction
répond aujourd'hui en HTTPS reconnu avec HTTP 401 sans authentification.
Pour TC, `tclongages.fr` reste dans le compte principal et la préproduction a
été préparée dans une lune distincte. Le refus de rattachement est donc lié à
ce choix d'hébergement ; les composants GitHub réutilisés ne règlent pas cette
association de domaine. La [règle o2switch](https://blog.o2switch.fr/creer-un-sous-domaine-o2switch-a-quoi-ca-sert-et-comment-le-configurer/)
interdit de créer le sous-domaine du compte principal dans une autre lune.

**Proposition technique retirée :** ne pas créer la préproduction sous
`universe.wf`. La [documentation Let's Encrypt o2switch](https://faq.o2switch.fr/cpanel/securite/lets-encrypt-ssl-gratuit/)
exclut les domaines techniques de son émission de certificats. Le plan
précédent n'avait pas vérifié cette condition ; aucun certificat ni domaine
n'a été créé par cette proposition. Une éventuelle autre méthode de certificat
n'est ni vérifiée ni retenue. L'ancienne demande d'accord sur cette adresse
est devenue sans objet.

**Choix humain retenu le 4 octobre :** le responsable demande « On suit la même
organisation que pour AVEREO ». Qualifier `preprod.tclongages.fr` dans le
compte principal TC, avec un dossier et une base dédiés.
Cette voie conserve le domaine officiel dans son compte actuel. Elle sépare
les fichiers et les données, mais ne conserve pas le cloisonnement entre
comptes apporté par la lune. Les ressources existantes de la lune restent
conservées ; aucun déplacement ou nettoyage automatique n'est prévu.

| Ordre | Préparation concrète | Limite ou décision |
| --- | --- | --- |
| 1 | Se reconnecter au compte principal TC via [cPanel](https://barriere.o2switch.net:2083/) ; les anciennes URL `cpsess` ne doivent pas être réutilisées comme accès permanent. | Le dernier onglet affiche une session refusée ; identifiants saisis personnellement. |
| 2 | Inventorier les domaines et sites du compte, le PHP réellement servi, les extensions, le stockage et les bases. | Lecture seule avant toute proposition de modification PHP partagée ; effet sur les sites existants à déterminer. |
| 3 | Présenter le lot du compte principal : sous-domaine `preprod`, parent `tclongages.fr`, dossier Composer proposé `tcl-preproduction/drupal/`, racine Web proposée `tcl-preproduction/drupal/web/`, base et utilisateur propres à ce compte. | Chemins, état vide, PHP et périmètre de sauvegarde à qualifier ; le lot de configuration autorisé sur la lune ne couvre pas ce compte. |
| 4 | Après l'accord correspondant, configurer cette cible fermée, son DNS et un certificat reconnu, puis qualifier PHP HTTP et SQL. | Le DNS de `preprod.tclongages.fr` ne résout pas au contrôle du 4 octobre ; aucune valeur DNS n'est inventée. |
| 5 | Adapter les contrôles de compte de l'outil de première installation au choix validé, tester et figer le candidat avant présentation du transfert. | L'outil actuel est limité à la lune ; aucun transfert Drupal ni déploiement autorisé par cette étude. |

**Autre voie :** si le cloisonnement de la lune est conservé, préparer une
demande à o2switch sur le rattachement de `preprod.tclongages.fr` sans déplacer
le domaine principal. La faisabilité de cette exception n'est pas établie ;
aucun ticket ni changement serveur n'est exécuté par l'agent.

Le [suivi canonique](suivi-chantier/suivi-chantier.json) porte le choix humain
d'implantation et le [reçu de diagnostic](../data/framework-revue-verification.json)
porte les lectures et leurs limites. Ce choix ne constitue pas l'accord du
lot de configuration encore à présenter après inventaire du compte, ni une
validation de phase, de transfert ou d'ouverture.

## Reprise initiale du 4 octobre — constat historique

Compte principal et lune reconnectés ; un compte actif gratuit et sept lunes
restantes sont affichés. Les tables Domaines Configurés et Sous-domaines de la
lune restent vides. Le formulaire `preprod` sous son domaine technique est
préparé sur la même racine protégée, sans soumission ; l'accord demandé sur
ce changement de cible est attendu. Aucun mot de passe n'est collecté.

Le terminal existant répond : PHP CLI 8.3.33 avec `pdo_mysql`, Python 3.6.8.
Le [guide d'installation](installation-drupal.md) porte l'outil désormais
préparé localement et ses limites. DNS, HTTPS reconnu, PHP HTTP et connexion SQL
restent à tester avant transfert autorisé du ZIP. L'état et les accords du
3 octobre conservés ensuite ne sont pas transformés en accord de transfert,
de migration officielle ou d'ouverture.

## Configuration autorisée et blocage de domaine — 3 octobre

Le lot de configuration a été autorisé et partiellement réalisé : PHP 8.3 appliqué, racine isolée créée avec fermeture Apache relue, base vide UTF-8 et utilisateur SQL dédié avec dix droits enregistrés. cPanel refuse `preprod.tclongages.fr` dans la lune parce que son domaine parent appartient au compte principal. Aucun DNS ni certificat n’est créé pour ce nom. Une adresse temporaire de la lune est proposée, non soumise ; son accord, DNS, HTTPS reconnu et PHP réellement servi restent à qualifier.

Le responsable a répondu **« Autoriser ce lot de configuration »**, puis **« Autoriser ces droits SQL »** au moment de leur attribution. Le mot de passe SQL a été saisi, confirmé et soumis personnellement. La base initialement vide avait `latin1_swedish_ci` ; elle est désormais en `utf8mb4_unicode_ci`. MariaDB 11.4.13 est observé dans phpMyAdmin du sous-compte ; cela ne prouve pas une connexion Drupal.

La [documentation officielle o2switch](https://blog.o2switch.fr/creer-un-sous-domaine-o2switch-a-quoi-ca-sert-et-comment-le-configurer/) confirme la restriction de sous-domaines entre comptes. Le plan initial ne l’avait pas prise en compte ; il est corrigé avant poursuite. La fermeture `.htaccess` de 36 octets a été téléchargée après dépôt et comparée à sa source. Son application HTTP reste à tester après rattachement d’un hôte. La sonde PHP n’a pas été transférée.

### Adresse temporaire proposée le 3 octobre — proposition retirée le 4 octobre

Le formulaire « Sous-domaines » de la lune proposait son seul domaine technique parent. Préfixe `preprod`, même racine `tcl-preproduction/drupal/web`. Le nom complet et la capture restent dans les preuves privées. Le formulaire n'a pas été soumis et aucun transfert Drupal ou changement du domaine officiel n'a été réalisé. Cette proposition est retirée pour le motif HTTPS documenté en tête du présent plan ; son ancienne demande d'accord ne doit plus déclencher une création.

### État initial du compte actif — 3 octobre

Le lot gratuit autorisé le 1er octobre a repris après contrôle de fraîcheur : trois exports JetBackup et une archive fraîche de la racine publique ont été restaurés en copie privée, avec gzip et empreintes vérifiés. Le responsable a saisi, confirmé et soumis le mot de passe dans cPanel. Le compteur indique une lune active et sept restantes ; l'accès à son compte séparé est confirmé. Le [reçu courant](../data/activation-lune-verification.json) remplace l'état d'attente historique décrit ci-dessous.

La lecture du compte isolé constate PHP natif 8.1, PHP 8.3 disponible, aucun domaine supplémentaire, aucune base et aucun utilisateur SQL. Le compte du domaine officiel reste distinct. Les noms de compte, chemins absolus et captures sont privés.

### Configuration initiale autorisée, avant installation du site

**Cible :** première lune gratuite désormais active, exclusivement pour `preprod.tclongages.fr`.

**Effets autorisés pour la cible initiale, dont le rattachement est bloqué :**

1. Sélectionner PHP 8.3 dans cette lune et qualifier les extensions exigées par le verrou Drupal. Ce réglage porte sur le compte isolé.
2. Préparer la racine Composer `tcl-preproduction/drupal/`, avec seule racine Web `tcl-preproduction/drupal/web/`, relative au dossier du sous-compte. Les dépendances et paramètres privés doivent rester hors de cette racine Web.
3. Rattacher uniquement `preprod.tclongages.fr` à cette racine. Ajouter seulement le DNS de ce sous-domaine vers l'adresse d'hébergement vérifiée et demander son certificat reconnu. Conserver le rattachement et les enregistrements du domaine officiel ; arrêt si l'interface exige de retirer ou migrer `tclongages.fr`.
4. Créer une base de suffixe `tclpreprod` et un utilisateur SQL dédié à cette base, sans privilèges sur d'autres bases. Le responsable crée et soumet lui-même le nouveau mot de passe par le canal cPanel ; aucune valeur dans le chat ou Git.

**Limites :** ce lot ne comprend ni transfert de Drupal, ni nouvelle clé SSH, ni secret GitHub, ni migration du domaine principal, ni ouverture du site. Après configuration, vérifier le PHP réellement servi, HTTPS, les paramètres SQL et les protections de la racine avant de présenter l'installation sous maintenance du candidat construit après fusion.

**Sauvegarde et retour :** conserver les exports du compte actuel et relever l'état initial de la lune avant toute écriture. Enregistrer chaque création et réglage pour préparer un retour précis. Ne pas supprimer automatiquement une ressource nouvellement créée qui pourrait avoir reçu des données. La restauration privée déjà réussie ne prouve pas un retour arrière Drupal ou SQL.

**Arrêts :** nouveau mot de passe à saisir par le responsable, contrat, coût, effet sur le domaine officiel, cible inattendue ou contrôle d'intégrité en échec. Aucun de ces effets supplémentaires n'est autorisé par l'activation gratuite.

Les sections du 1er octobre suivantes sont historiques ; leurs observations antérieures à l'activation restent conservées.

## Lot autorisé le 1er octobre — arrêté à la clôture

Le responsable a répondu **« Autoriser sauvegarde et activation gratuite »**.
Le [reçu daté](../data/activation-lune-verification.json) porte l'état courant ;
les identifiants de compte, archives, empreintes détaillées et captures restent
dans le dossier privé ignoré par Git.

Trois archives JetBackup du 1er octobre ont été téléchargées puis restaurées
dans une copie locale distincte : 75 fichiers réguliers et deux liens internes
contrôlés. Les flux gzip complets et toutes les empreintes des fichiers restaurés
ont été vérifiés. Les exports couvrent les fichiers du compte, deux zones DNS
et les certificats de deux domaines. Une archive fraîche de `public_html`, créée
dans le dossier privé du compte, hors racine publique, a aussi été téléchargée
et restaurée : un fichier et deux dossiers, aucun fichier masqué supplémentaire.
Le fichier courant et l'archive fraîche correspondent à l'instantané sauvegardé.
cPanel confirme zéro base MySQL et zéro utilisateur SQL. Les fichiers du site
courant n'ont pas été modifiés.

Ces essais prouvent la restauration des fichiers en copie privée sur le poste.
Ils ne constituent pas un réimport cPanel, DNS ou certificat, ni une restauration
de base. Les propriétaires et modes POSIX sont conservés dans les reçus, sans
application sur Windows. Les autres paramètres du compte ne sont pas réputés
restaurables à partir de ces exports seuls.

L'interface confirme huit lunes gratuites, zéro active et un total affiché de
0 € par mois. Le dialogue de la première lune a été ouvert après réussite des
contrôles ; il demande un nouveau mot de passe. L'agent s'est arrêté avant toute
saisie ou soumission, conformément à l'accord. **Activation non confirmée :** le
responsable doit saisir, confirmer et soumettre le mot de passe directement dans
cPanel. À la clôture du 1er octobre, le formulaire non soumis a été annulé,
la déconnexion cPanel confirmée et l'onglet fermé. Une nouvelle connexion et
une lecture de l'état sont nécessaires à la reprise, avec vérification de la
fraîcheur des sauvegardes. Vérifier ensuite le compteur et l'état de la lune. PHP, DNS, HTTPS,
base, accès SSH et installation Drupal restent le lot ultérieur à présenter.

## Inventaire avant intervention

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

## Périmètre du lot autorisé

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
