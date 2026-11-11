<!DOCTYPE html>
<html>
<head>
    <title>Teacher Management | TrainHub Al Amin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/teacher.css?v=<?php echo file_exists(public_path('assets/css/teacher.css')) ? filemtime(public_path('assets/css/teacher.css')) : time(); ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo file_exists(public_path('assets/css/style.css')) ? filemtime(public_path('assets/css/style.css')) : time(); ?>">
</head>

<body class="trainhub-app page-teacher">

@include('partials.topbar')

<div class="page">

    <div class="dashboard-header">
        <div>
            <h1>Teacher Management</h1>
            <p>View lists of schools, teachers and new teachers.</p>
        </div>
        <?php if (!empty($selectedSchool) && $selectedSchoolCategory !== '') { ?>
            <a href="teacher.php?tab=<?php echo e($activeTab === 'new_teacher' ? 'new_teacher' : $selectedSchoolCategory); ?>" class="secondary-back-btn header-back-to-school">
                <svg viewBox="0 0 24 24"><path d="M19 12H5" /><path d="M12 19L5 12L12 5" /></svg>
                Back to School List
            </a>
        <?php } ?>
    </div>

    <?php if (!empty($errorMessage)) { ?>
        <div class="alert alert-danger"><?php echo e($errorMessage); ?></div>
    <?php } ?>

    <?php if (isset($_GET['post_assigned'])) { ?>
        <div class="alert alert-success">Post Observation Attempt <?php echo (int)$_GET['post_assigned']; ?> assigned successfully.</div>
    <?php } ?>

    <?php if (isset($_GET['assigned'])) { ?>
        <div class="alert alert-success">Pre Observation / External Observation assignment saved successfully.</div>
    <?php } ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon stat-blue">
                <svg viewBox="0 0 24 24"><path d="M4 20V9L12 4L20 9V20" /><path d="M9 20V13H15V20" /></svg>
            </div>
            <div>
                <span>Total Schools</span>
                <h2><?php echo e($totalSchools); ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-green">
                <svg viewBox="0 0 24 24"><path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" /><path d="M4.5 20C5.3 16.8 8 15 12 15C16 15 18.7 16.8 19.5 20" /></svg>
            </div>
            <div>
                <span>Total Teachers</span>
                <h2><?php echo e($totalTeacherAll); ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-purple">
                <svg viewBox="0 0 24 24"><path d="M12 20V10" /><path d="M12 10C8.5 10 6 7.5 6 4C9.5 4 12 6.5 12 10Z" /><path d="M12 12C15.5 12 18 9.5 18 6C14.5 6 12 8.5 12 12Z" /></svg>
            </div>
            <div>
                <span>New Teachers</span>
                <h2><?php echo e($totalGuruNewAll); ?></h2>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon stat-orange">
                <svg viewBox="0 0 24 24"><path d="M20 6L9 17L4 12" /><path d="M5 20H19" /></svg>
            </div>
            <div>
                <span>Active Assignments</span>
                <h2><?php echo e($totalActiveAssignment); ?></h2>
            </div>
        </div>
    </div>

    <div class="teacher-tabs" role="tablist" aria-label="Teacher page tabs">
        <button type="button" class="teacher-tab-button <?php echo $activeTab === 'secondary' ? 'active' : ''; ?>" data-tab="secondary">Secondary School</button>
        <button type="button" class="teacher-tab-button <?php echo $activeTab === 'primary' ? 'active' : ''; ?>" data-tab="primary">Primary School</button>
        <button type="button" class="teacher-tab-button <?php echo $activeTab === 'preschool' ? 'active' : ''; ?>" data-tab="preschool">Preschool</button>
        <button type="button" class="teacher-tab-button <?php echo $activeTab === 'others' ? 'active' : ''; ?>" data-tab="others">Others</button>
        <button type="button" class="teacher-tab-button <?php echo $activeTab === 'organisations' ? 'active' : ''; ?>" data-tab="organisations">Organisations</button>
        <button type="button" class="teacher-tab-button <?php echo $activeTab === 'new_teacher' ? 'active' : ''; ?>" data-tab="new_teacher">New Teacher</button>
    </div>

    <div class="tab-content-wrap">
        <?php foreach (['secondary', 'primary', 'preschool', 'others'] as $categoryKey) { ?>
            <?php
                $showSelectedSchool = $selectedSchool && $selectedSchoolCategory === $categoryKey;
                $panelTitle = $tabLabels[$categoryKey];
            ?>
            <section class="teacher-tab-panel <?php echo $activeTab === $categoryKey ? 'active' : ''; ?>" data-panel="<?php echo e($categoryKey); ?>">
                <?php if ($showSelectedSchool) { ?>
                    <?php
                        $schoolTeachers = $teachersBySchool[$selectedSchoolID] ?? [];
                        $schoolNewTeachers = $newTeachersBySchool[$selectedSchoolID] ?? [];
                    ?>

                    <div class="selected-school-bar">
                        <div class="selected-school-left">
                            <div class="selected-school-icon"><?php echo e(strtoupper(substr((string)$selectedSchool['schoolName'], 0, 1))); ?></div>
                            <div>
                                <span><?php echo e($panelTitle); ?></span>
                                <h2><?php echo e($selectedSchool['schoolName']); ?></h2>
                                <p><?php echo e($selectedSchool['schoolID']); ?><?php echo !empty($selectedSchool['schoolAddress']) ? ' • ' . e($selectedSchool['schoolAddress']) : ''; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="school-detail-grid">
                        <div class="panel">
                            <div class="panel-header">
                                <div class="panel-title">
                                    <div class="panel-title-icon">
                                        <svg viewBox="0 0 24 24"><path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12Z" /><path d="M4.5 20C5.3 16.8 8 15 12 15C16 15 18.7 16.8 19.5 20" /></svg>
                                    </div>
                                    <div>
                                        <h2>Teacher List</h2>
                                        <p>Existing teachers under this selected school.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="teacher-list detail-list">
                                <?php renderTeacherRows($schoolTeachers); ?>
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel-header">
                                <div class="panel-title">
                                    <div class="panel-title-icon">
                                        <svg viewBox="0 0 24 24"><path d="M12 20V10" /><path d="M12 10C8.5 10 6 7.5 6 4C9.5 4 12 6.5 12 10Z" /><path d="M12 12C15.5 12 18 9.5 18 6C14.5 6 12 8.5 12 12Z" /></svg>
                                    </div>
                                    <div>
                                        <h2>New Teacher in This School</h2>
                                        <p>New teachers under this selected school.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="new-teacher-list school-new-teacher-list">
                                <?php renderSchoolNewTeacherRows($schoolNewTeachers); ?>
                            </div>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="panel school-panel">
                        <div class="panel-header course-panel-header">
                            <div class="panel-title">
                                <div class="panel-title-icon">
                                    <svg viewBox="0 0 24 24"><path d="M4 20V9L12 4L20 9V20" /><path d="M9 20V13H15V20" /></svg>
                                </div>
                                <div>
                                    <h2><?php echo e($panelTitle); ?></h2>
                                    <p>Choose a school to view its teachers and new teachers.</p>
                                </div>
                            </div>

                            <div class="course-filters school-search-form">
                                <div class="filter-search">
                                    <input type="search" class="school-tab-search" data-target-panel="<?php echo e($categoryKey); ?>" placeholder="Search school name, code or address...">
                                </div>
                                <button type="button" class="filter-btn school-tab-search-btn" data-target-panel="<?php echo e($categoryKey); ?>">
                                    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" /><path d="M20 20L16.65 16.65" /></svg>
                                    Filter
                                </button>
                                <button type="button" class="reset-filter school-tab-reset-btn" data-target-panel="<?php echo e($categoryKey); ?>">Reset</button>
                            </div>
                        </div>

                        <div class="school-card-grid category-school-card-grid">
                            <?php if (!empty($schoolsByCategory[$categoryKey])) { ?>
                                <?php foreach ($schoolsByCategory[$categoryKey] as $school) {
                                    $schoolSearchText = implode(' ', [$school['schoolID'] ?? '', $school['schoolName'] ?? '', $school['schoolAddress'] ?? '']);
                                ?>
                                    <a
                                        href="teacher.php?tab=<?php echo e($categoryKey); ?>&schoolID=<?php echo urlencode((string)$school['schoolID']); ?>"
                                        class="school-modern-card category-school-card filterable-school-card"
                                        data-panel="<?php echo e($categoryKey); ?>"
                                        data-search="<?php echo e($schoolSearchText); ?>"
                                    >
                                        <div class="school-card-topline">
                                            <div class="school-avatar"><?php echo e(strtoupper(substr((string)$school['schoolName'], 0, 1))); ?></div>
                                            <span><?php echo e($school['schoolID']); ?></span>
                                        </div>

                                        <h3><?php echo e($school['schoolName']); ?></h3>
                                        <p><?php echo !empty($school['schoolAddress']) ? e($school['schoolAddress']) : 'No address added.'; ?></p>

                                        <div class="school-mini-stats">
                                            <div>
                                                <small>Teachers</small>
                                                <strong><?php echo e($school['teacher_total']); ?></strong>
                                            </div>
                                            <div>
                                                <small>New Teachers</small>
                                                <strong><?php echo e($school['new_teacher_total']); ?></strong>
                                            </div>
                                        </div>

                                        <div class="school-card-action">View Teachers & New Teachers →</div>
                                    </a>
                                <?php } ?>
                                <div class="empty-state list-filter-empty school-search-empty" data-panel="<?php echo e($categoryKey); ?>" hidden>No school matches your search.</div>
                            <?php } else { ?>
                                <div class="empty-state">No school found in this category.</div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </section>
        <?php } ?>

        <section class="teacher-tab-panel <?php echo $activeTab === 'organisations' ? 'active' : ''; ?>" data-panel="organisations">
            <div class="panel organisation-panel">
                <div class="panel-header course-panel-header">
                    <div class="panel-title">
                        <div class="panel-title-icon">
                            <svg viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V5h10v16"/><path d="M15 9h4v12"/><path d="M8 9h4M8 13h4M8 17h4"/></svg>
                        </div>
                        <div>
                            <h2>Organisation List</h2>
                            <p>View registered organisation details.</p>
                        </div>
                    </div>
                </div>

                <?php if (empty($organizations)) { ?>
                    <div class="empty-state">No organisation found.</div>
                <?php } else { ?>
                    <div class="organisation-list">
                        <?php foreach ($organizations as $organisation) { ?>
                            <article class="organisation-card">
                                <div class="organisation-avatar"><?php echo e(strtoupper(substr((string)$organisation['OrganizationName'], 0, 1))); ?></div>
                                <div class="organisation-details">
                                    <h3><?php echo e($organisation['OrganizationName']); ?></h3>
                                    <div class="organisation-meta">
                                        <span><strong>Phone</strong><?php echo e($organisation['PhoneNumber'] ?: '-'); ?></span>
                                        <span><strong>Registered</strong><?php echo e($organisation['RegisterDate'] ?: '-'); ?></span>
                                    </div>
                                    <p><?php echo e($organisation['OrganizationAddress'] ?: 'No address added.'); ?></p>
                                </div>
                            </article>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </section>

        <section class="teacher-tab-panel <?php echo $activeTab === 'new_teacher' ? 'active' : ''; ?>" data-panel="new_teacher">
            <div class="panel">
                <div class="panel-header course-panel-header">
                    <div class="panel-title">
                        <div class="panel-title-icon">
                            <svg viewBox="0 0 24 24"><path d="M12 20V10" /><path d="M12 10C8.5 10 6 7.5 6 4C9.5 4 12 6.5 12 10Z" /><path d="M12 12C15.5 12 18 9.5 18 6C14.5 6 12 8.5 12 12Z" /></svg>
                        </div>
                        <div>
                            <h2><?php echo $selectedSchool ? 'New Teachers at '.e($selectedSchool['schoolName']) : 'Choose a School'; ?></h2>
                            <p><?php echo $selectedSchool ? 'Select Assign or Edit to manage observations in a popup.' : 'Select a school first to view its new teachers.'; ?></p>
                            
                        </div>
                    </div>
                </div>

                <?php if (!$selectedSchool) { ?>
                <div class="course-filters school-search-form post-school-search">
                    <input type="search" class="school-tab-search" data-target-panel="new_teacher" placeholder="Search school name or school ID...">
                    <button type="button" class="filter-btn school-tab-search-btn" data-target-panel="new_teacher">Search</button>
                    <button type="button" class="reset-filter school-tab-reset-btn" data-target-panel="new_teacher">Reset</button>
                </div>
                <div class="school-card-grid category-school-card-grid">
                    <?php foreach ($schoolsByID as $school) { ?>
                    <a class="school-modern-card category-school-card filterable-school-card" data-panel="new_teacher"
                       data-search="<?php echo e(strtolower($school['schoolName'].' '.$school['schoolID'])); ?>"
                       href="teacher.php?tab=new_teacher&amp;schoolID=<?php echo urlencode($school['schoolID']); ?>">
                        <div class="school-card-topline"><div class="school-avatar"><?php echo e(strtoupper(substr($school['schoolName'],0,1))); ?></div><span><?php echo e($school['schoolID']); ?></span></div>
                        <h3><?php echo e($school['schoolName']); ?></h3>
                        <p><?php echo e($school['schoolAddress'] ?: 'No address added'); ?></p>
                        <div class="school-mini-stats"><span><small>New Teachers</small><strong><?php echo (int)$school['new_teacher_total']; ?></strong></span></div>
                        <div class="school-card-action">View New Teachers →</div>
                    </a>
                    <?php } ?>
                    <div class="empty-state list-filter-empty school-search-empty" data-panel="new_teacher" hidden>No school matches your search.</div>
                </div>
                <?php } else { ?>
                <div class="list-toolbar" aria-label="New teacher list search and filter">
                    <div class="list-search-box">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20L16.65 16.65" /></svg>
                        <input type="search" id="newTeacherListSearch" placeholder="Search name, school, observer or email..." autocomplete="off">
                    </div>

                    <select id="newTeacherListFilter" class="list-filter-select" aria-label="Filter new teachers">
                        <option value="all">All new teachers</option>
                        <option value="assigned">Assigned</option>
                        <option value="pending">Pending assignment</option>
                        <option value="observer">Has observer</option>
                        <option value="external">Has external observer</option>
                    </select>

                    <button type="button" class="list-filter-btn" id="newTeacherListApply">Filter</button>
                    <button type="button" class="list-reset-btn" id="newTeacherListReset">Reset</button>
                </div>

                <div class="new-teacher-list" id="newTeacherList">
                    <?php renderNewTeacherList($newTeachersBySchool[$selectedSchoolID] ?? [], $eligibleTeacherList, 'School', 'new_teacher', $selectedSchoolID); ?>
                    <div class="empty-state list-filter-empty" id="newTeacherFilterEmpty" hidden>No new teacher matches the selected search or filter.</div>
                </div>
                <?php } ?>
            </div>
        </section>
    </div>

</div>

<script>
const teacherTabButtons = document.querySelectorAll('.teacher-tab-button');
const teacherTabPanels = document.querySelectorAll('.teacher-tab-panel');

teacherTabButtons.forEach(function(button) {
    button.addEventListener('click', function() {
        const tab = button.dataset.tab;
        window.location.href = 'teacher.php?tab=' + encodeURIComponent(tab);
    });
});

function runSchoolSearch(panelName) {
    const input = document.querySelector('.school-tab-search[data-target-panel="' + panelName + '"]');
    const cards = document.querySelectorAll('.filterable-school-card[data-panel="' + panelName + '"]');
    const emptyState = document.querySelector('.school-search-empty[data-panel="' + panelName + '"]');
    const query = input ? input.value.trim().toLowerCase() : '';
    let visibleCount = 0;

    cards.forEach(function(card) {
        const searchText = (card.dataset.search || '').toLowerCase();
        const show = query === '' || searchText.includes(query);
        card.hidden = !show;
        if (show) visibleCount++;
    });

    if (emptyState) {
        emptyState.hidden = query === '' || visibleCount !== 0;
    }
}

document.querySelectorAll('.school-tab-search-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        runSchoolSearch(button.dataset.targetPanel);
    });
});

document.querySelectorAll('.school-tab-reset-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        const panel = button.dataset.targetPanel;
        const input = document.querySelector('.school-tab-search[data-target-panel="' + panel + '"]');
        if (input) input.value = '';
        runSchoolSearch(panel);
    });
});

