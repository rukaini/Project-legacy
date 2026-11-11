@extends('layouts.app')
@section('title', $school->schoolName ?? 'School Details')

@section('content')
<div class="ui-page-shell">
    {{-- Back Button & Page Header --}}
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:20px;">
        <a href="{{ route('school.list') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-arrow-left"></i> Back to School List
        </a>
        <a href="{{ route('school.edit', $school->schoolID) }}" class="ui-button ui-button--primary" style="min-height:36px;padding:0 20px;display:inline-flex;align-items:center;gap:8px;">
            <i class="fas fa-edit"></i> Edit School
        </a>
    </div>

    {{-- School Header --}}
    <div style="background:linear-gradient(135deg, #059669, #047857);border-radius:var(--app-radius-panel);padding:24px 28px;margin-bottom:24px;color:#fff;position:relative;overflow:hidden;">
        <div style="position:absolute;right:-20px;top:-20px;font-size:80px;opacity:0.08;pointer-events:none;">
            🏫
        </div>
        <div style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:16px;position:relative;z-index:1;">
            <div>
                <div style="font-size:12px;font-weight:600;opacity:0.8;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:4px;">
                    <i class="fas fa-id-card"></i> School ID: {{ $school->schoolID }}
                </div>
                <h1 style="font-size:28px;font-weight:800;margin:0;line-height:1.2;">
                    {{ $school->schoolName }}
                </h1>
                <div style="font-size:13px;opacity:0.8;margin-top:6px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                    <i class="fas fa-map-marker-alt"></i>
                    {{ $school->schoolAddress ? Str::limit($school->schoolAddress, 60) : 'No address provided' }}
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                    <i class="fas fa-check-circle"></i> Active
                </span>
            </div>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">School Capacity</div>
                <div class="ui-card__value">{{ $school->capacity ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> Maximum Teachers
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Active Teachers</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ $activeTeachers ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-chalkboard-user"></i> Currently Teaching
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: {{ ($school->vacancy ?? 0) > 0 ? 'var(--app-warning)' : 'var(--app-success)' }};">
                <div class="ui-card__label">Vacancy</div>
                <div class="ui-card__value" style="color:{{ ($school->vacancy ?? 0) > 0 ? 'var(--app-warning)' : 'var(--app-success)' }};">
                    {{ $school->vacancy ?? 0 }}
                </div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-bullseye"></i> Open Positions
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Total Teachers</div>
                <div class="ui-card__value">{{ $school->totalTeacher ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> All Teachers
                </div>
            </div>
        </div>
    </div>

    {{-- School Information --}}
    <div class="ui-card ui-card--section mb-4">
        <div class="ui-card__header">
            <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                <i class="fas fa-info-circle" style="color:var(--app-primary);"></i> School Information
            </h3>
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-building"></i> Details
            </span>
        </div>
        <div class="ui-card__body">
            <div class="row g-4">
                <div class="col-md-6">
                    <div style="display:grid;gap:4px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--app-primary);"></i> Address
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $school->schoolAddress ?? 'N/A' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="display:grid;gap:4px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-phone" style="color:var(--app-primary);"></i> Phone Number
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $school->phoneNumber ?? 'N/A' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="display:grid;gap:4px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Register Date
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $school->registerDate ? \Carbon\Carbon::parse($school->registerDate)->format('d/m/Y') : 'N/A' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="display:grid;gap:4px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-chalkboard-user" style="color:var(--app-primary);"></i> Total Teacher
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $school->totalTeacher ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Teachers List --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header">
            <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                <i class="fas fa-chalkboard-user" style="color:var(--app-primary);"></i> Teachers in This School
            </h3>
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-user"></i> {{ isset($teachers) ? $teachers->count() : 0 }} Teachers
            </span>
        </div>
        <div class="ui-card__body" style="padding:0;">
            @if(isset($teachers) && $teachers->count() > 0)
                <div class="ui-table-wrap">
                    <table class="ui-table ui-table--dashboard">
                        <thead>
                            <tr>
                                <th>Teacher ID</th>
                                <th>Teacher Name</th>
                                <th>IC Number</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teachers as $teacher)
                            @php
                                $isActive = ($teacher->status ?? 'Aktif') == 'Aktif' || ($teacher->status ?? 'Active') == 'Active';
                            @endphp
                            <tr>
                                <td>
                                    <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                        {{ $teacher->teacherID }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:var(--app-text);">
                                        <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i>
                                        {{ $teacher->teacherName }}
                                    </div>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    {{ $teacher->ICNumber ?? '-' }}
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    {{ $teacher->phoneNumber ?? '-' }}
                                </td>
                                <td>
                                    @if($isActive)
                                        <span class="ui-badge ui-badge--success" style="font-size:11px;">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    @else
                                        <span class="ui-badge ui-badge--danger" style="font-size:11px;">
                                            <i class="fas fa-times-circle"></i> Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('teachers.show', $teacher->teacherID) }}" class="ui-button ui-button--compact" style="font-size:11px;padding:4px 14px;min-height:30px;border-color:var(--app-primary-border);color:var(--app-primary);">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ui-state ui-state--compact" style="min-height:200px;">
                    <div style="font-size:2.5rem;margin-bottom:8px;">👨‍🏫</div>
                    <div class="ui-state__title">No Teachers Assigned</div>
                    <div class="ui-state__copy">No teachers have been assigned to this school yet.</div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection