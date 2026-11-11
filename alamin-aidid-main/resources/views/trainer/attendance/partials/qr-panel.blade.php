@php
    $qr = $attendanceQr ?? [
        'state' => 'missing',
        'state_label' => 'Attendance QR not available yet',
        'state_message' => 'No QR code is available for this session yet.',
        'instruction' => 'Attendance QR is not available for this session yet.',
        'can_view' => false,
        'image_url' => null,
        'expiry_label' => null,
    ];
    $sessionTitle = $selectedSession->session_title ?? 'Training Session';
    $canViewQr = ! empty($qr['can_view']);
    $disabledReason = match ($qr['state'] ?? 'missing') {
        'upcoming' => 'Available when this session starts.',
        'expired' => 'This session has ended or the QR code has expired.',
        'closed' => 'QR access is unavailable because the session schedule is incomplete.',
        default => 'QR code is not available.',
    };
@endphp

<div class="attendance-qr-panel" data-attendance-qr-panel>
    <button type="button"
            class="attendance-qr-button"
            @if ($canViewQr)
                data-bs-toggle="modal"
                data-bs-target="#attendanceQrDisplayModal"
                data-attendance-qr-open
                data-qr-src="{{ $qr['image_url'] }}"
                data-qr-presentation-url="{{ $qr['presentation_url'] }}"
                data-session-title="{{ $sessionTitle }}"
                data-session-date="{{ $selectedSession->date_label ?? 'Date not set' }}"
                data-session-time="{{ $selectedSession->time_label ?? 'Time not set' }}"
                data-qr-state="{{ $qr['state'] }}"
                data-qr-label="{{ $qr['state_label'] }}"
                data-qr-message="{{ $qr['state_message'] ?? '' }}"
                data-qr-instruction="{{ $qr['instruction'] ?? '' }}"
                data-qr-expiry="{{ $qr['expiry_label'] ?? '' }}"
                aria-label="View attendance QR code for {{ $sessionTitle }}"
            @else
                disabled
                aria-disabled="true"
                title="{{ $disabledReason }}"
                aria-label="View attendance QR code unavailable. {{ $disabledReason }}"
            @endif>
        <i class="bi bi-qr-code" aria-hidden="true"></i>
        View QR Code
    </button>
</div>
