@extends('admin.layouts.app')

@section('title', 'Activity Log | Al Amin Edu Oasis')

@push('styles')
@include('admin.activity.partials.styles')
@endpush

@section('content')
@include('admin.activity.partials.content')
@endsection

@push('scripts')
@include('admin.activity.partials.scripts')
@endpush