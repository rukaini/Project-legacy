@extends('trainer.layouts.app')

@section('title', 'Learner Tracking | Al Amin Edu Oasis')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
@include('trainer.training.partials.workspace-header-styles')
@include('trainer.training.learner-tracking.partials.styles')
@endpush

@section('content')

@include('trainer.training.learner-tracking.partials.header')
@include('trainer.training.learner-tracking.partials.content')

@endsection

@push('scripts')
@include('trainer.training.learner-tracking.partials.scripts')
@endpush
