<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SaveModuleBlocksAtomicityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->dropTemporaryTables();

        // Isolated behavioral fixtures for the SQLite :memory: test connection.
        Schema::create('course_session', function (Blueprint $table) {
            $table->string('sessionID', 20)->primary();
            $table->string('courseID', 20);
        });

        Schema::create('session_trainer', function (Blueprint $table) {
            $table->string('sessionID', 20);
            $table->string('trainerID', 20);
        });

        Schema::create('training_module', function (Blueprint $table) {
            $table->integer('module_id')->primary();
            $table->string('course_id', 20);
            $table->string('module_title');
            $table->string('cover_image')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('training_content_block', function (Blueprint $table) {
            $table->integer('block_id')->primary();
            $table->integer('module_id');
            $table->string('block_type')->nullable();
            $table->string('block_title')->nullable();
            $table->text('block_content')->nullable();
            $table->string('external_url', 500)->nullable();
            $table->integer('block_order')->nullable();
            $table->integer('material_id')->nullable();
            $table->integer('assessment_id')->nullable();
            $table->boolean('is_visible')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        try {
            $this->dropTemporaryTables();
        } finally {
            parent::tearDown();
        }
    }

    public function test_late_block_validation_failure_leaves_module_and_earlier_blocks_unchanged(): void
    {
        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => null,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'updated_at' => null,
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->patchJson('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Block',
                ],
                [
                    'block_type' => 'text',
                    'block_content' => '',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $persistedModuleTitle = DB::table('training_module')
            ->where('module_id', 10)
            ->value('module_title');

        $persistedBlockTitle = DB::table('training_content_block')
            ->where('block_id', 101)
            ->value('block_title');

        $this->assertSame(
            'Original Module',
            $persistedModuleTitle,
            'Persisted block title after the failed request: '.$persistedBlockTitle
        );

        $this->assertSame('Original Block', $persistedBlockTitle);

        $this->assertSame(
            1,
            DB::table('training_content_block')
                ->where('module_id', 10)
                ->count()
        );

        $this->assertSame(
            0,
            DB::table('training_content_block')
                ->where('module_id', 10)
                ->where('block_id', '!=', 101)
                ->count()
        );
    }

    public function test_late_database_failure_rolls_back_module_and_earlier_block_changes(): void
    {
        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => null,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            [
                'block_id' => 101,
                'module_id' => 10,
                'block_title' => 'Original First Block',
                'block_content' => null,
                'external_url' => null,
                'block_order' => 1,
                'material_id' => null,
                'assessment_id' => null,
                'updated_at' => null,
            ],
            [
                'block_id' => 102,
                'module_id' => 10,
                'block_title' => 'Original Second Block',
                'block_content' => null,
                'external_url' => null,
                'block_order' => 2,
                'material_id' => null,
                'assessment_id' => null,
                'updated_at' => null,
            ],
        ]);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER fail_second_block_update
BEFORE UPDATE ON training_content_block
WHEN OLD.block_id = 102
BEGIN
    SELECT RAISE(ABORT, 'synthetic late database failure');
END
SQL);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->patchJson('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed First Block',
                ],
                [
                    'block_id' => 102,
                    'block_type' => 'title',
                    'block_title' => 'Changed Second Block',
                ],
            ],
        ]);

        $response
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'This module could not be saved. Please try again.',
            ]);
        $this->assertStringNotContainsString(
            'synthetic late database failure',
            (string) $response->getContent()
        );

        $persistedModuleTitle = DB::table('training_module')
            ->where('module_id', 10)
            ->value('module_title');

        $persistedFirstBlockTitle = DB::table('training_content_block')
            ->where('block_id', 101)
            ->value('block_title');

        $persistedSecondBlockTitle = DB::table('training_content_block')
            ->where('block_id', 102)
            ->value('block_title');

        $this->assertSame(
            'Original Module',
            $persistedModuleTitle,
            'Persisted block titles after the failed request: first='.
            $persistedFirstBlockTitle.
            ', second='.
            $persistedSecondBlockTitle
        );

        $this->assertSame(
            'Original First Block',
            $persistedFirstBlockTitle
        );

        $this->assertSame(
            'Original Second Block',
            $persistedSecondBlockTitle
        );
    }

    public function test_late_semantic_validation_failure_preserves_existing_cover_and_removes_replacement_file(): void
    {
        Storage::fake('public');

        $pngContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0XcAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($pngContents);

        $originalCoverPath = 'module-covers/original-cover.png';

        Storage::disk('public')->put(
            $originalCoverPath,
            $pngContents
        );

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => $originalCoverPath,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'updated_at' => null,
        ]);

        $replacementCover = UploadedFile::fake()->createWithContent(
            'replacement-cover.png',
            $pngContents
        );

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->patch('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'cover_image_file' => $replacementCover,
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Block',
                ],
                [
                    'block_type' => 'text',
                    'block_content' => '',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);

        $response->assertJsonPath(
            'message',
            'Text block cannot be empty. Fill the text or remove the block.'
        );

        $persistedCoverPath = DB::table('training_module')
            ->where('module_id', 10)
            ->value('cover_image');

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $this->assertTrue(
            Storage::disk('public')->exists($originalCoverPath),
            'Persisted cover path after the failed request: '.
            $persistedCoverPath.
            '; stored cover files: '.
            implode(', ', $storedCoverFiles)
        );

        $this->assertSame(
            $originalCoverPath,
            $persistedCoverPath
        );

        $this->assertSame(
            [$originalCoverPath],
            $storedCoverFiles
        );
    }

    public function test_successful_save_commits_module_block_and_replacement_cover(): void
    {
        Storage::fake('public');
        Log::spy();

        $pngContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0XcAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($pngContents);

        $originalCoverPath = 'module-covers/original-cover.png';

        Storage::disk('public')->put(
            $originalCoverPath,
            $pngContents
        );

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => $originalCoverPath,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_type' => 'title',
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'is_visible' => true,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $replacementCover = UploadedFile::fake()->createWithContent(
            'replacement-cover.png',
            $pngContents
        );

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->patch('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'cover_image_file' => $replacementCover,
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Block',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('module_title', 'Changed Module');
        $response->assertJsonPath('blocks.0.block_id', 101);
        $response->assertJsonPath('blocks.0.block_type', 'title');
        $response->assertJsonPath('blocks.0.block_order', 1);

        $this->assertSame(
            'Changed Module',
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('module_title')
        );

        $this->assertSame(
            'Changed Block',
            DB::table('training_content_block')
                ->where('block_id', 101)
                ->value('block_title')
        );

        $this->assertSame(
            1,
            DB::table('training_content_block')
                ->where('block_id', 101)
                ->value('block_order')
        );

        $persistedCoverPath = DB::table('training_module')
            ->where('module_id', 10)
            ->value('cover_image');

        $this->assertIsString($persistedCoverPath);
        $this->assertNotSame('', $persistedCoverPath);
        $this->assertNotSame($originalCoverPath, $persistedCoverPath);
        $this->assertStringStartsWith(
            'module-covers/',
            $persistedCoverPath
        );
        $this->assertTrue(
            Storage::disk('public')->exists($persistedCoverPath)
        );
        $this->assertFalse(
            Storage::disk('public')->exists($originalCoverPath)
        );

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $this->assertSame(
            [$persistedCoverPath],
            $storedCoverFiles
        );

        $this->assertSame(
            1,
            DB::table('training_content_block')
                ->where('module_id', 10)
                ->count()
        );
    }

    public function test_transaction_failure_removes_replacement_cover_and_preserves_original_cover(): void
    {
        Storage::fake('public');

        $pngContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0XcAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($pngContents);

        $originalCoverPath = 'module-covers/original-cover.png';

        Storage::disk('public')->put(
            $originalCoverPath,
            $pngContents
        );

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => $originalCoverPath,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            [
                'block_id' => 101,
                'module_id' => 10,
                'block_type' => 'title',
                'block_title' => 'Original First Block',
                'block_content' => null,
                'external_url' => null,
                'block_order' => 1,
                'material_id' => null,
                'assessment_id' => null,
                'is_visible' => true,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'block_id' => 102,
                'module_id' => 10,
                'block_type' => 'title',
                'block_title' => 'Original Second Block',
                'block_content' => null,
                'external_url' => null,
                'block_order' => 2,
                'material_id' => null,
                'assessment_id' => null,
                'is_visible' => true,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER fail_second_block_update
BEFORE UPDATE ON training_content_block
WHEN OLD.block_id = 102
BEGIN
    SELECT RAISE(ABORT, 'synthetic late database failure');
END
SQL);

        $replacementCover = UploadedFile::fake()->createWithContent(
            'replacement-cover.png',
            $pngContents
        );

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->patch('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'cover_image_file' => $replacementCover,
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed First Block',
                ],
                [
                    'block_id' => 102,
                    'block_type' => 'title',
                    'block_title' => 'Changed Second Block',
                ],
            ],
        ]);

        $response
            ->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'This module could not be saved. Please try again.',
            ]);
        $this->assertStringNotContainsString(
            'synthetic late database failure',
            (string) $response->getContent()
        );

        $this->assertSame(
            'Original Module',
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('module_title')
        );

        $this->assertSame(
            $originalCoverPath,
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('cover_image')
        );

        $this->assertSame(
            'Original First Block',
            DB::table('training_content_block')
                ->where('block_id', 101)
                ->value('block_title')
        );

        $this->assertSame(
            'Original Second Block',
            DB::table('training_content_block')
                ->where('block_id', 102)
                ->value('block_title')
        );

        $this->assertTrue(
            Storage::disk('public')->exists($originalCoverPath)
        );

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $this->assertSame(
            [$originalCoverPath],
            $storedCoverFiles
        );
    }

    public function test_unknown_existing_block_id_returns_conflict_without_persisting_mixed_save(): void
    {
        Storage::fake('public');
        Log::spy();

        $pngContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0XcAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($pngContents);

        $originalCoverPath = 'module-covers/original-cover.png';

        Storage::disk('public')->put(
            $originalCoverPath,
            $pngContents
        );

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => $originalCoverPath,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_type' => 'title',
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'is_visible' => true,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $replacementCover = UploadedFile::fake()->createWithContent(
            'replacement-cover.png',
            $pngContents
        );

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->patch('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'cover_image_file' => $replacementCover,
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Existing Block',
                ],
                [
                    'block_id' => 999999,
                    'block_type' => 'title',
                    'block_title' => 'Unknown Existing Block',
                ],
                [
                    'block_type' => 'title',
                    'block_title' => 'New Block',
                ],
            ],
        ]);

        $persistedModuleTitle = DB::table('training_module')
            ->where('module_id', 10)
            ->value('module_title');

        $persistedCoverPath = DB::table('training_module')
            ->where('module_id', 10)
            ->value('cover_image');

        $persistedBlockTitle = DB::table('training_content_block')
            ->where('block_id', 101)
            ->value('block_title');

        $moduleBlockCount = DB::table('training_content_block')
            ->where('module_id', 10)
            ->count();

        $newBlockCount = DB::table('training_content_block')
            ->where('module_id', 10)
            ->where('block_title', 'New Block')
            ->count();

        $unknownBlockIdCount = DB::table('training_content_block')
            ->where('block_id', 999999)
            ->count();

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $originalCoverExists = Storage::disk('public')
            ->exists($originalCoverPath);

        $this->assertSame(
            409,
            $response->getStatusCode(),
            'Persisted state after the request: module='.
            $persistedModuleTitle.
            '; valid block='.
            $persistedBlockTitle.
            '; cover='.
            $persistedCoverPath.
            '; block count='.
            $moduleBlockCount.
            '; new block count='.
            $newBlockCount.
            '; unknown ID count='.
            $unknownBlockIdCount.
            '; original cover exists='.
            ($originalCoverExists ? 'yes' : 'no').
            '; stored cover files='.
            implode(', ', $storedCoverFiles)
        );

        $response->assertExactJson([
            'success' => false,
            'message' => 'The module content could not be saved because it is out of date. '.
                'Refresh the page before retrying.',
        ]);

        $this->assertSame('Original Module', $persistedModuleTitle);
        $this->assertSame($originalCoverPath, $persistedCoverPath);
        $this->assertSame('Original Block', $persistedBlockTitle);
        $this->assertSame(1, $moduleBlockCount);
        $this->assertSame(0, $newBlockCount);
        $this->assertSame(0, $unknownBlockIdCount);
        $this->assertTrue($originalCoverExists);
        $this->assertSame([$originalCoverPath], $storedCoverFiles);
    }

    public function test_cross_module_existing_block_id_returns_the_same_conflict_without_changes(): void
    {
        Log::spy();

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            [
                'module_id' => 10,
                'course_id' => 1,
                'module_title' => 'Original Route Module',
                'cover_image' => null,
                'updated_at' => null,
            ],
            [
                'module_id' => 20,
                'course_id' => 1,
                'module_title' => 'Other Module',
                'cover_image' => null,
                'updated_at' => null,
            ],
        ]);

        DB::table('training_content_block')->insert([
            [
                'block_id' => 101,
                'module_id' => 10,
                'block_type' => 'title',
                'block_title' => 'Original Route Block',
                'block_content' => null,
                'external_url' => null,
                'block_order' => 1,
                'material_id' => null,
                'assessment_id' => null,
                'is_visible' => true,
                'created_at' => null,
                'updated_at' => null,
            ],
            [
                'block_id' => 202,
                'module_id' => 20,
                'block_type' => 'title',
                'block_title' => 'Other Module Block',
                'block_content' => null,
                'external_url' => null,
                'block_order' => 1,
                'material_id' => null,
                'assessment_id' => null,
                'is_visible' => true,
                'created_at' => null,
                'updated_at' => null,
            ],
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->patchJson('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Route Module',
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Route Block',
                ],
                [
                    'block_id' => 202,
                    'block_type' => 'title',
                    'block_title' => 'Submitted Other Module Block',
                ],
            ],
        ]);

        $routeModuleTitle = DB::table('training_module')
            ->where('module_id', 10)
            ->value('module_title');

        $otherModuleTitle = DB::table('training_module')
            ->where('module_id', 20)
            ->value('module_title');

        $routeBlockTitle = DB::table('training_content_block')
            ->where('block_id', 101)
            ->value('block_title');

        $otherModuleBlockTitle = DB::table('training_content_block')
            ->where('block_id', 202)
            ->value('block_title');

        $routeModuleBlockCount = DB::table('training_content_block')
            ->where('module_id', 10)
            ->count();

        $otherModuleBlockCount = DB::table('training_content_block')
            ->where('module_id', 20)
            ->count();

        $this->assertSame(
            409,
            $response->getStatusCode(),
            'Persisted state after the request: route module='.
            $routeModuleTitle.
            '; other module='.
            $otherModuleTitle.
            '; route block='.
            $routeBlockTitle.
            '; other-module block='.
            $otherModuleBlockTitle.
            '; route-module block count='.
            $routeModuleBlockCount.
            '; other-module block count='.
            $otherModuleBlockCount
        );

        $response->assertExactJson([
            'success' => false,
            'message' => 'The module content could not be saved because it is out of date. '.
                'Refresh the page before retrying.',
        ]);

        $this->assertSame('Original Route Module', $routeModuleTitle);
        $this->assertSame('Other Module', $otherModuleTitle);
        $this->assertSame('Original Route Block', $routeBlockTitle);
        $this->assertSame('Other Module Block', $otherModuleBlockTitle);
        $this->assertSame(1, $routeModuleBlockCount);
        $this->assertSame(1, $otherModuleBlockCount);
    }

    public function test_successful_save_preserves_foreign_prefix_old_cover_when_replacing_module_cover(): void
    {
        Storage::fake('public');
        Log::spy();

        $pngContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0XcAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($pngContents);

        $unrelatedCertificatePath = 'trainer_certs/unrelated-certificate.pdf';

        Storage::disk('public')->put(
            $unrelatedCertificatePath,
            'Synthetic certificate contents'
        );

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => $unrelatedCertificatePath,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_type' => 'title',
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'is_visible' => true,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $replacementCover = UploadedFile::fake()->createWithContent(
            'replacement-cover.png',
            $pngContents
        );

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->patch('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'cover_image_file' => $replacementCover,
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Block',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertSame(
            'Changed Module',
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('module_title')
        );

        $this->assertSame(
            'Changed Block',
            DB::table('training_content_block')
                ->where('block_id', 101)
                ->value('block_title')
        );

        $persistedCoverPath = DB::table('training_module')
            ->where('module_id', 10)
            ->value('cover_image');

        $this->assertIsString($persistedCoverPath);
        $this->assertNotSame('', $persistedCoverPath);
        $this->assertStringStartsWith(
            'module-covers/',
            $persistedCoverPath
        );
        $this->assertTrue(
            Storage::disk('public')->exists($persistedCoverPath)
        );

        $storedFiles = Storage::disk('public')->allFiles();

        sort($storedFiles);

        $this->assertTrue(
            Storage::disk('public')->exists($unrelatedCertificatePath),
            'Stored public files after module block save: '.
            ($storedFiles === [] ? '(empty)' : implode(', ', $storedFiles))
        );

        $expectedStoredFiles = [
            $persistedCoverPath,
            $unrelatedCertificatePath,
        ];

        sort($expectedStoredFiles);

        $this->assertSame(
            $expectedStoredFiles,
            $storedFiles
        );
    }

    public function test_successful_save_preserves_shared_old_cover_when_replacing_module_cover(): void
    {
        Storage::fake('public');
        Log::spy();

        $pngContents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Z0XcAAAAASUVORK5CYII=',
            true
        );

        $this->assertIsString($pngContents);

        $sharedCoverPath = 'module-covers/shared-cover.png';

        Storage::disk('public')->put(
            $sharedCoverPath,
            $pngContents
        );

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            [
                'module_id' => 10,
                'course_id' => 1,
                'module_title' => 'Original Module',
                'cover_image' => $sharedCoverPath,
                'updated_at' => null,
            ],
            [
                'module_id' => 20,
                'course_id' => 1,
                'module_title' => 'Other Module',
                'cover_image' => $sharedCoverPath,
                'updated_at' => null,
            ],
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_type' => 'title',
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'is_visible' => true,
            'created_at' => null,
            'updated_at' => null,
        ]);

        $replacementCover = UploadedFile::fake()->createWithContent(
            'replacement-cover.png',
            $pngContents
        );

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->patch('/trainer/courses/1/modules/10/save-blocks', [
            'module_title' => 'Changed Module',
            'cover_image_file' => $replacementCover,
            'blocks' => [
                [
                    'block_id' => 101,
                    'block_type' => 'title',
                    'block_title' => 'Changed Block',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $this->assertSame(
            'Changed Module',
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('module_title')
        );

        $this->assertSame(
            'Changed Block',
            DB::table('training_content_block')
                ->where('block_id', 101)
                ->value('block_title')
        );

        $persistedCoverPath = DB::table('training_module')
            ->where('module_id', 10)
            ->value('cover_image');

        $this->assertIsString($persistedCoverPath);
        $this->assertNotSame('', $persistedCoverPath);
        $this->assertStringStartsWith(
            'module-covers/',
            $persistedCoverPath
        );
        $this->assertNotSame(
            $sharedCoverPath,
            $persistedCoverPath
        );
        $this->assertTrue(
            Storage::disk('public')->exists($persistedCoverPath)
        );

        $otherModuleCoverPath = DB::table('training_module')
            ->where('module_id', 20)
            ->value('cover_image');

        $this->assertSame(
            $sharedCoverPath,
            $otherModuleCoverPath
        );

        $storedFiles = Storage::disk('public')->allFiles();

        sort($storedFiles);

        $this->assertTrue(
            Storage::disk('public')->exists($sharedCoverPath),
            'Updated module cover path: '.
            $persistedCoverPath.
            '; other module cover path: '.
            $otherModuleCoverPath.
            '; stored public files: '.
            ($storedFiles === [] ? '(empty)' : implode(', ', $storedFiles))
        );

        $expectedStoredFiles = [
            $persistedCoverPath,
            $sharedCoverPath,
        ];

        sort($expectedStoredFiles);

        $this->assertSame(
            $expectedStoredFiles,
            $storedFiles
        );
    }

    public function test_block_delete_failure_returns_generic_error_without_internal_details_or_changes(): void
    {
        Log::spy();

        DB::table('course_session')->insert([
            'sessionID' => '1001',
            'courseID' => '1',
        ]);

        DB::table('session_trainer')->insert([
            'sessionID' => '1001',
            'trainerID' => '9001',
        ]);

        DB::table('training_module')->insert([
            'module_id' => 10,
            'course_id' => 1,
            'module_title' => 'Original Module',
            'cover_image' => null,
            'updated_at' => null,
        ]);

        DB::table('training_content_block')->insert([
            'block_id' => 101,
            'module_id' => 10,
            'block_type' => 'title',
            'block_title' => 'Original Block',
            'block_content' => null,
            'external_url' => null,
            'block_order' => 1,
            'material_id' => null,
            'assessment_id' => null,
            'is_visible' => true,
            'created_at' => null,
            'updated_at' => null,
        ]);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER fail_block_delete
BEFORE DELETE ON training_content_block
WHEN OLD.block_id = 101
BEGIN
    SELECT RAISE(
        ABORT,
        'SYNTHETIC_INTERNAL_DATABASE_DETAIL'
    );
END
SQL);

        try {
            $response = $this->withSession([
                'user_role' => 'trainer',
                'user_id' => 9001,
            ])->withHeaders([
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->delete('/trainer/courses/1/blocks/101');
        } finally {
            DB::unprepared('DROP TRIGGER IF EXISTS fail_block_delete');
        }

        $persistedBlock = DB::table('training_content_block')
            ->where('block_id', 101)
            ->first();

        $persistedModule = DB::table('training_module')
            ->where('module_id', 10)
            ->first();

        $moduleBlockCount = DB::table('training_content_block')
            ->where('module_id', 10)
            ->count();

        $response->assertStatus(500);
        $response->assertJsonPath('success', false);
        $this->assertNotNull($persistedBlock);
        $this->assertSame('Original Block', $persistedBlock->block_title);
        $this->assertNotNull($persistedModule);
        $this->assertSame('Original Module', $persistedModule->module_title);
        $this->assertSame(1, $moduleBlockCount);

        $response->assertExactJson([
            'success' => false,
            'message' => 'Block could not be deleted.',
        ]);

        $this->assertStringNotContainsString(
            'SYNTHETIC_INTERNAL_DATABASE_DETAIL',
            $response->getContent()
        );
    }

    private function dropTemporaryTables(): void
    {
        Schema::dropIfExists('training_content_block');
        Schema::dropIfExists('training_module');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');
    }
}
