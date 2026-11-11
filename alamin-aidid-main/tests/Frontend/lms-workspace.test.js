import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const header = read('../../resources/views/trainer/training/partials/workspace-header.blade.php');
const overview = read('../../resources/views/trainer/training/workspace-overview.blade.php');
const materials = read('../../resources/views/trainer/training/material.blade.php');
const submissions = read('../../resources/views/trainer/training/submission/partials/header.blade.php');
const submissionInbox = read('../../resources/views/trainer/training/submission/partials/task-inbox.blade.php');
const submissionStyles = read('../../resources/views/trainer/training/submission/partials/styles.blade.php');
const submissionScripts = read('../../resources/views/trainer/training/submission/partials/scripts.blade.php');
const learnerTracking = read('../../resources/views/trainer/training/learner-tracking/partials/header.blade.php');
const participants = read('../../resources/views/trainer/training/participants/partials/content.blade.php');
const participantResults = read('../../resources/views/trainer/training/participants/partials/results.blade.php');
const participantStyles = read('../../resources/views/trainer/training/participants/partials/styles.blade.php');
const participantScripts = read('../../resources/views/trainer/training/participants/partials/scripts.blade.php');
const attendance = read('../../resources/views/trainer/training/attendance/partials/content.blade.php');
const attendanceDetail = read('../../resources/views/trainer/training/attendance/partials/detail.blade.php');
const attendanceResults = read('../../resources/views/trainer/training/attendance/partials/results.blade.php');
const attendanceStyles = read('../../resources/views/trainer/training/attendance/partials/styles.blade.php');
const attendanceScripts = read('../../resources/views/trainer/training/attendance/partials/scripts.blade.php');
const paginator = read('../../resources/views/trainer/training/partials/report-paginator.blade.php');

test('all LMS tabs share the stable identity-only header contract', () => {
    assert.match(header, /LMS/);
    assert.doesNotMatch(header, /target_participant|ParticipantTypeLabel::audience|bi-person-badge/);
    assert.doesNotMatch(header, /course_category|bi-tag/);
    assert.doesNotMatch(overview, /Category is|course_category/);
    assert.doesNotMatch(header, /enrolled|accessed|session count|participant count|pending review|course->status/i);
    for (const page of ['overview', 'materials', 'submissions', 'learner-tracking', 'attendance', 'participants']) assert.match(header, new RegExp(`'${page}'`));
    for (const file of [overview, materials, submissions, learnerTracking, participants, attendance]) assert.match(file, /partials\.workspace-header/);
});

test('LMS Overview presents shared attributed activity and the assigned training team', () => {
    assert.match(overview, /Recent Workspace Updates/);
    assert.match(overview, /\$activity->trainer_name/);
    assert.match(overview, /\(You\)/);
    assert.match(overview, /Training Team/);
    assert.match(overview, /\$trainingTeam as \$teamMember/);
    assert.match(overview, /\.lw-team\s*\{[^}]*gap:\s*10px;[^}]*padding:\s*16px 20px;/s);
    assert.match(overview, /No workspace updates yet/);
    assert.match(overview, /Important changes to materials, assessments, submissions, and attendance will appear here/);
    assert.doesNotMatch(overview, /foreach \(\$materials->take\(8\)/);
});

test('Overview retains four metrics with the approved system hover', () => {
    assert.equal((overview.match(/class="lw-stat"/g) || []).length, 4);
    assert.match(overview, /\.lw-stat:hover\s*\{[^}]*translateY\(-3px\)[^}]*#bfdbfe[^}]*0 12px 24px/s);
    assert.doesNotMatch(overview, /\.lw-stat:hover::before/);
});

