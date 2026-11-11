<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CanonicalIdentityEligibilityService
{
    public function isActive(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['active', 'aktif'], true);
    }

    public function teacherIsActive(string $teacherId, bool $lockForUpdate = false): bool
    {
        if (! Schema::hasTable('assign')
            || ! Schema::hasColumns('assign', ['schoolID', 'teacherID', 'assignDate', 'status'])) {
            return false;
        }

        $query = DB::table('assign')
            ->where('teacherID', $teacherId)
            ->orderByDesc('assignDate');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $assignment = $query->first();

        // Teachers without an assignment remain eligible under the currently approved rule.
        return ! $assignment || $this->isActive($assignment->status ?? null);
    }
}
