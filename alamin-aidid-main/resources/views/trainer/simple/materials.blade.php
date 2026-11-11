@php
    $materials = $materials ?? collect();
    $materialFilters = $materialFilters ?? ['search' => '', 'sort' => 'newest', 'type' => '', 'rows' => 10, 'page' => 1];
    $materialTypeOptions = $materialTypeOptions ?? collect();
    $materialTotal = $materialTotal ?? 0;
    $activeSimpleTab = 'materials';

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

    $iconFor = function ($type) {
        $t = strtolower((string) $type);
        if ($t === 'pdf') return 'bi-file-earmark-pdf';
        if ($t === 'presentation' || $t === 'slides') return 'bi-file-earmark-slides';
        if ($t === 'document') return 'bi-file-earmark-word';
        if ($t === 'spreadsheet') return 'bi-file-earmark-spreadsheet';
        if ($t === 'image') return 'bi-file-earmark-image';
        if ($t === 'video') return 'bi-file-earmark-play';
        if ($t === 'audio') return 'bi-file-earmark-music';
        return 'bi-file-earmark';
    };
@endphp

@extends('trainer.layouts.app')

@section('title', $course->course_name . ' | Training Workspace')

@push('styles')
@include('trainer.simple.materials.partials.styles')
@endpush

@section('content')

@include('trainer.simple.materials.partials.content')

@include('trainer.simple.materials.partials.scripts')

@endsection
