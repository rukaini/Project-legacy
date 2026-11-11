<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteModuleAtomicityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->dropTemporaryTables();

        // Isolated behavioral fixtures for SQLite :memory:, not a reconstruction
        // of the final shared database schema.
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
            $table->string('cover_image')->nullable();
        });

        Schema::create('training_content_block', function (Blueprint $table): void {
            $table->integer('block_id')->primary();
            $table->integer('module_id');
        });

        Schema::create('assessment', function (Blueprint $table): void {
            $table->integer('assessment_id')->primary();
            $table->string('course_id', 20);
            $table->integer('module_id')->nullable();
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

    public function test_late_module_delete_failure_preserves_module_and_cover(): void
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
        ]);

        DB::table('assessment')->insert([
            'assessment_id' => 201,
            'course_id' => 1,
            'module_id' => 10,
            'updated_at' => null,
        ]);

        DB::unprepared(<<<'SQL'
CREATE TRIGGER fail_module_delete
BEFORE DELETE ON training_module
WHEN OLD.module_id = 10
BEGIN
    SELECT RAISE(ABORT, 'synthetic late module deletion failure');
END
SQL);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->delete('/trainer/courses/1/modules/10');

        $response->assertRedirect(
            route('trainer.courses.materials', ['course_id' => 1])
        );

        $response->assertSessionHas('error', function ($message): bool {
            $this->assertIsString($message);
            $this->assertSame(
                'The module could not be deleted. Please try again.',
                $message
            );
            $this->assertStringNotContainsString(
                'synthetic late module deletion failure',
                $message
            );

            return true;
        });

        $response->assertSessionMissing('success');

        $persistedModule = DB::table('training_module')
            ->where('module_id', 10)
            ->first();

        $moduleExists = $persistedModule !== null;
        $persistedModuleTitle = $persistedModule?->module_title;
        $persistedCoverPath = $persistedModule?->cover_image;

        $assessmentModuleId = DB::table('assessment')
            ->where('assessment_id', 201)
            ->value('module_id');

        $contentBlockCount = DB::table('training_content_block')
            ->where('module_id', 10)
            ->count();

        $originalCoverExists = Storage::disk('public')
            ->exists($originalCoverPath);

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $this->assertTrue($moduleExists);
        $this->assertSame('Original Module', $persistedModuleTitle);
        $this->assertSame($originalCoverPath, $persistedCoverPath);
        $this->assertSame(10, (int) $assessmentModuleId);
        $this->assertSame(0, $contentBlockCount);

        $this->assertTrue(
            $originalCoverExists,
            'Rollback state: module exists='.
            ($moduleExists ? 'yes' : 'no').
            '; module title='.
            ($persistedModuleTitle ?? 'null').
            '; persisted cover path='.
            ($persistedCoverPath ?? 'null').
            '; assessment module_id='.
            ($assessmentModuleId ?? 'null').
            '; block count='.
            $contentBlockCount.
            '; stored cover files='.
            ($storedCoverFiles === []
                ? '(empty)'
                : implode(', ', $storedCoverFiles))
        );
    }

    public function test_successful_module_delete_commits_then_removes_unreferenced_cover(): void
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
        ]);

        DB::table('assessment')->insert([
            'assessment_id' => 201,
            'course_id' => 1,
            'module_id' => 10,
            'updated_at' => null,
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->delete('/trainer/courses/1/modules/10');

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $response->assertRedirect(
            route('trainer.courses.materials', ['course_id' => 1])
        );
        $response->assertSessionHas(
            'success',
            'Module deleted successfully.'
        );
        $response->assertSessionMissing('error');

        $this->assertFalse(
            DB::table('training_module')
                ->where('module_id', 10)
                ->exists()
        );
        $this->assertNull(
            DB::table('assessment')
                ->where('assessment_id', 201)
                ->value('module_id')
        );
        $this->assertSame(
            0,
            DB::table('training_content_block')
                ->where('module_id', 10)
                ->count()
        );
        $this->assertFalse(
            Storage::disk('public')->exists($originalCoverPath)
        );
        $this->assertSame([], $storedCoverFiles);
    }

    public function test_successful_module_delete_does_not_fail_when_cover_file_is_missing(): void
    {
        Storage::fake('public');
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
            'cover_image' => 'module-covers/already-missing.png',
        ]);

        DB::table('assessment')->insert([
            'assessment_id' => 201,
            'course_id' => 1,
            'module_id' => 10,
            'updated_at' => null,
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->delete('/trainer/courses/1/modules/10');

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $response->assertRedirect(
            route('trainer.courses.materials', ['course_id' => 1])
        );
        $response->assertSessionHas(
            'success',
            'Module deleted successfully.'
        );
        $response->assertSessionMissing('error');

        $this->assertFalse(
            DB::table('training_module')
                ->where('module_id', 10)
                ->exists()
        );
        $this->assertNull(
            DB::table('assessment')
                ->where('assessment_id', 201)
                ->value('module_id')
        );
        $this->assertSame([], $storedCoverFiles);
    }

    public function test_successful_module_delete_preserves_cover_referenced_by_another_module(): void
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
                'module_title' => 'Module To Delete',
                'cover_image' => $sharedCoverPath,
            ],
            [
                'module_id' => 20,
                'course_id' => 1,
                'module_title' => 'Remaining Module',
                'cover_image' => $sharedCoverPath,
            ],
        ]);

        DB::table('assessment')->insert([
            'assessment_id' => 201,
            'course_id' => 1,
            'module_id' => 10,
            'updated_at' => null,
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->delete('/trainer/courses/1/modules/10');

        $deletedModuleExists = DB::table('training_module')
            ->where('module_id', 10)
            ->exists();

        $remainingModule = DB::table('training_module')
            ->where('module_id', 20)
            ->first();

        $remainingModuleExists = $remainingModule !== null;
        $remainingModuleTitle = $remainingModule?->module_title;
        $remainingCoverPath = $remainingModule?->cover_image;

        $assessmentModuleId = DB::table('assessment')
            ->where('assessment_id', 201)
            ->value('module_id');

        $sharedCoverExists = Storage::disk('public')
            ->exists($sharedCoverPath);

        $storedCoverFiles = Storage::disk('public')
            ->allFiles('module-covers');

        sort($storedCoverFiles);

        $response->assertRedirect(
            route('trainer.courses.materials', ['course_id' => 1])
        );
        $response->assertSessionHas(
            'success',
            'Module deleted successfully.'
        );
        $response->assertSessionMissing('error');

        $this->assertFalse($deletedModuleExists);
        $this->assertTrue($remainingModuleExists);
        $this->assertSame('Remaining Module', $remainingModuleTitle);
        $this->assertSame($sharedCoverPath, $remainingCoverPath);
        $this->assertNull($assessmentModuleId);

        $this->assertTrue(
            $sharedCoverExists,
            'Shared-cover state: deleted module exists='.
            ($deletedModuleExists ? 'yes' : 'no').
            '; remaining module exists='.
            ($remainingModuleExists ? 'yes' : 'no').
            '; remaining module title='.
            ($remainingModuleTitle ?? 'null').
            '; remaining cover path='.
            ($remainingCoverPath ?? 'null').
            '; assessment module_id='.
            ($assessmentModuleId ?? 'null').
            '; stored cover files='.
            ($storedCoverFiles === []
                ? '(empty)'
                : implode(', ', $storedCoverFiles))
        );

        $this->assertSame([$sharedCoverPath], $storedCoverFiles);
    }

    public function test_successful_module_delete_preserves_local_path_outside_module_covers_prefix(): void
    {
        Storage::fake('public');
        Log::spy();

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
        ]);

        DB::table('assessment')->insert([
            'assessment_id' => 201,
            'course_id' => 1,
            'module_id' => 10,
            'updated_at' => null,
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->delete('/trainer/courses/1/modules/10');

        $storedFiles = Storage::disk('public')->allFiles();

        sort($storedFiles);

        $response->assertRedirect(
            route('trainer.courses.materials', ['course_id' => 1])
        );
        $response->assertSessionHas(
            'success',
            'Module deleted successfully.'
        );
        $response->assertSessionMissing('error');

        $this->assertFalse(
            DB::table('training_module')
                ->where('module_id', 10)
                ->exists()
        );
        $this->assertNull(
            DB::table('assessment')
                ->where('assessment_id', 201)
                ->value('module_id')
        );
        $this->assertSame(
            0,
            DB::table('training_content_block')
                ->where('module_id', 10)
                ->count()
        );

        $this->assertTrue(
            Storage::disk('public')->exists($unrelatedCertificatePath),
            'Stored public files after module deletion: '.
            ($storedFiles === [] ? '(empty)' : implode(', ', $storedFiles))
        );

        $this->assertSame(
            [$unrelatedCertificatePath],
            $storedFiles
        );
    }

    private function dropTemporaryTables(): void
    {
        Schema::dropIfExists('assessment');
        Schema::dropIfExists('training_content_block');
        Schema::dropIfExists('training_module');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');
    }
}
