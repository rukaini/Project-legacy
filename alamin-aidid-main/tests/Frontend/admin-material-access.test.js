import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const content = readFileSync(new URL('../../resources/views/admin/courses/partials/content.blade.php', import.meta.url), 'utf8');
const scripts = readFileSync(new URL('../../resources/views/admin/courses/partials/scripts.blade.php', import.meta.url), 'utf8');
const styles = readFileSync(new URL('../../resources/views/admin/courses/partials/styles.blade.php', import.meta.url), 'utf8');

test('Training Overview renders persisted participant material access controls', () => {
    assert.match(scripts, /Participant Material Access/);
    assert.match(scripts, /data-material-access-enabled/);
    assert.match(scripts, /name="access_mode" value="no_expiry"/);
    assert.match(scripts, /name="access_mode" value="until"/);
    assert.match(scripts, /type="datetime-local" name="access_until"/);
    assert.match(scripts, /training\.materialAccessUpdateUrl/);
    assert.match(scripts, /access\.accessUntilInput/);
});

test('Material access controls react to disabled, duration, and submission states', () => {
    assert.match(scripts, /duration\.hidden = !enabled/);
    assert.match(scripts, /until\.disabled = !enabled \|\| !untilMode \|\| !untilMode\.checked/);
    assert.match(scripts, /toggle\.setAttribute\('aria-checked'/);
    assert.match(scripts, /button\.disabled = true/);
    assert.match(scripts, /Saving\.\.\./);
    assert.match(content, /\$errors->materialAccess->any\(\)/);
    assert.doesNotMatch(content, /session\('success'\)/);
});

test('Material access card includes responsive and status styling', () => {
    assert.match(styles, /\.tm-material-access/);
    assert.match(styles, /\.tm-access-status\.expired/);
    assert.match(styles, /\.tm-access-duration/);
    assert.match(styles, /@media \(max-width: 767\.98px\)[\s\S]*\.tm-access-duration \{ grid-template-columns: 1fr; \}/);
});
