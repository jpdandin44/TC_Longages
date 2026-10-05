---
project: TC_Longages
document_type: integration-assessment
title: Sources FFT pour les compétitions du club
status: active
version: git
created: 2026-10-05
updated: 2026-10-05
owner: jpdandin
tags: [fft, tenup, adoc, competitions]
---

# Sources FFT

Le responsable fournit deux liens et précise que V2 doit afficher les compétitions.
La présence ou l'absence des compétiteurs serait renseignée par le capitaine
dans l'outil FFT. Cette organisation est une exigence pour la suite ; la lecture
effective de ces disponibilités et son raccordement restent à qualifier.
Les observations du 5 octobre sont dans le
[reçu de consultation](../data/fft-sources-verification.json). Aucun compte,
effectif privé ou résultat de présence n'a été importé.

## Ten’Up public

La [page compétitions du club 60310230](https://tenup.fft.fr/club/60310230/competitions)
est consultable sans connexion. Elle donne les dates, équipes opposées,
championnats, catégories d'âge, sexe, niveau régional/départemental et liens de
rencontre. Exemple : **Bérat TC 1 / Longages TC 1, 11 octobre 2026**, championnat
GAN 35 Messieurs ; **Aspet Encausse TC 2 / Longages TC 1, 18 octobre 2026**,
Coupe Claude Bonnefont Senior Messieurs.

Le [détail de la rencontre du 11 octobre](https://tenup.fft.fr/championnat/82647633/division/142822/phase/231385/poule/510367/rencontre/9822965)
affiche une catégorie 35 ans Homme. Division et poule sont « Non renseigné » ;
la feuille de match n'a pas encore été saisie. Les informations complètes des
joueurs demandent une connexion et une licence. Ne pas inventer heure, score,
composition, présence ou adresse de rencontre à partir de cette consultation.

La fiche publique affiche encore l'ancien site FFT et le contact FFT historique.
Le site actuel conserve `tclongages@gmail.com` comme contact confirmé. La mise
à jour de la fiche FFT vers `https://tclongages.fr/` est une action ultérieure à
effectuer dans le compte du club ; aucune modification externe n'est exécutée.

## ADOC

Le [lien fourni vers l'équipe 2463261](https://adoc.app.fft.fr/adoc/gsEquipe.do?method=read&equipe.identifiant=2463261)
affiche « Votre session a expiré. Veuillez vous reconnecter. » Aucun détail
d'équipe ni présence n'est lisible dans cette session. La reconnexion doit se
faire avec les accès FFT appropriés, sans mot de passe dans la conversation.
L'identifiant ADOC 2463261 ne peut pas être rapproché automatiquement de
l'équipe Ten’Up 2470090 observée dans le détail : saison et correspondance
restent inconnues.

## Raccordement à réaliser

1. Afficher les compétitions publiques sur le site avec une source actualisable
   et leurs liens FFT. Le [calendrier Google](google-calendar.md) est un autre
   affichage, indépendant tant qu'aucune alimentation FFT n'est qualifiée.
2. Examiner ADOC en session autorisée : écran de disponibilité, saison,
   équipe, rencontre et sens exact de présence/absence. Une composition ou une
   feuille de match ne prouve pas une déclaration de disponibilité.
3. Qualifier le mécanisme disponible auprès de FFT : API officielle, export ou
   autre moyen autorisé, droits, limites et fréquence. TBD — aucune API FFT,
   synchronisation ou automatisation continue n'est actuellement qualifiée.
4. Garder les informations personnelles des compétiteurs dans le périmètre
   privé autorisé ; définir ce qui peut apparaître sur le site public avant
   un affichage de noms ou présences. Le dossier d'adhésion reste distinct.

Le [cadrage ADOC de communication](adoc.md) concerne seulement les aperçus
d'actualités du prototype historique. Il n'implémente pas cette lecture FFT.
