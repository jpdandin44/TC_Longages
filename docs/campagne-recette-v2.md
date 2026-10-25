---
project: TC_Longages
document_type: acceptance-test-plan
title: Campagne humaine de recette V2 en préproduction
status: active
version: git
created: 2026-10-21
updated: 2026-10-22
owner: jpdandin
tags: [v2, recette, preproduction, parcours]
---

# Campagne de tests V2

Campagne exécutée partiellement par l’agent le 21 octobre 2026 dans Chrome, sur la session de préproduction autorisée. La création du mot de passe et les validations personnelles restent au responsable. Les résultats ci-dessous décrivent les observations de cette session, sans valoir acceptation humaine de la V2. Aucun code livré ni intervention en production pendant cette campagne.

## Cadrage

Cible unique : [préproduction](https://preprod.tclongages.fr/). Utiliser Chrome ou Edge, où la connexion protégée a fonctionné. La protection d’accès au site et la connexion au Bureau sont deux accès distincts. Ne saisir aucun mot de passe ou lien personnel dans la grille.

Aucune modification de production ni du calendrier Google partagé. Ne pas utiliser les commandes de création/modification de Google Agenda pendant ces essais. Les tests ne valent ni ouverture des inscriptions réelles ni accord de mise en production.

Utiliser uniquement des identités fictives : noms `Recette Campagne Adulte` / `Recette Campagne Mineur`, prénom distinct pour chaque dépôt voulu, naissance 15/06/1990 ou 15/06/2015, téléphone vide ou `0600000000`. Pour un mineur, responsable `Recette Responsable`. Choisir une formule publiée adaptée au profil ; noter son montant avant dépôt. Les créneaux et textes définitifs sont encore à compléter : leur absence annoncée n’est pas un défaut de cette campagne. Aucun import des fiches papier.

Adresses de test : notification d’adhésion vers la boîte du club déjà autorisée ; invitation vers le compte de recette déjà désigné par le responsable. L’adresse personnelle reste dans la configuration privée, hors de ce guide versionné. Pour le formulaire, utiliser `recette.campagne@example.invalid` ou l’adresse réelle déjà autorisée, sans ajouter de nouveau destinataire. Aucun accusé au demandeur n’est prévu. Les envois de cette campagne sont des envois réels : noter l’heure et vérifier la réception, sans multiplier les renvois.

Matériel : ordinateur, téléphone ou fenêtre étroite (390 px), compte Bureau autorisé, accès aux deux boîtes de test et un PDF/image sans données personnelles. Créer uniquement des contenus nommés `[RECETTE V2] …`. Conserver les exemples existants ; ne pas les remplacer.

## Développement local

Baseline documentaire : branche `feat/v2-recette`, HEAD relevé localement le 21 octobre `13e9283cc0a883ff4b150e8d275529f2f93324dd`. Complément Mois livré le 20 octobre : source `d50dca1645fa4ff3f63bf9a8f4b23442dfa8ba26`. Les lots précédents ont leurs reçus distincts ; ce SHA ne décrit pas à lui seul tous les fichiers du serveur.

Dernières preuves conservées : [agenda et Mois](../data/agenda-events-verification.json), [point de reprise](point-session.md), [guide communications](communication-bureau.md), [contrat d’inscription](../api/inscription-recette.md). Six contrôles CI réussis sur le candidat au relevé du 20 octobre ; aucun nouveau contrôle GitHub ou hébergé effectué pour cette préparation documentaire. Les modifications locales de clôture sont préservées.

## Préproduction

### Mode d’emploi et ordre conseillé

Commencer par les accès, puis l’adhésion, les communications et l’agenda. Les essais avancés de catalogue se font après le premier dossier : ils permettent de vérifier la conservation de son ancien tarif. Dans chaque ligne, remplacer « À faire » par **OK**, **Partiel**, **KO**, **Bloqué** ou **Non exécuté**, puis noter la date et une référence d’anomalie/capture. « Bloqué » signifie qu’un prérequis manque ; ne pas le compter comme une réussite.

Au début, noter : date/heure, testeur, navigateur, appareil et référence de version connue. En cas d’accès expiré, se reconnecter normalement ; conserver la protection. Avant un essai d’envoi, vérifier l’adresse affichée. Toute redirection inattendue vers la production arrête le scénario.

### Accès et comptes

| ID | Parcours à effectuer | Résultat attendu | Résultat / preuve |
| --- | --- | --- | --- |
| A01 | Ouvrir la préproduction, puis Bureau. Se connecter avec le compte prévu. | Accès protégé, identité TC correcte et fonctions correspondant au rôle. | Partiel — session Chrome authentifiée et accès Bureau vérifiés ; nouvelle connexion OAuth non rejouée. |
| A02 | Depuis Bureau → Comptes, ouvrir le compte invité existant ; envoyer une invitation seulement si son activation reste à tester et que le lien précédent est inutilisable. | Destinataire prévu ; confirmation d’envoi distincte de la réception. Pas de création d’un doublon de compte. | Partiel — compte invité existant Actif, rôle Bureau ; aucune invitation renvoyée, aucun doublon créé. |
| A03 | Consulter la boîte personnelle et les indésirables. Ouvrir personnellement le lien reçu, définir son mot de passe puis se connecter au Bureau. | Expéditeur attendu support@tclongages.fr, lien préproduction, activation et accès selon le rôle. Le responsable réalise lui-même la saisie du mot de passe. | Bloqué — création du mot de passe et connexion personnelle réservées au responsable. |
| A04 | Après activation, réouvrir le lien utilisé. Puis se déconnecter du Bureau et essayer un lien privé. | Le lien à usage unique ne permet pas une nouvelle activation ; les données privées exigent une connexion valide. | Bloqué — dépend de l’activation personnelle ; session existante non déconnectée. |
| A05 | Si un compte Capitaine/non-Bureau de recette est disponible, tenter les pages Communication et Événements. | Accès privé refusé. Sinon noter Bloqué — compte de test manquant, sans modifier les rôles. | Bloqué — aucun compte non-Bureau connecté pour ce contrôle ; rôles inchangés. |

### Accueil, tarifs et adhésion

Points d’entrée : [accueil](https://preprod.tclongages.fr/), [formulaire](https://preprod.tclongages.fr/adherer), [saison](https://preprod.tclongages.fr/bureau/saison), [dossiers](https://preprod.tclongages.fr/bureau/adherents).

| ID | Parcours à effectuer | Résultat attendu | Résultat / preuve |
| --- | --- | --- | --- |
| I01 | Depuis l’accueil, suivre le lien d’adhésion et comparer saison/formules/prix avec le tableau de tarifs. | Lien accessible ; même catalogue publié ; mention de recette fictive visible. | OK — V2-TARIFS-01 corrigé le 22 octobre : FAQ de / et /index.html conforme aux dix formules publiées, cours adultes 150 € et compétition 170 € séparés, note ancienne retirée. Formulaire et lien adhésion conservés ; empreintes des cinq dossiers et du catalogue identiques avant/après. Voir data/tariff-consistency-verification.json. |
| I02 | Remplir une demande adulte fictive ; afficher le récapitulatif, corriger un champ puis déposer. | Correction conservée, confirmation et un dossier ; tarif et formule conformes au choix. | OK — dossier adulte 4 (Adulte21), loisirs 70 €, récapitulatif puis correction de ville conservée au dépôt. |
| I03 | Vérifier la notification dans la boîte du club, puis retrouver le dossier au Bureau. | Courriel réellement reçu, contenu cohérent avec le dossier ; aucune attente d’accusé au demandeur. Noter l’heure et l’identifiant, sans copier les coordonnées. | Partiel — dossiers 4 et 5 présents au Bureau ; notifications acceptées par le serveur. Réception des deux messages Adulte21 et Mineur21 confirmée par le responsable le 21 octobre ; contenu des messages non inspecté par l’agent. |
| I04 | Faire une demande mineur fictive avec responsable légal et formule mineur. Essayer d’abord un champ obligatoire vide ou un courriel mal formé. | Erreur compréhensible et aucune soumission invalide ; parcours complet possible après correction. | OK — dossier mineur 5 (Mineur21), initiation 130 € ; courriel du responsable invalide bloqué, puis dépôt accepté après correction. |
| I05 | Reprendre la même identité/saison après dépôt ; tester un double clic sur le dépôt d’un seul nouveau dossier. | Aucun dossier supplémentaire pour le même dépôt/identité ; aucun envoi répété provoqué par le double clic. | Partiel — double clic puis nouvel essai séparé de la même identité : refus explicite « Un dossier de cette personne existe déjà pour cette saison », un seul dossier Adulte21 au Bureau. Nombre de courriels non revérifié après cet essai refusé. |
| I06 | Rechercher le dossier au Bureau, modifier une donnée fictive et passer Reçu → À compléter → Vérifié par le Bureau. | Données conservées, états cohérents ; aucune licence ni paiement créé. | Partiel — recherche et statut À compléter enregistrés avec commentaire sur le dossier 4 ; Vérifié par le Bureau réservé au responsable. |
| I07 | Ouvrir Saison sur ordinateur puis mobile. Parcourir chaque colonne et champ. | Libellés, profils, prix et boutons accessibles ; aucun champ tronqué ou superposé. Un défilement prévu reste utilisable. | Partiel — champs accessibles dans le DOM, rendu ordinateur et mobile 390 px contrôlé sans débordement ; toutes les positions de défilement non inspectées visuellement. |
| I08 | Noter le catalogue publié ; modifier en brouillon le prix d’une formule fictive utilisée dans I02, sans publier. | L’accueil et le formulaire gardent le prix publié précédent. | OK — brouillon loisirs passé à 71 €, formulaire resté à 70 € ; brouillon remis à 70 € et sauvegarde relue. Aucun catalogue republié. |
| I09 | Publier explicitement ce brouillon en préproduction. Déposer une nouvelle identité sur cette formule puis relire I02. | Nouveau dossier au nouveau prix ; ancien dossier inchangé. Remettre ensuite le prix initial et republier, sans effacer l’historique. | Bloqué — republication et attestation personnelle laissées au responsable ; conservation historique après changement publié non rejouée. |
| I10 | Ouvrir un formulaire avant une republication du catalogue, puis poursuivre ce formulaire après republication. | Nouveau récapitulatif demandé ; aucun dépôt silencieux avec un tarif périmé. | Bloqué — nécessite la republication personnelle du catalogue. |

### Communications et supports

Point d’entrée : [Communication](https://preprod.tclongages.fr/bureau/communication). Utiliser une nouvelle actualité fictive réservée à cette campagne.

| ID | Parcours à effectuer | Résultat attendu | Résultat / preuve |
| --- | --- | --- | --- |
| C01 | Créer `[RECETTE V2] Communication`, saisir le texte, importer un PDF existant, renseigner sa description et enregistrer un brouillon. | Aperçu pendant la saisie, puis première page du PDF enregistré ; téléchargement du PDF original utilisable. | Non exécuté — nouvel import écarté de cette passe par choix explicite du responsable le 21 octobre ; Chrome le refusait avant transfert (accès aux fichiers locaux de l’extension). PDF existant Halloween affiché et téléchargé ; cela ne valide pas un nouvel import. |
| C02 | Essayer une image dans une seconde communication ; comparer ordinateur et mobile. | Image entière lisible sans déformation ; aperçu et texte cohérents, boutons accessibles. | Non exécuté — nouvel import image écarté de cette passe par choix explicite du responsable, comme C01. |
| C03 | Choisir Facebook/WhatsApp sans Site internet ; enregistrer puis valider la révision. | Choix conservés ; validation seule ne publie rien, publication sur le site indisponible sans ce canal. | Partiel — brouillon 2 créé, Facebook/WhatsApp conservés et aperçus présents, Site absent ; validation personnelle non effectuée. |
| C04 | Ajouter Site internet, enregistrer, revalider puis publier explicitement sur le site de préproduction. Consulter accueil, actualités et détail. | Modification invalide l’ancienne validation ; publication explicite nécessaire ; titre, texte et support corrects sur la préproduction. | Bloqué — validation et publication personnelles non effectuées. |
| C05 | Sur la révision validée, copier le message et ouvrir la préparation WhatsApp/Facebook. S’arrêter avant l’envoi externe. | Texte conforme, outils du canal choisi ; affiche à télécharger et joindre manuellement. Aucun envoi automatique ni promesse de PDF joint par le lien WhatsApp. | Partiel — revue existante validée : PDF affiché et téléchargé ; bouton Copier annonce le succès mais presse-papiers non confirmé par l’outil. Liens sociaux présents, pas d’envoi ni ouverture externe testée. |
| C06 | Modifier la communication publiée, puis vérifier le site. Revalider/publier, retirer, archiver puis restaurer en brouillon. | Modification retire la publication ; retrait/archivage masquent le contenu et son support public ; restauration sans validation ni publication automatique. | Bloqué — cycle de publication/archivage réservé au responsable ; exemple existant conservé. |

Un envoi Facebook/WhatsApp réel n’est pas nécessaire pour cette campagne de préparation du partage. Ne pas publier un test sur les canaux officiels du club. Un essai de livraison sociale pourra être cadré séparément avec une destination privée choisie.

### Agenda et calendrier

Deux surfaces distinctes : [Calendrier public](https://preprod.tclongages.fr/calendrier.html) et [Agenda du Bureau](https://preprod.tclongages.fr/bureau/evenements). Les couleurs/icônes/miniatures nouvelles concernent les cartes du Bureau. Les couleurs de Google restent gérées dans Google ; aucun événement du Bureau n’y est synchronisé.

| ID | Parcours à effectuer | Résultat attendu | Résultat / preuve |
| --- | --- | --- | --- |
| E01 | Ouvrir Calendrier, choisir Semaine puis recharger ; choisir Liste puis rouvrir la page. | Mois à chaque ouverture, même après un ancien choix ; autres vues utilisables pendant la visite ; Ambiance absent. | OK — Semaine puis rechargement → Mois ; Liste puis rechargement → Mois ; Ambiance absent. |
| E02 | Au Bureau, créer un événement ponctuel fictif, catégorie Manifestation, avec l’affiche de C01 active. Vérifier les dates puis enregistrer sur le même formulaire. | Aperçu puis sauvegarde sans erreur ; miniature PDF, libellé, couleur et icône de catégorie. | OK — complément : événement ponctuel créé depuis aperçu, 5 novembre 2026 de 18 h à 19 h, Manifestation et affiche Halloween existante. Une date, carte et miniature vérifiées ; enregistré Annulé pour la recette. |
| E03 | Modifier lieu/horaire et essayer les catégories (cours, manifestation, réservation, repas, AG, compétition, autre), puis les filtres. | Modification conservée ; chaque catégorie identifiable par texte et icône, pas uniquement couleur ; filtre cohérent. | Partiel — catégories complémentaires Réservation, AG, Compétition et Autre enregistrées sur la série annulée ; filtres correspondants vérifiés, ainsi que Cours, Manifestation et Repas (vide après changement). Sept catégories couvertes sur les deux passes ; contrôle visuel exhaustif des sept couleurs et modification horaire non rejoués. |
| E04 | Retirer l’affiche de l’événement ; puis la réassocier. Archiver la communication liée et la restaurer. | Sans affiche, icône de remplacement ; affiche archivée absente des cartes. Aucune publication déclenchée par l’association. | Partiel — retrait puis réassociation de l’affiche vérifiés, icône de remplacement puis miniature ; archivage de la communication existante non effectué. |
| E05 | Créer un cours hebdomadaire, 4 occurrences à 18 h ; exclure la troisième date. Vérifier les dates puis enregistrer. | Exactement 3 occurrences, mêmes horaires, date exclue absente ; sauvegarde après aperçu fonctionnelle. | OK — série du 3 au 24 novembre 2026 à 18 h, exclusion du 17 : exactement 3 dates, aperçu puis sauvegarde réussis. |
| E06 | Prévisualiser une répétition toutes les deux semaines et une quotidienne bornée ; prévisualiser le 31 janvier au 31 mars 2027 en mensuel. | Espacement correct ; pour le mensuel, 31 janvier et 31 mars uniquement, aucun report au 28 février. | OK — aperçus toutes les deux semaines, quotidien borné et mensuel : 31 janvier et 31 mars 2027, sans report en février. Aperçus non enregistrés. |
| E07 | Prévisualiser une fin avant le début, une récurrence sans fin et une plage au-delà d’un an. | Refus expliqué ; rien enregistré tant que les données ne sont pas corrigées. | OK — fin avant début, absence de fin et borne supérieure à un an refusées avec message. Données invalides non enregistrées. |
| E08 | Modifier la série, l’annuler puis la réactiver. | Action sur toute la série, passé compris ; état visible, annulation réversible. Pour déplacer une seule occurrence : exclusion + événement ponctuel. | OK — modification de toute la série, annulation puis réactivation vérifiées ; série finalement annulée pour la remise en état. |
| E09 | Ouvrir le même événement dans deux onglets. Enregistrer A puis tenter d’enregistrer B resté ancien. | B ne remplace pas silencieusement A ; conflit signalé et invitation à recharger. | OK — deuxième onglet ancien refusé : événement changé, recharger ; modification A conservée. |
| E10 | Ouvrir la préparation du partage depuis une carte avec affiche, puis consulter le calendrier public. | Préparation de la communication liée ; aucun envoi ni publication automatique, événement privé absent du calendrier Google. | Partiel — lien de la carte ouvre la revue de la communication liée ; aucune action Google ni diffusion. Absence de la série aux dates futures dans Google non vérifiée visuellement. |

### Contrôle mobile et fin de campagne

| ID | Parcours à effectuer | Résultat attendu | Résultat / preuve |
| --- | --- | --- | --- |
| M01 | Sur téléphone, rejouer l’accès accueil → formulaire, les tarifs, aperçu PDF et agenda/filtres. | Aucun chevauchement ; champs et boutons utilisables ; navigation et textes lisibles. Noter appareil et navigateur. | Partiel — tarifs et PDF contrôlés en fenêtre mobile lors de la première passe. Dans la seconde passe, demande 390 px restée à 1920 px ; réglage réinitialisé. Accueil/formulaire/agenda sur téléphone physique restent à contrôler. |
| M02 | Retirer/archiver uniquement les communications de campagne, annuler les événements créés et restaurer les tarifs initiaux après I09/I10. | Aucun contenu de test laissé publié ; dossiers et preuves conservés, aucun effacement des exemples antérieurs. | Partiel — série créée et nouveau ponctuel annulés, brouillon tarif à 70 €, aucun contenu publié. Brouillon communication 2 conservé pour validation personnelle, dossiers 4/5 conservés ; aucun exemple antérieur supprimé. |

### Relevé de la session du 21 octobre

Testeur : agent, Chrome connecté par le responsable ; uniquement `preprod.tclongages.fr`. Session protégée conservée. Aucun nouveau contrôle CI, déploiement ou merge. Les réserves ci-dessous ne sont pas des anomalies applicatives confirmées.

- Objets de test conservés : dossiers 4 et 5 ; communication 2 en brouillon, sans support importé ; série `55cc60440d9c94f7cce2c464b02d9379` annulée, catégorie Autre après tests ; ponctuel `740350243413ef00092b7231f76a35f1` annulé, Manifestation ; affiche Halloween existante sur les deux. Les deux événements et la communication antérieurs sont conservés.
- Tarif loisirs : publié 70 €, essai brouillon 71 €, retour brouillon 70 € confirmé. Aucune publication effectuée.
- Deux notifications d’adhésion acceptées ; le responsable confirme le 21 octobre la réception des deux messages Adulte21 et Mineur21 dans la boîte du club. Cette déclaration ne vérifie pas le contenu détaillé. Aucun renvoi nécessaire.
- Chrome : import bloqué faute d’accès de l’extension aux fichiers locaux. Aucune permission changée ; le responsable choisit de continuer sans nouvel import. C01/C02 restent non exécutés dans cette passe, sans les considérer réussis. Le navigateur intégré reste indisponible pour l’authentification protégée.
- Captures privées hors Git : `.local/campagne-20261021/` (tarifs ordinateur/mobile, dépôt adulte, conflit événement, calendrier Mois, PDF, brouillon canaux et `agenda-final.png`). Le fichier `agenda-mobile-final.png` est en réalité une capture ordinateur ; ne pas l’utiliser comme preuve mobile.
- Prochaine action : effectuer mot de passe et validations personnelles ; reprendre les lignes Partielles/Bloquées, notamment publication/retrait et tarif historique après republication. Les nouveaux imports sont différés conformément au choix du responsable.

### Complément de recette et validations personnelles restantes

Seconde passe du 21 octobre : doublon refusé sur formulaire frais, dossier unique confirmé ; catégories et filtres complémentaires contrôlés ; création ponctuelle depuis aperçu réussie. Aucun nouvel import, aucun envoi social, aucune production. Captures privées : `doublon-refuse.png` et `agenda-suite-final.png` dans le dossier de preuves de la session.

Incident transitoire : le formulaire ouvert avant le renouvellement automatique de connexion au Bureau a été refusé comme périmé. Rejeu après rechargement : contrôle de doublon fonctionnel. Aucun nouveau dossier créé par cet essai. L’origine précise du renouvellement n’a pas été diagnostiquée ; ce refus isolé n’est pas classé comme défaut applicatif confirmé.

Actions à réaliser personnellement, uniquement en préproduction :

1. **Compte invité (A03/A04)** : mot de passe défini, confirmé par le responsable ; restent la connexion, le refus de réutilisation du lien et les accès après déconnexion. Ne transmettre aucun secret.
2. **Adhésion (I03/I06)** : comparer le contenu des deux notifications reçues aux dossiers Adulte21 et Mineur21 ; passer le dossier adulte À compléter vers Vérifié par le Bureau après relecture.
3. **Communications (C03/C04/C06)** : relire et valider le brouillon 2 Facebook/WhatsApp ; vérifier qu’il n’est pas publié. Tester ensuite, en préproduction seulement, ajout du canal Site, validation/publication, modification, nouvelle validation, retrait et archivage/restauration. Le responsable conserve les confirmations personnelles ; l’agent peut contrôler les écrans entre ces étapes.
4. **Partage (C05)** : vérifier le collage du message dans un brouillon personnel et la préparation WhatsApp/Facebook, sans envoi sur les canaux du club. Le PDF doit être joint manuellement ; pas de nouvel import dans cette campagne.
5. **Catalogue (I09/I10)** : coordonner une republication temporaire d’un prix fictif puis son retour au prix initial. L’agent peut préparer les formulaires et vérifier les tarifs historiques/périmés ; le responsable effectue les attestations et publications.
6. **Téléphone (M01)** : accueil → formulaire, tarifs, PDF et filtres de l’agenda ; confirmer lisibilité, défilement et accès aux boutons sur un vrai appareil.

Prérequis restant distinct : A05 demande un compte non-Bureau de test déjà autorisé ; aucun rôle nouveau créé. Imports C01/C02 différés par choix du responsable, pas demandés à nouveau. Textes définitifs et créneaux restent à compléter avant toute collecte réelle. La validation finale de recette, le merge et la production restent des décisions distinctes.

### Diagnostic du lien invité — 21 octobre

Le responsable signale un retour à l’accueil depuis l’invitation. Vérification en lecture seule dans Chrome : `/user` mène au profil Admin_TC_Longages, uid 2 ; l’invitation précédemment vérifiée concerne Artemis, uid 3. Le code local utilise `user_pass_reset_url()` sans destination ni suffixe `/login`. La documentation du parcours et le test de régression local prévoient le retour à l’accueil lorsqu’un autre compte est connecté. Ce test n’a pas été réexécuté pendant ce diagnostic ; le lien personnel n’a pas été ouvert par l’agent.

Cause probable : conflit entre session administrateur et invitation d’un autre compte. Prochain contrôle personnel : ouvrir le lien depuis une fenêtre privée, saisir uniquement la protection de préproduction si demandée, puis observer la confirmation native avant création du mot de passe. Ne pas se connecter au Bureau administrateur dans cette fenêtre. Validité/expiration du lien encore non vérifiée ; aucun renvoi, aucune modification de compte ou production.

### Correction V2-MAIL-01 — livrée, réception confirmée

Le 21 octobre, autorisation explicite du responsable de corriger puis tester Artemis. Plugin dédié livré en préproduction, seules les adresses déjà autorisées sont éligibles ; hôtes autres que préproduction et types autres que password_reset refusés. Expéditeur support@tclongages.fr. 24 contrôles locaux, 20 contrôles existants de mail, 24 contrôles simulés hébergés et six contrôles CI réussis. Source `8fa4f2c`, manifeste `978027d`, CI `39644082041`.

Essai unique du service natif pour Artemis accepté à 19 h 34 (Europe/Paris), sans lire le lien ni créer de mot de passe. Réception confirmée par le responsable le 21 octobre ; ouverture du lien et création personnelle du mot de passe confirmées ensuite par le responsable. Le formulaire HTTP connecté sous administrateur n’a pas été soumis pour éviter de cibler le mauvais compte. Retour à l’accueil après demande natif attendu ; ce n’est pas une connexion du demandeur. Le parcours HTTP anonyme et la saisie du mot de passe restent personnels.

Le défaut NullMail et le transport des invitations sont préservés. Sauvegarde privée et retour arrière dans le [workflow](../workflows/reinitialisation-recette.md), [preuves](../data/password-reset-recipe-verification.json). Aucune production modifiée. Le diagnostic initial ci-dessous est historique, corrigé par cette livraison.

### V2-MAIL-01 — réinitialisation native sans envoi réel

Le 21 octobre, le responsable ne reçoit pas le courriel demandé pour Artemis. Contrôle en lecture seule via cPanel, dans `/home2/daje5127/tcl-preproduction/drupal`, Drush avec URI `https://preprod.tclongages.fr` : le transport effectif `user:password_reset` est `Drupal\tcl_site\Plugin\Mail\NullMail`, tandis que `tcl_bureau:account_invitation` utilise `Drupal\Core\Mail\Plugin\Mail\PhpMail`. La configuration stockée a pour transport par défaut `tcl_null_mail`. Le code local NullMail retourne TRUE sans envoyer : la confirmation de demande ne prouve donc pas un départ du courriel.

Anomalie majeure du parcours de réinitialisation en préproduction, distincte de la réception confirmée des invitations Bureau et notifications d’adhésion. Aucun journal individuel de livraison n’a été consulté ; le diagnostic porte sur la configuration effective actuelle. Aucun courriel renvoyé, aucune configuration ni production modifiée pendant ce contrôle.

Prochaine action : corriger de façon bornée le circuit `user:password_reset` pour les destinataires de recette autorisés, avec l’expéditeur validé, puis qualifier l’envoi et la réception. Ne pas activer globalement tous les courriels. Alternative pour l’essai d’activation : nouvelle invitation depuis le Bureau existant. L’utilisateur conserve la saisie du mot de passe. Retour au site après soumission ne vaut ni connexion Artemis ni mot de passe créé.

### Fiche d’anomalie à remplir

Copier une fiche par anomalie dans les retours de campagne ; ne pas y inclure de mot de passe ou lien d’invitation.

- Référence : V2-… ; parcours : … ; date/heure : …
- Appareil/navigateur : … ; page : …
- Étapes précises : …
- Attendu : …
- Observé : …
- Reproductible : oui / non / non réessayé ; capture : …
- Gravité : bloquant (accès/parcours impossible, fuite ou perte de données), majeur (fonction erronée), mineur (présentation sans blocage).
- Résolution et contre-test : à compléter après correction.

Pour un courriel manquant : conserver heure, type et identifiant du dossier/compte ; vérifier boîte et indésirables, puis le suivi de livraison. « Accepté par le serveur » ne suffit pas à marquer reçu. Ne pas renvoyer en boucle.

### Fin de recette

Tous les parcours applicables doivent avoir un résultat et une preuve suffisante. Un parcours Bloqué reste à résoudre ou à exclure explicitement avec sa raison. Aucun bloquant/majeur ouvert pour proposer la validation ; les réserves mineures sont listées pour décision du responsable. Les corrections impliquent un nouveau candidat et le rejeu des parcours impactés. Le responsable décide ensuite de l’acceptation, sans case précochée.

## Mise en production

Exclue de cette campagne. La recette ne déclenche aucun merge, déploiement, ouverture publique, migration Google ou activation de collecte réelle. Les textes, créneaux et décisions encore attendus restent dans le suivi V2 ; ne pas les confondre avec des défauts d’affichage ni avec une validation obtenue.

## Retour personnel — mot de passe

Le 21 octobre, le responsable confirme avoir défini le mot de passe Artemis après réception du courriel corrigé. Cette déclaration valide cette étape personnelle ; aucun secret lu par l’agent. L’aspect visuel de la page est jugé insuffisant : présentation corrigée et livrée en préproduction, sans modification du mécanisme natif. Six CI et 20 contrôles locaux du parcours passent ; écran de compte hébergé inspecté en lecture seule. Contrôle visuel mobile restant.

## Rapprochement tarifaire du 22 octobre — V2-TARIFS-01

Vérification hébergée en lecture seule, sans soumission ni publication. Le tableau de la FAQ conserve la ligne historique des cours adultes et sa note « 125 € ou 150 € ». Le catalogue de saison et le formulaire public distinguent Cours Adultes 150 € et Cours Adultes compétition 170 €. Les huit autres formules concordent. L’empreinte du fichier SeasonCatalogue.php hébergé correspond à la source locale : le retour anticipé après insertion du lien adhésion empêche le remplacement du tableau tarifaire présent plus bas.

Correction à réaliser : traiter le tableau et le lien adhésion indépendamment à partir du catalogue publié, retirer la note historique contradictoire du rendu courant ; conserver les documents historiques et les prix figés des dossiers. Aucun correctif appliqué lors de cet audit. [Preuves](../data/tariff-consistency-verification.json).

Correctif livré après l’audit : source `517d3ac`, manifeste `2934e5f`, six contrôles CI réussis (39854796840), 18 tests catalogue réussis. Un fichier sauvegardé/restauré en copie puis installé et empreinte vérifiée. Les dix formules sont conformes sur / et /index.html ; ancien texte retiré. Catalogue et cinq dossiers inchangés (comparaison des empreintes avant/après). Aucun envoi, aucune republication, aucune production.