document.querySelectorAll('.school-tab-search').forEach(function(input) {
    input.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            runSchoolSearch(input.dataset.targetPanel);
        }
    });
    input.addEventListener('search', function() {
        runSchoolSearch(input.dataset.targetPanel);
    });
});

function setupListFilter(options) {
    const searchInput = document.getElementById(options.searchInputId);
    const filterSelect = document.getElementById(options.filterSelectId);
    const applyButton = document.getElementById(options.applyButtonId);
    const resetButton = document.getElementById(options.resetButtonId);
    const list = document.getElementById(options.listId);
    const emptyState = document.getElementById(options.emptyStateId);

    if (!searchInput || !filterSelect || !applyButton || !resetButton || !list) {
        return;
    }

    const items = Array.from(list.querySelectorAll(options.itemSelector));

    function applyFilter() {
        const query = searchInput.value.trim().toLowerCase();
        const filterValue = filterSelect.value;
        let visibleCount = 0;

        items.forEach(function(item) {
            const searchableText = (item.dataset.search || '').toLowerCase();
            const matchesSearch = query === '' || searchableText.includes(query);
            const matchesFilter = options.matchesFilter(item, filterValue);
            const shouldShow = matchesSearch && matchesFilter;

            item.hidden = !shouldShow;
            if (shouldShow) visibleCount++;
        });

        if (emptyState) {
            emptyState.hidden = visibleCount !== 0;
        }
    }

    function resetFilter() {
        searchInput.value = '';
        filterSelect.value = 'all';
        applyFilter();
        searchInput.focus();
    }

    applyButton.addEventListener('click', applyFilter);
    resetButton.addEventListener('click', resetFilter);
    filterSelect.addEventListener('change', applyFilter);
    searchInput.addEventListener('search', applyFilter);
    searchInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            applyFilter();
        }
    });
}

