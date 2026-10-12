---
project: TC_Longages
document_type: integration-contract
title: Contrat OAuth du Bureau
status: active
version: git
created: 2026-10-12
updated: 2026-10-12
owner: jpdandin
tags: [oauth, pkce, drupal, acces, bureau]
---

# Contrat OAuth du Bureau

Le fournisseur est le Drupal **TC Longages**, avec Simple OAuth 6.1.1 et
`tcl_identity`. Le consommateur `tcl_bureau_oauth` reprend le contrat CONNECT ;
les comptes et clés sont propres au TC. L'installation hébergée doit encore
être recettée avant annonce de service.

## Routes et flux

- `GET /bureau/connexion` : vers `/bureau` si la preuve est encore valable ;
  sinon transaction de cinq minutes en session et redirection au fournisseur.
  Configuration absente/invalide : réponse 503, aucun accès métier.
- `GET /bureau/oauth/retour` : transaction de session consommée à usage unique,
  échange du code côté serveur puis vérification des jetons et de l'habilitation.
  Échec/refus : 403 générique, catégorie technique journalisée sans jeton.
- `/bureau` et toutes ses opérations : preuve OAuth plus compte actif et droits
  Drupal. GET sans preuve : 303 vers connexion ; POST sans preuve : 403 sans
  exécution ni renvoi des champs au fournisseur. Les services métier appliquent
  eux aussi la vérification OAuth, au-delà de la seule navigation.
- Déconnexion : route native Drupal, suppression de la session et de sa preuve.
  Le fournisseur dédié partage la session Drupal : la déconnexion TC supprime
  aussi sa connexion native. Aucun autre service AVEREO n'est concerné.

Le code utilise **Authorization Code + PKCE S256** : state, nonce et verifier
aléatoires conservés côté serveur. Aucun retour arbitraire depuis `destination`
n'est accepté ; le callback et la destination Bureau sont fixes.
Le callback doit correspondre exactement à l'origine autorisée du TC.
Voir [RFC 7636](https://www.rfc-editor.org/rfc/rfc7636) et la
[finalisation native Drupal](https://api.drupal.org/api/drupal/core!modules!user!user.module/function/user_login_finalize/11.x).

ID token : algorithme RS256 seul, clé RSA locale configurée de 2 048 bits minimum,
signature, issuer exact, audience/client, azp, dates et identifiant sub vérifiés.
`userinfo.sub` doit être une chaîne identique au sub signé. Les clés indiquées
par le jeton ne sont pas téléchargées. Le parsing strict reprend le contrat
CONNECT existant et est couvert par les tests de signatures/claims.

Le nonce est obligatoire pour le fournisseur TC. Simple OAuth 6.1.1 ne le
propage pas nativement : `tcl_identity` conserve le nonce de la redirection
autorisée pendant cinq minutes dans un stockage expirable privé, sous le hash
du code. `TcIdTokenResponse` le consomme après validation du code/PKCE par League
et l'ajoute au jeton signé. L'émetteur est fixé par la configuration privée.
La compatibilité historique `allow_missing_nonce` reste disponible dans le
consommateur mais demeure désactivée pour ce fournisseur dédié.

Les routes fournisseur `/oauth/authorize`, `/oauth/token`, `/oauth/userinfo` et
`/oauth/jwks` sont contrôlées par leur nom de route, y compris en cas de préfixe
de langue. Client, callback, origine et PKCE S256 sont fixes. Seul le grant
`authorization_code` est accepté ; grants password, machine et refresh refusés.
L'autorisation automatique du seul client interne TC évite une deuxième
confirmation après connexion ; aucun client externe n'est ajouté.

## Configuration privée

Le [modèle dédié inactif](../drupal/config/tc-identity.example.php) est à copier
hors racine web puis inclure depuis `settings.php`. Ne jamais le remplir dans
Git, une configuration Drupal exportable, le navigateur ou la conversation.

| Paramètre | Sens |
| --- | --- |
| enabled | Activation explicite après qualification du client. |
| issuer | Émetteur exact attendu dans le jeton, non déduit d'une URL de connexion. |
| authorize_url / token_url / userinfo_url | URLs HTTPS vérifiées du même fournisseur, sans redirection HTTP suivie côté serveur. |
| redirect_uri | Callback TC exact ; candidat privé : `https://preprod.tclongages.fr/bureau/oauth/retour`. |
| client_id / client_secret | Client confidentiel dédié au TC ; secret distinct, 32 caractères minimum. |
| public_key_path | Clé publique du fournisseur hors racine web, lisible par le service. |
| identities | Associations explicites issuer/sub/uid ; ni partage par email ni création automatique. |
| allow_missing_nonce | Toujours false pour le fournisseur TC dédié. |

`tcl_identity` demande également enabled/issuer/client_id/redirect_uri ; les
deux modules doivent avoir les mêmes valeurs. Les chemins de clés Simple OAuth
sont des overrides privés, pas des exports Drupal. Client confidentiel, secret
propre, PKCE activé, un seul callback et `automatic_authorization: true` sont
nécessaires. Le client ne donne aucun droit métier : les rôles TC et la liste
issuer/sub/uid restent déterminants.

Les tokens sont supprimés après établissement de la session ; aucun refresh token
n'est conservé. La preuve est bornée par l'expiration du jeton et huit heures
maximum, avec trente minutes d'inactivité maximum. La session native est renouvelée
à la connexion. Les réponses privées et le callback sont `no-store`, `noindex`
et `no-referrer`. Les cookies HTTPS natifs sont Secure/HttpOnly ; leur retour
inter-domaines et le proxy doivent être contrôlés sur la cible réelle.

Une révocation du compte TC est prise en compte immédiatement lors de l'accès
suivant : fournisseur et Bureau utilisent le même compte local. La suppression
d'un token OAuth isolé n'est pas interrogée à chaque requête ; invalider le
compte, son association ou sa session pour révoquer l'accès Bureau courant.
L'expiration de la preuve impose une nouvelle connexion auprès du fournisseur.

`local_fixture: true` accepte uniquement HTTP en boucle locale et un hôte TC
`127.0.0.1`. `local_provider` et `tcl_identity.local_recipe` ouvrent la même
exception HTTP pour la recette du fournisseur TC. Ces options, les comptes,
clés et fixtures ne sont pas inclus dans le paquet hébergé.
