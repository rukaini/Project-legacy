<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff identifiers (approvedByStaff / createdByStaff / pdfGeneratedByStaff) are issued by an
 * external staff system, not this app's hr_administrator table. Drop the foreign keys added
 * earlier so these columns are plain, unconstrained identifiers; keep the columns and indexes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->dropForeign('fk_attendance_approver');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->dropForeign('fk_attendance_guru_baru_approver');
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->dropForeign('fk_attendance_outsider_approver');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->dropForeign('fk_feedback_form_staff');
            $table->dropForeign('fk_feedback_form_pdf_staff');
        });
    }

    public function down(): void
    {
        Schema::table('attendance', function (Blueprint $table) {
            $table->foreign('approvedByStaff', 'fk_attendance_approver')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');
        });

        Schema::table('attendance_guru_baru', function (Blueprint $table) {
            $table->foreign('approvedByStaff', 'fk_attendance_guru_baru_approver')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');
        });

        Schema::table('attendance_outsider', function (Blueprint $table) {
            $table->foreign('approvedByStaff', 'fk_attendance_outsider_approver')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');
        });

        Schema::table('feedback_form', function (Blueprint $table) {
            $table->foreign('createdByStaff', 'fk_feedback_form_staff')
                ->references('hrID')->on('hr_administrator')
                ->onUpdate('cascade');

            $table->foreign('pdfGeneratedByStaff', 'fk_feedback_form_pdf_staff')
                ->references('hrID')->on('hr_administrator')
                ->onDelete('set null')->onUpdate('cascade');
        });
    }
};
