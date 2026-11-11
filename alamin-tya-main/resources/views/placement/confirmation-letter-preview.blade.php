@extends('layouts.app')

@section('title', 'Confirmation Letter Preview')

@section('content')
<div class="ui-page-shell" style="max-width:1000px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-file-contract" style="color:var(--app-primary);font-size:24px;"></i> Confirmation Letter Preview
            </h1>
            <p>Review the confirmation letter before sending</p>
        </div>
        <div class="ui-page-heading__actions">
            <span class="ui-badge" style="border-color:#fde68a;background:#fffbeb;color:#92400e;font-size:12px;">
                <i class="fas fa-pen"></i> Draft
            </span>
            <a href="{{ route('confirmation.index') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button onclick="window.print()" class="ui-button ui-button--outline" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('error'))
        <div class="ui-message ui-message--error" style="margin-bottom:16px;">
            <i class="fas fa-exclamation-triangle"></i>
            <div>{{ session('error') }}</div>
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

    {{-- Main Card --}}
    <div class="ui-card ui-card--section">
        <div class="ui-card__header" style="background:linear-gradient(135deg, var(--app-success), var(--app-success));border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:10px;">
                <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-file-pdf"></i> Confirmation Letter
                </h5>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                        <i class="fas fa-pen"></i> Draft
                    </span>
                </div>
            </div>
        </div>
        <div class="ui-card__body" style="padding:24px;">

            {{-- Applicant Information --}}
            @if(isset($app))
                <div style="background:var(--app-background);border:1px solid var(--app-border);border-radius:var(--app-radius-card);padding:16px 20px;margin-bottom:20px;">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
                        <div>
                            <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                                <i class="fas fa-user" style="color:var(--app-primary);"></i> Applicant Name
                            </div>
                            <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                                {{ $app->applicant->full_name ?? 'N/A' }}
                            </div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                                <i class="fas fa-envelope" style="color:var(--app-primary);"></i> Email
                            </div>
                            <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                                {{ $app->applicant->email ?? 'N/A' }}
                            </div>
                        </div>
                        <div>
                            <div style="font-size:10px;font-weight:850;color:var(--app-text-subtle);letter-spacing:0.06em;text-transform:uppercase;margin-bottom:4px;">
                                <i class="fas fa-id-card" style="color:var(--app-primary);"></i> IC Number
                            </div>
                            <div style="font-size:14px;font-weight:600;color:var(--app-text);">
                                {{ $app->ic_number ?? 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Confirmation Letter Content --}}
            @if(isset($app))
                <div style="font-family:'Times New Roman',serif;line-height:1.8;padding:24px;background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius-card);min-height:500px;">
                    {!! $confirmationContent ?? '<p style="color:var(--app-text-subtle);text-align:center;padding:40px 0;">No content available</p>' !!}
                </div>
            @else
                <div class="ui-message ui-message--error" style="margin:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Application data not found. Please go back and try again.</div>
                </div>
            @endif

            {{-- Send Form --}}
            @if(isset($app) && isset($application))
                <div style="background:var(--app-primary-soft);border:1px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:16px 20px;margin-top:20px;display:flex;flex-wrap:wrap;align-items:center;gap:12px;">
                    <form action="{{ route('confirmation.send') }}" method="POST" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px;flex:1;">
                        @csrf
                        <input type="hidden" name="application_id" value="{{ $application->application_id }}">
                        
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <label style="font-size:13px;font-weight:700;color:var(--app-text-secondary);display:flex;align-items:center;gap:4px;">
                                <i class="fas fa-calendar-alt" style="color:var(--app-primary);"></i> Confirmation Date:
                            </label>
                            <input type="date" name="confirmation_date" id="confirmation_date" 
                                   value="{{ date('Y-m-d') }}" 
                                   style="padding:8px 12px;border:1px solid var(--app-border);border-radius:var(--app-radius-control);font-size:13px;background:var(--app-surface);min-width:160px;" 
                                   required>
                        </div>
                        
                        <button type="submit" class="ui-button ui-button--primary" style="min-height:40px;padding:0 24px;display:inline-flex;align-items:center;gap:6px;font-size:13px;" onclick="return confirm('Are you sure you want to send this confirmation letter?')">
                            <i class="fas fa-paper-plane"></i> Send Confirmation Letter
                        </button>
                    </form>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;margin-top:20px;padding-top:16px;border-top:1px solid var(--app-divider);flex-wrap:wrap;">
                <a href="{{ route('confirmation.index') }}" class="ui-button ui-button--compact" style="min-height:40px;padding:0 24px;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <button onclick="window.print()" class="ui-button ui-button--outline" style="min-height:40px;padding:0 24px;display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Print styles */
    @media print {
        .ui-header, .ui-page-heading__actions, .ui-button, .ui-message, .send-form, form {
            display: none !important;
        }
        .ui-page-shell {
            padding: 0 !important;
            max-width: 100% !important;
        }
        .ui-card {
            box-shadow: none !important;
            border: 1px solid var(--app-border) !important;
        }
        .ui-card__header {
            background: var(--app-success) !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .ui-card__header h5 {
            color: #ffffff !important;
        }
        .ui-card__header .ui-badge {
            color: #ffffff !important;
            background: rgba(255,255,255,0.2) !important;
        }
        .confirmation-letter-content {
            border: none !important;
            padding: 20px !important;
        }
        .applicant-info {
            background: var(--app-background) !important;
            border: 1px solid var(--app-border) !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            background: white !important;
            padding: 10px !important;
        }
    }

    /* Confirmation letter content styling */
    .confirmation-letter-content h2 {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .confirmation-letter-content h4 {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .confirmation-letter-content h5 {
        font-size: 1rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .confirmation-letter-content hr {
        border: 1px solid #333;
        margin: 1rem 0;
    }

    .confirmation-letter-content .text-center {
        text-align: center;
    }

    .confirmation-letter-content .mt-4 {
        margin-top: 1.5rem;
    }

    .confirmation-letter-content .mt-5 {
        margin-top: 3rem;
    }

    .confirmation-letter-content .mb-4 {
        margin-bottom: 1.5rem;
    }

    .confirmation-letter-content .mb-5 {
        margin-bottom: 3rem;
    }

    .confirmation-letter-content ul {
        padding-left: 20px;
    }

    .confirmation-letter-content ul li {
        margin-bottom: 0.5rem;
    }
</style>

<script>
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

    // Set minimum date to today
    document.addEventListener('DOMContentLoaded', function() {
        var dateInput = document.getElementById('confirmation_date');
        if (dateInput) {
            var today = new Date().toISOString().split('T')[0];
            dateInput.setAttribute('min', today);
        }
    });
</script>
@endsection