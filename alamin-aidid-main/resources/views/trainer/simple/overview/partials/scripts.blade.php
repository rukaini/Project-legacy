<script>
(() => {
    'use strict';
    if (window.__simpleOverviewInitialized) return;
    window.__simpleOverviewInitialized = true;
    const toggle = document.querySelector('[data-past-toggle]');
    const list = document.getElementById('pastSessionList');
    toggle?.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        list.hidden = !open;
    });
    document.querySelector('[data-show-more-past]')?.addEventListener('click', (event) => {
        const hidden = Array.from(document.querySelectorAll('[data-past-row][hidden]'));
        hidden.slice(0, Number(event.currentTarget.dataset.batchSize) || 5).forEach((row) => row.hidden = false);
        if (hidden.length <= 5) event.currentTarget.remove();
    });
})();
</script>
