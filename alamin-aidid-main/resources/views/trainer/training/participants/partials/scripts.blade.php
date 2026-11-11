@push('scripts')
<script>
(() => {
    'use strict';
    if (window.__lmsParticipantsInitialized) return;
    window.__lmsParticipantsInitialized = true;
    const form = document.querySelector('[data-lms-participant-form]');
    if (!form) return;
    const search = form.querySelector('[data-lms-search]');
    const clear = form.querySelector('[data-lms-search-clear]');
    const letterInput = form.querySelector('[data-lms-letter-input]');
    let controller = null;
    let requestNumber = 0;
    let debounceTimer = null;
    const results = () => document.getElementById('lmsParticipantResults');
    const setClearVisibility = () => { if (clear && search) clear.hidden = search.value.length === 0; };
    const urlFromForm = (extra = {}) => {
        const url = new URL(form.action, window.location.href);
        new FormData(form).forEach((value, key) => value ? url.searchParams.set(key, value) : url.searchParams.delete(key));
        Object.entries(extra).forEach(([key, value]) => value ? url.searchParams.set(key, value) : url.searchParams.delete(key));
        url.searchParams.delete('page');
        return url;
    };
    const syncControls = (url) => {
        for (const control of form.elements) if (control.name) control.value = url.searchParams.get(control.name) || (control.tagName === 'SELECT' ? control.options[0]?.value || '' : '');
        setClearVisibility();
        document.querySelectorAll('[data-lms-letter]').forEach((button) => button.classList.toggle('is-active', button.dataset.lmsLetter === (letterInput?.value || '')));
    };
    const load = async (url, historyMode = 'push') => {
        controller?.abort();
        controller = new AbortController();
        const currentRequest = ++requestNumber;
        const region = results();
        region?.classList.add('is-loading');
        try {
            const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}, signal: controller.signal});
            if (!response.ok) throw new Error('Unable to load participants.');
            const replacement = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('lmsParticipantResults');
            if (currentRequest !== requestNumber) return;
            if (!replacement || !region) throw new Error('Incomplete participant response.');
            region.replaceWith(replacement);
            if (historyMode === 'push') history.pushState({}, '', url);
            if (historyMode === 'replace') history.replaceState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError' && region) { region.classList.remove('is-loading'); region.querySelector('[data-lms-report-error]')?.removeAttribute('hidden'); }
        }
    };
    form.addEventListener('submit', (event) => event.preventDefault());
    form.addEventListener('change', (event) => { if (event.target.matches('select')) load(urlFromForm(), 'push'); });
    search?.addEventListener('input', () => { setClearVisibility(); window.clearTimeout(debounceTimer); debounceTimer = window.setTimeout(() => load(urlFromForm(), 'replace'), 300); });
    clear?.addEventListener('click', () => { search.value = ''; search.focus(); setClearVisibility(); window.clearTimeout(debounceTimer); load(urlFromForm(), 'replace'); });
    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest('#lmsParticipantResults [data-lms-report-link]');
        if (pageLink) { event.preventDefault(); load(new URL(pageLink.href), 'push'); return; }
        const letter = event.target.closest('[data-lms-letter]');
        if (letter && letterInput && !letter.disabled) { letterInput.value = letter.dataset.lmsLetter; document.querySelectorAll('[data-lms-letter]').forEach((button) => button.classList.toggle('is-active', button === letter)); load(urlFromForm(), 'push'); }
    });
    document.addEventListener('change', (event) => { if (event.target.matches('#lmsParticipantResults [data-lms-rows]')) load(urlFromForm({rows: event.target.value}), 'push'); });
    window.addEventListener('popstate', () => { const url = new URL(window.location.href); syncControls(url); load(url, 'none'); });
    setClearVisibility();
})();
</script>
@endpush
