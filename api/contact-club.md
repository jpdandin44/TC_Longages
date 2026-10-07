---
project: TC_Longages
document_type: integration-contract
title: Contact du club par Drupal Mail
status: active
version: git
created: 2026-10-07
updated: 2026-10-07
owner: jpdandin
tags: [contact, drupal, courriel, preproduction]
---

# Contact du club

## Parcours et destinataire

Le formulaire natif est intégré à `/contact.html`, avec le graphisme de la
vitrine. Il reçoit un nom, un e-mail ou téléphone, un message et l'accord
de transmission au club. La destination fixe est `tclongages@gmail.com` ;
aucun destinataire n'est accepté depuis le navigateur. Le nom est limité à
80 caractères, la coordonnée à 150 et le message à 2 000.

Drupal Mail envoie un texte simple, avec un objet fixe, depuis
`support@tclongages.fr`, domaine du transport déjà qualifié du signalement.
Une coordonnée e-mail valide devient Reply-To ; un téléphone reste dans le
corps. Cette intégration n'utilise ni l'API Gmail ni une connexion au compte
Google. Le formulaire de signalement garde son service et sa boîte distincts.

## Réglages privés

| Réglage | Valeur pour l'activation autorisée en préproduction |
| --- | --- |
| `tcl_contact_enabled` | `TRUE` |
| `tcl_contact_mail_mode` | `transport` |
| `tcl_contact_transport_qualified` | `TRUE`, transport PHP existant qualifié |
| `system.mail.interface.tcl_site_contact` | `php_mail` |

La recette locale et la copie restaurée utilisent `capture` et
`test_mail_collector`. Les autres courriels restent neutralisés par
`tcl_null_mail`. Un transport incohérent ou neutralisé ne produit jamais de
confirmation d'envoi. Ces réglages résident hors de la racine web ; aucune
valeur secrète n'est conservée dans ce document.

## Validation et protection

Le formulaire fonctionne sans JavaScript. Le serveur vérifie le jeton lié à
la session anonyme, l'origine, les champs obligatoires, les longueurs, les
coordonnées et un champ piège. Les retours à la ligne dans la coordonnée sont
refusés. Cinq messages par heure et adresse réseau sont autorisés, avec
identifiant réseau haché. Un verrou et une référence de soumission en session
empêchent de renvoyer le même POST. Les vingt dernières références restent
en session, sans corps de message.

Un refus du transport affiche une erreur et conserve la saisie. Une réussite
signifie que le service de courriel a accepté le message ; sa réception dans
Gmail reste un contrôle distinct, conformément au
[contrat Drupal Mail](https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Mail!MailManagerInterface.php/function/MailManagerInterface::mail/11.x).
Le formulaire n'ajoute pas de ticket ni de stockage métier des messages.
La base conserve les métadonnées natives de session/antispam ; les captures
de messages sont propres aux essais locaux et à la copie privée restaurée.
Aucune durée de conservation métier n'est inventée.

## Livraison et limites

L'activation concerne uniquement la préproduction protégée demandée par le
responsable. La production ne reçoit ni code ni configuration. Le
[workflow](../workflows/edition-contact-preproduction.md) et le
[reçu](../data/edition-contact-verification.json) portent l'état réellement
testé, la sauvegarde restaurée, le candidat et la réception Gmail restant
à constater. Aucun message réel de test n'est envoyé par l'agent sans l'accord
spécifique prévu par les consignes locales.
