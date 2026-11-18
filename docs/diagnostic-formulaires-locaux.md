---
project: TC_Longages
document_type: qualification
title: Diagnostic local des soumissions Contacts et brouillons
status: active
version: git
created: 2026-11-18
updated: 2026-11-18
owner: jpdandin
tags: [recette, bureau, contacts, communications]
---

# Diagnostic local des soumissions

## Cadrage

Reproduire les échecs de soumission constatés en préproduction après PR31, sans courriel ni écriture distante. Source fusionnée : `b320895a5c07af6230bed0e649021abcb04f54b6`. Copie distincte `bureau-form-post`, base SQLite neuve, identités OAuth et contacts fictifs. Aucune base, identité ou configuration de production copiée.

## Développement local

[Preuve structurée](../data/bureau-form-post-local.json) : 20 contrôles HTTP réussis sans protection Basic, puis les mêmes 20 avec une barrière Basic locale. Chrome confirme aussi la mise à jour des contacts et la création/relecture du brouillon local n°3, révision 1.

Parcours vérifiés : formulaire Contacts, actualisation, dédoublonnage, exclusion d'adresse invalide, persistance du résumé, panne Google, mauvais libellé, conservation du dernier succès, refus CSRF et anonyme ; création de brouillon, redirection unique locale, textes et variante WhatsApp conservés, modification/relecture, refus d'une version périmée, refus CSRF, brouillon non public.

**Aucune boucle reproduite et aucun correctif applicatif inventé.** Les modifications portent sur un banc d'essai HTTP qui manquait, pas sur les gestionnaires métier. Les tests unitaires seuls ne prouvaient pas les soumissions Form API et leurs redirections. Un premier essai du nouveau test a échoué sur le traitement de l'URL absolue de revue par le test lui-même ; le test vérifie désormais la destination locale avant d'en extraire le chemin.

### Rejouer

Sur un checkout isolé, préparer une installation locale **neuve** avec les scripts existants : `node scripts/build-officiel.mjs`, `scripts/drupal-local.ps1 -Action prepare -Port 4186`, puis `-Action install -Port 4186`, et `php -c .local/drupal-tools/php.ini drupal/bin/prepare-bureau-oauth-local.php`. Ne pas réinstaller une base existante. Les dépendances correspondent au lock Composer.

Créer `.local/registration-recipe.enabled`. Dans le `settings.php` local ignoré par Git, ajouter uniquement cette configuration fictive :

```php
$settings['tcl_member_contacts'] = [
  'enabled' => TRUE, 'client_id' => 'fixture-only',
  'client_secret' => 'fixture-only', 'refresh_token' => 'fixture-only',
  'group' => 'contactGroups/fixture',
];
```

Exécuter `scripts/bureau-form-post-recette.ps1`, puis avec `-Basic`. Les ports 4186 et 4190 doivent être libres ; le script ne termine pas les services déjà présents et arrête seulement ses propres processus. Les preuves détaillées sont produites dans `.local/bureau-form-post-results.json` et `.local/bureau-form-post-basic-results.json`.

Le module `tests/fixtures/tcl_post_fixture` est copié dans le checkout de test seulement, jamais dans un artefact hébergé. Il refuse une base non SQLite, simule Google, autorise uniquement le fournisseur OAuth loopback sur 4190 et refuse toute autre destination HTTP. PHP `mail` est désactivé, le transport local est nul et toute préparation de courriel dans le module d'essai lève une erreur. Le routeur Basic est réservé au serveur PHP de développement sur 127.0.0.1. Les identifiants `fixture:fixture` sont des constantes de test, pas des accès au club.

## Préproduction

Aucune intervention distante dans cette session locale. Le défaut hébergé reste **non résolu**. Les différences restantes incluent HTTPS, serveur frontal, protection hébergée, session réelle, configuration et dépendances réellement servies. La barrière Basic locale n'est pas une reproduction d'Apache/o2switch. Prochaine action : une investigation ciblée de la chaîne de requête hébergée, avec preuve de réponse/redirection, plutôt qu'un redéploiement de formulaires déjà fonctionnels localement. Ne pas attribuer définitivement le défaut au navigateur ou à l'hébergeur sans cette preuve.

## Mise en production

Exclue. Aucun courriel, publication, fusion, déploiement ou changement de protection distante. La recette PR31 reste non validée ; ces résultats locaux ne lèvent pas les réserves hébergées.


## Correctif ciblé après capture réseau hébergée

Une capture en continu a identifié 19 réponses HTTP 307 vers la même URL, émises par `o2switch-PowerBoost-v3` avec l'en-tête `tiger-protect-security`. La requête POST ne comporte pas de Referer et annonce `no-referrer`. Les événements n'étaient pas visibles après la navigation vers l'écran d'erreur : la capture pendant la soumission a été nécessaire. Cette preuve remplace l'hypothèse d'un défaut de pilotage seul.

Le candidat change uniquement la politique Referrer des formulaires `/bureau/contacts-google`, `/bureau/communication/ajouter` et `/bureau/communication/{id}/modifier` en `same-origin`. L'adresse du formulaire peut être transmise au même site, jamais aux origines externes. Les autres pages privées et les retours OAuth restent `no-referrer`. Aucun droit, cookie, jeton CSRF, secret ou réglage Tiger Protect modifié.

Le contrôle HTTP ajouté échoue avant ce changement, puis les 25 contrôles passent avec la protection Basic locale, y compris confidentialité OAuth, refus des écritures anonymes et CSRF. La validation de la correction sur Tiger Protect réel reste nécessaire avant de déclarer l'incident résolu. Documentation de l'hébergeur : https://faq.o2switch.fr/cpanel/o2switch/tiger-protect-waf/ (intervention avant l'application).
