---
project: TC_Longages
document_type: session-handoff
title: Point de session et reprise du site
status: active
version: git
created: 2026-09-29
updated: 2026-10-22
owner: jpdandin
tags: [session, reprise, framework, drupal, git]
---

## Raccordement Google Contacts — 22 octobre

People API et configuration OAuth confirmées actives dans le projet `tc-longages-preproduction`. Conditions acceptées par le responsable. Client Web prérempli avec retour exclusif `/bureau/contacts-google/retour` ; création personnelle et fichier privé attendus. Connexion administrative développée localement (état/PKCE, compte exact, libellé exact, jeton chiffré), 15 contrôles simulés réussis ; 14 régressions canal mail réussies. Aucun accès People API réel depuis le site, aucun import/campagne, production exclue.

Demande complémentaire : bouton explicite de réinitialisation dans Gestion des comptes, développé localement avec courriel adapté. 28 contrôles service et 19 HTTP réussis, aucun envoi réel. Prochaine étape : candidat CI et livraison ciblée des deux compléments, puis configuration privée Google et consentement personnel.

## Complément du 22 octobre — accès inscription et affiche

Demande autorisée : liens dans les courriels et les actualités hautes de l’accueil, miniature d’affiche, préproduction uniquement. Développement local : association explicite d’une actualité publiée à une occurrence ouverte, formulaire de demande de lien individuel par email, miniature PDF/image et retrait du doublon bas. Contrôles locaux 14 service + 12 HTTP réussis ; régressions bandeau 15/28 et canal mail 14/12 réussies. Livré en préproduction : source `1b01be2`, manifeste `ee0cead`, six CI réussies (39757966522), six fichiers vérifiés. Sauvegarde de cinq fichiers, une création inventoriée ; copies restaurées vérifiées. Chrome : miniature PDF Halloween, suppression du bloc bas, bouton accueil/article, formulaire et aperçu mail contrôlés. Communication fictive n°3 publiée en préproduction et associée à l’événement Beaujolais fictif ; aucun nouvel import. Demande de lien vers l’adresse de recette famille effectuée ; cPanel confirme remise acceptée le 22 octobre à 11 h 52. Réception du courriel confirmée par le responsable le 22 octobre ; utilisation du lien confirmée et vérifiée dans le Bureau : « Recette Lien Beaujolais », Je participe, six personnes. Total neuf avec les trois personnes du test Camille. Parcours personnel vérifié ; contrôle mobile restant. Voir le [reçu](../data/event-signup-verification.json). Google Contacts reste non connecté ; tarifs hors de ce lot.

## Reprise du 22 octobre — participations puis courriels

Lot livré et vérifié en préproduction uniquement : source `6d9f413`, manifeste `4d2f8ff`, six contrôles techniques CI réussis (39746190761). Sauvegarde ciblée : six fichiers existants restaurés dans des copies de contrôle, huit ajouts inventoriés ; aucune migration. Les 14 empreintes installées correspondent au candidat. Les permissions des deux fichiers CSS/JS ont été corrigées à 0644 après extraction privée ; affichage ensuite vérifié dans Chrome.

Recette hébergée : événement explicitement fictif, une seule personne répondante, Oui pour quatre puis Incertain (total zéro), Non, puis Oui pour trois ; total final trois vérifié au Bureau. Remplacement du lien invalide l’ancien sans perdre la réponse. Courriel fictif n°3 validé uniquement pour tester son aperçu : deux adresses autorisées, aucune présélection, aucun envoi réel. Google Contacts non configuré est explicitement bloqué. Le test Contact local sur ancien collecteur était en échec ; la même recette a réussi en CI propre, sans purge ni modification de ce test.

[Preuves du lot](../data/event-participation-mail-verification.json), [contrat](../api/participations-courriels.md), [livraison et retour arrière](../workflows/participations-courriels-preproduction.md). PR 24 en brouillon, sans conflit ; checklist humaine non cochée, aucun merge. Restent : dates officielles Beaujolais, libellé et connexion Google du site, liens individuels intégrés aux courriels et entrée publique, envoi/réception réelle du nouveau canal, contrôle visuel mobile et compléments accueil/tarifs. Production et calendrier Google inchangés.


# Point de session - preparation du 17 octobre 2026

## Clôture définitive du 21 octobre — améliorations enregistrées

Le responsable confirme l'ordre de reprise : participation aux événements avec nombre de personnes (Beaujolais nouveau), puis canal courriel adhérents. Ajouter au lot événement les miniatures et le bouton d'inscription dans le bloc d'actualités en haut de l'accueil ; retirer uniquement la liste « Actualités du club » du bas de l'accueil. Vérifier le tableau tarifaire contre le catalogue **publié** : décalage signalé mais non encore confirmé, préserver les tarifs historiques des dossiers.

