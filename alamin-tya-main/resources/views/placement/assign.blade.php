@extends('layouts.app')

@section('title', 'Assign Teacher to School')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-user-plus" style="color:var(--app-primary);font-size:24px;"></i> Assign Teacher to School
            </h1>
            <p>Assign teachers to schools with available vacancies</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-clock"></i> {{ $pendingCount ?? 0 }} Pending
            </span>
            <span class="ui-badge ui-badge--success">
                <i class="fas fa-check-circle"></i> {{ $assignedCount ?? 0 }} Assigned
            </span>
            <a href="{{ route('placement.records') }}" class="ui-button ui-button--outline" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-history"></i> View Records
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Pending Assignments</div>
                <div class="ui-card__value">{{ $pendingCount ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-clock" style="color:var(--app-warning);"></i> Awaiting Assignment
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Assigned Teachers</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ $assignedCount ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-check-circle" style="color:var(--app-success);"></i> Successfully Assigned
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Total Teachers</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ $totalTeachers ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> All Teachers
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Schools with Vacancies</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ $schools->count() ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> Available Schools
                </div>
            </div>
        </div>
    </div>

    {{-- Vacancy Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Total Available Vacancies</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ $schools->sum('vacancy') ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-briefcase"></i> Open Positions
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Schools with Vacancies</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ $schools->count() ?? 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-school"></i> Accepting Teachers
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-danger);">
                <div class="ui-card__label">Full Schools</div>
                <div class="ui-card__value" style="color:var(--app-danger);">{{ \App\Models\School::where('vacancy', '<=', 0)->count() }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-times-circle" style="color:var(--app-danger);"></i> No Vacancies
                </div>
            </div>
        </div>
    </div>

    {{-- Assignment Form --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-edit"></i> New Teacher Assignment
            </h5>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-user"></i> {{ $guruNewListWithApplicants->count() ?? 0 }} Available
            </span>
        </div>
        <div class="ui-card__body" style="padding:24px;">

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

            @if(session('error'))
                <div class="ui-message ui-message--error" style="margin-bottom:16px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <form action="{{ route('placement.assign') }}" method="POST">
                @csrf

                {{-- Select Teacher --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-user-graduate" style="color:var(--app-primary);"></i> Select Teacher to Assign <span style="color:var(--app-danger);">*</span>
                    </label>
                    <select name="gn_id" class="ui-select" required>
                        <option value="">-- Select Teacher --</option>
                        @forelse($guruNewListWithApplicants as $guruNew)
                            <option value="{{ $guruNew->gn_id }}" {{ old('gn_id') == $guruNew->gn_id ? 'selected' : '' }}>
                                {{ $guruNew->full_name ?? 'N/A' }} 
                                (ID: {{ $guruNew->gn_id ?? 'N/A' }})
                                @if($guruNew->applicant_email && $guruNew->applicant_email != 'No email found')
                                    - {{ $guruNew->applicant_email }}
                                @endif
                            </option>
                        @empty
                            <option value="" disabled>No pending assignments available.</option>
                        @endforelse
                    </select>
                    @if($guruNewListWithApplicants->count() == 0)
                        <div class="ui-field__helper" style="color:var(--app-warning);">No pending assignments available.</div>
                    @endif
                </div>

                {{-- Teacher ID --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-id-card" style="color:var(--app-primary);"></i> Teacher ID <span style="color:var(--app-danger);">*</span>
                    </label>
                    <input type="text" name="teacherID" class="ui-input" 
                           placeholder="Example: TCH20250001" 
                           value="{{ old('teacherID') }}"
                           required>
                    <div class="ui-field__helper">Enter a unique Teacher ID (max 20 characters). Must not already exist.</div>
                </div>

                {{-- Select School --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-school" style="color:var(--app-primary);"></i> Select School <span style="color:var(--app-danger);">*</span>
                    </label>
                    <select name="schoolID" class="ui-select" required>
                        <option value="">-- Select School --</option>
                        @forelse($schools as $school)
                            <option value="{{ $school->schoolID }}" {{ old('schoolID') == $school->schoolID ? 'selected' : '' }}>
                                {{ $school->schoolName }} ({{ $school->vacancy }} vacancies)
                            </option>
                        @empty
                            <option value="" disabled>⚠️ No schools with available vacancies</option>
                        @endforelse
                    </select>
                    @if($schools->count() == 0)
                        <div class="ui-field__helper" style="color:var(--app-danger);">
                            ⚠️ No schools have available vacancies. Please add vacancies to schools first.
                        </div>
                    @else
                        <div class="ui-field__helper">Showing schools with available vacancies (vacancy > 0)</div>
                    @endif
                </div>

                {{-- Assignment Date --}}
                <div class="ui-field" style="margin-bottom:16px;">
                    <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Assignment Date <span style="color:var(--app-danger);">*</span>
                    </label>
                    <input type="date" name="assignDate" class="ui-input" 
                           value="{{ old('assignDate', date('Y-m-d')) }}" required>
                    <div class="ui-field__helper">Date of this assignment/placement</div>
                </div>

                {{-- Info Badge --}}
                <div style="background:var(--app-primary-soft);border:1px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:12px 16px;margin-bottom:16px;">
                    <div style="display:flex;align-items:flex-start;gap:8px;">
                        <i class="fas fa-info-circle" style="color:var(--app-primary);font-size:16px;margin-top:2px;"></i>
                        <div style="font-size:13px;color:var(--app-text-secondary);">
                            <strong>Note:</strong> After assignment, a teacher account will be created with default password: <strong style="color:var(--app-text);">password</strong>. The teacher will be required to change password on first login.
                        </div>
                    </div>
                </div>

                {{-- Submit Button --}}
                <button type="submit" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;font-size:14px;" {{ $guruNewListWithApplicants->count() == 0 || $schools->count() == 0 ? 'disabled' : '' }}>
                    <i class="fas fa-check-circle"></i> Confirm Assignment & Convert to Teacher
                </button>
                
                @if($guruNewListWithApplicants->count() > 0 && $schools->count() == 0)
                    <div style="color:var(--app-danger);font-size:13px;margin-top:8px;text-align:center;">
                        <i class="fas fa-exclamation-triangle"></i> Cannot assign: No schools with available vacancies.
                    </div>
                @endif
            </form>
        </div>
    </div>

    {{-- Pending Assignments List --}}
    @if($guruNewListWithApplicants->count() > 0)
        <div class="ui-card ui-card--section mt-4">
            <div class="ui-card__header">
                <h5 style="margin:0;font-size:14px;font-weight:700;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--app-primary);"></i> Pending Assignments
                </h5>
                <span class="ui-badge ui-badge--warning">
                    <i class="fas fa-clock"></i> {{ $guruNewListWithApplicants->count() }} teachers pending
                </span>
            </div>
            <div class="ui-card__body" style="padding:0;">
                @foreach($guruNewListWithApplicants as $guruNew)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;border-bottom:1px solid var(--app-divider);transition:background 0.2s ease;" onmouseover="this.style.background='var(--app-background)'" onmouseout="this.style.background='transparent'">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;">
                            <div style="width:40px;height:40px;border-radius:var(--app-radius-card);background:var(--app-primary-soft);color:var(--app-primary);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:16px;flex-shrink:0;">
                                {{ strtoupper(substr($guruNew->full_name ?? 'N', 0, 1)) }}
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:700;color:var(--app-text);font-size:14px;">
                                    {{ $guruNew->full_name ?? 'N/A' }}
                                    @if($guruNew->applicant_email && $guruNew->applicant_email != 'No email found')
                                        <span style="font-size:12px;color:var(--app-text-subtle);font-weight:normal;">
                                            ({{ $guruNew->applicant_email }})
                                        </span>
                                    @endif
                                </div>
                                <div style="font-size:12px;color:var(--app-text-subtle);display:flex;gap:16px;flex-wrap:wrap;">
                                    <span>Application ID: {{ $guruNew->application_id ?? 'N/A' }}</span>
                                    <span>Join Date: {{ $guruNew->join_date ?? 'N/A' }}</span>
                                    @if($guruNew->phone_number && $guruNew->phone_number != 'N/A')
                                        <span>Phone: {{ $guruNew->phone_number }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="ui-badge ui-badge--warning" style="font-size:11px;">
                                <i class="fas fa-clock"></i> Pending Assignment
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="ui-card ui-card--section mt-4">
            <div class="ui-card__header">
                <h5 style="margin:0;font-size:14px;font-weight:700;color:var(--app-text);display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-list" style="color:var(--app-primary);"></i> Pending Assignments
                </h5>
            </div>
            <div class="ui-card__body">
                <div class="ui-state ui-state--compact">
                    <div style="font-size:3rem;margin-bottom:8px;">🎉</div>
                    <div class="ui-state__title">No Pending Assignments!</div>
                    <div class="ui-state__copy">All teachers have been assigned to schools.</div>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    // Auto-hide success/error messages after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.ui-message');
        alerts.forEach(function(alert) {
            if (!alert.classList.contains('ui-message--error')) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    if (alert.parentElement) alert.remove();
                }, 500);
            }
        });
    }, 5000);
</script>
@endsection