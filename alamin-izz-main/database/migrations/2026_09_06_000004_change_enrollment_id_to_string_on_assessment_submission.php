<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * enrollment_id dual-purposes as either enroll_guru_baru.enrollment_id (a
     * real bigint) or, for auto-enrolled series courses, the guru_new.gn_id
     * of the submitting Guru Baru — which is now a string (e.g. 'GN0001').
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE assessment_submission MODIFY enrollment_id VARCHAR(20) NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE assessment_submission MODIFY enrollment_id BIGINT UNSIGNED NOT NULL');
    }
};
