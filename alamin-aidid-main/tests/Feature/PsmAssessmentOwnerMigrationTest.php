<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PsmAssessmentOwnerMigrationTest extends TestCase
{
    private string $schema;

    protected function setUp(): void
    {
        parent::setUp();

        $contents = file_get_contents(base_path('backend-integration/deployment/trainer-canonical-schema.sql'));
        $this->assertNotFalse($contents);
        $this->schema = (string) $contents;
    }

    #[DataProvider('requiredPsmTables')]
    public function test_canonical_artifact_contains_each_psm_owner_table(string $table): void
    {
        $this->assertStringContainsString("CREATE TABLE `{$table}`", $this->schema);
    }

    public static function requiredPsmTables(): array
    {
        return array_map(fn (string $table): array => [$table], [
            'guru_new',
            'outsider',
            'enroll_teacher',
            'enroll_guru_baru',
            'enroll_outsider',
            'attendance',
            'attendance_guru_baru',
            'attendance_outsider',
            'assessment_submission',
            'training_access_log',
            'training_content_interaction_log',
        ]);
    }

    public function test_master_precedence_is_applied_to_dependent_business_identifiers(): void
    {
        $this->assertMatchesRegularExpression('/`teacherID` varchar\(20\) NOT NULL, `course_id` varchar\(20\) NOT NULL/', $this->schema);
        $this->assertMatchesRegularExpression('/`course_id` varchar\(20\) NOT NULL,\s*`module_id` int NOT NULL, `block_id` bigint NOT NULL/s', $this->schema);
        $this->assertStringContainsString('`graded_by_trainer_id` varchar(20) DEFAULT NULL', $this->schema);
    }

    public function test_legacy_tables_and_data_statements_are_absent(): void
    {
        foreach (['course_enrollment', 'training_attendance_record', 'outsider_participant'] as $legacyTable) {
            $this->assertStringNotContainsString("CREATE TABLE `{$legacyTable}`", $this->schema);
        }

        $this->assertDoesNotMatchRegularExpression('/^\s*INSERT\s+/mi', $this->schema);
    }

    public function test_canonical_artifact_defines_no_default_passwords_or_embedded_credentials(): void
    {
        $this->assertDoesNotMatchRegularExpression(
            '/`(?:password|trainerPassword)`\s+varchar\(255\)\s+NOT NULL\s+DEFAULT\s+/i',
            $this->schema
        );
        $this->assertDoesNotMatchRegularExpression('/^\s*(?:CREATE|ALTER) USER\b|\bIDENTIFIED BY\b/mi', $this->schema);
    }

    public function test_attendance_semantics_are_explicitly_left_uninterpreted(): void
    {
        $this->assertStringContainsString('CANONICAL_SCHEMA_BLOCKED_BY_MISSING_BUSINESS_SEMANTICS', $this->schema);
        $this->assertStringNotContainsString('CREATE FUNCTION `fn_course_attendance_rate`', $this->schema);
    }

    public function test_production_base_migration_owns_only_trainer_business_tables(): void
    {
        $migration = file_get_contents(base_path('database/migrations/2026_05_09_002502_create_trainer_system_tables.php'));
        $this->assertNotFalse($migration);
        preg_match('/private const OWNED_TABLES = \[(.*?)\];/s', (string) $migration, $match);
        preg_match_all("/'([^']+)'/", $match[1] ?? '', $tables);

        $this->assertSame([
            'training_module',
            'learning_material',
            'assessment',
            'assessment_question',
            'training_content_block',
            'course_proposal',
            'teaching_plans',
            'teaching_plan_comments',
            'trainer_activity_log',
            'trainer_certificate',
        ], $tables[1]);

        $databaseSeeder = file_get_contents(base_path('database/seeders/DatabaseSeeder.php'));
        $this->assertNotFalse($databaseSeeder);
        $this->assertStringNotContainsString('$this->call(', (string) $databaseSeeder);
    }
}
