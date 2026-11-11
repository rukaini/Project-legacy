<main class="adm-wrap adm-wrap--wide app-page-shell admin-analytics-shell" data-admin-page-shell data-admin-width="wide">
    <header class="adm-head">
        <div class="adm-head-row">
            <div class="adm-head-icon"><i class="bi bi-bar-chart-line" aria-hidden="true"></i></div>
            <div>
                <h1 class="adm-title">Analytics</h1>
                <p class="adm-subtitle">Organization-wide training performance and reporting.</p>
            </div>
        </div>
    </header>

    <div id="adminAnalyticsDynamicRegion" class="ana-dynamic-region" aria-live="polite">
        @include('admin.analytics.partials.filters')

        @if ($selectedTraining)
            @include('admin.analytics.partials.training-detail')
        @else
            @include('admin.analytics.partials.organization-overview')
        @endif
    </div>
</main>
