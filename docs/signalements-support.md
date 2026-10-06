---
project: TC_Longages
document_type: feature-guide
title: Signalements du site V1 et suivi privé
status: in_progress
version: git
created: 2026-10-06
updated: 2026-10-06
owner: jpdandin
tags: [support, signalements, drupal, v1, recette]
---

# Signalements du site V1

## Demande et état réel

Le responsable demande le 6 octobre un bouton sur toutes les pages V1,
un formulaire envoyé à `support@tclongages.fr` et une interface privée de
suivi disponible en développement. Ce correctif est développé séparément
de la V2 Bureau sur la branche `feat/v1-signalement-support`.

La boîte a été créée personnellement par le responsable dans cPanel et sa
présence a été vérifiée dans la liste des comptes du domaine. Aucun mot de
passe n'a été lu, saisi par l'agent ou enregistré dans Git. La présence de
la boîte ne prouve pas la réception d'un courriel. DNS et redirections de
messagerie n'ont pas été modifiés.

Le module `tcl_support` fonctionne en recette locale sur
[la V1 locale](http://127.0.0.1:4183/). Le responsable demande ensuite le dépôt
du candidat en préproduction, avec l'[édition native](modifier-textes-drupal.md).
L'installation et le transport hébergés sont attestés uniquement par les
constats datés du [reçu](../data/support-v1-verification.json), qui distingue
les contrôles locaux, la création de la boîte et la recette hébergée.

## Référence manquante : formulaire proposé

Le fichier demandé `docs/strategie-beta-test.md` est absent des copies TC
locales et de `origin/main` observée au commit `b4f8a53593b84c4f4a4c7610fa58d0ef8e90de30`.
Son emplacement ou son contenu a été demandé au responsable. Aucun document
substitutif n'a été créé sous ce nom.

**Les champs ci-dessous sont une proposition locale, à confronter à cette
source avant publication.** Leur implémentation est dans `ReportForm.php` :

- Type de demande : problème ou besoin/amélioration, obligatoire.
- Objet de la demande : obligatoire, 180 caractères maximum.
- Description : obligatoire, 4 000 caractères maximum ; invitation à préciser
  les étapes, le résultat attendu et le constat.
- Page concernée : chemin de la page d'origine parmi les neuf chemins V1
  autorisés ; aucun paramètre d'URL, jeton ou historique n'est collecté.
- Adresse e-mail pour une réponse : facultative, 254 caractères maximum.
- Confirmation personnelle de la transmission au club : obligatoire.

Aucune pièce jointe, donnée technique automatiquement jointe à la demande ou
durée de conservation n'est inventée. La limitation utilise une empreinte de
l'adresse IP dans le registre privé Drupal `flood`, avec une fenêtre d'une
heure ; la purge dépend du fonctionnement réel du cron, à qualifier.
**TBD :** champs, libellés, mentions de collecte, éventuelles
pièces jointes et règles de conservation issus de la stratégie de bêta-test.
Le formulaire local invite à utiliser uniquement des données fictives.

## Parcours public et suivi

Le bouton « Signaler un problème sur le site » est ajouté à la réponse
Drupal de chacune des sept pages, après leur contenu. La vitrine générée
conserve sa source ; les HTML ne sont pas corrigés à la main.

[Le formulaire](http://127.0.0.1:4183/signaler-un-probleme) enregistre la demande
dans la base avant toute notification. Une double soumission garde la même
référence. La confirmation publique indique l'enregistrement, pas la réception
du courriel. Aucun contenu de ticket ni coordonnées ne sont accessibles par
une référence publique.

[Le suivi privé](http://127.0.0.1:4183/admin/reports/tcl-support) exige une session
Drupal et la permission restreinte « Consulter et traiter les signalements du
site ». Cette permission n'est attribuée à aucun rôle métier automatiquement.
L'administrateur local peut consulter les demandes, filtrer leur état,
renseigner un responsable, enregistrer une note et réessayer une notification.

États : Nouvelle, En cours, Informations attendues, Résolue et Classée.
Chaque traitement et tentative de notification ajoute une trace privée.
Une modification concurrente est refusée pour préserver la note précédente.
Les notes de suivi ne sont pas envoyées au déclarant. Il n'existe pas d'export
public, de suppression dans l'interface ou de synchronisation Jira/GitHub.

## Courriels : états explicites

La destination et l'expéditeur sont fixés à `support@tclongages.fr`. Le champ
e-mail du visiteur figure seulement dans le corps du message. Les retours à
la ligne de l'objet ne peuvent pas créer un en-tête.

En développement, `test_mail_collector` capture le seul message
`tcl_support_report` dans l'état privé Drupal. Aucun SMTP ni envoi PHP n'est
activé. Les autres courriels conservent leur neutralisation `tcl_null_mail`.

En hébergement, les trois réglages de support sont fermés par défaut dans
`settings.hosting.example.php`. Une activation ultérieure doit être apportée
dans la configuration privée de la version autorisée. Le mode `transport`
exige à la fois `tcl_support_transport_qualified = TRUE` et le plugin natif
`php_mail` pour ce seul message. Le transport PHP doit être qualifié sur
o2switch ; aucun mot de passe de boîte n'est nécessaire à ce transport.
Un succès signifie « accepté par le transport », jamais « reçu en boîte ».
Un plugin neutralisé ou un envoi fermé ne peut pas produire ce succès.

## Recette locale reproductible

Depuis ce checkout, avec PHP et les prérequis du guide Drupal :

```powershell
npm.cmd ci --ignore-scripts
npm.cmd run officiel:build
./scripts/drupal-local.ps1 -Action prepare -Port 4183
./scripts/drupal-local.ps1 -Action install -Port 4183
php -c .local/drupal-tools/php.ini drupal/bin/configure-support-local.php
php -c .local/drupal-tools/php.ini tests/support-runtime.php
./scripts/drupal-local.ps1 -Action start -Port 4183
python tests/test_support_http.py
```

`install` refuse une base existante. `configure-support-local.php` exige la
base SQLite privée du checkout et le mode de capture ; il n'est pas un outil
de configuration hébergée. Le serveur écoute uniquement sur la boucle locale.
La connexion se fait à `/user/login` avec le compte privé généré dans
`.local/drupal-admin.json`. Le suffixe de session propre au checkout sépare
les cookies des autres développements sur `127.0.0.1`.

Les tests HTTP créent des demandes explicitement fictives pour examiner le
suivi. Les tests métier utilisent une transaction restaurée à la fin.
Les captures et données de session restent dans `.local/`, exclu de Git.
L'Action `support-runtime` rejoue l'installation et les contrôles dans son
seul checkout jetable ; une CI réussie ne qualifie pas la réception o2switch.

## Quatre phases et reste à faire

| Phase | État de ce correctif | Résultat attendu |
| --- | --- | --- |
| Cadrage | Référence du formulaire manquante | Retrouver la stratégie, arrêter les champs et mentions de collecte |
| Développement local | Fonctionnement local testé, état de PR dans le reçu | Examiner formulaire, suivi et contrôles sur le candidat exact |
| Préproduction | Dépôt demandé le 6 octobre ; progression et résultats dans le reçu | Sauvegarder/restaurer l'état existant, installer le module et l'édition, recetter droits et mail capturé, qualifier le transport réel |
| Mise en production | Non autorisée pour ce correctif | Accord sur version/cible/effet, sauvegarde et restauration, migration additive, ouverture du formulaire et contrôle de réception |

La création des tables est additive. Le module ne dépend pas des comptes
Bureau/Capitaine de la V2. Pour refermer la collecte, désactiver
`tcl_support_enabled` dans la configuration privée, conserver les tables et
le suivi ; ne pas désinstaller le module avec des demandes à conserver.
Une restauration de code ne doit pas écraser des demandes reçues depuis la
sauvegarde. Le retour réel et les règles de conservation restent à qualifier
en préproduction.
