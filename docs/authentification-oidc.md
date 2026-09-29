---
project: TC_Longages
document_type: operating-guide
title: Connexion OpenID Connect des deux comptes du bureau
status: active
version: git
created: 2026-09-16
updated: 2026-09-24
owner: jpdandin
tags:
  - oidc
  - bureau
  - comptes
---

# Connexion des deux comptes du bureau

## Décision et état

**Périmètre de ce guide : expérimentation antérieure à deux comptes, inactive.** Le [cadrage officiel V1](integration-officiel.md) prévoit désormais Admin/Bureau/Capitaine et un contrôle par équipe ; le futur domaine est `tclongages.fr`. L'utilisateur souhaite une authentification Drupal et l'examen de la logique CONNECT mutualisable, mais laisse l'installation dédiée ou le raccordement existant **« À déterminer ensemble »**. L'architecture doit donc être qualifiée avant G3/AUTH-001. Ce guide ne signifie pas que l'OIDC existant est adopté pour V1.

**Drupal n'est pas intégré ni configuré comme fournisseur d'authentification.** L'orientation pour le domaine final est explicite, mais aucun module, instance, protocole ou déploiement réel n'est encore qualifié pour le club. Le service ci-dessous est un client OIDC générique sans fournisseur réel actif ; les adresses `tclongages@gmail.com` et `support@tclongages.fr` ne créent aucune identité ni aucun droit. L'étude de réutilisation de CONNECT ne vaut pas autorisation de modifier ou connecter un autre projet.

