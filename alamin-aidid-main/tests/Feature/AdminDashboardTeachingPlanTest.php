<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminDashboardTeachingPlanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        DB::connection()->getPdo()->sqliteCreateFunction(
            'CURDATE',
            fn (): string => now()->toDateString()
        );

        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
        });
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->string('status')->nullable();
            $table->string('courseType')->nullable();
            $table->string('targetAudience')->nullable();
            $table->unsignedInteger('capacity')->default(0);
        });
        Schema::create('enroll_teacher', function (Blueprint $table): void {
            $table->string('teacherID', 20);
            $table->string('course_id', 20);
            $table->string('status')->default('approved');
            $table->timestamp('applied_at')->nullable();
        });
        Schema::create('course_proposal', function (Blueprint $table): void {
            $table->id('proposal_id');
            $table->string('trainer_id', 20);
            $table->string('proposed_title');
            $table->string('training_mode')->nullable();
            $table->string('delivery_mode')->nullable();
            $table->string('status');
            $table->timestamps();
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
        Schema::create('teaching_plan_comments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('teaching_plan_id');
            $table->string('reviewer_staff_id', 20);
            $table->text('comment');
            $table->timestamp('seen_by_trainer_at')->nullable();
            $table->timestamps();
        });

        DB::table('trainer')->insert([
            ['trainerID' => 'TR9001', 'trainerName' => 'Trainer Alpha'],
            ['trainerID' => 'TR9002', 'trainerName' => 'Trainer Beta'],
            ['trainerID' => 'TR9003', 'trainerName' => 'Trainer Gamma'],
        ]);
        DB::table('course')->insert([
            ['courseID' => 'C501', 'courseName' => 'Inclusive Facilitation'],
            ['courseID' => 'C502', 'courseName' => 'Reflective Practice'],
        ]);
        DB::table('enroll_teacher')->insert([
            ['teacherID' => 'T1', 'course_id' => 'C501', 'applied_at' => now()],
            ['teacherID' => 'T2', 'course_id' => 'C502', 'applied_at' => now()],
        ]);
        DB::table('course_proposal')->insert([
            'trainer_id' => 'TR9001',
            'proposed_title' => 'Inclusive Teaching Workshop',
            'training_mode' => 'LMS',
            'delivery_mode' => 'Online',
            'status' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('course_session')->insert([
            ['sessionID' => 'S601', 'courseID' => 'C501'],
            ['sessionID' => 'S602', 'courseID' => 'C501'],
            ['sessionID' => 'S603', 'courseID' => 'C502'],
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => 'S601', 'trainerID' => 'TR9001'],
            ['sessionID' => 'S602', 'trainerID' => 'TR9001'],
            ['sessionID' => 'S601', 'trainerID' => 'TR9002'],
            ['sessionID' => 'S603', 'trainerID' => 'TR9003'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'teaching_plan_comments',
            'teaching_plans',
            'session_trainer',
            'course_session',
            'course_proposal',
            'enroll_teacher',
            'course',
            'trainer',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_dashboard_shows_zero_and_all_reviewed_when_no_current_plan_needs_review(): void
    {
        $response = $this->adminDashboard()
            ->assertOk()
            ->assertSeeTextInOrder([
                'Trainers',
                'Trainings',
                'Participants',
                'Pending proposals',
                'Teaching Plans',
            ])
            ->assertSeeText('all reviewed')
            ->assertDontSee('<div class="adm-kpi-label">Attendance</div>', false);

        $this->assertSame(0, $response->viewData('teachingPlansNeedReview'));
        $this->assertSame(3, $response->viewData('totalTrainers'));
        $this->assertSame(2, $response->viewData('totalCourses'));
        $this->assertSame(2, $response->viewData('totalParticipants'));
        $this->assertSame(1, $response->viewData('pendingProposals'));
    }

    public function test_dashboard_counts_only_assigned_current_plans_without_admin_feedback(): void
    {
        $alphaPlan = $this->insertPlan('C501', 'TR9001', 'alpha.pdf');
        $betaPlan = $this->insertPlan('C501', 'TR9002', 'beta.pdf');
        $this->insertPlan('C502', 'TR9003', 'gamma.pdf');
        $this->insertPlan('C502', 'TR9001', 'unassigned.pdf');

        DB::table('teaching_plan_comments')->insert([
            'teaching_plan_id' => $betaPlan,
            'reviewer_staff_id' => 'STAFF7001',
            'comment' => 'Feedback has already been sent.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->adminDashboard()->assertOk()->assertSeeText('need review');

        $this->assertSame(2, $response->viewData('teachingPlansNeedReview'));
        $this->assertDatabaseHas('teaching_plans', ['id' => $alphaPlan]);
    }

    public function test_fresh_submission_after_reviewed_plan_removal_needs_review_again(): void
    {
        $reviewedPlan = $this->insertPlan('C501', 'TR9001', 'reviewed.pdf');
        DB::table('teaching_plan_comments')->insert([
            'teaching_plan_id' => $reviewedPlan,
            'reviewer_staff_id' => 'STAFF7001',
            'comment' => 'Reviewed.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $this->adminDashboard()->viewData('teachingPlansNeedReview'));

        DB::table('teaching_plan_comments')->where('teaching_plan_id', $reviewedPlan)->delete();
        DB::table('teaching_plans')->where('id', $reviewedPlan)->delete();
        $this->assertSame(0, $this->adminDashboard()->viewData('teachingPlansNeedReview'));

        $this->insertPlan('C501', 'TR9001', 'fresh.pdf');
        $this->assertSame(1, $this->adminDashboard()->viewData('teachingPlansNeedReview'));
    }

    private function adminDashboard()
    {
        return $this->withSession([
            'user_role' => 'admin',
            'user_id' => 'STAFF7001',
            'user_name' => 'Admin Reviewer',
        ])->get(route('admin.dashboard'));
    }

    private function insertPlan(string $courseId, string $trainerId, string $name): int
    {
        return (int) DB::table('teaching_plans')->insertGetId([
            'course_id' => $courseId,
            'trainer_id' => $trainerId,
            'original_name' => $name,
            'file_path' => "teaching-plans/{$courseId}/{$trainerId}/{$name}",
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
