<?php

namespace App\Support;

final class ParticipantTypeLabel
{
    public static function display(mixed $value, string $fallback = 'Participant'): string
    {
        $label = trim((string) $value);

        if ($label === '') {
            return $fallback;
        }

        if (in_array(strtolower($label), ['outsider', 'public'], true)) {
            return 'Public';
        }

        if (in_array(strtolower($label), ['guru new', 'guru baru', 'guru_new'], true)) {
            return 'New Teacher';
        }

        return $label;
    }

    public static function audience(mixed $value, string $fallback = 'Not specified'): string
    {
        $label = trim((string) $value);

        if ($label === '') {
            return $fallback;
        }

        $canonical = [
            'teacher' => 'Teacher',
            'new_teacher' => 'New Teacher',
            'staff' => 'Staff',
            'public' => 'Public',
            'other' => 'Other',
        ];
        if (isset($canonical[strtolower($label)])) {
            return $canonical[strtolower($label)];
        }

        $label = preg_replace('/\bOutsiders?\b/i', 'Public', $label) ?: $label;

        return preg_replace('/\bGuru[ _](?:New|Baru)\b/i', 'New Teacher', $label) ?: $label;
    }
}
