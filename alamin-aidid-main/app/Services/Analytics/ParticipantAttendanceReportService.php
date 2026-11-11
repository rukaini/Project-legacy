<?php

namespace App\Services\Analytics;

use App\Support\CanonicalAttendance;
use App\Support\CanonicalEnrollment;
use App\Support\CanonicalTrainingQuery;
use App\Support\ParticipantTypeLabel;
use App\Support\SharedTrainingSchema;
use Illuminate\Support\Collection;

class ParticipantAttendanceReportService
{
    public function forSession(string $courseId, string $sessionId): Collection
    {
        if ($courseId === '' || $sessionId === '') {
            return collect();
        }

        $course = SharedTrainingSchema::course($courseId);
        $session = SharedTrainingSchema::session($sessionId);
        if (! $course || ! $session) {
            return collect();
        }

        $sessionInCourse = SharedTrainingSchema::constrainSessionsToCourse(
            SharedTrainingSchema::constrainSession(CanonicalTrainingQuery::sessions(), $session->session_id),
            $course
        )->exists();
        if (! $sessionInCourse) {
            return collect();
        }

        $sessionReferences = collect([$session->session_id]);
        $attendance = CanonicalAttendance::query()
            ->whereIn('session_id', $sessionReferences)
            ->get()
            ->keyBy(fn (object $record): string => $this->participantKey($record));

        $participants = CanonicalEnrollment::query('enrollment');
        SharedTrainingSchema::joinEnrollmentIdentities($participants);
        SharedTrainingSchema::constrainEnrollmentToCourse($participants, $course);

        return $participants
            ->select('enrollment.*')
            ->selectRaw(SharedTrainingSchema::enrollmentParticipantNameExpression().' AS participant_name')
            ->orderBy('participant_name')
            ->get()
            ->map(function ($participant) use ($attendance) {
                $record = $attendance->get($this->participantKey($participant));
                $participant->participant_type = ParticipantTypeLabel::display($participant->participant_type);
                $participant->attendance_record_id = $record->record_id ?? null;
                $participant->timestamp_status = $record->timestamp_status ?? null;
                $participant->hours_ladap = $record->hours_ladap ?? null;
                $participant->attendance_status = $record === null
                    ? 'Not recorded'
                    : 'Recorded';

                return $participant;
            })
            ->values();
    }

    private function participantKey(object $record): string
    {
        $id = $record->teacher_id ?? $record->applicant_id ?? $record->outsider_id ?? $record->staff_id ?? '';

        return (string) $record->participant_type.':'.(string) $id;
    }
}
