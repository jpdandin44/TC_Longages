---
project: TC_Longages
document_type: release-source-guide
title: Sources de revue du candidat de lancement beta
status: in_progress
version: git
created: 2026-10-16
updated: 2026-10-16
owner: jpdandin
tags: [lancement, beta, securite, recette]
---

# Sources du candidat de lancement

## Objet et limite

Le responsable demande de finaliser le lancement apres la cloture du 15 octobre.
Cette branche isole les sources du candidat beta corrige, sans reprendre les
changements non commites du lot Competitions. Elle ne deploie rien et n'accorde
ni merge, ni validation humaine, ni ouverture publique.

Le candidat qualifie reste le ZIP du 15 octobre, SHA-256
`0b6b167c33d1b770729b4480b4558b12f0ef93aca5a323571ef875cb80477a0d`.
Il contient Drupal 11.4.10, 26 715 fichiers et le perimetre de la campagne beta :
sept pages, photo du court, agenda, edition native, contact et signalement.
Bureau/OAuth, gestion des adherents, communication interne et Competitions
restent hors du paquet public, mais sont conserves dans leurs sources actuelles.

## Sources figees

Le [contrat JSON](../config/launch-beta/source-binding.json) relie ce ZIP a
65 fichiers hors dependances, extraits sans transformation sous
`config/launch-beta/source/`. Les 33 fichiers des deux modules sont compares
au commit beta `e0ec292dabbb15159d1f0c22aad670aee6cd480e`.
Le verrou, les sept modeles HTML, la configuration exemple et les fichiers
Drupal distribues restants sont conserves octet pour octet.

Cette extraction est un **instantane immuable de revue**, pas une seconde
copie editable du site. Toute correction produit un autre candidat et une
nouvelle qualification ; ne jamais retoucher les fichiers figes ni leur
manifeste pour faire passer un controle. Les fichiers tiers generes, dont les
README Drupal, restent intacts dans leur format upstream. Les regles Git
preservent leurs fins de ligne. Aucune base, compte, cle, configuration active,
dependance vendorisee ou export editorial prive n'est versionne ici.
Les quatorze espaces de fin de ligne signales par Git dans les sept modeles
HTML proviennent du ZIP historique : ils sont conserves pour ne pas changer
ses octets. Le controle d'espaces passe sur les autres fichiers modifies.

```powershell
python scripts/verify_beta_release.py verify
python scripts/verify_beta_release.py verify --archive CHEMIN_DU_ZIP_QUALIFIE
```

La premiere commande controle les sources et l'historique Git. La seconde
compare aussi le ZIP exact, son manifeste et l'ensemble de ses fichiers.
La sortie `sourceCommit` identifie le checkout examine ; tant que les fichiers
ne sont pas commites et revus, cette valeur n'atteste pas leur acceptation.
Le manifeste embarque historique reste inchange, avec ses interdictions de
deploiement. Un recu de correspondance source/archive ne remplace pas les
accords et controles de livraison.

## Securite et CI

Le verrou principal `drupal/composer.lock` passe egalement de 11.4.8 a 11.4.10,
avec les cinq paquets du coeur seulement. Cela permet de tester la branche
de revue sans reinstaller le coeur vulnerable. Les dependances OAuth et le
code Bureau ne changent pas. Ce verrou principal conserve les composants V2 ;
**ce n'est pas le verrou beta**, lequel est extrait separement du ZIP.

Le job `beta-release` de [CI](../.github/workflows/ci.yml) controle les sources,
installe le verrou beta dans une copie jetable, audite les dependances et
verifie la version du coeur. Il ne reconstruit pas le ZIP qualifie et ne
prouve pas son deploiement. Les autres jobs continuent de tester les fonctions
existantes. Les confirmations de revue et de merge restent humaines.

Controles du 16 octobre : 170 tests Node reussis, 57 tests Python reussis
sur 61 (quatre tests POSIX ignores sur Windows), construction/controle du
cockpit et audits Composer des deux verrous reussis. Les metadonnees et
526 liens de fichiers locaux de 15 documents sont verifies ; aucun lien
distant ni ancre n'est atteste par ce controle. Les resultats GitHub restent
a observer sur la tete publiee. Le contrat d'inventaire est lui-meme fige :
modifier un fichier et son empreinte ensemble ne contourne pas le controle.

## Preuves de recette conservees

Les recus du 15 octobre, conserves dans le checkout `competitions-capitaines`,
attestent l'agenda sans compte Google, l'import exact des sept textes edites,
79 controles HTTP locaux et 79 en copie privee o2switch, le rendu mobile et
la restauration SQL des tables de test. Ils ne sont pas recrees par cette branche.
La recette hebergee utilise uniquement une boucle locale ; elle ne prouve
pas le routage Apache/HTTPS de livraison, les courriels reels ou la bascule
du site actif. La copie active de preproduction n'est pas remplacee.

Les contenus edites viennent de l'export prive verifie, jamais des seuls modeles
figes. Aucun script de premiere installation ni base de recette ne doit
remplacer la production existante. Une restauration vers le coeur vulnerable
11.4.8 n'est pas une solution de retour acceptable ; prevoir le maintien ferme
du site si la bascule du candidat corrige echoue.

## Suite en quatre phases

| Phase | Situation et condition |
| --- | --- |
| Cadrage | Perimetre beta confirme, ajouts internes conserves a part. |
| Développement local | Sources figees et controlees ; branche de revue, tests et CI a rattacher au candidat exact. |
| Préproduction | Recette privee du ZIP acquise ; exposition HTTPS protegee de la copie et bascule de retour a qualifier apres accord sur l'operation precise. |
| Mise en production | Non effectuee. Revue/merge humain, cible et recuperation qualifiees, sauvegardes fraiches puis accord exact de livraison ; ouverture distincte. |

Le suivi courant demeure l'iteration `tcl-lancement-beta-20261015` de
`docs/suivi-chantier/suivi-chantier.json` dans le checkout local
`C:/Users/examg/Documents local/Longages/TC_Longages/.worktrees/competitions-capitaines`.
La branche de revue ne remplace pas ce registre ni ses decisions historiques.
Le [recu de cette branche](../data/beta-release-verification.json) porte seulement
les controles propres aux sources et leurs references, pas un second suivi.

TBD : recette Apache/HTTPS et retour de bascule, livraison de courriel reel,
acceptation humaine du candidat corrige et mise en production. La remise a jour
de preproduction avec les travaux recents vient apres lancement autorise.
