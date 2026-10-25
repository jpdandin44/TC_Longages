---
project: TC_Longages
document_type: dependency-provenance
title: Provenance du moteur d’aperçu PDF
status: active
version: git
created: 2026-10-19
updated: 2026-10-19
owner: jpdandin
tags: [pdf, dependance, licence]
---

# Provenance PDF.js

Source : distribution npm `pdfjs-dist@6.4.299`, projet Mozilla PDF.js. Archive de travail conservée hors Git dans `.local/pdfjs-dist-6.4.299.tgz` à la racine du checkout V2. Licence conservée dans `LICENSE` et licences des ressources dans leurs répertoires respectifs.

Les fichiers `build/pdf.mjs` et `build/pdf.worker.mjs` sont copiés sous les noms `pdf.js` et `pdf.worker.js` pour leur service par le routeur local. Les répertoires `cmaps`, `standard_fonts` et `wasm` accompagnent le moteur. Aucun CDN requis au rendu.

Utilisation : aperçu de la première page sur canvas, avec worker local et évaluation dynamique désactivée. Le PDF original reste conservé. Intégration locale non commitée à la clôture ; qualification complète et installation préproduction à effectuer. Référence technique consultée : [exemples officiels PDF.js](https://mozilla.github.io/pdf.js/examples/).

Contrôle du 19 octobre : 201 fichiers comparés octet par octet à l’archive npm. SHA-256 de cette archive : `86269b40170eb41740ea05ad2102d71d33de4f122b34eb2f478be0ea7779c415`.
