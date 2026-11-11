<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ParticipantMaterialAccessService
{
    public const TABLE = 'participant_material_access';

    public function statesForCourses(Collection $courseIds, ?CarbonInterface $now = null): Collection
    {
        $courseIds = $courseIds->map(fn ($courseId): string => (string) $courseId)->unique()->values();
        $rows = Schema::hasTable(self::TABLE)
            ? DB::table(self::TABLE)->whereIn('course_id', $courseIds)->get()->keyBy('course_id')
            : collect();
        $now ??= Carbon::now((string) config('app.timezone'));

        return $courseIds->mapWithKeys(fn (string $courseId): array => [
            $courseId => $this->present($rows->get($courseId), $now),
        ]);
    }

    public function save(string $courseId, bool $isEnabled, ?CarbonInterface $accessUntil): void
    {
        $timestamp = Carbon::now((string) config('app.timezone'))->format('Y-m-d H:i:s');

        DB::table(self::TABLE)->upsert([[
            'course_id' => $courseId,
            'is_enabled' => $isEnabled,
            'access_until' => $isEnabled && $accessUntil ? $accessUntil->format('Y-m-d H:i:s') : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]], ['course_id'], ['is_enabled', 'access_until', 'updated_at']);
    }

    private function present(?object $row, CarbonInterface $now): array
    {
        $isEnabled = (bool) ($row->is_enabled ?? false);
        $accessUntil = ! empty($row->access_until)
            ? Carbon::parse((string) $row->access_until, (string) config('app.timezone'))
            : null;
        $isExpired = $isEnabled && $accessUntil !== null && $accessUntil->lt($now);
        $effectiveAccess = $isEnabled && ! $isExpired;

        return [
            'isEnabled' => $isEnabled,
            'accessUntil' => $accessUntil?->format('Y-m-d H:i:s'),
            'accessUntilInput' => $accessUntil?->format('Y-m-d\TH:i'),
            'accessUntilLabel' => $accessUntil?->format('d F Y, g:i A'),
            'isExpired' => $isExpired,
            'effectiveAccess' => $effectiveAccess,
            'stateKey' => ! $isEnabled ? 'disabled' : ($isExpired ? 'expired' : 'enabled'),
            'stateLabel' => ! $isEnabled ? 'Disabled' : ($isExpired ? 'Expired' : 'Enabled'),
        ];
    }
}
