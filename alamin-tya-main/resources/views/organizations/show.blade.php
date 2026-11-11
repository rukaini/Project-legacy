@extends('layouts.app')

@section('title', 'Organization Details')

@section('content')
<div class="ui-page-shell" style="max-width:1000px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-building" style="color:var(--app-primary);font-size:24px;"></i> Organization Details
            </h1>
            <p>{{ $organization->OrganizationName }}</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $organization->OrganizationID }}
            </span>
            <a href="{{ route('orgs.index') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-info-circle"></i> Organization Information
            </h5>
            <div style="display:flex;gap:8px;">
                <a href="{{ route('orgs.edit', $organization->OrganizationID) }}" class="ui-button" style="background:rgba(255,255,255,0.2);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:var(--app-radius-control);padding:4px 14px;font-size:12px;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;cursor:pointer;">
                    <i class="fas fa-edit"></i> Edit
                </a>
            </div>
        </div>
        <div class="ui-card__body" style="padding:24px;">
            <div class="row g-4">
                <div class="col-md-6">
                    <div style="background:var(--app-primary-soft);border:2px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-id-card" style="color:var(--app-primary);"></i> Organization ID
                        </div>
                        <div style="font-size:20px;font-weight:800;color:var(--app-primary);">
                            {{ $organization->OrganizationID }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-user" style="color:var(--app-primary);"></i> Organization Name
                        </div>
                        <div style="font-size:16px;font-weight:700;color:var(--app-text);">
                            {{ $organization->OrganizationName }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-map-marker-alt" style="color:var(--app-primary);"></i> Address
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $organization->OrganizationAddress ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-phone" style="color:var(--app-primary);"></i> Phone Number
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $organization->PhoneNumber ?? 'Not provided' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;">
                        <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;display:flex;align-items:center;gap:6px;margin-bottom:6px;">
                            <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Register Date
                        </div>
                        <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                            {{ $organization->RegisterDate ? \Carbon\Carbon::parse($organization->RegisterDate)->format('d F Y') : 'Not provided' }}
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--app-divider);display:flex;gap:12px;flex-wrap:wrap;">
                <a href="{{ route('orgs.edit', $organization->OrganizationID) }}" class="ui-button ui-button--primary" style="min-height:40px;padding:0 24px;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-edit"></i> Edit Organization
                </a>
                <a href="{{ route('orgs.index') }}" class="ui-button ui-button--compact" style="min-height:40px;padding:0 20px;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection