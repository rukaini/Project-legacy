@extends('admin.layouts.app')

@section('title', 'Training Monitoring | Al Amin Edu Oasis')

@push('styles')
@include('admin.courses.partials.styles')
@endpush

@section('content')
@include('admin.courses.partials.content')
@endsection

@push('scripts')
@include('admin.courses.partials.scripts')
@endpush