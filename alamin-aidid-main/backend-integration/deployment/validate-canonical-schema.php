<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = $argv[1] ?? '';
$mode = $argv[2] ?? '';
if (! preg_match('/\Atrainer_canonical_validation_[a-zA-Z0-9_]+\z/', $database)) {
    fwrite(STDERR, "Use a dedicated database named trainer_canonical_validation_<suffix>.\n");
    exit(2);
}

$connection = config('database.connections.mysql');
$host = (string) ($connection['host'] ?? '127.0.0.1');
$port = (string) ($connection['port'] ?? '3306');
$user = (string) ($connection['username'] ?? '');
$password = (string) ($connection['password'] ?? '');
$socket = (string) ($connection['unix_socket'] ?? '');
$dsn = $socket !== ''
    ? "mysql:unix_socket={$socket};charset=utf8mb4"
    : "mysql:host={$host};port={$port};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$exists = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
$exists->execute([$database]);
$databaseExists = (int) $exists->fetchColumn() !== 0;
if ($databaseExists && $mode !== '--inspect') {
    fwrite(STDERR, "Refusing to overwrite existing database {$database}.\n");
    exit(3);
}
if (! $databaseExists && $mode === '--inspect') {
    fwrite(STDERR, "Cannot inspect missing database {$database}.\n");
    exit(3);
}

$quotedDatabase = '`'.str_replace('`', '``', $database).'`';
if (! $databaseExists) {
    $pdo->exec("CREATE DATABASE {$quotedDatabase} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

if ($mode === '--empty') {
    fwrite(STDOUT, json_encode(['database' => $database, 'result' => 'EMPTY_DATABASE_CREATED'], JSON_PRETTY_PRINT)."\n");
    exit(0);
}

$schemaPath = __DIR__.'/trainer-canonical-schema.sql';
$schemaPdo = new PDO($dsn.';dbname='.$database, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
if ($mode !== '--inspect') {
    $schema = file_get_contents($schemaPath);
    if ($schema === false) {
        fwrite(STDERR, "Unable to read {$schemaPath}.\n");
        exit(4);
    }

    foreach (preg_split('/;\s*(?:\r\n|\r|\n|$)/', $schema) ?: [] as $statement) {
        if (trim($statement) !== '') {
            $schemaPdo->exec($statement);
        }
    }
}

$requiredTables = [
    'migrations', 'users', 'password_reset_tokens', 'sessions', 'cache',
    'cache_locks', 'jobs', 'job_batches', 'failed_jobs',
    'hr_administrator', 'school', 'guru_new', 'teacher', 'staff_edu', 'trainer',
    'course', 'course_session', 'session_trainer', 'qr_session',
    'enroll_teacher', 'enroll_guru_baru', 'enroll_outsider', 'outsider',
    'attendance', 'attendance_guru_baru', 'attendance_outsider',
    'feedback_form', 'feedback_category', 'feedback_question', 'feedback_response',
    'course_participant',
    'assessment_submission', 'training_access_log', 'training_content_interaction_log',
    'trainer_document',
    'training_module', 'learning_material', 'participant_material_access', 'assessment', 'assessment_question',
    'training_content_block', 'course_proposal', 'teaching_plans',
    'teaching_plan_comments', 'trainer_activity_log', 'trainer_certificate',
];
$tableQuery = $schemaPdo->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?');
$tableQuery->execute([$database]);
$tables = $tableQuery->fetchAll(PDO::FETCH_COLUMN);
$missing = array_values(array_diff($requiredTables, $tables));
$legacy = array_values(array_intersect(['course_enrollment', 'training_attendance_record', 'outsider_participant'], $tables));

if ($missing !== [] || $legacy !== []) {
    fwrite(STDERR, json_encode(compact('missing', 'legacy'), JSON_PRETTY_PRINT)."\n");
    exit(6);
}

$foreignKeyQuery = $schemaPdo->prepare(<<<'SQL'
    SELECT
        kcu.COLUMN_NAME,
        kcu.REFERENCED_TABLE_NAME,
        kcu.REFERENCED_COLUMN_NAME,
        rc.UPDATE_RULE,
        rc.DELETE_RULE
    FROM information_schema.KEY_COLUMN_USAGE AS kcu
    INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc
        ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
        AND rc.TABLE_NAME = kcu.TABLE_NAME
        AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
    WHERE kcu.TABLE_SCHEMA = ?
        AND kcu.TABLE_NAME = 'participant_material_access'
        AND kcu.COLUMN_NAME = 'course_id'
    SQL);
$foreignKeyQuery->execute([$database]);
$participantMaterialAccessForeignKey = $foreignKeyQuery->fetch(PDO::FETCH_ASSOC) ?: null;
$expectedParticipantMaterialAccessForeignKey = [
    'COLUMN_NAME' => 'course_id',
    'REFERENCED_TABLE_NAME' => 'course',
    'REFERENCED_COLUMN_NAME' => 'courseID',
    'UPDATE_RULE' => 'CASCADE',
    'DELETE_RULE' => 'CASCADE',
];

if ($participantMaterialAccessForeignKey !== $expectedParticipantMaterialAccessForeignKey) {
    fwrite(STDERR, json_encode([
        'participant_material_access_foreign_key' => $participantMaterialAccessForeignKey,
        'expected' => $expectedParticipantMaterialAccessForeignKey,
    ], JSON_PRETTY_PRINT)."\n");
    exit(7);
}

fwrite(STDOUT, json_encode([
    'database' => $database,
    'tables' => count($tables),
    'legacy_tables' => $legacy,
    'participant_material_access_foreign_key' => $participantMaterialAccessForeignKey,
    'result' => 'PASS',
], JSON_PRETTY_PRINT)."\n");
