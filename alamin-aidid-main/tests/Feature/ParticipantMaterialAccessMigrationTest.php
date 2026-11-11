<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ParticipantMaterialAccessMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('course', function (Blueprint $table): void {
            $table->string('courseID', 20)->primary();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('participant_material_access');
        Schema::dropIfExists('course');

        parent::tearDown();
    }

    public function test_migration_creates_the_expected_columns_primary_key_defaults_and_foreign_key(): void
    {
        $migrationPath = base_path('database/migrations/2026_10_13_000001_create_participant_material_access_table.php');
        $migrationDefinition = file_get_contents($migrationPath);
        $this->assertIsString($migrationDefinition);
        $this->assertStringContainsString("string('course_id', 20)->primary()", $migrationDefinition);

        $migration = require $migrationPath;
        $migration->up();

        $this->assertTrue(Schema::hasColumns('participant_material_access', [
            'course_id', 'is_enabled', 'access_until', 'created_at', 'updated_at',
        ]));
        $columns = collect(DB::select("PRAGMA table_info('participant_material_access')"))->keyBy('name');
        $this->assertSame('varchar', strtolower($columns['course_id']->type));
        $this->assertSame(1, $columns['course_id']->pk);
        $this->assertSame('0', trim((string) $columns['is_enabled']->dflt_value, "'\""));
        $this->assertSame(0, $columns['access_until']->notnull);

        $foreignKey = collect(DB::select("PRAGMA foreign_key_list('participant_material_access')"))
            ->firstWhere('from', 'course_id');
        $this->assertNotNull($foreignKey);
        $this->assertSame('course', $foreignKey->table);
        $this->assertSame('courseID', $foreignKey->to);
        $this->assertSame('CASCADE', $foreignKey->on_update);
        $this->assertSame('CASCADE', $foreignKey->on_delete);

        DB::table('course')->insert(['courseID' => 'EXAMPLE-1']);
        DB::table('participant_material_access')->insert(['course_id' => 'EXAMPLE-1']);

        $this->assertDatabaseHas('participant_material_access', [
            'course_id' => 'EXAMPLE-1', 'is_enabled' => 0, 'access_until' => null,
        ]);
        $this->expectException(QueryException::class);
        DB::table('participant_material_access')->insert(['course_id' => 'EXAMPLE-1']);
    }

    public function test_deployment_schema_artifacts_declare_the_same_physical_foreign_key(): void
    {
        foreach ([
            'backend-integration/deployment/trainer-owned-schema.sql',
            'backend-integration/deployment/trainer-canonical-schema.sql',
        ] as $path) {
            $schema = file_get_contents(base_path($path));
            $this->assertIsString($schema);
            $this->assertMatchesRegularExpression(
                '/CREATE TABLE(?: IF NOT EXISTS)? `participant_material_access` \((.*?)\) ENGINE=/s',
                $schema
            );
            preg_match(
                '/CREATE TABLE(?: IF NOT EXISTS)? `participant_material_access` \((.*?)\) ENGINE=/s',
                $schema,
                $matches
            );
            $tableDefinition = $matches[1];

            $this->assertStringContainsString('`course_id` varchar(20)', $tableDefinition);
            $this->assertStringContainsString('PRIMARY KEY (`course_id`)', $tableDefinition);
            $this->assertStringContainsString(
                'FOREIGN KEY (`course_id`) REFERENCES `course` (`courseID`)',
                $tableDefinition
            );
            $this->assertStringContainsString('ON UPDATE CASCADE', $tableDefinition);
            $this->assertStringContainsString('ON DELETE CASCADE', $tableDefinition);
        }
    }

    public function test_foreign_key_rejects_orphans_and_cascades_course_updates_and_deletes(): void
    {
        $migration = require base_path('database/migrations/2026_10_13_000001_create_participant_material_access_table.php');
        $migration->up();

        try {
            DB::table('participant_material_access')->insert(['course_id' => 'MISSING']);
            $this->fail('An orphaned participant material access row was accepted.');
        } catch (QueryException) {
            $this->assertDatabaseMissing('participant_material_access', ['course_id' => 'MISSING']);
        }

        DB::table('course')->insert(['courseID' => 'EXAMPLE-1']);
        DB::table('participant_material_access')->insert(['course_id' => 'EXAMPLE-1']);

        DB::table('course')->where('courseID', 'EXAMPLE-1')->update(['courseID' => 'EXAMPLE-2']);
        $this->assertDatabaseHas('participant_material_access', ['course_id' => 'EXAMPLE-2']);

        DB::table('course')->where('courseID', 'EXAMPLE-2')->delete();
        $this->assertDatabaseMissing('participant_material_access', ['course_id' => 'EXAMPLE-2']);
    }
}
