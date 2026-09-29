---
project: TC_Longages
document_type: operating-guide
title: Accès local réservé au bureau
status: active
version: git
created: 2026-09-16
updated: 2026-09-16
owner: jpdandin
tags:
  - bureau
  - authentification
  - prototype
---

# Accès local réservé au bureau

Ce guide concerne exclusivement le prototype protégé `dist/` sur le **port 4173**. La [visite fictive partageable](visiter-prototype.md), située dans `prototype/` sur le port 4174, montre les écrans sans compte et uniquement avec des exemples. Son accès libre ne crée aucun compte, ne retire pas la protection décrite ici et n'autorise aucune collecte réelle.

Le besoin futur confirmé est un compte personnel administrateur et un compte générique bureau. Leur [connexion OIDC expérimentale](authentification-oidc.md) est préparée séparément sur le port 4175 ; elle n'utilise pas le fichier Basic décrit ici. L'utilisateur maintient le projet en maquette et reporte la migration réelle après l'achat du domaine. Aucune activation d'accès n'est nécessaire pour la visite fictive.

## État actuel

Le serveur du prototype contrôle l'accès aux pages du bureau avant de servir leur HTML. La vitrine reste consultable sans connexion. **Aucun compte réel n'a été créé et la configuration locale n'est pas installée** : les pages internes restent fermées avec le message « Accès bureau en préparation ».

L'utilisateur demande de réserver au bureau la communication et la gestion des inscriptions. Il a confirmé un formulaire adhérent distinct de cette gestion privée. La [proposition de processus](processus-inscriptions.md) est disponible pour validation, mais la page protégée reste inchangée, sans formulaire ni collecte de données. Les écrans de formulaire et de suivi sont montrés dans la visite fictive séparée ; aucun accès adhérent réel ni reprise sécurisée de dossier n'est implémenté.

La recommandation initiale de comptes individuels a été suivie d'un choix explicite de l'utilisateur : deux comptes OIDC, personnel administrateur et générique bureau. Le fournisseur et leurs identités restent à définir lors de l'activation future. Les recettes Basic restent distinctes de ces futurs comptes ; aucune création Basic n'est nécessaire pour mettre en œuvre le choix OIDC.

## Pages et comportements

| Chemin du serveur local | Accès | Contenu |
|---|---|---|
| `/` ou `/index.html` | Libre sur cet ordinateur. | Vitrine, sans lecture des actualités de `localStorage`. |
| `/bureau.html` | Compte actif de rôle `bureau`. | Accueil des outils internes. |
| `/communication.html` | Compte actif de rôle `bureau`. | Préparation et validation des actualités. |
| `/inscriptions.html` | Compte actif de rôle `bureau`. | Page d'attente, sans formulaire ; proposition d'enrichissement documentée séparément. |
| `/actualites-bureau.html` | Compte actif de rôle `bureau`. | Aperçu privé des actualités validées dans ce navigateur. |

Pour les pages privées, le serveur renvoie `503` si la configuration est absente, invalide ou ne contient aucun compte bureau actif. Avec une configuration utilisable, il demande une connexion HTTP Basic et renvoie `401` aux demandes sans authentification valable. Seuls un mot de passe correct, `enabled: true` et `role: "bureau"` permettent l'accès. Une limitation des tentatives peut renvoyer `429` avec une invitation à patienter.

Le serveur démarre avec `npm.cmd run preview` sur `127.0.0.1:4173`. L'accès ne doit pas être élargi au réseau local ou à Internet. La configuration est relue à chaque requête : retirer ou désactiver un compte coupe ses requêtes suivantes, sans supprimer une page déjà chargée ni les données conservées dans son navigateur.

## Configuration et référence technique

| Fichier | Responsabilité |
|---|---|
| `scripts/preview.mjs` | Démarrage sur la boucle locale. |
| `scripts/preview-server.mjs` | Routes publiques/privées et contrôle avant lecture du HTML. |
| `scripts/bureau-auth.mjs` | Lecture et validation des comptes, dérivation scrypt et vérification des accès. |
| `data/bureau-users.example.json` | Exemple inactif : version 1 et liste de comptes vide. |
| `.local/bureau-users.json` | Futur fichier privé des comptes locaux, non créé à ce stade. |

Le schéma du fichier privé est un objet comportant `version: 1` et `users`, tableau d'au plus 100 comptes. L'exemple conservé dans le projet est volontairement vide :

