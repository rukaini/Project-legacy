@php
    $trainer = $trainer ?? null;
    $assignedTrainingCount = $assignedTrainingCount ?? 0;
    $proposalCount = $proposalCount ?? 0;
    $documents = $documents ?? collect();
    $documentCount = $documentCount ?? 0;
    $documentTableExists = $documentTableExists ?? false;
    $liveRating = $liveRating ?? null;
    $certificates = $certificates ?? collect();
    $certificateTableExists = $certificateTableExists ?? false;

    // A document without an attached file has no trainer-facing action.
    $documents = $documents->filter(function ($document) {
        return !empty($document->file_path) || !empty($document->file_url);
    })->values();
    $documentCount = $documents->count();

    $activeTab = request('tab', $activeTab ?? 'profile');
    if ($errors->has('certificate') || $errors->has('title')) {
        $activeTab = 'certificate';
    }
    if (!in_array($activeTab, ['profile', 'certificate', 'documents'], true)) {
        $activeTab = 'profile';
    }

    $trainerName = $trainer->trainer_name ?? 'Trainer';

    // Feedback metrics are derived from canonical response rows, never a stale cache.
    $rating = $liveRating !== null ? (float) $liveRating : null;

    $fmtDate = function ($value) {
        if (!$value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $iconForFile = function ($path) {
        $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'bi-file-earmark-pdf',
            'doc', 'docx' => 'bi-file-earmark-word',
            'jpg', 'jpeg', 'png', 'webp' => 'bi-file-earmark-image',
            default => 'bi-file-earmark-check',
        };
    };
@endphp

@extends('trainer.layouts.app')

@section('title', 'My Profile | Al Amin Edu Oasis')

@push('styles')
@include('trainer.profile.partials.styles')
@endpush

@section('content')
@include('trainer.profile.partials.content')
@include('trainer.profile.partials.scripts')
@endsection
