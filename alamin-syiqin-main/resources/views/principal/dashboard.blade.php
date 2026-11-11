<x-app-layout>
    <div class="app-page-shell">

        {{-- PAGE HEADING --}}
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 mb-7">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.7"
                        stroke="currentColor"
                        class="w-6 h-6 text-blue-600">
                        <path stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M8.25 7.5h.008v.008H8.25V7.5zm0 3.75h.008v.008H8.25v-.008zm0 3.75h.008v.008H8.25V15zm3.75-7.5h.008v.008H12V7.5zm0 3.75h.008v.008H12v-.008zM12 15h.008v.008H12V15zm3.75-7.5h.008v.008h-.008V7.5zm0 3.75h.008v.008h-.008v-.008zm0 3.75h.008v.008h-.008V15z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Dashboard
                    </h1>
                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Welcome back, {{ $principal->principalName }}.
                        Monitor evaluation performance for {{ $school->schoolName ?? $school->school_name ?? 'your school' }}.
                    </p>
                </div>
            </div>
        </div>


        {{-- SUMMARY CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 mb-7">

            {{-- EVALUATION COMPLETED --}}
            <div class="app-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    Evaluation
                </p>
                <p class="text-[15px] font-bold text-slate-800 mt-2">
                    Completed
                </p>
                <p class="text-[30px] font-extrabold text-emerald-600 mt-2">
                    {{ $summary['completed'] }}
                </p>
                <p class="text-xs text-slate-500 mt-2">
                    Evaluation completed
                </p>
            </div>

            {{-- EVALUATION REPEAT --}}
            <div class="app-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    Evaluation
                </p>
                <p class="text-[15px] font-bold text-slate-800 mt-2">
                    Repeat Required
                </p>
                <p class="text-[30px] font-extrabold text-red-600 mt-2">
                    {{ $summary['repeat_required'] }}
                </p>
                <p class="text-xs text-slate-500 mt-2">
                    Requires another evaluation
                </p>
            </div>

            {{-- TRAINING IN PROGRESS --}}
            <div class="app-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    Training
                </p>
                <p class="text-[15px] font-bold text-slate-800 mt-2">
                    In Progress
                </p>
                <p class="text-[30px] font-extrabold text-blue-600 mt-2">
                    {{ $trainingSummary['in_progress'] }}
                </p>
                <p class="text-xs text-slate-500 mt-2">
                    Training underway
                </p>
            </div>

            {{-- TRAINING COMPLETED --}}
            <div class="app-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    Training
                </p>
                <p class="text-[15px] font-bold text-slate-800 mt-2">
                    Completed
                </p>
                <p class="text-[30px] font-extrabold text-emerald-600 mt-2">
                    {{ $trainingSummary['completed'] }}
                </p>
                <p class="text-xs text-slate-500 mt-2">
                    Training completed
                </p>
            </div>

            {{-- FEEDBACK COMPLETED --}}
            <div class="app-card p-5">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">
                    Feedback
                </p>
                <p class="text-[15px] font-bold text-slate-800 mt-2">
                    Completed
                </p>
                <p class="text-[30px] font-extrabold text-violet-600 mt-2">
                    {{ $trainingSummary['feedback_completed'] }}
                </p>
                <p class="text-xs text-slate-500 mt-2">
                    Feedback submitted
                </p>
            </div>

        </div>




        {{-- TRAINING PROGRESS LIST --}}
        <div class="app-section-card mb-7">

            <div class="px-5 md:px-6 py-5 border-b border-slate-100
                flex flex-col sm:flex-row sm:items-center
                sm:justify-between gap-4">

                <div>
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Training Progress List
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Latest completed training and feedback of new teachers.
                    </p>
                </div>

                <a href="{{ route('principal.training.progress') }}"
                    class="app-button-outline">
                    View Training Progress
                </a>

            </div>

            <div class="app-table-wrap">
                <table class="app-table">

                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Teacher Name</th>
                            <th>Training Progress</th>
                            <th>Feedback Progress</th>
                            <th class="text-center">Overall Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($latestTrainingTeachers as $teacher)
                        <tr>

                            {{-- NO --}}
                            <td class="text-center text-slate-500">
                                {{ $loop->iteration }}
                            </td>

                            {{-- TEACHER --}}
                            <td>
                                <div class="flex items-center gap-3">

                                    <div class="w-9 h-9 rounded-full bg-blue-50
                                            border border-blue-200
                                            flex items-center justify-center shrink-0">

                                        <span class="text-[11px] font-bold text-blue-600">
                                            {{ strtoupper(substr($teacher->gn_name, 0, 1)) }}
                                        </span>
                                    </div>

                                    <div>
                                        <p class="font-bold text-slate-800">
                                            {{ $teacher->gn_name }}
                                        </p>

                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            {{ $teacher->gn_id }}
                                        </p>
                                    </div>

                                </div>
                            </td>

                            {{-- TRAINING PROGRESS --}}
                            <td>
                                <div class="min-w-[150px]">

                                    <div class="flex items-center justify-between
                                            text-xs mb-2">

                                        <span class="font-bold text-slate-700">
                                            {{ $teacher->completed_training }}/{{ $teacher->total_training }}
                                        </span>

                                        <span class="font-semibold text-blue-600">
                                            {{ $teacher->training_percentage }}%
                                        </span>
                                    </div>

                                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-blue-600 rounded-full"
                                            @style(['width: ' . $teacher->training_percentage . ' %'])
                                            </div>
                                        </div>

                                    </div>
                            </td>

                            {{-- FEEDBACK PROGRESS --}}
                            <td>
                                <div class="min-w-[150px]">

                                    <div class="flex items-center justify-between
                                            text-xs mb-2">

                                        <span class="font-bold text-slate-700">
                                            {{ $teacher->completed_feedback }}/{{ $teacher->total_feedback }}
                                        </span>

                                        <span class="font-semibold text-blue-600">
                                            {{ $teacher->feedback_percentage }}%
                                        </span>
                                    </div>

                                    <div class="w-full h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div
                                            class="h-full bg-blue-600 rounded-full"
                                            @style(['width: ' . $teacher->feedback_percentage . ' %'])
                                            </div>
                                        </div>

                                    </div>
                            </td>

                            {{-- OVERALL STATUS --}}
                            <td class="text-center">

                                @if($teacher->overall_training_status === 'Completed')

                                <span class="app-badge app-badge-success">
                                    Completed
                                </span>

                                @elseif($teacher->overall_training_status === 'In Progress')

                                <span class="app-badge app-badge-info">
                                    In Progress
                                </span>

                                @else

                                <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                    Not Started
                                </span>

                                @endif

                            </td>

                        </tr>

                        @empty
                        <tr>
                            <td colspan="5" class="!py-14 text-center">

                                <p class="font-semibold text-slate-700">
                                    No completed training records found
                                </p>

                                <p class="text-[12px] text-slate-400 mt-1">
                                    No teachers have completed both training and feedback yet.
                                </p>

                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>



        {{-- PERFORMANCE FILTER --}}
        <div class="app-section-card mb-7">
            <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                <h2 class="text-[17px] font-extrabold text-slate-900">
                    Performance Filter
                </h2>
                <p class="text-[12.5px] text-slate-500 mt-1">
                    Filter PRE and PDPC performance by teacher, month, year or status.
                </p>
            </div>

            <div class="p-5 md:p-6 bg-slate-50/40">
                <form method="GET" action="{{ route('principal.dashboard') }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-12 gap-4 items-end">

                        {{-- TEACHER --}}
                        <div class="xl:col-span-4">
                            <label class="app-label">Teacher</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z" />
                                </svg>
                                <input type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Search teacher..."
                                    class="app-input !pl-9">
                            </div>
                        </div>

                        {{-- MONTH --}}
                        <div class="xl:col-span-2">
                            <label class="app-label">Month</label>
                            <select name="month" class="app-select">
                                <option value="all">All Months</option>
                                @foreach([
                                1 => 'January',
                                2 => 'February',
                                3 => 'March',
                                4 => 'April',
                                5 => 'May',
                                6 => 'June',
                                7 => 'July',
                                8 => 'August',
                                9 => 'September',
                                10 => 'October',
                                11 => 'November',
                                12 => 'December'
                                ] as $monthNumber => $monthName)
                                <option value="{{ $monthNumber }}"
                                    {{ (string) $month === (string) $monthNumber ? 'selected' : '' }}>
                                    {{ $monthName }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- YEAR --}}
                        <div class="xl:col-span-2">
                            <label class="app-label">Year</label>
                            <select name="year" class="app-select">
                                <option value="all">All Years</option>
                                @foreach($years as $availableYear)
                                <option value="{{ $availableYear }}"
                                    {{ (string) $year === (string) $availableYear ? 'selected' : '' }}>
                                    {{ $availableYear }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- STATUS --}}
                        <div class="xl:col-span-2">
                            <label class="app-label">Status</label>
                            <select name="status" class="app-select">
                                <option value="all" {{ $status === 'all' ? 'selected' : '' }}>
                                    All Status
                                </option>
                                <option value="Completed" {{ $status === 'Completed' ? 'selected' : '' }}>
                                    Completed
                                </option>
                                <option value="In Progress" {{ $status === 'In Progress' ? 'selected' : '' }}>
                                    In Progress
                                </option>
                                <option value="Repeat Required" {{ $status === 'Repeat Required' ? 'selected' : '' }}>
                                    Repeat Required
                                </option>
                                <option value="Pending" {{ $status === 'Pending' ? 'selected' : '' }}>
                                    Pending
                                </option>
                            </select>
                        </div>

                        {{-- BUTTONS --}}
                        <div class="xl:col-span-2 flex gap-2">
                            <button type="submit" class="app-button-primary flex-1">
                                Filter
                            </button>
                            <a href="{{ route('principal.dashboard') }}" class="app-button-outline">
                                Reset
                            </a>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        {{-- CHARTS --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-7">

            {{-- PRE PERFORMANCE --}}
            <div class="app-section-card">
                <div class="px-5 md:px-6 py-5 border-b border-slate-100">
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        PRE Performance
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Number of teachers based on PRE achievement level.
                    </p>
                </div>

                <div class="p-5 md:p-6">
                    @if($prePerformance['total'] > 0)
                    <div class="h-[300px]">
                        <canvas id="prePerformanceChart"></canvas>
                    </div>
                    @else
                    <div class="h-[300px] flex flex-col items-center justify-center text-center">
                        <p class="font-semibold text-slate-700">
                            No PRE performance data
                        </p>
                        <p class="text-[12px] text-slate-400 mt-1">
                            No submitted PRE results match the selected filters.
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- PDPC PERFORMANCE --}}
            <div class="app-section-card">
                <div class="px-5 md:px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-[17px] font-extrabold text-slate-900">
                            PDPC Performance Trend
                        </h2>
                        <p class="text-[12.5px] text-slate-500 mt-1">
                            Average External and Post PDPC performance by month.
                        </p>
                    </div>
                    <span class="app-badge app-badge-info">
                        {{ $chartYear }}
                    </span>
                </div>

                <div class="p-5 md:p-6">
                    @if($pdpcTrendHasData)
                    <div class="h-[300px]">
                        <canvas id="pdpcTrendChart"></canvas>
                    </div>
                    @else
                    <div class="h-[300px] flex flex-col items-center justify-center text-center">
                        <p class="font-semibold text-slate-700">
                            No PDPC performance data
                        </p>
                        <p class="text-[12px] text-slate-400 mt-1">
                            No submitted PDPC results match the selected filters.
                        </p>
                    </div>
                    @endif
                </div>
            </div>

        </div>

        {{-- EVALUATION REPORT --}}
        <div class="app-section-card">
            <div class="px-5 md:px-6 py-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Evaluation Report
                    </h2>
                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Current evaluation progress of active new teachers.
                    </p>
                </div>

                <a href="{{ route('principal.result') }}" class="app-button-outline">
                    View Results
                </a>
            </div>

            <div class="app-table-wrap">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th class="text-center">No.</th>
                            <th>Teacher</th>
                            <th class="text-center">PRE</th>
                            <th class="text-center">External</th>
                            <th class="text-center">Post</th>
                            <th class="text-center">Overall Status</th>
                            <th class="text-center">Last Evaluation</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($reportTeachers as $teacher)
                        <tr>
                            <td class="text-center text-slate-500">
                                {{ $reportTeachers->firstItem() + $loop->index }}
                            </td>

                            {{-- TEACHER --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center shrink-0">
                                        <span class="text-[11px] font-bold text-blue-600">
                                            {{ strtoupper(substr($teacher->gn_name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800">
                                            {{ $teacher->gn_name }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5">
                                            New Teacher
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- PRE --}}
                            <td class="text-center">
                                <span class="font-extrabold text-slate-800">
                                    {{ $teacher->pre_completed }}/{{ $teacher->pre_total }}
                                </span>
                            </td>

                            {{-- EXTERNAL --}}
                            <td class="text-center">
                                <span class="font-extrabold text-slate-800">
                                    {{ $teacher->external_completed }}/{{ $teacher->external_total }}
                                </span>
                            </td>

                            {{-- POST --}}
                            <td class="text-center">
                                <span class="font-extrabold text-slate-800">
                                    {{ $teacher->post_completed }}/{{ $teacher->post_total }}
                                </span>
                            </td>

                            {{-- OVERALL STATUS --}}
                            <td class="text-center">
                                @if($teacher->evaluation_status === 'Completed')
                                <span class="app-badge app-badge-success">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Completed
                                </span>

                                @elseif($teacher->evaluation_status === 'Repeat Required')
                                <span class="app-badge app-badge-danger">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Repeat Required
                                </span>

                                @elseif($teacher->evaluation_status === 'In Progress')
                                <span class="app-badge app-badge-info">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                    In Progress
                                </span>

                                @else
                                <span class="app-badge border-slate-200 bg-slate-50 text-slate-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Pending
                                </span>
                                @endif
                            </td>

                            {{-- LAST EVALUATION --}}
                            <td class="text-center whitespace-nowrap">
                                {{ $teacher->last_evaluation_date
                                        ? \Carbon\Carbon::parse($teacher->last_evaluation_date)->format('d/m/Y')
                                        : '-' }}
                            </td>

                            {{-- ACTION --}}
                            <td class="text-center">
                                <a href="{{ route('principal.result.show', $teacher->gn_id) }}"
                                    class="app-button-outline">
                                    View
                                </a>
                            </td>
                        </tr>

                        @empty
                        <tr>
                            <td colspan="8" class="!py-14 text-center">
                                <p class="font-semibold text-slate-700">
                                    No evaluation records found
                                </p>
                                <p class="text-[12px] text-slate-400 mt-1">
                                    No active new teachers are currently available.
                                </p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            @if($reportTeachers->hasPages())
            <div class="px-5 md:px-6 py-4 border-t border-slate-100">
                {{ $reportTeachers->links() }}
            </div>
            @endif
        </div>

    </div>

    {{-- CHART DATA --}}
    <div id="principalChartData"
        data-pre-labels="{{ json_encode($prePerformance['labels']) }}"
        data-pre-values="{{ json_encode($prePerformance['values']) }}"
        data-pdpc-labels="{{ json_encode($pdpcTrend['labels']) }}"
        data-pdpc-external="{{ json_encode($pdpcTrend['external']) }}"
        data-pdpc-post="{{ json_encode($pdpcTrend['post']) }}"
        data-pdpc-external-count="{{ json_encode($pdpcTrend['external_count']) }}"
        data-pdpc-post-count="{{ json_encode($pdpcTrend['post_count']) }}"
        class="hidden">
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const chartData = document.getElementById('principalChartData');

        const preLabels = JSON.parse(chartData.dataset.preLabels);
        const preValues = JSON.parse(chartData.dataset.preValues);

        const pdpcLabels = JSON.parse(chartData.dataset.pdpcLabels);
        const pdpcExternal = JSON.parse(chartData.dataset.pdpcExternal);
        const pdpcPost = JSON.parse(chartData.dataset.pdpcPost);

        const pdpcExternalCount = JSON.parse(chartData.dataset.pdpcExternalCount);
        const pdpcPostCount = JSON.parse(chartData.dataset.pdpcPostCount);

        Chart.defaults.color = '#475569';
        Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';

        // =====================================================
        // PRE PERFORMANCE CHART
        // =====================================================
        const preCanvas = document.getElementById('prePerformanceChart');

        if (preCanvas) {
            new Chart(preCanvas, {
                type: 'bar',
                data: {
                    labels: preLabels,
                    datasets: [{
                        label: 'Teachers',
                        data: preValues,
                        backgroundColor: '#2563eb',
                        borderColor: '#1d4ed8',
                        borderWidth: 1,
                        borderRadius: 6,
                        barThickness: 28,
                        maxBarThickness: 28,
                        categoryPercentage: 0.55,
                        barPercentage: 0.65
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                stepSize: 1
                            },
                            title: {
                                display: true,
                                text: 'Number of Teachers'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 0,
                                minRotation: 0,
                                callback: function(value) {
                                    const label = this.getLabelForValue(value);
                                    const match = label.match(/^(.*?)\s*(\([^)]*\))$/);
                                    return match ? [match[1], match[2]] : label;
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: context => {
                                    const value = Number(context.raw);
                                    return `${value} teacher${value === 1 ? '' : 's'}`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // =====================================================
        // PDPC ACHIEVEMENT LEVEL
        // =====================================================
        function getPdpcAchievementLevel(percentage) {
            percentage = Number(percentage);

            if (!Number.isFinite(percentage)) {
                return 'Unknown';
            }

            if (percentage >= 90) return 'Excellent (90-100%)';
            if (percentage >= 80) return 'Very Good (80-89%)';
            if (percentage >= 60) return 'Good (60-79%)';
            if (percentage >= 40) return 'Satisfactory (40-59%)';

            return 'Weak (0-39%)';
        }

        // =====================================================
        // PDPC PERFORMANCE TREND
        // =====================================================
        const pdpcCanvas = document.getElementById('pdpcTrendChart');

        if (pdpcCanvas) {
            new Chart(pdpcCanvas, {
                type: 'line',
                data: {
                    labels: pdpcLabels,
                    datasets: [{
                            label: 'External',
                            data: pdpcExternal,
                            borderColor: '#2563eb',
                            backgroundColor: '#2563eb',
                            pointBackgroundColor: '#2563eb',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.35,
                            fill: false,
                            spanGaps: true
                        },
                        {
                            label: 'Post',
                            data: pdpcPost,
                            borderColor: '#f97316',
                            backgroundColor: '#f97316',
                            pointBackgroundColor: '#f97316',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            tension: 0.35,
                            fill: false,
                            spanGaps: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            min: 0,
                            max: 100,
                            ticks: {
                                stepSize: 20,
                                callback: value => value + '%'
                            },
                            title: {
                                display: true,
                                text: 'Average Score'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 18
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: context => {
                                    if (context.raw === null ||
                                        context.raw === undefined) {
                                        return `${context.dataset.label}: No data`;
                                    }

                                    const index = context.dataIndex;
                                    const percentage = Number(context.raw);

                                    const teacherCount =
                                        context.dataset.label === 'External' ?
                                        pdpcExternalCount[index] :
                                        pdpcPostCount[index];

                                    return `${context.dataset.label}: ${percentage.toFixed(2)}% — ${getPdpcAchievementLevel(percentage)} (${teacherCount} teacher${teacherCount === 1 ? '' : 's'})`;
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>

</x-app-layout>