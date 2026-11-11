@extends('layouts.app')

@section('title', 'My Profile - Staff')

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
                <i class="fas fa-id-card"></i> {{ $staffData['staffID'] ?? '—' }}
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
                    {{ strtoupper(substr($staffData['staffName'] ?? 'S', 0, 1)) }}
                </div>
                <div style="flex:1;">
                    <h1 style="margin:0;font-size:28px;font-weight:800;color:#fff;letter-spacing:-0.5px;">
                        {{ $staffData['staffName'] ?? 'Staff Member' }}
                    </h1>
                    <div style="color:rgba(255,255,255,0.7);font-size:14px;margin-top:4px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <span>🆔 {{ $staffData['staffID'] ?? '—' }}</span>
                        <span style="width:4px;height:4px;background:rgba(255,255,255,0.3);border-radius:50%;display:inline-block;"></span>
                        <span>🏢 {{ $staffData['department'] ?? 'Not Assigned' }}</span>
                        <span style="width:4px;height:4px;background:rgba(255,255,255,0.3);border-radius:50%;display:inline-block;"></span>
                        <span>📅 {{ date('d F Y') }}</span>
                    </div>
                </div>
                <div style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,0.12);backdrop-filter:blur(10px);padding:6px 18px;border-radius:50px;color:#fff;font-size:13px;font-weight:500;border:1px solid rgba(255,255,255,0.08);">
                    <span style="width:8px;height:8px;border-radius:50%;background:#34d399;display:inline-block;animation:pulse-dot 2s infinite;"></span>
                    <span>Active</span>
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
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['staffName'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-id-card" style="color:var(--app-primary);font-size:12px;"></i> Staff ID
                        </span>
                        <span style="font-size:14px;font-weight:700;color:var(--app-primary);">{{ $staffData['staffID'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-credit-card" style="color:var(--app-primary);font-size:12px;"></i> IC Number
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['ICNumber'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-venus-mars" style="color:var(--app-primary);font-size:12px;"></i> Gender
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['gender'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-flag" style="color:var(--app-primary);font-size:12px;"></i> Race
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['race'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-ring" style="color:var(--app-primary);font-size:12px;"></i> Marital Status
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['maritalStatus'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-envelope" style="color:var(--app-primary);font-size:12px;"></i> Email
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['email'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-phone" style="color:var(--app-primary);font-size:12px;"></i> Phone
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['phoneNumber'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar-plus" style="color:var(--app-primary);font-size:12px;"></i> Appointed Date
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if(isset($staffData['appointedDate']) && $staffData['appointedDate'] && $staffData['appointedDate'] != 'Not Set')
                                {{ $staffData['appointedDate'] }}
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
                            @if($serviceYears)
                                <strong style="color:var(--app-primary);">{{ $serviceYears }}</strong> years
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Department Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-building" style="color:var(--app-primary);"></i> Department Information
                <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Current Assignment</span>
            </h4>
            <div class="row g-0">
                <div class="col-md-4">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-folder" style="color:var(--app-primary);font-size:12px;"></i> Department
                        </span>
                        <span style="font-size:14px;font-weight:700;color:var(--app-text);">{{ $staffData['department'] ?? '—' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-user-tag" style="color:var(--app-primary);font-size:12px;"></i> Role
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">{{ $staffData['role'] ?? 'Staff' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-clock" style="color:var(--app-primary);font-size:12px;"></i> Credit Hours
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            <span class="ui-badge" style="border-color:#fde68a;background:#fffbeb;color:#d97706;font-size:12px;">
                                <i class="fas fa-hourglass-half"></i> {{ $staffData['credit_hour'] ?? '0' }} hours
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Pension Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-clock" style="color:var(--app-primary);"></i> Pension Information
                <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Retirement Planning</span>
            </h4>
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar-minus" style="color:var(--app-primary);font-size:12px;"></i> Pension Date
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($pensionDate && $pensionDate != 'Not Set')
                                <strong style="color:var(--app-primary);">{{ $pensionDate }}</strong>
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-calendar-plus" style="color:var(--app-primary);font-size:12px;"></i> Service Start Date
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($serviceStartDate && $serviceStartDate != 'Not Set')
                                {{ $serviceStartDate }}
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-hourglass-half" style="color:var(--app-primary);font-size:12px;"></i> Days Until Pension
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($daysUntilPension !== null && $daysUntilPension > 0)
                                <strong style="color:var(--app-primary);">{{ number_format($daysUntilPension) }}</strong> days
                            @elseif($daysUntilPension !== null && $daysUntilPension <= 0)
                                <span class="ui-badge ui-badge--success" style="font-size:12px;">
                                    <i class="fas fa-check-circle"></i> Retired
                                </span>
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-item" style="display:flex;justify-content:space-between;padding:10px 12px;border-bottom:1px solid var(--app-divider);">
                        <span style="font-size:13px;font-weight:500;color:var(--app-text-subtle);display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-clipboard-check" style="color:var(--app-primary);font-size:12px;"></i> Pension Status
                        </span>
                        <span style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($pensionStatus && $pensionStatus != 'Not Set')
                                @php
                                    $statusClass = '';
                                    $statusColor = '';
                                    if($pensionStatusClass == 'success') {
                                        $statusClass = 'ui-badge--success';
                                        $statusColor = '#34d399';
                                    } elseif($pensionStatusClass == 'warning') {
                                        $statusClass = 'ui-badge--warning';
                                        $statusColor = '#f59e0b';
                                    } elseif($pensionStatusClass == 'danger') {
                                        $statusClass = 'ui-badge--danger';
                                        $statusColor = '#ef4444';
                                    } else {
                                        $statusClass = 'ui-badge--info';
                                        $statusColor = '#3b82f6';
                                    }
                                @endphp
                                <span class="ui-badge {{ $statusClass }}" style="font-size:12px;">
                                    <i class="fas fa-circle" style="font-size:8px;color:{{ $statusColor }};"></i>
                                    {{ $pensionStatus }}
                                </span>
                            @else
                                <span style="color:var(--app-text-subtle);font-weight:400;font-style:italic;font-size:13px;">—</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            {{-- Home Address --}}
            @if(isset($staffData['address']) && $staffData['address'] && $staffData['address'] != 'Not Set')
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-home" style="color:var(--app-primary);"></i> Home Address
                    <span style="font-size:10px;font-weight:600;padding:2px 12px;border-radius:50px;background:var(--app-primary-soft);color:var(--app-primary-dark);margin-left:auto;">Contact</span>
                </h4>
                <div style="padding:12px 16px;background:var(--app-background);border-radius:var(--app-radius-card);border:1px solid var(--app-border);">
                    <p style="margin:0;color:var(--app-text);font-size:14px;line-height:1.6;">
                        {{ $staffData['address'] }}
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