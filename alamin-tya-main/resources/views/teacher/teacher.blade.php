@extends('layouts.app')

@section('title', 'Teacher List')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-chalkboard-user" style="color:var(--app-primary);font-size:24px;"></i> Teacher List
            </h1>
            <p>Manage all registered teachers</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-user"></i> {{ isset($teachers) ? $teachers->count() : 0 }} Teachers
            </span>
            <button type="button" class="ui-button ui-button--primary" onclick="openModal('registerModal')" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <i class="fas fa-plus"></i> Register
            </button>
            <button type="button" class="ui-button ui-button--outline" onclick="openModal('csvModal')" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <i class="fas fa-file-import"></i> Import CSV
            </button>
            <a href="{{ route('teachers.template') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-download"></i> Template
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('status'))
        <div class="ui-message" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('status') }}</span>
            </div>
            <button type="button" class="ui-button ui-button--icon" style="width:28px;height:28px;font-size:14px;" onclick="this.closest('.ui-message').remove();">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('success'))
        <div class="ui-message" style="border-color:#a7f3d0;background:#ecfdf5;color:#065f46;margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:8px;">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" class="ui-button ui-button--icon" style="width:28px;height:28px;font-size:14px;" onclick="this.closest('.ui-message').remove();">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')) > 0)
        <div class="ui-message" style="border-color:#fde68a;background:#fffbeb;color:#92400e;margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle" style="color:var(--app-warning);"></i>
            <div>
                <strong>Import completed with errors:</strong>
                <ul style="margin:8px 0 0 20px;padding:0;max-height:300px;overflow-y:auto;">
                    @foreach(session('import_errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Search Section --}}
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;align-items:center;justify-content:space-between;">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
            <div style="position:relative;flex:1;min-width:200px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--app-text-subtle);font-size:14px;"></i>
                <input type="text" name="search" class="ui-input" placeholder="Search by name, ID, IC or email..." value="{{ request('search') }}" style="padding-left:36px;height:40px;">
            </div>
            <button type="submit" class="ui-button ui-button--primary" style="height:40px;padding:0 20px;">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request('search'))
                <a href="{{ route('teachers.index') }}" class="ui-button ui-button--compact" style="height:40px;padding:0 16px;">
                    <i class="fas fa-times"></i> Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Teachers Grouped by School --}}
    @if(isset($teachers) && count($teachers) > 0)
        @foreach($teachers as $schoolID => $group)
            <div style="margin-bottom:24px;">
                {{-- School Header --}}
                <div style="background:linear-gradient(135deg, #1e3a5f, #1e40af);color:#fff;padding:12px 20px;border-radius:var(--app-radius-card) var(--app-radius-card) 0 0;font-weight:700;font-size:14px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-school"></i>
                    {{ $group->first()->schoolName ?? $schoolID }}
                    <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:11px;padding:2px 12px;margin-left:auto;">
                        <i class="fas fa-user"></i> {{ $group->count() }} Teachers
                    </span>
                </div>

                {{-- Teacher Table --}}
                <div class="ui-card ui-card--section" style="border-radius:0 0 var(--app-radius-card) var(--app-radius-card);">
                    <div class="ui-card__body" style="padding:0;">
                        <div class="ui-table-wrap">
                            <table class="ui-table ui-table--dashboard">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>IC Number</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($group as $teacher)
                                    @php
                                        $status = $teacher->status ?? 'Aktif';
                                        $isActive = ($status == 'Aktif' || $status == 'Active' || $status == 'active' || $status == null);
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                                {{ $teacher->teacherID }}
                                            </span>
                                        </td>
                                        <td style="font-weight:700;color:var(--app-text);">
                                            <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i>
                                            {{ $teacher->teacherName }}
                                        </td>
                                        <td style="font-size:13px;color:var(--app-text-secondary);">
                                            {{ $teacher->ICNumber ?? '-' }}
                                        </td>
                                        <td style="font-size:13px;color:var(--app-text-secondary);">
                                            {{ $teacher->phoneNumber ?? '-' }}
                                        </td>
                                        <td style="font-size:13px;color:var(--app-text-secondary);">
                                            {{ $teacher->email ?? '-' }}
                                        </td>
                                        <td>
                                            @if($isActive)
                                                <span class="ui-badge ui-badge--success" style="font-size:11px;">
                                                    <i class="fas fa-circle" style="font-size:8px;"></i> Active
                                                </span>
                                            @else
                                                <span class="ui-badge ui-badge--danger" style="font-size:11px;">
                                                    <i class="fas fa-circle" style="font-size:8px;"></i> Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('teachers.show', $teacher->teacherID) }}"
                                               class="ui-button ui-button--compact"
                                               style="font-size:11px;padding:4px 14px;min-height:30px;border-color:var(--app-primary-border);color:var(--app-primary);">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        @if(isset($teachers) && method_exists($teachers, 'links'))
            <div style="margin-top:20px;">
                {{ $teachers->links() }}
            </div>
        @endif
    @else
        <div class="ui-state ui-state--compact">
            <div style="font-size:3rem;margin-bottom:8px;">👨‍🏫</div>
            <div class="ui-state__title">No Teachers Found</div>
            <div class="ui-state__copy">
                @if(request('search'))
                    No teachers match your search criteria.
                @else
                    No teachers have been registered yet.
                @endif
            </div>
            @if(request('search'))
                <a href="{{ route('teachers.index') }}" class="ui-button ui-button--primary" style="margin-top:16px;">
                    <i class="fas fa-times"></i> Clear Search
                </a>
            @endif
        </div>
    @endif