```json
{
  "version": 1,
  "users": []
}
```

Chaque entrée future de `users` doit contenir :

| Champ | Format et effet |
|---|---|
| `login` | Identifiant unique de 1 à 100 caractères parmi lettres ASCII, chiffres, point, tiret, soulignement et `@`. |
| `role` | Chaîne ; seul `bureau` autorise ces pages. |
| `enabled` | Booléen ; seul `true` autorise l'accès. |
| `salt` | Sel aléatoire de 16 octets, représenté par 32 caractères hexadécimaux minuscules. |
| `passwordHash` | Résultat scrypt de 64 octets, représenté par 128 caractères hexadécimaux minuscules. Aucun mot de passe en clair. |

La fonction interne `passwordRecord` accepte un mot de passe de 12 à 256 caractères et génère un nouveau sel. Les paramètres scrypt sont définis dans le code ; ne pas fabriquer les empreintes à la main ni les remplacer par du texte encodé. Aucun outil interactif ou commande de création de comptes n'est livré pour l'instant.

Le dossier `.local/` est exclu par `.gitignore` et n'est pas servi par les routes du prototype. Cette exclusion ne chiffre pas le fichier : les permissions du compte Windows et les sauvegardes privées restent importantes. Ne pas inclure ce fichier dans `dist/`, `release/`, les exports éditoriaux ou une archive de diffusion.

## Configuration Basic éventuelle pour un usage local autorisé

Cette procédure concerne uniquement ce prototype local. Pour la future connexion du club, suivre le [guide OIDC](authentification-oidc.md) et le [guide certificat](certificat-et-connexion-bureau.md), après la phase de maquette et la décision d'activation.

1. Confirmer le besoin distinct d'un accès Basic local et les personnes concernées, puis présenter l'effet concret de sa création.
2. Préparer une procédure de création ou un outil interactif local qui collecte les secrets par un canal adapté, sans mot de passe dans la conversation, le dépôt ou les journaux.
3. Après l'accord correspondant, produire les empreintes via le mécanisme du projet et installer le fichier privé dans `.local/`. Ne pas copier l'exemple vide en prétendant avoir ouvert l'accès.
4. Vérifier l'accès d'un membre autorisé, le refus d'un accès non autorisé et la désactivation d'un compte.
5. Documenter les responsables des accès et le retrait des membres, sans conserver leurs secrets dans les Markdown.

La préparation du mécanisme et les tests ne créent aucun compte utilisateur réel. Les recettes automatisées démarrent un serveur éphémère sur un port local distinct, avec un compte et un mot de passe aléatoire uniquement en mémoire ; elles n'installent pas `.local/bureau-users.json`.

## Limites à respecter

- Le contrôle porte sur les réponses de ce serveur local. Ouvrir directement un HTML ou disposer des fichiers sur le disque contourne ce contrôle ; les livrables ne sont pas chiffrés.
- HTTP Basic sur boucle locale n'est pas une architecture d'authentification publique prête à déployer. Ne pas exposer ce serveur par tunnel, changement d'adresse d'écoute ou transfert brut des pages internes.
- Le navigateur peut conserver les identifiants Basic ; il n'existe pas de déconnexion applicative fiable. Pour l'usage local, employer un profil réservé et maîtrisé. Une désactivation côté fichier refuse les nouvelles requêtes, mais n'efface pas les pages déjà ouvertes.
- `localStorage` appartient au profil du navigateur, pas au compte bureau. Deux personnes utilisant le même profil peuvent accéder aux mêmes données locales ; changer de compte ne les isole pas. Aucun stockage d'inscription réelle n'existe ici ; les exemples de visite utilisent leurs propres clés sur une origine distincte.
- Aucun compte adhérent, espace membre, journal d'audit complet ou stockage partagé n'est livré.

La couche OIDC prépare la connexion et des sessions côté serveur, mais son activation est reportée. Avant toute mise en ligne du bureau, il faudra qualifier fournisseur, HTTPS, proxy et sessions sur l'hébergement, puis définir le stockage central. La proposition d'inscription doit encore être validée, notamment les modes d'accès des adhérents à leurs propres dossiers ; aucune donnée de gestion du bureau ne doit devenir publique du fait du futur formulaire. La mise en production conserve sa décision et son accord explicite distincts.
