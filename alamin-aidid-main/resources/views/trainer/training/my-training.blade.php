@php
    $courses = $courses ?? collect();

    $openUrlFor = function ($course) {
        $isLms = (($course->training_mode ?? 'standard') === 'lms');
        return $isLms
            ? route('trainer.courses.show', $course->course_id)
            : route('trainer.simple.overview', $course->course_id);
    };

    // Counts for the top stats strip — same visual anchor pattern as dashboard / proposals
    $totalCount  = $courses->count();
    $activeCount = $courses->filter(fn ($c) => strtolower($c->status ?? '') === 'active')->count();
    $lmsCount    = $courses->filter(fn ($c) => ($c->training_mode ?? 'standard') === 'lms')->count();
    $simpleCount = $totalCount - $lmsCount;
@endphp

@extends('trainer.layouts.app')

@section('title', 'My Training | Al Amin Edu Oasis')

@push('styles')
<style>
    :root {
        --trn-primary: #2563eb;
        --trn-primary-dark: #1d4ed8;
        --trn-primary-soft: #eff6ff;
        --trn-indigo: #4338ca;
        --trn-green: #059669;
        --trn-green-soft: #ecfdf5;
        --trn-pink: #db2777;
        --trn-slate-900: #0f172a;
        --trn-slate-600: #475569;
        --trn-slate-500: #64748b;
        --trn-slate-400: #94a3b8;
        --trn-border: #e2e8f0;
        --trn-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    /* Matches every other page in the system */
    body { background: #f8fafc; }

    .trn-wrap { color: var(--trn-slate-900); }

    .trn-head { margin-bottom: 20px; }
    /* Same 26px / 850 / -0.035em as every other page header title */
    .trn-title { font-size: 26px; font-weight: 850; letter-spacing: -0.035em; margin: 0 0 5px; line-height: 1.18; }
    .trn-subtitle { font-size: 14px; font-weight: 600; color: var(--trn-slate-500); margin: 0; }

    /* Stat cards — same pattern as dashboard / proposals to anchor the L/R rhythm */
    .trn-stat {
        background: #fff; border: 1px solid var(--trn-border); border-radius: 16px;
        padding: 16px 17px; box-shadow: var(--trn-shadow); height: 100%; transition: 0.15s ease;
    }
    .trn-stat:hover { transform: translateY(-2px); border-color: #bfdbfe; box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08); }
    .trn-stat-icon {
        width: 40px; height: 40px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 19px; margin-bottom: 12px;
    }
    .trn-ic-blue   { background: var(--trn-primary-soft); color: var(--trn-primary); }
    .trn-ic-green  { background: var(--trn-green-soft); color: var(--trn-green); }
    .trn-ic-indigo { background: #eef2ff; color: var(--trn-indigo); }
    .trn-ic-cyan   { background: #ecfeff; color: #0e7490; }
    .trn-stat-label-top {
        font-size: 11px; font-weight: 850; color: var(--trn-slate-500);
        text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 5px;
    }
    .trn-stat-value-top { font-size: 27px; font-weight: 900; line-height: 1; margin: 0; color: var(--trn-slate-900); }

    /* Filter bar (sits inside the main panel — matches proposals page rhythm) */
    .trn-filter-bar {
        padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
    }
    .trn-search { position: relative; }
    .trn-search i {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        color: var(--trn-slate-400); font-size: 14px;
    }
    .trn-search input {
        width: 100%; border: 1px solid var(--trn-border); border-radius: 10px;
        padding: 9px 12px 9px 34px; font-size: 13.5px; font-weight: 600;
        color: var(--trn-slate-600); background: #fff; outline: none; transition: 0.15s ease;
    }
    .trn-search input:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }
    .trn-select {
        width: 100%; border: 1px solid var(--trn-border); border-radius: 10px;
        padding: 9px 30px 9px 12px; font-size: 13px; font-weight: 750;
        color: var(--trn-slate-600); background-color: #fff; outline: none; cursor: pointer;
        appearance: none; -webkit-appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2364748b'><path d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z'/></svg>");
        background-repeat: no-repeat; background-position: right 9px center; background-size: 14px;
    }
    .trn-select:focus { border-color: #bfdbfe; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.09); }

    /* Panel — same 18px radius as proposals .pp-card */
    .trn-panel { background: #fff; border: 1px solid var(--trn-border); border-radius: 18px; box-shadow: var(--trn-shadow); overflow: hidden; }
    .trn-panel-head {
        padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    }
    .trn-panel-head > div { min-width: 0; }
    .trn-panel-title { font-size: 15px; font-weight: 850; margin: 0; }
    .trn-panel-sub { font-size: 12px; font-weight: 600; color: var(--trn-slate-500); margin-top: 2px; }
    .trn-count {
        font-size: 11px; font-weight: 850; color: var(--trn-primary);
        background: var(--trn-primary-soft); border: 1px solid #bfdbfe; border-radius: 999px; padding: 4px 11px; white-space: nowrap;
    }

    /* Course card row */
    .trn-card {
        display: flex; align-items: center; gap: 16px;
        padding: 16px 20px; border-bottom: 1px solid #f1f5f9; transition: 0.15s ease;
    }
    .trn-card:last-of-type { border-bottom: 0; }
    .trn-card:hover { background: #fbfcfe; }

    .trn-marker {
        width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
        background: var(--trn-primary-soft); color: var(--trn-primary); border: 1px solid #bfdbfe;
        display: flex; align-items: center; justify-content: center; font-size: 19px;
    }

    .trn-body { flex: 1; min-width: 0; }
    .trn-title-row { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; }
    .trn-name { overflow-wrap: anywhere; font-size: 15px; font-weight: 850; color: var(--trn-slate-900); text-decoration: none; line-height: 1.25; }
    .trn-name:hover { color: var(--trn-primary); }
    .trn-desc { font-size: 12.5px; font-weight: 550; color: var(--trn-slate-500); line-height: 1.5; margin-top: 4px; max-width: 620px; }
    .trn-badges { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 9px; }

    .trn-mode {
        font-size: 10px; font-weight: 850; text-transform: uppercase; letter-spacing: 0.04em;
        border-radius: 999px; padding: 3px 9px; border: 1px solid; white-space: nowrap;
    }
    .trn-mode.lms    { background: #eef2ff; color: var(--trn-indigo); border-color: #c7d2fe; }
    .trn-mode.simple { background: var(--trn-primary-soft); color: var(--trn-primary); border-color: #bfdbfe; }

    .trn-chip {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11.5px; font-weight: 800; color: var(--trn-slate-600);
        background: #f8fafc; border: 1px solid var(--trn-border); border-radius: 8px; padding: 5px 10px;
    }
    .trn-chip i { color: var(--trn-slate-400); }

    /* Right meta block — side metric cells (NOT the same as top strip .trn-stat) */
    .trn-side { display: flex; align-items: center; gap: 28px; flex-shrink: 0; }
    .trn-side-stat { text-align: center; min-width: 64px; }
    .trn-side-num { font-size: 19px; font-weight: 900; color: var(--trn-slate-900); line-height: 1; }
    .trn-side-label {
        font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--trn-slate-400); margin-top: 5px;
    }

    /* Optional participant gender metadata */
    .trn-chip.gender-m i { color: var(--trn-primary); }
    .trn-chip.gender-f i { color: var(--trn-pink); }

    .trn-open {
        display: inline-flex; align-items: center; gap: 6px;
        border: 1px solid #bfdbfe; background: #fff; color: var(--trn-primary);
        border-radius: 10px; padding: 9px 16px; font-size: 13px; font-weight: 850;
        text-decoration: none; transition: 0.15s ease; white-space: nowrap;
    }
    .trn-open:hover { background: var(--trn-primary-soft); color: var(--trn-primary-dark); transform: translateY(-1px); box-shadow: 0 6px 14px rgba(37, 99, 235, 0.1); }

    .trn-empty { padding: 46px 20px; text-align: center; color: var(--trn-slate-500); font-size: 13.5px; font-weight: 650; }
    .trn-empty i { font-size: 34px; color: var(--trn-slate-400); display: block; margin-bottom: 10px; }
    .trn-empty-title { font-size: 15px; font-weight: 850; color: var(--trn-slate-900); margin-bottom: 3px; }

    .trn-pager {
        display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 18px;
        min-height: 52px; padding: 10px 20px; border-top: 1px solid #f1f5f9; background: #fff;
    }
    .trn-pager[hidden] { display: none; }
    .trn-page-info { color: var(--trn-slate-500); font-size: 12px; font-weight: 700; }
    .trn-page-controls { display: flex; align-items: center; gap: 6px; }
    .trn-page-controls[hidden] { display: none; }
    .trn-page-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 5px;
        min-height: 36px; padding: 7px 12px; border: 1px solid var(--trn-border); border-radius: 9px;
        background: #fff; color: var(--trn-slate-600); cursor: pointer; font-family: inherit;
        font-size: 12px; font-weight: 800; transition: .15s ease;
    }
    .trn-page-btn:hover:not(:disabled) { border-color: #bfdbfe; background: var(--trn-primary-soft); color: var(--trn-primary); }
    .trn-page-btn:disabled { cursor: not-allowed; opacity: .45; }
    .trn-page-btn:focus-visible { outline: 3px solid rgba(37, 99, 235, .3); outline-offset: 2px; }
    .trn-page-current {
        min-width: 62px; padding: 7px 9px; border-radius: 9px; background: var(--trn-primary-soft);
        color: var(--trn-primary); font-size: 12px; font-weight: 850; text-align: center;
    }

    .hidden-by-filter { display: none !important; }

    @media (max-width: 991px) {
        .trn-card { flex-wrap: wrap; }
        .trn-marker { order: 0; }
        .trn-body { order: 1; flex-basis: calc(100% - 62px); }
        .trn-side { order: 2; width: 100%; justify-content: flex-start; padding-left: 62px; gap: 24px; }
        .trn-side-stat { text-align: left; }
    }

    @media (max-width: 768px) {
        .trn-title { font-size: 21px; }
        .trn-card { align-items: flex-start; padding-right: 16px; padding-left: 16px; }
        .trn-side { padding-left: 0; flex-wrap: wrap; }
        .trn-open { width: 100%; justify-content: center; }
        .trn-pager { grid-template-columns: 1fr; gap: 12px; padding-right: 16px; padding-left: 16px; }
        .trn-page-info { text-align: center; }
        .trn-page-controls { justify-content: center; }
    }

    @media (max-width: 479.98px) {
        .trn-filter-bar { padding-right: 16px; padding-left: 16px; }
        .trn-panel-head { align-items: flex-start; padding-right: 16px; padding-left: 16px; }
        .trn-page-controls { display: grid; grid-template-columns: 1fr auto 1fr; }
    }
</style>
@endpush

@section('content')

<main class="trn-wrap app-page-shell">

    <div class="trn-head">
        <h1 class="trn-title">My Training</h1>
        <p class="trn-subtitle">Open a training workspace to manage materials, sessions, participants, and assessments.</p>
    </div>

    {{-- Stats strip — same Bootstrap row + col-md-3 layout as dashboard / proposals stat row.
         Gives the page the visual L/R anchor that both other top-level pages have. --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="trn-stat">
                <div class="trn-stat-icon trn-ic-blue"><i class="bi bi-journal-bookmark"></i></div>
                <div class="trn-stat-label-top">Total Trainings</div>
                <h3 class="trn-stat-value-top">{{ $totalCount }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="trn-stat">
                <div class="trn-stat-icon trn-ic-green"><i class="bi bi-check-circle"></i></div>
                <div class="trn-stat-label-top">Active</div>
                <h3 class="trn-stat-value-top">{{ $activeCount }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="trn-stat">
                <div class="trn-stat-icon trn-ic-indigo"><i class="bi bi-grid-3x3-gap"></i></div>
                <div class="trn-stat-label-top">LMS Mode</div>
                <h3 class="trn-stat-value-top">{{ $lmsCount }}</h3>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="trn-stat">
                <div class="trn-stat-icon trn-ic-cyan"><i class="bi bi-easel"></i></div>
                <div class="trn-stat-label-top">Simple Mode</div>
                <h3 class="trn-stat-value-top">{{ $simpleCount }}</h3>
            </div>
        </div>
    </div>

    <div class="trn-panel">
        <div class="trn-panel-head">
            <div>
                <h2 class="trn-panel-title">Training Workspaces</h2>
                <div class="trn-panel-sub">Open a workspace to manage its materials, sessions, and participants.</div>
            </div>
            @if ($courses->count() > 0)
                <span class="trn-count" id="workspaceCount" aria-live="polite">{{ $courses->count() }} training(s)</span>
            @endif
        </div>

        @if ($courses->count() > 0)
            <div class="trn-filter-bar">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5 col-md-12">
                        <div class="trn-search">
                            <i class="bi bi-search"></i>
                            <input type="text" id="courseSearch" aria-label="Search trainings" placeholder="Search training title or category...">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-4">
                        <select id="categoryFilter" class="trn-select" aria-label="Filter by category">
                            <option value="">All Categories</option>
                            @foreach ($courses->pluck('course_category')->filter()->unique() as $category)
                                <option value="{{ strtolower($category) }}">{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select id="modeFilter" class="trn-select" aria-label="Filter by training mode">
                            <option value="">All Modes</option>
                            <option value="lms">LMS</option>
                            <option value="standard">Simple</option>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4">
                        <select id="statusFilter" class="trn-select" aria-label="Filter by status">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
            </div>
        @endif

        @if ($courses->count() > 0)
            <div id="workspaceList">
                @foreach ($courses as $course)
                    @php
                        $isLms = (($course->training_mode ?? 'standard') === 'lms');
                        $male = (int) $course->male_count;
                        $female = (int) $course->female_count;
                        $total = (int) $course->total_participants;
                        $cap = (int) ($course->capacity ?? 0);
                        $openUrl = $openUrlFor($course);
                    @endphp

                    <div class="trn-card course-item"
                         data-name="{{ strtolower(($course->course_name ?? '') . ' ' . ($course->course_category ?? '')) }}"
                         data-category="{{ strtolower($course->course_category ?? '') }}"
                         data-status="{{ strtolower($course->status ?? '') }}"
                         data-mode="{{ strtolower($course->training_mode ?? 'standard') }}">

                        <div class="trn-marker"><i class="bi bi-journal-text"></i></div>

                        <div class="trn-body">
                            <div class="trn-title-row">
                                <a href="{{ $openUrl }}" class="trn-name">{{ $course->course_name }}</a>
                                <span class="trn-mode {{ $isLms ? 'lms' : 'simple' }}">{{ $isLms ? 'LMS' : 'Simple' }}</span>
                            </div>
                            @if (!empty($course->description))
                                <div class="trn-desc">{{ \Illuminate\Support\Str::limit($course->description, 110) }}</div>
                            @endif
                            @if ($male > 0 || $female > 0)
                                <div class="trn-badges">
                                    @if ($male > 0)
                                        <span class="trn-chip gender-m"><i class="bi bi-gender-male"></i> {{ $male }} Male</span>
                                    @endif
                                    @if ($female > 0)
                                        <span class="trn-chip gender-f"><i class="bi bi-gender-female"></i> {{ $female }} Female</span>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="trn-side">
                            <div class="trn-side-stat">
                                <div class="trn-side-num">{{ $total }}@if ($cap > 0)<span style="font-size:12px;color:#94a3b8;font-weight:800;"> / {{ $cap }}</span>@endif</div>
                                <div class="trn-side-label">Enrolled</div>
                            </div>
                            <div class="trn-side-stat">
                                <div class="trn-side-num">{{ $course->total_sessions }}</div>
                                <div class="trn-side-label">Sessions</div>
                            </div>
                            <a href="{{ $openUrl }}" class="trn-open">Open <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="trn-empty" id="noResults" style="display:none;">
                <i class="bi bi-search"></i>
                <div class="trn-empty-title">No training matches your filters</div>
                <div>Try a different keyword, category, mode, or status.</div>
            </div>

            <nav class="trn-pager" id="workspacePager" aria-label="Training pagination">
                <span class="trn-page-info" id="workspacePageRange" aria-live="polite">Showing 1–{{ min(5, $courses->count()) }} of {{ $courses->count() }} trainings · Page 1 of {{ (int) ceil($courses->count() / 5) }}</span>
                <div class="trn-page-controls" id="workspacePageControls" @if ($courses->count() <= 5) hidden @endif>
                    <button type="button" class="trn-page-btn" id="workspacePrevious" aria-label="Previous training page">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>Previous
                    </button>
                    <span class="trn-page-current" id="workspaceCurrentPage" aria-current="page" aria-live="polite">1 / {{ (int) ceil($courses->count() / 5) }}</span>
                    <button type="button" class="trn-page-btn" id="workspaceNext" aria-label="Next training page">
                        Next<i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </nav>
        @else
            <div class="trn-empty">
                <i class="bi bi-journal-x"></i>
                <div class="trn-empty-title">No training assigned yet</div>
                <div>Once you're assigned to a training session, it will appear here.</div>
            </div>
        @endif
    </div>

</main>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('courseSearch');
        const categoryFilter = document.getElementById('categoryFilter');
        const modeFilter = document.getElementById('modeFilter');
        const statusFilter = document.getElementById('statusFilter');
        const items = Array.from(document.querySelectorAll('.course-item'));
        const noResults = document.getElementById('noResults');
        const count = document.getElementById('workspaceCount');
        const pager = document.getElementById('workspacePager');
        const pageRange = document.getElementById('workspacePageRange');
        const pageControls = document.getElementById('workspacePageControls');
        const currentPageLabel = document.getElementById('workspaceCurrentPage');
        const previousButton = document.getElementById('workspacePrevious');
        const nextButton = document.getElementById('workspaceNext');
        const pageSize = 5;
        let currentPage = 1;

        if (items.length === 0) return;

        function matchingItems() {
            const search = (searchInput.value || '').toLowerCase();
            const category = (categoryFilter.value || '').toLowerCase();
            const mode = (modeFilter.value || '').toLowerCase();
            const status = (statusFilter.value || '').toLowerCase();

            return items.filter(function (item) {
                const matchSearch = (item.dataset.name || '').includes(search);
                const matchCategory = category === '' || item.dataset.category === category;
                const matchMode = mode === '' || item.dataset.mode === mode;
                const matchStatus = status === '' || item.dataset.status === status;

                return matchSearch && matchCategory && matchMode && matchStatus;
            });
        }

        function render(resetPage) {
            const matches = matchingItems();
            const total = matches.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));

            if (resetPage) currentPage = 1;
            currentPage = Math.min(Math.max(currentPage, 1), totalPages);

            const start = (currentPage - 1) * pageSize;
            const pageItems = matches.slice(start, start + pageSize);

            items.forEach(function (item) { item.classList.add('hidden-by-filter'); });
            pageItems.forEach(function (item) { item.classList.remove('hidden-by-filter'); });

            if (count) count.textContent = total + ' training(s)';
            if (noResults) noResults.style.display = total === 0 ? '' : 'none';

            if (pager) pager.hidden = total === 0;
            if (pageControls) pageControls.hidden = total <= pageSize;
            if (pageRange) {
                pageRange.textContent = total === 0
                    ? ''
                    : 'Showing ' + (start + 1) + '\u2013' + Math.min(start + pageSize, total) + ' of ' + total + ' trainings \u00b7 Page ' + currentPage + ' of ' + totalPages;
            }
            if (currentPageLabel) currentPageLabel.textContent = currentPage + ' / ' + totalPages;
            if (previousButton) previousButton.disabled = currentPage === 1;
            if (nextButton) nextButton.disabled = currentPage === totalPages;
        }

        function resetAndRender() {
            render(true);
        }

        searchInput.addEventListener('input', resetAndRender);
        categoryFilter.addEventListener('change', resetAndRender);
        modeFilter.addEventListener('change', resetAndRender);
        statusFilter.addEventListener('change', resetAndRender);
        previousButton.addEventListener('click', function () {
            if (currentPage > 1) currentPage--;
            render(false);
        });
        nextButton.addEventListener('click', function () {
            const totalPages = Math.max(1, Math.ceil(matchingItems().length / pageSize));
            if (currentPage < totalPages) currentPage++;
            render(false);
        });

        render(false);
    });
</script>
@endpush
