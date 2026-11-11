@php
    $pendingSubmissions = $pendingSubmissions ?? 0;
@endphp

@extends('trainer.layouts.app')

@section('title', 'Assessment Builder | Al Amin Edu Oasis')

@include('trainer.training.partials.material-authoring-styles')

@section('content')
<script>document.body.dataset.lwCourseId = '{{ $course->course_id }}';</script>

@include('trainer.training.partials.workspace-header', [
    'activeLmsTab' => 'materials',
    'lmsSubpageTitle' => 'Assessment Builder',
])

<main class="lw-page app-workspace-shell" id="lmsWorkspaceContent" data-lms-authoring-page="assessment">
    <script type="application/json" id="materialsValidationToastJson">@json([
        'error' => $errors->any() ? $errors->first() : null,
    ])</script>

    @include('trainer.training.assessment-builder.partials.workspace')
    @include('trainer.training.assessment-builder.partials.dialogs')

</main>
@endsection

@include('trainer.training.partials.material-authoring-scripts')
