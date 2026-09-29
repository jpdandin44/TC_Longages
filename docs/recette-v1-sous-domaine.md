---
project: TC_Longages
document_type: validation-report
title: Recette du paquet V1 pour le sous-domaine
status: active
version: git
created: 2026-09-24
updated: 2026-09-24
owner: jpdandin
tags:
  - officiel
  - demonstration
  - apache
  - recette
---

# Recette locale de la démonstration V1

L'utilisateur demande un ZIP permettant de présenter la nouvelle V1 sur son sous-domaine avant la future production. L'agent prépare et contrôle les fichiers localement ; le dépôt et l'ouverture restent manuels. Aucun accès cPanel, DNS, certificat, compte Drupal ou service Google n'est modifié.

## Périmètre

Le [paquet V1 de démonstration](../livrables/tc-longages-v1-demo-o2switch.zip) est produit par `npm.cmd run officiel:demo:package`. Il dérive des sept pages de l'aperçu officiel validé, avec une bannière « DÉMONSTRATION V1 », un `.htaccess` distinct, `robots.txt` et le témoin `maintenance.active`. Les images, le logo, les tarifs, l'adresse au 35 chemin de Muret, Gmail et le support prévu sont conservés. Le contact reste un aperçu sans envoi ; les services Google et la connexion sont inactifs.

Le [guide de dépôt](publier-v1-sous-domaine.md) décrit la sauvegarde, l'extraction privée, la fermeture avant copie et le retour arrière. L'ancien ZIP `tc-longages-officiel-apercu.zip` garde son refus 503 inconditionnel ; il n'est pas le paquet à utiliser pour cette présentation. L'ancienne démo d'inscriptions/communication reste également une variante distincte.

## Tests et intégrité

- `npm.cmd run check` : **93 tests réussis, zéro échec**, dont six nouveaux tests de construction et d'archivage ; [journal](recette/v1-sous-domaine/tests-node.txt).
- Refus des sorties inattendues, des fichiers modifiés et des liens symboliques ; conservation de la fermeture par défaut et de la liste autorisée ; les sept pages ne divergent de l'aperçu que par leur bannière.
- Dix entrées ZIP exactes, CRC vérifiés, tailles et SHA-256 conformes au [manifeste de livraison](../livrables/tc-longages-v1-demo-o2switch.manifest.json). Chaque entrée est identique à sa sortie générée et à la copie contrôlée sous Apache ; [rapport d'intégrité](recette/v1-sous-domaine/archive-results.json).
- L'archive contient `maintenance.active`, aucun `maintenance.inactive`, aucun serveur applicatif, secret, compte, module Drupal ou ancien écran de bureau. Elle ne modifie pas les variantes précédentes.

Les octets et empreintes sont maintenus dans les manifestes générés et ne sont pas dupliqués manuellement ici.

## Contrôle Apache réel, local

Une copie temporaire sous `tmp/` a été servie par l'image Docker déjà disponible `httpd:2.4-alpine`, Apache **2.4.68**, uniquement sur `127.0.0.1` et un port éphémère. `mod_rewrite`, `mod_headers` et `AllowOverride All` sont actifs selon [la configuration de recette](../tests/apache-v1.conf). La sortie officielle et le ZIP ne sont jamais ouverts par ces essais : seuls les témoins de la copie temporaire sont renommés. Le conteneur est arrêté après les contrôles.

Le [script Apache](../scripts/verify-v1-subdomain-apache.mjs) a exécuté **121 contrôles réussis**, consignés dans les [résultats HTTP](recette/v1-sous-domaine/apache-results.json).

| État / action | Résultat local |
|---|---|
| `maintenance.active` présent à la livraison | Racine, sept pages et robots : 503 en GET/HEAD. |
| Renommage en `maintenance.inactive` | Racine, sept pages et robots : 200 en GET/HEAD ; contenu servi identique aux fichiers livrés. |
| Aucun témoin | Fermeture 503, sans ouverture accidentelle. |
| Deux témoins présents | Fermeture 503. |
| Retour au seul témoin actif | Fermeture 503 rétablie. |
| Anciens fichiers résiduels réellement présents | 404 sur bureau, communication, inscriptions, adhérer, parcours, actualités-bureau, index.php, app.cjs, .env, fichier et dossier fictifs hors liste. |
| POST, PUT, DELETE, OPTIONS sur Contact | 405 ; seules les consultations GET/HEAD sont admises. |
| `.htaccess` et témoins demandés directement | 403 ou 404, aucun contenu technique exposé. |
| Réponses contrôlées | `Cache-Control: no-store`, `X-Robots-Tag: noindex, nofollow`. |

Ces résultats valident les règles sur cet Apache local. Ils ne prouvent pas que la cible o2switch les interprète, ni qu'aucun cache distant ne les contourne. Le guide impose la vérification du 503 avant de copier les HTML, puis du 200 à l'ouverture et du 503 à la fermeture sur l'hébergement réel.

## Navigateur sur la copie Apache

Le [script de navigateur](../scripts/verify-v1-subdomain-browser.cjs) utilise uniquement la boucle locale. Les [résultats](recette/v1-sous-domaine/browser-results.json) attestent sept pages aux largeurs 1440, 390 et 320 px : bannière de démonstration, images décodées, un titre principal et aucun débordement horizontal. Les six raccourcis restent présents.

Le contact a été essayé avec des valeurs fictives : aperçu visible et message explicite « Aucun message envoyé ». Les liens e-mail visent Gmail ; support est un texte prévu, sans lien d'envoi. Aucune erreur JavaScript/console, requête externe ou écriture HTTP n'est constatée. Les [captures accueil mobile](recette/v1-sous-domaine/accueil-mobile.png) et [contact mobile](recette/v1-sous-domaine/contact-mobile.png) permettent la revue visuelle. Les liens mail ou externes n'ont pas été activés.

Une première attente d'image était trop précoce pour les images chargées à l'approche du défilement. Le script a été corrigé pour faire défiler chaque image puis attendre son décodage ; aucun correctif applicatif n'était nécessaire. La recette finale passe avec les règles CSP réellement servies par Apache.

Les captures finales d'accueil et de contact mobile ont été inspectées : contenu lisible, illustrations chargées et bannière de démonstration visible. Le conteneur temporaire est arrêté après cette dernière recette ; aucun autre service local n'a été modifié.

## Authentification et limites

La [note de mutualisation CONNECT](mutualisation-connect.md) répond séparément à la demande de réutilisation de Drupal/CONNECT. Elle repose sur un audit local en lecture seule et reste une proposition : choix d'instance à déterminer ensemble, domaines/applications autorisés à adapter, cloisonnement administratif et droits Bureau/Capitaine/équipe à qualifier. Aucun fichier AVEREO n'est modifié et aucun raccordement réel n'est effectué.

Cette livraison ne constitue pas une authentification, une bêta collectant des données ou un paquet de production. Le domaine final, HTTPS, les sessions, comptes, ressources Google, messagerie/support et traitement du contact restent à mettre en service après préparation, recette et accord explicite. La validité locale du ZIP ne vaut pas publication du site.

## Vérification documentaire

Le socle obligatoire existe. Treize documents impactés ont été contrôlés, avec 235 liens locaux valides et métadonnées conformes. Le rapport `docs/recette/v1-sous-domaine/documentation-results.json` identifie le périmètre. Les documents et le guide ont été rapprochés du générateur, des manifestes et des résultats de recette. Aucun problème détecté dans ce périmètre vérifié ; l'architecture physique Drupal/CONNECT et l'hébergement réel restent à déterminer et qualifier séparément.
