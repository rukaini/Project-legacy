@extends('layouts.app')

@section('title', 'Sent Offer Letters')

@section('styles')
<style>
    .page-title {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, #059669, #047857);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 1.5rem;
        letter-spacing: -0.02em;
    }

    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .badge-status {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-draft {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-pending {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .badge-secondary {
        background: #e5e7eb;
        color: #4b5563;
    }

    .table-hover tbody tr:hover {
        background: #ecfdf5;
    }

    .table thead th {
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
        font-weight: 600;
        color: #374151;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
    }

    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .card-header {
        border-bottom: 1px solid #f3f4f6;
        padding: 1.25rem 1.5rem;
    }

    .card-body {
        padding: 1.5rem;
    }

    .applicant-name {
        font-weight: 600;
        color: #1f2937;
    }

    .alert {
        border: none;
        border-radius: 16px;
        padding: 1rem 1.25rem;
        margin-bottom: 1.5rem;
        animation: slideIn 0.3s ease;
    }

    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border-left: 4px solid #059669;
    }

    .alert-danger {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #dc2626;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        opacity: 0.5;
        padding: 0 0 0 1rem;
    }

    .btn-close:hover {
        opacity: 1;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <h1 class="page-title">📨 Sent Offer Letters</h1>
    
    {{-- Success Message --}}
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span>✅</span>
        <span>{{ session('success') }}</span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <span>⚠️</span>
        <span>{{ session('error') }}</span>
        <button type="button" class="btn-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">All Sent Offer Letters</h5>
        </div>
        <div class="card-body">
            @if(isset($documents) && count($documents) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Document ID</th>
                                <th>Applicant Name</th>
                                <th>Document Title</th>
                                <th>Date Issued</th>
                                <th>Signed By</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documents as $index => $doc)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><code>{{ $doc->documentID ?? 'N/A' }}</code></td>
                                <td class="applicant-name">
                                    {{-- For Eloquent with relationship --}}
                                    @if(isset($doc->applicant) && $doc->applicant)
                                        {{ $doc->applicant->full_name ?? 'N/A' }}
                                    @else
                                        {{-- For DB facade with joined data --}}
                                        {{ $doc->full_name ?? $doc->applicant_name ?? 'N/A' }}
                                    @endif
                                </td>
                                <td>{{ $doc->documentTitle ?? $doc->document_title ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $dateField = $doc->dateIssued ?? $doc->datelssued ?? $doc->date_issued ?? $doc->issued_date ?? null;
                                    @endphp
                                    {{ $dateField ? date('d/m/Y', strtotime($dateField)) : 'N/A' }}
                                </td>
                                <td>{{ $doc->signedBy ?? $doc->signed_by ?? 'N/A' }}</td>
                                <td>
                                    @php
                                        $status = $doc->status ?? 'Unknown';
                                        $badgeClass = 'badge-status ';
                                        if(strtolower($status) == 'sent' || strtolower($status) == 'completed') {
                                            $badgeClass .= 'badge-success';
                                        } elseif(strtolower($status) == 'draft') {
                                            $badgeClass .= 'badge-draft';
                                        } elseif(strtolower($status) == 'pending') {
                                            $badgeClass .= 'badge-pending';
                                        } elseif(strtolower($status) == 'rejected') {
                                            $badgeClass .= 'badge-rejected';
                                        } else {
                                            $badgeClass .= 'badge-secondary';
                                        }
                                    @endphp
                                    <span class="{{ $badgeClass }}">{{ $status }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($documents, 'links'))
                    <div class="mt-3">
                        {{ $documents->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <div class="empty-state-icon">📭</div>
                    <h5>No sent offer letters</h5>
                    <p class="text-muted">No offer letters have been sent yet.</p>
                    <a href="{{ route('offer.letters') }}" class="btn btn-send" style="display: inline-block; padding: 0.75rem 2rem; background: linear-gradient(135deg, #059669, #047857); color: white; border: none; border-radius: 10px; font-weight: 600; text-decoration: none; margin-top: 1rem;">
                        📧 Go to Send Offer Letters
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row mt-4">
        <div class="col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Sent</h6>
                    <h3 class="mb-0" style="color: #065f46;">{{ isset($documents) ? (is_countable($documents) ? count($documents) : 0) : 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted">Pending Response</h6>
                    <h3 class="mb-0" style="color: #92400e;">
                        {{ DB::table('application')->where('application_status', 'Accepted')->where('offer_response', 'pending')->count() }}
                    </h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h6 class="text-muted">Accepted Offers</h6>
                    <h3 class="mb-0" style="color: #065f46;">
                        {{ DB::table('application')->where('application_status', 'Accepted')->where('offer_response', 'accepted')->count() }}
                    </h3>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert.parentElement) alert.remove();
            }, 500);
        });
    }, 5000);
</script>

@endsection