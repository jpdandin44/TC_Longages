---
project: TC_Longages
document_type: development-guide
title: Comptes et espace Bureau — première itération locale V2
status: active
version: git
created: 2026-10-05
updated: 2026-10-06
owner: jpdandin
tags: [drupal, comptes, bureau, inscriptions, recette]
---

# Comptes et espace Bureau

La [communication du Bureau](communication-bureau.md) utilise désormais le même
compte et menu : brouillons, affiches, revue et publication sur le site local.
Les visiteurs et capitaines n'accèdent pas à cette gestion.

Après la publication de V1, le responsable demande de commencer V2 en local,
puis précise que le Bureau doit surtout enregistrer les nouveaux adhérents.
La gestion des équipes vient ensuite. Le [suivi canonique](suivi-chantier/suivi-chantier.json#developmentIterations)
porte l'itération séparément du reçu V1 et des validations humaines antérieures.

## Premier lot fonctionnel

Le menu **Bureau / Capitaine** du site ouvre la connexion puis l'espace du club.
Le Bureau crée et modifie des dossiers Adultes/Mineurs, renseigne les coordonnées,
le responsable légal pour un mineur, la formule souhaitée et des notes internes.
Il recherche par nom/prénom et suit les états **Reçu**, **À compléter** et
**Vérifié par le Bureau**. Ce dernier état ne crée ni licence FFT ni paiement.
Une fiche par personne et saison est admise ; un email familial peut être partagé.

| Profil | Actions du candidat local |
|---|---|
| Administrateur Drupal | Accéder aux dossiers et gérer les comptes Bureau/Capitaine. |
| Bureau | Saisir/rechercher/modifier les dossiers ; gérer uniquement les comptes Capitaine, leur activation et leur mot de passe. Aucun accès natif à l'administration des utilisateurs ni aux comptes Bureau/administrateur. |
| Capitaine | Connexion native et écran d'attente du prochain lot ; aucun accès aux dossiers d'adhésion ni aux comptes. |
| Visiteur | Site public ; dossiers internes et inscription libre refusés. |

Les [permissions](../drupal/web/modules/custom/tcl_bureau/tcl_bureau.permissions.yml),
le [schéma et les rôles](../drupal/web/modules/custom/tcl_bureau/tcl_bureau.install)
et le [service de dossiers](../drupal/web/modules/custom/tcl_bureau/src/RegistrationRepository.php)
sont les sources exécutables. Le code et les exemples d'équipes déjà préparés
sont conservés pour la suite avec `teams_enabled: false` : routes refusées,
y compris à l'administrateur, et aucune équipe proposée dans le tableau Bureau.

## Recette locale

[Ouvrir le site local](http://127.0.0.1:4182/), puis **Bureau / Capitaine**.
[Accès direct aux dossiers](http://127.0.0.1:4182/bureau/adherents).
Le navigateur est préparé avec le compte fictif Bureau. Les identifiants générés
restent dans `.local/v2-local-accounts.json` ; ceux de l'administrateur dans
`.local/drupal-admin.json`. Ne pas importer ces fichiers sur une cible distante.
Le test HTTP ajoute dossiers et comptes fictifs isolés, avec emails `.invalid`.
Aucun joueur, dossier d'adhérent ou accès de production n'est utilisé ; courriels
neutralisés. Le logo fourni et les couleurs du club sont réutilisés.

Depuis la racine du checkout, avec PHP et les dépendances Composer du verrou :

```powershell
./scripts/drupal-local.ps1 prepare
./scripts/drupal-local.ps1 install
php -c .local/drupal-tools/php.ini drupal/bin/prepare-v2-local.php
./scripts/drupal-local.ps1 start
python tests/drupal-bureau-http.py
```

`install` refuse une base existante. Dans ce checkout préparé, utiliser seulement
`start` si le serveur est arrêté. Le helper CLI, hors de `web/`, refuse tout
stockage autre que le SQLite privé du checkout. Installer le module seul crée
le schéma et les rôles sans créer de compte ou donnée fictive.

Le [reçu](../data/bureau-local-verification.json) restitue **37 contrôles HTTP
réels réussis** des formulaires, sessions et écritures en base : Adultes/Mineurs,
champs requis et contact vide/espaces, responsable légal, CSRF, doublons,
double soumission, modification, ancienne révision, notes échappées, recherche,
création de comptes, rôle falsifié, blocage d'une session et refus des capitaines.
La recherche a aussi été vérifiée dans le navigateur. Le complément du 6 octobre
qualifie liste, création Adultes/Mineurs, modification et comptes à 390 pixels,
avec contrôles à 1 280 pixels. Le débordement des fieldsets est corrigé ; les
libellés/champs obligatoires sont conservés. Voir la
[recette complémentaire](recette-bureau-mysql.md) et son reçu distinct.
Les contrôles de syntaxe PHP en CI ne remplacent pas cette recette avec Drupal.

## Données et limites

Une table conserve les dossiers privés, auteurs, dates et révisions ; deux autres
conservent équipes et attributions différées. Comptes et mots de passe dérivés
restent natifs Drupal, sans stockage métier dans le navigateur. Les formulaires
contrôlent droits et CSRF avant écriture ; une mise à jour compare atomiquement
la révision et refuse une ancienne fiche. L'identité/saison et la clé de soumission
sont uniques. Les notes privées sont rendues comme texte, limitées à 1 000
caractères. Les réponses internes sont non indexables et sans cache partagé.

La [proposition métier existante](processus-inscriptions.md) reste la référence
pour le parcours complet et les décisions encore ouvertes. Ce premier formulaire
est à examiner avant collecte réelle. Import/export XLS/CSV, justificatifs,
consentements, disponibilités d'entraînement, groupes et suivi complet des
règlements ne sont pas implémentés dans ce lot. Les ambiguïtés tarifaires des
fiches ne sont pas arbitrées ; aucun tarif ou remise n'est calculé automatiquement.
La récupération par courriel nécessite un transport qualifié ; la réinitialisation
administrative existe dans les comptes autorisés. Les écrans métier sont français,
sans annoncer une traduction complète de l'administration Drupal.

## Quatre phases de l'itération

| Phase | État et suite |
|---|---|
| Cadrage | Priorité nouveaux adhérents et compléments calendriers/compétitions enregistrés ; champs et périmètres à examiner. |
| Développement local | Premier module, base fictive, recette, composants et PR du candidat. |
| Préproduction | À préparer : mise à jour Drupal/MySQL sur la cible isolée, sauvegarde et recette du même candidat. |
| Mise en production | Après recette et accord propre à V2 : préserver les données et conserver reçu/retour arrière. |

La [V1 publique](note-de-livraison.md) conserve son artefact et sa base. Aucun
déploiement V2 effectué ; les accords V1 ne sont pas transposés. Ne pas exécuter
les outils de première installation sur une base existante. Qualifier un
adaptateur de livraison hébergée et droits avant recette distante. Installation
additive, mise à jour MySQL et sauvegarde/restauration sont maintenant exercées
sur une base locale fictive, sans preuve d'hébergement. Les
[sources FFT](../api/fft.md) et le [calendrier Google](../api/google-calendar.md)
ont leur propre état : consultation publique Ten’Up possible, ADOC à reconnecter,
agenda actuellement refusé au public, aucune synchronisation des présences.
