<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 3 courses that demonstrate each LADAP multiplier tier:
 *   Course A – External organiser          → 0.5× for all teachers
 *   Course B – EDU Al-amin, regular teacher → 1.0×
 *   Course C – EDU Al-amin, teacher1 is a registered trainer → 1.5×
 *
 * Each course has one past session (with attendance already recorded) and
 * one session tomorrow at 15:00–17:00 for live demo QR scanning.
 */
class DemoLadapSeeder extends Seeder
{
    public function run(): void
    {
        $tomorrow = Carbon::parse('2026-06-24');
        $now      = Carbon::now();

        // ── Resolve teacher IDs ──────────────────────────────────────────
        $t1 = DB::table('teacher')->where('email', 'teacher1@edutrain.test')->first();
        $t2 = DB::table('teacher')->where('email', 'teacher2@edutrain.test')->first();
        $t3 = DB::table('teacher')->where('email', 'teacher3@edutrain.test')->first();

        // ── Add teacher1 as a registered trainer (for 1.5× on EDU Al-amin courses) ──
        $trainerExists = DB::table('trainer')->where('trainer_ic', $t1->ICNumber)->exists();
        $trainerId = null;
        if (! $trainerExists) {
            $trainerId = DB::table('trainer')->insertGetId([
                'trainer_name'    => $t1->teacherName,
                'trainer_ic'      => $t1->ICNumber,
                'trainer_email'   => $t1->email,
                'trainer_phoneNo' => $t1->phoneNumber,
                'expertise'       => 'Digital Pedagogy, 21st Century Learning, Instructional Coaching',
                'trainer_pic'     => null,
                'invitation_letter' => null,
                'average_rating'  => '4.70',
                'status'          => 'active',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        } else {
            $trainerId = DB::table('trainer')->where('trainer_ic', $t1->ICNumber)->value('trainer_id');
        }

        // ── Course A: External organiser → 0.5× ─────────────────────────
        $courseA = $this->upsertCourse([
            'courseName'     => 'Teknologi Pendidikan Digital Sekolah',
            'course_category' => 'multi_day',
            'description'     => 'Program peningkatan kompetensi digital guru dalam pengajaran abad ke-21. Peserta akan didedahkan kepada alat pedagogi digital terkini, reka bentuk pembelajaran bermakna, dan strategi penilaian berasaskan teknologi. Dianjurkan oleh Jabatan Pendidikan Negeri Selangor.',
            'capacity'        => 30,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'Jabatan Pendidikan Negeri Selangor',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => null,
            'poster'          => null,
        ]);

        // ── Course B: EDU Al-amin, regular teacher → 1.0× ───────────────
        $courseB = $this->upsertCourse([
            'courseName'     => 'Al Amin Innovative Pedagogy Programme',
            'course_category' => 'multi_day',
            'description'     => 'Program dalaman EDU Al-amin untuk membangunkan amalan pedagogi inovatif dalam kalangan guru. Kursus ini merangkumi pembelajaran aktif, reka bentuk instruksional dan penilaian autentik bagi meningkatkan keberkesanan pengajaran di sekolah Al Amin.',
            'capacity'        => 25,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'EDU Al-amin',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => null,
            'poster'          => null,
        ]);

        // ── Course C: EDU Al-amin + teacher1 is trainer → 1.5× ──────────
        $courseC = $this->upsertCourse([
            'courseName'     => 'Al Amin Certified Trainer Development',
            'course_category' => 'multi_day',
            'description'     => 'Program pembangunan jurulatih dalaman EDU Al-amin. Peserta yang merupakan jurulatih berdaftar akan mendapat nilai LADAP 1.5 kali ganda setiap jam latihan. Kursus ini meliputi kemahiran fasilitasi, reka bentuk kurikulum dan pembinaan bahan latihan profesional.',
            'capacity'        => 20,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'EDU Al-amin',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => null,
            'poster'          => null,
        ]);

        // ── Sessions ─────────────────────────────────────────────────────
        // Course A sessions
        $sesA1 = $this->upsertSession($courseA, '2026-06-10', '09:00:00', '11:00:00', 'Dewan Serbaguna, Jabatan Pendidikan Selangor, Shah Alam', $now);
        $sesA2 = $this->upsertSession($courseA, '2026-06-24', '15:00:00', '19:00:00', 'Dewan Serbaguna, Jabatan Pendidikan Selangor, Shah Alam', $now);

        // Course B sessions
        $sesB1 = $this->upsertSession($courseB, '2026-06-11', '09:00:00', '11:00:00', 'Bilik Latihan Utama, EDU Al-amin HQ, Kuala Lumpur', $now);
        $sesB2 = $this->upsertSession($courseB, '2026-06-24', '15:00:00', '19:00:00', 'Bilik Latihan Utama, EDU Al-amin HQ, Kuala Lumpur', $now);

        // Course C sessions
        $sesC1 = $this->upsertSession($courseC, '2026-06-12', '09:00:00', '11:00:00', 'Pusat Latihan Jurulatih, EDU Al-amin HQ, Kuala Lumpur', $now);
        $sesC2 = $this->upsertSession($courseC, '2026-06-24', '15:00:00', '19:00:00', 'Pusat Latihan Jurulatih, EDU Al-amin HQ, Kuala Lumpur', $now);

        // ── Assign trainer1 (teacher1) to Course C sessions ─────────────
        foreach ([$sesC1, $sesC2] as $sesId) {
            $exists = DB::table('session_trainer')->where('session_id', $sesId)->where('trainer_id', $trainerId)->exists();
            if (! $exists) {
                DB::table('session_trainer')->insert(['session_id' => $sesId, 'trainer_id' => $trainerId]);
            }
        }

        // ── Enroll teachers ──────────────────────────────────────────────
        // Course A: teacher1, teacher2, teacher3 approved
        foreach ([$t1, $t2, $t3] as $t) {
            $this->upsertEnroll($t->teacherID, $courseA, 'approved', '2026-05-20 09:00:00', $now);
        }
        // Course B: teacher1, teacher2, teacher3 approved
        foreach ([$t1, $t2, $t3] as $t) {
            $this->upsertEnroll($t->teacherID, $courseB, 'approved', '2026-05-22 09:00:00', $now);
        }
        // Course C: teacher1, teacher2 approved (trainer development)
        foreach ([$t1, $t2] as $t) {
            $this->upsertEnroll($t->teacherID, $courseC, 'approved', '2026-05-25 09:00:00', $now);
        }

        // ── Past attendance records ───────────────────────────────────────
        // Course A session 1 — 2 hrs × 0.5 = 1.0 LADAP
        $this->upsertAttendance($t1->teacherID, $sesA1, 2.0, 1.0, $now);
        $this->upsertAttendance($t2->teacherID, $sesA1, 2.0, 1.0, $now);
        $this->upsertAttendance($t3->teacherID, $sesA1, 2.0, 1.0, $now);

        // Course B session 1 — 2 hrs × 1.0 = 2.0 LADAP
        $this->upsertAttendance($t1->teacherID, $sesB1, 2.0, 2.0, $now);
        $this->upsertAttendance($t2->teacherID, $sesB1, 2.0, 2.0, $now);
        $this->upsertAttendance($t3->teacherID, $sesB1, 2.0, 2.0, $now);

        // Course C session 1 — 2 hrs × 1.5 = 3.0 LADAP (teacher1 is trainer)
        //                      2 hrs × 1.0 = 2.0 LADAP (teacher2 is NOT a trainer)
        $this->upsertAttendance($t1->teacherID, $sesC1, 2.0, 3.0, $now);
        $this->upsertAttendance($t2->teacherID, $sesC1, 2.0, 2.0, $now);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function upsertCourse(array $data): int
    {
        $existing = DB::table('course')->where('courseName', $data['courseName'])->first();
        if ($existing) return $existing->courseID;
        return DB::table('course')->insertGetId(array_merge($data, [
            'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function upsertSession(int $courseId, string $date, string $start, string $end, string $location, Carbon $now): int
    {
        $existing = DB::table('course_session')
            ->where('courseID', $courseId)
            ->where('session_date', $date)
            ->where('start_time', $start)
            ->first();
        if ($existing) return $existing->session_id;
        return DB::table('course_session')->insertGetId([
            'courseID'     => $courseId,
            'session_date'  => $date,
            'start_time'    => $start,
            'end_time'      => $end,
            'location'      => $location,
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    private function upsertEnroll(int $teacherId, int $courseId, string $status, string $appliedAt, Carbon $now): void
    {
        $exists = DB::table('enroll_teacher')
            ->where('teacherID', $teacherId)
            ->where('courseID', $courseId)
            ->exists();
        if (! $exists) {
            DB::table('enroll_teacher')->insert([
                'teacherID'        => $teacherId,
                'courseID'        => $courseId,
                'status'           => $status,
                'rejection_reason' => null,
                'applied_at'       => $appliedAt,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    private function upsertAttendance(int $teacherId, int $sessionId, float $hours, float $ladap, Carbon $now): void
    {
        $exists = DB::table('attendance')
            ->where('teacher_id', $teacherId)
            ->where('session_id', $sessionId)
            ->exists();
        if (! $exists) {
            DB::table('attendance')->insert([
                'teacher_id'       => $teacherId,
                'session_id'       => $sessionId,
                'timestamp_status' => $hours,
                'hours_ladap'      => $ladap,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }
}
