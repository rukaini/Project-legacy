<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TrainerDashboardTeachingPlanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Storage::fake('local');

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
        });
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->default('lms');
            $table->unsignedInteger('capacity')->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
            $table->date('sessionDate')->nullable();
            $table->time('startTime')->nullable();
            $table->time('endTime')->nullable();
            $table->string('location')->nullable();
            $table->string('sessionName')->nullable();
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });
        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->id('enrollment_id');
            $table->string('teacher_id', 20);
            $table->string('course_id', 20);
            $table->timestamp('enrollment_date')->nullable();
        });
        Schema::create('enroll_guru_baru', function (Blueprint $table): void {
            $table->id('enrollment_id');
            $table->string('gn_id', 20);
            $table->string('course_id', 20);
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('enroll_outsider', function (Blueprint $table): void {
            $table->id('enrollment_id');
            $table->unsignedBigInteger('outsider_id');
            $table->string('course_id', 20);
            $table->timestamp('created_at')->nullable();
        });
        Schema::create('assessment', function (Blueprint $table): void {
            $table->id('assessment_id');
            $table->string('course_id', 20);
            $table->string('type')->nullable();
        });
        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->timestamp('graded_at')->nullable();
        });
        Schema::create('course_proposal', function (Blueprint $table): void {
            $table->id('proposal_id');
            $table->string('trainer_id', 20);
            $table->string('proposed_title');
            $table->string('status');
            $table->string('course_category')->nullable();
            $table->date('proposed_start_date')->nullable();
            $table->date('proposed_end_date')->nullable();
            $table->timestamps();
        });
        Schema::create('teaching_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('course_id', 20);
            $table->string('trainer_id', 20);
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['course_id', 'trainer_id']);
        });

        DB::table('trainer')->insert(['trainerID' => '9001', 'trainerName' => 'Dashboard Trainer']);
        DB::table('course')->insert([
            ['courseID' => '501', 'courseName' => 'Submitted Course', 'courseCategory' => 'Pedagogy', 'status' => 'Active', 'courseType' => 'lms', 'capacity' => 20],
            ['courseID' => '502', 'courseName' => 'Missing Plan Course', 'courseCategory' => 'Leadership', 'status' => 'Active', 'courseType' => 'standard', 'capacity' => 15],
            ['courseID' => '503', 'courseName' => 'Unassigned Course', 'courseCategory' => 'Other', 'status' => 'Active', 'courseType' => 'lms', 'capacity' => 10],
        ]);
        DB::table('course_session')->insert([
            ['sessionID' => '601', 'courseID' => '501', 'sessionDate' => now()->addDay()->toDateString(), 'sessionName' => 'Submitted Session'],
            ['sessionID' => '602', 'courseID' => '502', 'sessionDate' => now()->addDays(2)->toDateString(), 'sessionName' => 'Missing Session'],
            ['sessionID' => '603', 'courseID' => '503', 'sessionDate' => now()->addDays(3)->toDateString(), 'sessionName' => 'Unassigned Session'],
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => '601', 'trainerID' => '9001'],
            ['sessionID' => '602', 'trainerID' => '9001'],
            ['sessionID' => '603', 'trainerID' => '9002'],
        ]);

        $now = now();
        DB::table('teaching_plans')->insert([
            ['course_id' => 501, 'trainer_id' => 9001, 'original_name' => 'current.pdf', 'file_path' => 'teaching-plans/course-501/trainer-9001/current.pdf', 'mime_type' => 'application/pdf', 'file_size' => 12, 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['course_id' => 503, 'trainer_id' => 9001, 'original_name' => 'unassigned.pdf', 'file_path' => 'teaching-plans/course-503/trainer-9001/plan.pdf', 'mime_type' => 'application/pdf', 'file_size' => 8, 'submitted_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'teaching_plans',
            'course_proposal',
            'assessment_submission',
            'assessment',
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

    public function test_dashboard_counts_submitted_teaching_plans_once_per_assigned_course(): void
    {
        $response = $this->withSession([
            'user_id' => 9001,
            'user_role' => 'trainer',
            'user_name' => 'Dashboard Trainer',
        ])->get(route('trainer.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Teaching Plans')
            ->assertSee('1 / 2')
            ->assertSee('Submitted / assigned')
            ->assertDontSee('Participants Enrolled');
        $this->assertSame(1, $response->viewData('teachingPlansSubmitted'));
        $this->assertSame(2, $response->viewData('totalAssignedCourses'));
    }
}
