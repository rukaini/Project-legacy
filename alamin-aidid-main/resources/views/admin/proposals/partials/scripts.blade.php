<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('reviewModal');
        var reviewModal = null;

        if (modalEl && window.bootstrap) {
            reviewModal = new bootstrap.Modal(modalEl);
            var form = document.getElementById('reviewForm');
            var titleEl = document.getElementById('reviewModalTitle');
            var propEl = document.getElementById('reviewModalProp');
            var labelEl = document.getElementById('reviewCommentLabel');
            var hintEl = document.getElementById('reviewCommentHint');
            var commentEl = document.getElementById('reviewComment');
            var submitBtn = document.getElementById('reviewSubmitBtn');

            document.querySelectorAll('[data-action="reject"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = btn.dataset.url;
                    propEl.textContent = btn.dataset.title || 'this proposal';
                    commentEl.value = '';
                    titleEl.textContent = 'Reject proposal';
                    labelEl.textContent = 'Reason for rejection (required)';
                    hintEl.textContent = 'Explain why so the trainer can improve and resubmit.';
                    commentEl.placeholder = 'e.g. Scope is too broad for the proposed dates.';
                    commentEl.required = true;
                    submitBtn.textContent = 'Reject proposal';
                    submitBtn.className = 'adm-btn adm-btn-reject';
                    reviewModal.show();
                });
            });

            document.querySelectorAll('[data-action="approve"]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var title = btn.dataset.title || 'this proposal';
                    if (!confirm('Approve "' + title + '"?')) return;

                    var approvalForm = document.createElement('form');
                    approvalForm.method = 'POST';
                    approvalForm.action = btn.dataset.url;
                    approvalForm.style.display = 'none';

                    var token = document.querySelector('meta[name="csrf-token"]');
                    var fallbackToken = document.querySelector('input[name="_token"]');
                    var tokenValue = token ? token.getAttribute('content') : (fallbackToken ? fallbackToken.value : null);

                    if (tokenValue) {
                        var tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = '_token';
                        tokenInput.value = tokenValue;
                        approvalForm.appendChild(tokenInput);
                    }

                    document.body.appendChild(approvalForm);
                    approvalForm.submit();
                });
            });
        }

        document.querySelectorAll('[data-toggle-body]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var card = btn.closest('.adm-prop-card');
                if (!card) return;

                var body = card.querySelector('[data-body]');
                var toggleLabel = btn.querySelector('[data-toggle-label]');
                var icon = btn.querySelector('i');
                if (!body) return;

                var open = body.classList.toggle('collapsed') === false;
                card.classList.toggle('details-expanded', open);
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (toggleLabel) toggleLabel.textContent = open ? 'Hide details' : 'View details';
                if (icon) icon.className = open ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
            });
        });

        var listEl = document.getElementById('admProposalList');
        var cards = Array.prototype.slice.call(document.querySelectorAll('.adm-prop-card[data-status]'));
        var tabs = document.querySelectorAll('.adm-filter-btn');
        var searchInput = document.getElementById('admSearch');
        var searchClear = document.querySelector('[data-admin-proposal-search-clear]');
        var sortSelect = document.getElementById('admSort');
        var rowsSelect = document.querySelector('[data-admin-proposal-rows]');
        var emptyEl = document.getElementById('admFilterEmpty');
        var pager = document.getElementById('admPager');
        var pagerControls = document.getElementById('admPagerControls');
        var countEl = document.getElementById('admCount');
        var anchor = document.querySelector('.adm-list-anchor');

        var perPage = rowsSelect ? Number(rowsSelect.value) || 10 : 10;
        var activeFilter = 'pending';
        var currentPage = 1;
        var sortMode = 'newest';

        function getMatches() {
            var query = (searchInput && searchInput.value ? searchInput.value : '').trim().toLowerCase();
            var matches = cards.filter(function (card) {
                var statusMatches = activeFilter === 'all' || card.dataset.status === activeFilter;
                var searchableText = (card.dataset.search || '').toLowerCase();
                var searchMatches = query === '' || searchableText.indexOf(query) !== -1;

                return statusMatches && searchMatches;
            });

            matches.sort(function (first, second) {
                if (sortMode === 'trainer') {
                    return (first.dataset.trainer || '').localeCompare(second.dataset.trainer || '');
                }

                var firstCreated = Number(first.dataset.created || 0);
                var secondCreated = Number(second.dataset.created || 0);

                return sortMode === 'oldest' ? firstCreated - secondCreated : secondCreated - firstCreated;
            });

            return matches;
        }

        function renderPager(totalPages, total) {
            if (!pagerControls) return;

            pagerControls.innerHTML = '<button type="button" class="adm-page-btn" data-page="prev"' + (currentPage === 1 || total === 0 ? ' disabled' : '') + '><i class="bi bi-chevron-left" aria-hidden="true"></i>Previous</button>'
                + '<span class="adm-page-current" aria-current="page">' + currentPage + ' / ' + totalPages + '</span>'
                + '<button type="button" class="adm-page-btn" data-page="next"' + (currentPage === totalPages || total === 0 ? ' disabled' : '') + '>Next<i class="bi bi-chevron-right" aria-hidden="true"></i></button>';

            pagerControls.querySelectorAll('[data-page]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (button.dataset.page === 'prev' && currentPage > 1) currentPage--;
                    if (button.dataset.page === 'next' && currentPage < totalPages) currentPage++;
                    render();

                    if (anchor) {
                        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        window.scrollTo({
                            top: anchor.offsetTop - 80,
                            behavior: reducedMotion ? 'auto' : 'smooth'
                        });
                    }
                });
            });
        }

        function render() {
            if (!listEl) return;

            var matches = getMatches();
            cards.forEach(function (card) { card.style.display = 'none'; });

            var total = matches.length;
            var totalPages = Math.max(1, Math.ceil(total / perPage));
            if (currentPage > totalPages) currentPage = totalPages;

            var start = (currentPage - 1) * perPage;
            var visible = matches.slice(start, start + perPage);

            visible.forEach(function (card) {
                card.style.display = '';
                listEl.appendChild(card);
            });

            if (countEl) {
                countEl.innerHTML = total === 0
                    ? '<strong>0</strong> proposals'
                    : 'Showing <strong>' + (start + 1) + '–' + Math.min(start + perPage, total) + '</strong> of <strong>' + total + '</strong> proposals';
            }

            if (emptyEl) emptyEl.style.display = total === 0 ? 'block' : 'none';
            if (pager) pager.hidden = false;
            if (searchClear) searchClear.hidden = !searchInput || searchInput.value === '';
            renderPager(totalPages, total);
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                tabs.forEach(function (otherTab) {
                    otherTab.classList.remove('active');
                    otherTab.setAttribute('aria-pressed', 'false');
                });

                tab.classList.add('active');
                tab.setAttribute('aria-pressed', 'true');
                activeFilter = tab.dataset.filter;
                currentPage = 1;
                render();
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                currentPage = 1;
                render();
            });
        }

        if (searchClear) {
            searchClear.addEventListener('click', function () {
                searchInput.value = '';
                currentPage = 1;
                render();
                searchInput.focus();
            });
        }

        if (sortSelect) {
            sortSelect.addEventListener('change', function () {
                sortMode = sortSelect.value;
                currentPage = 1;
                render();
            });
        }

        if (rowsSelect) {
            rowsSelect.addEventListener('change', function () {
                perPage = Number(rowsSelect.value) || 10;
                currentPage = 1;
                render();
            });
        }

        render();
    });
</script>
