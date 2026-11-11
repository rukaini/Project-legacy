<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->string('attendance_status', 20)->default('pending')->after('session_id');
            $table->dateTime('scanned_at')->useCurrent()->after('attendance_status');
            $table->string('approved_by', 20)->nullable()->after('remarks');
            $table->dateTime('approved_at')->nullable()->after('approved_by');
            $table->text('rejection_reason')->nullable()->after('approved_at');

            $table->foreign('approved_by')->references('hrID')->on('hr_administrator')->nullOnDelete();
            $table->index('approved_by', 'idx_attendance_guru_baru_approved_by');
        });

        DB::statement("
            ALTER TABLE attendance_guru_baru
            ADD CONSTRAINT chk_attendance_guru_baru_status
            CHECK (attendance_status in ('pending','approved','rejected'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_guru_baru DROP CONSTRAINT chk_attendance_guru_baru_status');

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropIndex('idx_attendance_guru_baru_approved_by');
            $table->dropColumn(['attendance_status', 'scanned_at', 'approved_by', 'approved_at', 'rejection_reason']);
        });
    }
};
