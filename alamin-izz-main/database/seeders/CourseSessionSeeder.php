<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Trainer;
use Illuminate\Database\Seeder;

class CourseSessionSeeder extends Seeder
{
    public function run(): void
    {
        $trainer1 = Trainer::where('trainerEmail', 'razif.hassan@trainer.test')->first();
        $trainer2 = Trainer::where('trainerEmail', 'limweichen@trainer.test')->first();
        $trainer3 = Trainer::where('trainerEmail', 'nuraini.malik@trainer.test')->first();

        // ── Course 1 — Introduction to Inclusive Education (single, completed) ──
        // Single day: 2026-01-15, morning + evening
        $course1 = Course::where('courseName', 'Introduction to Inclusive Education')->first();
        if ($course1) {
            $s1a = CourseSession::create([
                'courseID'      => $course1->courseID,
                'session_date'   => '2026-01-15',
                'start_time'     => '08:00:00',
                'end_time'       => '12:30:00',
                'location'       => 'Dewan Serbaguna, Jabatan Pendidikan Selangor',
            ]);
            $s1b = CourseSession::create([
                'courseID'      => $course1->courseID,
                'session_date'   => '2026-01-15',
                'start_time'     => '14:00:00',
                'end_time'       => '17:00:00',
                'location'       => 'Dewan Serbaguna, Jabatan Pendidikan Selangor',
            ]);

            if ($trainer1) {
                $s1a->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
                $s1b->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
            }
        }

        // ── Course 2 — Digital Pedagogy (series, upcoming) ──
        // Series: one session per day, no morning/evening split
        $course2 = Course::where('courseName', 'Digital Pedagogy for 21st Century Educators')->first();
        if ($course2) {
            $s3 = CourseSession::create([
                'courseID'      => $course2->courseID,
                'session_date'   => '2026-07-10',
                'start_time'     => '09:00:00',
                'end_time'       => '17:00:00',
                'location'       => 'Makmal Komputer, Kementerian Pendidikan Malaysia',
            ]);
            $s4 = CourseSession::create([
                'courseID'      => $course2->courseID,
                'session_date'   => '2026-07-11',
                'start_time'     => '09:00:00',
                'end_time'       => '17:00:00',
                'location'       => 'Makmal Komputer, Kementerian Pendidikan Malaysia',
            ]);

            if ($trainer2) {
                $s3->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
                $s4->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
            }
            if ($trainer3) {
                $s3->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
            }
        }

        // ── Course 3 — Classroom Management Strategies (single, ongoing) ──
        // Two days, each with morning + evening. DemoSeeder moves Day 1 to today.
        $course3 = Course::where('courseName', 'Classroom Management Strategies')->first();
        if ($course3) {
            // Day 1 (will be updated to today by DemoSeeder)
            $s5a = CourseSession::create([
                'courseID'      => $course3->courseID,
                'session_date'   => '2026-06-05',
                'start_time'     => '08:00:00',
                'end_time'       => '12:30:00',
                'location'       => 'Bilik Latihan A, Institut Aminuddin Baki',
            ]);
            $s5b = CourseSession::create([
                'courseID'      => $course3->courseID,
                'session_date'   => '2026-06-05',
                'start_time'     => '14:00:00',
                'end_time'       => '17:00:00',
                'location'       => 'Bilik Latihan A, Institut Aminuddin Baki',
            ]);
            // Day 2
            $s6a = CourseSession::create([
                'courseID'      => $course3->courseID,
                'session_date'   => '2026-06-09',
                'start_time'     => '08:00:00',
                'end_time'       => '12:30:00',
                'location'       => 'Bilik Latihan A, Institut Aminuddin Baki',
            ]);
            $s6b = CourseSession::create([
                'courseID'      => $course3->courseID,
                'session_date'   => '2026-06-09',
                'start_time'     => '14:00:00',
                'end_time'       => '17:00:00',
                'location'       => 'Bilik Latihan A, Institut Aminuddin Baki',
            ]);

            if ($trainer3) {
                $s5a->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                $s5b->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                $s6a->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                $s6b->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
            }
            if ($trainer1) {
                $s6a->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
                $s6b->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
            }
        }

        // ── Course 4 — STEM Integration Programme (multi_day, upcoming) ──
        // 3 days, each with morning + evening
        $course4 = Course::where('courseName', 'STEM Integration Programme for Secondary Teachers')->first();
        if ($course4) {
            foreach ([
                ['2026-08-10', 'Dewan Utama, Pusat STEM Negara, Putrajaya'],
                ['2026-08-11', 'Makmal Sains, Pusat STEM Negara, Putrajaya'],
                ['2026-08-12', 'Dewan Utama, Pusat STEM Negara, Putrajaya'],
            ] as [$date, $loc]) {
                $sa = CourseSession::create([
                    'courseID' => $course4->courseID, 'session_date' => $date,
                    'start_time' => '08:00:00', 'end_time' => '12:30:00',
                    'location' => $loc,
                ]);
                $sb = CourseSession::create([
                    'courseID' => $course4->courseID, 'session_date' => $date,
                    'start_time' => '14:00:00', 'end_time' => '17:00:00',
                    'location' => $loc,
                ]);
                if ($trainer2) {
                    $sa->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
                    $sb->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
                }
                if ($trainer3) {
                    $sa->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                    $sb->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                }
            }
        }

        // ── Effective Assessment and Feedback Techniques (single, tomorrow) ──
        $c5 = Course::where('courseName', 'Effective Assessment and Feedback Techniques')->first();
        if ($c5) {
            $sa = CourseSession::create([
                'courseID' => $c5->courseID, 'session_date' => '2026-06-08',
                'start_time' => '08:00:00', 'end_time' => '12:30:00',
                'location' => 'Dewan Seminar, Jabatan Pendidikan Selangor',
            ]);
            $sb = CourseSession::create([
                'courseID' => $c5->courseID, 'session_date' => '2026-06-08',
                'start_time' => '14:00:00', 'end_time' => '17:00:00',
                'location' => 'Dewan Seminar, Jabatan Pendidikan Selangor',
            ]);
            if ($trainer3) {
                $sa->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                $sb->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
            }
        }

        // ── Special Education Awareness Workshop (single, 2026-06-10) ──
        $c6 = Course::where('courseName', 'Special Education Awareness Workshop')->first();
        if ($c6) {
            $sa = CourseSession::create([
                'courseID' => $c6->courseID, 'session_date' => '2026-06-10',
                'start_time' => '08:30:00', 'end_time' => '12:30:00',
                'location' => 'Pusat Sumber Pendidikan Khas, KPM, Putrajaya',
            ]);
            $sb = CourseSession::create([
                'courseID' => $c6->courseID, 'session_date' => '2026-06-10',
                'start_time' => '14:00:00', 'end_time' => '16:30:00',
                'location' => 'Pusat Sumber Pendidikan Khas, KPM, Putrajaya',
            ]);
            if ($trainer1) {
                $sa->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
                $sb->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
            }
        }

        // ── Outdoor and Experiential Learning (multi_day, Sep 2026) ──
        $c7 = Course::where('courseName', 'Outdoor and Experiential Learning Facilitation')->first();
        if ($c7) {
            foreach ([
                ['2026-09-15', 'Kem Bina Semangat, Hulu Langat, Selangor'],
                ['2026-09-16', 'Kem Bina Semangat, Hulu Langat, Selangor'],
            ] as [$date, $loc]) {
                $sa = CourseSession::create([
                    'courseID' => $c7->courseID, 'session_date' => $date,
                    'start_time' => '07:30:00', 'end_time' => '12:30:00',
                    'location' => $loc,
                ]);
                $sb = CourseSession::create([
                    'courseID' => $c7->courseID, 'session_date' => $date,
                    'start_time' => '14:00:00', 'end_time' => '17:00:00',
                    'location' => $loc,
                ]);
                if ($trainer1) {
                    $sa->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
                    $sb->trainers()->syncWithoutDetaching([$trainer1->trainer_id]);
                }
                if ($trainer3) {
                    $sa->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                    $sb->trainers()->syncWithoutDetaching([$trainer3->trainer_id]);
                }
            }
        }

        // ── Project-Based Learning Implementation Workshop (multi_day, Jun 2026) ──
        $c9 = Course::where('courseName', 'Project-Based Learning Implementation Workshop')->first();
        if ($c9) {
            foreach ([
                ['2026-06-07', 'Dewan Besar, Jabatan Pendidikan Selangor'],
                ['2026-06-14', 'Dewan Besar, Jabatan Pendidikan Selangor'],
            ] as [$date, $loc]) {
                $sa = CourseSession::create([
                    'courseID' => $c9->courseID, 'session_date' => $date,
                    'start_time' => '08:00:00', 'end_time' => '12:30:00',
                    'location' => $loc,
                ]);
                $sb = CourseSession::create([
                    'courseID' => $c9->courseID, 'session_date' => $date,
                    'start_time' => '14:00:00', 'end_time' => '17:00:00',
                    'location' => $loc,
                ]);
                if ($trainer2) {
                    $sa->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
                    $sb->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
                }
            }
        }

        // ── Al Amin School Development Workshop (single, presentation day) ──
        $cAlAmin = Course::where('courseName', 'Al Amin School Development Workshop')->first();
        if ($cAlAmin) {
            CourseSession::create([
                'courseID'      => $cAlAmin->courseID,
                'session_date'   => \Illuminate\Support\Carbon::tomorrow()->toDateString(),
                'start_time'     => '08:00:00',
                'end_time'       => '17:00:00',
                'location'       => 'Dewan Seminar, EDU Al-amin',
            ]);
        }

        // ── Instructional Coaching for School Leaders (series, Oct 2026) ──
        // Series: one session per day
        $c8 = Course::where('courseName', 'Instructional Coaching for School Leaders')->first();
        if ($c8) {
            foreach ([
                ['2026-10-06', '09:00:00', '17:00:00', 'Bilik Latihan 1, Institut Aminuddin Baki'],
                ['2026-10-13', '09:00:00', '17:00:00', 'Bilik Latihan 1, Institut Aminuddin Baki'],
                ['2026-10-20', '09:00:00', '17:00:00', 'Bilik Latihan 1, Institut Aminuddin Baki'],
            ] as [$date, $start, $end, $loc]) {
                $s = CourseSession::create([
                    'courseID' => $c8->courseID, 'session_date' => $date,
                    'start_time' => $start, 'end_time' => $end,
                    'location' => $loc,
                ]);
                if ($trainer2) $s->trainers()->syncWithoutDetaching([$trainer2->trainer_id]);
            }
        }
    }
}