</div>

{{-- ============================================================ --}}
{{-- REGISTER MODAL --}}
{{-- ============================================================ --}}
<div id="registerModal" class="modal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-size:1.25rem;font-weight:700;margin:0;display:flex;align-items:center;gap:10px;color:#fff;">
                <i class="fas fa-user-plus"></i> Register New Teacher
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('registerModal')" style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1.4rem;transition:all 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('teachers.store') }}">
            @csrf
            <div class="modal-body">
                {{-- Validation errors --}}
                @if($errors->any())
                    <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:0.8rem;">
                        <div style="font-weight:700;margin-bottom:6px;">
                            <i class="fas fa-exclamation-triangle"></i> Please fix these errors:
                        </div>
                        <ul style="margin:0;padding-left:20px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Section: Basic Info --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    🆔 Basic Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-id-card"></i> Teacher ID <span style="color:#e11d48;">*</span></div>
                        <input name="teacherID" class="form-control-custom" placeholder="e.g. T001" value="{{ old('teacherID') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-user"></i> Full Name <span style="color:#e11d48;">*</span></div>
                        <input name="teacherName" class="form-control-custom" placeholder="Ahmad bin Abdullah" value="{{ old('teacherName') }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-id-card"></i> IC Number <span style="color:#e11d48;">*</span></div>
                        <input name="ICNumber" class="form-control-custom" placeholder="000101-10-1234" value="{{ old('ICNumber') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-venus-mars"></i> Gender</div>
                        <select name="gender" class="form-control-custom">
                            <option value="">Select Gender</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>👨 Male</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>👩 Female</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-ring"></i> Marital Status</div>
                        <select name="maritalStatus" class="form-control-custom">
                            <option value="">Select Status</option>
                            <option value="Single" {{ old('maritalStatus') == 'Single' ? 'selected' : '' }}>Single</option>
                            <option value="Married" {{ old('maritalStatus') == 'Married' ? 'selected' : '' }}>Married</option>
                            <option value="Divorced" {{ old('maritalStatus') == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                            <option value="Widowed" {{ old('maritalStatus') == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-users"></i> Race</div>
                        <select name="race" id="raceSelect" class="form-control-custom" onchange="toggleOtherRace()">
                            <option value="">Select Race</option>
                            <option value="Malay" {{ old('race') == 'Malay' ? 'selected' : '' }}>Malay</option>
                            <option value="Chinese" {{ old('race') == 'Chinese' ? 'selected' : '' }}>Chinese</option>
                            <option value="Indian" {{ old('race') == 'Indian' ? 'selected' : '' }}>Indian</option>
                            <option value="Other" {{ old('race') == 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>
                    <div class="form-group" id="otherRaceGroup" style="{{ old('race') == 'Other' ? '' : 'display:none;' }}">
                        <div class="form-label"><i class="fas fa-pen"></i> Specify Race</div>
                        <input type="text" name="otherRace" id="otherRaceInput" class="form-control-custom" placeholder="Please specify" value="{{ old('otherRace') }}">
                    </div>
                </div>

                <div class="divider-light"></div>

                {{-- Section: Contact --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    📞 Contact Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-phone"></i> Phone Number</div>
                        <input name="phoneNumber" class="form-control-custom" placeholder="012-3456789" value="{{ old('phoneNumber') }}">
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-envelope"></i> Email Address</div>
                        <input type="email" name="email" class="form-control-custom" placeholder="teacher@school.edu" value="{{ old('email') }}">
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                    <textarea name="address" class="form-control-custom" placeholder="No. 15, Jalan Pendidikan, Taman Ilmu">{{ old('address') }}</textarea>
                </div>

                <div class="divider-light"></div>

                {{-- Section: Employment --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    📅 Employment Details
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-calendar-plus"></i> Appointed Date</div>
                        <input type="date" name="appointedDate" class="form-control-custom" value="{{ old('appointedDate') }}">
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-calendar-minus"></i> Pension Date</div>
                        <input type="date" name="pensionDate" class="form-control-custom" value="{{ old('pensionDate') }}">
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-birthday-cake"></i> Latest Age</div>
                        <input type="number" name="latestAge" class="form-control-custom" placeholder="e.g. 34" min="0" max="120" value="{{ old('latestAge') }}">
                    </div>
                </div>

                <div class="divider-light"></div>

                {{-- Section: School --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    🏫 School Assignment
                </div>

                <div class="form-group">
                    <div class="form-label"><i class="fas fa-building"></i> Select School <span style="color:#e11d48;">*</span></div>
                    <select name="schoolID" class="form-control-custom" required>
                        <option value="">🏫 Choose a school...</option>
                        @foreach($schools ?? [] as $school)
                            <option value="{{ $school->schoolID }}" {{ old('schoolID') == $school->schoolID ? 'selected' : '' }}>
                                {{ $school->schoolName }} ({{ $school->schoolID }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-custom btn-save">
                        <i class="fas fa-save"></i> Save Teacher
                    </button>
                    <button type="button" class="btn-light-custom btn-cancel-modal" onclick="closeModal('registerModal')">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ============================================================ --}}
{{-- CSV MODAL --}}
{{-- ============================================================ --}}
<div id="csvModal" class="modal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-size:1.25rem;font-weight:700;margin:0;display:flex;align-items:center;gap:10px;color:#fff;">
                <i class="fas fa-file-import"></i> Import from CSV / Excel
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('csvModal')" style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1.4rem;transition:all 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form method="POST" action="{{ route('teachers.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <div class="form-label"><i class="fas fa-file-csv"></i> Choose File</div>
                    <input type="file" name="csvfile" class="form-control-custom" accept=".csv,.xlsx" required>
                    <p style="font-size: 0.7rem; color: #64748b; margin-top: 8px;">✓ Supported formats: .csv, .xlsx</p>
                    <p style="font-size: 0.7rem; color: #64748b; margin-top: 4px;">✓ Required columns: teacherID*, teacherName*, ICNumber*, schoolID*</p>
                    <p style="font-size: 0.7rem; color: #d97706; margin-top: 4px;">⚠️ Duplicates will be skipped. Default password: <strong>password</strong></p>
                </div>
                <div class="modal-actions" style="margin-top: 0.5rem;">
                    <button type="submit" class="btn-custom btn-save"><i class="fas fa-cloud-upload-alt"></i> Upload & Import</button>
                    <button type="button" class="btn-light-custom btn-cancel-modal" onclick="closeModal('csvModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(6px);
        justify-content: center;
        align-items: center;
        z-index: 99999;
        padding: 20px;
    }

    .modal.show {
        display: flex !important;
    }

    .modal-box {
        background: white;
        width: 660px;
        max-width: 95%;
        max-height: 90vh;
        overflow-y: auto;
        border-radius: 24px;
        animation: modalSlideIn 0.35s cubic-bezier(0.21, 1.11, 0.35, 1);
        box-shadow: 0 30px 60px -20px rgba(0,0,0,0.35);
    }

    @keyframes modalSlideIn {
        from { opacity: 0; transform: scale(0.96) translateY(-12px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-header {
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));
        padding: 1.25rem 1.5rem;
        border-radius: 24px 24px 0 0;
        color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .form-row {
        display: flex;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }

    .form-group {
        flex: 1;
        min-width: 160px;
    }

    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--app-text-secondary);
        margin-bottom: 0.4rem;
    }

    .form-label i {
        margin-right: 4px;
        color: var(--app-primary);
    }

    .form-control-custom {
        width: 100%;
        border: 1px solid var(--app-border);
        border-radius: var(--app-radius-control);
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        outline: none;
        background-color: var(--app-surface);
        font-family: inherit;
        box-sizing: border-box;
        color: var(--app-text);
    }

    .form-control-custom:focus {
        border-color: var(--app-primary);
        box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        background-color: white;
    }

    .form-control-custom:disabled {
        background-color: var(--app-background);
        cursor: not-allowed;
        opacity: 0.7;
    }

    select.form-control-custom {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23475569' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.2rem;
        padding-right: 2.5rem;
    }

    textarea.form-control-custom {
        resize: vertical;
        min-height: 80px;
    }

    .divider-light {
        height: 1px;
        background: linear-gradient(to right, var(--app-border), transparent);
        margin: 1.5rem 0 1rem;
    }

    .modal-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2rem;
        justify-content: flex-end;
        border-top: 1px solid var(--app-divider);
        padding-top: 1.5rem;
        flex-wrap: wrap;
    }

    .btn-custom {
        background: linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));
        color: white;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: var(--app-radius-control);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-custom:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    }

    .btn-custom:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .btn-save {
        padding: 0.8rem 2rem;
        font-weight: 700;
    }

    .btn-light-custom {
        background: white;
        border: 1px solid var(--app-border);
        color: var(--app-text-secondary);
        padding: 0.625rem 1.5rem;
        border-radius: var(--app-radius-control);
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-light-custom:hover {
        background: var(--app-primary-soft);
        border-color: var(--app-primary-border);
        color: var(--app-primary-dark);
    }

    .btn-cancel-modal {
        padding: 0.8rem 2rem;
    }

    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        .modal-box {
            width: 100%;
            max-width: 100%;
        }
        .modal-body {
            padding: 1rem;
        }
    }
</style>

<script>
    function openModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    function toggleOtherRace() {
        var race = document.getElementById('raceSelect');
        var otherGroup = document.getElementById('otherRaceGroup');
        var otherInput = document.getElementById('otherRaceInput');
        if (race && otherGroup && otherInput) {
            if (race.value === 'Other') {
                otherGroup.style.display = 'block';
                otherInput.required = true;
            } else {
                otherGroup.style.display = 'none';
                otherInput.required = false;
                otherInput.value = '';
            }
        }
    }

    // Close modal when clicking outside
    window.onclick = function(e) {
        var registerModal = document.getElementById('registerModal');
        var csvModal = document.getElementById('csvModal');
        if (registerModal && e.target == registerModal) {
            closeModal('registerModal');
        }
        if (csvModal && e.target == csvModal) {
            closeModal('csvModal');
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('registerModal');
            closeModal('csvModal');
        }
    });

    // Auto-hide success messages after 5 seconds (not errors)
    setTimeout(function() {
        document.querySelectorAll('.ui-message').forEach(function(alert) {
            if (!alert.classList.contains('ui-message--error')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentElement) alert.remove();
                }, 500);
            }
        });
    }, 5000);

    // Re-open register modal if there were validation errors
    @if($errors->any() && old('teacherID'))
        document.addEventListener('DOMContentLoaded', function () {
            openModal('registerModal');
        });
    @endif
</script>
@endsection