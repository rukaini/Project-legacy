<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrainerFeedbackControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::dropIfExists('feedback_response');
        Schema::dropIfExists('feedback_question');
        Schema::dropIfExists('feedback_category');
        Schema::dropIfExists('feedback_form');
        Schema::dropIfExists('course');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');

        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->date('sessionDate');
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
        });
        Schema::create('feedback_form', function (Blueprint $table): void {
            $table->string('formID', 20)->primary();
            $table->string('title');
            $table->string('sessionID', 20)->nullable();
            $table->string('courseID', 20)->nullable();
            $table->string('feedbackType', 20);
        });
        Schema::create('feedback_category', function (Blueprint $table): void {
            $table->string('categoryID', 20)->primary();
            $table->string('categoryName');
            $table->string('formID', 20);
        });
        Schema::create('feedback_question', function (Blueprint $table): void {
            $table->string('questionID', 20)->primary();
            $table->unsignedInteger('questionOrder');
            $table->string('questionText');
            $table->string('questionType');
            $table->string('categoryID', 20);
        });
        Schema::create('feedback_response', function (Blueprint $table): void {
            $table->string('responseID', 100)->primary();
            $table->decimal('rating', 4, 2)->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('responseDate');
            $table->string('participantID', 100)->nullable();
            $table->string('staffID', 100)->nullable();
            $table->string('questionID', 20);
        });

        $this->assignCourse(10, 101, 9001);
        $this->assignCourse(30, 301, 9001, now()->addWeek()->toDateString());
        $this->assignCourse(40, 401, 9999);

        $this->insertFeedback(10, 501, 'anonymous-1', 5, 'Clear and practical.', now());
        $this->insertFeedback(10, 501, 'anonymous-2', 4, null, now()->subDays(5));
        $this->insertFeedback(10, 501, 'anonymous-3', 3, 'Useful classroom examples.', now()->subDays(40));
        $this->insertFeedback(10, 502, 'anonymous-1', 5, 'Patient explanation.', now(), 'The trainer answered questions effectively.', 2);
        $this->insertFeedback(10, 502, 'anonymous-2', 5, null, now()->subDays(5), 'The trainer answered questions effectively.', 2);
        $this->insertFeedback(10, 502, 'anonymous-3', 4, null, now()->subDays(40), 'The trainer answered questions effectively.', 2);

        $this->insertFeedback(30, 701, 'future-1', 5, 'Future course comment.', now(), 'Future trainer question.');
        $this->insertFeedback(40, 801, 'other-trainer-1', 5, 'Another trainer comment.', now(), 'Another trainer question.');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('feedback_response');
        Schema::dropIfExists('feedback_question');
        Schema::dropIfExists('feedback_category');
        Schema::dropIfExists('feedback_form');
        Schema::dropIfExists('course');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');

        parent::tearDown();
    }

    public function test_feedback_overview_keeps_four_scoped_metrics_and_read_only_data(): void
    {
        DB::table('feedback_response')
            ->where('questionID', 502)
            ->where('participantID', 'anonymous-2')
            ->update(['comment' => '   ']);
        $before = $this->snapshot();

        $response = $this->trainerRequest()->get(route('trainer.feedback'))
            ->assertOk()
            ->assertSee('Inclusive Classroom Practice')
            ->assertSee('4.43')
            ->assertSee('3')
            ->assertSee('Feedback by Training')
            ->assertSee('1 training with feedback')
            ->assertDontSee('data-feedback-training-rows', false)
            ->assertDontSee('data-feedback-training-page', false)
            ->assertSee('id="feedbackQuestionSelector"', false)
            ->assertSee('Clear and practical.')
            ->assertDontSee('Teacher Training')
            ->assertDontSee('Another trainer comment.')
            ->assertDontSee('Future course comment.');

        $this->assertSame(4, substr_count($response->getContent(), 'class="ana-metric fb-summary-card"'));
        $this->assertSame(1, substr_count($response->getContent(), 'class="fb-question-detail"'));
        $this->assertSame(3, $response->viewData('totalRespondents'));
        $this->assertSame(1, $response->viewData('totalCourses'));
        $this->assertSame(3, $response->viewData('writtenComments'));
        $this->assertSame('10', $response->viewData('selectedTraining')['course_id']);
        $this->assertSame('501', $response->viewData('selectedQuestion')['id']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_feedback_access_matches_trainer_assignment_and_started_course(): void
    {
        $this->withSession(['user_id' => 9001, 'user_role' => 'admin'])
            ->get(route('trainer.feedback'))
            ->assertRedirect(route('login'));

        DB::table('course_session')->insert([
            'sessionID' => '201',
            'courseID' => '20',
            'sessionDate' => now()->subDay()->toDateString(),
        ]);
        DB::table('session_trainer')->insert(['sessionID' => '201', 'trainerID' => '9001']);
        $this->insertFeedback(20, 601, 'started-1', 5, 'Started course comment.', now(), 'Started course trainer question.');

        $response = $this->trainerRequest()->get(route('trainer.feedback', ['course_id' => 20]))
            ->assertOk()
            ->assertSee('Course 20')
            ->assertSee('Started course trainer question.')
            ->assertDontSee('Another trainer question.')
            ->assertDontSee('Future course comment.');

        $this->assertTrue(DB::table('session_trainer')->where('sessionID', '201')->where('trainerID', '9001')->exists());
        $this->assertSame('20', $response->viewData('filters')['course_id']);
        $this->assertSame('20', $response->viewData('selectedTraining')['course_id']);
        $this->assertTrue($response->viewData('courseOptions')->contains('course_id', '20'));
        $this->assertFalse($response->viewData('courseOptions')->contains('course_id', '30'));
        $this->assertFalse($response->viewData('courseOptions')->contains('course_id', '40'));
    }

    public function test_period_rating_and_comment_search_filters_use_canonical_feedback_rows(): void
    {
        $this->assignCourse(11, 111, 9001);
        $this->insertFeedback(11, 511, 'low-1', 2, 'Needs more examples.', now(), 'Low rated trainer question.');

        $recent = $this->trainerRequest()->get(route('trainer.feedback', ['filter_course_id' => 10, 'course_id' => 10, 'period' => '30']))->assertOk();
        $recentTraining = $recent->viewData('trainingResults')->firstWhere('course_id', 10);
        $this->assertSame(2, $recentTraining['respondent_count']);
        $this->assertSame(4.75, $recentTraining['avg_rating']);
        $this->assertSame('30', $recent->viewData('filters')['period']);
        $this->assertSame(2, $recent->viewData('totalRespondents'));
        $this->assertSame(1, $recent->viewData('totalCourses'));
        $this->assertSame(2, $recent->viewData('writtenComments'));
        $this->assertSame(4.75, $recent->viewData('liveRating'));

        $low = $this->trainerRequest()->get(route('trainer.feedback', ['rating' => 'low']))
            ->assertOk()
            ->assertSee('Course 11');
        $this->assertSame(1, $low->viewData('trainingResultCount'));
        $this->assertSame(['11'], $low->viewData('trainingResults')->pluck('course_id')->all());
        $this->assertSame(1, $low->viewData('totalRespondents'));
        $this->assertSame(1, $low->viewData('writtenComments'));
        $this->assertSame(2.0, $low->viewData('liveRating'));

        $search = $this->trainerRequest()->get(route('trainer.feedback', ['search' => 'classroom']))
            ->assertOk()
            ->assertSee('Inclusive Classroom Practice')
            ->assertSee('Comments matching “classroom”');
        $this->assertSame('classroom', $search->viewData('filters')['search']);
        $this->assertSame(['10'], $search->viewData('trainingResults')->pluck('course_id')->all());
        $this->assertSame(1, $search->viewData('totalRespondents'));
        $this->assertSame(1, $search->viewData('totalCourses'));
        $this->assertSame(1, $search->viewData('writtenComments'));
        $this->assertSame(3.0, $search->viewData('liveRating'));

        $none = $this->trainerRequest()->get(route('trainer.feedback', ['search' => 'not-present']))
            ->assertOk()
            ->assertSee('No matching feedback');
        $this->assertSame(0, $none->viewData('trainingResultCount'));
        $this->assertSame(0, $none->viewData('writtenComments'));
    }

    public function test_custom_period_uses_inclusive_response_dates_and_invalid_ranges_are_safe(): void
    {
        $this->assignCourse(11, 111, 9001);
        $this->insertFeedback(11, 511, 'before', 1, 'Before range.', '2026-01-09 23:59:59');
        $this->insertFeedback(11, 511, 'from-boundary', 4, 'From boundary.', '2026-01-10 00:00:00');
        $this->insertFeedback(11, 511, 'to-boundary', 5, 'To boundary.', '2026-01-12 23:59:59');
        $this->insertFeedback(11, 511, 'after', 2, 'After range.', '2026-01-13 00:00:00');
        $before = $this->snapshot();

        $custom = $this->trainerRequest()->get(route('trainer.feedback', [
            'filter_course_id' => 11,
            'period' => 'custom',
            'date_from' => '2026-01-10',
            'date_to' => '2026-01-12',
        ]))->assertOk()
            ->assertSee('From boundary.')
            ->assertSee('To boundary.')
            ->assertDontSee('Before range.')
            ->assertDontSee('After range.');

        $this->assertTrue($custom->viewData('filters')['custom_date_valid']);
        $this->assertSame('2026-01-10', $custom->viewData('filters')['date_from']);
        $this->assertSame('2026-01-12', $custom->viewData('filters')['date_to']);
        $this->assertSame(2, $custom->viewData('selectedQuestion')['count']);
        $this->assertSame(2, $custom->viewData('totalRespondents'));
        $this->assertSame(2, $custom->viewData('writtenComments'));
        $this->assertSame(4.5, $custom->viewData('liveRating'));

        $incomplete = $this->trainerRequest()->get(route('trainer.feedback', [
            'filter_course_id' => 11,
            'period' => 'custom',
            'date_from' => '2026-01-10',
        ]))->assertOk();
        $this->assertFalse($incomplete->viewData('filters')['custom_date_valid']);
        $this->assertSame('all', $incomplete->viewData('filters')['period']);
        $this->assertSame('', $incomplete->viewData('filters')['date_from']);
        $this->assertSame(4, $incomplete->viewData('selectedQuestion')['count']);

        $reversed = $this->trainerRequest()->get(route('trainer.feedback', [
            'filter_course_id' => 11,
            'period' => 'custom',
            'date_from' => '2026-01-12',
            'date_to' => '2026-01-10',
        ]))->assertOk();
        $this->assertFalse($reversed->viewData('filters')['custom_date_valid']);
        $this->assertSame('all', $reversed->viewData('filters')['period']);
        $this->assertSame('', $reversed->viewData('filters')['date_from']);
        $this->assertSame('', $reversed->viewData('filters')['date_to']);
        $this->assertSame(4, $reversed->viewData('selectedQuestion')['count']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_all_filtered_training_results_are_available_for_the_scrollable_navigator(): void
    {
        foreach (range(11, 22) as $courseId) {
            $this->assignCourse($courseId, 1000 + $courseId, 9001);
            $this->insertFeedback($courseId, 2000 + $courseId, 'anonymous-'.$courseId, 4, null, now());
        }

        $response = $this->trainerRequest()->get(route('trainer.feedback', ['rows' => 10, 'page' => 2]))->assertOk();
        $this->assertCount(13, $response->viewData('trainingResults'));
        $this->assertSame(13, $response->viewData('trainingResultCount'));
        $this->assertArrayNotHasKey('rows', $response->viewData('filters'));
        $this->assertArrayNotHasKey('page', $response->viewData('filters'));
        $response
            ->assertSee('13 trainings with feedback')
            ->assertDontSee('data-feedback-training-rows', false)
            ->assertDontSee('data-feedback-training-page', false)
            ->assertDontSee('Showing 1–')
            ->assertDontSee('>Previous<', false)
            ->assertDontSee('>Next<', false);
    }

    public function test_selected_training_renders_one_selected_question_and_preserves_rating_distribution(): void
    {
        $before = $this->snapshot();
        $response = $this->trainerRequest()->get(route('trainer.feedback', ['course_id' => 10, 'question_id' => 501]))
            ->assertOk()
            ->assertSee('Question 1 of 2')
            ->assertSee('id="feedbackQuestionSelector"', false)
            ->assertSee('The trainer explained the topic clearly.')
            ->assertSee('The trainer answered questions effectively.')
            ->assertSee('Written Comments')
            ->assertSee('Clear and practical.')
            ->assertDontSee('Another trainer question.');

        $questions = $response->viewData('feedbackQuestions')->keyBy('id');
        $this->assertCount(2, $questions);
        $this->assertSame(1, substr_count($response->getContent(), 'class="fb-question-detail"'));
        $this->assertSame('501', $response->viewData('selectedQuestion')['id']);
        $this->assertSame(4.0, $questions['501']['avg']);
        $this->assertSame([1 => 0, 2 => 0, 3 => 1, 4 => 1, 5 => 1], $questions['501']['distribution']);
        $this->assertSame(2, $questions['501']['comment_count']);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_comment_fragment_is_anonymous_scoped_searchable_and_batched(): void
    {
        foreach (range(4, 14) as $respondent) {
            $this->insertFeedback(10, 501, 'anonymous-'.$respondent, 4, 'Paginated comment '.$respondent, now()->subMinutes($respondent));
        }

        $before = $this->snapshot();
        $first = $this->trainerRequest()->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('trainer.feedback', [
            'feedback_fragment' => 'comments',
            'course_id' => 10,
            'question_id' => 501,
        ]))->assertOk()
            ->assertSee('Show more comments')
            ->assertDontSee('anonymous-1')
            ->assertDontSee('Another trainer comment.');

        $this->assertCount(5, $first->viewData('comments'));
        $this->assertSame(13, $first->viewData('commentTotal'));
        $this->assertSame(5, $first->viewData('nextCommentOffset'));
        $first->assertDontSee('data-feedback-comment-rows', false);

        $second = $this->trainerRequest()->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('trainer.feedback', [
            'feedback_fragment' => 'comments',
            'course_id' => 10,
            'question_id' => 501,
            'comment_offset' => 5,
        ]))->assertOk();
        $this->assertCount(5, $second->viewData('comments'));
        $this->assertSame(10, $second->viewData('nextCommentOffset'));

        $search = $this->trainerRequest()->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('trainer.feedback', [
            'feedback_fragment' => 'comments',
            'course_id' => 10,
            'question_id' => 501,
            'search' => 'classroom',
        ]))->assertOk()->assertSee('Useful classroom examples.')->assertDontSee('Clear and practical.');

        $unauthorized = $this->trainerRequest()->withHeader('X-Requested-With', 'XMLHttpRequest')->get(route('trainer.feedback', [
            'feedback_fragment' => 'comments',
            'course_id' => 40,
            'question_id' => 801,
        ]))->assertOk()->assertSee('No written comments for this question.')->assertDontSee('Another trainer comment.');

        $this->assertSame($before, $this->snapshot());
    }

    public function test_async_feedback_response_preserves_normalized_filter_state(): void
    {
        $response = $this->trainerRequest()
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('trainer.feedback', ['filter_course_id' => 10, 'course_id' => 10, 'question_id' => 501, 'period' => 'custom', 'date_from' => now()->subDays(7)->toDateString(), 'date_to' => now()->toDateString(), 'rating' => 'high', 'search' => 'clear']))
            ->assertOk()
            ->assertSee('id="feedbackDynamicRegion"', false)
            ->assertSee('value="clear"', false)
            ->assertDontSee('<!DOCTYPE html>', false);

        $this->assertSame([
            'filter_course_id' => '10',
            'course_id' => '10',
            'question_id' => '501',
            'period' => 'custom',
            'date_from' => now()->subDays(7)->toDateString(),
            'date_to' => now()->toDateString(),
            'rating' => 'high',
            'search' => 'clear',
        ], collect($response->viewData('filters'))->only(['filter_course_id', 'course_id', 'question_id', 'period', 'date_from', 'date_to', 'rating', 'search'])->all());
    }

    private function trainerRequest()
    {
        return $this->withSession([
            'user_id' => 9001,
            'user_role' => 'trainer',
            'user_name' => 'Trainer Test',
        ]);
    }

    private function assignCourse(int $courseId, int $sessionId, int $trainerId, ?string $date = null): void
    {
        DB::table('course_session')->insert([
            'sessionID' => (string) $sessionId,
            'courseID' => (string) $courseId,
            'sessionDate' => $date ?? now()->subDay()->toDateString(),
        ]);
        DB::table('session_trainer')->insert(['sessionID' => (string) $sessionId, 'trainerID' => (string) $trainerId]);
    }

    private function insertFeedback(
        int $courseId,
        int $questionId,
        string $anonymousCode,
        float $rating,
        ?string $comment,
        $responseDate,
        string $question = 'The trainer explained the topic clearly.',
        int $questionOrder = 1
    ): void {
        $sessionId = DB::table('course_session')->where('courseID', (string) $courseId)->value('sessionID');
        $formId = 'form-'.$courseId;
        $categoryId = 'category-'.$courseId;

        DB::table('course')->updateOrInsert(['courseID' => (string) $courseId], [
            'courseName' => $courseId === 10 ? 'Inclusive Classroom Practice' : 'Course '.$courseId,
            'courseCategory' => 'Teacher Training',
        ]);
        DB::table('feedback_form')->updateOrInsert(['formID' => $formId], [
            'title' => 'Trainer Feedback',
            'sessionID' => $sessionId,
            'courseID' => (string) $courseId,
            'feedbackType' => 'participant',
        ]);
        DB::table('feedback_category')->updateOrInsert(['categoryID' => $categoryId], [
            'categoryName' => 'Trainer',
            'formID' => $formId,
        ]);
        DB::table('feedback_question')->updateOrInsert(['questionID' => (string) $questionId], [
            'questionOrder' => $questionOrder,
            'questionText' => $question,
            'questionType' => 'rating',
            'categoryID' => $categoryId,
        ]);
        DB::table('feedback_response')->insert([
            'responseID' => implode('-', [$courseId, $questionId, $anonymousCode]),
            'rating' => $rating,
            'comment' => $comment,
            'responseDate' => $responseDate,
            'participantID' => $anonymousCode,
            'questionID' => (string) $questionId,
        ]);
    }

    private function snapshot(): array
    {
        return collect(['course', 'course_session', 'session_trainer', 'feedback_form', 'feedback_category', 'feedback_question', 'feedback_response'])->mapWithKeys(fn (string $table) => [
            $table => DB::table($table)->orderBy(DB::raw('1'))->get()->map(fn ($row) => (array) $row)->all(),
        ])->all();
    }
}
