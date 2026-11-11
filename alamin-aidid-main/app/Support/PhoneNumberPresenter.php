<?php

namespace App\Support;

final class PhoneNumberPresenter
{
    public static function display(mixed $value, string $fallback = 'Not available'): string
    {
        $phone = trim((string) $value);

        if ($phone === '') {
            return $fallback;
        }

        if (preg_match('/^011\d{8}$/', $phone) === 1) {
            return substr($phone, 0, 3).'-'.substr($phone, 3, 4).' '.substr($phone, 7);
        }

        if (preg_match('/^01\d{8}$/', $phone) === 1) {
            return substr($phone, 0, 3).'-'.substr($phone, 3, 3).' '.substr($phone, 6);
        }

        return $phone;
    }
}
