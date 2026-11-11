<?php

namespace Tests\Feature;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class SubmissionControllerTest extends TestCase
{
    private string $originalStoragePath;

    private string $testStoragePath;

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

        $this->originalStoragePath = storage_path();
        $this->testStoragePath = sys_get_temp_dir()
            .DIRECTORY_SEPARATOR
            .'trainer-submission-test-'
            .Str::uuid();
        $this->app->useStoragePath($this->testStoragePath);

        File::ensureDirectoryExists(storage_path('app/public/submissions'));
        file_put_contents(
            storage_path('app/public/submissions/learner-work.pdf'),
            '%PDF-1.4 isolated submission test file'
        );

        $this->createTables();
        $this->seedSubmissionWorkflow();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $testStoragePath = $this->testStoragePath;
        $this->app->useStoragePath($this->originalStoragePath);

        if (
            str_starts_with($testStoragePath, sys_get_temp_dir().DIRECTORY_SEPARATOR)
            && str_contains(basename($testStoragePath), 'trainer-submission-test-')
        ) {
            File::deleteDirectory($testStoragePath);
        }

        parent::tearDown();
    }

    public function test_submission_page_renders_without_writing_shared_records(): void
    {
        $before = $this->sharedTableCounts();

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [
                'course_id' => 10,
                'assessment_id' => 40,
                'status' => 'all',
            ]))
            ->assertOk()
            ->assertSee('Submission Tasks')
            ->assertSee('Inclusive Practice Upload')
            ->assertSee('Teacher Example')
            ->assertSee('learner-work.pdf')
            ->assertSee('Need Review')
            ->assertSee('1</span> pending', false);

        $this->assertSame($before, $this->sharedTableCounts());
        $this->assertSame(0, DB::table('trainer_activity_log')->count());
    }

    public function test_unassigned_trainer_cannot_open_the_submission_page(): void
    {
        $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 8,
        ])->get(route('trainer.courses.submissions', 10))
            ->assertRedirect(route('trainer.courses'))
            ->assertSessionHas('error', 'You are not assigned to this training.');

        $this->assertSame('Submitted', DB::table('assessment_submission')->value('status'));
    }

    public function test_unassigned_trainer_cannot_review_preview_download_or_zip_submission_files(): void
    {
        $unassigned = $this->withSession(['user_role' => 'trainer', 'user_id' => 8]);

        $unassigned->get(route('trainer.courses.submissions.preview', [10, 70]))->assertForbidden();
        $unassigned->get(route('trainer.courses.submissions.download', [10, 70]))->assertForbidden();
        $unassigned->patchJson(route('trainer.courses.submissions.review', [10, 70]), [
            'review_status' => 'Reviewed',
        ])->assertForbidden();
        $unassigned->get(route('trainer.courses.submissions.zip', [10, 40]))
            ->assertRedirect(route('trainer.courses'));

        $this->assertSame('Submitted', DB::table('assessment_submission')->value('status'));
        $this->assertSame(0, DB::table('trainer_activity_log')->count());
    }

    public function test_trainer_can_mark_reviewed_and_undo_review_with_existing_status_values(): void
    {
        $mark = $this->trainerRequest()
            ->patchJson(route('trainer.courses.submissions.review', [10, 70]), [
                'review_status' => 'Reviewed',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'Reviewed',
                'message' => 'Submission marked as reviewed.',
            ]);
        $mark->assertJsonPath('pending_count', 0)
            ->assertJsonPath('counts.need_review', 0)
            ->assertJsonPath('counts.reviewed', 1);

        $reviewed = DB::table('assessment_submission')->where('submission_id', 70)->first();
        $this->assertSame('Reviewed', $reviewed->status);
        $this->assertSame('7', $reviewed->graded_by_trainer_id);
        $this->assertNotNull($reviewed->graded_at);
        $this->trainerRequest()
            ->patchJson(route('trainer.courses.submissions.review', [10, 70]), [
                'review_status' => 'Reviewed',
            ])
            ->assertOk();
        $this->assertSame(1, DB::table('trainer_activity_log')->count());

        $undo = $this->trainerRequest()
            ->patchJson(route('trainer.courses.submissions.review', [10, 70]), [
                'review_status' => 'Submitted',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'status' => 'Submitted',
                'message' => 'Review undone.',
            ]);
        $undo->assertJsonPath('pending_count', 1)
            ->assertJsonPath('counts.need_review', 1)
            ->assertJsonPath('counts.reviewed', 0);

        $submitted = DB::table('assessment_submission')->where('submission_id', 70)->first();
        $this->assertSame('Submitted', $submitted->status);
        $this->assertNull($submitted->graded_by_trainer_id);
        $this->assertNull($submitted->graded_at);
        $this->assertSame(2, DB::table('trainer_activity_log')->count());
    }

    public function test_preview_and_download_keep_inline_and_attachment_contracts(): void
    {
        $expectedPath = storage_path('app/public/submissions/learner-work.pdf');
        $expectedContents = '%PDF-1.4 isolated submission test file';
        $preview = $this->trainerRequest()
            ->get(route('trainer.courses.submissions.preview', [10, 70]))
            ->assertOk();

        $this->assertStringStartsWith(
            'inline; filename="learner-work.pdf"',
            (string) $preview->headers->get('content-disposition')
        );
        $this->assertSame('application/pdf', $preview->headers->get('content-type'));
        $this->assertSame('nosniff', $preview->headers->get('x-content-type-options'));
        $this->assertSame(realpath($expectedPath), $preview->baseResponse->getFile()->getRealPath());
        $this->assertSame($expectedContents, file_get_contents($preview->baseResponse->getFile()->getPathname()));

        $download = $this->trainerRequest()
            ->get(route('trainer.courses.submissions.download', [10, 70]))
            ->assertOk()
            ->assertDownload('learner-work.pdf');

        $this->assertSame(realpath($expectedPath), $download->baseResponse->getFile()->getRealPath());
        $this->assertSame($expectedContents, file_get_contents($download->baseResponse->getFile()->getPathname()));

        $cacheControl = (string) $download->headers->get('cache-control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertSame(0, DB::table('trainer_activity_log')->count());
    }

    public function test_all_file_actions_normalize_the_same_public_disk_path_prefix(): void
    {
        DB::table('assessment_submission')->where('submission_id', 70)->update([
            'submitted_file_path' => 'public/submissions/learner-work.pdf',
        ]);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [10, 'assessment_id' => 40]))
            ->assertOk()
            ->assertSee('title="Preview learner-work.pdf"', false);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions.preview', [10, 70]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions.download', [10, 70]))
            ->assertOk()
            ->assertDownload('learner-work.pdf');

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions.zip', [10, 40]))
            ->assertOk();
    }

    public function test_download_uses_a_safe_display_filename_without_exposing_storage_path(): void
    {
        DB::table('assessment_submission')->where('submission_id', 70)->update([
            'original_file_name' => '../../learner-work.pdf',
        ]);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions.download', [10, 70]))
            ->assertOk()
            ->assertDownload('learner-work.pdf');
    }

    public function test_missing_submission_file_keeps_the_existing_not_found_response(): void
    {
        DB::table('assessment_submission')
            ->where('submission_id', 70)
            ->update(['submitted_file_path' => 'submissions/missing.pdf']);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions.preview', [10, 70]))
            ->assertNotFound();

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions.download', [10, 70]))
            ->assertNotFound();

        $this->trainerRequest()
            ->from(route('trainer.courses.submissions', 10))
            ->get(route('trainer.courses.submissions.zip', [10, 40]))
            ->assertRedirect(route('trainer.courses.submissions', 10))
            ->assertSessionHas('error', 'No actual files exist on disk for these submissions. The records exist but the uploads may have been removed.');
    }

    public function test_zip_download_uses_existing_submission_files_and_name_pattern(): void
    {
        $response = $this->trainerRequest()
            ->get(route('trainer.courses.submissions.zip', [10, 40]))
            ->assertOk();

        $disposition = (string) $response->headers->get('content-disposition');
        $this->assertStringContainsString(
            'inclusive-practice-upload_submissions_',
            $disposition
        );
        $this->assertStringContainsString('.zip', $disposition);
        $this->assertSame(0, DB::table('trainer_activity_log')->count());
    }

    public function test_reporting_filters_and_exact_server_page_sizes_preserve_task_wide_counts(): void
    {
        $this->addParticipants(29);
        DB::table('assessment_submission')->insert([
            'submission_id' => 71,
            'assessment_id' => 40,
            'enrollment_id' => 61,
            'submitted_file_path' => null,
            'original_file_name' => null,
            'submitted_at' => now()->subMinutes(30),
            'status' => 'Reviewed',
            'graded_by_trainer_id' => 7,
            'graded_at' => now()->subMinutes(10),
            'created_at' => now()->subMinutes(30),
            'updated_at' => now()->subMinutes(10),
        ]);

        foreach ([10, 25, 50] as $rows) {
            $this->trainerRequest()
                ->get(route('trainer.courses.submissions', [
                    'course_id' => 10,
                    'assessment_id' => 40,
                    'rows' => $rows,
                ]))
                ->assertOk()
                ->assertViewHas('submissionPaginator', function ($paginator) use ($rows): bool {
                    return $paginator->perPage() === $rows && $paginator->total() === 30;
                });
        }

        $filtered = $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [
                'course_id' => 10,
                'assessment_id' => 40,
                'search' => 'teacher12@example.test',
                'status' => 'awaiting',
            ]))
            ->assertOk()
            ->assertSee('Teacher 12')
            ->assertDontSee('Teacher Example');

        $filtered->assertViewHas('submissionPaginator', fn ($paginator): bool => $paginator->total() === 1)
            ->assertViewHas('taskCounts', fn (array $counts): bool => $counts === [
                'all' => 30,
                'need_review' => 1,
                'reviewed' => 1,
                'awaiting' => 28,
                'late' => 0,
            ]);

        foreach (['need_review' => 1, 'reviewed' => 1] as $status => $expectedTotal) {
            $this->trainerRequest()
                ->get(route('trainer.courses.submissions', [
                    'course_id' => 10,
                    'assessment_id' => 40,
                    'status' => $status,
                ]))
                ->assertOk()
                ->assertViewHas('submissionPaginator', fn ($paginator): bool => $paginator->total() === $expectedTotal);
        }

        DB::table('assessment')->where('assessment_id', 40)->update(['available_until' => now()->subHours(2)]);
        $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [
                'course_id' => 10,
                'assessment_id' => 40,
                'status' => 'late',
            ]))
            ->assertOk()
            ->assertViewHas('submissionPaginator', fn ($paginator): bool => $paginator->total() === 2)
            ->assertViewHas('taskCounts', fn (array $counts): bool => $counts['late'] === 2);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [
                'course_id' => 10,
                'assessment_id' => 40,
                'rows' => 10,
                'page' => 3,
            ]))
            ->assertOk()
            ->assertViewHas('submissionPaginator', fn ($paginator): bool => $paginator->currentPage() === 3
                && $paginator->count() === 10
                && $paginator->lastPage() === 3);
    }

    public function test_task_display_states_use_open_and_due_boundaries_without_writes(): void
    {
        Carbon::setTestNow('2026-08-13 12:00:00');
        DB::table('assessment')->insert([
            [
                'assessment_id' => 41,
                'course_id' => 10,
                'module_id' => 30,
                'title' => 'Future Upload',
                'type' => 'submission',
                'available_from' => '2026-08-13 12:00:01',
                'available_until' => '2026-08-14 12:00:00',
                'created_at' => '2026-08-13 10:00:00',
            ],
            [
                'assessment_id' => 42,
                'course_id' => 10,
                'module_id' => 30,
                'title' => 'Closed Upload',
                'type' => 'submission',
                'available_from' => '2026-08-10 12:00:00',
                'available_until' => '2026-08-13 11:59:59',
                'created_at' => '2026-08-13 09:00:00',
            ],
            [
                'assessment_id' => 43,
                'course_id' => 10,
                'module_id' => 30,
                'title' => 'Boundary Upload',
                'type' => 'submission',
                'available_from' => '2026-08-13 12:00:00',
                'available_until' => '2026-08-13 12:00:00',
                'created_at' => '2026-08-13 08:00:00',
            ],
        ]);
        $before = $this->sharedTableCounts();

        $response = $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [10, 'assessment_id' => 41]))
            ->assertOk();

        $states = $response->viewData('submissionTasks')->keyBy('assessment_id');
        $this->assertSame('Not Open Yet', $states[41]->display_state);
        $this->assertSame('is-not-open', $states[41]->display_state_class);
        $this->assertSame('bi-clock', $states[41]->display_state_icon);
        $this->assertSame('Opens 13 Aug 2026, 12:00 PM.', $states[41]->display_state_message);
        $this->assertSame('Closed', $states[42]->display_state);
        $this->assertSame('is-closed', $states[42]->display_state_class);
        $this->assertSame('bi-lock-fill', $states[42]->display_state_icon);
        $this->assertSame('Submission period closed on 13 Aug 2026, 11:59 AM.', $states[42]->display_state_message);
        $this->assertSame('Open', $states[43]->display_state);
        $this->assertSame('is-open', $states[43]->display_state_class);
        $this->assertSame('bi-check-circle-fill', $states[43]->display_state_icon);
        $this->assertSame('Open for submissions until 13 Aug 2026, 12:00 PM.', $states[43]->display_state_message);
        $this->assertSame($before, $this->sharedTableCounts());
    }

    public function test_foreign_task_submission_and_file_routes_cannot_leak(): void
    {
        $this->seedForeignSubmission();
        File::put(storage_path('app/public/submissions/foreign-work.pdf'), '%PDF foreign');
        DB::table('assessment_submission')->insert([
            'submission_id' => 171,
            'assessment_id' => 40,
            'enrollment_id' => 160,
            'submitted_file_path' => 'submissions/foreign-work.pdf',
            'original_file_name' => 'foreign-work.pdf',
            'submitted_at' => now(),
            'status' => 'Submitted',
        ]);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [
                'course_id' => 10,
                'assessment_id' => 140,
                'search' => 'Foreign Participant',
            ]))
            ->assertOk()
            ->assertSee('No submission task selected')
            ->assertDontSee('foreign-work.pdf');

        $this->trainerRequest()->get(route('trainer.courses.submissions.preview', [10, 170]))->assertNotFound();
        $this->trainerRequest()->get(route('trainer.courses.submissions.download', [10, 170]))->assertNotFound();
        $this->trainerRequest()
            ->patchJson(route('trainer.courses.submissions.review', [10, 170]), ['review_status' => 'Reviewed'])
            ->assertNotFound();
        $this->assertSame('Submitted', DB::table('assessment_submission')->where('submission_id', 170)->value('status'));

        $zipResponse = $this->trainerRequest()->get(route('trainer.courses.submissions.zip', [10, 40]))->assertOk();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipResponse->baseResponse->getFile()->getPathname()) === true);
        $this->assertSame(1, $zip->numFiles);
        $this->assertStringNotContainsString('foreign', $zip->getNameIndex(0));
        $zip->close();
    }

    public function test_missing_file_is_explained_in_roster_and_not_offered_for_preview(): void
    {
        DB::table('assessment_submission')->where('submission_id', 70)->update([
            'submitted_file_path' => 'submissions/missing.pdf',
            'original_file_name' => 'missing.pdf',
        ]);

        $this->trainerRequest()
            ->get(route('trainer.courses.submissions', [10, 'assessment_id' => 40]))
            ->assertOk()
            ->assertSee('File unavailable')
            ->assertSee('This submission file could not be found in storage.', false)
            ->assertDontSee('title="Preview missing.pdf"', false)
            ->assertSee('No submission files are currently available in storage.', false);
    }

    public function test_zip_includes_only_available_scoped_files_and_keeps_duplicate_names_unique(): void
    {
        $this->addParticipants(3, 'Same Participant');
        File::put(storage_path('app/public/submissions/second.pdf'), '%PDF second');
        DB::table('assessment_submission')->insert([
            [
                'submission_id' => 71,
                'assessment_id' => 40,
                'enrollment_id' => 61,
                'submitted_file_path' => 'submissions/second.pdf',
                'original_file_name' => 'learner-work.pdf',
                'submitted_at' => now(),
                'status' => 'Submitted',
            ],
            [
                'submission_id' => 72,
                'assessment_id' => 40,
                'enrollment_id' => 62,
                'submitted_file_path' => 'submissions/missing.pdf',
                'original_file_name' => 'missing.pdf',
                'submitted_at' => now(),
                'status' => 'Submitted',
            ],
            [
                'submission_id' => 73,
                'assessment_id' => 40,
                'enrollment_id' => 63,
                'submitted_file_path' => 'submissions/learner-work.pdf',
                'original_file_name' => 'awaiting-file.pdf',
                'submitted_at' => null,
                'status' => null,
            ],
        ]);

        DB::table('guru_new')->where('gn_id', 50)->update(['gn_name' => 'Same Participant']);
        $response = $this->trainerRequest()->get(route('trainer.courses.submissions.zip', [10, 40]))->assertOk();
        $archivePath = $response->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($archivePath) === true);
        $this->assertSame(2, $zip->numFiles);
        $entries = collect(range(0, $zip->numFiles - 1))->map(fn (int $index): string => $zip->getNameIndex($index));
        $this->assertSame(2, $entries->unique()->count());
        $this->assertFalse($entries->contains(fn (string $entry): bool => str_contains($entry, 'missing')));
        $zip->close();
    }

    public function test_pending_badge_contract_is_shared_by_all_six_lms_tabs(): void
    {
        $course = DB::table('course')
            ->where('courseID', '10')
            ->select('courseID as course_id', 'courseName as course_name', 'courseCategory as course_category', 'courseType as training_mode', 'targetAudience as target_participant')
            ->first();
        foreach (['overview', 'materials', 'submissions', 'learner-tracking', 'attendance', 'participants'] as $tab) {
            $html = view('trainer.training.partials.workspace-header', [
                'course' => $course,
                'activeLmsTab' => $tab,
            ])->render();
            $this->assertStringContainsString('data-lms-submissions-pending', $html);
            $this->assertStringContainsString('1</span> pending', $html);
        }
    }

    public function test_async_fragments_are_read_only_and_narrow(): void
    {
        $before = $this->sharedTableCounts();
        $this->trainerRequest()
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->get(route('trainer.courses.submissions', [
                'course_id' => 10,
                'assessment_id' => 40,
                'fragment' => 'results',
            ]))
            ->assertOk()
            ->assertSee('lmsSubmissionRosterResults', false)
            ->assertDontSee('data-lms-workspace-header', false);
        $this->assertSame($before, $this->sharedTableCounts());
    }

    private function trainerRequest()
    {
        return $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 7,
            'user_name' => 'Trainer Test',
        ]);
    }

    private function createTables(): void
    {
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->decimal('price')->nullable();
            $table->string('status')->nullable();
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

        Schema::create('training_module', function (Blueprint $table): void {
            $table->integer('module_id')->primary();
            $table->string('course_id', 20);
            $table->string('module_title');
        });

        Schema::create('assessment', function (Blueprint $table): void {
            $table->integer('assessment_id')->primary();
            $table->string('course_id', 20);
            $table->integer('module_id')->nullable();
            $table->string('title');
            $table->string('type');
            $table->text('instruction_text')->nullable();
            $table->string('accepted_format')->nullable();
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->timestamp('created_at')->nullable();
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
            $table->string('teacherName');
            $table->string('email')->nullable();
        });

        Schema::create('guru_new', function (Blueprint $table): void {
            $table->integer('gn_id')->primary();
            $table->string('gn_name')->nullable();
            $table->string('email')->nullable();
        });

        Schema::create('outsider', function (Blueprint $table): void {
            $table->integer('outsider_id')->primary();
            $table->string('name')->nullable();
        });

        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->integer('submission_id')->primary();
            $table->integer('assessment_id');
            $table->integer('enrollment_id');
            $table->string('submitted_file_path')->nullable();
            $table->string('original_file_name')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('status')->nullable();
            $table->string('graded_by_trainer_id', 20)->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
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
    }

    private function seedSubmissionWorkflow(): void
    {
        DB::table('course')->insert([
            'courseID' => '10',
            'courseName' => 'Inclusive Practice',
            'courseCategory' => 'Teacher Training',
            'courseType' => 'lms',
            'targetAudience' => 'New Teacher',
        ]);
        DB::table('course_session')->insert([
            'sessionID' => '20',
            'courseID' => '10',
        ]);
        DB::table('session_trainer')->insert([
            'sessionID' => '20',
            'trainerID' => '7',
        ]);
        DB::table('training_module')->insert([
            'module_id' => 30,
            'course_id' => '10',
            'module_title' => 'Evidence',
        ]);
        DB::table('assessment')->insert([
            'assessment_id' => 40,
            'course_id' => '10',
            'module_id' => 30,
            'title' => 'Inclusive Practice Upload',
            'type' => 'submission',
            'instruction_text' => 'Upload your completed reflection.',
            'accepted_format' => 'PDF',
            'available_from' => now()->subDays(2),
            'available_until' => now()->addDay(),
            'created_at' => now()->subDays(2),
        ]);
        DB::table('guru_new')->insert([
            'gn_id' => 50,
            'gn_name' => 'Teacher Example',
            'email' => 'teacher@example.test',
        ]);
        DB::table('enroll_guru_baru')->insert([
            'enrollment_id' => 60,
            'course_id' => '10',
            'gn_id' => 50,
            'status' => 'approved',
            'applied_at' => now(),
        ]);
        DB::table('assessment_submission')->insert([
            'submission_id' => 70,
            'assessment_id' => 40,
            'enrollment_id' => 60,
            'submitted_file_path' => 'submissions/learner-work.pdf',
            'original_file_name' => 'learner-work.pdf',
            'submitted_at' => now()->subHour(),
            'status' => 'Submitted',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
    }

    private function addParticipants(int $count, ?string $fixedName = null): void
    {
        for ($index = 1; $index <= $count; $index++) {
            $teacherId = 50 + $index;
            $enrollmentId = 60 + $index;
            DB::table('guru_new')->insert([
                'gn_id' => $teacherId,
                'gn_name' => $fixedName ?: sprintf('Teacher %02d', $index + 1),
                'email' => sprintf('teacher%d@example.test', $index + 1),
            ]);
            DB::table('enroll_guru_baru')->insert([
                'enrollment_id' => $enrollmentId,
                'course_id' => '10',
                'gn_id' => $teacherId,
                'status' => 'approved',
                'applied_at' => now(),
            ]);
        }
    }

    private function seedForeignSubmission(): void
    {
        DB::table('course')->insert(['courseID' => '11', 'courseName' => 'Foreign Course']);
        DB::table('training_module')->insert(['module_id' => 130, 'course_id' => '11', 'module_title' => 'Foreign Module']);
        DB::table('assessment')->insert([
            'assessment_id' => 140,
            'course_id' => '11',
            'module_id' => 130,
            'title' => 'Foreign Upload',
            'type' => 'submission',
            'available_from' => now()->subDay(),
            'available_until' => now()->addDay(),
            'created_at' => now(),
        ]);
        DB::table('guru_new')->insert(['gn_id' => 150, 'gn_name' => 'Foreign Participant', 'email' => 'foreign@example.test']);
        DB::table('enroll_guru_baru')->insert([
            'enrollment_id' => 160,
            'course_id' => '11',
            'gn_id' => 150,
            'status' => 'approved',
            'applied_at' => now(),
        ]);
        DB::table('assessment_submission')->insert([
            'submission_id' => 170,
            'assessment_id' => 140,
            'enrollment_id' => 160,
            'submitted_file_path' => 'submissions/foreign-work.pdf',
            'original_file_name' => 'foreign-work.pdf',
            'submitted_at' => now(),
            'status' => 'Submitted',
        ]);
    }

    private function sharedTableCounts(): array
    {
        return collect([
            'course',
            'course_session',
            'session_trainer',
            'training_module',
            'assessment',
            'enroll_guru_baru',
            'teacher',
            'guru_new',
            'outsider',
            'assessment_submission',
        ])->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->count(),
        ])->all();
    }
}
