<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\EnrollGuruBaru;
use App\Models\GuruNew;
use App\Models\EnrollOutsider;
use App\Models\FeedbackForm;
use App\Models\FeedbackQuestion;
use App\Models\FeedbackResponse;
use App\Models\Notification;
use App\Models\Outsider;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PresentationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $gb       = User::where('email', 'principal@edutrain.test')->firstOrFail();
            $teacher1 = User::where('email', 'teacher1@edutrain.test')->firstOrFail();
            $teacher2 = User::where('email', 'teacher2@edutrain.test')->firstOrFail();
            $teacher3 = User::where('email', 'teacher3@edutrain.test')->firstOrFail();
            $outsider = User::where('email', 'outsider@edutrain.test')->firstOrFail();

            $t1 = Teacher::where('email', $teacher1->email)->firstOrFail();
            $t2 = Teacher::where('email', $teacher2->email)->firstOrFail();
            $t3 = Teacher::where('email', $teacher3->email)->firstOrFail();

            $outsiderRecord = Outsider::where('user_id', $outsider->id)->firstOrFail();
            $applicant1 = GuruNew::where('email', 'applicant1@edutrain.test')->first();
            $applicant2 = GuruNew::where('email', 'applicant2@edutrain.test')->first();
            $gbUser2    = User::where('email', 'applicant2@edutrain.test')->first();

            $inclusiveEd   = Training::where('courseName', 'Introduction to Inclusive Education')->firstOrFail();
            $digitalPed    = Training::where('courseName', 'Digital Pedagogy for 21st Century Educators')->firstOrFail();
            $classroomMgmt = Training::where('courseName', 'Classroom Management Strategies')->firstOrFail();
            $stemTraining  = Training::where('courseName', 'STEM Integration Programme for Secondary Teachers')->firstOrFail();
            $effectiveAss  = Training::where('courseName', 'Effective Assessment and Feedback Techniques')->firstOrFail();
            $specialEd     = Training::where('courseName', 'Special Education Awareness Workshop')->firstOrFail();
            $outdoorLearn  = Training::where('courseName', 'Outdoor and Experiential Learning Facilitation')->firstOrFail();
            $alAmin        = Training::where('courseName', 'Al Amin School Development Workshop')->firstOrFail();
            $pblWorkshop   = Training::where('courseName', 'Project-Based Learning Implementation Workshop')->firstOrFail();

            $presentationDay = Carbon::tomorrow()->toDateString();

            // ─────────────────────────────────────────────────────────────────
            // MORE APPLICATIONS (teacher)
            // ─────────────────────────────────────────────────────────────────

            // Teacher1 → STEM (pending — GB can approve live during demo)
            Application::create([
                'teacherID'  => $t1->teacherID,
                'courseID'  => $stemTraining->courseID,
                'status'     => 'pending',
                'applied_at' => Carbon::now()->subDay(),
            ]);

            // Teacher2 → Effective Assessment (pending — GB can reject live)
            Application::create([
                'teacherID'  => $t2->teacherID,
                'courseID'  => $effectiveAss->courseID,
                'status'     => 'pending',
                'applied_at' => Carbon::now()->subHours(5),
            ]);

            // Teacher3 → STEM (rejected with Malay reason)
            Application::create([
                'teacherID'        => $t3->teacherID,
                'courseID'        => $stemTraining->courseID,
                'status'           => 'rejected',
                'rejection_reason' => 'Kuota latihan ini telah penuh. Sila memohon semula pada sesi akan datang.',
                'applied_at'       => Carbon::now()->subDays(8),
            ]);

            // Teacher1 → Effective Assessment (approved)
            Application::create([
                'teacherID'  => $t1->teacherID,
                'courseID'  => $effectiveAss->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(10),
            ]);

            // Teacher2 → Al Amin (approved — session tomorrow)
            Application::create([
                'teacherID'  => $t2->teacherID,
                'courseID'  => $alAmin->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(3),
            ]);

            // Teacher3 → Al Amin (approved — session tomorrow)
            Application::create([
                'teacherID'  => $t3->teacherID,
                'courseID'  => $alAmin->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(3),
            ]);

            // Teacher3 → Project-Based Learning (approved — upcoming)
            Application::create([
                'teacherID'  => $t3->teacherID,
                'courseID'  => $pblWorkshop->courseID,
                'status'     => 'approved',
                'applied_at' => Carbon::now()->subDays(12),
            ]);

            // ─────────────────────────────────────────────────────────────────
            // MORE ATTENDANCE (Inclusive Ed — completed, bulks up LADAP hours)
            // ─────────────────────────────────────────────────────────────────

            $incSessions = CourseSession::where('courseID', $inclusiveEd->courseID)
                ->orderBy('start_time')->get();
            $incMorning = $incSessions->first();
            $incEvening = $incSessions->last();

            // Teacher1 also attended evening (already has morning from DemoSeeder)
            if ($incEvening && $incEvening->session_id !== $incMorning?->session_id) {
                if (!Attendance::where('teacher_id', $t1->teacherID)->where('session_id', $incEvening->session_id)->exists()) {
                    Attendance::create(['teacher_id' => $t1->teacherID, 'session_id' => $incEvening->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]);
                }
            }

            // Teacher2 also attended morning (already has evening from DemoSeeder)
            if ($incMorning) {
                if (!Attendance::where('teacher_id', $t2->teacherID)->where('session_id', $incMorning->session_id)->exists()) {
                    Attendance::create(['teacher_id' => $t2->teacherID, 'session_id' => $incMorning->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]);
                }
            }

            // Teacher3 attended both sessions of Inclusive Ed
            if ($incMorning && !Attendance::where('teacher_id', $t3->teacherID)->where('session_id', $incMorning->session_id)->exists()) {
                Attendance::create(['teacher_id' => $t3->teacherID, 'session_id' => $incMorning->session_id, 'timestamp_status' => 4.5, 'hours_ladap' => 2.25]);
            }
            if ($incEvening && $incEvening->session_id !== $incMorning?->session_id) {
                if (!Attendance::where('teacher_id', $t3->teacherID)->where('session_id', $incEvening->session_id)->exists()) {
                    Attendance::create(['teacher_id' => $t3->teacherID, 'session_id' => $incEvening->session_id, 'timestamp_status' => 3.0, 'hours_ladap' => 1.5]);
                }
            }

            // ─────────────────────────────────────────────────────────────────
            // FEEDBACK RESPONSES — Inclusive Ed (completed training)
            // ─────────────────────────────────────────────────────────────────

            $feedbackForm = FeedbackForm::where('courseID', $inclusiveEd->courseID)->first();

            if ($feedbackForm) {
                $questions = FeedbackQuestion::where('formID', $feedbackForm->formID)
                    ->orderBy('questionOrder')->get();

                // Teacher1 responses — highly positive
                $t1Answers = [
                    1 => ['answer_value' => '5',   'rating' => null, 'comment' => null],
                    2 => ['answer_value' => '5',   'rating' => null, 'comment' => null],
                    3 => ['answer_value' => '5',   'rating' => 5,    'comment' => null],
                    4 => ['answer_value' => '4',   'rating' => 4,    'comment' => null],
                    5 => ['answer_value' => 'yes', 'rating' => null, 'comment' => null],
                    6 => ['answer_value' => '',    'rating' => null, 'comment' => 'Kursus ini sangat berguna. Saya dapat mengaplikasikan strategi pendidikan inklusif dalam kelas saya dengan lebih berkesan.'],
                ];

                foreach ($questions as $q) {
                    $ans = $t1Answers[$q->questionOrder] ?? ['answer_value' => '4', 'rating' => null, 'comment' => null];
                    FeedbackResponse::create([
                        'formID'       => $feedbackForm->formID,
                        'questionID'    => $q->questionID,
                        'user_id'       => $teacher1->id,
                        'teacherID'     => $t1->teacherID,
                        'gn_id'  => null,
                        'answer_value'  => $ans['answer_value'],
                        'rating'        => $ans['rating'],
                        'comment'       => $ans['comment'],
                        'responseDate' => Carbon::now()->subDays(30)->toDateString(),
                        'created_at'    => Carbon::now()->subDays(30),
                    ]);
                }

                // Teacher2 responses — good with suggestion
                $t2Answers = [
                    1 => ['answer_value' => '4',   'rating' => null, 'comment' => null],
                    2 => ['answer_value' => '4',   'rating' => null, 'comment' => null],
                    3 => ['answer_value' => '5',   'rating' => 5,    'comment' => null],
                    4 => ['answer_value' => '4',   'rating' => 4,    'comment' => null],
                    5 => ['answer_value' => 'yes', 'rating' => null, 'comment' => null],
                    6 => ['answer_value' => '',    'rating' => null, 'comment' => 'Program yang sangat informatif. Cadangan: tambahkan lebih banyak sesi amali dan perbincangan kumpulan.'],
                ];

                foreach ($questions as $q) {
                    $ans = $t2Answers[$q->questionOrder] ?? ['answer_value' => '4', 'rating' => null, 'comment' => null];
                    FeedbackResponse::create([
                        'formID'       => $feedbackForm->formID,
                        'questionID'    => $q->questionID,
                        'user_id'       => $teacher2->id,
                        'teacherID'     => $t2->teacherID,
                        'gn_id'  => null,
                        'answer_value'  => $ans['answer_value'],
                        'rating'        => $ans['rating'],
                        'comment'       => $ans['comment'],
                        'responseDate' => Carbon::now()->subDays(28)->toDateString(),
                        'created_at'    => Carbon::now()->subDays(28),
                    ]);
                }

                // Teacher3 responses — average with improvement note
                $t3Answers = [
                    1 => ['answer_value' => '4',   'rating' => null, 'comment' => null],
                    2 => ['answer_value' => '3',   'rating' => null, 'comment' => null],
                    3 => ['answer_value' => '4',   'rating' => 4,    'comment' => null],
                    4 => ['answer_value' => '4',   'rating' => 4,    'comment' => null],
                    5 => ['answer_value' => 'yes', 'rating' => null, 'comment' => null],
                    6 => ['answer_value' => '',    'rating' => null, 'comment' => 'Topik penting untuk semua guru. Harap ada sesi susulan yang lebih mendalam tentang murid berkeperluan khas.'],
                ];

                foreach ($questions as $q) {
                    $ans = $t3Answers[$q->questionOrder] ?? ['answer_value' => '4', 'rating' => null, 'comment' => null];
                    FeedbackResponse::create([
                        'formID'       => $feedbackForm->formID,
                        'questionID'    => $q->questionID,
                        'user_id'       => $teacher3->id,
                        'teacherID'     => $t3->teacherID,
                        'gn_id'  => null,
                        'answer_value'  => $ans['answer_value'],
                        'rating'        => $ans['rating'],
                        'comment'       => $ans['comment'],
                        'responseDate' => Carbon::now()->subDays(26)->toDateString(),
                        'created_at'    => Carbon::now()->subDays(26),
                    ]);
                }
            }

            // ─────────────────────────────────────────────────────────────────
            // GURU BARU — Applicant 2 data
            // ─────────────────────────────────────────────────────────────────

            if ($applicant2) {
                // Pending for STEM (for HR to approve live)
                if (!EnrollGuruBaru::where('gn_id', $applicant2->gn_id)->where('courseID', $stemTraining->courseID)->exists()) {
                    EnrollGuruBaru::create([
                        'gn_id' => $applicant2->gn_id,
                        'courseID'    => $stemTraining->courseID,
                        'status'       => 'pending',
                        'applied_at'   => Carbon::now()->subDays(2),
                    ]);
                }

                // Approved for Effective Assessment
                if (!EnrollGuruBaru::where('gn_id', $applicant2->gn_id)->where('courseID', $effectiveAss->courseID)->exists()) {
                    EnrollGuruBaru::create([
                        'gn_id' => $applicant2->gn_id,
                        'courseID'    => $effectiveAss->courseID,
                        'status'       => 'approved',
                        'applied_at'   => Carbon::now()->subDays(9),
                    ]);
                }

                // Rejected for Outdoor Learning (shows rejection history)
                if (!EnrollGuruBaru::where('gn_id', $applicant2->gn_id)->where('courseID', $outdoorLearn->courseID)->exists()) {
                    EnrollGuruBaru::create([
                        'gn_id'     => $applicant2->gn_id,
                        'courseID'        => $outdoorLearn->courseID,
                        'status'           => 'rejected',
                        'rejection_reason' => 'Sila kemukakan dokumen sokongan yang lengkap sebelum memohon semula.',
                        'applied_at'       => Carbon::now()->subDays(14),
                    ]);
                }

                // Notification for applicant2
                if ($gbUser2) {
                    Notification::create([
                        'user_id'    => $gbUser2->id,
                        'message'    => "Your application for 'Effective Assessment and Feedback Techniques' has been approved.",
                        'type'       => 'application.approved',
                        'url'        => route('guru_baru.applications.mine'),
                        'is_read'    => false,
                        'created_at' => Carbon::now()->subDays(8),
                    ]);
                    Notification::create([
                        'user_id'    => $gbUser2->id,
                        'message'    => "Your application for 'Outdoor and Experiential Learning Facilitation' has been rejected.",
                        'type'       => 'application.rejected',
                        'url'        => route('guru_baru.applications.mine'),
                        'is_read'    => false,
                        'created_at' => Carbon::now()->subDays(13),
                    ]);
                }
            }

            // ─────────────────────────────────────────────────────────────────
            // OUTSIDER — additional application
            // ─────────────────────────────────────────────────────────────────

            if (!EnrollOutsider::where('outsider_id', $outsiderRecord->outsider_id)->where('courseID', $specialEd->courseID)->exists()) {
                EnrollOutsider::create([
                    'outsider_id' => $outsiderRecord->outsider_id,
                    'courseID'   => $specialEd->courseID,
                    'status'      => 'pending',
                    'applied_at'  => Carbon::now()->subDay(),
                ]);
            }

            // ─────────────────────────────────────────────────────────────────
            // RICH NOTIFICATIONS
            // ─────────────────────────────────────────────────────────────────

            // Teacher1 notifications
            Notification::create([
                'user_id'    => $teacher1->id,
                'message'    => "Your application for 'Classroom Management Strategies' has been approved. Welcome aboard!",
                'type'       => 'application.approved',
                'url'        => route('applications.mine'),
                'is_read'    => true,
                'created_at' => Carbon::now()->subDays(20),
            ]);
            Notification::create([
                'user_id'    => $teacher1->id,
                'message'    => "Your application for 'Effective Assessment and Feedback Techniques' has been approved.",
                'type'       => 'application.approved',
                'url'        => route('applications.mine'),
                'is_read'    => false,
                'created_at' => Carbon::now()->subDays(8),
            ]);
            Notification::create([
                'user_id'    => $teacher1->id,
                'message'    => "Reminder: 'Classroom Management Strategies' has a session tomorrow ({$presentationDay}). Please mark your attendance.",
                'type'       => 'training.reminder',
                'url'        => route('attendance.mark.form', $classroomMgmt->courseID),
                'is_read'    => false,
                'created_at' => Carbon::now()->subHours(10),
            ]);

            // Teacher2 notifications
            Notification::create([
                'user_id'    => $teacher2->id,
                'message'    => "Your application for 'Classroom Management Strategies' has been approved.",
                'type'       => 'application.approved',
                'url'        => route('applications.mine'),
                'is_read'    => true,
                'created_at' => Carbon::now()->subDays(19),
            ]);
            Notification::create([
                'user_id'    => $teacher2->id,
                'message'    => "Reminder: 'Classroom Management Strategies' session is tomorrow ({$presentationDay}). Don't forget to mark your attendance.",
                'type'       => 'training.reminder',
                'url'        => route('attendance.mark.form', $classroomMgmt->courseID),
                'is_read'    => false,
                'created_at' => Carbon::now()->subHours(8),
            ]);
            Notification::create([
                'user_id'    => $teacher2->id,
                'message'    => "New training available: 'Al Amin School Development Workshop'. Session is on {$presentationDay}.",
                'type'       => 'training.created',
                'url'        => route('trainings.show', $alAmin->courseID),
                'is_read'    => false,
                'created_at' => Carbon::now()->subDays(5),
            ]);

            // Teacher3 notifications
            Notification::create([
                'user_id'    => $teacher3->id,
                'message'    => "Your application for 'Classroom Management Strategies' has been approved.",
                'type'       => 'application.approved',
                'url'        => route('applications.mine'),
                'is_read'    => true,
                'created_at' => Carbon::now()->subDays(18),
            ]);
            Notification::create([
                'user_id'    => $teacher3->id,
                'message'    => "Your application for 'STEM Integration Programme for Secondary Teachers' has been rejected. Reason: Kuota latihan telah penuh.",
                'type'       => 'application.rejected',
                'url'        => route('applications.mine'),
                'is_read'    => false,
                'created_at' => Carbon::now()->subDays(6),
            ]);
            Notification::create([
                'user_id'    => $teacher3->id,
                'message'    => "Reminder: 'Classroom Management Strategies' session is tomorrow ({$presentationDay}). Please attend and mark your attendance.",
                'type'       => 'training.reminder',
                'url'        => route('attendance.mark.form', $classroomMgmt->courseID),
                'is_read'    => false,
                'created_at' => Carbon::now()->subHours(6),
            ]);

            // Guru Besar notifications
            Notification::create([
                'user_id'    => $gb->id,
                'message'    => "Ahmad Firdaus has applied for 'STEM Integration Programme for Secondary Teachers'. Action required.",
                'type'       => 'application.submitted',
                'url'        => route('applications.index'),
                'is_read'    => false,
                'created_at' => Carbon::now()->subDay(),
            ]);
            Notification::create([
                'user_id'    => $gb->id,
                'message'    => "Siti Norehan has applied for 'Effective Assessment and Feedback Techniques'. Action required.",
                'type'       => 'application.submitted',
                'url'        => route('applications.index'),
                'is_read'    => false,
                'created_at' => Carbon::now()->subHours(5),
            ]);
            Notification::create([
                'user_id'    => $gb->id,
                'message'    => "Training 'Classroom Management Strategies' session is scheduled for tomorrow ({$presentationDay}). 3 teachers are enrolled.",
                'type'       => 'training.reminder',
                'url'        => route('attendance.manage', $classroomMgmt->courseID),
                'is_read'    => false,
                'created_at' => Carbon::now()->subHours(12),
            ]);

            // Outsider notifications
            Notification::create([
                'user_id'    => $outsider->id,
                'message'    => "Your application for 'STEM Integration Programme for Secondary Teachers' has been approved.",
                'type'       => 'application.approved',
                'url'        => route('trainings.index'),
                'is_read'    => false,
                'created_at' => Carbon::now()->subDays(4),
            ]);
            Notification::create([
                'user_id'    => $outsider->id,
                'message'    => "Your application for 'Special Education Awareness Workshop' is pending review.",
                'type'       => 'application.submitted',
                'url'        => route('trainings.index'),
                'is_read'    => false,
                'created_at' => Carbon::now()->subDay(),
            ]);
        });
    }
}
