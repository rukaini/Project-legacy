@extends('layouts.app')

@section('title', 'Confirmation Tracking')

@section('content')
<div class="ui-page-shell">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-chart-line" style="color:var(--app-primary);font-size:24px;"></i> Confirmation Tracking
            </h1>
            <p>Track all confirmation letters sent to applicants</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge ui-badge--info">
                <i class="fas fa-file"></i> {{ isset($confirmations) ? $confirmations->count() : 0 }} Documents
            </span>
            <a href="{{ route('confirmation.index') }}" class="ui-button ui-button--primary" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-plus"></i> Send New
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    @php
        $total = isset($confirmations) ? $confirmations->count() : 0;
        $accepted = isset($confirmations) ? $confirmations->where('display_status', 'Accepted')->count() : 0;
        $pending = isset($confirmations) ? $confirmations->where('display_status', 'Pending')->count() : 0;
        $rejected = isset($confirmations) ? $confirmations->where('display_status', 'Rejected')->count() : 0;
        $sent = isset($confirmations) ? $confirmations->where('display_status', 'Sent')->count() : 0;
        $draft = isset($confirmations) ? $confirmations->where('display_status', 'Draft')->count() : 0;
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="ui-card ui-card--stat-admin text-center">
                <div class="ui-card__label">Total</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ $total }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-file"></i> All Documents
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-success);">
                <div class="ui-card__label">Accepted</div>
                <div class="ui-card__value" style="color:var(--app-success);">{{ $accepted }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-check-circle"></i> Confirmed
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-warning);">
                <div class="ui-card__label">Pending</div>
                <div class="ui-card__value" style="color:var(--app-warning);">{{ $pending }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-clock"></i> Awaiting
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-danger);">
                <div class="ui-card__label">Rejected</div>
                <div class="ui-card__value" style="color:var(--app-danger);">{{ $rejected }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-times-circle"></i> Declined
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-primary);">
                <div class="ui-card__label">Sent</div>
                <div class="ui-card__value" style="color:var(--app-primary);">{{ $sent }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-paper-plane"></i> Delivered
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="ui-card ui-card--stat-admin text-center" style="border-left-color: var(--app-text-subtle);">
                <div class="ui-card__label">Draft</div>
                <div class="ui-card__value" style="color:var(--app-text-subtle);">{{ $draft }}</div>
                <div style="font-size:11px;color:var(--app-text-subtle);margin-top:4px;">
                    <i class="fas fa-pen"></i> Not Sent
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

    {{-- Filter Buttons --}}
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
        <button class="filter-btn active" data-filter="all" style="padding:6px 16px;border-radius:20px;border:2px solid #8b5cf6;background:var(--app-primary-soft);color:var(--app-primary-dark);font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;">
            All
        </button>
        <button class="filter-btn" data-filter="accepted" style="padding:6px 16px;border-radius:20px;border:2px solid var(--app-border);background:var(--app-surface);color:var(--app-text-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;">
            ✅ Accepted
        </button>
        <button class="filter-btn" data-filter="pending" style="padding:6px 16px;border-radius:20px;border:2px solid var(--app-border);background:var(--app-surface);color:var(--app-text-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;">
            ⏳ Pending
        </button>
        <button class="filter-btn" data-filter="rejected" style="padding:6px 16px;border-radius:20px;border:2px solid var(--app-border);background:var(--app-surface);color:var(--app-text-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;">
            ❌ Rejected
        </button>
        <button class="filter-btn" data-filter="sent" style="padding:6px 16px;border-radius:20px;border:2px solid var(--app-border);background:var(--app-surface);color:var(--app-text-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;">
            ✉️ Sent
        </button>
        <button class="filter-btn" data-filter="draft" style="padding:6px 16px;border-radius:20px;border:2px solid var(--app-border);background:var(--app-surface);color:var(--app-text-secondary);font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s ease;">
            📄 Draft
        </button>
        <button onclick="window.print()" class="ui-button ui-button--compact" style="min-height:32px;padding:0 14px;font-size:12px;margin-left:auto;">
            <i class="fas fa-print"></i> Print
        </button>
    </div>

    {{-- Main Card --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-primary), var(--app-primary-dark));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 20px;">
            <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-list"></i> Confirmation Letters Status
            </h5>
            <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                <i class="fas fa-file"></i> {{ isset($confirmations) ? $confirmations->count() : 0 }} Records
            </span>
        </div>
        <div class="ui-card__body" style="padding:0;">
            @if(isset($confirmations) && count($confirmations) > 0)
                <div class="ui-table-wrap">
                    <table class="ui-table ui-table--dashboard" id="confirmationTable">
                        <thead>
                            <tr>
                                <th>Document ID</th>
                                <th>Applicant Name</th>
                                <th>Email</th>
                                <th>Sent Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($confirmations as $doc)
                            @php
                                $status = $doc->display_status ?? 'Draft';
                                $statusClass = '';
                                $badgeClass = '';
                                if ($status == 'Accepted') {
                                    $badgeClass = 'ui-badge--success';
                                } elseif ($status == 'Pending') {
                                    $badgeClass = 'ui-badge--warning';
                                } elseif ($status == 'Rejected') {
                                    $badgeClass = 'ui-badge--danger';
                                } elseif ($status == 'Sent') {
                                    $badgeClass = 'ui-badge--info';
                                } else {
                                    $badgeClass = '';
                                }
                            @endphp
                            <tr data-status="{{ strtolower($status) }}">
                                <td>
                                    <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                        {{ $doc->documentID }}
                                    </span>
                                </td>
                                <td style="font-weight:700;color:var(--app-text);">
                                    <i class="fas fa-user" style="color:var(--app-primary);font-size:12px;"></i>
                                    {{ $doc->full_name ?? 'N/A' }}
                                    @if(isset($doc->teacherID) && $doc->teacherID)
                                        <div style="font-size:11px;color:var(--app-success);">
                                            <i class="fas fa-user-check"></i> Teacher: {{ $doc->teacherID }}
                                        </div>
                                    @endif
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    <div style="display:flex;flex-direction:column;">
                                        <span>{{ $doc->applicant_email ?? 'N/A' }}</span>
                                        @if(isset($doc->applicant_email) && $doc->applicant_email != 'N/A')
                                            <span style="font-size:11px;color:var(--app-success);">
                                                <i class="fas fa-check-circle"></i> Valid email
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td style="font-size:13px;color:var(--app-text-secondary);">
                                    <div style="display:flex;flex-direction:column;">
                                        <span>{{ isset($doc->dateIssued) ? date('d/m/Y', strtotime($doc->dateIssued)) : 'N/A' }}</span>
                                        @if(isset($doc->dateIssued))
                                            <span style="font-size:11px;color:var(--app-text-subtle);">
                                                {{ \Carbon\Carbon::parse($doc->dateIssued)->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($status == 'Accepted')
                                        <span class="ui-badge ui-badge--success" style="font-size:11px;">
                                            <i class="fas fa-check-circle"></i> Accepted
                                        </span>
                                    @elseif($status == 'Pending')
                                        <span class="ui-badge ui-badge--warning" style="font-size:11px;">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    @elseif($status == 'Rejected')
                                        <span class="ui-badge ui-badge--danger" style="font-size:11px;">
                                            <i class="fas fa-times-circle"></i> Rejected
                                        </span>
                                    @elseif($status == 'Sent')
                                        <span class="ui-badge ui-badge--info" style="font-size:11px;">
                                            <i class="fas fa-paper-plane"></i> Sent
                                        </span>
                                    @else
                                        <span class="ui-badge" style="border-color:var(--app-border);background:var(--app-background);color:var(--app-text-subtle);font-size:11px;">
                                            <i class="fas fa-pen"></i> Draft
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(isset($confirmations) && method_exists($confirmations, 'links'))
                    <div style="padding:12px 20px;border-top:1px solid var(--app-divider);">
                        {{ $confirmations->links() }}
                    </div>
                @endif
            @else
                <div class="ui-state ui-state--compact">
                    <div style="font-size:3rem;margin-bottom:8px;">📭</div>
                    <div class="ui-state__title">No Confirmation Letters</div>
                    <div class="ui-state__copy">Send confirmation letters to applicants who accepted the offer to see them here.</div>
                    <a href="{{ route('confirmation.index') }}" class="ui-button ui-button--primary" style="margin-top:16px;">
                        <i class="fas fa-plus"></i> Send Confirmation Letters
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* Filter button styles */
    .filter-btn {
        padding: 6px 16px;
        border-radius: 20px;
        border: 2px solid var(--app-border);
        background: var(--app-surface);
        color: var(--app-text-secondary);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .filter-btn:hover {
        border-color: var(--app-primary);
        color: var(--app-primary);
    }

    .filter-btn.active {
        border-color: var(--app-primary);
        background: var(--app-primary-soft);
        color: var(--app-primary-dark);
    }

    .filter-btn[data-filter="all"] {
        border-color: #8b5cf6;
        color: #7c3aed;
    }
    .filter-btn[data-filter="all"].active {
        background: #ede9fe;
        border-color: #7c3aed;
    }

    .filter-btn[data-filter="accepted"] {
        border-color: var(--app-success);
        color: var(--app-success);
    }
    .filter-btn[data-filter="accepted"].active {
        background: var(--app-success-soft);
        border-color: var(--app-success);
    }

    .filter-btn[data-filter="pending"] {
        border-color: var(--app-warning);
        color: var(--app-warning);
    }
    .filter-btn[data-filter="pending"].active {
        background: var(--app-warning-soft);
        border-color: var(--app-warning);
    }

    .filter-btn[data-filter="rejected"] {
        border-color: var(--app-danger);
        color: var(--app-danger);
    }
    .filter-btn[data-filter="rejected"].active {
        background: var(--app-danger-soft);
        border-color: var(--app-danger);
    }

    .filter-btn[data-filter="sent"] {
        border-color: var(--app-primary);
        color: var(--app-primary);
    }
    .filter-btn[data-filter="sent"].active {
        background: var(--app-primary-soft);
        border-color: var(--app-primary);
    }

    .filter-btn[data-filter="draft"] {
        border-color: var(--app-text-subtle);
        color: var(--app-text-subtle);
    }
    .filter-btn[data-filter="draft"].active {
        background: var(--app-background);
        border-color: var(--app-text-subtle);
    }

    /* Print styles */
    @media print {
        .ui-header, .ui-page-heading__actions, .filter-btn, .ui-message, .ui-button {
            display: none !important;
        }
        .ui-page-shell {
            padding: 0 !important;
        }
        .ui-card {
            box-shadow: none !important;
            border: 1px solid var(--app-border) !important;
        }
        .ui-card__header {
            background: var(--app-primary) !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .ui-card__header h5 {
            color: #fff !important;
        }
        .ui-card__header .ui-badge {
            color: #fff !important;
        }
        .ui-table {
            font-size: 10px !important;
        }
        .ui-table th, .ui-table td {
            padding: 4px 8px !important;
        }
        .ui-badge {
            font-size: 8px !important;
            padding: 2px 6px !important;
        }
        body {
            background: white !important;
            padding: 10px !important;
        }
        .ui-page-heading {
            margin-bottom: 10px !important;
        }
        .ui-page-heading h1 {
            font-size: 18px !important;
        }
        .row.g-3 {
            gap: 8px !important;
        }
        .ui-card--stat-admin {
            padding: 8px 10px !important;
        }
        .ui-card--stat-admin .ui-card__value {
            font-size: 16px !important;
        }
        .ui-card--stat-admin .ui-card__label {
            font-size: 8px !important;
        }
    }
</style>

<script>
    // Filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const tableRows = document.querySelectorAll('#confirmationTable tbody tr');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all filter buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                
                tableRows.forEach(row => {
                    const status = row.dataset.status;
                    
                    if (filter === 'all') {
                        row.style.display = '';
                    } else if (status === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });
</script>
@endsection