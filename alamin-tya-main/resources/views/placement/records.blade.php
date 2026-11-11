@extends('layouts.app')

@section('title', 'Placement Records')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-history" style="color:var(--app-primary);font-size:24px;"></i> Placement Records
            </h1>
            <p>View all teacher assignments to schools</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-user"></i> {{ isset($placements) ? $placements->total() : 0 }} Teachers
            </span>
            <a href="{{ route('placement.index') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-arrow-left"></i> Back to Assign
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Teachers</div>
                <div class="ui-card__value">{{ isset($placements) ? $placements->total() : 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> Assigned Teachers
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Teachers in Schools</div>
                <div class="ui-card__value" style="color:var(--app-success);">
                    {{ DB::table('school')->sum('totalTeacher') }}
                </div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-check-circle"></i> Currently Active
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-warning);">
                <div class="ui-card__label">Available Vacancies</div>
                <div class="ui-card__value" style="color:var(--app-warning);">
                    {{ DB::table('school')->sum('vacancy') }}
                </div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-briefcase"></i> Open Positions
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Total Schools</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ DB::table('school')->count() }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> Registered Schools
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-list"></i> Assigned Teachers List
            </h5>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-user"></i> {{ isset($placements) ? $placements->total() : 0 }} Records
            </span>
        </div>
        <div class="ui-card__body" style="padding:0;">
            @if(isset($placements) && count($placements) > 0)
                <div class="ui-table-wrap">
                    <table class="ui-table ui-table--dashboard">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Teacher</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>School</th>
                                <th>Vacancy</th>
                                <th>Assignment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($placements as $index => $placement)
                            <tr>
                                <td>
                                    <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:var(--app-text);">
                                        <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i>
                                        {{ $placement->teacher_name ?? $placement->applicant_name ?? 'N/A' }}
                                    </div>
                                    <div style="font-size:11px;color:var(--app-text-subtle);">
                                        ID: {{ $placement->teacherID ?? 'N/A' }}
                                    </div>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    {{ $placement->teacher_email ?? $placement->applicant_email ?? 'N/A' }}
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    {{ $placement->applicant_phone ?? 'N/A' }}
                                </td>
                                <td>
                                    <div style="font-weight:600;color:var(--app-text);">
                                        <i class="fas fa-school" style="color:var(--app-success);font-size:12px;"></i>
                                        {{ $placement->school_name ?? 'N/A' }}
                                    </div>
                                    @if(isset($placement->school_address))
                                        <div style="font-size:11px;color:var(--app-text-subtle);">
                                            {{ $placement->school_address }}
                                        </div>
                                    @endif
                                    <div style="font-size:11px;color:var(--app-text-subtle);">
                                        Teachers: {{ $placement->school_total_teacher ?? 0 }}
                                    </div>
                                </td>
                                <td>
                                    <span class="ui-badge" style="border-color:var(--app-primary-border);background:var(--app-primary-soft);color:var(--app-primary);font-size:11px;">
                                        <i class="fas fa-briefcase"></i> {{ $placement->school_vacancy ?? 0 }} vacancies
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size:13px;color:var(--app-text-secondary);">
                                        {{ isset($placement->assign_date) ? date('d/m/Y', strtotime($placement->assign_date)) : (isset($placement->join_date) ? date('d/m/Y', strtotime($placement->join_date)) : 'N/A') }}
                                    </div>
                                    @if(isset($placement->assign_date))
                                        <div style="font-size:11px;color:var(--app-text-subtle);">
                                            {{ \Carbon\Carbon::parse($placement->assign_date)->diffForHumans() }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span class="ui-badge ui-badge--success" style="font-size:11px;">
                                        <i class="fas fa-check-circle"></i> Assigned
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($placements, 'links'))
                    <div style="padding:12px 20px;border-top:1px solid var(--app-divider);">
                        {{ $placements->links() }}
                    </div>
                @endif
            @else
                <div class="ui-state ui-state--compact">
                    <div style="font-size:3rem;margin-bottom:8px;">📭</div>
                    <div class="ui-state__title">No Placement Records</div>
                    <div class="ui-state__copy">No teachers have been assigned to schools yet.</div>
                    <a href="{{ route('placement.index') }}" class="ui-button ui-button--primary" style="margin-top:16px;">
                        <i class="fas fa-arrow-right"></i> Go to Assign Teacher
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection