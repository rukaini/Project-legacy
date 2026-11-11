<script>
(() => {
    'use strict';
    if (window.__simpleTrainingReportsInitialized) return;
    window.__simpleTrainingReportsInitialized = true;
    const form = document.querySelector('[data-simple-report-form]');
    if (!form) return;
    const resultsId = form.dataset.resultsId;
    const results = () => document.getElementById(resultsId);
    const search = form.querySelector('[data-simple-search]');
    const clear = form.querySelector('[data-simple-search-clear]');
    const letterInput = form.querySelector('[name="letter"]');
    let controller = null;
    let requestNumber = 0;
    let debounceTimer = null;
    const setClearVisibility = () => { if (clear && search) clear.hidden = search.value.length === 0; };
    const formUrl = (extra = {}) => {
        const url = new URL(window.location.href);
        const action = new URL(form.action, window.location.href);
        url.pathname = action.pathname;
        form.querySelectorAll('[name]').forEach((control) => {
            if (String(control.value).length) url.searchParams.set(control.name, control.value);
            else url.searchParams.delete(control.name);
        });
        Object.entries(extra).forEach(([key, value]) => value === '' || value === null ? url.searchParams.delete(key) : url.searchParams.set(key, value));
        url.searchParams.delete('page');
        return url;
    };
    const syncControls = (url) => {
        form.querySelectorAll('[name]').forEach((control) => {
            control.value = url.searchParams.has(control.name)
                ? url.searchParams.get(control.name)
                : (control.tagName === 'SELECT' ? (control.options[0]?.value || '') : '');
        });
        setClearVisibility();
        document.querySelectorAll('[data-simple-letter]').forEach((button) => button.classList.toggle('active', button.dataset.simpleLetter === (letterInput?.value || '')));
    };
    const load = async (url, historyMode = 'push') => {
        controller?.abort();
        controller = new AbortController();
        const currentRequest = ++requestNumber;
        const region = results();
        region?.classList.add('is-loading');
        try {
            const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}, signal: controller.signal});
            if (!response.ok) throw new Error('Unable to load this report.');
            const html = await response.text();
            if (currentRequest !== requestNumber) return;
            const replacement = new DOMParser().parseFromString(html, 'text/html').getElementById(resultsId);
            if (!replacement || !region) throw new Error('The report response was incomplete.');
            region.replaceWith(replacement);
            if (historyMode === 'push') history.pushState({}, '', url);
            if (historyMode === 'replace') history.replaceState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError' && region) {
                region.classList.remove('is-loading');
                region.querySelector('[data-simple-report-error]')?.removeAttribute('hidden');
            }
        }
    };
    form.addEventListener('submit', (event) => event.preventDefault());
    form.addEventListener('change', (event) => { if (event.target.matches('select')) load(formUrl(), 'push'); });
    search?.addEventListener('input', () => {
        setClearVisibility();
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => load(formUrl(), 'replace'), 300);
    });
    clear?.addEventListener('click', () => {
        search.value = '';
        search.focus();
        setClearVisibility();
        window.clearTimeout(debounceTimer);
        load(formUrl(), 'replace');
    });
    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest('[data-simple-report-link]');
        if (pageLink) { event.preventDefault(); load(new URL(pageLink.href), 'push'); return; }
        const letter = event.target.closest('[data-simple-letter]');
        if (letter && letterInput && !letter.disabled) {
            letterInput.value = letter.dataset.simpleLetter;
            document.querySelectorAll('[data-simple-letter]').forEach((button) => button.classList.toggle('active', button === letter));
            load(formUrl(), 'push');
        }
    });
    document.addEventListener('change', (event) => { if (event.target.matches('[data-simple-rows]')) load(formUrl({rows: event.target.value}), 'push'); });
    window.addEventListener('popstate', () => { const url = new URL(window.location.href); syncControls(url); load(url, 'none'); });
    setClearVisibility();
})();
</script>
