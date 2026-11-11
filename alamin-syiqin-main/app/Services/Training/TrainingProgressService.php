<?php

namespace App\Services\Training;

use Illuminate\Support\Facades\DB;

class TrainingProgressService
{
    public function getProgress(string $gnId): array
    {
        // Get all LMS courses for Guru New
        $courses = DB::table('course')
            ->whereRaw('LOWER(TRIM(courseType)) = ?', ['lms'])
            ->select('courseID', 'courseName')
            ->orderBy('courseName')
            ->get();

        $progress = [];

        foreach ($courses as $course) {

            // Total sessions for this course
            $totalSessions = DB::table('course_session')
                ->where('courseID', $course->courseID)
                ->count();

            // Count approved attendance for this GN
            $approvedSessions = DB::table('attendance_guru_baru as a')
                ->join(
                    'course_session as s',
                    's.sessionID',
                    '=',
                    'a.session_id'
                )
                ->where('a.gn_id', $gnId)
                ->where('s.courseID', $course->courseID)
                ->whereRaw(
                    'LOWER(TRIM(a.attendance_status)) = ?',
                    ['approved']
                )
                ->distinct()
                ->count('a.session_id');

            // Check whether feedback has been submitted
            $feedbackCompleted = DB::table('course_participant')
                ->where('gn_id', $gnId)
                ->where('courseID', $course->courseID)
                ->where('isFeedbackCompleted', 1)
                ->exists();

            // Training is completed only when all sessions
            // are approved AND feedback is completed
            $completed = $totalSessions > 0
                && $approvedSessions >= $totalSessions
                && $feedbackCompleted;

            $progress[] = [
                'course_id' => $course->courseID,
                'course_name' => $course->courseName,
                'total_sessions' => $totalSessions,
                'approved_sessions' => $approvedSessions,
                'feedback_completed' => $feedbackCompleted,
                'completed' => $completed,
            ];
        }

        return $progress;
    }
}
