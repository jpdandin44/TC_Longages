import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const context = vm.createContext({ window: {}, Uint8Array, DataView, Blob });
vm.runInContext(readFileSync(new URL('../src/communication-store.js', import.meta.url), 'utf8'), context);
vm.runInContext(readFileSync(new URL('../src/communication-images.js', import.meta.url), 'utf8'), context);
const images = context.window.TCLCommunicationImages;
function png(width = 100, height = 100) {
  const bytes = Buffer.alloc(32); bytes.set([137, 80, 78, 71, 13, 10, 26, 10]);
  bytes.write('IHDR', 12); bytes.writeUInt32BE(width, 16); bytes.writeUInt32BE(height, 20); return bytes;
}
test('L’analyse vérifie la signature et les dimensions avant le décodage navigateur', () => {
  assert.equal(images.inspect(png()), 'image/png');
  const jpeg = Buffer.from([255,216,255,192,0,17,8,0,100,0,100,3,1,0,0,2,0,0,3,0,0]);
  assert.equal(images.inspect(jpeg), 'image/jpeg');
  const webp = Buffer.alloc(30); webp.write('RIFF', 0); webp.write('WEBP', 8); webp.write('VP8X', 12); webp[24] = 99; webp[27] = 99;
  assert.equal(images.inspect(webp), 'image/webp');
  for (const bytes of [Buffer.from('<svg onload="alert(1)"></svg>'), Buffer.from('fake jpeg header!'), png(20000, 10), png(8000, 8000), png(0, 10)]) assert.throws(() => images.inspect(bytes));
});
test('Le fichier surdimensionné ou déguisé échoue sans décodage ni écriture', async () => {
  await assert.rejects(images.prepare({ size: 8_000_001, arrayBuffer: async () => new ArrayBuffer(0) }), /8 Mo/);
  const bytes = png();
  await assert.rejects(images.prepare({ size: bytes.length, type: 'image/jpeg', arrayBuffer: async () => bytes.buffer.slice(bytes.byteOffset, bytes.byteOffset + bytes.byteLength) }), /format annoncé/);
  await assert.rejects(images.prepare({ size: 10, type: 'image/jpeg', arrayBuffer: async () => new ArrayBuffer(10) }), /n’est pas une image/);
});
