@extends('trainer.layouts.app')

@section('title', 'Attendance QR — '.$sessionTitle)

@push('styles')
<style>
    .qr-presentation-page {
        min-height: calc(100vh - var(--app-header-height, 58px));
        min-height: calc(100dvh - var(--app-header-height, 58px));
        box-sizing: border-box;
        padding: 40px 20px;
        background:
            radial-gradient(circle at top, rgba(37, 99, 235, .09), transparent 34rem),
            #f8fafc;
    }
    .qr-presentation-shell { width: min(720px, 100%); margin: 0 auto; }
    .qr-presentation-card {
        overflow: hidden;
        border: 1px solid #dbe3ef;
        border-radius: 22px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .12);
    }
    .qr-presentation-header { padding: 24px 28px 20px; border-bottom: 1px solid #edf2f7; text-align: center; }
    .qr-presentation-context { margin: 0 0 5px; color: #2563eb; font-size: 11px; font-weight: 850; letter-spacing: .09em; text-transform: uppercase; }
    .qr-presentation-header h1 { margin: 0; color: #0f172a; font-size: clamp(24px, 5vw, 34px); font-weight: 900; letter-spacing: -.025em; }
    .qr-presentation-session { margin: 9px 0 0; overflow-wrap: anywhere; color: #334155; font-size: clamp(16px, 3vw, 20px); font-weight: 750; }
    .qr-presentation-meta { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px 18px; margin-top: 10px; color: #64748b; font-size: 13px; font-weight: 650; }
    .qr-presentation-meta span { display: inline-flex; align-items: center; gap: 6px; }
    .qr-presentation-body { padding: 28px; text-align: center; }
    .qr-presentation-image {
        display: block;
        width: min(480px, 100%);
        aspect-ratio: 1;
        margin: 0 auto;
        object-fit: contain;
        border: 1px solid #dbeafe;
        border-radius: 18px;
        background: #fff;
    }
    .qr-presentation-instruction { margin: 20px 0 0; color: #1e293b; font-size: 15px; font-weight: 750; }
    .qr-presentation-expiry { margin: 6px 0 0; color: #64748b; font-size: 13px; font-weight: 650; }
    .qr-presentation-actions { display: flex; justify-content: center; margin-top: 22px; }
    .qr-presentation-back {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 42px;
        padding: 9px 16px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        background: #fff;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
    }
    .qr-presentation-back:hover { border-color: #94a3b8; background: #f8fafc; color: #0f172a; }
    @media (max-width: 575.98px) {
        .qr-presentation-page { padding: 20px 12px; }
        .qr-presentation-header, .qr-presentation-body { padding-right: 18px; padding-left: 18px; }
        .qr-presentation-back { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .qr-presentation-page * { transition-duration: .01ms !important; animation-duration: .01ms !important; }
    }
    @media print {
        .topbar, .qr-presentation-actions { display: none !important; }
        .qr-presentation-page { min-height: 0; padding: 0; background: #fff; }
        .qr-presentation-card { border: 0; box-shadow: none; }
    }
</style>
@endpush

@section('content')
<main class="qr-presentation-page">
    <div class="qr-presentation-shell">
        <article class="qr-presentation-card" aria-labelledby="qr-presentation-title">
            <header class="qr-presentation-header">
                <p class="qr-presentation-context">Al Amin Edu Oasis · Trainer</p>
                <h1 id="qr-presentation-title">Attendance QR</h1>
                <p class="qr-presentation-session">{{ $sessionTitle }}</p>
                <div class="qr-presentation-meta">
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i>{{ $sessionDateLabel }}</span>
                    <span><i class="bi bi-clock" aria-hidden="true"></i>{{ $sessionTimeLabel }}</span>
                </div>
            </header>

            <div class="qr-presentation-body">
                <img class="qr-presentation-image"
                     src="{{ $attendanceQr['image_url'] }}"
                     alt="Attendance QR code for {{ $sessionTitle }}"
                     referrerpolicy="no-referrer">
                <p class="qr-presentation-instruction">Scan this QR code to record your attendance.</p>
                <p class="qr-presentation-expiry">{{ $attendanceQr['expiry_label'] }}</p>
                <div class="qr-presentation-actions">
                    <a class="qr-presentation-back" href="{{ $attendanceRoute }}">
                        <i class="bi bi-arrow-left" aria-hidden="true"></i>
                        Back to Attendance
                    </a>
                </div>
            </div>
        </article>
    </div>
</main>
@endsection
