@extends('admin.layouts.app')

@section('title', 'Directory | Al Amin Edu Oasis')

@push('styles')
@include('admin.directory.partials.styles')
@endpush

@section('content')
@include('admin.directory.partials.content')
@endsection

@push('scripts')
@include('admin.directory.partials.scripts')
@endpush