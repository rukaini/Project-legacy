@extends('layouts.app')

@section('title', 'My Profile - Principal')

@section('content')
<div class="ui-page-shell" style="max-width:1000px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-user-circle" style="color:var(--app-primary);font-size:24px;"></i> My Profile
            </h1>
            <p>View your personal and professional information</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--success">
                <i class="fas fa-circle" style="font-size:8px;color:#34d399;"></i> Active
            </span>
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $principal->principalID ?? '—' }}
            </span>
        </div>
    </div>

    {{-- Main Profile Card --}}
    <div class="ui-card ui-card--section" style="position:relative;overflow:hidden;">
        {{-- Background Decoration --}}
        <div style="position:absolute;right:-30px;top:-30px;font-size:160px;opacity:0.04;pointer-events:none;z-index:0;">
            👤
        </div>

        {{-- Profile Header --}}
        <div class="ui-card__header" style="background:linear-gradient(135deg, #1e2a4a 0%, #1a3a6a 40%, #1e4a8a 100%);border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:28px 32px;position:relative;z-index:1;">
            <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
                {{-- Avatar --}}
                <div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));display:flex;align-items:center;justify-content:center;font-size:36px;font-weight:700;color:#fff;box-shadow:0 8px 32px rgba(37,99,235,0.3);border:4px solid rgba(255,255,255,0.2);flex-shrink:0;user-select:none;">
                    {{ strtoupper(substr($principal->principalName ?? 'P', 0, 1)) }}
                </div>
                <div style="flex:1;">
                    <h1 style="margin:0;font-size:28px;font-weight:800;color:#fff;letter-spacing:-0.5px;">
                        {{ $principal->principalName ?? 'Principal' }}
                    </h1>
                    <div style="color:rgba(255,255,255,0.7);font-size:14px;margin-top:4px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span>🆔 {{ $principal->principalID ?? '—' }}</span>
                        <span style="width:4px;height:4px;background:rgba(255,255,255,0.3);border-radius:50%;display:inline-block;"></span>
                        <span>🏫 {{ $school->schoolName ?? 'Not Assigned' }}</span>
                        <span style="width:4px;height:4px;background:rgba(255,255,255,0.3);border-radius:50%;display:inline-block;"></span>
                        <span>📅 {{ date('d F Y') }}</span>
                    </div>
                </div>
                <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);padding:6px 18px;border-radius:50px;color:#fff;font-size:13px;font-weight:500;border:1px solid rgba(255,255,255,0.08);">
                    <span style="width:8px;height:8px;border-radius:50%;background:#34d399;display:inline-block;animation:pulse-dot 2s infinite;"></span>
                    <span>{{ $principal->status ?? 'Active' }}</span>
                </div>
            </div>
        </div>

        {{-- Profile Body --}}
        <div class="ui-card__body" style="padding:28px 32px;position:relative;z-index:1;">

            {{-- Personal Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:0 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-address-card" style="color:var(--app-primary);"></i> Personal Information
                <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Basic Details</span>
            </h4>
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i> Full Name
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->principalName ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-id-card" style="color:var(--app-primary);font-size:12px;"></i> Principal ID
                        </span>
                        <span style="font-size:14px;font-weight:700;color:var(--app-primary);">{{ $principal->principalID ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-credit-card" style="color:var(--app-primary);font-size:12px;"></i> IC Number
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->ICNumber ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-venus-mars" style="color:var(--app-primary);font-size:12px;"></i> Gender
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->gender ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-flag" style="color:var(--app-primary);font-size:12px;"></i> Race
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->race ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-ring" style="color:var(--app-primary);font-size:12px;"></i> Marital Status
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->maritalStatus ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-envelope" style="color:var(--app-primary);font-size:12px;"></i> Email
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->email ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-phone" style="color:var(--app-primary);font-size:12px;"></i> Phone
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $principal->phoneNumber ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar-plus" style="color:var(--app-primary);font-size:12px;"></i> Appointed Date
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if(isset($principal->appointedDate) && $principal->appointedDate && $principal->appointedDate != 'Not Set')
                                {{ \Carbon\Carbon::parse($principal->appointedDate)->format('d F Y') }}
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-clock" style="color:var(--app-primary);font-size:12px;"></i> Years of Service
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @php
                                $serviceYears = 0;
                                if (isset($principal->appointedDate) && $principal->appointedDate && $principal->appointedDate != 'Not Set') {
                                    $serviceYears = \Carbon\Carbon::parse($principal->appointedDate)->diffInYears(now());
                                }
                            @endphp
                            @if($serviceYears > 0)
                                <strong style="color:var(--app-primary);">{{ $serviceYears }}</strong> years
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- School Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-school" style="color:var(--app-primary);"></i> School Information
                <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Current Assignment</span>
            </h4>
            <div class="row g-0">
                <div class="col-md-4">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-building" style="color:var(--app-primary);font-size:12px;"></i> School Name
                        </span>
                        <span style="font-size:14px;font-weight:700;color:var(--app-text);">{{ $school->schoolName ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-hashtag" style="color:var(--app-primary);font-size:12px;"></i> School ID
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $school->schoolID ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-phone" style="color:var(--app-primary);font-size:12px;"></i> School Phone
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $school->phoneNumber ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--app-primary);font-size:12px;"></i> School Address
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $school->schoolAddress ?? '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- School Statistics --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-chart-bar" style="color:var(--app-primary);"></i> School Statistics
                <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Overview</span>
            </h4>
            <div class="row">
                <div class="col-6 col-md-3">
                    <div style="background:var(--app-primary-soft);border-radius:var(--app-radius-card);padding:12px 16px;text-align:center;border:1px solid var(--app-primary-border);">
                        <div style="font-size:11px;font-weight:700;color:var(--app-text-subtle);text-transform:uppercase;letter-spacing:0.05em;">Total Teachers</div>
                        <div style="font-size:24px;font-weight:900;color:var(--app-primary);">{{ $totalTeachers ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div style="background:#ecfdf5;border-radius:var(--app-radius-card);padding:12px 16px;text-align:center;border:1px solid #a7f3d0;">
                        <div style="font-size:11px;font-weight:700;color:var(--app-text-subtle);text-transform:uppercase;letter-spacing:0.05em;">Active Teachers</div>
                        <div style="font-size:24px;font-weight:900;color:#059669;">{{ $activeTeachers ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div style="background:#fff1f2;border-radius:var(--app-radius-card);padding:12px 16px;text-align:center;border:1px solid #fecdd3;">
                        <div style="font-size:11px;font-weight:700;color:var(--app-text-subtle);text-transform:uppercase;letter-spacing:0.05em;">Resigned Teachers</div>
                        <div style="font-size:24px;font-weight:900;color:#dc2626;">{{ $resignedTeachers ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div style="background:#fffbeb;border-radius:var(--app-radius-card);padding:12px 16px;text-align:center;border:1px solid #fde68a;">
                        <div style="font-size:11px;font-weight:700;color:var(--app-text-subtle);text-transform:uppercase;letter-spacing:0.05em;">Vacancy</div>
                        <div style="font-size:24px;font-weight:900;color:#d97706;">{{ $vacancy ?? 0 }}</div>
                    </div>
                </div>
            </div>

            {{-- Address --}}
            @if(isset($principal->address) && $principal->address && $principal->address != 'Not Set')
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-home" style="color:var(--app-primary);"></i> Home Address
                    <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Contact</span>
                </h4>
                <div style="padding:12px 16px;background:var(--app-background);border-radius:var(--app-radius-card);border:1px solid var(--app-border);">
                    <p style="margin:0;color:var(--app-text);font-size:14px;line-height:1.6;">
                        {{ $principal->address }}
                    </p>
                </div>
            @endif

        </div>
    </div>
</div>

<style>
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(0.8); }
    }
    
    .info-item {
        transition: background 0.2s ease;
    }
    
    .info-item:hover {
        background: var(--app-primary-soft);
    }
    
    @media (max-width: 768px) {
        .info-item {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 2px;
        }
        .info-item span:last-child {
            font-size: 13px !important;
        }
    }
</style>
@endsection