export const ANALYTICS_PAGE_SIZES = [10, 25, 50];

export function initAnalyticsTablePagination(root = document, selector) {
    root.querySelectorAll(selector).forEach((table) => {
        if (table.dataset.paginationReady) return;
        table.dataset.paginationReady = 'true';

        const body = table.tBodies[0];
        const rows = body
            ? [...body.rows].filter((row) => !row.querySelector('.ana-inline-empty'))
            : [];
        if (rows.length === 0) return;

        const container = table.closest('.ana-table-divider');
        if (!container) return;

        const pager = document.createElement('div');
        pager.className = 'ana-pagination';
        pager.innerHTML = `<label>Rows <select>${ANALYTICS_PAGE_SIZES.map((size) => `<option value="${size}">${size}</option>`).join('')}</select></label><span class="ana-page-range"></span><button type="button" class="ana-page-prev">Previous</button><button type="button" class="ana-page-next">Next</button>`;
        container.append(pager);

        const select = pager.querySelector('select');
        const range = pager.querySelector('.ana-page-range');
        const previous = pager.querySelector('.ana-page-prev');
        const next = pager.querySelector('.ana-page-next');
        const noun = table.dataset.noun || 'rows';
        let page = 1;

        const render = () => {
            const size = Number(select.value);
            const pages = Math.max(1, Math.ceil(rows.length / size));
            page = Math.max(1, Math.min(page, pages));
            const firstIndex = (page - 1) * size;
            const lastIndex = Math.min(page * size, rows.length);

            rows.forEach((row, index) => {
                row.hidden = index < firstIndex || index >= lastIndex;
            });
            range.textContent = `Showing ${firstIndex + 1}–${lastIndex} of ${rows.length} ${noun}`;
            previous.disabled = page === 1;
            next.disabled = page === pages;
        };

        select.addEventListener('change', () => {
            page = 1;
            render();
        });
        previous.addEventListener('click', () => {
            page--;
            render();
        });
        next.addEventListener('click', () => {
            page++;
            render();
        });
        render();
    });
}
