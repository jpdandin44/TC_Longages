import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { inline } from './inline-html.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
const source = path.join(root, 'src');
const prototypeBlock = /<!-- PROTOTYPE:START -->[\s\S]*?<!-- PROTOTYPE:END -->/g;
const index = await readFile(path.join(source, 'index.html'), 'utf8');
const editor = await readFile(path.join(source, 'communication.html'), 'utf8');
const outputs = {
  'dist/index.html': await inline(index),
  'dist/communication.html': await inline(editor),
  'release/index.html': await inline(index.replace(prototypeBlock, ''))
};
for (const page of ['bureau', 'inscriptions', 'actualites-bureau']) {
  outputs[`dist/${page}.html`] = await inline(await readFile(path.join(source, `${page}.html`), 'utf8'));
}
const manifest = { mode: 'local-only', outputs: {} };
for (const [relative, content] of Object.entries(outputs)) {
  await mkdir(path.dirname(path.join(root, relative)), { recursive: true });
  await writeFile(path.join(root, relative), content, 'utf8');
  manifest.outputs[relative] = { bytes: Buffer.byteLength(content), sha256: createHash('sha256').update(content).digest('hex') };
}
await mkdir(path.join(root, 'data'), { recursive: true });
await writeFile(path.join(root, 'data/build-manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
console.log('Prototype et pages du bureau générés dans dist/. Le serveur contrôle les accès. Vitrine seule dans release/. Aucun transfert externe.');
