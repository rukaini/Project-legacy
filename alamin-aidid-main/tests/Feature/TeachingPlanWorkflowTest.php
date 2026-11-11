<?php

namespace Tests\Feature;

use App\Services\TeachingPlanService;
use App\Support\TrainerActivityLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TeachingPlanWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Storage::fake('local');

        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseType')->default('lms');
            $table->string('courseCategory')->nullable();
            $table->string('targetAudience')->nullable();
        });
        Schema::create('trainer', function (Blueprint $table): void {
            $table->string('trainerID', 20)->primary();
            $table->string('trainerName');
        });
        Schema::create('staff_edu', function (Blueprint $table): void {
            $table->string('staffID', 20)->primary();
            $table->string('staffName');
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
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

        DB::table('course')->insert([
            ['courseID' => '501', 'courseName' => 'Inclusive Facilitation', 'courseType' => 'lms'],
            ['courseID' => '502', 'courseName' => 'Simple Briefing', 'courseType' => 'standard'],
        ]);
        DB::table('trainer')->insert([
            ['trainerID' => '9001', 'trainerName' => 'Trainer Alpha'],
            ['trainerID' => '9002', 'trainerName' => 'Trainer Beta'],
            ['trainerID' => '9003', 'trainerName' => 'Trainer Unassigned'],
        ]);
        DB::table('staff_edu')->insert(['staffID' => '7001', 'staffName' => 'Admin Reviewer']);
        DB::table('course_session')->insert([
            ['sessionID' => '601', 'courseID' => '501'],
            ['sessionID' => '602', 'courseID' => '502'],
        ]);
        DB::table('session_trainer')->insert([
            ['sessionID' => '601', 'trainerID' => '9001'],
            ['sessionID' => '601', 'trainerID' => '9002'],
            ['sessionID' => '602', 'trainerID' => '9001'],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ([
            'trainer_activity_log',
            'teaching_plan_comments',
            'teaching_plans',
            'session_trainer',
            'course_session',
            'staff_edu',
            'trainer',
            'course',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_upload_creates_one_current_plan_and_second_upload_is_rejected(): void
    {
        $this->uploadPlan(9001, 501, 'alpha-current.pdf');
        $current = DB::table('teaching_plans')->where('trainer_id', 9001)->first();

        $this->assertSame('alpha-current.pdf', $current->original_name);
        $this->assertFalse(Schema::hasColumn('teaching_plans', 'version'));
        Storage::disk('local')->assertExists($current->file_path);

        $this->trainerRequest(9001)
            ->post(route('trainer.courses.teaching-plans.store', 501), [
                'teaching_plan' => UploadedFile::fake()->create('second.pdf', 30, 'application/pdf'),
            ])
            ->assertRedirect(route('trainer.courses.teaching-plans.index', 501))
            ->assertSessionHasErrors([
                'teaching_plan' => 'Remove your current Teaching Plan before uploading a new submission.',
            ]);

        $this->assertSame(1, DB::table('teaching_plans')->where('course_id', 501)->where('trainer_id', 9001)->count());
        $this->assertSame('alpha-current.pdf', DB::table('teaching_plans')->where('trainer_id', 9001)->value('original_name'));
    }

    public function test_multiple_assigned_trainers_keep_independent_current_plans(): void
    {
        $this->uploadPlan(9001, 501, 'alpha.pdf');
        $this->uploadPlan(9002, 501, 'beta.pdf');

        $this->assertDatabaseHas('teaching_plans', ['course_id' => 501, 'trainer_id' => 9001, 'original_name' => 'alpha.pdf']);
        $this->assertDatabaseHas('teaching_plans', ['course_id' => 501, 'trainer_id' => 9002, 'original_name' => 'beta.pdf']);
        $this->assertSame(2, DB::table('teaching_plans')->where('course_id', 501)->count());

        $beta = DB::table('teaching_plans')->where('trainer_id', 9002)->first();
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.view', [501, $beta->id]))
            ->assertNotFound();
        $this->adminRequest()
            ->get(route('admin.courses.teaching-plans.view', [501, 9001, $beta->id]))
            ->assertNotFound();
    }

    public function test_one_trainer_submission_does_not_satisfy_or_leak_to_a_co_trainer(): void
    {
        $this->uploadPlan(9001, 501, 'alpha-private-plan.pdf');
        $plan = DB::table('teaching_plans')->where('trainer_id', 9001)->first();
        $service = app(TeachingPlanService::class);

        $this->assertSame('awaiting-feedback', $service->navigationState(501, 9001)['key']);
        $this->assertSame('upload-required', $service->navigationState(501, 9002)['key']);
        $this->assertNull($service->current(501, 9002));
        $this->assertNull($service->planInTrainerContext(501, 9002, (int) $plan->id));
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 501,
            'action' => 'teaching_plan.submit',
            'detail' => 'alpha-private-plan.pdf',
            'target_id' => $plan->id,
        ]);

        $workspaceUpdates = TrainerActivityLogger::workspaceUpdates(
            501,
            collect([9001, 9002]),
            9002
        );
        $this->assertTrue($workspaceUpdates->isEmpty());

        $this->trainerRequest(9002)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertOk()
            ->assertSee('Upload Required')
            ->assertDontSee('alpha-private-plan.pdf');
    }

    public function test_trainer_can_remove_current_submission_without_feedback_and_file_is_deleted(): void
    {
        $this->uploadPlan(9001, 501, 'remove-me.pdf');
        $plan = DB::table('teaching_plans')->where('trainer_id', 9001)->first();

        $this->trainerRequest(9001)
            ->withHeader('Accept', 'text/html')
            ->delete(route('trainer.courses.teaching-plans.destroy', [501, $plan->id]))
            ->assertRedirect(route('trainer.courses.teaching-plans.index', 501))
            ->assertSessionHas('success', 'Teaching Plan submission removed.');

        $this->assertDatabaseMissing('teaching_plans', ['id' => $plan->id]);
        Storage::disk('local')->assertMissing($plan->file_path);
        $this->assertSame('upload-required', app(TeachingPlanService::class)->navigationState(501, 9001)['key']);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 501,
            'action' => 'teaching_plan.remove',
            'detail' => 'remove-me.pdf',
            'target_id' => $plan->id,
        ]);
    }

    public function test_unseen_feedback_blocks_removal_until_marked_seen_then_comments_are_deleted(): void
    {
        $this->uploadPlan(9001, 501, 'reviewed.pdf');
        $plan = DB::table('teaching_plans')->where('trainer_id', 9001)->first();
        $this->addFeedback($plan, 9001, 'Please strengthen the final activity.');

        $this->trainerRequest(9001)
            ->delete(route('trainer.courses.teaching-plans.destroy', [501, $plan->id]))
            ->assertRedirect(route('trainer.courses.teaching-plans.index', 501))
            ->assertSessionHas('error', 'Review and mark the latest Admin feedback as seen before replacing this submission.');
        $this->assertDatabaseHas('teaching_plans', ['id' => $plan->id]);
        $this->assertDatabaseHas('teaching_plan_comments', ['teaching_plan_id' => $plan->id, 'seen_by_trainer_at' => null]);
        Storage::disk('local')->assertExists($plan->file_path);

        $this->trainerRequest(9001)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('trainer.courses.teaching-plans.feedback.seen', [501, $plan->id]))
            ->assertOk()
            ->assertJsonPath('state.key', 'up-to-date');
        $this->assertNotNull(DB::table('teaching_plan_comments')->where('teaching_plan_id', $plan->id)->value('seen_by_trainer_at'));
        $this->assertSame(
            ['teaching_plan.submit'],
            DB::table('trainer_activity_log')->orderBy('log_id')->pluck('action')->all(),
            'Marking feedback as seen must not create a major Trainer activity item.'
        );

        $this->trainerRequest(9001)
            ->withHeader('Accept', 'text/html')
            ->delete(route('trainer.courses.teaching-plans.destroy', [501, $plan->id]))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('teaching_plans', ['id' => $plan->id]);
        $this->assertDatabaseMissing('teaching_plan_comments', ['teaching_plan_id' => $plan->id]);
        Storage::disk('local')->assertMissing($plan->file_path);
        $this->assertSame(
            ['teaching_plan.submit', 'teaching_plan.remove'],
            DB::table('trainer_activity_log')->orderBy('log_id')->pluck('action')->all()
        );
    }

    public function test_resubmission_after_removal_is_fresh_and_needs_admin_review(): void
    {
        $this->uploadPlan(9001, 501, 'first.pdf');
        $first = DB::table('teaching_plans')->where('trainer_id', 9001)->first();
        $this->addFeedback($first, 9001, 'Feedback attached only to the first submission.');
        $this->trainerRequest(9001)
            ->patch(route('trainer.courses.teaching-plans.feedback.seen', [501, $first->id]));
        $this->trainerRequest(9001)
            ->delete(route('trainer.courses.teaching-plans.destroy', [501, $first->id]));

        $this->uploadPlan(9001, 501, 'fresh.pdf');
        $fresh = DB::table('teaching_plans')->where('trainer_id', 9001)->first();

        $this->assertNotSame((int) $first->id, (int) $fresh->id);
        $this->assertSame('fresh.pdf', $fresh->original_name);
        $this->assertSame(0, DB::table('teaching_plan_comments')->where('teaching_plan_id', $fresh->id)->count());
        $this->assertSame('awaiting-feedback', app(TeachingPlanService::class)->navigationState(501, 9001)['key']);
        $this->assertSame('Needs Review', app(TeachingPlanService::class)->adminState(app(TeachingPlanService::class)->current(501, 9001))['label']);
    }

    public function test_remove_and_feedback_actions_reject_cross_trainer_and_cross_course_contexts(): void
    {
        $this->uploadPlan(9001, 501, 'alpha.pdf');
        $this->uploadPlan(9002, 501, 'beta.pdf');
        $alpha = DB::table('teaching_plans')->where('trainer_id', 9001)->first();
        $beta = DB::table('teaching_plans')->where('trainer_id', 9002)->first();

        $this->trainerRequest(9001)
            ->delete(route('trainer.courses.teaching-plans.destroy', [501, $beta->id]))
            ->assertNotFound();
        $this->trainerRequest(9001)
            ->delete(route('trainer.courses.teaching-plans.destroy', [502, $alpha->id]))
            ->assertNotFound();
        $this->trainerRequest(9003)
            ->delete(route('trainer.courses.teaching-plans.destroy', [501, $alpha->id]))
            ->assertForbidden();

        $this->adminRequest()->post(route('admin.courses.teaching-plans.comments.store', [501, 9002, $alpha->id]), [
            'comment' => 'Manipulated trainer context.',
        ])->assertNotFound();
        $this->adminRequest()->post(route('admin.courses.teaching-plans.comments.store', [502, 9001, $alpha->id]), [
            'comment' => 'Manipulated course context.',
        ])->assertNotFound();

        $this->assertDatabaseHas('teaching_plans', ['id' => $alpha->id]);
        $this->assertDatabaseHas('teaching_plans', ['id' => $beta->id]);
        $this->assertSame(0, DB::table('teaching_plan_comments')->count());
    }

    public function test_admin_can_view_download_and_add_multiple_comments_to_the_current_plan(): void
    {
        $this->uploadPlan(9001, 501, 'review-plan.pdf');
        $plan = DB::table('teaching_plans')->where('trainer_id', 9001)->first();

        $trainerView = $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.view', [501, $plan->id]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('inline;', (string) $trainerView->headers->get('content-disposition'));

        $adminDownload = $this->adminRequest()
            ->get(route('admin.courses.teaching-plans.download', [501, 9001, $plan->id]))
            ->assertOk();
        $this->assertStringStartsWith('attachment;', (string) $adminDownload->headers->get('content-disposition'));

        $this->addFeedback($plan, 9001, 'Clear structure and useful activities.');
        $this->addFeedback($plan, 9001, 'Please retain the reflection prompt.');

        $current = app(TeachingPlanService::class)->current(501, 9001);
        $this->assertCount(2, $current->comments);
        $this->assertSame([
            'Clear structure and useful activities.',
            'Please retain the reflection prompt.',
        ], $current->comments->pluck('comment')->all());
        $this->assertTrue($current->comments->every(fn ($comment) => $comment->seen_by_trainer_at === null));
    }

    public function test_trainer_attention_follows_upload_feedback_seen_remove_and_resubmit_lifecycle(): void
    {
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertOk()
            ->assertSee('data-teaching-plan-attention="upload-required"', false)
            ->assertSee('Upload Required')
            ->assertSee('Upload your teaching plan for Admin review and feedback.')
            ->assertDontSee('Previous Submissions')
            ->assertDontSee('Version ');

        $this->uploadPlan(9001, 501, 'attention.pdf');
        $plan = DB::table('teaching_plans')->where('trainer_id', 9001)->first();

        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertSee('Awaiting Admin Feedback')
            ->assertSee('Awaiting Admin feedback.')
            ->assertSee('Remove Submission')
            ->assertDontSee('data-teaching-plan-attention=', false)
            ->assertDontSee('Upload Revised Plan');

        $this->addFeedback($plan, 9001, 'Add a clearer assessment timeline.');
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertSee('data-teaching-plan-attention="new-feedback"', false)
            ->assertSee('New Feedback')
            ->assertSee('Mark as seen')
            ->assertSee('Review and mark the latest Admin feedback as seen before replacing this submission.')
            ->assertSee('data-teaching-plan-remove-form', false);

        $this->trainerRequest(9001)
            ->withHeaders(['Accept' => 'application/json'])
            ->patch(route('trainer.courses.teaching-plans.feedback.seen', [501, $plan->id]))
            ->assertJsonPath('state.key', 'up-to-date');
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertSee('Up to Date')
            ->assertSee('Remove Submission')
            ->assertDontSee('Mark as seen')
            ->assertDontSee('data-teaching-plan-attention=', false);

        $this->trainerRequest(9001)->delete(route('trainer.courses.teaching-plans.destroy', [501, $plan->id]));
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertSee('Upload Required')
            ->assertSee('data-teaching-plan-attention="upload-required"', false);

        $this->uploadPlan(9001, 501, 'resubmitted.pdf');
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 501))
            ->assertSee('Awaiting Admin Feedback')
            ->assertSee('resubmitted.pdf')
            ->assertDontSee('Add a clearer assessment timeline.')
            ->assertDontSee('data-teaching-plan-attention=', false);
    }

    public function test_simple_workspace_is_supported_and_unassigned_or_invalid_uploads_are_denied(): void
    {
        $this->trainerRequest(9001)
            ->get(route('trainer.courses.teaching-plans.index', 502))
            ->assertOk()
            ->assertViewIs('trainer.teaching-plan.show')
            ->assertSee('aria-label="Simple Training workspace"', false)
            ->assertSee('Teaching plan upload required');

        $this->trainerRequest(9003)->post(route('trainer.courses.teaching-plans.store', 501), [
            'teaching_plan' => UploadedFile::fake()->create('unassigned.pdf', 20, 'application/pdf'),
        ])->assertForbidden();
        $this->trainerRequest(9001)->post(route('trainer.courses.teaching-plans.store', 501), [
            'teaching_plan' => UploadedFile::fake()->create('script.exe', 20, 'application/x-msdownload'),
        ])->assertSessionHasErrors('teaching_plan');
        $this->trainerRequest(9001)->post(route('trainer.courses.teaching-plans.store', 501), [
            'teaching_plan' => UploadedFile::fake()->create('oversized.pdf', 10241, 'application/pdf'),
        ])->assertSessionHasErrors('teaching_plan');
    }

    private function uploadPlan(int $trainerId, int $courseId, string $name): void
    {
        $this->trainerRequest($trainerId)->post(route('trainer.courses.teaching-plans.store', $courseId), [
            'teaching_plan' => UploadedFile::fake()->create($name, 30, 'application/pdf'),
        ])->assertRedirect(route('trainer.courses.teaching-plans.index', $courseId));
    }

    private function addFeedback(object $plan, int $trainerId, string $comment): void
    {
        $this->adminRequest()->post(route('admin.courses.teaching-plans.comments.store', [501, $trainerId, $plan->id]), [
            'comment' => $comment,
        ])->assertSessionHas('success');
    }

    private function trainerRequest(int $trainerId)
    {
        return $this->withSession([
            'user_id' => $trainerId,
            'user_role' => 'trainer',
            'user_name' => 'Trainer Test',
        ]);
    }

    private function adminRequest()
    {
        return $this->withSession([
            'user_id' => 7001,
            'user_role' => 'admin',
            'user_name' => 'Admin Reviewer',
        ]);
    }
}
