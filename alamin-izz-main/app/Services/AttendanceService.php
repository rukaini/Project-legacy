<?php

namespace App\Services;

use App\Models\GuruNew;
use App\Models\Application;
use App\Models\Attendance;
use App\Models\AttendanceGuruBaru;
use App\Models\AttendanceOutsider;
use App\Models\AuditLog;
use App\Models\CourseSession;
use App\Models\EnrollGuruBaru;
use App\Models\EnrollOutsider;
use App\Models\Outsider;
use App\Models\Teacher;
use App\Models\Trainer;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function markAttendance(User $user, int|string $sessionId, ?string $remarks = null): Attendance|AttendanceOutsider|AttendanceGuruBaru
    {
        $session = CourseSession::find($sessionId);
        abort_if(!$session, 422, 'Session not found.');

        $start = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time);
        $end   = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->end_time);
        $now   = Carbon::now();
        abort_if($now->lt($start) || $now->gt($end), 422, 'This session is not currently active.');

        if ($user->isGuruBaru()) {
            return $this->markGuruBaruAttendance($user, $session, $start, $end, $remarks);
        }

        if ($user->isOutsider()) {
            return $this->markOutsiderAttendance($user, $session, $start, $end, $remarks);
        }

        $teacher = Teacher::where('email', $user->email)->first();
        abort_unless($teacher, 403, 'No teacher profile found for your account.');

        $approved = Application::where('teacherID', $teacher->teacherID)
            ->where('course_id', $session->courseID)
            ->where('status', 'approved')
            ->exists();
        abort_unless($approved, 403, 'You are not approved for this training.');

        // One session per day per course — block same-day double scan
        $sameDaySessions = CourseSession::where('courseID', $session->courseID)
            ->whereDate('sessionDate', $session->session_date->format('Y-m-d'))
            ->pluck('sessionID');

        abort_if(
            Attendance::where('teacher_id', $teacher->teacherID)
                ->whereIn('session_id', $sameDaySessions)
                ->exists(),
            422,
            'You have already attended a session today for this training.'
        );

        $baseHours = $start->diffInMinutes($end) / 60;

        $multiplier = $this->resolveMultiplier($teacher, $session->courseID);

        $attendance = Attendance::create([
            'teacher_id'       => $teacher->teacherID,
            'session_id'       => $session->session_id,
            'timestamp_status' => round($baseHours, 2),
            'hours_ladap'      => round($baseHours * $multiplier, 2),
            'remarks'          => $remarks,
        ]);

        $this->writeAudit($user, 'attendance.marked_present', $attendance, [
            'session_id' => $session->session_id,
            'courseID'  => $session->courseID,
        ]);

        return $attendance;
    }

    private function markGuruBaruAttendance(User $user, CourseSession $session, Carbon $start, Carbon $end, ?string $remarks = null): AttendanceGuruBaru
    {
        $guruNew = GuruNew::where('email', $user->email)->first();
        abort_unless($guruNew, 403, 'No applicant profile found for your account.');

        $course = Training::find($session->courseID);

        // Series courses are auto-enrolled; single/multi_day require approved enrollment
        if (!$course || ! $course->isLms()) {
            $approved = EnrollGuruBaru::where('gn_id', $guruNew->gn_id)
                ->where('course_id', $session->courseID)
                ->where('status', 'approved')
                ->exists();
            abort_unless($approved, 403, 'You are not approved for this training.');
        }

        $sameDaySessions = CourseSession::where('courseID', $session->courseID)
            ->whereDate('sessionDate', $session->session_date->format('Y-m-d'))
            ->pluck('sessionID');

        abort_if(
            AttendanceGuruBaru::where('gn_id', $guruNew->gn_id)
                ->whereIn('session_id', $sameDaySessions)
                ->exists(),
            422,
            'You have already attended a session today for this training.'
        );

        $hours = round($start->diffInMinutes($end) / 60, 2);

        return AttendanceGuruBaru::create([
            'gn_id'            => $guruNew->gn_id,
            'session_id'       => $session->session_id,
            'timestamp_status' => $hours,
            'hours_ladap'      => $hours,
            'remarks'          => $remarks,
        ]);
    }

    private function markOutsiderAttendance(User $user, CourseSession $session, Carbon $start, Carbon $end, ?string $remarks = null): AttendanceOutsider
    {
        $outsider = Outsider::where('user_id', $user->id)->first();
        abort_unless($outsider, 403, 'No outsider profile found for your account.');

        $approved = EnrollOutsider::where('outsider_id', $outsider->outsider_id)
            ->where('course_id', $session->courseID)
            ->where('status', 'approved')
            ->exists();
        abort_unless($approved, 403, 'You are not approved for this training.');

        $sameDaySessions = CourseSession::where('courseID', $session->courseID)
            ->whereDate('sessionDate', $session->session_date->format('Y-m-d'))
            ->pluck('sessionID');

        abort_if(
            AttendanceOutsider::where('outsider_id', $outsider->outsider_id)
                ->whereIn('session_id', $sameDaySessions)
                ->exists(),
            422, 'You have already attended a session today for this training.'
        );

        $hours = round($start->diffInMinutes($end) / 60, 2);

        return AttendanceOutsider::create([
            'outsider_id'      => $outsider->outsider_id,
            'session_id'       => $session->session_id,
            'timestamp_status' => $hours,
            'remarks'          => $remarks,
        ]);
    }

    private function resolveMultiplier(Teacher $teacher, int|string $courseId): float
    {
        $course   = Training::find($courseId);
        $isAlAmin = $course && $course->isInHouse();

        // Check if teacher is a trainer assigned to any session of THIS specific course
        $isSessionTrainer = DB::table('session_trainer')
            ->join('course_session', 'session_trainer.sessionID', '=', 'course_session.sessionID')
            ->join('trainer', 'session_trainer.trainerID', '=', 'trainer.trainerID')
            ->where('course_session.courseID', $courseId)
            ->where('trainer.trainerIC', $teacher->ICNumber)
            ->exists();

        // Trainer assigned to this in-house course → 1.5×
        if ($isSessionTrainer && $isAlAmin) {
            return 1.5;
        }

        // In-house course, not a trainer for this course → 1.0×
        if ($isAlAmin) {
            return 1.0;
        }

        // External organiser: assigned by Guru Besar → 1.0×, self-applied → 0.5×
        $isAssigned = Application::where('teacherID', $teacher->teacherID)
            ->where('course_id', $courseId)
            ->where('enrollment_type', Application::ENROLLMENT_ASSIGNED)
            ->exists();

        return $isAssigned ? 1.0 : 0.5;
    }

    private function writeAudit(?User $actor, string $action, $target, array $metadata = []): void
    {
        AuditLog::create([
            'user_id'     => $actor?->id,
            'action'      => $action,
            'target_type' => get_class($target),
            'target_id'   => $target->attendance_id,
            'metadata'    => $metadata,
            'created_at'  => now(),
        ]);
    }
}
