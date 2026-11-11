<?php

namespace Database\Seeders;

use App\Models\GuruNew;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\Outsider;
use App\Models\Teacher;
use App\Models\Notification;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $gb       = User::where('email', 'principal@edutrain.test')->firstOrFail();
            $teacher1 = User::where('email', 'teacher1@edutrain.test')->firstOrFail();
            $teacher2 = User::where('email', 'teacher2@edutrain.test')->firstOrFail();
            $teacher3 = User::where('email', 'teacher3@edutrain.test')->firstOrFail();
            $outsider = User::where('email', 'outsider@edutrain.test')->firstOrFail();

            $completedTraining = Training::where('courseName', 'Introduction to Inclusive Education')->firstOrFail();
            $upcomingTraining  = Training::where('courseName', 'Digital Pedagogy for 21st Century Educators')->firstOrFail();
            $ongoingTraining   = Training::where('courseName', 'Classroom Management Strategies')->firstOrFail();

            // Resolve teacher records
            $t1Record = Teacher::where('email', $teacher1->email)->first();
            $t2Record = Teacher::where('email', $teacher2->email)->first();
            $t3Record = Teacher::where('email', $teacher3->email)->first();

            // ── Complete Training: approved applications ──────────────────────
            Application::create([
                'teacherID'  => $t1Record->teacherID,
                'courseID'  => $completedTraining->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(45),
            ]);

            Application::create([
                'teacherID'  => $t2Record->teacherID,
                'courseID'  => $completedTraining->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(44),
            ]);

            // ── Complete Training: attendance records ──────────────────────────
            $morningCompleted = CourseSession::where('courseID', $completedTraining->courseID)
                ->orderBy('start_time')->first();
            $eveningCompleted = CourseSession::where('courseID', $completedTraining->courseID)
                ->orderBy('start_time', 'desc')->first();

            if ($morningCompleted && $t1Record) {
                Attendance::create([
                    'teacher_id' => $t1Record->teacherID, 'session_id' => $morningCompleted->session_id,
                    'timestamp_status' => 4.5, 'hours_ladap' => 2.25,
                ]);
            }
            if ($eveningCompleted && $t2Record && $eveningCompleted->session_id !== $morningCompleted?->session_id) {
                Attendance::create([
                    'teacher_id' => $t2Record->teacherID, 'session_id' => $eveningCompleted->session_id,
                    'timestamp_status' => 3.0, 'hours_ladap' => 1.5,
                ]);
            }

            // ── Upcoming Training: pending/approved applications ────────────────
            Application::create([
                'teacherID'  => $t2Record->teacherID,
                'courseID'  => $upcomingTraining->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(5),
            ]);

            Application::create([
                'teacherID'  => $t3Record->teacherID,
                'courseID'  => $upcomingTraining->courseID,
                'status'     => 'pending',
                'applied_at' => Carbon::now()->subDays(2),
            ]);

            // ── Ongoing Training: applications ────────────────────────────────
            Application::create([
                'teacherID'  => $t1Record->teacherID,
                'courseID'  => $ongoingTraining->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(20),
            ]);

            Application::create([
                'teacherID'  => $t2Record->teacherID,
                'courseID'  => $ongoingTraining->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(19),
            ]);

            Application::create([
                'teacherID'  => $t3Record->teacherID,
                'courseID'  => $ongoingTraining->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(18),
            ]);

            // ── Ongoing Training: move sessions to presentation day ────────────
            $presentationDay = Carbon::tomorrow()->toDateString();

            CourseSession::where('courseID', $ongoingTraining->courseID)
                ->where('session_date', '2026-06-05')
                ->update(['session_date' => $presentationDay]);

            $morningOngoing = CourseSession::where('courseID', $ongoingTraining->courseID)
                ->where('session_date', $presentationDay)
                ->orderBy('start_time')->first();
            if ($morningOngoing) {
                $morningOngoing->update([
                    'start_time' => '08:00:00',
                    'end_time'   => '13:00:00',
                ]);
            }

            $eveningOngoing = CourseSession::where('courseID', $ongoingTraining->courseID)
                ->where('session_date', $presentationDay)
                ->orderBy('start_time', 'desc')->first();
            if ($eveningOngoing && $eveningOngoing->session_id !== $morningOngoing?->session_id) {
                $eveningOngoing->update([
                    'start_time' => '14:00:00',
                    'end_time'   => '23:59:00',
                ]);
            }

            // ── Ongoing Training: attendance ──────────────────────────────────
            if ($morningOngoing && $t1Record) {
                Attendance::create([
                    'teacher_id' => $t1Record->teacherID, 'session_id' => $morningOngoing->session_id,
                    'timestamp_status' => 4.5, 'hours_ladap' => 2.25,
                ]);
            }
            if ($eveningOngoing && $t2Record && $eveningOngoing->session_id !== $morningOngoing?->session_id) {
                Attendance::create([
                    'teacher_id' => $t2Record->teacherID, 'session_id' => $eveningOngoing->session_id,
                    'timestamp_status' => 3.0, 'hours_ladap' => 1.5,
                ]);
            }

            // ── Al Amin Workshop: application and attendance ───────────────────
            $alAminTraining = Training::where('courseName', 'Al Amin School Development Workshop')->first();
            if ($alAminTraining && $t1Record) {
                Application::create([
                    'teacherID'  => $t1Record->teacherID,
                    'courseID'  => $alAminTraining->courseID,
                    'status'     => 'approved',
                    'applied_at' => Carbon::now()->subHours(2),
                ]);

                $alAminSession = CourseSession::where('courseID', $alAminTraining->courseID)
                    ->where('session_date', $presentationDay)
                    ->first();
                if ($alAminSession) {
                    Attendance::create([
                        'teacher_id'       => $t1Record->teacherID,
                        'session_id'       => $alAminSession->session_id,
                        'timestamp_status' => 4.0,
                        'hours_ladap'      => 4.0,
                    ]);
                }
            }

            // ── Guru Baru applications (non-series courses) ───────────────────
            $stemTraining    = Training::where('courseName', 'STEM Integration Programme for Secondary Teachers')->first();
            $outdoorTraining = Training::where('courseName', 'Outdoor and Experiential Learning Facilitation')->first();
            $guruNew         = GuruNew::first();

            if ($guruNew && $stemTraining) {
                EnrollGuruBaru::create([
                    'gn_id'      => $guruNew->gn_id,
                    'courseID'  => $stemTraining->courseID,
                    'status'     => 'pending',
                    'applied_at' => Carbon::now()->subDays(3),
                ]);
            }

            if ($guruNew && $outdoorTraining) {
                EnrollGuruBaru::create([
                    'gn_id'      => $guruNew->gn_id,
                    'courseID'  => $outdoorTraining->courseID,
                    'status'     => 'approved',
                    'applied_at' => Carbon::now()->subDays(7),
                ]);
            }

            // ── Outsider applications ─────────────────────────────────────────
            $outsiderRecord = Outsider::where('user_id', $outsider->id)->first();

            if ($outsiderRecord && $outdoorTraining) {
                EnrollOutsider::create([
                    'outsider_id' => $outsiderRecord->outsider_id,
                    'courseID'   => $outdoorTraining->courseID,
                    'status'      => 'pending',
                    'applied_at'  => Carbon::now()->subDays(2),
                ]);
            }

            if ($outsiderRecord && $stemTraining) {
                EnrollOutsider::create([
                    'outsider_id' => $outsiderRecord->outsider_id,
                    'courseID'   => $stemTraining->courseID,
                    'status'      => 'approved',
                    'applied_at'  => Carbon::now()->subDays(5),
                ]);
            }

            // ── Notifications ─────────────────────────────────────────────────
            $allUsers = [$gb, $teacher1, $teacher2, $teacher3, $outsider];

            foreach ($allUsers as $user) {
                Notification::create([
                    'user_id'    => $user->id,
                    'message'    => "New training available: '{$completedTraining->courseName}'.",
                    'type'       => 'training.created',
                    'url'        => route('trainings.show', $completedTraining->courseID),
                    'is_read'    => false,
                    'created_at' => Carbon::now()->subDays(45),
                ]);

                Notification::create([
                    'user_id'    => $user->id,
                    'message'    => "New training available: '{$upcomingTraining->courseName}'.",
                    'type'       => 'training.created',
                    'url'        => route('trainings.show', $upcomingTraining->courseID),
                    'is_read'    => false,
                    'created_at' => Carbon::now()->subDays(7),
                ]);

                Notification::create([
                    'user_id'    => $user->id,
                    'message'    => "New training available: '{$ongoingTraining->courseName}'.",
                    'type'       => 'training.created',
                    'url'        => route('trainings.show', $ongoingTraining->courseID),
                    'is_read'    => false,
                    'created_at' => Carbon::now()->subDays(22),
                ]);
            }
        });
    }
}
