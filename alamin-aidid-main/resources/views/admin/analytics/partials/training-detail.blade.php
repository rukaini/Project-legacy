<section class="ana-metrics" aria-label="Selected training analytics summary">
    @foreach ($headlineMetrics as $metric)
        <article class="ana-metric ana-tone-{{ $metric['tone'] }}" data-admin-analytics-metric>
            <div class="ana-metric-icon"><i class="bi {{ $metric['icon'] }}" aria-hidden="true"></i></div>
            <div class="ana-metric-copy">
                <div class="ana-metric-label">{{ $metric['label'] }}</div>
                <div class="ana-metric-value">{{ $metric['value'] }}</div>
                <div class="ana-metric-note">{{ $metric['note'] }}</div>
            </div>
        </article>
    @endforeach
</section>

@php($analyticsFilterFormId = 'adminAnalyticsFilterForm')
@php($isAdminAnalyticsReport = true)
@include('trainer.training.analytics.partials.report-panels')
