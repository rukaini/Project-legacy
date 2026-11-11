<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EmptyTableSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ── attendance_guru_baru ──────────────────────────────────────────
            // Applicant 1 & 2 — series course 2 (sessions 3 & 4)
            $ap1 = DB::table('guru_new')->where('email', 'applicant1@edutrain.test')->value('gn_id');
            $ap2 = DB::table('guru_new')->where('email', 'applicant2@edutrain.test')->value('gn_id');
            if ($ap1 && $ap2) {
                DB::table('attendance_guru_baru')->insert([
                    ['gn_id' => $ap1, 'session_id' => 3, 'timestamp_status' => 8.00, 'hours_ladap' => 4.00, 'created_at' => now(), 'updated_at' => now()],
                    ['gn_id' => $ap1, 'session_id' => 4, 'timestamp_status' => 7.00, 'hours_ladap' => 3.50, 'created_at' => now(), 'updated_at' => now()],
                    ['gn_id' => $ap2, 'session_id' => 3, 'timestamp_status' => 8.00, 'hours_ladap' => 4.00, 'created_at' => now(), 'updated_at' => now()],
                ]);
            }
            $this->command->info('Guru baru attendance created');

            // ── attendance_outsider ───────────────────────────────────────────
            // Outsider 1 — course 7 sessions (19 & 20)
            DB::table('attendance_outsider')->insert([
                ['outsider_id' => 1, 'session_id' => 19, 'timestamp_status' => 5.00, 'created_at' => now(), 'updated_at' => now()],
                ['outsider_id' => 1, 'session_id' => 20, 'timestamp_status' => 3.00, 'created_at' => now(), 'updated_at' => now()],
            ]);
            $this->command->info('Outsider attendance created');

            // ── feedback_response ─────────────────────────────────────────────
            // Teacher 1 (user_id=2, teacherID=1) responds to form 1 (course 1 — completed)
            $t1      = ['4', '4', '5', '4', 'yes', 'Great program, very informative. The group activities were particularly effective.'];
            $t1Date  = Carbon::now()->subDays(30);
            foreach (range(1, 6) as $i) {
                DB::table('feedback_response')->insert(['formID' => 1, 'questionID' => $i, 'user_id' => 2, 'answer_value' => $t1[$i - 1], 'rating' => 4, 'comment' => 'Great program overall.', 'responseDate' => $t1Date, 'teacherID' => 1, 'gn_id' => null, 'created_at' => $t1Date]);
            }
            // Teacher 2 (user_id=3, teacherID=2)
            $t2      = ['5', '5', '4', '5', 'yes', 'Appreciated the hands-on approach. Would love more time for peer discussion in future sessions.'];
            $t2Date  = Carbon::now()->subDays(29);
            foreach (range(1, 6) as $i) {
                DB::table('feedback_response')->insert(['formID' => 1, 'questionID' => $i, 'user_id' => 3, 'answer_value' => $t2[$i - 1], 'rating' => 5, 'comment' => 'Appreciated the hands-on approach.', 'responseDate' => $t2Date, 'teacherID' => 2, 'gn_id' => null, 'created_at' => $t2Date]);
            }
            $this->command->info('Feedback responses created');
        });
    }
}
