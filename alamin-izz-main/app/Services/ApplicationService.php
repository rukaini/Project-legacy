<?php

namespace App\Services;

use App\Events\ApplicationApproved;
use App\Events\ApplicationRejected;
use App\Events\ApplicationSubmitted;
use App\Events\TeacherAssigned;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\CourseSession;
use App\Models\Teacher;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApplicationService
{
    public const LADAP_TARGET_HOURS = 30.0;

    public function apply(User $user, Training $training): Application
    {
        abort_if($training->status !== 'upcoming', 422, 'This training is not open for applications.');

        $firstSessionDate = $training->courseSessions()->min('sessionDate');
        abort_if(
            $firstSessionDate === null || ! now()->startOfDay()->lt(Carbon::parse($firstSessionDate)->startOfDay()),
            422,
            'The application deadline has passed. Applications must be submitted at least one day before the first session.'
        );

        $teacher = Teacher::where('email', $user->email)->first();
        abort_unless($teacher, 422, 'Only registered teachers can apply for training.');

        $alreadyApplied = Application::where('teacherID', $teacher->teacherID)
            ->where('course_id', $training->getKey())
            ->exists();

        abort_if($alreadyApplied, 422, 'You have already applied for this training.');

        abort_if(
            $this->totalLadapHours($teacher) >= self::LADAP_TARGET_HOURS,
            422,
            'You have reached the ' . self::LADAP_TARGET_HOURS . '-hour LADAP target and cannot enroll in more training.'
        );

        $approvedCount = Application::where('course_id', $training->getKey())
            ->where('status', 'approved')
            ->count()
            + $training->outsiderEnrollments()->where('status', 'approved')->count()
            + $training->guruBaruEnrollments()->where('status', 'approved')->count();

        abort_if($approvedCount >= $training->capacity, 422, 'This training is full.');

        $application = Application::create([
            'teacherID'  => $teacher->teacherID,
            'courseID'  => $training->getKey(),
            'status'     => 'pending',
            'applied_at' => now(),
        ]);

        event(new ApplicationSubmitted($application));

        $this->writeAudit($user, 'application.submitted', $application, [
            'courseID' => $training->getKey(),
        ]);

        return $application;
    }

    public function approve(Application $application, User $guruBesar): void
    {
        DB::transaction(function () use ($application, $guruBesar) {
            $application->loadMissing('training');

            $approvedCount = Application::where('course_id', $application->courseID)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->count();

            abort_if(
                $approvedCount >= $application->training->capacity,
                422,
                'Cannot approve — training is at full capacity.'
            );

            abort_if($application->status === 'approved', 422, 'Application is already approved.');

            DB::table('enroll_teacher')
                ->where('teacherID', $application->teacherID)
                ->where('course_id', $application->courseID)
                ->update(['status' => 'approved']);

            $application->status = 'approved';

            event(new ApplicationApproved($application));

        });

        $this->writeAudit($guruBesar, 'application.approved', $application, [
            'courseID'  => $application->courseID,
            'teacherID' => $application->teacherID,
        ]);
    }

    public function reject(Application $application, User $guruBesar, ?string $reason): void
    {
        abort_if($application->status === 'rejected', 422, 'Application is already rejected.');

        DB::table('enroll_teacher')
            ->where('teacherID', $application->teacherID)
            ->where('course_id', $application->courseID)
            ->update(['status' => 'rejected', 'rejection_reason' => $reason]);

        $application->status           = 'rejected';
        $application->rejection_reason = $reason;

        event(new ApplicationRejected($application));

        $this->writeAudit($guruBesar, 'application.rejected', $application, [
            'courseID' => $application->courseID,
            'teacherID' => $application->teacherID,
            'reason'    => $reason,
        ]);
    }

    /**
     * @param array<int> $userIds
     */
    public function assignDirect(Training $training, array $userIds, User $guruBesar): void
    {
        $users = User::whereIn('id', $userIds)->get();

        $users->each(fn (User $u) => abort_unless($u->isTeacher(), 422, "User {$u->id} is not a teacher."));

        $assignedApplications = [];

        DB::transaction(function () use ($training, $users, &$assignedApplications) {
            foreach ($users as $user) {
                $teacher = Teacher::where('email', $user->email)->first();
                if (! $teacher) continue;

                $existing = Application::where('teacherID', $teacher->teacherID)
                    ->where('course_id', $training->getKey())
                    ->first();

                if ($existing) continue;

                $application = Application::create([
                    'teacherID'  => $teacher->teacherID,
                    'courseID'  => $training->getKey(),
                    'status'          => 'approved',
                    'enrollment_type' => Application::ENROLLMENT_ASSIGNED,
                    'applied_at'      => now(),
                ]);

                $training->increment('capacity');

                event(new TeacherAssigned($application));

                $assignedApplications[] = $application;
            }
        });

        foreach ($assignedApplications as $application) {
            $this->writeAudit($guruBesar, 'application.assigned', $application, [
                'courseID'  => $training->getKey(),
                'teacherID' => $application->teacherID,
            ]);
        }
    }

    /**
     * Sums LADAP hours earned by the teacher, scoped to the calendar year the
     * hours were earned in (i.e. the session's date), defaulting to the
     * current year. LADAP progress resets every 1 January.
     */
    public function totalLadapHours(Teacher $teacher, ?int $year = null): float
    {
        $year = $year ?? now()->year;

        $approvedCourseIds = Application::where('teacherID', $teacher->teacherID)
            ->where('status', 'approved')
            ->pluck('course_id');

        $total = 0.0;
        foreach ($approvedCourseIds as $courseId) {
            $sessionIds = CourseSession::where('courseID', $courseId)
                ->whereYear('sessionDate', $year)
                ->pluck('sessionID');
            $total += (float) Attendance::where('teacher_id', $teacher->teacherID)
                ->whereIn('session_id', $sessionIds)
                ->sum('hours_ladap');
        }

        return round($total, 2);
    }

    private function writeAudit(User $actor, string $action, Application $application, array $metadata = []): void
    {
        // audit_logs.target_id is a non-null bigint, but this entity is
        // identified by canonical string/composite keys. Do not invent a
        // lossy numeric identity that could point at another shared record.
        try {
            Log::warning('Canonical database audit row skipped for a string/composite target.', [
                'user_id'     => $actor->id,
                'action'      => $action,
                'target_type' => Application::class,
                'domain_id'   => [
                    'teacherID' => $application->teacherID,
                    'courseID'  => $application->courseID,
                ],
                'metadata'    => array_merge([
                    'teacherID' => $application->teacherID,
                    'courseID'  => $application->courseID,
                ], $metadata),
            ]);
        } catch (\Throwable) {
            // Audit incompatibility must never roll back the business action.
        }
    }
}
