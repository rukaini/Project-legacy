import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

test('trainer material authoring inline script has valid JavaScript syntax', () => {
    const blade = readFileSync(
        new URL('../../resources/views/trainer/training/partials/material-authoring-scripts.blade.php', import.meta.url),
        'utf8',
    );
    const scripts = [...blade.matchAll(/<script>([\s\S]*?)<\/script>/g)];
    const authoringScript = scripts.at(-1)?.[1]
        .replace("'{{ csrf_token() }}'", "'test-csrf-token'");

    assert.ok(authoringScript, 'Expected to find the authoring workspace script.');
    assert.doesNotThrow(() => new vm.Script(authoringScript));
});
