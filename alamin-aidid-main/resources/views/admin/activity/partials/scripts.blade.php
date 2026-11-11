<script>
    document.addEventListener('DOMContentLoaded', function () {
        var rows = Array.prototype.slice.call(document.querySelectorAll('#admTimeline .act-row'));
        var timeline = document.getElementById('admTimeline');
        var trainerSelect = document.getElementById('admTrainerFilter');
        var actionSelect = document.getElementById('admActionFilter');
        var searchInput = document.getElementById('admSearch');
        var searchClear = document.querySelector('[data-admin-activity-search-clear]');
        var resetButton = document.getElementById('admResetBtn');
        var rangeButtons = document.querySelectorAll('#admRange .act-range-btn');
        var sortSelect = document.getElementById('admSort');
        var rowsSelect = document.querySelector('[data-admin-activity-rows]');
        var emptyEl = document.getElementById('admEmpty');
        var pagerControls = document.getElementById('admPagerControls');
        var countEl = document.getElementById('admCount');
        var anchor = document.querySelector('.adm-list-anchor');
        var customPeriodForm = document.querySelector('[data-activity-custom-period]');
        var periodContext = document.getElementById('activityPeriodContext');
        var metricCards = {
            today: document.querySelector('[data-activity-metric="today"]'),
            week: document.querySelector('[data-activity-metric="week"]'),
            trainer: document.querySelector('[data-activity-metric="trainer"]'),
            action: document.querySelector('[data-activity-metric="action"]')
        };

        var perPage = rowsSelect ? Number(rowsSelect.value) || 10 : 10;
        var currentPage = 1;
        var range = timeline ? (timeline.dataset.initialPeriod || 'all') : 'all';
        var sort = sortSelect ? sortSelect.value : 'desc';
        var customFrom = timeline ? timeline.dataset.customFrom || '' : '';
        var customTo = timeline ? timeline.dataset.customTo || '' : '';
        var appToday = timeline ? timeline.dataset.appToday || '' : '';
        var appWeekFrom = timeline ? timeline.dataset.appWeekFrom || '' : '';
        var appMonthFrom = timeline ? timeline.dataset.appMonthFrom || '' : '';

        function inSelectedRange(timestamp, dayValue) {
            if (range === 'all') return true;
            if (!timestamp || !dayValue) return false;

            if (range === 'custom') {
                return Boolean(customFrom && customTo && dayValue >= customFrom && dayValue <= customTo);
            }

            var fromValue = range === 'today' ? appToday : (range === '7' ? appWeekFrom : appMonthFrom);

            return Boolean(fromValue && appToday && dayValue >= fromValue && dayValue <= appToday);
        }

        function syncQueryState() {
            try {
                var url = new URL(document.location.href);
                function setOrDelete(key, value, defaultValue) {
                    if (value && value !== defaultValue) url.searchParams.set(key, value);
                    else url.searchParams.delete(key);
                }
                setOrDelete('search', searchInput ? searchInput.value.trim() : '', '');
                setOrDelete('trainer', trainerSelect ? trainerSelect.value : 'all', 'all');
                setOrDelete('action', actionSelect ? actionSelect.value : 'all', 'all');
                setOrDelete('sort', sortSelect ? sortSelect.value : 'desc', 'desc');
                setOrDelete('rows', rowsSelect ? rowsSelect.value : '10', '10');
                if (range === 'custom' && customFrom && customTo) {
                    url.searchParams.set('period', 'custom');
                    url.searchParams.set('from', customFrom);
                    url.searchParams.set('to', customTo);
                } else {
                    url.searchParams.delete('period');
                    url.searchParams.delete('from');
                    url.searchParams.delete('to');
                }
                history.replaceState(null, '', url);
            } catch (error) {}
        }

        function getMatches() {
            var trainer = trainerSelect ? trainerSelect.value : 'all';
            var action = actionSelect ? actionSelect.value : 'all';
            var query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();

            var matches = rows.filter(function (row) {
                var timestamp = Number(row.dataset.ts || 0);
                if (!inSelectedRange(timestamp, row.dataset.day || '')) return false;
                if (trainer !== 'all' && row.dataset.trainerId !== trainer) return false;
                if (action !== 'all' && row.dataset.action !== action) return false;
                if (query !== '' && (row.dataset.search || '').toLowerCase().indexOf(query) === -1) return false;

                return true;
            });

            matches.sort(function (first, second) {
                var firstTimestamp = Number(first.dataset.ts || 0);
                var secondTimestamp = Number(second.dataset.ts || 0);

                return sort === 'asc'
                    ? firstTimestamp - secondTimestamp
                    : secondTimestamp - firstTimestamp;
            });

            return matches;
        }

        function activeRangeContext() {
            var activeButton = document.querySelector('#admRange .act-range-btn.active');
            var context = activeButton ? activeButton.querySelector('small') : null;

            return context ? context.textContent.trim() : 'All recorded dates';
        }

        function updatePeriodContext() {
            if (!periodContext) return;
            var label = activeRangeContext();
            var strong = periodContext.querySelector('strong');
            if (strong) strong.textContent = label;
        }

        function updateMetric(card, value, primaryNote, secondaryNote) {
            if (!card) return;
            var valueEl = card.querySelector('.adm-stat-value');
            var noteStrong = card.querySelector('.adm-stat-note strong');
            var noteSpan = card.querySelector('.adm-stat-note span');
            if (valueEl) valueEl.textContent = value;
            if (noteStrong) noteStrong.textContent = primaryNote;
            if (noteSpan) noteSpan.textContent = secondaryNote;
        }

        function updatePeriodStatistics() {
            var selectedRows = rows.filter(function (row) {
                return inSelectedRange(Number(row.dataset.ts || 0), row.dataset.day || '');
            });
            var todayCount = selectedRows.filter(function (row) { return row.dataset.day === appToday; }).length;
            var weekCount = selectedRows.filter(function (row) {
                var day = row.dataset.day || '';
                return day >= appWeekFrom && day <= appToday;
            }).length;
            var trainerCounts = {};
            var actionCounts = {};
            var actionLabels = {};

            selectedRows.forEach(function (row) {
                var trainerName = (row.dataset.trainerName || '').trim();
                var actionKey = row.dataset.action || '';
                if (trainerName) trainerCounts[trainerName] = (trainerCounts[trainerName] || 0) + 1;
                if (actionKey) {
                    actionCounts[actionKey] = (actionCounts[actionKey] || 0) + 1;
                    actionLabels[actionKey] = row.dataset.actionLabel || actionKey;
                }
            });

            function mostFrequent(counts) {
                return Object.keys(counts).sort(function (first, second) {
                    return counts[second] - counts[first] || first.localeCompare(second);
                })[0] || '';
            }

            var topTrainer = mostFrequent(trainerCounts);
            var topAction = mostFrequent(actionCounts);
            var periodLabel = activeRangeContext();
            var todayLabel = document.querySelector('[data-range="today"] small');
            var weekLabel = document.querySelector('[data-range="7"] small');

            updateMetric(metricCards.today, todayCount, 'Recorded actions', todayLabel ? todayLabel.textContent.trim() : 'Today');
            updateMetric(metricCards.week, weekCount, 'Recorded actions', weekLabel ? weekLabel.textContent.trim() : 'Last 7 days');
            updateMetric(metricCards.trainer, topTrainer || 'Not available', topTrainer ? trainerCounts[topTrainer] + ' recorded actions' : 'No recorded activity', periodLabel);
            updateMetric(metricCards.action, topAction ? actionLabels[topAction] : 'Not available', topAction ? actionCounts[topAction] + ' occurrences' : 'No recorded activity', periodLabel);
        }

        function dayLabel(dateValue) {
            if (!dateValue) return 'Date unavailable';

            var parts = dateValue.split('-');
            var date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            var yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);

            if (date.getTime() === today.getTime()) return 'Today';
            if (date.getTime() === yesterday.getTime()) return 'Yesterday';

            return date.toLocaleDateString(undefined, {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }

        function createDayGroup(dateValue) {
            var group = document.createElement('section');
            var heading = document.createElement('div');
            var headingText = document.createElement('h3');
            var line = document.createElement('span');
            var list = document.createElement('div');
            var headingId = 'activity-day-' + (dateValue || 'undated');

            group.className = 'act-day-group';
            group.setAttribute('aria-labelledby', headingId);
            heading.className = 'act-day-heading';
            headingText.id = headingId;
            headingText.textContent = dayLabel(dateValue);
            line.className = 'line';
            line.setAttribute('aria-hidden', 'true');
            list.className = 'act-day-list';
            heading.appendChild(headingText);
            heading.appendChild(line);
            group.appendChild(heading);
            group.appendChild(list);

            return { group: group, list: list };
        }

        function renderPager(totalPages, total) {
            if (!pagerControls) return;

            pagerControls.innerHTML = '<button type="button" class="act-page-btn" data-page="prev"' + (currentPage === 1 || total === 0 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i>Previous</button>'
                + '<span class="act-page-current" aria-current="page">' + currentPage + ' / ' + totalPages + '</span>'
                + '<button type="button" class="act-page-btn" data-page="next"' + (currentPage === totalPages || total === 0 ? ' disabled' : '') + '>Next<i class="bi bi-chevron-right" aria-hidden="true"></i></button>';

            pagerControls.querySelectorAll('[data-page]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (button.dataset.page === 'prev' && currentPage > 1) currentPage--;
                    if (button.dataset.page === 'next' && currentPage < totalPages) currentPage++;
                    render();

                    if (anchor) {
                        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        window.scrollTo({
                            top: anchor.offsetTop - 80,
                            behavior: reducedMotion ? 'auto' : 'smooth'
                        });
                    }
                });
            });
        }

        function controlsAreActive() {
            var hasSearch = Boolean(searchInput && searchInput.value.trim() !== '');
            var hasTrainer = Boolean(trainerSelect && trainerSelect.value !== 'all');
            var hasAction = Boolean(actionSelect && actionSelect.value !== 'all');
            var hasSort = Boolean(sortSelect && sortSelect.value !== 'desc');

            return range !== 'all' || hasSearch || hasTrainer || hasAction || hasSort;
        }

        function syncControls() {
            if (searchClear) searchClear.hidden = !searchInput || searchInput.value === '';
            if (resetButton) resetButton.disabled = !controlsAreActive();
        }

        function render() {
            if (!timeline) return;

            var matches = getMatches();
            var total = matches.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            var start = (currentPage - 1) * perPage;
            var pageRows = matches.slice(start, start + perPage);
            timeline.innerHTML = '';

            if (total === 0) {
                timeline.hidden = true;
                if (emptyEl) emptyEl.hidden = false;
            } else {
                timeline.hidden = false;
                if (emptyEl) emptyEl.hidden = true;

                var currentDay = null;
                var currentList = null;

                pageRows.forEach(function (row) {
                    var day = row.dataset.day || '';
                    if (day !== currentDay) {
                        var dayGroup = createDayGroup(day);
                        timeline.appendChild(dayGroup.group);
                        currentList = dayGroup.list;
                        currentDay = day;
                    }

                    currentList.appendChild(row);
                });
            }

            if (countEl) {
                countEl.innerHTML = total === 0
                    ? '<strong>0</strong> activities'
                    : 'Showing <strong>' + (start + 1) + '&ndash;' + Math.min(start + perPage, total) + '</strong> of <strong>' + total + '</strong> activities';
            }

            syncControls();
            renderPager(totalPages, total);
        }

        if (trainerSelect) {
            trainerSelect.addEventListener('change', function () {
                currentPage = 1;
                syncQueryState();
                render();
            });
        }

        if (actionSelect) {
            actionSelect.addEventListener('change', function () {
                currentPage = 1;
                syncQueryState();
                render();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentPage = 1;
                syncQueryState();
                render();
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', function () {
                searchInput.value = '';
                currentPage = 1;
                syncQueryState();
                render();
                searchInput.focus();
            });
        }

        rangeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                rangeButtons.forEach(function (otherButton) {
                    otherButton.classList.remove('active');
                    otherButton.setAttribute('aria-pressed', 'false');
                });
                button.classList.add('active');
                button.setAttribute('aria-pressed', 'true');
                range = button.dataset.range;
                if (customPeriodForm) customPeriodForm.hidden = range !== 'custom';
                if (range !== 'custom') syncQueryState();
                updatePeriodContext();
                updatePeriodStatistics();
                currentPage = 1;
                if (range !== 'custom' || (customFrom && customTo)) render();
            });
        });

        if (customPeriodForm) {
            customPeriodForm.addEventListener('submit', function (event) {
                var fromInput = customPeriodForm.querySelector('[name="from"]');
                var toInput = customPeriodForm.querySelector('[name="to"]');
                if (!fromInput || !toInput) return;
                toInput.setCustomValidity('');
                if (fromInput.value && toInput.value && fromInput.value > toInput.value) {
                    event.preventDefault();
                    toInput.setCustomValidity('The end date must be the same as or later than the start date.');
                    toInput.reportValidity();
                    return;
                }

                ['search', 'trainer', 'action', 'sort', 'rows'].forEach(function (name) {
                    var existing = customPeriodForm.querySelector('input[type="hidden"][name="' + name + '"]');
                    if (existing) existing.remove();
                });
                var controlValues = {
                    search: searchInput ? searchInput.value.trim() : '',
                    trainer: trainerSelect ? trainerSelect.value : 'all',
                    action: actionSelect ? actionSelect.value : 'all',
                    sort: sortSelect ? sortSelect.value : 'desc',
                    rows: rowsSelect ? rowsSelect.value : '10'
                };
                Object.keys(controlValues).forEach(function (name) {
                    var value = controlValues[name];
                    if (!value || value === 'all' || value === 'desc' || value === '10') return;
                    var hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = name;
                    hidden.value = value;
                    customPeriodForm.appendChild(hidden);
                });
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', function () {
                sort = sortSelect.value;
                currentPage = 1;
                syncQueryState();
                render();
            });
        }

        if (rowsSelect) {
            rowsSelect.addEventListener('change', function () {
                perPage = Number(rowsSelect.value) || 10;
                currentPage = 1;
                syncQueryState();
                render();
            });
        }

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                if (trainerSelect) trainerSelect.value = 'all';
                if (actionSelect) actionSelect.value = 'all';
                if (searchInput) searchInput.value = '';
                if (sortSelect) sortSelect.value = 'desc';
                rangeButtons.forEach(function (button) {
                    var isAll = button.dataset.range === 'all';
                    button.classList.toggle('active', isAll);
                    button.setAttribute('aria-pressed', isAll ? 'true' : 'false');
                });
                range = 'all';
                customFrom = '';
                customTo = '';
                sort = 'desc';
                if (customPeriodForm) {
                    customPeriodForm.hidden = true;
                    var resetFrom = customPeriodForm.querySelector('[name="from"]');
                    var resetTo = customPeriodForm.querySelector('[name="to"]');
                    if (resetFrom) resetFrom.value = '';
                    if (resetTo) {
                        resetTo.value = '';
                        resetTo.setCustomValidity('');
                    }
                }
                syncQueryState();
                updatePeriodContext();
                updatePeriodStatistics();
                currentPage = 1;
                render();
            });
        }

        if (customPeriodForm) customPeriodForm.hidden = range !== 'custom';
        updatePeriodContext();
        updatePeriodStatistics();
        render();
    });
</script>
