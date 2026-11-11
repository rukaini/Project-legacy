<?php

namespace Database\Seeders;

use App\Models\AttendanceGuruBaru;
use App\Models\EnrollGuruBaru;
use App\Models\GuruNew;
use Illuminate\Database\Seeder;

class AttendanceGuruBaruSeeder extends Seeder
{
    public function run(): void
    {
        $ap1 = GuruNew::where('email', 'applicant1@edutrain.test')->value('gn_id');
        $ap2 = GuruNew::where('email', 'applicant2@edutrain.test')->value('gn_id');

        if (!$ap1 || !$ap2) {
            return;
        }

        // Applicant 1 (Nurul Aisyah) — enrolled in course 3 (Classroom Management, multi_day, ongoing)
        // and course 11 (Digital Pedagogy 21st Century, single, ongoing)
        EnrollGuruBaru::insert([
            ['gn_id' => $ap1, 'courseID' => 3,  'status' => 'approved', 'applied_at' => '2026-06-01 09:00:00'],
            ['gn_id' => $ap1, 'courseID' => 11, 'status' => 'approved', 'applied_at' => '2026-06-02 10:00:00'],
            ['gn_id' => $ap2, 'courseID' => 3,  'status' => 'approved', 'applied_at' => '2026-06-01 11:00:00'],
        ]);

        // Applicant 1 attendance
        // Course 3 — session 5 (June 7 AM, 4.5h) and session 7 (June 9 AM, 4.5h)
        // Course 11 — session 31 (June 10, 5h)
        // Course 2 (series, auto-enrolled) — no past sessions yet (sessions are July 2026)
        AttendanceGuruBaru::insert([
            [
                'gn_id'            => $ap1,
                'session_id'       => 5,      // course 3, June 7 AM 08:00-12:30
                'timestamp_status' => 4.50,
                'hours_ladap'      => 4.50,
                'created_at'       => '2026-06-07 08:30:00',
                'updated_at'       => '2026-06-07 08:30:00',
            ],
            [
                'gn_id'            => $ap1,
                'session_id'       => 7,      // course 3, June 9 AM 08:00-12:30
                'timestamp_status' => 4.50,
                'hours_ladap'      => 4.50,
                'created_at'       => '2026-06-09 08:45:00',
                'updated_at'       => '2026-06-09 08:45:00',
            ],
            [
                'gn_id'            => $ap1,
                'session_id'       => 31,     // course 11, June 10 15:00-20:00
                'timestamp_status' => 5.00,
                'hours_ladap'      => 5.00,
                'created_at'       => '2026-06-10 15:10:00',
                'updated_at'       => '2026-06-10 15:10:00',
            ],
            // Applicant 2 — course 3 session 5 only
            [
                'gn_id'            => $ap2,
                'session_id'       => 5,      // course 3, June 7 AM
                'timestamp_status' => 4.50,
                'hours_ladap'      => 4.50,
                'created_at'       => '2026-06-07 08:35:00',
                'updated_at'       => '2026-06-07 08:35:00',
            ],
        ]);
    }
}