// Keep assignment dialogs at <body> level. This prevents card/list overflow or
// stacking contexts from trapping a fixed observer-assignment popup.
document.querySelectorAll('.assignment-form').forEach(function(form) {
    if (form.parentElement !== document.body) document.body.appendChild(form);
});

setupListFilter({
    searchInputId: 'newTeacherListSearch',
    filterSelectId: 'newTeacherListFilter',
    applyButtonId: 'newTeacherListApply',
    resetButtonId: 'newTeacherListReset',
    listId: 'newTeacherList',
    emptyStateId: 'newTeacherFilterEmpty',
    itemSelector: '.filterable-new-teacher-card',
    matchesFilter: function(item, filterValue) {
        if (filterValue === 'assigned') return item.dataset.assignment === 'assigned';
        if (filterValue === 'pending') return item.dataset.assignment === 'pending';
        if (filterValue === 'observer') return item.dataset.hasObserver === '1';
        if (filterValue === 'external') return item.dataset.hasExternal === '1';
        return true;
    }
});

function openAssignmentModal(targetId) {
    const form = document.getElementById(targetId);
    if (!form) return;
    form.hidden = false;
    form.classList.add('show-assignment-form');
    form.style.setProperty('display', 'flex', 'important');
    form.setAttribute('aria-hidden', 'false');
    form.scrollTop = 0;
    const shell = form.querySelector('.assignment-modal-shell');
    if (shell) shell.scrollTop = 0;
    document.body.classList.add('modal-open');
}

