<?php

namespace Tests\Feature;

use App\Services\Lms\TrainerLmsMaterialAuthoringService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrackingAnalyticsControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createTables();
        $this->seedAssignedEmptyCourse();
    }

    public function test_learner_tracking_renders_missing_data_without_writing_shared_records(): void
    {
        $before = $this->readOnlySnapshot();

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', 10))
            ->assertOk()
            ->assertSee('Learner Tracking')
            ->assertSee('No participants are enrolled in this training.')
            ->assertSee('Material tracking unavailable')
            ->assertDontSee('Course Progress')
            ->assertDontSee('demo-file');

        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_learner_tracking_full_page_uses_the_canonical_lms_workspace_shell(): void
    {
        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', 10))
            ->assertOk()
            ->assertSee('data-lms-workspace-header', false)
            ->assertSee('data-lms-workspace-header-styles', false)
            ->assertSee('Empty Tracking Course')
            ->assertSee('class="lw-icon"', false)
            ->assertSee('class="lw-pill mode"', false)
            ->assertDontSee('Professional Development')
            ->assertDontSee('Teachers')
            ->assertSee('class="lw-tabs" aria-label="LMS workspace"', false)
            ->assertSee('data-lms-submissions-pending', false)
            ->assertSee('lw-tab-badge', false);

        $html = $response->getContent();
        $this->assertSame(1, substr_count($html, 'data-lms-workspace-header>'));
        $this->assertMatchesRegularExpression(
            '~<a href="[^"]+/learner-tracking" class="active"\s+aria-current="page"\s*>\s*Learner Tracking~',
            $html
        );
        $this->assertStringContainsString('.lw-tabs a.active', $html);
    }

    public function test_analytics_renders_a_clear_empty_state_without_assignments(): void
    {
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 8])
            ->get(route('trainer.analytics', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('Training Analytics')
            ->assertSee('Review attendance and assessment results for your assigned training.')
            ->assertSee('No training assigned yet')
            ->assertSee('id="trainingSelector"', false)
            ->assertSee('disabled', false)
            ->assertDontSee('View summary');

        $this->assertOldAnalyticsLabelsAreAbsent($response);
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_simple_training_report_renders_session_filters_and_attendance_table(): void
    {
        $this->seedMultiSessionSimpleTraining();
        DB::table('guru_new')->insert([
            'gn_id' => 5,
            'gn_name' => 'Teacher Without Attendance',
            'email' => 'missing@example.test',
        ]);
        DB::table('enroll_guru_baru')->insert([
            'enrollment_id' => 115,
            'course_id' => '11',
            'gn_id' => 5,
        ]);
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('Training Analytics')
            ->assertSee('Simple Delivery')
            ->assertSee('Simple Training')
            ->assertSee('id="trainingSelector"', false)
            ->assertSee('id="sessionSelector"', false)
            ->assertSee('All sessions')
            ->assertSee('5 participants')
            ->assertSee('Attendance Record Coverage')
            ->assertSee('Session results')
            ->assertDontSee('Participant Attendance')
            ->assertSee('Opening Session')
            ->assertSee('Follow-up Session')
            ->assertSee('Record coverage')
            ->assertSee('Not recorded')
            ->assertDontSee('Feedback snapshot')
            ->assertDontSee('Unassigned Course')
            ->assertDontSee('View summary');

        $this->assertOldAnalyticsLabelsAreAbsent($response);
        $this->assertSame($before, $this->readOnlySnapshot());

        $selectedSession = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['course_id' => 11, 'session_id' => 21, 'section' => 'attendance']))
            ->assertOk()
            ->assertSee('Opening Session')
            ->assertSee('records captured')
            ->assertSee('Participant Attendance')
            ->assertSee('data-noun="participants"', false)
            ->assertSee('Teacher Without Attendance')
            ->assertSee('New Teacher')
            ->assertSee('Recorded')
            ->assertDontSee('Approved')
            ->assertDontSee('Pending')
            ->assertDontSee('Rejected')
            ->assertDontSee('owner status not interpreted')
            ->assertSee('Not recorded')
            ->assertDontSee('Session results');

        $this->assertSame('21', $selectedSession->viewData('selectedSessionId'));
        $this->assertSame(80, $selectedSession->viewData('attendanceSummary')['rate']);
        $this->assertSame(['Recorded' => 4, 'Not Recorded' => 1], $selectedSession->viewData('attendanceSummary')['counts']);
        $participantAttendance = $selectedSession->viewData('participantAttendance')->keyBy('enrollment_id');
        $this->assertCount(5, $participantAttendance);
        $this->assertSame('Recorded', $participantAttendance[111]->attendance_status);
        $this->assertSame('Recorded', $participantAttendance[112]->attendance_status);
        $this->assertSame('Recorded', $participantAttendance[113]->attendance_status);
        $this->assertSame('Recorded', $participantAttendance[114]->attendance_status);
        $this->assertSame('Not recorded', $participantAttendance[115]->attendance_status);

        $sorted = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['course_id' => 11, 'session_sort' => 'missing', 'section' => 'attendance']))
            ->assertOk()
            ->assertSee('Reset')
            ->assertSeeInOrder(['Opening Session', 'Follow-up Session']);

        $this->assertSame('missing', $sorted->viewData('sessionSort'));
        $this->assertSame(['23', '21'], $sorted->viewData('sessionResults')->pluck('session_id')->all());
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_simple_training_without_attendance_shows_no_rate_and_hides_single_session_filter(): void
    {
        $this->seedSingleSessionSimpleTraining();
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 12])
            ->get(route('trainer.analytics', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('No enrolled participant-session attendance slots are available for this selection')
            ->assertDontSee('Attendance Rate')
            ->assertDontSee('id="sessionSelector"', false);

        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_lms_report_calculates_quiz_percentages_and_submission_outcomes(): void
    {
        $this->seedLmsAssessmentOutcomes();
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['section' => 'assessments']))
            ->assertOk()
            ->assertSee('Training Analytics')
            ->assertSee('Quiz Performance')
            ->assertSee('class="ana-metric ana-quiz-metric"', false)
            ->assertSee('Knowledge Check')
            ->assertSee('85.0%')
            ->assertSee('90.0%')
            ->assertSee('80.0%')
            ->assertSee('id="assessmentSelector"', false)
            ->assertSee('Quiz comparison becomes available when at least two quizzes have graded attempts.')
            ->assertDontSee('id="quizComparisonChart"', false);

        $quizResults = $response->viewData('quizResults');
        $knowledgeCheck = $quizResults->firstWhere('assessment_id', 301);

        $this->assertSame(2, $knowledgeCheck->attempt_count);
        $this->assertSame(85.0, $knowledgeCheck->average_percent);
        $this->assertSame(90.0, $knowledgeCheck->highest_percent);
        $this->assertSame(80.0, $knowledgeCheck->lowest_percent);
        $this->assertSame([301], $quizResults->pluck('assessment_id')->all());
        $this->assertSame(['attempts' => 2, 'average_score' => 85.0], $response->viewData('quizTotals'));
        $this->assertSame(['submitted' => 4, 'reviewed' => 1, 'pending' => 3], $response->viewData('submissionTotals'));
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_lms_assessment_filters_focus_results_and_hide_the_other_type(): void
    {
        $this->seedLmsAssessmentOutcomes();
        $before = $this->readOnlySnapshot();

        $quizResponse = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['course_id' => 10, 'assessment_type' => 'quiz', 'assessment_id' => 301, 'section' => 'assessments']))
            ->assertOk()
            ->assertSee('Knowledge Check')
            ->assertDontSee('Submission outcomes')
            ->assertDontSee('awaiting your review')
            ->assertSee('Reset');

        $this->assertSame('quiz', $quizResponse->viewData('assessmentType'));
        $this->assertSame(301, $quizResponse->viewData('selectedAssessmentId'));
        $this->assertSame([301], $quizResponse->viewData('quizResults')->pluck('assessment_id')->all());

        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_learner_tracking_uses_real_course_sources_without_fabricating_progress_or_completion(): void
    {
        $this->seedLearnerTrackingReport();
        $beforeInteractions = DB::table('training_content_interaction_log')->get()->map(fn ($row) => (array) $row)->all();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', ['course_id' => 10, 'participant' => 701]))
            ->assertOk()
            ->assertSee('Tracked Learner')
            ->assertSee('Teacher Guide.pdf')
            ->assertSee('Quiz attempts')
            ->assertDontSee('Foreign Learner')
            ->assertDontSee('Foreign Material')
            ->assertDontSee('Course Progress')
            ->assertDontSee('Completed')
            ->assertDontSee('In Progress')
            ->assertDontSee('demo-');

        $this->assertSame(1, $response->viewData('overview')['metrics']['materials_accessed']);
        $this->assertSame(3, $response->viewData('overview')['metrics']['quiz_attempts']);
        $this->assertSame(600, $response->viewData('overview')['metrics']['tracked_seconds']);
        $this->assertSame('2026-08-20 12:00:00', $response->viewData('overview')['metrics']['last_activity']);
        $this->assertSame($beforeInteractions, DB::table('training_content_interaction_log')->get()->map(fn ($row) => (array) $row)->all());
    }

    public function test_engagement_uses_materials_order_and_server_side_pagination_with_material_context(): void
    {
        $this->seedLearnerTrackingReport();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'module',
                'module' => 802,
                'rows' => 10,
            ]))
            ->assertOk()
            ->assertSee('View all in module')
            ->assertSee('Teacher Guide.pdf')
            ->assertSee('Orientation Video')
            ->assertSee('Workshop Video')
            ->assertSee('Workshop Audio')
            ->assertDontSee('Inline Reference Image')
            ->assertSee('Accessed')
            ->assertSee('Not accessed')
            ->assertDontSee('Completed');

        $this->assertSame([802, 801, 803], $response->viewData('modules')->pluck('module_id')->all());
        $this->assertSame([901, 902, 907, 908], $response->viewData('trackableBlocks')->pluck('block_id')->all());
        $materialsVisibleIds = app(TrainerLmsMaterialAuthoringService::class)
            ->blocksForModules(collect([802, 801, 803]))
            ->filter(fn (object $block): bool => in_array($block->block_type, ['material', 'youtube'], true)
                || ($block->block_type === 'media'
                    && in_array(TrainerLmsMaterialAuthoringService::mediaKind($block->original_file_name), ['video', 'audio'], true)))
            ->pluck('block_id')
            ->sort()
            ->values()
            ->all();
        $this->assertSame(
            $materialsVisibleIds,
            $response->viewData('trackableBlocks')->pluck('block_id')->sort()->values()->all(),
            'Learner Tracking must use the same persisted visible block IDs that Materials renders.'
        );
        $this->assertLessThanOrEqual(10, $response->viewData('engagement')['rows']->count());
        $this->assertSame(4, $response->viewData('engagement')['rows']->total());
    }

    public function test_inline_image_media_is_not_trackable_but_video_audio_material_and_youtube_remain_eligible(): void
    {
        $this->seedLearnerTrackingReport();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'module',
                'module' => 801,
            ]))
            ->assertOk()
            ->assertSee('Workshop Video')
            ->assertSee('Workshop Audio')
            ->assertSee('Video')
            ->assertSee('Audio')
            ->assertDontSee('Inline Reference Image');

        $trackable = $response->viewData('trackableBlocks')->keyBy('block_id');
        $engagementTypes = $response->viewData('engagement')['rows']->getCollection()->keyBy('block_id');
        $this->assertFalse($trackable->has(903));
        $this->assertSame('video', TrainerLmsMaterialAuthoringService::mediaKind('workshop.MP4'));
        $this->assertSame('audio', TrainerLmsMaterialAuthoringService::mediaKind('workshop.ogg'));
        $this->assertSame('image', TrainerLmsMaterialAuthoringService::mediaKind('reference.png'));
        $this->assertTrue($trackable->has(901), 'Ordinary Material blocks remain trackable.');
        $this->assertTrue($trackable->has(902), 'YouTube blocks remain trackable.');
        $this->assertTrue($trackable->has(907), 'Supported video Media remains trackable.');
        $this->assertTrue($trackable->has(908), 'Supported audio Media remains trackable.');
        $this->assertSame('Video', $engagementTypes[907]->content_type_label);
        $this->assertSame('Audio', $engagementTypes[908]->content_type_label);

        $overview = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', ['course_id' => 10, 'participant' => 701]))
            ->assertOk();
        $this->assertSame(1, $overview->viewData('overview')['metrics']['materials_accessed']);
        $this->assertSame('2026-08-20 12:00:00', $overview->viewData('overview')['metrics']['last_activity']);
    }

    public function test_quiz_tracking_uses_real_quizzes_attempts_and_never_divides_zero_total_marks(): void
    {
        $this->seedLearnerTrackingReport();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 912,
            ]))
            ->assertOk()
            ->assertSee('Zero Mark Quiz')
            ->assertSee('Not scored')
            ->assertSee('View details')
            ->assertDontSee('Foreign Quiz')
            ->assertDontSee('Submission Task');

        $this->assertSame([912, 911], $response->viewData('quizzes')->pluck('assessment_id')->all());
        $attempts = $response->viewData('quizTracking')['attempts']->get(701);
        $this->assertCount(1, $attempts);
        $this->assertNull($attempts->first()->percentage);
        $this->assertSame(1, $response->viewData('quizTracking')['summary']['attempted']);
        $this->assertSame(1, $response->viewData('quizTracking')['summary']['not_attempted']);
    }

    public function test_quiz_participant_search_is_course_scoped_and_preserves_the_selected_quiz(): void
    {
        $this->seedLearnerTrackingReport();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'participant_search' => 'Quiet',
            ]))
            ->assertOk()
            ->assertSee('Quiet Learner')
            ->assertDontSee('Tracked Learner')
            ->assertDontSee('Foreign Learner');

        $this->assertSame('Quiet', $response->viewData('participantSearch'));
        $this->assertSame(911, $response->viewData('selectedQuizId'));
        $this->assertSame(1, $response->viewData('quizTracking')['rows']->total());

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'participant_search' => 'Nobody',
            ]))
            ->assertOk()
            ->assertSee('No quiz participants match this search.');
    }

    public function test_quiz_attempt_details_use_real_read_only_answers_and_keep_attempts_chronological(): void
    {
        $this->seedLearnerTrackingReport();
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'attempt' => 922,
            ]))
            ->assertOk()
            ->assertSee('Quiz attempt details')
            ->assertSee('Attempt 1')
            ->assertSee('Attempt 2')
            ->assertSee('Which colour is primary?')
            ->assertSee('B. Lev Vygotsky')
            ->assertSee('Correct')
            ->assertSee('Participant answer')
            ->assertSee('lt-answer-correctness is-correct', false)
            ->assertSee('is-correct-selected is-participant-selected', false)
            ->assertDontSee('data-answer-option-state="correct-answer"', false)
            ->assertDontSee('Your answer')
            ->assertDontSee('Correct answer')
            ->assertSee('Checkbox')
            ->assertSee('Short Answer')
            ->assertSee('Essay')
            ->assertSee('Manual review')
            ->assertSee('Use evidence and a clear conclusion.');

        $detail = $response->viewData('quizTracking')['detail'];
        $this->assertSame(2, $detail['attempt_number']);
        $this->assertSame([921, 922], $detail['attempts']->pluck('submission_id')->all());
        $this->assertCount(5, $detail['questions']);
        $this->assertSame('B. Lev Vygotsky', $detail['questions']->first()->learner_answer);
        $this->assertSame(['correct', 'correct', 'correct', 'manual', 'manual'], $detail['questions']->pluck('answer_status')->all());
        $this->assertSame('B. Lev Vygotsky', $detail['questions']->first()->display_correct_answer);
        $mcqOptions = collect($detail['questions']->first()->answer_options)->keyBy('key');
        $this->assertTrue($mcqOptions['B']->selected);
        $this->assertTrue($mcqOptions['B']->correct);
        $this->assertSame('correct-selected', $mcqOptions['B']->state);
        $checkboxOptions = collect($detail['questions'][2]->answer_options)->keyBy('key');
        $this->assertSame(['A', 'C'], $checkboxOptions->where('selected', true)->keys()->sort()->values()->all());
        $this->assertSame(5, substr_count($response->getContent(), 'data-question-jump'));
        $this->assertStringContainsString('id="quiz-question-951"', $response->getContent());
        $this->assertSame($before, $this->readOnlySnapshot());

        $earlier = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'attempt' => 921,
            ]))
            ->assertOk()
            ->assertSee('Incorrect')
            ->assertSee('Participant answer')
            ->assertSee('lt-answer-correctness is-incorrect', false)
            ->assertSee('is-wrong-selected is-participant-selected', false)
            ->assertSee('is-correct-selected is-participant-selected', false)
            ->assertSee('data-answer-option-state="correct-answer"', false)
            ->assertSee('Correct answer')
            ->assertDontSee('Your answer')
            ->assertSee('Unanswered');
        $earlierQuestions = $earlier->viewData('quizTracking')['detail']['questions'];
        $this->assertSame(['incorrect', 'incorrect', 'incorrect', 'manual', 'unanswered'], $earlierQuestions->pluck('answer_status')->all());
        $earlierMcq = collect($earlierQuestions->first()->answer_options)->keyBy('key');
        $this->assertSame('wrong-selected', $earlierMcq['A']->state);
        $this->assertSame('correct-answer', $earlierMcq['B']->state);
        $earlierTrueFalse = collect($earlierQuestions[1]->answer_options)->keyBy('display');
        $this->assertSame('wrong-selected', $earlierTrueFalse['False']->state);
        $this->assertSame('correct-answer', $earlierTrueFalse['True']->state);
        $earlierCheckbox = collect($earlierQuestions[2]->answer_options)->keyBy('key');
        $this->assertSame('correct-selected', $earlierCheckbox['A']->state);
        $this->assertSame('wrong-selected', $earlierCheckbox['B']->state);
        $this->assertSame('correct-answer', $earlierCheckbox['C']->state);

        $foreign = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'attempt' => 925,
            ]))
            ->assertOk();
        $this->assertNull($foreign->viewData('quizTracking')['detail']);
    }

    public function test_quiz_attempt_details_keep_malformed_legacy_answers_neutral(): void
    {
        $this->seedLearnerTrackingReport();
        DB::table('assessment_submission')->insert([
            'submission_id' => 926,
            'assessment_id' => 911,
            'enrollment_id' => 702,
            'grade' => 20,
            'submitted_at' => '2026-08-20 15:00:00',
            'status' => 'Submitted',
            'answers_json' => '{"q1":{"legacy_value":"B. Lev Vygotsky"},"q4":"Some response"}',
        ]);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'attempt' => 926,
            ]))
            ->assertOk()
            ->assertSee('Participant answer unavailable')
            ->assertSee('The saved response for this question cannot be read reliably.')
            ->assertSee('Unanswered')
            ->assertDontSee('Not enough recorded data')
            ->assertDontSee('Cannot determine')
            ->assertDontSee('Your answer');

        $questions = $response->viewData('quizTracking')['detail']['questions'];
        $this->assertSame('recorded_unavailable', $questions->first()->answer_status);
        $malformedOptions = collect($questions->first()->answer_options)->keyBy('key');
        $this->assertSame('correct-reference', $malformedOptions['B']->state);
        $this->assertFalse($malformedOptions->contains(fn (object $option): bool => $option->selected));
        $this->assertSame('unanswered', $questions[1]->answer_status);
        $unansweredTrueFalse = collect($questions[1]->answer_options)->keyBy('display');
        $this->assertSame('correct-answer', $unansweredTrueFalse['True']->state);
        $this->assertFalse($unansweredTrueFalse->contains(fn (object $option): bool => $option->selected));
        $this->assertSame('manual', $questions[3]->answer_status, 'Recorded Short Answer remains manual review.');
        $this->assertSame('unanswered', $questions[4]->answer_status);
    }

    public function test_quiz_attempt_details_distinguish_missing_canonical_answer_from_malformed_participant_data(): void
    {
        $this->seedLearnerTrackingReport();
        DB::table('assessment_question')->where('question_id', 951)->update(['correct_answer' => null]);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'attempt' => 922,
            ]))
            ->assertOk()
            ->assertSee('Answer cannot be evaluated')
            ->assertSee('The canonical correct answer is missing or cannot be matched safely to the current options.')
            ->assertDontSee('Not enough recorded data')
            ->assertDontSee('Your answer');

        $question = $response->viewData('quizTracking')['detail']['questions']->first();
        $this->assertSame('cannot_evaluate', $question->answer_status);
        $options = collect($question->answer_options)->keyBy('key');
        $this->assertSame('participant-selected-neutral', $options['B']->state);
        $this->assertFalse($options->contains(fn (object $option): bool => $option->correct));
    }

    public function test_quiz_attempt_details_explain_when_answer_level_contract_is_unavailable(): void
    {
        $this->seedLearnerTrackingReport();
        Schema::drop('assessment_question');

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'attempt' => 922,
            ]))
            ->assertOk()
            ->assertSee('Answer-level responses are not available from the connected assessment records.');
    }

    public function test_attempt_without_usable_answer_data_renders_one_neutral_attempt_level_state(): void
    {
        $this->seedLearnerTrackingReport();
        $this->seedObjectiveReviewQuiz();
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 912,
                'attempt' => 923,
            ]))
            ->assertOk()
            ->assertSee('Recorded result')
            ->assertSee('Detailed answers unavailable')
            ->assertSee('The recorded attempt does not contain enough participant-answer data for a question-by-question review.')
            ->assertDontSee('data-question-jump', false)
            ->assertDontSee('Correct answer');

        $detail = $response->viewData('quizTracking')['detail'];
        $this->assertFalse($detail['detailed_answers_available']);
        $this->assertNull($detail['answer_review']);
        $this->assertSame($before, $this->readOnlySnapshot());

        DB::table('assessment_submission')->where('submission_id', 923)->update([
            'answers_json' => '{"legacy_metadata":"response omitted"}',
        ]);
        $malformedBefore = $this->readOnlySnapshot();
        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 912,
                'attempt' => 923,
            ]))
            ->assertOk()
            ->assertSee('Detailed answers unavailable')
            ->assertDontSee('data-question-jump', false)
            ->assertDontSee('Correct answer');
        $this->assertSame($malformedBefore, $this->readOnlySnapshot());
    }

    public function test_objective_answer_review_total_is_read_only_and_reports_grade_mismatches(): void
    {
        $this->seedLearnerTrackingReport();
        $this->seedObjectiveReviewQuiz();
        DB::table('assessment_submission')->insert([
            [
                'submission_id' => 927,
                'assessment_id' => 912,
                'enrollment_id' => 702,
                'grade' => 2,
                'submitted_at' => '2026-08-20 16:00:00',
                'status' => 'Submitted',
                'answers_json' => '{"q1":"B. Kuala Lumpur"}',
            ],
            [
                'submission_id' => 928,
                'assessment_id' => 912,
                'enrollment_id' => 702,
                'grade' => 5,
                'submitted_at' => '2026-08-20 17:00:00',
                'status' => 'Submitted',
                'answers_json' => '{"q1":"B. Kuala Lumpur"}',
            ],
        ]);
        $before = $this->readOnlySnapshot();

        $matching = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 912,
                'attempt' => 927,
            ]))
            ->assertOk()
            ->assertSee('Unanswered')
            ->assertSee('Correct answer')
            ->assertDontSee('The recorded result differs from the score calculated');
        $matchingDetail = $matching->viewData('quizTracking')['detail'];
        $this->assertTrue($matchingDetail['detailed_answers_available']);
        $this->assertSame(['correct', 'unanswered'], $matchingDetail['questions']->pluck('answer_status')->all());
        $this->assertSame(2.0, $matchingDetail['answer_review']['earned']);
        $this->assertSame(5.0, $matchingDetail['answer_review']['maximum']);
        $this->assertTrue($matchingDetail['answer_review']['matches_recorded']);

        $mismatch = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 912,
                'attempt' => 928,
            ]))
            ->assertOk()
            ->assertSee('Recorded result')
            ->assertSee('Answer review')
            ->assertSee('The recorded result differs from the score calculated from the currently reviewable answers.');
        $mismatchReview = $mismatch->viewData('quizTracking')['detail']['answer_review'];
        $this->assertSame(2.0, $mismatchReview['earned']);
        $this->assertSame(5.0, $mismatchReview['maximum']);
        $this->assertFalse($mismatchReview['matches_recorded']);
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_module_without_trackable_content_has_an_explicit_empty_state(): void
    {
        $this->seedLearnerTrackingReport();

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'module',
                'module' => 803,
            ]))
            ->assertOk()
            ->assertSee('Text Only Module')
            ->assertSee('No trackable materials in this module')
            ->assertDontSee('Static introduction text');
    }

    public function test_tracking_hierarchy_excludes_orphan_and_hidden_material_rows(): void
    {
        $this->seedLearnerTrackingReport();
        DB::table('learning_material')->insert([
            'material_id' => 823,
            'original_file_name' => 'Orphan Upload.pdf',
        ]);
        DB::table('training_content_block')->insert([
            'block_id' => 906,
            'module_id' => 802,
            'material_id' => 824,
            'block_type' => 'media',
            'block_title' => 'Hidden Legacy Media',
            'block_order' => 4,
            'is_visible' => false,
        ]);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'module',
                'module' => 802,
                'search' => 'Orphan Upload',
            ]))
            ->assertOk()
            ->assertSee('No modules or materials found')
            ->assertSee('First Module')
            ->assertDontSee('Orphan Upload.pdf')
            ->assertDontSee('Hidden Legacy Media');

        $this->assertSame([901, 902], $response->viewData('trackableBlocks')->pluck('block_id')->all());
    }

    public function test_tracking_fragment_is_json_and_foreign_course_remains_inaccessible(): void
    {
        $this->seedLearnerTrackingReport();

        $fragment = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', ['course_id' => 10, 'tab' => 'quiz', 'fragment' => 1]))
            ->assertOk()
            ->assertJsonStructure(['html', 'title'])
            ->assertJsonPath('title', 'Learner Tracking | Al Amin Edu Oasis');

        $fragmentHtml = $fragment->json('html');
        $this->assertStringContainsString('data-tracking-workspace', $fragmentHtml);
        $this->assertStringNotContainsString('data-lms-workspace-header', $fragmentHtml);
        $this->assertStringNotContainsString('aria-label="LMS workspace"', $fragmentHtml);
        $this->assertStringNotContainsString('data-lms-workspace-header-styles', $fragmentHtml);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', 99))
            ->assertRedirect(route('trainer.courses'));
    }

    public function test_tab_and_engagement_pivot_fragments_include_every_dependent_region(): void
    {
        $this->seedLearnerTrackingReport();

        $quiz = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'fragment' => 1,
            ]))
            ->assertOk();
        $this->assertStringContainsString('data-tracking-region="tabs"', $quiz->json('html'));
        $this->assertStringContainsString('data-tracking-region="navigator"', $quiz->json('html'));
        $this->assertStringContainsString('data-tracking-region="main"', $quiz->json('html'));
        $this->assertStringContainsString('Quizzes', $quiz->json('html'));

        $byModule = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'module',
                'fragment' => 1,
            ]))
            ->assertOk();
        $this->assertStringContainsString('Modules &amp; materials', $byModule->json('html'));
        $this->assertStringContainsString('By module', $byModule->json('html'));
        $this->assertStringContainsString('data-tracking-region="navigator"', $byModule->json('html'));
        $this->assertStringContainsString('data-tracking-region="main"', $byModule->json('html'));
    }

    public function test_navigator_search_empty_states_use_the_shared_icon_title_and_guidance_pattern(): void
    {
        $this->seedLearnerTrackingReport();

        foreach ([
            ['tab' => 'overview', 'search' => 'Nobody Here', 'title' => 'No participants found'],
            ['tab' => 'engagement', 'pivot' => 'module', 'search' => 'Nothing Here', 'title' => 'No modules or materials found'],
            ['tab' => 'quiz', 'search' => 'Unknown Quiz', 'title' => 'No quizzes found'],
        ] as $query) {
            $title = $query['title'];
            unset($query['title']);
            $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
                ->get(route('trainer.courses.learner-tracking', array_merge([
                    'course_id' => 10,
                    'fragment' => 1,
                ], $query)))
                ->assertOk();
            $this->assertStringContainsString('lt-nav-empty', $response->json('html'));
            $this->assertStringContainsString('bi-search', $response->json('html'));
            $this->assertStringContainsString($title, $response->json('html'));
        }
    }

    public function test_missing_optional_quiz_attempt_source_degrades_without_fabricating_results(): void
    {
        Schema::drop('assessment_submission');

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'fragment' => 1,
            ]))
            ->assertOk();

        $this->assertStringContainsString('Quiz attempts are not available', $response->json('html'));
        $this->assertStringNotContainsString('Not attempted', $response->json('html'));
    }

    public function test_navigators_use_bounded_server_windows_instead_of_loading_every_record(): void
    {
        $applicants = [];
        $enrollments = [];
        $modules = [];
        for ($index = 1; $index <= 105; $index++) {
            $applicantId = 2000 + $index;
            $applicants[] = [
                'gn_id' => $applicantId,
                'gn_name' => 'Applicant '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'email' => null,
            ];
            $enrollments[] = [
                'enrollment_id' => $applicantId,
                'course_id' => '10',
                'gn_id' => $applicantId,
            ];
            $modules[] = [
                'module_id' => 3000 + $index,
                'course_id' => 10,
                'module_title' => 'Module '.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'module_order' => $index,
            ];
        }
        DB::table('guru_new')->insert($applicants);
        DB::table('enroll_guru_baru')->insert($enrollments);
        DB::table('training_module')->insert($modules);

        $participants = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', 10))
            ->assertOk();
        $this->assertCount(100, $participants->viewData('participants'));

        $modulesResponse = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'module',
                'module' => 3105,
            ]))
            ->assertOk();
        $this->assertCount(101, $modulesResponse->viewData('modules'));
        $this->assertTrue($modulesResponse->viewData('modules')->contains('module_id', 3105));
        $this->assertSame(3105, $modulesResponse->viewData('selectedModuleId'));
    }

    public function test_tracking_search_period_and_out_of_range_pages_preserve_authoritative_state(): void
    {
        $this->seedLearnerTrackingReport();

        $search = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', ['course_id' => 10, 'search' => 'no such learner']))
            ->assertOk()
            ->assertSee('No participants found');
        $this->assertTrue($search->viewData('participants')->isEmpty());

        $period = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'quiz',
                'quiz' => 911,
                'period' => 'custom',
                'from' => '2026-08-20',
                'until' => '2026-08-20',
            ]))
            ->assertOk();
        $this->assertTrue($period->viewData('period')['valid']);
        $this->assertSame(2, $period->viewData('quizTracking')['summary']['total_attempts']);

        $normalized = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', [
                'course_id' => 10,
                'tab' => 'engagement',
                'pivot' => 'participant',
                'participant' => 701,
                'engagement_page' => 999,
                'rows' => 10,
            ]))
            ->assertOk();
        $this->assertSame(1, $normalized->viewData('engagement')['rows']->currentPage());
        $this->assertSame(4, $normalized->viewData('engagement')['rows']->total());
    }

    public function test_quiz_navigator_reuses_canonical_availability_states(): void
    {
        CarbonImmutable::setTestNow('2026-08-21 12:00:00');
        DB::table('assessment')->insert([
            ['assessment_id' => 931, 'course_id' => 10, 'title' => 'Always Quiz', 'type' => 'quiz', 'total_marks' => 1, 'available_from' => null, 'available_until' => null],
            ['assessment_id' => 932, 'course_id' => 10, 'title' => 'Scheduled Quiz', 'type' => 'quiz', 'total_marks' => 1, 'available_from' => '2026-08-22 12:00:00', 'available_until' => null],
            ['assessment_id' => 933, 'course_id' => 10, 'title' => 'Open Quiz', 'type' => 'quiz', 'total_marks' => 1, 'available_from' => '2026-08-20 12:00:00', 'available_until' => '2026-08-21 12:00:00'],
            ['assessment_id' => 934, 'course_id' => 10, 'title' => 'Closed Quiz', 'type' => 'quiz', 'total_marks' => 1, 'available_from' => null, 'available_until' => '2026-08-21 11:59:59'],
        ]);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.courses.learner-tracking', ['course_id' => 10, 'tab' => 'quiz']))
            ->assertOk()
            ->assertSee('Always available')
            ->assertSee('Scheduled')
            ->assertSee('Open')
            ->assertSee('Closed');

        $states = $response->viewData('quizzes')->keyBy('assessment_id')->map(fn ($quiz) => $quiz->availability['key']);
        $this->assertSame('always', $states[931]);
        $this->assertSame('scheduled', $states[932]);
        $this->assertSame('open', $states[933]);
        $this->assertSame('closed', $states[934]);
        CarbonImmutable::setTestNow();
    }

    public function test_trainer_session_participants_remain_scoped_to_assigned_training_and_sessions(): void
    {
        $this->seedMultiSessionSimpleTraining();
        DB::table('guru_new')->insert([
            'gn_id' => 99,
            'gn_name' => 'Private Unassigned Participant',
            'email' => 'private@example.test',
        ]);
        DB::table('enroll_guru_baru')->insert([
            'enrollment_id' => 199,
            'course_id' => '12',
            'gn_id' => 99,
        ]);
        $before = $this->readOnlySnapshot();

        $unassignedSession = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['course_id' => 11, 'session_id' => 22, 'section' => 'attendance']))
            ->assertOk()
            ->assertSee('Session results')
            ->assertDontSee('Participant Attendance')
            ->assertDontSee('Private Unassigned Participant');

        $this->assertSame('', $unassignedSession->viewData('selectedSessionId'));
        $this->assertTrue($unassignedSession->viewData('participantAttendance')->isEmpty());

        $unassignedTraining = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['course_id' => 12, 'session_id' => 22, 'section' => 'attendance']))
            ->assertOk()
            ->assertDontSee('Private Unassigned Participant');

        $this->assertSame(11, (int) $unassignedTraining->viewData('selectedTraining')->course_id);
        $this->assertTrue($unassignedTraining->viewData('participantAttendance')->isEmpty());
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_participant_type_comes_from_the_canonical_enrollment_source(): void
    {
        $this->seedMultiSessionSimpleTraining();
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', [
                'course_id' => 11,
                'session_id' => 21,
                'section' => 'attendance',
            ]))
            ->assertOk();

        $participants = $response->viewData('participantAttendance')->keyBy('enrollment_id');
        $this->assertSame('New Teacher', $participants[113]->participant_type);
        $this->assertSame('Guru New One', $participants[113]->participant_name);
        $this->assertSame('New Teacher', $participants[114]->participant_type);
        $this->assertSame('Public Participant', $participants[114]->participant_name);
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_selected_quiz_exposes_only_graded_attempts_with_access_boundaries(): void
    {
        $this->seedLmsAssessmentOutcomes();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['course_id' => 10, 'assessment_id' => 301, 'section' => 'assessments']))
            ->assertOk()
            ->assertSee('Student')
            ->assertSee('class="ana-metric ana-quiz-metric"', false)
            ->assertSee('16 / 20')
            ->assertDontSee('Practice Reflection');

        $attempts = $response->viewData('quizAttempts');
        $this->assertCount(2, $attempts);
        $this->assertSame([90.0, 80.0], $attempts->pluck('percentage')->all());
        $this->assertSame([1, 1], $attempts->pluck('attempt_number')->all());

        $unassigned = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['course_id' => 12, 'assessment_id' => 301, 'section' => 'assessments']))
            ->assertOk();
        $this->assertSame(10, (int) $unassigned->viewData('selectedTraining')->course_id);

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['course_id' => 10, 'assessment_id' => 401, 'section' => 'assessments']))
            ->assertOk()
            ->assertDontSee('Student');
    }

    public function test_selected_quiz_numbers_each_learners_attempts_chronologically_before_score_sorting(): void
    {
        $this->seedLmsAssessmentOutcomes();
        DB::table('assessment_submission')->insert([
            [
                'submission_id' => 505,
                'assessment_id' => 301,
                'enrollment_id' => 201,
                'grade' => 10,
                'submitted_at' => '2026-08-10 09:00:00',
                'status' => 'Submitted',
            ],
            [
                'submission_id' => 506,
                'assessment_id' => 301,
                'enrollment_id' => 201,
                'grade' => 20,
                'submitted_at' => '2026-08-10 12:00:00',
                'status' => 'Submitted',
            ],
        ]);

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', [
                'course_id' => 10,
                'assessment_id' => 301,
                'section' => 'assessments',
            ]))
            ->assertOk();

        $attempts = $response->viewData('quizAttempts');
        $this->assertSame([506, 502, 501, 505], $attempts->pluck('submission_id')->all());
        $this->assertSame([100.0, 90.0, 80.0, 50.0], $attempts->pluck('percentage')->all());
        $this->assertSame([3, 1, 2, 1], $attempts->pluck('attempt_number')->all());
    }

    public function test_pending_review_action_disappears_when_all_submissions_are_reviewed(): void
    {
        $this->seedLmsAssessmentOutcomes();

        DB::table('assessment_submission')
            ->whereIn('assessment_id', [401, 402])
            ->update(['status' => 'Reviewed']);

        $reviewedSnapshot = $this->readOnlySnapshot();

        $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['assessment_type' => 'submission', 'section' => 'assessments']))
            ->assertOk()
            ->assertDontSee('Submission Review');

        $this->assertSame($reviewedSnapshot, $this->readOnlySnapshot());
    }

    public function test_lms_report_hides_unavailable_assessment_sections_and_uses_compact_empty_states(): void
    {
        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('No enrolled participant-session attendance slots are available for this selection')
            ->assertDontSee('Feedback snapshot')
            ->assertDontSee('Submission Review')
            ->assertDontSee('Quiz performance')
            ->assertDontSee('Action required')
            ->assertDontSee('Recent comments');

        $this->assertOldAnalyticsLabelsAreAbsent($response);
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    public function test_feedback_is_absent_from_analytics(): void
    {
        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics'))
            ->assertOk();
        $response->assertDontSee('Feedback snapshot');
        $response->assertDontSee('View full feedback');
        $this->assertArrayNotHasKey('feedbackSummary', $response->viewData());

    }

    public function test_section_and_custom_date_filters_normalize_safely(): void
    {
        $this->seedMultiSessionSimpleTraining();
        $custom = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['section' => 'attendance', 'report_date' => 'custom', 'date_from' => '2026-08-12', 'date_to' => '2026-08-12']))
            ->assertOk();
        $this->assertSame('attendance', $custom->viewData('section'));
        $this->assertSame('custom', $custom->viewData('reportDate'));
        $this->assertCount(1, $custom->viewData('sessionResults'));

        $invalid = $this->withSession(['user_role' => 'trainer', 'user_id' => 9])
            ->get(route('trainer.analytics', ['section' => 'invalid', 'report_date' => 'custom', 'date_from' => '2026-08-20', 'date_to' => '2026-08-10']))
            ->assertOk();
        $this->assertSame('overview', $invalid->viewData('section'));
        $this->assertSame('all', $invalid->viewData('reportDate'));
    }

    public function test_lms_session_filter_scopes_attendance_without_scoping_assessments(): void
    {
        $this->seedLmsAssessmentOutcomes();
        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['section' => 'attendance', 'session_id' => 20]))
            ->assertOk();
        $this->assertSame('', $response->viewData('selectedSessionId'));
        $this->assertSame(2, $response->viewData('quizTotals')['attempts']);
    }

    public function test_training_filter_preserves_valid_selection_and_rejects_unassigned_selection(): void
    {
        $this->seedAdditionalAssignedTrainingForTrainerSeven();

        DB::table('course')->insert([
            'courseID' => '99',
            'courseName' => 'Private Unassigned Training',
            'courseCategory' => 'Restricted',
            'targetAudience' => 'Staff',
            'status' => 'Active',
            'courseType' => 'simple',
        ]);

        $before = $this->readOnlySnapshot();

        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['course_id' => 11]))
            ->assertOk()
            ->assertSee('id="trainingSelector"', false)
            ->assertSee('Empty Tracking Course')
            ->assertSee('Second Assigned Training')
            ->assertSee('<h2 id="selected-training-name">Second Assigned Training</h2>', false)
            ->assertDontSee('Private Unassigned Training');

        $this->assertSame(11, (int) $response->viewData('selectedTraining')->course_id);

        $fallback = $this->withSession(['user_role' => 'trainer', 'user_id' => 7])
            ->get(route('trainer.analytics', ['course_id' => 99]))
            ->assertOk()
            ->assertSee('Empty Tracking Course')
            ->assertDontSee('Private Unassigned Training');

        $this->assertSame(10, (int) $fallback->viewData('selectedTraining')->course_id);
        $this->assertSame($before, $this->readOnlySnapshot());
    }

    private function assertOldAnalyticsLabelsAreAbsent($response): void
    {
        foreach ([
            'My Training Summary',
            'View summary',
            'Assigned Training',
            'Material Opens',
            'Trainer Action Insights',
            'Attendance Analysis',
            'Training Analytics Summary',
            'RSVP',
            'Will Come',
            'Not Coming',
            'No Response',
        ] as $removedLabel) {
            $response->assertDontSee($removedLabel);
        }

        $response->assertDontSee('chart.js', false);
    }

    private function createTables(): void
    {
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->string('targetAudience')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->nullable();
        });

        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->string('sessionName')->nullable();
            $table->date('sessionDate')->nullable();
        });

        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });

        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->integer('enrollment_id')->primary();
            $table->string('gn_id', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });

        Schema::create('teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20)->primary();
            $table->string('teacherName')->nullable();
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
        });

        Schema::create('guru_new', function (Blueprint $table): void {
            $table->integer('gn_id')->primary();
            $table->string('gn_name')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('outsider', function (Blueprint $table): void {
            $table->integer('outsider_id')->primary();
            $table->string('name')->nullable();
            $table->string('gender')->nullable();
        });

        Schema::create('training_module', function (Blueprint $table): void {
            $table->integer('module_id')->primary();
            $table->string('course_id', 20);
            $table->string('module_title')->nullable();
            $table->integer('module_order')->nullable();
        });

        Schema::create('learning_material', function (Blueprint $table): void {
            $table->integer('material_id')->primary();
            $table->string('original_file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('material_type')->nullable();
            $table->string('ai_generated_title')->nullable();
            $table->text('ai_synopsis')->nullable();
        });

        Schema::create('training_content_block', function (Blueprint $table): void {
            $table->integer('block_id')->primary();
            $table->integer('module_id');
            $table->integer('material_id')->nullable();
            $table->integer('assessment_id')->nullable();
            $table->string('block_type')->nullable();
            $table->string('block_title')->nullable();
            $table->text('block_content')->nullable();
            $table->text('external_url')->nullable();
            $table->integer('block_order')->nullable();
            $table->boolean('is_visible')->nullable();
        });

        Schema::create('assessment', function (Blueprint $table): void {
            $table->integer('assessment_id')->primary();
            $table->string('course_id', 20);
            $table->string('title')->nullable();
            $table->string('type')->nullable();
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->string('accepted_format')->nullable();
        });

        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->integer('submission_id')->primary();
            $table->integer('assessment_id');
            $table->integer('enrollment_id');
            $table->decimal('grade', 8, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status')->nullable();
            $table->text('answers_json')->nullable();
        });

        Schema::create('assessment_question', function (Blueprint $table): void {
            $table->integer('question_id')->primary();
            $table->integer('assessment_id');
            $table->text('question_text');
            $table->string('question_image_path')->nullable();
            $table->integer('question_order');
            $table->string('question_type');
            $table->text('options_json')->nullable();
            $table->text('correct_answer')->nullable();
            $table->decimal('marks', 8, 2)->nullable();
            $table->text('grading_rubric')->nullable();
        });

        Schema::create('attendance_guru_baru', function (Blueprint $table): void {
            $table->integer('attendGuruBaru_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->string('gn_id', 20);
            $table->string('session_id', 20);
            $table->timestamps();
        });
        Schema::create('attendance', function (Blueprint $table): void {
            $table->integer('attendance_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->decimal('hours_ladap', 5, 2)->default(0);
            $table->string('teacher_id', 20);
            $table->string('session_id', 20);
            $table->timestamps();
        });
        Schema::create('attendance_outsider', function (Blueprint $table): void {
            $table->integer('attendOutsider_id')->primary();
            $table->string('attendance_status')->nullable();
            $table->decimal('timestamp_status', 5, 2)->default(0);
            $table->integer('outsider_id');
            $table->string('session_id', 20);
            $table->timestamps();
        });

    }

    private function seedAssignedEmptyCourse(): void
    {
        DB::table('course')->insert([
            'courseID' => '10',
            'courseName' => 'Empty Tracking Course',
            'courseCategory' => 'Professional Development',
            'targetAudience' => 'Teachers',
            'status' => 'Active',
            'courseType' => 'lms',
        ]);

        DB::table('course_session')->insert([
            'sessionID' => '20',
            'courseID' => '10',
            'sessionName' => 'LMS Delivery',
            'sessionDate' => '2026-08-10',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '20',
            'trainerID' => '7',
        ]);
    }

    private function seedMultiSessionSimpleTraining(): void
    {
        DB::table('course')->insert([
            [
                'courseID' => '11',
                'courseName' => 'Simple Delivery',
                'courseCategory' => 'Workshop',
                'targetAudience' => 'Teachers',
                'status' => 'Completed',
                'courseType' => 'simple',
            ],
            [
                'courseID' => '12',
                'courseName' => 'Unassigned Course',
                'courseCategory' => 'Workshop',
                'targetAudience' => 'Teachers',
                'status' => 'Active',
                'courseType' => 'simple',
            ],
        ]);

        DB::table('course_session')->insert([
            ['sessionID' => '21', 'courseID' => '11', 'sessionName' => 'Opening Session', 'sessionDate' => '2026-08-12'],
            ['sessionID' => '23', 'courseID' => '11', 'sessionName' => 'Follow-up Session', 'sessionDate' => '2026-08-13'],
            ['sessionID' => '22', 'courseID' => '12', 'sessionName' => 'Private Session', 'sessionDate' => '2026-08-14'],
        ]);

        DB::table('session_trainer')->insert([
            ['sessionID' => '21', 'trainerID' => '9'],
            ['sessionID' => '23', 'trainerID' => '9'],
            ['sessionID' => '22', 'trainerID' => '99'],
        ]);

        DB::table('guru_new')->insert([
            ['gn_id' => 1, 'gn_name' => 'Teacher One', 'email' => 'teacher1@example.test'],
            ['gn_id' => 2, 'gn_name' => 'Teacher Two', 'email' => 'teacher2@example.test'],
            ['gn_id' => 3, 'gn_name' => 'Guru New One', 'email' => 'guru@example.test'],
            ['gn_id' => 4, 'gn_name' => 'Public Participant', 'email' => 'public@example.test'],
        ]);

        DB::table('enroll_guru_baru')->insert([
            ['enrollment_id' => 111, 'course_id' => '11', 'gn_id' => 1],
            ['enrollment_id' => 112, 'course_id' => '11', 'gn_id' => 2],
            ['enrollment_id' => 113, 'course_id' => '11', 'gn_id' => 3],
            ['enrollment_id' => 114, 'course_id' => '11', 'gn_id' => 4],
        ]);

        DB::table('attendance_guru_baru')->insert([
            ['attendGuruBaru_id' => 1, 'session_id' => '21', 'gn_id' => 1, 'attendance_status' => 'approved'],
            ['attendGuruBaru_id' => 2, 'session_id' => '21', 'gn_id' => 2, 'attendance_status' => 'pending'],
            ['attendGuruBaru_id' => 3, 'session_id' => '21', 'gn_id' => 3, 'attendance_status' => 'rejected'],
            ['attendGuruBaru_id' => 4, 'session_id' => '21', 'gn_id' => 4, 'attendance_status' => 'approved'],
            ['attendGuruBaru_id' => 5, 'session_id' => '23', 'gn_id' => 1, 'attendance_status' => 'approved'],
            ['attendGuruBaru_id' => 6, 'session_id' => '23', 'gn_id' => 2, 'attendance_status' => 'pending'],
            ['attendGuruBaru_id' => 7, 'session_id' => '23', 'gn_id' => 3, 'attendance_status' => 'rejected'],
        ]);

    }

    private function seedSingleSessionSimpleTraining(): void
    {
        DB::table('course')->insert([
            'courseID' => '13',
            'courseName' => 'Single Session Workshop',
            'courseCategory' => 'Workshop',
            'targetAudience' => 'Teachers',
            'status' => 'Active',
            'courseType' => 'simple',
        ]);
        DB::table('course_session')->insert([
            'sessionID' => '31',
            'courseID' => '13',
            'sessionName' => 'Only Session',
            'sessionDate' => '2026-08-20',
        ]);
        DB::table('session_trainer')->insert([
            'sessionID' => '31',
            'trainerID' => '12',
        ]);
    }

    private function seedLmsAssessmentOutcomes(): void
    {
        DB::table('guru_new')->insert([
            ['gn_id' => 201, 'gn_name' => 'Assessment Learner One', 'email' => 'assessment1@example.test'],
            ['gn_id' => 202, 'gn_name' => 'Assessment Learner Two', 'email' => 'assessment2@example.test'],
            ['gn_id' => 203, 'gn_name' => 'Assessment Learner Three', 'email' => 'assessment3@example.test'],
        ]);
        DB::table('enroll_guru_baru')->insert([
            ['enrollment_id' => 201, 'gn_id' => 201, 'course_id' => '10'],
            ['enrollment_id' => 202, 'gn_id' => 202, 'course_id' => '10'],
            ['enrollment_id' => 203, 'gn_id' => 203, 'course_id' => '10'],
        ]);

        DB::table('assessment')->insert([
            ['assessment_id' => 301, 'course_id' => 10, 'title' => 'Knowledge Check', 'type' => 'quiz', 'total_marks' => 20],
            ['assessment_id' => 302, 'course_id' => 10, 'title' => 'Invalid Total Quiz', 'type' => 'quiz', 'total_marks' => 0],
            ['assessment_id' => 303, 'course_id' => 10, 'title' => 'Missing Grade Quiz', 'type' => 'quiz', 'total_marks' => 100],
            ['assessment_id' => 401, 'course_id' => 10, 'title' => 'Practice Reflection', 'type' => 'submission', 'total_marks' => 100],
            ['assessment_id' => 402, 'course_id' => 10, 'title' => 'Portfolio Evidence', 'type' => 'submission', 'total_marks' => 100],
        ]);

        DB::table('assessment_submission')->insert([
            [
                'submission_id' => 501,
                'assessment_id' => 301,
                'enrollment_id' => 201,
                'grade' => 16,
                'submitted_at' => '2026-08-10 10:00:00',
                'status' => 'Submitted',
            ],
            [
                'submission_id' => 502,
                'assessment_id' => 301,
                'enrollment_id' => 202,
                'grade' => 18,
                'submitted_at' => '2026-08-10 10:15:00',
                'status' => 'Submitted',
            ],
            ['submission_id' => 503, 'assessment_id' => 302, 'enrollment_id' => 201, 'grade' => 10, 'submitted_at' => '2026-08-10 11:00:00', 'status' => 'Submitted'],
            ['submission_id' => 504, 'assessment_id' => 303, 'enrollment_id' => 202, 'grade' => null, 'submitted_at' => '2026-08-10 11:15:00', 'status' => 'Submitted'],
            ['submission_id' => 601, 'assessment_id' => 401, 'enrollment_id' => 201, 'grade' => null, 'submitted_at' => '2026-08-10 10:00:00', 'status' => 'Reviewed'],
            ['submission_id' => 602, 'assessment_id' => 401, 'enrollment_id' => 202, 'grade' => null, 'submitted_at' => '2026-08-10 10:15:00', 'status' => 'Submitted'],
            ['submission_id' => 603, 'assessment_id' => 402, 'enrollment_id' => 201, 'grade' => null, 'submitted_at' => '2026-08-11 08:00:00', 'status' => 'Submitted'],
            ['submission_id' => 604, 'assessment_id' => 402, 'enrollment_id' => 203, 'grade' => null, 'submitted_at' => '2026-08-11 09:00:00', 'status' => 'Submitted'],
        ]);
    }

    private function seedAdditionalAssignedTrainingForTrainerSeven(): void
    {
        DB::table('course')->insert([
            'courseID' => '11',
            'courseName' => 'Second Assigned Training',
            'courseCategory' => 'Workshop',
            'targetAudience' => 'Teachers',
            'status' => 'Active',
            'courseType' => 'simple',
        ]);

        DB::table('course_session')->insert([
            'sessionID' => '21',
            'courseID' => '11',
            'sessionName' => 'Second Delivery',
            'sessionDate' => '2026-08-15',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '21',
            'trainerID' => '7',
        ]);
    }

    private function seedLearnerTrackingReport(): void
    {
        Schema::create('training_content_interaction_log', function (Blueprint $table): void {
            $table->integer('enrollment_id');
            $table->integer('block_id');
            $table->integer('open_count')->nullable();
            $table->integer('total_seconds')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
        });

        DB::table('guru_new')->insert([
            ['gn_id' => 701, 'gn_name' => 'Tracked Learner', 'email' => 'tracked@example.test'],
            ['gn_id' => 702, 'gn_name' => 'Quiet Learner', 'email' => 'quiet@example.test'],
            ['gn_id' => 799, 'gn_name' => 'Foreign Learner', 'email' => 'foreign@example.test'],
        ]);
        DB::table('enroll_guru_baru')->insert([
            ['enrollment_id' => 701, 'course_id' => '10', 'gn_id' => 701],
            ['enrollment_id' => 702, 'course_id' => '10', 'gn_id' => 702],
            ['enrollment_id' => 799, 'course_id' => '99', 'gn_id' => 799],
        ]);
        DB::table('training_module')->insert([
            ['module_id' => 801, 'course_id' => 10, 'module_title' => 'Second Module', 'module_order' => 2],
            ['module_id' => 802, 'course_id' => 10, 'module_title' => 'First Module', 'module_order' => 1],
            ['module_id' => 803, 'course_id' => 10, 'module_title' => 'Text Only Module', 'module_order' => 3],
            ['module_id' => 899, 'course_id' => 99, 'module_title' => 'Foreign Module', 'module_order' => 1],
        ]);
        DB::table('learning_material')->insert([
            ['material_id' => 821, 'original_file_name' => 'Teacher Guide.pdf'],
            ['material_id' => 822, 'original_file_name' => 'Photo.png'],
            ['material_id' => 824, 'original_file_name' => 'Workshop.mp4'],
            ['material_id' => 825, 'original_file_name' => 'Workshop.ogg'],
        ]);
        DB::table('training_content_block')->insert([
            ['block_id' => 901, 'module_id' => 802, 'material_id' => 821, 'block_type' => 'material', 'block_title' => null, 'block_order' => 1],
            ['block_id' => 902, 'module_id' => 802, 'material_id' => null, 'block_type' => 'youtube', 'block_title' => 'Orientation Video', 'block_order' => 2],
            ['block_id' => 904, 'module_id' => 802, 'material_id' => null, 'block_type' => 'title', 'block_title' => 'Not trackable', 'block_order' => 3],
            ['block_id' => 905, 'module_id' => 803, 'material_id' => null, 'block_type' => 'text', 'block_title' => 'Static introduction text', 'block_order' => 1],
            ['block_id' => 903, 'module_id' => 801, 'material_id' => 822, 'block_type' => 'media', 'block_title' => 'Inline Reference Image', 'block_order' => 1],
            ['block_id' => 907, 'module_id' => 801, 'material_id' => 824, 'block_type' => 'media', 'block_title' => 'Workshop Video', 'block_order' => 2],
            ['block_id' => 908, 'module_id' => 801, 'material_id' => 825, 'block_type' => 'media', 'block_title' => 'Workshop Audio', 'block_order' => 3],
            ['block_id' => 999, 'module_id' => 899, 'material_id' => null, 'block_type' => 'youtube', 'block_title' => 'Foreign Material', 'block_order' => 1],
        ]);
        DB::table('training_content_interaction_log')->insert([
            [
                'enrollment_id' => 701,
                'block_id' => 901,
                'open_count' => 2,
                'total_seconds' => 600,
                'first_opened_at' => '2026-08-19 10:00:00',
                'last_accessed_at' => '2026-08-20 11:00:00',
            ],
            [
                'enrollment_id' => 701,
                'block_id' => 903,
                'open_count' => 8,
                'total_seconds' => 900,
                'first_opened_at' => '2026-08-20 13:00:00',
                'last_accessed_at' => '2026-08-20 14:00:00',
            ],
        ]);
        DB::table('assessment')->insert([
            ['assessment_id' => 911, 'course_id' => 10, 'title' => 'Knowledge Quiz', 'type' => 'quiz', 'total_marks' => 20],
            ['assessment_id' => 912, 'course_id' => 10, 'title' => 'Zero Mark Quiz', 'type' => 'quiz', 'total_marks' => 0],
            ['assessment_id' => 913, 'course_id' => 10, 'title' => 'Submission Task', 'type' => 'submission', 'total_marks' => 10],
            ['assessment_id' => 914, 'course_id' => 99, 'title' => 'Foreign Quiz', 'type' => 'quiz', 'total_marks' => 10],
        ]);
        DB::table('assessment_question')->insert([
            [
                'question_id' => 951,
                'assessment_id' => 911,
                'question_text' => 'Which colour is primary?',
                'question_order' => 1,
                'question_type' => 'multiple_choice',
                'options_json' => '["A. Jean Piaget","B. Lev Vygotsky"]',
                'correct_answer' => 'B. Lev Vygotsky',
                'marks' => 4,
                'grading_rubric' => null,
            ],
            [
                'question_id' => 952,
                'assessment_id' => 911,
                'question_text' => 'The sky can appear blue.',
                'question_order' => 2,
                'question_type' => 'true_false',
                'options_json' => '["True","False"]',
                'correct_answer' => 'True',
                'marks' => 4,
                'grading_rubric' => null,
            ],
            [
                'question_id' => 953,
                'assessment_id' => 911,
                'question_text' => 'Choose the primary colours.',
                'question_order' => 3,
                'question_type' => 'checkbox',
                'options_json' => '["Red","Green","Blue","Black"]',
                'correct_answer' => '["A","C"]',
                'marks' => 4,
                'grading_rubric' => null,
            ],
            [
                'question_id' => 954,
                'assessment_id' => 911,
                'question_text' => 'Name one primary colour.',
                'question_order' => 4,
                'question_type' => 'short_answer',
                'options_json' => null,
                'correct_answer' => 'Red',
                'marks' => 4,
                'grading_rubric' => null,
            ],
            [
                'question_id' => 955,
                'assessment_id' => 911,
                'question_text' => 'Explain why colour matters.',
                'question_order' => 5,
                'question_type' => 'essay',
                'options_json' => null,
                'correct_answer' => null,
                'marks' => 4,
                'grading_rubric' => 'Use evidence and a clear conclusion.',
            ],
        ]);
        DB::table('assessment_submission')->insert([
            ['submission_id' => 921, 'assessment_id' => 911, 'enrollment_id' => 701, 'grade' => 16, 'submitted_at' => '2026-08-20 10:00:00', 'status' => 'Submitted', 'answers_json' => '{"q1":"A. Jean Piaget","q2":"False","q3":["A","B"],"q4":"Red"}'],
            ['submission_id' => 922, 'assessment_id' => 911, 'enrollment_id' => 701, 'grade' => 18, 'submitted_at' => '2026-08-20 12:00:00', 'status' => 'Submitted', 'answers_json' => '{"q1":"B. Lev Vygotsky","q2":"True","q3":["C","A"],"q4":"Blue","q5":"A detailed explanation"}'],
            ['submission_id' => 923, 'assessment_id' => 912, 'enrollment_id' => 701, 'grade' => 0, 'submitted_at' => '2026-08-20 09:00:00', 'status' => 'Submitted', 'answers_json' => null],
            ['submission_id' => 924, 'assessment_id' => 913, 'enrollment_id' => 701, 'grade' => null, 'submitted_at' => '2026-08-20 13:00:00', 'status' => 'Submitted', 'answers_json' => null],
            ['submission_id' => 925, 'assessment_id' => 912, 'enrollment_id' => 799, 'grade' => 99, 'submitted_at' => '2026-08-20 14:00:00', 'status' => 'Submitted', 'answers_json' => null],
        ]);
    }

    private function seedObjectiveReviewQuiz(): void
    {
        DB::table('assessment')->where('assessment_id', 912)->update(['total_marks' => 5]);
        DB::table('assessment_question')->insert([
            [
                'question_id' => 956,
                'assessment_id' => 912,
                'question_text' => 'What is the capital of Malaysia?',
                'question_order' => 1,
                'question_type' => 'mcq',
                'options_json' => '["A. Johor Bahru","B. Kuala Lumpur"]',
                'correct_answer' => 'B. Kuala Lumpur',
                'marks' => 2,
                'grading_rubric' => null,
            ],
            [
                'question_id' => 957,
                'assessment_id' => 912,
                'question_text' => 'Water freezes at zero degrees Celsius.',
                'question_order' => 2,
                'question_type' => 'true_false',
                'options_json' => '["True","False"]',
                'correct_answer' => 'True',
                'marks' => 3,
                'grading_rubric' => null,
            ],
        ]);
    }

    private function readOnlySnapshot(): array
    {
        return collect([
            'course',
            'course_session',
            'session_trainer',
            'enroll_guru_baru',
            'teacher',
            'guru_new',
            'outsider',
            'training_module',
            'learning_material',
            'training_content_block',
            'assessment',
            'assessment_submission',
            'assessment_question',
            'attendance',
            'attendance_guru_baru',
            'attendance_outsider',
        ])->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->get()->map(fn ($row) => (array) $row)->all(),
        ])->all();
    }
}
