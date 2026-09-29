---
project: framework-developpement-pilote
document_type: delivery-index
title: Dossier de duplication du framework de développement
status: proposed
version: git
created: 2026-09-29
updated: 2026-09-29
owner: jpdandin
tags: [framework, livraison, documentation]
---

# Dossier de duplication

Commencer par le [document central](framework-developpement-pilote.md). Il contient le processus complet, son origine AVEREO, les invariants, les phases, la revue, les environnements, les accès, les actions, les sauvegardes et les modèles.

- [Profil JSON vierge](modele-projet.json) et [schéma](schema-projet.json).
- [Suivi de chantier vierge](modeles/docs/suivi-chantier/suivi-chantier.json).
- [Règles pour l’agent](modeles/AGENTS.md) et [template PR](modeles/.github/PULL_REQUEST_TEMPLATE.md).
- [Inventaire des sources auditées](sources-audit.json).
- [Rapport des contrôles réellement effectués](controles-livraison.json).

Les exemples d’Actions se trouvent dans `modeles/.github/workflows/` et portent l’extension `.example`. Ils demandent l’implémentation des adaptateurs et le remplacement des paramètres avant activation. Aucun nouveau dépôt, environnement, compte, secret, serveur ou déploiement n’a été créé.

## Vérifier les modèles localement

Avec Python 3.10 ou ultérieur, créer un environnement de vérification isolé et installer les dépendances indiquées dans `requirements-verification.txt`. Puis :

```text
python valider-modele.py --self-test
```

Le vérificateur lit uniquement le profil, son schéma et le suivi vierge. Il contrôle leur structure et plusieurs invariants ; il n’authentifie pas les accords et ne contacte aucun service. Les tests négatifs portent sur des copies en mémoire et ne créent aucune décision.

Après placement dans un nouveau dépôt, adapter les chemins du profil et utiliser au besoin `--profile`, `--schema` et `--tracker`. La mise en place du nouveau moteur et de ses adaptateurs se fait selon la procédure de duplication du document central.

## Sources et versionnage

Le Markdown est la référence documentaire. Le profil JSON représente sa configuration machine ; le suivi JSON conserve les événements d’une instance. Les modèles extraits correspondent aux annexes du document central ; les maintenir depuis cette référence tant que ce paquet reste un modèle. Après adoption par un projet, ses fichiers deviennent ses sources propres, sous Git.

`MANIFEST.sha256` permet de comparer l’intégrité des fichiers distribués. Il ne signe pas leur authenticité.

## État documentaire

Dossier proposé à l’adoption, vérifié dans le périmètre détaillé par le rapport. Les TBD des nouveaux projets restent volontairement ouverts. Les projets AVEREO ont été consultés sans mutation ; les limites historiques restent exposées dans le document central.

