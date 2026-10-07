import test from 'node:test';
import assert from 'node:assert/strict';
import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';

const root = fileURLToPath(new URL('../', import.meta.url));
const phpAvailable = spawnSync('php', ['-v'], {stdio:'ignore', windowsHide:true}).status === 0;

test('L’éditeur Drupal conserve les textes et la photo PNG de grande taille', {skip:!phpAvailable}, () => {
  const result = spawnSync('php', ['tests/public-page-text.php'], {cwd:root, encoding:'utf8', windowsHide:true});
  assert.equal(result.status, 0, result.stderr || result.stdout);
  const proof = JSON.parse(result.stdout);
  assert.equal(proof.passed, true);
  assert.equal(proof.roundTripPreserved, true);
  assert.ok(proof.bytes > 2_000_000);
  assert.ok(proof.blocks >= 10);
});
