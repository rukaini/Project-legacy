@if ($trainingResultCount > 0)
    <div id="feedbackWorkspace"
         class="fb-workspace"
         data-selected-course="{{ $filters['course_id'] }}"
         data-selected-question="{{ $filters['question_id'] }}">
        <aside id="feedbackTrainingNavigator" class="fb-training-navigator" aria-labelledby="feedbackNavigatorTitle">
            <div class="fb-navigator-collapsed">
                <button type="button"
                        class="fb-navigator-expand"
                        data-feedback-training-list-toggle
                        aria-label="Expand training list"
                        title="Expand training list"
                        aria-controls="feedbackTrainingNavigatorContent"
                        aria-expanded="false">
                    <i class="bi bi-list-ul" aria-hidden="true"></i>
                </button>
            </div>
            <div id="feedbackTrainingNavigatorContent" class="fb-navigator-expanded">
                <div class="ana-panel-head">
                    <div><h2 id="feedbackNavigatorTitle">Feedback by Training</h2><p>{{ $trainingResultCount }} {{ \Illuminate\Support\Str::plural('training', $trainingResultCount) }} with feedback</p></div>
                    <button type="button"
                            class="fb-navigator-collapse"
                            data-feedback-training-list-toggle
                            aria-label="Collapse training list"
                            title="Collapse training list"
                            aria-controls="feedbackTrainingNavigatorContent"
                            aria-expanded="true">
                        <i class="bi bi-layout-sidebar-inset" aria-hidden="true"></i>
                    </button>
                </div>
                @if ($filters['search'] !== '')
                    <div class="fb-search-state"><i class="bi bi-search" aria-hidden="true"></i><span>Comments matching “{{ $filters['search'] }}”</span></div>
                @endif
                <div class="fb-training-list" role="listbox" aria-label="Trainings with feedback">
                    @foreach ($trainingResults as $training)
                        @php($isSelected = $filters['course_id'] === $training['course_id'])
                        <button type="button"
                                class="fb-training-row {{ $isSelected ? 'is-selected' : '' }}"
                                data-feedback-select-training="{{ $training['course_id'] }}"
                                role="option"
                                aria-selected="{{ $isSelected ? 'true' : 'false' }}">
                            <span class="fb-training-row-copy"><strong>{{ $training['course_name'] }}</strong></span>
                            <span class="fb-training-row-meta">
                                <small>{{ $training['respondent_count'] }} {{ \Illuminate\Support\Str::plural('respondent', $training['respondent_count']) }}</small>
                                <strong aria-label="{{ $training['avg_rating'] !== null ? number_format($training['avg_rating'], 2).' average rating out of 5' : 'No average rating' }}">{{ $training['avg_rating'] !== null ? number_format($training['avg_rating'], 2) : '—' }} @if ($training['avg_rating'] !== null)<i class="bi bi-star-fill" aria-hidden="true"></i>@endif</strong>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </aside>

        @include('trainer.feedback.partials.training-detail')
    </div>
@else
    <div id="feedbackWorkspace" class="fb-empty-workspace">
        <div class="ana-compact-empty fb-empty-state">
            <i class="bi {{ $totalCourses > 0 ? 'bi-search' : 'bi-inbox' }}" aria-hidden="true"></i>
            <span><strong>{{ $courseOptions->isNotEmpty() ? 'No matching feedback' : 'No feedback yet' }}</strong>{{ $courseOptions->isNotEmpty() ? 'No training feedback matches the selected filters or comment search.' : 'Anonymous participant feedback will appear after a started training receives feedback.' }}</span>
        </div>
    </div>
@endif
