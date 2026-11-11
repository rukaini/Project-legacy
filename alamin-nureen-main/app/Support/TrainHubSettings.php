<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Settings are kept outside the shared course/teacher tables. */
final class TrainHubSettings
{
    public const DEFAULTS = [
        'new_teacher_lms_required' => 5,
        'training_target_hours' => 30,
        'tarbiah_target_hours' => 10,
        'ladap_target_hours' => 30,
        'trainer_multiplier' => 1.5,
        'alamin_multiplier' => 1.0,
        'other_multiplier' => 0.5,
        'outside_order_multiplier' => 1.0,
    ];

    public static function all(): array
    {
        $settings = self::DEFAULTS;
        if (!Schema::hasTable('trainhub_settings')) {
            return $settings;
        }
        foreach (DB::table('trainhub_settings')->get(['setting_key', 'setting_value']) as $row) {
            if (array_key_exists($row->setting_key, $settings) && is_numeric($row->setting_value)) {
                $settings[$row->setting_key] = (float) $row->setting_value;
            }
        }
        return $settings;
    }

    public static function set(array $values, string $staffID): void
    {
        if (!Schema::hasTable('trainhub_settings')) {
            throw new \RuntimeException('Run database_patches/2026_10_19_trainhub_features.sql first.');
        }
        DB::transaction(function () use ($values, $staffID) {
            foreach ($values as $key => $value) {
                if (!array_key_exists($key, self::DEFAULTS)) {
                    continue;
                }
                DB::table('trainhub_settings')->updateOrInsert(
                    ['setting_key' => $key],
                    ['setting_value' => (string) $value, 'updated_by' => $staffID, 'updated_at' => now()]
                );
            }
        });
    }
}
