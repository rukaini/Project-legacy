@extends('layouts.app')

@section('title', 'Offer Tracking')

@section('styles')
<style>
    :root {
        --primary-green: #059669;
        --primary-dark: #047857;
        --primary-light: #10b981;
        --primary-soft: #d1fae5;
        --primary-bg: #ecfdf5;
        --gray-50: #f9fafb;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-600: #4b5563;
        --gray-700: #374151;
        --gray-800: #1f2937;
        --gray-900: #111827;
    }

    body {
        background: linear-gradient(135deg, #eef9f2 0%, #e0f2e9 100%);
        font-family: 'Inter', 'Poppins', system-ui, -apple-system, sans-serif;
        min-height: 100vh;
    }

    .page-title {
        font-size: 2rem;
        font-weight: 700;
        background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        margin-bottom: 0;
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title::before {
        content: "📊";
        font-size: 2rem;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.1);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
    }

    .stat-card.pending::before { background: #f59e0b; }
    .stat-card.accepted::before { background: #10b981; }
    .stat-card.rejected::before { background: #ef4444; }

    .stat-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 800;
        margin: 0;
        line-height: 1;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--gray-600);
        margin-top: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .stat-trend {
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 20px;
        background: var(--gray-100);
    }

    .main-card {
        border: none;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .main-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    }

    .card-header-custom {
        background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }

    .card-header-custom h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .card-header-custom h5::before {
        content: "📋";
        font-size: 1.2rem;
    }

    .card-body-custom {
        padding: 1.5rem;
    }

    .table-custom {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .table-custom thead th {
        background: var(--gray-50);
        padding: 1rem;
        font-weight: 600;
        color: var(--gray-700);
        border-bottom: 2px solid var(--gray-200);
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-custom tbody td {
        padding: 1rem;
        vertical-align: middle;
        color: var(--gray-600);
        border-bottom: 1px solid var(--gray-100);
    }

    .table-custom tbody tr {
        transition: background 0.2s ease;
    }

    .table-custom tbody tr:hover {
        background: var(--primary-bg);
    }

    .applicant-name {
        font-weight: 700;
        color: var(--gray-800);
    }

    .badge-custom {
        padding: 0.375rem 0.875rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
    }

    .badge-pending {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-pending::before {
        content: "⏳";
        font-size: 0.7rem;
    }

    .badge-accepted {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-accepted::before {
        content: "✅";
        font-size: 0.7rem;
    }

    .badge-rejected {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-rejected::before {
        content: "❌";
        font-size: 0.7rem;
    }

    .btn-back {
        background: white;
        border: 1px solid var(--gray-200);
        color: var(--gray-700);
        padding: 0.625rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
    }

    .btn-back:hover {
        background: var(--gray-50);
        border-color: var(--gray-300);
        transform: translateY(-1px);
        color: var(--gray-800);
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
    }

    .empty-state-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .empty-state h5 {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--gray-700);
        margin-bottom: 0.5rem;
    }

    .empty-state p {
        color: var(--gray-500);
        margin-bottom: 0;
    }

    code {
        background: var(--gray-100);
        padding: 0.2rem 0.4rem;
        border-radius: 6px;
        font-size: 0.75rem;
    }

    .text-success {
        color: #065f46 !important;
    }
    .text-danger {
        color: #dc2626 !important;
    }
    .text-muted {
        color: var(--gray-600) !important;
    }

    @media (max-width: 768px) {
        .page-title {
            font-size: 1.5rem;
        }
        .stats-container {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        .stat-number {
            font-size: 1.75rem;
        }
        .table-custom {
            min-width: 600px;
        }
        .card-body-custom {
            padding: 1rem;
        }
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    
    {{-- Header Section --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <h2 class="page-title">Offer Tracking</h2>
        <a href="{{ route('offer.index') }}" class="btn-back">
            ← Back to Send Offer
        </a>
    </div>

    {{-- Main Content Card --}}
    <div class="main-card">
        <div class="card-header-custom">
            <h5>Offer Responses</h5>
        </div>
        <div class="card-body-custom">
            @if(isset($applications) && count($applications) > 0)
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Applicant Name</th>
                                <th>Email Address</th>
                                <th>IC Number</th>
                                <th>Offer Sent Date</th>
                                <th>Response</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($applications as $index => $app)
                            <tr>
                                <td><span class="badge-custom badge-pending" style="background: var(--gray-100); color: var(--gray-600); padding: 0.25rem 0.5rem;">{{ $index + 1 }}</span></td>
                                <td class="applicant-name">
                                    {{-- FIXED: Show the applicant name --}}
                                    {{ $app->full_name ?? $app->name ?? 'N/A' }}
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        @php
                                            $email = $app->applicant_email ?? $app->email ?? null;
                                        @endphp
                                        <span>{{ $email ?? 'N/A' }}</span>
                                        @if($email)
                                            <small class="text-success" style="font-size: 0.65rem;">✓ Valid email</small>
                                        @else
                                            <small class="text-danger" style="font-size: 0.65rem;">⚠ No email</small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{-- ic_number is from application table --}}
                                    <code>{{ $app->ic_number ?? 'N/A' }}</code>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        @php
                                            $dateField = $app->updated_at ?? $app->offer_sent_date ?? $app->application_date ?? null;
                                        @endphp
                                        <span>{{ $dateField ? date('d/m/Y', strtotime($dateField)) : '-' }}</span>
                                        @if($dateField)
                                            <small class="text-muted" style="font-size: 0.65rem;">
                                                {{ \Carbon\Carbon::parse($dateField)->diffForHumans() }}
                                            </small>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $response = strtolower($app->offer_response ?? 'pending');
                                    @endphp
                                    @if($response == 'accepted')
                                        <span class="badge-custom badge-accepted">Accepted</span>
                                    @elseif($response == 'rejected')
                                        <span class="badge-custom badge-rejected">Rejected</span>
                                    @else
                                        <span class="badge-custom badge-pending">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($applications, 'links'))
                    <div class="mt-3">
                        {{ $applications->links() }}
                    </div>
                @endif
            @else
                <div class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <h5>No Offer Responses Yet</h5>
                    <p>Send offer letters to see responses here.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection