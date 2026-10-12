---
project: TC_Longages
document_type: delivery-workflow
title: Raccorder le Bureau OAuth à la préproduction TC
status: active
version: git
created: 2026-10-12
updated: 2026-10-12
owner: jpdandin
tags: [preproduction, bureau, oauth, sauvegarde, recette]
---

# Raccorder le Bureau OAuth à la préproduction TC

Ce workflow complète le protocole commun et son suivi canonique ; il ne crée
aucune autorisation de merge, de modification du fournisseur ou de livraison.
Le candidat est décrit dans le [guide](../docs/bureau-oauth.md) et le
[contrat](../api/bureau-oauth.md). Les commandes et accès réels doivent être
requalifiés avant leur exécution distante.

## Cadrage

Reprendre `tcl-v2-bureau-oauth` dans le suivi existant. Conserver la déclaration
de V1 confirmée et l'absence de retour signalée par le responsable.
Identifier le fournisseur préproduction effectivement validé et les comptes
qui doivent accéder au Bureau. Aucun compte ou client d'une autre application
n'est réutilisé implicitement. Préparer le client confidentiel **TC uniquement**,
scopes `openid profile email` et callback exact TC ; accord applicable et
accès administrateur du fournisseur nécessaires à sa création.

## Développement local

Conserver SHA de branche/PR, preuves de tests, configuration exemple inerte et
résultat navigateur. Le fournisseur local n'est pas une preuve d'OAuth hébergé.
Les outils de recette SQLite, comptes et clés fictifs restent hors du paquet.

## Préproduction

Cible connue à recontrôler : `https://preprod.tclongages.fr/`, racine
`/home2/daje5127/tcl-preproduction/drupal/web`. La protection Basic Auth des
bêta-testeurs reste distincte de l'identité OAuth Bureau. Préserver cette
protection, les textes/révisions, contacts et signalements. Aucun changement
de `public_html` ou d'une autre application AVEREO n'est inclus.

Avant installation : vérifier candidat/CI/revue, accord de cette livraison,
état actuel des modules et rôles, configuration privée dédiée, sauvegarde
fraîche fichiers/base et restauration contrôlée. L'activation de `tcl_bureau`
est additive mais refuse les collisions de rôles préexistants : les examiner
avant d'activer, sans suppression ni élargissement automatique.

Installer le module OAuth et le module Bureau avec l'outil Drupal qualifié
sur la cible, puis reconstruire le cache/routes. Aucun import de base locale
ni réinitialisation des sept pages. La nouvelle entrée est insérée au rendu
serveur ; les HTML existants ne sont pas à remplacer pour ce bouton.
Associer les identités stables à des comptes TC existants autorisés, dans la
configuration privée ; un nouveau compte reste une action explicitement suivie.

Recetter réellement : bouton sur sept pages, retour HTTPS, connexion du Bureau,
Capitaine sans dossier privé, refus de login natif comme contournement,
saisie/modification/rechargement, retrait de rôle/association, expiration,
déconnexion, conservation édition/agenda/photo/contact/support. Consigner les
résultats et limites dans le suivi, sans confondre succès OAuth et droits métier.

Retour arrière à préparer et tester : fermeture du nouvel accès Bureau,
conservation des tables/dossiers et des comptes, restauration du seul code et
de la configuration nécessaires depuis la sauvegarde. Ne pas restaurer une
base complète par-dessus des dossiers reçus depuis la livraison.

## Mise en production

Non autorisée dans ce lot. Préparer son propre candidat, récupération, revue
et accord exact après recette préproduction. Les accès et protections de V1
ne valent pas ouverture du Bureau aux données réelles. La suite métier conserve
équipes/matchs, partenaires et améliorations de communication demandés.
