@extends('layouts.app')

@section('title', 'Send Confirmation Letters')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-file-contract" style="color:var(--app-primary);font-size:24px;"></i> Send Confirmation Letters
            </h1>
            <p>Send confirmation letters to applicants who accepted the offer</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-user"></i> {{ isset($applications) ? $applications->count() : 0 }} Applicants
            </span>
            <span class="ui-badge ui-badge--success">
                <i class="fas fa-check-circle"></i> {{ isset($acceptedCount) ? $acceptedCount : 0 }} Accepted
            </span>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total Applicants</div>
                <div class="ui-card__value">{{ isset($applications) ? $applications->count() : 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-users"></i> All Applicants
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-warning);">
                <div class="ui-card__label">Pending</div>
                <div class="ui-card__value" style="color:var(--app-warning);">{{ isset($pendingCount) ? $pendingCount : 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-clock"></i> Awaiting Confirmation
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Accepted</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ isset($acceptedCount) ? $acceptedCount : 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-check-circle"></i> Confirmed
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-danger);">
                <div class="ui-card__label">Rejected</div>
                <div class="ui-card__value" style="color:var(--app-danger);">{{ isset($rejectedCount) ? $rejectedCount : 0 }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-times-circle"></i> Declined
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

    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    {{-- Main Card --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-list"></i> Confirmation Letter Management
            </h5>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-file"></i> {{ isset($applications) ? $applications->count() : 0 }} Records
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
                                <th>Email</th>
                                <th>IC Number</th>
                                <th>Offer Accepted</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $index => $app)
                            @php
                                $response = $app->confirmation_response ?? $app->confirmation_status ?? 'draft';
                                $isAccepted = $response === 'accepted';
                                $isRejected = $response === 'rejected';
                                $isPending = $response === 'pending';
                                $isDraft = $response === 'draft' || $response === null;
                            @endphp
                            <tr>
                                <td>
                                    <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td style="font-weight:700;color:var(--app-text);">
                                    <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i>
                                    {{ $app->full_name ?? $app->name ?? 'N/A' }}
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    <div style="display:flex;flex-direction:column;">
                                        <span>{{ $app->applicant_email ?? $app->email ?? 'N/A' }}</span>
                                        @if(isset($app->applicant_email) && $app->applicant_email)
                                            <span style="font-size:11px;color:var(--app-success);">
                                                <i class="fas fa-check-circle"></i> Valid email
                                            </span>
                                        @else
                                            <span style="font-size:11px;color:var(--app-danger);">
                                                <i class="fas fa-exclamation-triangle"></i> No email
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    <code style="background:var(--app-divider);padding:2px 8px;border-radius:4px;font-size:12px;">{{ $app->ic_number ?? 'N/A' }}</code>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    <div style="display:flex;flex-direction:column;">
                                        <span>{{ isset($app->application_date) ? date('d/m/Y', strtotime($app->application_date)) : 'N/A' }}</span>
                                        @if(isset($app->application_date))
                                            <span style="font-size:11px;color:var(--app-text-subtle);">
                                                {{ \Carbon\Carbon::parse($app->application_date)->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($isAccepted)
                                        <span class="ui-badge ui-badge--success" style="font-size:11px;">
                                            <i class="fas fa-check-circle"></i> Accepted
                                        </span>
                                    @elseif($isRejected)
                                        <span class="ui-badge ui-badge--danger" style="font-size:11px;">
                                            <i class="fas fa-times-circle"></i> Rejected
                                        </span>
                                    @elseif($isPending)
                                        <span class="ui-badge ui-badge--warning" style="font-size:11px;">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @else
                                        <span class="ui-badge" style="border-color:var(--app-border);background:var(--app-background);color:var(--app-text-subtle);font-size:11px;">
                                            <i class="fas fa-file"></i> Not Sent
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($isAccepted || $isRejected)
                                        <a href="{{ route('confirmation.tracking') }}" class="ui-button ui-button--compact" style="font-size:11px;padding:4px 14px;min-height:30px;border-color:var(--app-primary-border);color:var(--app-primary);">
                                            <i class="fas fa-eye"></i> View Status
                                        </a>
                                    @else
                                        <a href="{{ route('confirmation.preview', $app->application_id) }}" class="ui-button ui-button--primary" style="font-size:11px;padding:4px 14px;min-height:30px;">
                                            <i class="fas fa-paper-plane"></i> Send
                                        </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(isset($applications) && method_exists($applications, 'links'))
                    <div style="padding:12px 20px;border-top:1px solid var(--app-divider);">
                        {{ $applications->links() }}
                    </div>
                @endif
            @else
                <div class="ui-state ui-state--compact">
                    <div style="font-size:3rem;margin-bottom:8px;">📭</div>
                    <div class="ui-state__title">No Pending Confirmations</div>
                    <div class="ui-state__copy">No applicants have accepted the offer yet. Check back later!</div>
                </div>
            @endif
        </div>
    </div>
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