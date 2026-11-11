@extends('layouts.app')

@section('title', 'Send Offer Letters')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-envelope" style="color:var(--app-primary);font-size:24px;"></i> Send Offer Letters
            </h1>
            <p>Send offer letters to accepted applicants</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-user"></i> {{ isset($pendingCount) ? $pendingCount : 0 }} Pending
            </span>
            <span class="ui-badge ui-badge--success">
                <i class="fas fa-check-circle"></i> {{ DB::table('application')->where('application_status', 'Accepted')->count() }} Accepted
            </span>
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

    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if(isset($pendingCount) && $pendingCount > 0)
        <div class="ui-message" style="border-color:#bfdbfe;background:#eff6ff;color:#1d4ed8;margin-bottom:16px;">
            <i class="fas fa-info-circle"></i>
            <div>Total <strong>{{ $pendingCount }}</strong> applicant(s) pending offer letter.</div>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, #059669, #047857);border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-file-signature"></i> Applicants Ready for Offer Letters
            </h5>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-user"></i> {{ isset($applications) ? count($applications) : 0 }} Applicants
            </span>
        </div>
        <div class="ui-card__body" style="padding:0;">
            @if(isset($applications) && count($applications) > 0)
                <div class="ui-table-wrap">
                    <table class="ui-table ui-table--dashboard">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Applicant Name</th>
                                <th>Email Address</th>
                                <th>IC Number</th>
                                <th>Application Date</th>
                                <th>Offer School</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $index => $app)
                            <tr>
                                <td>
                                    <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td style="font-weight:700;color:var(--app-text);">
                                    <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i>
                                    {{ $app->full_name ?? 'N/A' }}
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    @php
                                        $email = $app->applicant_email ?? null;
                                    @endphp
                                    <div style="display:flex;flex-direction:column;">
                                        <span>{{ $email ?? 'N/A' }}</span>
                                        @if($email)
                                            <span style="font-size:11px;color:var(--app-success);">
                                                <i class="fas fa-check-circle"></i> Valid email
                                            </span>
                                        @else
                                            <span style="font-size:11px;color:var(--app-danger);">
                                                <i class="fas fa-exclamation-triangle"></i> No email found
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    <code style="background:var(--app-divider);padding:2px 8px;border-radius:4px;font-size:12px;">{{ $app->ic_number ?? 'N/A' }}</code>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    {{ isset($app->application_date) ? date('d/m/Y', strtotime($app->application_date)) : 'N/A' }}
                                </td>
                                <td>
                                    <form action="{{ route('offer.assign-school') }}" method="POST" style="display:flex;flex-direction:column;gap:8px;">
                                        @csrf
                                        <input type="hidden" name="application_id" value="{{ $app->application_id }}">
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            <select name="schoolID" class="ui-select" style="font-size:12px;padding:6px 10px;min-width:160px;" required>
                                                <option value="">-- Select School --</option>
                                                @if(isset($schools) && count($schools) > 0)
                                                    @foreach($schools as $school)
                                                        <option value="{{ $school->schoolID }}" {{ isset($app->schoolID) && $app->schoolID == $school->schoolID ? 'selected' : '' }}>
                                                            {{ $school->schoolName }} 
                                                            ({{ $school->vacancy }} vacancies)
                                                        </option>
                                                    @endforeach
                                                @else
                                                    <option value="" disabled>⚠️ No schools with vacancies</option>
                                                @endif
                                            </select>
                                            <button type="submit" class="ui-button ui-button--primary" style="font-size:11px;padding:4px 12px;min-height:30px;width:100%;" {{ !isset($schools) || count($schools) == 0 ? 'disabled' : '' }}>
                                                @if(isset($app->schoolID) && $app->schoolID)
                                                    <i class="fas fa-sync"></i> Update School
                                                @else
                                                    <i class="fas fa-plus"></i> Assign School
                                                @endif
                                            </button>
                                        </div>
                                    </form>
                                    @if(isset($app->schoolID) && $app->schoolID)
                                        <div style="margin-top:4px;">
                                            <span class="ui-badge ui-badge--success" style="font-size:10px;">
                                                <i class="fas fa-check-circle"></i> {{ $app->school_name ?? 'Assigned' }}
                                            </span>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if(isset($app->schoolID) && $app->schoolID)
                                        <a href="{{ route('offer.preview', $app->application_id) }}" class="ui-button ui-button--primary" style="font-size:12px;padding:6px 14px;min-height:32px;">
                                            <i class="fas fa-paper-plane"></i> Preview & Send
                                        </a>
                                    @else
                                        <span style="font-size:11px;color:var(--app-warning);display:block;">
                                            <i class="fas fa-exclamation-triangle"></i> Assign school first
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="ui-state ui-state--compact">
                    <div style="font-size:3rem;margin-bottom:8px;">📭</div>
                    <div class="ui-state__title">No Pending Applications</div>
                    <div class="ui-state__copy">No applications found with status 'Accepted' and no offer sent yet.</div>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mt-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Accepted</div>
                <div class="ui-card__value">{{ DB::table('application')->where('application_status', 'Accepted')->count() }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-check-circle" style="color:var(--app-success);"></i> Approved Applicants
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-warning);">
                <div class="ui-card__label">Pending Offer</div>
                <div class="ui-card__value" style="color:var(--app-warning);">{{ isset($pendingCount) ? $pendingCount : 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-clock" style="color:var(--app-warning);"></i> Awaiting Offer Letter
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Available Vacancies</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ DB::table('school')->sum('vacancy') }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> Open Positions
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Total Schools</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ DB::table('school')->count() }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-building"></i> Registered Schools
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Additional custom styles for the offer page */
    .ui-table td {
        vertical-align: middle !important;
    }
    
    .ui-table code {
        background: var(--app-divider);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-family: monospace;
        color: var(--app-text-secondary);
    }
    
    .ui-select option {
        padding: 4px 8px;
    }
</style>

<script>
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.ui-message');
        alerts.forEach(function(alert) {
            // Don't hide info messages
            if (!alert.querySelector('.fa-info-circle')) {
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