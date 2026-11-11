<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->dropForeign('attendance_outsider_outsider_id_foreign');
            $table->dropForeign('attendance_outsider_session_id_foreign');
            $table->dropUnique('attendance_outsider_outsider_id_session_id_unique');
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->string('attendance_status', 20)->default('pending')->after('session_id');
            $table->dateTime('scanned_at')->useCurrent()->after('attendance_status');
            $table->string('approvedByStaff', 20)->nullable()->after('remarks');
            $table->dateTime('approved_at')->nullable()->after('approvedByStaff');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->unique(['outsider_id', 'session_id'], 'uq_attendance_outsider_session');
            $table->index('session_id', 'idx_attendance_outsider_session');
            $table->index('approvedByStaff', 'idx_attendance_outsider_approved_by');

            $table->foreign('approvedByStaff', 'fk_attendance_outsider_approver')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('outsider_id', 'fk_attendance_outsider_person')
                ->references('outsider_id')->on('outsider')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('session_id', 'fk_attendance_outsider_session')
                ->references('session_id')->on('course_session')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement("
            ALTER TABLE attendance_outsider
            ADD CONSTRAINT chk_attendance_outsider_1
            CHECK (attendance_status in ('pending','approved','rejected'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance_outsider DROP CONSTRAINT chk_attendance_outsider_1');

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->dropForeign('fk_attendance_outsider_approver');
            $table->dropForeign('fk_attendance_outsider_person');
            $table->dropForeign('fk_attendance_outsider_session');
            $table->dropUnique('uq_attendance_outsider_session');
            $table->dropIndex('idx_attendance_outsider_session');
            $table->dropIndex('idx_attendance_outsider_approved_by');
            $table->dropColumn(['attendance_status', 'scanned_at', 'approvedByStaff', 'approved_at', 'rejection_reason']);
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->unique(['outsider_id', 'session_id']);
            $table->foreign('outsider_id')->references('outsider_id')->on('outsider')->onDelete('cascade');
            $table->foreign('session_id')->references('session_id')->on('course_session')->onDelete('cascade');
        });
    }
};
