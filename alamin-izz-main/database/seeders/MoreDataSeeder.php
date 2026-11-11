<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Application;
use App\Models\Assign;
use App\Models\Attendance;
use App\Models\AttendanceGuruBaru;
use App\Models\AttendanceOutsider;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\GuruNew;
use App\Models\Notification;
use App\Models\Outsider;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MoreDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedTeachers();
            $this->seedOutsiders();
            $this->seedGuruBaru();
            $this->seedCoursesAndData();
        });
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NEW TEACHERS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedTeachers(): void
    {
        $school1 = School::where('schoolName', 'Sekolah Kebangsaan Al Amin')->first();
        $school2 = School::where('schoolName', 'Sekolah Kebangsaan Seri Setia')->first();
        $school3 = School::where('schoolName', 'Sekolah Kebangsaan Bukit Damansara')->first();

        $teachers = [
            [
                'teacherName'   => 'Nabilah binti Hassan',
                'ICNumber'      => '910215-14-5678',
                'phoneNumber'   => '012-8887766',
                'email'         => 'teacher4@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 22, Jalan Cempaka 1, Bandar Baru Bangi, 43000 Kajang, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2015-08-01'),
                'serviceDate'   => Carbon::parse('2015-08-01'),
                'pensionDate'   => null,
                'latestAge'     => 34,
                'schoolID'      => $school1->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Mohd Zulkifli bin Ariffin',
                'ICNumber'      => '870930-01-3344',
                'phoneNumber'   => '013-5556677',
                'email'         => 'teacher5@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 7, Jalan Mawar 3, Taman Maju, 43000 Kajang, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2011-01-03'),
                'serviceDate'   => Carbon::parse('2011-01-03'),
                'pensionDate'   => null,
                'latestAge'     => 38,
                'schoolID'      => $school1->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Priya a/p Krishnamurthy',
                'ICNumber'      => '920410-10-7890',
                'phoneNumber'   => '016-3334455',
                'email'         => 'teacher6@edutrain.test',
                'martialStatus' => 'Single',
                'gender'        => 'Female',
                'address'       => 'No 15, Jalan SS 7/15, Kelana Jaya, 47301 Petaling Jaya, Selangor',
                'race'          => 'Indian',
                'appointedDate' => Carbon::parse('2017-03-01'),
                'serviceDate'   => Carbon::parse('2017-03-01'),
                'pensionDate'   => null,
                'latestAge'     => 33,
                'schoolID'      => $school2->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Farahiyah binti Yusoff',
                'ICNumber'      => '890714-07-2233',
                'phoneNumber'   => '011-7778899',
                'email'         => 'teacher7@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Female',
                'address'       => 'No 3, Jalan PJ 5/2, Taman Petaling, 47810 Petaling Jaya, Selangor',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2013-07-01'),
                'serviceDate'   => Carbon::parse('2013-07-01'),
                'pensionDate'   => null,
                'latestAge'     => 36,
                'schoolID'      => $school2->schoolID,
                'password'      => Hash::make('password'),
            ],
            [
                'teacherName'   => 'Azlan bin Mohd Noor',
                'ICNumber'      => '820303-14-4455',
                'phoneNumber'   => '019-2223344',
                'email'         => 'teacher8@edutrain.test',
                'martialStatus' => 'Married',
                'gender'        => 'Male',
                'address'       => 'No 55, Jalan Damansara 8, 50490 Kuala Lumpur',
                'race'          => 'Malay',
                'appointedDate' => Carbon::parse('2007-04-15'),
                'serviceDate'   => Carbon::parse('2007-04-15'),
                'pensionDate'   => Carbon::parse('2042-03-03'),
                'latestAge'     => 43,
                'schoolID'      => $school3->schoolID,
                'password'      => Hash::make('password'),
            ],
        ];

        foreach ($teachers as $data) {
            $teacher = Teacher::firstOrCreate(['email' => $data['email']], $data);

            Assign::firstOrCreate(
                ['schoolID' => $teacher->schoolID, 'teacherID' => $teacher->teacherID],
                ['assignDate' => $teacher->serviceDate, 'status' => 'active']
            );

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['teacherName'], 'password' => Hash::make('password')]
            );
            if (!$user->hasRole(Role::Teacher->value)) {
                $user->assignRole(Role::Teacher->value);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NEW OUTSIDERS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedOutsiders(): void
    {
        $outsiders = [
            [
                'email'        => 'outsider2@edutrain.test',
                'name'         => 'Mohd Hafiz bin Kamaruddin',
                'ICNumber'     => '870525-11-6677',
                'phoneNumber'  => '012-6667788',
                'gender'       => 'Male',
                'organization' => 'Perbadanan Putrajaya',
            ],
            [
                'email'        => 'outsider3@edutrain.test',
                'name'         => 'Jessica Tan Wei Ling',
                'ICNumber'     => '930812-10-8899',
                'phoneNumber'  => '016-9990011',
                'gender'       => 'Female',
                'organization' => 'SMK Damansara Jaya',
            ],
            [
                'email'        => 'outsider4@edutrain.test',
                'name'         => 'Amirul Haziq bin Ramli',
                'ICNumber'     => '950120-14-1122',
                'phoneNumber'  => '011-5551122',
                'gender'       => 'Male',
                'organization' => 'Universiti Malaya',
            ],
        ];

        foreach ($outsiders as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('password')]
            );
            if (!$user->hasRole(Role::Outsider->value)) {
                $user->assignRole(Role::Outsider->value);
            }

            Outsider::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'name'        => $data['name'],
                    'ICNumber'    => $data['ICNumber'],
                    'phoneNumber' => $data['phoneNumber'],
                    'gender'      => $data['gender'],
                    'organization'=> $data['organization'],
                ]
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NEW GURU BARU
    // ─────────────────────────────────────────────────────────────────────────
    private function seedGuruBaru(): void
    {
        $guruBaruList = [
            [
                'email'        => 'applicant3@edutrain.test',
                'full_name'    => 'Nur Hidayah binti Salleh',
                'phone_number' => '014-7778899',
            ],
            [
                'email'        => 'applicant4@edutrain.test',
                'full_name'    => 'Hazwan bin Othman',
                'phone_number' => '014-1112233',
            ],
        ];

        foreach ($guruBaruList as $data) {
            GuruNew::firstOrCreate(
                ['email' => $data['email']],
                ['gn_name' => $data['full_name'], 'phone_number' => $data['phone_number'], 'password' => Hash::make('password')]
            );

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['full_name'], 'password' => Hash::make('password')]
            );
            if (!$user->hasRole(Role::GuruBaru->value)) {
                $user->assignRole(Role::GuruBaru->value);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NEW COURSES + SESSIONS + APPLICATIONS + ATTENDANCE + NOTIFICATIONS
    // ─────────────────────────────────────────────────────────────────────────
    private function seedCoursesAndData(): void
    {
        $trainer1 = Trainer::where('trainer_email', 'razif.hassan@trainer.test')->first();
        $trainer2 = Trainer::where('trainer_email', 'limweichen@trainer.test')->first();
        $trainer3 = Trainer::where('trainer_email', 'nuraini.malik@trainer.test')->first();

        $presentationDay = Carbon::tomorrow()->toDateString();

        // Resolve existing users
        $gb       = User::where('email', 'principal@edutrain.test')->first();
        $teacher1 = User::where('email', 'teacher1@edutrain.test')->first();
        $teacher2 = User::where('email', 'teacher2@edutrain.test')->first();
        $teacher3 = User::where('email', 'teacher3@edutrain.test')->first();
        $teacher4 = User::where('email', 'teacher4@edutrain.test')->first();
        $teacher5 = User::where('email', 'teacher5@edutrain.test')->first();
        $teacher6 = User::where('email', 'teacher6@edutrain.test')->first();
        $teacher7 = User::where('email', 'teacher7@edutrain.test')->first();
        $teacher8 = User::where('email', 'teacher8@edutrain.test')->first();
        $outsider2User = User::where('email', 'outsider2@edutrain.test')->first();
        $outsider3User = User::where('email', 'outsider3@edutrain.test')->first();
        $outsider4User = User::where('email', 'outsider4@edutrain.test')->first();
        $gb3User  = User::where('email', 'applicant3@edutrain.test')->first();
        $gb4User  = User::where('email', 'applicant4@edutrain.test')->first();

        // Resolve teacher records
        $t1 = Teacher::where('email', 'teacher1@edutrain.test')->first();
        $t2 = Teacher::where('email', 'teacher2@edutrain.test')->first();
        $t3 = Teacher::where('email', 'teacher3@edutrain.test')->first();
        $t4 = Teacher::where('email', 'teacher4@edutrain.test')->first();
        $t5 = Teacher::where('email', 'teacher5@edutrain.test')->first();
        $t6 = Teacher::where('email', 'teacher6@edutrain.test')->first();
        $t7 = Teacher::where('email', 'teacher7@edutrain.test')->first();
        $t8 = Teacher::where('email', 'teacher8@edutrain.test')->first();

        $o2 = Outsider::whereHas('user', fn($q) => $q->where('email', 'outsider2@edutrain.test'))->first();
        $o3 = Outsider::whereHas('user', fn($q) => $q->where('email', 'outsider3@edutrain.test'))->first();
        $o4 = Outsider::whereHas('user', fn($q) => $q->where('email', 'outsider4@edutrain.test'))->first();

        $ap1 = GuruNew::where('email', 'applicant1@edutrain.test')->first();
        $ap2 = GuruNew::where('email', 'applicant2@edutrain.test')->first();
        $ap3 = GuruNew::where('email', 'applicant3@edutrain.test')->first();
        $ap4 = GuruNew::where('email', 'applicant4@edutrain.test')->first();

        // ── Auto-enroll new guru baru in existing series courses ────────────
        $seriesCourses = Training::where('course_category', 'series')->get();
        foreach ($seriesCourses as $series) {
            foreach ([$ap3, $ap4] as $ap) {
                if ($ap && !EnrollGuruBaru::where('gn_id', $ap->gn_id)->where('courseID', $series->courseID)->exists()) {
                    EnrollGuruBaru::create([
                        'gn_id' => $ap->gn_id,
                        'courseID'    => $series->courseID,
                        'status'       => 'auto_enrolled',
                        'applied_at'   => Carbon::now()->subDays(30),
                    ]);
                }
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 11 — Mental Health & Wellbeing in Schools (single, complete)
        // ══════════════════════════════════════════════════════════════════════
        $mentalHealth = Course::firstOrCreate(['courseName' => 'Mental Health & Wellbeing in Schools'], [
            'course_category' => 'single',
            'description'     => 'Equips teachers with knowledge and strategies to support student mental health, identify early signs of distress, and create a psychologically safe classroom environment.',
            'capacity'        => 35,
            'price'           => 0,
            'status'          => 'completed',
            'poster'          => null,
            'mode'      => 'physical',
            'course_pic'      => 'Pn. Syarifah Aisyah',
            'whatsappGroup'  => null,
            'organiserName'  => 'Kementerian Pendidikan Malaysia',
        ]);

        $mh1 = CourseSession::create([
            'courseID' => $mentalHealth->courseID, 'session_date' => '2026-02-20',
            'start_time' => '08:00:00', 'end_time' => '12:30:00',
            'location' => 'Bilik Persidangan A, KPM, Putrajaya',
        ]);
        $mh2 = CourseSession::create([
            'courseID' => $mentalHealth->courseID, 'session_date' => '2026-02-20',
            'start_time' => '14:00:00', 'end_time' => '17:00:00',
            'location' => 'Bilik Persidangan A, KPM, Putrajaya',
        ]);
        if ($trainer1) { $mh1->trainers()->syncWithoutDetaching([$trainer1->trainer_id]); $mh2->trainers()->syncWithoutDetaching([$trainer1->trainer_id]); }

        // Applications
        foreach ([$t4, $t5, $t6] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $mentalHealth->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(55)]);
        }
        if ($t7) Application::create(['teacherID' => $t7->teacherID, 'courseID' => $mentalHealth->courseID, 'status' => 'rejected', 'rejection_reason' => 'Permohonan tidak lengkap. Sila kemukakan semula.', 'applied_at' => Carbon::now()->subDays(56)]);
        if ($t2) Application::create(['teacherID' => $t2->teacherID, 'courseID' => $mentalHealth->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(54)]);

        // Attendance
        if ($t4) Attendance::create(['teacher_id' => $t4->teacherID, 'session_id' => $mh1->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]);
        if ($t5) { Attendance::create(['teacher_id' => $t5->teacherID, 'session_id' => $mh1->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]); Attendance::create(['teacher_id' => $t5->teacherID, 'session_id' => $mh2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]); }
        if ($t6) Attendance::create(['teacher_id' => $t6->teacherID, 'session_id' => $mh2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]);
        if ($t2) { Attendance::create(['teacher_id' => $t2->teacherID, 'session_id' => $mh1->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]); Attendance::create(['teacher_id' => $t2->teacherID, 'session_id' => $mh2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]); }

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 12 — English Language Teaching Methodology (single, complete)
        // ══════════════════════════════════════════════════════════════════════
        $engTeaching = Course::firstOrCreate(['courseName' => 'English Language Teaching Methodology'], [
            'course_category' => 'single',
            'description'     => 'Practical workshop on modern ELT approaches including communicative language teaching, task-based learning, and integrating technology into English lessons.',
            'capacity'        => 40,
            'price'           => 0,
            'status'          => 'completed',
            'poster'          => null,
            'mode'      => 'hybrid',
            'course_pic'      => 'Dr. Sarah Lim',
            'whatsappGroup'  => null,
            'organiserName'  => 'Jabatan Pendidikan Selangor',
        ]);

        $el1 = CourseSession::create([
            'courseID' => $engTeaching->courseID, 'session_date' => '2026-03-15',
            'start_time' => '08:30:00', 'end_time' => '12:30:00',
            'location' => 'Dewan Bahasa dan Pustaka, Kuala Lumpur',
        ]);
        $el2 = CourseSession::create([
            'courseID' => $engTeaching->courseID, 'session_date' => '2026-03-15',
            'start_time' => '14:00:00', 'end_time' => '17:00:00',
            'location' => 'Dewan Bahasa dan Pustaka, Kuala Lumpur',
        ]);
        if ($trainer2) { $el1->trainers()->syncWithoutDetaching([$trainer2->trainer_id]); $el2->trainers()->syncWithoutDetaching([$trainer2->trainer_id]); }

        foreach ([$t4, $t6, $t7, $t8] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $engTeaching->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(42)]);
        }
        if ($t3) Application::create(['teacherID' => $t3->teacherID, 'courseID' => $engTeaching->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(43)]);

        if ($t4) Attendance::create(['teacher_id' => $t4->teacherID, 'session_id' => $el1->session_id, 'timestamp_status' => 4.0, 'hours_ladap' => 2.0]);
        if ($t6) { Attendance::create(['teacher_id' => $t6->teacherID, 'session_id' => $el1->session_id, 'timestamp_status' => 4.0, 'hours_ladap' => 2.0]); Attendance::create(['teacher_id' => $t6->teacherID, 'session_id' => $el2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]); }
        if ($t7) Attendance::create(['teacher_id' => $t7->teacherID, 'session_id' => $el2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]);
        if ($t8) { Attendance::create(['teacher_id' => $t8->teacherID, 'session_id' => $el1->session_id, 'timestamp_status' => 4.0, 'hours_ladap' => 2.0]); Attendance::create(['teacher_id' => $t8->teacherID, 'session_id' => $el2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]); }
        if ($t3) { Attendance::create(['teacher_id' => $t3->teacherID, 'session_id' => $el1->session_id, 'timestamp_status' => 4.0, 'hours_ladap' => 2.0]); Attendance::create(['teacher_id' => $t3->teacherID, 'session_id' => $el2->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]); }

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 13 — Leadership & School Management (multi_day, complete)
        // ══════════════════════════════════════════════════════════════════════
        $leadership = Course::firstOrCreate(['courseName' => 'Leadership & School Management'], [
            'course_category' => 'multi_day',
            'description'     => 'A two-day intensive programme on transformational school leadership, staff management, performance appraisal, and strategic planning for school improvement.',
            'capacity'        => 30,
            'price'           => 80.00,
            'status'          => 'completed',
            'poster'          => null,
            'mode'      => 'physical',
            'course_pic'      => 'Dr. Norhaslinda Ahmad',
            'whatsappGroup'  => null,
            'organiserName'  => 'Institut Aminuddin Baki',
        ]);

        $ls = [];
        foreach ([
            ['2026-04-10', '08:00:00', '12:30:00', 'Dewan Latihan A, Institut Aminuddin Baki'],
            ['2026-04-10', '14:00:00', '17:00:00', 'Dewan Latihan A, Institut Aminuddin Baki'],
            ['2026-04-11', '08:00:00', '12:30:00', 'Dewan Latihan A, Institut Aminuddin Baki'],
            ['2026-04-11', '14:00:00', '17:00:00', 'Dewan Latihan A, Institut Aminuddin Baki'],
        ] as [$date, $start, $end, $loc]) {
            $s = CourseSession::create(['courseID' => $leadership->courseID, 'session_date' => $date, 'start_time' => $start, 'end_time' => $end, 'location' => $loc]);
            if ($trainer3) $s->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
            $ls[] = $s;
        }

        foreach ([$t1, $t4, $t5, $t7] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $leadership->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(68)]);
        }
        if ($o2) EnrollOutsider::create(['outsider_id' => $o2->outsider_id, 'courseID' => $leadership->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(67)]);

        // outsider2 attends all 4 sessions of the leadership workshop
        if ($o2) {
            AttendanceOutsider::create(['outsider_id' => $o2->outsider_id, 'session_id' => $ls[0]->session_id, 'timestamp_status' => 4.5]);
            AttendanceOutsider::create(['outsider_id' => $o2->outsider_id, 'session_id' => $ls[1]->session_id, 'timestamp_status' => 3.0]);
            AttendanceOutsider::create(['outsider_id' => $o2->outsider_id, 'session_id' => $ls[2]->session_id, 'timestamp_status' => 4.5]);
            AttendanceOutsider::create(['outsider_id' => $o2->outsider_id, 'session_id' => $ls[3]->session_id, 'timestamp_status' => 3.0]);
        }

        // One session per day — teachers attend morning sessions only (08:00-12:30 = 4.5h)
        // Afternoon sessions are skipped; live validation prevents same-day double scan
        foreach ([$t1, $t4, $t5, $t7] as $t) {
            if ($t) {
                Attendance::create(['teacher_id' => $t->teacherID, 'session_id' => $ls[0]->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]); // day 1 morning
                Attendance::create(['teacher_id' => $t->teacherID, 'session_id' => $ls[2]->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]); // day 2 morning
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 14 — Creative Arts Integration (single, ongoing, tomorrow)
        // ══════════════════════════════════════════════════════════════════════
        $creativeArts = Course::firstOrCreate(['courseName' => 'Creative Arts Integration in Learning'], [
            'course_category' => 'single',
            'description'     => 'Explores how visual arts, drama, and music can be integrated across subjects to enhance student engagement, creativity, and learning retention.',
            'capacity'        => 30,
            'price'           => 0,
            'status'          => 'ongoing',
            'poster'          => null,
            'mode'      => 'physical',
            'course_pic'      => 'En. Faizal Nordin',
            'whatsappGroup'  => null,
            'organiserName'  => 'Jabatan Pendidikan Selangor',
        ]);

        $ca = CourseSession::create([
            'courseID' => $creativeArts->courseID, 'session_date' => $presentationDay,
            'start_time' => '09:00:00', 'end_time' => '23:59:00',
            'location' => 'Studio Seni, Jabatan Pendidikan Selangor',
        ]);
        if ($trainer1) $ca->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);

        foreach ([$t5, $t6, $t8] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $creativeArts->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(10)]);
        }
        if ($t7) Application::create(['teacherID' => $t7->teacherID, 'courseID' => $creativeArts->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(2)]);
        if ($o3) EnrollOutsider::create(['outsider_id' => $o3->outsider_id, 'courseID' => $creativeArts->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(8)]);

        // Teacher5 already marked attendance (others haven't — for demo)
        if ($t5) Attendance::create(['teacher_id' => $t5->teacherID, 'session_id' => $ca->session_id, 'timestamp_status' => 4.0, 'hours_ladap' => 4.0]);

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 15 — Data-Driven Teaching Practices (multi_day, upcoming)
        // ══════════════════════════════════════════════════════════════════════
        $dataDriven = Course::firstOrCreate(['courseName' => 'Data-Driven Teaching Practices'], [
            'course_category' => 'multi_day',
            'description'     => 'Trains teachers to collect, analyse, and act on classroom data to improve instruction. Covers formative assessment tools, progress monitoring, and differentiating based on data insights.',
            'capacity'        => 40,
            'price'           => 60.00,
            'status'          => 'upcoming',
            'poster'          => null,
            'mode'      => 'hybrid',
            'course_pic'      => 'Dr. Lim Wei Chen',
            'whatsappGroup'  => 'https://chat.whatsapp.com/datadrive2026',
            'organiserName'  => 'Bahagian Pembangunan Kurikulum, KPM',
        ]);

        foreach ([
            ['2026-08-20', '08:00:00', '12:30:00', 'Makmal Komputer, KPM, Putrajaya'],
            ['2026-08-20', '14:00:00', '17:00:00', 'Makmal Komputer, KPM, Putrajaya'],
            ['2026-08-21', '08:00:00', '12:30:00', 'Makmal Komputer, KPM, Putrajaya'],
            ['2026-08-21', '14:00:00', '17:00:00', 'Makmal Komputer, KPM, Putrajaya'],
        ] as [$date, $start, $end, $loc]) {
            $s = CourseSession::create(['courseID' => $dataDriven->courseID, 'session_date' => $date, 'start_time' => $start, 'end_time' => $end, 'location' => $loc]);
            if ($trainer2) $s->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
        }

        if ($t4) Application::create(['teacherID' => $t4->teacherID, 'courseID' => $dataDriven->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(3)]);
        if ($t5) Application::create(['teacherID' => $t5->teacherID, 'courseID' => $dataDriven->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(7)]);
        if ($t8) Application::create(['teacherID' => $t8->teacherID, 'courseID' => $dataDriven->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(2)]);
        if ($t1) Application::create(['teacherID' => $t1->teacherID, 'courseID' => $dataDriven->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(5)]);
        if ($ap3) {
            if (!EnrollGuruBaru::where('gn_id', $ap3->gn_id)->where('courseID', $dataDriven->courseID)->exists()) {
                EnrollGuruBaru::create(['gn_id' => $ap3->gn_id, 'courseID' => $dataDriven->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(4)]);
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 16 — Islamic Education Enhancement Workshop (single, upcoming)
        // ══════════════════════════════════════════════════════════════════════
        $islamicEd = Course::firstOrCreate(['courseName' => 'Islamic Education Enhancement Workshop'], [
            'course_category' => 'single',
            'description'     => 'A professional development day for Pendidikan Islam teachers, covering updated curriculum delivery, integrating Islamic values across subjects, and assessment design for Islamic studies.',
            'capacity'        => 35,
            'price'           => 0,
            'status'          => 'upcoming',
            'poster'          => null,
            'mode'      => 'physical',
            'course_pic'      => 'Ustaz Hafizuddin',
            'whatsappGroup'  => null,
            'organiserName'  => 'Bahagian Pendidikan Islam, KPM',
        ]);

        $ie1 = CourseSession::create(['courseID' => $islamicEd->courseID, 'session_date' => '2026-07-25', 'start_time' => '08:00:00', 'end_time' => '12:30:00', 'location' => 'Pusat Islam, KPM, Putrajaya']);
        $ie2 = CourseSession::create(['courseID' => $islamicEd->courseID, 'session_date' => '2026-07-25', 'start_time' => '14:00:00', 'end_time' => '17:00:00', 'location' => 'Pusat Islam, KPM, Putrajaya']);
        if ($trainer1) { $ie1->trainers()->syncWithoutDetaching([$trainer1->trainer_id]); $ie2->trainers()->syncWithoutDetaching([$trainer1->trainer_id]); }

        foreach ([$t4, $t7, $t8] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $islamicEd->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(4)]);
        }
        if ($t6) Application::create(['teacherID' => $t6->teacherID, 'courseID' => $islamicEd->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(6)]);
        if ($t2) Application::create(['teacherID' => $t2->teacherID, 'courseID' => $islamicEd->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(5)]);

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 17 — Community Outreach & Parent Engagement (single, upcoming)
        // ══════════════════════════════════════════════════════════════════════
        $communityEng = Course::firstOrCreate(['courseName' => 'Community Outreach & Parent Engagement'], [
            'course_category' => 'single',
            'description'     => 'Equips educators with strategies to build effective partnerships with parents and the community. Covers communication techniques, PIBG collaboration, and home-school engagement programmes.',
            'capacity'        => 45,
            'price'           => 0,
            'status'          => 'upcoming',
            'poster'          => null,
            'mode'      => 'physical',
            'course_pic'      => null,
            'whatsappGroup'  => null,
            'organiserName'  => 'EDU Al-amin',
        ]);

        $ce1 = CourseSession::create(['courseID' => $communityEng->courseID, 'session_date' => '2026-09-15', 'start_time' => '08:30:00', 'end_time' => '12:30:00', 'location' => 'Dewan Al Amin, EDU Al-amin']);
        $ce2 = CourseSession::create(['courseID' => $communityEng->courseID, 'session_date' => '2026-09-15', 'start_time' => '14:00:00', 'end_time' => '17:00:00', 'location' => 'Dewan Al Amin, EDU Al-amin']);
        if ($trainer3) { $ce1->trainers()->syncWithoutDetaching([$trainer3->trainer_id]); $ce2->trainers()->syncWithoutDetaching([$trainer3->trainer_id]); }

        if ($o2) {
            if (!EnrollOutsider::where('outsider_id', $o2->outsider_id)->where('courseID', $communityEng->courseID)->exists()) {
                EnrollOutsider::create(['outsider_id' => $o2->outsider_id, 'courseID' => $communityEng->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(5)]);
            }
        }
        if ($o3) {
            if (!EnrollOutsider::where('outsider_id', $o3->outsider_id)->where('courseID', $communityEng->courseID)->exists()) {
                EnrollOutsider::create(['outsider_id' => $o3->outsider_id, 'courseID' => $communityEng->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(2)]);
            }
        }
        if ($o4) {
            if (!EnrollOutsider::where('outsider_id', $o4->outsider_id)->where('courseID', $communityEng->courseID)->exists()) {
                EnrollOutsider::create(['outsider_id' => $o4->outsider_id, 'courseID' => $communityEng->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDay()]);
            }
        }
        foreach ([$t4, $t5, $t6] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $communityEng->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(6)]);
        }
        if ($ap3) {
            if (!EnrollGuruBaru::where('gn_id', $ap3->gn_id)->where('courseID', $communityEng->courseID)->exists()) {
                EnrollGuruBaru::create(['gn_id' => $ap3->gn_id, 'courseID' => $communityEng->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(3)]);
            }
        }
        if ($ap4) {
            if (!EnrollGuruBaru::where('gn_id', $ap4->gn_id)->where('courseID', $communityEng->courseID)->exists()) {
                EnrollGuruBaru::create(['gn_id' => $ap4->gn_id, 'courseID' => $communityEng->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(5)]);
            }
        }

        // ══════════════════════════════════════════════════════════════════════
        // COURSE 18 — Classroom Innovation & 21st Century Teaching (single)
        // ══════════════════════════════════════════════════════════════════════
        $classroomInno = Course::firstOrCreate(['courseName' => 'Classroom Innovation & 21st Century Teaching'], [
            'course_category' => 'single',
            'description'     => 'A one-day workshop exploring innovative classroom practices and 21st century learning strategies. Covers design thinking, flipped classroom, gamification, and student-centred inquiry-based approaches.',
            'capacity'        => 40,
            'price'           => 0,
            'status'          => 'completed',
            'poster'          => null,
            'mode'      => 'physical',
            'course_pic'      => null,
            'whatsappGroup'  => null,
            'organiserName'  => 'EDU Al-amin',
        ]);

        $ci1 = CourseSession::create(['courseID' => $classroomInno->courseID, 'session_date' => '2026-06-17', 'start_time' => '08:00:00', 'end_time' => '17:00:00', 'location' => 'Dewan Latihan EDU Al-amin, Kuala Lumpur']);
        if ($trainer1) { $ci1->trainers()->syncWithoutDetaching([$trainer1->trainer_id]); }

        foreach ([$t1, $t2, $t4, $t5] as $t) {
            if ($t) Application::create(['teacherID' => $t->teacherID, 'courseID' => $classroomInno->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(30)]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // OUTSIDER ENROLLMENTS IN EXISTING COURSES
        // ─────────────────────────────────────────────────────────────────────
        $outdoorLearn  = Training::where('courseName', 'Outdoor and Experiential Learning Facilitation')->first();
        $stemTraining  = Training::where('courseName', 'STEM Integration Programme for Secondary Teachers')->first();
        $specialEd     = Training::where('courseName', 'Special Education Awareness Workshop')->first();

        if ($o3 && $stemTraining && !EnrollOutsider::where('outsider_id', $o3->outsider_id)->where('courseID', $stemTraining->courseID)->exists()) {
            EnrollOutsider::create(['outsider_id' => $o3->outsider_id, 'courseID' => $stemTraining->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(6)]);
        }
        if ($o4 && $outdoorLearn && !EnrollOutsider::where('outsider_id', $o4->outsider_id)->where('courseID', $outdoorLearn->courseID)->exists()) {
            EnrollOutsider::create(['outsider_id' => $o4->outsider_id, 'courseID' => $outdoorLearn->courseID, 'status' => 'pending', 'applied_at' => Carbon::now()->subDays(3)]);
        }
        if ($o4 && $specialEd && !EnrollOutsider::where('outsider_id', $o4->outsider_id)->where('courseID', $specialEd->courseID)->exists()) {
            EnrollOutsider::create(['outsider_id' => $o4->outsider_id, 'courseID' => $specialEd->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(8)]);
        }

        // outsider4 attends both sessions of Special Education Awareness (course 6, complete)
        $specialEdSessions = $specialEd
            ? CourseSession::where('courseID', $specialEd->courseID)->orderBy('start_time')->get()
            : collect();
        if ($o4 && $specialEdSessions->count() >= 2) {
            AttendanceOutsider::create(['outsider_id' => $o4->outsider_id, 'session_id' => $specialEdSessions[0]->session_id, 'timestamp_status' => 4.0]);
            AttendanceOutsider::create(['outsider_id' => $o4->outsider_id, 'session_id' => $specialEdSessions[1]->session_id, 'timestamp_status' => 2.5]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // GURU BARU ENROLLMENTS IN EXISTING COURSES
        // ─────────────────────────────────────────────────────────────────────
        $classroomMgmt = Training::where('courseName', 'Classroom Management Strategies')->first();
        $effectiveAss  = Training::where('courseName', 'Effective Assessment and Feedback Techniques')->first();

        if ($ap3 && $effectiveAss && !EnrollGuruBaru::where('gn_id', $ap3->gn_id)->where('courseID', $effectiveAss->courseID)->exists()) {
            EnrollGuruBaru::create(['gn_id' => $ap3->gn_id, 'courseID' => $effectiveAss->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(9)]);
        }
        if ($ap4 && $classroomMgmt && !EnrollGuruBaru::where('gn_id', $ap4->gn_id)->where('courseID', $classroomMgmt->courseID)->exists()) {
            EnrollGuruBaru::create(['gn_id' => $ap4->gn_id, 'courseID' => $classroomMgmt->courseID, 'status' => 'approved', 'applied_at' => Carbon::now()->subDays(15)]);
        }
        if ($ap4 && $outdoorLearn && !EnrollGuruBaru::where('gn_id', $ap4->gn_id)->where('courseID', $outdoorLearn->courseID)->exists()) {
            EnrollGuruBaru::create([
                'gn_id'     => $ap4->gn_id,
                'courseID'        => $outdoorLearn->courseID,
                'status'           => 'rejected',
                'rejection_reason' => 'Dokumen sokongan tidak mencukupi.',
                'applied_at'       => Carbon::now()->subDays(20),
            ]);
        }

        // ─────────────────────────────────────────────────────────────────────
        // NOTIFICATIONS FOR NEW USERS
        // ─────────────────────────────────────────────────────────────────────
        $newTeacherUsers = [$teacher4, $teacher5, $teacher6, $teacher7, $teacher8];
        $newCourses = [
            $mentalHealth->courseName => $mentalHealth->courseID,
            $engTeaching->courseName  => $engTeaching->courseID,
            $leadership->courseName   => $leadership->courseID,
            $dataDriven->courseName   => $dataDriven->courseID,
        ];

        foreach ($newTeacherUsers as $u) {
            if (!$u) continue;
            foreach ($newCourses as $name => $id) {
                Notification::create([
                    'user_id'    => $u->id,
                    'message'    => "New training available: '{$name}'.",
                    'type'       => 'training.created',
                    'url'        => route('trainings.show', $id),
                    'is_read'    => true,
                    'created_at' => Carbon::now()->subDays(rand(10, 60)),
                ]);
            }
        }

        // Approval/rejection notifications for new teachers
        if ($teacher4) {
            Notification::create(['user_id' => $teacher4->id, 'message' => "Your application for 'Mental Health & Wellbeing in Schools' has been approved.", 'type' => 'application.approved', 'url' => route('applications.mine'), 'is_read' => true, 'created_at' => Carbon::now()->subDays(54)]);
            Notification::create(['user_id' => $teacher4->id, 'message' => "Your application for 'Data-Driven Teaching Practices' is pending review.", 'type' => 'application.submitted', 'url' => route('applications.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(3)]);
        }
        if ($teacher5) {
            Notification::create(['user_id' => $teacher5->id, 'message' => "Your application for 'Data-Driven Teaching Practices' has been approved.", 'type' => 'application.approved', 'url' => route('applications.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(6)]);
            Notification::create(['user_id' => $teacher5->id, 'message' => "Reminder: 'Creative Arts Integration in Learning' session is tomorrow. Please mark your attendance.", 'type' => 'training.reminder', 'url' => route('attendance.mark.form', $creativeArts->courseID), 'is_read' => false, 'created_at' => Carbon::now()->subHours(8)]);
        }
        if ($teacher6) {
            Notification::create(['user_id' => $teacher6->id, 'message' => "Reminder: 'Creative Arts Integration in Learning' session is tomorrow. Don't forget to mark attendance.", 'type' => 'training.reminder', 'url' => route('attendance.mark.form', $creativeArts->courseID), 'is_read' => false, 'created_at' => Carbon::now()->subHours(7)]);
        }
        if ($teacher7) {
            Notification::create(['user_id' => $teacher7->id, 'message' => "Your application for 'Mental Health & Wellbeing in Schools' has been rejected. Reason: Permohonan tidak lengkap.", 'type' => 'application.rejected', 'url' => route('applications.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(55)]);
        }
        if ($teacher8) {
            Notification::create(['user_id' => $teacher8->id, 'message' => "Your application for 'English Language Teaching Methodology' has been approved.", 'type' => 'application.approved', 'url' => route('applications.mine'), 'is_read' => true, 'created_at' => Carbon::now()->subDays(41)]);
            Notification::create(['user_id' => $teacher8->id, 'message' => "Reminder: 'Creative Arts Integration in Learning' session is tomorrow. Please attend.", 'type' => 'training.reminder', 'url' => route('attendance.mark.form', $creativeArts->courseID), 'is_read' => false, 'created_at' => Carbon::now()->subHours(6)]);
        }

        // Notifications for new outsiders
        if ($outsider2User) {
            Notification::create(['user_id' => $outsider2User->id, 'message' => "Your application for 'Leadership & School Management' has been approved.", 'type' => 'application.approved', 'url' => route('trainings.index'), 'is_read' => true, 'created_at' => Carbon::now()->subDays(66)]);
            Notification::create(['user_id' => $outsider2User->id, 'message' => "Your application for 'Community Outreach & Parent Engagement' has been approved.", 'type' => 'application.approved', 'url' => route('trainings.index'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(4)]);
        }
        if ($outsider3User) {
            Notification::create(['user_id' => $outsider3User->id, 'message' => "Your application for 'Creative Arts Integration in Learning' has been approved.", 'type' => 'application.approved', 'url' => route('trainings.index'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(7)]);
        }

        // Notifications for new guru baru
        if ($gb3User) {
            Notification::create(['user_id' => $gb3User->id, 'message' => "You have been automatically enrolled in 'Digital Pedagogy for 21st Century Educators' (series).", 'type' => 'training.created', 'url' => route('guru_baru.courses.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(30)]);
            Notification::create(['user_id' => $gb3User->id, 'message' => "Your application for 'Effective Assessment and Feedback Techniques' has been approved.", 'type' => 'application.approved', 'url' => route('guru_baru.applications.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(8)]);
        }
        if ($gb4User) {
            Notification::create(['user_id' => $gb4User->id, 'message' => "You have been automatically enrolled in 'Digital Pedagogy for 21st Century Educators' (series).", 'type' => 'training.created', 'url' => route('guru_baru.courses.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(30)]);
            Notification::create(['user_id' => $gb4User->id, 'message' => "Your application for 'Outdoor and Experiential Learning Facilitation' has been rejected.", 'type' => 'application.rejected', 'url' => route('guru_baru.applications.mine'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(18)]);
        }

        // Pending application notifications for Guru Besar
        if ($gb) {
            Notification::create(['user_id' => $gb->id, 'message' => "Nabilah Hassan has applied for 'Data-Driven Teaching Practices'. Action required.", 'type' => 'application.submitted', 'url' => route('applications.index'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(3)]);
            Notification::create(['user_id' => $gb->id, 'message' => "Azlan Mohd Noor has applied for 'Data-Driven Teaching Practices'. Action required.", 'type' => 'application.submitted', 'url' => route('applications.index'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(2)]);
            Notification::create(['user_id' => $gb->id, 'message' => "Farahiyah Yusoff has applied for 'Creative Arts Integration in Learning'. Action required.", 'type' => 'application.submitted', 'url' => route('applications.index'), 'is_read' => false, 'created_at' => Carbon::now()->subDays(2)]);
            Notification::create(['user_id' => $gb->id, 'message' => "Reminder: 'Creative Arts Integration in Learning' session is tomorrow. 3 teachers enrolled.", 'type' => 'training.reminder', 'url' => route('attendance.manage', $creativeArts->courseID), 'is_read' => false, 'created_at' => Carbon::now()->subHours(10)]);
        }
    }
}
