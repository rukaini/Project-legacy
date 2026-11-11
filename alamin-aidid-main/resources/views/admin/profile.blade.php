@php
    $staff = $staff ?? null;
    $proposalsReviewed = $proposalsReviewed ?? 0;
    $proposalsApproved = $proposalsApproved ?? 0;
    $proposalsRejected = $proposalsRejected ?? 0;
    $approvalRate = $approvalRate ?? null;
    $daysToRetirement = $daysToRetirement ?? null;
    $recentReviews = $recentReviews ?? collect();

    $staffName = $staff->staff_name ?? 'Admin';
    $staffRole = $staff->role ?? 'Admin';

    $fmtDate = function ($value) {
        if (!$value) return null;
        try { return \Carbon\Carbon::parse($value)->format('d M Y'); }
        catch (\Throwable $e) { return null; }
    };

    $fmtReviewTime = function ($value) {
        if (!$value) return null;
        try { return \Carbon\Carbon::parse($value); }
        catch (\Throwable $e) { return null; }
    };
@endphp

@extends('admin.layouts.app')

@section('title', 'My Profile | Al Amin Edu Oasis')

@push('styles')
@include('admin.profile.partials.styles')
@endpush

@section('content')
@include('admin.profile.partials.content')
@endsection
