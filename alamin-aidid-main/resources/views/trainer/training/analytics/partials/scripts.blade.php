<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeRequest = null;
    let requestSequence = 0;
    const currentRegion = () => document.getElementById('analyticsDynamicRegion');
    const initCustomDateRange = (root = document) => {
        const period = root.querySelector('#reportDateSelector');
        const range = root.querySelector('.ana-daterange');
        if (!period || !range) return;
        const from = range.querySelector('[name="date_from"]');
        const to = range.querySelector('[name="date_to"]');
        const sync = () => {
            const visible = period.value === 'custom';
            range.hidden = !visible;
            if (from && to) to.setCustomValidity(visible && from.value && to.value && to.value < from.value ? 'The end date must be on or after the start date.' : '');
        };
        period.addEventListener('change', sync);
        from?.addEventListener('change', sync);
        to?.addEventListener('change', sync);
        sync();
    };
    initCustomDateRange();
    window.initAnalyticsCharts?.();
    window.initAnalyticsTables?.();
    const update = async (url, push = true) => {
        const region = currentRegion();
        if (!region) return;
        if (activeRequest) activeRequest.abort();
        const controller = new AbortController();
        activeRequest = controller;
        const sequence = ++requestSequence;
        const scrollY = window.scrollY;
        const focusKey = document.activeElement?.id || document.activeElement?.name || '';
        region.classList.add('is-loading');
        region.setAttribute('aria-busy', 'true');
        try {
            window.destroyAnalyticsCharts?.();
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
            if (!response.ok) throw new Error('Analytics request failed');
            const nextRegion = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector('#analyticsDynamicRegion');
            if (!nextRegion || sequence !== requestSequence) throw new Error('Stale analytics response');
            region.replaceWith(nextRegion);
            initCustomDateRange(nextRegion);
            window.initAnalyticsCharts?.(nextRegion);
            window.initAnalyticsTables?.(nextRegion);
            if (push) history.pushState({}, '', url);
            window.scrollTo({ top: scrollY, behavior: 'instant' });
            const focusTarget = focusKey && (document.getElementById(focusKey) || document.querySelector(`[name="${CSS.escape(focusKey)}"]`));
            focusTarget?.focus({ preventScroll: true });
        } catch (error) {
            if (error.name !== 'AbortError' && sequence === requestSequence) window.location.assign(url);
        } finally {
            if (sequence === requestSequence) {
                activeRequest = null;
                currentRegion()?.classList.remove('is-loading');
                currentRegion()?.removeAttribute('aria-busy');
            }
        }
    };
    document.addEventListener('change', (event) => {
        const control = event.target.closest('#analyticsFilterForm select, #analyticsFilterForm input, [form="analyticsFilterForm"]');
        if (!control) return;
        const form = document.getElementById('analyticsFilterForm');
        if (!form) return;
        if (control.id === 'reportDateSelector') {
            if (control.value === 'custom') { initCustomDateRange(currentRegion()); return; }
            update(new URL(form.action + '?' + new URLSearchParams(new FormData(form)), window.location.origin).toString());
            return;
        }
        if (control.id === 'dateFrom' || control.id === 'dateTo') {
            const from = form.querySelector('#dateFrom')?.value;
            const to = form.querySelector('#dateTo')?.value;
            if (!from || !to || to < from) { form.querySelector('#dateTo')?.reportValidity(); return; }
        }
        const url = new URL(form.action, window.location.origin);
        for (const [key, value] of new FormData(form)) if (value !== '') url.searchParams.set(key, value);
        update(url.toString());
    });
    window.addEventListener('popstate', () => update(window.location.href, false));
});
</script>
