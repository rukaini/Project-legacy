<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const START_MARKER = '-- BEGIN LARAVEL-MIGRATION-BUSINESS-SCHEMA';

    private const END_MARKER = '-- END LARAVEL-MIGRATION-BUSINESS-SCHEMA';

    /** Tables owned by Trainer Management. External owner tables must exist first. */
    private const OWNED_TABLES = [
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
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('The canonical Trainer deployment migration requires MySQL 8.x.');
        }

        $path = base_path('backend-integration/deployment/trainer-canonical-schema.sql');
        $schema = file_get_contents($path);
        if ($schema === false) {
            throw new RuntimeException("Unable to read canonical schema artifact: {$path}");
        }

        $start = strpos($schema, self::START_MARKER);
        $end = strpos($schema, self::END_MARKER);
        if ($start === false || $end === false || $end <= $start) {
            throw new RuntimeException('Canonical business-schema markers are missing or invalid.');
        }

        $businessSchema = substr($schema, $start + strlen(self::START_MARKER), $end - $start - strlen(self::START_MARKER));
        $created = [];
        foreach (preg_split('/;\s*(?:\r\n|\r|\n|$)/', $businessSchema) ?: [] as $statement) {
            $statement = trim($statement);
            if (! preg_match('/CREATE TABLE `([^`]+)`/i', $statement, $matches)
                || ! in_array($matches[1], self::OWNED_TABLES, true)) {
                continue;
            }

            DB::unprepared($statement);
            $created[] = $matches[1];
        }

        $missing = array_values(array_diff(self::OWNED_TABLES, $created));
        if ($missing !== []) {
            throw new RuntimeException('Canonical Trainer table definitions are missing: '.implode(', ', $missing));
        }
    }

    public function down(): void
    {
        foreach ([
            'trainer_certificate', 'trainer_activity_log', 'teaching_plan_comments',
            'teaching_plans', 'course_proposal', 'training_content_block',
            'assessment_question', 'assessment', 'learning_material', 'training_module',
        ] as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }
    }
};
