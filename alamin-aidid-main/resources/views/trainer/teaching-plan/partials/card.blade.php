@php
    $currentTeachingPlan = $currentTeachingPlan ?? null;
    $teachingPlanState = $teachingPlanState ?? ['key' => 'upload-required', 'label' => 'Upload Required', 'message' => 'Upload your teaching plan for Admin review and feedback.'];
    $teachingPlanFileId = 'teachingPlanFile-'.$course->course_id;
    $currentComments = $currentTeachingPlan
        ? collect($currentTeachingPlan->comments ?? [])->sortByDesc('created_at')->values()
        : collect();
    $hasUnseenCurrentFeedback = ($teachingPlanState['key'] ?? null) === 'new-feedback';
@endphp

<section class="tp-workspace" aria-labelledby="teaching-plan-title">
    <header class="tp-page-heading">
        <div class="tp-page-title">
            <span class="tp-page-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
            <div>
                <h1 id="teaching-plan-title">Teaching Plan</h1>
                <p>Manage your current submission and Admin feedback.</p>
            </div>
        </div>
        <span class="tp-state {{ $teachingPlanState['key'] }}" data-teaching-plan-state>{{ $teachingPlanState['label'] }}</span>
    </header>

    <div class="tp-status-message {{ $teachingPlanState['key'] }}" data-teaching-plan-state-message role="status">
        <i class="bi {{ $hasUnseenCurrentFeedback ? 'bi-chat-left-text-fill' : ($currentTeachingPlan ? 'bi-info-circle' : 'bi-exclamation-circle-fill') }}" aria-hidden="true"></i>
        <span>{{ $teachingPlanState['message'] }}</span>
    </div>

    @if ($currentTeachingPlan)
        <section class="tp-panel tp-current-panel" aria-labelledby="teaching-plan-current-title">
            <div class="tp-section-head">
                <div>
                    <span class="tp-section-kicker">Active document</span>
                    <h2 id="teaching-plan-current-title">Current Submission</h2>
                    <p>This is the Teaching Plan Admin will review.</p>
                </div>
            </div>

            <div class="tp-current">
                <span class="tp-file-icon" aria-hidden="true"><i class="bi bi-file-earmark-text"></i></span>
                <div class="tp-file-main">
                    <strong>{{ $currentTeachingPlan->original_name }}</strong>
                    <span>Submitted {{ \Carbon\Carbon::parse($currentTeachingPlan->submitted_at)->format('d M Y, g:i A') }}</span>
                </div>
                <div class="tp-actions">
                    @if ($currentTeachingPlan->file_available)
                        <a href="{{ route('trainer.courses.teaching-plans.view', [$course->course_id, $currentTeachingPlan->id]) }}" target="_blank" rel="noopener"><i class="bi bi-eye" aria-hidden="true"></i>View</a>
                        <a href="{{ route('trainer.courses.teaching-plans.download', [$course->course_id, $currentTeachingPlan->id]) }}"><i class="bi bi-download" aria-hidden="true"></i>Download</a>
                    @else
                        <span class="tp-unavailable"><i class="bi bi-exclamation-circle" aria-hidden="true"></i>File unavailable</span>
                    @endif
                </div>
            </div>
        </section>

        <section class="tp-panel tp-current-feedback" aria-labelledby="teaching-plan-feedback-title">
            <div class="tp-section-head tp-feedback-head">
                <div>
                    <h2 id="teaching-plan-feedback-title">Admin Feedback</h2>
                    <p>Feedback attached to your current submission.</p>
                </div>
                @if ($hasUnseenCurrentFeedback)
                    <form method="POST"
                          action="{{ route('trainer.courses.teaching-plans.feedback.seen', [$course->course_id, $currentTeachingPlan->id]) }}"
                          data-teaching-plan-seen-form>
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="tp-seen-button"><i class="bi bi-check2" aria-hidden="true"></i>Mark as seen</button>
                    </form>
                @endif
            </div>

            @if ($currentComments->isNotEmpty())
                <div class="tp-feedback-list" data-current-feedback-list>
                    @foreach ($currentComments as $comment)
                        @php($commentIsUnseen = ! property_exists($comment, 'seen_by_trainer_at') || $comment->seen_by_trainer_at === null)
                        <article @class(['tp-feedback-item', 'is-unseen' => $commentIsUnseen])>
                            <div class="tp-feedback-meta">
                                <div>
                                    <span class="tp-reviewer-avatar" aria-hidden="true"><i class="bi bi-person"></i></span>
                                    <strong>{{ $comment->reviewer_name ?: 'Admin' }}</strong>
                                    @if ($commentIsUnseen)<span class="tp-new-label">New</span>@endif
                                </div>
                                <time datetime="{{ \Carbon\Carbon::parse($comment->created_at)->toIso8601String() }}">{{ \Carbon\Carbon::parse($comment->created_at)->format('d M Y, g:i A') }}</time>
                            </div>
                            <p>{{ $comment->comment }}</p>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="tp-awaiting-feedback">
                    <i class="bi bi-clock" aria-hidden="true"></i>
                    <span>Awaiting Admin feedback.</span>
                </div>
            @endif
            <p class="tp-seen-result" data-teaching-plan-seen-result aria-live="polite" hidden></p>
        </section>

        <section class="tp-panel tp-remove-panel" aria-labelledby="teaching-plan-remove-title">
            <div class="tp-section-head">
                <div>
                    <h2 id="teaching-plan-remove-title">Replace Current Submission</h2>
                    <p>Remove this submission before uploading a corrected or updated Teaching Plan.</p>
                </div>
            </div>

            <p class="tp-remove-blocked" data-teaching-plan-remove-blocked @if (! $hasUnseenCurrentFeedback) hidden @endif>
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                <span>Review and mark the latest Admin feedback as seen before replacing this submission.</span>
            </p>
            <form method="POST"
                  action="{{ route('trainer.courses.teaching-plans.destroy', [$course->course_id, $currentTeachingPlan->id]) }}"
                  data-teaching-plan-remove-form
                  @if ($hasUnseenCurrentFeedback) hidden @endif>
                @csrf
                @method('DELETE')
                <button type="submit" class="tp-remove-button"><i class="bi bi-trash3" aria-hidden="true"></i>Remove Submission</button>
            </form>
        </section>
    @else
        <section class="tp-panel tp-first-submission" aria-labelledby="teaching-plan-upload-title">
            <span class="tp-empty-icon" aria-hidden="true"><i class="bi bi-file-earmark-arrow-up"></i></span>
            <div>
                <h2 id="teaching-plan-upload-title">Upload Teaching Plan</h2>
                <p>Upload your teaching plan for Admin review and feedback.</p>
            </div>
        </section>

        <section class="tp-panel tp-upload-panel" aria-labelledby="teaching-plan-upload-form-title">
            <div class="tp-section-head">
                <div>
                    <h2 id="teaching-plan-upload-form-title">Upload Teaching Plan</h2>
                    <p>PDF, DOC, or DOCX &middot; Maximum 10 MB</p>
                </div>
            </div>

            <form method="POST" action="{{ route('trainer.courses.teaching-plans.store', $course->course_id) }}" enctype="multipart/form-data" class="tp-upload-form" data-teaching-plan-upload>
                @csrf
                <div class="tp-file-field">
                    <div class="tp-file-picker">
                        <input type="file" id="{{ $teachingPlanFileId }}" name="teaching_plan" accept=".pdf,.doc,.docx" required data-teaching-plan-file>
                        <label for="{{ $teachingPlanFileId }}" class="tp-file-trigger"><i class="bi bi-paperclip" aria-hidden="true"></i>Choose file</label>
                        <span class="tp-selected-file" data-teaching-plan-file-name>No file selected</span>
                        <button type="button" class="tp-file-clear" data-teaching-plan-file-clear aria-label="Clear selected Teaching Plan file" hidden><i class="bi bi-x-lg" aria-hidden="true"></i></button>
                    </div>
                    @error('teaching_plan')<span class="tp-field-error" role="alert">{{ $message }}</span>@enderror
                </div>
                <button type="submit" class="tp-upload-button"><i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>Upload Teaching Plan</button>
            </form>
        </section>
    @endif
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-teaching-plan-upload]').forEach(function (form) {
            var input = form.querySelector('[data-teaching-plan-file]');
            var fileName = form.querySelector('[data-teaching-plan-file-name]');
            var clearButton = form.querySelector('[data-teaching-plan-file-clear]');
            if (!input || !fileName || !clearButton) return;

            function syncSelectedFile() {
                var selected = input.files && input.files[0] ? input.files[0].name : '';
                fileName.textContent = selected || 'No file selected';
                fileName.classList.toggle('has-file', Boolean(selected));
                clearButton.hidden = !selected;
            }

            input.addEventListener('change', syncSelectedFile);
            clearButton.addEventListener('click', function () {
                input.value = '';
                syncSelectedFile();
                input.focus();
            });
        });

        document.querySelectorAll('[data-teaching-plan-remove-form]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm('Remove this Teaching Plan submission and all feedback attached to it?')) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('[data-teaching-plan-seen-form]').forEach(function (form) {
            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                var button = form.querySelector('button[type="submit"]');
                var result = document.querySelector('[data-teaching-plan-seen-result]');
                if (!button || !result) return;

                button.disabled = true;
                result.hidden = true;

                try {
                    var response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                        credentials: 'same-origin'
                    });
                    var payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || 'The feedback could not be marked as seen.');

                    var state = document.querySelector('[data-teaching-plan-state]');
                    var stateMessage = document.querySelector('[data-teaching-plan-state-message]');
                    if (state) {
                        state.className = 'tp-state ' + payload.state.key;
                        state.textContent = payload.state.label;
                    }
                    if (stateMessage) {
                        stateMessage.className = 'tp-status-message ' + payload.state.key;
                        var message = stateMessage.querySelector('span');
                        if (message) message.textContent = payload.state.message;
                    }
                    document.querySelectorAll('[data-teaching-plan-attention]').forEach(function (indicator) {
                        if (indicator.dataset.teachingPlanAttention === 'new-feedback') indicator.remove();
                    });
                    document.querySelectorAll('[data-current-feedback-list] .is-unseen').forEach(function (item) {
                        item.classList.remove('is-unseen');
                        var label = item.querySelector('.tp-new-label');
                        if (label) label.remove();
                    });
                    var blockedMessage = document.querySelector('[data-teaching-plan-remove-blocked]');
                    var removeForm = document.querySelector('[data-teaching-plan-remove-form]');
                    if (blockedMessage) blockedMessage.hidden = true;
                    if (removeForm) removeForm.hidden = false;
                    form.hidden = true;
                    result.textContent = payload.message;
                    result.className = 'tp-seen-result success';
                    result.hidden = false;
                } catch (error) {
                    button.disabled = false;
                    result.textContent = error instanceof Error ? error.message : 'The feedback could not be marked as seen.';
                    result.className = 'tp-seen-result error';
                    result.hidden = false;
                }
            });
        });
    });
</script>
