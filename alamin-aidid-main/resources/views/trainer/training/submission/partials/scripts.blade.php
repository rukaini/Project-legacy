@push('scripts')
<script>
(() => {
    'use strict';
    if (window.__lmsSubmissionsInitialized) return;
    window.__lmsSubmissionsInitialized = true;

    let reportController = null;
    let requestNumber = 0;
    let searchTimer = null;
    let previewOpener = null;
    let previousBodyOverflow = '';

    const reportForm = () => document.querySelector('[data-submission-report-form]');
    const rosterSearch = () => document.querySelector('[data-submission-search]');
    const setClearVisibility = (input, button) => { if (input && button) button.hidden = input.value.length === 0; };
    const cleanUrl = (url) => { const value = new URL(url, window.location.href); value.searchParams.delete('fragment'); return value; };
    const stateUrl = (extra = {}, resetPage = true) => {
        const form = reportForm();
        const url = cleanUrl(form?.action || window.location.href);
        if (form) {
            new FormData(form).forEach((value, key) => {
                if (value && value !== 'all' && !(key === 'rows' && value === '10')) url.searchParams.set(key, value);
                else url.searchParams.delete(key);
            });
        }
        const existingRows = new URL(window.location.href).searchParams.get('rows');
        if (existingRows && existingRows !== '10' && !Object.hasOwn(extra, 'rows')) url.searchParams.set('rows', existingRows);
        Object.entries(extra).forEach(([key, value]) => {
            if (value && value !== 'all' && !(key === 'rows' && String(value) === '10')) url.searchParams.set(key, value);
            else url.searchParams.delete(key);
        });
        if (resetPage) url.searchParams.delete('page');
        return url;
    };
    const syncTaskSelection = () => {
        const selectedId = document.getElementById('lmsSubmissionWorkspace')?.dataset.selectedAssessmentId || '';
        document.querySelectorAll('[data-task-link]').forEach((link) => {
            const active = link.dataset.assessmentId === selectedId;
            link.classList.toggle('is-active', active);
            if (active) link.setAttribute('aria-current', 'true'); else link.removeAttribute('aria-current');
        });
    };
    const syncFormFromUrl = (url) => {
        const form = reportForm();
        if (!form) return;
        const status = url.searchParams.get('status') || 'all';
        const search = url.searchParams.get('search') || '';
        const assessment = url.searchParams.get('assessment_id') || document.getElementById('lmsSubmissionWorkspace')?.dataset.selectedAssessmentId || '';
        const statusInput = form.querySelector('[data-submission-status-input]');
        const searchInput = form.querySelector('[data-submission-search]');
        if (statusInput) statusInput.value = status;
        if (searchInput) searchInput.value = search;
        const assessmentInput = form.querySelector('[name="assessment_id"]');
        if (assessmentInput) assessmentInput.value = assessment;
        form.querySelectorAll('[data-submission-status]').forEach((chip) => chip.classList.toggle('is-active', chip.dataset.submissionStatus === status));
        setClearVisibility(searchInput, form.querySelector('[data-submission-search-clear]'));
    };
    const showReportError = (region) => {
        const selector = region === 'results' ? '[data-submission-results-error]' : '[data-submission-page-error]';
        document.querySelector(selector)?.removeAttribute('hidden');
    };
    const loadReport = async (url, region = 'results', historyMode = 'push', showLoading = true) => {
        reportController?.abort();
        reportController = new AbortController();
        const currentRequest = ++requestNumber;
        const targetId = region === 'results' ? 'lmsSubmissionRosterResults' : 'lmsSubmissionWorkspace';
        const target = document.getElementById(targetId);
        if (showLoading) target?.classList.add('is-loading');
        const requestUrl = cleanUrl(url);
        requestUrl.searchParams.set('fragment', region);
        try {
            const response = await fetch(requestUrl, {
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'},
                signal: reportController.signal
            });
            if (!response.ok) throw new Error('Unable to load submissions.');
            const fragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (currentRequest !== requestNumber) return;
            const replacement = fragment.getElementById(targetId);
            if (!target || !replacement) throw new Error('Submission response was incomplete.');
            target.replaceWith(replacement);
            const browserUrl = cleanUrl(url);
            if (historyMode === 'push') history.pushState({}, '', browserUrl);
            if (historyMode === 'replace') history.replaceState({}, '', browserUrl);
            if (region === 'workspace') syncTaskSelection();
            syncFormFromUrl(browserUrl);
            return true;
        } catch (error) {
            if (error.name !== 'AbortError') {
                target?.classList.remove('is-loading');
                showReportError(region);
            }
            return false;
        }
    };

    const filterTasks = () => {
        const input = document.querySelector('[data-task-search]');
        const clear = document.querySelector('[data-task-search-clear]');
        const needle = (input?.value || '').toLowerCase().trim();
        let visible = 0;
        document.querySelectorAll('[data-task-link]').forEach((link) => {
            const show = (link.dataset.taskSearchText || '').includes(needle);
            link.classList.toggle('lw-hidden', !show);
            if (show) visible++;
        });
        document.querySelector('[data-no-task-matches]')?.classList.toggle('lw-hidden', visible > 0);
        setClearVisibility(input, clear);
    };

    const updatePendingBadge = (count) => {
        const badge = document.querySelector('[data-lms-submissions-pending]');
        const value = badge?.querySelector('[data-lms-submissions-pending-count]');
        if (value) value.textContent = String(count);
        badge?.classList.toggle('lw-hidden', Number(count) < 1);
        if (badge) badge.hidden = Number(count) < 1;
    };
    const updateCounts = (counts) => {
        if (!counts) return;
        Object.entries(counts).forEach(([key, value]) => {
            const element = document.querySelector(`[data-submission-count="${key}"]`);
            if (element) element.textContent = String(value);
        });
        const activeTask = document.querySelector('[data-task-link].is-active');
        if (!activeTask) return;
        const needReview = Number(counts.need_review || 0);
        const submitted = Number(counts.all || 0) - Number(counts.awaiting || 0);
        const submittedElement = activeTask.querySelector('.sidebar-submitted-count');
        const badge = activeTask.querySelector('.sidebar-review-badge');
        if (submittedElement) submittedElement.textContent = String(Math.max(0, submitted));
        if (badge) {
            if (needReview > 0) badge.innerHTML = `<span class="lw-mini-pill rose"><span class="sidebar-need-review-count">${needReview}</span> Need Review</span>`;
            else if (submitted > 0) badge.innerHTML = '<span class="lw-mini-pill success">All Reviewed</span>';
            else badge.innerHTML = '<span class="lw-mini-pill muted">No file yet</span>';
        }
    };
    const updateReviewRow = (row, status) => {
        if (!row) return;
        const isReviewed = status === 'Reviewed';
        const badge = row.querySelector('.submission-status-badge');
        const form = row.querySelector('.review-form');
        const input = form?.querySelector('[name="review_status"]');
        const button = form?.querySelector('button[type="submit"]');

        row.dataset.status = isReviewed ? 'Reviewed' : 'Need Review';
        row.classList.toggle('is-need-review', !isReviewed);
        form?.classList.remove('is-pending');
        form?.removeAttribute('aria-busy');
        if (badge) {
            badge.textContent = isReviewed ? 'Reviewed' : 'Need Review';
            badge.classList.toggle('s-reviewed', isReviewed);
            badge.classList.toggle('s-need-review', !isReviewed);
        }
        if (input) input.value = isReviewed ? 'Submitted' : 'Reviewed';
        if (button) {
            button.disabled = false;
            button.className = `lw-btn ${isReviewed ? 'lw-btn-ghost' : 'lw-btn-primary'} lw-btn-sm lw-action`;
            button.innerHTML = isReviewed
                ? '<i class="bi bi-arrow-counterclockwise"></i> Undo Review'
                : '<i class="bi bi-check2-circle"></i> Mark Reviewed';
        }
    };
    const submitReview = async (form) => {
        const row = form.closest('[data-submission-row]');
        const button = form.querySelector('button[type="submit"]');
        const original = button?.innerHTML || '';
        form.classList.add('is-pending');
        form.setAttribute('aria-busy', 'true');
        if (button) { button.disabled = true; button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Saving...'; }
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                body: new FormData(form)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) throw new Error(data.message || 'Could not update review status. Please try again.');
            updateCounts(data.counts);
            updatePendingBadge(data.pending_count);
            updateReviewRow(row, data.status);

            const activeFilter = reportForm()?.querySelector('[data-submission-status-input]')?.value || 'all';
            const rowStillMatches = activeFilter === 'all'
                || activeFilter === 'late'
                || (activeFilter === 'need_review' && data.status !== 'Reviewed')
                || (activeFilter === 'reviewed' && data.status === 'Reviewed');
            if (!rowStillMatches) {
                row?.classList.add('is-review-removing');
                const refreshed = await loadReport(cleanUrl(window.location.href), 'results', 'none', false);
                if (!refreshed) row?.classList.remove('is-review-removing');
            }
        } catch (error) {
            form.classList.remove('is-pending');
            form.removeAttribute('aria-busy');
            if (button) { button.disabled = false; button.innerHTML = original; }
            const region = document.getElementById('lmsSubmissionRosterResults');
            let message = region?.querySelector('[data-review-error]');
            if (!message && region) {
                message = document.createElement('div');
                message.className = 'lw-report-error';
                message.dataset.reviewError = '';
                region.prepend(message);
            }
            if (message) message.textContent = error.message || 'Could not update review status. Please try again.';
        }
    };

    const openPreview = (button) => {
        const modal = document.querySelector('[data-file-preview-modal]');
        const frame = document.getElementById('filePreviewFrame');
        const previewUrl = button.dataset.previewUrl || '';
        if (!modal || !frame || !previewUrl) return;
        previewOpener = button;
        previousBodyOverflow = document.body.style.overflow;
        document.getElementById('filePreviewTitle').textContent = button.dataset.fileName || 'Submitted File Preview';
        document.getElementById('filePreviewOpenLink').href = previewUrl;
        document.getElementById('filePreviewDownloadLink').href = button.dataset.downloadUrl || '#';
        const unavailable = modal.querySelector('[data-file-preview-error]');
        if (unavailable) unavailable.hidden = true;
        frame.hidden = false;
        frame.src = previewUrl;
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        modal.querySelector('[data-file-preview-close]')?.focus();
    };
    const closePreview = () => {
        const modal = document.querySelector('[data-file-preview-modal]');
        const frame = document.getElementById('filePreviewFrame');
        if (!modal || !frame || modal.classList.contains('hidden')) return;
        frame.src = '';
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = previousBodyOverflow;
        previewOpener?.focus();
    };

    document.addEventListener('input', (event) => {
        if (event.target.matches('[data-task-search]')) { filterTasks(); return; }
        if (!event.target.matches('[data-submission-search]')) return;
        setClearVisibility(event.target, reportForm()?.querySelector('[data-submission-search-clear]'));
        window.clearTimeout(searchTimer);
        searchTimer = window.setTimeout(() => loadReport(stateUrl(), 'results', 'replace'), 300);
    });
    document.addEventListener('change', (event) => {
        if (!event.target.matches('#lmsSubmissionRosterResults [data-lms-rows]')) return;
        loadReport(stateUrl({rows: event.target.value}), 'results', 'push');
    });
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.review-form');
        if (form) { event.preventDefault(); submitReview(form); return; }
        if (event.target.matches('[data-submission-report-form]')) event.preventDefault();
    });
    document.addEventListener('click', (event) => {
        const sidebarToggle = event.target.closest('[data-task-sidebar-toggle]');
        if (sidebarToggle) {
            const sidebar = document.querySelector('[data-task-sidebar]');
            if (!sidebar || window.matchMedia('(max-width: 1024px)').matches) return;
            const collapsed = !sidebar.classList.contains('is-collapsed');
            const expanded = sidebar.querySelector('.lw-inbox-expanded');
            sidebar.classList.toggle('is-collapsed', collapsed);
            expanded?.toggleAttribute('inert', collapsed);
            expanded?.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
            sidebar.querySelectorAll('[data-task-sidebar-toggle]').forEach((button) => button.setAttribute('aria-expanded', collapsed ? 'false' : 'true'));
            sidebar.querySelector(collapsed ? '.lw-show-tasks' : '.lw-collapse-btn')?.focus();
            return;
        }
        const taskClear = event.target.closest('[data-task-search-clear]');
        if (taskClear) {
            const input = document.querySelector('[data-task-search]');
            if (input) { input.value = ''; input.focus(); filterTasks(); }
            return;
        }
        const rosterClear = event.target.closest('[data-submission-search-clear]');
        if (rosterClear) {
            const input = rosterSearch();
            if (input) { input.value = ''; input.focus(); setClearVisibility(input, rosterClear); }
            window.clearTimeout(searchTimer);
            loadReport(stateUrl(), 'results', 'replace');
            return;
        }
        const taskLink = event.target.closest('[data-task-link]');
        if (taskLink) { event.preventDefault(); loadReport(stateUrl({assessment_id: taskLink.dataset.assessmentId}), 'workspace', 'push'); return; }
        const statusLink = event.target.closest('[data-submission-status]');
        if (statusLink) {
            event.preventDefault();
            const input = reportForm()?.querySelector('[data-submission-status-input]');
            if (input) input.value = statusLink.dataset.submissionStatus;
            reportForm()?.querySelectorAll('[data-submission-status]').forEach((chip) => chip.classList.toggle('is-active', chip === statusLink));
            loadReport(stateUrl(), 'results', 'push');
            return;
        }
        const pageLink = event.target.closest('#lmsSubmissionRosterResults [data-lms-report-link]');
        if (pageLink) { event.preventDefault(); loadReport(new URL(pageLink.href), 'results', 'push'); return; }
        const preview = event.target.closest('[data-file-preview]');
        if (preview) { openPreview(preview); return; }
        if (event.target.closest('[data-file-preview-close]') || event.target === document.querySelector('[data-file-preview-modal]')) closePreview();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !document.querySelector('[data-file-preview-modal]')?.classList.contains('hidden')) closePreview();
    });
    window.addEventListener('popstate', () => {
        const url = cleanUrl(window.location.href);
        const selected = document.getElementById('lmsSubmissionWorkspace')?.dataset.selectedAssessmentId || '';
        const requested = url.searchParams.get('assessment_id') || selected;
        syncFormFromUrl(url);
        loadReport(url, requested !== selected ? 'workspace' : 'results', 'none');
    });

    filterTasks();
    syncTaskSelection();
    const initialUrl = cleanUrl(window.location.href);
    const initialAssessment = document.getElementById('lmsSubmissionWorkspace')?.dataset.selectedAssessmentId || '';
    if (initialAssessment && !initialUrl.searchParams.has('assessment_id')) {
        initialUrl.searchParams.set('assessment_id', initialAssessment);
        history.replaceState({}, '', initialUrl);
    }
    syncFormFromUrl(initialUrl);
})();
</script>
@endpush