function closeAssignmentModal(targetId) {
    const form = document.getElementById(targetId);
    if (!form) return;
    form.classList.remove('show-assignment-form');
    form.style.setProperty('display', 'none', 'important');
    form.setAttribute('aria-hidden', 'true');
    if (!document.querySelector('.assignment-form.show-assignment-form')) {
        document.body.classList.remove('modal-open');
    }
}

// Delegation keeps Edit/Assign working even if a card is redrawn by filtering.
document.addEventListener('click', function(event) {
    const button = event.target.closest('.assign-toggle-btn');
    if (!button) return;
    event.preventDefault();
    openAssignmentModal(button.dataset.target);
});

document.querySelectorAll('[data-close-target]').forEach(function(button) {
    button.addEventListener('click', function() {
        closeAssignmentModal(button.dataset.closeTarget);
    });
});

document.querySelectorAll('.assignment-form').forEach(function(form) {
    form.addEventListener('click', function(event) {
        if (event.target === form) {
            closeAssignmentModal(form.id);
        }
    });
});

document.addEventListener('keydown', function(event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.assignment-form.show-assignment-form').forEach(function(form) {
        closeAssignmentModal(form.id);
    });
});

document.querySelectorAll('.post-assignment-form').forEach(function(form) {
    const list = form.querySelector('.post-teacher-select');
    const search = form.querySelector('.post-observer-search');
    const reuse = form.querySelector('.post-reuse-external');
    if (!list) return;
    const original = Array.from(list.options, option => option.cloneNode(true));
    search?.addEventListener('input', function () {
        const query = search.value.trim().toLowerCase();
        const previous = list.value;
        const options = original.filter(opt => !opt.value || !query || (opt.dataset.search || opt.textContent || '').toLowerCase().includes(query));
        list.replaceChildren(...options.map(opt => opt.cloneNode(true)));
        list.value = options.some(opt => opt.value === previous) ? previous : '';
    });
    reuse?.addEventListener('change', function () {
        if (!reuse.checked) return;
        search.value = '';
        search.dispatchEvent(new Event('input'));
        list.value = reuse.dataset.teacher || '';
        list.dispatchEvent(new Event('change', {bubbles: true}));
    });
    list.addEventListener('change', function () { if (reuse) reuse.checked = list.value === reuse.dataset.teacher; });
});

