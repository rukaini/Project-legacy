import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');

test('signed-in Trainer and Admin chrome omits decorative training-category labels', () => {
    const sources = [
        '../../resources/views/trainer/dashboard.blade.php',
        '../../resources/views/admin/dashboard.blade.php',
        '../../resources/views/admin/analytics/partials/filters.blade.php',
        '../../resources/views/trainer/training/analytics/partials/header-filters.blade.php',
        '../../resources/views/trainer/training/partials/workspace-header.blade.php',
        '../../resources/views/trainer/simple/partials/header.blade.php',
        '../../resources/views/trainer/simple/overview/partials/sidebar.blade.php',
        '../../resources/views/trainer/training/workspace-overview.blade.php',
    ];

    for (const source of sources) {
        assert.doesNotMatch(read(source), /course_category|Category is|bi-tag/);
    }
});

test('course-owner category data remains while the Proposal workflow omits categories', () => {
    const myTraining = read('../../resources/views/trainer/training/my-training.blade.php');
    const proposalForm = read('../../resources/views/trainer/training/proposal/partials/form.blade.php');
    const proposalList = read('../../resources/views/trainer/training/proposal/partials/list.blade.php');
    const proposalResults = read('../../resources/views/trainer/training/proposal/partials/results.blade.php');
    const proposalDetail = read('../../resources/views/trainer/training/proposal/partials/detail.blade.php');
    const adminProposals = read('../../resources/views/admin/proposals/partials/content.blade.php');
    const directory = read('../../resources/views/admin/directory/partials/content.blade.php');
    const adminTrainingContent = read('../../resources/views/admin/courses/partials/content.blade.php');
    const adminTrainingScripts = read('../../resources/views/admin/courses/partials/scripts.blade.php');

    assert.match(myTraining, /id="categoryFilter"[\s\S]*?course_category/);
    assert.match(myTraining, /data-category="\{\{ strtolower\(\$course->course_category/);
    for (const source of [proposalForm, proposalList, proposalResults, proposalDetail, adminProposals]) {
        assert.doesNotMatch(source, /course_category|categoryOptions|Course Category|All Categories|Uncategorized|>Category</);
    }
    assert.match(directory, /directoryTrainingCategory/);
    assert.match(directory, /<th>Category<\/th>/);
    assert.match(adminTrainingContent, /data-search="[^"]*\$training->course_category/);
    assert.doesNotMatch(adminTrainingScripts, /<dt>Category<\/dt>|training\.category/);
});

test('mode, status, and participant semantics remain separate from training categories', () => {
    const myTraining = read('../../resources/views/trainer/training/my-training.blade.php');
    const lmsHeader = read('../../resources/views/trainer/training/partials/workspace-header.blade.php');
    const simpleHeader = read('../../resources/views/trainer/simple/partials/header.blade.php');
    const directory = read('../../resources/views/admin/directory/partials/content.blade.php');

    assert.match(myTraining, />LMS<|\? 'LMS'/);
    assert.match(myTraining, /: 'Simple'/);
    assert.match(myTraining, /id="statusFilter"/);
    assert.doesNotMatch(lmsHeader, /ParticipantTypeLabel::audience|target_participant|bi-person-badge/);
    assert.doesNotMatch(simpleHeader, /ParticipantTypeLabel::audience|target_participant|bi-person-badge/);
    assert.match(directory, /data-mode="\{\{ \$c->is_lms \? 'LMS' : 'Simple' \}\}"/);
});
