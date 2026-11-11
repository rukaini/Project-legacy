@extends('layouts.app')

@section('title', 'Preview Offer Letter')

@section('content')
<div class="ui-page-shell" style="max-width:1000px;margin:0 auto;">
    {{-- Page Heading --}}
    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1 style="display:flex;align-items:center;gap:10px;">
                <i class="fas fa-file-signature" style="color:var(--app-primary);font-size:24px;"></i> Preview Offer Letter
            </h1>
            <p>Review the offer letter before sending</p>
        </div>
        <div class="ui-page-heading__actions">
            @if(isset($app) && isset($document))
                <form action="{{ route('offer.send') }}" method="POST" style="display:inline;">
                    @csrf
                    <input type="hidden" name="application_id" value="{{ $app->application_id }}">
                    <input type="hidden" name="document_id" value="{{ $document->documentID ?? $document->id ?? '' }}">
                    <input type="hidden" name="offer_date" value="{{ date('Y-m-d') }}">
                    <button type="submit" class="ui-button ui-button--primary" style="min-height:36px;padding:0 20px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
                        <i class="fas fa-paper-plane"></i> Send Offer Letter
                    </button>
                </form>
            @endif
            <a href="{{ route('offer.letters') }}" class="ui-button ui-button--compact" style="min-height:36px;padding:0 16px;display:inline-flex;align-items:center;gap:6px;font-size:13px;">
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
        <div class="ui-card__header" style="background:linear-gradient(135deg, #059669, #047857);border-radius:18px 18px 0 0;margin:-1px -1px 0 -1px;padding:16px 24px;">
            <div style="display:flex;justify-content:space-between;align-items:center;width:100%;flex-wrap:wrap;gap:10px;">
                <h5 style="margin:0;font-size:16px;font-weight:700;color:#fff;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-file-pdf"></i> Offer Letter
                </h5>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <span class="ui-badge" style="background:rgba(255,255,255,0.2);border-color:rgba(255,255,255,0.3);color:#fff;font-size:12px;padding:4px 14px;">
                        <i class="fas fa-pen"></i> Draft
                    </span>
                    @if(isset($app) && isset($app->school))
                        <span class="ui-badge" style="background:rgba(16,185,129,0.3);border-color:rgba(16,185,129,0.4);color:#a7f3d0;font-size:12px;padding:4px 14px;">
                            <i class="fas fa-school"></i> {{ $app->school->schoolName ?? 'N/A' }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="ui-card__body" style="padding:24px;">

            {{-- School Info Box --}}
            @if(isset($app) && isset($app->school))
                <div style="background:var(--app-primary-soft);border:1px solid var(--app-primary-border);border-radius:var(--app-radius-card);padding:12px 16px;margin-bottom:20px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-weight:600;color:var(--app-primary-dark);font-size:13px;display:flex;align-items:center;gap:6px;">
                        <i class="fas fa-school"></i> School:
                    </span>
                    <span style="font-weight:700;color:var(--app-text);font-size:14px;">
                        {{ $app->school->schoolName ?? 'N/A' }}
                    </span>
                    <span style="color:var(--app-text-subtle);font-size:12px;margin-left:4px;">
                        ({{ $app->school->schoolID ?? 'N/A' }})
                    </span>
                    @if(isset($app->school->vacancy))
                        <span class="ui-badge ui-badge--success" style="font-size:10px;">
                            <i class="fas fa-users"></i> {{ $app->school->vacancy }} vacancies
                        </span>
                    @endif
                </div>
            @endif

            {{-- Offer Letter Content --}}
            @if(isset($app))
                <div style="font-family:'Times New Roman',serif;line-height:1.8;padding:24px;background:var(--app-surface);border:1px solid var(--app-border);border-radius:var(--app-radius-card);min-height:500px;">
                    {!! $offerContent ?? '<p style="color:var(--app-text-subtle);text-align:center;padding:40px 0;">No content available</p>' !!}
                </div>
            @else
                <div class="ui-message ui-message--error" style="margin:0;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>Application data not found. Please go back and try again.</div>
                </div>
            @endif

            {{-- Action Buttons --}}
            <div style="display:flex;gap:12px;margin-top:24px;flex-wrap:wrap;padding-top:20px;border-top:1px solid var(--app-divider);">
                <a href="{{ route('offer.letters') }}" class="ui-button ui-button--compact" style="min-height:40px;padding:0 24px;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                
                @if(isset($app) && isset($document))
                    <form action="{{ route('offer.send') }}" method="POST" style="display:inline;">
                        @csrf
                        <input type="hidden" name="application_id" value="{{ $app->application_id }}">
                        <input type="hidden" name="document_id" value="{{ $document->documentID ?? $document->id ?? '' }}">
                        <input type="hidden" name="offer_date" value="{{ date('Y-m-d') }}">
                        <button type="submit" class="ui-button ui-button--primary" style="min-height:40px;padding:0 28px;display:inline-flex;align-items:center;gap:8px;">
                            <i class="fas fa-paper-plane"></i> Send Offer Letter
                        </button>
                    </form>
                @endif

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
        .ui-header, .ui-page-heading__actions, .ui-button, .ui-message {
            display: none !important;
        }
        .ui-page-shell {
            padding: 0 !important;
            max-width: 100% !important;
        }
        .ui-card {
            box-shadow: none !important;
            border: 1px solid #e5e7eb !important;
        }
        .ui-card__header {
            background: #059669 !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .ui-card__header h5 {
            color: #ffffff !important;
        }
        .ui-card__header .ui-badge {
            background: rgba(255,255,255,0.2) !important;
            color: #ffffff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .offer-letter-content {
            border: none !important;
            padding: 20px !important;
        }
        .school-info-box {
            background: #f0fdf4 !important;
            border: 1px solid #86efac !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }

    /* Offer letter content styling */
    .offer-letter-content h2 {
        font-size: 1.5rem;
        font-weight: bold;
        margin-bottom: 0.25rem;
    }

    .offer-letter-content h4 {
        font-size: 1.1rem;
        font-weight: 600;
    }

    .offer-letter-content h5 {
        font-size: 1rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }

    .offer-letter-content hr {
        border: 1px solid #333;
        margin: 1rem 0;
    }

    .offer-letter-content .text-center {
        text-align: center;
    }

    .offer-letter-content .mt-4 {
        margin-top: 1.5rem;
    }

    .offer-letter-content .mt-5 {
        margin-top: 3rem;
    }

    .offer-letter-content .mb-4 {
        margin-bottom: 1.5rem;
    }

    .offer-letter-content .mb-5 {
        margin-bottom: 3rem;
    }
</style>

<script>
    // Auto-hide messages after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.ui-message');
        alerts.forEach(function(alert) {
            // Don't hide error messages
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