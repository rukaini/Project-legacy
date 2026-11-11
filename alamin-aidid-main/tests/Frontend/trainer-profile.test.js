import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

function read(path) {
    return readFileSync(new URL(path, import.meta.url), 'utf8');
}

const profileView = read('../../resources/views/trainer/profile.blade.php');
const content = read('../../resources/views/trainer/profile/partials/content.blade.php');
const account = read('../../resources/views/trainer/profile/partials/account-panel.blade.php');
const summary = read('../../resources/views/trainer/profile/partials/summary.blade.php');
const certificates = read('../../resources/views/trainer/profile/partials/certificates-panel.blade.php');
const documents = read('../../resources/views/trainer/profile/partials/documents-panel.blade.php');
const styles = read('../../resources/views/trainer/profile/partials/styles.blade.php');
const scriptsBlade = read('../../resources/views/trainer/profile/partials/scripts.blade.php');
const scripts = scriptsBlade.match(/<script>([\s\S]*?)<\/script>/)?.[1];

test('Trainer Profile exposes only the professional Profile, Certificates, and Documents workspace', () => {
    assert.match(content, /View your trainer account details, manage your certificates, and access official documents\./);
    assert.equal((content.match(/role="tab"/g) || []).length, 3);
    assert.match(content, /role="tablist" aria-label="Trainer profile sections"/);
    assert.match(content, /data-tab="profile"/);
    assert.match(content, /data-tab="certificate"/);
    assert.match(content, /data-tab="documents"/);
    assert.doesNotMatch(content, /data-tab="security"|Security|Change Password|Update Password/);

    assert.match(account, /pf-identity-name[\s\S]*?pf-identity-email/);
    assert.match(account, /trainer\.profile\.partials\.summary/);
    assert.match(account, /Account Details/);
    assert.doesNotMatch(account, /pf-avatar|trainer_pic|pf-badge|Account Status|managed by the admin office/);
    assert.equal((summary.match(/data-trainer-metric=/g) || []).length, 4);
    assert.match(summary, /Trainer Summary/);
    assert.match(summary, /Trainings/);
    assert.match(summary, /Proposals/);
    assert.match(summary, /Certificates/);
    assert.match(summary, /Feedback Rating/);
    assert.doesNotMatch(summary, /bi-star-fill|bi-star-half/);

    assert.doesNotMatch(profileView, /trainerPicUrl|trainerInitial|trainerStatus|statusKey|current_password|new_password/);
});

test('Trainer Profile preserves certificate and official-document controls', () => {
    assert.match(certificates, /route\('trainer\.profile\.certificate'\)/);
    assert.match(certificates, /name="title"/);
    assert.match(certificates, /name="certificate"/);
    assert.match(certificates, /PDF, DOC, DOCX, JPG, PNG/);
    assert.match(certificates, /Max 10 MB/);
    assert.match(certificates, /trainer\.profile\.certificate\.remove/);
    assert.match(certificates, /> View\s*</);
    assert.match(certificates, /> Remove\s*</);
    assert.match(certificates, /No certificates uploaded yet/);

    assert.match(documents, /id="docSearch"/);
    assert.match(documents, /id="docSort"/);
    assert.match(documents, /data-title=/);
    assert.match(documents, /data-type=/);
    assert.match(documents, /data-ts=/);
    assert.doesNotMatch(documents, /pf-doc-badge|document_type\s*\?:\s*'Document'/);
    assert.match(documents, /trainer\.profile\.document\.view/);
    assert.match(documents, /View \/ Download/);
    assert.match(documents, /No documents yet/);
});

test('Trainer Profile scripts retain accessible tabs, certificate feedback, and document tools without photo or password code', () => {
    assert.ok(scripts);
    assert.doesNotThrow(() => new vm.Script(scripts));
    assert.match(scripts, /setAttribute\('aria-selected'/);
    assert.match(scripts, /\['ArrowLeft', 'ArrowRight', 'Home', 'End'\]/);
    assert.match(scripts, /panel\.hidden = !active/);
    assert.match(scripts, /pfCertFileInput/);
    assert.match(scripts, /function docGetFiltered\(\)/);
    assert.match(scripts, /function docRender\(\)/);
    assert.match(scripts, /docSearch\.addEventListener\('input'/);
    assert.match(scripts, /docSort\.addEventListener\('change'/);
    assert.doesNotMatch(scripts, /pfCamBtn|pfPicInput|pfAvatar|FileReader|pf-pass-toggle|new_password|pwStrength/);
});

test('Trainer Profile styles provide approved card hover and responsive profile controls without dead photo or security selectors', () => {
    assert.match(styles, /\.pf-stat:hover,[\s\S]*?\.pf-detail-item:hover[\s\S]*?translateY\(-3px\)/);
    assert.match(styles, /\.pf-cert-card:hover,[\s\S]*?\.pf-doc-card:hover/);
    assert.match(styles, /\.pf-tab-btn:focus-visible/);
    assert.match(styles, /@media \(max-width: 991\.98px\)/);
    assert.match(styles, /@media \(max-width: 767\.98px\)/);
    assert.match(styles, /@media \(max-width: 575\.98px\)/);
    assert.match(styles, /prefers-reduced-motion: reduce/);
    assert.doesNotMatch(styles, /zoom\s*:|pf-avatar|pf-pic-|pf-badge|pf-managed-note|pf-pass|pf-strength|pf-info-box|pf-form-grid/);
    assert.doesNotMatch(styles, /\.pf-stat\s*\{[^}]*cursor:\s*pointer/s);
    assert.doesNotMatch(styles, /\.pf-detail-item\s*\{[^}]*cursor:\s*pointer/s);
});
