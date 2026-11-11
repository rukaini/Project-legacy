@extends('layouts.app')
@section('title', 'HR Dashboard')

@section('content')
{{-- Add this debug line at the very beginning --}}
<!-- DEBUG: Dashboard view is loading -->

<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1>Welcome back, HR Team! 👋</h1>
            <p>Here's what's happening with your workforce today.</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-calendar-alt me-1"></i>{{ now()->format('l, d F Y') }}
            </span>
        </div>
    </div>

    {{-- Debug: Check if variables exist --}}
    <!-- Debug: totalSchools = {{ isset($totalSchools) ? $totalSchools : 'NOT SET' }} -->
    <!-- Debug: totalPrincipals = {{ isset($totalPrincipals) ? $totalPrincipals : 'NOT SET' }} -->
    <!-- Debug: totalTeachers = {{ isset($totalTeachers) ? $totalTeachers : 'NOT SET' }} -->

    {{-- Statistics Grid using Edu Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Schools</div>
                <div class="ui-card__value">{{ $totalSchools ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> Active Institutions
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Principals</div>
                <div class="ui-card__value">{{ $totalPrincipals ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-user-tie"></i> School Leaders
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Teachers</div>
                <div class="ui-card__value">{{ $totalTeachers ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-chalkboard-user"></i> Teaching Staff
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-danger);">
                <div class="ui-card__label">Schools w/o Principal</div>
                <div class="ui-card__value" style="color:var(--app-danger);">
                    {{ $totalSchoolsWithoutPrincipal ?? 0 }}
                </div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-exclamation-triangle" style="color:var(--app-warning);"></i> Requires Attention
                </div>
            </div>
        </div>
    </div>

    {{-- Schools Without Principal Alert --}}
    @if(($totalSchoolsWithoutPrincipal ?? 0) > 0)
        <div class="ui-message ui-message--error" style="margin-bottom: 1.5rem;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>{{ $totalSchoolsWithoutPrincipal }}</strong> school(s) currently don't have an active principal assigned.
                Please assign principals to these schools as soon as possible.
            </div>
        </div>
    @endif

    {{-- Two Column Layout --}}
    <div class="row g-4">
        {{-- LEFT COLUMN --}}
        <div class="col-lg-7">
            {{-- School Capacity Table --}}
            <div class="ui-card ui-card--section">
                <div class="ui-card__header">
                    <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);">
                        <i class="fas fa-school" style="color:var(--app-primary);"></i> School Capacity Overview
                    </h3>
                    <span class="ui-badge ui-badge--info">
                        {{ isset($schools) ? count($schools) : 0 }} Schools
                    </span>
                </div>
                <div class="ui-card__body" style="padding:0;">
                    <div class="ui-table-wrap">
                        <table class="ui-table ui-table--dashboard">
                            <thead>
                                <tr>
                                    <th>School Name</th>
                                    <th class="text-center">👨‍🏫</th>
                                    <th class="text-center">🎯</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($schools ?? collect())->take(6) as $school)
                                <tr>
                                    <td class="fw-semibold">{{ $school->schoolName ?? 'N/A' }}</td>
                                    <td class="text-center fw-bold">{{ $school->totalTeacher ?? 0 }}</td>
                                    <td class="text-center">
                                        @if(($school->vacancy ?? 0) > 0)
                                            <span class="fw-bold" style="color:var(--app-warning);">{{ $school->vacancy }}</span>
                                        @else
                                            <span style="color:var(--app-text-subtle);">0</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if(($school->vacancy ?? 0) > 0)
                                            <span class="ui-badge ui-badge--warning">
                                                <i class="fas fa-exclamation-triangle"></i> Needs
                                            </span>
                                        @else
                                            <span class="ui-badge ui-badge--success">
                                                <i class="fas fa-check-circle"></i> Full
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center" style="padding:20px;color:var(--app-text-subtle);">
                                            No Schools Found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-5">
            {{-- Quick Actions --}}
            <div class="ui-card ui-card--section">
                <div class="ui-card__header">
                    <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);">
                        <i class="fas fa-bolt" style="color:var(--app-warning);"></i> Quick Actions
                    </h3>
                </div>
                <div class="ui-card__body" style="padding:16px;">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ route('school.list') }}" class="btn btn-outline-primary w-100" style="padding:12px 8px;font-size:13px;font-weight:700;border-radius:var(--app-radius-card);">
                                <i class="fas fa-school me-2"></i> Manage Schools
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('teachers.index') }}" class="btn btn-outline-success w-100" style="padding:12px 8px;font-size:13px;font-weight:700;border-radius:var(--app-radius-card);">
                                <i class="fas fa-chalkboard-user me-2"></i> Teachers
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('staff.list') }}" class="btn btn-outline-info w-100" style="padding:12px 8px;font-size:13px;font-weight:700;border-radius:var(--app-radius-card);">
                                <i class="fas fa-users me-2"></i> Staff
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('principal.index') }}" class="btn btn-outline-warning w-100" style="padding:12px 8px;font-size:13px;font-weight:700;border-radius:var(--app-radius-card);">
                                <i class="fas fa-user-tie me-2"></i> Principals
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection