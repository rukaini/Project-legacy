import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

test('the extracted submission script contains valid JavaScript', () => {
    const path = '../../resources/views/trainer/training/submission/partials/scripts.blade.php';
    const blade = readFileSync(new URL(path, import.meta.url), 'utf8');
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)?.[1];

    assert.ok(script, 'Expected the submission script partial to contain a script block.');
    assert.doesNotThrow(() => new vm.Script(script));
});
