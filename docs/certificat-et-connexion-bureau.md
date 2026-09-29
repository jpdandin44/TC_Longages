---
project: TC_Longages
document_type: operating-guide
title: Certificat HTTPS et connexion du bureau
status: active
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - https
  - oidc
  - bureau
  - o2switch
---

# Certificat et connexion du bureau

**L'objectif : choisir une adresse maîtrisée, vérifier HTTPS, puis activer la connexion du bureau sur Internet.** Le développement local peut avancer avant ces opérations.

**Décision actuelle : rester au maquettage.** L'activation réelle est reportée jusqu'à la décision du bureau d'acquérir un nom de domaine. La visite fictive reste le support de présentation ; aucune connexion réelle n'est nécessaire à ses exemples.

## Forcer HTTPS ne remplace pas le certificat

La [procédure o2switch fournie par l'utilisateur](https://faq.o2switch.fr/guides/webmastering/forcer-https/) demande d'activer un certificat à son étape 2, avant d'ajouter la redirection. Celle-ci oriente les visites HTTP vers HTTPS ; elle ne crée pas de certificat et ne corrige pas un nom non couvert. La vérification TLS du navigateur intervient avant le traitement de la page ou du fichier `.htaccess`.

Sur un sous-domaine technique sans certificat valide, forcer HTTPS ne contourne donc pas le blocage : une alerte ou un échec de connexion demeure. Aucun fichier `.htaccess` n'a été ajouté. Pour montrer la maquette, conserver des données fictives et une connexion simulée ; pour une présentation distante avec HTTPS sans alerte, utiliser une adresse déjà couverte par un certificat valide ou un hébergement de démonstration fournissant cette adresse, après choix et accord de publication distincts.

Deux comptes sont confirmés : votre compte personnel **administrateur** et un compte générique **bureau**. Le fournisseur de connexion et le domaine restent à choisir. Aucun compte réel n'est créé par ce guide.

## 1. Choisir l'adresse et le certificat

Le dossier historique mentionne `http://tclongages.daje3540.odns.fr/`. Son utilisation actuelle n'est pas confirmée. L'outil Let's Encrypt d'o2switch exclut les noms techniques terminant par `odns.fr`, `o2switch.net` ou `universe.wf` ; une tentative sur ces noms échoue. La recommandation est donc un domaine ou sous-domaine que vous contrôlez, avec certificat gratuit, plutôt qu'un achat destiné à contourner cette restriction. [Documentation officielle o2switch](https://faq.o2switch.fr/cpanel/securite/lets-encrypt-ssl-gratuit/).

Sur ce domaine maîtrisé, vérifier le pointage et la racine du site, puis utiliser d'abord la simulation Let's Encrypt. La validation HTTP exige l'accès au fichier de contrôle ; une protection ou redirection peut le bloquer. La validation DNS proposée par cet outil exige les DNS o2switch. Après émission, vérifier HTTPS sans alerte avant d'activer la redirection et l'accès Internet du bureau. [Procédure et diagnostics](https://faq.o2switch.fr/cpanel/securite/lets-encrypt-ssl-gratuit/).

Un certificat externe reconnu reste une alternative : générer une demande **CSR**, faire valider le contrôle du domaine, puis installer **CRT**, clé privée **KEY** et chaîne **CA** dans SSL/TLS. Pour un domaine technique, obtenir d'abord confirmation de l'hébergeur ; ne pas présumer qu'un achat résout l'éligibilité. Conserver la clé privée hors conversation. Un certificat autosigné provoque une alerte navigateur : il n'est pas retenu pour le bureau réel. [Gestion des certificats](https://faq.o2switch.fr/cpanel/securite/certificat-ssl/).

## 2. Préparer la connexion

La connexion OpenID Connect est préparée à titre expérimental avec `openid-client` 6.8.8 : code d'autorisation avec PKCE, puis identification par le couple fournisseur/utilisateur (`iss`/`sub`). Les droits distinguent administrateur personnel et bureau générique. Ce dernier identifie le compte partagé, pas chaque personne qui l'utilise.

Le serveur OIDC prévu sur `127.0.0.1:4175` reste distinct du prototype Basic sur 4173 et de la visite fictive sur 4174. La [recette locale](recette-oidc.md) décrit les tests avec des identités fictives et leurs limites ; aucun fournisseur réel n'est connecté. Le fournisseur, les identités autorisées et les adresses de retour restent à configurer lors d'une future activation ; aucun secret n'est attendu dans les fichiers publics.

Pour l'hébergement futur, séparer le dossier des sources Node.js (**Application root**) du dossier public du domaine (**document root**). o2switch avertit que des sources placées dans ce dernier peuvent devenir accessibles lorsque l'application s'arrête. [Hébergement Node.js](https://faq.o2switch.fr/cpanel/logiciels/hebergement-nodejs-multi-version/).

## Décisions sensibles

| Action préparée | Votre décision |
|---|---|
| Domaine, pointage DNS et certificat | Choisir l'adresse et autoriser les changements précis ; aucun achat automatique. |
| Fournisseur et deux comptes | Confirmer les identités, leurs droits et la configuration sécurisée. |
| Activation Internet | Examiner la recette, HTTPS et le retour arrière, puis donner l'accord explicite de déploiement. |

Sources officielles consultées le **16 septembre 2026**. Ce guide ne réalise aucun achat, changement DNS, installation de certificat, création d'accès ou déploiement.
