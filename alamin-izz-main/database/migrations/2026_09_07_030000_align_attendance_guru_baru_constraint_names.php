<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE attendance_guru_baru DROP CONSTRAINT chk_attendance_guru_baru_status');

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropForeign('attendance_guru_baru_approved_by_foreign');
            $table->dropForeign('attendance_guru_baru_gn_id_foreign');
            $table->dropForeign('attendance_guru_baru_session_id_foreign');
            $table->dropUnique('attendance_guru_baru_gn_id_session_id_unique');
            $table->dropIndex('idx_attendance_guru_baru_approved_by');
            $table->dropIndex('attendance_guru_baru_session_id_foreign');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->renameColumn('approved_by', 'approvedByStaff');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->unique(['gn_id', 'session_id'], 'uq_attendance_guru_baru_session');
            $table->index('session_id', 'idx_attendance_guru_baru_session');
            $table->index('approvedByStaff', 'idx_attendance_guru_baru_approved_by');

            $table->foreign('approvedByStaff', 'fk_attendance_guru_baru_approver')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('gn_id', 'fk_attendance_guru_baru_gn')
                ->references('gn_id')->on('guru_new')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('session_id', 'fk_attendance_guru_baru_session')
                ->references('session_id')->on('course_session')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement("
            ALTER TABLE attendance_guru_baru
            ADD CONSTRAINT chk_attendance_guru_baru_1
            CHECK (attendance_status in ('pending','approved','rejected'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_guru_baru DROP CONSTRAINT chk_attendance_guru_baru_1');

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropForeign('fk_attendance_guru_baru_approver');
            $table->dropForeign('fk_attendance_guru_baru_gn');
            $table->dropForeign('fk_attendance_guru_baru_session');
            $table->dropUnique('uq_attendance_guru_baru_session');
            $table->dropIndex('idx_attendance_guru_baru_session');
            $table->dropIndex('idx_attendance_guru_baru_approved_by');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->renameColumn('approvedByStaff', 'approved_by');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->unique(['gn_id', 'session_id']);
            $table->index('approved_by', 'idx_attendance_guru_baru_approved_by');

            $table->foreign('approved_by')->references('hrID')->on('hr_administrator')->nullOnDelete();
            $table->foreign('gn_id')->references('gn_id')->on('guru_new')->onDelete('cascade');
            $table->foreign('session_id')->references('session_id')->on('course_session')->onDelete('cascade');
        });

        DB::statement("
            ALTER TABLE attendance_guru_baru
            ADD CONSTRAINT chk_attendance_guru_baru_status
            CHECK (attendance_status in ('pending','approved','rejected'))
        ");
    }
};
