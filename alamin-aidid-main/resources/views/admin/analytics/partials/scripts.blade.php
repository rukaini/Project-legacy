<script>
document.addEventListener('DOMContentLoaded', () => {
    let activeRequest = null;
    let requestSequence = 0;
    const currentRegion = () => document.getElementById('adminAnalyticsDynamicRegion');
    const initCustomDateRange = (root = document) => {
        const period = root.querySelector('#adminReportDateSelector');
        const range = root.querySelector('.ana-daterange');
        if (!period || !range) return;
        const from = range.querySelector('[name="date_from"]');
        const to = range.querySelector('[name="date_to"]');
        const sync = () => {
            const visible = period.value === 'custom';
            range.hidden = !visible;
            if (from && to) to.setCustomValidity(visible && from.value && to.value && to.value < from.value ? 'The end date must be on or after the start date.' : '');
        };
        if (period.dataset.customRangeReady !== 'true') {
            period.dataset.customRangeReady = 'true';
            period.addEventListener('change', sync);
            from?.addEventListener('change', sync);
            to?.addEventListener('change', sync);
        }
        sync();
    };
    const initReports = (root = document) => {
        initCustomDateRange(root);
        window.initAnalyticsCharts?.(root);
        window.initAnalyticsTables?.(root);
        window.initAdminAnalyticsCharts?.(root);
        window.initAdminAnalyticsTables?.(root);
    };
    const update = async (url, push = true) => {
        const region = currentRegion();
        if (!region) return;
        activeRequest?.abort();
        const controller = new AbortController();
        activeRequest = controller;
        const sequence = ++requestSequence;
        const scrollY = window.scrollY;
        const focusKey = document.activeElement?.id || document.activeElement?.name || '';
        region.classList.add('is-loading');
        region.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: controller.signal });
            if (!response.ok) throw new Error('Admin Analytics request failed');
            const nextRegion = new DOMParser().parseFromString(await response.text(), 'text/html').querySelector('#adminAnalyticsDynamicRegion');
            if (!nextRegion || sequence !== requestSequence) throw new Error('Stale Admin Analytics response');
            region.replaceWith(nextRegion);
            initReports(nextRegion);
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

    initReports();
    document.addEventListener('change', (event) => {
        const control = event.target.closest('#adminAnalyticsFilterForm select, #adminAnalyticsFilterForm input, [form="adminAnalyticsFilterForm"]');
        if (!control) return;
        const form = document.getElementById('adminAnalyticsFilterForm');
        if (!form) return;
        if (control.id === 'adminReportDateSelector') {
            if (control.value === 'custom') { initCustomDateRange(currentRegion()); return; }
        }
        if (control.id === 'adminDateFrom' || control.id === 'adminDateTo') {
            const from = form.querySelector('#adminDateFrom')?.value;
            const to = form.querySelector('#adminDateTo')?.value;
            if (!from || !to || to < from) { form.querySelector('#adminDateTo')?.reportValidity(); return; }
        }
        if (control.id === 'adminTrainingTypeSelector') {
            const training = form.querySelector('#adminTrainingSelector');
            if (training) training.value = '0';
        }
        const url = new URL(form.action, window.location.origin);
        for (const [key, value] of new FormData(form)) if (value !== '') url.searchParams.set(key, value);
        update(url.toString());
    });
    window.addEventListener('popstate', () => update(window.location.href, false));
});
</script>
