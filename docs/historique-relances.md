---
project: TC_Longages
document_type: validation-guide
title: Historique des tentatives et relance ciblée
status: active
version: git
created: 2026-11-19
updated: 2026-11-19
owner: jpdandin
tags: [bureau, communications, recette]
---

# Historique et relances

## Cadrage

Afficher les destinataires et les tentatives, puis préparer une relance ciblée depuis le Bureau. Périmètre autorisé : local et préproduction ; aucun courriel envoyé et aucune modification de production.

## Développement local

Les nouveaux reçus conservent l'adresse destinataire et l'identifiant de l'acteur. `MailHistory` déroule les reçus `previous` et affiche chaque tentative, sa date en heure de Paris, son résultat et le code HTTP disponible. Les anciens destinataires sont rapprochés par empreinte du carnet déjà enregistré, sans lecture réseau Google. Une adresse introuvable reste explicitement inconnue. Les détails bruts du fournisseur et les liens personnels ne sont jamais exposés.

Seuls les derniers reçus en échec ou rejet confirmé ouvrent la préparation d'une relance. La communication doit toujours être validée ou publiée, au même numéro de révision approuvé. Le formulaire exige une sélection et une confirmation ; la relance exclut les envois acceptés, incertains et les destinataires jamais contactés. Les droits OAuth et métier, les verrous, la validité de l'aperçu et la liste autorisée sont conservés. Aucun envoi n'est déclenché par un lien GET.

## Préproduction

Activer uniquement en préproduction le paramètre privé `tcl_member_mail.simulation_only = TRUE`. Il bloque le transport avant lecture du jeton Gmail, accès au carnet, génération des liens ou écriture de reçus. Quatre adresses `adherent-01` à `adherent-04@example.invalid` permettent les essais. Les autres traces sont anonymisées à l'affichage. Aucun import de production.

Parcours de recette :

1. Ouvrir [Historique des envois](https://preprod.tclongages.fr/bureau/envois), sélectionner la communication de recette.
2. Vérifier les adresses fictives, les tentatives précédentes et les filtres Échec/Rejet confirmé.
3. Cliquer « Préparer une relance ciblée » : aucun envoi à l'ouverture.
4. Sélectionner un échec et une adresse acceptée, confirmer puis simuler : un destinataire éligible et un exclu.
5. Répéter sans confirmation : refus ; vérifier que les reçus et réponses restent inchangés.

Le test PHP `tests/gmail-communications.php` couvre le transport simulé, les tentatives et les protections. La preuve hébergée est conservée dans le suivi canonique `../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json` depuis la racine du worktree.

## Mise en production

Non autorisée pour ce lot. Aucun envoi réel de recette. La livraison éventuelle devra conserver les sauvegardes et les anciennes tentatives. La simulation ne peut pas s'activer sur le domaine de production.

## Sécurité, données et retour arrière

Les adresses sont des données privées visibles uniquement par les utilisateurs autorisés aux communications. Aucun nouveau droit Google ; aucune migration ni export public. Les nouveaux champs sont compatibles avec les anciens lecteurs. Restaurer les quatre fichiers du module sauvegardés et les paramètres privés, puis reconstruire le cache ; ne pas restaurer une base ancienne sur des réponses récentes.

Les anciens reçus ne permettent pas de reconstruire des tentatives absentes ni de prouver la réception finale. La qualification « rejet confirmé » doit reposer sur une preuve ; ce lot n'ajoute aucune qualification automatique.
