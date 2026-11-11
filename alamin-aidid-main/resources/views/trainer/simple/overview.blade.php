@php
    $sessions = $sessions ?? collect();
    $pastSessions = $pastSessions ?? collect();
    $scheduleUpcoming = $scheduleUpcoming ?? collect();
    $participantsPreview = $participantsPreview ?? collect();
    $participantStats = $participantStats ?? ['total' => 0, 'male' => 0, 'female' => 0, 'unspecified' => 0, 'teacher' => 0, 'new_teacher' => 0, 'public' => 0];
    $materialCount = $materialCount ?? 0;
    $featuredSession = $featuredSession ?? null;
    $featuredLabel = $featuredLabel ?? null;
    $trainingTeam = $trainingTeam ?? collect();
    $activeSimpleTab = 'overview';
    $materialsUrl = route('trainer.simple.materials', $course->course_id);
    $attendanceUrl = route('trainer.simple.attendance', $course->course_id);

    $sessionPlace = function ($session) {
        $location = trim((string) ($session->location ?? ''));
        $type = strtolower((string) ($session->session_type ?? ''));
        $isUrl = str_starts_with($location, 'http://') || str_starts_with($location, 'https://');
        $isOnline = $isUrl || str_contains($type, 'online') || str_contains($type, 'virtual');

        return [
            'icon' => $isOnline ? 'bi-camera-video' : 'bi-geo-alt',
            'label' => $isUrl ? 'Join meeting' : ($location !== '' ? $location : ($isOnline ? 'Online session' : 'Location not set')),
            'url' => $isUrl ? $location : null,
        ];
    };

    $hasParticipants = $participantStats['total'] > 0;
    $totalParticipants = $participantStats['total'];
    $hasMaterials = $materialCount > 0;
    $hasSessions = $sessions->isNotEmpty();
    $readyCount = (int) $hasParticipants + (int) $hasMaterials + (int) $hasSessions;
    $allReady = $readyCount === 3;
@endphp

@extends('trainer.layouts.app')

@section('title', $course->course_name.' | Training Workspace')

@push('styles')
@include('trainer.simple.overview.partials.styles')
@endpush

@section('content')
@include('trainer.simple.overview.partials.content')
@include('trainer.simple.overview.partials.scripts')
@endsection
