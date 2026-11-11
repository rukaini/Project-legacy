import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const header = read('../../resources/views/trainer/simple/partials/header.blade.php');
const commonStyles = read('../../resources/views/trainer/simple/partials/workspace-styles.blade.php');
const paginator = read('../../resources/views/trainer/simple/partials/paginator.blade.php');
const reportScripts = read('../../resources/views/trainer/simple/partials/report-scripts.blade.php');
const overview = read('../../resources/views/trainer/simple/overview/partials/session-spotlight.blade.php');
const overviewSidebar = read('../../resources/views/trainer/simple/overview/partials/sidebar.blade.php');
const overviewScripts = read('../../resources/views/trainer/simple/overview/partials/scripts.blade.php');
const materials = read('../../resources/views/trainer/simple/materials/partials/content.blade.php');
const materialResults = read('../../resources/views/trainer/simple/materials/partials/results.blade.php');
const attendance = read('../../resources/views/trainer/simple/attendance/partials/content.blade.php');
const attendanceResults = read('../../resources/views/trainer/simple/attendance/partials/results.blade.php');
const participants = read('../../resources/views/trainer/simple/participants/partials/content.blade.php');
const participantResults = read('../../resources/views/trainer/simple/participants/partials/results.blade.php');

test('Simple Training uses one shared header with the exact final tab order', () => {
    const overviewIndex = header.indexOf("'overview'");
    const materialsIndex = header.indexOf("'materials'");
    const attendanceIndex = header.indexOf("'attendance'");
    const participantsIndex = header.indexOf("'participants'");
    assert.ok(overviewIndex < materialsIndex);
    assert.ok(materialsIndex < attendanceIndex);
    assert.ok(attendanceIndex < participantsIndex);
    assert.match(header, /data-simple-training-header/);
    assert.match(header, /aria-current="page"/);
    assert.match(header, /Simple Training/);
    assert.doesNotMatch(header, /course_category|bi-tag/);
    assert.doesNotMatch(header + overviewSidebar, /target_participant|ParticipantTypeLabel::audience|bi-person-badge/);
    assert.doesNotMatch(header, /course->status|bi-check-circle/);
});

test('Simple Overview keeps co-trainer identity lightweight without adding an activity feed', () => {
    assert.match(overviewSidebar, /Training Team/);
    assert.match(overviewSidebar, /\$trainingTeam as \$teamMember/);
    assert.match(overviewSidebar, /sw-team-you/);
    assert.doesNotMatch(overview + overviewSidebar, /Recent Workspace Updates|Activity Feed/);
});

test('Overview prioritizes one current or next session and keeps past history collapsed and batched', () => {
    assert.match(overview, /\$featuredLabel/);
    assert.match(overview, /Current Session|Happening now/);
    assert.match(overview, /No upcoming session/);
    assert.match(overview, /data-past-toggle/);
    assert.match(overview, /aria-expanded="false"/);
    assert.match(overview, /\$index >= 5/);
    assert.match(overview, /Show more past sessions/);
    assert.match(overviewScripts, /data-show-more-past/);
    assert.doesNotMatch(overview, /Rows|10,\s*25,\s*50|data-simple-paginator/);
});

test('Materials keeps upload state outside its async result region and supports scalable controls', () => {
    assert.match(materials, /id="simpleMaterialUploadForm"/);
    assert.match(materials, /id="materialFile"/);
    assert.match(materials, /data-simple-report-form/);
    assert.match(materials, /Search Materials/);
    assert.match(materials, /Newest first/);
    assert.match(materials, /Oldest first/);
    assert.match(materials, /Name A–Z/);
    assert.match(materials, /All Types/);
    assert.match(materialResults, /id="simpleMaterialResults"/);
    assert.doesNotMatch(materialResults, /simpleMaterialUploadForm|materialFile/);
    assert.match(materialResults, /partials\.paginator/);
});

