@extends('layouts.app')

@section('title', 'Principal Profile')

@section('content')
<div class="ui-page-shell" style="max-width:1200px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-user-tie" style="color:var(--app-primary);font-size:24px;"></i> Principal Profile
            </h1>
            <p>{{ $principal->principalName }}</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $principal->principalID }}
            </span>
            @php
                $isActive = ($principal->status ?? 'Aktif') == 'Aktif';
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

    {{-- Main Profile Card --}}
    <div class="ui-card ui-card--section" style="position:relative;overflow:hidden;">
        {{-- Background Decoration --}}
        <div style="position:absolute;right:-30px;top:-30px;font-size:160px;opacity:0.04;pointer-events:none;z-index:0;">
            👨‍🏫
        </div>

        {{-- Header --}}
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:24px 28px;position:relative;z-index:1;">
            <div>
                <h3 style="margin:0;font-size:22px;font-weight:800;color:#fff;display:flex;align-items:center;gap:12px;">
                    <i class="fas fa-user-tie"></i> Principal Profile
                </h3>
                <div style="margin-top:8px;display:flex;gap:12px;flex-wrap:wrap;">
                    <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:13px;padding:4px 16px;">
                        <i class="fas fa-id-card"></i> {{ $principal->principalID }}
                    </span>
                    @php
                        $isActive = ($principal->status ?? 'Aktif') == 'Aktif';
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
                <a href="{{ route('principals.edit', $principal->principalID) }}" class="ui-button" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:var(--app-radius-control);padding:6px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('principal.index') }}" class="ui-button" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.2);border-radius:var(--app-radius-control);padding:6px 16px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
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
                            <i class="fas fa-id-card" style="color:var(--app-primary);"></i> Principal ID
                        </div>
                        <div style="font-size:20px;font-weight:800;color:var(--app-primary);">
                            {{ $principal->principalID }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-user" style="color:var(--app-primary);"></i> Full Name
                        </div>
                        <div style="font-size:16px;font-weight:700;color:var(--app-text);">
                            {{ $principal->principalName }}
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
                            {{ $principal->ICNumber ?? '-' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-venus-mars" style="color:var(--app-primary);"></i> Gender
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if($principal->gender)
                                <span class="ui-badge" style="border-color:var(--app-primary-border);background:var(--app-primary-soft);color:var(--app-primary);font-size:12px;">
                                    {{ $principal->gender == 'Male' ? '👨 Male' : ($principal->gender == 'Female' ? '👩 Female' : $principal->gender) }}
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
                            {{ $principal->maritalStatus ?? '-' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-flag" style="color:var(--app-primary);"></i> Race
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $principal->race ?? '-' }}
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
                            {{ $principal->phoneNumber ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-envelope" style="color:var(--app-primary);"></i> Email Address
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $principal->email ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--app-primary);"></i> Address
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $principal->address ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 4: School Assignment --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-school" style="color:var(--app-primary);"></i> School Assignment
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="info-card" style="background:linear-gradient(135deg, var(--app-primary-soft), var(--app-surface));border:2px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:18px 22px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-building" style="color:var(--app-primary);"></i> Assigned School
                        </div>
                        <div style="font-size:18px;font-weight:800;color:var(--app-primary-dark);">
                            {{ $principal->schoolName ?? 'Not assigned' }}
                        </div>
                        @if($principal->schoolID)
                            <div style="font-size:13px;color:var(--app-text-subtle);margin-top:4px;">
                                School ID: {{ $principal->schoolID }}
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
                            {{ $principal->appointedDate ? \Carbon\Carbon::parse($principal->appointedDate)->format('d F Y') : '-' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card" style="background:linear-gradient(135deg, var(--app-primary-soft), var(--app-surface));border:2px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:14px 18px;text-align:center;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                            <i class="fas fa-clock" style="color:var(--app-primary);"></i> Service Years
                        </div>
                        <div style="font-size:20px;font-weight:800;color:var(--app-primary);">
                            @if($principal->serviceDate)
                                {{ $principal->serviceDate }}
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
                            {{ $principal->pensionDate ? \Carbon\Carbon::parse($principal->pensionDate)->format('d F Y') : '-' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section 6: Account Information --}}
            <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                <i class="fas fa-lock" style="color:var(--app-primary);"></i> Account Information
            </h4>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-user-shield" style="color:var(--app-primary);"></i> Role
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            <span class="ui-badge" style="border-color:#c7d2fe;background:#eef2ff;color:#4338ca;font-size:12px;">
                                <i class="fas fa-graduation-cap"></i> {{ ucfirst($principal->role ?? 'Principal') }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-card" style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:14px 18px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                            <i class="fas fa-key" style="color:var(--app-primary);"></i> Password Status
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            @if(($principal->password_change_required ?? 0) == 1)
                                <span class="ui-badge ui-badge--warning" style="font-size:12px;">
                                    <i class="fas fa-exclamation-triangle"></i> Change required on next login
                                </span>
                            @else
                                <span class="ui-badge ui-badge--success" style="font-size:12px;">
                                    <i class="fas fa-check-circle"></i> Already changed
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Divider --}}
            <div style="height:1px;background:linear-gradient(to right, transparent, var(--app-border), transparent);margin:24px 0;"></div>

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
                <a href="{{ route('principals.edit', $principal->principalID) }}" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                @if(($principal->status ?? 'Aktif') == 'Aktif')
                    <form action="{{ route('principals.terminate', $principal->principalID) }}" 
                          method="POST" 
                          style="display:inline-block;"
                          onsubmit="return confirm('Are you sure you want to terminate this principal? This action can be reversed.')">
                        @csrf
                        <button type="submit" class="ui-button" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;background:var(--app-danger);color:#fff;border:1px solid var(--app-danger);border-radius:var(--app-radius-control);font-weight:700;cursor:pointer;">
                            <i class="fas fa-user-slash"></i> Terminate Principal
                        </button>
                    </form>
                @endif
                <a href="{{ route('principal.index') }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection