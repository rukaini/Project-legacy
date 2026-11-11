<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeachingPlanMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('teaching_plan_comments');
        Schema::dropIfExists('teaching_plans');

        parent::tearDown();
    }

    public function test_retired_teaching_plan_migrations_do_not_duplicate_the_canonical_base_schema(): void
    {
        $historyPath = base_path('backend-integration/history/retired-migrations');
        $plansMigration = require $historyPath.'/2026_08_30_000002_create_teaching_plans_table.php';
        $commentsMigration = require $historyPath.'/2026_08_30_000003_create_teaching_plan_comments_table.php';
        $seenMigration = require $historyPath.'/2026_08_31_000001_add_seen_by_trainer_at_to_teaching_plan_comments_table.php';

        $plansMigration->up();
        $commentsMigration->up();
        $seenMigration->up();

        $this->assertFalse(Schema::hasTable('teaching_plans'));
        $this->assertFalse(Schema::hasTable('teaching_plan_comments'));

        $schema = file_get_contents(base_path('backend-integration/deployment/trainer-canonical-schema.sql'));
        $this->assertIsString($schema);
        $this->assertStringContainsString('CREATE TABLE `teaching_plans`', $schema);
        $this->assertStringContainsString('`course_id` varchar(20) NOT NULL', $schema);
        $this->assertStringContainsString('`trainer_id` varchar(20) NOT NULL', $schema);
        $this->assertStringContainsString('UNIQUE KEY `teaching_plans_course_id_trainer_id_unique` (`course_id`,`trainer_id`)', $schema);
        $this->assertStringContainsString('CREATE TABLE `teaching_plan_comments`', $schema);
        $this->assertStringContainsString('`reviewer_staff_id` varchar(20) NOT NULL', $schema);
        $this->assertStringContainsString('`seen_by_trainer_at` timestamp NULL', $schema);

        $seenMigration->down();
        $commentsMigration->down();
        $plansMigration->down();
        $this->assertFalse(Schema::hasTable('teaching_plan_comments'));
        $this->assertFalse(Schema::hasTable('teaching_plans'));
    }
}
