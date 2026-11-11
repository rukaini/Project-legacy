@extends('admin.layouts.app')

@section('title', 'Analytics | Al Amin Edu Oasis')

@push('styles')
@include('trainer.training.analytics.partials.styles')
@include('admin.analytics.partials.styles')
@endpush

@vite(['resources/js/analytics-charts.js', 'resources/js/admin-analytics-charts.js'])

@section('content')
@include('admin.analytics.partials.content')
@include('admin.analytics.partials.scripts')
@endsection
