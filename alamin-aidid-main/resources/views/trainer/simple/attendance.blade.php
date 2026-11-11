@php
    $sessions = $sessions ?? collect();
    $attendanceCounts = $attendanceCounts ?? ['total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'not_recorded' => 0];
    $attendanceFilters = $attendanceFilters ?? ['session_id' => 0, 'search' => '', 'status' => 'all', 'rows' => 10, 'page' => 1];
    $selectedSession = $selectedSession ?? null;
    $activeSimpleTab = 'attendance';

    $statusInfo = function ($value) {
        $v = strtolower(trim((string) $value));
        return [$value ?: 'Not Recorded', str_replace(' ', '-', $v ?: 'not-recorded')];
    };

    $locationFor = function ($session) {
        if (!$session) return ['Location not set', 'bi-geo-alt', null];
        $location = trim($session->location ?? '');
        $type = strtolower($session->session_type ?? '');
        $isOnline = str_contains($type, 'online') || str_contains($type, 'virtual');

        if ($isOnline) {
            if ($location !== '' && (str_starts_with($location, 'http://') || str_starts_with($location, 'https://'))) {
                return ['Join meeting', 'bi-camera-video', $location];
            }
            return [$location !== '' ? $location : 'Online session', 'bi-camera-video', null];
        }

        return [$location !== '' ? $location : 'Location not set', 'bi-geo-alt', null];
    };
@endphp

@extends('trainer.layouts.app')

@section('title', $course->course_name . ' | Training Workspace')

@push('styles')
@include('trainer.simple.attendance.partials.styles')
@endpush

@section('content')

@include('trainer.simple.attendance.partials.content')

@include('trainer.simple.attendance.partials.scripts')

@endsection