Source détaillée : [exigences](../requirements.md#accueil-et-cohérence-tarifaire--complément-de-clôture-du-21-octobre). Aucun développement, test hébergé, envoi ou livraison pour ces nouveaux besoins ce soir. Session close ; attendre une demande de reprise, préproduction uniquement. Les acquis et réserves de la campagne précédente restent conservés.

## Idée ajoutée après clôture — 21 octobre

Nouveau besoin V2 consigné : réponses aux événements (Oui/Non/Incertain, nombre de personnes si Oui) et canal courriel adhérents depuis Google Contacts. Priorité métier : Beaujolais nouveau. Voir les exigences et la roadmap ; cadrage uniquement, aucun développement, accès Google, envoi ou déploiement effectué pour cette extension. La clôture précédente reste l'état de fin des développements du jour.

## Clôture du 21 octobre — sur demande du responsable

Session close pour aujourd’hui. Attendre une nouvelle demande de reprise. V2 en préproduction ; aucune intervention en production, aucun merge ou accord de phase créé à la clôture.

- Campagne exécutée partiellement : notifications adulte/mineur reçues selon le responsable, doublon refusé, tarifs brouillon restaurés, aperçus de supports existants et événements/récurrences contrôlés. Nouvel import différé par choix humain.
- Réinitialisation bornée aux destinataires autorisés corrigée ; courriel reçu et mot de passe personnel défini, confirmés par le responsable. Ne pas redemander cette création.
- Présentation du compte livrée en préproduction : source `206885b5488154023f175a218e0f98434c7ce046`, manifeste/HEAD `f20f15c21801437a9e93210c3015b86603387bce`, CI `39646909744` six contrôles réussis. Vérification visuelle ordinateur ; contrôle visuel mobile restant. Sauvegarde/retour arrière dans les preuves de présentation.
- PR 24 observée en brouillon et sans conflit au dernier contrôle de cette session ; non fusionnée. Aucun nouveau relevé GitHub à la clôture. Tests applicatifs non relancés pour la synthèse.
- Changements documentaires et preuves locales conservés, dont `data/account-presentation-verification.json`, `docs/campagne-recette-v2.md` et l’opérateur historique `workflows/agenda-events-preproduction/month.py`. Ne pas écraser les modifications antérieures. Git signale aussi trois fichiers de présentation ; leur diff textuel est vide après normalisation des fins de ligne, les empreintes livrées sont consignées.
- Serveur local de test 4186 arrêté à la fin du travail précédent. Sessions navigateur conservées ; aucune reprise automatique.

**Reprise :** lire la campagne et le suivi canonique dans `../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`, puis guider les validations personnelles restantes : connexion/déconnexion du compte invité et lien usagé, relecture des notifications et dossiers, publication/retrait des communications, partage manuel, tarifs historiques après republication et téléphone. Textes/créneaux officiels encore à compléter. Ne pas déclencher de nouveau courriel ni de publication sociale sans besoin dans le parcours autorisé.

La recette globale reste partielle ; les confirmations personnelles reçues ne valent pas acceptation de toute la V2. [Campagne](campagne-recette-v2.md), [preuves du compte](../data/account-presentation-verification.json), [bilan quotidien](<C:/Users/examg/.codex/bilans-quotidiens/2026-10-21.md>).

## Présentation du compte — 21 octobre

Création personnelle du mot de passe Artemis confirmée par le responsable. Habillage des écrans de compte livré en préproduction à 19 h 52 : carte centrée, champs et boutons harmonisés, action « Choisir mon mot de passe ». Source `206885b`, manifeste `f20f15c`, six CI réussies et 20 tests HTTP locaux du mot de passe. Affichage réel du compte vérifié sans soumission ; mobile visuel restant. [Preuves](../data/account-presentation-verification.json). Aucun accès ni modification de production.

## Réinitialisation corrigée le 21 octobre

V2-MAIL-01 livré en préproduction : plugin réservé aux destinataires de recette, source 8fa4f2c, manifeste 978027d, six CI réussies. Essai natif Artemis accepté à 19 h 34 ; réception confirmée par le responsable, création personnelle du mot de passe confirmée par le responsable le 21 octobre. Défaut NullMail conservé, production exclue. Voir [workflow et retour arrière](../workflows/reinitialisation-recette.md) et [reçu](../data/password-reset-recipe-verification.json). Le blocage ci-dessous conserve le diagnostic avant correction.

## Blocage réinitialisation du 21 octobre

V2-MAIL-01 confirmé par contrôle cPanel en lecture seule : user:password_reset utilise NullMail (aucun envoi), invitation Bureau utilise PhpMail. Voir la campagne de recette. Correction bornée du circuit natif à préparer ; aucun changement de configuration ni renvoi effectué. La réception des invitations précédentes reste valide, mais ne qualifie pas Mot de passe oublié.

## Diagnostic invitation du 21 octobre

Retour à l’accueil signalé ; Chrome vérifié connecté sous Admin_TC_Longages (uid 2), invitation Artemis (uid 3). Conflit de session probable conformément au parcours documenté. Essai personnel en fenêtre privée à faire ; lien secret non ouvert, expiration inconnue, aucun renvoi. Voir le diagnostic dans la campagne de recette.

## Complément de recette du 21 octobre

Doublon de même identité refusé après renouvellement de session ; un dossier adulte conservé. Catégories/filtres supplémentaires vérifiés et événement ponctuel créé depuis aperçu, puis conservé Annulé (comme la série). Aucun import ni production. Liste des validations personnelles et réserves techniques dans la [campagne](campagne-recette-v2.md#complément-de-recette-et-validations-personnelles-restantes). Le test mobile reste partiel : réduction du navigateur sans effet dans cette passe.

## Recette exécutée partiellement le 21 octobre

Résultats détaillés et réserves dans la [campagne V2](campagne-recette-v2.md), source de référence des résultats par scénario. Chrome authentifié : dépôts fictifs adulte/mineur, brouillon de tarif isolé, aperçus PDF existants, brouillon de canaux, récurrences et conflit de modification vérifiés. Notifications acceptées et réception des deux messages Adulte21/Mineur21 confirmée par le responsable le 21 octobre. Mot de passe et validations personnelles laissés au responsable ; nouvel import différé sur choix explicite du responsable, sans changer les permissions de fichiers Chrome. Série de test annulée, prix brouillon restauré ; dossiers et brouillon de communication conservés. Aucun déploiement, aucune écriture de production ou Google. Reprendre les scénarios partiels/bloqués du guide.

## Reprise du 21 octobre — préparation de campagne

[Campagne humaine V2](campagne-recette-v2.md) préparée depuis les preuves de clôture et les parcours implémentés. Tous les résultats restent À faire ; aucun test utilisateur, envoi, accès hébergeur ou déploiement effectué ce jour pour cette préparation. Prochaine action : exécuter les parcours dans l’ordre et consigner les anomalies. Les destinataires privés restent ceux déjà autorisés ; Google partagé en consultation seulement. Modifications locales du 20 octobre préservées, sans commit/push à cette étape.

## Clôture du 20 octobre — sur demande du responsable

Session close pour aujourd’hui. Reprise uniquement sur nouvelle demande. Agenda V2 et complément Mois livrés et vérifiés en préproduction ; campagne humaine différée. Aucune intervention en production ni écriture dans le calendrier Google partagé.

À la reprise : lire ce point et le champ `agendaEvolution.monthDefault` du suivi canonique dans `../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`. Préserver les modifications locales documentaires, les vues dérivées et l’opérateur `workflows/agenda-events-preproduction/month.py` encore non commités. Le code livré est poussé, source `d50dca1645fa4ff3f63bf9a8f4b23442dfa8ba26`, HEAD CI `13e9283cc0a883ff4b150e8d275529f2f93324dd`. Aucun commit/push supplémentaire à la clôture.

Prochaine étape : campagne humaine lorsqu’elle sera demandée ; textes et créneaux définitifs d’adhésion et activation personnelle du compte restent à confirmer. PR 24 en brouillon au dernier relevé, aucun accord de merge ou de production. Les processus locaux et sessions navigateur n’ont pas été arrêtés ou déconnectés lors de cette clôture.

## Vue Mois globale — livré et vérifié le 20 octobre

Le responsable demande Mois à chaque ouverture pour tous les visiteurs, pas seulement un réglage de son navigateur. Le code ignore désormais les préférences mémorisées et permet Semaine/Liste pendant la visite. 3 tests JavaScript et 13 tests de rendu passent. Deux fichiers livrés à 18 h 05 (Europe/Paris), contenus et protections conservés. Ancienne préférence Semaine ignorée ; Liste puis rechargement → Mois vérifiés dans Chrome. Source `d50dca1645fa4ff3f63bf9a8f4b23442dfa8ba26`, six contrôles CI réussis sur `13e9283cc0a883ff4b150e8d275529f2f93324dd`. Reçu dans [les preuves](../data/agenda-events-verification.json), champ `monthDefault`. Production exclue ; campagne humaine différée.

## Agenda V2 — livraison vérifiée le 20 octobre 2026

| Phase | État réel |
| --- | --- |
| Cadrage | Catégories et affiches dans l’agenda Bureau ; calendrier Google partagé en consultation seulement. |
| Développement local | Création, modification, récurrences, exceptions, annulation, sept catégories, filtres et miniatures réalisés. 29 HTTP, 19 métier, 13 rendu agenda et 3 JavaScript réussis. |
| Préproduction | Treize fichiers installés, puis correctif de deux fichiers ; recette Chrome ordinateur/mobile réussie, protection anonyme 401 conservée. |
| Mise en production | Exclue, aucune écriture. |

Source finale applicative `14cf8723e4ca811295427f94a1e41e8362246bea`, CI six contrôles réussis sur `627cb8a5b4da942b2d323c554edcf766061e9963`. [Preuves et reçus](../data/agenda-events-verification.json), [guide](evenements-bureau.md), [retour arrière](../workflows/agenda-events-preproduction/README.md). La première livraison et son correctif sont conservés séparément avec leurs empreintes.

Dernière CI de la branche vérifiée le 20 octobre : les six contrôles techniques ont réussi sur `3e87b9a2f0a938cb7d32203f4acb7f8d2768d26b`, exécution [39422131823](https://github.com/jpdandin44/TC_Longages/actions/runs/39422131823), terminée à 14 h 30 (Europe/Paris). La PR 24 reste en brouillon et sans conflit ; le contrôle de politique échoue uniquement sur les quatre attestations humaines non cochées. La campagne humaine est explicitement différée par le responsable. Cette vérification et la mise à jour du suivi ne modifient ni le code hébergé ni la production.

Deux défauts observés puis corrigés en recette : cache navigateur de l’ancien script Ambiance ; service du formulaire perdu après aperçu. Les commandes de vue et l’enchaînement aperçu/enregistrement ont été rejoués après correction. Deux événements explicitement fictifs restent dans le Bureau pour les essais du responsable ; aucune communication publiée ni message envoyé.

La campagne humaine reste différée. Textes et créneaux d’inscription définitifs, activation personnelle du compte invité et accord humain de merge restent distincts. La PR 24 demeure en brouillon ; ses quatre attestations humaines ne sont pas cochées. Le calendrier intégré n’est pas modifié par les événements privés de recette.

## Clôture du 19 octobre — sur demande du responsable

Session close, reprise uniquement sur nouvelle demande. Aucun déploiement des événements ce soir ; aucun changement de production ni écriture dans Google Agenda. Serveur Drupal local et fournisseur OAuth fictif lancés pour cette session arrêtés.

| Phase | État réel |
| --- | --- |
| Cadrage | Calendrier Google de production conservé en consultation ; écritures de recette séparées dans le Bureau, accord humain confirmé. |
| Développement local | Création/modification, récurrences, exclusions et annulation développées. 19 contrôles métier et 21 HTTP réussis. Source `83c98ee968a813e3e2d7c48dea4b68ad1ac9b561`, HEAD poussé `33e721d22529384b43608776c8fbf84e82267a08`. |
| Préproduction | Lot événements non livré. Version précédente communications/présentation agenda toujours dernier reçu connu (`9cd0b5a`). Cible cPanel accessible, aucune commande distante exécutée pour ce nouveau lot. |
| Mise en production | Exclue ; aucune action. |

PR [24](https://github.com/jpdandin44/TC_Longages/pull/24) en brouillon et sans conflit au relevé de clôture, cible `e7e71addee734317ef2da5706b290767a3b317e8`. [CI du candidat](https://github.com/jpdandin44/TC_Longages/actions/runs/39330938687) encore en cours au relevé : livraison-sécurité réussie, autres contrôles non conclus. Ne pas assimiler les six contrôles réussis du candidat précédent à ceux-ci. Aucun accord de merge.

Dernier relevé avant fermeture : trois contrôles techniques réussis (beta-release, delivery-safety, drupal-mysql), trois encore en cours (bureau-oauth-runtime, support-runtime, technical-ci). Le contrôle de politique PR est en échec ; résultat à relire à la reprise, sans cocher les attestations humaines. Aucun résultat final de CI annoncé.

Reprise précise :

1. Lire ce point, [le guide événements](evenements-bureau.md) et le suivi canonique dans `../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`, itération `tcl-v2-recette-20261018`. Conserver les changements documentaires de clôture et les deux vues V2 déjà modifiées.
2. Relever le résultat final de la CI sur `33e721d`, traiter tout échec ; compléter le contrôle visuel (aperçu local observé, enregistrement navigateur et mobile à terminer). Les 21 tests HTTP couvrent sauvegarde, édition, concurrence, CSRF, accès Capitaine et rejeu de création.
3. Mettre à jour la description de PR avec le nouveau lot, consolider les documents et actualiser le manifeste candidat si nécessaire. Préparer le paquet limité aux huit fichiers applicatifs : EventSchedule, EventRepository, EventController, EventForm, BureauController, RegistrationController, routing et services. Aucun paquet de ce lot n’a encore été créé.
4. Qualifier la sauvegarde/restauration fraîche des fichiers affectés et la conservation des contenus/protections. Aucun changement de schéma requis ; collection `tcl_bureau.recipe_events` créée à la première sauvegarde. Contrôle de passage avant installation exclusivement en préproduction.
5. Tester avec un compte Bureau : événement ponctuel, édition, série et date exclue, annulation/réactivation ; vérifier que le calendrier partagé reste inchangé. Garder les données fictives et ne pas envoyer de message.

Limites : modification de toute la série seulement ; déplacement individuel par exclusion puis événement séparé. Pas d’écriture Google, de publication publique des événements de recette ou de lien automatique avec une affiche. Campagne humaine toujours différée. [Règles complètes](evenements-bureau.md).

Les sources Markdown et le JSON canonique sont actualisés ; vues dérivées du cockpit non régénérées à la clôture conformément au workflow quotidien (aucun build supplémentaire). Les régénérer à la reprise. Les changements de clôture restent locaux, sans commit/push supplémentaire.

## Travail courant — événements Bureau en préparation

Création/modification et récurrences développées localement, avec stockage de recette distinct. Le responsable accepte de conserver le calendrier de production en consultation. Tests : 19 métier et 21 HTTP réussis ; qualification hébergée à poursuivre. [Guide et limites](evenements-bureau.md).

## Travail courant — communications et agenda livrés en préproduction

Les optimisations sont installées en préproduction : copie du message validé et repli manuel, présentation des supports, vues Mois/Semaine/Liste, trois palettes par navigateur et cartes des communications Événement publiées sur le site. Aucun envoi social automatique ni création d’événement dans Google.

Candidat applicatif `9cd0b5a47d5187fb2488ecee93eca2dbdf15a5c8`, paquet de neuf fichiers SHA-256 `2aabd94fd014543844b5f44460f212e19f849048b9e14f4aaf5f6d7f3452cec6`, livré le 19 octobre à 21:24:57 UTC. Sauvegarde/restauration des cinq fichiers précédents comparée ; quatre fichiers nouveaux. Six contrôles techniques CI réussis sur `e18dfb9`, octets applicatifs identiques. La CI a nécessité l’adaptation du test de l’accueil éditorial et l’actualisation du manifeste de revue, sans modification du paquet.

Recette Chrome hébergée : agenda chargé, préférences conservées après rechargement, réinitialisation, aperçu PDF des deux canaux choisis et copie sans publication. Miniature d’événement et accès au détail vérifiés sur une fixture locale ensuite archivée ; aucun événement publié en préproduction pour ce contrôle. L’actualité « Haloween » existante reste Validée, catégorie Vie du club ; elle n’apparaît donc pas dans les affiches d’événements publiés.

Correction d’hébergement complémentaire : les dossiers publics du module, `css` et `images`, étaient en mode 0700, empêchant le chargement des scripts, styles et logo. Modes antérieurs consignés en privé, trois dossiers corrigés en 0755 ; fichiers privés et Basic conservés, accès anonyme toujours 401. Les neuf fichiers, contenus, comptes, catalogue et paramètres ont été contrôlés avant/après installation. Production exclue et inchangée.

La campagne humaine reste différée, la PR 24 en brouillon sans accord de merge. Suite : gestion des événements et récurrences, choix de la source unique, personnalisation globale. [Preuves](../data/communications-agenda-verification.json) ; [guide](communication-bureau.md) ; [opérateur et retour arrière](../workflows/communications-agenda-preproduction/README.md).

## Recette Chrome du 19 octobre 2026 — état courant

### Accueil raccordé au formulaire

Bouton « Formulaire d’adhésion — test » ajouté au rendu de la rubrique « Et si on jouait ensemble ? » lorsque le catalogue fictif est publié. Les textes éditoriaux, liens de contact et Ten’Up sont conservés. Parcours Chrome vérifié en préproduction : accueil, « Jouer au club », nouveau bouton, formulaire `/adherer` saison 2026-2027 et ses formules. Aucun dépôt ni courriel supplémentaire pour ce contrôle. Le défaut de lien indiqué dans les paragraphes historiques est résolu.

Seul `SeasonCatalogue.php` livré, empreinte `e02ceb27c45a10f290972191bf9da9c2a19002bbb3d873a9bf2d1741b78431b8`, ancienne source comparée avant écriture, sauvegarde privée `SeasonCatalogue.before-home-link-20261019.php`, syntaxe PHP hébergée et cache vérifiés. Treize contrôles locaux de catalogue réussis avec la configuration PHP du projet, dont conservation des textes, absence de doublon et catalogue non publié. Capture privée `.local/accueil-lien-adhesion-20261019.png`. Production inchangée. Création du mot de passe et connexion du compte invité restent à vérifier par le responsable.

### Correction de l’expéditeur et réception vérifiée

**Réception finale confirmée le 19 octobre :** capture fournie puis confirmation explicite « Mail reçu » du responsable pour l’invitation issue de `support@tclongages.fr`. La réception des deux types de courriel est donc établie : notification du dossier 3 observée dans Gmail par l’agent, troisième invitation attestée par capture et confirmation humaine. Aucun lien personnel conservé dans la documentation. Création du mot de passe et connexion du compte invité encore à vérifier par le responsable ; raccordement du formulaire à l’accueil encore à corriger. Les constats de non-réception ci-dessous restent historiques.

**Complément après non-réception signalée :** la trace cPanel révèle que la deuxième invitation utilisait encore l’ancien expéditeur Gmail, contrairement au formulaire 3. L’annonce initiale d’un expéditeur corrigé pour cette invitation était prématurée. Configuration enregistrée `system.site.mail` désormais alignée sur la surcharge, dans la base préproduction vérifiée uniquement ; sauvegarde privée du champ dans `system-site-mail-before-20261019.json`, puis cache reconstruit. Composition sans envoi des deux types de message contrôlée. Après respect du délai anti-doublon (aucune désactivation), troisième invitation acceptée ; cPanel confirme réellement `support@tclongages.fr` vers le destinataire autorisé à 21:32:21, heure affichée par l’hébergeur. Réception personnelle encore non confirmée. Preuve privée : `.local/recette-invitation-trace-expediteur-20261019.png`. Cinq messages au total : trois invitations, deux notifications. Aucun nouvel essai à multiplier sans diagnostic supplémentaire. La cause de la différence transitoire entre CLI et parcours web n’est pas établie.

cPanel indique que les deux premiers messages ont été acceptés par le relais `smtp.jabatus.fr` ; cette trace ne prouve pas leur réception par Gmail. L’expéditeur effectif était une adresse Gmail envoyée depuis l’hébergement. Le domaine `tclongages.fr` présente SPF et DKIM valides dans cPanel et la boîte `support@tclongages.fr` existe. L’expéditeur de la préproduction uniquement est désormais cette boîte, par surcharge de `system.site.mail` dans son fichier privé runtime. Sauvegarde `runtime-settings.before-sender-20261019.php`, contrôle de la base préproduction, syntaxe PHP et valeur effective vérifiés ; cache reconstruit. Aucun DNS, compte de messagerie, rôle, protection ni production modifié.

Après correction, une nouvelle invitation et une nouvelle demande fictive ont été envoyées depuis les formulaires. **Notification du dossier 3 reçue dans la boîte de réception Gmail du club le 19 octobre à 21 h 27**, constatée par l’agent. Nouvelle invitation acceptée par le transport, réception encore demandée au responsable. Le premier dossier 2 et ses traces sont conservés. Le rejet ou filtrage exact des premiers messages n’est pas établi ; ne pas présenter cette cause comme prouvée. Preuve privée : `.local/recette-mail-recu-20261019.png`. Le lien depuis l’accueil reste à corriger. Les paragraphes suivants décrivent le premier essai.

Session Chrome authentifiée utilisable. Catalogue fictif 2026-2027 publié depuis le Bureau, sans modifier les prix ni définir les créneaux ; notice explicitement fictive ajoutée. Formulaire `/adherer` testé avec données fictives : dossier 2 visible dans le Bureau, formule Adhésion Adulte loisirs et tarif au dépôt 70 € conservés. Notification au destinataire privé du club et invitation du compte existant UID 3 acceptées par le serveur d’envoi, une tentative chacune ; ne pas les renvoyer sans nécessité.

Réception des deux courriels non confirmée : recherche ciblée dans la boîte du club, y compris indésirables, sans résultat au premier contrôle ; le responsable indique le 19 octobre que l’invitation n’est pas encore reçue. Diagnostic de livraison en attente de connexion cPanel dans Chrome : page de connexion ouverte, aucune session hébergeur authentifiée disponible. Aucun renvoi effectué ; consulter le suivi de livraison avant un nouvel essai. La page d’accueil actuelle ne montre pas le lien du formulaire, même après actualisation : accès direct fonctionnel mais raccordement accueil à corriger après rapprochement avec la version éditoriale hébergée. La session Bureau authentifiée est vérifiée ; aucune nouvelle connexion OAuth complète ni création du mot de passe n’a été exécutée par l’agent.

Captures privées dans `.local/recette-formulaire-preprod-20261019.png` et `.local/recette-invitation-preprod-20261019.png`. Aucune production, aucune publication sociale, aucun changement de rôle ou mot de passe. Le signalement du navigateur intégré ne sera pas envoyé conformément à la décision du responsable. Les diagnostics antérieurs ci-dessous restent historiques.

## Reprise du 19 octobre 2026 — en cours

Candidat applicatif `8b7ab6f78a8d9de6b3e8b60484cd126b8ddf59e8` livré uniquement en préproduction le 19 octobre à 15:16 UTC : aperçus PDF/images et saisie, tarifs bureau/mobile, invitations et notifications. Paquet `95540db934f92867d01a7301e428bb91712bf0adc1a0a67a439896c225f4f48f`, 244 fichiers vérifiés. Core 11.4.8 conservé. Six contrôles techniques GitHub réussis sur le HEAD documentaire `4a465a3`; PR #24 en brouillon, aucune fusion.

Sauvegarde fraîche `20261019T145855Z` restaurée dans `restore-13dh72qp` : 27 459 fichiers, 62 tables et 5 fichiers d’identité comparés ; 20 contrôles de courriel réussis dans la copie privée. Données actives préservées. Les paramètres privés autorisent les deux destinataires confirmés ; seuls les courriels invitation/adhésion utilisent PhpMail, sans accusé au demandeur. Aucun courriel réel envoyé.

**Blocages de recette constatés :** cPanel connecté, mais le navigateur de l’agent refuse toujours Basic Auth malgré la connexion déclarée par le responsable. Le catalogue hébergé est encore non publié : le lien de demande depuis l’accueil n’est pas actif. L’association TC du compte existant est cohérente (UID 3), sans changement de rôle. Prochaine action : depuis un Bureau authentifié, publier le catalogue fictif, envoyer l’invitation du compte existant, puis déposer une demande fictive et vérifier les boîtes destinataires. Ne pas annoncer réception ou parcours hébergé validés avant ces essais.

Diagnostic complémentaire du 19 octobre : session du responsable dans Chrome/Edge externe, non raccordé aux outils. HTTPS répond bien `401` avec challenge Basic ; échec du navigateur intégré avant Drupal, cause exacte non déterminée. Une connexion via l’extension officielle Chrome/Edge est une voie de reprise documentée. Aucune modification de protection ni de production.

Le responsable demande de conserver le navigateur intégré. Ressources de l’application Windows `26.1013.4894.0` examinées en lecture seule : aucun mécanisme de réparation de l’authentification exploitable trouvé. L’outil refuse également l’observation de l’onglet d’erreur (protocole non autorisé) ; aucun contournement effectué. Prochain essai limité : fermeture complète puis relance de l’application par le responsable, pour tester un éventuel état d’authentification transitoire. Cette piste n’est pas une correction prouvée. Aucun cache, cookie, mot de passe ou fichier applicatif modifié.

Après redémarrage déclaré par le responsable, nouvel échec d’accès : l’outil refuse l’observation du document d’erreur. Le responsable signale une fenêtre fugitive, possiblement d’authentification ; son contenu n’a pas été observé par l’agent. Le redémarrage n’a donc pas résolu le blocage. Ne pas conclure à un mot de passe erroné ; identifier la fenêtre avant une nouvelle action. Aucune mutation distante.

Vérification de mise à jour du 19 octobre via l’outil de l’application : `up_to_date`, version déclarée `26.1013.40934`, build `20770`, canal installé `prod`. Aucune mise à jour éligible proposée par le mécanisme configuré ; cela ne prouve pas l’absence d’une version sur un autre canal ni d’un correctif futur. Le numéro du paquet Windows inspecté précédemment (`26.1013.4894.0`) est une métadonnée distincte. Signalement préparé dans la conversation, non envoyé : fenêtre Basic fugitive signalée par le responsable, erreur `ERR_INVALID_AUTH_CREDENTIALS`, accès externe fonctionnel selon lui, redémarrage sans résolution. Demande maintenue : navigateur intégré et protection inchangée. Aucune installation, relance, mutation distante ou émission de courriel lors de ce contrôle.

Décision suivante du responsable : ne pas envoyer le signalement et utiliser sa session Chrome existante. Inventaire des outils : Chrome non raccordé, seuls navigateur intégré et MCP Apps disponibles. Raccordement de Chrome requis avant reprise de la recette ; autorisations catalogue fictif, invitation et formulaire préproduction conservées. Protection Basic maintenue, production exclue.

[Preuves du lot](../data/v2-apercus-recette-verification.json). [Opérateurs de qualification et livraison](../workflows/v2-apercus-preproduction/README.md). Le suivi canonique conserve les quatre phases ; la mise en production reste exclue.


## Clôture de la journée du 18 octobre — consignée le 19 octobre 2026

Session close sur demande du responsable. Aucune reprise automatique. Production inchangée durant ce lot ; aucune nouvelle fusion, livraison ou émission de courriel à la clôture. Les sections suivantes conservent les états historiques.

| Phase | État à la clôture |
| --- | --- |
| Cadrage | Catalogue/formulaire en recette fictive ; destinataires réels confirmés pour les essais de courriel. Textes et créneaux à compléter. Évolutions et personnalisation de l’agenda enregistrées, non implémentées. |
| Développement local | Invitations et notifications au commit `3fa8a54`, suivi au HEAD `3cb7fea355965a421051f33109ddaf294e3d7390`, PR #24 en brouillon. Correctifs d’affichage et aperçus supplémentaires non commités, à consolider. |
| Préproduction | Catalogue/formulaire puis import PDF/canaux installés le 18 octobre. Le dernier complément livré reste la source `2c137071e2e43ec38a70885ae34ca5a51b5b989c`. Invitations, notifications et nouveaux aperçus ne sont pas installés. |
| Mise en production | Exclue ; aucune modification de production dans cette session. |

### Preuves et limites

- [Reçu PDF/canaux](../data/communication-pdf-canaux-verification.json) : livraison du 18 octobre à 23 h 23, paquet `72cafd03fed7966c5a70aa8a5884502dd69600b6bb906161d026365a3c1b82b8`.
- Invitations/courriels : 20 contrôles locaux, 11 contrôles HTTP d’invitation et 18 de création du mot de passe réussis avant les derniers changements d’interface. Les six contrôles techniques CI du HEAD `3cb7fea` ont réussi ; les confirmations humaines restent ouvertes.
- Copie privée hébergée : sauvegarde `20261018T213810Z`, restauration `restore-xl4337ej`, 62 tables comparées et 20 contrôles du service courriel réussis. Aucun courriel réel émis. Le correctif du test filtre les messages métier du module pour exclure un message technique de statut de compte capturé pendant la recette.
- Paquet courriel préparé `9ed8b191ad447772695a4e2bea75c17494e0a6f96b1a4b7100155f6bf08154ec`, jamais appliqué. Ce paquet ne comprend pas les derniers correctifs locaux : ne pas le livrer comme candidat final.
- Tarifs : colonnes visibles sur bureau et présentation en cartes sur mobile, vérifiées visuellement en local. Communications : première page du PDF et aperçu pendant la saisie vérifiés localement avec l’affiche fournie. Captures privées dans `.local/` : `tarifs-corriges-desktop.png`, `tarifs-corriges-mobile.png`, `apercu-affiche-pdf.png`, `communication-apercu-saisie.png`.
- Accès Bureau/Basic du site non disponible dans le navigateur de l’agent ; l’utilisateur indique y accéder. Aucun parcours navigateur hébergé ni réception dans les boîtes réelles n’est confirmé. Le compte destinataire existe déjà et est actif ; son association OAuth doit encore être contrôlée.

### Reprise précise

1. Conserver le checkout `feat/v2-recette` et tous ses changements non commités ; lire le [suivi canonique](../../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json), itération `tcl-v2-recette-20261018`.
2. Terminer les contrôles des aperçus PDF/images, des droits et du formulaire tarifaire ; consolider un nouveau candidat avec ses dépendances PDF.js, manifeste et CI. Ne pas extrapoler les résultats de `3cb7fea` aux fichiers non commités.
3. Requalifier une sauvegarde/restauration fraîche et le nouveau paquet avant installation uniquement en préproduction. Préserver les données saisies depuis la dernière sauvegarde.
4. Configurer les destinataires confirmés dans les paramètres privés ; vérifier l’association OAuth du compte existant, puis tester l’invitation et le formulaire depuis l’accueil avec données fictives et les adresses autorisées. Distinguer acceptation par le transport et réception effective. Aucun accusé au demandeur prévu.
5. Reprendre ensuite l’agenda et sa personnalisation ; aucune diffusion Facebook/WhatsApp effectuée.

La clôture met à jour les sources Markdown et JSON, sans relancer de tests applicatifs ni de build. Les vues dérivées du cockpit ont été générées avant ces dernières informations : les régénérer à la reprise. Aucun accord humain n’est ajouté par cette clôture.


## Reprise du complément PDF et canaux

Demande complémentaire : importer un PDF existant et choisir Site internet/Facebook/WhatsApp. Implémentation dans `feat/v2-recette`, sans génération de PDF. L’affiche Halloween fournie reste en brouillon privé local, sans validation, publication ni envoi. Contrôles et état de livraison dans [le reçu dédié](../data/communication-pdf-canaux-verification.json). Complément livré en préproduction avec migration `11003` ; reçu et récupération vérifiés. Production exclue.


## Reprise courante — V2 en préproduction, 18 octobre

Le paquet limité V2 est installé le 18 octobre à 22 h 47 (Paris) : catalogue, formulaire fictif et bandeau, 38 fichiers vérifiés (12 différents). Core hébergé 11.4.8 et OAuth 6.1.1 conservés ; local 11.4.10. Sauvegarde/restauration privée vérifiée (27 449 fichiers, 62 tables, démarrage Drupal), puis 11 contrôles sur cette copie réussis. Les sept pages, trois comptes et le dossier existant sont conservés. Basic répond 401, aucune production ni collecte réelle modifiée. Catalogue encore non publié : connexion Bureau/OAuth, publication de recette puis parcours navigateur à réaliser. [Guide et reprise](recette-v2.md), [preuve de livraison](../data/v2-preproduction-livraison.json), [contrat](../api/inscription-recette.md). Source livrée `e7ec6af`, paquet `b51144894adf…`. Suivi canonique dans `../../competitions-capitaines/docs/suivi-chantier/suivi-chantier.json`. Les sections suivantes sont historiques.

## Reprise courante - action manuelle de production

Branche `feat/manual-production-action`, rapprochee de `main` apres fusion
de la PR #23. Les conflits documentaires sont resolus en conservant les deux
lots ; le manifeste est regenere pour ce nouveau candidat de preparation.
Lire le [guide](../workflows/production-manuelle.md) et le
[recu local](../data/production-action-verification.json). Le workflow et le
controleur sont prepares pour revue, catalogue inactif ; l'adaptateur heberge
reste a implementer et qualifier. Aucun deploiement ni fusion dans ce lot.

Le suivi courant demeure l'iteration `tcl-action-production-manuelle-20261017`
du checkout voisin `competitions-capitaines`. Ne pas remplacer ce registre
par l'ancien suivi herite de `main`. La V1 et la preproduction sont conservees.
La suite depend de la revue humaine, puis d'un lot de raccordement et recette
isolee autorise separement. Les sections suivantes sont historiques.
## Correctif tarifaire integre depuis main, lot distinct

Branche `fix/tarifs-adultes` : [cours adultes 150/170](correctif-tarifs-adultes.md),
adhésion compétition 100 inchangée, correctif préparé et non déployé.
La note décrit les contrôles et les prérequis de livraison. Le suivi canonique
reste dans `../competitions-capitaines`, itération `tcl-tarifs-adultes-20261017`.
Les observations suivantes sont historiques, pas l'état courant de ce correctif.

## Reprise courante — Bureau OAuth

V1 confirmée par le responsable ; aucun retour de bêta-testeur signalé.
Le nouveau lot rend le Bureau natif utilisable, ajoute **Bureau** au menu
et impose OAuth côté serveur en plus des droits Drupal. Le cockpit reste reporté.

Checkout actif : `.worktrees/bureau-oauth`, branche `feat/bureau-preproduction`
créée depuis `origin/main` à `8bd9b8f9c8625a5811e35eab4a8d43da7c4a1306`.
La PR #19 est vérifiée fusionnée, sans différence de contenu avec son dernier
head. Les 50 fichiers du paquet natif immuable concordent avec le merge.
Le [guide](bureau-oauth.md), le [workflow](../workflows/bureau-oauth-preproduction.md)
et le [reçu](../data/bureau-oauth-verification.json#hostingPreparation) portent
la préparation ; l'itération `tcl-v2-bureau-oauth` du
[suivi canonique](suivi-chantier/suivi-chantier.json) conserve l'historique.

Le fournisseur est **TC uniquement**, Drupal 11.4.8 / Simple OAuth 6.1.1.
Aucun compte ni secret AVEREO n'est repris. La sauvegarde fraîche du
12 octobre `20261012T202257Z` a été restaurée en copie privée : fichiers,
50 schémas et toutes les lignes actives comparés, 440 tables de récupérations
antérieures conservées. Installation Composer, rôle Admin, inscription fictive
et préservation des sept pages ont réussi sur cette copie uniquement.

Après accord explicite de création de l'accès, le même paquet PR #19 est
installé sur la préproduction. `Admin_TC_Longages` (uid 2), rôle `tcl_admin`,
clés RSA/client/association TC sont vérifiés. Le raccordement de la configuration
a nécessité une reprise de l'opérateur : ajout temporaire du seul droit
d'écriture propriétaire sur `web/sites/default`, puis retour vérifié à 0555.
Compte, clés et client existants ont été conservés, sans reprovisionnement.

Les trois sondes serveur renvoient racine 401, token invalide 400 et userinfo
sans Bearer 403. Les cinq sondes extérieures restent en 401 avec challenge Basic.
Les sept contenus/révisions, agenda Mois, photo et choix de contact sont conservés.
Le runtime V1 et ses transports restent inchangés. Aucun message de l'agent,
aucun compte AVEREO ni modification de production.

## Reprise du 13 octobre — première saisie corrigée

Le responsable demande de reprendre OAuth et de finaliser la préproduction.
La [clôture précédente](suivi-chantier/archives/2026-10-12-cloture-journee.md)
reste conservée. Le contrôle serveur de 06:55 UTC retrouve uid 2 sans mot de
passe, OAuth configuré et le même contenu des sept pages.

Les huit contrôles HTTP locaux de première saisie réussissent avec un compte
fictif supprimé après essai. L'opérateur corrigé est sauvegardé puis installé
dans le dossier privé. Son opération `initialise` vérifie le compte existant,
le client, les clés et le reçu du paquet avant de régénérer uniquement le lien
natif, sans `destination`. La régénération réussit à 07:07 UTC ; compte,
clés, client et contenus restent conservés, fichier en 0600.
Le [reçu canonique](../data/bureau-oauth-verification.json#passwordSetupResumption)
porte les empreintes, contrôles et limites.

Le premier fichier corrigé téléchargé est `initialisation-admin (1).html` ; son lien
secret n'est ni lu ni publié par l'agent. Le responsable doit choisir une
adresse distincte de celle du club, saisir/confirmer/enregistrer son mot de
passe dans Drupal, puis permettre la recette réelle Menu → Bureau et des
opérations métier. L'ancien fichier téléchargé reste à écarter ; régénérer un
lien ne prétend pas révoquer tous les liens antérieurs avant leur expiration
ou une modification du compte. Le contrôle du lien déjà utilisé réussit localement.

Connexion OAuth réelle, recette métier et décision humaine restent en attente.
Le responsable signale ensuite un refus avec `TCL_admin`. Le contrôle en lecture
seule confirme que cet identifiant est présent dans la protection Basic, comme
`TCL_visiteur` ; aucun mot de passe n'est vérifié ou modifié. Les captures reçues
ensuite situent le refus dans Drupal, après la protection Basic.
La [distinction des deux accès](bureau-oauth.md#première-connexion--deux-accès-distincts)
reste applicable à la saisie personnelle pour `Admin_TC_Longages`.

Le raccourci `/login` exige un navigateur non connecté. Le même 403 est reproduit
localement avec une session du compte cible ou d'un autre compte. Le générateur
est donc corrigé pour l'entrée native `user_pass_reset_url($admin)`, sans suffixe
ni destination : gestion des sessions existantes, confirmation puis formulaire
avec son token. Les 18 contrôles du parcours passent. L'essai déclaré en fenêtre privée
reste refusé. Le diagnostic hébergé de 07:54 UTC retrouve six refus avec uid 2
connecté et l'exigence de visiteur anonyme. Le compte est actif, sans mot de passe,
Basic Auth Drupal est désactivé et les sept pages sont inchangées. Le diagnostic
impose un nouveau lien par l'entrée native complète. Aucun contenu de session
ni secret n'est lu.
La capture contient un lien privé : sa valeur n'est pas reportée dans Git et
aucune copie de la capture n'est ajoutée au dépôt.

Le rejeu local compte 20 contrôles de jetons, 66 HTTP Bureau/OAuth, 45 du
fournisseur TC et 8 de première saisie réussis ; 38 contrôles framework passent.
Le contrôle documentaire porte sur 12 Markdown modifiés, 438 liens locaux,
leurs métadonnées et le socle obligatoire. La recette globale n'est pas déclarée
verte : le contrôle contact retrouve 10 captures fictives accumulées au lieu des
5 attendues, puis s'arrête avant l'éditeur. Le rejeu isolé de ce contrôle est
refusé par la politique d'exécution avant démarrage. La base et les captures
existantes restent conservées ; aucun courriel réel, serveurs locaux arrêtés.
La [CI sur environnement neuf](https://github.com/jpdandin44/TC_Longages/actions/runs/38366270275)
réussit ensuite ses cinq contrôles, dont la recette complète incluant Contact et
éditeur. Elle lève cette réserve de qualification sans supprimer les captures locales.

## Résultat courant — première connexion OAuth réelle

Le correctif d'outillage est dans la [PR #20](https://github.com/jpdandin44/TC_Longages/pull/20),
en brouillon, sans fusion ni cases humaines cochées. Son opérateur privé est
installé à 08:13 UTC après contrôle des empreintes, sauvegarde et restauration
vérifiées. Le paquet Drupal installé reste exactement celui de la PR #19.
La régénération du lien complet réussit à 08:14 UTC ; le fichier de remplacement
`initialisation-admin (2).html` est remis au responsable, sans lecture du lien.

Le responsable confirme ensuite « Mot de passe enregistré ». Le contrôle serveur
de 08:18 UTC confirme sa présence pour uid 2, sans lecture du mot de passe.
Il confirme ensuite l'accueil Bureau après Menu → Bureau. À 08:22 UTC, un
comptage serveur retrouve une session récente de uid 2 avec la clé de preuve
OAuth ; aucun contenu de session, cookie, identifiant de session ou jeton n'est
affiché. Le [reçu de recette réelle](../data/bureau-oauth-verification.json#hostedUserRecipe)
distingue cette déclaration humaine, cet indicateur serveur et les tests automatisés.

Le responsable confirme ensuite que l'essai du dossier fictif OAuth RECETTE,
sa modification et l'ouverture de Communication et Gestion des comptes fonctionnent.
Le contrôle de 08:28 UTC retrouve exactement le dossier fictif attendu, en
révision 2, créé puis modifié par uid 2, avec le commentaire prévu. Il reste
dans la préproduction privée. Aucun dossier réel, publication ni nouveau compte
demandé. Les observations navigateur restent celles du responsable ; le contrôle
agent corrobore seulement les indicateurs de connexion et la persistance ciblée.

Les quatre contrôles extérieurs sans identifiant retrouvent un 401 Basic sur
l'accueil, le Bureau, GET userinfo et POST token. Un premier lot rapproché a
reçu des 429 après l'accueil ; les contrôles espacés réussissent jusqu'à 08:39 UTC,
sans changement de protection. Les sept pages conservent la même empreinte.

Prochaine action : revue du bilan et de la PR #20, toujours en brouillon et non
fusionnée. Les confirmations fonctionnelles ne cochent pas les validations de
phase. Les essais de publication, création de comptes, mobile et nouvelle
déconnexion/reconnexion ne sont pas inclus dans cette recette hébergée initiale.

La production n'est pas autorisée pour ce lot. Le cockpit reste reporté comme
interface ; son suivi JSON est maintenu.

Les observations suivantes sont historiques.

## Observation du 8 octobre — Gmail, messagerie habituelle et formulaire

Le responsable accepte les trois moyens de contact et demande de raccorder
tous les boutons des différentes pages. L'ajustement est réalisé sur la branche
de la PR #18, encore ouverte. Le formulaire reste prioritaire ; les liens Gmail
et messagerie habituelle sont insérés après le rendu des textes édités.
Toutes les actions de contact des sept pages Drupal rejoignent `/contact.html`.

Le bloc `contactMailChoices` du [reçu](../data/framework-revue-verification.json)
et l'itération `tcl-v1-contact-choices` du suivi existant portent la qualification
locale et la prochaine étape. Ce candidat ne remplace pas encore la version
installée : les reçus d'installation du 7 octobre et leurs réserves de connexion
personnelle/réception Gmail restent conservés. Aucun envoi réel, merge ou passage
en production n'est réalisé. Le cockpit reste reporté.

Les observations suivantes sont historiques.

## Observation précédente — édition visible et contact du club

Le responsable confirme les améliorations installées après fusion de #17 et
la réception des signalements. Il demande les commandes de modification sur
les pages Drupal et l'envoi du contact à `tclongages@gmail.com`.
Le contrôle actuel constate la préproduction hors maintenance, non-indexée,
avec support transport qualifié et droits éditoriaux du compte administrateur.

La branche `fix/edition-contact-preprod` ajoute la barre d'édition native et
le contact intégré. Les tests locaux couvrent le vrai formulaire, les erreurs,
les doublons et la sauvegarde des textes. La cible est uniquement la
préproduction protégée, après sauvegarde restaurée et contrôles du candidat.
Le bloc `editionContactPreproduction` du [reçu framework](../data/framework-revue-verification.json)
conserve les empreintes, la PR, l’installation réelle, les limites et la prochaine
action ; l’itération `tcl-v1-edition-contact` du suivi canonique lui est raccordée.
Compte réellement utilisé et réception Gmail restent à qualifier.
Le [workflow](../workflows/edition-contact-preproduction.md) et le
[reçu local](../data/edition-contact-verification.json) conservent la qualification
initiale, avant fixation du candidat ; ils ne certifient pas son installation.
Les observations suivantes sont historiques ; le cockpit reste reporté.

## Observation précédente — conflits de #17 et calendrier Mois

`main` contient désormais #16 (`c0fa31b4c886695843668d3bf336230df6c1dfc2`).
La demande actuelle conserve la PR #17, ouverte en brouillon, et corrige ses
onze conflits documentaires et de suivi par merge sur sa branche existante.
La vue Mois et la construction de recette privée sont préparées localement.
Le passage en préproduction est demandé ; aucune installation de ce nouveau
candidat n'est encore attestée. Le support existe déjà sur l'hébergement :
ne pas réinitialiser son transport ni les contenus Drupal. Prochaine action :
contrôler et publier la branche rapprochée, puis qualifier le rapprochement
éditorial et le retour arrière avant la mise à jour hébergée.

Les rubriques suivantes sont les observations antérieures conservées.


## Reprise prioritaire — site, agenda et édition Drupal

La PR #15 est fusionnée le 7 octobre ; la [PR #17](https://github.com/jpdandin44/TC_Longages/pull/17)
réunit désormais sa branche avec `main`, conserve l’agenda et la photo, puis
requalifie le candidat. Le résultat à examiner est [le site local](http://127.0.0.1:4184/index.html),
avec [Calendrier](http://127.0.0.1:4184/calendrier.html). Le responsable reporte
le cockpit et envisage de créer son dépôt séparé. URL de ce dépôt : TBD.
Aucune migration n’est lancée ; la PR #16 et ses preuves restent conservées.

Le défaut d’édition signalé concerne `preprod.tclongages.fr`. Le correctif
`PublicPageText` de ce lot est vérifié avec la vraie photo volumineuse et par
la CI native. Le contrôle navigateur hébergé s’arrête à l’authentification
d’accès, avant Drupal ; une modification enregistrée en préproduction n’a
pas été démontrée dans cette reprise. Prochaine action : terminer cette
qualification avec l’accès Drupal, puis préparer le candidat hébergé autorisé.
Les sections suivantes conservent les observations historiques datées.

## Historique — PR #15, résolution des conflits du 7 octobre

## PR #16 — recette et base actualisées au 7 octobre

Le responsable signale le cockpit de recette inaccessible et interroge son
réalignement. Le port 4181 était arrêté et la branche n’était pas actualisée ;
#15 est maintenant fusionnée dans `main`. Quatre conflits documentaires/de
manifeste sont réunis depuis la tête publiée de #16 dans une copie isolée
`pr16-recette`, sans forçage ni perte des observations locales antérieures.
Le [guide de pilotage](piloter-versions.md) donne le lancement et les limites.
Les contrôles et le candidat sont dans le bloc `cockpitRecipeRecovery` du
[reçu framework](../data/framework-revue-verification.json).

La modification des sept contenus Drupal provient de #15 et reste présente.
Le responsable précise que sa difficulté d’édition concerne la préproduction.
Le navigateur de l’agent est refusé par la protection HTTP ; il ne permet pas
de conclure sur les droits ou le formulaire du compte Drupal du responsable.
Le parcours est [Contenu → Pages du club](modifier-textes-drupal.md) après
connexion Drupal, puis Modifier et Enregistrer. La recette locale et sa CI
restent distinctes d’un contrôle du compte hébergé. Aucun accord humain,
compte, transport, contenu hébergé ou déploiement n’est modifié.

Prochaine action : tester le cockpit sur 4181 et préciser le résultat rencontré
sur Pages du club en préproduction pour qualifier l’accès Drupal.

## PR #15 — résolution des conflits du 7 octobre

La demande du responsable autorise la correction des conflits de #15. Après
fusion humaine de #14, `main` vaut `89489f3` et la comparaison compte quinze
fichiers en conflit. La réunion conserve le formulaire à quatre éléments,
l’édition native des textes, les apports V2, les deux itérations et les
contrôles support/MariaDB. Le [dossier de résolution](resolution-conflits-pr15.md)
et le [reçu support](../data/support-v1-verification.json) portent le candidat
et les vérifications, distincts des preuves de préproduction déjà enregistrées.

Le checkout `.worktrees/support-v1` contient des travaux non commis de bêta-test,
de courriels et de suivi. Ils sont conservés. La correction utilise la copie
isolée `.worktrees/pr15-conflicts` ; ne pas mettre à jour aveuglément le checkout
opérationnel avec `git pull`. Le suivi opérationnel continue dans `support-v1`
et référence la version corrigée ; le suivi de la PR est son instantané versionné.
La PR #17 demeure un lot agenda/photo distinct dépendant de la branche support.

Prochaine action : examiner #15 sur son nouveau candidat et ses contrôles.
La correction de branche ne constitue ni fusion de PR ni livraison hébergée.

## Historique — Bureau mobile et recette MariaDB

Le responsable demande la reprise de V2 en local. Drupal est relancé sur
`127.0.0.1:4182` avec sa base conservée. La PR #14 est toujours le candidat
de l'itération ; les accords V1 ne couvrent aucun hébergement V2.

Le débordement des formulaires Bureau sur mobile est corrigé. La
[recette complémentaire](recette-bureau-mysql.md) et son
[reçu](../data/reprise-bureau-verification.json) portent les neuf observations
à 390/1 280 pixels, les 26 contrôles MariaDB et la restauration comparée des
39 tables, schémas et données compris. La fixture MariaDB est autonome, locale
et fictive ; son serveur est arrêté après contrôle. Docker a d'abord échoué
au démarrage ; le responsable le redémarre, puis le contrôle confirme son moteur
Linux 29.7.2 disponible. Aucun reset ni changement de ses conteneurs n'est effectué.

Le job CI natif PHP 8.3/MariaDB est ajouté à la PR ; son état courant est
enregistré dans l'itération canonique. Les tests locaux ne constituent pas
une réussite GitHub ni une validation humaine. L'agenda public n'est pas encore
créé, réponse explicite du responsable ; l'affichage reste désactivé.

La première exécution GitHub réussit pour la recette native et la sécurité
de livraison, puis révèle le test CI resté sur l'ancienne liste de jobs.
Le contrat de test est corrigé, y compris l'action PHP épinglée et ses
limites. Les quatre confirmations personnelles de la PR restent décochées ;
le contrôle de politique attend leur déclaration par le responsable.

**Suite :** examiner le Bureau et la Communication de la PR #14 ; après réussite
des contrôles et accord propre à V2, préparer la livraison et la mise à jour
en préproduction sous maintenance. Qualifier les nouvelles routes/images
Apache, la reprise de la base hébergée et son retour arrière sur le même paquet.
Les champs/consentements du processus d'inscription restent à examiner avant
collecte réelle. Aucun effet distant n'est déduit de cette reprise.

## Clôture précédente du 6 octobre — historique conservé

La session est arrêtée le 6 octobre. Drupal local (4182), le suivi (4181) et
le prototype Communication (4174) sont arrêtés ; leurs ports ne sont plus en
écoute. Le verrou du suivi a été retiré seulement après vérification de l'arrêt
de son propriétaire. La base SQLite, les dossiers fictifs, l'exemple de
communication et les brouillons du prototype sont conservés. Les sessions
Drupal de préproduction et du Bureau local ont été déconnectées ; la
préproduction affiche « Site en maintenance ». Aucun réglage de production
n'est modifié lors de cette clôture.

La [PR #14](https://github.com/jpdandin44/TC_Longages/pull/14) reste ouverte en
brouillon sur `aedc85d`. Au relevé de clôture, `delivery-safety` réussit ;
`technical-ci` et `policy` sont annulés. Leur cause n'est pas déterminée par ce
relevé et ils ne sont pas relancés pendant la clôture. Les contrôles locaux
précédents restent consignés : 50 HTTP Communication, 37 HTTP Bureau,
151 Node, 49 Python dont 4 ignorés, 16 fichiers PHP et 37 contrôles framework.
Les quatre aperçus Communication ont été examinés à 1 280 et 390 pixels.

**Reprise exacte :** relire ce point et `developmentIterations` du
[suivi canonique](suivi-chantier/suivi-chantier.json), relancer les services
selon les guides [Drupal](installation-drupal.md) et
[suivi](installation-framework.md), puis se reconnecter au Bureau. Examiner
les dossiers et la communication de la PR #14 et relancer ses contrôles annulés.
Avant recette hébergée V2, qualifier update MySQL, images, routes Apache,
sauvegarde/restauration et recette mobile complète des dossiers. L'ID et
l'accès de l'agenda public dédié restent attendus.

Cette clôture ne valide aucune phase, ne fusionne pas la PR et ne livre pas V2.
Aucune reprise automatique n'est programmée. Le candidat produit testé
`a605a2e` et ses preuves restent conservés ; les modifications documentaires de
clôture sont locales, à intégrer au prochain candidat avant sa remise en revue.

## Candidat conservé du 5 octobre — Bureau et nouveaux adhérents

Après confirmation de l'objectif V1 atteint, le responsable demande V2 en local,
puis donne priorité à l'enregistrement des nouveaux adhérents par le Bureau.
Checkout `.worktrees/livraison-fiable`, branche `feat/v2-comptes-bureau-local`.
Le [site local](http://127.0.0.1:4182/) utilise Drupal 11.4.8 et une base SQLite
neuve avec dossiers/comptes fictifs et courriels neutralisés. Le Bureau permet
création/modification Adultes/Mineurs, recherche et état du dossier ; les équipes
restent désactivées. Le [guide](comptes-et-bureau.md), le
[reçu](../data/bureau-local-verification.json) et `developmentIterations` du
suivi canonique portent ce lot distinct. La session navigateur locale Bureau
est déconnectée à la clôture. Les mots de passe de recette restent dans `.local/`.

La communication du prototype est intégrée au Bureau V2, liens externes compris :
brouillons/affiches en base, quatre aperçus, validation puis publication distincte
sur le site local, retrait, archives et restauration. Le [guide](communication-bureau.md)
et le [reçu](../data/communication-local-verification.json) portent les contrôles.
La PR #14 reste le candidat de cette itération. Les anciens brouillons de 4174
ne sont pas importés automatiquement. Qualifier MySQL, les nouvelles routes
Apache et les images avant une livraison hébergée.

Les [sources FFT](../api/fft.md) sont examinées : Ten’Up fournit des rencontres
publiques ; ADOC demande une reconnexion. Aucune intégration ou donnée réelle
d'adhérent n'est importée. Prochaine action : examiner le candidat local et ses
champs, puis qualifier la mise à jour/MySQL avant une recette hébergée V2.
La qualification mobile du nouveau Bureau reste à faire. Les accords de V1
conservent leur portée ; aucun déploiement V2 ni validation humaine nouvelle.

## État courant — V1 livrée, maintenance activée par le responsable

**Observation d'exploitation ultérieure du 5 octobre.** Le responsable indique
avoir activé la maintenance. Les contrôles anonymes de `http://tclongages.fr/`,
`https://tclongages.fr/`, `https://www.tclongages.fr/` et d'une requête avec
paramètre neuf rendent tous 503 et « Site en maintenance », avec `no-store`.
La préproduction rend aussi 503. Les administrateurs connectés peuvent continuer
à voir le site ; le prototype 4174 est indépendant. Aucune modification de
maintenance ou déconnexion distante n'est effectuée par l'agent. Le reçu
initial d'ouverture ci-dessous reste historique et inchangé.

Le responsable autorise explicitement la mise en production de V1 dans la
conversation (`TCL-PROD-EXECUTION-20261005`). Le même candidat site `49b4ef7`,
ZIP `14c270431af12397…`, est ouvert sur [tclongages.fr](https://tclongages.fr/)
le 5 octobre à 18:14:45 UTC. Les sept pages anonymes, HTTPS, `www`, les cinq
refus de chemins privés, l'administration et l'inscription anonymes, ainsi que
la non-indexation de la connexion et de la préproduction sont contrôlés.
La configuration Apache d'hébergement est identifiée séparément du ZIP ; les
26 686 autres fichiers du manifeste sont revérifiés après retrait des diagnostics.
Le retour réel à `/home2/daje5127/public_html` a rendu la page d'attente attendue
sur les deux noms avant la reconnexion du Drupal. Les copies et sauvegardes
privées sont conservées. Le site a aussi été examiné sur bureau et à 390 pixels.

Les paramètres privés et la base dédiée de production restent séparés de la
préproduction, qui conserve sa maintenance. Aucun compte Bureau/Capitaine,
transport de contact ni raccordement Calendar/Forms n'est activé. Ces réserves
V1 sont conservées dans la [note de livraison](note-de-livraison.md).
Le [reçu technique](../data/industrialisation-verification.json#publication)
et le suivi canonique portent la livraison réelle. Les critères, commentaires
et statuts de validation humaine des phases ne sont pas modifiés par cette
exécution ; l'accord de production est enregistré avec sa provenance dans le chat.

Checkout opératoire : `.worktrees/livraison-fiable`, branche
`fix/revue-pr-candidate`, HEAD `ab89256`, PR #13 fusionnée **au constat de livraison V1**. Les documents,
configuration d'hébergement et reçus de cette publication sont conservés dans
ce checkout. Les anciens checkouts et le lot de comptes restent intacts.
La reprise V2 est décrite en tête de ce document. Les mises à jour doivent
préserver la base de production. Les exigences de versioning, cockpit, GitHub Actions et
notes de version restent dans le backlog après V1 ; aucune synchronisation
automatique ni nouvelle permission GitHub n'a été installée.

Les sections suivantes conservent les constats antérieurs à cette ouverture.

La demande complémentaire du 7 octobre remplace la photo d’accueil par
[Image_terrain_OK.png](../Images_Photos/Image_terrain_OK.png), fournie dans le dossier de la photo originale.
Le PNG de 1448 × 1086 pixels est intégré sans modification ; l’original JPEG
reste conservé. Les contrôles d’empreinte des pages sont adaptés à cette source.


## Agenda partagé — démarrage local du 7 octobre

Demande directe : intégrer localement l’iframe Google fournie. Checkout dédié
`.worktrees/agenda-local`, branche `feat/v1-agenda-local`, depuis `6696f39`.
L’état de préproduction et les travaux documentaires récents ont été lus dans
`support-v1` ; leurs décisions, données et sources ne sont pas remplacées.
Le suivi canonique de ce cockpit demeure dans `support-v1`, avec ajout du seul lot
`tcl-v1-agenda-local`. Le suivi de ce checkout conserve la photographie Git de
départ des autres lots et reçoit seulement une projection dérivée du lot agenda.
La PR du lot, son candidat et sa dernière observation figurent dans ce suivi
et dans le reçu. Sa base `feat/v1-signalement-support` correspond à la PR #15
encore ouverte ; après sa fusion, remettre la PR agenda sur `main` et vérifier
de nouveau le candidat si le contenu change.

La première CI de la PR #17 a réussi les contrôles généraux mais échoué dans
l’installation Drupal : la nouvelle photo dépassait la limite de recherche
du contenu principal. L’extraction et le rendu de `PublicPageText` ont été
corrigés pour les grandes images intégrées. Le reçu conserve cette première
observation et les contrôles du candidat corrigé ; ne pas réutiliser le succès
technique de la première version comme preuve du nouveau candidat.

`npm.cmd run agenda` ouvre l’aperçu sur
[Calendrier local](http://127.0.0.1:4184/calendrier.html).
Le [guide](../api/google-calendar.md) et le [reçu](../data/agenda-local-verification.json)
portent le contenu et les résultats du lot. Aucune installation Drupal ni écriture
hébergée n’est incluse. Prochaine action : revue locale, droits Google puis
préparation du raccordement Drupal si demandé. Aucun accord de livraison n’est ajouté.


## Dernière demande du 6 octobre — formulaire à quatre éléments

Le responsable demande seulement le type, la description, le mail pour une
réponse et la confirmation de transmission au club. Le mail reste facultatif ;
type, description et confirmation restent obligatoires. L'objet est attribué
par le serveur et la page d'origine conservée automatiquement. Aucun schéma
de stockage, ticket existant ou contrôle d'accès n'est remplacé.

Cette simplification reste dans le lot support/édition de la PR #15 et dans
la recette privée déjà autorisée sur `preprod.tclongages.fr`. Le
[reçu courant](../data/support-v1-verification.json) et l'itération canonique
portent le nouveau candidat, ses contrôles et son état réel d'installation.
Les validations historiques et la V2 locale restent conservées. La définition
directe des quatre champs remplace leur ancien statut de proposition ; les
mentions de collecte et règles de conservation restent à compléter.

## Raccordement du cockpit et recette V1.1 — 6 octobre

Le correctif support/édition est installé sous maintenance en préproduction.
Le reçu opérationnel du checkout `support-v1` atteste les empreintes, la sauvegarde
restaurée avant application, les sept pages éditables et la réception du message
fictif dans la boîte support. La modification par le formulaire Drupal, son
affichage et sa restauration ont été vérifiés, avec révisions conservées.
Les courriels des formulaires de recette restent capturés. Aucun correctif
de production ni validation personnelle de phase n'est exécuté par l'agent.

Le code du cockpit évolue dans `.worktrees/cockpit-iterations`, branche
`feat/cockpit-iterations`. Sa configuration privée raccorde les sources
`support-v1` et `livraison-fiable` ; l'historique et le brouillon précédent du
suivi 4181 sont sauvegardés avant remplacement de son seul processus vérifié.
Les runtimes Drupal 4182/4183 et les données/brouillons V2 sont conservés.
Le [guide](piloter-versions.md) précise la procédure de reprise et les limites.

Prochaine action : recette personnelle support/édition, examen des PR et des
critères. La stratégie de bêta-test demeure absente, réserve avant production.
Le besoin **Nos partenaires** V1.1 est inscrit comme prévu ; noms, logos et liens
restent à fournir. Le pilotage des Actions et les notes de version restent futurs.


## Nouvelle demande du 6 octobre — édition et préproduction

Le responsable accepte l'apparence locale du correctif, demande de vérifier
la légende « photo fournie par le club », souhaite l'édition simple des textes
dans Drupal et demande le dépôt de la nouvelle version en préproduction.
Cet accord porte sur le candidat support/édition, la cible TC existante et
sa recette privée ; il n'autorise ni merge, ni production, ni ouverture anonyme.

Le raccordement éditorial préparé dans `feat/drupal-comptes-edition` est repris
et complété dans `.worktrees/support-v1` : sept contenus natifs, titre,
présentation, rubriques, questions, légendes, révisions. L'aller-retour réel
du formulaire conserve l'affichage initial et échappe le HTML saisi.
Le [guide d'édition](modifier-textes-drupal.md) donne le parcours.
Le [reçu courant](../data/support-v1-verification.json) et l'itération
`tcl-v1-signalements-support` du suivi portent les empreintes, résultats,
accord de préproduction, sauvegarde/restauration et progression réelle.
Le travail V2 sur 4182 et les décisions historiques sont préservés.

Le premier contrôle distant de restauration a détecté les instructions de
verrouillage de table présentes dans l'export MariaDB. Leur cible de récupération
est maintenant préfixée comme celles des tables et insertions, avec un test de
non-régression dédié. Cette tentative n'a pas appliqué le correctif aux fichiers
actifs ; ses sauvegardes et copies privées restent conservées. Les reçus courants
permettent de distinguer cette tentative du paquet corrigé et de sa recette.

## Reprise du 6 octobre — support V1

Constats du premier candidat local, antérieurs à la demande de dépôt ci-dessus.

Le responsable demande un correctif V1 de signalement pendant le développement
V2. Le checkout dédié est `.worktrees/support-v1`, branche
`feat/v1-signalement-support`, issu de `origin/main` à `b4f8a53593b84c4f4a4c7610fa58d0ef8e90de30`.
Le checkout V2 et ses observations non commises sont conservés.

La boîte `support@tclongages.fr` est créée personnellement et sa présence
confirmée dans cPanel. Aucun secret n'est consigné. Bouton, formulaire proposé
et suivi privé fonctionnent sur [la V1 locale](http://127.0.0.1:4183/), avec
une base propre et des courriels capturés. Aucun correctif n'est installé sur
les domaines hébergés. Le [reçu](../data/support-v1-verification.json) est
l'autorité des résultats et du candidat ; le [guide](signalements-support.md)
décrit les commandes et limites.

Prochaine action : retrouver `docs/strategie-beta-test.md` ou son contenu,
aligner les champs et mentions, puis recetter le candidat en préproduction
avant toute demande de publication. L'emplacement de cette source a été
demandé au responsable et reste inconnu. Les validations humaines V1 et
les brouillons V2 ne sont pas réutilisés pour approuver ce correctif.


## Dernière correction du suivi — trois décisions et garde PR

La PR #12 est fusionnée le 5 octobre à 15:23:37 UTC sur `76198ee`. La branche
opérationnelle `fix/revue-pr-candidate` porte la correction suivante : les trois
décisions et la garde de fusion exacte, absentes de cette PR déjà fusionnée. Le responsable
retire le démarrage puis l’autorisation des phases suivantes : Revue, Valider et
Demander des corrections constituent le parcours actuel. La fusion exacte de la
PR candidate est désormais un prérequis vérifié par le serveur, avec une nouvelle
lecture GitHub lors de la validation. Les critères obligatoires, le commentaire
et la confirmation personnelle sont conservés.

Les deux décisions humaines du 5 octobre à 14:55 et 14:57 UTC ont été enregistrées
alors que la PR #12 restait ouverte en brouillon. Sa fusion ultérieure ne
transforme pas ces acceptations en preuve de revue de la nouvelle correction.
Elles restent intactes dans le
suivi ; le moteur les signale historiques à requalifier. Aucune nouvelle validation
n’est créée par la correction. Les résultats de tests et le candidat qualifié sont
rattachés au registre canonique et au reçu de revue. Le site installé, ses secrets
et sa base sont inchangés. Le raccordement officiel sous maintenance attend toujours
l’accord sur le lot déjà présenté ; l’ouverture publique reste une décision distincte.

## Reprise du 5 octobre : V1 publique prioritaire

Le responsable demande l'effort restant et une livraison reproductible, puis
reporte explicitement les comptes Bureau/Capitaine et droits par équipe en V2
pour publier le site existant aujourd'hui. La demande relance le travail sur
le site ; elle ne transforme pas l'arrêt précédent en validation de phase.

Checkout de ce lot : `.worktrees/livraison-fiable`, branche
`feat/livraison-fiable`, depuis `main` `a288f83`. Le dernier suivi, les décisions
et la clôture sont repris ; les anciens checkouts et leurs modifications
restent conservés. Seule cette copie du suivi a un écrivain actif pour le lot.
La correction UI non publiée reste sur `fix/cockpit-validation-directe`,
l'édition locale sur `feat/drupal-comptes-edition` ; aucun de ces lots ne
conditionne la V1 publique ou n'est ajouté au paquet installé.

**Constats rafraîchis :** PR #8, #9 et #10 fusionnées ; paquet `49b4ef7`,
ZIP `14c270…`, manifeste `a6b9ab…`, 26 687 fichiers revérifiés contre le reçu.
Préproduction française : accueil anonyme 503, connexion 200, non-indexation ;
site visible en session authentifiée. cPanel connecté au compte principal TC,
racine officielle `public_html` modifiable. Le certificat officiel autosigné
est remplacé après accord par un certificat gratuit : HTTPS du domaine et
de `www` vérifiés le 5 octobre à 08:28 UTC. La racine actuelle est conservée.
Terminal cPanel, PHP, MySQL/mysqldump, uapi et rsync sont disponibles ; aucun
nouvel accès SSH n'est créé. Aucun environnement GitHub ni secret/variable
de dépôt TC n'est configuré. Les [constats datés](../data/industrialisation-verification.json)
gardent les limites, sans déduire une livraison des workflows.

**Travail réalisé :** constructeur protégé contre l'écrasement d'un paquet,
contrôle du reçu, tests Linux ajoutés dans les PR, sauvegarde privée
intégralement relue et 26 819 fichiers restaurés en copie privée. Le lot de
préparation de production est autorisé (`TCL-PROD-PREP-20261005`) : nouvelle
base `daje5127_tclprod`, même ZIP préparé et revérifié dans
`tcl-production/releases/14c270431af12397/drupal`. Code et paramètres restent
propriétaires, hors domaine. L'utilisateur SQL est créé personnellement et
ses dix droits sont appliqués à la seule base `daje5127_tclprod`, après accord
`TCL-PROD-SQL-20261005`. La saisie privée est contrôlée avec succès le
5 octobre à 11:25 UTC : authentification, base vide et dix droits vérifiés.
L'encodage `utf8mb4_unicode_ci` est confirmé avant import. La restauration
réussit à 11:26 UTC : 43 tables, connexion SQL et démarrage des copies
restaurée et de production vérifiés. Maintenance, français, courriels
neutralisés, inscription libre et cron désactivés, non-indexation confirmés.
Les paramètres restent privés ; aucune valeur de secret n'est affichée.
Les 141 tests Node passent. Sur 49 tests Python, 45 passent et quatre
contrôles POSIX sont ignorés sous Windows. La syntaxe des trois fragments PHP
du runtime, du préflight et du contrôle Drupal de production est vérifiée.
Le reçu hébergé de restauration est dans
`/home2/daje5127/tcl-production/private/production-restore-receipt.json`.
L'ancienne racine et sa copie restaurée correspondent à la sauvegarde ;
les droits privés sont contrôlés. Les fichiers publics restent en 0600/0700 :
permissions Apache et HTTP de cette nouvelle racine non qualifiés.
Aucune bascule ni ouverture de production.

La [PR #11](https://github.com/jpdandin44/TC_Longages/pull/11) est fusionnée
le 5 octobre à 14 h 19, heure de Paris, sur
`1aa1581c5d36818424da5956c59d29dc0d15cb9b`. Ses quatre confirmations de revue
sont cochées dans GitHub par l'utilisateur ; aucune case n'est cochée par
l'agent. La CI technique et de livraison a réussi sur le candidat examiné et
sur `main` après fusion. L'arbre fusionné concorde avec celui de la PR
qualifiée. Le candidat du site reste le ZIP `14c270…` issu de `49b4ef7`.
La racine officielle `public_html` est relue dans cPanel après ce merge.
L'accord de raccordement sous maintenance reste en attente, distinct du merge
et de l'ouverture publique. Le suivi canonique conserve les décisions et les
états de phase ; le merge ne les valide pas automatiquement.
Les reçus de la PR fusionnée conservent leur provenance. La remise en
cohérence locale du dossier de revue qualifie séparément les documents
actualisés et rattache Cadrage et Développement local au même manifeste.
Elle conserve les décisions, les brouillons et les critères obligatoires ;
aucune validation humaine ni autorisation d'hébergement n'est créée.
Les résultats et la version exacte figurent dans
`developmentWorkflow.localReviewQualification` du suivi canonique.
Le responsable demande ensuite une validation en un clic et le retrait du
démarrage séparé. Les critères cochés, le commentaire et la confirmation
personnelle restent requis ; l'autorisation de la phase locale suivante la
passe directement en cours. Cette simplification ne crée aucune décision sur
le vrai suivi et ne déclenche aucune opération d'hébergement.

**Suite exacte :** recueillir l'accord sur le lot réversible de qualification
de la racine officielle décrit dans le parcours V1. Rendre les seuls fichiers
publics lisibles par Apache, raccorder le domaine à Drupal sous maintenance,
vérifier HTTPS/PHP/protections, puis éprouver le retour à `public_html`.
L'acceptation de la V1 avec ses réserves et l'ouverture restent des décisions
humaines distinctes. Ne pas relancer l'outil de restauration sur cette base
désormais remplie ; il refuse une seconde tentative.
Le [parcours existant](parcours-mise-en-ligne.md) et `developmentWorkflow.deliveryPlan`
portent les résultats attendus et l'estimation restante de 1 h 30 à 3 heures
avec marge de correction, hors attentes humaines ou techniques externes.
Les liens Google reçus restent non activés : partage Calendar non qualifié,
Forms non destiné aux répondants, noms/catégories des équipes non fournis.

Les sections ci-dessous conservent la clôture et les observations du 4 octobre.

## Session du 4 octobre close à la demande du responsable

Le responsable demande l’arrêt puis la clôture de cette journée, après environ
neuf heures signalées sans atteindre la mise en production. L’objectif est
**non atteint et mis en pause**. Les sections suivantes conservent les constats
de travail ; elles ne décrivent plus une session active. Aucune opération de
développement ou d’hébergement n’est poursuivie après cette demande, hors
conservation des brouillons, fermeture et documentation de clôture.

- **État livré :** Drupal en préproduction sous maintenance, sept pages du
  club et administration française ; SQL opérationnel. Le domaine officiel
  conserve sa page d’attente. Aucune bascule ou ouverture de production.
- **Travail conservé :** branche locale `fix/cockpit-validation-directe`,
  commits `3435c53` et `d84bbff`. Trente-huit tests locaux réussis pour la
  correction du suivi. Cette branche n’est pas poussée, aucune nouvelle PR
  n’est créée. La dernière observation navigateur affichait encore l’ancienne
  interface : la correction n’est donc **pas déclarée vérifiée dans l’écran**.
  Les brouillons personnels sont conservés en privé, sans confirmation active.
- **Édition du site :** travail séparé dans `.worktrees/drupal-comptes-edition`,
  branche `feat/drupal-comptes-edition`, HEAD `9b1a0d2`. Drupal 11.4.8 installé
  sur une base SQLite locale isolée le 4 octobre à 21:26:52 UTC. Cette
  installation ne prouve pas les parcours d’édition, droits et révisions.
  Six fichiers de scaffold modifiés par Composer restent à examiner ; aucun
  transfert de ce nouvel éditeur sur le serveur.
- **Claude :** application inaccessible dans le connecteur disponible ;
  version web ouverte sur sa page de connexion, sans consultation réalisée.
  L’onglet Claude a été fermé à la clôture.
- **Fermeture :** serveur du suivi sur 4181 arrêté après conservation de la
  saisie ; aperçu du site sur 4180 identifié comme TC Longages puis arrêté.
  Aucun service n’écoute sur 4173, 4174, 4175, 4180, 4181 et 4182 au contrôle
  de clôture ; base locale conservée, aucun serveur Drupal local démarré. cPanel
  a affiché « Vous vous êtes déconnecté. » et ses onglets de travail ont été
  fermés. Les onglets du site et du suivi sont conservés pour la reprise ;
  la session Drupal n’a pas été déconnectée ni requalifiée à la clôture.

**Blocages et reste à faire, dans l’ordre de reprise :**

1. Finaliser et recetter l’édition des pages et les comptes/droits utiles au
   club, puis préparer la PR fonctionnelle et le candidat testable en
   préproduction. Le travail restant ne doit pas se concentrer sur le suivi.
2. Renseigner les liens Calendar/Forms et les équipes à partir des informations
   du responsable ; ces données restent absentes. Leur périmètre de livraison
   doit être explicite, sans inventer de liens ou d’effectifs.
3. Sauvegarder le Drupal et la base réellement installés, vérifier une
   restauration et répéter le retour arrière. Qualifier la racine et le mode
   de bascule du domaine officiel ; la sauvegarde initiale de la page d’attente
   ne remplace pas cette qualification.
4. Présenter la version finale, sa recette, la cible et le retour arrière pour
   l’accord de production applicable, puis livrer et contrôler le site. Les
   accords de configuration et merges antérieurs gardent leur portée.

À la prochaine reprise, lire cette section avant toute relance. Requalifier
le candidat après les modifications documentaires de clôture : le manifeste
`3435c53` conserve sa provenance mais ne couvre pas ces nouvelles sources.
La correction du suivi reste un lot local secondaire à vérifier et à publier,
sans fabriquer de validation de phase. Aucun redémarrage automatique n’est
programmé ; la reprise attend une demande du responsable.

## Correction actuelle du bouton de validation

La PR #10 est fusionnée le 4 octobre à 21:27:49 UTC sur
`a288f835e98f3cfea1977b1eff59a4d80da322b1`. Le responsable a repris puis
soumis le cadrage à revue dans le suivi. Le blocage observé est distinct du
merge : quatre cases cochées dans le navigateur, mais zéro critère enregistré.
Le moteur exigeait cet enregistrement séparé avant d’activer la validation.

La correction locale permet à **Valider cette phase** de conserver atomiquement
les cases et l’accord personnel. **Enregistrer les critères** reste disponible
pour une revue partielle. Les contrôles de version, justificatifs, révision,
origine et confirmation restent obligatoires. Les brouillons des phases sont
conservés lors du rechargement ; leur confirmation personnelle est retirée.
La correction ne valide aucune phase et ne change aucun droit d’hébergement.
La [procédure de revue](installation-framework.md#préparer-une-revue) est mise
à jour. La recette de l’édition Drupal continue dans une base SQLite locale
isolée ; ce lot distinct n’est pas encore transféré en préproduction.


## Reprise actuelle — préproduction Drupal installée

Le [suivi canonique](suivi-chantier/suivi-chantier.json) présente quatre phases
selon la règle commune, avec les huit anciennes phases et décisions conservées.
La PR #8 est fusionnée sur `3fb41457e2c402b85de1806dbdf85c77e62e53ed`.
Ce merge ne vaut ni validation de phase, ni autorisation de production.
La PR #9 est ensuite fusionnée le 4 octobre à 20:57:41 UTC sur
`d9b9d9f79ce798371f859280e68c3f61b01f5052`, avec les quatre cases humaines
cochées personnellement. La phase 1 du suivi reste en cours : le cadrage
possède encore la décision du 29 septembre, liée à l’ancien dossier. La reprise
de ce cadrage reste une action humaine ; aucun nouveau clic ni accord de phase
n’est déduit du merge. Les justificatifs techniques sont remis en cohérence
avant cette reprise, sans effacer les douze cases de brouillon de phase 1.

Le lot de première installation déjà autorisé est **réalisé** sur le compte
principal TC : `preprod.tclongages.fr`, base `daje5127_tclpreprod`, utilisateur
`daje5127_tcl`, racine `/home2/daje5127/tcl-preproduction/drupal/web`.
Même ZIP source `49b4ef7` / `14c270…` et outils `b462f6f` / `80c111…`.
La tentative `first-install-svfc2tg9` est désormais `installed_maintenance` ;
aucune réextraction ni suppression de base n’a été nécessaire.

Le refus SQL 1045 est **résolu** après synchronisation personnelle du mot de
passe et du fichier enregistré à 19:57:33 UTC. Connexion réussie et zéro table
constatée avant écriture. À 19:59:37 UTC, DNS/HTTPS/PHP HTTP 8.3.33 et les
18 extensions sont requalifiés sur la cible réelle, avec huit chemins fermés
et refus Apache restauré avant installation. Drupal 11.4.8 est installé à
20:00:15 UTC sur MariaDB 11.4.13. Un processus neuf confirme maintenance,
courriels neutralisés, inscription réservée à l’administration et cron arrêté.

La [connexion HTTPS](https://preprod.tclongages.fr/user/login) répond 200 avec
formulaire natif et non-indexation. Dix-neuf chemins sont contrôlés anonymement :
les neuf routes publiques restent en 503 non indexables ; écrans administratifs,
installation, mise à jour et paramètres privés restent inaccessibles au visiteur.
Le [reçu d’installation](../data/framework-revue-verification.json#primaryAccountFirstInstallation)
porte les résultats et leurs limites. La page d’attente officielle conserve
son empreinte ; la Lune et ses ressources restent conservées. Aucun secret
n’est lu dans le chat, aucun compte métier ni ouverture de production n’est créé.

Le responsable s’est connecté personnellement et a constaté le fonctionnement
de Drupal. Le [site de recette](https://preprod.tclongages.fr/) est ouvert dans
cette session : accueil et six pages secondaires parcourus, titres présents,
aucun débordement horizontal au format observé. Le lien de validation de la
PR #9 ouvre désormais ce site, avec les quatre libellés et coches conservés ;
le modèle des futures PR reprend cette destination. Le suivi reste personnel
au pilotage. Ce constat ne constitue pas une approbation du site ou de la PR.

L’administration est maintenant en français : 10 480 traductions importées,
français par défaut, détection sans préfixe d’URL. La page de maintenance affiche
les libellés français et la case reste cochée. À 20:55 UTC, contrôle HTTPS
anonyme réussi : accueil 503, connexion française 200, non-indexation dans les
deux cas. Le [guide Drupal](installation-drupal.md#français-et-accès-au-site-de-recette)
porte les réglages reproductibles et leurs limites.

**Prochaine action exacte :** tester le site dans cette préproduction, puis
connexion/déconnexion, comptes et permissions natifs. L’édition des
sept pages et les rôles Bureau/Capitaine restent absents du ZIP livré. Le
travail d’édition est isolé dans `.worktrees/drupal-comptes-edition`, commit
de travail `cde2180`, sans qualification ni transfert de ce nouveau code.
Préparer sa PR fonctionnelle et sa recette, puis sauvegarde Drupal/SQL,
restauration et répétition du retour arrière avant production. Aucun accord
de production ou d’ouverture n’est ajouté.

Le brouillon de phase 1 conserve douze cases cochées ; l’enregistrement d’une
note ne les a pas enregistrées comme critères. La validation historique du
cadrage ne couvre plus le périmètre élargi. Une correction locale permet la
reprise humaine de revue, conserve l’ancienne décision et nomme la dépendance.
Le [guide du cockpit](installation-framework.md) décrit cette action ; sa version,
ses tests et sa PR figurent dans le [reçu de correction](../data/framework-revue-verification.json#reviewValidationCorrection).
La première installation utilise son accord distinct déjà acquis, sans
déduire une validation du cockpit ni la laisser bloquer cet accord.

Les sections suivantes conservent les constats historiques de la reprise.

## Réserve de recette — comptes, droits et édition

Le responsable demande une PR permettant de tester et approuver ce périmètre.
Le [dossier de préproduction](suivi-chantier/04-phase.md)
distingue les droits SQL appliqués, l’administrateur prévu à l’installation,
les rôles Bureau/Capitaine absents et l’édition des pages absente. Les PR
existantes sont rattachées à la phase 2 dans le suivi canonique ; elles ne
couvrent pas un éditeur ni les rôles métier. Le libellé obligatoire de la case
reste inchangé, sans validation ni report automatique.

TBD — réponse du responsable sur le périmètre à livrer avant production :
administrateur et édition des pages, ajout des rôles métier, ou report explicite
à une autre itération. Préparer une PR fonctionnelle et sa recette selon cette
réponse. L’ancien ZIP de site et le paquet des outils restent identifiés
séparément ; un futur changement applicatif exige son propre candidat.

## Lot du compte principal terminé — 4 octobre

La PR #7 est fusionnée personnellement sur `f6785bc` après ses quatre
confirmations. L’accord cPanel « J'autorise ce lot sur le compte principal TC »
est reçu séparément. Le [reçu](../data/framework-revue-verification.json#hostingPrimaryConfiguration)
porte les résultats du lot : PHP partagé CLI/HTTP 8.3.33 et 18 extensions,
racine dédiée fermée, domaine/DNS public/HTTPS reconnu, base vide UTF-8/InnoDB.
Le responsable a soumis le mot de passe SQL puis autorisé les dix droits ;
leur persistance est relue sur la seule base prévue. La page d’attente
officielle conserve son empreinte. La Lune et ses ressources sont conservées.

GET et HEAD de la sonde sans secret répondent 200, POST 405 ; cinq chemins
restent en 403. Le refus Apache est rétabli et les fichiers de test sont
déplacés en privé. Le contrôle TLS strict externe, avec résolution sur
l’adresse qualifiée, confirme les 403 finaux. Le DNS public et la résolution
normale depuis le serveur réussissent ; le résolveur ordinaire Windows du
poste reste non qualifié, sans cause précise établie. SQL est inspecté par
SSO phpMyAdmin, pas par le nouvel utilisateur applicatif.

L’adaptateur est adapté au compte principal et à la correspondance des sept
champs du reçu/profil. Quatorze tests de frontières passent sur Linux local,
sans exécution de l’adaptateur sur le Python 3.6.8 hébergé ni installation SQL.
L’ancien outil de Lune, les reçus et le ZIP Drupal reçu restent conservés.

**Suite exacte :** examiner le candidat d’outillage et autoriser le [lot de
première installation](installation-drupal.md#lot-de-premiere-installation-sur-le-compte-principal).
Le ZIP source `49b4ef7` est inchangé ; les paramètres privés sont fournis
personnellement sur le serveur après préparation fermée. Qualifier PDO,
installer Drupal sous maintenance, faire la recette, sauvegarde/restauration
et retour arrière, puis préparer production et ouverture séparées. Aucune
validation de phase, aucun transfert Drupal ni accord de production ou
d’ouverture n’est déduit du lot terminé. La session demeure active.

## Compte principal connecté et lot préparé — constat avant accord

La connexion personnelle au compte principal TC est confirmée. L’inventaire
authentifié, la comparaison de la racine actuelle et la restauration privée
de l’export de fichiers du 4 octobre sont terminés. Le [plan de configuration](preparer-lune-tc.md#lot-du-compte-principal)
porte les effets exacts : PHP 8.3 partagé et modules, nouveau dossier fermé,
`preprod.tclongages.fr`/DNS/HTTPS et SQL dédié. Le formulaire est préparé sans
soumission. Le mot de passe SQL sera créé et soumis personnellement ; les
droits seront présentés sur la seule base dédiée au moment de leur attribution.

**Prochaine action :** obtenir l’accord de ce lot sur le compte principal,
puis appliquer et qualifier les réglages autorisés. L’ancien accord sur la
lune reste limité à cette lune. Ses ressources sont conservées ; aucun
nettoyage ni déplacement automatique. Adapter et retester ensuite l’outil
avant de présenter le transfert Drupal. PHP HTTP et SQL applicatif, recette
hébergée, restauration Drupal, livraison et ouverture restent non qualifiés.
Aucun nouveau appel Claude pour cet inventaire et les contrôles déterministes.

## Diagnostic de déblocage du 4 octobre — constats historiques et choix humain

La comparaison avec AVEREO explique le refus : AVEREO utilise deux dossiers du
même compte pour son domaine et sa préproduction ; TC a préparé sa cible dans
une lune différente du compte du domaine principal. La proposition technique
en `universe.wf` est retirée après vérification de son exclusion de l'émission
Let's Encrypt o2switch. L'ancienne demande d'accord sur cette adresse est
sans objet. La [correction du plan](preparer-lune-tc.md) est la référence unique
des étapes proposées et des limites.

Le responsable retient ensuite « la même organisation que pour AVEREO » :
qualifier le compte principal TC pour `preprod.tclongages.fr`, avec fichiers
et base séparés, puis présenter le lot de configuration de ce compte.
Aucune ressource de la lune n'est supprimée et aucun réglage PHP du compte
principal n'est modifié. L'outil de première installation
actuel reste limité à la lune et devra être adapté et retesté si le compte
principal est retenu.

La lecture de diagnostic constatait la session cPanel refusée et l'absence de
résolution DNS de `preprod.tclongages.fr`. La préproduction AVEREO répond en
HTTPS reconnu, HTTP 401 anonyme ; cela prouve sa protection observable,
sans remplacer une recette complète de ce site. PR #6 fusionnée, PR #7 en
brouillon ; aucune mise en production TC exécutée. Reconnexion personnelle au
compte principal TC nécessaire pour l'inventaire de l'implantation retenue.
L'identifiant de connexion a été corrigé dans l'onglet ; le mot de passe
d'hébergement et la soumission restent personnels, sans valeur enregistrée.

## Reprise initiale du 4 octobre — préparation conservée, constat historique

Le responsable demande de reprendre et finaliser la mise en production avec
Claude, puis confirme sa connexion cPanel. Le compte TC et sa première lune
gratuite sont ouverts ; aucune reprise du domaine officiel ni écriture DNS
n'est exécutée. Le formulaire de l'adresse technique temporaire est préparé
sur la racine isolée déjà protégée, sans soumission. Son accord reste attendu.
Les clôtures précédentes ci-dessous sont historiques ; la session de travail
est reprise et les onglets cPanel sont conservés pour cette décision.

Le terminal existant répond dans la lune : PHP CLI **8.3.33**, `pdo_mysql`
chargé, Python **3.6.8**. L'[adaptateur de première installation](installation-drupal.md)
est préparé localement pour ces runtimes : réemploi du vérificateur partagé,
extraction privée et contrôle exhaustif, conservation de la racine vide,
configuration fournie personnellement, installation fermée puis maintenance
vérifiée dans un processus neuf. Il refuse une cible avec données, la
production et toute suppression automatique de tables. L'exécution
MySQL/Drupal réelle reste non testée.

Douze tests de frontières passent sur Linux local ; les seize tests partagés
et les 137 contrôles du projet passent. La syntaxe de l'auxiliaire PHP est
vérifiée localement. Le mode `plan` a relu le même ZIP reçu : source `49b4ef7`,
26 687 fichiers, empreintes ZIP et manifeste inchangées. Ces résultats ne
prouvent ni PHP HTTP, connexion SQL, installation ou retour arrière sur la lune.
Les preuves finales de revue seront rattachées au candidat figé, séparément du
ZIP de site déjà conservé.

Claude a fourni une revue dans « Drupal installation review », avec Sonnet
5.5 et effort Moyen, un seul échange. Le [prompt de référence](../prompts/revue-premiere-installation-claude.md)
et le [reçu courant](../data/activation-lune-verification.json) en conservent
le périmètre. Captures, compte, chemins et archives restent privés. Aucun
solde ou budget chiffré n'est inventé ; aucun nouvel appel de modèle pour les
tests déterministes. Historique humain, phase 0 validée et libellés obligatoires
de PR sont conservés.

**Suite exacte :** accord sur la cible temporaire ; création et qualification
DNS/HTTPS/PHP HTTP sans données ; présentation du transfert et de
l'installation sous maintenance du ZIP et des outils revus ; saisie personnelle
des paramètres privés ; recette hébergée, sauvegarde/restauration et retour
arrière ; enfin qualification de la cible officielle et décisions de livraison
et d'ouverture. Le formulaire proposé et ses effets n'autorisent aucun
déplacement du domaine officiel. Aucune mise en production n'est déclarée prête
ou réalisée par cette préparation locale.

## Fin de session du 3 octobre — configuration partielle conservée

Le responsable demande de terminer la tâche en cours puis de fermer la journée. Les interventions d’hébergement sont arrêtées. Le formulaire d’adresse temporaire a été quitté sans soumission et sans accord reçu. La lune a affiché « Vous vous êtes déconnecté. » ; les anciennes pages authentifiées des comptes principal et lune demandent désormais une connexion. Les sept onglets de travail sont fermés, l’affichage temporaire du navigateur est réinitialisé et aucun service n’écoute sur les ports du projet 4173, 4174, 4180, 4181 et 4182 au contrôle de clôture. L’aperçu local est arrêté ; le relancer avec `npm.cmd run officiel` depuis ce checkout si une revue des sept pages est souhaitée.

### Acquis conservés

La PR #5 a été fusionnée personnellement. L’Action partagée a construit le candidat Drupal non configuré sur `49b4ef7` ; son ZIP reçu et ses 26 687 fichiers ont été vérifiés. Sauvegardes du compte et copie fraîche de la racine publique sont conservées et restaurées en copie privée. La lune gratuite est active ; PHP 8.3 y est sélectionné, le dossier Composer isolé existe et la règle de fermeture Apache a été relue à l’identique. La base dédiée est vide, sa collation est `utf8mb4_unicode_ci`, et l’utilisateur créé personnellement possède les dix droits autorisés uniquement sur cette base. Le mot de passe n’a pas été saisi ni lu par l’agent.

### Blocages et suite, dans l’ordre

1. **Choisir une adresse utilisable.** cPanel refuse `preprod.tclongages.fr` sur la lune parce que `tclongages.fr` appartient au compte principal. L’adresse `preprod.<adresse-technique-de-la-lune>` est une proposition non créée et non autorisée. À la reprise, choisir cette adresse ou obtenir une solution d’o2switch ; aucun transfert du domaine officiel n’est autorisé par le lot actuel.
2. **Qualifier l’hébergement de cette cible.** Créer le routage et le DNS autorisés, obtenir un certificat HTTPS reconnu sans contournement, puis contrôler le PHP réellement servi, ses extensions et la connexion SQL de l’utilisateur dédié. Le réglage PHP de cPanel et les cases SQL ne prouvent pas encore ces tests applicatifs ; aucun script PHP de sonde n’est transféré.
3. **Préparer la première installation Drupal.** L’adaptateur de première installation manque encore ; `run-delivery.py` n’est pas présent et le modèle de déploiement reste inactif. Préparer une procédure contrôlée pour le même ZIP reçu, puis obtenir l’accord correspondant à son transfert et son installation fermée. Ce transfert est exclu du lot de configuration réalisé aujourd’hui.
4. **Effectuer la recette de préproduction et le retour arrière.** Tester le Drupal réellement hébergé, ses sept pages, maintenance et droits, sur ordinateur et mobile. Éprouver sauvegarde/restauration et retour arrière sur cette cible. La restauration privée actuelle porte sur les fichiers du compte existant et ne constitue pas un retour arrière du futur Drupal.
5. **Préparer puis livrer la production.** Après recette et revue humaines, qualifier la cible officielle et la stratégie de migration, figer l’artefact retenu, présenter les effets et obtenir les accords applicables de livraison et d’ouverture. Livrer en maintenance, vérifier, puis ouvrir sur décision humaine distincte.

La phase 1 reste en cours ; aucune phase n’est validée ou clôturée par cette fin de session. Aucun Drupal distant, déploiement de production ou ouverture publique n’a été exécuté. Aucune reprise automatique n’est programmée. Le reçu d’hébergement et le suivi canonique portent l’état courant ; le reçu technique conserve la publication GitHub et le candidat documentaire sans remplacer les preuves historiques. Aucun nouveau appel Claude n’a été lancé pour les contrôles déterministes et la clôture.

## Reprise du 3 octobre — préparation fusionnée, lune active

Le responsable reprend le projet, se reconnecte à cPanel puis fusionne la [PR #5](https://github.com/jpdandin44/TC_Longages/pull/5) à 22 h 16, heure de Paris. La fusion observée est `49b4ef7b975b8edff4308373732033630582da6b`. Le checkout isolé est conservé ; la branche `feat/qualify-preproduction` part de cette fusion en préservant les documents locaux de clôture antérieurs.

L'[Action de construction](https://github.com/jpdandin44/TC_Longages/actions/runs/37151085511) a réussi sur ce commit. Le ZIP reçu depuis GitHub est propre et non configuré ; ses 26 687 fichiers et leurs empreintes ont été vérifiés par `prepare_delivery.py verify`. Son identité exacte figure dans le [reçu de préparation](../data/actions-mutualisees-verification.json), rubrique `postMergeBuild`. L'archive reçue est conservée hors Git, sans reconstruction à promouvoir comme identique.

Le lien de validation de la PR était une page documentaire GitHub. Il a été corrigé vers [l'aperçu local](http://127.0.0.1:4180/) après lancement et ouverture du site. Tous les libellés obligatoires et les coches présents avant cette correction ont été conservés. Le responsable a ensuite confirmé la case de test et fusionné la PR. L'aperçu couvre les sept pages publiques ; les comptes, les services connectés et la recette du Drupal hébergé restent hors preuve. L’aperçu a été servi sur 4180 pendant la revue, puis arrêté à la clôture demandée.

La sauvegarde a été renouvelée avant l'activation autorisée : trois exports JetBackup du 3 octobre à 08 h 10, heure affichée par le serveur, restaurés en copie privée, soit 78 fichiers et deux liens. La racine publique fraîche a aussi été archivée hors webroot, téléchargée, restaurée et comparée à l'instantané : un fichier et deux dossiers, aucune différence d'empreinte ni fichier masqué supplémentaire. Une première compression a été interrompue par le renouvellement de la session cPanel ; l'absence d'archive a été constatée, puis la reprise dans la session valide et les contrôles ont réussi. Les archives et preuves détaillées restent privées.

Le responsable a créé et soumis personnellement le mot de passe de la première lune gratuite. Son état est confirmé : une active, sept restantes, coût total affiché de 0 € par mois, accès cPanel séparé. La lecture du sous-compte constate PHP natif 8.1, choix PHP 8.3 disponible, aucun domaine supplémentaire, aucune base ni utilisateur SQL. Le [reçu d'hébergement](../data/activation-lune-verification.json) est la source courante, sans réimport du compte actif ni recette DNS/SSL/SQL déduite des copies privées.

**Configuration du 3 octobre :** Le lot de configuration a été autorisé et partiellement réalisé : PHP 8.3 appliqué, racine isolée créée avec fermeture Apache relue, base vide UTF-8 et utilisateur SQL dédié avec dix droits enregistrés. cPanel refuse `preprod.tclongages.fr` dans la lune parce que son domaine parent appartient au compte principal. Aucun DNS ni certificat n’est créé pour ce nom. Une adresse temporaire de la lune est proposée, non soumise ; son accord, DNS, HTTPS reconnu et PHP réellement servi restent à qualifier.

**Prochaine action :** décider de la [cible temporaire préparée](preparer-lune-tc.md), puis qualifier DNS/HTTPS et le runtime. L’adaptateur de première installation et la recette hébergée restent à réaliser. La préproduction ne résout pas lors du contrôle public de reprise ; la validation TLS stricte du domaine officiel échoue. Aucun dépôt Drupal, retour arrière de ce candidat, livraison ou ouverture n'a été exécuté.

Le protocole commun GitHub et cockpit est raccordé au bloc `developmentWorkflow` du [suivi canonique](suivi-chantier/suivi-chantier.json). Sa prochaine action de session a été réalignée ; l'ancien texte reste conservé en observation. Les décisions, revues, commentaires, historique humain et phase 0 validée sont inchangés. Le candidat de livraison réellement construit ne vaut pas acceptation humaine de recette hébergée. Les contrôles de passage restent bloquants tant que leurs prérequis ne sont pas prouvés.

### Bilan documentaire de reprise

Politique documentaire globale appliquée ; socle obligatoire présent. Le contrôle final de cette reprise a examiné 13 documents, 287 liens locaux et cinq fichiers structurés, sans erreur de métadonnées ni lien local absent. Les six anciennes références ont été rendues explicites : archives générées non versionnées et sources externes absentes de ce checkout, sans copie concurrente inventée.

Le mapping du protocole a été vérifié dans le cockpit ; son champ historique `release` est réservé à la livraison et reste nul. Les preuves de préparation sont rattachées à `developmentWorkflow`, sans assouplir le garde-fou du moteur local. Les vues ont été régénérées et les 27 tests du framework ont réussi. Le contrôle compare aussi l'historique humain, les décisions, revues et la phase 0 validée avec l'état précédant le raccordement : aucun changement. Les notes et reçus de reprise restent sur la nouvelle branche ; leurs nouvelles preuves ne remplacent pas les anciennes.

**Limites restantes :** source externe des anciens crédits et spécification reçue non incluses dans ce checkout ; restauration de fichiers privée distincte d'un réimport cPanel/SQL ; choix de la cible, fin de configuration et recette hébergée encore à réaliser. Les contrôles documentaires portent sur la reprise locale, sans déclaration de conformité globale de l'hébergement. Les contrôles de passage en préproduction et production refusent la livraison tant que ces prérequis et leurs accords distincts manquent.

Les sections du 1er octobre et précédentes ci-dessous sont l'historique de clôture ; elles ne décrivent plus l'état courant de la lune.

## Clôture de la journée du 1er octobre — 22 h 26, heure de Paris

Le responsable demande la fin de session pour aujourd'hui. Le travail est arrêté : aucune activation, configuration ou livraison supplémentaire ne sera poursuivie sans nouvelle demande de reprise. L'accord borné de sauvegarde et d'activation gratuite reste consigné dans le [reçu d'hébergement](../data/activation-lune-verification.json), sans devenir un accord de configuration ou d'ouverture.

Le formulaire d'activation non soumis a été annulé. cPanel a affiché **« Vous vous êtes déconnecté. »**, puis l'onglet de travail a été fermé ; la preuve reste privée avec les sauvegardes. Aucun service n'écoute sur les ports locaux du projet 4173, 4174, 4180, 4181 et 4182 au contrôle de clôture. La revue Claude est terminée ; aucune nouvelle consultation de modèle ni automatisation de reprise n'a été lancée pour cette clôture.

La lecture GitHub de clôture confirme la [PR #5](https://github.com/jpdandin44/TC_Longages/pull/5) ouverte en brouillon au commit `fcc0efa27b01b8b33a641c1626a62d437ad31c51`, avec CI technique réussie et politique de PR en échec. Les documents d'hébergement et de clôture restent locaux et non commités ; les contrôles GitHub du commit précédent ne qualifient pas ces ajouts. Aucun commit, push, merge ou validation humaine n'est effectué lors de la clôture. Les modifications antérieures et les sauvegardes privées sont conservées.

**Reprise exacte :** lire d'abord ce point et le reçu d'hébergement ; reconnecter cPanel directement ; vérifier si une activation a été faite entre-temps, ainsi que la fraîcheur des sauvegardes. Si la lune reste inactive, reprendre uniquement le lot gratuit déjà autorisé et laisser au responsable toute saisie et soumission du nouveau mot de passe. Après confirmation de la lune, présenter son lot PHP, racine Composer, sous-domaine, HTTPS, base et accès TC avant toute configuration. Figer ensuite le candidat incluant les documents locaux, régénérer son manifeste et rattacher les contrôles avant une nouvelle remise. Les six références historiques absentes de ce checkout restent à retrouver ou corriger avant publication documentaire.

**Bilan documentaire de clôture : Partiellement à jour.** Politique globale appliquée sans modification de son fichier. Socle obligatoire présent ; 11 documents contrôlés sans erreur de métadonnées, 246 liens locaux examinés et cinq fichiers structurés analysés, plus cohérence des reçus et états de clôture vérifiée. Les six références historiques manquantes restent la limite documentaire ; les nouvelles références de reprise sont accessibles. Les quatre reçus privés de restauration sont conservés. Le contrôle des différences Git n'a signalé aucune erreur ; les notices de conversion de fins de ligne ne sont pas des échecs. Aucun test fonctionnel n'a été relancé pour cette clôture documentaire.

## Sauvegarde autorisée et activation en attente de saisie personnelle

Le responsable a autorisé la sauvegarde et l'activation de la première lune gratuite, avec arrêt devant un nouveau mot de passe, un contrat ou un coût. Le [reçu courant](../data/activation-lune-verification.json) remplace les réserves historiques de sauvegarde pour ce seul périmètre : trois archives JetBackup restaurées en copie privée, 75 fichiers et deux liens vérifiés, puis archive fraîche de toute la racine publique restaurée et comparée. cPanel confirme zéro base et zéro utilisateur SQL. Aucun réimport sur le compte actif n'a été testé ; DNS, certificats et propriétaires POSIX ne sont pas déclarés réinstallés.

Le dialogue d'activation demande un nouveau mot de passe ; aucun identifiant n'a été saisi par l'agent. Il a ensuite été annulé à la clôture, avant déconnexion et fermeture de cPanel. La lune n'est pas confirmée active. À la reprise, vérifier le résultat puis présenter la configuration de ce compte isolé selon le [plan](preparer-lune-tc.md). Aucun changement PHP/DNS/HTTPS, base, installation Drupal ou ouverture n'a été effectué. Les archives et reçus détaillés restent privés, hors dépôt public. Les sections précédentes de reprise sont conservées ci-dessous comme historique daté.

Ce lot ajoute des documents locaux non commités à la branche `feat/release-four-stages`. Le contrôle technique GitHub réussi sur `fcc0efa` précède ces ajouts ; il reste une preuve du candidat précédent. Avant une nouvelle remise ou livraison, figer ces sources et régénérer le manifeste de candidat, puis rattacher les contrôles à ce nouvel état. L'attente de saisie personnelle n'autorise ni merge ni validation de phase.

## Reprise du 1er octobre — Actions partagées

La demande de reprise annule l'arrêt de travail du 30 septembre, sans effacer son état historique ci-dessous. La [préparation de livraison](../workflows/preparer-livraison.md) décrit le composant SSH réutilisable, le constructeur de candidat et les limites de première installation. Le [reçu de préparation](../data/actions-mutualisees-verification.json) conserve les tests et constats avant envoi ; le [reçu de revue](../data/framework-revue-verification.json), rubrique `deliveryPreparation`, porte les résultats GitHub du candidat ensuite figé. Aucun merge, secret, domaine, certificat, base, copie distante ou ouverture n'est exécuté par cette reprise. L'utilisateur a reconnecté cPanel directement. L'[intervention suivante](preparer-lune-tc.md) présente les constats récents et la lune gratuite isolée à activer après accord. Aucun mot de passe n'est transmis dans la conversation.

## Clôture immédiate du 30 septembre

Le responsable demande l'arrêt pour changer de projet en urgence. La session est close : aucun déploiement, DNS, certificat, base, PHP distant ou ouverture n'a été exécuté. La revue Claude est terminée, la sonde locale et son archive sont conservées, les tests sont terminés et aucun service local démarré par cette session ne reste actif. L'onglet cPanel créé pour la connexion a été fermé ; l'état d'authentification après l'intervention du responsable et une déconnexion côté serveur ne sont pas vérifiés. Aucune étape humaine n'est validée par cette clôture.

Les modifications restent locales dans le checkout candidat, sans commit ni push ; les modifications antérieures sont préservées. Reprendre par le [reçu](../data/reprise-deploiement-verification.json), puis le [test de qualification](qualification-preproduction.md). Accès cPanel, racine exacte, sauvegarde restaurable, décisions d'écriture et réserve de crédits restent à qualifier. Le contrôle documentaire a identifié six références historiques absentes du checkout isolé ; vérifier leur emplacement et leur disponibilité avant publication documentaire. Cette réserve n'affecte pas l'exécution locale de la sonde, mais interdit de déclarer tous les liens documentaires accessibles.

## Reprise historique du 30 septembre avec le connecteur Claude

La demande du 30 septembre autorise la reprise de préparation avec Claude et l'optimisation des crédits. Une revue ciblée a été obtenue dans sa conversation « Redéploiement site TC Longages » via Computer Use, avec Sonnet 5.5 et effort Moyen, sans transmission de fichiers privés ou d'accès d'hébergement. Le [guide de qualification](qualification-preproduction.md) transforme cet avis en un test PHP sans secret, fermé par défaut et préparé localement. Le [reçu de reprise](../data/reprise-deploiement-verification.json) porte les résultats exacts et leurs limites ; les faits du 29 septembre ci-dessous restent historiques.

Le checkout isolé `feat/release-four-stages` est conservé au commit `6935a7da4507b16da7cdb6c9aa312a4ba8857e13`, avec modifications non commitées antérieures préservées. Aucune décision du suivi n'est créée ou réécrite. Les nouveaux fichiers et changements restent locaux, sans commit, push ou mise à jour de la PR. L'accès GitHub authentifié renvoie encore 401 ; la lecture publique a permis de vérifier la PR et ses checks.

La page de connexion cPanel est ouverte dans le navigateur intégré, sans session authentifiée. Pour poursuivre : s'y connecter directement, rendre l'hébergement accessible en lecture, vérifier racine et sauvegarde restaurable, puis présenter l'action exacte de qualification avant les écritures. Aucun test PHP distant, transfert, certificat, base SQL, ouverture de préproduction ou production n'a été réalisé. La réserve historique de 800 dépasse le solde de reprise ; la marge de remplacement proposée reste à confirmer.

## Reprise accélérée de la V1

Le responsable a simplifié le pilotage en quatre étapes et choisi `preprod.tclongages.fr`. La première V1 vise les sept pages publiques Drupal ; Bureau, services Google et publications automatiques restent fermés. Le [parcours](parcours-mise-en-ligne.md) et son [registre](../data/parcours-mise-en-ligne.json) sont distincts du suivi historique des huit phases, dont la validation de phase 0 et l'autorisation/démarrage de phase 1 ont été reprises sans réécriture. Le candidat de pages publiques est généré localement ; `npm.cmd run check` a réussi 132 tests sur 132, la syntaxe PHP modifiée a été vérifiée et une instance Drupal isolée a confirmé maintenance 503, connexion 200, sept pages 200 lors de l'ouverture locale et indexation restreinte. La maintenance a été réactivée. Un ZIP candidat local a été inventorié (34 017 entrées) ; son manifeste précise l'empreinte des pages et l'état de la source lors de chaque assemblage. Aucune préproduction, production ou ouverture publique n'a été exécutée. Le sous-domaine de préproduction ne résolvait pas lors de la lecture DNS du 29 septembre ; son état doit être revérifié avant intervention.

La lecture cPanel du 29 septembre a confirmé que `preprod.tclongages.fr` n'est pas créé, que PHP 8.1 est le réglage global et que la séparation par domaine est désactivée dans le sélecteur. Le domaine principal possède un certificat autosigné ; aucune base MySQL ni utilisateur n'est créé. Une sauvegarde JetBackup apparaît, mais sa restauration n'a pas été éprouvée. Le formulaire cPanel permet de proposer une racine documentaire propre au sous-domaine, sans que cette racine ait été créée. Le [guide Drupal](installation-drupal.md) expose les options PHP sans en choisir une avant qualification. La PR de préparation [#5](https://github.com/jpdandin44/TC_Longages/pull/5) reste un brouillon soumis à revue humaine ; elle ne déclenche aucun dépôt distant. Son premier contrôle technique a signalé un ancien manifeste de candidat ; celui-ci a été réaligné et le contrôle technique a ensuite réussi. Le contrôle de politique PR reste rouge tant que les cases réservées à l'utilisateur ne sont pas confirmées.

La relecture cPanel confirme que les trois domaines existants utilisent toujours le PHP 8.1 commun. Le [guide d'installation](installation-drupal.md) compare désormais la préproduction dans le compte actuel avec PHP limité à son dossier, et le sous-compte « lune » isolé proposé par o2switch. Le premier demande un test strict du gestionnaire PHP avant tout dépôt ; le second reporte une migration distincte du domaine principal. La PR #5 recentrée sur le site a un contrôle technique GitHub réussi sur `007a044` ; sa politique de revue reste volontairement en attente des confirmations du responsable. Aucun sous-domaine, certificat, compte, base ou fichier distant n'a été créé pendant cette relecture.

Le responsable a ensuite retenu le sous-compte « lune » dédié au club pour la préproduction. L'application « Mon Univers Web » n'a pas affiché son quota dans le navigateur intégré, même en affichage ordinateur ; la disponibilité d'une lune reste donc à constater dans cPanel. Son éventuelle activation exigera la création d'un accès propre par le responsable, sans transmettre le mot de passe dans cette conversation. Le choix n'est pas une autorisation de transfert du domaine principal ni de publication.

Une recette navigateur supplémentaire a ouvert les sept pages de l'instance Drupal isolée à 390 et 1 280 pixels : chaque page possède son titre principal et aucune n'a présenté de débordement horizontal selon la largeur du document. Les sept fichiers servis correspondent aux empreintes de l'archive locale. Le formulaire de contact reste un aperçu sans `action` d'envoi ni appel `fetch` dans le HTML vérifié. La maintenance Drupal a été rétablie ; un accès anonyme à l'accueil a de nouveau répondu 503. Cette recette technique ne vaut pas validation de la présentation par le responsable du club.

Une lecture supplémentaire de phpMyAdmin indique MariaDB 11.4.13 : la version du serveur SQL convient au minimum exigé par Drupal 11. Le PHP 8.4 de phpMyAdmin ne prouve pas que les domaines du club l'utilisent ; leur sélecteur reste en 8.1. Aucune extension alternative n'a été activée et aucune base n'a été créée. Le jeu de caractères de la future base reste à vérifier.

Le responsable précise que la création d'un second tableau de suivi ne faisait pas partie de sa demande. La page expérimentale `/parcours` est retirée de la branche candidate ; les quatre étapes restent décrites dans le registre et le guide, et le suivi HTML existant reste consacré aux décisions historiques. Le prototype G1 joint le 29 septembre a la même empreinte SHA-256 que la source déjà inventoriée le 24 septembre. Son contenu est une référence visuelle antérieure : il conserve l'adresse FFT et ne contient pas les coordonnées ni le logo fournis ensuite. La variante officielle locale reprend son identité et ses accès rapides, avec les corrections ultérieures. Le site de démonstration distant n'a pas pu être comparé directement : le navigateur signale un certificat non reconnu et la lecture web ne récupère pas la page. Aucune écriture hébergée n'a été effectuée.

L'archive Drupal candidate a été relue sans dépôt : ses 34 017 entrées passent le contrôle CRC ; aucun chemin de traversée, lien symbolique, `.env`, base SQLite, identifiant local, route de bureau ou `settings.php` actif n'y a été trouvé. Trois fichiers nommés `settings.php` appartiennent uniquement à des fixtures de test de Drupal et d'une dépendance ; ils ne sont pas chargés comme configuration du site. Le diff Git depuis le commit source inscrit dans l'archive ne montre aucune modification sous `drupal/`. Cette vérification structurelle ne démontre pas encore le comportement Apache de l'hébergeur.

## Revue et dépôt

La [PR initiale no 1](https://github.com/jpdandin44/TC_Longages/pull/1) a été fusionnée par l’utilisateur le 29 septembre à 10 h 25 UTC ; le commit de fusion observé est `d4e0fdbaf9c05ca510202dad9233d944771118d1`. Les contrôles technique et de checklist ont réussi avant fusion. Cela intègre le socle local dans `main`, sans installation hébergée ni validation de phase déduite.

Le refus **« Requête extérieure refusée »** à l’ouverture du suivi depuis un lien externe a été reproduit : la garde locale bloquait aussi une navigation volontaire. Le correctif permet uniquement l’ouverture de l’accueil par un clic de premier niveau et maintient les refus sur les API, écritures et pages incorporées. La branche `fix/navigation-revue` porte ce correctif ; ses références de revue et résultats sont dans le reçu technique ci-dessous.

L’utilisateur demande de finaliser les PR et les contrôles de phases. Ses deux notes de revue sont conservées dans le [registre canonique](suivi-chantier/suivi-chantier.json). La saisie était déjà possible ; la remise et la validation étaient bloquées faute de PR, version source et preuves rattachées. L’interface explique désormais chaque étape, les causes de blocage et les critères enregistrés ; elle conserve le commentaire après enregistrement des critères et redemande une confirmation personnelle pour une décision.

Git local est raccordé au dépôt, avec préservation du commit initial. L’utilisateur a rendu le dépôt **public**. Les détails internes d’hébergement et chemins personnels ont été séparés des sources publiables, avec originaux privés conservés sur le poste. La protection de `main` a été enregistrée : PR obligatoire, checks `technical-ci` et `policy`, application aux administrateurs, discussions résolues et refus des envois forcés et suppressions. Voir [le fonctionnement du dépôt](preparer-depot.md).

Le [reçu technique](../data/framework-revue-verification.json) est la source des références effectives de PR, commit testé, tests et observation des protections. Le registre est la source des remises et décisions. Lire ces deux fichiers avant de conclure à un succès distant ou à une validation ; une description de procédure ne remplace pas un résultat. Le [candidat de revue](candidat-revue.md) distingue le commit source testé des reçus et vues ajoutés ensuite.

Les PR #1, #2 et #3 ont été fusionnées par l’utilisateur et concernent la phase 0. La #3 synchronise le suivi après la #2 ; elle n’a pas clos la phase. Le suivi interactif doit présenter les seules PR rattachées à la phase sélectionnée, avant les dossiers, et lire leur état public sur GitHub. La phase 0 demeure en revue tant que ses quatre critères et sa validation personnelle ne sont pas enregistrés ; le passage à la phase 1 demande une autorisation distincte.

Les contrôles GitHub exécutent les tests et la politique stricte de PR, sans accès de production. Les quatre cases humaines restent à renseigner par le responsable : `policy` échoue tant qu’elles ne sont pas confirmées. Ce contrôle porte sur les déclarations écrites, pas sur l’authenticité d’une signature ; aucune seconde revue GitHub impossible à obtenir avec un compte unique n’est imposée. Ni phase, ni merge ne sont acceptés à la place de l’utilisateur.

## Services locaux et sécurité

Le [suivi local](http://127.0.0.1:4181/) permet notes, critères, remise, validation et autorisation suivante distinctes. Son identité est déclarée sur le poste, sans authentification Drupal partagée. Les garde-fous Host/Origin, jeton, révision, sauvegarde et boucle locale restent actifs. L’exception d’installation TCL-EX01 est refermée : [installation.json](../framework/installation.json) reste `secured`.

Le [Drupal local](http://127.0.0.1:4182/) est laissé en maintenance, avec [connexion administrative](http://127.0.0.1:4182/user/login) et réglage natif `/admin/config/development/maintenance`. Identifiant de recette, SQLite et configuration privée restent sous `.local/`, hors Git et racine publique. Les rôles Bureau/Capitaine et les intégrations Google ne sont pas réalisés. Les [guides framework](installation-framework.md) et [Drupal](installation-drupal.md) donnent les commandes locales. Vérifier les processus à la reprise ; ne retirer un verrou qu’après avoir constaté l’arrêt de son propriétaire.

## Recettes et preuves

Les tests courants liés au candidat et les limites de leur périmètre sont inscrits dans le reçu technique. Les contrôles de transitions utilisent des fixtures isolées : aucune décision humaine de test n’est écrite dans le vrai suivi.

Les [115 tests Node et 45 contrôles Drupal de l’installation](../data/framework-interactif-verification.json), les [preuves d’adoption](../data/framework-verification.json) et son [point archivé](suivi-chantier/archives/2026-09-29-adoption-framework.md) sont des résultats historiques sans commit source de l’époque. Ils ne sont pas réétiquetés comme preuves d’un nouveau commit. La recette courante Node ne vaut pas un nouvel essai Drupal ou une qualification hébergée. L’archive V1 historique n’est pas devenue un paquet Drupal.

## Réserves d’hébergement et prochaine décision

L’[inventaire public expurgé](../data/hebergement-inventaire.json) conserve les constats utiles : domaine et racine observés, PHP natif 8.1, certificat autosigné, absence de base et un enregistrement JetBackup visible. La sauvegarde n’est ni téléchargée ni restaurée, les fichiers cachés et versions CLI/SQL restent à inventorier. L’original détaillé est conservé hors dépôt. Aucun changement PHP/SSL/DNS, création de base, transfert de site ou ouverture n’a été exécuté.

La phrase historique qui demandait de terminer la revue de phase 0 est dépassée : l'utilisateur a depuis validé cette phase et autorisé/démarré la phase 1. Pour la V1 actuelle, suivre le parcours simplifié. Sur l’hébergement, la version PHP compatible, la base SQL et un certificat reconnu restent à confirmer. Ensuite : sauvegarde restaurable, base dédiée, secrets saisis dans un canal adapté, racine Drupal propre, installation fermée et recette avant ouverture explicite. Le modèle `settings.hosting.example.php` est inactif. Rétention, restauration, droits métier, ressources Google et support restent à qualifier.

## Bilan documentaire

La politique documentaire globale est appliquée sans modification de son fichier global. Profil, registre, inventaire et reçu technique sont les sources structurées ; Markdown conserve procédures et décisions ; les deux tableaux de bord dérivent du suivi. Le reçu indique les vérifications réellement effectuées. Les limites d’hébergement restent des réserves ouvertes et ne sont pas résolues par la CI ou la création d’une PR.
