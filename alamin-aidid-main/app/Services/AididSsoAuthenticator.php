<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AididSsoAuthenticator
{
    public function resolve(array $identity): ?array
    {
        $role = (string) ($identity['role'] ?? '');
        $source = (string) ($identity['source'] ?? '');
        $id = (string) ($identity['id'] ?? '');
        $email = strtolower(trim((string) ($identity['email'] ?? '')));

        if ($id === '' || $email === '') {
            return null;
        }

        return match ($role) {
            'staff_edu' => $source === 'staff_edu' ? $this->staff($id, $email) : null,
            'trainer' => $source === 'trainer' ? $this->trainer($id, $email) : null,
            default => null,
        };
    }

    private function staff(string $id, string $email): ?array
    {
        $staff = DB::table('staff_edu')
            ->where('staffID', $id)
            ->whereRaw('LOWER(TRIM(`email`)) = ?', [$email])
            ->first();

        if (! $staff || ! $this->isActive($staff->status ?? null)) {
            return null;
        }

        return [
            'user_id' => (string) $staff->staffID,
            'user_name' => (string) $staff->staffName,
            'user_email' => strtolower(trim((string) $staff->email)),
            'user_role' => 'admin',
            'dashboard' => 'admin.dashboard',
        ];
    }

    private function trainer(string $id, string $email): ?array
    {
        $trainer = DB::table('trainer')
            ->where('trainerID', $id)
            ->whereRaw('LOWER(TRIM(`trainerEmail`)) = ?', [$email])
            ->first();

        if (! $trainer || ! $this->isActive($trainer->status ?? null)) {
            return null;
        }

        return [
            'user_id' => (string) $trainer->trainerID,
            'user_name' => (string) $trainer->trainerName,
            'user_email' => strtolower(trim((string) $trainer->trainerEmail)),
            'user_role' => 'trainer',
            'dashboard' => 'trainer.dashboard',
        ];
    }

    private function isActive(mixed $status): bool
    {
        return in_array(strtolower(trim((string) $status)), ['active', 'aktif'], true);
    }
}
