import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const scriptPartials = [
    '../../resources/views/trainer/training/participants/partials/scripts.blade.php',
    '../../resources/views/trainer/training/attendance/partials/scripts.blade.php',
];

for (const path of scriptPartials) {
    test(`${path} contains valid JavaScript`, () => {
        const blade = readFileSync(new URL(path, import.meta.url), 'utf8');
        const scripts = [...blade.matchAll(/<script(?:\s[^>]*)?>([\s\S]*?)<\/script>/g)]
            .map((match) => match[1])
            .filter((script) => !script.includes('{!! json_encode('));

        assert.ok(scripts.length > 0, `Expected ${path} to contain an executable script block.`);

        for (const script of scripts) {
            const normalized = script.replace(
                /'\{\{\s*\$initialCalendarDate\s*\}\}T00:00:00'/g,
                "'2026-07-30T00:00:00'"
            );
            assert.doesNotThrow(() => new vm.Script(normalized));
        }
    });
}
