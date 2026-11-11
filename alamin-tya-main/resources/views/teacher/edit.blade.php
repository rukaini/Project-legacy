@extends('layouts.app')
@section('title', 'Edit Teacher')

@section('content')
<div class="ui-page-shell" style="max-width:1200px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-edit" style="color:var(--app-primary);font-size:24px;"></i> Edit Teacher Profile
            </h1>
            <p>ID: <strong>{{ $teacher->teacherID }}</strong></p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-id-card"></i> {{ $teacher->teacherID }}
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
            <form action="{{ route('teachers.update', $teacher->teacherID) }}" method="POST" id="teacherForm">
                @csrf
                @method('PUT')

                {{-- Section 1: Basic Information --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:0 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-address-card" style="color:var(--app-primary);"></i> Basic Information
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-id-card"></i> Teacher ID (Read-only)</label>
                            <input type="text" class="ui-input" value="{{ $teacher->teacherID }}" readonly style="background:var(--app-divider);color:var(--app-text-secondary);cursor:not-allowed;">
                            <input type="hidden" name="teacherID" value="{{ $teacher->teacherID }}">
                            <div class="ui-field__helper">Teacher ID cannot be changed</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label required"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" 
                                   name="teacherName"
                                   class="ui-input"
                                   value="{{ old('teacherName', $teacher->teacherName) }}" 
                                   required
                                   placeholder="Enter teacher's full name">
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
                                   value="{{ old('ICNumber', $teacher->ICNumber) }}" 
                                   required
                                   placeholder="Enter IC number (e.g., 750101101234)">
                            <div class="ui-field__helper">Format: YYMMDD-XX-XXXX</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-toggle-on"></i> Status</label>
                            <select name="status" class="ui-select">
                                <option value="Aktif" {{ (old('status', $teacher->status ?? 'Aktif') == 'Aktif') ? 'selected' : '' }}>
                                    ✅ Active
                                </option>
                                <option value="Berhenti" {{ (old('status', $teacher->status ?? 'Aktif') == 'Berhenti') ? 'selected' : '' }}>
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
                                <option value="Male" {{ old('gender', $teacher->gender) == 'Male' ? 'selected' : '' }}>👨 Male</option>
                                <option value="Female" {{ old('gender', $teacher->gender) == 'Female' ? 'selected' : '' }}>👩 Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-ring"></i> Marital Status</label>
                            <select name="maritalStatus" class="ui-select">
                                <option value="">Select Status</option>
                                <option value="Single" {{ old('maritalStatus', $teacher->maritalStatus) == 'Single' ? 'selected' : '' }}>💑 Single</option>
                                <option value="Married" {{ old('maritalStatus', $teacher->maritalStatus) == 'Married' ? 'selected' : '' }}>💒 Married</option>
                                <option value="Divorced" {{ old('maritalStatus', $teacher->maritalStatus) == 'Divorced' ? 'selected' : '' }}>💔 Divorced</option>
                                <option value="Widowed" {{ old('maritalStatus', $teacher->maritalStatus) == 'Widowed' ? 'selected' : '' }}>🕊️ Widowed</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-flag"></i> Race</label>
                            <select name="race" id="raceSelect" class="ui-select">
                                <option value="">Select Race</option>
                                <option value="Malay" {{ old('race', $teacher->race) == 'Malay' ? 'selected' : '' }}>Malay</option>
                                <option value="Chinese" {{ old('race', $teacher->race) == 'Chinese' ? 'selected' : '' }}>Chinese</option>
                                <option value="Indian" {{ old('race', $teacher->race) == 'Indian' ? 'selected' : '' }}>Indian</option>
                                <option value="Other" {{ old('race', $teacher->race) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-birthday-cake"></i> Age</label>
                            <input type="number" 
                                   name="latestAge" 
                                   class="ui-input" 
                                   value="{{ old('latestAge', $teacher->latestAge) }}"
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
                                   value="{{ old('phoneNumber', $teacher->phoneNumber) }}"
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
                                   value="{{ old('email', $teacher->email) }}"
                                   placeholder="teacher@school.edu.my">
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
                                      placeholder="Enter full address (Street, City, State, Postal Code)">{{ old('address', $teacher->address) }}</textarea>
                            <div class="ui-field__helper">Complete residential or correspondence address</div>
                        </div>
                    </div>
                </div>

                {{-- Section 4: School Assignment --}}
                <h4 style="font-size:14px;font-weight:800;color:var(--app-text);margin:24px 0 16px 0;display:flex;align-items:center;gap:8px;padding-bottom:10px;border-bottom:2px solid var(--app-divider);">
                    <i class="fas fa-school" style="color:var(--app-primary);"></i> School Assignment
                </h4>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="ui-field">
                            <label class="ui-label required"><i class="fas fa-building"></i> Assigned School</label>
                            <select name="schoolID" class="ui-select" required>
                                <option value="">Select School</option>
                                @foreach($schools as $school)
                                    <option value="{{ $school->schoolID }}" 
                                        {{ old('schoolID', $teacher->schoolID) == $school->schoolID ? 'selected' : '' }}>
                                        {{ $school->schoolName }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="ui-field__helper">School where teacher is currently assigned</div>
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
                                   value="{{ old('appointedDate', $teacher->appointedDate) }}">
                            <div class="ui-field__helper">Date when teacher was appointed</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ui-field">
                            <label class="ui-label"><i class="fas fa-clock"></i> Service Years</label>
                            <input type="number" 
                                   name="serviceDate" 
                                   class="ui-input" 
                                   value="{{ old('serviceDate', $teacher->serviceDate) }}"
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
                                   value="{{ old('pensionDate', $teacher->pensionDate) }}">
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
                    <a href="{{ route('teachers.show', $teacher->teacherID) }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <a href="{{ route('teachers.index') }}" class="ui-button ui-button--compact" style="min-height:44px;padding:0 24px;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                    <button type="submit" form="deleteTeacherForm" class="ui-button" style="min-height:44px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;background:var(--app-danger);color:#fff;border:1px solid var(--app-danger);border-radius:var(--app-radius-control);font-weight:700;cursor:pointer;margin-left:auto;">
                        <i class="fas fa-trash"></i> Delete Teacher
                    </button>
                </div>
            </form>
            {{-- Separate form (forms cannot be nested); triggered by the Delete button above --}}
            <form id="deleteTeacherForm" action="{{ route('teachers.destroy', $teacher->teacherID) }}" method="POST" style="display:none;"
                  onsubmit="return confirm('Are you sure you want to permanently delete this teacher? This cannot be undone.')">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>

<script>
    // Unsaved changes warning
    let formChanged = false;
    const form = document.getElementById('teacherForm');
    const inputs = document.querySelectorAll('#teacherForm input, #teacherForm select, #teacherForm textarea');
    
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
    document.getElementById('deleteTeacherForm').addEventListener('submit', function(e) {
        if (!e.defaultPrevented) formChanged = false;
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