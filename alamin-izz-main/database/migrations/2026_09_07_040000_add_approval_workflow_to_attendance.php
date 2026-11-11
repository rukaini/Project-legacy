<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign('attendance_session_id_foreign');
            $table->dropForeign('attendance_teacher_id_foreign');
            $table->dropUnique('attendance_teacher_id_session_id_unique');
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->string('attendance_status', 20)->default('pending')->after('session_id');
            $table->dateTime('scanned_at')->useCurrent()->after('attendance_status');
            $table->string('approvedByStaff', 20)->nullable()->after('remarks');
            $table->dateTime('approved_at')->nullable()->after('approvedByStaff');
            $table->text('rejection_reason')->nullable()->after('approved_at');
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->unique(['teacher_id', 'session_id'], 'uq_attendance_teacher_session');
            $table->index('session_id', 'idx_attendance_session');
            $table->index('approvedByStaff', 'idx_attendance_approved_by');

            $table->foreign('approvedByStaff', 'fk_attendance_approver')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');

            $table->foreign('session_id', 'fk_attendance_session')
                ->references('session_id')->on('course_session')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('teacher_id', 'fk_attendance_teacher')
                ->references('teacherID')->on('teacher')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement("
            ALTER TABLE attendance
            ADD CONSTRAINT chk_attendance_1
            CHECK (attendance_status in ('pending','approved','rejected'))
        ");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE attendance DROP CONSTRAINT chk_attendance_1');

        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign('fk_attendance_approver');
            $table->dropForeign('fk_attendance_session');
            $table->dropForeign('fk_attendance_teacher');
            $table->dropUnique('uq_attendance_teacher_session');
            $table->dropIndex('idx_attendance_session');
            $table->dropIndex('idx_attendance_approved_by');
            $table->dropColumn(['attendance_status', 'scanned_at', 'approvedByStaff', 'approved_at', 'rejection_reason']);
        });

        Schema::table('attendance', function (Blueprint $table) {
            $table->unique(['teacher_id', 'session_id']);
            $table->foreign('session_id')->references('session_id')->on('course_session')->onDelete('cascade');
            $table->foreign('teacher_id')->references('teacherID')->on('teacher')->onDelete('cascade');
        });
    }
};