test('LMS Participants preserves access reporting and uses a server report region', () => {
    assert.equal((participants.match(/class="lw-sum-card"/g) || []).length, 4);
    for (const label of ['Participant List', 'All Gender', 'All Access']) assert.match(participants, new RegExp(label));
    for (const column of ['Access Status', 'Last Accessed']) assert.match(participantResults, new RegExp(column));
    assert.match(participantResults, /partials\.report-paginator/);
    assert.match(paginator, /\[10, 25, 50\]/);
    assert.match(participantStyles, /\.lw-summary\s*\{[^}]*gap:\s*16px[^}]*margin-bottom:\s*24px/s);
    assert.match(participantStyles, /\.lw-sum-card\s*\{[^}]*min-height:\s*120px[^}]*border-radius:\s*16px[^}]*padding:\s*16px 17px[^}]*box-shadow:\s*var\(--lw-shadow\)[^}]*flex-direction:\s*column/s);
    assert.match(participantStyles, /\.lw-sum-icon\s*\{[^}]*width:\s*40px[^}]*height:\s*40px[^}]*margin-bottom:\s*12px[^}]*border-radius:\s*12px/s);
    assert.match(participantStyles, /\.lw-sum-card:hover\s*\{[^}]*translateY\(-3px\)[^}]*#bfdbfe[^}]*0 12px 24px/s);
    assert.match(participantScripts, /setTimeout\(\(\) => load\(urlFromForm\(\), 'replace'\), 300\)/);
    assert.match(participantScripts, /AbortController/);
    assert.match(participantScripts, /__lmsParticipantsInitialized/);
});

test('LMS Participants hides technical codes and exposes one custom search clear control', () => {
    assert.match(participantResults, /participant_name/);
    assert.match(participantResults, /participant_email/);
    assert.doesNotMatch(participantResults, /participant_code|lw-cell-code/);
    assert.match(participants, /input type="search"[^>]*data-lms-search/);
    assert.equal((participants.match(/data-lms-search-clear/g) || []).length, 1);
    assert.match(participantStyles, /\.lw-controls \.lw-search input\[type="search"\]::\-webkit-search-cancel-button\s*\{[^}]*display:\s*none/s);
    assert.match(participantScripts, /search\.value = '';\s*search\.focus\(\)/);
});

test('LMS Attendance roster uses normal page flow and server pagination', () => {
    assert.match(attendance, /Review attendance records and display the session QR code during active sessions\./);
    assert.match(attendance, /trainer\.attendance\.partials\.qr-modal/);
    assert.match(attendanceDetail, /trainer\.attendance\.partials\.qr-panel/);
    assert.match(attendanceResults, /partials\.report-paginator/);
    assert.match(attendanceStyles, /\.lw-table-wrap\s*\{[^}]*overflow-x:\s*auto;[^}]*overflow-y:\s*visible/s);
    assert.doesNotMatch(attendanceStyles, /\.lw-table-wrap\s*\{[^}]*overflow-y:\s*auto/s);
    assert.doesNotMatch(attendanceStyles, /\.lw-att-row\s*\{[^}]*height:\s*calc/s);
    assert.match(attendanceStyles, /\.lw-att-sidebar\s*\{[^}]*position:\s*sticky/s);
    assert.match(attendanceStyles, /\.lw-att-sidebar\s*\{[^}]*max-height:\s*calc\(100vh - [^)]+\)/s);
    assert.match(attendanceStyles, /\.lw-sb-expanded\s*\{[^}]*flex-direction:\s*column[^}]*min-height:\s*0/s);
    assert.match(attendanceStyles, /\.lw-sb-list\s*\{[^}]*flex:\s*1 1 auto[^}]*min-height:\s*0[^}]*overflow-y:\s*auto/s);
    assert.match(attendance, /data-lms-attendance-form/);
    assert.doesNotMatch(attendance, /attendance\.partials\.summary|lw-roster-stats|lw-stat-pill/);
    assert.match(attendanceScripts, /AbortController/);
    assert.match(attendanceScripts, /__lmsAttendanceInitialized/);
});

