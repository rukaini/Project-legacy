<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeRequest = null;
    let requestSequence = 0;
    let searchTimer = null;
    let trainingNavigatorCollapsed = false;
    let lastRenderedSearchValue = document.getElementById('fbSearch')?.value || '';
    const commentRequests = new WeakMap();
    const trainingNavigatorMedia = window.matchMedia('(max-width: 900px)');

    const setRatingWidths = (root = document) => {
        root.querySelectorAll('.fb-rating-fill').forEach((bar) => {
            const width = Number.parseFloat(bar.dataset.width || '0');
            bar.style.width = `${Number.isFinite(width) ? Math.min(100, Math.max(0, width)) : 0}%`;
        });
    };

    const stateUrl = (changes = {}, removals = []) => {
        const url = new URL(window.location.href);
        ['feedback_fragment', 'comment_offset', 'rows', 'page'].forEach((key) => url.searchParams.delete(key));
        removals.forEach((key) => url.searchParams.delete(key));
        Object.entries(changes).forEach(([key, value]) => {
            if (value === '' || value === null || value === undefined || value === 0 || value === '0' || value === 'all') {
                url.searchParams.delete(key);
            } else {
                url.searchParams.set(key, String(value));
            }
        });
        return url;
    };

    const withCanonicalSelection = (url, source) => {
        const workspace = source?.querySelector?.('#feedbackWorkspace') || document.querySelector('#feedbackWorkspace');
        const courseId = workspace?.dataset.selectedCourse || '';
        const questionId = workspace?.dataset.selectedQuestion || '';
        if (courseId) url.searchParams.set('course_id', courseId); else url.searchParams.delete('course_id');
        if (questionId) url.searchParams.set('question_id', questionId); else url.searchParams.delete('question_id');
        return url;
    };

    const withCanonicalFilters = (url, source) => {
        const form = source?.querySelector?.('#feedbackFilterForm');
        const names = ['filter_course_id', 'period', 'rating', 'search', 'date_from', 'date_to'];
        url.searchParams.delete('rows');
        url.searchParams.delete('page');
        names.forEach((name) => url.searchParams.delete(name));
        if (!form) return url;
        for (const name of names) {
            const value = form.elements.namedItem(name)?.value || '';
            if (value !== '' && value !== '0' && value !== 'all') url.searchParams.set(name, value);
        }
        return url;
    };

    const applyHistory = (mode, url) => {
        if (mode === 'push') history.pushState({}, '', url);
        if (mode === 'replace') history.replaceState({}, '', url);
    };

    const setTrainingNavigatorCollapsed = (collapsed, moveFocus = true) => {
        trainingNavigatorCollapsed = Boolean(collapsed && !trainingNavigatorMedia.matches);
        const sidebar = document.getElementById('feedbackTrainingNavigator');
        if (!sidebar) return;
        const expanded = sidebar.querySelector('.fb-navigator-expanded');
        sidebar.classList.toggle('is-collapsed', trainingNavigatorCollapsed);
        sidebar.closest('.fb-workspace')?.classList.toggle('is-training-list-collapsed', trainingNavigatorCollapsed);
        expanded?.toggleAttribute('inert', trainingNavigatorCollapsed);
        expanded?.setAttribute('aria-hidden', trainingNavigatorCollapsed ? 'true' : 'false');
        sidebar.querySelectorAll('[data-feedback-training-list-toggle]').forEach((button) => {
            button.setAttribute('aria-expanded', trainingNavigatorCollapsed ? 'false' : 'true');
        });
        if (moveFocus) {
            sidebar.querySelector(trainingNavigatorCollapsed ? '.fb-navigator-expand' : '.fb-navigator-collapse')?.focus();
        }
    };

    const updateFilterControls = () => {
        const form = document.getElementById('feedbackFilterForm');
        if (!form) return;
        const period = form.querySelector('[name="period"]');
        const from = form.querySelector('[name="date_from"]');
        const to = form.querySelector('[name="date_to"]');
        const customDates = form.querySelector('[data-feedback-custom-dates]');
        const search = form.querySelector('[name="search"]');
        const clearSearch = form.querySelector('[data-feedback-clear-search]');
        const reset = form.querySelector('[data-feedback-reset]');
        const isCustom = period?.value === 'custom';

        if (customDates) customDates.hidden = !isCustom;
        if (from && to) {
            from.max = to.value || '';
            to.min = from.value || '';
            to.setCustomValidity(isCustom && from.value && to.value && from.value > to.value ? 'The To date must be on or after the From date.' : '');
        }
        if (clearSearch) clearSearch.hidden = !search?.value;
        if (reset) {
            const nonSearchActive = form.querySelector('[name="filter_course_id"]')?.value !== '0'
                || period?.value !== 'all'
                || form.querySelector('[name="rating"]')?.value !== 'all'
                || Boolean(from?.value)
                || Boolean(to?.value);
            reset.hidden = !nonSearchActive;
        }
    };

    const customDatesReady = () => {
        const form = document.getElementById('feedbackFilterForm');
        const from = form?.querySelector('[name="date_from"]')?.value || '';
        const to = form?.querySelector('[name="date_to"]')?.value || '';
        return from !== '' && to !== '' && from <= to;
    };

    const filterRequestReady = () => document.querySelector('#feedbackFilterForm [name="period"]')?.value !== 'custom'
        || customDatesReady();

    const syncFilterForm = (source) => {
        const current = document.getElementById('feedbackFilterForm');
        const next = source?.querySelector?.('#feedbackFilterForm');
        if (!current || !next) return;
        ['filter_course_id', 'period', 'rating', 'search', 'date_from', 'date_to'].forEach((name) => {
            const currentControl = current.elements.namedItem(name);
            const nextControl = next.elements.namedItem(name);
            if (currentControl && nextControl) currentControl.value = nextControl.value;
        });
        updateFilterControls();
    };

    const requestDocument = async (url, loadingSelector) => {
        activeRequest?.abort();
        const controller = new AbortController();
        activeRequest = controller;
        const sequence = ++requestSequence;
        const loadingTarget = document.querySelector(loadingSelector);
        loadingTarget?.classList.add('is-loading');
        loadingTarget?.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Feedback request failed');
            const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (sequence !== requestSequence) throw new Error('Stale feedback response');
            return { parsed, sequence };
        } finally {
            if (sequence === requestSequence) {
                activeRequest = null;
                document.querySelector(loadingSelector)?.classList.remove('is-loading');
                document.querySelector(loadingSelector)?.removeAttribute('aria-busy');
            }
        }
    };

    const finishRefresh = (url, parsed, historyMode, scrollY) => {
        setRatingWidths(document);
        const parsedWorkspace = parsed.querySelector('#feedbackWorkspace');
        const currentWorkspace = document.querySelector('#feedbackWorkspace');
        if (parsedWorkspace && currentWorkspace) {
            currentWorkspace.dataset.selectedCourse = parsedWorkspace.dataset.selectedCourse || '';
            currentWorkspace.dataset.selectedQuestion = parsedWorkspace.dataset.selectedQuestion || '';
        }
        const canonicalUrl = withCanonicalSelection(withCanonicalFilters(new URL(url), parsed), parsed);
        lastRenderedSearchValue = parsed.querySelector('#fbSearch')?.value || '';
        setTrainingNavigatorCollapsed(trainingNavigatorCollapsed, false);
        applyHistory(historyMode, canonicalUrl);
        window.scrollTo({ top: scrollY, behavior: 'instant' });
    };

    const refreshReport = async (url, historyMode = 'push', syncFilters = false) => {
        const scrollY = window.scrollY;
        try {
            const { parsed } = await requestDocument(url, '#feedbackResultsRegion');
            const nextSummary = parsed.querySelector('#feedbackSummaryRegion');
            const nextResults = parsed.querySelector('#feedbackResultsRegion');
            const summary = document.querySelector('#feedbackSummaryRegion');
            const results = document.querySelector('#feedbackResultsRegion');
            if (!nextSummary || !nextResults || !summary || !results) throw new Error('Feedback response missing');
            summary.replaceWith(nextSummary);
            results.replaceWith(nextResults);
            if (syncFilters) syncFilterForm(parsed);
            finishRefresh(url, parsed, historyMode, scrollY);
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        }
    };

    const refreshTarget = async (url, selector, historyMode = 'push') => {
        const target = document.querySelector(selector);
        if (!target) return;
        const scrollY = window.scrollY;
        const focusKey = document.activeElement?.id || '';
        try {
            const { parsed } = await requestDocument(url, selector);
            const next = parsed.querySelector(selector);
            if (!next) throw new Error('Feedback response missing');
            target.replaceWith(next);
            finishRefresh(url, parsed, historyMode, scrollY);
            if (focusKey && focusKey !== 'fbSearch') document.getElementById(focusKey)?.focus({ preventScroll: true });
        } catch (error) {
            if (error.name !== 'AbortError') window.location.assign(url);
        }
    };

    const filterUrl = (changes = {}, removals = []) => {
        const form = document.getElementById('feedbackFilterForm');
        const url = stateUrl(changes, ['filter_course_id', 'period', 'rating', 'search', 'date_from', 'date_to', ...removals]);
        if (!form) return url;
        for (const [key, value] of new FormData(form)) {
            if (value !== '' && value !== '0' && value !== 'all') url.searchParams.set(key, value);
        }
        return url;
    };

    const loadMoreComments = async (button) => {
        const stream = button.closest('[data-feedback-comment-stream]');
        const existingBatch = stream?.querySelector('[data-feedback-comment-batch]');
        if (!stream || !existingBatch) return;
        commentRequests.get(stream)?.abort();
        const controller = new AbortController();
        commentRequests.set(stream, controller);
        const url = stateUrl({
            course_id: stream.dataset.courseId,
            question_id: stream.dataset.questionId,
            comment_offset: button.dataset.nextOffset,
        });
        url.searchParams.set('feedback_fragment', 'comments');
        button.disabled = true;
        button.textContent = 'Loading comments…';
        stream.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Comment request failed');
            const holder = document.createElement('div');
            holder.innerHTML = await response.text();
            const nextBatch = holder.querySelector('[data-feedback-comment-batch]');
            if (!nextBatch) throw new Error('Comment response missing');
            button.remove();
            nextBatch.querySelectorAll('.fb-comment-row').forEach((row) => existingBatch.append(row));
            const nextButton = nextBatch.querySelector('[data-feedback-show-more]');
            if (nextButton) existingBatch.append(nextButton);
        } catch (error) {
            if (error.name !== 'AbortError') {
                button.disabled = false;
                button.textContent = 'Show more comments';
            }
        } finally {
            if (commentRequests.get(stream) === controller) {
                commentRequests.delete(stream);
                stream.classList.remove('is-loading');
            }
        }
    };

    setRatingWidths();
    updateFilterControls();
    const initialUrl = withCanonicalSelection(withCanonicalFilters(new URL(window.location.href), document), document);
    history.replaceState({}, '', initialUrl);

    document.addEventListener('submit', (event) => {
        if (!event.target.matches('#feedbackFilterForm')) return;
        event.preventDefault();
        if (event.target.elements.period.value === 'custom' && !customDatesReady()) return;
        refreshReport(filterUrl(), 'replace');
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('#fbPeriodFilter')) {
            updateFilterControls();
            if (event.target.value === 'custom') return;
            const form = event.target.form;
            form.elements.date_from.value = '';
            form.elements.date_to.value = '';
            updateFilterControls();
            refreshReport(filterUrl());
            return;
        }
        if (event.target.matches('#fbDateFrom, #fbDateTo')) {
            updateFilterControls();
            if (customDatesReady()) refreshReport(filterUrl());
            return;
        }
        if (event.target.matches('#fbCourseFilter, #fbRatingFilter')) {
            updateFilterControls();
            if (filterRequestReady()) refreshReport(filterUrl());
            return;
        }
        if (event.target.matches('[data-feedback-question-selector]')) {
            refreshTarget(filterUrl({ question_id: event.target.value }), '#feedbackTrainingDetail');
        }
    });

    document.addEventListener('input', (event) => {
        if (!event.target.matches('#fbSearch')) return;
        updateFilterControls();
        window.clearTimeout(searchTimer);
        activeRequest?.abort();
        if (event.target.value === lastRenderedSearchValue) return;
        searchTimer = window.setTimeout(() => {
            if (filterRequestReady()) refreshReport(filterUrl(), 'replace');
        }, 180);
    });

    document.addEventListener('click', (event) => {
        const trainingListToggle = event.target.closest('[data-feedback-training-list-toggle]');
        if (trainingListToggle) {
            if (trainingNavigatorMedia.matches) return;
            setTrainingNavigatorCollapsed(!document.getElementById('feedbackTrainingNavigator')?.classList.contains('is-collapsed'));
            return;
        }

        const clearSearch = event.target.closest('[data-feedback-clear-search]');
        if (clearSearch) {
            window.clearTimeout(searchTimer);
            const search = clearSearch.closest('.fb-search-control')?.querySelector('#fbSearch');
            if (!search) return;
            search.value = '';
            updateFilterControls();
            search.focus({ preventScroll: true });
            if (filterRequestReady()) refreshReport(filterUrl(), 'replace');
            return;
        }

        const reset = event.target.closest('[data-feedback-reset]');
        if (reset) {
            window.clearTimeout(searchTimer);
            const form = reset.closest('form');
            form.elements.filter_course_id.value = '0';
            form.elements.period.value = 'all';
            form.elements.rating.value = 'all';
            form.elements.search.value = '';
            form.elements.date_from.value = '';
            form.elements.date_to.value = '';
            updateFilterControls();
            refreshReport(filterUrl());
            return;
        }

        const training = event.target.closest('[data-feedback-select-training]');
        if (training) {
            refreshTarget(filterUrl({ course_id: training.dataset.feedbackSelectTraining }, ['question_id']), '#feedbackResultsRegion');
            return;
        }

        const showMore = event.target.closest('[data-feedback-show-more]');
        if (showMore && !showMore.disabled) loadMoreComments(showMore);
    });

    trainingNavigatorMedia.addEventListener('change', (event) => {
        if (event.matches) setTrainingNavigatorCollapsed(false, false);
    });
    window.addEventListener('popstate', () => refreshReport(window.location.href, 'none', true));
    setTrainingNavigatorCollapsed(false, false);
});
</script>
