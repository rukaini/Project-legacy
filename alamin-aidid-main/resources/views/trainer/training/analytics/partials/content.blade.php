<div class="ana-shell"><main class="ana-page app-page-shell">
    <header class="ana-head"><div class="ana-head-left"><h1 class="ana-title">Training Analytics</h1><p class="ana-subtitle">Review attendance and assessment results for your assigned training.</p></div></header>
    <div id="analyticsDynamicRegion" class="ana-dynamic-region" aria-live="polite">
        @include('trainer.training.analytics.partials.header-filters')
        @if (!$selectedTraining)
            <section class="ana-empty-state" aria-labelledby="analytics-empty-title"><div class="ana-empty-icon"><i class="bi bi-calendar2-x" aria-hidden="true"></i></div><div><h2 id="analytics-empty-title">No training assigned yet</h2><p>Your analytics report will appear here when a training is assigned to you.</p></div></section>
        @else
            @include('trainer.training.analytics.partials.metrics')
            @include('trainer.training.analytics.partials.report-panels')
        @endif
    </div>
</main></div>