// Keep new-teacher rows compact. Details belong inside Assign / Edit popups.
document.querySelectorAll('.new-teacher-card').forEach(function(card) {
    const shell = card.querySelector('.observer-assignment-form .assignment-modal-shell');
    const actions = shell?.querySelector('.assignment-modal-actions');
    if (!shell || !actions) return;
    ['.pre-observation-summary', '.post-observation-card'].forEach(function(selector) {
        const details = card.querySelector(':scope > ' + selector);
        if (details) shell.insertBefore(details, actions);
    });
});

document.querySelectorAll('.observer-assignment-form').forEach(function(form) {
    const observerSelect = form.querySelector('.observer-select');
    const externalSelect = form.querySelector('.external-select');
    const observerSearch = form.querySelector('.observer-option-search');
    const externalSearch = form.querySelector('.external-option-search');
    const observerStartDate = form.querySelector('.observer-start-date');
    const externalStartDate = form.querySelector('.external-start-date');

    if (!observerSelect || !externalSelect || !observerStartDate || !externalStartDate) return;

    // Keep the source options so search rebuilds a real, scrollable native select.
    // Browsers do not consistently hide individual <option> elements.
    const allObserverOptions = Array.from(observerSelect.options, o => o.cloneNode(true));
    const allExternalOptions = Array.from(externalSelect.options, o => o.cloneNode(true));
    function refill(select, original, query, excludedId) {
        const previous = select.value;
        const matches = original.filter(o => !o.value || (
            o.dataset.teacherId !== excludedId &&
            (!query || (o.dataset.search || o.textContent || '').toLowerCase().includes(query))
        ));
        select.replaceChildren(...matches.map(o => o.cloneNode(true)));
        select.value = matches.some(o => o.value === previous) ? previous : '';
    }
    function filterObserverOptions() {
        const observerTeacherId = observerSelect.value;
        const externalTeacherId = externalSelect.value;
        refill(observerSelect, allObserverOptions, (observerSearch?.value || '').trim().toLowerCase(), externalTeacherId);
        refill(externalSelect, allExternalOptions, (externalSearch?.value || '').trim().toLowerCase(), observerTeacherId);
    }

    function syncRequiredDate() {
        if (observerSelect.value) {
            observerStartDate.required = true;
            if (!observerStartDate.value) observerStartDate.value = new Date().toISOString().slice(0, 10);
        } else {
            observerStartDate.required = false;
        }

        if (externalSelect.value) {
            externalStartDate.required = true;
            if (!externalStartDate.value) externalStartDate.value = new Date().toISOString().slice(0, 10);
        } else {
            externalStartDate.required = false;
        }
    }

    observerSelect.addEventListener('change', function() {
        filterObserverOptions();
        syncRequiredDate();
    });

    externalSelect.addEventListener('change', function() {
        filterObserverOptions();
        syncRequiredDate();
    });

    observerSearch?.addEventListener('input', filterObserverOptions);
    externalSearch?.addEventListener('input', filterObserverOptions);

    form.addEventListener('submit', function(event) {
        if (!observerSelect.value && !externalSelect.value) {
            event.preventDefault();
            if (typeof openGlobalNotice === 'function') {
                openGlobalNotice('Please select an observer or external observer before saving.', 'error');
            }
            return;
        }
        // A selected role is always saved as active. Historical rows are only
        // marked inactive when that role is explicitly cleared during Edit.
        const observerStatus = form.querySelector('input[name="observerStatus"]');
        const observerAssignmentStatus = form.querySelector('input[name="observerAssignmentStatus"]');
        const externalStatus = form.querySelector('input[name="externalStatus"]');
        const externalAssignmentStatus = form.querySelector('input[name="externalAssignmentStatus"]');
        if (observerSelect.value) {
            if (observerStatus) observerStatus.value = 'active';
            if (observerAssignmentStatus) observerAssignmentStatus.value = 'active';
        }
        if (externalSelect.value) {
            if (externalStatus) externalStatus.value = 'active';
            if (externalAssignmentStatus) externalAssignmentStatus.value = 'active';
        }
    });

    filterObserverOptions();
    syncRequiredDate();
});


