import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const scripts = [
    '../../resources/views/trainer/simple/overview/partials/scripts.blade.php',
    '../../resources/views/trainer/simple/participants/partials/scripts.blade.php',
    '../../resources/views/trainer/simple/materials/partials/scripts.blade.php',
    '../../resources/views/trainer/simple/attendance/partials/scripts.blade.php',
];

for (const path of scripts) {
    test(`${path} contains valid JavaScript`, () => {
        const blade = readFileSync(new URL(path, import.meta.url), 'utf8');
        const script = blade.match(/<script>([\s\S]*?)<\/script>/)?.[1];

        assert.ok(script, `Expected a script block in ${path}.`);
        assert.doesNotThrow(() => new vm.Script(script));
    });
}
