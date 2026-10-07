---
project: TC_Longages
document_type: deployment-workflow
title: Correctif édition et contact en préproduction
status: active
version: git
created: 2026-10-07
updated: 2026-10-07
owner: jpdandin
tags: [edition, contact, preproduction, sauvegarde]
---

# Correctif édition et contact

Le statut d’exécution, le candidat et les reçus assainis sont conservés dans
`editionContactPreproduction` du [reçu framework](../data/framework-revue-verification.json),
raccordé à l’itération `tcl-v1-edition-contact` du suivi canonique.
Le [reçu local](../data/edition-contact-verification.json) est la qualification
initiale antérieure au commit figé ; ses identifiants nuls ne désignent pas le
candidat livré.

## Cadrage

Le responsable confirme que l'agenda, la photo et les signalements installés
après #17 fonctionnent. Il demande des commandes d'édition visibles lorsqu'il
est connecté à Drupal et l'envoi du contact à `tclongages@gmail.com`.
Les pages standalone ne rendaient ni toolbar ni onglets Drupal ; le contact
était un aperçu sans transmission. Le compte administrateur installé possède
déjà les droits. Aucun rôle, droit ou compte n'est élargi.

## Développement local

Branche `fix/edition-contact-preprod`, après le commit fusionné de #17.
Les sept pages exposent **Modifier cette page** seulement à un compte Drupal
connecté autorisé à modifier le contenu natif correspondant. Le formulaire
de contenu et ses révisions sont conservés ; la destination revient sur la
page. **Pages du club** respecte sa permission propre. L'anonyme et le
compte en lecture restent sans commande d'édition. Les réponses sont privées.

Le contact utilise un formulaire Drupal sans Ajax dans la page existante.
Les [règles de transmission](../api/contact-club.md) décrivent son destinataire,
sa validation, ses erreurs et sa protection. Les sept modèles et les valeurs
éditoriales sont conservés ; le formulaire remplace seulement la région
d'aperçu à l'affichage, sans réinitialiser le contenu Drupal. Les libellés
fonctionnels et informations de transmission du formulaire restent dans le code.

## Préproduction

Cible unique : `preprod.tclongages.fr`, racine
`/home2/daje5127/tcl-preproduction/drupal`. Le paquet fixé sur un commit contient
les modules, les sept modèles et les outils natifs ; jamais core/vendor,
base active, comptes ou secrets. Ses octets et empreintes sont vérifiés.

Les outils [PHP](../scripts/edition-contact-hosting.php) et
[Python](../scripts/edition-contact-hosting.py) résident dans le dossier privé
d'hébergement. `stage` exige la source et l'empreinte exactes. Il sauvegarde
la base complète et les fichiers, restaure les fichiers, copie les 50 tables
actives dans un préfixe de récupération neuf et compare toutes les lignes et
tous les schémas. Les restaurations précédentes restent intactes. Après cette
comparaison, seuls les caches de la copie sont vidés pour retirer les chemins
absolus de l'ancien conteneur. Le candidat y est installé avec le contact en
capture ; un message fictif et sa double soumission produisent une seule
capture. Les sept pages, commandes d'édition, photo, vue Mois, contenus et
bouton support sont vérifiés. Aucun courriel réel n'est envoyé.

Le contrôle du passage du protocole commun doit réussir sur ce candidat
avant `apply`. L'état actif, les textes et les réglages privés doivent encore
correspondre à la répétition. La copie des fichiers est atomique, puis seuls
les quatre nouveaux réglages de contact sont ajoutés au runtime privé.
Le transport PHP préalablement qualifié est réutilisé ; son activation ne
prouve pas la réception dans Gmail. Le signalement et les autres courriels
gardent leurs réglages. La maintenance constatée, la protection d'accès et
la non-indexation sont conservées. Aucun essai ne modifie un texte actif.

Une erreur remet les fichiers et réglages précédents, sans écraser la base
ni les nouveaux signalements. Les archives, copies de récupération et reçus
privés restent disponibles. Le reçu publié est assaini : aucun texte,
paramètre secret ni contenu de courriel réel.

## Mise en production

Cette phase reste non autorisée. La recette du compte réellement utilisé
et la réception du contact dans Gmail restent distinctes des contrôles
techniques et des validations humaines. Le responsable ne doit pas valider
le cockpit pour examiner le site ; la PR et ses liens de recette ouvrent le
site du club. La production et l'ouverture publique exigent leur propre accord.
