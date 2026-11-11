<?php

namespace Tests\Feature;

use App\Services\Lms\TrainerLmsMaterialAuthoringService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthoringWorkspaceContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->dropTemporaryTables();
        $this->createTemporaryTables();

        DB::table('course_session')->insert([
            ['sessionID' => '101', 'courseID' => '1'],
            ['sessionID' => '202', 'courseID' => '2'],
        ]);
        DB::table('session_trainer')->insert([
            'sessionID' => '101',
            'trainerID' => '9001',
        ]);
        DB::table('training_module')->insert([
            [
                'module_id' => 10,
                'course_id' => '1',
                'module_title' => 'Course One Module',
                'module_type' => 'lesson',
                'module_order' => 1,
            ],
            [
                'module_id' => 20,
                'course_id' => '2',
                'module_title' => 'Course Two Module',
                'module_type' => 'lesson',
                'module_order' => 1,
            ],
        ]);
        DB::table('assessment')->insert([
            'assessment_id' => 30,
            'course_id' => '1',
            'module_id' => null,
            'title' => 'Course One Quiz',
            'type' => 'quiz',
            'status' => 'Active',
        ]);
    }

    protected function tearDown(): void
    {
        try {
            $this->dropTemporaryTables();
        } finally {
            parent::tearDown();
        }
    }

    public function test_obsolete_block_routes_are_absent_and_replacement_route_is_registered(): void
    {
        $this->assertFalse(Route::has('trainer.courses.blocks.text.store'));
        $this->assertFalse(Route::has('trainer.courses.blocks.update'));
        $this->assertTrue(Route::has('trainer.courses.blocks.file.replace'));
    }

    public function test_assessment_cannot_be_attached_to_a_module_from_another_course(): void
    {
        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/20/blocks/assessment', [
                'assessment_id' => 30,
            ]);

        $response
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'message' => 'Module not found.',
            ]);
        $this->assertSame(0, DB::table('training_content_block')->count());
        $this->assertNull(
            DB::table('assessment')
                ->where('assessment_id', 30)
                ->value('module_id')
        );
    }

    public function test_assessment_can_only_be_embedded_once(): void
    {
        DB::table('training_content_block')->insert([
            'block_id' => 40,
            'module_id' => 10,
            'block_type' => 'quiz',
            'block_title' => 'Course One Quiz',
            'assessment_id' => 30,
            'block_order' => 1,
            'is_visible' => true,
        ]);
        DB::table('assessment')
            ->where('assessment_id', 30)
            ->update(['module_id' => 10]);

        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/assessment', [
                'assessment_id' => 30,
            ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'This assessment is already attached to a module. Edit the existing assessment block instead.',
            ]);
        $this->assertSame(
            1,
            DB::table('training_content_block')
                ->where('assessment_id', 30)
                ->count()
        );
    }

    public function test_update_rejects_a_question_image_path_not_owned_by_the_assessment(): void
    {
        DB::table('assessment_question')->insert([
            'question_id' => 50,
            'assessment_id' => 30,
            'question_text' => 'Original question',
            'question_image_path' => 'assessment-question-images/1/original.png',
            'question_order' => 1,
            'question_type' => 'mcq',
            'options_json' => '["A","B"]',
            'correct_answer' => 'A',
            'marks' => 1,
        ]);

        $response = $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', [
                'title' => 'Updated Quiz',
                'type' => 'quiz',
                'total_marks' => 1,
                'questions' => [[
                    'question_text' => 'Updated question',
                    'question_type' => 'mcq',
                    'options_json' => '["A","B"]',
                    'correct_answer' => 'A',
                    'correct_answers_json' => '[]',
                    'marks' => 1,
                    'question_order' => 1,
                    'existing_question_image_path' => 'assessment-question-images/1/another-assessment.png',
                ]],
            ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'A question image reference is out of date. Refresh the page before retrying.',
            ]);
        $this->assertSame(
            'Course One Quiz',
            DB::table('assessment')
                ->where('assessment_id', 30)
                ->value('title')
        );
        $this->assertSame(
            'Original question',
            DB::table('assessment_question')
                ->where('question_id', 50)
                ->value('question_text')
        );
    }

    public function test_file_replacement_updates_the_material_atomically_and_removes_the_old_file(): void
    {
        Storage::fake('public');
        $oldPath = 'training-materials/1/original.pdf';
        Storage::disk('public')->put($oldPath, 'original file');

        DB::table('learning_material')->insert([
            'material_id' => 60,
            'course_id' => 1,
            'trainer_id' => 9001,
            'original_file_name' => 'original.pdf',
            'file_path' => $oldPath,
            'material_type' => 'lesson',
            'ai_generated_title' => 'Reference notes',
            'ai_synopsis' => 'Original description',
        ]);
        DB::table('training_content_block')->insert([
            'block_id' => 61,
            'module_id' => 10,
            'block_type' => 'material',
            'block_title' => 'Reference notes',
            'block_content' => 'Original description',
            'material_id' => 60,
            'block_order' => 1,
            'is_visible' => true,
        ]);

        $response = $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/blocks/61/file', [
                'material_file' => UploadedFile::fake()->create(
                    'replacement.pdf',
                    10,
                    'application/pdf'
                ),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('block_id', 61);
        $newPath = DB::table('learning_material')
            ->where('material_id', 60)
            ->value('file_path');
        $this->assertNotSame($oldPath, $newPath);
        $this->assertStringStartsWith('training-materials/1/', $newPath);
        $this->assertSame(
            'replacement.pdf',
            DB::table('learning_material')
                ->where('material_id', 60)
                ->value('original_file_name')
        );
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 1,
            'action' => 'material.replace',
            'detail' => 'replacement.pdf',
            'target_id' => 60,
        ]);
    }

    public function test_material_upload_returns_a_rendered_block_for_the_open_workspace(): void
    {
        Storage::fake('public');

        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/material', [
                'material_title' => 'Trainer guide',
                'material_description' => 'Use during the workshop.',
                'material_file' => UploadedFile::fake()->create(
                    'trainer-guide.pdf',
                    10,
                    'application/pdf'
                ),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('module_id', 10)
            ->assertJsonStructure(['block_id', 'block_html']);
        $this->assertSame(
            'Trainer guide',
            DB::table('training_content_block')->value('block_title')
        );
        $materialPath = DB::table('learning_material')->value('file_path');
        $this->assertStringStartsWith('training-materials/1/', $materialPath);
        Storage::disk('public')->assertExists($materialPath);
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 1,
            'action' => 'material.upload',
            'detail' => 'trainer-guide.pdf',
        ]);
        $this->assertSame(1, DB::table('trainer_activity_log')->count());
    }

    public function test_material_metadata_edit_records_one_named_material_update(): void
    {
        DB::table('learning_material')->insert([
            'material_id' => 60,
            'course_id' => 1,
            'trainer_id' => 9001,
            'original_file_name' => 'original-guide.pdf',
            'file_path' => 'training-materials/1/original-guide.pdf',
            'material_type' => 'PDF',
            'ai_generated_title' => 'Original guide',
            'ai_synopsis' => 'Original description',
        ]);
        DB::table('training_content_block')->insert([
            'block_id' => 61,
            'module_id' => 10,
            'block_type' => 'material',
            'block_title' => 'Original guide',
            'block_content' => 'Original description',
            'material_id' => 60,
            'block_order' => 1,
            'is_visible' => true,
        ]);

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/modules/10/save-blocks', [
                'module_title' => 'Course One Module',
                'blocks' => [[
                    'block_id' => 61,
                    'block_type' => 'material',
                    'block_title' => 'Renamed trainer guide',
                    'block_content' => 'Updated description',
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 1,
            'action' => 'material.update',
            'detail' => 'Renamed trainer guide',
            'target_id' => 60,
        ]);
        $this->assertSame(1, DB::table('trainer_activity_log')->count());
    }

    public function test_removing_a_question_image_cleans_up_the_unreferenced_local_file(): void
    {
        Storage::fake('public');
        $imagePath = 'assessment-question-images/1/original.png';
        Storage::disk('public')->put($imagePath, 'synthetic image');

        DB::table('assessment_question')->insert([
            'question_id' => 50,
            'assessment_id' => 30,
            'question_text' => 'Original question',
            'question_image_path' => $imagePath,
            'question_order' => 1,
            'question_type' => 'mcq',
            'options_json' => '["A","B"]',
            'correct_answer' => 'A',
            'marks' => 1,
        ]);

        $response = $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', [
                'title' => 'Updated Quiz',
                'type' => 'quiz',
                'total_marks' => 1,
                'questions' => [[
                    'question_text' => 'Updated question',
                    'question_type' => 'mcq',
                    'options_json' => '["A","B"]',
                    'correct_answer' => 'A',
                    'correct_answers_json' => '[]',
                    'marks' => 1,
                    'question_order' => 1,
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('assessment.title', 'Updated Quiz');
        $this->assertNull(
            DB::table('assessment_question')
                ->where('assessment_id', 30)
                ->value('question_image_path')
        );
        Storage::disk('public')->assertMissing($imagePath);
    }

    public function test_module_card_count_and_builder_collection_share_the_canonical_block_definition(): void
    {
        DB::table('training_content_block')->insert([
            [
                'block_id' => 70,
                'module_id' => 10,
                'block_type' => 'title',
                'block_title' => 'Visible title',
                'block_content' => null,
                'assessment_id' => null,
                'block_order' => 1,
                'is_visible' => true,
            ],
            [
                'block_id' => 71,
                'module_id' => 10,
                'block_type' => 'quiz',
                'block_title' => 'Visible quiz',
                'block_content' => null,
                'assessment_id' => 30,
                'block_order' => 2,
                'is_visible' => true,
            ],
            [
                'block_id' => 72,
                'module_id' => 10,
                'block_type' => 'assessment',
                'block_title' => 'Legacy assessment wrapper',
                'block_content' => null,
                'assessment_id' => 30,
                'block_order' => 3,
                'is_visible' => true,
            ],
            [
                'block_id' => 73,
                'module_id' => 10,
                'block_type' => 'external_link',
                'block_title' => 'Legacy link',
                'block_content' => null,
                'assessment_id' => null,
                'block_order' => 4,
                'is_visible' => true,
            ],
            [
                'block_id' => 74,
                'module_id' => 10,
                'block_type' => 'text',
                'block_title' => null,
                'block_content' => 'Hidden content',
                'assessment_id' => null,
                'block_order' => 5,
                'is_visible' => false,
            ],
        ]);

        $service = app(TrainerLmsMaterialAuthoringService::class);
        $builderBlocks = $service->blocksForModules(collect([10]));

        $this->assertSame(2, $service->visibleBlockCount(10));
        $this->assertCount(2, $builderBlocks);
        $this->assertSame(['title', 'quiz'], $builderBlocks->pluck('block_type')->all());
        $this->assertSame(0, $service->visibleBlockCount(20));
    }

    public function test_repeated_module_save_is_idempotent_and_persists_dom_order(): void
    {
        DB::table('training_content_block')->insert([
            [
                'block_id' => 80,
                'module_id' => 10,
                'block_type' => 'title',
                'block_title' => 'First',
                'block_content' => null,
                'block_order' => 1,
                'is_visible' => true,
            ],
            [
                'block_id' => 81,
                'module_id' => 10,
                'block_type' => 'text',
                'block_title' => null,
                'block_content' => 'Second',
                'block_order' => 2,
                'is_visible' => true,
            ],
        ]);
        $payload = [
            'module_title' => 'Course One Module',
            'blocks' => [
                [
                    'block_id' => 81,
                    'block_type' => 'text',
                    'block_content' => 'Second',
                ],
                [
                    'block_id' => 80,
                    'block_type' => 'title',
                    'block_title' => 'First',
                ],
            ],
        ];

        foreach ([1, 2] as $attempt) {
            $this->withSession($this->trainerSession())
                ->patchJson('/trainer/courses/1/modules/10/save-blocks', $payload)
                ->assertOk()
                ->assertJsonPath('block_count', 2);
            $this->assertSame(
                [81, 80],
                DB::table('training_content_block')
                    ->where('module_id', 10)
                    ->orderBy('block_order')
                    ->pluck('block_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all(),
                'Unexpected block order after save attempt '.$attempt
            );
            $this->assertSame(2, DB::table('training_content_block')->where('module_id', 10)->count());
        }
    }

    public function test_youtube_blocks_accept_only_supported_youtube_hosts_and_append(): void
    {
        DB::table('training_content_block')->insert([
            'block_id' => 90,
            'module_id' => 10,
            'block_type' => 'text',
            'block_content' => 'Existing block',
            'block_order' => 8,
            'is_visible' => true,
        ]);

        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/youtube', [
                'youtube_title' => 'Teaching video',
                'youtube_url' => 'https://www.youtube.com/watch?v=abcdefghijk',
            ])
            ->assertOk()
            ->assertJsonPath('block_count', 2);
        $youtube = DB::table('training_content_block')->where('block_type', 'youtube')->first();
        $this->assertSame('https://www.youtube.com/embed/abcdefghijk', $youtube->external_url);
        $this->assertSame(9, (int) $youtube->block_order);

        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/youtube', [
                'youtube_title' => 'Unsafe video',
                'youtube_url' => 'https://example.com/youtube.com/watch?v=abcdefghijk',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->assertSame(1, DB::table('training_content_block')->where('block_type', 'youtube')->count());
    }

    public function test_all_builder_block_types_append_reorder_reload_and_repeated_save_without_duplicates(): void
    {
        Storage::fake('public');
        DB::table('assessment')->insert([
            'assessment_id' => 31,
            'course_id' => 1,
            'module_id' => null,
            'title' => 'Course One Submission',
            'type' => 'submission',
            'instruction_text' => 'Upload the reflection.',
            'available_from' => '2026-08-20 09:00:00',
            'available_until' => '2026-08-21 09:00:00',
            'accepted_format' => 'PDF Only',
            'status' => 'Active',
        ]);

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/modules/10/save-blocks', [
                'module_title' => 'Course One Module',
                'blocks' => [
                    ['block_type' => 'title', 'block_title' => 'Introduction'],
                    ['block_type' => 'text', 'block_content' => 'Read this first.'],
                ],
            ])->assertOk()->assertJsonPath('block_count', 2);
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/material', [
                'material_title' => 'Guide',
                'material_file' => UploadedFile::fake()->create('guide.pdf', 10, 'application/pdf'),
                'block_order' => 5,
            ])->assertOk()->assertJsonPath('block_count', 3);
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/youtube', [
                'youtube_title' => 'Video',
                'youtube_url' => 'https://youtu.be/abcdefghijk',
                'block_order' => 4,
            ])->assertOk()->assertJsonPath('block_count', 4);
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/media', [
                'material_title' => 'Diagram',
                'material_file' => UploadedFile::fake()->create('diagram.png', 10, 'image/png'),
                'block_order' => 3,
            ])->assertOk()->assertJsonPath('block_count', 5);
        foreach ([30 => 6, 31 => 7] as $assessmentId => $blockOrder) {
            $this->withSession($this->trainerSession())
                ->postJson('/trainer/courses/1/modules/10/blocks/assessment', [
                    'assessment_id' => $assessmentId,
                    'block_order' => $blockOrder,
                ])->assertOk();
        }

        $blocks = DB::table('training_content_block')
            ->where('module_id', 10)
            ->orderBy('block_order')
            ->get();
        $this->assertSame(
            ['title', 'text', 'media', 'youtube', 'material', 'quiz', 'submission'],
            $blocks->pluck('block_type')->all()
        );

        $reordered = $blocks->reverse()->values();
        $payload = [
            'module_title' => 'Course One Module',
            'blocks' => $reordered->map(static fn ($block): array => [
                'block_id' => $block->block_id,
                'block_type' => $block->block_type,
                'block_title' => $block->block_title,
                'block_content' => $block->block_content,
                'external_url' => $block->external_url,
            ])->all(),
        ];

        foreach ([1, 2] as $attempt) {
            $this->withSession($this->trainerSession())
                ->patchJson('/trainer/courses/1/modules/10/save-blocks', $payload)
                ->assertOk()
                ->assertJsonPath('block_count', 7);
            $this->assertSame(
                $reordered->pluck('block_id')->map(static fn ($id): int => (int) $id)->all(),
                DB::table('training_content_block')
                    ->where('module_id', 10)
                    ->orderBy('block_order')
                    ->pluck('block_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all(),
                'Order changed after repeated save attempt '.$attempt
            );
            $this->assertSame(7, DB::table('training_content_block')->where('module_id', 10)->count());
            $this->assertSame(2, DB::table('training_content_block')->whereNotNull('assessment_id')->count());
        }

        $submissionBlock = $reordered->first();
        $this->assertSame('submission', $submissionBlock->block_type);
        $this->withSession($this->trainerSession())
            ->deleteJson('/trainer/courses/1/blocks/'.$submissionBlock->block_id)
            ->assertOk()
            ->assertJsonPath('remaining_blocks', 6);
        $this->assertSame(
            [1, 2, 3, 4, 5, 6],
            DB::table('training_content_block')
                ->where('module_id', 10)
                ->whereIn('block_type', TrainerLmsMaterialAuthoringService::BUILDER_BLOCK_TYPES)
                ->orderBy('block_order')
                ->pluck('block_order')
                ->map(static fn ($order): int => (int) $order)
                ->all()
        );
        $this->assertTrue(DB::table('assessment')->where('assessment_id', 31)->exists());
        $this->assertNull(DB::table('assessment')->where('assessment_id', 31)->value('module_id'));
    }

    public function test_upload_validation_rejects_unsupported_file_and_media_types(): void
    {
        Storage::fake('public');

        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/material', [
                'material_file' => UploadedFile::fake()->create('unsafe.exe', 10),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/media', [
                'material_file' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);

        $this->assertSame(0, DB::table('learning_material')->count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_submission_window_must_close_after_it_opens(): void
    {
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', [
                'title' => 'Invalid window',
                'type' => 'submission',
                'instruction_text' => 'Upload your file.',
                'available_from' => '2026-08-20 09:00:00',
                'available_until' => '2026-08-20 09:00:00',
                'accepted_format' => 'PDF Only',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['available_until']);
        $this->assertSame(1, DB::table('assessment')->count());
    }

    public function test_module_reorder_is_course_scoped_and_persists_normalized_order(): void
    {
        DB::table('training_module')->insert([
            'module_id' => 11,
            'course_id' => 1,
            'module_title' => 'Course One Second Module',
            'module_type' => 'lesson',
            'module_order' => 9,
        ]);

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/modules/reorder', [
                'module_ids' => [11, 10],
            ])
            ->assertOk()
            ->assertJsonPath('module_ids', [11, 10]);
        $this->assertSame(
            [11, 10],
            DB::table('training_module')
                ->where('course_id', 1)
                ->orderBy('module_order')
                ->pluck('module_id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
        );

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/modules/reorder', [
                'module_ids' => [11, 10, 20],
            ])
            ->assertStatus(409);
        $this->assertSame(1, (int) DB::table('training_module')->where('module_id', 20)->value('module_order'));
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 1,
            'action' => 'module.reorder',
            'detail' => '2 modules reordered',
            'target_id' => 1,
        ]);
        $this->assertSame(1, DB::table('trainer_activity_log')->count());
    }

    public function test_module_creation_trims_title_rejects_blank_and_starts_with_zero_blocks(): void
    {
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules', ['module_title' => '   '])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Module title is required.');

        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules', [
                'module_title' => '  New Module  ',
            ])
            ->assertOk()
            ->assertJsonPath('module_title', 'New Module')
            ->assertJsonPath('block_count', 0);
        $moduleId = (int) $response->json('module_id');
        $this->assertSame(2, (int) DB::table('training_module')->where('module_id', $moduleId)->value('module_order'));
        $this->assertSame(0, DB::table('training_content_block')->where('module_id', $moduleId)->count());

        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules', ['module_title' => 'new module'])
            ->assertUnprocessable()
            ->assertJsonPath('success', false);
        $this->assertSame(1, DB::table('training_module')->where('module_title', 'New Module')->count());
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 1,
            'action' => 'module.create',
            'detail' => 'New Module',
            'target_id' => $moduleId,
        ]);
        $this->assertSame(1, DB::table('trainer_activity_log')->count());
    }

    public function test_module_delete_removes_owned_blocks_but_preserves_assessment_and_learner_submission(): void
    {
        Storage::fake('public');
        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->integer('submission_id')->primary();
            $table->integer('assessment_id');
        });
        DB::table('assessment')->where('assessment_id', 30)->update(['module_id' => 10]);
        DB::table('assessment_submission')->insert([
            'submission_id' => 301,
            'assessment_id' => 30,
        ]);
        $path = 'training-materials/1/owned.pdf';
        Storage::disk('public')->put($path, 'owned material');
        DB::table('learning_material')->insert([
            'material_id' => 300,
            'course_id' => 1,
            'trainer_id' => 9001,
            'original_file_name' => 'owned.pdf',
            'file_path' => $path,
        ]);
        DB::table('training_content_block')->insert([
            [
                'block_id' => 301,
                'module_id' => 10,
                'block_type' => 'material',
                'material_id' => 300,
                'block_order' => 1,
                'is_visible' => true,
            ],
            [
                'block_id' => 302,
                'module_id' => 10,
                'block_type' => 'quiz',
                'assessment_id' => 30,
                'block_order' => 2,
                'is_visible' => true,
            ],
        ]);

        $this->withSession($this->trainerSession())
            ->deleteJson('/trainer/courses/1/modules/10')
            ->assertOk()
            ->assertJsonPath('deleted_blocks', 2);

        $this->assertFalse(DB::table('training_module')->where('module_id', 10)->exists());
        $this->assertSame(0, DB::table('training_content_block')->where('module_id', 10)->count());
        $this->assertFalse(DB::table('learning_material')->where('material_id', 300)->exists());
        Storage::disk('public')->assertMissing($path);
        $this->assertTrue(DB::table('assessment')->where('assessment_id', 30)->exists());
        $this->assertNull(DB::table('assessment')->where('assessment_id', 30)->value('module_id'));
        $this->assertTrue(DB::table('assessment_submission')->where('submission_id', 301)->exists());
        $this->assertDatabaseHas('trainer_activity_log', [
            'trainer_id' => 9001,
            'course_id' => 1,
            'action' => 'module.delete',
            'detail' => 'Course One Module',
            'target_id' => 10,
        ]);
    }

    public function test_assessment_authoring_data_is_loaded_on_demand_and_course_scoped(): void
    {
        DB::table('assessment_question')->insert([
            [
                'question_id' => 52,
                'assessment_id' => 30,
                'question_text' => 'Second question',
                'question_order' => 2,
                'question_type' => 'essay',
                'options_json' => null,
                'correct_answer' => null,
                'marks' => 3,
                'grading_rubric' => 'Clear explanation',
            ],
            [
                'question_id' => 51,
                'assessment_id' => 30,
                'question_text' => 'First question',
                'question_order' => 1,
                'question_type' => 'true_false',
                'options_json' => '["True","False"]',
                'correct_answer' => 'True',
                'marks' => 1,
                'grading_rubric' => null,
            ],
        ]);
        DB::table('assessment')->insert([
            'assessment_id' => 31,
            'course_id' => 2,
            'title' => 'Foreign quiz',
            'type' => 'quiz',
        ]);

        $this->withSession($this->trainerSession())
            ->getJson('/trainer/courses/1/assessments/30/authoring')
            ->assertOk()
            ->assertJsonPath('assessment.assessment_id', 30)
            ->assertJsonPath('assessment.questions.0.question_id', 51)
            ->assertJsonPath('assessment.questions.1.question_id', 52)
            ->assertJsonPath('assessment.has_learner_records', false)
            ->assertJsonStructure(['assessment' => ['load_url', 'update_url']]);

        $this->withSession($this->trainerSession())
            ->getJson('/trainer/courses/1/assessments/31/authoring')
            ->assertNotFound();
        $this->withSession($this->trainerSession())
            ->getJson('/trainer/courses/2/assessments/31/authoring')
            ->assertForbidden();
    }

    public function test_assessment_builder_is_a_dedicated_course_scoped_page(): void
    {
        DB::table('course')->insert([
            'courseID' => '1',
            'courseName' => 'Course One',
            'courseCategory' => 'Professional',
            'targetAudience' => 'Teachers',
            'status' => 'Active',
        ]);

        $this->withSession($this->trainerSession())
            ->get('/trainer/courses/1/assessments/builder')
            ->assertOk()
            ->assertSee('data-assessment-page', false)
            ->assertSee('Assessment Builder')
            ->assertSee('Course One Quiz')
            ->assertSee(route('trainer.courses.materials', 1), false)
            ->assertDontSee('id="assessmentModal"', false);

        $this->withSession($this->trainerSession())
            ->get('/trainer/courses/2/assessments/builder')
            ->assertRedirect(route('trainer.courses'));
    }

    public function test_assessment_catalog_includes_all_current_course_types_and_module_linked_items_only(): void
    {
        DB::table('assessment')->insert([
            ['assessment_id' => 31, 'course_id' => 1, 'module_id' => null, 'title' => 'Course One Quiz B', 'type' => 'QUIZ'],
            ['assessment_id' => 32, 'course_id' => 1, 'module_id' => 10, 'title' => 'Module Quiz', 'type' => ' quiz '],
            ['assessment_id' => 33, 'course_id' => 1, 'module_id' => null, 'title' => 'Submission A', 'type' => 'submission'],
            ['assessment_id' => 34, 'course_id' => 1, 'module_id' => 10, 'title' => 'Module Submission', 'type' => 'SUBMISSION'],
            ['assessment_id' => 35, 'course_id' => 2, 'module_id' => null, 'title' => 'Foreign Quiz', 'type' => 'quiz'],
            ['assessment_id' => 36, 'course_id' => 2, 'module_id' => null, 'title' => 'Foreign Submission', 'type' => 'submission'],
        ]);

        $catalog = app(TrainerLmsMaterialAuthoringService::class)
            ->assessmentsForCourse(1);

        $this->assertSame(
            [30, 31, 32],
            $catalog->where('type', 'quiz')->pluck('assessment_id')->map(static fn ($id): int => (int) $id)->sort()->values()->all()
        );
        $this->assertSame(
            [33, 34],
            $catalog->where('type', 'submission')->pluck('assessment_id')->map(static fn ($id): int => (int) $id)->sort()->values()->all()
        );
        $this->assertFalse($catalog->contains('course_id', 2));
        $this->assertTrue($catalog->contains('module_id', 10));
    }

    public function test_quiz_save_supports_all_question_types_and_is_idempotent(): void
    {
        $payload = $this->validQuizPayload('Complete Quiz');
        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertOk()
            ->assertJsonPath('assessment.type', 'quiz')
            ->assertJsonPath('assessment.total_marks', 9);
        $assessmentId = (int) $response->json('assessment.assessment_id');
        foreach ($response->json('assessment.questions') as $index => $question) {
            $payload['questions'][$index]['question_id'] = $question['question_id'];
        }

        $this->assertSame(
            ['mcq', 'checkbox', 'true_false', 'short_answer', 'essay'],
            DB::table('assessment_question')
                ->where('assessment_id', $assessmentId)
                ->orderBy('question_order')
                ->pluck('question_type')
                ->all()
        );
        $this->assertSame([1, 2, 3, 4, 5], DB::table('assessment_question')
            ->where('assessment_id', $assessmentId)
            ->orderBy('question_order')
            ->pluck('question_order')
            ->map(static fn ($order): int => (int) $order)
            ->all());

        foreach ([1, 2] as $attempt) {
            $saveResponse = $this->withSession($this->trainerSession())
                ->patchJson('/trainer/courses/1/assessments/'.$assessmentId, $payload)
                ->assertOk()
                ->assertJsonPath('assessment.assessment_id', $assessmentId);
            foreach ($saveResponse->json('assessment.questions') as $index => $question) {
                $payload['questions'][$index]['question_id'] = $question['question_id'];
            }
            $this->assertSame(5, DB::table('assessment_question')->where('assessment_id', $assessmentId)->count(), 'Question count changed on save '.$attempt);
        }
        $this->assertSame(['quiz.create', 'quiz.update', 'quiz.update'], DB::table('trainer_activity_log')->orderBy('log_id')->pluck('action')->all());
        $this->assertTrue(DB::table('trainer_activity_log')->where('detail', 'Complete Quiz')->get()->every(
            fn ($activity) => (int) $activity->trainer_id === 9001 && (int) $activity->course_id === 1 && (int) $activity->target_id === $assessmentId
        ));
    }

    public function test_true_false_only_quiz_saves_reloads_and_repeated_save_is_idempotent(): void
    {
        $payload = $this->validQuizPayload('True False Quiz');
        $payload['questions'] = [[
            'question_text' => 'The sky appears blue.',
            'question_type' => 'true_false',
            'options_json' => '["True","False"]',
            'correct_answer' => 'True',
            'marks' => 1,
            'question_order' => 1,
        ]];

        $created = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertOk()
            ->assertJsonPath('assessment.questions.0.question_type', 'true_false')
            ->assertJsonPath('assessment.questions.0.correct_answer', 'True');

        $assessmentId = (int) $created->json('assessment.assessment_id');
        $payload['questions'][0]['question_id'] = (int) $created->json('assessment.questions.0.question_id');

        foreach (range(1, 2) as $attempt) {
            $this->withSession($this->trainerSession())
                ->patchJson('/trainer/courses/1/assessments/'.$assessmentId, $payload)
                ->assertOk()
                ->assertJsonPath('assessment.questions.0.question_type', 'true_false')
                ->assertJsonPath('assessment.questions.0.correct_answer', 'True');

            $this->assertSame(
                1,
                DB::table('assessment_question')->where('assessment_id', $assessmentId)->count(),
                'True / False question duplicated after repeated save '.$attempt
            );
        }

        $this->withSession($this->trainerSession())
            ->getJson('/trainer/courses/1/assessments/'.$assessmentId.'/authoring')
            ->assertOk()
            ->assertJsonCount(1, 'assessment.questions')
            ->assertJsonPath('assessment.questions.0.question_type', 'true_false')
            ->assertJsonPath('assessment.questions.0.correct_answer', 'True');
    }

    public function test_question_image_preview_is_course_scoped_and_uses_safe_storage(): void
    {
        Storage::fake('public');
        $path = 'assessment-question-images/1/question.png';
        Storage::disk('public')->put($path, 'image-bytes');
        DB::table('assessment_question')->insert([
            'question_id' => 50,
            'assessment_id' => 30,
            'question_text' => 'Image question',
            'question_image_path' => $path,
            'question_order' => 1,
            'question_type' => 'essay',
            'marks' => 1,
            'grading_rubric' => 'Use the image',
        ]);

        $this->withSession($this->trainerSession())
            ->get('/trainer/courses/1/assessments/30/questions/50/image')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->withSession($this->trainerSession())
            ->get('/trainer/courses/2/assessments/30/questions/50/image')
            ->assertForbidden();
    }

    public function test_quiz_accepts_zero_marks_but_rejects_negative_marks_and_incomplete_answers(): void
    {
        $payload = $this->validQuizPayload('Zero Mark Quiz');
        foreach ($payload['questions'] as &$question) {
            $question['marks'] = 0;
        }
        unset($question);
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertOk()
            ->assertJsonPath('assessment.total_marks', 0);

        $payload = $this->validQuizPayload('Invalid Negative Marks Quiz');
        $payload['questions'][0]['marks'] = -1;
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['questions.0.marks']);

        $payload = $this->validQuizPayload('Invalid Quiz');
        $payload['questions'][0]['correct_answer'] = '';
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please choose the correct answer for Question 1.');

        $payload = $this->validQuizPayload('Invalid Quiz');
        $payload['questions'][0]['options_json'] = '["Same","same"]';
        $payload['questions'][0]['correct_answer'] = 'Same';
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Question 1 needs at least 2 different choices.');

        $payload = $this->validQuizPayload('Invalid Quiz');
        $payload['questions'][0]['options_json'] = '["First","Second",""]';
        $payload['questions'][0]['correct_answer'] = 'First';
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please fill every choice for Question 1.');

        $payload = $this->validQuizPayload('Invalid True False Quiz');
        $payload['questions'][2]['correct_answer'] = 'Yes';
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Please choose True or False as the correct answer for Question 3.');

        $payload = $this->validQuizPayload('Invalid True False Options');
        $payload['questions'][2]['options_json'] = '["True","False","Maybe"]';
        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Question 3 must use the fixed True and False choices.');
    }

    public function test_existing_quiz_attempts_lock_structure_but_allow_metadata_updates(): void
    {
        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->integer('submission_id')->primary();
            $table->integer('assessment_id');
        });
        DB::table('assessment_question')->insert([
            'question_id' => 50,
            'assessment_id' => 30,
            'question_text' => 'Original question',
            'question_order' => 1,
            'question_type' => 'mcq',
            'options_json' => '["A","B"]',
            'correct_answer' => 'A',
            'marks' => 1,
        ]);
        DB::table('assessment_submission')->insert([
            'submission_id' => 500,
            'assessment_id' => 30,
        ]);
        $payload = [
            'title' => 'Metadata Updated Quiz',
            'type' => 'quiz',
            'questions' => [[
                'question_text' => 'Original question',
                'question_type' => 'mcq',
                'options_json' => '["A","B"]',
                'correct_answer' => 'A',
                'correct_answers_json' => '[]',
                'marks' => 1,
                'question_order' => 1,
            ]],
        ];

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', $payload)
            ->assertOk()
            ->assertJsonPath('assessment.has_learner_records', true);
        $this->assertSame(50, (int) DB::table('assessment_question')->value('question_id'));

        $payload['questions'][0]['question_text'] = 'Changed historical question';
        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', $payload)
            ->assertStatus(409)
            ->assertJsonPath('success', false);
        $this->assertSame('Original question', DB::table('assessment_question')->value('question_text'));
        $this->assertTrue(DB::table('assessment_submission')->where('submission_id', 500)->exists());
    }

    public function test_attempted_quiz_returns_all_question_types_and_metadata_only_save_preserves_them(): void
    {
        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->integer('submission_id')->primary();
            $table->integer('assessment_id');
        });
        $questions = [
            ['question_id' => 51, 'assessment_id' => 30, 'question_text' => 'MCQ', 'question_order' => 1, 'question_type' => 'mcq', 'options_json' => '["A","B"]', 'correct_answer' => 'A', 'marks' => 0, 'grading_rubric' => null],
            ['question_id' => 52, 'assessment_id' => 30, 'question_text' => 'Checkbox', 'question_order' => 2, 'question_type' => 'checkbox', 'options_json' => '["A","B"]', 'correct_answer' => '["A"]', 'marks' => 2, 'grading_rubric' => null],
            ['question_id' => 53, 'assessment_id' => 30, 'question_text' => 'True False', 'question_order' => 3, 'question_type' => 'true_false', 'options_json' => '["True","False"]', 'correct_answer' => 'True', 'marks' => 0, 'grading_rubric' => null],
            ['question_id' => 54, 'assessment_id' => 30, 'question_text' => 'Short', 'question_order' => 4, 'question_type' => 'short_answer', 'options_json' => null, 'correct_answer' => 'Answer', 'marks' => 1, 'grading_rubric' => null],
            ['question_id' => 55, 'assessment_id' => 30, 'question_text' => 'Essay', 'question_order' => 5, 'question_type' => 'essay', 'options_json' => null, 'correct_answer' => null, 'marks' => 3, 'grading_rubric' => 'Rubric'],
        ];
        DB::table('assessment_question')->insert($questions);
        DB::table('assessment')->where('assessment_id', 30)->update([
            'time_limit_minutes' => 45,
            'total_marks' => 6,
            'max_attempts' => 2,
            'weight_percent' => 25,
        ]);
        DB::table('assessment_submission')->insert([
            'submission_id' => 501,
            'assessment_id' => 30,
        ]);
        $originalQuestions = DB::table('assessment_question')
            ->where('assessment_id', 30)
            ->orderBy('question_order')
            ->get()
            ->map(static fn (object $question): array => (array) $question)
            ->all();

        $this->withSession($this->trainerSession())
            ->getJson('/trainer/courses/1/assessments/30/authoring')
            ->assertOk()
            ->assertJsonPath('assessment.has_learner_records', true)
            ->assertJsonCount(5, 'assessment.questions');

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', [
                'title' => 'Metadata Only Update',
                'type' => 'quiz',
                'instruction_text' => 'Questions stay unchanged.',
                'available_from' => '2026-08-20 09:00:00',
                'available_until' => '2026-08-21 09:00:00',
            ])
            ->assertOk()
            ->assertJsonCount(5, 'assessment.questions')
            ->assertJsonPath('assessment.total_marks', 6);

        $savedAssessment = DB::table('assessment')->where('assessment_id', 30)->first();
        $this->assertSame(45, (int) $savedAssessment->time_limit_minutes);
        $this->assertSame(2, (int) $savedAssessment->max_attempts);
        $this->assertSame(25.0, (float) $savedAssessment->weight_percent);
        $this->assertSame(6.0, (float) $savedAssessment->total_marks);
        $this->assertSame(
            $originalQuestions,
            DB::table('assessment_question')
                ->where('assessment_id', 30)
                ->orderBy('question_order')
                ->get()
                ->map(static fn (object $question): array => (array) $question)
                ->all()
        );

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', [
                'title' => 'Blocked Scoring Update',
                'type' => 'quiz',
                'time_limit_minutes' => 60,
            ])
            ->assertStatus(409)
            ->assertJsonPath('success', false);

        $this->assertSame(
            [51, 52, 53, 54, 55],
            DB::table('assessment_question')
                ->where('assessment_id', 30)
                ->orderBy('question_order')
                ->pluck('question_id')
                ->map(static fn ($id): int => (int) $id)
                ->all()
        );
        $this->assertSame(1, DB::table('assessment_submission')->where('assessment_id', 30)->count());
    }

    public function test_assessment_attachment_uses_current_course_block_relationship_not_legacy_module_hint(): void
    {
        $service = app(TrainerLmsMaterialAuthoringService::class);
        $this->assertNull(
            $service->assessmentsForCourse(1)
                ->firstWhere('assessment_id', 30)
                ->attachment_module_id
        );
        DB::table('training_module')->insert([
            'module_id' => 11,
            'course_id' => 1,
            'module_title' => 'Chapter One',
            'module_type' => 'lesson',
            'module_order' => 2,
        ]);
        DB::table('assessment')->insert([
            'assessment_id' => 31,
            'course_id' => 1,
            'module_id' => 11,
            'title' => 'Legacy Module Quiz',
            'type' => 'quiz',
            'status' => 'Active',
        ]);
        DB::table('assessment')->insert([
            'assessment_id' => 32,
            'course_id' => 1,
            'module_id' => 999,
            'title' => 'Invalid Legacy Module Quiz',
            'type' => 'quiz',
            'status' => 'Active',
        ]);
        DB::table('training_content_block')->insert([
            'block_id' => 41,
            'module_id' => 10,
            'block_type' => 'quiz',
            'block_title' => 'Course One Quiz',
            'assessment_id' => 30,
            'block_order' => 1,
            'is_visible' => true,
        ]);
        DB::table('assessment')->where('assessment_id', 30)->update([
            'module_id' => 11,
        ]);

        $catalog = $service->assessmentsForCourse(1);
        $assessment = $catalog->firstWhere('assessment_id', 30);
        $this->assertSame(10, (int) $assessment->attachment_module_id);
        $this->assertSame('Course One Module', $assessment->attachment_module_title);
        $this->assertSame('content_block', $assessment->attachment_source);

        $legacyAssessment = $catalog->firstWhere('assessment_id', 31);
        $this->assertNull($legacyAssessment->attachment_module_id);
        $this->assertNull($legacyAssessment->attachment_module_title);
        $this->assertNull($legacyAssessment->attachment_source);

        $invalidLegacyAssessment = $catalog->firstWhere('assessment_id', 32);
        $this->assertNull($invalidLegacyAssessment->attachment_module_id);
        $this->assertNull($invalidLegacyAssessment->attachment_source);

        DB::table('training_content_block')->where('block_id', 41)->delete();
        $assessment = $service->assessmentsForCourse(1)->firstWhere('assessment_id', 30);
        $this->assertNull($assessment->attachment_module_id);

        $assessment = $service->assessmentsForCourse(1)->firstWhere('assessment_id', 31);
        $this->assertNull($assessment->attachment_module_id);

        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/10/blocks/assessment', [
                'assessment_id' => 31,
            ])
            ->assertOk()
            ->assertJsonPath('attachment_module_id', 10);
        $this->assertSame(1, DB::table('training_content_block')->where('assessment_id', 31)->count());

        $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/modules/11/blocks/assessment', [
                'assessment_id' => 31,
            ])
            ->assertStatus(409);

        $attachedBlockId = (int) DB::table('training_content_block')
            ->where('assessment_id', 31)
            ->value('block_id');
        $this->withSession($this->trainerSession())
            ->deleteJson('/trainer/courses/1/blocks/'.$attachedBlockId)
            ->assertOk()
            ->assertJsonPath('assessment_id', 31);
        $this->assertNull(
            $service->assessmentsForCourse(1)
                ->firstWhere('assessment_id', 31)
                ->attachment_module_id
        );
        $this->assertNull(
            DB::table('assessment')
                ->where('assessment_id', 31)
                ->value('module_id')
        );
    }

    public function test_assessment_availability_boundaries_are_deterministic(): void
    {
        $service = app(TrainerLmsMaterialAuthoringService::class);
        $now = CarbonImmutable::parse('2026-08-18 12:00:00', 'Asia/Kuala_Lumpur');

        $this->assertSame('always', $service->deriveAssessmentAvailability(null, null, $now)['key']);
        $this->assertSame('scheduled', $service->deriveAssessmentAvailability('2026-08-19 12:00:00', null, $now)['key']);
        $this->assertSame('open', $service->deriveAssessmentAvailability('2026-08-18 12:00:00', '2026-08-18 13:00:00', $now)['key']);
        $this->assertSame('open', $service->deriveAssessmentAvailability(null, '2026-08-18 12:00:00', $now)['key']);
        $this->assertSame('closed', $service->deriveAssessmentAvailability(null, '2026-08-18 11:59:59', $now)['key']);
        $this->assertSame('open', $service->deriveAssessmentAvailability('2026-08-17 12:00:00', null, $now)['key']);
    }

    public function test_editable_existing_quiz_can_clear_all_questions_transactionally(): void
    {
        DB::table('assessment_question')->insert([
            'question_id' => 50,
            'assessment_id' => 30,
            'question_text' => 'Question to remove',
            'question_order' => 1,
            'question_type' => 'true_false',
            'options_json' => '["True","False"]',
            'correct_answer' => 'True',
            'marks' => 1,
        ]);

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/30', [
                'title' => 'Cleared Quiz',
                'type' => 'quiz',
                'questions' => [],
            ])
            ->assertOk()
            ->assertJsonPath('assessment.questions', [])
            ->assertJsonPath('assessment.total_marks', 0);

        $this->assertSame(0, DB::table('assessment_question')->where('assessment_id', 30)->count());
    }

    public function test_submission_task_edit_preserves_learner_submissions(): void
    {
        Schema::create('assessment_submission', function (Blueprint $table): void {
            $table->integer('submission_id')->primary();
            $table->integer('assessment_id');
            $table->integer('participant_id')->nullable();
            $table->string('file_path')->nullable();
            $table->string('review_status')->nullable();
            $table->text('review_notes')->nullable();
        });
        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', [
                'title' => 'Portfolio',
                'type' => 'submission',
                'instruction_text' => 'Upload the completed portfolio.',
                'available_from' => '2026-08-20 09:00:00',
                'available_until' => '2026-08-21 09:00:00',
                'accepted_format' => 'PDF Only',
            ])
            ->assertOk();
        $assessmentId = (int) $response->json('assessment.assessment_id');
        DB::table('assessment_submission')->insert([
            'submission_id' => 700,
            'assessment_id' => $assessmentId,
            'participant_id' => 88,
            'file_path' => 'submissions/portfolio.pdf',
            'review_status' => 'Reviewed',
            'review_notes' => 'Strong reflection.',
        ]);
        $learnerSubmission = (array) DB::table('assessment_submission')
            ->where('submission_id', 700)
            ->first();

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/'.$assessmentId, [
                'title' => 'Updated Portfolio',
                'type' => 'submission',
                'instruction_text' => 'Upload one final PDF.',
                'available_from' => '2026-08-20 09:00:00',
                'available_until' => '2026-08-22 09:00:00',
                'accepted_format' => 'Document Only',
            ])
            ->assertOk()
            ->assertJsonPath('assessment.title', 'Updated Portfolio')
            ->assertJsonPath('assessment.accepted_format', 'Document Only');

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/'.$assessmentId, [
                'title' => 'Updated Portfolio Again',
                'type' => 'submission',
                'instruction_text' => 'Upload the revised document.',
                'available_from' => '2026-08-21 09:00:00',
                'available_until' => '2026-08-23 09:00:00',
                'accepted_format' => 'ZIP Only',
            ])
            ->assertOk()
            ->assertJsonPath('assessment.title', 'Updated Portfolio Again')
            ->assertJsonPath('assessment.accepted_format', 'ZIP Only');
        $this->assertTrue(DB::table('assessment_submission')->where('submission_id', 700)->exists());
        $this->assertSame(
            $learnerSubmission,
            (array) DB::table('assessment_submission')
                ->where('submission_id', 700)
                ->first()
        );
        $this->assertSame(
            ['submission_task.create', 'submission_task.update', 'submission_task.update'],
            DB::table('trainer_activity_log')->orderBy('log_id')->pluck('action')->all()
        );
        $this->assertSame(
            ['Portfolio', 'Updated Portfolio', 'Updated Portfolio Again'],
            DB::table('trainer_activity_log')->orderBy('log_id')->pluck('detail')->all()
        );
    }

    public function test_submission_save_discards_stale_quiz_question_payload_and_remains_idempotent(): void
    {
        $payload = [
            'title' => 'Submission Without Quiz State',
            'type' => 'submission',
            'instruction_text' => 'Upload one document.',
            'accepted_format' => 'Document Only',
            'time_limit_minutes' => 30,
            'weight_percent' => 50,
            'max_attempts' => 2,
            'total_marks' => 99,
            'questions' => [[
                'question_text' => '',
                'question_type' => 'mcq',
                'options_json' => '[]',
                'correct_answer' => '',
                'marks' => 0,
            ]],
        ];

        $response = $this->withSession($this->trainerSession())
            ->postJson('/trainer/courses/1/assessments', $payload)
            ->assertOk()
            ->assertJsonPath('assessment.type', 'submission')
            ->assertJsonPath('assessment.questions', []);
        $assessmentId = (int) $response->json('assessment.assessment_id');

        $this->withSession($this->trainerSession())
            ->patchJson('/trainer/courses/1/assessments/'.$assessmentId, $payload)
            ->assertOk()
            ->assertJsonPath('assessment.assessment_id', $assessmentId)
            ->assertJsonPath('assessment.questions', []);

        $this->assertSame(0, DB::table('assessment_question')->where('assessment_id', $assessmentId)->count());
        $this->assertSame(1, DB::table('assessment')->where('assessment_id', $assessmentId)->count());
        $saved = DB::table('assessment')->where('assessment_id', $assessmentId)->first();
        $this->assertNull($saved->time_limit_minutes);
        $this->assertNull($saved->total_marks);
    }

    /**
     * @return array<string, mixed>
     */
    private function validQuizPayload(string $title): array
    {
        return [
            'title' => $title,
            'type' => 'quiz',
            'instruction_text' => 'Answer every question.',
            'available_from' => '2026-08-20 09:00:00',
            'available_until' => '2026-08-21 09:00:00',
            'questions' => [
                ['question_text' => 'Choose one', 'question_type' => 'mcq', 'options_json' => '["A","B"]', 'correct_answer' => 'A', 'correct_answers_json' => '[]', 'marks' => 1, 'question_order' => 1],
                ['question_text' => 'Choose several', 'question_type' => 'checkbox', 'options_json' => '["A","B","C"]', 'correct_answer' => 'A', 'correct_answers_json' => '["A","C"]', 'marks' => 2, 'question_order' => 2],
                ['question_text' => 'True or false', 'question_type' => 'true_false', 'options_json' => '["True","False"]', 'correct_answer' => 'True', 'marks' => 1, 'question_order' => 3],
                ['question_text' => 'Give a keyword', 'question_type' => 'short_answer', 'correct_answer' => 'reflection', 'marks' => 2, 'question_order' => 4],
                ['question_text' => 'Explain your answer', 'question_type' => 'essay', 'grading_rubric' => 'Clarity and evidence', 'marks' => 3, 'question_order' => 5],
            ],
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function trainerSession(): array
    {
        return [
            'user_role' => 'trainer',
            'user_id' => 9001,
        ];
    }

    private function createTemporaryTables(): void
    {
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
            $table->string('courseName');
            $table->string('courseCategory')->nullable();
            $table->string('courseType')->nullable();
            $table->string('targetAudience')->nullable();
            $table->string('status')->nullable();
        });
        Schema::create('course_session', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
        });
        Schema::create('session_trainer', function (Blueprint $table): void {
            $table->string('sessionID', 20)->primary();
            $table->string('trainerID', 20);
        });
        Schema::create('training_module', function (Blueprint $table): void {
            $table->integer('module_id')->primary();
            $table->string('course_id', 20);
            $table->string('module_title');
            $table->string('module_type');
            $table->integer('module_order');
            $table->string('cover_image')->nullable();
            $table->timestamps();
        });
        Schema::create('training_content_block', function (Blueprint $table): void {
            $table->integer('block_id')->primary();
            $table->integer('module_id');
            $table->string('block_type');
            $table->string('block_title')->nullable();
            $table->text('block_content')->nullable();
            $table->string('external_url')->nullable();
            $table->integer('material_id')->nullable();
            $table->integer('assessment_id')->nullable();
            $table->integer('block_order');
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
        Schema::create('assessment', function (Blueprint $table): void {
            $table->integer('assessment_id')->primary();
            $table->string('course_id', 20);
            $table->integer('module_id')->nullable();
            $table->string('title');
            $table->string('type');
            $table->text('instruction_text')->nullable();
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            $table->string('accepted_format')->nullable();
            $table->integer('time_limit_minutes')->nullable();
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->string('status')->nullable();
            $table->integer('max_attempts')->nullable();
            $table->decimal('weight_percent', 5, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('learning_material', function (Blueprint $table): void {
            $table->integer('material_id')->primary();
            $table->string('course_id', 20);
            $table->string('trainer_id', 20)->nullable();
            $table->string('original_file_name');
            $table->string('file_path');
            $table->string('material_type')->nullable();
            $table->boolean('metadata_cleared')->default(false);
            $table->text('extracted_metadata')->nullable();
            $table->string('ai_generated_title')->nullable();
            $table->text('ai_synopsis')->nullable();
            $table->dateTime('uploaded_at')->nullable();
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
    }

    private function dropTemporaryTables(): void
    {
        Schema::dropIfExists('trainer_activity_log');
        Schema::dropIfExists('assessment_question');
        Schema::dropIfExists('assessment_submission');
        Schema::dropIfExists('learning_material');
        Schema::dropIfExists('assessment');
        Schema::dropIfExists('training_content_block');
        Schema::dropIfExists('training_module');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');
        Schema::dropIfExists('course');
    }
}
