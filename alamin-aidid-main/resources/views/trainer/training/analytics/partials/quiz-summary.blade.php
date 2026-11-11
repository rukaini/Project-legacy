<div class="ana-quiz-summary">
    @foreach ($quizSummaryCards as $card)
        <article class="ana-metric ana-quiz-metric">
            <div class="ana-metric-label">{{ $card['label'] }}</div>
            <div class="ana-metric-value {{ !empty($card['title']) ? 'is-title' : '' }}">
                {{ $card['value'] }}
                @if (!empty($card['detail']))
                    <span class="ana-quiz-metric-detail">{{ $card['detail'] }}</span>
                @endif
            </div>
        </article>
    @endforeach
</div>
