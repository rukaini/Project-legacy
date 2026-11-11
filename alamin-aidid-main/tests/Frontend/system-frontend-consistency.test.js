import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

const participantMapper = read('../../app/Support/ParticipantTypeLabel.php');
const sharedTrainingSchema = read('../../app/Support/SharedTrainingSchema.php');
const simpleReports = read('../../app/Services/SimpleTraining/TrainerSimpleTrainingReportService.php');
const attendanceReports = read('../../app/Services/Analytics/ParticipantAttendanceReportService.php');
const adminMonitoring = read('../../app/Http/Controllers/Admin/MonitoringController.php');
const adminDirectory = read('../../app/Http/Controllers/Admin/DirectoryController.php');
const adminDirectoryView = read('../../resources/views/admin/directory/partials/content.blade.php');
const adminTrainingScripts = read('../../resources/views/admin/courses/partials/scripts.blade.php');
const trainerProposalDetail = read('../../resources/views/trainer/training/proposal/partials/detail.blade.php');

test('participant integration keys are preserved while every report path uses English display labels', () => {
    assert.match(participantMapper, /\['outsider', 'public'\][\s\S]*?return 'Public'/);
    assert.match(participantMapper, /'guru new', 'guru baru', 'guru_new'[\s\S]*?return 'New Teacher'/);

    assert.match(simpleReports, /ParticipantTypeLabel::display\(\$participant->participant_type\)/);
    assert.doesNotMatch(simpleReports, /PARTICIPANT_TYPE_LABELS/);
    assert.match(attendanceReports, /ParticipantTypeLabel::display\(\$participant->participant_type\)/);
    assert.match(sharedTrainingSchema, /'Unknown New Teacher'/);
    assert.doesNotMatch(attendanceReports, /Unknown Guru New/);
    assert.match(adminMonitoring, /ParticipantTypeLabel::display\(\$typeValue\)/);
    assert.match(adminDirectory, /ParticipantTypeLabel::display\(\$type\)/);
    assert.match(trainerProposalDetail, /ParticipantTypeLabel::display\(\$value\)/);

    assert.match(adminDirectoryView, /<option value="Guru Baru">New Teacher<\/option>/);
    assert.match(adminDirectoryView, /<option value="Outsider">Public<\/option>/);
    assert.doesNotMatch(adminDirectoryView, />Outsider<\/option>|>Guru Baru<\/option>/);
    assert.match(adminTrainingScripts, /participant\.typeLabel/);
});

test('all major Admin and Trainer layout families retain responsive structural contracts', () => {
    const responsiveStyles = [
        '../../resources/views/admin/partials/system-styles.blade.php',
        '../../resources/views/admin/activity/partials/styles.blade.php',
        '../../resources/views/admin/proposals/partials/styles.blade.php',
        '../../resources/views/admin/courses/partials/styles.blade.php',
        '../../resources/views/admin/directory/partials/styles.blade.php',
        '../../resources/views/admin/analytics/partials/styles.blade.php',
        '../../resources/views/admin/profile/partials/styles.blade.php',
        '../../resources/views/trainer/training/my-training.blade.php',
        '../../resources/views/trainer/feedback/partials/styles.blade.php',
        '../../resources/views/trainer/training/proposal/partials/styles.blade.php',
        '../../resources/views/trainer/profile/partials/styles.blade.php',
        '../../resources/views/trainer/training/partials/workspace-header-styles.blade.php',
        '../../resources/views/trainer/training/partials/material-authoring-styles.blade.php',
        '../../resources/views/trainer/training/submission/partials/styles.blade.php',
        '../../resources/views/trainer/training/learner-tracking/partials/styles.blade.php',
        '../../resources/views/trainer/training/attendance/partials/styles.blade.php',
        '../../resources/views/trainer/training/participants/partials/styles.blade.php',
        '../../resources/views/trainer/training/analytics/partials/styles.blade.php',
        '../../resources/views/trainer/simple/partials/workspace-styles.blade.php',
    ];

    for (const path of responsiveStyles) {
        const styles = read(path);
        assert.match(styles, /@media \(max-width:/, `${path} needs a narrow-viewport contract`);
        assert.doesNotMatch(styles, /devicePixelRatio|(?:^|[;{])\s*zoom\s*:/, `${path} must not detect or force browser zoom`);
    }

    const tableStyles = [
        '../../resources/views/admin/partials/system-styles.blade.php',
        '../../resources/views/admin/directory/partials/styles.blade.php',
        '../../resources/views/admin/analytics/partials/styles.blade.php',
        '../../resources/views/trainer/simple/partials/workspace-styles.blade.php',
        '../../resources/views/trainer/training/attendance/partials/styles.blade.php',
        '../../resources/views/trainer/training/participants/partials/styles.blade.php',
        '../../resources/views/trainer/training/submission/partials/styles.blade.php',
        '../../resources/views/trainer/training/analytics/partials/styles.blade.php',
    ];

    for (const path of tableStyles) {
        assert.match(read(path), /overflow-x:\s*auto/, `${path} must keep wide tables locally scrollable`);
    }
});

test('shared workspace navigation and meaningful motion remain accessible at narrow widths', () => {
    const lmsHeader = read('../../resources/views/trainer/training/partials/workspace-header-styles.blade.php');
    const simpleWorkspace = read('../../resources/views/trainer/simple/partials/workspace-styles.blade.php');
    const adminTraining = read('../../resources/views/admin/courses/partials/styles.blade.php');
    const trainerAttendance = read('../../resources/views/trainer/training/attendance/partials/styles.blade.php');

    for (const styles of [lmsHeader, simpleWorkspace]) {
        assert.match(styles, /max-width:\s*100%[\s\S]*?overflow-x:\s*auto[\s\S]*?overscroll-behavior-inline:\s*contain/);
        assert.match(styles, /overflow-wrap:\s*anywhere/);
    }

    for (const styles of [adminTraining, trainerAttendance]) {
        assert.match(styles, /transition[^;]*\.18s ease/);
        assert.match(styles, /@media \(prefers-reduced-motion: reduce\)/);
    }
});
