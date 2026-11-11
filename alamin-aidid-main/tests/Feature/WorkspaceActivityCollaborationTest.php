<?php

namespace Tests\Feature;

use App\Support\TrainerActivityLogger;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkspaceActivityCollaborationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
            $table->string('trainerEmail')->nullable();
            $table->string('trainerPhoneNo')->nullable();
            $table->string('trainerPic')->nullable();
            $table->string('trainerPassword')->nullable();
            $table->decimal('averageRating', 3, 2)->nullable();
        });
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->text('description')->nullable();
            $table->string('targetAudience')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->string('sessionName')->nullable();
            $table->date('sessionDate')->nullable();
            $table->time('startTime')->nullable();
            $table->time('endTime')->nullable();
            $table->string('location')->nullable();
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });
        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('course_id', 20);
            $table->string('teacherID', 20);
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->integer('enrollment_id')->primary();
            $table->string('course_id', 20);
            $table->string('gn_id', 20);
            $table->string('status')->default('pending');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_outsider', function (Blueprint $table): void {
            $table->string('course_id', 20);
            $table->integer('outsider_id');
            $table->string('status')->default('pending');
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
        Schema::create('learning_material', function (Blueprint $table): void {
            $table->increments('material_id');
            $table->string('course_id', 20);
            $table->string('trainer_id', 20)->nullable();
            $table->string('original_file_name')->nullable();
            $table->string('ai_generated_title')->nullable();
            $table->timestamp('uploaded_at')->nullable();
        });
        Schema::create('training_module', function (Blueprint $table): void {
            $table->increments('module_id');
            $table->string('course_id', 20);
        });
        Schema::create('training_content_block', function (Blueprint $table): void {
            $table->increments('block_id');
            $table->integer('module_id');
            $table->boolean('is_visible')->nullable();
        });
        Schema::create('assessment', function (Blueprint $table): void {
            $table->increments('assessment_id');
            $table->string('course_id', 20);
            $table->string('title')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->increments('submission_id');
            $table->integer('assessment_id');
            $table->string('status')->nullable();
            $table->timestamp('submitted_at')->nullable();
        });
        Schema::create('trainer_activity_log', function (Blueprint $table): void {
            $table->bigIncrements('log_id');
            $table->string('trainer_id', 20)->index('idx_trainer');
            $table->string('action', 60)->index('idx_action');
            $table->string('detail', 255)->nullable();
            $table->string('course_id', 20)->nullable();
            $table->string('target_type', 40)->nullable();
            $table->bigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent()->index('idx_created');
        });

        DB::table('trainer')->insert([
            ['trainerID' => '11', 'trainerName' => 'Dr. Aisyah'],
            ['trainerID' => '12', 'trainerName' => 'Ahmad bin Ali'],
            ['trainerID' => '13', 'trainerName' => 'Unrelated Trainer'],
        ]);
        DB::table('course')->insert([
            ['courseID' => '101', 'courseName' => 'Course A', 'courseType' => 'lms', 'targetAudience' => 'teacher'],
            ['courseID' => '102', 'courseName' => 'Course B', 'courseType' => 'lms', 'targetAudience' => 'teacher'],
        ]);
        DB::table('course_session')->insert([
            ['sessionID' => '201', 'courseID' => '101', 'sessionName' => 'Session One', 'sessionDate' => '2026-09-10', 'startTime' => '09:00', 'endTime' => '10:00'],
            ['sessionID' => '202', 'courseID' => '101', 'sessionName' => 'Session Two', 'sessionDate' => '2026-09-11', 'startTime' => '09:00', 'endTime' => '10:00'],
            ['sessionID' => '203', 'courseID' => '102', 'sessionName' => 'Other Course Session', 'sessionDate' => '2026-09-12', 'startTime' => '09:00', 'endTime' => '10:00'],
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => '201', 'trainerID' => '11'],
            ['sessionID' => '201', 'trainerID' => '12'],
            ['sessionID' => '202', 'trainerID' => '11'],
            ['sessionID' => '202', 'trainerID' => '12'],
            ['sessionID' => '203', 'trainerID' => '13'],
        ]);

        DB::table('trainer_activity_log')->insert([
            ['trainer_id' => 11, 'course_id' => 101, 'action' => 'material.upload', 'detail' => 'Database_Normalization.pdf', 'target_type' => 'material', 'target_id' => 301, 'created_at' => '2026-09-03 09:00:00'],
            ['trainer_id' => 12, 'course_id' => 101, 'action' => 'quiz.create', 'detail' => 'Chapter 3 Review', 'target_type' => 'assessment', 'target_id' => 302, 'created_at' => '2026-09-03 10:00:00'],
            ['trainer_id' => 13, 'course_id' => 101, 'action' => 'module.create', 'detail' => 'Unassigned Activity', 'target_type' => 'module', 'target_id' => 303, 'created_at' => '2026-09-03 11:00:00'],
            ['trainer_id' => 11, 'course_id' => 102, 'action' => 'material.upload', 'detail' => 'Other Course File.pdf', 'target_type' => 'material', 'target_id' => 304, 'created_at' => '2026-09-03 12:00:00'],
            ['trainer_id' => 11, 'course_id' => 101, 'action' => 'teaching_plan.submit', 'detail' => 'Private Plan.pdf', 'target_type' => 'teaching_plan', 'target_id' => 305, 'created_at' => '2026-09-03 13:00:00'],
            ['trainer_id' => 11, 'course_id' => 101, 'action' => 'training.open', 'detail' => 'Course A', 'target_type' => 'course', 'target_id' => 101, 'created_at' => '2026-09-03 14:00:00'],
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        foreach ([
            'trainer_activity_log',
            'assessment_submission',
            'assessment',
            'training_content_block',
            'training_module',
            'learning_material',
            'outsider',
            'guru_new',
            'teacher',
            'enroll_outsider',
            'enroll_guru_baru',
            'enroll_teacher',
            'session_trainer',
            'course_session',
            'course',
            'trainer',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_co_trainers_share_meaningful_course_updates_with_actor_attribution_and_isolation(): void
    {
        Carbon::setTestNow('2026-09-03 15:00:00');
        $before = DB::table('trainer_activity_log')->count();

        $asAhmad = $this->trainerRequest(12)
            ->get(route('trainer.courses.show', 101))
            ->assertOk()
            ->assertSee('Recent Workspace Updates')
            ->assertSee('Material uploaded')
            ->assertSee('Database_Normalization.pdf')
            ->assertSee('Dr. Aisyah')
            ->assertSee('Quiz created')
            ->assertSee('Chapter 3 Review')
            ->assertSee('Ahmad bin Ali (You)')
            ->assertDontSee('Unassigned Activity')
            ->assertDontSee('Other Course File.pdf')
            ->assertDontSee('Private Plan.pdf')
            ->assertDontSee('Opened a training');

        $updates = $asAhmad->viewData('recentWorkspaceLogs');
        $this->assertSame(['quiz.create', 'material.upload'], $updates->pluck('action')->all());
        $this->assertSame(['Ahmad bin Ali', 'Dr. Aisyah'], $updates->pluck('trainer_name')->all());
        $this->assertSame($before, DB::table('trainer_activity_log')->count(), 'Opening the workspace must remain read-only.');

        $asAisyah = $this->trainerRequest(11)->get(route('trainer.courses.show', 101))->assertOk();
        $this->assertSame(['quiz.create', 'material.upload'], $asAisyah->viewData('recentWorkspaceLogs')->pluck('action')->all());
        $asAisyah->assertSee('Dr. Aisyah (You)')->assertSee('Ahmad bin Ali');
    }

    public function test_lms_course_page_queries_the_canonical_lowercase_learning_material_table(): void
    {
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $this->trainerRequest(11)
            ->get(route('trainer.courses.show', 101))
            ->assertOk();

        $materialQueries = array_values(array_filter(
            $queries,
            fn (string $sql): bool => preg_match('/(?<![A-Za-z0-9_])learning_material(?![A-Za-z0-9_])/', $sql) === 1
        ));

        $this->assertNotEmpty(
            $materialQueries,
            'The LMS course page must query the canonical lowercase learning_material table.'
        );
        $this->assertSame(
            [],
            array_values(array_filter(
                $queries,
                fn (string $sql): bool => str_contains($sql, 'LEARNING_MATERIAL')
            )),
            'The LMS course page must not query the non-canonical uppercase LEARNING_MATERIAL table.'
        );
    }

    public function test_workspace_updates_use_the_real_log_id_schema_and_order_equal_timestamps_deterministically(): void
    {
        $this->assertTrue(Schema::hasColumn('trainer_activity_log', 'log_id'));
        $this->assertFalse(Schema::hasColumn('trainer_activity_log', 'activity_id'));

        DB::table('trainer_activity_log')->insert([
            ['trainer_id' => 11, 'course_id' => 101, 'action' => 'module.update', 'detail' => 'First equal-time update', 'created_at' => '2026-09-04 09:00:00'],
            ['trainer_id' => 12, 'course_id' => 101, 'action' => 'quiz.update', 'detail' => 'Second equal-time update', 'created_at' => '2026-09-04 09:00:00'],
        ]);

        $updates = $this->trainerRequest(11)
            ->get(route('trainer.courses.show', 101))
            ->assertOk()
            ->viewData('recentWorkspaceLogs');

        $this->assertSame(
            ['Second equal-time update', 'First equal-time update'],
            $updates->take(2)->pluck('detail')->all()
        );
    }

    public function test_logger_writes_only_real_columns_and_bounds_string_payloads_to_the_shared_table_shape(): void
    {
        TrainerActivityLogger::log(
            11,
            'material.upload',
            str_repeat('A', 300),
            101,
            'material',
            999
        );

        $activity = DB::table('trainer_activity_log')->where('target_id', 999)->first();

        $this->assertNotNull($activity);
        $this->assertSame(255, mb_strlen($activity->detail));
        $this->assertSame('material.upload', $activity->action);
        $this->assertSame('material', $activity->target_type);
        $this->assertObjectNotHasProperty('activity_id', $activity);
    }

    public function test_training_team_uses_assignments_deduplicates_multiple_sessions_and_excludes_unrelated_trainers(): void
    {
        $response = $this->trainerRequest(11)->get(route('trainer.courses.show', 101))->assertOk();
        $team = $response->viewData('trainingTeam');

        $this->assertSame([12, 11], $team->pluck('trainer_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(2, $team->count());
        $this->assertTrue((bool) $team->firstWhere('trainer_id', 11)->is_current);
        $response->assertSee('Training Team')->assertDontSee('Unrelated Trainer');

        $this->trainerRequest(13)
            ->get(route('trainer.courses.show', 101))
            ->assertRedirect(route('trainer.courses'));

        $singleTrainer = $this->trainerRequest(13)
            ->get(route('trainer.courses.show', 102))
            ->assertOk()
            ->assertSee('Unrelated Trainer')
            ->assertSee('You')
            ->assertSee('No workspace updates yet');
        $this->assertSame(1, $singleTrainer->viewData('trainingTeam')->count());
    }

    private function trainerRequest(int $trainerId)
    {
        return $this->withSession([
            'user_id' => $trainerId,
            'user_role' => 'trainer',
            'user_name' => DB::table('trainer')->where('trainerID', (string) $trainerId)->value('trainerName'),
        ]);
    }
}
