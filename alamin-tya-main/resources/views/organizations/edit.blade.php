@extends('layouts.app')

@section('title', 'Edit Organization')

@section('content')
<div class="ui-page-shell" style="max-width:800px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-edit" style="color:var(--app-primary);font-size:24px;"></i> Edit Organization
            </h1>
            <p>Update organization information</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $organization->OrganizationID }}
            </span>
        </div>
    </div>

    {{-- Edit Form --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-pen"></i> Edit Organization
            </h5>
        </div>
        <div class="ui-card__body" style="padding:24px;">
            <form method="POST" action="{{ route('orgs.update', $organization->OrganizationID) }}">
                @csrf
                @method('PUT')
                
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label">🆔 Organization ID (Read Only)</label>
                    <input type="text" class="ui-input" value="{{ $organization->OrganizationID }}" readonly style="background:var(--app-divider);color:var(--app-text-secondary);cursor:not-allowed;">
                    <div class="ui-field__helper">Organization ID cannot be changed</div>
                </div>
                
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label required">📛 Organization Name <span style="color:var(--app-danger);">*</span></label>
                    <input type="text" name="OrganizationName" class="ui-input" value="{{ old('OrganizationName', $organization->OrganizationName) }}" required>
                    @error('OrganizationName')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label">📍 Organization Address</label>
                    <textarea name="OrganizationAddress" class="ui-textarea" rows="2">{{ old('OrganizationAddress', $organization->OrganizationAddress) }}</textarea>
                    @error('OrganizationAddress')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label">📅 Register Date</label>
                    <input type="date" name="RegisterDate" class="ui-input" value="{{ old('RegisterDate', $organization->RegisterDate) }}">
                    @error('RegisterDate')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label">📞 Phone Number</label>
                    <input type="text" name="PhoneNumber" class="ui-input" value="{{ old('PhoneNumber', $organization->PhoneNumber) }}" placeholder="Phone Number">
                    @error('PhoneNumber')
                        <div class="ui-field__error">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--app-divider);">
                    <button type="submit" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-save"></i> Update Organization
                    </button>
                    <a href="{{ route('orgs.index') }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .required::after {
        content: "*";
        color: var(--app-danger);
        margin-left: 4px;
        font-weight: 700;
    }
</style>
@endsection