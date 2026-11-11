@php
    $stats = $stats ?? ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
    $proposalTableExists = $proposalTableExists ?? false;
    $proposalPaginator = $proposalPaginator ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10);
    $selectedProposal = $selectedProposal ?? null;
    $proposalFilters = $proposalFilters ?? [
        'status' => 'all',
        'search' => '',
        'submitted_period' => 'all',
        'submitted_from' => '',
        'submitted_to' => '',
        'rows' => 10,
        'page' => 1,
        'proposal_id' => 0,
    ];
    $nonSearchFiltersActive = $nonSearchFiltersActive ?? false;
    $oldAudience = (array) old('target_audience', []);
@endphp

@extends('trainer.layouts.app')

@section('title', 'Training Proposal | Al Amin Edu Oasis')

@push('styles')
@include('trainer.training.proposal.partials.styles')
@endpush

@section('content')

@include('trainer.training.proposal.partials.content')

@include('trainer.training.proposal.partials.scripts')

@endsection
