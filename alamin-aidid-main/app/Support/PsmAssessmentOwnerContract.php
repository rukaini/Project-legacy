<?php

namespace App\Support;

final class PsmAssessmentOwnerContract
{
    public const OWNER_OUTSIDER_TABLE = 'outsider';

    /** @var array<string, string> */
    private const ENROLLMENT_TABLES = [
        'teacher' => 'enroll_teacher',
        'new_teacher' => 'enroll_guru_baru',
        'outsider' => 'enroll_outsider',
    ];

    /** @var array<string, string> */
    private const ATTENDANCE_TABLES = [
        'teacher' => 'attendance',
        'new_teacher' => 'attendance_guru_baru',
        'outsider' => 'attendance_outsider',
    ];

    /** @var array<string, string> */
    private const ENROLLMENT_IDENTITY_COLUMNS = [
        'enroll_teacher' => 'teacherID',
        'enroll_guru_baru' => 'gn_id',
        'enroll_outsider' => 'outsider_id',
    ];

    /** @var array<string, string> */
    private const ATTENDANCE_IDENTITY_COLUMNS = [
        'attendance' => 'teacher_id',
        'attendance_guru_baru' => 'gn_id',
        'attendance_outsider' => 'outsider_id',
    ];

    /** @var array<string, list<string>> */
    private const UNIQUE_KEYS = [
        'enroll_teacher' => ['teacherID', 'course_id'],
        'enroll_guru_baru' => ['gn_id', 'course_id'],
        'enroll_outsider' => ['outsider_id', 'course_id'],
        'attendance' => ['teacher_id', 'session_id'],
        'attendance_guru_baru' => ['gn_id', 'session_id'],
        'attendance_outsider' => ['outsider_id', 'session_id'],
        'training_content_interaction_log' => ['enrollment_id', 'block_id'],
    ];

    /** @var array<string, list<string>> */
    private const INTENTIONALLY_UNCONSTRAINED_REFERENCES = [
        'assessment_submission' => ['enrollment_id', 'graded_by_trainer_id'],
        'training_access_log' => ['courseID', 'enrollment_id'],
        'training_content_interaction_log' => ['courseID', 'module_id', 'block_id', 'enrollment_id'],
    ];

    public static function enrollmentTableFor(string $participantType): ?string
    {
        return self::ENROLLMENT_TABLES[self::normalizeParticipantType($participantType)] ?? null;
    }

    public static function attendanceTableFor(string $participantType): ?string
    {
        return self::ATTENDANCE_TABLES[self::normalizeParticipantType($participantType)] ?? null;
    }

    public static function enrollmentIdentityColumn(string $table): ?string
    {
        return self::ENROLLMENT_IDENTITY_COLUMNS[$table] ?? null;
    }

    public static function attendanceIdentityColumn(string $table): ?string
    {
        return self::ATTENDANCE_IDENTITY_COLUMNS[$table] ?? null;
    }

    /** @return list<string> */
    public static function uniqueKey(string $table): array
    {
        return self::UNIQUE_KEYS[$table] ?? [];
    }

    /** @return list<string> */
    public static function intentionallyUnconstrainedReferences(string $table): array
    {
        return self::INTENTIONALLY_UNCONSTRAINED_REFERENCES[$table] ?? [];
    }

    /** @return list<string> */
    public static function outsiderColumns(): array
    {
        return [
            'outsider_id',
            'name',
            'ICNumber',
            'phoneNumber',
            'gender',
            'organization',
            'contact_no',
            'user_id',
            'created_at',
            'updated_at',
        ];
    }

    /** @return list<string> */
    public static function assessmentSubmissionContentColumns(): array
    {
        return [
            'submitted_file_path',
            'original_file_name',
            'answers_json',
            'grade',
            'feedback',
            'graded_at',
        ];
    }

    /** @return list<string> */
    public static function learnerInteractionColumns(): array
    {
        return [
            'first_opened_at',
            'last_accessed_at',
            'open_count',
            'total_seconds',
            'last_event_type',
        ];
    }

    /** @return list<string> */
    public static function enrollmentStatuses(string $table): array
    {
        $statuses = ['pending', 'approved', 'rejected'];

        if ($table === 'enroll_guru_baru') {
            $statuses[] = 'auto_enrolled';
        }

        return $statuses;
    }

    public static function grantsCourseAccess(string $table, string $status): bool
    {
        $status = strtolower(trim($status));

        return $status === 'approved'
            || ($table === 'enroll_guru_baru' && $status === 'auto_enrolled');
    }

    public static function requiresNewTeacher(string $table): bool
    {
        return in_array($table, ['enroll_guru_baru', 'attendance_guru_baru'], true);
    }

    private static function normalizeParticipantType(string $participantType): string
    {
        return match (strtolower(trim(str_replace(['-', ' '], '_', $participantType)))) {
            'teacher' => 'teacher',
            'guru_new', 'guru_baru', 'new_teacher', 'applicant' => 'new_teacher',
            'outsider', 'public' => 'outsider',
            default => '',
        };
    }
}
