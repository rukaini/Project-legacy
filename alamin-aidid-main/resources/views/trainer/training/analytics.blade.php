@php
    $trainings = $trainings ?? collect();
    $selectedTraining = $selectedTraining ?? null;
    $headlineMetrics = $headlineMetrics ?? collect();
    $attendanceSummary = $attendanceSummary ?? [
        'available' => false,
        'total' => 0,
        'rate' => null,
        'counts' => ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'Not Recorded' => 0],
    ];
    $sessionOptions = $sessionOptions ?? collect();
    $sessionResults = $sessionResults ?? collect();
    $participantAttendance = $participantAttendance ?? collect();
    $selectedSessionId = $selectedSessionId ?? 0;
    $sessionSort = $sessionSort ?? 'date';
    $assessmentType = $assessmentType ?? 'all';
    $assessmentOptions = $assessmentOptions ?? collect();
    $selectedAssessmentId = $selectedAssessmentId ?? 0;
    $quizResults = $quizResults ?? collect();
    $quizSort = $quizSort ?? 'name';
    $quizTotals = $quizTotals ?? ['attempts' => 0, 'average_score' => null];
    $hasQuizAssessments = $hasQuizAssessments ?? false;
    $submissionResults = $submissionResults ?? collect();
    $submissionSort = $submissionSort ?? 'name';
    $submissionTotals = $submissionTotals ?? ['submitted' => 0, 'reviewed' => 0, 'pending' => 0];
    $hasSubmissionAssessments = $hasSubmissionAssessments ?? false;
    $pendingReviewCount = $pendingReviewCount ?? 0;
    $contextFilterActive = $contextFilterActive ?? false;
    $reportDate = $reportDate ?? 'all';
    $section = $section ?? 'overview';
    $dateFrom = $dateFrom ?? null;
    $dateTo = $dateTo ?? null;
@endphp

@extends('trainer.layouts.app')

@section('title', 'Training Analytics | Al Amin Edu Oasis')

@push('styles')
@include('trainer.training.analytics.partials.styles')
@endpush

@vite('resources/js/analytics-charts.js')

@section('content')
@include('trainer.training.analytics.partials.content')
@include('trainer.training.analytics.partials.scripts')
@endsection
