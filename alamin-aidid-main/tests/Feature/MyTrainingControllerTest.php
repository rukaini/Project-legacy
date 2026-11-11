<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MyTrainingControllerTest extends TestCase
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

        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price')->nullable();
            $table->string('status')->nullable();
            $table->string('mode')->nullable();
            $table->string('courseType')->nullable();
            $table->string('targetAudience')->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });
        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20);
            $table->string('course_id', 20);
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->increments('enrollment_id');
            $table->string('gn_id', 20);
            $table->string('course_id', 20);
        });
        Schema::create('enroll_outsider', function (Blueprint $table): void {
            $table->increments('enrollment_id');
            $table->unsignedInteger('outsider_id');
            $table->string('course_id', 20);
        });

        foreach (range(1, 6) as $courseId) {
            $canonicalCourseId = 'C'.$courseId;
            $canonicalSessionId = 'CS'.$courseId;

            DB::table('course')->insert([
                'courseID' => $canonicalCourseId,
                'courseName' => 'Assigned Training '.$courseId,
                'courseCategory' => $courseId <= 5 ? 'Teacher Training' : 'Public Training',
                'description' => 'Real assigned training description.',
                'capacity' => 30,
                'price' => 0,
                'status' => $courseId === 6 ? 'Completed' : 'Active',
                'mode' => $courseId % 2 === 0 ? 'physical' : 'online',
                'courseType' => $courseId % 2 === 0 ? 'lms' : 'simple',
                'targetAudience' => $courseId === 6 ? 'Public' : 'Teachers',
            ]);
            DB::table('course_session')->insert(['sessionID' => $canonicalSessionId, 'courseID' => $canonicalCourseId]);
            DB::table('session_trainer')->insert(['sessionID' => $canonicalSessionId, 'trainerID' => 'TR7']);
        }
    }

    public function test_assigned_trainings_render_once_for_client_filtering_and_fixed_paging(): void
    {
        $response = $this->withSession(['user_role' => 'trainer', 'user_id' => 'TR7'])
            ->get(route('trainer.courses'))
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->count() === 6)
            ->assertSee('Assigned Training 1')
            ->assertSee('Assigned Training 6')
            ->assertSee('id="workspacePager"', false)
            ->assertSee('aria-label="Training pagination"', false)
            ->assertSee('Showing 1–5 of 6 trainings · Page 1 of 2')
            ->assertSee('id="workspacePageControls"', false)
            ->assertSee('id="categoryFilter"', false)
            ->assertSee('Teacher Training')
            ->assertSee('id="statusFilter"', false)
            ->assertSee('value="active"', false)
            ->assertSee('data-status="completed"', false)
            ->assertSee('>LMS</span>', false)
            ->assertSee('>Simple</span>', false)
            ->assertDontSee('class="trn-status', false)
            ->assertDontSee('bi-tag', false)
            ->assertDontSee('Rows per page');

        $this->assertSame(['C1', 'C2', 'C3', 'C4', 'C5', 'C6'], $response->viewData('courses')->pluck('course_id')->all());
        $courses = $response->viewData('courses')->keyBy('course_id');
        $this->assertSame('simple', $courses['C1']->training_mode);
        $this->assertSame('online', $courses['C1']->course_mode);
        $this->assertSame('lms', $courses['C2']->training_mode);
        $this->assertSame('physical', $courses['C2']->course_mode);
    }

    public function test_unassigned_trainer_keeps_truthful_empty_state_without_pagination(): void
    {
        $this->withSession(['user_role' => 'trainer', 'user_id' => 'TR99'])
            ->get(route('trainer.courses'))
            ->assertOk()
            ->assertSee('No training assigned yet')
            ->assertDontSee('id="workspacePager"', false);
    }
}