test('Attendance roster exposes one custom clear control and suppresses only its native search cancel UI', () => {
    assert.match(attendance, /lw-roster-filters[\s\S]*?input type="search"[^>]*data-lms-search/);
    assert.equal((attendance.match(/data-lms-search-clear/g) || []).length, 1);
    assert.match(attendanceStyles, /\.lw-roster-filters \.lw-search input\[type="search"\]::\-webkit-search-cancel-button\s*\{[^}]*display:\s*none/s);
    assert.match(attendanceScripts, /search\.value = '';\s*search\.focus\(\)/);
    assert.match(attendanceScripts, /setTimeout\(\(\) => load\(urlFromForm\(\), 'replace'\), 300\)/);
    assert.match(attendanceScripts, /AbortController/);
    assert.match(attendance, /lw-sb-search[\s\S]*?input type="search"[^>]*data-session-search/);
    assert.equal((attendance.match(/data-session-search-clear/g) || []).length, 1);
    assert.match(attendance, /data-session-search-clear[^>]*aria-label="Clear session search"[^>]*hidden/s);
    assert.match(attendanceStyles, /\.lw-sb-search input\[type="search"\]::\-webkit-search-cancel-button\s*\{[^}]*display:\s*none/s);
    assert.match(attendanceScripts, /sessionSearch\.value = '';\s*sessionSearch\.focus\(\);\s*setSessionClearVisibility\(\);\s*filterSessions\(\)/);
    assert.match(attendanceScripts, /sessionSearch\?\.addEventListener\('input'/);
    assert.match(participantStyles, /\.lw-controls \.lw-search input\[type="search"\]::\-webkit-search-cancel-button\s*\{[^}]*display:\s*none/s);
});

test('Attendance collapsed session navigation is one compact control and mobile returns to document flow', () => {
    assert.match(attendance, /aria-label="Show sessions"[^>]*title="Show sessions"/);
    assert.equal((attendance.match(/aria-label="Show sessions"/g) || []).length, 1);
    assert.doesNotMatch(attendance, /lw-sb-collapsed-icon|lw-sb-collapsed-text/);
    assert.doesNotMatch(attendanceStyles, /writing-mode|rotate\(180deg\)/);
    assert.match(attendanceStyles, /\.lw-att-sidebar\.is-collapsed\s*\{[^}]*width:\s*44px[^}]*background:\s*transparent[^}]*box-shadow:\s*none/s);
    for (const style of [attendanceStyles, submissionStyles]) {
        assert.match(style, /width:\s*44px;\s*height:\s*42px;[^}]*border:\s*1px solid var\(--lw-border\);[^}]*border-radius:\s*10px;[^}]*background:\s*#fff;[^}]*color:\s*var\(--lw-primary\);[^}]*box-shadow:\s*var\(--lw-shadow\)/s);
        assert.match(style, /border-color:\s*#93c5fd;[^}]*box-shadow:\s*0 8px 22px rgba\(37, 99, 235, \.12\);[^}]*translateY\(-1px\)/s);
        assert.match(style, /prefers-reduced-motion:\s*reduce/);
    }
    assert.match(attendanceStyles, /\.lw-sb-expanded\s*\{[^}]*opacity:\s*1[^}]*visibility:\s*visible[^}]*transition:[^}]*opacity[^}]*transform[^}]*visibility/s);
    assert.match(attendanceStyles, /\.lw-att-sidebar\.is-collapsed \.lw-sb-expanded\s*\{[^}]*opacity:\s*0[^}]*visibility:\s*hidden[^}]*visibility 0s linear \.18s/s);
    assert.doesNotMatch(attendanceStyles, /\.lw-att-sidebar\.is-collapsed \.lw-sb-expanded\s*\{[^}]*display:\s*none/s);
    assert.doesNotMatch(submissionStyles, /\.lw-inbox\.is-collapsed \.lw-inbox-expanded\s*\{[^}]*display:\s*none/s);
    assert.match(attendanceScripts, /toggleAttribute\('inert', collapsed\)/);
    assert.match(submissionScripts, /toggleAttribute\('inert', collapsed\)/);
    assert.match(attendanceStyles, /@media \(max-width:\s*900px\)[\s\S]*?\.lw-att-sidebar[^\{]*\{[^}]*position:\s*static[^}]*max-height:\s*none[\s\S]*?\.lw-sb-list\s*\{[^}]*overflow:\s*visible/s);
    const collapsedMarkup = attendance.match(/<div class="lw-sb-collapsed">([\s\S]*?)<\/div>/)?.[1] || '';
    assert.doesNotMatch(collapsedMarkup, /calendar|Calendar View/i);
    assert.equal((submissionInbox.match(/aria-label="Show submission tasks"/g) || []).length, 1);
});

test('Calendar open and close are deterministic, accessible, and restore focus', () => {
    assert.match(header, /data-calendar-open/);
    assert.match(attendance, /role="dialog"[^>]*aria-modal="true"[^>]*aria-hidden="true"/);
    assert.match(attendanceStyles, /\.lw-modal-overlay\s*\{[^}]*transition:[^}]*opacity[^}]*visibility/s);
    assert.match(attendanceStyles, /\.lw-modal-overlay\.is-open/);
    assert.match(attendanceScripts, /calendarOpener/);
    assert.match(attendanceScripts, /calendarOpener\.focus\(\)/);
    assert.match(attendanceScripts, /event\.key === 'Escape'/);
    assert.doesNotMatch(attendanceScripts, /openCalendar[^;]+setTimeout|closeCalendar[^;]+setTimeout/s);
});
