<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Material access for the three courses in the shared database. Run manually:
 *   php artisan db:seed --class=ParticipantMaterialAccessSeeder
 */
class ParticipantMaterialAccessSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            // C0001 = Leadership 2.0 — material access disabled
            ['course_id' => 'C0001', 'is_enabled' => 0, 'access_until' => null],
            // C0002 = Administration — enabled, no expiry
            ['course_id' => 'C0002', 'is_enabled' => 1, 'access_until' => null],
            // C0003 = Demo Hari Ini — enabled until 31 December 2026
            ['course_id' => 'C0003', 'is_enabled' => 1, 'access_until' => '2026-12-31 23:59:00'],
        ];

        foreach ($rows as $row) {
            DB::table('participant_material_access')->updateOrInsert(
                ['course_id' => $row['course_id']],
                $row + ['created_at' => $now, 'updated_at' => $now],
            );
        }
    }
}
