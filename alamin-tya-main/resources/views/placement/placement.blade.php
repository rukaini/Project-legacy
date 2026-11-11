@extends('layouts.app')

@section('title', 'Teacher Placement')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-user-graduate" style="color:var(--app-primary);font-size:24px;"></i> Convert Guru New to Teacher
            </h1>
            <p>Convert and assign Guru New records to schools</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-clock"></i> {{ $guruNewList->count() }} Pending
            </span>
            <span class="ui-badge ui-badge--success">
                <i class="fas fa-check-circle"></i> {{ $assignedCount }} Converted
            </span>
            <a href="{{ route('placement.records') }}" class="ui-button ui-button--outline" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-history"></i> View Records
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Pending Conversion</div>
                <div class="ui-card__value">{{ $guruNewList->count() }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-clock" style="color:var(--app-warning);"></i> Awaiting Conversion
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Converted Teachers</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ $assignedCount }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-check-circle" style="color:var(--app-success);"></i> Successfully Converted
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Available Schools</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ $schools->count() }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> Active Schools
                </div>
            </div>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="ui-card ui-card--section" style="max-width:700px;margin:0 auto;">
        <div class="ui-card__header" style="background:linear-gradient(135deg, #059669, #047857);border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-user-plus"></i> Convert Guru New to Teacher
            </h5>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-user"></i> {{ $guruNewList->count() }} Available
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

            {{-- Form --}}
            @if($guruNewList->count() > 0)
                <form action="{{ route('placement.store') }}" method="POST">
                    @csrf

                    {{-- Select Guru New --}}
                    <div class="ui-field" style="margin-bottom:16px;">
                        <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-user-graduate" style="color:var(--app-primary);"></i> Select Guru New <span style="color:var(--app-danger);">*</span>
                        </label>
                        <select name="gn_id" id="gn_id" class="ui-select" required>
                            <option value="">-- Select Guru New --</option>
                            @foreach($guruNewList as $guru)
                                <option value="{{ $guru->gn_id }}" 
                                        data-name="{{ $guru->applicant->full_name ?? 'N/A' }}" 
                                        data-email="{{ $guru->applicant->email ?? 'N/A' }}">
                                    {{ $guru->applicant->full_name ?? 'Unknown' }} 
                                    (GN-{{ $guru->gn_id }}) 
                                    - Joined: {{ isset($guru->join_date) ? date('d/m/Y', strtotime($guru->join_date)) : 'N/A' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="ui-field__helper">Select a Guru New record to convert to a teacher</div>
                    </div>

                    {{-- Selected Guru Info --}}
                    <div id="guruInfo" style="display:none;background:var(--app-primary-soft);border:1px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:12px 16px;margin-bottom:16px;">
                        <div style="display:flex;flex-direction:column;gap:4px;">
                            <div style="font-size:13px;font-weight:600;color:var(--app-text);">
                                <i class="fas fa-user" style="color:var(--app-primary);"></i> 
                                <span id="guruName">N/A</span>
                            </div>
                            <div style="font-size:13px;color:var(--app-text-secondary);">
                                <i class="fas fa-envelope" style="color:var(--app-primary);"></i> 
                                <span id="guruEmail">N/A</span>
                            </div>
                        </div>
                    </div>

                    {{-- Select School --}}
                    <div class="ui-field" style="margin-bottom:16px;">
                        <label class="ui-label" style="display:flex;align-items:center;gap:6px;">
                            <i class="fas fa-school" style="color:var(--app-primary);"></i> Select School <span style="color:var(--app-danger);">*</span>
                        </label>
                        <select name="schoolID" class="ui-select" required>
                            <option value="">-- Select School --</option>
                            @foreach($schools as $school)
                                <option value="{{ $school->schoolID }}">
                                    {{ $school->schoolName }} ({{ $school->schoolID }})
                                    @if(isset($school->vacancy) && $school->vacancy > 0)
                                        - {{ $school->vacancy }} vacancies
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="ui-field__helper">Select the school to assign this teacher to</div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="ui-button ui-button--primary" style="min-height:44px;padding:0 28px;width:100%;display:flex;align-items:center;justify-content:center;gap:8px;font-size:14px;">
                        <i class="fas fa-check-circle"></i> Convert to Teacher & Assign
                    </button>
                </form>
            @else
                {{-- Empty State --}}
                <div class="ui-state ui-state--compact" style="min-height:200px;">
                    <div style="font-size:3rem;margin-bottom:8px;">📭</div>
                    <div class="ui-state__title">No Pending Conversions</div>
                    <div class="ui-state__copy">All Guru New records have been converted to teachers.</div>
                    <a href="{{ route('confirmation.tracking') }}" class="ui-button ui-button--primary" style="margin-top:16px;">
                        <i class="fas fa-arrow-right"></i> Go to Confirmations
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Show guru info when selected
    document.addEventListener('DOMContentLoaded', function() {
        var gnSelect = document.getElementById('gn_id');
        var guruInfo = document.getElementById('guruInfo');
        var guruName = document.getElementById('guruName');
        var guruEmail = document.getElementById('guruEmail');
        
        if (gnSelect) {
            gnSelect.addEventListener('change', function() {
                var selectedOption = this.options[this.selectedIndex];
                
                if (this.value) {
                    guruName.textContent = selectedOption.dataset.name || 'N/A';
                    guruEmail.textContent = selectedOption.dataset.email || 'N/A';
                    guruInfo.style.display = 'block';
                } else {
                    guruInfo.style.display = 'none';
                }
            });
        }

        // Auto-hide alerts after 5 seconds
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
    });
</script>
@endsection