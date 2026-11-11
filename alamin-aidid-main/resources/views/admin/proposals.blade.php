@extends('admin.layouts.app')

@section('title', 'Proposal Review | Al Amin Edu Oasis')

@push('styles')
@include('admin.proposals.partials.styles')
@endpush

@section('content')
@include('admin.proposals.partials.content')
@endsection

@push('scripts')
@include('admin.proposals.partials.scripts')
@endpush