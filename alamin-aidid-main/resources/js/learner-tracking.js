export const TRACKING_TABS = new Set(['overview', 'engagement', 'quiz']);
export const TRACKING_ROWS = new Set([10, 25, 50]);

export function normalizeTrackingState(input = {}) {
    const tab = TRACKING_TABS.has(input.tab) ? input.tab : (input.tab === 'assessment' ? 'quiz' : 'overview');
    const pivot = input.pivot === 'module' ? 'module' : 'participant';
    const rows = TRACKING_ROWS.has(Number(input.rows)) ? Number(input.rows) : 10;

    return { tab, pivot, rows };
}

export function trackingUrl(href, changes = {}) {
    const url = new URL(href, 'http://localhost');
    Object.entries(changes).forEach(([key, value]) => {
        if (value === null || value === undefined || value === '') url.searchParams.delete(key);
        else url.searchParams.set(key, String(value));
    });
    url.searchParams.delete('fragment');
    return url;
}

export function trackingRequestUrl(href) {
    const url = trackingUrl(href);
    url.searchParams.set('fragment', '1');

    return url;
}

export function trackingSearchChanges(parameter, value) {
    const normalized = String(value ?? '').trim() || null;
    if (parameter === 'participant_search') {
        return { participant_search: normalized, quiz_page: 1, attempt: null };
    }

    return { search: normalized, activity_page: 1, engagement_page: 1, quiz_page: 1 };
}

export const TRACKING_REGION_NAMES = ['tabs', 'period', 'navigator', 'navigator-count', 'navigator-list', 'main', 'activity-results', 'engagement-results', 'quiz-results', 'attempt-detail'];
export const TRACKING_POPSTATE_REGIONS = ['tabs', 'period', 'navigator', 'main'];
export const TRACKING_ACTION_REGIONS = Object.freeze({
    tab: ['tabs', 'navigator', 'main'],
    pivot: ['navigator', 'main'],
    selection: ['main'],
    period: ['main'],
    'navigator-search': ['navigator-count', 'navigator-list'],
    'quiz-search': ['quiz-results', 'attempt-detail'],
    history: TRACKING_POPSTATE_REGIONS,
});

export function normalizeTrackingRegions(value, fallback = ['main']) {
    const values = Array.isArray(value) ? value : String(value ?? '').split(/\s+/);
    const requested = values.filter((name) => TRACKING_REGION_NAMES.includes(name));
    if (requested.length > 0) return [...new Set(requested)];
    const fallbackValues = Array.isArray(fallback) ? fallback : String(fallback ?? '').split(/\s+/);
    const normalizedFallback = fallbackValues.filter((name) => TRACKING_REGION_NAMES.includes(name));

    return normalizedFallback.length > 0 ? [...new Set(normalizedFallback)] : ['main'];
}

export function trackingRegionsForAction(action, declared = null, fallback = ['main']) {
    if (action && TRACKING_ACTION_REGIONS[action]) return [...TRACKING_ACTION_REGIONS[action]];

    return normalizeTrackingRegions(declared, fallback);
}

export function shouldCloseTrackingAttempt(href, regions, isOpen) {
    if (!isOpen) return false;
    const destination = trackingUrl(href);
    const targets = normalizeTrackingRegions(regions);

    return !destination.searchParams.has('attempt')
        && (targets.includes('attempt-detail') || targets.includes('main'));
}

export function questionScrollBehavior(reducedMotion) {
    return reducedMotion ? 'auto' : 'smooth';
}

export function createQuestionNavigationGate() {
    let targetId = null;

    return {
        begin(questionId) {
            targetId = questionId;
            return targetId;
        },
        active(visibleQuestionId, targetReached = false) {
            if (!targetId) return visibleQuestionId;
            const activeId = targetId;
            if (targetReached) targetId = null;
            return activeId;
        },
        target() { return targetId; },
        release() { targetId = null; },
    };
}

export function shouldHandleTrackingLink(event, anchor) {
    return Boolean(anchor)
        && event.button === 0
        && !event.metaKey
        && !event.ctrlKey
        && !event.shiftKey
        && !event.altKey
        && anchor.target !== '_blank'
        && anchor.origin === window.location.origin;
}

