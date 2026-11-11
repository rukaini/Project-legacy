<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.__trainerProposalPageInitialized) return;
    window.__trainerProposalPageInitialized = true;

    const submissionStart = document.getElementById('proposed_start_date');
    const submissionEnd = document.getElementById('proposed_end_date');
    if (submissionStart && submissionEnd) {
        const syncSubmissionDates = function () {
            submissionEnd.min = submissionStart.value || submissionStart.min;
            if (submissionStart.value && submissionEnd.value && submissionEnd.value < submissionStart.value) {
                submissionEnd.value = '';
            }
        };
        submissionStart.addEventListener('change', syncSubmissionDates);
        syncSubmissionDates();
    }

    const reportForm = document.getElementById('proposalReportFilters');
    if (!reportForm) return;

    let activeRequest = null;
    let requestSequence = 0;
    let searchTimer = null;
    const searchDebounceMs = 300;
    const resultsRegion = function () { return document.getElementById('proposalResultsRegion'); };
    const searchInput = function () { return document.getElementById('proposalSearch'); };
    const statusInput = function () { return reportForm.querySelector('[name="status"]'); };
    const periodInput = function () { return document.getElementById('proposalSubmittedPeriod'); };
    const fromInput = function () { return document.getElementById('proposalSubmittedFrom'); };
    const toInput = function () { return document.getElementById('proposalSubmittedTo'); };

    const selectHasValue = function (select, value) {
        return Array.from(select?.options || []).some(function (option) { return option.value === value; });
    };

    const syncStatusTabs = function () {
        const selected = statusInput()?.value || 'all';
        document.querySelectorAll('[data-proposal-status]').forEach(function (tab) {
            const active = tab.dataset.proposalStatus === selected;
            tab.classList.toggle('active', active);
            if (active) tab.setAttribute('aria-current', 'page');
            else tab.removeAttribute('aria-current');
        });
    };

    const syncCustomDates = function () {
        const custom = periodInput()?.value === 'custom';
        const range = document.querySelector('[data-proposal-custom-dates]');
        if (range) range.hidden = !custom;
        const from = fromInput();
        const to = toInput();
        if (to) {
            to.setCustomValidity(custom && from?.value && to.value && from.value > to.value
                ? 'The To date must be on or after the From date.'
                : '');
        }
    };

    const customDatesReady = function () {
        if (periodInput()?.value !== 'custom') return true;
        const from = fromInput()?.value || '';
        const to = toInput()?.value || '';
        return from !== '' && to !== '' && from <= to;
    };

    const syncControlActions = function () {
        const clear = document.querySelector('[data-proposal-search-clear]');
        if (clear) clear.hidden = (searchInput()?.value || '') === '';
        const reset = document.querySelector('[data-proposal-reset]');
        const nonSearchActive = (statusInput()?.value || 'all') !== 'all'
            || (periodInput()?.value || 'all') !== 'all'
            || (fromInput()?.value || '') !== ''
            || (toInput()?.value || '') !== '';
        if (reset) reset.hidden = !nonSearchActive;
        syncStatusTabs();
        syncCustomDates();
    };

    const rowsValue = function () {
        const selectedRows = document.querySelector('[data-proposal-rows]')?.value;
        const urlRows = new URL(window.location.href).searchParams.get('rows');
        return ['10', '25', '50'].includes(selectedRows) ? selectedRows : (['10', '25', '50'].includes(urlRows) ? urlRows : '10');
    };

    const filterUrl = function () {
        const url = new URL(reportForm.action, window.location.origin);
        const status = statusInput()?.value || 'all';
        const search = (searchInput()?.value || '').trim();
        const period = periodInput()?.value || 'all';
        const rows = rowsValue();
        if (status !== 'all') url.searchParams.set('status', status);
        if (search !== '') url.searchParams.set('search', search);
        if (period !== 'all') url.searchParams.set('submitted_period', period);
        if (period === 'custom' && customDatesReady()) {
            url.searchParams.set('submitted_from', fromInput().value);
            url.searchParams.set('submitted_to', toInput().value);
        }
        if (rows !== '10') url.searchParams.set('rows', rows);
        return url;
    };

    const updateResults = async function (url, historyMode) {
        const current = resultsRegion();
        if (!current) return;
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        const sequence = ++requestSequence;
        const scrollY = window.scrollY;
        current.classList.remove('has-error');
        const loadingLabel = current.querySelector('.pp-loading-label');
        if (loadingLabel) loadingLabel.textContent = 'Updating proposals…';
        current.classList.add('is-loading');
        current.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Proposal report request failed');
            const documentFragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = documentFragment.querySelector('#proposalResultsRegion');
            if (!next || sequence !== requestSequence) throw new Error('Stale proposal report response');
            current.replaceWith(next);
            if (historyMode === 'push') history.pushState({}, '', url);
            if (historyMode === 'replace') history.replaceState({}, '', url);
            window.scrollTo({ top: scrollY, behavior: 'instant' });
        } catch (error) {
            if (error.name !== 'AbortError' && sequence === requestSequence) {
                current.classList.add('has-error');
                if (loadingLabel) loadingLabel.textContent = 'Unable to update proposals. Try again.';
            }
        } finally {
            if (sequence === requestSequence) {
                activeRequest = null;
                resultsRegion()?.classList.remove('is-loading');
                resultsRegion()?.removeAttribute('aria-busy');
            }
        }
    };

    const requestFromControls = function (historyMode) {
        syncControlActions();
        if (!customDatesReady()) return;
        updateResults(filterUrl().toString(), historyMode);
    };

    const syncControlsFromUrl = function (urlValue) {
        const parameters = new URL(urlValue, window.location.origin).searchParams;
        const status = parameters.get('status') || 'all';
        statusInput().value = ['all', 'pending', 'approved', 'rejected'].includes(status) ? status : 'all';
        searchInput().value = parameters.get('search') || '';
        const period = parameters.get('submitted_period') || 'all';
        periodInput().value = selectHasValue(periodInput(), period) ? period : 'all';
        fromInput().value = parameters.get('submitted_from') || '';
        toInput().value = parameters.get('submitted_to') || '';
        syncControlActions();
    };

    reportForm.addEventListener('submit', function (event) {
        event.preventDefault();
        requestFromControls('push');
    });

    document.addEventListener('input', function (event) {
        if (!event.target.matches('#proposalSearch')) return;
        syncControlActions();
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(function () {
            requestFromControls('replace');
        }, searchDebounceMs);
    });

    document.addEventListener('change', function (event) {
        if (event.target.matches('#proposalSubmittedPeriod')) {
            if (event.target.value !== 'custom') {
                fromInput().value = '';
                toInput().value = '';
            }
            syncControlActions();
            if (event.target.value !== 'custom') requestFromControls('push');
            return;
        }
        if (event.target.matches('#proposalSubmittedFrom, #proposalSubmittedTo')) {
            syncControlActions();
            if (customDatesReady()) requestFromControls('push');
            else toInput()?.reportValidity();
            return;
        }
        if (event.target.matches('[data-proposal-rows]')) {
            requestFromControls('push');
        }
    });

    document.addEventListener('click', function (event) {
        const clear = event.target.closest('[data-proposal-search-clear]');
        if (clear) {
            window.clearTimeout(searchTimer);
            searchInput().value = '';
            searchInput().focus();
            requestFromControls('replace');
            return;
        }

        const reset = event.target.closest('[data-proposal-reset]');
        if (reset) {
            window.clearTimeout(searchTimer);
            statusInput().value = 'all';
            searchInput().value = '';
            periodInput().value = 'all';
            fromInput().value = '';
            toInput().value = '';
            requestFromControls('push');
            return;
        }

        const statusTab = event.target.closest('[data-proposal-status]');
        if (statusTab) {
            event.preventDefault();
            statusInput().value = statusTab.dataset.proposalStatus || 'all';
            requestFromControls('push');
            return;
        }

        const reportLink = event.target.closest('[data-proposal-report-link]');
        if (reportLink) {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            if (!customDatesReady()) return;
            const targetUrl = new URL(reportLink.href, window.location.origin);
            const currentFilters = filterUrl();
            ['status', 'search', 'submitted_period', 'submitted_from', 'submitted_to', 'rows'].forEach(function (key) {
                targetUrl.searchParams.delete(key);
                if (currentFilters.searchParams.has(key)) targetUrl.searchParams.set(key, currentFilters.searchParams.get(key));
            });
            updateResults(targetUrl.toString(), 'push');
        }
    });

    window.addEventListener('popstate', function () {
        window.clearTimeout(searchTimer);
        syncControlsFromUrl(window.location.href);
        updateResults(window.location.href, 'none');
    });

    syncControlActions();
});
</script>
