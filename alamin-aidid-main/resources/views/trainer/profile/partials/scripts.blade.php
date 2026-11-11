<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tabButtons = Array.prototype.slice.call(document.querySelectorAll('.pf-tab-btn'));
        const tabPanels = Array.prototype.slice.call(document.querySelectorAll('.pf-tab-panel'));

        function activateTab(tab, moveFocus, updateUrl) {
            tabButtons.forEach(function (button) {
                const active = button.dataset.tab === tab;
                button.classList.toggle('active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
                button.tabIndex = active ? 0 : -1;
                if (active && moveFocus) button.focus();
            });

            tabPanels.forEach(function (panel) {
                const active = panel.dataset.panel === tab;
                panel.classList.toggle('show', active);
                panel.hidden = !active;
            });

            if (updateUrl) {
                try {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tab);
                    window.history.replaceState(null, '', url);
                } catch (error) {}
            }
        }

        tabButtons.forEach(function (button, index) {
            button.addEventListener('click', function () {
                activateTab(button.dataset.tab, false, true);
            });

            button.addEventListener('keydown', function (event) {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

                event.preventDefault();
                let nextIndex = index;
                if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabButtons.length) % tabButtons.length;
                if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabButtons.length;
                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = tabButtons.length - 1;
                activateTab(tabButtons[nextIndex].dataset.tab, true, true);
            });
        });

        const initiallyActiveTab = tabButtons.find(function (button) {
            return button.getAttribute('aria-selected') === 'true';
        });
        if (initiallyActiveTab) activateTab(initiallyActiveTab.dataset.tab, false, false);

        const certFileInput = document.getElementById('pfCertFileInput');
        const certFileName = document.getElementById('pfCertFileName');
        if (certFileInput && certFileName) {
            certFileInput.addEventListener('change', function () {
                if (this.files && this.files[0]) {
                    certFileName.textContent = this.files[0].name;
                    certFileName.classList.add('has-file');
                } else {
                    certFileName.textContent = 'No file chosen';
                    certFileName.classList.remove('has-file');
                }
            });
        }

        document.querySelectorAll('.js-doc-view').forEach(function (link) {
            link.addEventListener('click', function () {
                const card = link.closest('.pf-doc-card');
                if (!card) return;
                card.classList.remove('is-unread');
                card.dataset.read = '1';
                const dot = card.querySelector('.pf-doc-unread');
                if (dot) dot.remove();
            });
        });

        const docList = document.getElementById('docList');
        if (docList) {
            const docSearch = document.getElementById('docSearch');
            const docSort = document.getElementById('docSort');
            const docPager = document.getElementById('docPager');
            const docNoResult = document.getElementById('docNoResult');
            const allDocs = Array.prototype.slice.call(docList.querySelectorAll('.pf-doc-card'));
            const perPage = 6;
            let docPage = 1;

            function docGetFiltered() {
                const query = (docSearch.value || '').trim().toLowerCase();
                const list = allDocs.filter(function (card) {
                    if (!query) return true;
                    return card.dataset.title.indexOf(query) !== -1
                        || card.dataset.type.indexOf(query) !== -1
                        || card.dataset.desc.indexOf(query) !== -1;
                });

                const mode = docSort.value;
                list.sort(function (first, second) {
                    if (mode === 'oldest') return first.dataset.ts.localeCompare(second.dataset.ts);
                    if (mode === 'type') return first.dataset.type.localeCompare(second.dataset.type)
                        || second.dataset.ts.localeCompare(first.dataset.ts);
                    if (mode === 'unread') {
                        const firstUnread = first.dataset.read === '0' ? 1 : 0;
                        const secondUnread = second.dataset.read === '0' ? 1 : 0;
                        return (secondUnread - firstUnread) || second.dataset.ts.localeCompare(first.dataset.ts);
                    }
                    return second.dataset.ts.localeCompare(first.dataset.ts);
                });

                return list;
            }

            function docRender() {
                const list = docGetFiltered();
                list.forEach(function (card) { docList.appendChild(card); });
                allDocs.forEach(function (card) { card.style.display = 'none'; });

                const totalPages = Math.max(1, Math.ceil(list.length / perPage));
                docPage = Math.min(Math.max(docPage, 1), totalPages);
                const start = (docPage - 1) * perPage;
                const pageItems = list.slice(start, start + perPage);
                pageItems.forEach(function (card) { card.style.display = 'flex'; });

                if (docNoResult) docNoResult.style.display = list.length ? 'none' : 'block';

                if (!docPager) return;
                if (list.length <= perPage) {
                    docPager.innerHTML = '';
                    return;
                }

                const from = list.length ? start + 1 : 0;
                const to = start + pageItems.length;
                docPager.innerHTML =
                    '<div class="pf-pager-info">Showing ' + from + '–' + to + ' of ' + list.length + '</div>' +
                    '<div class="pf-pager-btns">' +
                    '<button type="button" class="pf-pager-btn" aria-label="Previous document page" data-page="' + (docPage - 1) + '"' + (docPage <= 1 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i></button>' +
                    '<span class="pf-pager-cur">Page ' + docPage + ' / ' + totalPages + '</span>' +
                    '<button type="button" class="pf-pager-btn" aria-label="Next document page" data-page="' + (docPage + 1) + '"' + (docPage >= totalPages ? ' disabled' : '') + '><i class="bi bi-chevron-right" aria-hidden="true"></i></button>' +
                    '</div>';
            }

            docSearch.addEventListener('input', function () {
                docPage = 1;
                docRender();
            });
            docSort.addEventListener('change', function () {
                docPage = 1;
                docRender();
            });
            docPager.addEventListener('click', function (event) {
                const button = event.target.closest('.pf-pager-btn');
                if (!button || button.disabled) return;
                const page = parseInt(button.dataset.page, 10);
                if (!Number.isNaN(page)) {
                    docPage = page;
                    docRender();
                }
            });

            docRender();
        }
    });
</script>
