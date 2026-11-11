@extends('layouts.app')

@section('title', 'Staff Profile')

@section('content')
<div class="ui-page-shell" style="max-width:1200px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-user-tie" style="color:var(--app-primary);font-size:24px;"></i> Staff Profile
            </h1>
            <p>{{ $staff->staffName }}</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $staff->staffID }}
            </span>
            @php
                $status = $staff->status ?? 'active';
                $isActive = in_array($status, ['active', 'Active', 'AKTIF', 'Aktif']);
            @endphp
            @if($isActive)
                <span class="ui-badge ui-badge--success">
                    <i class="fas fa-circle" style="font-size:8px;"></i> Active
                </span>
            @else
                <span class="ui-badge ui-badge--danger">
                    <i class="fas fa-circle" style="font-size:8px;"></i> Inactive
                </span>
            @endif
        </div>
    </div>

    {{-- Warning for temporary IC --}}
    @if(isset($staff->ICNumber) && strpos($staff->ICNumber, 'TEMP_') !== false)
        <div class="ui-message" style="border-color:#fde68a;background:#fffbeb;color:#92400e;margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle" style="color:var(--app-warning);"></i>
            <div>
                <strong>Temporary IC Number:</strong> This staff member has a temporary IC number ({{ $staff->ICNumber }}). 
                Please update with the actual IC number.
            </div>
        </div>
    @endif

    {{-- Main Profile Card --}}
    <div class="ui-card ui-card--section" style="position:relative;overflow:hidden;">
        {{-- Background Decoration --}}
        <div style="position:absolute;right:-30px;top:-30px;font-size:160px;opacity:0.04;pointer-events:none;z-index:0;">
            👥
        </div>

        {{-- Header --}}
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:24px 28px;position:relative;z-index:1;">
            <div>
                <h3 style="margin:0;font-size:22px;font-weight:800;color:#fff;display:flex;align-items:center;gap:12px;">
                    <i class="fas fa-user-tie"></i> Staff Profile
                </h3>
                <div style="margin-top:8px;display:flex;gap:12px;flex-wrap:wrap;">
                    <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:13px;padding:4px 16px;">
                        <i class="fas fa-id-card"></i> {{ $staff->staffID }}
                    </span>
                    @php
                        $status = $staff->status ?? 'active';
                        $isActive = in_array($status, ['active', 'Active', 'AKTIF', 'Aktif']);
                    @endphp
                    @if($isActive)
                        <span class="ui-badge" style="background:rgba(16,185,129,0.3);border-color:rgba(16,185,129,0.4);color:#a7f3d0;font-size:13px;padding:4px 16px;">
                            <i class="fas fa-circle" style="font-size:8px;color:#34d399;"></i> Active
                        </span>
                    @else
                        <span class="ui-badge" style="background:rgba(239,68,68,0.3);border-color:rgba(239,68,68,0.4);color:#fca5a5;font-size:13px;padding:4px 16px;">
                            <i class="fas fa-circle" style="font-size:8px;color:#f87171;"></i> Inactive
                        </span>
                    @endif
                </div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('staff.edit', $staff->staffID) }}" class="ui-button" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:var(--app-radius-control);padding:6px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('staff.list') }}" class="ui-button" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.2);border-radius:var(--app-radius-control);padding:6px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {{-- Body --}}
        <div class="ui-card__body" style="padding:28px;position:relative;z-index:1;">
            
            {{-- Section 1: Basic Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:0 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-address-card" style="color:var(--app-primary);"></i> Basic Information
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-primary-soft);border:2px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-id-card" style="color:var(--app-primary);"></i> Staff ID
                        </div>
                        <div style="font-size:20px;font-weight:800;color:var(--app-primary);">
                            {{ $staff->staffID }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-user" style="color:var(--app-primary);"></i> Full Name
                        </div>
                        <div style="font-size:16px;font-weight:700;color:var(--app-text);">
                            {{ $staff->staffName }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 2: Personal Details --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-user-circle" style="color:var(--app-primary);"></i> Personal Details
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-credit-card" style="color:var(--app-primary);"></i> IC Number
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if(isset($staff->ICNumber) && strpos($staff->ICNumber, 'TEMP_') !== false)
                                <span class="ui-badge ui-badge--warning" style="font-size:12px;">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $staff->ICNumber }}
                                </span>
                            @else
                                {{ $staff->ICNumber ?? '-' }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-venus-mars" style="color:var(--app-primary);"></i> Gender
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($staff->gender)
                                <span class="ui-badge" style="border-color:var(--app-primary-border);background:var(--app-primary-soft);color:var(--app-primary);font-size:12px;">
                                    {{ $staff->gender == 'Male' ? '👨 Male' : ($staff->gender == 'Female' ? '👩 Female' : $staff->gender) }}
                                </span>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-ring" style="color:var(--app-primary);"></i> Marital Status
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($staff->maritalStatus == 'Single')
                                💑 Single
                            @elseif($staff->maritalStatus == 'Married')
                                💒 Married
                            @elseif($staff->maritalStatus == 'Divorced')
                                💔 Divorced
                            @elseif($staff->maritalStatus == 'Widowed')
                                🕊️ Widowed
                            @else
                                {{ $staff->maritalStatus ?? '-' }}
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-flag" style="color:var(--app-primary);"></i> Race
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $staff->race ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 3: Contact Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-address-book" style="color:var(--app-primary);"></i> Contact Information
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-phone" style="color:var(--app-primary);"></i> Phone Number
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $staff->phoneNumber ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-envelope" style="color:var(--app-primary);"></i> Email Address
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $staff->email ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--app-primary);"></i> Address
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $staff->address ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4: Department --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-building" style="color:var(--app-primary);"></i> Department
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="info-card" style="background:linear-gradient(135deg, var(--app-primary-soft), var(--app-surface));border:2px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:18px 22px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-folder" style="color:var(--app-primary);"></i> Assigned Department
                        </div>
                        <div style="font-size:18px;font-weight:800;color:var(--app-primary-dark);">
                            {{ $staff->department ?? 'Not assigned' }}
                        </div>
                        @if($staff->credit_hour)
                            <div style="margin-top:8px;">
                                <span class="ui-badge" style="border-color:#fde68a;background:#fffbeb;color:#d97706;font-size:12px;">
                                    <i class="fas fa-clock"></i> {{ $staff->credit_hour }} credit hours
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Section 5: Service Timeline --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Service Timeline
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;text-align:center;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                            <i class="fas fa-calendar-plus" style="color:var(--app-primary);"></i> Appointed Date
                        </div>
                        <div style="font-size:15px;font-weight:700;color:var(--app-text);">
                            {{ $staff->appointedDate ? \Carbon\Carbon::parse($staff->appointedDate)->format('d F Y') : '-' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card" style="background:linear-gradient(135deg, var(--app-primary-soft), var(--app-surface));border:2px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:14px 18px;text-align:center;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                            <i class="fas fa-clock" style="color:var(--app-primary);"></i> Service Years
                        </div>
                        <div style="font-size:20px;font-weight:800;color:var(--app-primary);">
                            @if($staff->serviceDate)
                                {{ $staff->serviceDate }}
                            @else
                                -
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;text-align:center;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                            <i class="fas fa-calendar-minus" style="color:var(--app-primary);"></i> Pension Date
                        </div>
                        <div style="font-size:15px;font-weight:700;color:var(--app-text);">
                            {{ $staff->pensionDate ? \Carbon\Carbon::parse($staff->pensionDate)->format('d F Y') : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:linear-gradient(to right, transparent, var(--app-border), transparent);margin:24px 0;"></div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                <a href="{{ route('staff.edit', $staff->staffID) }}" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                @php
                    $currentStatus = $staff->status ?? 'active';
                    $isActive = in_array($currentStatus, ['active', 'Active', 'AKTIF', 'Aktif']);
                @endphp
                @if($isActive)
                    <form action="{{ route('staff.terminate', $staff->staffID) }}" 
                          method="POST" 
                          style="display:inline-block;"
                          onsubmit="return confirm('Are you sure you want to terminate this staff member? This action can be reversed.')">
                        @csrf
                        <button type="submit" class="ui-button" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;background:var(--app-danger);color:#fff;border:1px solid var(--app-danger);border-radius:var(--app-radius-control);font-weight:700;cursor:pointer;">
                            <i class="fas fa-user-slash"></i> Terminate Staff
                        </button>
                    </form>
                @endif
                <a href="{{ route('staff.list') }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-hide alert after 5 seconds
    setTimeout(function() {
        var alert = document.querySelector('.ui-message[style*="border-color:#fde68a"]');
        if(alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                if(alert.parentElement) alert.remove();
            }, 500);
        }
    }, 5000);
</script>
@endsection