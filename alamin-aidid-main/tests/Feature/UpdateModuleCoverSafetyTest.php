<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateModuleCoverSafetyTest extends TestCase
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

    public function test_module_cover_reset_preserves_local_file_outside_module_covers_prefix(): void
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
            'course_id' => '1',
            'module_title' => 'Original Module',
            'cover_image' => $unrelatedCertificatePath,
            'updated_at' => null,
        ]);

        $response = $this->withSession([
            'user_role' => 'trainer',
            'user_id' => 9001,
        ])->patch('/trainer/courses/1/modules/10', [
            'module_title' => 'Updated Module',
            'delete_cover_image' => '1',
        ]);

        $response->assertRedirect(
            route('trainer.courses.materials', ['course_id' => 1])
        );
        $response->assertSessionHas(
            'success',
            'Module updated. Cover image reset to default.'
        );

        $this->assertSame(
            'Updated Module',
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('module_title')
        );
        $this->assertNull(
            DB::table('training_module')
                ->where('module_id', 10)
                ->value('cover_image')
        );

        $storedFiles = Storage::disk('public')->allFiles();

        sort($storedFiles);

        $this->assertTrue(
            Storage::disk('public')->exists($unrelatedCertificatePath),
            'Stored public files after module cover reset: '.
            ($storedFiles === [] ? '(empty)' : implode(', ', $storedFiles))
        );

        $this->assertSame(
            [$unrelatedCertificatePath],
            $storedFiles
        );
    }

    private function dropTemporaryTables(): void
    {
        Schema::dropIfExists('training_module');
        Schema::dropIfExists('session_trainer');
        Schema::dropIfExists('course_session');
    }
}
