---
project: TC_Longages
document_type: operating-guide
title: Piloter les versions et leur recette dans le cockpit
status: active
version: git
created: 2026-10-06
updated: 2026-10-06
owner: jpdandin
tags: [cockpit, versions, recette, github]
---

# Piloter les versions et leur recette

## Parcours disponible

L'accueil du [cockpit local](http://127.0.0.1:4181/) affiche les lots raccordés,
leurs changements, la PR candidate, les contrôles, la livraison observée et les
liens vers le **site** de recette. Les pages de pilotage restent locales.
Le suivi historique V1 reste accessible par « Consulter le suivi historique V1 ».

Chaque lot présente exactement **Cadrage, Développement local, Préproduction et
Mise en production**. Les libellés des critères viennent du suivi canonique,
sans réécriture. Choisir la phase, examiner le site et la PR, puis renseigner les
critères, le commentaire et la confirmation personnelle.

- **Revue** enregistre le dossier à examiner.
- **Valider** exige tous les critères, un commentaire, la confirmation, un candidat
  vérifié, une PR fusionnée sur les mêmes sources et les contrôles techniques.
- **Demander des corrections** conserve la demande et les décisions précédentes.

La fusion ne valide aucune phase. Une validation antérieure devenue incompatible
avec le candidat ou une nouvelle correction reste dans l'historique. La phase
suivante se traite sans bouton de démarrage ni autorisation intermédiaire.
Les brouillons de saisie survivent aux changements de lot, de phase et au
rechargement dans le même onglet ; la confirmation doit être renouvelée après
rechargement ou changement du dossier. Aucun secret ne doit être saisi ici.

## Limites et décisions distantes

Le bouton « Actualiser GitHub et le suivi » relit GitHub et les observations
canoniques. La validation relit GitHub sans se contenter de l'état affiché.
Le lien Actions ouvre l'exécution attachée au lot. **Le déclenchement des Actions
depuis le cockpit et la synchronisation continue ne sont pas implémentés.**

Ces trois décisions enregistrent une revue personnelle déclarée sur le poste,
pas une identité authentifiée distante. Elles ne fusionnent pas de PR, ne
changent pas les permissions et n'exécutent pas de déploiement. En production,
l'accord explicite, la livraison et ses contrôles doivent être consignés
séparément sur le candidat et la cible avant validation de la dernière phase.

## Sources et lancement

Le code du cockpit lit `developmentIterations` dans le
`docs/suivi-chantier/suivi-chantier.json` de chaque checkout déclaré. Il écrit
uniquement les événements de revue du lot choisi dans cette même source.
Les anciens champs `phases`, `decisions`, `history`, `publication`, `release`
et les accords distants sont préservés. Une copie privée du JSON précédent est
gardée dans `.local/framework-iteration-backups/` avant chaque décision.
Une révision périmée refuse l'écriture et conserve la saisie à l'écran.

Créer un fichier privé `.local/cockpit-sources.json` dans le checkout du cockpit :

```json
{
  "project": "tclongages",
  "historyRoot": "CHEMIN_ABSOLU_DU_CHECKOUT_HISTORIQUE",
  "iterationRoots": ["CHEMIN_ABSOLU_DU_LOT_V1", "CHEMIN_ABSOLU_DU_LOT_V2"],
  "port": 4181
}
```

Les chemins doivent être remplacés par les checkouts existants ; aucun chemin
n'est accepté depuis le navigateur. Le lancement lie le serveur à `127.0.0.1` :

```powershell
node scripts/cockpit-local.mjs .local/cockpit-sources.json
```

Le serveur historique conserve sa commande `npm.cmd run framework` lorsqu'aucun
raccordement de lots n'est configuré. L'arrêt ou le remplacement d'un serveur
doit viser son PID et son verrou vérifiés ; les runtimes Drupal et leurs bases
restent indépendants. Conserver les brouillons historiques avant remplacement.

## Situation du lot V1.1 et partenaires

Le lot support/édition possède son propre candidat et sa recette de
préproduction. Son état daté reste dans le reçu du checkout `support-v1`,
`data/support-v1-verification.json`. Il n'atteste pas une mise à jour de production.
L'éditeur est [Pages du club](https://preprod.tclongages.fr/admin/content/tcl-pages),
accessible après connexion Drupal ; le
[suivi support](https://preprod.tclongages.fr/admin/reports/tcl-support) est privé.

La bannière **Nos partenaires** est un besoin V1.1 enregistré, encore à développer :
bas des pages publiques, noms et logos, liens facultatifs, ordre et visibilité
administrables dans Drupal. Aucun logo ni lien n'a été fourni. Le bandeau sera
masqué si aucun partenaire actif n'est renseigné ; la présentation devra être
contrôlée sur mobile et ordinateur. Ne pas afficher de partenaire fictif ni
transformer cette exigence en fonctionnalité déjà livrée.

Le lot V2 Bureau reste local ; ses décisions et sa recette hébergée sont distinctes.
Les notes de version distribuées et le pilotage des workflows restent les
prochaines capacités du cockpit prévues par la feuille de route.
