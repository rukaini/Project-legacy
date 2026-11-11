@push('scripts')
<script id="calendar-session-data" type="application/json">{!! json_encode($calendarSessionsData) !!}</script>
<script>
(() => {
    'use strict';
    if (window.__lmsAttendanceInitialized) return;
    window.__lmsAttendanceInitialized = true;

    const calendarData = (() => { try { return JSON.parse(document.getElementById('calendar-session-data')?.textContent || '{}'); } catch (error) { return {}; } })();
    const form = document.querySelector('[data-lms-attendance-form]');
    const search = form?.querySelector('[data-lms-search]');
    const clear = form?.querySelector('[data-lms-search-clear]');
    const sessionSearch = document.querySelector('[data-session-search]');
    const sessionSearchClear = document.querySelector('[data-session-search-clear]');
    const sessionInput = form?.querySelector('[name="session_id"]');
    const calendar = document.getElementById('calendarModal');
    const dayPopover = document.getElementById('dayDetailPopover');
    let selectedSessionFilter = 'all';
    let controller = null;
    let requestNumber = 0;
    let debounceTimer = null;
    let calendarOpener = null;
    let previousBodyOverflow = '';
    let currentCalDate = new Date('{{ $initialCalendarDate }}T00:00:00');
    if (Number.isNaN(currentCalDate.getTime())) currentCalDate = new Date();
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const weekdayLong = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const today = new Date();
    const todayKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    const escapeHtml = (value) => String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    const dateKey = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const setClearVisibility = () => { if (clear && search) clear.hidden = search.value.length === 0; };
    const setSessionClearVisibility = () => { if (sessionSearchClear && sessionSearch) sessionSearchClear.hidden = sessionSearch.value.length === 0; };
    const filterSessions = () => {
        const needle = (document.querySelector('[data-session-search]')?.value || '').toLowerCase().trim();
        const cards = [...document.querySelectorAll('[data-lms-session-link]')];
        let visible = 0;
        cards.forEach((card) => { const show = (card.dataset.sessionSearch || '').includes(needle) && (selectedSessionFilter === 'all' || card.dataset.sessionGroup === selectedSessionFilter); card.classList.toggle('lw-hidden', !show); if (show) visible++; });
        document.getElementById('noSessionFilterResult')?.classList.toggle('lw-hidden', !(cards.length && visible === 0));
    };
    const urlFromForm = (extra = {}) => {
        const url = new URL(form?.action || window.location.href, window.location.href);
        if (form) new FormData(form).forEach((value, key) => value && value !== 'all' ? url.searchParams.set(key, value) : url.searchParams.delete(key));
        Object.entries(extra).forEach(([key, value]) => value && value !== 'all' ? url.searchParams.set(key, value) : url.searchParams.delete(key));
        url.searchParams.delete('page');
        return url;
    };
    const syncControls = (url) => {
        if (!form) return;
        for (const control of form.elements) if (control.name) control.value = url.searchParams.get(control.name) || (control.name === 'status' ? 'all' : control.name === 'rows' ? '10' : '');
        setClearVisibility();
    };
    const replaceReportRegions = (documentFragment) => {
        for (const id of ['lmsAttendanceSessionDetail', 'lmsAttendanceRosterSummary', 'lmsAttendanceRosterResults']) {
            const current = document.getElementById(id);
            const replacement = documentFragment.getElementById(id);
            if (current && replacement) current.replaceWith(replacement);
        }
    };
    const load = async (url, historyMode = 'push') => {
        controller?.abort();
        controller = new AbortController();
        const currentRequest = ++requestNumber;
        const region = document.getElementById('lmsAttendanceRosterResults');
        region?.classList.add('is-loading');
        try {
            const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html'}, signal: controller.signal});
            if (!response.ok) throw new Error('Unable to load attendance.');
            const fragment = new DOMParser().parseFromString(await response.text(), 'text/html');
            if (currentRequest !== requestNumber) return;
            replaceReportRegions(fragment);
            const selectedId = url.searchParams.get('session_id') || '';
            document.querySelectorAll('[data-lms-session-link]').forEach((link) => link.classList.toggle('is-active', link.dataset.sessionId === selectedId));
            if (historyMode === 'push') history.pushState({}, '', url);
            if (historyMode === 'replace') history.replaceState({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError' && region) { region.classList.remove('is-loading'); region.querySelector('[data-lms-report-error]')?.removeAttribute('hidden'); }
        }
    };

    const renderCalendar = () => {
        const year = currentCalDate.getFullYear();
        const month = currentCalDate.getMonth();
        const heading = document.getElementById('calMonthYear');
        if (heading) heading.textContent = `${monthNames[month]} ${year}`;
        const first = new Date(year, month, 1);
        const start = new Date(first);
        start.setDate(start.getDate() - start.getDay());
        let html = '';
        for (let index = 0; index < 42; index++) {
            const day = new Date(start); day.setDate(start.getDate() + index);
            const key = dateKey(day); const events = calendarData[key] || []; const visible = events.slice(0, 3);
            const eventHtml = visible.map((event) => `<a href="${escapeHtml(event.url)}" class="lw-cal-event ${event.is_online ? 'online' : 'physical'}" data-calendar-session-link title="${escapeHtml(event.title)}"><p class="lw-cal-event-type">${escapeHtml(event.type_label)}</p><p class="lw-cal-event-title">${escapeHtml(event.title)}</p><p class="lw-cal-event-time">${escapeHtml(event.time_label)}</p></a>`).join('');
            html += `<div class="lw-cal-day${day.getMonth() === month ? '' : ' out'}${events.length ? '' : ' no-events'}" data-calendar-day="${events.length ? key : ''}"><div class="lw-cal-day-num"><span class="lw-cal-num ${key === todayKey ? 'today' : ''}">${day.getDate()}</span>${events.length ? `<span class="lw-cal-count">${events.length}</span>` : ''}</div><div class="lw-cal-events">${eventHtml}${events.length > 3 ? `<button type="button" class="lw-cal-more" data-calendar-day="${key}">+${events.length - 3} more</button>` : ''}</div></div>`;
        }
        const grid = document.getElementById('calendarGrid'); if (grid) grid.innerHTML = html;
    };
    const closeDayDetail = () => dayPopover?.classList.add('is-hidden');
    const openDayDetail = (key) => {
        const events = calendarData[key] || []; if (!events.length) return;
        const [year, month, day] = key.split('-').map(Number); const date = new Date(year, month - 1, day);
        document.getElementById('dayDetailTitle').textContent = `${weekdayLong[date.getDay()]}, ${day} ${monthNames[month - 1]} ${year}`;
        document.getElementById('dayDetailSub').textContent = `${events.length} session${events.length === 1 ? '' : 's'} on this day`;
        document.getElementById('dayDetailList').innerHTML = events.map((event) => `<a href="${escapeHtml(event.url)}" class="lw-day-pop-event ${event.is_online ? 'online' : 'physical'}" data-calendar-session-link><p class="lw-day-pop-event-type"><i class="bi ${event.is_online ? 'bi-camera-video' : 'bi-geo-alt'}"></i> ${escapeHtml(event.type_label)}</p><p class="lw-day-pop-event-title">${escapeHtml(event.title)}</p><span class="lw-day-pop-event-time"><i class="bi bi-clock"></i> ${escapeHtml(event.time_label)}</span></a>`).join('');
        dayPopover?.classList.remove('is-hidden');
    };
    const openCalendar = () => { calendarOpener = document.activeElement; previousBodyOverflow = document.body.style.overflow; calendar?.classList.add('is-open'); calendar?.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; renderCalendar(); calendar?.querySelector('[data-calendar-close]')?.focus(); };
    const closeCalendar = () => { closeDayDetail(); calendar?.classList.remove('is-open'); calendar?.setAttribute('aria-hidden', 'true'); document.body.style.overflow = previousBodyOverflow; if (calendarOpener instanceof HTMLElement) calendarOpener.focus(); };

    form?.addEventListener('submit', (event) => event.preventDefault());
    form?.addEventListener('change', (event) => { if (event.target.matches('select')) load(urlFromForm(), 'push'); });
    search?.addEventListener('input', () => { setClearVisibility(); window.clearTimeout(debounceTimer); debounceTimer = window.setTimeout(() => load(urlFromForm(), 'replace'), 300); });
    clear?.addEventListener('click', () => { search.value = ''; search.focus(); setClearVisibility(); window.clearTimeout(debounceTimer); load(urlFromForm(), 'replace'); });
    sessionSearch?.addEventListener('input', () => { setSessionClearVisibility(); filterSessions(); });
    sessionSearchClear?.addEventListener('click', () => { sessionSearch.value = ''; sessionSearch.focus(); setSessionClearVisibility(); filterSessions(); });
    document.addEventListener('click', (event) => {
        const sidebarToggle = event.target.closest('[data-session-sidebar-toggle]'); if (sidebarToggle) { const sidebar = document.getElementById('sessionSidebar'); if (!sidebar || window.matchMedia('(max-width: 900px)').matches) return; const collapsed = !sidebar.classList.contains('is-collapsed'); const expanded = sidebar.querySelector('.lw-sb-expanded'); sidebar.classList.toggle('is-collapsed', collapsed); expanded?.toggleAttribute('inert', collapsed); expanded?.setAttribute('aria-hidden', collapsed ? 'true' : 'false'); sidebar.querySelectorAll('[data-session-sidebar-toggle]').forEach((button) => button.setAttribute('aria-expanded', collapsed ? 'false' : 'true')); sidebar.querySelector(collapsed ? '.lw-sb-collapsed-btn' : '.lw-sb-collapse-btn')?.focus(); return; }
        const sessionFilter = event.target.closest('[data-session-filter]'); if (sessionFilter) { selectedSessionFilter = sessionFilter.dataset.sessionFilter; document.querySelectorAll('[data-session-filter]').forEach((button) => button.classList.toggle('is-active', button === sessionFilter)); filterSessions(); return; }
        const pageLink = event.target.closest('#lmsAttendanceRosterResults [data-lms-report-link]'); if (pageLink) { event.preventDefault(); load(new URL(pageLink.href), 'push'); return; }
        const sessionLink = event.target.closest('[data-lms-session-link], [data-calendar-session-link]'); if (sessionLink && form && sessionInput) { event.preventDefault(); const url = new URL(sessionLink.href); sessionInput.value = url.searchParams.get('session_id') || ''; closeCalendar(); load(urlFromForm({session_id: sessionInput.value}), 'push'); return; }
        if (event.target.closest('[data-calendar-open]')) { openCalendar(); return; }
        if (event.target.closest('[data-calendar-close]')) { closeCalendar(); return; }
        const monthButton = event.target.closest('[data-calendar-month]'); if (monthButton) { currentCalDate = new Date(currentCalDate.getFullYear(), currentCalDate.getMonth() + Number(monthButton.dataset.calendarMonth), 1); renderCalendar(); return; }
        if (event.target.closest('[data-calendar-today]')) { currentCalDate = new Date(); renderCalendar(); return; }
        const dayButton = event.target.closest('[data-calendar-day]'); if (dayButton?.dataset.calendarDay && !event.target.closest('[data-calendar-session-link]')) openDayDetail(dayButton.dataset.calendarDay);
        if (event.target.closest('[data-day-detail-close]') || event.target === dayPopover) closeDayDetail();
        if (event.target === calendar) closeCalendar();
    });
    document.addEventListener('change', (event) => { if (event.target.matches('#lmsAttendanceRosterResults [data-lms-rows]')) load(urlFromForm({rows: event.target.value}), 'push'); });
    document.addEventListener('keydown', (event) => { if (!calendar?.classList.contains('is-open')) return; if (event.key === 'Escape') { event.preventDefault(); if (!dayPopover?.classList.contains('is-hidden')) closeDayDetail(); else closeCalendar(); } else if (!dayPopover?.classList.contains('is-hidden')) return; else if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') { currentCalDate = new Date(currentCalDate.getFullYear(), currentCalDate.getMonth() + (event.key === 'ArrowLeft' ? -1 : 1), 1); renderCalendar(); event.preventDefault(); } });
    window.addEventListener('popstate', () => { const url = new URL(window.location.href); syncControls(url); load(url, 'none'); });
    setClearVisibility(); setSessionClearVisibility(); filterSessions(); renderCalendar();
})();
</script>
@include('trainer.attendance.partials.qr-scripts')
@endpush