test('Attendance preserves its read-only report design and scoped controls without a count strip', () => {
    assert.match(attendance, /Review attendance records and display the session QR code during active sessions\./);
    assert.match(attendance, /Viewing Session/);
    assert.match(attendance, /Search by name or email/);
    assert.match(attendance, /All Records/);
    assert.match(attendance, /Read only/);
    for (const status of ['Recorded', 'Not Recorded']) assert.match(attendanceResults, new RegExp(status));
    for (const unsupported of ['Present', 'Late', 'Absent']) assert.doesNotMatch(attendanceResults, new RegExp(`>${unsupported}<`));
    assert.doesNotMatch(attendanceResults, /sw-attendance-counts|class="sw-count/);
    assert.match(attendance, /display_status/);
    assert.match(attendanceResults, /display_status_class/);
    assert.match(attendanceResults, /display_status/);
    assert.match(attendanceResults, /partials\.paginator/);
    assert.match(attendance, /trainer\.attendance\.partials\.qr-modal/);
    assert.match(attendanceResults, /trainer\.attendance\.partials\.qr-panel/);
    assert.doesNotMatch(attendance + attendanceResults, /Mark Attendance|Edit Attendance|attendance_status.*<select/i);
});

test('Participants follows the LMS visual hierarchy using only Simple Training data', () => {
    assert.equal((participants.match(/class="sw-summary-card"/g) || []).length, 2);
    for (const label of ['Total Participants', 'Gender Split']) assert.match(participants, new RegExp(label));
    assert.match(participants, /Participant List/);
    assert.match(participants, /Search name, email or phone/);
    assert.match(participants, /All Gender/);
    assert.doesNotMatch(participants, /Participant Type|All Type/);
    assert.match(participants, /range\('A', 'Z'\)/);
    assert.match(participantResults, /<th>Participant<\/th><th>Email<\/th><th>Phone Number<\/th><th>Organisation \/ Department<\/th>/);
    assert.match(participantResults, /PhoneNumberPresenter::display/);
    assert.doesNotMatch(participantResults, /<th>Type<\/th>|<th>Enrolled<\/th>|RSVP/i);
    assert.doesNotMatch(participants + participantResults, /Access Status|Last Accessed|LMS progress|engagement/i);
});

test('Participant metric cards reuse the approved standard system hover treatment', () => {
    assert.equal((participants.match(/class="sw-summary-card"/g) || []).length, 2);
    assert.match(commonStyles, /\.sw-summary-card\s*\{[^}]*cursor:\s*default[^}]*transition:\s*\.15s ease/s);
    assert.match(commonStyles, /\.sw-summary-card:hover\s*\{[^}]*translateY\(-3px\)[^}]*border-color:\s*#bfdbfe[^}]*0 12px 24px rgba\(15, 23, 42, \.08\)/s);
    assert.doesNotMatch(commonStyles, /\.sw-summary-card:hover[^}]*rotate|\.sw-summary-card:hover[^}]*scale/s);
});

test('All report lists share server pagination with exactly 10 25 50 and visible one-page controls', () => {
    assert.match(paginator, /\[10, 25, 50\]/);
    assert.match(paginator, /\$paginator->total\(\) > 0/);
    assert.match(paginator, /Previous/);
    assert.match(paginator, /Next/);
    assert.match(paginator, /Showing/);
    assert.match(reportScripts, /\[data-simple-rows\]/);
    assert.doesNotMatch(reportScripts, /PAGE_SIZE|\.slice\(/);
});

test('Shared search is debounced, abortable, caret-safe, clearable, and history-aware', () => {
    assert.match(reportScripts, /setTimeout\(\(\) => load\(formUrl\(\), 'replace'\), 300\)/);
    assert.match(reportScripts, /AbortController/);
    assert.match(reportScripts, /requestNumber/);
    assert.match(reportScripts, /region\.replaceWith\(replacement\)/);
    assert.match(reportScripts, /history\.replaceState/);
    assert.match(reportScripts, /history\.pushState/);
    assert.match(reportScripts, /popstate/);
    assert.match(reportScripts, /search\.focus\(\)/);
    assert.match(reportScripts, /window\.__simpleTrainingReportsInitialized/);
    assert.doesNotMatch(reportScripts, /setSelectionRange|window\.location\.href\s*=|document\.body.*loading/);
});

test('Simple tabs reuse one system style and responsive paginator language', () => {
    for (const name of ['overview', 'materials', 'attendance', 'participants']) {
        const styles = read('../../resources/views/trainer/simple/' + name + '/partials/styles.blade.php');
        assert.match(styles, /trainer\.simple\.partials\.workspace-styles/);
    }
    assert.match(commonStyles, /\.sw-summary-card/);
    assert.match(commonStyles, /\.sw-report-toolbar/);
    assert.match(commonStyles, /\.sw-search-clear/);
    assert.match(commonStyles, /@media \(max-width: 991\.98px\)/);
    assert.match(commonStyles, /@media \(max-width: 767\.98px\)/);
    assert.match(commonStyles, /\.sw-tabs[^}]*overflow-x:\s*auto/);
});
