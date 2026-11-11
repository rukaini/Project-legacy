@extends('trainer.layouts.app')

@section('title', 'Submissions | Al Amin Edu Oasis')

@include('trainer.training.submission.partials.styles')

@section('content')
<script>document.body.dataset.lwCourseId = '{{ $course->course_id }}';</script>

@include('trainer.training.submission.partials.header')

<main class="lw-page app-workspace-shell" data-lms-submissions-page>
    @include('trainer.training.partials.workspace-page-heading', [
        'title' => 'Submissions',
        'description' => 'Review participant file-upload tasks and submitted work for this training.',
    ])

    <div class="lw-report-error" data-submission-page-error hidden>
        <i class="bi bi-exclamation-circle"></i>
        <span>Submissions could not be refreshed. Please try again.</span>
    </div>

    <div class="lw-sub-grid" id="lwSubGrid">
        @include('trainer.training.submission.partials.task-inbox')
        @include('trainer.training.submission.partials.workspace')
    </div>
</main>

@include('trainer.training.submission.partials.file-preview-modal')
@endsection

@include('trainer.training.submission.partials.scripts')
