<?php

namespace Tests\Unit;

use App\Support\PsmAssessmentOwnerContract;
use PHPUnit\Framework\TestCase;

class PsmAssessmentOwnerContractTest extends TestCase
{
    public function test_owner_enrollment_and_attendance_architectures_are_participant_specific(): void
    {
        $this->assertSame('enroll_teacher', PsmAssessmentOwnerContract::enrollmentTableFor('Teacher'));
        $this->assertSame('enroll_guru_baru', PsmAssessmentOwnerContract::enrollmentTableFor('Guru New'));
        $this->assertSame('enroll_guru_baru', PsmAssessmentOwnerContract::enrollmentTableFor('new-teacher'));
        $this->assertSame('enroll_outsider', PsmAssessmentOwnerContract::enrollmentTableFor('Public'));

        $this->assertSame('attendance', PsmAssessmentOwnerContract::attendanceTableFor('Teacher'));
        $this->assertSame('attendance_guru_baru', PsmAssessmentOwnerContract::attendanceTableFor('Guru Baru'));
        $this->assertSame('attendance_outsider', PsmAssessmentOwnerContract::attendanceTableFor('Outsider'));

        $this->assertNull(PsmAssessmentOwnerContract::enrollmentTableFor('Staff EDU'));
        $this->assertNull(PsmAssessmentOwnerContract::attendanceTableFor('Staff EDU'));
    }

    public function test_new_teacher_contract_uses_canonical_guru_new_identity(): void
    {
        $this->assertSame('gn_id', PsmAssessmentOwnerContract::enrollmentIdentityColumn('enroll_guru_baru'));
        $this->assertSame('gn_id', PsmAssessmentOwnerContract::attendanceIdentityColumn('attendance_guru_baru'));
        $this->assertTrue(PsmAssessmentOwnerContract::requiresNewTeacher('enroll_guru_baru'));
        $this->assertTrue(PsmAssessmentOwnerContract::requiresNewTeacher('attendance_guru_baru'));
        $this->assertNull(PsmAssessmentOwnerContract::enrollmentTableFor('legacy numeric participant'));
    }

    public function test_owner_status_rules_include_auto_enrolled_only_for_new_teachers(): void
    {
        $standardStatuses = ['pending', 'approved', 'rejected'];

        $this->assertSame($standardStatuses, PsmAssessmentOwnerContract::enrollmentStatuses('enroll_teacher'));
        $this->assertSame($standardStatuses, PsmAssessmentOwnerContract::enrollmentStatuses('enroll_outsider'));
        $this->assertSame(
            [...$standardStatuses, 'auto_enrolled'],
            PsmAssessmentOwnerContract::enrollmentStatuses('enroll_guru_baru')
        );

        $this->assertFalse(PsmAssessmentOwnerContract::grantsCourseAccess('enroll_teacher', 'pending'));
        $this->assertTrue(PsmAssessmentOwnerContract::grantsCourseAccess('enroll_teacher', 'approved'));
        $this->assertFalse(PsmAssessmentOwnerContract::grantsCourseAccess('enroll_teacher', 'rejected'));
        $this->assertFalse(PsmAssessmentOwnerContract::grantsCourseAccess('enroll_teacher', 'auto_enrolled'));
        $this->assertTrue(PsmAssessmentOwnerContract::grantsCourseAccess('enroll_guru_baru', 'auto_enrolled'));
    }

    public function test_legacy_generic_table_names_are_not_part_of_the_runtime_contract(): void
    {
        $this->assertNull(PsmAssessmentOwnerContract::enrollmentIdentityColumn('course_enrollment'));
        $this->assertNull(PsmAssessmentOwnerContract::attendanceIdentityColumn('training_attendance_record'));
        $this->assertSame('outsider', PsmAssessmentOwnerContract::OWNER_OUTSIDER_TABLE);
    }

    public function test_owner_uniqueness_and_unconstrained_reference_rules_are_explicit(): void
    {
        $this->assertSame(['teacherID', 'course_id'], PsmAssessmentOwnerContract::uniqueKey('enroll_teacher'));
        $this->assertSame(['gn_id', 'course_id'], PsmAssessmentOwnerContract::uniqueKey('enroll_guru_baru'));
        $this->assertSame(['outsider_id', 'course_id'], PsmAssessmentOwnerContract::uniqueKey('enroll_outsider'));
        $this->assertSame(['teacher_id', 'session_id'], PsmAssessmentOwnerContract::uniqueKey('attendance'));
        $this->assertSame(['gn_id', 'session_id'], PsmAssessmentOwnerContract::uniqueKey('attendance_guru_baru'));
        $this->assertSame(['outsider_id', 'session_id'], PsmAssessmentOwnerContract::uniqueKey('attendance_outsider'));
        $this->assertSame(
            ['enrollment_id', 'block_id'],
            PsmAssessmentOwnerContract::uniqueKey('training_content_interaction_log')
        );

        $this->assertSame(
            ['enrollment_id', 'graded_by_trainer_id'],
            PsmAssessmentOwnerContract::intentionallyUnconstrainedReferences('assessment_submission')
        );
        $this->assertSame(
            ['courseID', 'enrollment_id'],
            PsmAssessmentOwnerContract::intentionallyUnconstrainedReferences('training_access_log')
        );
    }

    public function test_owner_outsider_submission_and_tracking_fields_remain_available_without_identity_inference(): void
    {
        $this->assertSame([
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
        ], PsmAssessmentOwnerContract::outsiderColumns());

        $this->assertSame([
            'submitted_file_path',
            'original_file_name',
            'answers_json',
            'grade',
            'feedback',
            'graded_at',
        ], PsmAssessmentOwnerContract::assessmentSubmissionContentColumns());

        $this->assertSame([
            'first_opened_at',
            'last_accessed_at',
            'open_count',
            'total_seconds',
            'last_event_type',
        ], PsmAssessmentOwnerContract::learnerInteractionColumns());
    }
}
