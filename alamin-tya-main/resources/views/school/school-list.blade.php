@extends('layouts.app')
@section('title', 'School List')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1>🏫 School List</h1>
            <p>Manage all registered schools and their capacity</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-building"></i> {{ $schools->count() ?? 0 }} Schools
            </span>
            <button type="button" class="ui-button ui-button--primary" onclick="openModal('registerModal')" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <i class="fas fa-plus"></i> Register
            </button>
            <a href="{{ route('school.upload') }}" class="ui-button ui-button--outline" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-file-import"></i> Import CSV
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    @php
        $totalTeachers = $schools->sum('totalTeacher');
        $totalCapacity = $schools->sum(function($school) {
            return ($school->totalTeacher ?? 0) + ($school->vacancy ?? 0);
        });
        $totalVacancy  = $schools->sum('vacancy');
        $totalSchools  = $schools->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Schools</div>
                <div class="ui-card__value">{{ $totalSchools }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> Registered Institutions
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Teachers</div>
                <div class="ui-card__value">{{ $totalTeachers }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-chalkboard-user"></i> Across All Schools
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Capacity</div>
                <div class="ui-card__value">{{ $totalCapacity }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> Maximum Teacher Slots
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: {{ $totalVacancy > 0 ? 'var(--app-warning)' : 'var(--app-success)' }};">
                <div class="ui-card__label">Total Vacancy</div>
                <div class="ui-card__value" style="color:{{ $totalVacancy > 0 ? 'var(--app-warning)' : 'var(--app-success)' }};">
                    {{ $totalVacancy }}
                </div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-exclamation-circle"></i> Open Positions
                </div>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
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
        <form method="GET" action="{{ route('school.list') }}" style="display:flex;gap:10px;flex-wrap:wrap;flex:1;">
            <div style="position:relative;flex:1;min-width:200px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--app-text-subtle);font-size:14px;"></i>
                <input type="text" name="search" class="ui-input" placeholder="Search by school name, code or address..." value="{{ request('search') }}" style="padding-left:36px;height:40px;">
            </div>
            <button type="submit" class="ui-button ui-button--primary" style="height:40px;padding:0 20px;">
                <i class="fas fa-search"></i> Search
            </button>
            @if(request('search'))
                <a href="{{ route('school.list') }}" class="ui-button ui-button--compact" style="height:40px;padding:0 16px;">
                    <i class="fas fa-times"></i> Clear
                </a>
            @endif
        </form>
    </div>

    {{-- School Table --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header">
            <h3 style="margin:0;font-size:14px;font-weight:800;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                <i class="fas fa-list" style="color:var(--app-primary);"></i> School Management Records
            </h3>
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-school"></i> {{ $totalSchools }} Schools
            </span>
        </div>
        <div class="ui-card__body" style="padding:0;">
            <div class="ui-table-wrap">
                <table class="ui-table ui-table--dashboard">
                    <thead>
                        <tr>
                            <th>School Code</th>
                            <th>School Name</th>
                            <th class="text-center">👨‍🏫</th>
                            <th class="text-center">📊</th>
                            <th class="text-center">🎯</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                            @php
                                $capacity     = $school->capacity ?? 15;
                                $totalTeacher = $school->totalTeacher ?? 0;
                                $vacancy      = $school->vacancy ?? ($capacity - $totalTeacher);

                                $vacancyBadge = 'ui-badge--success';
                                $vacancyIcon  = 'fa-check-circle';
                                if ($vacancy <= 0) {
                                    $vacancyBadge = 'ui-badge--danger';
                                    $vacancyIcon  = 'fa-times-circle';
                                } elseif ($vacancy <= 3) {
                                    $vacancyBadge = 'ui-badge--warning';
                                    $vacancyIcon  = 'fa-exclamation-triangle';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                        {{ $school->schoolID }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700;color:var(--app-text);">
                                        <i class="fas fa-school" style="color:var(--app-primary);font-size:12px;"></i>
                                        {{ $school->schoolName }}
                                    </div>
                                    <div style="font-size:11px;color:var(--app-text-subtle);">
                                        {{ $school->schoolAddress ? \Str::limit($school->schoolAddress, 40) : 'No address' }}
                                    </div>
                                </td>
                                <td class="text-center" style="font-weight:700;color:var(--app-primary);">
                                    {{ $totalTeacher }}
                                </td>
                                <td class="text-center" style="font-weight:700;color:var(--app-text-secondary);">
                                    {{ $capacity }}
                                </td>
                                <td class="text-center">
                                    <span class="ui-badge {{ $vacancyBadge }}" style="font-size:12px;">
                                        <i class="fas {{ $vacancyIcon }}"></i>
                                        {{ $vacancy }}
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;">
                                        <a href="{{ route('school.show', $school->schoolID) }}" class="ui-button ui-button--compact" style="font-size:11px;padding:4px 12px;min-height:30px;border-color:var(--app-primary-border);color:var(--app-primary);">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="{{ route('school.edit', $school->schoolID) }}" class="ui-button ui-button--compact" style="font-size:11px;padding:4px 12px;min-height:30px;">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="ui-state ui-state--compact">
                                        <div style="font-size:2.5rem;margin-bottom:8px;">🏫</div>
                                        <div class="ui-state__title">No Schools Found</div>
                                        <div class="ui-state__copy">No schools match your search criteria.</div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($schools, 'links'))
            <div style="padding:12px 20px;border-top:1px solid var(--app-divider);">
                {{ $schools->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ============================================================ --}}
{{-- REGISTER MODAL --}}
{{-- ============================================================ --}}
<div id="registerModal" class="modal" style="display:none;">
    <div class="modal-box">
        <div class="modal-header">
            <h3 style="font-size:1.25rem;font-weight:700;margin:0;display:flex;align-items:center;gap:10px;color:#fff;">
                <i class="fas fa-school"></i> Register New School
            </h3>
            <button type="button" class="modal-close" onclick="closeModal('registerModal')" style="background:rgba(255,255,255,0.2);border:none;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;font-size:1.4rem;transition:all 0.2s;">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('school.store') }}">
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

                {{-- Section: School Info --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    🏫 School Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-id-card"></i> School ID <span style="color:#e11d48;">*</span></div>
                        <input name="schoolID" class="form-control-custom" placeholder="e.g. SCH001" value="{{ old('schoolID') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-signature"></i> School Name <span style="color:#e11d48;">*</span></div>
                        <input name="schoolName" class="form-control-custom" placeholder="Sekolah Kebangsaan Damai" value="{{ old('schoolName') }}" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="form-label"><i class="fas fa-map-marker-alt"></i> School Address <span style="color:#e11d48;">*</span></div>
                    <textarea name="schoolAddress" class="form-control-custom" placeholder="Jalan Damai, Kuala Lumpur" required>{{ old('schoolAddress') }}</textarea>
                </div>

                <div class="divider-light"></div>

                {{-- Section: Contact --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    📞 Contact Information
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-calendar-alt"></i> Register Date</div>
                        <input type="date" name="registerDate" class="form-control-custom" value="{{ old('registerDate') }}">
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-phone"></i> Phone Number</div>
                        <input name="phoneNumber" class="form-control-custom" placeholder="03-42567890" value="{{ old('phoneNumber') }}">
                    </div>
                </div>

                <div class="divider-light"></div>

                {{-- Section: Capacity --}}
                <div style="background:var(--app-primary-soft);padding:0.4rem 1rem;border-radius:40px;font-size:0.75rem;font-weight:600;color:var(--app-primary-dark);margin-bottom:1.2rem;display:inline-block;">
                    👥 Capacity Details
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-chalkboard-user"></i> Total Teachers <span style="color:#e11d48;">*</span></div>
                        <input type="number" name="totalTeacher" id="totalTeacher" class="form-control-custom" placeholder="0" min="0" value="{{ old('totalTeacher') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-users"></i> Capacity <span style="color:#e11d48;">*</span></div>
                        <input type="number" name="capacity" id="capacity" class="form-control-custom" placeholder="15" min="1" value="{{ old('capacity', 15) }}" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label"><i class="fas fa-bullseye"></i> Vacancy (auto)</div>
                        <input type="number" name="vacancy" id="vacancy" class="form-control-custom" placeholder="0" min="0" readonly style="background:var(--app-primary-soft);color:var(--app-primary);font-weight:700;">
                    </div>
                </div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:-8px;margin-bottom:1rem;">
                    Vacancy is auto-calculated (Capacity − Total Teachers)
                </div>

                <div class="modal-actions">
                    <button type="submit" class="btn-custom btn-save">
                        <i class="fas fa-save"></i> Save School
                    </button>
                    <button type="button" class="btn-light-custom btn-cancel-modal" onclick="closeModal('registerModal')">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
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

    // Close modal when clicking outside
    window.onclick = function(e) {
        var registerModal = document.getElementById('registerModal');
        if (registerModal && e.target == registerModal) {
            closeModal('registerModal');
        }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal('registerModal');
        }
    });

    // ============================================================
    // Auto-calculate vacancy
    // ============================================================
    var totalTeacherInput = document.getElementById('totalTeacher');
    var capacityInput     = document.getElementById('capacity');
    var vacancyInput      = document.getElementById('vacancy');

    if (totalTeacherInput && capacityInput && vacancyInput) {
        function calculateVacancy() {
            var totalTeacher = parseInt(totalTeacherInput.value) || 0;
            var capacity     = parseInt(capacityInput.value) || 0;
            var vacancy      = capacity - totalTeacher;
            vacancyInput.value = vacancy >= 0 ? vacancy : 0;

            if (vacancy < 0) {
                vacancyInput.style.color      = 'var(--app-danger)';
                vacancyInput.style.background = 'var(--app-danger-soft)';
            } else {
                vacancyInput.style.color      = 'var(--app-primary)';
                vacancyInput.style.background = 'var(--app-primary-soft)';
            }
        }

        totalTeacherInput.addEventListener('input', calculateVacancy);
        capacityInput.addEventListener('input', calculateVacancy);
        calculateVacancy();
    }

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
    @if($errors->any() && old('schoolID'))
        document.addEventListener('DOMContentLoaded', function () {
            openModal('registerModal');
        });
    @endif
</script>
@endsection