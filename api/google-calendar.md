---
project: TC_Longages
document_type: integration-guide
title: Affichage du calendrier Google sur le site
status: active
version: git
created: 2026-10-05
updated: 2026-10-06
owner: jpdandin
tags: [google-calendar, calendrier, integration, v2]
---

# Calendrier Google du club

Le responsable demande l'affichage des événements directement sur le site.
Le lien fourni contient un `cid` correspondant à `tclongages@gmail.com`.
La consultation de l'intégration Google, sans connexion, affiche le 5 octobre :
« Les événements d'un ou de plusieurs agendas n'ont pas pu être affichés ici,
car vous n'êtes pas autorisé à y accéder. » Le
[reçu FFT/Google](../data/fft-sources-verification.json#googleCalendar)
conserve ce constat ; aucun événement privé n'est lu ou publié.

## Composant préparé

La [configuration](../config/officiel.json) conserve `calendarEmbedId: null`
et `calendarSharingReviewed: false` tant que l'agenda public n'est pas qualifié.
Après choix de l'agenda et contrôle réel de son partage, renseigner son ID et
la revue de partage. Le générateur affiche alors une vue Planning Google
intégrée à `calendrier.html`, en français, fuseau `Europe/Paris`, avec lien
d'ouverture Google et dimensions adaptées à la largeur de la page.

Les événements sont affichés depuis Google au chargement de l'agenda ; il n'y
a pas de copie manuelle à maintenir sur le site, de clé API, de mot de passe
Google ou d'accès à Gmail dans le code. Seuls les événements publics de l'agenda
choisi sont concernés. La politique des aperçus limite les cadres externes à
Google Calendar ; les autres ressources externes restent refusées.
Les tests vérifient le refus d'une configuration sans revue de partage,
l'échappement des paramètres et le refus de sources de cadre étrangères.

L'affichage d'événements réels et sa recette navigateur restent **bloqués par
le partage Google**. Un test du HTML avec une configuration fictive ne prouve
pas que l'agenda du club est public. Aucune bascule en production effectuée.

## Réglage à effectuer par le responsable

Le responsable a choisi **un agenda dédié aux événements publics du club**.
Le 6 octobre, il confirme qu'il n'est **pas encore créé**. L'ID reste nul et
l'affichage désactivé ; la création et le partage sont la prochaine action
nécessaire uniquement pour ce complément calendrier.
Il peut rester dans le même compte Gmail ; choisir cet agenda lors de la création
d'un rendez-vous décide de son affichage sur le site. Un repère de catégorie
(Compétition, Tournoi, Animation, par exemple) facilite la lecture ; sa convention
reste à préciser. Une catégorie ou une couleur ne change aucun droit de partage.
Google propose une visibilité Public/Privé par événement, mais elle ne rend pas
accessible un agenda non partagé : voir la
[documentation de partage](https://developers.google.com/workspace/calendar/api/concepts/sharing).
Un agenda mixte partagé en disponibilités peut exposer les créneaux occupés des
événements privés ; ce mécanisme n'est pas le choix retenu dans ce lot.

Dans Google Agenda,
ouvrir **Paramètres → Paramètres de cet agenda → Autorisations d'accès aux
événements**, puis choisir le partage public et le niveau qui permet l'affichage
des détails voulus. Cette action expose les événements de cet agenda ; vérifier
son contenu avant de l'effectuer. Sous **Intégrer l'agenda**, récupérer son
**ID de l'agenda** ou son code d'intégration et le transmettre sans URL privée
ni jeton. Aucun réglage de partage n'est modifié par l'agent dans ce lot.

La [documentation Google](https://support.google.com/calendar/answer/41207?hl=fr)
confirme qu'un agenda intégré n'est visible que par les personnes avec lesquelles
il est partagé, et qu'il doit être public pour être visible par tous.
Après ce réglage : vérifier sans session Google, activer le candidat local,
recetter dates/heures/détails et mobile, puis promouvoir ce même candidat dans
le cycle V2. Le lien entre événements FFT et agenda Google reste à concevoir ;
aucun transfert automatique de compétitions ou de présences n'est actif.
