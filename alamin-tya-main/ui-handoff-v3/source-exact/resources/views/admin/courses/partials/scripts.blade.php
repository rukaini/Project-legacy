<script>
    document.addEventListener('DOMContentLoaded', function () {
        var dataEl = document.getElementById('trainingData');
        if (!dataEl) return;

        var DATA = {};
        try { DATA = JSON.parse(dataEl.textContent || '{}'); } catch (error) { DATA = {}; }

        var detail = document.getElementById('trainingDetail');
        var workspace = document.getElementById('trainingMonitoringWorkspace');
        var master = document.getElementById('trainingMaster');
        var masterToggles = document.querySelectorAll('[data-training-master-toggle]');
        var listEl = document.getElementById('trainingList');
        var listEmpty = document.getElementById('trainingListEmpty');
        var masterRows = Array.prototype.slice.call(document.querySelectorAll('.tm-training-row[data-training-id]'));
        var searchInput = document.getElementById('trainingSearch');
        var searchClear = document.querySelector('[data-training-search-clear]');
        var scheduleFilter = document.getElementById('trainingScheduleFilter');
        var modeFilter = document.getElementById('trainingModeFilter');
        var trainerFilter = document.getElementById('trainingTrainerFilter');
        var resetButton = document.getElementById('trainingReset');
        var filterToggle = document.querySelector('[data-training-filter-toggle]');
        var filterPanel = document.querySelector('[data-training-filter-panel]');
        var filterCount = document.querySelector('[data-training-filter-count]');
        var filterChevron = document.querySelector('[data-training-filter-chevron]');
        var directoryUrl = detail ? detail.dataset.directoryUrl || '' : '';
        var csrfToken = detail ? detail.dataset.csrfToken || '' : '';

        var currentId = null;
        var activeTab = 'overview';
        var selectedTeachingPlanTrainerId = null;
        var sessionState = { search: '', status: 'all', sort: 'date-asc', page: 1, rows: 10 };
        var participantState = { search: '', type: 'all', sort: 'name-asc', page: 1, rows: 10 };
        var attendanceState = { search: '', session: '', status: 'all', sort: 'name-asc', page: 1, rows: 10 };
        var masterCollapsePreference = false;
        var filterPanelOpen = false;

        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
            });
        }

        function reducedMotion() {
            return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }

        function isCompactWorkspace() {
            return window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
        }

        function setMasterCollapsed(collapsed, moveFocus) {
            if (!workspace || !master) return;
            var shouldCollapse = Boolean(collapsed) && !isCompactWorkspace();
            workspace.classList.toggle('is-master-collapsed', shouldCollapse);
            master.classList.toggle('is-collapsed', shouldCollapse);
            masterToggles.forEach(function (button) {
                var isExpandControl = button.classList.contains('tm-master-expand');
                button.setAttribute('aria-expanded', shouldCollapse ? 'false' : 'true');
                button.setAttribute('aria-label', isExpandControl ? 'Expand training list' : 'Collapse training list');
                button.setAttribute('title', isExpandControl ? 'Expand training list' : 'Collapse training list');
                if (moveFocus && isExpandControl === shouldCollapse) button.focus();
            });
            var expanded = master.querySelector('[data-training-master-expanded]');
            var compact = master.querySelector('[data-training-master-collapsed]');
            if (expanded) {
                expanded.setAttribute('aria-hidden', shouldCollapse ? 'true' : 'false');
                expanded.toggleAttribute('inert', shouldCollapse);
            }
            if (compact) {
                compact.setAttribute('aria-hidden', shouldCollapse ? 'false' : 'true');
                compact.toggleAttribute('inert', !shouldCollapse);
            }
        }

        function typeTone(label) {
            if (label === 'Teacher') return 'blue';
            if (label === 'Guru Baru' || label === 'Guru New' || label === 'New Teacher') return 'indigo';
            if (label === 'Public') return 'amber';
            return 'slate';
        }

        function statusTone(status) {
            var normalized = String(status || '').toLowerCase();
            if (normalized === 'present' || normalized === 'active' || normalized === 'approved' || normalized === 'open' || normalized === 'ongoing') return 'success';
            if (normalized === 'late' || normalized === 'pending') return 'warning';
            if (normalized === 'absent' || normalized === 'rejected' || normalized === 'cancelled') return 'danger';
            return 'neutral';
        }

        function emptyState(icon, title, message, className) {
            return '<div class="adm-empty ' + (className || '') + '">'
                + '<i class="bi ' + icon + '" aria-hidden="true"></i>'
                + '<div class="t">' + esc(title) + '</div>'
                + '<div class="s">' + esc(message) + '</div></div>';
        }

        function detailSearchClear(scope, hasValue, label) {
            return '<button type="button" class="tm-search-clear" data-detail-clear="' + scope + '" aria-label="Clear ' + esc(label) + '"' + (hasValue ? '' : ' hidden') + '><i class="bi bi-x-lg" aria-hidden="true"></i></button>';
        }

        function pagerMarkup(scope, page, totalPages, total, rows, noun) {
            return '<div class="tm-pager" data-detail-pager="' + scope + '">'
                + '<label class="tm-rows-control"><span>Rows</span><select data-detail-rows="' + scope + '">'
                + [10, 25, 50].map(function (option) {
                    return '<option value="' + option + '"' + (rows === option ? ' selected' : '') + '>' + option + '</option>';
                }).join('')
                + '</select></label>'
                + '<span class="tm-page-info">' + (total === 0
                    ? '<strong>0</strong> ' + noun
                    : 'Showing <strong>' + (((page - 1) * rows) + 1) + '&ndash;' + Math.min(page * rows, total) + '</strong> of <strong>' + total + '</strong> ' + noun) + '</span>'
                + '<div class="tm-pager-controls">'
                + '<button type="button" class="tm-page-btn" data-detail-page="' + scope + '" data-direction="prev"' + (page === 1 || total === 0 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i>Previous</button>'
                + '<span class="tm-page-current" aria-current="page">' + page + ' / ' + totalPages + '</span>'
                + '<button type="button" class="tm-page-btn" data-detail-page="' + scope + '" data-direction="next"' + (page === totalPages || total === 0 ? ' disabled' : '') + '>Next<i class="bi bi-chevron-right" aria-hidden="true"></i></button>'
                + '</div></div>';
        }

        function masterControlsActive() {
            return Boolean(
                (scheduleFilter && scheduleFilter.value !== 'all')
                || (modeFilter && modeFilter.value !== 'all')
                || (trainerFilter && trainerFilter.value !== 'all')
            );
        }

        function activeMasterFilterCount() {
            return [scheduleFilter, modeFilter, trainerFilter].filter(function (control) {
                return control && control.value !== 'all';
            }).length;
        }

        function syncFilterPanel() {
            var activeCount = activeMasterFilterCount();
            if (filterPanel) {
                filterPanel.classList.toggle('is-open', filterPanelOpen);
                filterPanel.setAttribute('aria-hidden', filterPanelOpen ? 'false' : 'true');
                filterPanel.toggleAttribute('inert', !filterPanelOpen);
            }
            if (filterToggle) {
                filterToggle.classList.toggle('has-active-filters', activeCount > 0);
                filterToggle.setAttribute('aria-expanded', filterPanelOpen ? 'true' : 'false');
            }
            if (filterCount) {
                filterCount.hidden = activeCount === 0;
                filterCount.textContent = String(activeCount);
            }
            if (filterChevron) {
                filterChevron.className = 'bi ' + (filterPanelOpen ? 'bi-chevron-up' : 'bi-chevron-down');
            }
        }

        function masterMatches() {
            var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var schedule = scheduleFilter ? scheduleFilter.value : 'all';
            var mode = modeFilter ? modeFilter.value : 'all';
            var trainer = trainerFilter ? trainerFilter.value : 'all';
            var matches = masterRows.filter(function (row) {
                var trainers = row.dataset.trainers || ',,';
                return (query === '' || (row.dataset.search || '').toLowerCase().indexOf(query) !== -1)
                    && (schedule === 'all' || row.dataset.schedule === schedule)
                    && (mode === 'all' || row.dataset.mode === mode)
                    && (trainer === 'all' || trainers.indexOf(',' + trainer + ',') !== -1);
            });

            matches.sort(function (first, second) {
                var firstDate = Number(first.dataset.sortDate || 0);
                var secondDate = Number(second.dataset.sortDate || 0);
                return secondDate - firstDate;
            });

            return matches;
        }

        function syncMasterSelection() {
            masterRows.forEach(function (row) {
                var selected = row.dataset.trainingId === currentId;
                row.classList.toggle('active', selected);
                row.setAttribute('aria-selected', selected ? 'true' : 'false');
            });
        }

        function renderNoSelection(filtered) {
            if (!detail) return;
            detail.innerHTML = emptyState(
                filtered ? 'bi-funnel' : 'bi-mortarboard',
                filtered ? 'No matching training selected' : 'Select a training to monitor',
                filtered
                    ? 'Adjust the training-list controls to select and inspect a training.'
                    : 'Choose a training from the list to review its schedule, participants, and attendance.',
                'tm-detail-empty'
            );
        }

        function renderMaster() {
            if (!listEl) return;
            var matches = masterMatches();
            var total = matches.length;

            masterRows.forEach(function (row) { row.hidden = true; });
            matches.forEach(function (row) {
                row.hidden = false;
                listEl.appendChild(row);
            });

            if (listEmpty) listEmpty.hidden = total !== 0;
            if (searchClear) searchClear.hidden = !searchInput || searchInput.value === '';
            if (resetButton) resetButton.disabled = !masterControlsActive();
            syncFilterPanel();

            var selectedStillMatches = currentId && matches.some(function (row) {
                return row.dataset.trainingId === currentId;
            });
            if (!selectedStillMatches) {
                if (matches.length) {
                    selectTraining(matches[0].dataset.trainingId, false);
                } else {
                    currentId = null;
                    syncMasterSelection();
                    renderNoSelection(true);
                }
            } else {
                syncMasterSelection();
            }
        }

        function metricCard(tone, icon, label, value, note) {
            return '<article class="tm-metric"><span class="tm-metric-icon ' + tone + '" aria-hidden="true"><i class="bi ' + icon + '"></i></span>'
                + '<span class="tm-metric-label">' + esc(label) + '</span>'
                + '<strong>' + esc(value) + '</strong><small>' + esc(note) + '</small></article>';
        }

        function attendanceSummaryMarkup(attendance, available) {
            if (!available) {
                return emptyState('bi-database-x', 'Attendance unavailable', 'The attendance reporting table or required fields are unavailable for this training.', 'tm-inline-empty');
            }
            if (!attendance || attendance.recorded === 0) {
                return emptyState('bi-person-check', 'No attendance records', 'No present, late, or absent records have been recorded for this training.', 'tm-inline-empty');
            }

            return '<div class="tm-attendance-summary">'
                + metricCard('green', 'bi-check-circle', 'Present', attendance.present, 'Recorded attendance')
                + metricCard('amber', 'bi-clock-history', 'Late', attendance.late, 'Recorded attendance')
                + metricCard('red', 'bi-x-circle', 'Absent', attendance.absent, 'Recorded attendance')
                + metricCard('blue', 'bi-percent', 'Attendance Rate', attendance.rate + '%', 'Present and late of recorded')
                + '</div>';
        }

        function trainerLinks(training) {
            if (!training.trainers || !training.trainers.length) {
                return '<span class="tm-unavailable"><i class="bi bi-person-x" aria-hidden="true"></i>No trainer assigned</span>';
            }

            return training.trainers.map(function (trainer) {
                return '<a class="tm-trainer-link" href="' + esc(directoryUrl) + '?tab=trainers&amp;trainerName=' + encodeURIComponent(trainer.name) + '"><i class="bi bi-person-badge" aria-hidden="true"></i>' + esc(trainer.name) + '</a>';
            }).join('');
        }

        function renderDetail(id) {
            var training = DATA[id];
            if (!training || !detail) {
                renderNoSelection(false);
                return;
            }

            var capacity = Number(training.capacity);
            var teachingPlanAttention = Number(training.teachingPlanCounts && training.teachingPlanCounts.needsReview) || 0;
            var teachingPlanAttentionLabel = teachingPlanAttention + ' teaching plan' + (teachingPlanAttention === 1 ? '' : 's') + ' needs review';
            detail.innerHTML = '<article class="tm-detail-card">'
                + '<header class="tm-detail-head">'
                + '<div class="tm-detail-title-row"><div><span class="tm-eyebrow">Selected Training</span><h2>' + esc(training.name) + '</h2></div>'
                + '<div class="tm-detail-badges"><span class="tm-mode-badge ' + esc(training.modeKey) + '">' + esc(training.modeLabel) + '</span>'
                + '<span class="tm-schedule-badge ' + esc(training.scheduleKey) + '">' + esc(training.scheduleLabel) + '</span></div></div>'
                + '<div class="tm-detail-identity">'
                + '<div><span>Trainer in charge</span><div class="tm-trainer-list">' + trainerLinks(training) + '</div></div>'
                + '<div><span>Training dates</span><strong><i class="bi bi-calendar3" aria-hidden="true"></i>' + esc(training.dateRangeLabel) + '</strong></div>'
                + '<div><span>Enrolment / Capacity</span><strong><i class="bi bi-people" aria-hidden="true"></i>' + (Number.isFinite(capacity) && capacity > 0 ? esc(training.enrolled) + ' / ' + capacity + ' enrolled' : esc(training.enrolled) + ' enrolled · Capacity unavailable') + '</strong></div>'
                + '</div>'
                + (training.description ? '<p class="tm-description">' + esc(training.description) + '</p>' : '')
                + '</header>'
                + '<nav class="tm-detail-tabs" role="tablist" aria-label="Selected training details">'
                + [['overview', 'bi-grid', 'Overview'], ['teaching-plans', 'bi-file-earmark-text', 'Teaching Plans'], ['sessions', 'bi-calendar3', 'Sessions'], ['participants', 'bi-people', 'Participants'], ['attendance', 'bi-person-check', 'Attendance']].map(function (tab) {
                    var attention = tab[0] === 'teaching-plans' && teachingPlanAttention > 0
                        ? '<span class="tm-tab-count" aria-label="' + teachingPlanAttentionLabel + '" title="' + teachingPlanAttentionLabel + '">' + teachingPlanAttention + '</span>'
                        : '';
                    return '<button type="button" role="tab" data-training-tab="' + tab[0] + '" aria-selected="' + (activeTab === tab[0] ? 'true' : 'false') + '" class="' + (activeTab === tab[0] ? 'active' : '') + '"><i class="bi ' + tab[1] + '" aria-hidden="true"></i>' + tab[2] + attention + '</button>';
                }).join('')
                + '</nav><div class="tm-tab-panel" id="trainingTabPanel" role="tabpanel"></div></article>';

            renderActiveTab();
        }

        function renderOverview(training, panel) {
            var capacity = Number(training.capacity);
            var capacityNote = Number.isFinite(capacity) && capacity > 0 ? 'Participants enrolled' : 'Capacity not recorded';
            var attendanceValue = training.attendance && training.attendance.rate != null ? training.attendance.rate + '%' : 'Unavailable';
            var attendanceNote = training.attendanceAvailable
                ? (training.attendance.recorded ? training.attendance.recorded + ' recorded attendance entries' : 'No attendance records')
                : 'Attendance source unavailable';
            var nextSession = training.nextSession;
            var materialAccessState = training.allowMaterialAfterEnd ? 'Enabled' : 'Disabled';

            panel.innerHTML = '<div class="tm-overview-metrics">'
                + metricCard('blue', 'bi-people', 'Enrolled / Capacity', Number.isFinite(capacity) && capacity > 0 ? training.enrolled + ' / ' + capacity : training.enrolled, capacityNote)
                + metricCard('indigo', 'bi-calendar-check', 'Session Progress', training.held + ' / ' + training.total, 'Scheduled sessions elapsed')
                + metricCard('amber', 'bi-person-check', 'Attendance', attendanceValue, attendanceNote)
                + metricCard('green', 'bi-calendar-event', 'Next Session', nextSession ? nextSession.dateLabel : 'None scheduled', nextSession ? nextSession.title : 'No current or future dated session')
                + '</div>'
                + '<div class="tm-overview-grid"><section class="tm-info-section"><div class="tm-section-head"><div><h3>Training Information</h3><p>Persisted and schedule-derived details.</p></div></div>'
                + '<dl class="tm-fact-grid">'
                + '<div><dt>Persisted Status</dt><dd>' + esc(training.status) + '</dd></div>'
                + '<div><dt>Schedule State</dt><dd>' + esc(training.scheduleLabel) + '</dd></div>'
                + '<div><dt>Training Mode</dt><dd>' + esc(training.modeLabel) + '</dd></div>'
                + '<div><dt>Target Participants</dt><dd>' + esc(training.targetParticipant || 'Not recorded') + '</dd></div>'
                + '<div><dt>Training Dates</dt><dd>' + esc(training.dateRangeLabel) + '</dd></div>'
                + '</dl></section>'
                + '<section class="tm-info-section tm-attendance-section"><div class="tm-section-head"><div><h3>Recorded Attendance</h3><p>Summary of real present, late, and absent records.</p></div></div>'
                + attendanceSummaryMarkup(training.attendance, training.attendanceAvailable)
                + '</section></div>'
                + '<section class="tm-material-access" aria-labelledby="participant-material-access-title">'
                + '<div class="tm-section-head"><div><h3 id="participant-material-access-title">Participant Material Access</h3><p>Control whether enrolled participants can continue accessing training materials after the training ends.</p></div><span class="tm-access-state ' + (training.allowMaterialAfterEnd ? 'enabled' : 'disabled') + '" data-material-access-state aria-live="polite">' + materialAccessState + '</span></div>'
                + '<div class="tm-material-access-control">'
                + '<button type="button" class="tm-access-switch' + (training.allowMaterialAfterEnd ? ' is-enabled' : '') + '" role="switch" aria-checked="' + (training.allowMaterialAfterEnd ? 'true' : 'false') + '" aria-label="Allow access after training ends" data-material-access-toggle data-update-url="' + esc(training.materialAccessUpdateUrl) + '"><span aria-hidden="true"></span></button>'
                + '<div class="tm-access-copy"><strong>Allow access after training ends</strong></div>'
                + '</div></section>';
        }

        function syncMaterialAccessControl(training, control, state, persistedValue) {
            training.allowMaterialAfterEnd = persistedValue;
            control.classList.toggle('is-enabled', persistedValue);
            control.setAttribute('aria-checked', persistedValue ? 'true' : 'false');
            state.className = 'tm-access-state ' + (persistedValue ? 'enabled' : 'disabled');
            state.textContent = persistedValue ? 'Enabled' : 'Disabled';
        }

        function updateMaterialAccess(control) {
            var training = DATA[currentId];
            var state = detail.querySelector('[data-material-access-state]');
            if (!training || !state || control.disabled) return;

            var persistedValue = Boolean(training.allowMaterialAfterEnd);
            var requestedValue = !persistedValue;
            control.disabled = true;
            control.setAttribute('aria-busy', 'true');
            state.className = 'tm-access-state saving';
            state.textContent = 'Saving';

            fetch(control.dataset.updateUrl, {
                method: 'PATCH',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ allow_material_after_end: requestedValue })
            }).then(function (response) {
                return response.json().catch(function () { return {}; }).then(function (payload) {
                    if (!response.ok) throw new Error(payload.message || 'The setting could not be saved.');
                    return payload;
                });
            }).then(function (payload) {
                if (typeof payload.allow_material_after_end !== 'boolean') {
                    throw new Error('The saved setting could not be confirmed.');
                }
                var savedValue = Boolean(payload.allow_material_after_end);
                syncMaterialAccessControl(training, control, state, savedValue);
            }).catch(function (error) {
                syncMaterialAccessControl(training, control, state, persistedValue);
                window.showAppToast('error', error.message || 'The setting could not be saved. Please try again.');
            }).finally(function () {
                control.disabled = false;
                control.removeAttribute('aria-busy');
            });
        }

        function sessionControls(training) {
            return '<div class="tm-section-head"><div><h3>All Sessions</h3><p>Every persisted session belonging to this training.</p></div><span class="tm-section-count">' + training.sessions.length + ' total</span></div>'
                + '<div class="tm-detail-controls"><div class="tm-filter-field tm-search-field"><label for="sessionSearch">Search Sessions</label><div class="tm-search-control"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="sessionSearch" data-detail-search="sessions" value="' + esc(sessionState.search) + '" placeholder="Search title, mode, location, or trainer...">' + detailSearchClear('sessions', sessionState.search !== '', 'session search') + '</div></div>'
                + '<div class="tm-filter-field"><label for="sessionStatus">Schedule</label><select id="sessionStatus" data-detail-filter="sessions" data-key="status"><option value="all">All sessions</option>'
                + [['today', 'Today'], ['upcoming', 'Upcoming'], ['past', 'Past'], ['unavailable', 'Unavailable']].map(function (option) { return '<option value="' + option[0] + '"' + (sessionState.status === option[0] ? ' selected' : '') + '>' + option[1] + '</option>'; }).join('')
                + '</select></div><div class="tm-filter-field"><label for="sessionSort">Sort By</label><select id="sessionSort" data-detail-filter="sessions" data-key="sort"><option value="date-asc"' + (sessionState.sort === 'date-asc' ? ' selected' : '') + '>Earliest first</option><option value="date-desc"' + (sessionState.sort === 'date-desc' ? ' selected' : '') + '>Latest first</option><option value="title"' + (sessionState.sort === 'title' ? ' selected' : '') + '>Title A-Z</option></select></div></div>'
                + '<div id="sessionResults"></div><div id="sessionPager"></div>';
        }

        function renderSessionResults(training) {
            var resultEl = document.getElementById('sessionResults');
            var pagerEl = document.getElementById('sessionPager');
            if (!resultEl || !pagerEl) return;
            var query = sessionState.search.trim().toLowerCase();
            var sessions = training.sessions.filter(function (session) {
                var text = [session.title, session.type, session.location, (session.trainers || []).join(' ')].join(' ').toLowerCase();
                return (query === '' || text.indexOf(query) !== -1)
                    && (sessionState.status === 'all' || session.scheduleKey === sessionState.status);
            });
            sessions.sort(function (first, second) {
                if (sessionState.sort === 'title') return first.title.localeCompare(second.title);
                return sessionState.sort === 'date-desc' ? second.sortDate - first.sortDate : first.sortDate - second.sortDate;
            });
            var total = sessions.length;
            var totalPages = Math.max(1, Math.ceil(total / sessionState.rows));
            sessionState.page = Math.min(sessionState.page, totalPages);
            var start = (sessionState.page - 1) * sessionState.rows;
            var visible = sessions.slice(start, start + sessionState.rows);

            resultEl.innerHTML = total === 0
                ? emptyState('bi-calendar-x', training.sessions.length ? 'No sessions match these controls' : 'No sessions recorded', training.sessions.length ? 'Try another search or schedule filter.' : 'This training has no persisted session records.', 'tm-results-empty')
                : '<div class="tm-session-list">' + visible.map(function (session) {
                    var attendance = training.attendanceAvailable && session.attendance.recorded
                        ? '<span class="tm-session-attendance"><i class="bi bi-person-check" aria-hidden="true"></i>' + session.attendance.present + ' present &middot; ' + session.attendance.late + ' late &middot; ' + session.attendance.absent + ' absent</span>'
                        : '<span class="tm-session-attendance unavailable"><i class="bi bi-dash-circle" aria-hidden="true"></i>' + (training.attendanceAvailable ? 'No attendance recorded' : 'Attendance unavailable') + '</span>';
                    return '<article class="tm-session-row"><div class="tm-session-date"><span>' + esc(session.month) + '</span><strong>' + esc(session.day) + '</strong></div>'
                        + '<div class="tm-session-main"><div class="tm-session-title-line"><h4>' + esc(session.title) + '</h4><span class="tm-schedule-badge ' + esc(session.scheduleKey) + '">' + esc(session.scheduleLabel) + '</span></div>'
                        + '<div class="tm-session-meta"><span><i class="bi bi-calendar3" aria-hidden="true"></i>' + esc(session.dateLabel) + '</span><span><i class="bi bi-clock" aria-hidden="true"></i>' + esc(session.timeLabel) + '</span><span><i class="bi bi-display" aria-hidden="true"></i>' + esc(session.type) + '</span><span><i class="bi bi-geo-alt" aria-hidden="true"></i>' + esc(session.location) + '</span></div>'
                        + '<div class="tm-session-secondary"><span><i class="bi bi-person-badge" aria-hidden="true"></i>' + esc(session.trainers.length ? session.trainers.join(', ') : 'Trainer not assigned') + '</span>' + attendance + '</div>'
                        + '<div class="tm-session-actions"><button type="button" class="tm-session-attendance-btn" data-view-session-attendance="' + session.id + '"><i class="bi bi-person-check" aria-hidden="true"></i>View Attendance</button></div></div></article>';
                }).join('') + '</div>';
            pagerEl.innerHTML = pagerMarkup('sessions', sessionState.page, totalPages, total, sessionState.rows, 'sessions');
        }

        function participantControls(training) {
            var types = [];
            training.participants.forEach(function (participant) {
                if (!types.some(function (type) { return type.value === participant.typeValue; })) {
                    types.push({ value: participant.typeValue, label: participant.typeLabel });
                }
            });
            types.sort(function (first, second) { return first.label.localeCompare(second.label); });

            return '<div class="tm-section-head"><div><h3>Participants</h3><p>Persisted enrolments and their recorded attendance summary.</p></div><span class="tm-section-count">' + training.participants.length + ' enrolled</span></div>'
                + '<div class="tm-detail-controls"><div class="tm-filter-field tm-search-field"><label for="participantSearch">Search Participants</label><div class="tm-search-control"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="participantSearch" data-detail-search="participants" value="' + esc(participantState.search) + '" placeholder="Search participant name...">' + detailSearchClear('participants', participantState.search !== '', 'participant search') + '</div></div>'
                + '<div class="tm-filter-field"><label for="participantType">Classification</label><select id="participantType" data-detail-filter="participants" data-key="type"><option value="all">All classifications</option>'
                + types.map(function (type) { return '<option value="' + esc(type.value) + '"' + (participantState.type === type.value ? ' selected' : '') + '>' + esc(type.label) + '</option>'; }).join('') + '</select></div>'
                + '<div class="tm-filter-field"><label for="participantSort">Sort By</label><select id="participantSort" data-detail-filter="participants" data-key="sort"><option value="name-asc"' + (participantState.sort === 'name-asc' ? ' selected' : '') + '>Name A-Z</option><option value="name-desc"' + (participantState.sort === 'name-desc' ? ' selected' : '') + '>Name Z-A</option><option value="recent"' + (participantState.sort === 'recent' ? ' selected' : '') + '>Recently enrolled</option><option value="earliest"' + (participantState.sort === 'earliest' ? ' selected' : '') + '>Earliest enrolled</option></select></div></div>'
                + '<div id="participantResults"></div><div id="participantPager"></div>';
        }

        function renderParticipantResults(training) {
            var resultEl = document.getElementById('participantResults');
            var pagerEl = document.getElementById('participantPager');
            if (!resultEl || !pagerEl) return;
            var query = participantState.search.trim().toLowerCase();
            var participants = training.participants.filter(function (participant) {
                return (query === '' || participant.name.toLowerCase().indexOf(query) !== -1)
                    && (participantState.type === 'all' || participant.typeValue === participantState.type);
            });
            participants.sort(function (first, second) {
                if (participantState.sort === 'recent') return Number(second.enrolledAtSort || 0) - Number(first.enrolledAtSort || 0);
                if (participantState.sort === 'earliest') return Number(first.enrolledAtSort || 0) - Number(second.enrolledAtSort || 0);
                if (participantState.sort === 'name-desc') return second.name.localeCompare(first.name);
                return first.name.localeCompare(second.name);
            });
            var total = participants.length;
            var totalPages = Math.max(1, Math.ceil(total / participantState.rows));
            participantState.page = Math.min(participantState.page, totalPages);
            var start = (participantState.page - 1) * participantState.rows;
            var visible = participants.slice(start, start + participantState.rows);

            resultEl.innerHTML = total === 0
                ? emptyState('bi-person-x', training.participants.length ? 'No participants match these controls' : 'No participants enrolled', training.participants.length ? 'Try another name, classification, or sort order.' : 'This training has no persisted enrolment records.', 'tm-results-empty')
                : '<div class="tm-table-wrap"><table class="tm-table"><thead><tr><th>Participant</th><th>Classification</th><th>Enrolled</th><th>Recorded Attendance</th></tr></thead><tbody>'
                + visible.map(function (participant) {
                    var attendance = !training.attendanceAvailable
                        ? '<span class="tm-muted">Unavailable</span>'
                        : (participant.attendance.recorded
                            ? '<span class="tm-attendance-inline">' + participant.attendance.present + ' present &middot; ' + participant.attendance.late + ' late &middot; ' + participant.attendance.absent + ' absent</span>'
                            : '<span class="tm-muted">No records</span>');
                    return '<tr><td><div class="tm-person-cell"><span class="tm-avatar">' + esc((participant.name || 'P').charAt(0).toUpperCase()) + '</span><span><strong>' + esc(participant.name) + '</strong><small>' + esc(participant.enrolledAt ? 'Enrolled ' + participant.enrolledAt : 'Enrolment date not recorded') + '</small></span></div></td>'
                        + '<td><span class="tm-type-badge ' + typeTone(participant.typeLabel) + '">' + esc(participant.typeLabel) + '</span></td>'
                        + '<td><span class="tm-cell-date">' + esc(participant.enrolledAt || 'Not recorded') + '</span></td><td>' + attendance + '</td></tr>';
                }).join('') + '</tbody></table></div>';
            pagerEl.innerHTML = pagerMarkup('participants', participantState.page, totalPages, total, participantState.rows, 'participants');
        }

        function selectedAttendanceSession(training) {
            return training.sessions.find(function (session) {
                return String(session.id) === String(attendanceState.session);
            }) || null;
        }

        function attendanceControls(training) {
            var selectedSession = selectedAttendanceSession(training);
            var sessionOptions = training.sessions.map(function (session) {
                return '<option value="' + session.id + '"' + (String(attendanceState.session) === String(session.id) ? ' selected' : '') + '>' + esc(session.title + ' - ' + session.dateLabel) + '</option>';
            }).join('');
            var selection = '<div class="tm-attendance-session-picker"><div class="tm-filter-field"><label for="attendanceSession">Choose Session</label><select id="attendanceSession" data-detail-filter="attendance" data-key="session"><option value="">Select a session</option>' + sessionOptions + '</select></div></div>';

            if (!selectedSession) {
                return '<div class="tm-section-head"><div><h3>Attendance by Session</h3><p>Select one session before reviewing its participant attendance.</p></div><span class="tm-section-count">' + training.sessions.length + ' sessions</span></div>'
                    + selection
                    + emptyState('bi-calendar2-check', training.sessions.length ? 'Select a session' : 'No sessions recorded', training.sessions.length ? 'Choose a session above to view its attendance summary and participant records.' : 'Attendance cannot be reviewed until this training has a persisted session.', 'tm-results-empty tm-session-first-empty');
            }

            var selectedRecords = training.attendanceRecords.filter(function (record) {
                return String(record.sessionId) === String(selectedSession.id);
            });
            var selectedSessionHeader = '<div class="tm-section-head"><div><h3>Attendance by Session</h3><p>Read-only attendance records for the selected session only.</p></div><span class="tm-section-count">' + selectedSession.attendance.recorded + ' recorded</span></div>'
                + selection
                + '<section class="tm-selected-session" aria-label="Selected attendance session"><div><span class="tm-eyebrow">Selected Session</span><h4>' + esc(selectedSession.title) + '</h4></div><div class="tm-selected-session-meta"><span><i class="bi bi-calendar3" aria-hidden="true"></i>' + esc(selectedSession.dateLabel) + '</span><span><i class="bi bi-clock" aria-hidden="true"></i>' + esc(selectedSession.timeLabel) + '</span><span><i class="bi bi-display" aria-hidden="true"></i>' + esc(selectedSession.type) + '</span><span><i class="bi bi-geo-alt" aria-hidden="true"></i>' + esc(selectedSession.location) + '</span></div></section>';

            if (!training.attendanceAvailable) {
                return selectedSessionHeader
                    + emptyState('bi-database-x', 'Detailed attendance unavailable', 'The required read-only attendance source is not available for the selected session.', 'tm-results-empty tm-attendance-empty');
            }

            if (selectedRecords.length === 0) {
                return selectedSessionHeader
                    + emptyState('bi-person-check', 'No attendance recorded for this session', 'No present, late, or absent records have been recorded for the selected session.', 'tm-results-empty tm-attendance-empty');
            }

            return selectedSessionHeader
                + attendanceSummaryMarkup(selectedSession.attendance, training.attendanceAvailable)
                + '<div class="tm-detail-controls tm-attendance-controls"><div class="tm-filter-field tm-search-field"><label for="attendanceSearch">Search Participants</label><div class="tm-search-control"><i class="bi bi-search" aria-hidden="true"></i><input type="search" id="attendanceSearch" data-detail-search="attendance" value="' + esc(attendanceState.search) + '" placeholder="Search participant name...">' + detailSearchClear('attendance', attendanceState.search !== '', 'attendance participant search') + '</div></div>'
                + '<div class="tm-filter-field"><label for="attendanceStatus">Status</label><select id="attendanceStatus" data-detail-filter="attendance" data-key="status"><option value="all">All statuses</option><option value="present"' + (attendanceState.status === 'present' ? ' selected' : '') + '>Present</option><option value="late"' + (attendanceState.status === 'late' ? ' selected' : '') + '>Late</option><option value="absent"' + (attendanceState.status === 'absent' ? ' selected' : '') + '>Absent</option><option value="not-recorded"' + (attendanceState.status === 'not-recorded' ? ' selected' : '') + '>Not recorded</option></select></div>'
                + '<div class="tm-filter-field"><label for="attendanceSort">Sort By</label><select id="attendanceSort" data-detail-filter="attendance" data-key="sort"><option value="name-asc"' + (attendanceState.sort === 'name-asc' ? ' selected' : '') + '>Name A-Z</option><option value="name-desc"' + (attendanceState.sort === 'name-desc' ? ' selected' : '') + '>Name Z-A</option></select></div></div>'
                + '<div id="attendanceResults"></div><div id="attendancePager"></div>';
        }

        function renderAttendanceResults(training) {
            var resultEl = document.getElementById('attendanceResults');
            var pagerEl = document.getElementById('attendancePager');
            if (!resultEl || !pagerEl) return;
            if (!training.attendanceAvailable) {
                resultEl.innerHTML = emptyState('bi-database-x', 'Detailed attendance unavailable', 'The required read-only attendance source is not available.', 'tm-results-empty');
                pagerEl.innerHTML = '';
                return;
            }
            var selectedSession = selectedAttendanceSession(training);
            if (!selectedSession) return;
            var query = attendanceState.search.trim().toLowerCase();
            var records = training.attendanceRecords.filter(function (record) {
                return (query === '' || record.participantName.toLowerCase().indexOf(query) !== -1)
                    && String(record.sessionId) === String(attendanceState.session)
                    && (attendanceState.status === 'all' || record.statusKey === attendanceState.status);
            });
            records.sort(function (first, second) {
                return attendanceState.sort === 'name-desc'
                    ? second.participantName.localeCompare(first.participantName)
                    : first.participantName.localeCompare(second.participantName);
            });
            var total = records.length;
            var totalPages = Math.max(1, Math.ceil(total / attendanceState.rows));
            attendanceState.page = Math.min(attendanceState.page, totalPages);
            var start = (attendanceState.page - 1) * attendanceState.rows;
            var visible = records.slice(start, start + attendanceState.rows);

            resultEl.innerHTML = total === 0
                ? emptyState('bi-person-check', selectedSession.attendance.recorded ? 'No attendance matches these controls' : 'No attendance records for this session', selectedSession.attendance.recorded ? 'Try another participant or attendance status.' : 'No present, late, or absent entries are recorded for the selected session.', 'tm-results-empty')
                : '<div class="tm-table-wrap"><table class="tm-table tm-attendance-table"><thead><tr><th>Participant</th><th>Classification</th><th>Status</th></tr></thead><tbody>'
                + visible.map(function (record) {
                    return '<tr><td><div class="tm-person-cell"><span class="tm-avatar">' + esc((record.participantName || 'P').charAt(0).toUpperCase()) + '</span><span><strong>' + esc(record.participantName) + '</strong></span></div></td><td><span class="tm-type-badge ' + typeTone(record.participantType) + '">' + esc(record.participantType) + '</span></td><td><span class="tm-attendance-badge ' + esc(record.statusKey) + '"><i class="bi ' + (record.statusKey === 'present' ? 'bi-check-circle' : (record.statusKey === 'late' ? 'bi-clock-history' : (record.statusKey === 'absent' ? 'bi-x-circle' : 'bi-dash-circle'))) + '" aria-hidden="true"></i>' + esc(record.statusLabel) + '</span></td></tr>';
                }).join('') + '</tbody></table></div>';
            pagerEl.innerHTML = pagerMarkup('attendance', attendanceState.page, totalPages, total, attendanceState.rows, 'records');
        }

        function teachingPlanFileActions(plan) {
            if (!plan.fileAvailable) return '<span class="tm-plan-unavailable"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>File unavailable</span>';
            return '<a href="' + esc(plan.viewUrl) + '" class="tm-plan-action" target="_blank" rel="noopener"><i class="bi bi-eye" aria-hidden="true"></i>View</a>'
                + '<a href="' + esc(plan.downloadUrl) + '" class="tm-plan-action"><i class="bi bi-download" aria-hidden="true"></i>Download</a>';
        }

        function teachingPlanFileIcon(fileName) {
            var normalizedName = String(fileName || '').trim().toLowerCase();
            var extension = normalizedName.indexOf('.') === -1 ? '' : normalizedName.split('.').pop();
            var iconClass = 'bi-file-earmark-text';
            var toneClass = 'generic';

            if (extension === 'pdf') {
                iconClass = 'bi-file-earmark-pdf';
                toneClass = 'pdf';
            } else if (extension === 'doc' || extension === 'docx') {
                iconClass = 'bi-file-earmark-word';
                toneClass = 'word';
            }

            return '<span class="tm-plan-file-icon ' + toneClass + '" aria-hidden="true"><i class="bi ' + iconClass + '"></i></span>';
        }

        function teachingPlanComments(plan) {
            if (!plan.comments.length) return '';
            return '<div class="tm-plan-comments">' + plan.comments.map(function (comment) {
                return '<article class="tm-plan-comment"><div class="tm-plan-comment-meta"><strong>' + esc(comment.reviewerName) + '</strong><time>' + esc(comment.createdAt) + '</time></div><p>' + esc(comment.comment) + '</p></article>';
            }).join('') + '</div>';
        }

        function teachingPlanAdminState(trainer) {
            return {
                key: trainer.teachingPlan.stateKey,
                label: trainer.teachingPlan.stateLabel
            };
        }

        function preferredTeachingPlanTrainer(training) {
            var requested = training.trainers.find(function (trainer) {
                return String(trainer.id) === String(selectedTeachingPlanTrainerId);
            });
            if (requested) return requested;

            return training.trainers.find(function (trainer) {
                return teachingPlanAdminState(trainer).key === 'needs-review';
            }) || training.trainers.find(function (trainer) {
                return Boolean(trainer.teachingPlan.current);
            }) || training.trainers[0] || null;
        }

        function teachingPlanReview(training, trainer) {
            var teachingPlan = trainer.teachingPlan;
            var adminState = teachingPlanAdminState(trainer);
            if (!teachingPlan.current) {
                return '<section class="tm-plan-review" aria-labelledby="teaching-plan-review-title">'
                    + '<div class="tm-section-head"><div><h3 id="teaching-plan-review-title">' + esc(trainer.name) + '</h3><p>No Teaching Plan submission yet.</p></div><span class="tm-plan-state not-submitted">Not Submitted</span></div>'
                    + '<div class="tm-plan-inline-empty"><i class="bi bi-file-earmark-plus" aria-hidden="true"></i><div><strong>Nothing to review yet</strong><span>The trainer can submit a Teaching Plan from their training workspace.</span></div></div></section>';
            }

            var current = teachingPlan.current;
            return '<section class="tm-plan-review" aria-labelledby="teaching-plan-review-title">'
                + '<div class="tm-section-head"><div><h3 id="teaching-plan-review-title">' + esc(trainer.name) + '</h3><p>Review the current submission and its Admin feedback.</p></div><span class="tm-plan-state ' + adminState.key + '">' + adminState.label + '</span></div>'
                + '<section class="tm-plan-current-section" aria-labelledby="current-teaching-plan-title"><div class="tm-plan-section-heading"><div><span>Active document</span><h4 id="current-teaching-plan-title">Current Submission</h4></div></div>'
                + '<div class="tm-plan-current">' + teachingPlanFileIcon(current.originalName) + '<div class="tm-plan-file-copy"><strong>' + esc(current.originalName) + '</strong><span>Submitted ' + esc(current.submittedAt) + '</span></div><div class="tm-plan-actions">' + teachingPlanFileActions(current) + '</div></div></section>'
                + '<section class="tm-plan-feedback" aria-labelledby="admin-feedback-title"><div class="tm-plan-section-heading"><div><h4 id="admin-feedback-title">Admin Feedback</h4><p>Feedback attached to the current submission.</p></div></div>'
                + (!current.comments.length ? '<div class="tm-plan-needs-review"><strong>Needs Review</strong><span>This submission has not received Admin feedback yet.</span></div>' : teachingPlanComments(current))
                + '<form class="tm-plan-feedback-form" method="POST" action="' + esc(current.feedbackUrl) + '"><input type="hidden" name="_token" value="' + esc(csrfToken) + '"><div class="tm-plan-form-heading"><h5>Add Feedback</h5><p>Share clear feedback with the trainer.</p></div><label for="teachingPlanFeedback">Feedback</label><textarea id="teachingPlanFeedback" name="comment" rows="4" maxlength="2000" required placeholder="Write feedback for this submission..."></textarea><div><small>Maximum 2,000 characters</small><button type="submit"><i class="bi bi-send" aria-hidden="true"></i>Send Feedback</button></div></form></section></section>';
        }

        function renderTeachingPlans(training, panel) {
            if (!training.trainers.length) {
                panel.innerHTML = '<div class="tm-section-head"><div><h3>Teaching Plans</h3><p>Plans are listed by assigned trainer.</p></div></div>'
                    + emptyState('bi-person-x', 'No trainers assigned', 'A Teaching Plan can be submitted after a trainer is assigned through the training schedule.', 'tm-results-empty');
                return;
            }

            var selectedTrainer = preferredTeachingPlanTrainer(training);
            selectedTeachingPlanTrainerId = selectedTrainer ? String(selectedTrainer.id) : null;
            panel.innerHTML = '<div class="tm-plan-tab-head"><div><h3>Teaching Plans</h3><p>Review teaching plans submitted by assigned trainers and provide feedback.</p></div></div>'
                + '<div class="tm-plan-layout"><aside class="tm-plan-master" aria-label="Assigned trainers"><div class="tm-plan-master-head"><strong>Assigned Trainers</strong><span>Select a trainer to review</span></div><div class="tm-plan-trainer-list">'
                + training.trainers.map(function (trainer) {
                    var plan = trainer.teachingPlan;
                    var state = teachingPlanAdminState(trainer);
                    var selected = selectedTrainer && String(selectedTrainer.id) === String(trainer.id);
                    return '<button type="button" class="tm-plan-trainer' + (selected ? ' is-selected' : '') + '" data-review-teaching-plan="' + trainer.id + '" aria-pressed="' + (selected ? 'true' : 'false') + '"><span class="tm-plan-trainer-copy"><strong>' + esc(trainer.name) + '</strong>'
                        + (plan.current ? '<span>' + esc(plan.current.originalName) + '</span><small>Submitted ' + esc(plan.current.submittedAt) + '</small>' : '<span>No Teaching Plan uploaded</span>')
                        + '</span><span class="tm-plan-row-state"><span class="tm-plan-state ' + state.key + '">' + state.label + '</span></span></button>';
                }).join('') + '</div></aside><div class="tm-plan-review-shell">'
                + (selectedTrainer ? teachingPlanReview(training, selectedTrainer) : emptyState('bi-file-earmark-text', 'No Teaching Plans available', 'Assigned trainers will appear here when available.', 'tm-plan-review-empty'))
                + '</div></div>';
        }

        function renderActiveTab() {
            var training = DATA[currentId];
            var panel = document.getElementById('trainingTabPanel');
            if (!training || !panel) return;
            detail.querySelectorAll('[data-training-tab]').forEach(function (button) {
                var selected = button.dataset.trainingTab === activeTab;
                button.classList.toggle('active', selected);
                button.setAttribute('aria-selected', selected ? 'true' : 'false');
            });

            if (activeTab === 'teaching-plans') {
                renderTeachingPlans(training, panel);
            } else if (activeTab === 'sessions') {
                panel.innerHTML = sessionControls(training);
                renderSessionResults(training);
            } else if (activeTab === 'participants') {
                panel.innerHTML = participantControls(training);
                renderParticipantResults(training);
            } else if (activeTab === 'attendance') {
                panel.innerHTML = attendanceControls(training);
                renderAttendanceResults(training);
            } else {
                renderOverview(training, panel);
            }
        }

        function selectTraining(id, updateUrl) {
            id = String(id);
            if (!DATA[id]) return;
            currentId = id;
            activeTab = 'overview';
            selectedTeachingPlanTrainerId = null;
            sessionState = { search: '', status: 'all', sort: 'date-asc', page: 1, rows: 10 };
            participantState = { search: '', type: 'all', sort: 'name-asc', page: 1, rows: 10 };
            attendanceState = { search: '', session: '', status: 'all', sort: 'name-asc', page: 1, rows: 10 };
            syncMasterSelection();
            renderDetail(id);

            if (updateUrl !== false) {
                try {
                    var url = new URL(window.location.href);
                    url.searchParams.set('course', id);
                    history.replaceState(null, '', url);
                } catch (error) {}
            }
        }

        if (listEl) {
            listEl.addEventListener('click', function (event) {
                var row = event.target.closest('[data-training-id]');
                if (row) selectTraining(row.dataset.trainingId, true);
            });
        }

        masterToggles.forEach(function (button) {
            button.addEventListener('click', function () {
                masterCollapsePreference = !master.classList.contains('is-collapsed');
                setMasterCollapsed(masterCollapsePreference, true);
            });
        });

        [searchInput, scheduleFilter, modeFilter, trainerFilter].forEach(function (control) {
            if (!control) return;
            control.addEventListener(control === searchInput ? 'input' : 'change', function () {
                renderMaster();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('search', function () {
                renderMaster();
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', function () {
                searchInput.value = '';
                renderMaster();
                searchInput.focus();
            });
        }

        if (filterToggle) {
            filterToggle.addEventListener('click', function () {
                filterPanelOpen = !filterPanelOpen;
                syncFilterPanel();
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                scheduleFilter.value = 'all';
                modeFilter.value = 'all';
                trainerFilter.value = 'all';
                renderMaster();
            });
        }

        if (detail) {
            detail.addEventListener('click', function (event) {
                var materialAccessToggle = event.target.closest('[data-material-access-toggle]');
                if (materialAccessToggle) {
                    updateMaterialAccess(materialAccessToggle);
                    return;
                }

                var teachingPlanButton = event.target.closest('[data-review-teaching-plan]');
                if (teachingPlanButton) {
                    selectedTeachingPlanTrainerId = String(teachingPlanButton.dataset.reviewTeachingPlan);
                    renderActiveTab();
                    try {
                        var reviewUrl = new URL(window.location.href);
                        reviewUrl.searchParams.set('tab', 'teaching-plans');
                        reviewUrl.searchParams.set('trainer', selectedTeachingPlanTrainerId);
                        history.replaceState(null, '', reviewUrl);
                    } catch (error) {}
                    return;
                }

                var sessionAttendanceButton = event.target.closest('[data-view-session-attendance]');
                if (sessionAttendanceButton) {
                    attendanceState = { search: '', session: String(sessionAttendanceButton.dataset.viewSessionAttendance), status: 'all', sort: 'name-asc', page: 1, rows: 10 };
                    activeTab = 'attendance';
                    renderActiveTab();
                    var detailTabs = detail.querySelector('.tm-detail-tabs');
                    if (detailTabs) detailTabs.scrollIntoView({ behavior: reducedMotion() ? 'auto' : 'smooth', block: 'start' });
                    return;
                }

                var tabButton = event.target.closest('[data-training-tab]');
                if (tabButton) {
                    activeTab = tabButton.dataset.trainingTab;
                    if (activeTab !== 'teaching-plans') selectedTeachingPlanTrainerId = null;
                    renderActiveTab();
                    try {
                        var tabUrl = new URL(window.location.href);
                        tabUrl.searchParams.set('tab', activeTab);
                        if (activeTab !== 'teaching-plans') tabUrl.searchParams.delete('trainer');
                        history.replaceState(null, '', tabUrl);
                    } catch (error) {}
                    return;
                }

                var clearButton = event.target.closest('[data-detail-clear]');
                if (clearButton) {
                    var clearScope = clearButton.dataset.detailClear;
                    var clearState = clearScope === 'sessions' ? sessionState : (clearScope === 'participants' ? participantState : attendanceState);
                    var clearInput = detail.querySelector('[data-detail-search="' + clearScope + '"]');
                    clearState.search = '';
                    clearState.page = 1;
                    if (clearInput) clearInput.value = '';
                    clearButton.hidden = true;
                    if (clearScope === 'sessions') renderSessionResults(DATA[currentId]);
                    if (clearScope === 'participants') renderParticipantResults(DATA[currentId]);
                    if (clearScope === 'attendance') renderAttendanceResults(DATA[currentId]);
                    if (clearInput) clearInput.focus();
                    return;
                }

                var pageButton = event.target.closest('[data-detail-page]');
                if (pageButton) {
                    var pageScope = pageButton.dataset.detailPage;
                    var pageState = pageScope === 'sessions' ? sessionState : (pageScope === 'participants' ? participantState : attendanceState);
                    if (pageButton.dataset.direction === 'prev' && pageState.page > 1) pageState.page--;
                    if (pageButton.dataset.direction === 'next') pageState.page++;
                    if (pageScope === 'sessions') renderSessionResults(DATA[currentId]);
                    if (pageScope === 'participants') renderParticipantResults(DATA[currentId]);
                    if (pageScope === 'attendance') renderAttendanceResults(DATA[currentId]);
                }
            });

            function handleDetailSearch(event) {
                var scope = event.target.dataset.detailSearch;
                if (!scope) return;
                var state = scope === 'sessions' ? sessionState : (scope === 'participants' ? participantState : attendanceState);
                state.search = event.target.value;
                state.page = 1;
                var clearButton = detail.querySelector('[data-detail-clear="' + scope + '"]');
                if (clearButton) clearButton.hidden = event.target.value === '';
                if (scope === 'sessions') renderSessionResults(DATA[currentId]);
                if (scope === 'participants') renderParticipantResults(DATA[currentId]);
                if (scope === 'attendance') renderAttendanceResults(DATA[currentId]);
            }
            detail.addEventListener('input', handleDetailSearch);
            detail.addEventListener('search', handleDetailSearch);

            detail.addEventListener('change', function (event) {
                var rowScope = event.target.dataset.detailRows;
                if (rowScope) {
                    var rowState = rowScope === 'sessions' ? sessionState : (rowScope === 'participants' ? participantState : attendanceState);
                    rowState.rows = Number(event.target.value) || 10;
                    rowState.page = 1;
                    if (rowScope === 'sessions') renderSessionResults(DATA[currentId]);
                    if (rowScope === 'participants') renderParticipantResults(DATA[currentId]);
                    if (rowScope === 'attendance') renderAttendanceResults(DATA[currentId]);
                    return;
                }

                var scope = event.target.dataset.detailFilter;
                var key = event.target.dataset.key;
                if (!scope || !key) return;
                var state = scope === 'sessions' ? sessionState : (scope === 'participants' ? participantState : attendanceState);
                state[key] = event.target.value;
                state.page = 1;
                if (scope === 'sessions') renderSessionResults(DATA[currentId]);
                if (scope === 'participants') renderParticipantResults(DATA[currentId]);
                if (scope === 'attendance' && key === 'session') renderActiveTab();
                else if (scope === 'attendance') renderAttendanceResults(DATA[currentId]);
            });
        }

        if (window.matchMedia) {
            var compactWorkspaceQuery = window.matchMedia('(max-width: 991.98px)');
            var syncMasterForViewport = function () { setMasterCollapsed(masterCollapsePreference, false); };
            if (compactWorkspaceQuery.addEventListener) compactWorkspaceQuery.addEventListener('change', syncMasterForViewport);
            else if (compactWorkspaceQuery.addListener) compactWorkspaceQuery.addListener(syncMasterForViewport);
        }

        var params = new URLSearchParams(window.location.search);
        var requestedId = params.get('course');
        var requestedTab = params.get('tab');
        var requestedTrainerId = params.get('trainer');
        if (requestedId && DATA[requestedId]) currentId = String(requestedId);
        if (['overview', 'teaching-plans', 'sessions', 'participants', 'attendance'].indexOf(requestedTab) !== -1) activeTab = requestedTab;
        if (requestedTrainerId) selectedTeachingPlanTrainerId = String(requestedTrainerId);
        renderMaster();
        if (!currentId && masterRows.length) selectTraining(masterRows[0].dataset.trainingId, false);
        if (currentId && DATA[currentId]) {
            syncMasterSelection();
            renderDetail(currentId);
        }
        setMasterCollapsed(false, false);
    });
</script>