export function quizPercentage(grade, totalMarks) {
    if (grade === null || grade === undefined || grade === '') return null;
    const gradeNumber = Number(grade);
    const total = Number(totalMarks);
    if (!Number.isFinite(gradeNumber) || !Number.isFinite(total) || total <= 0) return null;
    return Math.round((gradeNumber / total) * 1000) / 10;
}

export function createTrackingRequestGate() {
    let sequence = 0;
    let controller = null;

    return {
        begin() {
            controller?.abort();
            controller = new AbortController();
            sequence += 1;
            return { id: sequence, signal: controller.signal };
        },
        isCurrent(id) { return id === sequence; },
        abort() { controller?.abort(); controller = null; sequence += 1; },
    };
}

export function initializeLearnerTracking(doc = document, win = window) {
    if (win.__learnerTrackingInitialized) return win.__learnerTrackingInitialized;

    const gate = createTrackingRequestGate();
    const questionNavigationGate = createQuestionNavigationGate();
    let searchTimer = null;
    let attemptOpener = null;
    let questionNavigationBody = null;
    let questionNavigationTimer = null;

    const syncAttemptScrollLock = () => {
        const locked = Boolean(doc.querySelector('[data-attempt-detail-panel]'))
            || Boolean(doc.querySelector('.lt-attempt-layer.is-opening, .lt-attempt-layer.is-closing'));
        doc.documentElement?.classList.toggle('lt-attempt-scroll-locked', locked);
        doc.body?.classList.toggle('lt-attempt-scroll-locked', locked);
    };

    const waitForAttemptExit = (layer) => {
        if (!layer?.querySelector('[data-attempt-detail-panel]')) return Promise.resolve();
        layer.classList.remove('is-continuing');
        layer.classList.add('is-closing');
        syncAttemptScrollLock();

        return new Promise((resolve) => {
            let settled = false;
            const finish = () => {
                if (settled) return;
                settled = true;
                layer.removeEventListener('animationend', onAnimationEnd);
                resolve();
            };
            const onAnimationEnd = (event) => {
                if (event.target.matches?.('.lt-attempt-drawer')) finish();
            };
            layer.addEventListener('animationend', onAnimationEnd);
            win.setTimeout(finish, 190);
        });
    };

    const invalidateAttemptImmediately = () => {
        const layer = doc.querySelector('[data-tracking-region="attempt-detail"]');
        if (!layer?.querySelector('[data-attempt-detail-panel]')) return;

        const destination = trackingUrl(win.location.href, { attempt: null });
        win.history.replaceState({ learnerTracking: true }, '', destination.href);
        waitForAttemptExit(layer).then(() => {
            if (!layer.isConnected || trackingUrl(win.location.href).searchParams.has('attempt')) return;
            layer.replaceChildren();
            layer.classList.remove('is-open', 'is-closing', 'is-continuing');
            syncAttemptScrollLock();
        });
    };

    const toast = (message) => {
        win.showAppToast?.('error', message);
    };

    const setCurrentQuestion = (drawerBody, questionId) => {
        drawerBody.querySelectorAll('[data-question-jump]').forEach((button) => {
            const isCurrent = button.dataset.questionJump === `#${questionId}`;
            button.classList.toggle('is-current', isCurrent);
            button.toggleAttribute('aria-current', isCurrent);
            if (isCurrent) button.setAttribute('aria-current', 'location');
        });
    };

    const releaseQuestionNavigation = () => {
        questionNavigationGate.release();
        questionNavigationBody = null;
        win.clearTimeout(questionNavigationTimer);
        questionNavigationTimer = null;
    };

    const syncVisibleQuestion = (drawerBody) => {
        const cards = [...drawerBody.querySelectorAll('[data-question-card]')];
        if (cards.length === 0) return;
        const drawerTop = drawerBody.getBoundingClientRect().top;
        const navHeight = drawerBody.querySelector('[data-question-navigator]')?.offsetHeight ?? 0;
        const referenceTop = drawerTop + navHeight + 14;
        const current = cards.find((card) => card.getBoundingClientRect().bottom > referenceTop) ?? cards.at(-1);
        const navigationTargetId = questionNavigationBody === drawerBody
            ? questionNavigationGate.target()
            : null;
        const target = navigationTargetId ? drawerBody.querySelector(`#${navigationTargetId}`) : null;
        const reachedTarget = Boolean(target)
            && (Math.abs(target.getBoundingClientRect().top - referenceTop) <= 14
                || (target === cards.at(-1) && drawerBody.scrollTop + drawerBody.clientHeight >= drawerBody.scrollHeight - 2));
        const activeId = questionNavigationGate.active(current?.id ?? null, reachedTarget);
        if (activeId) setCurrentQuestion(drawerBody, activeId);
        if (reachedTarget) {
            questionNavigationBody = null;
            win.clearTimeout(questionNavigationTimer);
            questionNavigationTimer = null;
        }
    };

    const replaceRegions = (currentWorkspace, nextWorkspace, regions) => {
        for (const name of regions) {
            if (name === 'attempt-detail') releaseQuestionNavigation();
            const currentRegion = currentWorkspace.querySelector(`[data-tracking-region="${name}"]`);
            const nextRegion = nextWorkspace.querySelector(`[data-tracking-region="${name}"]`);
            if (!currentRegion || !nextRegion) throw new Error(`Missing tracking region: ${name}`);
            if (name === 'attempt-detail'
                && (currentRegion.classList.contains('is-opening') || currentRegion.querySelector('[data-attempt-detail-panel]'))
                && nextRegion.querySelector('[data-attempt-detail-panel]')) {
                nextRegion.classList.add('is-continuing');
            }
            currentRegion.replaceWith(nextRegion);
        }
        currentWorkspace.dataset.tab = nextWorkspace.dataset.tab;
    };

    const load = async (href, { historyMode = 'push', regions = ['main'], undoOptimistic = null } = {}) => {
        win.clearTimeout(searchTimer);
        searchTimer = null;
        const current = doc.querySelector('[data-tracking-workspace]');
        if (!current) return false;

        const destination = trackingUrl(href);
        const requestUrl = trackingRequestUrl(destination.href);
        const request = gate.begin();
        const activeSearch = doc.activeElement?.matches?.('[data-tracking-search]');
        const activeSearchParameter = activeSearch ? doc.activeElement.dataset.trackingSearch : null;
        const cursor = activeSearch ? doc.activeElement.selectionStart : null;
        const navigatorScroll = current.querySelector('.lt-nav-list')?.scrollTop ?? 0;
        const navigatorCollapsed = current.querySelector('[data-tracking-navigator]')?.classList.contains('is-collapsed') ?? false;
        const attemptWasOpen = Boolean(current.querySelector('[data-attempt-detail-panel]'));
        const targetRegions = normalizeTrackingRegions(regions);
        const attemptRegion = current.querySelector('[data-tracking-region="attempt-detail"]');
        const shouldCloseAttempt = shouldCloseTrackingAttempt(destination.href, targetRegions, attemptWasOpen);
        if (!shouldCloseAttempt) attemptRegion?.classList.remove('is-closing');
        const attemptExit = shouldCloseAttempt ? waitForAttemptExit(attemptRegion) : Promise.resolve();
        if (targetRegions.includes('attempt-detail') && destination.searchParams.has('attempt') && !attemptRegion?.querySelector('[data-attempt-detail-panel]')) {
            attemptRegion?.classList.add('is-opening');
            if (attemptRegion) attemptRegion.dataset.trackingOpeningRequest = String(request.id);
            syncAttemptScrollLock();
        }
        const targets = targetRegions
            .filter((name) => !['tabs', 'navigator'].includes(name))
            .map((name) => current.querySelector(`[data-tracking-region="${name}"]`))
            .filter(Boolean);
        const busyRequest = String(request.id);
        const busyTimer = win.setTimeout(() => targets.forEach((target) => {
            if (target.dataset.trackingBusyRequest === busyRequest) target.classList.add('is-loading');
        }), 160);
        targets.forEach((target) => {
            target.dataset.trackingBusyRequest = busyRequest;
            target.setAttribute('aria-busy', 'true');
        });

        try {
            const response = await fetch(requestUrl.href, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: request.signal,
            });
            if (!response.ok) throw new Error(`Tracking request failed (${response.status})`);
            const payload = await response.json();
            if (!gate.isCurrent(request.id)) return false;
            await attemptExit;
            if (!gate.isCurrent(request.id)) return false;
            const replacement = doc.createElement('template');
            replacement.innerHTML = payload.html.trim();
            const next = replacement.content.firstElementChild;
            if (!next?.matches('[data-tracking-workspace]')) throw new Error('Invalid tracking response');
            replaceRegions(current, next, targetRegions);
            syncAttemptScrollLock();
            if (targetRegions.includes('navigator')) {
                const nextNavigator = current.querySelector('[data-tracking-navigator]');
                const nextExpanded = nextNavigator?.querySelector('.lt-nav-expanded');
                nextNavigator?.classList.toggle('is-collapsed', navigatorCollapsed);
                nextExpanded?.toggleAttribute('inert', navigatorCollapsed);
                nextExpanded?.setAttribute('aria-hidden', navigatorCollapsed ? 'true' : 'false');
                nextNavigator?.querySelectorAll('[data-tracking-collapse]').forEach((button) => button.setAttribute('aria-expanded', navigatorCollapsed ? 'false' : 'true'));
                current.querySelector('.lt-layout')?.classList.toggle('is-collapsed', navigatorCollapsed);
            }
            if (payload.title) doc.title = payload.title;
            if (historyMode === 'push') win.history.pushState({ learnerTracking: true }, '', destination.href);
            if (historyMode === 'replace') win.history.replaceState({ learnerTracking: true }, '', destination.href);
            if (activeSearch) {
                const replacementSearch = current.querySelector(`[data-tracking-search="${activeSearchParameter}"]`);
                replacementSearch?.focus({ preventScroll: true });
                replacementSearch?.setSelectionRange(cursor, cursor);
            }
            const nextNavigatorList = current.querySelector('.lt-nav-list');
            if (nextNavigatorList) nextNavigatorList.scrollTop = navigatorScroll;
            if (targetRegions.includes('attempt-detail') && destination.searchParams.has('attempt')) {
                const focusTarget = attemptWasOpen
                    ? current.querySelector('[data-attempt-switch].is-active')
                    : current.querySelector('[data-attempt-detail-panel]');
                focusTarget?.focus({ preventScroll: true });
            } else if (targetRegions.includes('attempt-detail') && attemptOpener?.isConnected) {
                attemptOpener.focus({ preventScroll: true });
                attemptOpener = null;
            }
            return true;
        } catch (error) {
            if (attemptRegion?.dataset.trackingOpeningRequest === String(request.id)) {
                attemptRegion.classList.remove('is-opening');
                delete attemptRegion.dataset.trackingOpeningRequest;
            }
            if (error?.name === 'AbortError') return false;
            if (shouldCloseAttempt && gate.isCurrent(request.id)) {
                attemptRegion?.classList.remove('is-closing');
                syncAttemptScrollLock();
            }
            undoOptimistic?.();
            toast('Learner Tracking could not be refreshed. Please try again.');
            return false;
        } finally {
            win.clearTimeout(busyTimer);
            targets.forEach((target) => {
                if (target.dataset.trackingBusyRequest === busyRequest) {
                    delete target.dataset.trackingBusyRequest;
                    target.removeAttribute('aria-busy');
                    target.classList.remove('is-loading');
                }
            });
        }
    };

    const activateLocally = (anchor) => {
        const group = anchor.dataset.trackingActiveGroup;
        const selections = [];
        if (group) {
            const container = anchor.closest(group === 'tabs' ? '.lt-tabs' : '.lt-pivot');
            container?.querySelectorAll('a').forEach((item) => selections.push([item, item.classList.contains('is-active')]));
        } else if (anchor.matches('[data-tracking-selection]')) {
            const container = anchor.closest('[data-tracking-navigator]');
            container?.querySelectorAll('[data-tracking-selection]').forEach((item) => selections.push([item, item.classList.contains('is-active')]));
            anchor.closest('details')?.setAttribute('open', '');
        }
        if (selections.length === 0) return null;
        selections.forEach(([item]) => item.classList.remove('is-active'));
        anchor.classList.add('is-active');

        return () => selections.forEach(([item, wasActive]) => item.classList.toggle('is-active', wasActive));
    };

    const onClick = (event) => {
        const questionJump = event.target.closest('[data-question-jump]');
        if (questionJump) {
            event.preventDefault();
            const drawerBody = questionJump.closest('.lt-attempt-drawer-body');
            const target = drawerBody?.querySelector(questionJump.dataset.questionJump);
            if (drawerBody && target) {
                const navHeight = drawerBody.querySelector('[data-question-navigator]')?.offsetHeight ?? 0;
                const targetTop = target.getBoundingClientRect().top
                    - drawerBody.getBoundingClientRect().top
                    + drawerBody.scrollTop
                    - navHeight
                    - 10;
                const reducedMotion = win.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
                questionNavigationBody = drawerBody;
                setCurrentQuestion(drawerBody, questionNavigationGate.begin(target.id));
                win.clearTimeout(questionNavigationTimer);
                questionNavigationTimer = win.setTimeout(() => {
                    releaseQuestionNavigation();
                    if (drawerBody.isConnected) syncVisibleQuestion(drawerBody);
                }, reducedMotion ? 0 : 900);
                drawerBody.scrollTo({ top: Math.max(0, targetTop), behavior: questionScrollBehavior(reducedMotion) });
                questionJump.focus({ preventScroll: true });
            }
            return;
        }

        const attemptBackdrop = event.target.closest('[data-attempt-backdrop-close]');
        if (attemptBackdrop) {
            event.preventDefault();
            attemptBackdrop.closest('[data-tracking-region="attempt-detail"]')?.querySelector('[data-attempt-close]')?.click();
            return;
        }

        const clear = event.target.closest('[data-search-clear]');
        if (clear) {
            const input = clear.closest('.lt-search')?.querySelector('[data-tracking-search]');
            if (input) {
                input.value = '';
                input.focus();
                clear.classList.add('is-hidden');
                const url = trackingUrl(win.location.href, trackingSearchChanges(input.dataset.trackingSearch, ''));
                load(url.href, {
                    historyMode: 'replace',
                    regions: trackingRegionsForAction(input.dataset.trackingAction, input.dataset.trackingRegions, ['navigator-list']),
                });
            }
            return;
        }

        const collapse = event.target.closest('[data-tracking-collapse]');
        if (collapse) {
            const navigator = collapse.closest('[data-tracking-navigator]');
            const layout = navigator?.closest('.lt-layout');
            const collapsed = !navigator?.classList.contains('is-collapsed');
            navigator?.classList.toggle('is-collapsed', collapsed);
            layout?.classList.toggle('is-collapsed', collapsed);
            const expanded = navigator?.querySelector('.lt-nav-expanded');
            expanded?.toggleAttribute('inert', collapsed);
            expanded?.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
            navigator?.querySelectorAll('[data-tracking-collapse]').forEach((button) => button.setAttribute('aria-expanded', collapsed ? 'false' : 'true'));
            navigator?.querySelector(collapsed ? '.lt-nav-show' : '.lt-collapse')?.focus();
            return;
        }

        const anchor = event.target.closest('a[data-tracking-link]');
        if (!shouldHandleTrackingLink(event, anchor) || anchor.classList.contains('is-disabled')) return;
        event.preventDefault();
        if (anchor.matches('[data-attempt-opener]')) {
            attemptOpener = anchor;
        }
        load(anchor.href, {
            regions: trackingRegionsForAction(anchor.dataset.trackingAction, anchor.dataset.trackingRegions),
            undoOptimistic: activateLocally(anchor),
        });
    };

    const onInput = (event) => {
        const input = event.target.closest('[data-tracking-search]');
        if (!input) return;
        input.closest('.lt-search')?.querySelector('[data-search-clear]')?.classList.toggle('is-hidden', input.value.length === 0);
        gate.abort();
        win.clearTimeout(searchTimer);
        if (input.dataset.trackingSearch === 'participant_search') invalidateAttemptImmediately();
        searchTimer = win.setTimeout(() => {
            const url = trackingUrl(win.location.href, trackingSearchChanges(input.dataset.trackingSearch, input.value));
            load(url.href, {
                historyMode: 'replace',
                regions: trackingRegionsForAction(input.dataset.trackingAction, input.dataset.trackingRegions, input.dataset.trackingSearch === 'search' ? ['navigator-list'] : ['quiz-results']),
            });
        }, 300);
    };

    const onChange = (event) => {
        const period = event.target.closest('[data-tracking-period]');
        if (period) {
            const form = period.closest('[data-tracking-period-form]');
            form?.querySelector('[data-custom-period]')?.classList.toggle('is-hidden', period.value !== 'custom');
            if (period.value !== 'custom') {
                const url = trackingUrl(win.location.href, { period: period.value, from: null, until: null, activity_page: 1, engagement_page: 1, quiz_page: 1 });
                load(url.href, { regions: trackingRegionsForAction(period.dataset.trackingAction, period.dataset.trackingRegions) });
            }
            return;
        }
        const rows = event.target.closest('[data-tracking-rows]');
        if (rows) {
            const url = trackingUrl(win.location.href, { rows: rows.value, activity_page: 1, engagement_page: 1, quiz_page: 1 });
            load(url.href, { regions: normalizeTrackingRegions(rows.dataset.trackingRegions) });
            return;
        }
        const filter = event.target.closest('[data-tracking-filter]');
        if (filter) {
            const changes = { [filter.dataset.trackingFilter]: filter.value || null, engagement_page: 1, quiz_page: 1 };
            if (filter.dataset.trackingFilter === 'status') changes.attempt = null;
            const url = trackingUrl(win.location.href, changes);
            load(url.href, { regions: normalizeTrackingRegions(filter.dataset.trackingRegions) });
        }
    };

    const onSubmit = (event) => {
        const form = event.target.closest('[data-tracking-period-form]');
        if (!form) return;
        event.preventDefault();
        const from = form.elements.from?.value;
        const until = form.elements.until?.value;
        if (!from || !until || from > until) {
            toast('Choose a valid From and To date.');
            return;
        }
        const url = trackingUrl(win.location.href, { period: 'custom', from, until, activity_page: 1, engagement_page: 1, quiz_page: 1 });
        load(url.href, { regions: TRACKING_ACTION_REGIONS.period });
    };

    const onKeyDown = (event) => {
        const panel = doc.querySelector('[data-attempt-detail-panel]');
        if (!panel) return;
        if (questionNavigationBody?.contains(event.target)
            && ['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End'].includes(event.key)) {
            releaseQuestionNavigation();
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            panel.querySelector('[data-attempt-close]')?.click();
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...panel.querySelectorAll('a[href], button:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])')]
            .filter((item) => !item.hasAttribute('hidden'));
        if (focusable.length === 0) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && (doc.activeElement === first || doc.activeElement === panel)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && doc.activeElement === panel) {
            event.preventDefault();
            first.focus();
        } else if (!event.shiftKey && doc.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    };

    const onScroll = (event) => {
        if (event.target.matches?.('.lt-attempt-drawer-body')) syncVisibleQuestion(event.target);
    };

    const onScrollIntent = (event) => {
        if (questionNavigationBody?.contains(event.target)) releaseQuestionNavigation();
    };

    const onPopState = () => {
        if (doc.querySelector('[data-tracking-workspace]')) load(win.location.href, { historyMode: 'none', regions: TRACKING_ACTION_REGIONS.history });
    };

    doc.addEventListener('click', onClick);
    doc.addEventListener('input', onInput);
    doc.addEventListener('change', onChange);
    doc.addEventListener('submit', onSubmit);
    doc.addEventListener('keydown', onKeyDown);
    doc.addEventListener('scroll', onScroll, true);
    doc.addEventListener('wheel', onScrollIntent, true);
    doc.addEventListener('touchstart', onScrollIntent, true);
    win.addEventListener('popstate', onPopState);
    syncAttemptScrollLock();

    const controller = {
        load,
        destroy() {
            gate.abort();
            win.clearTimeout(searchTimer);
            releaseQuestionNavigation();
            doc.removeEventListener('click', onClick);
            doc.removeEventListener('input', onInput);
            doc.removeEventListener('change', onChange);
            doc.removeEventListener('submit', onSubmit);
            doc.removeEventListener('keydown', onKeyDown);
            doc.removeEventListener('scroll', onScroll, true);
            doc.removeEventListener('wheel', onScrollIntent, true);
            doc.removeEventListener('touchstart', onScrollIntent, true);
            win.removeEventListener('popstate', onPopState);
            doc.documentElement?.classList.remove('lt-attempt-scroll-locked');
            doc.body?.classList.remove('lt-attempt-scroll-locked');
            delete win.__learnerTrackingInitialized;
        },
    };
    win.__learnerTrackingInitialized = controller;
    return controller;
}

if (typeof document !== 'undefined' && typeof window !== 'undefined') initializeLearnerTracking();