// Searchable dropdown. Keep the original select as the submitted form value.
// The results always open below the input and stay scrollable in the modal.
(function initTeacherComboboxes() {
    const pairs = [
        ['.observer-option-search', '.observer-select'],
        ['.external-option-search', '.external-select'],
        ['.post-observer-search', '.post-teacher-select']
    ];
    document.querySelectorAll('.assignment-form').forEach(form => {
        pairs.forEach(([searchSelector, selectSelector]) => {
            const search = form.querySelector(searchSelector);
            const select = form.querySelector(selectSelector);
            if (!search || !select) return;

            const wrapper = document.createElement('div');
            wrapper.className = 'teacher-choice';
            search.parentNode.insertBefore(wrapper, search);
            wrapper.append(search, select);
            const arrow = document.createElement('button');
            arrow.type = 'button';
            arrow.className = 'teacher-choice-arrow';
            arrow.textContent = '⌄';
            arrow.setAttribute('aria-label', 'Show teachers');
            const panel = document.createElement('div');
            panel.className = 'teacher-choice-menu';
            panel.setAttribute('role', 'listbox');
            panel.hidden = true;
            wrapper.append(arrow, panel);
            search.setAttribute('role', 'combobox');
            search.setAttribute('aria-autocomplete', 'list');
            search.setAttribute('aria-expanded', 'false');
            search.setAttribute('autocomplete', 'off');
            search.placeholder = 'Search or choose a teacher';
            select.setAttribute('tabindex', '-1');
            select.classList.add('teacher-choice-native');

            function selectedText() {
                return select.selectedOptions[0]?.value ? select.selectedOptions[0].textContent.trim() : '';
            }
            if (selectedText()) {
                search.value = selectedText();
                search.dataset.chosen = 'true';
            }
            const close = () => {
                panel.hidden = true;
                search.setAttribute('aria-expanded', 'false');
            };
            const show = (all = false) => {
                document.querySelectorAll('.teacher-choice-menu').forEach(menu => {
                    if (menu !== panel) {
                        menu.hidden = true;
                        menu.parentNode?.querySelector('[role="combobox"]')?.setAttribute('aria-expanded','false');
                    }
                });
                panel.replaceChildren();
                const q = all ? '' : (search.value || '').trim().toLocaleLowerCase();
                const options = Array.from(select.options).filter(opt => opt.value &&
                    (!q || (opt.dataset.search || opt.textContent || '').toLocaleLowerCase().includes(q)));
                if (!options.length) {
                    const noResults = document.createElement('div');
                    noResults.className = 'teacher-choice-empty';
                    noResults.textContent = 'No matching teacher';
                    panel.append(noResults);
                }
                options.forEach(opt => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'teacher-choice-option';
                    item.setAttribute('role', 'option');
                    item.setAttribute('aria-selected', String(opt.value === select.value));
                    item.textContent = opt.textContent.trim();
                    item.addEventListener('mousedown', e => e.preventDefault());
                    item.addEventListener('click', () => {
                        select.value = opt.value;
                        search.value = opt.textContent.trim();
                        search.dataset.chosen = 'true';
                        select.dispatchEvent(new Event('change', {bubbles: true}));
                        close();
                    });
                    panel.append(item);
                });
                panel.hidden = false;
                search.setAttribute('aria-expanded','true');
            };
            search.addEventListener('input', () => {
                search.dataset.chosen = 'false';
                show(false);
            });
            search.addEventListener('focus', () => {
                if (search.dataset.chosen === 'true') search.select();
                show(search.dataset.chosen === 'true');
            });
            arrow.addEventListener('click', () => {
                search.focus();
                show(true);
            });
            search.addEventListener('keydown', e => {
                if (e.key === 'Escape') { close(); e.stopPropagation(); }
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (panel.hidden) show(true);
                    panel.querySelector('.teacher-choice-option')?.focus();
                }
                if (e.key === 'Enter' && !panel.hidden) {
                    const first = panel.querySelector('.teacher-choice-option');
                    if (first) {e.preventDefault(); first.click();}
                }
            });
            panel.addEventListener('keydown', e => {
                const options = Array.from(panel.querySelectorAll('.teacher-choice-option'));
                const index = options.indexOf(document.activeElement);
                if (e.key === 'ArrowDown') {e.preventDefault(); options[Math.min(index+1,options.length-1)]?.focus();}
                if (e.key === 'ArrowUp') {e.preventDefault(); index <= 0 ? search.focus() : options[index-1]?.focus();}
                if (e.key === 'Escape') {e.preventDefault(); e.stopPropagation(); close(); search.focus();}
            });
            document.addEventListener('pointerdown', e => {if (!wrapper.contains(e.target)) close();});
            select.addEventListener('change', () => {
                // Handles programmatic selection (e.g. reuse external observer).
                if (select.value) {search.value = selectedText(); search.dataset.chosen = 'true';}
                else if (search.dataset.chosen === 'true') {search.value = ''; search.dataset.chosen = 'false';}
            });
            search.addEventListener('blur', () => {
                // Restore the selected value if a search was abandoned.
                setTimeout(() => {if (!wrapper.contains(document.activeElement) && panel.hidden) {
                    search.value = selectedText(); search.dataset.chosen = !!select.value ? 'true' : 'false';
                }}, 80);
            });
        });
    });
})();

</script>

</body>
</html>
