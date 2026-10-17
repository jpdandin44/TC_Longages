---
project: TC_Longages
document_type: release-note
title: Correctif des tarifs des cours adultes
status: prepared
version: git
created: 2026-10-17
updated: 2026-10-17
owner: jpdandin
tags: [tarifs, correctif, v1, v2]
---

# Correctif des cours adultes

## Contenu approuvé

Confirmation directe du responsable le 17 octobre :

| Formule | Tarif |
| --- | --- |
| Cours Adultes | 150 € |
| Cours adultes compétition | 170 € |
| Adhésion Adulte compétition | 100 €, inchangé |

Le correctif remplace uniquement la ligne commune « 125 € ou 150 € » par
deux lignes et actualise sa note explicative. Les huit autres formules,
la saison, les conditions non précisées et les autres fonctions restent intactes.
Aucun cumul cours/adhésion, licence incluse ou durée de cours n'est inventé.

## Sources et contrôles

La [correction approuvée](../data/correctif-tarifs-adultes.json) complète le
[relevé historique](../data/tarifs-inscription.json), lequel reste la transcription
inchangée des PDF et la référence des anciennes démonstrations.
La construction officielle utilise le catalogue corrigé ; l'instantané bêta
`config/launch-beta/source/` reste immuable.

`node scripts/prepare-adult-tariffs.mjs` prépare sous `.local/tarifs-adultes/`
un delta `correctif.json` et un aperçu autonome `apercu-tarifs.html`.
**Cet aperçu ne doit pas remplacer la page hébergée : il ne contient pas les
textes privés éventuellement révisés dans Drupal.** Il ne constitue pas un
paquet complet de redéploiement.

Contrôles locaux : montants et libellés exacts, huit autres lignes inchangées,
refus d'une source différente ou déjà corrigée, conservation des empreintes
éditoriales Drupal et d'un texte personnalisé lors du rendu de la note corrigée.
Le test PHP nécessite `mbstring` ; il doit être exécuté, pas seulement ignoré.
La recette navigateur ciblée utilise `scripts/verify-adult-tariffs.cjs`
avec Playwright disponible, sans requête réseau ni soumission de formulaire.

## Livraison et retour arrière à qualifier

Le delta est préparé, **pas installé**. Avant écriture, après revue/merge et
accord explicite sur cette intervention :

1. Relire l'état réel de production et préserver sa maintenance. Ne toucher
   ni à la préproduction active ni aux accès ou aux formulaires.
2. Sauvegarder dans un nouveau dossier privé le modèle actif de l'accueil,
   sa configuration de rattachement, les textes courants et la révision du
   nœud. Vérifier les empreintes et une restauration isolée avant écriture.
3. Recetter sur une copie privée autorisée le remplacement unique de la ligne
   de tableau. **Ne pas changer le paragraphe source de la note** : son changement
   invaliderait l'empreinte des rubriques Drupal. Modifier uniquement sa valeur
   éditoriale avec l'API native et une nouvelle révision, après comparaison avec
   l'ancienne valeur. Refuser si elle a été personnalisée entre-temps.
4. Vérifier le rendu, les trois tarifs ci-dessus, les autres textes et pages,
   l'état de maintenance inchangé, puis enregistrer le reçu de livraison.
5. En cas d'échec, revenir au seul modèle sauvegardé et à la valeur de note
   précédente, avec garde contre les modifications concurrentes. Ne jamais
   réimporter une ancienne base complète après le lancement public.

Aucun outil d'application distante, restauration de base, nouvelle permission
ou ouverture publique n'est livré par ce correctif. La sauvegarde fraîche,
la recette hébergée et la répétition du retour arrière restent à réaliser.

## Quatre phases

| Phase | État du présent correctif |
| --- | --- |
| Cadrage | Deux cours 150/170 confirmés ; adhésion compétition à 100 conservée. |
| Développement local | Delta, rendu officiel, tests et note préparés pour revue. |
| Préproduction | Nouvelle recette isolée non exécutée ; préprod active inchangée. |
| Mise en production | Non effectuée ; sauvegarde fraîche, retour qualifié, revue/accord et contrôles après livraison requis. |

Suivi canonique : itération `tcl-tarifs-adultes-20261017` du checkout opérationnel
`../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`.
Les autres itérations et leurs décisions restent inchangées.

## V2 demandée, non implémentée ici

Prévoir des tarifs administrables directement dans Drupal, avec saison,
libellé et montant distincts, droits d'édition, aperçu, révisions et validation.
Le tableau public et le formulaire d'inscription devront utiliser une même
source native ; pas de double saisie dans le code. Les droits précis et le
processus de publication restent à cadrer en V2. Le présent correctif urgent
n'ajoute aucun écran d'administration et ne remplace pas la préproduction.
