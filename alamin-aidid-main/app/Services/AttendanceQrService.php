<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AttendanceQrService
{
    /**
     * Return presentation-safe QR information for one local course session.
     *
     * This service is intentionally read-only. QR creation and lifecycle
     * management remain the responsibility of the external QR owner.
     */
    public function forSession(object $session, ?string $courseId = null): array
    {
        $sessionId = trim((string) ($session->session_id ?? $session->sessionID ?? ''));

        if ($sessionId === '' || ! Schema::hasTable('qr_session')) {
            return $this->missingState();
        }

        $now = Carbon::now($this->timezone());
        $sessionDate = $this->sessionDate($session->sessionDate ?? $session->date ?? null);
        $sessionStartsAt = $this->sessionStartsAt($session, $sessionDate);
        $sessionEndsAt = $this->sessionEndsAt($session, $sessionDate);
        $rows = DB::table('qr_session')
            ->where('sessionID', $this->externalSessionId($sessionId))
            ->orderByDesc('expiryTime')
            ->orderByDesc('qrID')
            ->get(['qrID', 'qrCode', 'expiryTime']);

        if ($rows->isEmpty()) {
            return $this->missingState();
        }

        $candidates = $rows->map(function (object $row) use ($now, $sessionStartsAt, $sessionEndsAt): array {
            $explicitExpiry = $this->dateTime($row->expiryTime ?? null);
            $closesAt = $this->effectiveClose($sessionEndsAt, $explicitExpiry);
            $state = $this->state($now, $sessionStartsAt, $closesAt);

            return compact('row', 'closesAt', 'state');
        });

        // Prefer the one currently viewable row, then retain expiryTime/qrID
        // ordering only to normalize a deterministic non-viewable state.
        $candidate = $candidates->first(fn (array $candidate): bool => $candidate['state'] === 'active')
            ?? $candidates->first();
        $state = $candidate['state'];
        $closesAt = $candidate['closesAt'];
        $imageUrl = $this->safeImageUrl($candidate['row']->qrCode ?? null);
        $canView = $state === 'active' && $imageUrl !== null;

        return [
            'state' => $state,
            'state_label' => match ($state) {
                'upcoming' => 'QR scheduled',
                'expired' => 'QR expired',
                'closed' => 'QR unavailable',
                default => 'QR available',
            },
            'state_message' => match ($state) {
                'upcoming' => 'This session has not started yet.',
                'expired' => 'This QR code is no longer active for attendance.',
                'closed' => 'The session schedule does not provide a safe QR viewing window.',
                default => 'This QR code is currently active for attendance.',
            },
            'instruction' => $canView ? 'Scan this QR code to record your attendance.' : null,
            'can_view' => $canView,
            'image_url' => $canView ? $imageUrl : null,
            'presentation_url' => $canView && $courseId !== null && trim((string) $courseId) !== ''
                ? route('trainer.courses.attendance.qr', [$courseId, $sessionId])
                : null,
            'starts_at' => $sessionStartsAt,
            'ends_at' => $sessionEndsAt,
            'closes_at' => $closesAt,
            'expiry_label' => $canView ? $this->closeLabel($closesAt) : null,
        ];
    }

    /** Canonical sessions and QR rows share the same varchar(20) sessionID. */
    private function externalSessionId(string $sessionId): string
    {
        return $sessionId;
    }

    private function state(Carbon $now, ?Carbon $startsAt, ?Carbon $closesAt): string
    {
        if (! $startsAt || ! $closesAt) {
            return 'closed';
        }

        if ($now->lessThan($startsAt)) {
            return 'upcoming';
        }

        if ($now->greaterThan($closesAt)) {
            return 'expired';
        }

        return 'active';
    }

    private function sessionStartsAt(object $session, ?Carbon $sessionDate): ?Carbon
    {
        if (! $sessionDate) {
            return null;
        }

        $startTime = $this->firstPopulated(
            $session->startTime ?? null,
            $session->start_time ?? null,
            $session->time ?? null,
        );

        return $startTime === null ? null : $this->dateAndTime($sessionDate, $startTime);
    }

    private function sessionEndsAt(object $session, ?Carbon $sessionDate): ?Carbon
    {
        if (! $sessionDate) {
            return null;
        }

        $endTime = $this->firstPopulated($session->endTime ?? null, $session->end_time ?? null);

        return $endTime === null ? null : $this->dateAndTime($sessionDate, $endTime);
    }

    private function effectiveClose(?Carbon $sessionEndsAt, ?Carbon $explicitExpiry): ?Carbon
    {
        if (! $sessionEndsAt) {
            return null;
        }

        if (! $explicitExpiry || $sessionEndsAt->lessThanOrEqualTo($explicitExpiry)) {
            return $sessionEndsAt;
        }

        return $explicitExpiry;
    }

    private function sessionDate(mixed $value): ?Carbon
    {
        $date = $this->dateTime($value);

        return $date?->startOfDay();
    }

    private function dateAndTime(Carbon $date, mixed $time): ?Carbon
    {
        return $this->dateTime($date->toDateString().' '.trim((string) $time));
    }

    private function dateTime(mixed $value): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value, $this->timezone());
        } catch (Throwable) {
            return null;
        }
    }

    private function firstPopulated(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function closeLabel(?Carbon $closesAt): ?string
    {
        if (! $closesAt) {
            return null;
        }

        $formatted = $closesAt->format('d M Y, h:i A');

        return 'Available until '.$formatted;
    }

    private function safeImageUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)
            ? $url
            : null;
    }

    private function timezone(): string
    {
        return (string) config('app.timezone', 'Asia/Kuala_Lumpur');
    }

    private function missingState(): array
    {
        return [
            'state' => 'missing',
            'state_label' => 'Attendance QR not available yet',
            'state_message' => 'No QR code is available for this session yet.',
            'instruction' => 'Attendance QR is not available for this session yet.',
            'can_view' => false,
            'image_url' => null,
            'presentation_url' => null,
            'starts_at' => null,
            'ends_at' => null,
            'closes_at' => null,
            'expiry_label' => null,
        ];
    }
}
