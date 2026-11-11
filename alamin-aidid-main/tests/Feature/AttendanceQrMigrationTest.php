<?php

namespace Tests\Feature;

use Tests\TestCase;

class AttendanceQrMigrationTest extends TestCase
{
    public function test_canonical_qr_table_uses_owner_columns_string_session_identity_and_fk(): void
    {
        $schema = (string) file_get_contents(base_path('backend-integration/deployment/trainer-canonical-schema.sql'));

        $this->assertMatchesRegularExpression(
            '/CREATE TABLE `qr_session` \(.*?`qrID` varchar\(20\).*?`qrCode` text.*?`attendanceLink` varchar\(255\).*?`expiryTime` datetime.*?`sessionID` varchar\(20\).*?UNIQUE KEY `uq_qr_session_session` \(`sessionID`\).*?FOREIGN KEY \(`sessionID`\) REFERENCES `course_session` \(`sessionID`\)/s',
            $schema
        );
    }

    public function test_historical_qr_alignment_migrations_defer_to_fyp2_ownership(): void
    {
        foreach ([
            '2026_09_05_000001_create_qr_session_table.php',
            '2026_09_06_000001_align_qr_session_owner_unique_key.php',
        ] as $file) {
            $migration = file_get_contents(base_path('backend-integration/history/retired-migrations/'.$file));
            $this->assertNotFalse($migration);
            $this->assertStringContainsString('fyp2', strtolower((string) $migration));
            $this->assertStringNotContainsString('Schema::create', (string) $migration);
        }
    }
}
