<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Adds 6 new courses (3 single, 3 multi_day) covering all LADAP tiers:
 *   0.5× — external organiser (courses 22, 23)
 *   1.0× — EDU Al-amin, teacher is NOT a registered trainer (e.g. teacher4)
 *   1.5× — EDU Al-amin, teacher IS a registered trainer (e.g. teacher5 once added)
 *
 * All courses have a session today (2026-06-24) at 16:00–18:00.
 * Multi-day courses also have one completed past session with attendance.
 *
 * Trainer setup:
 *   trainer5 — teacher5 (Mohd Zulkifli), so teacher5 earns 1.5× on EDU Al-amin courses
 *   trainer6 — Dr. Siti Rashidah (external expert)
 *
 * Enrollments:
 *   teacher5 → all 6 courses (0.5× on external; 1.5× on courses 24/25/27; 1.0× on course 26 — not a session trainer there)
 *   teacher4 → EDU Al-amin courses only (demonstrates 1.0× as non-trainer)
 *   teacher1 → EDU Al-amin courses (already trainer4, demonstrates 1.5×)
 */
class MoreCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $today = '2026-06-24';
        $now   = Carbon::now();

        // ── Resolve teachers + outsiders ─────────────────────────────────
        $t1 = DB::table('teacher')->where('email', 'teacher1@edutrain.test')->first();
        $t4 = DB::table('teacher')->where('email', 'teacher4@edutrain.test')->first();
        $t5 = DB::table('teacher')->where('email', 'teacher5@edutrain.test')->first();

        $resolveOutsider = fn(string $email) => DB::table('outsider')
            ->join('users', 'outsider.user_id', '=', 'users.id')
            ->where('users.email', $email)
            ->value('outsider.outsider_id');

        $o1 = $resolveOutsider('outsider@edutrain.test');
        $o2 = $resolveOutsider('outsider2@edutrain.test');
        $o3 = $resolveOutsider('outsider3@edutrain.test');
        $o4 = $resolveOutsider('outsider4@edutrain.test');

        // ── Trainers ─────────────────────────────────────────────────────
        // trainer5: teacher5 — makes teacher5 earn 1.5× on EDU Al-amin courses
        $trainer5Id = $this->upsertTrainer([
            'trainer_name'    => $t5->teacherName,
            'trainer_ic'      => $t5->ICNumber,
            'trainer_email'   => $t5->email,
            'trainer_phoneNo' => $t5->phoneNumber,
            'expertise'       => 'Instructional Leadership, Curriculum Design, Classroom Observation',
            'trainer_pic'     => null,
            'invitation_letter' => null,
            'average_rating'  => '4.60',
            'status'          => 'active',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // trainer6: external domain expert
        $trainer6Id = $this->upsertTrainer([
            'trainer_name'    => 'Dr. Siti Rashidah binti Mohd Yusoff',
            'trainer_ic'      => '810704-10-6789',
            'trainer_email'   => 'siti.rashidah@expert.test',
            'trainer_phoneNo' => '017-9876543',
            'expertise'       => 'Educational Psychology, Student Motivation, Formative Assessment',
            'trainer_pic'     => null,
            'invitation_letter' => null,
            'average_rating'  => '4.75',
            'status'          => 'active',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        // ── Courses ───────────────────────────────────────────────────────

        // ── 0.5× tier: external organiser ────────────────────────────────

        // Course 22 — single_day, external
        $c22 = $this->upsertCourse([
            'courseName'     => 'Literasi Digital dan Keselamatan Siber dalam Pendidikan',
            'course_category' => 'single',
            'description'     => 'Kursus satu hari dianjurkan oleh Kementerian Pendidikan Malaysia untuk meningkatkan literasi digital guru. Peserta akan didedahkan kepada amalan keselamatan siber, perlindungan data pelajar, penggunaan alat digital selamat, dan etika digital dalam pengajaran harian. Kursus ini memberi nilai LADAP 0.5× kerana dianjurkan oleh pihak luar.',
            'capacity'        => 50,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'Kementerian Pendidikan Malaysia',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => 'https://chat.whatsapp.com/demo22',
            'poster'          => null,
        ]);

        // Course 23 — multi_day, external
        $c23 = $this->upsertCourse([
            'courseName'     => 'Pengurusan Emosi dan Kesihatan Mental Guru',
            'course_category' => 'multi_day',
            'description'     => 'Program dua hari anjuran Jabatan Pendidikan Negeri Selangor memfokuskan kepada kesejahteraan mental guru. Kursus ini merangkumi teknik pengurusan tekanan, regulasi emosi, strategi pemulihan burnout, dan membina persekitaran kerja yang positif. LADAP dikira pada kadar 0.5× kerana penganjur luar.',
            'capacity'        => 40,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'Jabatan Pendidikan Negeri Selangor',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => 'https://chat.whatsapp.com/demo23',
            'poster'          => null,
        ]);

        // ── 1.0× / 1.5× tier: EDU Al-amin ────────────────────────────────

        // Course 24 — single_day, EDU Al-amin
        $c24 = $this->upsertCourse([
            'courseName'     => 'Amalan Pengajaran Bertaraf Dunia Al Amin',
            'course_category' => 'single',
            'description'     => 'Program dalaman EDU Al-amin sehari untuk memperkasakan amalan pengajaran guru agar menepati piawaian antarabangsa. Kursus ini merangkumi reka bentuk pembelajaran, penggunaan data penilaian, dan pedagogi yang berpusatkan murid. Guru berdaftar sebagai jurulatih mendapat nilai LADAP 1.5× manakala guru lain mendapat 1.0×.',
            'capacity'        => 30,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'EDU Al-amin',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => 'https://chat.whatsapp.com/demo24',
            'poster'          => null,
        ]);

        // Course 25 — multi_day, EDU Al-amin
        $c25 = $this->upsertCourse([
            'courseName'     => 'Inovasi Kurikulum dan Pentaksiran Al Amin',
            'course_category' => 'multi_day',
            'description'     => 'Program berbilang hari EDU Al-amin memfokuskan kepada pembangunan kurikulum inovatif dan strategi pentaksiran holistik. Peserta akan meneroka reka bentuk backward design, pentaksiran autentik, dan penggunaan rubrik yang berkesan. Jurulatih berdaftar mendapat 1.5× dan guru biasa mendapat 1.0× nilai LADAP.',
            'capacity'        => 25,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'EDU Al-amin',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => 'https://chat.whatsapp.com/demo25',
            'poster'          => null,
        ]);

        // Course 26 — single_day, EDU Al-amin
        $c26 = $this->upsertCourse([
            'courseName'     => 'Kepimpinan Digital Al Amin',
            'course_category' => 'single',
            'description'     => 'Kursus sehari EDU Al-amin untuk membangunkan kompetensi kepimpinan digital dalam kalangan guru. Peserta akan belajar mengintegrasikan teknologi dalam pengurusan bilik darjah, komunikasi ibu bapa digital, dan analitik pembelajaran. LADAP dikira pada kadar 1.0× untuk semua peserta (tiada jurulatih dalaman ditugaskan bagi kursus ini).',
            'capacity'        => 30,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'EDU Al-amin',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => 'https://chat.whatsapp.com/demo26',
            'poster'          => null,
        ]);

        // Course 27 — multi_day, EDU Al-amin
        $c27 = $this->upsertCourse([
            'courseName'     => 'Pengajaran Berkesan Abad ke-21 Al Amin',
            'course_category' => 'multi_day',
            'description'     => 'Program pembangunan profesional berbilang hari EDU Al-amin yang merangkumi kemahiran pembelajaran abad ke-21 (4C), penggunaan alat STEM, dan reka bentuk projek pembelajaran berasaskan masalah (PBL). Nilai LADAP: 1.5× untuk jurulatih berdaftar EDU Al-amin, 1.0× untuk peserta lain.',
            'capacity'        => 28,
            'price'           => 0.00,
            'status'          => 'ongoing',
            'organiserName'  => 'EDU Al-amin',
            'mode'      => null,
            'course_pic'      => null,
            'whatsappGroup'  => 'https://chat.whatsapp.com/demo27',
            'poster'          => null,
        ]);

        // ── Sessions ──────────────────────────────────────────────────────

        // Course 22 — single_day, session today only
        $ses22 = $this->upsertSession($c22, $today, '16:00:00', '19:00:00',
            'Dewan Besar, Kementerian Pendidikan Malaysia, Putrajaya', $now);

        // Course 23 — multi_day: past + today
        $ses23past = $this->upsertSession($c23, '2026-06-17', '09:00:00', '11:00:00',
            'Bilik Latihan A, Jabatan Pendidikan Selangor, Shah Alam', $now);
        $ses23 = $this->upsertSession($c23, $today, '16:00:00', '19:00:00',
            'Bilik Latihan A, Jabatan Pendidikan Selangor, Shah Alam', $now);

        // Course 24 — single_day, session today only
        $ses24 = $this->upsertSession($c24, $today, '16:00:00', '19:00:00',
            'Dewan Latihan Utama, EDU Al-amin HQ, Kuala Lumpur', $now);

        // Course 25 — multi_day: past + today
        $ses25past = $this->upsertSession($c25, '2026-06-18', '09:00:00', '11:00:00',
            'Bilik Seminar 2, EDU Al-amin HQ, Kuala Lumpur', $now);
        $ses25 = $this->upsertSession($c25, $today, '16:00:00', '19:00:00',
            'Bilik Seminar 2, EDU Al-amin HQ, Kuala Lumpur', $now);

        // Course 26 — single_day, session today only
        $ses26 = $this->upsertSession($c26, $today, '16:00:00', '19:00:00',
            'Pusat Digital Al-amin, EDU Al-amin HQ, Kuala Lumpur', $now);

        // Course 27 — multi_day: past + today
        $ses27past = $this->upsertSession($c27, '2026-06-19', '09:00:00', '11:00:00',
            'Studio Pembelajaran, EDU Al-amin HQ, Kuala Lumpur', $now);
        $ses27 = $this->upsertSession($c27, $today, '16:00:00', '19:00:00',
            'Studio Pembelajaran, EDU Al-amin HQ, Kuala Lumpur', $now);

        // ── Session–Trainer assignments ───────────────────────────────────
        // External courses: use trainer1 (Ahmad Razif) and trainer3 (Nurul Ain)
        $trainer1Id = DB::table('trainer')->where('trainer_id', 1)->value('trainer_id');
        $trainer3Id = DB::table('trainer')->where('trainer_id', 3)->value('trainer_id');

        $this->assignTrainer($ses22, $trainer1Id);
        $this->assignTrainer($ses22, $trainer6Id);

        $this->assignTrainer($ses23past, $trainer3Id);
        $this->assignTrainer($ses23, $trainer3Id);
        $this->assignTrainer($ses23, $trainer6Id);

        // EDU Al-amin courses: use trainer5 (teacher5) + trainer6
        $this->assignTrainer($ses24, $trainer5Id);
        $this->assignTrainer($ses24, $trainer6Id);

        $this->assignTrainer($ses25past, $trainer5Id);
        $this->assignTrainer($ses25past, $trainer6Id);
        $this->assignTrainer($ses25, $trainer5Id);
        $this->assignTrainer($ses25, $trainer6Id);

        // Course 26: only trainer6 — teacher5 is NOT a trainer here, so earns 1.0×
        $this->assignTrainer($ses26, $trainer6Id);

        $this->assignTrainer($ses27past, $trainer5Id);
        $this->assignTrainer($ses27past, $trainer6Id);
        $this->assignTrainer($ses27, $trainer5Id);
        $this->assignTrainer($ses27, $trainer6Id);

        // ── Enroll teachers ───────────────────────────────────────────────
        // teacher5 → all 6 courses
        foreach ([$c22, $c23, $c24, $c25, $c26, $c27] as $courseId) {
            $this->upsertEnroll($t5->teacherID, $courseId, 'approved', '2026-06-01 09:00:00', $now);
        }

        // teacher4 (non-trainer) → EDU Al-amin courses only (demonstrates 1.0×)
        foreach ([$c24, $c25, $c26, $c27] as $courseId) {
            $this->upsertEnroll($t4->teacherID, $courseId, 'approved', '2026-06-01 09:30:00', $now);
        }

        // teacher1 (already trainer4) → EDU Al-amin courses (demonstrates 1.5×)
        foreach ([$c24, $c25, $c26, $c27] as $courseId) {
            $this->upsertEnroll($t1->teacherID, $courseId, 'approved', '2026-06-01 10:00:00', $now);
        }

        // ── Outsider enrollments + attendance ────────────────────────────
        // course 22 = Literasi Digital (single, today 16:00–19:00 = 3h)
        // course 23 = Pengurusan Emosi (multi_day: past 2h + today 3h = 5h)
        $outsiderEnrollments = [
            // [outsider_id, courseID, applied_at]
            [$o1, $c22, '2026-06-20 09:00:00'],
            [$o1, $c23, '2026-06-14 10:00:00'],
            [$o2, $c22, '2026-06-20 09:30:00'],
            [$o3, $c22, '2026-06-20 11:00:00'],
            [$o3, $c23, '2026-06-14 11:00:00'],
            [$o4, $c22, '2026-06-20 14:00:00'],
        ];
        foreach ($outsiderEnrollments as [$oid, $cid, $appliedAt]) {
            if ($oid && !DB::table('enroll_outsider')->where('outsider_id', $oid)->where('courseID', $cid)->exists()) {
                DB::table('enroll_outsider')->insert(['outsider_id' => $oid, 'courseID' => $cid, 'status' => 'approved', 'applied_at' => $appliedAt]);
            }
        }

        $outsiderAttendance = [
            // [outsider_id, session_id, hours]
            [$o1, $ses22, 3.00],       // course 22 today
            [$o1, $ses23past, 2.00],   // course 23 past
            [$o1, $ses23, 3.00],       // course 23 today
            [$o2, $ses22, 3.00],       // course 22 today
            [$o3, $ses22, 3.00],       // course 22 today
            [$o3, $ses23past, 2.00],   // course 23 past
            [$o3, $ses23, 3.00],       // course 23 today
            [$o4, $ses22, 3.00],       // course 22 today
        ];
        foreach ($outsiderAttendance as [$oid, $sid, $hrs]) {
            if ($oid && !DB::table('attendance_outsider')->where('outsider_id', $oid)->where('session_id', $sid)->exists()) {
                DB::table('attendance_outsider')->insert(['outsider_id' => $oid, 'session_id' => $sid, 'timestamp_status' => $hrs, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // ── Guru Baru (applicant1) enrollments + attendance ──────────────
        $ap1 = DB::table('guru_new')->where('email', 'applicant1@edutrain.test')->value('gn_id');

        $guruBaruEnrollments = [
            // [gn_id, courseID, applied_at]
            [$ap1, $c22, '2026-06-20 09:00:00'],  // Literasi Digital (single, external)
            [$ap1, $c23, '2026-06-14 10:00:00'],  // Pengurusan Emosi (multi_day, external)
            [$ap1, $c25, '2026-06-10 09:00:00'],  // Inovasi Kurikulum (multi_day, EDU Al-amin)
        ];
        foreach ($guruBaruEnrollments as [$gnId, $cid, $appliedAt]) {
            if ($gnId && !DB::table('enroll_guru_baru')->where('gn_id', $gnId)->where('courseID', $cid)->exists()) {
                DB::table('enroll_guru_baru')->insert(['gn_id' => $gnId, 'courseID' => $cid, 'status' => 'approved', 'applied_at' => $appliedAt]);
            }
        }

        $guruBaruAttendance = [
            // [gn_id, session_id, hours]  — guru_baru LADAP = hours (1:1)
            [$ap1, $ses22,     3.00],  // course 22 today
            [$ap1, $ses23past, 2.00],  // course 23 past
            [$ap1, $ses23,     3.00],  // course 23 today
            [$ap1, $ses25past, 2.00],  // course 25 past
            [$ap1, $ses25,     3.00],  // course 25 today
        ];
        foreach ($guruBaruAttendance as [$gnId, $sid, $hrs]) {
            if ($gnId && !DB::table('attendance_guru_baru')->where('gn_id', $gnId)->where('session_id', $sid)->exists()) {
                DB::table('attendance_guru_baru')->insert(['gn_id' => $gnId, 'session_id' => $sid, 'timestamp_status' => $hrs, 'hours_ladap' => $hrs, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        // ── Past attendance records ────────────────────────────────────────
        // Course 23 past (0.5×, external): teacher5 attended
        $this->upsertAttendance($t5->teacherID, $ses23past, 2.0, 1.0, $now);

        // Course 25 past (EDU Al-amin):
        //   teacher5 (trainer) → 2 hrs × 1.5 = 3.0 LADAP
        //   teacher4 (non-trainer) → 2 hrs × 1.0 = 2.0 LADAP
        //   teacher1 (trainer) → 2 hrs × 1.5 = 3.0 LADAP
        $this->upsertAttendance($t5->teacherID, $ses25past, 2.0, 3.0, $now);
        $this->upsertAttendance($t4->teacherID, $ses25past, 2.0, 2.0, $now);
        $this->upsertAttendance($t1->teacherID, $ses25past, 2.0, 3.0, $now);

        // Course 27 past (EDU Al-amin):
        //   teacher5 → 2 hrs × 1.5 = 3.0 LADAP
        //   teacher4 → 2 hrs × 1.0 = 2.0 LADAP
        //   teacher1 → 2 hrs × 1.5 = 3.0 LADAP
        $this->upsertAttendance($t5->teacherID, $ses27past, 2.0, 3.0, $now);
        $this->upsertAttendance($t4->teacherID, $ses27past, 2.0, 2.0, $now);
        $this->upsertAttendance($t1->teacherID, $ses27past, 2.0, 3.0, $now);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function upsertCourse(array $data): int
    {
        $existing = DB::table('course')->where('courseName', $data['courseName'])->first();
        if ($existing) return $existing->courseID;
        return DB::table('course')->insertGetId(array_merge($data, [
            'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function upsertTrainer(array $data): int
    {
        $existing = DB::table('trainer')->where('trainer_ic', $data['trainer_ic'])->first();
        if ($existing) return $existing->trainer_id;
        return DB::table('trainer')->insertGetId($data);
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
            'courseID'      => $courseId,
            'session_date'   => $date,
            'start_time'     => $start,
            'end_time'       => $end,
            'location'       => $location,
            'created_at'     => $now,
            'updated_at'     => $now,
        ]);
    }

    private function assignTrainer(int $sessionId, int $trainerId): void
    {
        $exists = DB::table('session_trainer')
            ->where('session_id', $sessionId)
            ->where('trainer_id', $trainerId)
            ->exists();
        if (! $exists) {
            DB::table('session_trainer')->insert([
                'session_id' => $sessionId,
                'trainer_id' => $trainerId,
            ]);
        }
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