La [documentation officielle d'installation Drupal](https://www.drupal.org/docs/getting-started/installing-drupal/before-a-drupal-installation) distingue l'application serveur et sa base de données ; un ZIP de pages statiques n'installe pas ce service. Le projet [Simple OAuth](https://www.drupal.org/project/simple_oauth) est une possibilité de modules OAuth/OIDC à qualifier, sans choix de version ou de module acté ici. La [démonstration V1 sur le sous-domaine](publier-v1-sous-domaine.md) reste volontairement sans compte ni serveur Drupal ; son ouverture ne préjuge pas de la future connexion.

La [note de mutualisation CONNECT](mutualisation-connect.md) décrit un autre mécanisme existant de connexion Drupal et de tickets d'application ; ce n'est pas le client OIDC local décrit ci-dessous. Les domaines et applications sont actuellement limités à AVEREO, sans règles Capitaine/équipe du club. Leur adaptation, l'isolation des comptes et le modèle d'instance restent à décider. La mutualisation d'un service et la mise à jour d'un module embarqué n'ont pas le même cycle de déploiement : aucun héritage automatique global n'est annoncé.

Le besoin confirmé est un compte personnel **administrateur** et un compte générique **bureau**. OpenID Connect (OIDC), construit sur OAuth 2.0, sert à vérifier leur identité auprès d'un fournisseur externe. Le site ne demande pas leur mot de passe. La bibliothèque `openid-client` 6.8.8 réalise le protocole, avec ses dépendances verrouillées dans `package-lock.json`.

Le code du service est préparé dans `server/`. Le fournisseur et l'adresse HTTPS restent à confirmer. Aucun compte réel, secret client ou fichier privé d'autorisation n'est installé. Sans configuration, le service affiche « Connexion en préparation » et ne livre aucune page du bureau. Les résultats réellement obtenus sont dans la [recette OIDC](recette-oidc.md) ; ils ne valent pas activation d'un fournisseur réel.

Le port local 4175 concerne ce nouveau service. Le prototype Basic 4173 et la visite fictive 4174 gardent leurs fonctions distinctes. L'archive publique à un fichier reste inchangée : elle ne contient pas ce serveur ni les pages internes. Le service d'inscription et la base partagée restent à réaliser.

## Parcours et droits

1. Le visiteur ouvre le bureau et choisit **Se connecter**.
2. Il s'identifie chez le fournisseur choisi. Le serveur vérifie le code, la signature du jeton d'identité et les valeurs attendues.
3. Le couple exact **émetteur / identifiant stable** (`iss` / `sub`) est recherché dans la liste privée des deux comptes. Une adresse e-mail, même vérifiée, ne crée pas de droit automatiquement.
4. Une identité active ouvre une session du site. Une identité inconnue reste refusée et voit uniquement ses propres identifiants techniques, à transmettre au responsable pour préparation de son autorisation. Aucun accès n'est attribué par ce premier passage.
5. **Se déconnecter** ferme la session du site. Le fournisseur peut conserver sa propre connexion sur l'appareil.

| Page | Administrateur | Bureau générique |
|---|---|---|
| Accueil, communication, inscriptions en attente, aperçu privé | Autorisé | Autorisé |
| `/admin/acces` : état des deux accès, en lecture seule | Autorisé | Refusé |
| Modification de la liste des comptes | Configuration privée du serveur | Aucun écran de modification |

Le compte générique peut avoir plusieurs sessions simultanées. Il identifie le bureau collectivement. Retirer un membre de ce compte implique de traiter son accès chez le fournisseur et de révoquer les sessions du site ; changer seulement le mot de passe chez le fournisseur ne ferme pas automatiquement les sessions déjà ouvertes ici.

## Configuration privée à préparer

L'exemple [.env.example](../.env.example) est volontairement inactif. Le fichier `.env` local, les secrets cPanel et `.local/oidc-accounts.json` ne sont pas créés par cette préparation. Ne jamais envoyer leur contenu secret dans la conversation, dans le HTML ou dans une archive publique.

| Variable | Valeur attendue et effet |
|---|---|
| `TCL_PUBLIC_BASE_URL` | Origine exacte de l'espace bureau, en HTTPS, sans sous-chemin ni barre oblique finale. Exemple de forme uniquement : `https://bureau.votre-domaine.fr`. |
| `TCL_OIDC_ISSUER` | Émetteur OIDC HTTPS exact du fournisseur choisi, avec découverte standard et point d'autorisation sur la même origine. Pour Microsoft, un émetteur concret et compatible reste à déterminer ; un point multi-tenant n'est pas accepté par substitution automatique. |
| `TCL_OIDC_CLIENT_ID` | Identifiant de l'application enregistrée chez le fournisseur. |
| `TCL_OIDC_CLIENT_SECRET` | Secret de cette application, uniquement dans la configuration privée du serveur. |
| `TCL_OIDC_ACCOUNTS_FILE` | Chemin du JSON privé, idéalement absolu sur l'hébergement. Par défaut : `.local/oidc-accounts.json` dans le projet. |
| `TCL_OIDC_LOCAL_HTTP` | `false` en hébergement. `true` permet exclusivement une origine HTTP `localhost` ou `127.0.0.1` pour une recette locale. |
| `TCL_OIDC_TRUST_LOCAL_PROXY` | `false` par défaut. À activer seulement après vérification du proxy TLS local o2switch ; seul un pair de boucle locale et `X-Forwarded-Proto: https` sont alors acceptés. |
| `PORT` | Port d'écoute local, 4175 par défaut ; comportement Passenger à qualifier sur l'hébergement. |

L'adresse de retour à déclarer chez le fournisseur est exactement **l'origine choisie suivie de `/auth/callback`**. Les permissions demandées sont `openid email profile`. Aucun accès au courrier, aux fichiers, aux réseaux sociaux ou accès permanent par jeton de renouvellement n'est demandé.

Le [modèle des comptes](../data/oidc-accounts.example.json) contient exactement deux entrées, une de rôle `admin`, l'autre `bureau`, désactivées et sans identifiant. Après choix du fournisseur, renseigner l'émetteur exact. Chaque compte effectue son premier passage OIDC pour identifier son `sub`, puis le responsable fait préparer l'entrée correspondante avant activation explicite. Ne pas utiliser le premier compte connecté comme administrateur automatique.

Le JSON est relu à chaque requête authentifiée. `enabled: false` ou le retrait d'une identité coupe ses requêtes suivantes. Une modification du fichier ne retire pas une page déjà affichée. Un redémarrage du service efface toutes les sessions, ce qui constitue ici le moyen de déconnecter les deux comptes sur tous leurs appareils.

## Préparation et vérifications locales

Depuis la racine du projet :

```powershell
npm.cmd ci --ignore-scripts
npm.cmd run check
npm.cmd run bureau
```

Ces commandes installent les dépendances verrouillées, vérifient le code et ouvrent le service seulement sur la boucle locale. Sans configuration, [127.0.0.1:4175](http://127.0.0.1:4175/) reste fermé. Configurer un vrai client OIDC déclenchera des échanges avec son fournisseur lorsque l'utilisateur choisira de se connecter ; ce n'est pas un test fictif.

## Conditions de future activation sur o2switch

Choisir l'adresse et vérifier son certificat selon le [guide HTTPS](certificat-et-connexion-bureau.md). Le [gestionnaire Node.js d'o2switch](https://faq.o2switch.fr/cpanel/logiciels/hebergement-nodejs-multi-version/) permet de séparer **Application root** du dossier public du domaine : le projet, `dist/`, `.local/`, `.env` et les dépendances doivent rester hors de toutes les racines publiques. Le point d'entrée préparé est `app.cjs`, qui charge `server/start.mjs`. Ne pas déposer les pages du bureau comme fichiers statiques dans le domaine.

Le gestionnaire crée le raccordement au service. La racine publique peut contenir les fichiers techniques nécessaires au serveur, mais aucun HTML interne ni secret. Une application arrêtée ne doit jamais rendre les sources téléchargeables. Vérifier également la transmission de l'hôte et du protocole depuis le proxy ; le serveur refuse HTTP hors recette et ne déduit jamais ses URL de redirection d'en-têtes non fiables.

Les sessions et transactions sont conservées en mémoire, avec une limite de 100 chacune : **une seule instance Node.js** est prévue. Un redémarrage impose une reconnexion ; plusieurs processus indépendants feraient échouer les retours OIDC et les sessions. Vérifier cette configuration Passenger avant ouverture, ou ajouter un stockage de sessions partagé si nécessaire. Aucune disponibilité hébergée n'est attestée à ce stade.

L'authentification ne transforme pas le stockage local des actualités en base partagée. Les données déjà affichées ou conservées dans un profil de navigateur peuvent rester accessibles à son utilisateur après déconnexion. Pour des données réelles partagées entre personnes, le stockage serveur et les contrôles métier restent une étape nécessaire.

## Protections réalisées et périmètre

Code d'autorisation avec PKCE S256, `state` et `nonce` ; signature et claims validés par la bibliothèque ; transaction liée au navigateur, valable cinq minutes et consommée une seule fois. Les sessions utilisent un identifiant opaque aléatoire : aucun jeton fournisseur dans le navigateur. Cookie `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS et préfixe `__Host-`, sans attribut de domaine. Expiration après 30 minutes d'inactivité ou huit heures au maximum ; nouvelle session à chaque connexion. Déconnexion par POST, origine exacte et jeton CSRF. Les pages et réponses d'authentification portent `no-store`.

La liste exacte des routes est contrôlée avant lecture du HTML. Les fichiers de configuration, sources, exports et chemins arbitraires ne sont pas servis. Le protocole historique Basic n'est pas un moyen de secours dans ce service. Les erreurs affichées ne reproduisent ni code d'autorisation, ni réponse fournisseur, ni secret. La [bibliothèque OIDC](https://github.com/panva/openid-client) et la [définition des identifiants stables OIDC](https://openid.net/specs/openid-connect-core-1_0.html#ClaimStability) constituent les références techniques.

Avant toute action réelle, préciser cible, comptes, accès et retour arrière selon les [commandes sensibles](commandes-sensibles.md). Ni les paramètres d'exemple ni les tests ne constituent une autorisation de publication.
