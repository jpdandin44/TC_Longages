---
project: TC_Longages
document_type: integration-plan
title: Partage des actualités dans les groupes WhatsApp
status: active
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - whatsapp
  - communication
  - validation
---

# Partage WhatsApp

## Périmètre du prototype

Le parcours retenu prépare un texte pour les groupes WhatsApp du club après la validation individuelle de l'actualité. Le responsable choisit les groupes destinataires et confirme l'envoi dans WhatsApp. Le site n'est connecté à aucun compte WhatsApp, ne connaît pas les membres des groupes et ne peut pas vérifier la livraison d'un message.

Ce partage manuel est distinct du relais automatique envisagé pour Facebook. L'enregistrement d'un brouillon ou la validation locale ne déclenche aucun envoi WhatsApp. Une ouverture de WhatsApp ou une copie du texte ne doit pas être présentée comme une publication réussie.

Ce parcours réel concerne le prototype protégé, notamment sur le port 4173. Dans les démonstrations locale et destinée à o2switch, l'ouverture WhatsApp et le presse-papiers restent simulés, même lorsqu'une affiche figure dans l'aperçu.

## Parcours et informations transmises

1. Rédiger l'actualité et consulter l'aperçu WhatsApp.
2. Valider individuellement la révision avant de rendre son texte disponible au partage.
3. Ouvrir le lien de partage vers WhatsApp, ou copier le texte validé.
4. Choisir les groupes souhaités dans WhatsApp, relire les destinataires et le message, puis confirmer l'envoi. Si le client ne propose pas le groupe dans son sélecteur, coller le texte copié directement dans la conversation concernée.

Le lien de partage utilise `https://wa.me/?text=…`, avec le texte encodé pour une URL. Son ouverture transmet le texte préparé au service WhatsApp pour le préremplissage ; elle n'envoie pas à elle seule le message aux groupes. La copie place le texte dans le presse-papiers de l'appareil. Aucun jeton, numéro de membre ou lien d'invitation de groupe n'est nécessaire au prototype.

L'ajout d'une image dans une actualité ne modifie pas ce mécanisme : **le lien et la copie ne joignent pas l'image automatiquement**. L'aperçu illustré montre la préparation éditoriale, pas une pièce jointe transmise à WhatsApp. Lors d'un futur envoi réel, le responsable devra joindre lui-même le fichier dans le client, puis relire texte, image et destinataires avant confirmation. Aucun téléversement de média vers une API WhatsApp n'est implémenté.

L'aide officielle WhatsApp décrit le lien sans numéro, le préremplissage et le choix des destinataires. Elle ne garantit pas la présence de chaque groupe dans tous les clients : [fonction « click to chat »](https://faq.whatsapp.com/5913398998672934). Consultation : 16 septembre 2026.

## Conditions et limites

- Le responsable utilise son propre accès WhatsApp et doit pouvoir écrire dans le groupe. Les administrateurs peuvent réserver les messages aux administrateurs : [permissions de groupe](https://faq.whatsapp.com/526742385997912/?cms_platform=web), consultées le 16 septembre 2026.
- Aucun lien d'invitation de groupe n'est enregistré : inviter des membres et publier un message sont deux opérations différentes.
- Une modification de l'actualité, de son image ou de la description de celle-ci impose un nouvel enregistrement puis une nouvelle validation avant son partage. La vérification de la révision inclut le visuel ; une modification non enregistrée bloque le partage.
- Le choix des groupes, l'envoi effectif, les erreurs du client et les éventuels doublons restent sous le contrôle du responsable. Le prototype ne dispose d'aucun accusé de livraison WhatsApp.
- Le contrôle local peut vérifier l'aperçu, l'encodage du lien et la validation. Le parcours dans un compte connecté et la réception dans un groupe restent à vérifier par l'utilisateur lors d'un essai réel autorisé.

## Automatisation future par API

Le prototype n'intègre aucune API WhatsApp Business. La référence [Groups API de Meta](https://developers.facebook.com/docs/whatsapp/cloud-api/groups/) a renvoyé HTTP 429 lors de la recherche du 16 septembre 2026 ; son contenu n'a pas pu être vérifié. Cela ne permet pas d'affirmer que toute publication automatisée dans des groupes serait impossible ou autorisée pour les groupes actuels du club.

TBD avant tout choix d'automatisation : éligibilité du compte, conditions et limites officielles applicables, compatibilité des groupes existants ou nécessité de groupes créés par API, droits des responsables, coûts, service serveur et conservation sécurisée des accès. Aucun seuil de volume, nombre de participants ou statut de compte requis n'est présenté comme vérifié.

Le raccordement d'un compte, l'activation d'un service serveur ou un essai d'envoi réel exigeraient une proposition concrète et l'accord explicite de l'utilisateur. Les [commandes sensibles](../docs/commandes-sensibles.md) restent la référence de contrôle ; aucun secret ne doit être placé dans le navigateur ou la conversation.
