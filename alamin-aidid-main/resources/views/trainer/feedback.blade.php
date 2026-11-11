@extends('trainer.layouts.app')

@section('title', 'Feedback')

@push('styles')
@include('trainer.training.analytics.partials.styles')
@include('trainer.feedback.partials.styles')
@endpush

@section('content')

@include('trainer.feedback.partials.content')
@endsection

@push('scripts')
@include('trainer.feedback.partials.scripts')
@endpush
