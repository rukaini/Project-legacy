<script>
    document.addEventListener('DOMContentLoaded', function () {
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (character) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[character];
            });
        }

        function passSearch(row, query) {
            return query === '' || (row.dataset.search || '').toLowerCase().includes(query);
        }

        function passSelect(row, select) {
            var value = select.value;
            if (value === 'all' || value === '') return true;
            var rowValue = row.getAttribute('data-' + select.dataset.key) || '';
            return select.dataset.match === 'contains'
                ? rowValue.includes(',' + value + ',')
                : rowValue === value;
        }

        function rowMatches(row, query, selects, except) {
            if (!passSearch(row, query)) return false;
            return selects.every(function (select) {
                return select === except || passSelect(row, select);
            });
        }

        function compareRows(first, second, sort) {
            if (sort.indexOf('date-') === 0) {
                var firstDate = Number(first.dataset.sortDate || 0);
                var secondDate = Number(second.dataset.sortDate || 0);
                if (firstDate === 0 && secondDate !== 0) return 1;
                if (secondDate === 0 && firstDate !== 0) return -1;
                return sort === 'date-asc' ? firstDate - secondDate : secondDate - firstDate;
            }

            var direction = sort === 'name-desc' ? -1 : 1;
            return (first.dataset.sortName || '').localeCompare(second.dataset.sortName || '') * direction;
        }

        function pagerMarkup(noun, page, totalPages, total, rows) {
            var start = total === 0 ? 0 : ((page - 1) * rows) + 1;
            var end = Math.min(page * rows, total);

            return '<label class="dir-rows-control"><span>Rows</span><select data-directory-rows aria-label="Rows per page">'
                + [10, 25, 50].map(function (option) {
                    return '<option value="' + option + '"' + (option === rows ? ' selected' : '') + '>' + option + '</option>';
                }).join('')
                + '</select></label>'
                + '<span class="dir-page-info" aria-live="polite">' + (total === 0
                    ? '<strong>0</strong> ' + noun
                    : 'Showing <strong>' + start + '&ndash;' + end + '</strong> of <strong>' + total + '</strong> ' + noun) + '</span>'
                + '<div class="dir-page-controls">'
                + '<button type="button" class="dir-page-btn" data-directory-page="prev"' + (page === 1 || total === 0 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i>Previous</button>'
                + '<span class="dir-current-page" aria-current="page">' + page + ' / ' + totalPages + '</span>'
                + '<button type="button" class="dir-page-btn" data-directory-page="next"' + (page === totalPages || total === 0 ? ' disabled' : '') + '>Next<i class="bi bi-chevron-right" aria-hidden="true"></i></button>'
                + '</div>';
        }

        function setupPanel(panel) {
            var search = panel.querySelector('.dir-search');
            var searchClear = panel.querySelector('[data-directory-search-clear]');
            var selects = Array.from(panel.querySelectorAll('.dir-filter'));
            var sort = panel.querySelector('.dir-sort');
            var reset = panel.querySelector('.dir-reset');
            var rows = Array.from(panel.querySelectorAll('tbody .dir-row'));
            var tbody = panel.querySelector('tbody');
            var frame = panel.querySelector('[data-directory-table-frame]');
            var empty = panel.querySelector('[data-directory-empty]');
            var emptyTitle = panel.querySelector('[data-directory-empty-title]');
            var emptyMessage = panel.querySelector('[data-directory-empty-message]');
            var pager = panel.querySelector('[data-directory-pager]');
            var noun = panel.dataset.directoryNoun || 'records';
            var defaultSort = sort ? sort.options[0].value : 'name-asc';
            var page = 1;
            var rowsPerPage = 10;

            function controlsActive() {
                return Boolean(
                    (search && search.value.trim() !== '')
                    || selects.some(function (select) { return select.value !== 'all'; })
                    || (sort && sort.value !== defaultSort)
                );
            }

            function refreshFacets(query) {
                selects.forEach(function (select) {
                    var pool = rows.filter(function (row) { return rowMatches(row, query, selects, select); });
                    var allowed = {};
                    pool.forEach(function (row) {
                        var value = row.getAttribute('data-' + select.dataset.key) || '';
                        if (select.dataset.match === 'contains') {
                            value.split(',').forEach(function (part) { if (part !== '') allowed[part] = true; });
                        } else if (value !== '') {
                            allowed[value] = true;
                        }
                    });
                    Array.from(select.options).forEach(function (option) {
                        option.hidden = option.value !== 'all' && !allowed[option.value] && option.value !== select.value;
                    });
                });
            }

            function render() {
                var query = search ? search.value.trim().toLowerCase() : '';
                refreshFacets(query);
                var matches = rows
                    .filter(function (row) { return rowMatches(row, query, selects, null); })
                    .sort(function (first, second) { return compareRows(first, second, sort ? sort.value : defaultSort); });
                var totalPages = Math.max(1, Math.ceil(matches.length / rowsPerPage));
                if (page > totalPages) page = totalPages;
                var visible = matches.slice((page - 1) * rowsPerPage, page * rowsPerPage);

                rows.forEach(function (row) { row.hidden = true; });
                matches.forEach(function (row) { if (tbody) tbody.appendChild(row); });
                visible.forEach(function (row) { row.hidden = false; });

                var hasMatches = matches.length > 0;
                if (frame) frame.hidden = !hasMatches;
                if (empty) empty.hidden = hasMatches;
                if (!hasMatches) {
                    var noRecords = rows.length === 0;
                    if (emptyTitle) emptyTitle.textContent = panel.dataset[noRecords ? 'emptyTitle' : 'noResultsTitle'];
                    if (emptyMessage) emptyMessage.textContent = panel.dataset[noRecords ? 'emptyMessage' : 'noResultsMessage'];
                }
                if (searchClear) searchClear.hidden = !search || search.value === '';
                if (reset) reset.disabled = !controlsActive();

                if (pager) {
                    pager.innerHTML = pagerMarkup(noun, page, totalPages, matches.length, rowsPerPage);
                    var rowsSelect = pager.querySelector('[data-directory-rows]');
                    if (rowsSelect) {
                        rowsSelect.addEventListener('change', function () {
                            rowsPerPage = Number(rowsSelect.value) || 10;
                            page = 1;
                            render();
                        });
                    }
                    pager.querySelectorAll('[data-directory-page]').forEach(function (button) {
                        button.addEventListener('click', function () {
                            if (button.dataset.directoryPage === 'prev' && page > 1) page--;
                            if (button.dataset.directoryPage === 'next' && page < totalPages) page++;
                            render();
                        });
                    });
                }
            }

            function resetPage() {
                page = 1;
                render();
            }

            if (search) {
                search.addEventListener('input', resetPage);
                search.addEventListener('search', resetPage);
            }
            if (searchClear) {
                searchClear.addEventListener('click', function () {
                    search.value = '';
                    page = 1;
                    render();
                    search.focus();
                });
            }
            selects.forEach(function (select) { select.addEventListener('change', resetPage); });
            if (sort) sort.addEventListener('change', resetPage);
            if (reset) {
                reset.addEventListener('click', function () {
                    if (search) search.value = '';
                    selects.forEach(function (select) { select.value = 'all'; });
                    if (sort) sort.value = defaultSort;
                    resetPage();
                });
            }

            panel._renderDirectory = resetPage;
            render();
        }

        document.querySelectorAll('[data-directory-panel]').forEach(setupPanel);

        var tabs = Array.from(document.querySelectorAll('.dir-section-tab'));
        var panels = Array.from(document.querySelectorAll('[data-directory-panel]'));

        function activateTab(name, moveFocus) {
            var activeTab = null;
            tabs.forEach(function (tab) {
                var active = tab.dataset.tab === name;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                if (active) activeTab = tab;
            });
            panels.forEach(function (panel) {
                var active = panel.id === 'tab-' + name;
                panel.classList.toggle('active', active);
                panel.hidden = !active;
                if (active && panel._renderDirectory) panel._renderDirectory();
            });
            if (moveFocus && activeTab) activeTab.focus();
            return Boolean(activeTab);
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () { activateTab(tab.dataset.tab, false); });
            tab.addEventListener('keydown', function (event) {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                var targetIndex = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
                activateTab(tabs[targetIndex].dataset.tab, true);
            });
        });

        var urlParams = new URLSearchParams(window.location.search);
        var requestedTab = urlParams.get('tab');
        if (requestedTab) activateTab(requestedTab, false);

        var activePanel = document.querySelector('[data-directory-panel].active');
        if (activePanel) {
            [
                { key: 'course', name: 'course' },
                { key: 'courses', name: 'courses' },
                { key: 'trainer', name: 'trainers' },
                { key: 'trainers', name: 'trainers' },
                { key: 'session', name: 'sessions' },
                { key: 'sessions', name: 'sessions' },
            ].forEach(function (target) {
                var value = urlParams.get(target.key);
                var select = activePanel.querySelector('.dir-filter[data-key="' + target.name + '"]');
                if (value && select && Array.from(select.options).some(function (option) { return option.value === value; })) select.value = value;
            });
            var nameValue = urlParams.get('trainerName') || urlParams.get('name');
            var activeSearch = activePanel.querySelector('.dir-search');
            if (nameValue && activeSearch) activeSearch.value = nameValue;
            if (activePanel._renderDirectory) activePanel._renderDirectory();
        }

        var modalElement = document.getElementById('viewModal');
        var modal = modalElement && window.bootstrap ? new bootstrap.Modal(modalElement) : null;

        function openDetails(button) {
            var kind = button.dataset.dKind;
            var name = button.dataset.dName || '';
            var subtype = button.dataset.dSub || '';
            var courses = button.dataset.dCourses || '0';
            var sessions = button.dataset.dSessions || '0';
            var banner = document.getElementById('vmBanner');
            var tag = document.getElementById('vmTag');

            banner.className = 'vm-banner ' + (kind === 'trainer' ? 'trainer' : subtype === 'Teacher' ? 'teacher' : subtype === 'New Teacher' ? 'guru' : subtype === 'Public' ? 'outsider' : '');
            document.getElementById('vmAvatar').textContent = (name.charAt(0) || '?').toUpperCase();
            document.getElementById('vmName').textContent = name;
            document.getElementById('vmSub').textContent = kind === 'trainer' ? (subtype || 'Trainer') : 'Participant';

            if (kind === 'trainer') {
                tag.hidden = true;
                tag.textContent = '';
            } else {
                tag.hidden = false;
                tag.innerHTML = '<i class="bi bi-person-badge" aria-hidden="true"></i>' + esc(subtype || 'Participant');
            }

            document.getElementById('vmStats').innerHTML = '<div class="vm-stat"><span class="vm-stat-label">Trainings</span><strong class="vm-stat-value">' + esc(courses) + '</strong><small class="vm-stat-sub">' + (kind === 'trainer' ? 'assigned trainings' : 'enrolled trainings') + '</small></div>'
                + '<div class="vm-stat"><span class="vm-stat-label">Sessions</span><strong class="vm-stat-value">' + esc(sessions) + '</strong><small class="vm-stat-sub">' + (kind === 'trainer' ? 'assigned sessions' : 'related sessions') + '</small></div>';

            var details = {};
            try { details = JSON.parse(button.dataset.dDetails || '{}'); } catch (error) { details = {}; }
            var detailMarkup = Object.keys(details).map(function (key) {
                var raw = String(details[key] == null ? 'Not recorded' : details[key]);
                var value = esc(raw);
                if (/^Email$/i.test(key) && raw.includes('@')) value = '<a href="mailto:' + encodeURIComponent(raw) + '">' + esc(raw) + '</a>';
                if (/^Phone$/i.test(key)) value = '<a href="tel:' + esc(raw.replace(/[^0-9+]/g, '')) + '">' + esc(raw) + '</a>';
                return '<div class="vm-row"><dt>' + esc(key) + '</dt><dd>' + value + '</dd></div>';
            }).join('');
            document.getElementById('vmGrid').innerHTML = detailMarkup || '<div class="vm-empty">No additional profile details are recorded.</div>';

            var certificateSection = document.getElementById('vmCertificatesSection');
            var certificateList = document.getElementById('vmCertificates');
            certificateSection.hidden = kind !== 'trainer';
            certificateList.innerHTML = '';

            if (kind === 'trainer') {
                var certificates = [];
                try { certificates = JSON.parse(button.dataset.dCertificates || '[]'); } catch (error) { certificates = []; }
                if (!Array.isArray(certificates)) certificates = [];

                certificateList.innerHTML = certificates.length
                    ? certificates.map(function (certificate) {
                        var title = esc(certificate.title || 'Certificate');
                        var metadata = esc(certificate.file_type || 'File') + ' · Uploaded ' + esc(certificate.uploaded_at || 'date not available');
                        var action = certificate.available && certificate.view_url
                            ? '<a class="vm-certificate-view" href="' + esc(certificate.view_url) + '" target="_blank" rel="noopener"><i class="bi bi-eye" aria-hidden="true"></i>View</a>'
                            : '<span class="vm-certificate-unavailable"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>File unavailable</span>';

                        return '<article class="vm-certificate-row"><span class="vm-certificate-icon" aria-hidden="true"><i class="bi bi-patch-check"></i></span><div class="vm-certificate-main"><h4>' + title + '</h4><p>' + metadata + '</p></div>' + action + '</article>';
                    }).join('')
                    : '<div class="vm-certificate-empty" role="status"><i class="bi bi-patch-check" aria-hidden="true"></i><span>No certificates uploaded.</span></div>';
            }

            if (modal) modal.show();
        }

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.dir-view');
            if (button) {
                event.preventDefault();
                openDetails(button);
                return;
            }
            var row = event.target.closest('.dir-row[data-inspectable]');
            if (row && row.querySelector('.dir-view')) row.querySelector('.dir-view').click();
        });

        document.addEventListener('keydown', function (event) {
            var row = event.target.closest('.dir-row[data-inspectable]');
            if (row && (event.key === 'Enter' || event.key === ' ')) {
                event.preventDefault();
                row.querySelector('.dir-view').click();
            }
        });
    });
</script>
