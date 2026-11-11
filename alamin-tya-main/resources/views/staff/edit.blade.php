@extends('layouts.app')
@section('title', 'Edit Staff')

@section('content')
<div class="ui-page-shell" style="max-width:1200px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-edit" style="color:var(--app-primary);font-size:24px;"></i> Edit Staff Profile
            </h1>
            <p>ID: <strong>{{ $staff->staffID }}</strong></p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $staff->staffID }}
            </span>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Please fix the following errors:</strong>
                <ul style="margin:8px 0 0 20px;padding:0;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Edit Form --}}
    <div class="ui-card ui-card--section" style="position:relative;overflow:hidden;">
        {{-- Background Decoration --}}
        <div style="position:absolute;right:-30px;top:-30px;font-size:160px;opacity:0.04;pointer-events:none;z-index:0;">
            ✏️
        </div>

        {{-- Form Body --}}
        <div class="ui-card__body" style="padding:28px;position:relative;z-index:1;">
            <form action="{{ route('staff.update', $staff->staffID) }}" method="POST" id="staffForm">
                @csrf
                @method('PUT')

                {{-- Section 1: Basic Information --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:0 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-address-card" style="color:var(--app-primary);"></i> Basic Information
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-id-card"></i> Staff ID (Read-only)</label>
                            <input type="text" class="ui-input" value="{{ $staff->staffID }}" readonly style="background:var(--app-divider);color:var(--app-text-secondary);cursor:not-allowed;">
                            <input type="hidden" name="staffID" value="{{ $staff->staffID }}">
                            <div class="ui-field__helper">Staff ID cannot be changed</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label required"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" 
                                   name="staffName"
                                   class="ui-input"
                                   value="{{ old('staffName', $staff->staffName) }}" 
                                   required
                                   placeholder="Enter staff's full name">
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label required"><i class="fas fa-credit-card"></i> IC Number</label>
                            <input type="text" 
                                   name="ICNumber"
                                   class="ui-input"
                                   value="{{ old('ICNumber', $staff->ICNumber) }}" 
                                   required
                                   placeholder="Enter IC number (e.g., 750101101234)">
                            <div class="ui-field__helper">Format: YYMMDD-XX-XXXX</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-toggle-on"></i> Status</label>
                            <select name="status" class="ui-select">
                                <option value="Aktif" {{ (old('status', $staff->status ?? 'Aktif') == 'Aktif') ? 'selected' : '' }}>
                                    ✅ Active
                                </option>
                                <option value="Berhenti" {{ (old('status', $staff->status ?? 'Aktif') == 'Berhenti') ? 'selected' : '' }}>
                                    ⏹️ Stopped / Resigned
                                </option>
                            </select>
                            <div class="ui-field__helper">Current employment status</div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Personal Details --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-user-circle" style="color:var(--app-primary);"></i> Personal Details
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-venus-mars"></i> Gender</label>
                            <select name="gender" class="ui-select">
                                <option value="">Select Gender</option>
                                <option value="Male" {{ old('gender', $staff->gender) == 'Male' ? 'selected' : '' }}>👨 Male</option>
                                <option value="Female" {{ old('gender', $staff->gender) == 'Female' ? 'selected' : '' }}>👩 Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-ring"></i> Marital Status</label>
                            <select name="maritalStatus" class="ui-select">
                                <option value="">Select Status</option>
                                <option value="Single" {{ old('maritalStatus', $staff->maritalStatus) == 'Single' ? 'selected' : '' }}>💑 Single</option>
                                <option value="Married" {{ old('maritalStatus', $staff->maritalStatus) == 'Married' ? 'selected' : '' }}>💒 Married</option>
                                <option value="Divorced" {{ old('maritalStatus', $staff->maritalStatus) == 'Divorced' ? 'selected' : '' }}>💔 Divorced</option>
                                <option value="Widowed" {{ old('maritalStatus', $staff->maritalStatus) == 'Widowed' ? 'selected' : '' }}>🕊️ Widowed</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-flag"></i> Race</label>
                            <select name="race" class="ui-select">
                                <option value="">Select Race</option>
                                <option value="Malay" {{ old('race', $staff->race) == 'Malay' ? 'selected' : '' }}>Malay</option>
                                <option value="Chinese" {{ old('race', $staff->race) == 'Chinese' ? 'selected' : '' }}>Chinese</option>
                                <option value="Indian" {{ old('race', $staff->race) == 'Indian' ? 'selected' : '' }}>Indian</option>
                                <option value="Other" {{ old('race', $staff->race) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-birthday-cake"></i> Age</label>
                            <input type="number" 
                                   name="latestAge" 
                                   class="ui-input" 
                                   value="{{ old('latestAge', $staff->latestAge) }}"
                                   min="0"
                                   max="120"
                                   placeholder="Enter age in years">
                            <div class="ui-field__helper">Age in years</div>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Contact Information --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-address-book" style="color:var(--app-primary);"></i> Contact Information
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="text" 
                                   name="phoneNumber"
                                   class="ui-input"
                                   value="{{ old('phoneNumber', $staff->phoneNumber) }}"
                                   placeholder="e.g., 012-3456789">
                            <div class="ui-field__helper">Include area code for landline numbers</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" 
                                   name="email"
                                   class="ui-input"
                                   value="{{ old('email', $staff->email) }}"
                                   placeholder="staff@school.edu.my">
                            <div class="ui-field__helper">Valid email for official communication</div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-map-marker-alt"></i> Address</label>
                            <textarea name="address" 
                                      class="ui-textarea" 
                                      rows="3"
                                      placeholder="Enter full address (Street, City, State, Postal Code)">{{ old('address', $staff->address) }}</textarea>
                            <div class="ui-field__helper">Complete residential or correspondence address</div>
                        </div>
                    </div>
                </div>

                {{-- Section 4: Work Information --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-briefcase" style="color:var(--app-primary);"></i> Work Information
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label required"><i class="fas fa-building"></i> Department</label>
                            <input type="text"
                                   name="department"
                                   class="ui-input"
                                   value="{{ old('department', $staff->department) }}"
                                   required
                                   placeholder="e.g., Administration, Finance, IT">
                            <div class="ui-field__helper">Department where staff member works</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-clock"></i> Credit Hour</label>
                            <input type="number"
                                   name="credit_hour"
                                   class="ui-input"
                                   value="{{ old('credit_hour', $staff->credit_hour ?? 0) }}"
                                   min="0"
                                   placeholder="Enter credit hours">
                            <div class="ui-field__helper">Total credit hours assigned</div>
                        </div>
                    </div>
                </div>

                {{-- Section 5: Service Timeline --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Service Timeline
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-calendar-plus"></i> Appointed Date</label>
                            <input type="date" 
                                   name="appointedDate" 
                                   class="ui-input" 
                                   value="{{ old('appointedDate', $staff->appointedDate) }}">
                            <div class="ui-field__helper">Date when appointed as staff</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-clock"></i> Service Years</label>
                            <input type="number" 
                                   name="serviceDate" 
                                   class="ui-input" 
                                   value="{{ old('serviceDate', $staff->serviceDate) }}"
                                   min="0"
                                   step="1"
                                   placeholder="Enter years of service">
                            <div class="ui-field__helper">Total years of service</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-calendar-minus"></i> Pension Date</label>
                            <input type="date" 
                                   name="pensionDate" 
                                   class="ui-input" 
                                   value="{{ old('pensionDate', $staff->pensionDate) }}">
                            <div class="ui-field__helper">Expected or actual pension date</div>
                        </div>
                    </div>
                </div>

                {{-- Divider --}}
                <div style="height:1px;background:linear-gradient(to right, transparent, var(--app-border), transparent);margin:24px 0;"></div>

                {{-- Action Buttons --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <button type="submit" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
                    <a href="{{ route('staff.show', $staff->staffID) }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="{{ route('staff.list') }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" form="deleteStaffForm" class="ui-button" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;background:var(--app-danger);color:#fff;border:1px solid var(--app-danger);border-radius:var(--app-radius-control);font-weight:700;cursor:pointer;margin-left:auto;">
                        <i class="fas fa-trash"></i> Delete Staff
                    </button>
                </div>
            </form>
            {{-- Separate form (forms cannot be nested); triggered by the Delete button above --}}
            <form id="deleteStaffForm" action="{{ route('staff.destroy', $staff->staffID) }}" method="POST" style="display:none;"
                  onsubmit="return confirm('Are you sure you want to permanently delete this staff member? This cannot be undone.')">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<script>
    // Unsaved changes warning
    let formChanged = false;
    const form = document.getElementById('staffForm');
    const inputs = document.querySelectorAll('#staffForm input, #staffForm select, #staffForm textarea');
    
    inputs.forEach(function(input) {
        if (input.type !== 'hidden') {
            input.addEventListener('change', function() {
                formChanged = true;
            });
            input.addEventListener('input', function() {
                formChanged = true;
            });
        }
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
    
    form.addEventListener('submit', function() {
        formChanged = false;
    });

    // Don't show the 'unsaved changes' prompt when deleting
    document.getElementById('deleteStaffForm').addEventListener('submit', function(e) {
        if (!e.defaultPrevented) formChanged = false;
    });

    // Auto-calculate service years when appointed date changes
    document.addEventListener('DOMContentLoaded', function() {
        var appointedDateInput = document.querySelector('input[name="appointedDate"]');
        var serviceYearsInput = document.querySelector('input[name="serviceDate"]');

        if (appointedDateInput && serviceYearsInput) {
            appointedDateInput.addEventListener('change', function() {
                if (this.value) {
                    var appointedDate = new Date(this.value);
                    var today = new Date();
                    var years = today.getFullYear() - appointedDate.getFullYear();
                    var m = today.getMonth() - appointedDate.getMonth();
                    if (m < 0 || (m === 0 && today.getDate() < appointedDate.getDate())) {
                        years--;
                    }
                    if (years > 0) {
                        serviceYearsInput.value = years;
                        serviceYearsInput.style.background = 'var(--app-primary-soft)';
                        serviceYearsInput.style.borderColor = 'var(--app-primary-border)';
                        serviceYearsInput.style.color = 'var(--app-primary)';
                    }
                }
            });
        }
    });
</script>

<style>
    .required::after {
        content: "*";
        color: var(--app-danger);
        margin-left: 4px;
        font-weight: 700;
    }
</style>
@endsection