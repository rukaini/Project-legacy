@php
    $participantStats = $participantStats ?? ['total' => 0, 'male' => 0, 'female' => 0, 'unspecified' => 0];
    $participantFilters = $participantFilters ?? ['search' => '', 'gender' => '', 'letter' => '', 'rows' => 10, 'page' => 1];
    $participantLetters = $participantLetters ?? collect();
    $activeSimpleTab = 'participants';
@endphp

@extends('trainer.layouts.app')

@section('title', $course->course_name . ' | Training Workspace')

@push('styles')
@include('trainer.simple.participants.partials.styles')
@endpush

@section('content')

@include('trainer.simple.participants.partials.content')

@include('trainer.simple.participants.partials.scripts')

@endsection
